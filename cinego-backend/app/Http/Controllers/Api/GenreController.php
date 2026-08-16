<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GenreController extends Controller
{

    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Genre::all()
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:genres,name,NULL,id,deleted_at,NULL',
        ],[
            'name.unique' => 'Tên thể loại này đã tồn tại, vui lòng chọn tên khác!',
            'name.required' => 'Tên thể loại không được để trống.',
        ]);

        $baseSlug = Str::slug($request->name);
        $slug = $baseSlug;
        $i = 1;
        while (Genre::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }

        $genre = Genre::create([
            'name' => $request->name,
            'slug' => $slug,
        ],);

        return response()->json([
            'success' => true,
            'message' => 'Thêm thể loại thành công',
            'data' => $genre
        ], 201);
    }


    public function update(Request $request, $id)
    {
        $genre = Genre::find($id);
        if (!$genre) {
            return response()->json(['message' => 'Không tìm thấy thể loại'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:genres,name,' . $id . ',id,deleted_at,NULL',
        ], [
            'name.unique' => 'Tên thể loại này đã tồn tại, vui lòng chọn tên khác!',
            'name.required' => 'Tên thể loại không được để trống.',
        ]);

        $baseSlug = Str::slug($request->name);
        $slug = $baseSlug;
        $i = 1;
        while (Genre::withTrashed()->where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }

        $genre->update([
            'name' => $request->name,
            'slug' => $slug,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thể loại thành công',
            'data' => $genre
        ], 200);
    }


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
