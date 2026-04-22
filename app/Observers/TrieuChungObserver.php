<?php

namespace App\Observers;

use App\Models\TrieuChung;
use App\Services\Chat\AiKnowledgeSyncService;

class TrieuChungObserver
{
    public function saved(TrieuChung $symptom): void
    {
        app(AiKnowledgeSyncService::class)->syncSourceRecord('repair_catalog', $symptom->id);
    }

    public function deleted(TrieuChung $symptom): void
    {
        app(AiKnowledgeSyncService::class)->deleteSourceRecord('repair_catalog', $symptom->id);
    }
}
