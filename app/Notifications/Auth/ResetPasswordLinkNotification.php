<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        protected ?string $role = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiryMinutes = (int) config('auth.passwords.users.expire', 60);

        return (new MailMessage())
            ->subject('Đặt lại mật khẩu - Thợ Tốt NTU')
            ->greeting('Xin chào ' . ($notifiable->name ?: 'bạn') . ',')
            ->line('Chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.')
            ->action('Đặt lại mật khẩu', $this->buildResetUrl($notifiable))
            ->line("Liên kết này sẽ hết hạn sau {$expiryMinutes} phút.")
            ->line('Nếu bạn không thực hiện yêu cầu này, bạn có thể bỏ qua email này.');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }

    protected function buildResetUrl(object $notifiable): string
    {
        $routeParameters = [
            'token' => $this->token,
            'email' => $notifiable->email,
        ];

        if ($this->role) {
            $routeParameters['role'] = $this->role;
        }

        return route('password.reset', $routeParameters);
    }
}
