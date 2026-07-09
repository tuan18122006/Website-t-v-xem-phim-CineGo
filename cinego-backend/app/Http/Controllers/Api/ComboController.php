<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Combo;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\ComboResource;

class ComboController extends Controller
{
    //
  public function index()
{
    return ComboResource::collection(Combo::all());
}

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:combos,name|max:255',
            'description' => 'required|string|max:1000',
            'type' => 'required|in:combo,drink,food',
            'price' => 'required|numeric|min:1',
            'stock' => 'required|integer|min:0',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
        ], [
            'name.required' => 'Vui lòng nhập tên combo.',
            'name.unique' => 'Tên combo đã tồn tại.',
            'description.required' => 'Vui lòng nhập mô tả.',
            'type.required' => 'Vui lòng chọn loại sản phẩm.',
            'price.min' => 'Giá tiền không được là số âm.',
            'price.required' => 'Vui lòng nhập gía tiền.',
            'stock.min' => 'Số lượng không được là số âm.',
            'image.required' => 'Bạn chưa chọn ảnh.',
            'image.image' => 'File phải là hình ảnh.',
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('combos', 'public');
            $data['image_url'] = '/storage/' . $path;
        } else {
            return response()->json(['message' => 'Lỗi tải ảnh lên'], 400);
class ComboController extends Controller
{
    // Lấy danh sách cho Client (chỉ lấy combo active)
    public function getActive()
    {
        $combos = Combo::where('status', 'active')->get();
        return response()->json([
            'success' => true,
            'data' => $combos
        ], 200);
    }

    // Lấy toàn bộ danh sách cho Admin
    public function index()
    {
        $combos = Combo::all();
        return response()->json([
            'success' => true,
            'data' => $combos
        ], 200);
    }

    // Thêm mới
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:active,inactive'
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('combos', 'public');
            $data['image_url'] = asset('storage/' . $path);
        }

        $combo = Combo::create($data);

        return response()->json($combo, 201);
    }

    public function update(Request $request, $id)
    {
        $combo = Combo::findOrFail($id);

        $request->validate([
            'name' => 'required|unique:combos,name,' . $id . '|max:255',
            'description' => 'required|string|max:1000',
            'type'=>'required|in:combo,drink,food',
            'price' => 'required|numeric|min:1',
            'stock' => 'required|integer|min:1',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ], [
            'name.required' => 'Vui lòng nhập tên combo.',
            'name.unique' => 'Tên combo đã tồn tại.',
            'description.required' => 'Vui lòng nhập mô tả.',
            'type.required' => 'Vui lòng chọn loại sản phẩm.',
            'price.min' => 'Giá tiền không được là số âm.',
            'price.required' => 'Vui lòng nhập gía tiền.',
            'stock.min' => 'Số lượng không được là số âm.',
            'image.required' => 'Bạn chưa chọn ảnh.',
            'image.image' => 'File phải là hình ảnh.',
        ]);

        $data = $request->except(['image', '_method']);

        if ($request->hasFile('image')) {
            if ($combo->image_url) {
                $oldPath = str_replace('/storage/', '', $combo->image_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('image')->store('combos', 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        $combo->update($data);
        return response()->json($combo);
    }

   public function destroy($id)
{
    $combo = Combo::findOrFail($id);
    
    try {
        if ($combo->image_url) {
            $path = str_replace('/storage/', '', $combo->image_url);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
        $combo->delete();
        
        return response()->json(['message' => 'Đã xóa combo và ảnh thành công']);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Lỗi khi xóa tài nguyên: ' . $e->getMessage()], 500);
    }
}
}
        return response()->json([
            'success' => true,
            'message' => 'Thêm Combo thành công',
            'data' => $combo
        ], 201);
    }

    // Cập nhật
    public function update(Request $request, $id)
    {
        $combo = Combo::find($id);
        if (!$combo) {
            return response()->json(['message' => 'Không tìm thấy Combo'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'status' => 'sometimes|in:active,inactive'
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            if ($combo->image_url) {
                // Parse old path
                $oldPath = str_replace(asset('storage/'), '', $combo->image_url);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('image')->store('combos', 'public');
            $data['image_url'] = asset('storage/' . $path);
        }

        $combo->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật Combo thành công',
            'data' => $combo
        ], 200);
    }

    // Xóa (hoặc ẩn đi)
    public function destroy($id)
    {
        $combo = Combo::find($id);
        if (!$combo) {
            return response()->json(['message' => 'Không tìm thấy Combo'], 404);
        }

        $combo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa Combo'
        ], 200);
    }
}
