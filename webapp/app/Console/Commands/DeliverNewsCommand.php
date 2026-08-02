<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Delivery\DeliveryService;
use Illuminate\Console\Command;

class DeliverNewsCommand extends Command
{
    protected $signature = 'dailynews:deliver {--schedule= : Only process this schedule id}';

    protected $description = 'Deliver news to members according to active schedules';

    public function handle(DeliveryService $delivery): int
    {
        $scheduleId = $this->option('schedule');

        if ($scheduleId) {
            $schedule = \App\Models\MemberSchedule::findOrFail((int) $scheduleId);
            $results = $delivery->processSchedule($schedule);
            $this->info(json_encode($results, JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $results = $delivery->processAllDueSchedules();
        $this->info('Delivery completed for '.count($results).' schedules.');

        foreach ($results as $scheduleId => $result) {
            $this->line("  Schedule #{$scheduleId}: ".json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }
}
