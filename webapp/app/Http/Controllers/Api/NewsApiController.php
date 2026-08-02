<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = News::with('source');

        if ($request->filled('q')) {
            $term = '%'.$request->get('q').'%';
            $query->where(function ($q) use ($term): void {
                $q->where('title', 'like', $term)
                    ->orWhere('summary', 'like', $term);
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }

        if ($request->filled('from')) {
            $query->where('published_at', '>=', $request->get('from'));
        }

        $news = $query->orderByDesc('published_at')->limit((int) ($request->get('limit', 20)))->get();

        return response()->json(['data' => $news]);
    }

    public function show(News $news): JsonResponse
    {
        return response()->json(['data' => $news->load(['source', 'translations'])]);
    }
}
