<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    /**
     * Get all banners for Admin
     */
    public function index()
    {
        $banners = Banner::with('movie:id,title,poster_url')
            ->orderBy('order')
            ->latest()
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $banners
        ]);
    }

    /**
     * Get active banners for public client (Homepage)
     */
    public function publicFeatured()
    {
        $banners = Banner::with('movie:id,title,description,duration,rating,poster_url,release_date')
            ->where('is_active', true)
            ->orderBy('order')
            ->latest()
            ->get();
            
        // Also load genres
        $banners->each(function($banner) {
            if ($banner->movie) {
                $banner->movie->load('genres:id,name');
            }
        });

        return response()->json([
            'success' => true,
            'data' => $banners
        ]);
    }

    /**
     * Store a new banner (Admin) - uses movie poster_url automatically
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'movie_id' => 'required|exists:movies,id',
            'is_active' => 'boolean',
            'order' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Kiểm tra phim đã có banner chưa
        $existing = Banner::where('movie_id', $request->movie_id)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Phim này đã có banner rồi!'
            ], 422);
        }

        $movie = Movie::find($request->movie_id);

        $banner = Banner::create([
            'movie_id' => $request->movie_id,
            'image_url' => $movie->poster_url ?? '',
            'is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
            'order' => $request->order ?? 0
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo banner thành công!',
            'data' => $banner->load('movie:id,title,poster_url')
        ]);
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id)
    {
        $banner = Banner::findOrFail($id);
        $banner->is_active = !$banner->is_active;
        $banner->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công!'
        ]);
    }

    /**
     * Update banner (Admin)
     */
    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'movie_id' => 'required|exists:movies,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_active' => 'boolean',
            'order' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $banner->movie_id = $request->movie_id;

        if ($request->hasFile('image')) {
            // Xóa ảnh cũ
            if (Storage::disk('public')->exists($banner->image_url)) {
                Storage::disk('public')->delete($banner->image_url);
            }
            $banner->image_url = $request->file('image')->store('banners', 'public');
        }

        if ($request->has('is_active')) {
            $banner->is_active = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
        }

        if ($request->has('order')) {
            $banner->order = $request->order;
        }

        $banner->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật banner thành công!',
            'data' => $banner->load('movie:id,title,poster_url')
        ]);
    }

    /**
     * Delete banner
     */
    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);
        
        if (Storage::disk('public')->exists($banner->image_url)) {
            Storage::disk('public')->delete($banner->image_url);
        }
        
        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa banner thành công!'
        ]);
    }
}
