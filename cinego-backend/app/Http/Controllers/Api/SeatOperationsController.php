<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SeatLock;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\BookingDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SeatOperationsController extends Controller
{
    /**
     * Khóa ghế tạm thời theo khung giờ
     */
    public function lockTemporary(Request $request)
    {
        $request->validate([
            'seat_id' => 'required|exists:seats,id',
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'reason' => 'nullable|string'
        ]);

        $start = Carbon::parse($request->start_time);
        $end = Carbon::parse($request->end_time);

        DB::beginTransaction();
        try {
            // Lưu log khóa ghế
            $lock = SeatLock::create([
                'seat_id' => $request->seat_id,
                'room_id' => $request->room_id,
                'start_time' => $start,
                'end_time' => $end,
                'reason' => $request->reason,
                'locked_by' => auth()->id()
            ]);

            // Quét tìm các suất chiếu bị ảnh hưởng trong khoảng thời gian này
            $overlappingShowtimes = Showtime::where('room_id', $request->room_id)
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->pluck('id');

            $affectedBookings = [];
            if ($overlappingShowtimes->isNotEmpty()) {
                // Quét xem ghế này có khách mua (đã thanh toán) trong các suất chiếu bị đè lên không
                $affectedBookings = BookingDetail::with(['booking.user', 'booking.showtime.movie'])
                    ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                    ->whereIn('bookings.showtime_id', $overlappingShowtimes)
                    ->where('bookings.payment_status', 'paid')
                    ->where('booking_details.seat_id', $request->seat_id)
                    ->select('booking_details.*') // tránh đè ID
                    ->get();
            }

            foreach ($overlappingShowtimes as $showtimeId) {
                broadcast(new \App\Events\SeatLocked($showtimeId, $request->seat_id, auth()->id(), 'broken'));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Khóa ghế thành công.',
                'lock' => $lock,
                'has_conflict' => $affectedBookings->isNotEmpty(),
                'affected_bookings' => $affectedBookings
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Báo hỏng phần cứng (Khóa vĩnh viễn)
     */
    public function markBroken(Request $request)
    {
        $request->validate([
            'seat_id' => 'required|exists:seats,id'
        ]);

        $seat = Seat::findOrFail($request->seat_id);

        DB::beginTransaction();
        try {
            $seat->status = 'broken';
            $seat->save();

            // Tìm TẤT CẢ các vé tương lai đã mua trên ghế này
            $affectedBookings = BookingDetail::with(['booking.user', 'booking.showtime.movie'])
                ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.id')
                ->where('showtimes.start_time', '>', now())
                ->where('bookings.payment_status', 'paid')
                ->where('booking_details.seat_id', $seat->id)
                ->select('booking_details.*')
                ->orderBy('showtimes.start_time')
                ->get();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Báo hỏng ghế thành công.',
                'seat' => $seat,
                'has_conflict' => $affectedBookings->isNotEmpty(),
                'affected_bookings' => $affectedBookings
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mở khóa ghế hỏng / xóa lock tạm thời
     */
    public function unlockSeat(Request $request)
    {
        $request->validate([
            'seat_id' => 'required|exists:seats,id',
            'lock_id' => 'nullable|exists:seat_locks,id'
        ]);

        DB::beginTransaction();
        try {
            $seat = Seat::findOrFail($request->seat_id);
            if ($seat->status === 'broken') {
                $seat->status = 'available';
                $seat->save();
            }

            if ($request->lock_id) {
                SeatLock::where('id', $request->lock_id)->delete();
            } else {
                // Xóa các lock đang hiệu lực của ghế
                SeatLock::where('seat_id', $seat->id)->where('end_time', '>', now())->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mở khóa ghế thành công.',
                'seat' => $seat
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Xem các ghế đang bị khóa tạm thời trong 1 phòng
     */
    public function getActiveLocks($roomId)
    {
        $locks = SeatLock::with('seat')
            ->where('room_id', $roomId)
            ->where('end_time', '>', now())
            ->get();

        return response()->json([
            'success' => true,
            'data' => $locks
        ], 200);
    }
}
