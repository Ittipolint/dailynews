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

    public function deliveryLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DeliveryLog::class, 'schedule_id');
    }

    /**
     * Convert a stored cron expression into a human-readable Thai schedule
     * (day(s) + time). Falls back to the raw expression for custom crons.
     */
    public function humanSchedule(): string
    {
        $cron = trim((string) $this->cron_expression);
        $parts = preg_split('/\s+/', $cron);

        if (count($parts) !== 5) {
            return $cron !== '' ? $cron : '-';
        }

        [$min, $hour, $dom, $month, $dow] = $parts;
        $time = str_pad($hour, 2, '0', STR_PAD_LEFT).':'.str_pad($min, 2, '0', STR_PAD_LEFT);

        // hourly: 0 * * * *
        if ($min === '0' && $hour === '*' && $dom === '*' && $month === '*' && $dow === '*') {
            return 'ทุกชั่วโมง';
        }

        // weekly: M H * * DOW[,DOW]
        if ($hour !== '*' && $min !== '*' && $dom === '*' && $month === '*' && $dow !== '*') {
            $dayNames = [
                '0' => 'อาทิตย์', '1' => 'จันทร์', '2' => 'อังคาร', '3' => 'พุธ',
                '4' => 'พฤหัสบดี', '5' => 'ศุกร์', '6' => 'เสาร์',
            ];
            $days = collect(explode(',', $dow))
                ->map(fn ($d) => $dayNames[$d] ?? $d)
                ->join(' / ');

            return "ทุกสัปดาห์ (วัน{$days}) เวลา {$time} น.";
        }

        // monthly: M H DOM * *
        if ($hour !== '*' && $min !== '*' && $dom !== '*' && $month === '*' && $dow === '*') {
            $domLabel = $dom === 'L' ? 'วันสุดท้ายของเดือน' : "วันที่ {$dom} ของเดือน";

            return "ทุกเดือน {$domLabel} เวลา {$time} น.";
        }

        // daily: M H * * *
        if ($hour !== '*' && $min !== '*' && $dom === '*' && $month === '*' && $dow === '*') {
            return "ทุกวัน เวลา {$time} น.";
        }

        return $cron;
    }
}
