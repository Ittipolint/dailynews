<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsTranslation extends Model
{
    

    protected $fillable = [
        'news_id', 'locale', 'title', 'summary', 'body',
        'status', 'error_message', 'translated_at',
    ];

    protected function casts(): array
    {
        return [
            'translated_at' => 'datetime',
        ];
    }

    public function news(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
