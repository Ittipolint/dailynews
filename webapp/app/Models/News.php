<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $connection = 'pgsql';

    protected $fillable = [
        'source_id', 'source_url', 'title', 'summary', 'body', 'category',
        'tags', 'thumbnail', 'lang', 'content_hash', 'status', 'sentiment',
        'is_breaking', 'published_at', 'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_breaking' => 'boolean',
            'published_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }

    public function source(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(NewsSource::class, 'source_id');
    }

    public function translations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(NewsTranslation::class);
    }

    public function translation(string $locale): ?NewsTranslation
    {
        return $this->translations()->where('locale', $locale)->first();
    }

    public function translatedTitle(string $locale): string
    {
        return $this->translation($locale)?->title ?? $this->title;
    }

    public function translatedSummary(string $locale): ?string
    {
        return $this->translation($locale)?->summary ?? $this->summary;
    }
}
