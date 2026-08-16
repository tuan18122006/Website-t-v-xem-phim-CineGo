<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification
{
    use Queueable;

    public $bookingCode;
    public $message;

    public function __construct($bookingCode, $message)
    {
        $this->bookingCode = $bookingCode;
        $this->message = $message;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_failed',
            'booking_code' => $this->bookingCode,
            'message' => $this->message,
        ];
    }
}