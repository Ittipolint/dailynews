<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\NewsSource;
use App\Services\CredentialEncryption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class NewsSourceController extends Controller
{
    public function index(): View
    {
        $sources = NewsSource::withCount('news')->orderBy('name')->paginate(15);

        return view('admin.sources.index', compact('sources'));
    }

    public function create(): View
    {
        return view('admin.sources.form', [
            'source' => new NewsSource(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'selectedCategories' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $source = NewsSource::create([
            ...$data,
            'category' => $this->flattenCategories($request),
            'cron_expression' => $this->cronExpression($request),
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::record('news_source', 'create', (string) $source->id, null, $data);

        return redirect()->route('admin.sources.index')->with('success', 'เพิ่มแหล่งข่าวเรียบร้อย');
    }

    public function edit(NewsSource $source): View
    {
        $selected = array_values(array_filter(array_map('trim', explode(',', (string) $source->category))));

        return view('admin.sources.form', [
            'source' => $source,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'selectedCategories' => $selected,
        ]);
    }

    public function update(Request $request, NewsSource $source): RedirectResponse
    {
        $data = $this->validated($request);

        $old = $source->only(['name', 'url', 'fetch_type', 'feed_url', 'is_active', 'locale']);
        $source->update([
            ...$data,
            'category' => $this->flattenCategories($request),
            'cron_expression' => $this->cronExpression($request),
        ]);

        AuditLog::record('news_source', 'update', (string) $source->id, $old, $data);

        return redirect()->route('admin.sources.index')->with('success', 'แก้ไขแหล่งข่าวเรียบร้อย');
    }

    public function destroy(NewsSource $source): RedirectResponse
    {
        AuditLog::record('news_source', 'delete', (string) $source->id, ['name' => $source->name], null);
        $source->delete();

        return redirect()->route('admin.sources.index')->with('success', 'ลบแหล่งข่าวเรียบร้อย');
    }

    public function toggle(NewsSource $source): RedirectResponse
    {
        $source->update(['is_active' => ! $source->is_active]);

        return redirect()->route('admin.sources.index')->with('success', 'เปลี่ยนสถานะเรียบร้อย');
    }

    public function testConnection(NewsSource $source): JsonResponse
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)->get($source->feed_url ?: $source->url);

            return response()->json([
                'ok' => $response->successful(),
                'status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function fetchNow(NewsSource $source): JsonResponse
    {
        $webhook = config('services.n8n.fetch_webhook');

        if (! $webhook) {
            return response()->json(['ok' => false, 'error' => 'n8n fetch webhook not configured'], 503);
        }

        $payload = [
            'source' => [
                'slug' => $source->slug,
                'name' => $source->name,
                'url' => $source->url,
                'feed_url' => $source->feed_url,
                'fetch_type' => $source->fetch_type,
                'locale' => $source->locale,
            ],
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders([
                    'X-API-Token' => (string) config('services.api_token'),
                    'Accept' => 'application/json',
                ])
                ->post($webhook, $payload);

            $ok = $response->successful();

            $source->update([
                'last_fetched_at' => now(),
                'last_status' => $ok ? 'success' : 'failed',
                'last_error' => $ok ? null : 'n8n webhook responded with HTTP '.$response->status(),
            ]);

            return response()->json([
                'ok' => $ok,
                'status' => $response->status(),
                'body' => $response->json(),
            ], $ok ? 200 : 502);
        } catch (\Throwable $e) {
            $source->update([
                'last_fetched_at' => now(),
                'last_status' => 'failed',
                'last_error' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 502);
        }
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:500'],
            'locale' => ['required', 'string', 'max:5'],
            'fetch_type' => ['required', 'in:rss,api,crawl'],
            'feed_url' => ['nullable', 'url', 'max:500'],
            'cron_expression' => ['nullable', 'string', 'max:100'],
            'freq' => ['nullable', 'in:hourly,daily,weekly,monthly'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['nullable', 'array'],
        ]);
    }

    protected function cronExpression(Request $request): string
    {
        $manual = trim((string) $request->input('cron_expression'));

        // If user enabled advanced mode or provided a custom cron, keep it as-is.
        if ($request->boolean('advanced') || ! in_array($request->input('freq'), ['hourly', 'daily', 'weekly', 'monthly'], true)) {
            return $manual ?: '0 * * * *';
        }

        $freq = $request->input('freq');

        if ($freq === 'hourly') {
            return '0 * * * *';
        }

        [$h, $m] = array_pad(explode(':', (string) $request->input('time', '08:00')), 2, null);

        if ($freq === 'weekly') {
            return "{$m} {$h} * * ".($request->input('dow') ?: '*');
        }

        if ($freq === 'monthly') {
            return "{$m} {$h} ".($request->input('dom') ?: '1').' * *';
        }

        return "{$m} {$h} * * *";
    }

    protected function flattenCategories(Request $request): ?string
    {
        $request->merge([
            'categories' => is_array($request->input('categories'))
                ? array_values(array_filter(array_map('trim', $request->input('categories'))))
                : [],
        ]);

        $categories = $request->input('categories', []);

        // Validate each selected category is a real, active category code.
        $valid = Category::where('is_active', true)->pluck('code')->all();
        $filtered = array_values(array_intersect($categories, $valid));

        if (empty($filtered)) {
            return null;
        }

        return implode(',', $filtered);
    }
}
