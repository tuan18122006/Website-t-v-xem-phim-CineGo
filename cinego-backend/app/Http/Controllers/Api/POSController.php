<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Combo;
use App\Models\Booking;
use App\Mail\BookingSuccessMail;
use App\Services\BookingService;
use App\Services\LoyaltyService;
use App\Services\VNPayService;

class POSController extends Controller
{
    protected $bookingService;
    protected $loyaltyService;
    protected $vnpayService;

    public function __construct(BookingService $bookingService, LoyaltyService $loyaltyService, VNPayService $vnpayService)
    {
        $this->bookingService = $bookingService;
        $this->loyaltyService = $loyaltyService;
        $this->vnpayService = $vnpayService;
    }

    private function sendBookingEmail($booking): void
    {
        try {
            $email = $booking->user?->email;
            if ($email && !str_ends_with($email, '@cinego.local')) {
                Mail::to($email)->send(new BookingSuccessMail($booking));
            }
        } catch (\Throwable $e) {
            report($e);
        }
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

        $email = ($data['email'] ?? null) ?: ('walkin_' . preg_replace('/\D/', '', $data['phone']) . '@cinego.local');
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
            'customer_id'           => 'required|integer|exists:users,id',
            'total_amount'          => 'required|numeric'
        ]);

        try {
            $booking = $this->bookingService->createBooking(
                $request->showtime_id,
                $request->seat_ids,
                $request->combos ?? [],
                $request->payment_method,
                $request->customer_id,
                $request->voucher_id,
                'paid',
                null,
                []
            );

            $booking->booking_status = 'confirmed';
            $booking->save();

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

            if ($booking->user) {
                try {
                    $this->loyaltyService->processBookingPoints(
                        $booking->user,
                        $booking->total_amount,
                        $booking
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $this->sendBookingEmail($booking);

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

    public function createPOSPayment(Request $request)
    {
        $request->validate([
            'showtime_id'         => 'required|integer|exists:showtimes,id',
            'seat_ids'            => 'required|array|min:1',
            'seat_ids.*'          => 'required|integer|exists:seats,id',
            'combos'              => 'nullable|array',
            'combos.*.id'         => 'required|integer|exists:combos,id',
            'combos.*.quantity'   => 'required|integer|min:1',
            'customer_id'         => 'required|integer|exists:users,id',
            'total_amount'        => 'required|numeric',
        ]);

        $txnRef = 'CG' . time() . rand(100, 999);

        try {
            $booking = $this->bookingService->createBooking(
                $request->showtime_id,
                $request->seat_ids,
                $request->combos ?? [],
                'vnpay',
                $request->customer_id,
                null,
                'pending',
                $txnRef,
                []
            );
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $paymentUrl = $this->buildVNPayUrl([
            'txn_ref'     => $txnRef,

            'order_info'  => 'Thanh toán vé phim - ' . $booking->booking_code,
            'amount'      => $booking->total_amount,
            'ip_address'  => $request->ip(),
            'expire_date' => Carbon::now()->addMinutes(15)->format('YmdHis'),
        ]);

        return response()->json([
            'success'      => true,
            'booking_id'   => $booking->id,
            'booking_code' => $booking->booking_code,
            'payment_url'  => $paymentUrl,
        ]);
    }

    private function buildVNPayUrl(array $data): string
    {
        $input = [
            'vnp_Version'    => '2.1.0',
            'vnp_TmnCode'    => config('vnpay.tmn_code'),
            'vnp_Amount'     => $data['amount'] * 100,
            'vnp_Command'    => 'pay',
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_ExpireDate' => $data['expire_date'],
            'vnp_CurrCode'   => 'VND',
            'vnp_IpAddr'     => $data['ip_address'],
            'vnp_Locale'     => 'vn',
            'vnp_OrderInfo'  => $data['order_info'],
            'vnp_OrderType'  => 'other',
            'vnp_ReturnUrl'  => url('/api/staff/pos/vnpay-return'),
            'vnp_TxnRef'     => $data['txn_ref'],
        ];
        ksort($input);

        $hashData = '';
        $query = '';
        $i = 0;
        foreach ($input as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . '=' . urlencode($value);
            } else {
                $hashData .= urlencode($key) . '=' . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . '=' . urlencode($value) . '&';
        }

        $query = rtrim($query, '&');
        $secureHash = hash_hmac('sha512', $hashData, config('vnpay.hash_secret'));
        return config('vnpay.url') . '?' . $query . '&vnp_SecureHash=' . $secureHash;
    }

    public function posVnpayReturn(Request $request)
    {
        $params   = $request->all();
        $valid    = $this->vnpayService->verifyReturnUrl($params);
        $frontend = env('FRONTEND_URL', 'http://localhost:5173');
        $booking  = Booking::where('vnp_txn_ref', $params['vnp_TxnRef'] ?? null)->first();

        if (!$valid || !$booking) {
            if ($booking) {
                $this->bookingService->markAsFailed($booking);
            }
            return redirect($frontend . '/staff/dashboard?pos_pay=invalid');
        }

        if (($params['vnp_ResponseCode'] ?? null) === '00') {
            try {
                if ($booking->payment_status !== 'paid') {
                    $this->bookingService->markAsPaid($booking);

                    if ($booking->user) {
                        try {
                            $this->loyaltyService->processBookingPoints($booking->user, $booking->total_amount, $booking);
                        } catch (\Throwable $e) {
                            report($e);
                        }
                    }

                    $this->sendBookingEmail($booking);
                }
            } catch (\Throwable $e) {
                report($e);
            }

            return redirect($frontend . '/staff/dashboard?pos_pay=success&code=' . $booking->booking_code);
        }

        $isCancelled = ($params['vnp_ResponseCode'] ?? null) === '24';
        $isCancelled ? $this->bookingService->markAsCancelled($booking) : $this->bookingService->markAsFailed($booking);

        return redirect($frontend . '/staff/dashboard?pos_pay=' . ($isCancelled ? 'cancelled' : 'failed') . '&code=' . $booking->booking_code);
    }
}
