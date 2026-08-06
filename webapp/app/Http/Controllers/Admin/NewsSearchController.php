<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsSource;
use App\Services\Search\NewsSearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class NewsSearchController extends Controller
{
    public function index(Request $request, NewsSearchService $search): View
    {
        $filters = $request->only(['q', 'category', 'source_id', 'lang', 'from', 'to', 'per_page']);

        $news = $search->search($filters);
        $sources = NewsSource::orderBy('name')->get(['id', 'name']);

        return view('admin.news.index', compact('news', 'sources', 'filters'));
    }

    public function destroyMany(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids) || empty($ids)) {
            return redirect()->route('admin.news.index')
                ->with('error', 'ไม่ได้เลือกข่าวที่จะลบ');
        }

        $deleted = News::whereIn('id', $ids)->delete();

        return redirect()->route('admin.news.index')
            ->with('success', "ลบข่าวเรียบร้อย {$deleted} รายการ");
    }

    public function destroyByFilter(Request $request): RedirectResponse
    {
        $sourceId = $request->input('source_id');
        $from = $request->input('from');
        $to = $request->input('to');

        if (! $sourceId && ! $from && ! $to) {
            return redirect()->route('admin.news.index')
                ->with('error', 'กรุณาระบุแหล่งข่าว หรือช่วงวันที่อย่างน้อย 1 อย่าง');
        }

        $query = News::query();
        if ($sourceId) $query->where('source_id', $sourceId);
        if ($from) $query->where('published_at', '>=', $from);
        if ($to) $query->where('published_at', '<=', $to . ' 23:59:59');

        $deleted = $query->delete();

        return redirect()->route('admin.news.index')
            ->with('success', "ลบข่าวตามเงื่อนไขเรียบร้อย {$deleted} รายการ");
    }
}
