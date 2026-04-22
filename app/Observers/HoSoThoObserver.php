<?php

namespace App\Observers;

use App\Models\HoSoTho;
use App\Services\Chat\AiKnowledgeSyncService;

class HoSoThoObserver
{
    public function saved(HoSoTho $profile): void
    {
        app(AiKnowledgeSyncService::class)->syncSourceRecord('worker_profile', (int) $profile->user_id);
    }

    public function deleted(HoSoTho $profile): void
    {
        app(AiKnowledgeSyncService::class)->deleteSourceRecord('worker_profile', (int) $profile->user_id);
    }
}
