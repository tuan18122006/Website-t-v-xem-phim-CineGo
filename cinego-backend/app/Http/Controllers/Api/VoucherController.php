<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher;
use Carbon\Carbon;
use App\Models\Combo;

class VoucherController extends Controller
{
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $code = strtoupper($request->code);
        $subtotal = $request->subtotal;

        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher) {
            return response()->json([
                'message' => 'Mã giảm giá không tồn tại.'
            ], 404);
        }

        // Check expiration
        if ($voucher->expires_at && Carbon::parse($voucher->expires_at)->isPast()) {
            return response()->json([
                'message' => 'Mã giảm giá đã hết hạn sử dụng.'
            ], 422);
        }

        // Check usage limit
        if ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) {
            return response()->json([
                'message' => 'Mã giảm giá đã hết lượt sử dụng.'
            ], 422);
        }

        // Check min spend requirement
        if ($subtotal < $voucher->min_spend) {
            return response()->json([
                'message' => 'Chưa đạt giá trị đơn hàng tối thiểu để áp dụng mã giảm giá này. Cần tối thiểu ' . number_format($voucher->min_spend) . ' đ.'
            ], 422);
        }
        $discountAmount = 0;

        // Tính toán số tiền giảm
        if ($voucher->discount_type === 'fixed') {
            $discountAmount = $voucher->discount_value;
        } else {
            // Nếu là phần trăm
            $discountAmount = ($subtotal * $voucher->discount_value) / 100;

            // Kiểm tra max_discount nếu có
            if ($voucher->max_discount !== null && $discountAmount > $voucher->max_discount) {
                $discountAmount = $voucher->max_discount;
            }
        }

        return response()->json([
            'id' => $voucher->id,
            'code' => $voucher->code,
            'discount_type' => $voucher->discount_type,
            'discount_value' => (float) $voucher->discount_value,
            'min_spend' => (float) $voucher->min_spend,
            'max_discount' => $voucher->max_discount ? (float) $voucher->max_discount : null,
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
            'max_discount' => 'nullable|numeric|min:0',
            'expires_at' => 'required|date|after:now',
            'usage_limit' => 'nullable|integer|min:1',
        ], [
            'code.required' => 'Mã giảm giá không được để trống.',
            'code.unique' => 'Mã giảm giá này đã tồn tại, vui lòng chọn mã khác.',
            'discount_value.required' => 'Vui lòng nhập giá trị.',
            'discount_value.min' => 'Giá trị giảm không được là số âm.',
            'min_spend.min' => 'Đơn tối thiểu không được là số âm.',
            'expires_at.after' => 'Ngày hết hạn phải là thời điểm trong tương lai.',
            'expires_at.required' => 'Vui lòng chọn ngày hết hạn mã.',

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
        ], [
            'code.unique' => 'Mã giảm giá này đã tồn tại.',
            'discount_value.min' => 'Giá trị giảm không được là số âm.',
            'min_spend.min' => 'Đơn tối thiểu không được là số âm.',
        ]);

        $voucher->update($request->all());
        return response()->json($voucher);
    }
    // 4. Xóa mã giảm giá
    public function destroy($id)
    {
        $combo = Combo::findOrFail($id);

        if ($combo->bookingCombos()->exists()) {
            return response()->json([
                'message' => 'Combo đã được sử dụng trong đơn đặt vé nên không thể xóa.'
            ], 409);
        }

        $combo->delete();

        return response()->json([
            'message' => 'Xóa combo thành công.'
        ]);
    }
}
