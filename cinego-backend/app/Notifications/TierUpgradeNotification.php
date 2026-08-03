<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TierUpgradeNotification extends Notification
{
    use Queueable;

    public $tierName;
    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct($tierName, $message)
    {
        $this->tierName = $tierName;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Only store in database
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'tier_upgrade',
            'tier' => $this->tierName,
            'message' => $this->message,
        ];
    }
}
