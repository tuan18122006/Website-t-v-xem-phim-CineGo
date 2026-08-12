<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\PaymentReminderNotification;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelStalePendingBookings extends Command
{
    protected $signature = 'bookings:cancel-stale-pending';

    protected $description = 'Nhắc thanh toán (6h) và tự hủy các đơn hàng pending quá 24h để giải phóng ghế';

    private const REMIND_AFTER_HOURS = 6;

    private const CANCEL_AFTER_HOURS = 24;

    public function handle(BookingService $bookingService): int
    {
        $now = Carbon::now();

        $remindCutoff = $now->copy()->subHours(self::REMIND_AFTER_HOURS);
        $notifySent = 0;

        $pendingForReminder = Booking::with(['showtime', 'user'])
            ->where('payment_status', 'pending')
            ->where('payment_reminder_sent', false)
            ->where('created_at', '<=', $remindCutoff)
            ->get();

        foreach ($pendingForReminder as $booking) {
            if (!$booking->user) {
                DB::table('bookings')
                    ->where('id', $booking->id)
                    ->update(['payment_reminder_sent' => true]);
                continue;
            }

            $hoursLeft = max(1, (int) ceil($now->diffInHours($booking->created_at->copy()->addHours(self::CANCEL_AFTER_HOURS))));

            try {
                $booking->user->notify(new PaymentReminderNotification(
                    $booking->booking_code,
                    "Đơn hàng {$booking->booking_code} của bạn chưa thanh toán. Còn {$hoursLeft} giờ để thanh toán, quá hạn đơn sẽ bị hủy và ghế được trả lại."
                ));
                $notifySent++;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Lỗi gửi nhắc thanh toán đơn ' . $booking->booking_code . ': ' . $e->getMessage());
            }

            DB::table('bookings')
                ->where('id', $booking->id)
                ->update(['payment_reminder_sent' => true]);
        }

        $cutoff = $now->copy()->subHours(self::CANCEL_AFTER_HOURS);

        $staleBookings = Booking::with('showtime')
            ->where('payment_status', 'pending')
            ->where('created_at', '<=', $cutoff)
            ->get();

        $cancelled = 0;

        foreach ($staleBookings as $booking) {
            if ($booking->showtime && Carbon::parse($booking->showtime->start_time)->lte($now)) {
                continue;
            }

            $bookingService->markAsFailed($booking);

            DB::table('seat_holds')
                ->where('showtime_id', $booking->showtime_id)
                ->whereIn('seat_id', $booking->bookingDetails()->pluck('seat_id'))
                ->where('user_id', $booking->user_id)
                ->delete();

            $cancelled++;
        }

        $this->info("Đã gửi {$notifySent} lời nhắc thanh toán, tự hủy {$cancelled} đơn hàng quá " . self::CANCEL_AFTER_HOURS . " giờ.");

        return self::SUCCESS;
    }
}