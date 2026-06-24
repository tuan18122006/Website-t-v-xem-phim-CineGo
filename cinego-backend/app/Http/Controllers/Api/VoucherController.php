<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher;
use Carbon\Carbon;

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

        return response()->json([
            'id' => $voucher->id,
            'code' => $voucher->code,
            'discount_type' => $voucher->discount_type,
            'discount_value' => (float) $voucher->discount_value,
            'min_spend' => (float) $voucher->min_spend,
            'max_discount' => $voucher->max_discount ? (float) $voucher->max_discount : null,
        ], 200);
    }
}
