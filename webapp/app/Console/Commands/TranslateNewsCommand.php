<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Translation\TranslationService;
use Illuminate\Console\Command;

class TranslateNewsCommand extends Command
{
    protected $signature = 'dailynews:translate {--limit=20 : Max number of news to translate}';

    protected $description = 'Translate pending news into th/en/zh';

    public function handle(TranslationService $translation): int
    {
        $limit = (int) $this->option('limit');
        $results = $translation->translatePending($limit);

        $count = 0;
        foreach ($results as $newsId => $result) {
            foreach ($result as $locale => $outcome) {
                if (($outcome['status'] ?? '') === 'translated') {
                    $count++;
                }
            }
        }

        $this->info("Translated {$count} language variants across ".count($results)." news items.");

        return self::SUCCESS;
    }
}
