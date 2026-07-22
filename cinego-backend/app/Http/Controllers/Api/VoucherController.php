<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher;
use Carbon\Carbon;
use App\Models\Combo;
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

    // 1. Kiểm tra Voucher gốc có tồn tại & đang active không
    $voucher = Voucher::where('code', $code)->where('is_active', 1)->first();

    if (!$voucher) {
        return response()->json([
            'message' => 'Mã Voucher không tồn tại.'
        ], 422);
    }

    // 🔥 2. KIỂM TRA SỞ HỮU TRONG VÍ (Áp dụng cho Voucher đổi bằng điểm tích lũy)
    if ($voucher->points_required && $voucher->points_required > 0) {
        $userVoucher = DB::table('user_vouchers')
            ->where('user_id', $userId)
            ->where('voucher_id', $voucher->id)
            ->where('is_used', false) // Phải chưa được sử dụng
            ->first();

        if (!$userVoucher) {
            return response()->json([
                'message' => 'Bạn chưa đổi voucher này hoặc voucher đã được sử dụng!'
            ], 422);
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
            return response()->json([
                'message' => "Mỗi tài khoản chỉ được sử dụng mã này tối đa {$voucher->user_limit} lần."
            ], 422);
        }
    }

    // 5. Check expiration
    if ($voucher->expires_at && Carbon::parse($voucher->expires_at)->isPast()) {
        return response()->json([
            'message' => 'Mã giảm giá đã hết hạn sử dụng.'
        ], 422);
    }

    // 6. Check usage limit tổng hệ thống
    if ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) {
        return response()->json([
            'message' => 'Mã giảm giá đã hết lượt sử dụng.'
        ], 422);
    }

    // 7. Check min spend requirement
    if ($subtotal < $voucher->min_spend) {
        return response()->json([
            'message' => 'Chưa đạt giá trị đơn hàng tối thiểu để áp dụng mã giảm giá này. Cần tối thiểu ' . number_format($voucher->min_spend) . ' đ.'
        ], 422);
    }

    // 8. KIỂM TRA GIỚI HẠN ĐỐI TƯỢNG
    if ($voucher->target_limit === 'new_user') {
        if (!$request->is_new_user) {
            return response()->json([
                'message' => 'Mã giảm giá này chỉ dành riêng cho tài khoản đăng ký mới (Tân binh).'
            ], 422);
        }
    } elseif ($voucher->target_limit === 'birthday') {
        if (!$request->user_birthday) {
            return response()->json([
                'message' => 'Mã sinh nhật yêu cầu tài khoản của bạn phải cập nhật ngày sinh.'
            ], 422);
        }
        $birthMonth = Carbon::parse($request->user_birthday)->month;
        $currentMonth = Carbon::now()->month;
        if ($birthMonth !== $currentMonth) {
            return response()->json([
                'message' => 'Mã giảm giá sinh nhật chỉ áp dụng trong tháng sinh của bạn.'
            ], 422);
        }
    }

    // 9. KIỂM TRA GIỚI HẠN THEO KHUNG GIỜ / PHIM
    if (!empty($voucher->usage_condition)) {
        $conditions = is_array($voucher->usage_condition)
            ? $voucher->usage_condition
            : json_decode($voucher->usage_condition, true);

        // Kiểm tra Thứ trong tuần
        if (isset($conditions['day_of_week'])) {
            $todayOfWeek = Carbon::now()->dayOfWeekIso; // Thứ 2 là 1, CN là 7
            if ($todayOfWeek != $conditions['day_of_week']) {
                $days = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ Bảy', 7 => 'Chủ Nhật'];
                $targetDay = $days[$conditions['day_of_week']] ?? '';
                return response()->json([
                    'message' => "Mã giảm giá này chỉ áp dụng vào {$targetDay}."
                ], 422);
            }
        }

        // Kiểm tra theo Phim cụ thể
        if (isset($conditions['movie_id']) && $conditions['movie_id'] !== '') {
            if (!$request->movie_id || $request->movie_id != $conditions['movie_id']) {
                return response()->json([
                    'message' => 'Mã giảm giá này không áp dụng cho bộ phim này.'
                ], 422);
            }
        }
    }

    // 10. Tính toán số tiền giảm
    $discountAmount = 0;

    if ($voucher->discount_type === 'fixed') {
        $discountAmount = $voucher->discount_value;
    } else {
        $discountAmount = ($subtotal * $voucher->discount_value) / 100;

        if ($voucher->max_discount !== null && $discountAmount > $voucher->max_discount) {
            $discountAmount = $voucher->max_discount;
        }
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
        return Voucher::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:vouchers,code|max:20|alpha_dash',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
            'min_spend' => 'required|numeric|min:0',
            'starts_at' => 'nullable|date',
            'max_discount' => 'nullable|numeric|min:0',
            'expires_at' => 'required|date|after:now',
            'usage_limit' => 'nullable|integer|min:1',
            'target_limit' => 'required|in:all,new_user,birthday',
            'usage_condition' => 'nullable|array',
            'points_required' => 'nullable|integer|min:0',
            'max_exchanges' => 'nullable|integer|min:1',
        ], [
            'code.required' => 'Mã giảm giá không được để trống.',
            'code.unique' => 'Mã giảm giá này đã tồn tại, vui lòng chọn mã khác.',
            'discount_value.required' => 'Vui lòng nhập giá trị.',
            'discount_value.min' => 'Giá trị giảm không được là số âm.',
            'min_spend.min' => 'Đơn tối thiểu không được là số âm.',
            'expires_at.after' => 'Ngày hết hạn phải là thời điểm trong tương lai.',
            'expires_at.required' => 'Vui lòng chọn ngày hết hạn mã.',
            'max_exchanges.integer' => 'Số lượt đổi tối đa phải là số nguyên.',
            'max_exchanges.min'     => 'Số lượt đổi tối đa phải lớn hơn hoặc bằng 1.',
        ]);

        return Voucher::create($request->all());
    }

    public function update(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);

        $request->validate([
            'code' => 'required|max:20|unique:vouchers,code,' . $id,
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
            'min_spend' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'expires_at' => 'required|date',
            'usage_limit' => 'nullable|integer|min:1',
            'target_limit' => 'required|in:all,new_user,birthday',
            'usage_condition' => 'nullable|array',
            'points_required' => 'nullable|integer|min:0',
            'max_exchanges' => 'nullable|integer|min:1',
        ], [
            'code.unique' => 'Mã giảm giá này đã tồn tại.',
            'discount_value.min' => 'Giá trị giảm không được là số âm.',
            'min_spend.min' => 'Đơn tối thiểu không được là số âm.',
            'max_exchanges.integer' => 'Số lượt đổi tối đa phải là số nguyên.',
            'max_exchanges.min'     => 'Số lượt đổi tối đa phải lớn hơn hoặc bằng 1.',
        ]);

        $voucher->update($request->all());
        return response()->json($voucher);
    }

    public function destroy($id)
    {
        $voucher = Voucher::findOrFail($id);
        if ($voucher->bookings()->exists()) {
            return response()->json(['message' => 'Mã giảm giá đã được sử dụng, không thể xóa.'], 409);
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
            // 1. Lock dữ liệu Voucher để tránh xung đột lượt đổi
            $voucher = Voucher::lockForUpdate()->find($voucherId);

            if (!$voucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher không tồn tại!'
                ], 444);
            }

            // 2. Kiểm tra Trạng thái & Hạn sử dụng
            if ($voucher->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher này hiện không khả dụng!'
                ], 400);
            }

            if ($voucher->end_date && now()->gt($voucher->end_date)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher này đã hết hạn đổi!'
                ], 400);
            }

            // 3. Kiểm tra Tổng giới hạn lượt dùng toàn hệ thống (nếu Admin có thiết lập)
            if (!is_null($voucher->total_quantity) && $voucher->used_quantity >= $voucher->total_quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher này đã hết lượt quy đổi trên hệ thống!'
                ], 400);
            }

            $userClaimCount = DB::table('user_vouchers')
                ->where('user_id', $userId)
                ->where('voucher_id', $voucherId)
                ->count();

            $limitPerUser = $voucher->limit_per_user ?? 1;
            if ($userClaimCount >= $limitPerUser) {
                return response()->json([
                    'success' => false,
                    'message' => "Bạn đã sở hữu tối đa ({$limitPerUser}) Voucher này trong ví rồi!"
                ], 400);
            }

            if ($voucher->points_required && $voucher->points_required > 0) {
                $user = auth()->user();
                if ($user->points < $voucher->points_required) {
                    return response()->json([
                        'success' => false,
                        'message' => "Bạn không đủ điểm tích lũy! Cần {$voucher->points_required} điểm."
                    ], 400);
                }

                // Trừ điểm user
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
            // Đã dùng HOẶC Đã hết hạn
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
