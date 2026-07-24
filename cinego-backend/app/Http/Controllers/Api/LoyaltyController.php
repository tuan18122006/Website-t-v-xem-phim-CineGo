<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\User;
use App\Models\Voucher;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LoyaltyController extends Controller
{
    protected $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    public function getProfileAndHistories(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'cine_points' => $user->cine_points,
                'membership_tier' => $user->membership_tier,
                'total_spent' => $user->total_spent,
                'multiplier' => $user->point_multiplier,
                'histories' => $user->pointHistories()->latest()->paginate(15)
            ]
        ]);
    }

    public function getRedeemableVouchers()
    {
        $vouchers = Voucher::whereNotNull('points_required')
            ->where('points_required', '>', 0)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $vouchers
        ]);
    }

    public function getRedeemableCombos()
    {
        $combos = Combo::where('status', 'active')
            ->where('is_redeemable', true) 
            ->where('points_required', '>', 0)
            ->where('stock', '>', 0)
            ->get();

        return response()->json([
            'status'   => 'success',
            'data'     => $combos
        ]);
    }

   public function redeemVoucher(Request $request, Voucher $voucher)
{
    $user = $request->user();

    if (!$voucher->points_required || $voucher->points_required <= 0) {
        return response()->json(['message' => 'Voucher này không áp dụng đổi điểm.'], 400);
    }

    $maxExchanges = $voucher->max_exchanges ?? $voucher->user_limit ?? 0;
    if ($maxExchanges > 0) {
        $alreadyRedeemedCount = $user->vouchers()->where('voucher_id', $voucher->id)->count();
        if ($alreadyRedeemedCount >= $maxExchanges) {
            return response()->json(['message' => 'Bạn đã đạt giới hạn số lần đổi cho voucher này!'], 400);
        }
    }

    try {
        $this->loyaltyService->redeemWithPoints(...);

        $user->vouchers()->attach($voucher->id, [
            'is_used'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Đổi voucher thành công!',
            'cine_points' => $user->cine_points
        ]);
    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage()], 400);
    }
}

    public function redeemCombo(Request $request)
    {
        $request->validate([
            'combo_id' => 'required|exists:combos,id'
        ]);

        $user = $request->user();

        try {
            return DB::transaction(function () use ($user, $request) {
                $combo = Combo::where('id', $request->combo_id)->lockForUpdate()->firstOrFail();

                if ($combo->stock <= 0) {
                    return response()->json(['message' => 'Sản phẩm này đã hết hàng trong kho!'], 400);
                }

                if ($user->cine_points < $combo->points_required) {
                    return response()->json(['message' => 'Bạn không đủ điểm để đổi quà này.'], 400);
                }

                if (!empty($combo->valid_minutes)) {
                    $expiredAt = now()->addMinutes($combo->valid_minutes);
                } elseif (!empty($combo->valid_days)) {
                    $expiredAt = now()->addDays($combo->valid_days);
                } else {
                    $expiredAt = now()->addDays(30);
                }

                $combo->decrement('stock', 1);

                $user->decrement('cine_points', $combo->points_required);

                $user->pointHistories()->create([
                    'points'      => -$combo->points_required,
                    'type'        => 'redeem',
                    'description' => "Đổi điểm nhận Combo: {$combo->name}",
                ]);

                $user->combos()->attach($combo->id, [
                    'code'       => 'COMBO-' . strtoupper(Str::random(8)),
                    'end_date'   => $expiredAt,
                    'is_used'    => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'status'      => 'success',
                    'message'     => 'Đổi Combo thành công!',
                    'cine_points' => $user->fresh()->cine_points
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

   public function getMyVouchers(Request $request)
{
    try {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Người dùng chưa đăng nhập'
            ], 401);
        }

        // 1. Lấy danh sách Vouchers của User
        $vouchers = $user->vouchers()
            ->orderBy('user_vouchers.created_at', 'desc')
            ->get()
            ->map(function ($voucher) {
                $pivot = $voucher->pivot;
               $endDate = $voucher->expires_at ?? null;
                $isUsed = (bool) ($pivot->is_used ?? false);

                $parsedEndDate = null;
                $isExpired = false;

                if ($endDate) {
                    try {
                        $carbonEnd = Carbon::parse($endDate);
                        $parsedEndDate = $carbonEnd->toIso8601String();
                        $isExpired = now()->gt($carbonEnd);
                    } catch (\Exception $e) {
                        $parsedEndDate = null;
                        $isExpired = false;
                    }
                }

                return [
                    'id'               => $voucher->id,
                    'user_voucher_id'  => $pivot->id ?? null,
                    'type'             => 'voucher',
                    'code'             => $pivot->code ?? $voucher->code ?? 'VOUCHER',
                    'title'            => $voucher->title ?? $voucher->name ?? 'Voucher giảm giá',
                    'description'      => $voucher->description ?? 'Voucher giảm giá vé xem phim',
                    'discount_amount'  => $voucher->discount_amount ?? 0,
                    'discount_percent' => $voucher->discount_percent ?? 0,
                    'min_order_value'  => $voucher->min_order_value ?? 0,
                    'end_date'         => $parsedEndDate,
                    'is_used'          => $isUsed,
                    'used_at'          => $pivot->used_at ?? null,
                    'is_expired'       => $isExpired,
                ];
            });

        // 2. Lấy danh sách Combos của User
        $combos = $user->combos()
            ->orderBy('user_combos.created_at', 'desc')
            ->get()
            ->map(function ($combo) {
                $pivot = $combo->pivot;
                $endDate = $pivot->end_date ?? null;
                $isUsed = (bool) ($pivot->is_used ?? false);

                $parsedEndDate = null;
                $isExpired = false;

                if ($endDate) {
                    try {
                        $carbonEnd = Carbon::parse($endDate);
                        $parsedEndDate = $carbonEnd->toIso8601String();
                        $isExpired = now()->gt($carbonEnd);
                    } catch (\Exception $e) {
                        $parsedEndDate = null;
                        $isExpired = false;
                    }
                }

                return [
                    'id'               => $combo->id,
                    'combo_id'         => $combo->id, 
                    'user_combo_id'    => $pivot->id ?? $combo->id, 
                    'type'             => 'combo',
                    'code'             => $pivot->code ?? ('COMBO-' . $combo->id),
                    'title'            => $combo->name ?? 'Ưu đãi Bắp Nước',
                    'description'      => $combo->description ?? 'Đổi trực tiếp tại quầy bắp nước',
                    'discount_amount'  => 0,
                    'discount_percent' => 0,
                    'min_order_value'  => 0,
                    'end_date'         => $parsedEndDate,
                    'is_used'          => $isUsed,
                    'used_at'          => $pivot->used_at ?? null,
                    'is_expired'       => $isExpired,
                ];
            });

        $merged = $vouchers->concat($combos);

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'data'    => $merged
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Lỗi máy chủ khi lấy ví voucher: ' . $e->getMessage()
        ], 500);
    }
}

    public function adminGetUserHistories($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'cine_points', 'membership_tier', 'total_spent']),
                'histories' => $user->pointHistories()->latest()->paginate(20)
            ]
        ]);
    }

    public function adminAdjustPoints(Request $request, $id)
    {
        $request->validate([
            'type'   => 'required|in:add,subtract',
            'points' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($id);

        $points = $request->type === 'add' ? $request->points : -$request->points;

        if ($points < 0 && $user->cine_points < abs($points)) {
            return response()->json(['message' => 'Số điểm trừ vượt quá số điểm hiện có của User.'], 400);
        }

        $user->cine_points += $points;
        $user->save();

        $user->pointHistories()->create([
            'points' => $points,
            'type' => 'admin_adjustment',
            'description' => "Admin điều chỉnh: " . $request->reason,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Điều chỉnh điểm thành công!',
            'cine_points' => $user->cine_points
        ]);
    }

    public function adminGetUsers(Request $request)
    {
        $query = User::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->select('id', 'name', 'email', 'membership_tier', 'total_spent', 'cine_points')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function getAvailableCombos(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người dùng chưa đăng nhập'
                ], 401);
            }

            $combos = $user->combos()
                ->withPivot('id', 'code', 'end_date', 'is_used') 
                ->wherePivot('is_used', false)
                ->where(function ($query) {
                    $query->whereNull('user_combos.end_date')
                          ->orWhere('user_combos.end_date', '>', now());
                })
                ->get()
                ->map(function ($combo) {
                    $rawEndDate = $combo->pivot->end_date;

                    return [
                        'user_combo_id' => $combo->pivot->id,
                        'combo_id'      => $combo->id,
                        'code'          => $combo->pivot->code ?? ('COMBO-' . $combo->id),
                        'name'          => $combo->name ?? 'Ưu đãi Bắp Nước',
                        'description'   => $combo->description ?? 'Đổi trực tiếp tại quầy bắp nước',
                        'image_url'     => $combo->image_url ?? '',
                        'end_date'      => $rawEndDate ? Carbon::parse($rawEndDate)->toIso8601String() : null
                    ];
                });

            return response()->json([
                'success' => true,
                'data'    => $combos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCombosForSale()
    {
        $combos = Combo::where('status', 'active')
            ->where('is_sellable', true) 
            ->where('stock', '>', 0)
            ->get();

        return response()->json(['data' => $combos]);
    }
}