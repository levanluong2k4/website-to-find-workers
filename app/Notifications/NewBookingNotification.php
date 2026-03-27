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

        return [
            'booking_id' => $this->booking->id,
            'booking_code' => '#' . $this->booking->id,
            'khach_hang_name' => $this->booking->khachHang->name ?? 'Khách',
            'dich_vu_name' => implode(', ', $serviceNames) ?: 'Dịch vụ',
            'loai_dat_lich' => $this->booking->loai_dat_lich,
            'thoi_gian_hen' => $this->booking->thoi_gian_hen,
            'dia_chi' => $this->booking->dia_chi,
            'title' => 'Đơn đặt lịch mới',
            'type' => 'new_booking',
            'link' => '/worker/jobs/' . $this->booking->id,
            'action_label' => 'Xem chi tiết đơn',
            'message' => 'Bạn có 1 đơn đặt lịch mới từ ' . ($this->booking->khachHang->name ?? 'Khách'),
        ];
    }
}
