<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SimpleNotification extends Notification
{
    use Queueable;

    protected $message;
    protected $type;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $message, string $type = 'info')
    {
        $this->message = $message;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'type' => $this->type,
        ];
    }
}
