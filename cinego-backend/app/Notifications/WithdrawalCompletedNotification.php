<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WithdrawalCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public float $amount,
        public int $withdrawalId
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'withdrawal_completed',
            'title'   => '✅ Chuyển khoản thành công',
            'message' => 'Yêu cầu rút ' . number_format($this->amount, 0, ',', '.') . 'đ từ Ví CineGo đã được Admin xử lý và chuyển khoản thành công vào tài khoản của bạn.',
            'amount'  => $this->amount,
            'withdrawal_id' => $this->withdrawalId,
        ];
    }
}
