<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use SoftDeletes;

    

    protected $fillable = [
        'member_type_id', 'name', 'email', 'line_user_id', 'line_oa_user_id',
        'line_oa_basic_id', 'line_oa_channel_id', 'line_oa_channel_secret', 'line_oa_webhook_url',
        'preferred_locale', 'status', 'is_active',
        'plan_start_date', 'plan_end_date',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'plan_start_date' => 'datetime',
            'plan_end_date' => 'datetime',
        ];
    }

    public function type(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MemberType::class, 'member_type_id');
    }

    public function channels(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MemberChannel::class);
    }

    public function interests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MemberInterest::class);
    }

    public function schedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MemberSchedule::class);
    }
}
