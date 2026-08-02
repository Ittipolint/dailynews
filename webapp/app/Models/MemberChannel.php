<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberChannel extends Model
{
    protected $connection = 'pgsql';

    protected $fillable = [
        'member_id', 'channel_type', 'credentials', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function setCredentialsAttribute($value): void
    {
        $this->attributes['credentials'] = json_encode(
            \App\Services\CredentialEncryption::encrypt($value),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function getCredentialsAttribute($value): ?array
    {
        $decoded = json_decode($value ?? 'null', true);

        return \App\Services\CredentialEncryption::decrypt($decoded);
    }

    public function member(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
