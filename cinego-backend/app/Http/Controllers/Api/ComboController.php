<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Combo;
use Illuminate\Support\Facades\Storage;

class ComboController extends Controller
{
    public function getActive() {
        return response()->json(['success' => true, 'data' => Combo::where('status', 'active')->get()]);
    }

    public function index() {
        return response()->json(['success' => true, 'data' => Combo::all()]);
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255', 
            'price' => 'required|numeric|min:0', 
            'type' => 'required|in:combo,food,drink',
            'stock' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048'
        ], [
            'name.required' => 'Vui lòng nhập tên combo/sản phẩm.',
            'price.required' => 'Vui lòng nhập giá tiền.',
            'price.min' => 'Giá tiền không được nhỏ hơn 0.',
            'type.required' => 'Vui lòng chọn loại sản phẩm.',
            'stock.min' => 'Số lượng kho không hợp lệ.',
            'image.image' => 'Tệp tải lên phải là hình ảnh.',
            'image.mimes' => 'Hình ảnh phải có định dạng jpeg, png, jpg, gif, svg hoặc webp.',
            'image.max' => 'Kích thước hình ảnh không được vượt quá 2MB.'
        ]);
        
        $data = $request->all();
        $data['status'] = $data['status'] ?? 'active';
        
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('combos', 'public');
            $data['image_url'] = asset('storage/' . $path);
        }
        return response()->json(Combo::create($data), 201);
    }

    public function update(Request $request, $id) {
        $combo = Combo::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255', 
            'price' => 'required|numeric|min:0', 
            'type' => 'required|in:combo,food,drink',
            'stock' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048'
        ], [
            'name.required' => 'Vui lòng nhập tên combo/sản phẩm.',
            'price.required' => 'Vui lòng nhập giá tiền.',
            'price.min' => 'Giá tiền không được nhỏ hơn 0.',
            'type.required' => 'Vui lòng chọn loại sản phẩm.',
            'stock.min' => 'Số lượng kho không hợp lệ.',
            'image.image' => 'Tệp tải lên phải là hình ảnh.',
            'image.mimes' => 'Hình ảnh phải có định dạng jpeg, png, jpg, gif, svg hoặc webp.',
            'image.max' => 'Kích thước hình ảnh không được vượt quá 2MB.'
        ]);

        $data = $request->all();
        
        if ($request->hasFile('image')) {
            $oldPath = str_replace(asset('storage/'), '', $combo->image_url);
            Storage::disk('public')->delete($oldPath);
            $path = $request->file('image')->store('combos', 'public');
            $data['image_url'] = asset('storage/' . $path);
        }
        $combo->update($data);
        return response()->json($combo);
    }

    public function destroy($id) {
        $combo = Combo::findOrFail($id);
        $combo->delete();
        return response()->json(['message' => 'Đã xóa combo']);
    }
}