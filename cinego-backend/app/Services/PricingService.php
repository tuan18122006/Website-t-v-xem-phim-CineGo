<?php

namespace App\Services;

use App\Models\PricingRule;
use App\Models\TimeBasedPricingRule;
use Carbon\Carbon;

class PricingService
{
    /**
     * Tính giá cho một loại ghế cụ thể tại một thời gian cụ thể
     *
     * @param string $seatType Loại ghế (standard, vip, couple)
     * @param Carbon $datetime Thời gian showtime
     * @param bool $includeTimeBasedRules Có áp dụng quy tắc theo thời gian hay không
     * @param int|null $movieId ID của phim (để áp dụng quy tắc riêng phim)
     * @return int Giá tiền
     */
    public static function calculatePrice(string $seatType, Carbon $datetime, bool $includeTimeBasedRules = true, ?int $movieId = null): int
    {
        // Lấy giá cơ bản từ PricingRule
        $pricingRule = PricingRule::first();

        $basePrice = match($seatType) {
            'standard' => $pricingRule?->standard_price ?? 50000,
            'vip' => $pricingRule?->vip_price ?? 70000,
            'couple' => $pricingRule?->couple_price ?? 120000,
            default => 50000
        };

        $totalPrice = $basePrice;

        // Áp dụng quy tắc theo thời gian nếu được yêu cầu
        if ($includeTimeBasedRules) {
            $adjustment = TimeBasedPricingRule::calculateTotalAdjustment($datetime, $seatType, $movieId);
            $totalPrice += $adjustment;
        }

        // Đảm bảo giá không âm
        return max(0, $totalPrice);
    }

    /**
     * Tạo snapshot giá cho showtime (bao gồm giá cơ bản và áp dụng quy tắc theo thời gian)
     *
     * @param Carbon $datetime Thời gian showtime
     * @param int|null $movieId ID của phim (để áp dụng quy tắc riêng phim)
     * @return array Pricing snapshot
     */
    public static function createPricingSnapshot(Carbon $datetime, ?int $movieId = null): array
    {
        $pricingRule = PricingRule::first();

        // Lấy snapshot cơ bản từ PricingRule
        $snapshot = $pricingRule ? $pricingRule->toArray() : [
            'standard_price' => 50000,
            'vip_price' => 70000,
            'couple_price' => 120000,
            'weekend_price' => 10000,
            'happy_hour_price' => 10000,
            'format_3d_price' => 30000,
            'sneak_show_price' => 20000,
            'weekend_surcharge' => 10000,
            'happy_hour_discount' => 10000,
            'format_3d_surcharge' => 30000,
            'sneak_show_surcharge' => 20000
        ];

        // Áp dụng các điều chỉnh theo thời gian
        $seatTypes = ['standard', 'vip', 'couple'];
        $adjustments = [];

        foreach ($seatTypes as $seatType) {
            $adjustment = TimeBasedPricingRule::calculateTotalAdjustment($datetime, $seatType, $movieId);
            $adjustments[$seatType . '_time_based_adjustment'] = $adjustment;

            // Cập nhật giá cơ bản
            $priceKey = $seatType . '_price';
            if (isset($snapshot[$priceKey])) {
                $snapshot[$priceKey] = max(0, $snapshot[$priceKey] + $adjustment);
            }
        }

        // Thêm thông tin về các quy tắc áp dụng
        $snapshot['time_based_rules_applied'] = [];
        foreach ($seatTypes as $seatType) {
            $rules = TimeBasedPricingRule::getApplicableRules($datetime, $seatType, $movieId);
            $snapshot['time_based_rules_applied'][$seatType] = $rules->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'adjustment' => $rule->price_adjustment
                ];
            })->toArray();
        }

        return $snapshot;
    }

    /**
     * Lấy danh sách tất cả quy tắc áp dụng cho một showtime
     *
     * @param Carbon $datetime Thời gian showtime
     * @param int|null $movieId ID của phim (để áp dụng quy tắc riêng phim)
     * @return array Danh sách quy tắc theo loại ghế
     */
    public static function getApplicableRulesForShowtime(Carbon $datetime, ?int $movieId = null): array
    {
        $seatTypes = ['standard', 'vip', 'couple'];
        $result = [];

        foreach ($seatTypes as $seatType) {
            $rules = TimeBasedPricingRule::getApplicableRules($datetime, $seatType, $movieId);
            $result[$seatType] = $rules->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'adjustment' => $rule->price_adjustment,
                    'start_time' => $rule->start_time,
                    'end_time' => $rule->end_time
                ];
            })->toArray();
        }

        return $result;
    }
}
