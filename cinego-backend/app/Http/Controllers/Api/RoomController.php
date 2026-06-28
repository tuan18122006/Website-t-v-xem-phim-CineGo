<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    // Lấy danh sách phòng
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Room::orderBy('id', 'desc')->get()
        ], 200);
    }

    // Thêm phòng mới & tự động sinh ghế
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rows' => 'required|integer|max:10',
            'cols' => 'required|integer|min:1',
        ]);

        $room = Room::create([
            'name' => $request->name,
            'total_seats' => $request->rows * $request->cols,
            'status' => 'active'
        ]);

        $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        $seatsData = [];
        $now = now();

        for ($i = 0; $i < $request->rows; $i++) {
            for ($j = 1; $j <= $request->cols; $j++) {
                $seatsData[] = [
                    'room_id'    => $room->id,
                    'row'        => $rows[$i],
                    'number'     => $j,
                    'type'       => 'standard',
                    'status'     => 'available',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        Seat::insert($seatsData);

        return response()->json([
            'success' => true,
            'message' => 'Rạp và sơ đồ ghế đã được tạo thành công!',
            'data' => $room
        ], 201);
    }

    // Lấy chi tiết phòng và sơ đồ ghế
    public function show($id)
    {
        $room = Room::findOrFail($id);
        $seats = $room->seats()
            ->orderBy('row', 'asc')
            ->orderBy('number', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'room' => $room,
                'seats' => $seats
            ]
        ], 200);
    }

    public function updateSeatMap(Request $request, $id)
    {
        $request->validate([
            'seats' => 'required|array',
        ]);

        $room = Room::findOrFail($id);

        if ($room->showtimes()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể lưu sơ đồ! Rạp này đang có suất chiếu. Vui lòng xóa các suất chiếu trước khi đổi sơ đồ.'
            ], 400);
        }

        if (empty($request->seats)) {
            return response()->json(['message' => 'Cập nhật thành công']);
        }

        $cases = [];
        $params = [];
        $ids = [];

        foreach ($request->seats as $seatData) {
            $seatId = (int)$seatData['id'];
            $cases[] = "WHEN id = ? THEN ?";
            $params[] = $seatId;
            $params[] = $seatData['type'];
            $ids[] = $seatId;
        }

        $casesString = implode(' ', $cases);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $params = array_merge($params, $ids);

        DB::update("UPDATE seats SET type = CASE {$casesString} ELSE type END WHERE id IN ({$placeholders})", $params);

        return response()->json(['message' => 'Cập nhật sơ đồ ghế thành công']);
    }

    // Xóa phòng
    public function destroy($id)
    {
        $room = Room::findOrFail($id);

        if ($room->showtimes()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa rạp này vì đã được lên lịch chiếu! Vui lòng xóa suất chiếu trước.'
            ], 400);
        }

        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa phòng chiếu'
        ], 200);
    }
}
