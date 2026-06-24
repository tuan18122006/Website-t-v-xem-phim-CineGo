<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;

class SeatHoldController extends Controller
{
    public function hold(Request $request)
    {
        $request->validate([
            'showtime_id' => 'required|integer|exists:showtimes,id',
            'seat_id' => 'required|integer|exists:seats,id',
        ]);

        $showtimeId = $request->showtime_id;
        $seatId = $request->seat_id;
        $userId = auth()->id();

        try {
            DB::transaction(function () use ($showtimeId, $seatId, $userId) {
                // 1. Lock the seat row to verify status
                $seat = DB::table('seats')->where('id', $seatId)->lockForUpdate()->first();

                if (!$seat) {
                    throw new \Exception('Ghế không tồn tại.');
                }

                if ($seat->status !== 'available') {
                    throw new \Exception('Ghế đã bị hỏng hoặc không khả dụng.');
                }

                // 2. Check if seat is already booked/paid
                $isBooked = DB::table('booking_details')
                    ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                    ->where('bookings.showtime_id', $showtimeId)
                    ->where('booking_details.seat_id', $seatId)
                    ->where('bookings.payment_status', 'paid')
                    ->exists();

                if ($isBooked) {
                    throw new \Exception('Ghế này đã được người khác mua thành công.');
                }

                // 3. Check if seat is currently held by someone else
                $now = Carbon::now();
                $activeHold = DB::table('seat_holds')
                    ->where('showtime_id', $showtimeId)
                    ->where('seat_id', $seatId)
                    ->where('expires_at', '>', $now)
                    ->lockForUpdate()
                    ->first();

                if ($activeHold) {
                    if ($activeHold->user_id == $userId) {
                        // Already held by this user, extend duration
                        DB::table('seat_holds')
                            ->where('id', $activeHold->id)
                            ->update([
                                'expires_at' => $now->copy()->addMinutes(10),
                                'updated_at' => $now
                            ]);
                        return;
                    } else {
                        throw new \Exception('Ghế này đang được chọn bởi người khác.');
                    }
                }

                // 4. Delete old expired hold for this seat
                DB::table('seat_holds')
                    ->where('showtime_id', $showtimeId)
                    ->where('seat_id', $seatId)
                    ->delete();

                // 5. Insert new hold record
                DB::table('seat_holds')->insert([
                    'showtime_id' => $showtimeId,
                    'seat_id' => $seatId,
                    'user_id' => $userId,
                    'expires_at' => $now->copy()->addMinutes(10),
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Giữ ghế thành công trong 10 phút'
            ], 200);
        } catch (UniqueConstraintViolationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ghế này đang được chọn bởi người khác.'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function release(Request $request)
    {
        $request->validate([
            'showtime_id' => 'required|integer|exists:showtimes,id',
            'seat_id' => 'required|integer|exists:seats,id',
        ]);

        $showtimeId = $request->showtime_id;
        $seatId = $request->seat_id;
        $userId = auth()->id();

        DB::table('seat_holds')
            ->where('showtime_id', $showtimeId)
            ->where('seat_id', $seatId)
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Giải phóng giữ ghế thành công'
        ], 200);
    }
}
