<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingCombo;
use App\Models\Showtime;
use App\Models\Seat;
use App\Models\Review;
use App\Helpers\BookingHelper;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function history(Request $request)
    {
        $user = $request->user();

        $bookings = Booking::with(['showtime.movie', 'showtime.room'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $response = $bookings->map(function ($booking) use ($user) {
            $showtime = $booking->showtime;
            $movie = $showtime?->movie;
            $hasReviewed = false;
            $canReview = false;

            if ($movie) {
                $hasReviewed = Review::where('user_id', $user->id)
                    ->where('movie_id', $movie->id)
                    ->exists();

                $showtimeEnded = $showtime?->end_time && Carbon::parse($showtime->end_time)->lte(Carbon::now());
                $canReview = !$hasReviewed
                    && $showtimeEnded
                    && $booking->payment_status === 'paid'
                    && $booking->booking_status === 'confirmed';
            }

            return [
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'movie_id' => $movie?->id,
                'movie_title' => $movie?->title,
                'poster_url' => $movie?->poster_url,
                'room_name' => $showtime?->room?->name,
                'showtime_start' => $showtime?->start_time?->format('H:i d/m/Y'),
                'showtime_end' => $showtime?->end_time?->format('H:i d/m/Y'),
                'movie_duration' => $movie?->duration,
                'payment_status' => $booking->payment_status,
                'booking_status' => $booking->booking_status,
                'can_review' => $canReview,
                'reviewed' => $hasReviewed,
                'review_message' => $hasReviewed
                    ? 'Bạn đã đánh giá phim này.'
                    : ($canReview ? 'Bạn có thể đánh giá phim này.' : 'Bạn có thể đánh giá sau khi suất chiếu kết thúc.'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $response,
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'showtime_id' => 'required|integer|exists:showtimes,id',
            'seat_ids' => 'required|array|min:1',
            'seat_ids.*' => 'required|integer|exists:seats,id',
            'combos' => 'nullable|array',
            'combos.*.id' => 'required|integer|exists:combos,id',
            'combos.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'total_amount' => 'required|numeric'
        ]);

        $showtimeId = $request->showtime_id;
        $seatIds = $request->seat_ids;
        $combosInput = $request->combos ?? [];
        $paymentMethod = $request->payment_method;
        $userId = $request->user()?->id;

        try {
            $booking = DB::transaction(function () use ($showtimeId, $seatIds, $combosInput, $paymentMethod, $userId) {
                // 1. Check showtime
                $showtime = Showtime::findOrFail($showtimeId);
                $now = Carbon::now();

                // 2. Lock and check seats
                foreach ($seatIds as $seatId) {
                    $seat = DB::table('seats')->where('id', $seatId)->lockForUpdate()->first();
                    if (!$seat) {
                        throw new \Exception('Ghế không tồn tại.');
                    }
                    if ($seat->room_id !== $showtime->room_id) {
                        throw new \Exception('Ghế không thuộc phòng chiếu của suất chiếu này.');
                    }
                    if ($seat->status !== 'available') {
                        throw new \Exception("Ghế {$seat->row}{$seat->number} đã bị hỏng hoặc không khả dụng.");
                    }

                    // Check if already booked
                    $isBooked = DB::table('booking_details')
                        ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                        ->where('bookings.showtime_id', $showtimeId)
                        ->where('booking_details.seat_id', $seatId)
                        ->where('bookings.payment_status', 'paid')
                        ->exists();

                    if ($isBooked) {
                        throw new \Exception("Ghế {$seat->row}{$seat->number} đã có người đặt mua trước.");
                    }

                    // Check if held by another user
                    $activeHold = DB::table('seat_holds')
                        ->where('showtime_id', $showtimeId)
                        ->where('seat_id', $seatId)
                        ->where('expires_at', '>', $now)
                        ->first();

                    if ($activeHold && $activeHold->user_id != $userId) {
                        throw new \Exception("Ghế {$seat->row}{$seat->number} đang bị giữ bởi người dùng khác.");
                    }
                }

                // 3. Backend calculations using BookingHelper to prevent spoofing
                $seatsCalc = BookingHelper::calculateSeats($showtimeId, $seatIds);
                $combosCalc = BookingHelper::calculateCombos($combosInput);
                
                $subtotal = $seatsCalc['subtotal'] + $combosCalc['subtotal'];
                $totalAmount = $subtotal;

                // 4. Generate unique Booking Code
                do {
                    $bookingCode = 'CG-' . mt_rand(100000, 999999);
                } while (Booking::where('booking_code', $bookingCode)->exists());

                // 5. Store official Booking
                $booking = Booking::create([
                    'booking_code' => $bookingCode,
                    'user_id' => $userId,
                    'showtime_id' => $showtimeId,
                    'voucher_id' => null,
                    'subtotal' => $subtotal,
                    'discount_amount' => 0.00,
                    'total_amount' => $totalAmount,
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'paid', // Mark as paid immediately for demo
                    'booking_status' => 'confirmed'
                ]);

                // 6. Store Booking Details
                foreach ($seatsCalc['details'] as $seatDetail) {
                    BookingDetail::create([
                        'booking_id' => $booking->id,
                        'seat_id' => $seatDetail['seat_id'],
                        'price' => $seatDetail['price'],
                        'ticket_code' => 'TC-' . strtoupper(Str::random(10)),
                        'is_checked_in' => false
                    ]);
                }

                // 7. Store Booking Combos
                foreach ($combosCalc['details'] as $comboDetail) {
                    BookingCombo::create([
                        'booking_id' => $booking->id,
                        'combo_id' => $comboDetail['combo_id'],
                        'quantity' => $comboDetail['quantity'],
                        'price' => $comboDetail['price']
                    ]);
                }

                // 8. Remove seat holds for this user and seats
                DB::table('seat_holds')
                    ->where('showtime_id', $showtimeId)
                    ->whereIn('seat_id', $seatIds)
                    ->where('user_id', $userId)
                    ->delete();

                return $booking;
            });

            return response()->json([
                'success' => true,
                'message' => 'Đặt vé thành công',
                'booking_code' => $booking->booking_code
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
