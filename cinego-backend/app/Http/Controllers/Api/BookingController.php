<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BookingService;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
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

        try {
            $booking = $this->bookingService->createBooking(
                $request->showtime_id,
                $request->seat_ids,
                $request->combos ?? [],
                $request->payment_method,
                auth()->id(),
                'paid' // giữ nguyên hành vi cũ: đặt vé xong là paid ngay
            );

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