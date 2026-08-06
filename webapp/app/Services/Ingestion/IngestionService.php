<?php

declare(strict_types=1);

namespace App\Services\Ingestion;

use App\Models\News;
use App\Models\NewsSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IngestionService
{
    public function fetchSource(NewsSource $source): array
    {
        try {
            $items = match ($source->fetch_type) {
                'rss' => $this->fetchRss($source),
                'api' => $this->fetchApi($source),
                'crawl' => $this->fetchCrawl($source),
                default => throw new \RuntimeException("Unsupported fetch_type: {$source->fetch_type}"),
            };

            $stored = $this->storeItems($source, $items);

            $source->update([
                'last_fetched_at' => now(),
                'last_status' => 'success',
                'last_error' => null,
            ]);

            return $stored;
        } catch (\Throwable $e) {
            $source->update([
                'last_fetched_at' => now(),
                'last_status' => 'failed',
                'last_error' => $e->getMessage(),
            ]);

            Log::channel('ingest')->error('Ingestion failed', [
                'source' => $source->slug,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function fetchAllActive(): array
    {
        $results = [];

        NewsSource::where('is_active', true)->get()->each(function (NewsSource $source) use (&$results): void {
            try {
                $results[$source->slug] = $this->fetchSource($source);
            } catch (\Throwable $e) {
                $results[$source->slug] = ['error' => $e->getMessage()];
            }
        });

        return $results;
    }

    protected function fetchRss(NewsSource $source): array
    {
        $feedUrl = $source->feed_url ?: $source->url;

        $response = Http::timeout(60)
            ->withHeaders($this->headers($source))
            ->get($feedUrl);

        if (! $response->successful()) {
            throw new \RuntimeException("RSS fetch failed with status {$response->status()}");
        }

        $xml = simplexml_load_string($response->body());
        if ($xml === false) {
            throw new \RuntimeException('Unable to parse RSS/Atom XML');
        }

        $items = [];
        $entries = $xml->channel->item ?? $xml->entry ?? [];

        foreach ($entries as $entry) {
            $link = (string) ($entry->link->attributes()['href'] ?? $entry->link ?? '');
            $title = (string) ($entry->title ?? '');

            if ($title === '' || $link === '') {
                continue;
            }

            $items[] = [
                'title' => trim($title),
                'link' => trim($link),
                'summary' => trim((string) ($entry->description ?? $entry->summary ?? '')),
                'published_at' => $this->parseDate((string) ($entry->pubDate ?? $entry->updated ?? '')),
            ];
        }

        return $items;
    }

    protected function fetchApi(NewsSource $source): array
    {
        $config = $source->config ?? [];
        $credentials = $source->credentials ?? [];
        $endpoint = $source->feed_url ?: $source->url;

        $headers = $this->headers($source);
        if (! empty($credentials['api_key']) && ! empty($config['api_key_header'])) {
            $headers[$config['api_key_header']] = $credentials['api_key'];
        }

        $response = Http::timeout(60)->withHeaders($headers)->get($endpoint, $config['params'] ?? []);
        if (! $response->successful()) {
            throw new \RuntimeException("API fetch failed with status {$response->status()}");
        }

        $data = $response->json();
        $items = $data['articles'] ?? $data['data'] ?? $data['results'] ?? [];

        if (! is_array($items)) {
            return [];
        }

        return array_map(function (array $article) use ($config): array {
            return [
                'title' => trim((string) ($article['title'] ?? '')),
                'link' => trim((string) ($article['url'] ?? $article['link'] ?? '')),
                'summary' => trim((string) ($article['description'] ?? $article['summary'] ?? $article['excerpt'] ?? '')),
                'published_at' => $this->parseDate((string) ($article['publishedAt'] ?? $article['published_at'] ?? $article['publishedAt'] ?? '')),
            ];
        }, $items);
    }

    protected function fetchCrawl(NewsSource $source): array
    {
        $config = $source->config ?? [];
        $url = $source->url;
        $selectors = $config['selectors'] ?? ['item' => 'article', 'title' => 'h2 a', 'link' => 'h2 a', 'summary' => 'p'];

        $response = Http::timeout(60)
            ->withHeaders($this->headers($source))
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("Crawl failed with status {$response->status()}");
        }

        $html = $response->body();
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xp = new \DOMXPath($doc);

        $items = [];
        $nodes = $xp->query($selectors['item'] ?? 'article');

        if ($nodes === false) {
            return [];
        }

        foreach ($nodes as $node) {
            $titleNode = $xp->query('.//'.$selectors['title'], $node)->item(0);
            $linkNode = $xp->query('.//'.$selectors['link'], $node)->item(0);
            $summaryNode = $xp->query('.//'.($selectors['summary'] ?? 'p'), $node)->item(0);

            if ($titleNode === null) {
                continue;
            }

            $title = trim($titleNode->textContent);
            $link = $linkNode instanceof \DOMElement
                ? trim($linkNode->getAttribute('href'))
                : '';

            if ($title === '' || $link === '') {
                continue;
            }

            $link = $this->resolveUrl($link, $url);

            $items[] = [
                'title' => $title,
                'link' => $link,
                'summary' => $summaryNode ? trim($summaryNode->textContent) : '',
                'published_at' => null,
            ];
        }

        return $items;
    }

    protected function storeItems(NewsSource $source, array $items): array
    {
        $stored = [];

        foreach ($items as $item) {
            $normalizedTitle = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $item['title'])));
            $contentHash = hash('sha256', $source->id.'|'.($normalizedTitle !== '' ? $normalizedTitle : Str::slug($item['title'])).'|'.parse_url($item['link'], PHP_URL_HOST));
            $sourceUrl = $this->normalizeUrl($item['link']);

            $exists = News::where('source_url', $sourceUrl)
                ->orWhere(fn ($q) => $q->where('source_id', $source->id)->where('content_hash', $contentHash))
                ->exists();

            if ($exists) {
                continue;
            }

            $news = News::create([
                'source_id' => $source->id,
                'source_url' => $sourceUrl,
                'title' => $item['title'],
                'summary' => $item['summary'] ?: null,
                'category' => $source->category ?: $this->guessCategory($item['title']),
                'lang' => $source->locale ?: 'en',
                'content_hash' => $contentHash,
                'status' => 'new',
                'published_at' => $item['published_at'],
                'fetched_at' => now(),
            ]);

            $stored[] = $news;
        }

        return $stored;
    }

    protected function guessCategory(string $title): string
    {
        $keywords = [
            'business' => ['economy', 'market', 'stock', 'business', 'finance'],
            'technology' => ['tech', 'software', 'ai', 'cyber', 'digital', 'internet'],
            'sports' => ['sport', 'football', 'olympic', 'league'],
            'world' => ['world', 'international', 'diplomat', 'united nations'],
            'politics' => ['politics', 'parliament', 'election', 'government'],
        ];

        $lower = Str::lower($title);

        foreach ($keywords as $category => $words) {
            foreach ($words as $word) {
                if (Str::contains($lower, $word)) {
                    return $category;
                }
            }
        }

        return 'general';
    }

    protected function headers(NewsSource $source): array
    {
        $headers = [
            'User-Agent' => 'DailyNewsBot/1.0 (+https://ittipolint-sbu.veya.co.th/dailynews)',
            'Accept' => 'application/rss+xml, application/atom+xml, application/json, text/html, */*;q=0.8',
        ];

        $config = $source->config ?? [];
        if (! empty($config['headers']) && is_array($config['headers'])) {
            $headers = array_merge($headers, $config['headers']);
        }

        return $headers;
    }

    protected function parseDate(?string $value): ?\Carbon\Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);

        if ($parts === false) {
            return $url;
        }

        // Strip tracking params
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            unset($query['utm_source'], $query['utm_medium'], $query['utm_campaign'], $query['utm_term'], $query['utm_content']);
            $parts['query'] = http_build_query($query);
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '';
        $query = ! empty($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = ! empty($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return "{$scheme}://{$host}{$path}{$query}{$fragment}";
    }

    protected function resolveUrl(string $url, string $base): string
    {
        if (Str::startsWith($url, 'http')) {
            return $url;
        }

        if (Str::startsWith($url, '//')) {
            return 'https:'.$url;
        }

        if (Str::startsWith($url, '/')) {
            $parts = parse_url($base);

            return "{$parts['scheme']}://{$parts['host']}{$url}";
        }

        return $base.'/'.$url;
    }
}
