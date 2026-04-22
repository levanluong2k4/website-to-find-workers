<?php

namespace App\Observers;

use App\Models\DonDatLich;
use App\Services\Chat\AiKnowledgeSyncService;

class DonDatLichObserver
{
    public function saved(DonDatLich $booking): void
    {
        app(AiKnowledgeSyncService::class)->syncSourceRecord('booking_case', $booking->id);
    }

    public function deleted(DonDatLich $booking): void
    {
        app(AiKnowledgeSyncService::class)->deleteSourceRecord('booking_case', $booking->id);
    }
}
