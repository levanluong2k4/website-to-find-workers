<?php

namespace App\Observers;

use App\Models\DanhMucDichVu;
use App\Services\Chat\AiKnowledgeSyncService;
use Illuminate\Support\Facades\DB;

class DanhMucDichVuObserver
{
    public function saved(DanhMucDichVu $service): void
    {
        $sync = app(AiKnowledgeSyncService::class);
        $sync->syncSourceRecord('service_catalog', $service->id);
        $sync->syncServiceDependents($service->id);
    }

    public function deleting(DanhMucDichVu $service): void
    {
        $service->setAttribute(
            'ai_sync_worker_ids',
            $service->thos()->pluck('users.id')->map(fn ($id) => (int) $id)->all()
        );

        $service->setAttribute(
            'ai_sync_booking_ids',
            DB::table('don_dat_lich_dich_vu')
                ->where('dich_vu_id', $service->id)
                ->pluck('don_dat_lich_id')
                ->map(fn ($id) => (int) $id)
                ->all()
        );

        $service->setAttribute(
            'ai_sync_symptom_ids',
            $service->trieuChungs()->pluck('id')->map(fn ($id) => (int) $id)->all()
        );
    }

    public function deleted(DanhMucDichVu $service): void
    {
        $sync = app(AiKnowledgeSyncService::class);
        $sync->deleteSourceRecord('service_catalog', $service->id);
        $sync->syncWorkerProfilesByIds((array) $service->getAttribute('ai_sync_worker_ids'));
        $sync->syncBookingCasesByIds((array) $service->getAttribute('ai_sync_booking_ids'));
        $sync->syncRepairCatalogByIds((array) $service->getAttribute('ai_sync_symptom_ids'));
    }
}
