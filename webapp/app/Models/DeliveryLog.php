<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryLog extends Model
{
    

    protected $fillable = [
        'member_id', 'schedule_id', 'channel_type', 'news_ids',
        'status', 'error_message', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'news_ids' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function member(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function schedule(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MemberSchedule::class, 'schedule_id');
    }
}
