<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ActorController extends Controller
{
    public function index()
    {
        try {
            $actors = Actor::withCount('movies')->orderBy('id', 'desc')->get();
            return response()->json([
                'success' => true,
                'status' => 'success',
                'data' => $actors
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
            $actor = Actor::with('movies')->findOrFail($id);
            return response()->json([
                'success' => true,
                'status' => 'success',
                'data' => $actor
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Không tìm thấy diễn viên!'
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:255|unique:actors,name',
            'birth_date' => 'required|date|before_or_equal:today',
            'nationality'=> 'required|string|max:100',
            'avatar'     => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'name.required'     => 'Vui lòng nhập tên diễn viên.',
            'name.unique'       => 'Tên diễn viên này đã tồn tại.',
            'name.max'          => 'Tên diễn viên không được vượt quá 255 ký tự.',
            'birth_date.required' => 'Vui lòng chọn ngày sinh.',
            'birth_date.date'   => 'Ngày sinh không đúng định dạng.',
            'birth_date.before_or_equal' => 'Ngày sinh không được ở trong tương lai.',
            'nationality.required' => 'Vui lòng nhập quốc tịch.',
            'avatar.required'   => 'Vui lòng chọn ảnh đại diện.',
            'avatar.image'      => 'File phải là hình ảnh.',
            'avatar.mimes'      => 'Ảnh chỉ chấp nhận định dạng: jpeg, png, jpg, webp.',
        ]);

        $avatarUrl = null;
        if ($request->hasFile('avatar')) {
            $avatarUrl = $request->file('avatar')->store('actors', 'public');
        }

        $actor = Actor::create([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'avatar_url'  => $avatarUrl,
            'birth_date'  => $request->birth_date,
            'nationality' => $request->nationality,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm diễn viên thành công',
            'data' => $actor
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $actor = Actor::find($id);
        if (!$actor) {
            return response()->json(['message' => 'Không tìm thấy diễn viên'], 404);
        }

        $request->validate([
            'name'       => 'required|string|max:255|unique:actors,name,' . $id,
            'birth_date' => 'required|date|before_or_equal:today',
            'nationality'=> 'required|string|max:100',
            'avatar'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'name.required' => 'Vui lòng nhập tên diễn viên.',
            'name.unique'   => 'Tên diễn viên này đã tồn tại.',
            'birth_date.required' => 'Vui lòng chọn ngày sinh.',
            'birth_date.date'   => 'Ngày sinh không đúng định dạng.',
            'birth_date.before_or_equal' => 'Ngày sinh không được ở trong tương lai.',
            'nationality.required' => 'Vui lòng nhập quốc tịch.',
            'avatar.image'  => 'File phải là hình ảnh.',
            'avatar.mimes'  => 'Ảnh chỉ chấp nhận định dạng: jpeg, png, jpg, webp.',
        ]);

        $data = [
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'birth_date'  => $request->birth_date,
            'nationality' => $request->nationality,
        ];

        if ($request->hasFile('avatar')) {
            if ($actor->avatar_url && !str_starts_with($actor->avatar_url, 'http')) {
                if (Storage::disk('public')->exists($actor->avatar_url)) {
                    Storage::disk('public')->delete($actor->avatar_url);
                }
            }
            $data['avatar_url'] = $request->file('avatar')->store('actors', 'public');
        }

        $actor->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật diễn viên thành công',
            'data' => $actor
        ], 200);
    }

    public function destroy($id): JsonResponse
    {
        $actor = Actor::find($id);
        if (!$actor) {
            return response()->json(['message' => 'Không tìm thấy diễn viên'], 404);
        }

        if ($actor->avatar_url && !str_starts_with($actor->avatar_url, 'http')) {
            if (Storage::disk('public')->exists($actor->avatar_url)) {
                Storage::disk('public')->delete($actor->avatar_url);
            }
        }

        $actor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa diễn viên thành công'
        ], 200);
    }
}