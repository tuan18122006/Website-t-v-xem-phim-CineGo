<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    public function verify(Request $request)
    {
        $request->validate([
            'code'        => 'required|string',
            'subtotal'    => 'required|numeric|min:0',
            'user_id'     => 'required|integer',
            'is_new_user' => 'nullable|boolean',
            'movie_id'    => 'nullable|integer'
        ]);

        $code = strtoupper($request->code);
        $subtotal = $request->subtotal;
        $userId = $request->user_id;

        // 1. Kiểm tra Voucher gốc
        $voucher = Voucher::where('code', $code)->where('is_active', 1)->first();

        if (!$voucher) {
            return response()->json(['message' => 'Mã Voucher không tồn tại hoặc đã bị khóa.'], 422);
        }

        // 2. KIỂM TRA SỞ HỮU TRONG VÍ (Cho voucher đổi bằng điểm)
        if ($voucher->points_required && $voucher->points_required > 0) {
            $userVoucher = DB::table('user_vouchers')
                ->where('user_id', $userId)
                ->where('voucher_id', $voucher->id)
                ->where('is_used', false)
                ->first();

            if (!$userVoucher) {
                return response()->json(['message' => 'Bạn chưa đổi voucher này hoặc voucher trong ví đã được sử dụng!'], 422);
            }
        }

        // 3. Check thời gian bắt đầu
        if ($voucher->starts_at && Carbon::parse($voucher->starts_at)->isFuture()) {
            return response()->json([
                'message' => 'Chương trình ưu đãi chưa bắt đầu. Thời gian áp dụng từ: ' . Carbon::parse($voucher->starts_at)->format('H:i d/m/Y')
            ], 422);
        }

        // 4. Check giới hạn lượt dùng của từng User
        if ($voucher->user_limit) {
            $usedCountForUser = DB::table('bookings')
                ->where('user_id', $userId)
                ->where('voucher_id', $voucher->id)
                ->where('booking_status', 'confirmed')
                ->where('payment_status', 'paid')
                ->count();

            if ($usedCountForUser >= $voucher->user_limit) {
                return response()->json(['message' => "Mỗi tài khoản chỉ được sử dụng mã này tối đa {$voucher->user_limit} lần."], 422);
            }
        }

        // 5. Check expiration
        if ($voucher->expires_at && Carbon::parse($voucher->expires_at)->isPast()) {
            return response()->json(['message' => 'Mã giảm giá đã hết hạn sử dụng.'], 422);
        }

        // 6. Check usage limit tổng hệ thống
        if ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) {
            return response()->json(['message' => 'Mã giảm giá đã hết lượt sử dụng trên hệ thống.'], 422);
        }

        // 7. Check min spend
        if ($subtotal < $voucher->min_spend) {
            return response()->json([
                'message' => 'Chưa đạt giá trị đơn hàng tối thiểu. Cần tối thiểu ' . number_format($voucher->min_spend) . ' đ.'
            ], 422);
        }

        // 8. KIỂM TRA ĐỐI TƯỢNG
        if ($voucher->target_limit === 'new_user' && !$request->is_new_user) {
            return response()->json(['message' => 'Mã giảm giá này chỉ dành riêng cho tài khoản đăng ký mới.'], 422);
        } elseif ($voucher->target_limit === 'birthday') {
            if (!$request->user_birthday) {
                return response()->json(['message' => 'Vui lòng cập nhật ngày sinh nhật để sử dụng mã này.'], 422);
            }
            if (Carbon::parse($request->user_birthday)->month !== Carbon::now()->month) {
                return response()->json(['message' => 'Mã giảm giá chỉ áp dụng trong tháng sinh nhật của bạn.'], 422);
            }
        }

        // 9. KIỂM TRA ĐIỀU KIỆN ÁP DỤNG
        if (!empty($voucher->usage_condition)) {
            $conditions = is_array($voucher->usage_condition)
                ? $voucher->usage_condition
                : json_decode($voucher->usage_condition, true);

            if (isset($conditions['day_of_week']) && Carbon::now()->dayOfWeekIso != $conditions['day_of_week']) {
                $days = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ Bảy', 7 => 'Chủ Nhật'];
                return response()->json(['message' => "Mã giảm giá này chỉ áp dụng vào {$days[$conditions['day_of_week']]}."], 422);
            }

            if (isset($conditions['movie_id']) && $conditions['movie_id'] !== '' && $request->movie_id != $conditions['movie_id']) {
                return response()->json(['message' => 'Mã giảm giá này không áp dụng cho bộ phim đang chọn.'], 422);
            }
        }

        // 10. Tính tiền giảm
        $discountAmount = ($voucher->discount_type === 'fixed')
            ? $voucher->discount_value
            : ($subtotal * $voucher->discount_value) / 100;

        if ($voucher->discount_type === 'percentage' && $voucher->max_discount !== null && $discountAmount > $voucher->max_discount) {
            $discountAmount = $voucher->max_discount;
        }

        return response()->json([
            'id'              => $voucher->id,
            'code'            => $voucher->code,
            'discount_type'   => $voucher->discount_type,
            'discount_value'  => (float) $voucher->discount_value,
            'min_spend'       => (float) $voucher->min_spend,
            'max_discount'    => $voucher->max_discount ? (float) $voucher->max_discount : null,
            'discount_amount' => (float) $discountAmount
        ], 200);
    }

    public function index()
    {
        return Voucher::orderBy('id', 'desc')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'            => 'required|unique:vouchers,code|max:20|alpha_dash',
            'discount_type'   => 'required|in:fixed,percentage',
            'discount_value'  => 'required|numeric|min:0',
            'min_spend'       => 'required|numeric|min:0',
            'starts_at'       => 'nullable|date',
            'max_discount'    => 'nullable|numeric|min:0',
            'expires_at'      => 'required|date|after:now',
            'usage_limit'     => 'nullable|integer|min:1',
            'target_limit'    => 'required|in:all,new_user,birthday',
            'usage_condition' => 'nullable|array',
            'points_required' => 'nullable|integer|min:0',
            'max_exchanges'   => 'nullable|integer|min:1',
            'is_active'       => 'nullable|boolean'
        ]);

        $data['starts_at'] = $request->filled('starts_at') ? $request->starts_at : now();

        return Voucher::create($data);
    }

    public function update(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);

        $data = $request->validate([
            'code'            => 'required|max:20|unique:vouchers,code,' . $id,
            'discount_type'   => 'required|in:fixed,percentage',
            'discount_value'  => 'required|numeric|min:0',
            'min_spend'       => 'required|numeric|min:0',
            'max_discount'    => 'nullable|numeric|min:0',
            'starts_at'       => 'nullable|date', 
            'expires_at'      => 'required|date',
            'usage_limit'     => 'nullable|integer|min:1',
            'user_limit'      => 'nullable|integer|min:1',
            'target_limit'    => 'required|in:all,new_user,birthday',
            'usage_condition' => 'nullable|array',
            'points_required' => 'nullable|integer|min:0',
            'max_exchanges'   => 'nullable|integer|min:1',
            'is_active'       => 'nullable|boolean'

        ]);

        $data['starts_at'] = $request->filled('starts_at') 
            ? $request->starts_at 
            : ($voucher->starts_at ?? $voucher->created_at ?? now());

        $voucher->update($data);
        return response()->json($voucher);
    }

    public function destroy($id)
    {
        $voucher = Voucher::findOrFail($id);
        if ($voucher->bookings()->exists()) {
            return response()->json(['message' => 'Mã giảm giá đã được sử dụng trong đơn hàng, không thể xóa.'], 409);
        }
        $voucher->delete();
        return response()->json(['message' => 'Xóa mã giảm giá thành công.']);
    }

    public function claimVoucher(Request $request)
    {
        $request->validate([
            'voucher_id' => 'required|integer|exists:vouchers,id',
        ]);

        $userId = auth()->id();
        $voucherId = $request->voucher_id;

        return DB::transaction(function () use ($userId, $voucherId) {
            $voucher = Voucher::lockForUpdate()->find($voucherId);

            if (!$voucher) {
                return response()->json(['success' => false, 'message' => 'Voucher không tồn tại!'], 404);
            }

            if (!$voucher->is_active) {
                return response()->json(['success' => false, 'message' => 'Voucher này hiện không hoạt động!'], 400);
            }

            if ($voucher->expires_at && now()->gt($voucher->expires_at)) {
                return response()->json(['success' => false, 'message' => 'Voucher này đã hết hạn quy đổi!'], 400);
            }

            // Kiểm tra giới hạn lượt đổi tổng hệ thống
            if (!is_null($voucher->max_exchanges)) {
                $totalExchanged = DB::table('user_vouchers')->where('voucher_id', $voucherId)->count();
                if ($totalExchanged >= $voucher->max_exchanges) {
                    return response()->json(['success' => false, 'message' => 'Voucher này đã hết lượt quy đổi trên hệ thống!'], 400);
                }
            }

            // Kiểm tra giới hạn lượt đổi của từng User
            $userClaimCount = DB::table('user_vouchers')
                ->where('user_id', $userId)
                ->where('voucher_id', $voucherId)
                ->count();

            $limitPerUser = $voucher->user_limit ?? 1;
            if ($userClaimCount >= $limitPerUser) {
                return response()->json([
                    'success' => false,
                    'message' => "Bạn đã sở hữu tối đa ({$limitPerUser}) Voucher này trong ví rồi!"
                ], 400);
            }

            // Trừ điểm tích lũy
            if ($voucher->points_required && $voucher->points_required > 0) {
                $user = auth()->user();
                if ($user->points < $voucher->points_required) {
                    return response()->json([
                        'success' => false,
                        'message' => "Bạn không đủ điểm tích lũy! Cần {$voucher->points_required} điểm."
                    ], 400);
                }

                $user->decrement('points', $voucher->points_required);
            }

            DB::table('user_vouchers')->insert([
                'user_id'    => $userId,
                'voucher_id' => $voucherId,
                'is_used'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đổi Voucher thành công! Voucher đã được thêm vào Ví của bạn.'
            ]);
        });
    }

    public function getExchangeableVouchers()
    {
        $vouchers = Voucher::where('is_active', 1)
            ->where('points_required', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->get();

        return response()->json($vouchers);
    }

    public function getMyVouchers(Request $request)
    {
        $userId = auth()->id();
        $status = $request->query('status', 'available');

        $query = DB::table('user_vouchers')
            ->join('vouchers', 'user_vouchers.voucher_id', '=', 'vouchers.id')
            ->where('user_vouchers.user_id', $userId)
            ->select(
                'user_vouchers.id as user_voucher_id',
                'user_vouchers.is_used',
                'user_vouchers.created_at as claimed_at',
                'vouchers.*'
            );

        if ($status === 'available') {
            $query->where('user_vouchers.is_used', false)
                ->where(function ($q) {
                    $q->whereNull('vouchers.expires_at')
                        ->orWhere('vouchers.expires_at', '>', now());
                });
        } else {
            $query->where(function ($q) {
                $q->where('user_vouchers.is_used', true)
                    ->orWhere(function ($sub) {
                        $sub->where('user_vouchers.is_used', false)
                            ->where('vouchers.expires_at', '<=', now());
                    });
            });
        }

        $vouchers = $query->get()->map(function ($item) {
            if ($item->usage_condition) {
                $item->usage_condition = json_decode($item->usage_condition, true);
            }
            return $item;
        });

        return response()->json($vouchers);
    }
}
