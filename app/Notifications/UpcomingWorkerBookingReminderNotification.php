<?php

namespace App\Notifications;

use App\Models\DonDatLich;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class UpcomingWorkerBookingReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected DonDatLich $booking,
    ) {
    }

    public function via(object $notifiable): array
    {
        return empty($notifiable->email) ? [] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Nhac lich sua chua sap den - Don #' . $this->booking->id)
            ->greeting('Xin chao ' . ($notifiable->name ?? 'ban') . ',')
            ->line('Don sua chua #' . $this->booking->id . ' sap den gio hen.')
            ->line('Thoi gian hen: ' . $this->formatScheduledAt())
            ->line('Khach hang: ' . ($this->booking->khachHang?->name ?? 'Khach hang'))
            ->line('Dich vu: ' . $this->resolveServiceNames())
            ->line('Dia chi: ' . ($this->booking->dia_chi ?: 'Cap nhat tren he thong'))
            ->action('Xem chi tiet cong viec', url('/worker/my-bookings?status=upcoming&booking=' . $this->booking->id))
            ->line('Vui long sap xep de den dung gio.');
    }

    private function formatScheduledAt(): string
    {
        return optional($this->booking->thoi_gian_hen)->format('d/m/Y H:i') ?: 'Dang cap nhat';
    }

    private function resolveServiceNames(): string
    {
        if (
            !Schema::hasTable('danh_muc_dich_vu')
            || !Schema::hasTable('don_dat_lich_dich_vu')
        ) {
            return 'Dich vu sua chua';
        }

        $serviceNames = $this->booking->relationLoaded('dichVus')
            ? $this->booking->dichVus->pluck('ten_dich_vu')->filter()->values()->all()
            : $this->booking->dichVus()->pluck('ten_dich_vu')->filter()->values()->all();

        return implode(', ', $serviceNames) ?: 'Dich vu sua chua';
    }
}
