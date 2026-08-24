<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ShowtimeRescheduledNotification extends Notification
{
    use Queueable;

    protected string $movieName;
    protected string $oldStartTime;
    protected string $newStartTime;
    protected string $oldSeats;
    protected string $newSeats;
    protected int $newShowtimeId;

    public function __construct(
        string $movieName,
        string $oldStartTime,
        string $newStartTime,
        string $oldSeats,
        string $newSeats,
        int $newShowtimeId
    ) {
        $this->movieName = $movieName;
        $this->oldStartTime = $oldStartTime;
        $this->newStartTime = $newStartTime;
        $this->oldSeats = $oldSeats;
        $this->newSeats = $newSeats;
        $this->newShowtimeId = $newShowtimeId;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'showtime_rescheduled',
            'title' => 'Đổi lịch chiếu thành công',
            'message' => "Vé xem phim \"{$this->movieName}\" của bạn đã được đổi từ suất {$this->oldStartTime} sang suất {$this->newStartTime}. Ghế mới: {$this->newSeats}.",
            'old_start_time' => $this->oldStartTime,
            'new_start_time' => $this->newStartTime,
            'old_seats' => $this->oldSeats,
            'new_seats' => $this->newSeats,
            'new_showtime_id' => $this->newShowtimeId,
        ];
    }
}
