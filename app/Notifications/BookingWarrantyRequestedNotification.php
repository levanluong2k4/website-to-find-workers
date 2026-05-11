<?php

namespace App\Notifications;

use App\Models\DonDatLich;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingWarrantyRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected DonDatLich $booking,
        protected string $reasonLabel = 'Bao hanh',
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $serviceNames = $this->booking->relationLoaded('dichVus')
            ? $this->booking->dichVus->pluck('ten_dich_vu')->filter()->values()->all()
            : $this->booking->dichVus()->pluck('ten_dich_vu')->filter()->values()->all();

        return [
            'booking_id' => $this->booking->id,
            'booking_code' => '#' . $this->booking->id,
            'khach_hang_name' => $this->booking->khachHang->name ?? 'Khach',
            'dich_vu_name' => implode(', ', $serviceNames) ?: 'Dich vu',
            'title' => 'Khach vua gui yeu cau bao hanh',
            'type' => 'booking_warranty_requested',
            'link' => '/worker/my-bookings?status=warranty&booking=' . $this->booking->id,
            'action_label' => 'Mo case bao hanh',
            'message' => 'Don #' . $this->booking->id . ' vua phat sinh yeu cau bao hanh: ' . $this->reasonLabel . '.',
        ];
    }
}
