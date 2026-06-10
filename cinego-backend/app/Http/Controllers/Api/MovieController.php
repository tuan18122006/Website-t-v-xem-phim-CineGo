<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    /**
     * Lấy toàn bộ danh sách phim kèm thể loại để Vue tự lọc, 
     * hoặc dùng để render trực tiếp lên Trang chủ CineGo.
     */
    public function index(): JsonResponse
    {
        try {
            $movies = Movie::with('genres')->get();

            return response()->json([
                'status' => 'success',
                'data' => $movies
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API Tìm kiếm và lọc phim nâng cao
     */
    public function search(Request $request): JsonResponse
    {
        try {
            // Sử dụng Eloquent Query Builder
            $query = Movie::with('genres');

            // 1. Lọc theo Tên phim (Tìm kiếm tương đối LIKE)
            if ($request->has('keyword') && !empty($request->keyword)) {
                $query->where('title', 'LIKE', '%' . $request->keyword . '%');
            }

            // 2. Lọc theo Năm phát hành
            if ($request->has('year') && !empty($request->year)) {
                $query->whereYear('release_date', $request->year);
            }

            // 3. Lọc theo Quốc gia
            if ($request->has('country') && !empty($request->country)) {
                $query->where('country', $request->country);
            }

            // 4. Lọc theo Thể loại (Mối quan hệ nhiều-nhiều)
            if ($request->has('genre_id') && !empty($request->genre_id)) {
                $query->whereHas('genres', function ($q) use ($request) {
                    $q->where('genres.id', $request->genre_id);
                });
            }

            // Lấy danh sách kết quả
            $movies = $query->latest()->get();

            return response()->json([
                'status' => 'success',
                'data' => $movies
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi lọc dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }
}
