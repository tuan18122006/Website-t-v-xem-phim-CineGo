<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogCategoryController extends Controller
{
    /**
     * Lấy danh sách toàn bộ chuyên mục (Dùng chung cho cả Client lọc bài và Admin chọn khi viết bài)
     */
    public function index()
    {
        try {
            // Lấy danh mục kèm theo số lượng bài viết thuộc danh mục đó
            $categories = BlogCategory::withCount('blogPosts')->orderBy('name', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin tạo chuyên mục mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:blog_categories,name',
        ], [
            'name.required' => 'Tên chuyên mục không được bỏ trống.',
            'name.unique' => 'Tên chuyên mục này đã tồn tại trong hệ thống.'
        ]);

        try {
            $slug = Str::slug($validated['name']);
            
            // Đảm bảo slug là duy nhất
            $originalSlug = $slug;
            $count = 1;
            while (BlogCategory::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $category = BlogCategory::create([
                'name' => $validated['name'],
                'slug' => $slug
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tạo chuyên mục thành công!',
                'data' => $category
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi không thể tạo chuyên mục: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xem chi tiết 1 chuyên mục (kèm theo các bài viết thuộc chuyên mục đó)
     */
    public function show($id)
    {
        try {
            $category = BlogCategory::with('blogPosts')->find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chuyên mục không tồn tại.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $category
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin cập nhật chuyên mục
     */
    public function update(Request $request, $id)
    {
        $category = BlogCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Chuyên mục không tồn tại.'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:blog_categories,name,' . $id,
        ], [
            'name.required' => 'Tên chuyên mục không được bỏ trống.',
            'name.unique' => 'Tên chuyên mục này đã trùng với chuyên mục khác.'
        ]);

        try {
            // Cập nhật slug nếu thay đổi tên danh mục
            if ($category->name !== $validated['name']) {
                $slug = Str::slug($validated['name']);
                $originalSlug = $slug;
                $count = 1;
                while (BlogCategory::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }
                $category->slug = $slug;
            }

            $category->name = $validated['name'];
            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật chuyên mục thành công!',
                'data' => $category
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi cập nhật chuyên mục: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin xóa chuyên mục
     */
    public function destroy($id)
    {
        try {
            $category = BlogCategory::find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chuyên mục không tồn tại.'
                ], 404);
            }

            // Xóa chuyên mục (Trong Migration ta đã set onDelete('cascade'), 
            // nên khi xóa danh mục, toàn bộ bài viết thuộc danh mục đó cũng sẽ tự động được dọn dẹp).
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa chuyên mục thành công!'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa chuyên mục: ' . $e->getMessage()
            ], 500);
        }
    }
}