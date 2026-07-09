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
            $rule = PricingRule::create([
                'standard_price' => 50000,
                'vip_price' => 70000,
                'couple_price' => 120000,
                'weekend_surcharge' => 10000,
                'happy_hour_discount' => 10000,
                'format_3d_surcharge' => 30000,
                'sneak_show_surcharge' => 20000
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => $rule
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'standard_price' => 'required|integer|min:0|max:1000000000',
            'vip_price' => 'required|integer|min:0|max:1000000000',
            'couple_price' => 'required|integer|min:0|max:1000000000',
            'weekend_surcharge' => 'required|integer|min:0|max:1000000000',
            'happy_hour_discount' => 'required|integer|min:0|max:1000000000',
            'format_3d_surcharge' => 'required|integer|min:0|max:1000000000',
            'sneak_show_surcharge' => 'required|integer|min:0|max:1000000000',
        ]);

        $rule = PricingRule::first();
        if (!$rule) {
            $rule = new PricingRule();
        }

        $rule->update($request->only([
            'standard_price',
            'vip_price',
            'couple_price',
            'weekend_surcharge',
            'happy_hour_discount',
            'format_3d_surcharge',
            'sneak_show_surcharge'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật cấu hình giá thành công',
            'data' => $rule
        ]);
    }
}
