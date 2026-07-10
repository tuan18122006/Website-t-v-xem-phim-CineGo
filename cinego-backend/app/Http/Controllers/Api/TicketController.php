<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\Carbon;

class TicketController extends Controller
{
    public function show($bookingCode)
    {
        $booking = Booking::with([
            'user',
            'showtime.movie',
            'showtime.room',
            'bookingDetails.seat',
            'bookingCombos.combo',
            'voucher',
        ])
            ->where('booking_code', $bookingCode)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy vé.',
            ], 404);
        }

        $seats = $booking->bookingDetails->map(function ($detail) {
            return [
                'seat_code' => $detail->seat
                    ? $detail->seat->row . $detail->seat->number
                    : 'N/A',

                'seat_type' => $detail->seat?->type ?? 'N/A',

                'price' => $detail->price,
            ];
        })->values();

        $combos = $booking->bookingCombos->map(function ($item) {
            return [
                'name' => $item->combo?->name ?? 'N/A',
                'quantity' => $item->quantity,
                'price' => $item->price_at_purchase,
                'subtotal' => $item->price_at_purchase * $item->quantity,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'booking_code' => $booking->booking_code,

                'customer_name' => $booking->user?->name ?? 'Quý khách',

                'movie_title' => $booking->showtime?->movie?->title ?? 'N/A',

                'showtime' => $booking->showtime?->start_time
                    ? Carbon::parse($booking->showtime->start_time)->format('d/m/Y H:i')
                    : 'N/A',

                'room_name' => $booking->showtime?->room?->name ?? 'N/A',

                'booking_time' => $booking->created_at
                    ? Carbon::parse($booking->created_at)->format('d/m/Y H:i')
                    : 'N/A',

                'seats' => $seats,
                'combos' => $combos,

                'subtotal' => $booking->subtotal,
                'discount_amount' => $booking->discount_amount,
                'voucher_code' => $booking->voucher?->code,
                'total_amount' => $booking->total_amount,

                'payment_method' => $booking->payment_method,
                'payment_status' => $booking->payment_status,
            ],
        ]);
    }
}