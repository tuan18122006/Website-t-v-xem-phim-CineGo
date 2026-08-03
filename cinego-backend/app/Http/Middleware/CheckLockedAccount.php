<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLockedAccount
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->status === 'locked') {
            // Xóa session nếu đang dùng web
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            
            // Trả về lỗi 401 để frontend tự động logout (Axios interceptor)
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên!'
            ], 401);
        }

        return $next($request);
    }
}
