<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Lấy danh sách thông báo
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Lấy 20 thông báo mới nhất
        $notifications = $user->notifications()->paginate(20);

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications
        ]);
    }

    // Đánh dấu 1 thông báo là đã đọc
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);
        
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['message' => 'Đã đánh dấu đọc']);
    }

    // Đánh dấu tất cả là đã đọc
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Đã đánh dấu đọc tất cả']);
    }
}
