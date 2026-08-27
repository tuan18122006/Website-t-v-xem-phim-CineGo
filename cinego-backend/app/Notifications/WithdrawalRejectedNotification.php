<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WithdrawalRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public float $amount,
        public int $withdrawalId,
        public ?string $adminNote = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $formatted = number_format($this->amount, 0, ',', '.');
        $note = $this->adminNote ? ' Lý do: ' . $this->adminNote : '';
        return [
            'type'    => 'withdrawal_rejected',
            'title'   => '❌ Yêu cầu rút tiền bị từ chối',
            'message' => 'Yêu cầu rút ' . $formatted . 'đ đã bị từ chối.' . $note . ' Số tiền đã được hoàn trả lại Ví CineGo của bạn.',
            'amount'  => $this->amount,
            'withdrawal_id' => $this->withdrawalId,
            'admin_note' => $this->adminNote,
        ];
    }
}
