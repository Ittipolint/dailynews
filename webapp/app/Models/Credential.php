<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credential extends Model
{
    

    protected $fillable = [
        'code', 'name', 'config', 'is_active', 'updated_by', 'last_tested_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    public function setConfigAttribute($value): void
    {
        $this->attributes['config'] = json_encode(
            \App\Services\CredentialEncryption::encrypt($value),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function getConfigAttribute($value): ?array
    {
        $decoded = json_decode($value ?? 'null', true);

        return \App\Services\CredentialEncryption::decrypt($decoded);
    }
}
