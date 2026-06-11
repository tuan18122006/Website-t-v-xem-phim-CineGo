<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use Illuminate\Http\Request;

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
}
