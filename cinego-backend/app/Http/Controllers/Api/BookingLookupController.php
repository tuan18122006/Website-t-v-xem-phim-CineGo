<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingLookupController extends Controller
{
    /**
     * Tra cứu đơn hàng cho nhân viên hỗ trợ khách.
     * Tìm theo Số điện thoại / Email của khách, hoặc Mã đơn (booking_code).
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:3',
        ]);

        $q = trim($request->q);

        $bookings = Booking::with([
                'user:id,name,email,phone',
                'showtime.movie:id,title',
                'showtime.room:id,name',
            ])
            ->where(function ($outer) use ($q) {
                $outer->where('booking_code', 'like', "%{$q}%")
                    ->orWhereHas('user', function ($query) use ($q) {
                        $query->where('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    });
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($b) {
                return [
                    'id'             => $b->id,
                    'booking_code'   => $b->booking_code,
                    'customer_name'  => $b->user?->name,
                    'customer_email' => $b->user?->email,
                    'customer_phone' => $b->user?->phone,
                    'movie_title'    => $b->showtime?->movie?->title ?? 'Không xác định',
                    'room_name'      => $b->showtime?->room?->name ?? '—',
                    'showtime_at'    => $b->showtime?->start_time
                        ? $b->showtime->start_time->format('H:i d/m/Y') : null,
                    'total_amount'   => (float) $b->total_amount,
                    'payment_status' => $b->payment_status,
                    'booking_status' => $b->booking_status,
                    'created_at'     => $b->created_at?->format('H:i d/m/Y'),
                ];
            });

        return response()->json([
            'count' => $bookings->count(),
            'data'  => $bookings,
        ], 200);
    }

    /**
     * Chi tiết một đơn: khách mua ghế nào, bắp nước gì — để nhân viên báo lại cho khách.
     */
    public function show($id)
    {
        $b = Booking::with([
            'user:id,name,email,phone',
            'showtime.movie:id,title,poster_url,duration',
            'showtime.room:id,name',
            'bookingDetails.seat:id,row,number,type',
            'bookingCombos.combo:id,name,image_url',
            'voucher:id,code',
        ])->find($id);

        if (!$b) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        $seats = $b->bookingDetails->map(function ($d) {
            $seat = $d->seat;
            return [
                'label'         => $seat ? ($seat->row . $seat->number) : '??',
                'type'          => $seat?->type ?? 'standard',
                'price'         => (float) $d->price,
                'ticket_code'   => $d->ticket_code,
                'is_checked_in' => (bool) $d->is_checked_in,
            ];
        });

        $combos = $b->bookingCombos->map(function ($c) {
            return [
                'name'     => $c->combo?->name ?? 'Combo',
                'quantity' => (int) $c->quantity,
                'price'    => (float) $c->price_at_purchase,
            ];
        });

        return response()->json([
            'id'             => $b->id,
            'booking_code'   => $b->booking_code,
            'customer' => [
                'name'  => $b->user?->name,
                'email' => $b->user?->email,
                'phone' => $b->user?->phone,
            ],
            'movie' => [
                'title'      => $b->showtime?->movie?->title ?? 'Không xác định',
                'poster_url' => $b->showtime?->movie?->poster_url,
                'duration'   => $b->showtime?->movie?->duration,
            ],
            'room_name'      => $b->showtime?->room?->name ?? '—',
            'showtime_at'    => $b->showtime?->start_time
                ? $b->showtime->start_time->format('H:i - d/m/Y') : null,
            'format'         => $b->showtime?->format,
            'translation'    => $b->showtime?->translation,
            'seats'          => $seats,
            'combos'         => $combos,
            'seat_count'     => $seats->count(),
            'combo_count'    => $combos->sum('quantity'),
            'subtotal'       => (float) $b->subtotal,
            'discount_amount' => (float) $b->discount_amount,
            'total_amount'   => (float) $b->total_amount,
            'payment_method' => $b->payment_method,
            'payment_status' => $b->payment_status,
            'booking_status' => $b->booking_status,
            'voucher_code'   => $b->voucher?->code,
            'created_at'     => $b->created_at?->format('H:i - d/m/Y'),
        ], 200);
    }

    /**
     * Soát vé / Quét mã QR
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $booking = Booking::where('booking_code', $request->code)->first();

        if (!$booking) {
            return response()->json(['message' => 'Mã vé không tồn tại trong hệ thống.'], 404);
        }

        if ($booking->payment_status !== 'paid') {
            return response()->json(['message' => 'Đơn hàng này chưa được thanh toán thành công.'], 400);
        }

        if ($booking->booking_status === 'completed') {
            return response()->json(['message' => 'Vé này đã được sử dụng (soát vé rồi).'], 400);
        }

        // Cập nhật trạng thái vé
        $booking->booking_status = 'completed';
        $booking->save();

        // Cập nhật chi tiết ghế đã soát
        $booking->bookingDetails()->update(['is_checked_in' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Soát vé hợp lệ.',
            'data' => [
                'booking_code' => $booking->booking_code
            ]
        ], 200);
    }
}
