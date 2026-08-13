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
use Carbon\Carbon;

class PaymentController extends Controller
{
    protected const MAX_PAYMENT_RETRIES = 1;

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
            'combos.*.id'           => 'required|integer|exists:combos,id',
            'combos.*.quantity'     => 'required|integer|min:1',
            'used_user_combo_ids'   => 'nullable|array',
            'used_user_combo_ids.*' => 'integer',
            'voucher_id'            => 'nullable|exists:vouchers,id',
            'payment_method'        => 'required|string',
            'total_amount'          => 'required|numeric',
        ]);

        $hold = DB::table('seat_holds')
            ->where('user_id', auth()->id())
            ->where('showtime_id', $request->showtime_id)
            ->whereIn('seat_id', $request->seat_ids)
            ->where('expires_at', '>', now())
            ->orderBy('expires_at', 'asc')
            ->first();

        $showtime = \App\Models\Showtime::find($request->showtime_id);
        if ($showtime && Carbon::parse($showtime->start_time)->lte(Carbon::now())) {
            return response()->json([
                'success' => false,
                'message' => 'Suất chiếu đã bắt đầu hoặc kết thúc. Vui lòng liên hệ quầy vé để được hỗ trợ.'
            ], 400);
        }

        if (!$hold) {
            return response()->json([
                'success' => false,
                'message' => 'Thời gian giữ ghế đã hết. Vui lòng thực hiện lại!'
            ], 400);
        }

        $vnpayExpireDate = \Carbon\Carbon::parse($hold->expires_at)->format('YmdHis');
        
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

        if ($request->payment_method === 'bank_transfer') {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            return response()->json([
                'success'     => true,
                'booking_id'  => $booking->id,
                'payment_url' => $frontendUrl . '/payment/qrcode?booking_id=' . $booking->id,
            ]);
        }

        $paymentUrl = $this->vnpayService->createPaymentUrl([
            'txn_ref'     => $txnRef,
            'order_info'  => 'Thanh toán vé phim - ' . $booking->booking_code,
            'amount'      => $booking->total_amount,
            'ip_address'  => $request->ip(),
            'expire_date' => $vnpayExpireDate,
        ]);

        return response()->json([
            'success'     => true,
            'booking_id'  => $booking->id,
            'payment_url' => $paymentUrl,
        ]);
    }

    /**
     * Bảng mã lỗi VNPay -> thông báo cho khách hàng
     */
    protected const VNPAY_ERROR_CODES = [
        '01' => 'Giao dịch đã tồn tại, vui lòng kiểm tra lại.',
        '02' => 'Merchant không hợp lệ.',
        '03' => 'Dữ liệu gửi sang VNPay không hợp lệ.',
        '04' => 'Khóa bí mật không đúng.',
        '05' => 'Tài khoản VNPay không tồn tại hoặc bị khóa.',
        '06' => 'Giao dịch đang chờ thanh toán.',
        '07' => 'Trừ tiền thành công nhưng giao dịch bị nghi ngờ, vui lòng liên hệ hỗ trợ.',
        '08' => 'Giao dịch tại Ngân hàng đang xử lý.',
        '09' => 'Thẻ chưa đăng ký Internet Banking.',
        '10' => 'Xác thực giao dịch không thành công.',
        '11' => 'Đã hết hạn chờ thanh toán, vui lòng thanh toán lại.',
        '12' => 'Thẻ bị khóa.',
        '13' => 'Sai mã OTP, vui lòng thử lại.',
        '24' => 'Bạn đã hủy giao dịch.',
        '51' => 'Tài khoản không đủ số dư.',
        '65' => 'Tài khoản vượt quá hạn mức giao dịch trong ngày.',
        '75' => 'Ngân hàng đang bảo trì.',
        '79' => 'Sai mật khẩu thanh toán quá số lần quy định.',
        '99' => 'Lỗi không xác định từ VNPay.',
    ];

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
            return redirect($frontendUrl . '/payment/result?status=invalid' . ($booking ? '&booking_id=' . $booking->id : ''));
        }

        if (($vnpParams['vnp_ResponseCode'] ?? null) === '00') {
            try {
                if ($booking->payment_status !== 'paid') {
                    $this->bookingService->markAsPaid($booking);

                    $this->handlePaymentSuccess($booking);

                    if ($booking->user) {
                        $booking->user->notify(new \App\Notifications\BookingConfirmedNotification(
                            $booking->booking_code,
                            "Đơn hàng " . $booking->booking_code . " đã được thanh toán thành công qua VNPay."
                        ));
                    }

                    $admins = \App\Models\User::where('role', 'admin')->get();
                    foreach ($admins as $admin) {
                        $admin->notify(new \App\Notifications\BookingConfirmedNotification(
                            $booking->booking_code,
                            "Khách hàng đã thanh toán thành công đơn vé " . $booking->booking_code . " qua VNPay."
                        ));
                    }

                    if ($booking->user && $booking->user->email) {
                        Mail::to($booking->user->email)->send(new BookingSuccessMail($booking));
                    }
                }
            } catch (\Exception $ex) {
                \Illuminate\Support\Facades\Log::error('Lỗi xử lý kết quả VNPAY: ' . $ex->getMessage());
            }

            return redirect(
                $frontendUrl . '/payment/result?status=success&code=' . $booking->booking_code . '&booking_id=' . $booking->id
            );
        }

        $isUserCancelled = ($vnpParams['vnp_ResponseCode'] ?? null) === '24';

        if ($isUserCancelled) {
            $this->bookingService->markAsCancelled($booking);
        } else {
            $this->bookingService->markAsFailed($booking);
        }

        DB::table('seat_holds')
            ->where('showtime_id', $booking->showtime_id)
            ->whereIn('seat_id', $booking->bookingDetails()->pluck('seat_id'))
            ->where('user_id', $booking->user_id)
            ->delete();

        $code = $vnpParams['vnp_ResponseCode'] ?? '';
        $reason = self::VNPAY_ERROR_CODES[$code] ?? 'Giao dịch không thành công, vui lòng thử lại.';

        $resultStatus = $isUserCancelled ? 'cancelled' : 'failed';

        return redirect(
            $frontendUrl . '/payment/result?status=' . $resultStatus
            . '&code=' . urlencode($code)
            . '&reason=' . urlencode($reason)
            . '&booking_id=' . $booking->id
        );
    }

    public function retryPayment(Request $request, $id)
    {
        $booking = Booking::where('user_id', auth()->id())->findOrFail($id);

        if ($booking->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng này đã thanh toán thành công.',
            ], 400);
        }

        if ($booking->payment_status === 'waiting_confirmation') {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng đang chờ xác nhận chuyển khoản, không thể thanh toán lại.',
            ], 400);
        }

        if ($booking->payment_status === 'payment_cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã hủy thanh toán đơn hàng này. Vui lòng đặt vé mới.',
            ], 400);
        }

        if ((int) $booking->retry_count >= self::MAX_PAYMENT_RETRIES) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng đã hết lượt thanh toán lại (tối đa ' . self::MAX_PAYMENT_RETRIES . ' lần). Vui lòng đặt vé mới.',
            ], 400);
        }

        $showtime = $booking->showtime;
        if ($showtime && Carbon::parse($showtime->start_time)->lte(Carbon::now())) {
            return response()->json([
                'success' => false,
                'message' => 'Suất chiếu đã bắt đầu hoặc kết thúc. Không thể thanh toán lại. Vui lòng liên hệ quầy vé để được hỗ trợ.',
            ], 400);
        }

        try {
            $seatIds = $booking->bookingDetails()->pluck('seat_id')->all();

            $holdExpiresAt = DB::transaction(function () use ($booking, $seatIds) {
                $now = Carbon::now();

                $isBooked = DB::table('booking_details')
                    ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                    ->where('bookings.showtime_id', $booking->showtime_id)
                    ->whereIn('booking_details.seat_id', $seatIds)
                    ->where('bookings.payment_status', 'paid')
                    ->exists();

                if ($isBooked) {
                    throw new \Exception('Một số ghế của đơn hàng đã không còn trống. Không thể thanh toán lại.');
                }

                $heldByOthers = DB::table('seat_holds')
                    ->where('showtime_id', $booking->showtime_id)
                    ->whereIn('seat_id', $seatIds)
                    ->where('user_id', '!=', auth()->id())
                    ->where('expires_at', '>', $now)
                    ->exists();

                if ($heldByOthers) {
                    throw new \Exception('Một số ghế của đơn hàng đang được khách khác giữ. Không thể thanh toán lại.');
                }

                DB::table('seat_holds')
                    ->where('user_id', auth()->id())
                    ->where('showtime_id', $booking->showtime_id)
                    ->whereIn('seat_id', $seatIds)
                    ->delete();

                $expiresAt = $now->copy()->addMinutes(10);
                $holdsData = [];

                foreach ($seatIds as $seatId) {
                    $holdsData[] = [
                        'user_id'     => auth()->id(),
                        'showtime_id' => $booking->showtime_id,
                        'seat_id'     => $seatId,
                        'expires_at'  => $expiresAt,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }

                DB::table('seat_holds')->insert($holdsData);

                return $expiresAt;
            });

            $combos = $booking->bookingCombos()
                ->where('price_at_purchase', '>', 0)
                ->get()
                ->map(fn ($bc) => ['id' => $bc->combo_id, 'quantity' => $bc->quantity])
                ->values()
                ->all();

            $paymentMethod = $booking->payment_method;
            $txnRef = 'CG' . time() . rand(100, 999);
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

            $newBooking = $this->bookingService->createBooking(
                $booking->showtime_id,
                $seatIds,
                $combos,
                $paymentMethod,
                auth()->id(),
                $booking->voucher_id,
                'pending',
                $txnRef,
                []
            );

            $this->bookingService->markAsFailed($booking);
            $booking->retry_count = ((int) $booking->retry_count) + 1;
            $booking->save();

            $paymentUrl = null;

            if ($paymentMethod === 'bank_transfer') {
                $paymentUrl = $frontendUrl . '/payment/qrcode?booking_id=' . $newBooking->id;
            } else {
                $paymentUrl = $this->vnpayService->createPaymentUrl([
                    'txn_ref'     => $txnRef,
                    'order_info'  => 'Thanh toán vé phim - ' . $newBooking->booking_code,
                    'amount'      => $newBooking->total_amount,
                    'ip_address'  => $request->ip(),
                    'expire_date' => $holdExpiresAt->format('YmdHis'),
                ]);
            }

            return response()->json([
                'success'           => true,
                'booking_id'        => $newBooking->id,
                'payment_url'       => $paymentUrl,
                'expires_at'        => $holdExpiresAt->toIso8601String(),
                'seconds_remaining' => 600,
                'retries_left'      => max(0, self::MAX_PAYMENT_RETRIES - ((int) $booking->retry_count)),
            ]);
        } catch (\Exception $e) {
            if (isset($holdExpiresAt) && !isset($newBooking)) {
                DB::table('seat_holds')
                    ->where('user_id', auth()->id())
                    ->where('showtime_id', $booking->showtime_id)
                    ->whereIn('seat_id', $booking->bookingDetails()->pluck('seat_id')->all())
                    ->delete();
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function handlePaymentSuccess($booking)
    {
        if (!$booking->user) {
            return;
        }

        DB::transaction(function () use ($booking) {
            if ($booking->voucher_id) {
                DB::table('user_vouchers')
                    ->where('voucher_id', $booking->voucher_id)
                    ->where('user_id', $booking->user_id)
                    ->where('is_used', false)
                    ->limit(1)
                    ->update([
                        'is_used'    => true,
                        'booking_id' => $booking->id,
                        'used_at'    => now(),
                        'updated_at' => now()
                    ]);
            }

            $bookingCombos = DB::table('booking_combos')
                ->where('booking_id', $booking->id)
                ->where('price_at_purchase', 0)
                ->get();

            foreach ($bookingCombos as $bCombo) {
                DB::table('user_combos')
                    ->where('user_id', $booking->user_id)
                    ->where('combo_id', $bCombo->combo_id)
                    ->where('is_used', false)
                    ->limit($bCombo->quantity ?? 1)
                    ->update([
                        'is_used'    => true,
                        'booking_id' => $booking->id,
                        'used_at'    => now(),
                        'updated_at' => now()
                    ]);
            }

            $this->loyaltyService->processBookingPoints(
                $booking->user,
                $booking->total_amount,
                $booking
            );
        });
    }
}
