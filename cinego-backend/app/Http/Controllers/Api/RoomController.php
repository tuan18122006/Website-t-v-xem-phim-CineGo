<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use App\Models\Seat;

class RoomController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Room::all()
        ], 200);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'rows' => 'required|integer|max:10',
            'cols' => 'required|integer',
        ]);

        $room = Room::create([
            'name' => $request->name,
            'total_seats' => $request->rows * $request->cols,
        ]);

        $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        $seatsData = []; // Mảng chứa dữ liệu các ghế

        for ($i = 0; $i < $request->rows; $i++) {
            for ($j = 1; $j <= $request->cols; $j++) {
                $seatsData[] = [
                    'room_id'    => $room->id,
                    'row'        => $rows[$i],
                    'number'     => $j,
                    'type'       => 'standard',
                    'is_booked'  => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Chèn 1 lần duy nhất vào database
        Seat::insert($seatsData);
        return response()->json(['message' => 'Rạp và sơ đồ ghế đã được tạo thành công!', 'room' => $room], 201);
    }
    public function show(string $id)
    {
        $room = Room::findOrFail($id);


        $seats = $room->seats()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'room' => $room,
                'seats' => $seats
            ]
        ], 200);
    }
}
