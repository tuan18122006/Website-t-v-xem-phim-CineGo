<?php

namespace App\Helpers;

use App\Models\PriceConfig;
use App\Models\Seat;
use App\Models\Combo;
class BookingHelper
{
    /**
     * Calculate seat prices based on showtime and selected seat IDs.
     */
    public static function calculateSeats(int $showtimeId, array $seatIds): array
    {
        $prices = PriceConfig::where('showtime_id', $showtimeId)->pluck('price', 'seat_type')->toArray();
        
        // Fallback pricing if price configs are missing
        $defaultPrices = [
            'standard' => 75000.00,
            'vip' => 95000.00,
            'couple' => 140000.00,
        ];

        $seats = Seat::whereIn('id', $seatIds)->get();
        $subtotal = 0.00;
        $details = [];

        foreach ($seats as $seat) {
            $type = $seat->type;
            $price = $prices[$type] ?? $defaultPrices[$type] ?? 75000.00;
            $subtotal += $price;
            $details[] = [
                'seat_id' => $seat->id,
                'price' => $price
            ];
        }

        return [
            'subtotal' => $subtotal,
            'details' => $details
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
