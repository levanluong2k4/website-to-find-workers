<?php

namespace App\Observers;

use App\Models\HuongXuLy;
use App\Services\Chat\AiKnowledgeSyncService;

class HuongXuLyObserver
{
    public function saved(HuongXuLy $resolution): void
    {
        app(AiKnowledgeSyncService::class)->syncRepairCatalogByCauseId((int) $resolution->nguyen_nhan_id);
    }

    public function deleted(HuongXuLy $resolution): void
    {
        app(AiKnowledgeSyncService::class)->syncRepairCatalogByCauseId((int) $resolution->nguyen_nhan_id);
    }
}
