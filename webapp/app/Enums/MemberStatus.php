<?php

declare(strict_types=1);

namespace App\Enums;

enum MemberStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case TRIAL = 'trial';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Expired',
            self::TRIAL => 'Trial',
            self::SUSPENDED => 'Suspended',
        };
    }
}
