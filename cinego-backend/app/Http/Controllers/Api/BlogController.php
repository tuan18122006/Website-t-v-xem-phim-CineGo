<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BlogCategory; // Giả định bạn đã có bảng/Model Category để phân loại Blog
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BlogController extends Controller
{
    /**
     * Lấy danh sách bài viết (Đồng bộ với bộ lọc và tìm kiếm ở BlogList.vue)
     */
    public function index(Request $request)
    {
        try {
            $query = BlogPost::with('category'); // Eager loading danh mục blog

            // 🔍 Tìm kiếm theo tiêu đề (Debounce từ Frontend)
            if ($request->filled('search')) {
                $query->where('title', 'like', '%' . $request->search . '%');
            }

            // 📁 Lọc theo Chuyên mục
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // 🟢 Lọc theo Trạng thái (published, draft, scheduled)
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Lấy toàn bộ bài viết mới nhất lên đầu
            $posts = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $posts
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo bài viết mới (Xử lý từ BlogCreate.vue)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'category_id' => 'required|integer',
            'thumbnail_url' => 'nullable|string',
            'status' => 'required|in:draft,published,scheduled',
            'published_at' => 'nullable|date',
            'movie_id' => 'nullable|integer|exists:movies,id',
        ], [
            'title.required' => 'Tiêu đề bài viết không được để trống.',
            'content.required' => 'Nội dung bài viết không được để trống.',
            'category_id.required' => 'Vui lòng chọn chuyên mục cho bài viết.'
        ]);

        try {
            // Tự động sinh Slug chuẩn SEO nếu rỗng
            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $count = 1;
            while (BlogPost::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            // Xử lý thời gian xuất bản bài viết
            $publishedAt = null;
            if ($validated['status'] === 'published') {
                $publishedAt = Carbon::now();
            } elseif ($validated['status'] === 'scheduled') {
                $publishedAt = $validated['published_at'] ? Carbon::parse($validated['published_at']) : Carbon::now();
            }

            $post = BlogPost::create([
                'title' => $validated['title'],
                'slug' => $slug,
                'content' => $validated['content'],
                'excerpt' => $validated['excerpt'] ?? null,
                'category_id' => $validated['category_id'],
                'thumbnail_url' => $validated['thumbnail_url'] ?? null,
                'status' => $validated['status'],
                'published_at' => $publishedAt,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tạo bài viết thành công!',
                'data' => $post
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi tạo bài viết: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xem chi tiết bài viết (Đổ dữ liệu lên BlogDetail.vue hoặc BlogEdit.vue)
     */
    public function show($id)
    {
        try {
            $post = BlogPost::with('category')->find($id);

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy bài viết này.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $post
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật bài viết (Xử lý lưu từ BlogEdit.vue)
     */
    public function update(Request $request, $id)
    {
        $post = BlogPost::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Bài viết cần sửa không tồn tại.'
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'category_id' => 'required|integer',
            'thumbnail_url' => 'nullable|string',
            'status' => 'required|in:draft,published,scheduled',
            'published_at' => 'nullable|date',
        ]);

        try {
            // Cập nhật lại Slug nếu thay đổi tiêu đề bài viết
            if ($post->title !== $validated['title']) {
                $slug = Str::slug($validated['title']);
                $originalSlug = $slug;
                $count = 1;
                while (BlogPost::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }
                $post->slug = $slug;
            }

            // Xử lý thời gian cập nhật trạng thái xuất bản
            if ($validated['status'] === 'published') {
                $post->published_at = Carbon::now();
            } elseif ($validated['status'] === 'scheduled') {
                $post->published_at = $validated['published_at'] ? Carbon::parse($validated['published_at']) : $post->published_at;
            } else {
                $post->published_at = null; // Chuyển về bản nháp
            }

            $post->update([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'excerpt' => $validated['excerpt'] ?? null,
                'category_id' => $validated['category_id'],
                'thumbnail_url' => $validated['thumbnail_url'] ?? null,
                'status' => $validated['status'],
                'published_at' => $post->published_at,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật bài viết thành công!',
                'data' => $post
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi cập nhật: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa bài viết (Nhận lệnh từ nút Xóa ở BlogList.vue)
     */
    public function destroy($id)
    {
        try {
            $post = BlogPost::find($id);

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bài viết không tồn tại.'
                ], 404);
            }

            $post->delete();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa bài viết thành công!'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa: ' . $e->getMessage()
            ], 500);
        }
    }
}