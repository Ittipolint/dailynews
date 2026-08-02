<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberSchedule extends Model
{
    

    protected $fillable = [
        'member_id', 'name', 'cron_expression', 'channels',
        'categories', 'languages', 'limit', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'categories' => 'array',
            'languages' => 'array',
            'limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function member(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
