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
            'standard_price' => 'required|numeric|min:0',
            'vip_price' => 'required|numeric|min:0',
            'couple_price' => 'required|numeric|min:0',
        ]);

        // Chuẩn hóa thời gian về 'Y-m-d H:i:s' để so sánh chính xác trong DB
        // (input datetime-local gửi lên dạng "Y-m-dTH:i" có chữ T).
        $start = Carbon::parse($request->start_time);
        $end   = Carbon::parse($request->end_time);

        // === CHỐNG TRÙNG LỊCH ===
        // Hai khoảng thời gian [A_start, A_end] và [B_start, B_end] bị chồng (overlap)
        // khi và chỉ khi:  A_start < B_end  VÀ  A_end > B_start.
        // Chỉ xét trong cùng một phòng và các suất còn "active".
        $conflict = Showtime::where('room_id', $request->room_id)
            ->where('status', 'active')
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->with('movie:id,title')
            ->first();

        if ($conflict) {
            $clashName = $conflict->movie ? $conflict->movie->title : 'một suất chiếu khác';

            return response()->json([
                'success' => false,
                'message' => "Phòng đang chiếu \"{$clashName}\" từ "
                    . $conflict->start_time->format('H:i d/m/Y') . ' đến '
                    . $conflict->end_time->format('H:i d/m/Y')
                    . '. Vui lòng chọn khung giờ khác!',
                'conflict' => [
                    'id'         => $conflict->id,
                    'movie'      => $clashName,
                    'start_time' => $conflict->start_time->toIso8601String(),
                    'end_time'   => $conflict->end_time->toIso8601String(),
                ],
            ], 422);
        }

        $showtime = Showtime::create([
            'movie_id' => $request->movie_id,
            'room_id' => $request->room_id,
            'start_time' => $start,
            'end_time' => $end,
            'format' => $request->format,
            'translation' => $request->translation,
            'status' => 'active'
        ]);

        // Tạo 3 cấu hình giá vé cơ bản
        \Illuminate\Support\Facades\DB::table('price_configs')->insert([
            [
                'showtime_id' => $showtime->id,
                'seat_type' => 'standard',
                'price' => $request->standard_price,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'showtime_id' => $showtime->id,
                'seat_type' => 'vip',
                'price' => $request->vip_price,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'showtime_id' => $showtime->id,
                'seat_type' => 'couple',
                'price' => $request->couple_price,
                'created_at' => now(),
                'updated_at' => now(),
            ]
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

    public function getShowtimesByMovie(Request $request, $id)
    {
        $date = $request->query('date');

        $query = Showtime::with('room')
            ->where('movie_id', $id);

        if ($date) {
            $query->whereDate('start_time', $date);
        }

        $showtimes = $query->get();

        $grouped = $showtimes->groupBy('room_id')->map(function ($items) {
            $room = $items->first()->room;

            return [
                'roomId' => $room->id,
                'roomName' => $room->name,
                'showtimes' => $items->map(function ($showtime) {
                    return [
                        'id' => $showtime->id,
                        'start_time' => \Carbon\Carbon::parse($showtime->start_time)->format('H:i'),
                        'available_seats' => 85,
                        'room_name' => $showtime->room->name,
                    ];
                })->values()
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $grouped
        ]);
    }

    public function getAvailableDates($id)
    {
        $dates = Showtime::where('movie_id', $id)
            ->whereDate('start_time', '>=', now()->toDateString())
            ->orderBy('start_time')
            ->pluck('start_time')
            ->map(function ($date) {
                return \Carbon\Carbon::parse($date)->toDateString();
            })
            ->unique()
            ->values()
            ->take(7);

        return response()->json([
            'success' => true,
            'data' => $dates
        ]);
    }
}
