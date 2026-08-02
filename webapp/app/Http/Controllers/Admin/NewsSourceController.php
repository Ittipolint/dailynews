<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
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
        return view('admin.sources.form', ['source' => new NewsSource()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $source = NewsSource::create([
            ...$data,
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
        ]);

        AuditLog::record('news_source', 'create', (string) $source->id, null, $data);

        return redirect()->route('admin.sources.index')->with('success', 'เพิ่มแหล่งข่าวเรียบร้อย');
    }

    public function edit(NewsSource $source): View
    {
        return view('admin.sources.form', compact('source'));
    }

    public function update(Request $request, NewsSource $source): RedirectResponse
    {
        $data = $this->validated($request);

        $old = $source->only(['name', 'url', 'fetch_type', 'feed_url', 'is_active', 'locale']);
        $source->update($data);

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

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:500'],
            'locale' => ['required', 'string', 'max:5'],
            'fetch_type' => ['required', 'in:rss,api,crawl'],
            'feed_url' => ['nullable', 'url', 'max:500'],
            'cron_expression' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['nullable', 'array'],
        ]);
    }
}
