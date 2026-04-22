<?php

namespace App\Observers;

use App\Models\DanhGia;
use App\Services\Chat\AiKnowledgeSyncService;

class DanhGiaObserver
{
    public function saved(DanhGia $review): void
    {
        $sync = app(AiKnowledgeSyncService::class);

        if ($review->don_dat_lich_id) {
            $sync->syncSourceRecord('booking_case', (int) $review->don_dat_lich_id);
        }

        if ($review->nguoi_bi_danh_gia_id) {
            $sync->syncSourceRecord('worker_profile', (int) $review->nguoi_bi_danh_gia_id);
        }
    }

    public function deleted(DanhGia $review): void
    {
        $this->saved($review);
    }
}
