<?php

declare(strict_types=1);

namespace App\Services\Translation;

use App\Models\News;
use App\Models\NewsTranslation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    protected const TARGET_LOCALES = ['th', 'en', 'zh'];

    public function translateNews(News $news): array
    {
        $sourceLang = $news->lang ?: 'en';
        $results = [];

        foreach (self::TARGET_LOCALES as $locale) {
            if ($locale === $sourceLang) {
                $results[$locale] = $this->copyOriginal($news, $locale);
                continue;
            }

            try {
                $results[$locale] = $this->translateTo($news, $locale, $sourceLang);
            } catch (\Throwable $e) {
                $this->markFailed($news, $locale, $e->getMessage());
                Log::channel('translation')->error('Translation failed', [
                    'news_id' => $news->id,
                    'locale' => $locale,
                    'error' => $e->getMessage(),
                ]);
                $results[$locale] = ['status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        $news->update(['status' => 'translated']);

        return $results;
    }

    public function translatePending(?int $limit = 20): array
    {
        $results = [];

        News::where('status', 'new')
            ->whereDoesntHave('translations', function ($query): void {
                $query->where('status', 'translated');
            })
            ->limit($limit)
            ->get()
            ->each(function (News $news) use (&$results): void {
                $results[$news->id] = $this->translateNews($news);
            });

        return $results;
    }

    protected function translateTo(News $news, string $locale, string $sourceLang): array
    {
        $driver = config('services.translation.driver', 'google');

        if ($driver !== 'google') {
            throw new \RuntimeException("Unsupported translation driver: {$driver}");
        }

        $payload = $this->googlePayload($news, $locale);

        $apiKey = config('services.translation.api_key');
        $model = config('services.translation.model', 'gemini-1.5-flash');

        $response = Http::timeout(90)
            ->withQueryParameters(['key' => $apiKey])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $payload],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 8192,
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Translation API error: {$response->status()} {$response->body()}");
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! $text) {
            throw new \RuntimeException('Empty translation response');
        }

        [$title, $summary, $body] = $this->parseTranslated($text);

        return $this->storeTranslation($news, $locale, $title, $summary, $body);
    }

    protected function googlePayload(News $news, string $locale): string
    {
        $localeName = [
            'th' => 'Thai',
            'en' => 'English',
            'zh' => 'Chinese',
        ][$locale];

        return "You are a professional news translator. Translate the following news article into {$localeName}. "
            ."Keep the meaning faithful, preserve proper nouns where appropriate, and use natural journalistic language. "
            ."Return ONLY a JSON object with keys: title, summary, body. "
            ."Do not wrap in markdown code fences.\n\n"
            ."--- TITLE ---\n{$news->title}\n"
            ."--- SUMMARY ---\n".($news->summary ?? '')."\n"
            ."--- BODY ---\n".($news->body ?? '')."\n";
    }

    protected function parseTranslated(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $decoded = json_decode($text, true);

        if (is_array($decoded) && isset($decoded['title'])) {
            return [
                (string) ($decoded['title'] ?? ''),
                (string) ($decoded['summary'] ?? ''),
                (string) ($decoded['body'] ?? ''),
            ];
        }

        // Fallback: split heuristically
        $title = '';
        $rest = $text;
        if (preg_match('/^(.+?)\n/', $text, $m)) {
            $title = trim($m[1]);
            $rest = trim(substr($text, strlen($m[0])));
        }

        return [$title, '', $rest];
    }

    protected function copyOriginal(News $news, string $locale): array
    {
        return $this->storeTranslation($news, $locale, $news->title, $news->summary, $news->body);
    }

    protected function storeTranslation(News $news, string $locale, string $title, ?string $summary, ?string $body): array
    {
        $translation = NewsTranslation::updateOrCreate(
            ['news_id' => $news->id, 'locale' => $locale],
            [
                'title' => $title,
                'summary' => $summary,
                'body' => $body,
                'status' => 'translated',
                'error_message' => null,
                'translated_at' => now(),
            ]
        );

        return ['status' => 'translated', 'translation_id' => $translation->id];
    }

    protected function markFailed(News $news, string $locale, string $error): void
    {
        NewsTranslation::updateOrCreate(
            ['news_id' => $news->id, 'locale' => $locale],
            [
                'title' => $news->title,
                'status' => 'failed',
                'error_message' => $error,
            ]
        );
    }
}
