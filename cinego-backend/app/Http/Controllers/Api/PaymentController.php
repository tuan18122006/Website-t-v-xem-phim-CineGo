<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VNPayService;
use App\Services\BookingService;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\BookingSuccessMail;
use App\Services\LoyaltyService;

class PaymentController extends Controller
{
    protected $vnpayService;
    protected $bookingService;
    protected $loyaltyService;

    public function __construct(VNPayService $vnpayService, BookingService $bookingService, LoyaltyService $loyaltyService)
    {
        $this->vnpayService = $vnpayService;
        $this->bookingService = $bookingService;
        $this->loyaltyService = $loyaltyService;
    }

    public function createPayment(Request $request)
    {
        $request->validate([
            'showtime_id'           => 'required|integer|exists:showtimes,id',
            'seat_ids'              => 'required|array|min:1',
            'seat_ids.*'            => 'required|integer|exists:seats,id',
            'combos'                => 'nullable|array',
            'combos.*.id'          => 'required|integer|exists:combos,id',
            'combos.*.quantity'    => 'required|integer|min:1',
            'used_user_combo_ids'   => 'nullable|array',
            'used_user_combo_ids.*' => 'integer',
            'voucher_id'            => 'nullable|exists:vouchers,id',
            'payment_method'        => 'required|string',
            'total_amount'          => 'required|numeric',
        ]);

        $txnRef = 'CG' . time() . rand(100, 999);

        try {
            $booking = $this->bookingService->createBooking(
                $request->showtime_id,
                $request->seat_ids,
                $request->combos ?? [],
                $request->payment_method,
                auth()->id(),
                $request->voucher_id,
                'pending',
                $txnRef,
                $request->used_user_combo_ids ?? []
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }

        $paymentUrl = $this->vnpayService->createPaymentUrl([
            'txn_ref'    => $txnRef,
            'order_info' => 'Thanh toán vé phim - ' . $booking->booking_code,
            'amount'     => $booking->total_amount,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'payment_url' => $paymentUrl,
        ]);
    }

    public function vnpayReturn(Request $request)
    {
        $vnpParams = $request->all();
        $isValid = $this->vnpayService->verifyReturnUrl($vnpParams);
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        $booking = Booking::where('vnp_txn_ref', $vnpParams['vnp_TxnRef'] ?? null)->first();

        if (!$isValid || !$booking) {
            if ($booking) {
                $this->bookingService->markAsFailed($booking);
            }
            return redirect($frontendUrl . '/payment/result?status=invalid');
        }

        if (($vnpParams['vnp_ResponseCode'] ?? null) === '00') {

            if ($booking->payment_status !== 'paid') {

                $this->bookingService->markAsPaid($booking);

                $this->handlePaymentSuccess($booking);

                try {
                    if ($booking->user && $booking->user->email) {
                        Mail::to($booking->user->email)->send(new BookingSuccessMail($booking));
                    }
                } catch (\Exception $mailEx) {
                    \Illuminate\Support\Facades\Log::error('Lỗi gửi mail VNPAY: ' . $mailEx->getMessage());
                }
            }

            return redirect(
                $frontendUrl . '/payment/result?status=success&code=' . $booking->booking_code
            );
        }

        $this->bookingService->markAsFailed($booking);
        return redirect($frontendUrl . '/payment/result?status=failed');
    }

    public function handlePaymentSuccess($booking)
    {
        if (!$booking->user) {
            return;
        }

        DB::transaction(function () use ($booking) {
            // 1. Trừ Voucher đã dùng
            if ($booking->voucher_id) {
                DB::table('user_vouchers')
                    ->where('voucher_id', $booking->voucher_id)
                    ->where('user_id', $booking->user_id)
                    ->where('is_used', false)
                    ->limit(1)
                    ->update([
                        'is_used'    => true,
                        'used_at'    => now(),
                        'updated_at' => now()
                    ]);
            }

            // 2. Trừ Quà tặng (Lấy từ bảng booking_combos đã lưu khi đặt vé)
            $bookingCombos = DB::table('booking_combos')
                ->where('booking_id', $booking->id)
                ->get();

            foreach ($bookingCombos as $bCombo) {
                DB::table('user_combos')
                    ->where('user_id', $booking->user_id)
                    ->where('combo_id', $bCombo->combo_id)
                    ->where('is_used', false)
                    ->limit($bCombo->quantity ?? 1)
                    ->update([
                        'is_used'    => true,
                        'updated_at' => now()
                    ]);
            }

            // 3. Tích điểm thành viên
            $this->loyaltyService->processBookingPoints(
                $booking->user,
                $booking->total_amount,
                $booking
            );
        });
    }
}