<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Llm;

use App\Services\Llm\LlmService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LlmServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.llm.api_key', null);
        Config::set('services.llm.fallback.api_key', null);
    }

    public function test_generate_with_google_driver_calls_gemini_endpoint(): void
    {
        Config::set('services.llm.driver', 'google');
        Config::set('services.llm.api_key', 'test-gemini-key');
        Config::set('services.llm.model', 'gemini-2.5-flash');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Hello Thai']]]],
                ],
            ]),
        ]);

        $service = app(LlmService::class);
        $result = $service->generate('Translate: Hello');

        $this->assertSame('Hello Thai', $result);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent')
                && $request['contents'][0]['parts'][0]['text'] === 'Translate: Hello'
                && $request['generationConfig']['temperature'] === 0.2;
        });
    }

    public function test_generate_with_openai_driver_calls_chat_completions(): void
    {
        Config::set('services.llm.driver', 'openai');
        Config::set('services.llm.api_key', 'test-openai-key');
        Config::set('services.llm.model', 'llama-3.1-8b-instant');
        Config::set('services.llm.base_url', 'https://api.groq.com/openai/v1');

        Http::fake([
            'https://api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'สวัสดี']]],
            ]),
        ]);

        $service = app(LlmService::class);
        $result = $service->generate('Translate: Hello');

        $this->assertSame('สวัสดี', $result);

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/chat/completions')
                && $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-openai-key')
                && $request['model'] === 'llama-3.1-8b-instant'
                && $request['messages'][0]['content'] === 'Translate: Hello';
        });
    }

    public function test_generate_falls_back_to_secondary_provider_on_primary_failure(): void
    {
        Config::set('services.llm.driver', 'openai');
        Config::set('services.llm.api_key', 'primary-key');
        Config::set('services.llm.model', 'primary-model');
        Config::set('services.llm.base_url', 'https://primary.example.com/v1');

        Config::set('services.llm.fallback.driver', 'google');
        Config::set('services.llm.fallback.api_key', 'fallback-key');
        Config::set('services.llm.fallback.model', 'gemini-2.5-flash');

        Http::fake([
            'https://primary.example.com/*' => Http::response([], 429),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Fallback answer']]]],
                ],
            ]),
        ]);

        $service = app(LlmService::class);
        $result = $service->generate('Hello');

        $this->assertSame('Fallback answer', $result);
    }

    public function test_generate_throws_when_all_providers_fail(): void
    {
        Config::set('services.llm.driver', 'openai');
        Config::set('services.llm.api_key', 'primary-key');
        Config::set('services.llm.base_url', 'https://primary.example.com/v1');

        Config::set('services.llm.fallback.driver', 'openai');
        Config::set('services.llm.fallback.api_key', 'fallback-key');
        Config::set('services.llm.fallback.base_url', 'https://fallback.example.com/v1');

        Http::fake([
            'https://primary.example.com/*' => Http::response([], 500),
            'https://fallback.example.com/*' => Http::response([], 503),
        ]);

        $service = app(LlmService::class);

        $this->expectException(\RuntimeException::class);

        $service->generate('Hello');
    }

    public function test_generate_throws_when_no_provider_configured(): void
    {
        $service = app(LlmService::class);

        $this->expectException(\RuntimeException::class);

        $service->generate('Hello');
    }

    public function test_is_available_false_without_keys(): void
    {
        $service = app(LlmService::class);

        $this->assertFalse($service->isAvailable());
    }

    public function test_is_available_true_with_fallback_key_only(): void
    {
        Config::set('services.llm.fallback.driver', 'google');
        Config::set('services.llm.fallback.api_key', 'fallback-key');

        $service = app(LlmService::class);

        $this->assertTrue($service->isAvailable());
    }

    public function test_openai_driver_requires_base_url(): void
    {
        Config::set('services.llm.driver', 'openai');
        Config::set('services.llm.api_key', 'key');
        Config::set('services.llm.base_url', null);

        $service = app(LlmService::class);

        $this->expectException(\RuntimeException::class);

        $service->generate('Hello');
    }

    public function test_translation_custom_provider_option_is_used(): void
    {
        Config::set('services.llm.driver', 'openai');
        Config::set('services.llm.api_key', 'global-key');
        Config::set('services.llm.base_url', 'https://global.example.com/v1');

        Http::fake([
            'https://api.translate.example.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'translated']]],
            ]),
        ]);

        $service = app(LlmService::class);
        $result = $service->generate('Hello', [
            'provider' => [
                'driver' => 'openai',
                'api_key' => 'translation-key',
                'model' => 'translation-model',
                'base_url' => 'https://api.translate.example.com/v1',
            ],
        ]);

        $this->assertSame('translated', $result);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.translate.example.com/v1/chat/completions');
    }

    public function test_google_429_backoff_then_success(): void
    {
        Config::set('services.llm.driver', 'google');
        Config::set('services.llm.api_key', 'key');
        Config::set('services.llm.model', 'gemini-2.5-flash');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([], 429, ['Retry-After' => '1'])
                ->push([
                    'candidates' => [
                        ['content' => ['parts' => [['text' => 'after backoff']]]],
                    ],
                ]),
        ]);

        $service = app(LlmService::class);
        $result = $service->generate('Hello', ['retries' => 2]);

        $this->assertSame('after backoff', $result);
        Http::assertSentCount(2);
    }

    public function test_network_connection_error_falls_back(): void
    {
        Config::set('services.llm.driver', 'openai');
        Config::set('services.llm.api_key', 'primary-key');
        Config::set('services.llm.base_url', 'https://primary.example.com/v1');

        Config::set('services.llm.fallback.driver', 'google');
        Config::set('services.llm.fallback.api_key', 'fallback-key');

        Http::fake([
            'https://primary.example.com/*' => fn () => throw new ConnectionException('timeout'),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'recovered']]]],
                ],
            ]),
        ]);

        $service = app(LlmService::class);
        $result = $service->generate('Hello');

        $this->assertSame('recovered', $result);
    }
}
