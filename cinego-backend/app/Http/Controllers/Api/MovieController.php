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


        public function store(Request $request): JsonResponse
        {
            $request->validate([
                'title'        => 'required|string|max:255|unique:movies,title',
                'rating'       => 'required|string',
                'description'  => 'required|string',
                'duration'     => 'required|integer|min:1',
                'release_date' => 'required|date|after_or_equal:today',
                'status'       => 'required|string',
                'trailer_url'  => 'required|url',
                'poster'       => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
                'genre_ids'    => 'required|array|min:1',
            ], [
                'title.required'        => 'Vui lòng nhập tên phim.',
                'title.unique'          => 'Tên phim này đã tồn tại.',
                'rating.required'       => 'Vui lòng nhập đánh giá.',
                'description.required'  => 'Vui lòng nhập mô tả.',
                'duration.required'     => 'Vui lòng nhập thời lượng.',
                'duration.min'          => 'Thời lượng phải lớn hơn 0.',
                'release_date.required' => 'Vui lòng chọn ngày khởi chiếu.',
                'release_date.after_or_equal' => 'Ngày khởi chiếu không được là quá khứ.',
                'status.required'       => 'Vui lòng chọn trạng thái.',
                'trailer_url.required'  => 'Vui lòng nhập link trailer.',
                'trailer_url.url'       => 'Link trailer không đúng định dạng URL.',
                'poster.required'       => 'Vui lòng chọn ảnh poster.',
                'poster.image'          => 'File phải là hình ảnh.',
                'poster.mimes'          => 'Ảnh chỉ chấp nhận định dạng: jpeg, png, jpg, webp.',
                'genre_ids.required'    => 'Vui lòng chọn ít nhất 1 thể loại.',
                'genre_ids.min'         => 'Vui lòng chọn ít nhất 1 thể loại.',
            ]);

            $posterUrl = $request->file('poster')->store('posters', 'public');
            

            $movie = Movie::create([
                'title'        => $request->title,
                'slug'         => Str::slug($request->title),
                'description'  => $request->description,
                'duration'     => $request->duration,
                'release_date' => $request->release_date,
                'status'       => $request->status,
                'rating'       => $request->rating,
                'poster_url'   => $posterUrl,
                'trailer_url'  => $request->trailer_url
            ]);

            $movie->genres()->attach($request->genre_ids);

            return response()->json(['success' => true, 'data' => $movie->load('genres')], 201);
        }

        public function update(Request $request, $id): JsonResponse
        {
            $movie = Movie::findOrFail($id);

            $request->validate([
                'title'        => 'required|string|max:255|unique:movies,title,' . $id,
                'rating'       => 'required|string',
                'description'  => 'required|string',
                'duration'     => 'required|integer|min:1',
                'status'       => 'required|string',
                'trailer_url'  => 'required|url',
                'poster'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'genre_ids'    => 'required|array|min:1',
            ], [
                'title.unique'         => 'Tên phim này đã tồn tại.',
                'duration.min'         => 'Thời lượng phải lớn hơn 0.',
                'trailer_url.url'      => 'Link trailer không đúng định dạng URL.',
                'poster.image'         => 'File phải là hình ảnh.',
                'genre_ids.min'        => 'Vui lòng chọn ít nhất 1 thể loại.',
            ]);

            $data = $request->except(['poster', 'genre_ids', '_method']);

            if ($request->has('title') && $request->title !== $movie->title) {
                $data['slug'] = Str::slug($request->title);
            }

            if ($request->hasFile('poster')) {
                if ($movie->poster_url) {
                    Storage::disk('public')->delete($movie->poster_url);
                }
                $data['poster_url'] = $request->file('poster')->store('posters', 'public');
            }

            $movie->update($data);

            $movie->genres()->sync($request->genre_ids);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thành công!'
            ]);
        }

        public function destroy($id): JsonResponse
        {
            $movie = Movie::findOrFail($id);

            $posterPath = $movie->poster_url;

            if ($posterPath && !str_starts_with($posterPath, 'http')) {

                if (Storage::disk('public')->exists($posterPath)) {
                    Storage::disk('public')->delete($posterPath);
                }
            }

            $movie->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa phim và ảnh poster thành công!'
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
