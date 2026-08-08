<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\BookingService;
use App\Services\LoyaltyService;

class POSController extends Controller
{
    protected $bookingService;
    protected $loyaltyService;

    public function __construct(BookingService $bookingService, LoyaltyService $loyaltyService)
    {
        $this->bookingService = $bookingService;
        $this->loyaltyService = $loyaltyService;
    }

    public function searchCustomer(Request $request)
    {
        $query = $request->get('query');
        if (empty($query)) {
            return response()->json(['success' => false, 'message' => 'Vui lòng nhập SĐT hoặc Email']);
        }

        $users = User::where('role', 'user')
            ->where(function ($q) use ($query) {
                $q->where('phone', 'like', '%' . $query . '%')
                  ->orWhere('email', 'like', '%' . $query . '%');
            })->get(['id', 'name', 'phone', 'email', 'membership_tier', 'cine_points']);

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function storePOSBooking(Request $request)
    {
        $request->validate([
            'showtime_id'           => 'required|integer|exists:showtimes,id',
            'seat_ids'              => 'required|array|min:1',
            'seat_ids.*'            => 'required|integer|exists:seats,id',
            'combos'                => 'nullable|array',
            'combos.*.id'          => 'required|integer|exists:combos,id',
            'combos.*.quantity'    => 'required|integer|min:1',
            'voucher_id'            => 'nullable|integer|exists:vouchers,id',
            'payment_method'        => 'required|string|in:cash,bank_transfer',
            'customer_id'           => 'nullable|integer|exists:users,id',
            'total_amount'          => 'required|numeric'
        ]);

        try {
            $booking = $this->bookingService->createBooking(
                $request->showtime_id,
                $request->seat_ids,
                $request->combos ?? [],
                $request->payment_method,
                $request->customer_id, // can be null for guest
                $request->voucher_id,
                'paid', // Immediate payment for POS
                null,
                []
            );

            // Cập nhật booking_status thành confirmed vì đã thu tiền tại quầy
            $booking->booking_status = 'confirmed';
            $booking->save();

            // Trừ voucher nếu có (chỉ trừ nếu có customer_id)
            if ($request->voucher_id && $request->customer_id) {
                DB::table('user_vouchers')
                    ->where('voucher_id', $request->voucher_id)
                    ->where('user_id', $request->customer_id)
                    ->where('is_used', false)
                    ->limit(1)
                    ->update([
                        'is_used'    => true,
                        'booking_id' => $booking->id,
                        'used_at'    => now(),
                        'updated_at' => now()
                    ]);
            }

            // Xử lý điểm loyalty
            if ($booking->user) {
                $this->loyaltyService->processBookingPoints(
                    $booking->user,
                    $booking->total_amount,
                    $booking
                );
            }

            return response()->json([
                'success'      => true,
                'message'      => 'Đặt vé thành công',
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
