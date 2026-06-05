<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GenreController extends Controller
{
    // 1. Hàm lấy danh sách thể loại
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Genre::all()
        ], 200);
    }

    // 2. Hàm thêm mới thể loại (Đảm bảo tự sinh slug để không bị rỗng DB)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:genres,name',
        ]);

        $genre = Genre::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name), // Tự động chuyển "Kinh Dị" thành "kinh-di"
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm thể loại thành công',
            'data' => $genre
        ], 201);
    }

    // 3. Hàm cập nhật thể loại
    public function update(Request $request, $id)
    {
        $genre = Genre::find($id);
        if (!$genre) {
            return response()->json(['message' => 'Không tìm thấy thể loại'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:genres,name,' . $id,
        ]);

        $genre->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thể loại thành công',
            'data' => $genre
        ], 200);
    }

    // 4. Hàm xóa thể loại
    public function destroy($id)
    {
        
        $genre = Genre::find($id);
        if (!$genre) {
            return response()->json(['message' => 'Không tìm thấy thể loại'], 404);
        }
        
        $genre->delete();
        return response()->json([
            'success' => true,
            'message' => 'Xóa thể loại thành công'
        ], 200);
    }
}