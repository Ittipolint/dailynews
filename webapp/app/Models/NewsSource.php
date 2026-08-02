<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsSource extends Model
{
    use SoftDeletes;

    

    protected $fillable = [
        'name', 'slug', 'url', 'locale', 'fetch_type', 'feed_url',
        'cron_expression', 'credentials', 'config', 'category',
        'is_active', 'last_fetched_at', 'last_status', 'last_error',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'array',
            'config' => 'array',
            'is_active' => 'boolean',
            'last_fetched_at' => 'datetime',
        ];
    }

    public function news(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(News::class, 'source_id');
    }
}
