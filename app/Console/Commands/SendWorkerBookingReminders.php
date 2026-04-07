<?php

namespace App\Console\Commands;

use App\Models\DonDatLich;
use App\Notifications\UpcomingWorkerBookingReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendWorkerBookingReminders extends Command
{
    protected $signature = 'app:send-worker-booking-reminders {--minutes= : Override lead time in minutes}';

    protected $description = 'Gui email nhac tho khi sap den gio sua chua.';

    public function handle(): int
    {
        $leadMinutes = max(
            1,
            (int) ($this->option('minutes') ?: config('booking.worker_reminder.minutes_before', 30))
        );

        $now = now();
        $cutoff = $now->copy()->addMinutes($leadMinutes);
        $sentCount = 0;

        DonDatLich::query()
            ->with([
                'tho:id,name,email',
                'khachHang:id,name',
            ])
            ->where('trang_thai', 'da_xac_nhan')
            ->whereNotNull('tho_id')
            ->whereNull('worker_reminder_sent_at')
            ->whereNotNull('thoi_gian_hen')
            ->where('thoi_gian_hen', '>', $now)
            ->where('thoi_gian_hen', '<=', $cutoff)
            ->whereHas('tho', function ($query) {
                $query->whereNotNull('email')
                    ->where('email', '!=', '');
            })
            ->orderBy('id')
            ->chunkById(100, function ($bookings) use (&$sentCount): void {
                foreach ($bookings as $booking) {
                    if (!$booking->tho) {
                        continue;
                    }

                    try {
                        $booking->tho->notify(new UpcomingWorkerBookingReminderNotification($booking));

                        $booking->forceFill([
                            'worker_reminder_sent_at' => now(),
                        ])->saveQuietly();

                        $sentCount++;
                    } catch (\Throwable $exception) {
                        Log::warning('Worker booking reminder failed', [
                            'booking_id' => $booking->id,
                            'worker_id' => $booking->tho_id,
                            'message' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Da gui {$sentCount} email nhac lich cho tho.");

        return self::SUCCESS;
    }
}
