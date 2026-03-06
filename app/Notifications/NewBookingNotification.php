<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\DonDatLich;

class NewBookingNotification extends Notification
{
    use Queueable;

    protected $booking;

    /**
     * Create a new notification instance.
     */
    public function __construct(DonDatLich $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Lưu thẳng vào Database (Short polling DB)
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'khach_hang_name' => $this->booking->khachHang->name ?? 'Khách',
            'dich_vu_name' => $this->booking->dichVu->ten_dich_vu ?? 'Dịch vụ',
            'loai_dat_lich' => $this->booking->loai_dat_lich,
            'thoi_gian_hen' => $this->booking->thoi_gian_hen,
            'dia_chi' => $this->booking->dia_chi,
            'message' => 'Bạn có 1 đơn đặt lịch mới từ ' . ($this->booking->khachHang->name ?? 'Khách')
        ];
    }
}
