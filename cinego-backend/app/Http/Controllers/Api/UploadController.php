<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:5120',
        ], [
            'file.required' => 'Vui lòng chọn file ảnh.',
            'file.image' => 'File phải là ảnh.',
            'file.max' => 'Kích thước ảnh tối đa 5MB.',
        ]);

        $file = $request->file('file');
        $path = $file->store('blog-images', 'public');

        $fullUrl = $request->getScheme() . '://' . $request->getHost() . ':' . $request->getPort() . '/storage/' . $path;

        return response()->json([
            'success' => true,
            'url' => $fullUrl,
        ]);
    }
}
