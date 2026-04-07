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
        $isDirectFixedWorkerBooking = (int) ($this->booking->tho_id ?? 0) > 0 && $this->booking->trang_thai === 'da_xac_nhan';
        $statusTab = $isDirectFixedWorkerBooking ? 'upcoming' : 'pending';
        $title = $isDirectFixedWorkerBooking ? 'Lich hen moi da duoc gan cho ban' : 'Don dat lich moi';
        $message = $isDirectFixedWorkerBooking
            ? 'Khach hang vua dat lich truc tiep voi ban tai mot khung gio moi.'
            : 'Ban co 1 don dat lich moi tu ' . ($this->booking->khachHang->name ?? 'Khach');

        return [
            'booking_id' => $this->booking->id,
            'booking_code' => '#' . $this->booking->id,
            'khach_hang_name' => $this->booking->khachHang->name ?? 'Khach',
            'dich_vu_name' => implode(', ', $serviceNames) ?: 'Dich vu',
            'loai_dat_lich' => $this->booking->loai_dat_lich,
            'thoi_gian_hen' => $this->booking->thoi_gian_hen,
            'dia_chi' => $this->booking->dia_chi,
            'title' => $title,
            'type' => 'new_booking',
            'link' => '/worker/my-bookings?status=' . $statusTab . '&booking=' . $this->booking->id,
            'action_label' => 'Xem chi tiet don',
            'message' => $message,
        ];
    }
}
