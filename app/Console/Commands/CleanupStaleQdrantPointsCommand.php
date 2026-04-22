<?php

namespace App\Console\Commands;

use App\Services\Chat\QdrantPendingDeletionService;
use Illuminate\Console\Command;

class CleanupStaleQdrantPointsCommand extends Command
{
    protected $signature = 'app:cleanup-stale-qdrant-points {--limit=100 : Maximum pending deletions to process}';

    protected $description = 'Delete stale Qdrant points queued after source records are removed.';

    public function handle(QdrantPendingDeletionService $service): int
    {
        $stats = $service->processPending(max(1, (int) $this->option('limit')));

        $this->info('Qdrant stale cleanup finished.');
        $this->line('processed: ' . $stats['processed']);
        $this->line('deleted: ' . $stats['deleted']);
        $this->line('failed: ' . $stats['failed']);

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
