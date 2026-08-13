<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Combo;
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
        $query = trim($request->get('query', ''));
        if (mb_strlen($query) < 2) {
            return response()->json(['success' => false, 'message' => 'Nhập ít nhất 2 ký tự (tên / SĐT / email).', 'data' => []]);
        }

        $users = User::where('role', 'customer')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('phone', 'like', '%' . $query . '%')
                  ->orWhere('email', 'like', '%' . $query . '%');
            })
            ->limit(10)
            ->get(['id', 'name', 'phone', 'email', 'membership_tier', 'loyalty_points']);

        return response()->json(['success' => true, 'data' => $users]);
    }

    /**
     * Danh sách combo đang bán cho màn hình POS (kèm tồn kho để chặn chọn khi hết hàng).
     */
    public function listCombos()
    {
        $combos = Combo::where('status', 'active')
            ->get(['id', 'name', 'description', 'price', 'image_url', 'stock'])
            ->map(function ($c) {
                return [
                    'id'          => $c->id,
                    'name'        => $c->name,
                    'description' => $c->description,
                    'price'       => (float) $c->price,
                    'image_url'   => $c->image_url,
                    'stock'       => (int) $c->stock,
                    'available'   => (int) $c->stock > 0,
                ];
            });

        return response()->json(['success' => true, 'data' => $combos]);
    }

    /**
     * Tạo nhanh 1 khách hàng tại quầy (khách vãng lai) để gắn vào đơn + tích điểm.
     * Nếu đã có khách trùng SĐT thì trả về khách đó, tránh tạo trùng.
     */
    public function quickCreateCustomer(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:50',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $existing = User::where('role', 'customer')->where('phone', $data['phone'])->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'existed' => true,
                'data'    => $existing->only(['id', 'name', 'phone', 'email', 'membership_tier', 'loyalty_points']),
            ]);
        }

        // Email tự sinh nếu không nhập (khách vãng lai) — đảm bảo không trùng
        $email = $data['email'] ?: ('walkin_' . preg_replace('/\D/', '', $data['phone']) . '@cinego.local');
        if (User::where('email', $email)->exists()) {
            $email = 'walkin_' . preg_replace('/\D/', '', $data['phone']) . '_' . Str::lower(Str::random(4)) . '@cinego.local';
        }

        $user = User::create([
            'name'     => $data['name'],
            'phone'    => $data['phone'],
            'email'    => $email,
            'password' => Hash::make(Str::random(16)),
            'role'     => 'customer',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $user->only(['id', 'name', 'phone', 'email', 'membership_tier', 'loyalty_points']),
        ], 201);
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

            // Xử lý điểm loyalty — KHÔNG để lỗi tích điểm làm hỏng việc tạo vé.
            // (LoyaltyService thuộc phần khác; nếu nó lỗi thì vẫn phải bán được vé.)
            if ($booking->user) {
                try {
                    $this->loyaltyService->processBookingPoints(
                        $booking->user,
                        $booking->total_amount,
                        $booking
                    );
                } catch (\Throwable $e) {
                    report($e); // ghi log, bỏ qua để không chặn việc bán vé
                }
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
