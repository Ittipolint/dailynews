<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Models\News;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GraphRagService
{
    public function ask(string $question, string $locale = 'th'): array
    {
        $entities = $this->extractEntities($question);
        $keywords = $this->extractKeywords($question);

        // 1. Retrieve candidate news (vector / keyword / entity based)
        $candidates = $this->retrieve($question, $keywords, $entities, $locale);

        // 2. Build context for the LLM
        $context = $this->buildContext($candidates, $locale);

        // 3. Generate answer with citations
        $answer = $this->generateAnswer($question, $context, $locale);

        return [
            'answer' => $answer['answer'] ?? $answer,
            'sources' => $candidates->map(fn (News $news) => [
                'id' => $news->id,
                'title' => $news->translatedTitle($locale),
                'url' => $news->source_url,
                'source' => $news->source?->name,
                'published_at' => $news->published_at?->toIso8601String(),
                'relevance' => $news->relevance_score ?? 0,
            ])->values()->all(),
            'entities' => $entities,
            'keywords' => $keywords,
        ];
    }

    protected function retrieve(string $question, array $keywords, array $entities, string $locale)
    {
        $query = News::with('source')
            ->where('status', '!=', 'failed')
            ->where('published_at', '>=', now()->subDays(30));

        $terms = array_merge($keywords, $entities);

        if (empty($terms)) {
            return $query->orderByDesc('published_at')->limit(5)->get();
        }

        $query->where(function ($q) use ($terms, $locale): void {
            foreach ($terms as $term) {
                $q->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('summary', 'like', "%{$term}%")
                    ->orWhere('body', 'like', "%{$term}%")
                    ->orWhereHas('translations', function ($tq) use ($term, $locale): void {
                        $tq->where('locale', $locale)
                            ->where(fn ($inner) => $inner
                                ->where('title', 'like', "%{$term}%")
                                ->orWhere('summary', 'like', "%{$term}%")
                                ->orWhere('body', 'like', "%{$term}%"));
                    });
            }
        });

        return $query->orderByDesc('published_at')->limit(6)->get();
    }

    protected function buildContext(iterable $candidates, string $locale): string
    {
        $context = '';

        foreach ($candidates as $i => $news) {
            $context .= "[{$i}] ".$news->translatedTitle($locale)
                .' (Source: '.($news->source?->name ?? 'unknown').', '
                .($news->published_at?->toDateString() ?? 'n/a').")\n"
                .($news->translatedSummary($locale) ?: $news->summary)
                ."\nURL: {$news->source_url}\n\n";
        }

        return $context;
    }

    protected function generateAnswer(string $question, string $context, string $locale): array
    {
        $apiKey = config('services.llm.api_key');
        $model = config('services.llm.model', 'gemini-1.5-flash');

        $prompt = "You are a helpful news assistant for the DailyNews platform. "
            ."Answer the user's question in the requested language using ONLY the news context provided below. "
            ."Cite sources by their [index] number at the end of relevant sentences. "
            ."If the context does not contain enough information, say so honestly.\n\n"
            ."News context:\n{$context}\n"
            ."User question: {$question}\n"
            ."Answer:";

        if (! $apiKey) {
            // Fallback: return top candidate titles as a basic answer
            $titles = collect($this->parseContextTitles($context))->map(
                fn ($t, $i) => "[{$i}] {$t}"
            )->implode("\n");

            return [
                'answer' => "พบข่าวที่เกี่ยวข้องดังนี้:\n{$titles}",
                'fallback' => true,
            ];
        }

        try {
            $response = Http::timeout(90)
                ->withQueryParameters(['key' => $apiKey])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 1024,
                    ],
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException($response->body());
            }

            return [
                'answer' => $response->json('candidates.0.content.parts.0.text') ?? 'ไม่พบคำตอบ',
            ];
        } catch (\Throwable $e) {
            Log::channel('translation')->warning('Graph RAG LLM call failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'answer' => 'ขออภัย ไม่สามารถประมวลผลคำถามได้ในขณะนี้ โปรดลองใหม่อีกครั้ง',
                'fallback' => true,
            ];
        }
    }

    protected function parseContextTitles(string $context): array
    {
        preg_match_all('/^\[\d+\] (.+?) \(Source:/m', $context, $matches);

        return $matches[1] ?? [];
    }

    protected function extractEntities(string $question): array
    {
        // Simple entity extraction: capitalized words / common entities
        preg_match_all('/\b[A-Z][a-z]{2,}\b/u', $question, $matches);

        return array_values(array_unique(array_slice($matches[0] ?? [], 0, 8)));
    }

    protected function extractKeywords(string $question): array
    {
        $stopwords = [
            'the', 'a', 'an', 'of', 'in', 'on', 'at', 'to', 'and', 'or', 'for',
            'with', 'about', 'what', 'when', 'where', 'who', 'how', 'news',
            'บอก', 'เกี่ยวกับ', 'เรื่อง', 'ข่าว', 'มี', 'คือ', 'อะไร', 'ใคร',
            'ที่ไหน', 'เมื่อไหร่', 'อย่างไร', 'ของ', 'ใน', 'ที่', 'กับ', 'และ', 'หรือ', 'ให้',
        ];

        $tokens = preg_split('/[\s,\.!\?\n]+/u', Str::lower($question)) ?: [];

        return array_values(array_unique(array_filter($tokens, function (string $token) use ($stopwords): bool {
            return mb_strlen($token) > 2 && ! in_array($token, $stopwords, true);
        })));
    }
}
