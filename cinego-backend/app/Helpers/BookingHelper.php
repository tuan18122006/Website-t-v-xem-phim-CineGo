<?php

namespace App\Helpers;

use App\Models\Showtime;
use App\Models\PricingRule;
use App\Models\Seat;
use App\Models\Combo;

class BookingHelper
{
    /**
     * Tính giá vé dựa trên pricing_snapshot của suất chiếu.
     * Snapshot được chốt lúc tạo suất chiếu, đảm bảo giá thanh toán
     * khớp chính xác với giá đã hiển thị cho khách hàng lúc chọn ghế.
     */
    public static function calculateSeats(int $showtimeId, array $seatIds): array
    {
        // Lấy giá từ snapshot của suất chiếu (được chốt lúc admin tạo suất)
        $showtime = Showtime::find($showtimeId);
        $snapshot = $showtime?->pricing_snapshot ?? [];

        // Dựng bảng giá từ snapshot
        $prices = [
            'standard' => (float) ($snapshot['standard_price'] ?? 0),
            'vip'      => (float) ($snapshot['vip_price'] ?? 0),
            'couple'   => (float) ($snapshot['couple_price'] ?? 0),
        ];

        // Nếu snapshot trống (suất chiếu cũ chưa có snapshot), fallback về PricingRule toàn hệ thống
        if (!$prices['standard'] && !$prices['vip'] && !$prices['couple']) {
            $rule = PricingRule::first();
            $prices = [
                'standard' => (float) ($rule?->standard_price ?? 50000),
                'vip'      => (float) ($rule?->vip_price ?? 70000),
                'couple'   => (float) ($rule?->couple_price ?? 120000),
            ];
        }

        $seats = Seat::whereIn('id', $seatIds)->get();
        $subtotal = 0.00;
        $details = [];

        foreach ($seats as $seat) {
            $type = $seat->type ?? 'standard';
            $price = $prices[$type] ?? $prices['standard'];
            $subtotal += $price;
            $details[] = [
                'seat_id' => $seat->id,
                'price'   => $price,
            ];
        }

        return [
            'subtotal' => $subtotal,
            'details'  => $details,
        ];
    }

    /**
     * Calculate combo prices based on combo IDs and quantities.
     */
    public static function calculateCombos(array $combosInput): array
    {
        $subtotal = 0.00;
        $details = [];

        if (empty($combosInput)) {
            return [
                'subtotal' => $subtotal,
                'details' => $details
            ];
        }

        $comboIds = array_column($combosInput, 'id');
        $combos = Combo::whereIn('id', $comboIds)->get()->keyBy('id');

        foreach ($combosInput as $item) {
            $comboId = $item['id'];
            $qty = $item['quantity'] ?? 0;
            if ($qty <= 0) continue;

            $combo = $combos->get($comboId);
            if ($combo) {
                $price = $combo->price;
                $linePrice = $price * $qty;
                $subtotal += $linePrice;
                $details[] = [
                    'combo_id' => $combo->id,
                    'quantity' => $qty,
                    'price' => $price
                ];
            }
        }

        return [
            'subtotal' => $subtotal,
            'details' => $details
        ];
    }
}
