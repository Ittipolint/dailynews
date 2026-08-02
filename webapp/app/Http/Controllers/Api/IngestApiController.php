<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsSource;
use App\Services\Ingestion\IngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IngestApiController extends Controller
{
    public function push(Request $request, IngestionService $ingestion): JsonResponse
    {
        // Simple shared-secret protection; can be tightened with HMAC in production.
        $token = config('services.n8n.api_key');
        $provided = $request->header('X-N8N-Token', $request->input('token'));

        if ($token && ! hash_equals((string) $token, (string) $provided)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'source' => ['required', 'string'],
            'items' => ['required', 'array'],
            'items.*.title' => ['required', 'string'],
            'items.*.url' => ['required', 'url'],
            'items.*.summary' => ['nullable', 'string'],
            'items.*.published_at' => ['nullable', 'date'],
        ]);

        $source = NewsSource::where('slug', $data['source'])
            ->orWhere('name', $data['source'])
            ->first();

        if (! $source) {
            return response()->json(['error' => 'Source not found'], 404);
        }

        $stored = [];

        foreach ($data['items'] as $item) {
            $hash = hash('sha256', Str::lower(Str::slug($item['title'])).'|'.parse_url($item['url'], PHP_URL_HOST));

            if (News::where('source_url', $item['url'])->orWhere('content_hash', $hash)->exists()) {
                continue;
            }

            $news = News::create([
                'source_id' => $source->id,
                'source_url' => $item['url'],
                'title' => $item['title'],
                'summary' => $item['summary'] ?? null,
                'category' => $source->category,
                'lang' => $source->locale ?: 'en',
                'content_hash' => $hash,
                'status' => 'new',
                'published_at' => isset($item['published_at']) ? \Carbon\Carbon::parse($item['published_at']) : now(),
                'fetched_at' => now(),
            ]);

            $stored[] = $news;
        }

        Log::channel('ingest')->info('Ingest push completed', [
            'source' => $source->slug,
            'stored' => count($stored),
        ]);

        return response()->json(['stored' => count($stored), 'data' => $stored]);
    }
}
