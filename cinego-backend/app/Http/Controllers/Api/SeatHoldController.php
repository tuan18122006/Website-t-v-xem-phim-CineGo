<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use App\Events\SeatLocked;
use App\Events\SeatUnlocked;

class SeatHoldController extends Controller
{
    private const PICK_HOLD_MINUTES = 3;

    private const CHECKOUT_HOLD_MINUTES = 10;

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
                $seat = DB::table('seats')->where('id', $seatId)->lockForUpdate()->first();

                if (!$seat) {
                    throw new \Exception('Ghế không tồn tại.');
                }

                if ($seat->status !== 'available') {
                    throw new \Exception('Ghế đã bị hỏng hoặc không khả dụng.');
                }

                $isBooked = DB::table('booking_details')
                    ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                    ->where('bookings.showtime_id', $showtimeId)
                    ->where('booking_details.seat_id', $seatId)
                    ->where('bookings.payment_status', 'paid')
                    ->exists();

                if ($isBooked) {
                    throw new \Exception('Ghế này đã được người khác mua thành công.');
                }

                $now = Carbon::now();

                // Dọn các hàng giữ ghế đã hết hạn của suất chiếu này
                DB::table('seat_holds')
                    ->where('showtime_id', $showtimeId)
                    ->where('expires_at', '<=', $now)
                    ->delete();

                $activeHold = DB::table('seat_holds')
                    ->where('showtime_id', $showtimeId)
                    ->where('seat_id', $seatId)
                    ->where('expires_at', '>', $now)
                    ->lockForUpdate()
                    ->first();

                if ($activeHold) {
                    if ($activeHold->user_id == $userId) {
                        if (Carbon::parse($activeHold->expires_at)->diffInSeconds($now) <= 60) {
                            DB::table('seat_holds')
                                ->where('id', $activeHold->id)
                                ->update([
                                    'expires_at' => $now->copy()->addMinutes(self::PICK_HOLD_MINUTES),
                                    'updated_at' => $now
                                ]);
                        }
                        return;
                    } else {
                        throw new \Exception('Ghế này đang được chọn bởi người khác.');
                    }
                }

                DB::table('seat_holds')
                    ->where('showtime_id', $showtimeId)
                    ->where('seat_id', $seatId)
                    ->delete();

                DB::table('seat_holds')->insert([
                    'showtime_id' => $showtimeId,
                    'seat_id' => $seatId,
                    'user_id' => $userId,
                    'expires_at' => $now->copy()->addMinutes(self::PICK_HOLD_MINUTES),
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            });

            broadcast(new SeatLocked($showtimeId, $seatId, $userId))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Giữ ghế thành công trong ' . self::PICK_HOLD_MINUTES . ' phút'
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

    /**
     * Bước 2: Người dùng bấm "Tiếp Tục" -> gia hạn giữ ghế chính thức đủ thời gian thanh toán.
     */
    public function confirmHold(Request $request)
    {
        $request->validate([
            'showtime_id' => 'required|integer|exists:showtimes,id',
            'seat_ids'    => 'required|array|min:1',
            'seat_ids.*'  => 'required|integer|exists:seats,id',
        ]);

        $userId = auth()->id();
        $now = Carbon::now();

        try {
            return DB::transaction(function () use ($request, $userId, $now) {
                $seatIds = array_unique($request->seat_ids);

                $activeHoldCount = DB::table('seat_holds')
                    ->where('user_id', $userId)
                    ->where('showtime_id', $request->showtime_id)
                    ->whereIn('seat_id', $seatIds)
                    ->where('expires_at', '>', $now)
                    ->count();

                if ($activeHoldCount !== count($seatIds)) {
                    throw new \Exception('Thời gian giữ ghế đã hết. Vui lòng chọn lại ghế!');
                }

                $expiresAt = $now->copy()->addMinutes(self::CHECKOUT_HOLD_MINUTES);

                DB::table('seat_holds')
                    ->where('user_id', $userId)
                    ->where('showtime_id', $request->showtime_id)
                    ->whereIn('seat_id', $seatIds)
                    ->where('expires_at', '>', $now)
                    ->update([
                        'expires_at' => $expiresAt,
                        'updated_at' => $now
                    ]);

                return response()->json([
                    'success'           => true,
                    'message'           => 'Ghế đã được giữ cho quá trình thanh toán trong ' . self::CHECKOUT_HOLD_MINUTES . ' phút',
                    'expires_at'        => $expiresAt->toIso8601String(),
                    'seconds_remaining' => self::CHECKOUT_HOLD_MINUTES * 60
                ]);
            });
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

        broadcast(new SeatUnlocked($showtimeId, $seatId))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Giải phóng giữ ghế thành công'
        ], 200);
    }
}
