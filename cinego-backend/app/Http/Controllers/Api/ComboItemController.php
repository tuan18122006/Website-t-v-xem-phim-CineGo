<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Combo;
use App\Models\ComboItem;
use Illuminate\Support\Facades\DB;

class ComboItemController extends Controller
{
    // Lấy danh sách thành phần của một combo
    public function getItems($comboId)
    {
        $combo = Combo::where('type', 'combo')->findOrFail($comboId);

        return response()->json(
            $combo->comboItems()->with('item')->get()
        );
    }

    // Thêm thành phần vào combo
    public function store(Request $request)
    {
        $request->validate([
            'combo_id' => 'required|exists:combos,id',
            'item_id' => 'required|exists:combos,id',
            'quantity' => 'required|integer|min:1'
        ], [
            'item_id.required' => 'Vui lòng chọn 1 loại đồ.',
            'quantity.min'      => 'Số lượng không được là số âm hoặc bằng 0.',
            'quantity.required' => 'Vui lòng nhập số lượng.',
            'quantity.integer'  => 'Số lượng phải là số nguyên.',
        ]);

        $combo = Combo::findOrFail($request->combo_id);

        if ($combo->type !== 'combo') {
            return response()->json([
                'message' => 'Chỉ sản phẩm loại Combo mới được thêm thành phần.'
            ], 422);
        }

        $item = Combo::findOrFail($request->item_id);

        if (!in_array($item->type, ['drink', 'food'])) {
            return response()->json([
                'message' => 'Thành phần chỉ được là nước hoặc đồ ăn.'
            ], 422);
        }

        $exists = ComboItem::where('combo_id', $request->combo_id)
            ->where('item_id', $request->item_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Sản phẩm này đã tồn tại trong combo.'
            ], 422);
        }

        $comboItem = ComboItem::create([
            'combo_id' => $request->combo_id,
            'item_id' => $request->item_id,
            'quantity' => $request->quantity,
        ]);

        return response()->json($comboItem, 201);
    }

    // Cập nhật số lượng thành phần
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $comboItem = ComboItem::findOrFail($id);

        $comboItem->update([
            'quantity' => $request->quantity
        ]);

        return response()->json($comboItem);
    }

    // Xóa thành phần khỏi combo
    public function destroy($id)
    {
        ComboItem::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Đã xóa thành phần.'
        ]);
    }
}
