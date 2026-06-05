<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class MovieController extends Controller
{
    public function index()
    {
        // Eager loading nạp kèm thể loại để hiển thị ra table Vue 3
        $movies = Movie::with('genres')->orderBy('id', 'desc')->get();
        return response()->json(['success' => true, 'data' => $movies], 200);
    }

  public function store(Request $request)
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

    return response()->json(['success' => true, 'data' => $movie->load('genres')], 201);
}

public function update(Request $request, $id)
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
        $path = $request->file('poster')->store('posters', 'public');
        $data['poster_url'] = asset('storage/' . $path);
    }

    $movie->update($data);
    
    if ($request->has('genre_ids')) {
        $genreIds = is_array($request->genre_ids) ? $request->genre_ids : json_decode($request->genre_ids, true);
        $movie->genres()->sync($genreIds);
    }

    return response()->json(['success' => true, 'message' => 'Cập nhật thành công!']);
}
   public function show($id)
    {
        $movie = Movie::with('genres')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $movie], 200);
    }

     
 
    public function destroy($id)
    {
        $movie = Movie::findOrFail($id);
        if ($movie->poster_url) {
            $oldPath = str_replace(asset('storage/'), '', $movie->poster_url);
            Storage::disk('public')->delete($oldPath);
        }
        $movie->delete(); // Do có ON DELETE CASCADE nên movie_genre tự động bị xóa theo

        return response()->json(['success' => true, 'message' => 'Xóa phim thành công!'], 200);
    }
}