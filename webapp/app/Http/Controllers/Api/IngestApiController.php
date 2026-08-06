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
        // Shared-secret protection using the same API token as the rest of the API.
        $token = config('services.api_token');
        $provided = $request->header('X-API-Token', $request->input('token'));

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

        $source->update([
            'last_fetched_at' => now(),
            'last_status' => 'success',
            'last_error' => null,
        ]);

        $stored = [];

        foreach ($data['items'] as $item) {
            $normalizedTitle = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $item['title'])));
            $hash = hash('sha256', $source->id.'|'.($normalizedTitle !== '' ? $normalizedTitle : Str::slug($item['title'])).'|'.parse_url($item['url'], PHP_URL_HOST));

            $exists = News::where('source_url', $item['url'])
                ->orWhere(fn ($q) => $q->where('source_id', $source->id)->where('content_hash', $hash))
                ->exists();

            if ($exists) {
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
