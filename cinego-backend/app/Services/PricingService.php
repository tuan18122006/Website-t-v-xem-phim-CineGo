<?php

namespace App\Services;

use App\Models\PricingRule;
use Carbon\Carbon;

class PricingService
{
    /**
     * Lấy các quy tắc giá đặc biệt đang áp dụng từ cột JSON trong bảng pricing_rules
     */
    public static function getApplicableRules(Carbon $datetime, string $seatType, ?int $movieId = null): array
    {
        $pricingRule = PricingRule::first();
        if (!$pricingRule || empty($pricingRule->pricing_rules)) {
            return [];
        }

        $applicableRules = [];
        $rules = is_string($pricingRule->pricing_rules) ? json_decode($pricingRule->pricing_rules, true) : $pricingRule->pricing_rules;

        if (!is_array($rules)) {
            return [];
        }

        $dateString = $datetime->format('Y-m-d');
        $timeString = $datetime->format('H:i');
        
        // Map PHP dayOfWeek (0=Sunday, 1=Monday...) to frontend representation
        $dayMap = [0 => 'CN', 1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7'];
        $currentDay = $dayMap[$datetime->dayOfWeek];

        foreach ($rules as $rule) {
            if (($rule['status'] ?? 'active') !== 'active') {
                continue;
            }

            // Kiểm tra Phạm vi (Scope)
            if (isset($rule['scope']) && $rule['scope'] === 'movie') {
                if (!$movieId || (int)$rule['movie_id'] !== (int)$movieId) {
                    continue;
                }
            }

            // Kiểm tra Loại ghế (Seat Type)
            if (isset($rule['seat_type']) && $rule['seat_type'] !== 'all' && $rule['seat_type'] !== $seatType) {
                continue;
            }

            // Kiểm tra Ngày (Date range)
            if (!empty($rule['start_date']) && $dateString < $rule['start_date']) continue;
            if (!empty($rule['end_date']) && $dateString > $rule['end_date']) continue;

            // Kiểm tra Ngày ngoại trừ (Excluded dates)
            if (!empty($rule['excluded_dates']) && is_array($rule['excluded_dates'])) {
                if (in_array($dateString, $rule['excluded_dates'])) {
                    continue;
                }
            }

            // Kiểm tra Thứ (Days of week)
            if (empty($rule['all_weekdays'])) {
                if (!empty($rule['days']) && is_array($rule['days'])) {
                    if (!in_array($currentDay, $rule['days'])) {
                        continue;
                    }
                }
            }

            // Kiểm tra Khung giờ (Time filter)
            if (!empty($rule['use_time_filter'])) {
                if (!empty($rule['time_from']) && $timeString < $rule['time_from']) continue;
                if (!empty($rule['time_to']) && $timeString > $rule['time_to']) continue;
            }

            $applicableRules[] = $rule;
        }

        return $applicableRules;
    }

    /**
     * Tạo snapshot giá cho showtime (bao gồm giá cơ bản và áp dụng các quy tắc)
     */
    public static function createPricingSnapshot(Carbon $datetime, ?int $movieId = null, string $format = '2D', bool $isSneakShow = false): array
    {
        $pricingRule = PricingRule::first();

        // Lấy giá cơ bản từ PricingRule
        $snapshot = [
            'base_standard_price' => $pricingRule?->standard_price ?? 50000,
            'base_vip_price' => $pricingRule?->vip_price ?? 70000,
            'base_couple_price' => $pricingRule?->couple_price ?? 120000,
            'standard_price' => $pricingRule?->standard_price ?? 50000,
            'vip_price' => $pricingRule?->vip_price ?? 70000,
            'couple_price' => $pricingRule?->couple_price ?? 120000,
            'format_3d_surcharge' => $pricingRule?->format_3d_surcharge ?? 0,
            'sneak_show_surcharge' => $pricingRule?->sneak_show_surcharge ?? 0,
        ];

        // Áp dụng Phụ thu Cố định (3D, Sneak Show)
        $fixedSurcharge = 0;
        if (str_contains(strtoupper($format), '3D')) {
            $fixedSurcharge += $pricingRule?->format_3d_surcharge ?? 0;
        }
        if ($isSneakShow) {
            $fixedSurcharge += $pricingRule?->sneak_show_surcharge ?? 0;
        }

        if ($fixedSurcharge > 0) {
            $snapshot['standard_price'] += $fixedSurcharge;
            $snapshot['vip_price'] += $fixedSurcharge;
            $snapshot['couple_price'] += ($fixedSurcharge * 2); // Ghế đôi tính 2 người
        }

        // Áp dụng các điều chỉnh theo thời gian
        $seatTypes = ['standard', 'vip', 'couple'];
        $snapshot['time_based_rules_applied'] = [];

        foreach ($seatTypes as $seatType) {
            $applicableRules = self::getApplicableRules($datetime, $seatType, $movieId);
            $priceKey = $seatType . '_price';
            
            $totalAdjustment = 0;
            $appliedRuleDetails = [];
            
            $basePrice = $snapshot[$priceKey];
            $currentPrice = $basePrice;

            foreach ($applicableRules as $rule) {
                $adjType = $rule['adjustment_type'] ?? 'surcharge';
                $value = (float)($rule['value'] ?? 0);
                $adjustmentAmount = 0;

                if ($adjType === 'surcharge') {
                    $actualValue = ($seatType === 'couple') ? ($value * 2) : $value;
                    $adjustmentAmount = $actualValue;
                    $currentPrice += $actualValue;
                } elseif ($adjType === 'percentage') {
                    $adjustmentAmount = ($basePrice * $value) / 100;
                    $currentPrice += $adjustmentAmount;
                } elseif ($adjType === 'free') {
                    $adjustmentAmount = -$currentPrice; // Trừ hết
                    $currentPrice = 0;
                }

                $totalAdjustment += $adjustmentAmount;

                $appliedRuleDetails[] = [
                    'name' => $rule['name'] ?? 'Không tên',
                    'adjustment_type' => $adjType,
                    'value' => $value,
                    'calculated_adjustment' => $adjustmentAmount
                ];
            }

            // Cập nhật giá cuối cùng
            $snapshot[$priceKey] = max(0, $currentPrice);
            $snapshot[$seatType . '_time_based_adjustment'] = $totalAdjustment;
            $snapshot['time_based_rules_applied'][$seatType] = $appliedRuleDetails;
        }

        return $snapshot;
    }
}

