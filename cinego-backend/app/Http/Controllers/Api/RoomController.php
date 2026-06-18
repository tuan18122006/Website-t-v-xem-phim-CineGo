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
        $seats = $room->seats()->orderBy('row')->orderBy('number')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'room' => $room,
                'seats' => $seats
            ]
        ], 200);
    }

    // Cập nhật cấu hình sơ đồ ghế (Visual Editor)
    public function updateSeatMap(Request $request, $id)
    {
        $request->validate([
            'seats' => 'required|array',
            'seats.*.id' => 'required|integer|exists:seats,id',
            'seats.*.type' => 'required|string|in:standard,vip,couple,hidden',
        ]);

        Room::findOrFail($id);
        
        DB::transaction(function () use ($request) {
            foreach ($request->seats as $seatData) {
                Seat::where('id', $seatData['id'])->update([
                    'type' => $seatData['type']
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Lưu sơ đồ ghế thành công!'
        ], 200);
    }

    // Xóa phòng
    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa phòng chiếu'
        ], 200);
    }
}