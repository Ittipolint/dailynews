<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsSource;
use App\Services\Search\NewsSearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsSearchController extends Controller
{
    public function index(Request $request, NewsSearchService $search): View
    {
        $filters = $request->only(['q', 'category', 'source_id', 'lang', 'from', 'to', 'per_page']);

        $news = $search->search($filters);
        $sources = NewsSource::orderBy('name')->get(['id', 'name']);

        return view('admin.news.index', compact('news', 'sources', 'filters'));
    }
}
