<?php

namespace App\Observers;

use App\Models\NguyenNhan;
use App\Services\Chat\AiKnowledgeSyncService;

class NguyenNhanObserver
{
    public function saved(NguyenNhan $cause): void
    {
        app(AiKnowledgeSyncService::class)->syncRepairCatalogByCauseId($cause->id);
    }

    public function deleting(NguyenNhan $cause): void
    {
        $cause->setAttribute(
            'ai_sync_symptom_ids',
            $cause->trieuChungs()->pluck('trieu_chung.id')->map(fn ($id) => (int) $id)->all()
        );
    }

    public function deleted(NguyenNhan $cause): void
    {
        app(AiKnowledgeSyncService::class)->syncRepairCatalogByIds((array) $cause->getAttribute('ai_sync_symptom_ids'));
    }
}
