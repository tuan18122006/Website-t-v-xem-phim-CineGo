<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\BookingDetail;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ShowtimeController extends Controller
{
    public function index()
    {
        $showtimes = Showtime::with(['movie', 'room'])->get()->map(function ($st) {
            return [
                'id' => $st->id,
                'movie_id' => $st->movie_id,
                'room_id' => $st->room_id,
                'start_time' => $st->start_time ? $st->start_time->toIso8601String() : null,
                'end_time' => $st->end_time ? $st->end_time->toIso8601String() : null,
                'format' => $st->format,
                'translation' => $st->translation,
                'status' => $st->status ?? 'active',
                'movie_title' => $st->movie ? $st->movie->title : 'Không xác định',
                'room_name' => $st->room ? $st->room->name : 'Không xác định',
            ];
        });

        return response()->json($showtimes, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'format' => 'required|string',
            'translation' => 'required|string',
        ]);

        $showtime = Showtime::create([
            'movie_id' => $request->movie_id,
            'room_id' => $request->room_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'format' => $request->format,
            'translation' => $request->translation,
            'status' => 'active'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm suất chiếu thành công',
            'data' => $showtime
        ], 201);
    }

    public function destroy($id)
    {
        $showtime = Showtime::find($id);
        if (!$showtime) {
            return response()->json(['message' => 'Không tìm thấy suất chiếu'], 404);
        }

        $showtime->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa suất chiếu thành công'
        ], 200);
    }

    /**
     * Lấy danh sách ghế của suất chiếu kèm trạng thái khả dụng thực tế.
     */
    public function getSeats($id)
    {
        $showtime = Showtime::find($id);
        if (!$showtime) {
            return response()->json(['message' => 'Không tìm thấy suất chiếu'], 404);
        }

        $now = Carbon::now();

        // 1. Dọn dẹp tất cả các giữ ghế đã hết hạn trên hệ thống
        SeatHold::where('expires_at', '<=', $now)->delete();

        // 2. Giải phóng các ghế chính tài khoản này đang giữ tại suất chiếu này để bắt đầu phiên mới sạch sẽ
        $currentUser = auth('sanctum')->user();
        if ($currentUser) {
            SeatHold::where('showtime_id', $showtime->id)
                ->where('user_id', $currentUser->id)
                ->delete();
        }

        // 3. Lấy toàn bộ ghế của phòng chiếu
        $seats = Seat::where('room_id', $showtime->room_id)->get();

        // 4. Lấy danh sách ghế đã được đặt mua thành công (payment_status = paid)
        $bookedSeatIds = BookingDetail::join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
            ->where('bookings.showtime_id', $showtime->id)
            ->where('bookings.payment_status', 'paid')
            ->pluck('booking_details.seat_id')
            ->toArray();

        // 5. Lấy danh sách ghế đang bị giữ bởi người khác (expires_at > now)
        $heldSeatIds = SeatHold::where('showtime_id', $showtime->id)
            ->where('expires_at', '>', $now)
            ->pluck('seat_id')
            ->toArray();

        // 6. Định dạng đầu ra khớp chính xác với frontend yêu cầu
        $formattedSeats = $seats->map(function ($seat) use ($bookedSeatIds, $heldSeatIds) {
            $status = 'available';
            if ($seat->status === 'broken') {
                $status = 'broken';
            } elseif (in_array($seat->id, $bookedSeatIds)) {
                $status = 'sold';
            } elseif (in_array($seat->id, $heldSeatIds)) {
                $status = 'holding';
            }

            return [
                'id' => $seat->id,
                'row_name' => $seat->row,
                'seat_number' => $seat->number,
                'status' => $status,
                'is_aisle' => ($seat->number === 3 || $seat->number === 10), // Cột 3 và 10 là lối đi
            ];
        });

        return response()->json($formattedSeats, 200);
    }
}

