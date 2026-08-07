<?php

declare(strict_types=1);

namespace App\Services\Llm;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Multi-provider LLM client.
 *
 * Supports a primary provider and an optional fallback provider. If the primary
 * call fails (quota exhausted, network error, auth failure), the fallback
 * provider is tried automatically. Providers currently supported:
 *
 *  - google : Gemini generateContent endpoint (default, keeps existing behaviour)
 *  - openai : any OpenAI-compatible chat/completions API (Groq, OpenRouter,
 *             OpenAI, DeepSeek, ...) configured via LLM_BASE_URL
 *
 * Configuration lives in config/services.php under 'llm' (primary) and
 * 'llm.fallback'. Callers may override the primary provider per-call by passing
 * a 'provider' option (used by translation, which has its own driver config).
 */
class LlmService
{
    public const DRIVER_GOOGLE = 'google';

    public const DRIVER_OPENAI = 'openai';

    /**
     * Run a prompt through the LLM. Tries the primary provider, then the
     * fallback provider, honouring rate-limit backoff on 429 responses.
     *
     * @param  array<string, mixed>  $options  temperature, max_tokens, model,
     *                                         provider (primary override),
     *                                         fallback (fallback override),
     *                                         retries
     *
     * @throws \RuntimeException when every configured provider fails
     */
    public function generate(string $prompt, array $options = []): string
    {
        $primary = $options['provider'] ?? $this->primaryProvider();
        $fallback = $options['fallback'] ?? $this->fallbackProvider();

        $retries = (int) ($options['retries'] ?? config('services.translation.retry_attempts', 3));
        $errors = [];

        if ($this->isConfigured($primary)) {
            try {
                return $this->callWithRetry($primary, $prompt, $options, $retries);
            } catch (\Throwable $e) {
                $errors[] = $this->describe($primary).': '.$e->getMessage();
                Log::channel('translation')->warning('Primary LLM provider failed, trying fallback', [
                    'driver' => $primary['driver'] ?? null,
                    'model' => $primary['model'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($this->isConfigured($fallback)) {
            try {
                return $this->callWithRetry($fallback, $prompt, $options, max(1, $retries - 1));
            } catch (\Throwable $e) {
                $errors[] = $this->describe($fallback).': '.$e->getMessage();
            }
        }

        throw new \RuntimeException('All LLM providers failed: '.implode(' | ', $errors ?: ['no provider configured']));
    }

    /**
     * True when at least one provider (primary or fallback) is configured.
     */
    public function isAvailable(?array $provider = null): bool
    {
        return $this->isConfigured($provider ?? $this->primaryProvider())
            || $this->isConfigured($this->fallbackProvider());
    }

    protected function callWithRetry(array $provider, string $prompt, array $options, int $maxAttempts): string
    {
        $attempts = 0;

        do {
            $attempts++;
            $response = $this->callDriver($provider, $prompt, $options);

            if ($response->successful()) {
                $text = $this->extractText($provider, $response);

                if ($text !== '') {
                    return $text;
                }

                throw new \RuntimeException('Empty LLM response');
            }

            if ($response->status() === 429 && $attempts < $maxAttempts) {
                $retryDelay = $this->retryDelayFrom($provider, $response);

                Log::channel('translation')->warning('LLM provider rate limited, backing off', [
                    'driver' => $provider['driver'] ?? null,
                    'model' => $provider['model'] ?? null,
                    'attempt' => $attempts,
                    'retry_in' => $retryDelay,
                ]);

                sleep($retryDelay);

                continue;
            }

            throw new \RuntimeException(sprintf(
                'LLM API error: %d %s',
                $response->status(),
                mb_substr($response->body(), 0, 500)
            ));
        } while ($attempts < $maxAttempts);

        throw new \RuntimeException('LLM rate limit exceeded after '.$maxAttempts.' attempts');
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function callDriver(array $provider, string $prompt, array $options): Response
    {
        $driver = $provider['driver'] ?? self::DRIVER_GOOGLE;

        return match ($driver) {
            self::DRIVER_OPENAI => $this->callOpenAi($provider, $prompt, $options),
            default => $this->callGoogle($provider, $prompt, $options),
        };
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function callGoogle(array $provider, string $prompt, array $options): Response
    {
        $model = $options['model'] ?? $provider['model'] ?? 'gemini-2.5-flash';
        $timeout = (int) ($options['timeout'] ?? $provider['timeout'] ?? config('services.llm.timeout', 90));

        return Http::timeout($timeout)
            ->withQueryParameters(['key' => $provider['api_key']])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => $options['temperature'] ?? 0.2,
                    'maxOutputTokens' => $options['max_tokens'] ?? 4096,
                ],
            ]);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function callOpenAi(array $provider, string $prompt, array $options): Response
    {
        $baseUrl = rtrim((string) $provider['base_url'], '/');
        $model = $options['model'] ?? $provider['model'];
        $timeout = (int) ($options['timeout'] ?? $provider['timeout'] ?? config('services.llm.timeout', 90));

        return Http::timeout($timeout)
            ->withToken((string) $provider['api_key'])
            ->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $options['temperature'] ?? 0.2,
                'max_tokens' => $options['max_tokens'] ?? 4096,
            ]);
    }

    protected function extractText(array $provider, Response $response): string
    {
        $driver = $provider['driver'] ?? self::DRIVER_GOOGLE;

        $text = $driver === self::DRIVER_OPENAI
            ? $response->json('choices.0.message.content')
            : $response->json('candidates.0.content.parts.0.text');

        return (string) $text;
    }

    protected function retryDelayFrom(array $provider, Response $response): int
    {
        if (($provider['driver'] ?? null) === self::DRIVER_OPENAI) {
            $after = $response->header('Retry-After');

            if ($after && (int) $after > 0) {
                return min((int) $after, 60);
            }

            return 30;
        }

        foreach (($response->json('error.details') ?? []) as $detail) {
            if (isset($detail['retryDelay'])) {
                $seconds = (int) preg_replace('/[^0-9]/', '', (string) $detail['retryDelay']) ?: 30;

                return min($seconds, 60);
            }
        }

        return 30;
    }

    protected function isConfigured(?array $provider): bool
    {
        if (! $provider || empty($provider['api_key'])) {
            return false;
        }

        $driver = $provider['driver'] ?? self::DRIVER_GOOGLE;

        if ($driver === self::DRIVER_OPENAI && empty($provider['base_url'])) {
            return false;
        }

        return true;
    }

    protected function primaryProvider(): array
    {
        return [
            'driver' => config('services.llm.driver', self::DRIVER_GOOGLE),
            'api_key' => config('services.llm.api_key'),
            'model' => config('services.llm.model', 'gemini-2.5-flash'),
            'base_url' => config('services.llm.base_url'),
            'timeout' => config('services.llm.timeout', 90),
        ];
    }

    protected function fallbackProvider(): array
    {
        return [
            'driver' => config('services.llm.fallback.driver'),
            'api_key' => config('services.llm.fallback.api_key'),
            'model' => config('services.llm.fallback.model'),
            'base_url' => config('services.llm.fallback.base_url'),
            'timeout' => config('services.llm.fallback.timeout', 90),
        ];
    }

    protected function describe(array $provider): string
    {
        return ($provider['driver'] ?? 'unknown').'/'.($provider['model'] ?? '?');
    }
}
