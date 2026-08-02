<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\News;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NewsSearchService
{
    public function search(array $filters): LengthAwarePaginator
    {
        $query = News::with('source')->where('status', '!=', 'failed');

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($q) use ($term): void {
                $q->where('title', 'ilike', $term)
                    ->orWhere('summary', 'ilike', $term)
                    ->orWhere('body', 'ilike', $term);
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['source_id'])) {
            $query->where('source_id', $filters['source_id']);
        }

        if (! empty($filters['lang'])) {
            $query->where('lang', $filters['lang']);
        }

        if (! empty($filters['from'])) {
            $query->where('published_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where('published_at', '<=', $filters['to']);
        }

        $query->orderByDesc('published_at');

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }
}
