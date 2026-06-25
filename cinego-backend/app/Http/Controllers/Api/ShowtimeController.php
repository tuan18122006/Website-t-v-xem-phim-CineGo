<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingDetail;
use App\Models\Showtime;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShowtimeController extends Controller
{
    /**
     * Xuất thời gian dạng "giờ địa phương trần" (không kèm offset múi giờ).
     * Giá trị lưu trong DB chính là giờ người dùng nhập (wall-clock), nên trả về
     * nguyên dạng "Y-m-dTH:i:s" để trình duyệt hiểu là giờ địa phương, tránh bị
     * lệch 7 tiếng do new Date() tự quy đổi từ UTC.
     */
    private function localTime($value): ?string
    {
        return $value ? $value->format('Y-m-d\TH:i:s') : null;
    }

    public function index()
    {
        $showtimes = Showtime::with(['movie', 'room'])
            ->orderBy('start_time')
            ->get()
            ->map(function ($st) {
                return [
                    'id' => $st->id,
                    'movie_id' => $st->movie_id,
                    'room_id' => $st->room_id,
                    'start_time' => $this->localTime($st->start_time),
                    'end_time' => $this->localTime($st->end_time),
                    'format' => $st->format,
                    'translation' => $st->translation,
                    'status' => $st->status ?? 'active',
                    'movie_title' => $st->movie ? $st->movie->title : 'Không xác định',
                    'room_name' => $st->room ? $st->room->name : 'Không xác định',
                ];
            });

        return response()->json($showtimes, 200);
    }

    /**
     * Chi tiết một suất chiếu (kèm thông tin phim, phòng và tình hình đặt vé).
     */
    public function show($id)
    {
        $st = Showtime::with(['movie', 'room'])->find($id);

        if (!$st) {
            return response()->json(['message' => 'Không tìm thấy suất chiếu'], 404);
        }

        // Số ghế đã bán = số vé thuộc các booking chưa bị hủy của suất này.
        $bookedSeats = BookingDetail::whereHas('booking', function ($q) use ($id) {
            $q->where('showtime_id', $id)
                ->where('booking_status', '!=', 'cancelled');
        })->count();

        $totalSeats = $st->room ? (int) $st->room->total_seats : 0;
        $durationMins = $st->start_time && $st->end_time
            ? $st->start_time->diffInMinutes($st->end_time)
            : null;

        return response()->json([
            'id' => $st->id,
            'movie_id' => $st->movie_id,
            'room_id' => $st->room_id,
            'start_time' => $this->localTime($st->start_time),
            'end_time' => $this->localTime($st->end_time),
            'duration_mins' => $durationMins,
            'format' => $st->format,
            'translation' => $st->translation,
            'status' => $st->status ?? 'active',
            'movie' => $st->movie ? [
                'id' => $st->movie->id,
                'title' => $st->movie->title,
                'duration' => $st->movie->duration,
                'rating' => $st->movie->rating,
                'poster_url' => $st->movie->poster_url,
            ] : null,
            'room' => $st->room ? [
                'id' => $st->room->id,
                'name' => $st->room->name,
                'total_seats' => $totalSeats,
            ] : null,
            'movie_title' => $st->movie ? $st->movie->title : 'Không xác định',
            'room_name' => $st->room ? $st->room->name : 'Không xác định',
            'total_seats' => $totalSeats,
            'booked_seats' => $bookedSeats,
            'available_seats' => max($totalSeats - $bookedSeats, 0),
        ], 200);
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
}
