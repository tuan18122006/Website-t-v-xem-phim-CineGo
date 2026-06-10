<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class MovieController extends Controller
{
    /**
     * Lấy toàn bộ danh sách phim kèm thể loại
     */
    public function index(): JsonResponse
    {
        try {
            $movies = Movie::with('genres')->orderBy('id', 'desc')->get();
            return response()->json([
                'success' => true,
                'status' => 'success',
                'data' => $movies
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy chi tiết 1 phim
     */
    public function show($id): JsonResponse
    {
        try {
            $movie = Movie::with('genres')->findOrFail($id);
            return response()->json([
                'success' => true,
                'status' => 'success',
                'data' => $movie
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Không tìm thấy phim!'
            ], 404);
        }
    }

    /**
     * Tạo phim mới (Admin)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'rating' => 'required|string',
            'description' => 'required|string',
            'duration' => 'required|integer',
            'release_date' => 'required|date',
            'status' => 'required|string',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        $posterUrl = null;
        if ($request->hasFile('poster')) {
            $path = $request->file('poster')->store('posters', 'public');
            $posterUrl = asset('storage/' . $path);
        }

        $movie = Movie::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . time(),
            'description' => $request->description,
            'duration' => $request->duration,
            'release_date' => $request->release_date,
            'status' => $request->status,
            'rating' => $request->rating,
            'poster_url' => $posterUrl,
            'trailer_url' => $request->trailer_url
        ]);

        // Xử lý genre_ids nếu gửi từ FormData
        $genreIds = $request->has('genre_ids') ? (is_array($request->genre_ids) ? $request->genre_ids : json_decode($request->genre_ids, true)) : [];
        if (!empty($genreIds)) {
            $movie->genres()->attach($genreIds);
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $movie->load('genres')
        ], 201);
    }

    /**
     * Cập nhật phim (Admin)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $movie = Movie::findOrFail($id);

        $request->validate([
            'title' => 'required|string',
            'rating' => 'required|string',
            'description' => 'required|string',
            'duration' => 'required|integer',
            'status' => 'required|string',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        $data = $request->except(['poster', 'genre_ids', '_method']);
        
        if ($request->hasFile('poster')) {
            // Xóa ảnh cũ nếu có
            if ($movie->poster_url) {
                $oldPath = str_replace(asset('storage/'), '', $movie->poster_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('poster')->store('posters', 'public');
            $data['poster_url'] = asset('storage/' . $path);
        }

        $movie->update($data);
        
        if ($request->has('genre_ids')) {
            $genreIds = is_array($request->genre_ids) ? $request->genre_ids : json_decode($request->genre_ids, true);
            $movie->genres()->sync($genreIds);
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Cập nhật thành công!'
        ]);
    }

    /**
     * Xóa phim (Admin)
     */
    public function destroy($id): JsonResponse
    {
        $movie = Movie::findOrFail($id);
        if ($movie->poster_url) {
            $oldPath = str_replace(asset('storage/'), '', $movie->poster_url);
            Storage::disk('public')->delete($oldPath);
        }
        $movie->delete();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Xóa phim thành công!'
        ], 200);
    }

    /**
     * API Tìm kiếm và lọc phim nâng cao (Client)
     */
    public function search(Request $request): JsonResponse
    {
        try {
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

            $movies = $query->latest()->get();

            return response()->json([
                'success' => true,
                'status' => 'success',
                'data' => $movies
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Lỗi lọc dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }
}
