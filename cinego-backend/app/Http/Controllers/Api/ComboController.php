<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ComboController extends Controller
{
    public function getActive()
    {
        return response()->json([
            'success' => true,
            'data' => Combo::where('stock', '>', 0)->get()
        ]);
    }

    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Combo::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:combos,name'
            ],
            'description' => [
                'required',
                'string',
                'max:2000',
            ],
            'type' => [
                'required',
                Rule::in(['combo', 'drink', 'food']),
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'stock' => [
                'required',
                'integer',
                'min:0',
            ],
            'image' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
            ],
        ], [
            'name.required' => 'Vui lòng nhập tên combo.',
            'name.max' => 'Tên combo không được vượt quá 255 ký tự.',
            'name.unique' => 'Tên sản phẩm đã tồn tại.',

            'description.required' => 'Vui lòng nhập mô tả.',

            
            'type.required' => 'Vui lòng chọn loại sản phẩm.',
            'type.in' => 'Loại sản phẩm không hợp lệ.',

            'price.required' => 'Vui lòng nhập giá sản phẩm.',
            'price.numeric' => 'Giá sản phẩm phải là số.',
            'price.min' => 'Giá sản phẩm không được nhỏ hơn 0.',

            'stock.required' => 'Vui lòng nhập số lượng kho.',
            'stock.integer' => 'Số lượng kho phải là số nguyên.',
            'stock.min' => 'Số lượng kho không được nhỏ hơn 0.',

            'image.required' => 'Vui lòng chọn 1 ảnh.',
            'image.image' => 'File được chọn phải là hình ảnh.',
            'image.mimes' => 'Ảnh phải có định dạng JPG, JPEG, PNG hoặc WEBP.',
            'image.max' => 'Dung lượng ảnh không được vượt quá 5MB.',
        ]);

        unset($validated['image']);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('combos', 'public');

            $validated['image_url'] = $path;
        }

        $combo = Combo::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Thêm combo thành công.',
            'data' => $combo,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $combo = Combo::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:combos,name,' . $combo->id
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'type' => [
                'required',
                Rule::in(['combo', 'drink', 'food']),
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'stock' => [
                'required',
                'integer',
                'min:0',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
            ],
        ], [
            'name.required' => 'Vui lòng nhập tên combo.',
            'name.unique' => 'Tên sản phẩm đã tồn tại.',
            'type.required' => 'Vui lòng chọn loại sản phẩm.',
            'type.in' => 'Loại sản phẩm không hợp lệ.',
            'price.required' => 'Vui lòng nhập giá sản phẩm.',
            'price.numeric' => 'Giá sản phẩm phải là số.',
            'price.min' => 'Giá sản phẩm không được nhỏ hơn 0.',
            'stock.required' => 'Vui lòng nhập số lượng kho.',
            'stock.integer' => 'Số lượng kho phải là số nguyên.',
            'stock.min' => 'Số lượng kho không được nhỏ hơn 0.',
            'image.image' => 'File được chọn phải là hình ảnh.',
            'image.mimes' => 'Ảnh phải có định dạng JPG, JPEG, PNG hoặc WEBP.',
            'image.max' => 'Dung lượng ảnh không được vượt quá 5MB.',
        ]);

        unset($validated['image']);

        if ($request->hasFile('image')) {
            $this->deleteComboImage($combo->image_url);
            $path = $request->file('image')->store('combos', 'public');

            $validated['image_url'] = $path;
        }

        $combo->update($validated);
        $combo->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật combo thành công.',
            'data' => $combo,
        ]);
    }

    public function destroy($id)
    {
        $combo = Combo::findOrFail($id);

        $this->deleteComboImage($combo->image_url);

        $combo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa combo.',
        ]);
    }

    private function deleteComboImage(?string $imageUrl): void
    {
        if (!$imageUrl) {
            return;
        }

        $urlPath = parse_url($imageUrl, PHP_URL_PATH);

        if (!$urlPath) {
            return;
        }

        $storagePath = preg_replace('#^/storage/#', '', $urlPath);

        if ($storagePath && Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }
    }
}
