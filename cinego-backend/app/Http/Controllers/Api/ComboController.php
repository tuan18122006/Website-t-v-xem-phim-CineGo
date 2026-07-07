<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use Illuminate\Http\Request;

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
