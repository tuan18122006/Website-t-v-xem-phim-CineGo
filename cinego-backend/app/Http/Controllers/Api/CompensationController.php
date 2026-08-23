<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;

class CompensationController extends Controller
{
    /**
     * NGƯỜI DÙNG: danh sách vé bị ảnh hưởng bởi ghế hỏng (chưa chiếu)
     */
    public function myAffectedTickets(Request $request)
    {
        $userId = $request->user()->id;

        $details = BookingDetail::with(['booking', 'seat', 'booking.showtime.movie'])
            ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
            ->join('seats', 'booking_details.seat_id', '=', 'seats.id')
            ->where('bookings.user_id', $userId)
            ->where('bookings.payment_status', 'paid')
            ->where('showtimes.start_time', '>', now())
            ->where('seats.status', 'broken')
            ->select('booking_details.*')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $details->map(fn($d) => [
                'booking_detail_id' => $d->id,
                'booking_id' => $d->booking_id,
                'booking_code' => $d->booking->booking_code,
                'seat_label' => ($d->seat->row ?? '') . ($d->seat->number ?? ''),
                'movie_title' => $d->booking->showtime->movie->title ?? '',
                'showtime_at' => $d->booking->showtime->start_time ?? '',
                'showtime_id' => $d->booking->showtime_id,
                'total_amount' => (float) $d->price,
            ])
        ]);
    }

    /**
     * NGƯỜI DÙNG: tự hoàn tiền vé bị ảnh hưởng -> vào Ví Tiền
     */
    public function selfRefund(Request $request)
    {
        $request->validate([
            'booking_detail_id' => 'required|exists:booking_details,id'
        ]);

        DB::beginTransaction();
        try {
            $detail = BookingDetail::with('booking')->findOrFail($request->booking_detail_id);
            $booking = $detail->booking;

            if ($booking->user_id !== $request->user()->id) {
                throw new \Exception('Bạn không có quyền với đơn hàng này.');
            }
            if ($booking->payment_status !== 'paid') {
                throw new \Exception('Chỉ hoàn được vé đã thanh toán.');
            }
            if (in_array($booking->booking_status, ['refunded', 'cancelled'])) {
                throw new \Exception('Đơn hàng này đã được hoàn/hủy trước đó.');
            }
            if ($detail->is_checked_in) {
                throw new \Exception('Ghế đã check-in, không thể hoàn tiền.');
            }

            $seat = Seat::findOrFail($detail->seat_id);
            if ($seat->status !== 'broken') {
                throw new \Exception('Ghế này không nằm trong diện sự cố. Vui lòng liên hệ nhân viên.');
            }

            $refundAmount = (float) $detail->price;

            $remainingDetails = $booking->bookingDetails()
                ->where('id', '!=', $detail->id)
                ->count();

            DB::table('seat_holds')
                ->where('showtime_id', $booking->showtime_id)
                ->where('seat_id', $detail->seat_id)
                ->where('user_id', $booking->user_id)
                ->delete();

            $detail->delete();

            $booking->total_amount -= $refundAmount;
            if ($remainingDetails === 0) {
                $booking->booking_status = 'refunded';
                $booking->payment_status = 'refunded';
            }
            $booking->save();

            $user = $request->user();
            app(WalletService::class)->credit(
                $user,
                $refundAmount,
                "Hoàn tiền ghế hỏng {$seat->row}{$seat->number}. Đơn {$booking->booking_code}",
                'refund',
                $booking
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã hoàn tiền vào Ví Tiền thành công.',
                'refund_amount' => $refundAmount,
                'new_balance' => app(WalletService::class)->getBalance($user)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
    /**
     * Đổi ghế nhanh (Quick Swap) trong cùng suất chiếu
     */
    public function quickSwap(Request $request)
    {
        $request->validate([
            'booking_detail_id' => 'required|exists:booking_details,id',
            'new_seat_id' => 'required|exists:seats,id'
        ]);

        DB::beginTransaction();
        try {
            $detail = BookingDetail::with('booking')->findOrFail($request->booking_detail_id);
            $booking = $detail->booking;
            
            $newSeat = Seat::findOrFail($request->new_seat_id);
            $showtime = Showtime::findOrFail($booking->showtime_id);
            
            if ($newSeat->room_id !== $showtime->room_id) {
                throw new \Exception('Ghế mới không thuộc phòng của suất chiếu này.');
            }

            $isBooked = BookingDetail::join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                ->where('bookings.showtime_id', $showtime->id)
                ->where('bookings.payment_status', 'paid')
                ->where('booking_details.seat_id', $newSeat->id)
                ->exists();

            if ($isBooked) {
                throw new \Exception('Ghế mới đã được khách khác mua.');
            }

            $detail->seat_id = $newSeat->id;
            $detail->save();

            DB::commit();

            $oldSeatLabel = $seat->row . $seat->number;
            $newSeatLabel = $newSeat->row . $newSeat->number;
            $user = $booking->user ?? null;
            if ($user) {
                $movieTitle = $showtime->movie->title ?? 'phim';
                $showtimeLabel = $showtime->start_time ? $showtime->start_time->format('d/m/Y H:i') : '';

                $user->notify(new \App\Notifications\SeatIncidentNotification(
                    $booking->booking_code,
                    "Ghế {$oldSeatLabel} đã được đổi sang {$newSeatLabel} cho suất {$movieTitle} ({$showtimeLabel}). Đơn {$booking->booking_code}."
                ));

                try {
                    \Illuminate\Support\Facades\Mail::to($user->email)->send(
                        new \App\Mail\SeatIncidentMail(
                            $booking,
                            $newSeatLabel,
                            $movieTitle,
                            $showtimeLabel,
                            $showtime->room->name ?? '',
                            (float) $detail->price
                        )
                    );
                } catch (\Exception $e) {
                    \Log::error("Gửi email đổi ghế thất bại cho user {$user->id}: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Đổi ghế thành công từ {$oldSeatLabel} sang {$newSeatLabel} (Miễn phí chênh lệch giá).",
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function selfSwap(Request $request)
    {
        $request->validate([
            'booking_detail_id' => 'required|exists:booking_details,id',
            'new_seat_id' => 'required|exists:seats,id'
        ]);

        DB::beginTransaction();
        try {
            $detail = BookingDetail::with('booking')->findOrFail($request->booking_detail_id);
            $booking = $detail->booking;

            if ($booking->user_id !== $request->user()->id) {
                throw new \Exception('Bạn không có quyền với đơn hàng này.');
            }
            if ($booking->payment_status !== 'paid') {
                throw new \Exception('Chỉ đổi được vé đã thanh toán.');
            }
            if (in_array($booking->booking_status, ['refunded', 'cancelled'])) {
                throw new \Exception('Đơn hàng này đã được hoàn/hủy.');
            }
            if ($detail->is_checked_in) {
                throw new \Exception('Ghế đã check-in, không thể đổi.');
            }

            $seat = Seat::findOrFail($detail->seat_id);
            if ($seat->status !== 'broken') {
                throw new \Exception('Ghế này không bị hỏng, không thể đổi.');
            }

            $newSeat = Seat::findOrFail($request->new_seat_id);
            $showtime = Showtime::findOrFail($booking->showtime_id);

            if ($newSeat->room_id !== $showtime->room_id) {
                throw new \Exception('Ghế mới không thuộc phòng của suất chiếu này.');
            }
            if ($newSeat->status !== 'available') {
                throw new \Exception('Ghế mới không khả dụng.');
            }

            $isBooked = BookingDetail::join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                ->where('bookings.showtime_id', $showtime->id)
                ->where('bookings.payment_status', 'paid')
                ->where('booking_details.seat_id', $newSeat->id)
                ->exists();

            if ($isBooked) {
                throw new \Exception('Ghế mới đã được khách khác mua.');
            }

            $detail->seat_id = $newSeat->id;
            $detail->save();

            DB::commit();

            $oldSeatLabel = $seat->row . $seat->number;
            $newSeatLabel = $newSeat->row . $newSeat->number;
            $user = $request->user();
            if ($user) {
                $movieTitle = $showtime->movie->title ?? 'phim';
                $showtimeLabel = $showtime->start_time ? $showtime->start_time->format('d/m/Y H:i') : '';

                $user->notify(new \App\Notifications\SeatIncidentNotification(
                    $booking->booking_code,
                    "Bạn đã đổi ghế {$oldSeatLabel} sang {$newSeatLabel} cho suất {$movieTitle} ({$showtimeLabel}). Đơn {$booking->booking_code}."
                ));

                try {
                    \Illuminate\Support\Facades\Mail::to($user->email)->send(
                        new \App\Mail\SeatIncidentMail(
                            $booking,
                            $newSeatLabel,
                            $movieTitle,
                            $showtimeLabel,
                            $showtime->room->name ?? '',
                            (float) $detail->price
                        )
                    );
                } catch (\Exception $e) {
                    \Log::error("Gửi email đổi ghế thất bại cho user {$user->id}: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Đổi ghế thành công từ {$oldSeatLabel} sang {$newSeatLabel} (Miễn phí chênh lệch giá).",
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Chuyển suất chiếu (Reschedule) sang suất chiếu khác của cùng phim
     */
    public function reschedule(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'new_showtime_id' => 'required|exists:showtimes,id',
            'seat_mapping' => 'required|array' // [old_seat_id => new_seat_id]
        ]);

        DB::beginTransaction();
        try {
            $booking = Booking::with('details')->findOrFail($request->booking_id);
            $newShowtime = Showtime::findOrFail($request->new_showtime_id);

            // Kiểm tra suất mới có cùng phim không
            $oldShowtime = Showtime::findOrFail($booking->showtime_id);
            if ($oldShowtime->movie_id !== $newShowtime->movie_id) {
                throw new \Exception('Không thể chuyển sang suất chiếu của bộ phim khác.');
            }

            // Perform reschedule
            $booking->showtime_id = $newShowtime->id;
            $booking->save();

            foreach ($booking->details as $detail) {
                if (isset($request->seat_mapping[$detail->seat_id])) {
                    $newSeatId = $request->seat_mapping[$detail->seat_id];
                    
                    // Kiểm tra ghế mới có bị mua chưa
                    $isBooked = BookingDetail::join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                        ->where('bookings.showtime_id', $newShowtime->id)
                        ->where('bookings.payment_status', 'paid')
                        ->where('booking_details.seat_id', $newSeatId)
                        ->exists();

                    if ($isBooked) {
                        throw new \Exception("Ghế ID {$newSeatId} ở suất chiếu mới đã có người mua.");
                    }

                    $detail->seat_id = $newSeatId;
                    // Giá giữ nguyên (Free Upgrade)
                    $detail->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Chuyển suất chiếu thành công (Miễn phí chênh lệch giá).',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Hoàn tiền qua Ví Tiền (Wallet Refund)
     */
    public function pointRefund(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id'
        ]);

        DB::beginTransaction();
        try {
            $booking = Booking::findOrFail($request->booking_id);

            if ($booking->payment_status !== 'paid') {
                throw new \Exception('Chỉ có thể hoàn tiền cho đơn hàng đã thanh toán.');
            }
            if (in_array($booking->booking_status, ['cancelled', 'refunded'])) {
                throw new \Exception('Đơn hàng này đã được hoàn/hủy trước đó.');
            }

            // Hủy đơn hàng
            $booking->booking_status = 'refunded';
            $booking->payment_status = 'refunded';
            $booking->save();

            // Hoàn 100% số tiền thực tế khách đã trả (total_amount) vào Ví Tiền
            $refundAmount = (float) $booking->total_amount;

            $user = User::findOrFail($booking->user_id);
            app(WalletService::class)->credit(
                $user,
                $refundAmount,
                "Hoàn tiền do sự cố rạp. Mã đơn: {$booking->booking_code}",
                'refund',
                $booking
            );

            // Giải phóng ghế của đơn đã hoàn
            $seatIds = $booking->bookingDetails()->pluck('seat_id');
            if ($seatIds->isNotEmpty()) {
                DB::table('seat_holds')
                    ->where('showtime_id', $booking->showtime_id)
                    ->whereIn('seat_id', $seatIds)
                    ->where('user_id', $booking->user_id)
                    ->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hủy vé và hoàn tiền vào Ví Tiền thành công.',
                'refund_amount' => $refundAmount,
                'new_balance' => app(WalletService::class)->getBalance($user)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
