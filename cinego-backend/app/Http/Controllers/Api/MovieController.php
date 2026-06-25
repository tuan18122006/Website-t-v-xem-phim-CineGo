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
    public function index()
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


    public function show($id)
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


    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'rating'       => 'required|string',
            'description'  => 'required|string',
            'duration'     => 'required|integer|min:1',
            'genre_ids' => 'required|array|min:1',
            'release_date' => 'required|date',
            'status'       => 'required|string',
            'trailer_url'  => 'required|url',
            'poster'       => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
        ], [
            'title.required'        => 'Vui lòng nhập tên phim.',
            'required'            => 'Trường :attribute không được để trống.',
            'trailer_url.url'     => 'Đường dẫn Trailer phải là một URL hợp lệ.',
            'duration.min'        => 'Thời lượng phải là số dương.',
            'genre_ids.required' => 'Vui lòng chọn ít nhất một thể loại!',
            'genre_ids.min'      => 'Vui lòng chọn ít nhất một thể loại!',
            'poster.image'        => 'File phải là hình ảnh.',
            'poster.mimes'        => 'Chỉ chấp nhận: jpeg, png, jpg, webp.',
            'poster.max'          => 'Dung lượng ảnh tối đa là 2MB.',
        ]);

        $posterUrl = null;
        if ($request->hasFile('poster')) {
            $path = $request->file('poster')->store('posters', 'public');
            $posterUrl = asset('storage/' . $path);
        }

        $slug = Str::slug($request->title);
        if (Movie::where('slug', $slug)->exists()) {
            return response()->json([
                'errors' => ['title' => ['Tên phim này đã tồn tại, vui lòng chọn tên khác!']]
            ], 422);
        }

        $movie = Movie::create([
            'title'        => $request->title,
            'slug'         => $slug,
            'description'  => $request->description,
            'duration'     => $request->duration,
            'release_date' => $request->release_date,
            'status'       => $request->status,
            'rating'       => $request->rating,
            'poster_url'   => $posterUrl,
            'trailer_url'  => $request->trailer_url
        ]);

        if ($request->has('genre_ids')) {
            $movie->genres()->attach($request->genre_ids);
        }

        return response()->json(['success' => true, 'data' => $movie->load('genres')], 201);
    }
    public function update(Request $request, $id)
    {
        $movie = Movie::findOrFail($id);

        $request->validate([
            'title'        => 'required|string|max:255',
            'rating'       => 'required|string',
            'description'  => 'required|string',
            'duration'     => 'required|integer|min:1',
            'genre_ids' => 'required|array|min:1',
            'release_date' => 'required|date',
            'status'       => 'required|string',
            'trailer_url'  => 'required|url',
            'poster'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ], [
            'title.required'        => 'Vui lòng nhập tên phim.',
            'required'            => 'Trường :attribute không được để trống.',
            'trailer_url.url'     => 'Đường dẫn Trailer phải là một URL hợp lệ.',
            'duration.min'        => 'Thời lượng phải là số dương.',
            'genre_ids.required' => 'Vui lòng chọn ít nhất một thể loại!',
            'genre_ids.min'      => 'Vui lòng chọn ít nhất một thể loại!',
            'poster.image'        => 'File phải là hình ảnh.',
            'poster.mimes'        => 'Chỉ chấp nhận: jpeg, png, jpg, webp.',
            'poster.max'          => 'Dung lượng ảnh tối đa là 2MB.',
        ]);

        $data = $request->except(['poster', 'genre_ids', '_method']);

        if ($request->hasFile('poster')) {
            if ($movie->poster_url) {
                $oldPath = str_replace(asset('storage/'), '', $movie->poster_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('poster')->store('posters', 'public');
            $data['poster_url'] = asset('storage/' . $path);
        }


        $slug = Str::slug($request->title);
        if (Movie::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            return response()->json(['errors' => ['title' => ['Tên phim này đã tồn tại!']]], 422);
        }

        $movie->update($data);



        if ($request->has('genre_ids')) {
            $genreIds = is_array($request->genre_ids) ? $request->genre_ids : json_decode($request->genre_ids, true);
            $movie->genres()->sync($genreIds);
        }

        return response()->json(['success' => true, 'message' => 'Cập nhật thành công!']);
    }


    public function destroy($id)
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

    public function search(Request $request)
    {
        try {
            $query = Movie::with('genres');

            if ($request->has('keyword') && !empty($request->keyword)) {
                $query->where('title', 'LIKE', '%' . $request->keyword . '%');
            }

            if ($request->has('year') && !empty($request->year)) {
                $query->whereYear('release_date', $request->year);
            }

            if ($request->has('country') && !empty($request->country)) {
                $query->where('country', $request->country);
            }

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
