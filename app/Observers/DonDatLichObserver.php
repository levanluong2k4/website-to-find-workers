<?php

namespace App\Observers;

use App\Models\DonDatLich;
use App\Services\Chat\AiKnowledgeSyncService;
use Illuminate\Support\Facades\Log;
use Throwable;

class DonDatLichObserver
{
    public function saved(DonDatLich $booking): void
    {
        try {
            app(AiKnowledgeSyncService::class)->syncSourceRecord('booking_case', $booking->id);
        } catch (Throwable $exception) {
            Log::warning('Skipping booking AI sync after save.', [
                'booking_id' => $booking->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function deleted(DonDatLich $booking): void
    {
        try {
            app(AiKnowledgeSyncService::class)->deleteSourceRecord('booking_case', $booking->id);
        } catch (Throwable $exception) {
            Log::warning('Skipping booking AI sync after delete.', [
                'booking_id' => $booking->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
