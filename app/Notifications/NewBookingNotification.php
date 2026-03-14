<?php

namespace App\Notifications;

use App\Models\DonDatLich;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewBookingNotification extends Notification
{
    use Queueable;

    protected DonDatLich $booking;

    public function __construct(DonDatLich $booking)
    {
        $this->booking = $booking;
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

        if (empty($serviceNames) && $this->booking->dichVu) {
            $serviceNames = [$this->booking->dichVu->ten_dich_vu];
        }

        return [
            'booking_id' => $this->booking->id,
            'khach_hang_name' => $this->booking->khachHang->name ?? 'Khach',
            'dich_vu_name' => implode(', ', $serviceNames) ?: 'Dich vu',
            'loai_dat_lich' => $this->booking->loai_dat_lich,
            'thoi_gian_hen' => $this->booking->thoi_gian_hen,
            'dia_chi' => $this->booking->dia_chi,
            'message' => 'Ban co 1 don dat lich moi tu ' . ($this->booking->khachHang->name ?? 'Khach'),
        ];
    }
}
