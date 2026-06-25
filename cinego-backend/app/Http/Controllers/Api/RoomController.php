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
            'total_seats' => 'required|integer|min:10|max:1000'
        ]);

        $room = Room::create([
            'name' => $request->name,
            'total_seats' => $request->total_seats,
            'status' => 'active'
        ]);

        // Logic sinh ghế tự động (10 ghế / hàng)
        $seatsToInsert = [];
        $rowsCount = ceil($request->total_seats / 10);
        $seatsCounted = 0;

        // Hàm tạo tên hàng (A, B... Z, AA, AB...)
        $generateRowName = function($index) {
            $letters = range('A', 'Z');
            $rowName = '';
            while ($index >= 0) {
                $rowName = $letters[$index % 26] . $rowName;
                $index = intdiv($index, 26) - 1;
            }
            return $rowName;
        };

        $now = now();
        for ($i = 0; $i < $rowsCount; $i++) {
            $rowName = $generateRowName($i);
            for ($number = 1; $number <= 10; $number++) {
                if ($seatsCounted >= $request->total_seats) break;
                
                $seatsToInsert[] = [
                    'room_id' => $room->id,
                    'row' => $rowName,
                    'number' => $number,
                    'type' => 'standard',
                    'status' => 'available',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $seatsCounted++;
            }
        }
        
        // Insert hàng loạt để tối ưu hiệu năng
        Seat::insert($seatsToInsert);

        return response()->json([
            'success' => true,
            'message' => 'Tạo phòng và ' . $seatsCounted . ' ghế thành công',
            'data' => $room
        ], 201);
    }

    // Lấy sơ đồ ghế của phòng
    public function getSeats($id)
    {
        $room = Room::findOrFail($id);
        // Sắp xếp theo ID đảm bảo thứ tự A1 -> A10, B1 -> B10
        $seats = $room->seats()->orderBy('id')->get();
        
        return response()->json([
            'success' => true,
            'data' => $seats
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

        Room::findOrFail($id); // Đảm bảo phòng tồn tại
        
        DB::transaction(function () use ($request) {
            foreach ($request->seats as $seatData) {
                // Chỉ cập nhật type (loại ghế)
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
        $room->delete(); // Lệnh này cũng sẽ tự động xóa các ghế liên quan nhờ ON DELETE CASCADE trong DB

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa phòng chiếu'
        ], 200);
    }
}
