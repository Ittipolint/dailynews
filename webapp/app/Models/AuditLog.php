<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    

    protected $fillable = [
        'user_id', 'action', 'entity', 'entity_id', 'old_value', 'new_value',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
        ];
    }

    public static function record(string $entity, string $action, ?string $entityId = null, ?array $old = null, ?array $new = null): void
    {
        static::create([
            'user_id' => auth()->id() ?? 'system',
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'old_value' => $old,
            'new_value' => $new,
        ]);
    }
}
