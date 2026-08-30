<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\PricingRule;

class PricingRuleController extends Controller
{
    public function index()
    {
        $rule = PricingRule::first();
        if (!$rule) {
            $rule = PricingRule::create(PricingRule::normalizePayload([
                'standard_price' => 50000,
                'vip_price' => 70000,
                'couple_price' => 120000,
                'weekend_surcharge' => 10000,
                'happy_hour_discount' => 10000,
                'format_3d_surcharge' => 30000,
                'sneak_show_surcharge' => 20000,
                'pricing_rules' => []
            ]));
        }

        return response()->json([
            'success' => true,
            'data' => $rule
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'standard_price' => 'nullable|integer|min:0|max:1000000000',
            'vip_price' => 'nullable|integer|min:0|max:1000000000',
            'couple_price' => 'nullable|integer|min:0|max:1000000000',
            'weekend_price' => 'nullable|integer|min:0|max:1000000000',
            'happy_hour_price' => 'nullable|integer|min:0|max:1000000000',
            'format_3d_price' => 'nullable|integer|min:0|max:1000000000',
            'sneak_show_price' => 'nullable|integer|min:0|max:1000000000',
            'weekend_surcharge' => 'nullable|integer|min:0|max:1000000000',
            'happy_hour_discount' => 'nullable|integer|min:0|max:1000000000',
            'format_3d_surcharge' => 'nullable|integer|min:0|max:1000000000',
            'sneak_show_surcharge' => 'nullable|integer|min:0|max:1000000000',
            'pricing_rules' => 'nullable|array',
            'pricing_rules.*.name' => 'required|string|max:255',
            'pricing_rules.*.scope' => 'required|in:system,movie',
            'pricing_rules.*.movie_id' => 'nullable|integer|exists:movies,id',
            'pricing_rules.*.seat_type' => 'nullable|in:all,standard,vip,couple',
            'pricing_rules.*.adjustment_type' => 'required|in:surcharge,percentage,free',
            'pricing_rules.*.value' => 'nullable|numeric|min:0',
            'pricing_rules.*.start_date' => 'nullable|date_format:Y-m-d',
            'pricing_rules.*.end_date' => 'nullable|date_format:Y-m-d',
            'pricing_rules.*.excluded_dates' => 'nullable|array',
            'pricing_rules.*.excluded_dates.*' => 'date_format:Y-m-d',
            'pricing_rules.*.use_time_filter' => 'boolean',
            'pricing_rules.*.time_from' => 'nullable|date_format:H:i',
            'pricing_rules.*.time_to' => 'nullable|date_format:H:i',
            'pricing_rules.*.status' => 'nullable|in:active,inactive',
        ]);

        if ($request->filled('standard_price') && $request->filled('vip_price') &&
            $request->integer('standard_price') > $request->integer('vip_price')) {
            return response()->json([
                'message' => 'Giá vé phải theo thứ tự Thường <= VIP <= Ghế đôi.',
                'errors' => [
                    'standard_price' => ['Giá vé Thường phải nhỏ hơn hoặc bằng Giá vé VIP.'],
                    'vip_price' => ['Giá vé VIP phải lớn hơn hoặc bằng Giá vé Thường.']
                ]
            ], 422);
        }

        if ($request->filled('vip_price') && $request->filled('couple_price') &&
            $request->integer('vip_price') > $request->integer('couple_price')) {
            return response()->json([
                'message' => 'Giá vé phải theo thứ tự Thường <= VIP <= Ghế đôi.',
                'errors' => [
                    'vip_price' => ['Giá vé VIP phải nhỏ hơn hoặc bằng Giá vé Đôi.'],
                    'couple_price' => ['Giá vé Đôi phải lớn hơn hoặc bằng Giá vé VIP.']
                ]
            ], 422);
        }

        $rule = PricingRule::first();
        if (!$rule) {
            $rule = new PricingRule();
        }

        $payload = $request->only([
            'standard_price',
            'vip_price',
            'couple_price',
            'weekend_surcharge',
            'happy_hour_discount',
            'format_3d_surcharge',
            'sneak_show_surcharge',
            'pricing_rules'
        ]);

        $rule->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật cấu hình giá thành công',
            'data' => $rule
        ]);
    }
}
