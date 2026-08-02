<?php

declare(strict_types=1);

namespace App\Enums;

enum ChannelType: string
{
    case LINE_PERSONAL = 'line_personal';
    case LINE_OA = 'line_oa';
    case EMAIL = 'email';

    public function label(): string
    {
        return match ($this) {
            self::LINE_PERSONAL => 'LINE ส่วนตัว',
            self::LINE_OA => 'LINE OA',
            self::EMAIL => 'Email',
        };
    }
}
