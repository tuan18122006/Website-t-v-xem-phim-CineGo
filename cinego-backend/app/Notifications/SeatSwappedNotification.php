<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Không dùng ShouldQueue → chạy đồng bộ, database notification lưu ngay lập tức
class SeatSwappedNotification extends Notification
{
    use Queueable;

    protected string $oldSeatLabel;
    protected string $newSeatLabel;
    protected string $movieName;
    protected string $startTime;
    protected int $showtimeId;

    public function __construct(string $oldSeatLabel, string $newSeatLabel, string $movieName, string $startTime, int $showtimeId)
    {
        $this->oldSeatLabel = $oldSeatLabel;
        $this->newSeatLabel = $newSeatLabel;
        $this->movieName = $movieName;
        $this->startTime = $startTime;
        $this->showtimeId = $showtimeId;
    }

    /**
     * Chỉ gửi qua database (web notification) - không gửi mail ở đây để tránh lỗi SMTP làm gián đoạn
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
            'type' => 'seat_swapped',
            'title' => 'Cập nhật ghế ngồi',
            'message' => "Ghế của bạn đã được rạp đổi từ {$this->oldSeatLabel} sang {$this->newSeatLabel}.",
            'movie_name' => $this->movieName,
            'start_time' => $this->startTime,
            'old_seat' => $this->oldSeatLabel,
            'new_seat' => $this->newSeatLabel,
            'showtime_id' => $this->showtimeId,
        ];
    }
}
