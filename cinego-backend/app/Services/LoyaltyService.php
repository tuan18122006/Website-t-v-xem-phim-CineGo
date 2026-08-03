<?php

namespace App\Services;

use App\Models\User;
use App\Models\PointHistory;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    public const TIERS = [
        'Bronze' => 0,
        'Silver' => 2000000,
        'Gold' => 5000000,
        'Diamond' => 10000000,
    ];

    public function processBookingPoints(User $user, float $spentAmount, $booking)
    {
        return DB::transaction(function () use ($user, $spentAmount, $booking) {
            // 1. Cộng tổng tiền chi tiêu
            $user->total_spent = ($user->total_spent ?? 0) + $spentAmount;

            // 2. Cập nhật hạng thẻ dựa trên tổng chi tiêu tích lũy
            $this->updateMembershipTier($user);

            // 3. Tính điểm thưởng dựa trên hệ số của User (mặc định 1 nếu null)
            $multiplier = $user->point_multiplier ?? 1;
            $earnedPoints = floor(($spentAmount / 10000) * $multiplier);

            // 4. Cộng CinePoints
            if ($earnedPoints > 0) {
                $user->cine_points = ($user->cine_points ?? 0) + $earnedPoints;
                $user->save();

                // Ghi log lịch sử điểm
                $user->pointHistories()->create([
                    'points'         => $earnedPoints,
                    'type'           => 'booking_earning',
                    'description'    => "Tích điểm từ đơn đặt vé #{$booking->booking_code}",
                    'reference_type' => get_class($booking),
                    'reference_id'   => $booking->id,
                ]);
            }

            return $earnedPoints;
        });
    }

    public function updateMembershipTier(User $user)
    {
        $totalSpent = $user->total_spent ?? 0;
        $tier = 'Bronze';
        
        foreach (self::TIERS as $t => $threshold) {
            if ($totalSpent >= $threshold) {
                $tier = $t;
            }
        }

        // Chỉ update khi có sự thay đổi hạng
        if ($user->membership_tier !== $tier) {
            $user->membership_tier = $tier;
            $user->save();
        }
    }

    public function addPoints(User $user, int $points, string $description)
    {
        if ($points <= 0) {
            return;
        }

        $user->cine_points = ($user->cine_points ?? 0) + $points;
        $user->save();

        PointHistory::create([
            'user_id'     => $user->id,
            'points'      => $points,
            'type'        => 'earn',
            'description' => $description,
        ]);

        $this->updateMembershipTier($user);
    }

    public function redeemWithPoints(User $user, int $pointsRequired, string $description, $itemModel)
    {
        if (($user->cine_points ?? 0) < $pointsRequired) {
            throw new \Exception("Bạn không đủ CinePoints để đổi ưu đãi này.");
        }

        return DB::transaction(function () use ($user, $pointsRequired, $description, $itemModel) {
            $user->cine_points -= $pointsRequired;
            $user->save();

            return $user->pointHistories()->create([
                'points'         => -$pointsRequired,
                'type'           => 'redemption',
                'description'    => $description,
                'reference_type' => get_class($itemModel),
                'reference_id'   => $itemModel->id,
            ]);
        });
    }

    public static function getProgressInfo(User $user): array
    {
        $currentTier = $user->membership_tier ?: 'Bronze';
        $totalSpent = (float) ($user->total_spent ?? 0);

        // Đảo lại để tìm logic đúng hơn (từ bé đến lớn)
        $tiersNormal = self::TIERS;
        $nextTier = null;
        $nextThreshold = null;
        foreach ($tiersNormal as $tier => $threshold) {
            if ($threshold > $totalSpent) {
                $nextTier = $tier;
                $nextThreshold = $threshold;
                break;
            }
        }
        
        $currentThreshold = self::TIERS[$currentTier] ?? 0;

        if (!$nextTier) {
            // Đã đạt hạng cao nhất
            return [
                'current_tier' => $currentTier,
                'next_tier' => null,
                'progress_percent' => 100,
                'remaining_amount' => 0,
                'total_spent' => $totalSpent,
                'loyalty_points' => $user->cine_points ?? 0,
                'multiplier' => $user->point_multiplier ?? 1,
            ];
        }

        $tierRange = $nextThreshold - $currentThreshold;
        $spentInCurrentTier = $totalSpent - $currentThreshold;
        
        if ($tierRange <= 0) $tierRange = 1;

        $progressPercent = min(100, max(0, ($spentInCurrentTier / $tierRange) * 100));

        return [
            'current_tier' => $currentTier,
            'next_tier' => $nextTier,
            'progress_percent' => round($progressPercent, 2),
            'remaining_amount' => $nextThreshold - $totalSpent,
            'total_spent' => $totalSpent,
            'loyalty_points' => $user->cine_points ?? 0,
            'multiplier' => $user->point_multiplier ?? 1,
        ];
    }
}