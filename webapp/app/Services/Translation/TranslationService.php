<?php

declare(strict_types=1);

namespace App\Services\Translation;

use App\Models\News;
use App\Models\NewsTranslation;
use App\Services\Llm\LlmService;
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

    /**
     * Ensure a translation exists for the given news in the requested locale.
     * Returns the translation model, or null if translation failed.
     */
    public function translateForLocale(News $news, string $locale): ?NewsTranslation
    {
        $sourceLang = $news->lang ?: 'en';

        $existing = $news->translation($locale);

        // Guard against a stale/misplaced "translation" that is actually just a
        // verbatim copy of the original content (created while the article was
        // wrongly tagged as the target language). In that case, re-translate
        // instead of delivering untranslated text to the member.
        $isStaleCopy = $existing
            && $existing->status === 'translated'
            && $locale !== $sourceLang
            && trim((string) $existing->title) === trim((string) $news->title);

        if ($existing && $existing->status === 'translated' && ! $isStaleCopy) {
            return $existing;
        }

        if ($locale === $sourceLang) {
            return $this->copyOriginal($news, $locale)['translation'] ?? null;
        }

        try {
            $this->translateTo($news, $locale, $sourceLang);

            return $news->translation($locale);
        } catch (\Throwable $e) {
            $this->markFailed($news, $locale, $e->getMessage());
            Log::channel('translation')->error('Translation failed for locale', [
                'news_id' => $news->id,
                'locale' => $locale,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Ensure a Thai-ready translation exists for many news items at once.
     * Articles that are already valid for the target locale are left untouched;
     * the rest are translated in a single API call per batch, which drastically
     * reduces the number of Gemini requests (staying within the free-tier quota).
     */
    public function translateBatch(iterable $news, string $locale): void
    {
        $pending = [];

        foreach ($news as $item) {
            $sourceLang = $item->lang ?: 'en';

            $existing = $item->translation($locale);
            $isStaleCopy = $existing
                && $existing->status === 'translated'
                && $locale !== $sourceLang
                && trim((string) $existing->title) === trim((string) $item->title);

            if ($existing && $existing->status === 'translated' && ! $isStaleCopy) {
                continue;
            }

            if ($locale === $sourceLang) {
                $this->copyOriginal($item, $locale);

                continue;
            }

            $pending[$item->id] = $item;
        }

        if (empty($pending)) {
            return;
        }

        // Translate in chunks to keep each prompt small yet use ~1 API call per chunk.
        $chunkSize = max(1, (int) config('services.translation.batch_size', 8));

        foreach (array_chunk($pending, $chunkSize, true) as $chunk) {
            $this->translateToChunk($chunk, $locale);
        }
    }

    protected function translateTo(News $news, string $locale, string $sourceLang): array
    {
        $text = $this->callGenerateContent($this->googlePayload($news, $locale));
        [$title, $summary, $body] = $this->parseTranslated($text);

        return $this->storeTranslation($news, $locale, $title, $summary, $body);
    }

    protected function translateToChunk(array $chunk, string $locale): void
    {
        $text = $this->callGenerateContent($this->googleBatchPayload($chunk, $locale));

        foreach ($this->parseBatchTranslated($text, array_keys($chunk)) as $id => $parts) {
            $news = $chunk[$id] ?? null;
            if (! $news) {
                continue;
            }
            [$title, $summary, $body] = $parts;
            $this->storeTranslation($news, $locale, $title, $summary, $body);
        }
    }

    /**
     * Call the configured LLM provider (Google Gemini by default) through the
     * LlmService abstraction, with the translation driver config as the primary
     * provider and the shared llm.fallback as the automatic failover.
     */
    protected function callGenerateContent(string $payload): string
    {
        $llm = app(LlmService::class);

        return $llm->generate($payload, [
            'provider' => [
                'driver' => config('services.translation.driver', 'google'),
                'api_key' => config('services.translation.api_key'),
                'model' => config('services.translation.model', 'gemini-2.5-flash'),
                'base_url' => config('services.translation.base_url'),
                'timeout' => config('services.llm.timeout', 90),
            ],
            'temperature' => 0.2,
            'max_tokens' => 8192,
            'retries' => (int) config('services.translation.retry_attempts', 3),
        ]);
    }

    protected function googlePayload(News $news, string $locale): string
    {
        $localeName = [
            'th' => 'Thai',
            'en' => 'English',
            'zh' => 'Chinese',
        ][$locale];

        return "You are a professional news translator. Translate the following news article into {$localeName}. "
            .'Keep the meaning faithful, preserve proper nouns where appropriate, and use natural journalistic language. '
            .'Return ONLY a JSON object with keys: title, summary, body. '
            ."Do not wrap in markdown code fences.\n\n"
            ."--- TITLE ---\n{$news->title}\n"
            ."--- SUMMARY ---\n".($news->summary ?? '')."\n"
            ."--- BODY ---\n".($news->body ?? '')."\n";
    }

    /**
     * Build a single prompt that asks the model to translate several articles
     * at once, keyed by numeric ID, so multiple news items share one API call.
     */
    protected function googleBatchPayload(array $chunk, string $locale): string
    {
        $localeName = [
            'th' => 'Thai',
            'en' => 'English',
            'zh' => 'Chinese',
        ][$locale];

        $articles = '';
        foreach ($chunk as $id => $news) {
            $articles .= "[{$id}]\n"
                ."TITLE: {$news->title}\n"
                .'SUMMARY: '.($news->summary ?? '')."\n"
                .'BODY: '.($news->body ?? '')."\n\n";
        }

        return "You are a professional news translator. Translate each of the following news articles into {$localeName}. "
            .'Keep the meaning faithful, preserve proper nouns where appropriate, and use natural journalistic language. '
            .'Return ONLY a JSON object where each key is the article id and each value is an object with keys: title, summary, body. '
            ."Do not wrap in markdown code fences. Include every article id.\n\n"
            .$articles;
    }

    /**
     * Parse a batch response into [news_id => [title, summary, body], ...].
     * Falls back to treating any single JSON object as one article's translation.
     */
    protected function parseBatchTranslated(string $text, array $ids): array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $decoded = json_decode($text, true);

        if (is_array($decoded) && count($decoded) > 0) {
            $out = [];

            foreach ($decoded as $key => $item) {
                if (! is_array($item) || ! isset($item['title'])) {
                    continue;
                }

                $resolved = in_array((string) $key, $ids, true) ? (string) $key : $this->firstKnownId((string) $key, $ids);

                if ($resolved !== null) {
                    $out[$resolved] = [
                        (string) ($item['title'] ?? ''),
                        (string) ($item['summary'] ?? ''),
                        (string) ($item['body'] ?? ''),
                    ];
                }
            }

            if ($out) {
                return $out;
            }

            // Single-object response (model collapsed the batch).
            if (isset($decoded['title'])) {
                $id = $ids[0] ?? null;
                if ($id !== null) {
                    return [$id => [
                        (string) ($decoded['title'] ?? ''),
                        (string) ($decoded['summary'] ?? ''),
                        (string) ($decoded['body'] ?? ''),
                    ]];
                }
            }
        }

        return [];
    }

    protected function firstKnownId(string $key, array $ids): ?string
    {
        foreach ($ids as $id) {
            if (str_contains((string) $key, (string) $id)) {
                return (string) $id;
            }
        }

        return null;
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

        return ['status' => 'translated', 'translation_id' => $translation->id, 'translation' => $translation];
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
