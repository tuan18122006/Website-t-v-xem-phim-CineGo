<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompensationController extends Controller
{
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
            
            // Validate the new seat is in the same room
            $newSeat = Seat::findOrFail($request->new_seat_id);
            $showtime = Showtime::findOrFail($booking->showtime_id);
            
            if ($newSeat->room_id !== $showtime->room_id) {
                throw new \Exception('Ghế mới không thuộc phòng của suất chiếu này.');
            }

            // Check if the new seat is already booked in this showtime
            $isBooked = BookingDetail::join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                ->where('bookings.showtime_id', $showtime->id)
                ->where('bookings.payment_status', 'paid')
                ->where('booking_details.seat_id', $newSeat->id)
                ->exists();

            if ($isBooked) {
                throw new \Exception('Ghế mới đã được khách khác mua.');
            }

            // Perform swap
            $detail->seat_id = $newSeat->id;
            // MIỄN PHÍ chênh lệch giá (Free Upgrade): Giá detail giữ nguyên
            $detail->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đổi ghế thành công (Miễn phí chênh lệch giá).',
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
     * Hoàn tiền qua Ví Điểm (Point Refund)
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
            if ($booking->status === 'cancelled' || $booking->status === 'refunded') {
                throw new \Exception('Đơn hàng này đã được hoàn/hủy trước đó.');
            }

            // Hủy đơn hàng
            $booking->status = 'refunded';
            $booking->save();

            // Cộng điểm cho User
            $user = User::findOrFail($booking->user_id);
            
            // Hoàn 100% số tiền thực tế khách đã trả (final_total) vào Ví Điểm
            $refundAmount = $booking->final_total;
            
            $user->cine_points += $refundAmount;
            $user->save();

            // Ghi log
            $user->pointHistories()->create([
                'points' => $refundAmount,
                'type' => 'refund',
                'description' => "Hoàn tiền do sự cố rạp. Mã đơn: {$booking->booking_code}",
                'reference_type' => Booking::class,
                'reference_id' => $booking->id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hủy vé và hoàn tiền vào Ví Điểm thành công.',
                'refund_amount' => $refundAmount,
                'new_balance' => $user->cine_points
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
