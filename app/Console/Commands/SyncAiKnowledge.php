<?php

namespace App\Console\Commands;

use App\Services\Chat\AiKnowledgeSyncService;
use Illuminate\Console\Command;

class SyncAiKnowledge extends Command
{
    protected $signature = 'app:sync-ai-knowledge
        {--source=all : all|booking_case|service_catalog|worker_profile|customer_feedback_case|repair_catalog}
        {--id= : Optional source record id}';

    protected $description = 'Dong bo du lieu tri thuc AI tu database van hanh sang kho du lieu truy xuat nhanh.';

    public function handle(AiKnowledgeSyncService $syncService): int
    {
        $source = (string) $this->option('source');
        $sourceId = $this->option('id');
        $sourceId = $sourceId !== null && $sourceId !== '' ? (int) $sourceId : null;

        if (!in_array($source, ['all', 'booking_case', 'service_catalog', 'worker_profile', 'customer_feedback_case', 'repair_catalog'], true)) {
            $this->error('Gia tri --source khong hop le. Su dung: all, booking_case, service_catalog, worker_profile, customer_feedback_case, repair_catalog.');
            return self::FAILURE;
        }

        $result = $syncService->sync($source, $sourceId);

        $this->info('Dong bo AI knowledge thanh cong.');
        $this->line('booking_case: ' . $result['booking_case']);
        $this->line('service_catalog: ' . $result['service_catalog']);
        $this->line('worker_profile: ' . $result['worker_profile']);
        $this->line('customer_feedback_case: ' . $result['customer_feedback_case']);
        $this->line('repair_catalog: ' . $result['repair_catalog']);

        return self::SUCCESS;
    }
}
