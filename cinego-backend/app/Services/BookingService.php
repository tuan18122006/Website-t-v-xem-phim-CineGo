<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingCombo;
use App\Models\Showtime;
use App\Models\Voucher;
use App\Models\Combo;

use App\Helpers\BookingHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookingService
{
    /**
     * Tạo booking (dùng chung cho cả thanh toán trực tiếp lẫn VNPay).
     * $paymentStatus: 'paid' (demo/tiền mặt) hoặc 'pending' (chờ VNPay xác nhận)
     */
    public function createBooking(
        int $showtimeId,
        array $seatIds,
        array $combosInput,
        string $paymentMethod,
        int $userId,
        ?int $voucherId = null,
        string $paymentStatus = 'paid',
        ?string $vnpTxnRef = null,
        array $usedUserComboIds = []
    ): Booking {
        return DB::transaction(function () use (
            $showtimeId,
            $seatIds,
            $combosInput,
            $paymentMethod,
            $userId,
            $voucherId,
            $paymentStatus,
            $vnpTxnRef,
            $usedUserComboIds
        ) {

            $showtime = Showtime::findOrFail($showtimeId);
            $now = Carbon::now();

            foreach ($seatIds as $seatId) {

                $seat = DB::table('seats')
                    ->where('id', $seatId)
                    ->lockForUpdate()
                    ->first();

                if (!$seat) {
                    throw new \Exception('Ghế không tồn tại.');
                }

                if ($seat->room_id !== $showtime->room_id) {
                    throw new \Exception('Ghế không thuộc phòng chiếu.');
                }

                if ($seat->status !== 'available') {
                    throw new \Exception("Ghế {$seat->row}{$seat->number} không khả dụng.");
                }

                $isBooked = DB::table('booking_details')
                    ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                    ->where('bookings.showtime_id', $showtimeId)
                    ->where('booking_details.seat_id', $seatId)
                    ->where('bookings.payment_status', 'paid')
                    ->exists();

                if ($isBooked) {
                    throw new \Exception("Ghế {$seat->row}{$seat->number} đã có người đặt.");
                }

                $activeHold = DB::table('seat_holds')
                    ->where('showtime_id', $showtimeId)
                    ->where('seat_id', $seatId)
                    ->where('expires_at', '>', $now)
                    ->first();

                if ($activeHold && $activeHold->user_id != $userId) {
                    throw new \Exception("Ghế {$seat->row}{$seat->number} đang được giữ.");
                }
            }

            $seatsCalc = BookingHelper::calculateSeats($showtimeId, $seatIds);
            $combosCalc = BookingHelper::calculateCombos($combosInput);
            
            foreach ($combosCalc['details'] as $comboDetail) {
                $combo = Combo::lockForUpdate()->findOrFail($comboDetail['combo_id']);

                if ($combo->stock < $comboDetail['quantity']) {
                    throw new \Exception("{$combo->name} chỉ còn {$combo->stock} sản phẩm.");
                }

                if ($combo->type === 'combo') {
                    foreach ($combo->comboItems as $component) {
                        $subItem = Combo::lockForUpdate()->find($component->item_id);
                        if ($subItem) {
                            $requiredQty = $comboDetail['quantity'] * $component->quantity;
                            if ($subItem->stock < $requiredQty) {
                                throw new \Exception(
                                    "Thành phần '{$subItem->name}' trong '{$combo->name}' không đủ tồn kho (Cần {$requiredQty}, hiện còn {$subItem->stock})."
                                );
                            }
                        }
                    }
                }
            }
            $subtotal = $seatsCalc['subtotal'] + $combosCalc['subtotal'];

            $voucher = null;
            $discountAmount = 0;

            if ($voucherId) {

                $voucher = Voucher::find($voucherId);

                if ($voucher) {

                    if ($voucher->discount_type == 'percentage') {

                        $discountAmount = $subtotal * $voucher->discount_value / 100;

                        if ($voucher->max_discount_amount) {
                            $discountAmount = min(
                                $discountAmount,
                                $voucher->max_discount_amount
                            );
                        }
                    } else {

                        $discountAmount = $voucher->discount_value;
                    }
                }
            }

            $totalAmount = max(0, $subtotal - $discountAmount);

            do {
                $bookingCode = 'CG-' . mt_rand(100000, 999999);
            } while (Booking::where('booking_code', $bookingCode)->exists());

            $booking = Booking::create([
                'booking_code' => $bookingCode,
                'vnp_txn_ref'  => $vnpTxnRef,
                'user_id'      => $userId,
                'showtime_id'  => $showtimeId,
                'voucher_id'   => $voucher?->id,

                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount'   => $totalAmount,

                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'booking_status' => $paymentStatus == 'paid'
                    ? 'confirmed'
                    : 'pending',
            ]);

            foreach ($seatsCalc['details'] as $seatDetail) {

                BookingDetail::create([
                    'booking_id'    => $booking->id,
                    'seat_id'       => $seatDetail['seat_id'],
                    'price'         => $seatDetail['price'],
                    'ticket_code'   => 'TC-' . strtoupper(Str::random(10)),
                    'is_checked_in' => false,
                ]);
            }

            // 1. Lưu danh sách Combo mua thêm bằng tiền
            foreach ($combosCalc['details'] as $comboDetail) {

                BookingCombo::create([
                    'booking_id'        => $booking->id,
                    'combo_id'          => $comboDetail['combo_id'],
                    'quantity'          => $comboDetail['quantity'],
                    'price_at_purchase' => $comboDetail['price'],
                    'subtotal'          => $comboDetail['price'] * $comboDetail['quantity'],
                ]);
            }

            // 🔥 2. BỔ SUNG: Lưu các Mã Quà Tặng (Mã đổi Combo miễn phí) vào bảng booking_combos
            if (!empty($usedUserComboIds)) {
                foreach ($usedUserComboIds as $userComboId) {
                    $userCombo = DB::table('user_combos')
                        ->where('id', $userComboId)
                        ->where('user_id', $userId)
                        ->first();

                    if ($userCombo) {
                        BookingCombo::create([
                            'booking_id'        => $booking->id,
                            'combo_id'          => $userCombo->combo_id,
                            'quantity'          => 1,
                            'price_at_purchase' => 0, 
                            'subtotal'          => 0,
                        ]);
                    }
                }
            }

            if ($paymentStatus === 'paid') {
                DB::table('seat_holds')
                    ->where('showtime_id', $showtimeId)
                    ->whereIn('seat_id', $seatIds)
                    ->where('user_id', $userId)
                    ->delete();

                $this->deductComboStock($booking);
            }

            return $booking;
        });
    }

    public function markAsPaid(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {

            $booking->update([
                'payment_status' => 'paid',
                'booking_status' => 'confirmed',
            ]);

            $this->deductComboStock($booking);

            DB::table('seat_holds')
                ->where('showtime_id', $booking->showtime_id)
                ->whereIn(
                    'seat_id',
                    $booking->bookingDetails()->pluck('seat_id')
                )
                ->delete();

        });
    }

    public function markAsFailed(Booking $booking): void
    {
        $booking->update([
            'payment_status' => 'failed',
            'booking_status' => 'cancelled',
        ]);
    }

   private function deductComboStock(Booking $booking): void
{
    foreach ($booking->bookingCombos as $item) {
        $combo = Combo::lockForUpdate()->find($item->combo_id);

        if (!$combo) {
            continue;
        }

      
        if (isset($item->price) && (float)$item->price == 0) {
            continue;
        }
        if (isset($item->subtotal) && (float)$item->subtotal == 0) {
            continue;
        }

        if ($combo->stock < $item->quantity) {
            throw new \Exception("{$combo->name} không đủ số lượng.");
        }

        $combo->decrement('stock', $item->quantity);

        if ($combo->type === 'combo') {
            $comboComponents = $combo->comboItems()->get(); 

            foreach ($comboComponents as $component) {
                $subItem = Combo::lockForUpdate()->find($component->item_id);

                if (!$subItem) {
                    continue;
                }

                $totalDeductQuantity = $item->quantity * $component->quantity;

                if ($subItem->stock < $totalDeductQuantity) {
                    throw new \Exception(
                        "Thành phần '{$subItem->name}' trong '{$combo->name}' đã hết hoặc không đủ số lượng tồn kho."
                    );
                }

                $subItem->decrement('stock', $totalDeductQuantity);
            }
        }
    }
}
}