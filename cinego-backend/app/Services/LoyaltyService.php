<?php

namespace App\Services;

use App\Models\User;
use App\Models\Voucher;
use App\Notifications\TierUpgradeNotification;
use Illuminate\Support\Str;

class LoyaltyService
{
    /**
     * Bảng mốc thăng hạng dựa trên total_spent (VNĐ).
     * Thứ tự: từ hạng cao nhất xuống thấp nhất.
     */
    const TIERS = [
        'Diamond' => 10000000,
        'Gold'    => 3000000,
        'Silver'  => 1000000,
        'Bronze'  => 0,
    ];

    /**
     * Tỷ lệ quy đổi: 1.000 VNĐ = 1 điểm
     */
    const POINTS_PER_VND = 1000;

    /**
     * Cộng điểm và tổng chi tiêu sau khi thanh toán thành công.
     * Tự động kiểm tra và thăng hạng nếu đạt mốc.
     */
    public static function rewardForBooking(User $user, float $totalAmount): array
    {
        $pointsEarned = (int) floor($totalAmount / self::POINTS_PER_VND);

        $user->increment('loyalty_points', $pointsEarned);
        $user->increment('total_spent', $totalAmount);

        // Refresh lại model sau khi increment
        $user->refresh();

        $result = [
            'points_earned' => $pointsEarned,
            'new_total_points' => $user->loyalty_points,
            'new_total_spent' => $user->total_spent,
            'tier_upgraded' => false,
            'old_tier' => $user->membership_tier,
            'new_tier' => $user->membership_tier,
        ];

        // Kiểm tra thăng hạng
        $newTier = self::calculateTier($user->total_spent);
        if ($newTier !== $user->membership_tier) {
            $result['tier_upgraded'] = true;
            $result['new_tier'] = $newTier;
            $user->membership_tier = $newTier;
            $user->save();

            self::handleTierUpgrade($user, $newTier);
        }

        return $result;
    }

    /**
     * Tính hạng thành viên dựa trên tổng chi tiêu.
     */
    private static function handleTierUpgrade(User $user, string $newTier)
    {
        $message = "Chúc mừng! Bạn đã thăng hạng $newTier.";
        $discountValue = 0;
        
        if ($newTier === 'Silver') {
            $discountValue = 10;
            $message .= " Bạn được tặng 1 Voucher giảm giá 10%.";
        } elseif ($newTier === 'Gold') {
            $discountValue = 20;
            $message .= " Bạn được tặng 1 Voucher giảm giá 20%.";
        } elseif ($newTier === 'Diamond') {
            $discountValue = 30;
            $message .= " Bạn được tặng 1 Voucher giảm giá 30%.";
        }

        if ($discountValue > 0) {
            $voucher = Voucher::create([
                'code' => strtoupper(Str::random(10)),
                'discount_type' => 'percent',
                'discount_value' => $discountValue,
                'min_spend' => 0,
                'max_discount' => null,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(1),
                'usage_limit' => 1,
                'target_limit' => 'user',
                'is_active' => true,
            ]);
            
            $user->vouchers()->attach($voucher->id);
        }

        $user->notify(new TierUpgradeNotification($newTier, $message));
    }

    public static function calculateTier(float $totalSpent): string
    {
        foreach (self::TIERS as $tier => $threshold) {
            if ($totalSpent >= $threshold) {
                return $tier;
            }
        }
        return 'Bronze';
    }

    /**
     * Admin cộng / trừ điểm thủ công.
     * $amount dương = cộng, âm = trừ.
     */
    public static function adjustPoints(User $user, int $amount, string $reason = ''): array
    {
        $newPoints = max(0, $user->loyalty_points + $amount);
        $user->loyalty_points = $newPoints;
        $user->save();

        return [
            'adjusted_amount' => $amount,
            'new_total_points' => $newPoints,
            'reason' => $reason,
        ];
    }

    /**
     * Admin set rank thủ công (không cần đạt mốc chi tiêu).
     */
    public static function setTier(User $user, string $tier): void
    {
        if ($user->membership_tier !== $tier) {
            $user->membership_tier = $tier;
            $user->save();
            self::handleTierUpgrade($user, $tier);
        }
    }

    /**
     * Lấy thông tin tiến trình thăng hạng cho hiển thị trên Profile.
     */
    public static function getProgressInfo(User $user): array
    {
        $currentTier = $user->membership_tier;
        $totalSpent = (float) $user->total_spent;

        // Tìm hạng tiếp theo
        $tiers = array_reverse(self::TIERS, true); // Bronze -> Diamond
        $nextTier = null;
        $nextThreshold = null;
        $currentThreshold = 0;
        $foundCurrent = false;

        foreach ($tiers as $tier => $threshold) {
            if ($foundCurrent) {
                $nextTier = $tier;
                $nextThreshold = $threshold;
                break;
            }
            if ($tier === $currentTier) {
                $currentThreshold = $threshold;
                $foundCurrent = true;
            }
        }

        $progress = 0;
        $remaining = 0;
        if ($nextThreshold !== null) {
            $range = $nextThreshold - $currentThreshold;
            $spent = $totalSpent - $currentThreshold;
            $progress = $range > 0 ? min(100, round(($spent / $range) * 100, 1)) : 100;
            $remaining = max(0, $nextThreshold - $totalSpent);
        } else {
            $progress = 100; // Đã đạt hạng cao nhất (Diamond)
        }

        return [
            'current_tier' => $currentTier,
            'next_tier' => $nextTier,
            'total_spent' => $totalSpent,
            'current_threshold' => $currentThreshold,
            'next_threshold' => $nextThreshold,
            'progress_percent' => $progress,
            'remaining_amount' => $remaining,
            'loyalty_points' => $user->loyalty_points,
        ];
    }
}


