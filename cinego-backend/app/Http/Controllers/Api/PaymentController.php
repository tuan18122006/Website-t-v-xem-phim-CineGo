<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VNPayService;
use App\Services\BookingService;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderSuccessMail;
use App\Mail\BookingSuccessMail;

class PaymentController extends Controller
{
    protected $vnpayService;
    protected $bookingService;

    public function __construct(VNPayService $vnpayService, BookingService $bookingService)
    {
        $this->vnpayService = $vnpayService;
        $this->bookingService = $bookingService;
    }

    public function createPayment(Request $request)
    {
        $request->validate([
            'showtime_id' => 'required|integer|exists:showtimes,id',
            'seat_ids' => 'required|array|min:1',
            'seat_ids.*' => 'required|integer|exists:seats,id',
            'combos' => 'nullable|array',
            'combos.*.id' => 'required|integer|exists:combos,id',
            'combos.*.quantity' => 'required|integer|min:1',
            'voucher_id' => 'nullable|exists:vouchers,id',
            'payment_method' => 'required|string',
            'total_amount' => 'required|numeric',
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
                $txnRef
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }

        $paymentUrl = $this->vnpayService->createPaymentUrl([
            'txn_ref' => $txnRef,
            'order_info' => 'Thanh toán vé xe phim - ' . $booking->booking_code,
            'amount' => $booking->total_amount,
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
            return redirect($frontendUrl . '/payment-result?status=invalid');
        }

        if (($vnpParams['vnp_ResponseCode'] ?? null) === '00') {

            $this->bookingService->markAsPaid($booking);

            Mail::to($booking->user->email)
                ->send(new OrderSuccessMail($booking));

            return redirect(
                $frontendUrl .
                    '/payment-result?status=success&code=' .
                ->send(new BookingSuccessMail($booking));

            return redirect(
                $frontendUrl .
                    '/payment/result?status=success&code=' .
                    $booking->booking_code
            );
        }

        $this->bookingService->markAsFailed($booking);
        return redirect($frontendUrl . '/payment-result?status=failed');
    }
}
