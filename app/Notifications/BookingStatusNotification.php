<?php

namespace App\Notifications;

use App\Models\DonDatLich;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected DonDatLich $booking,
        protected string $title,
        protected string $message,
        protected string $type = 'booking_status_updated',
        protected ?string $actionLabel = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject($this->title . ' - Đơn đặt lịch #' . $this->booking->id)
            ->greeting('Xin chào ' . ($notifiable->name ?? 'bạn') . ',')
            ->line($this->message)
            ->line('Mã đơn: #' . $this->booking->id)
            ->line('Dịch vụ: ' . $this->resolveServiceNames())
            ->line('Trạng thái hiện tại: ' . $this->resolveStatusLabel($this->booking->trang_thai))
            ->action($this->actionLabel ?: 'Xem chi tiết đơn đặt lịch', url('/customer/my-bookings/' . $this->booking->id))
            ->line('Cảm ơn bạn đã sử dụng dịch vụ Thợ Tốt NTU.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_code' => '#' . $this->booking->id,
            'booking_status' => $this->booking->trang_thai,
            'status_label' => $this->resolveStatusLabel($this->booking->trang_thai),
            'service_name' => $this->resolveServiceNames(),
            'worker_name' => $this->booking->tho?->name,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'link' => '/customer/my-bookings/' . $this->booking->id,
            'action_label' => $this->actionLabel ?: 'Xem chi tiết đơn đặt lịch',
            'thoi_gian_hen' => optional($this->booking->thoi_gian_hen)->toIso8601String(),
        ];
    }

    private function resolveServiceNames(): string
    {
        $serviceNames = $this->booking->relationLoaded('dichVus')
            ? $this->booking->dichVus->pluck('ten_dich_vu')->filter()->values()->all()
            : $this->booking->dichVus()->pluck('ten_dich_vu')->filter()->values()->all();

        return implode(', ', $serviceNames) ?: 'Dịch vụ sửa chữa';
    }

    private function resolveStatusLabel(?string $status): string
    {
        return match ($status) {
            'cho_xac_nhan' => 'Đang tìm thợ',
            'da_xac_nhan' => 'Đã có thợ nhận',
            'dang_lam' => 'Đang xử lý',
            'cho_hoan_thanh' => 'Chờ xác nhận COD',
            'cho_thanh_toan' => 'Chờ thanh toán trực tuyến',
            'da_xong' => 'Đã hoàn tất',
            'da_huy' => 'Đã hủy',
            default => 'Đang cập nhật',
        };
    }
}
