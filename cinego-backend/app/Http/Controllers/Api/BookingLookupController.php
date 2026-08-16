<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingCheckIn;
use Illuminate\Http\Request;

class BookingLookupController extends Controller
{

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

                        $query->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
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

    public function show($id)
    {
        $b = Booking::with([
            'user:id,name,email,phone',
            'showtime.movie:id,title,poster_url,duration',
            'showtime.room:id,name',
            'bookingDetails.seat:id,row,number,type',
            'bookingCombos.combo:id,name,image_url',
            'voucher:id,code',
            'refundRequests:id,booking_id,status',
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

        $checkIns = BookingCheckIn::where('booking_id', $b->id)
            ->orderBy('checked_in_at')
            ->get()
            ->map(function ($c) {
                return [
                    'checked_in_at' => optional($c->checked_in_at)->format('H:i:s d/m/Y'),
                    'reason'        => $c->reason,
                ];
            });

        return response()->json([
            'id'             => $b->id,
            'booking_code'   => $b->booking_code,
            'check_in_count' => $checkIns->count(),
            'check_ins'      => $checkIns,
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
            'order_status' => $b->order_status,
            'voucher_code'   => $b->voucher?->code,
            'created_at'     => $b->created_at?->format('H:i - d/m/Y'),
        ], 200);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'reason' => 'nullable|string|max:255',
        ]);

        $booking = Booking::where('booking_code', $request->code)->first();

        if (!$booking) {
            return response()->json(['message' => 'Mã vé không tồn tại trong hệ thống.'], 404);
        }

        if ($booking->payment_status !== 'paid') {
            return response()->json(['message' => 'Đơn hàng này chưa được thanh toán thành công.'], 400);
        }

        $count = BookingCheckIn::where('booking_id', $booking->id)->count();
        $isReprint = $count >= 1;
        $reason = trim((string) $request->reason);

        if ($isReprint && $reason === '') {
            return response()->json([
                'needs_reason'   => true,
                'check_in_count' => $count,
                'message'        => 'Vé đã được soát ' . $count . ' lần trước đó. Nhập lí do để soát / in lại vé.',
            ], 422);
        }

        $now = now();
        BookingCheckIn::create([
            'booking_id'    => $booking->id,
            'checked_in_at' => $now,
            'reason'        => $isReprint ? $reason : null,
        ]);

        if (!$isReprint) {
            $booking->booking_status = 'completed';
            $booking->save();
            $booking->bookingDetails()->update(['is_checked_in' => true]);
        }

        return response()->json([
            'success'        => true,
            'is_reprint'     => $isReprint,
            'check_in_count' => $count + 1,
            'checked_in_at'  => $now->format('H:i:s d/m/Y'),
            'message'        => $isReprint ? 'Soát / in lại vé thành công.' : 'Soát vé hợp lệ.',
            'data'           => ['booking_code' => $booking->booking_code],
        ], 200);
    }
}
