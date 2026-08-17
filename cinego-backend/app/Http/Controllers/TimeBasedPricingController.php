<?php

namespace App\Http\Controllers;

use App\Models\TimeBasedPricingRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TimeBasedPricingController extends Controller
{
    /**
     * Lấy danh sách các quy tắc giá theo ngày/giờ
     */
    public function index()
    {
        $rules = TimeBasedPricingRule::orderBy('start_date', 'desc')
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json([
            'data' => $rules,
            'total' => $rules->count()
        ]);
    }

    /**
     * Tạo quy tắc giá mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scope' => ['required', Rule::in(['system', 'movie'])],
            'movie_id' => 'nullable|integer|exists:movies,id',
            'seat_type' => ['nullable', Rule::in(['standard', 'vip', 'couple', ''])],
            'adjustment_type' => ['required', Rule::in(['surcharge', 'percentage', 'free'])],
            'price_adjustment' => 'nullable|integer|min:-1000000|max:1000000',
            'use_date' => 'boolean',
            'use_time' => 'boolean',
            'start_date' => 'nullable|date|date_format:Y-m-d',
            'end_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'is_active' => 'boolean'
        ]);

        // Validate scope and movie_id relationship
        if ($validated['scope'] === 'movie' && !$validated['movie_id']) {
            return response()->json([
                'message' => 'Khi chọn "Theo phim", vui lòng chọn phim',
                'errors' => ['movie_id' => ['Phim không được để trống']]
            ], 422);
        }

        if ($validated['scope'] === 'system') {
            $validated['movie_id'] = null;
        }

        // Validate seat_type (can be empty for "all")
        if (!$validated['seat_type'] || $validated['seat_type'] === '') {
            $validated['seat_type'] = null;
        }

        // Validate price_adjustment based on adjustment_type
        if ($validated['adjustment_type'] === 'free') {
            $validated['price_adjustment'] = 0;
        } elseif (!isset($validated['price_adjustment']) || $validated['price_adjustment'] === null) {
            return response()->json([
                'message' => 'Giá trị không được để trống',
                'errors' => ['price_adjustment' => ['Vui lòng nhập giá trị']]
            ], 422);
        }

        // Validate that at least one section is selected
        if (!$validated['use_date'] && !$validated['use_time']) {
            return response()->json([
                'message' => 'Vui lòng chọn ít nhất Theo Ngày hoặc Theo Giờ',
                'errors' => ['rules' => ['Phải chọn ít nhất một phần']]
            ], 422);
        }

        // Validate required fields for selected sections
        if ($validated['use_date'] && (!$validated['start_date'] || !$validated['end_date'])) {
            return response()->json([
                'message' => 'Khi chọn Theo Ngày, vui lòng điền ngày bắt đầu và kết thúc',
                'errors' => ['date_fields' => ['Cần điền ngày nếu chọn Theo Ngày']]
            ], 422);
        }

        if ($validated['use_time'] && (!$validated['start_time'] || !$validated['end_time'])) {
            return response()->json([
                'message' => 'Khi chọn Theo Giờ, vui lòng điền giờ bắt đầu và kết thúc',
                'errors' => ['time_fields' => ['Cần điền giờ nếu chọn Theo Giờ']]
            ], 422);
        }

        // Clear unused fields
        if (!$validated['use_date']) {
            $validated['start_date'] = null;
            $validated['end_date'] = null;
        }
        if (!$validated['use_time']) {
            $validated['start_time'] = null;
            $validated['end_time'] = null;
        }

        $rule = TimeBasedPricingRule::create($validated);

        return response()->json([
            'message' => 'Quy tắc giá được tạo thành công',
            'data' => $rule
        ], 201);
    }

    /**
     * Lấy chi tiết quy tắc giá
     */
    public function show(TimeBasedPricingRule $rule)
    {
        return response()->json($rule);
    }

    /**
     * Cập nhật quy tắc giá
     */
    public function update(Request $request, TimeBasedPricingRule $rule)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'scope' => [Rule::in(['system', 'movie'])],
            'movie_id' => 'nullable|integer|exists:movies,id',
            'seat_type' => [Rule::in(['standard', 'vip', 'couple'])],
            'price_adjustment' => 'integer|min:-1000000|max:1000000',
            'use_date' => 'boolean',
            'use_time' => 'boolean',
            'start_date' => 'nullable|date|date_format:Y-m-d',
            'end_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'is_active' => 'boolean'
        ]);

        // Get scope and use flags
        $scope = $validated['scope'] ?? $rule->scope;
        $use_date = $validated['use_date'] ?? $rule->use_date;
        $use_time = $validated['use_time'] ?? $rule->use_time;

        // Validate scope and movie_id relationship
        if ($scope === 'movie' && !($validated['movie_id'] ?? $rule->movie_id)) {
            return response()->json([
                'message' => 'Khi chọn "Theo phim", vui lòng chọn phim',
                'errors' => ['movie_id' => ['Phim không được để trống']]
            ], 422);
        }

        if ($scope === 'system') {
            $validated['movie_id'] = null;
        }

        // Validate that at least one section is selected
        if (!$use_date && !$use_time) {
            return response()->json([
                'message' => 'Vui lòng chọn ít nhất Theo Ngày hoặc Theo Giờ',
                'errors' => ['rules' => ['Phải chọn ít nhất một phần']]
            ], 422);
        }

        // Validate required fields for selected sections
        if ($use_date && (!($validated['start_date'] ?? $rule->start_date) || !($validated['end_date'] ?? $rule->end_date))) {
            return response()->json([
                'message' => 'Khi chọn Theo Ngày, vui lòng điền ngày bắt đầu và kết thúc',
                'errors' => ['date_fields' => ['Cần điền ngày nếu chọn Theo Ngày']]
            ], 422);
        }

        if ($use_time && (!($validated['start_time'] ?? $rule->start_time) || !($validated['end_time'] ?? $rule->end_time))) {
            return response()->json([
                'message' => 'Khi chọn Theo Giờ, vui lòng điền giờ bắt đầu và kết thúc',
                'errors' => ['time_fields' => ['Cần điền giờ nếu chọn Theo Giờ']]
            ], 422);
        }

        // Clear unused fields
        if (!$use_date) {
            $validated['start_date'] = null;
            $validated['end_date'] = null;
        }
        if (!$use_time) {
            $validated['start_time'] = null;
            $validated['end_time'] = null;
        }

        $rule->update($validated);

        return response()->json([
            'message' => 'Quy tắc giá được cập nhật thành công',
            'data' => $rule
        ]);
    }

    /**
     * Xóa quy tắc giá
     */
    public function destroy(TimeBasedPricingRule $rule)
    {
        $rule->delete();

        return response()->json([
            'message' => 'Quy tắc giá được xóa thành công'
        ]);
    }

    /**
     * Bật/tắt quy tắc giá
     */
    public function toggle(TimeBasedPricingRule $rule)
    {
        $rule->is_active = !$rule->is_active;
        $rule->save();

        return response()->json([
            'message' => $rule->is_active ? 'Quy tắc giá được kích hoạt' : 'Quy tắc giá được vô hiệu hóa',
            'data' => $rule
        ]);
    }

    /**
     * Lấy tất cả quy tắc áp dụng cho một showtime
     */
    public function getApplicableRules(Request $request)
    {
        $validated = $request->validate([
            'start_time' => 'required|date_format:Y-m-d H:i',
            'seat_type' => ['required', Rule::in(['standard', 'vip', 'couple'])]
        ]);

        $datetime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $validated['start_time']);
        $rules = TimeBasedPricingRule::getApplicableRules($datetime, $validated['seat_type']);

        return response()->json([
            'rules' => $rules,
            'total_adjustment' => TimeBasedPricingRule::calculateTotalAdjustment($datetime, $validated['seat_type'])
        ]);
    }

    /**
     * Xóa nhiều quy tắc cùng lúc
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:time_based_pricing_rules,id'
        ]);

        TimeBasedPricingRule::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'message' => 'Xóa ' . count($validated['ids']) . ' quy tắc giá thành công'
        ]);
    }

    /**
     * Bật/tắt nhiều quy tắc cùng lúc
     */
    public function bulkToggle(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:time_based_pricing_rules,id',
            'is_active' => 'required|boolean'
        ]);

        TimeBasedPricingRule::whereIn('id', $validated['ids'])
            ->update(['is_active' => $validated['is_active']]);

        return response()->json([
            'message' => 'Cập nhật ' . count($validated['ids']) . ' quy tắc giá thành công'
        ]);
    }
}
