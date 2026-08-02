<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ingestion\IngestionService;
use Illuminate\Console\Command;

class IngestNewsCommand extends Command
{
    protected $signature = 'dailynews:ingest {--source= : Only ingest this source slug}';

    protected $description = 'Fetch news from all active sources (or a single source)';

    public function handle(IngestionService $ingestion): int
    {
        $sourceSlug = $this->option('source');

        if ($sourceSlug) {
            $source = \App\Models\NewsSource::where('slug', $sourceSlug)->firstOrFail();
            $result = $ingestion->fetchSource($source);
            $this->info("Stored ".count($result)." news items from {$source->name}");

            return self::SUCCESS;
        }

        $results = $ingestion->fetchAllActive();
        $this->info('Ingestion completed.');

        foreach ($results as $slug => $result) {
            if (isset($result['error'])) {
                $this->error("  {$slug}: ERROR - {$result['error']}");
            } else {
                $this->line("  {$slug}: stored ".count($result)." items");
            }
        }

        return self::SUCCESS;
    }
}
