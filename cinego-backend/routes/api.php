<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\ShowtimeController;
use App\Http\Controllers\Api\RoomController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Hệ thống phân quyền & xác thực (Sanctum) cùng Gate 'admin-only' được giữ lại.
| Tất cả các route nghiệp vụ khác (Phim, Lịch chiếu, Ghế, Đặt vé) đã được dọn dẹp sạch sẽ 
| để thành viên trong nhóm tự lập trình các Controller và Route từ đầu.
|
*/

// 1. Routes Xác thực & Phân quyền (Public Routes)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ==========================================
// [THÀNH VIÊN NHÓM TỰ LẬP TRÌNH ROUTE PUBLIC DƯỚI ĐÂY]
// Ví dụ: Lấy danh sách phim, chi tiết phim, lịch chiếu...
// ==========================================


// 2. Protected Routes (Yêu cầu đăng nhập qua Sanctum để kiểm tra quyền)
Route::middleware(['auth:sanctum', 'can:admin-only'])->prefix('admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'userProfile']); // Trả về thông tin user + vai trò (customer/admin)

    // ==========================================
    // [THÀNH VIÊN NHÓM TỰ LẬP TRÌNH ROUTE PROTECTED CHO KHÁCH HÀNG DƯỚI ĐÂY]
    // Ví dụ: Giữ ghế, Đặt vé mới, Xem vé đã mua...
    // ==========================================

    // Admin Routes (Yêu cầu vai trò quản trị viên - admin-only)
    Route::middleware('can:admin-only')->group(function () {
        // ==========================================
        // [THÀNH VIÊN NHÓM TỰ LẬP TRÌNH ROUTE QUẢN TRỊ ADMIN DƯỚI ĐÂY]
        // Ví dụ: Thêm phim, sửa phim, cấu hình lịch chiếu...
        // ==========================================
        Route::get('/genres', [GenreController::class, 'index']);
        Route::post('/genres', [GenreController::class, 'store']);
        Route::put('/genres/{genre}', [GenreController::class, 'update']);
        Route::delete('/genres/{genre}', [GenreController::class, 'destroy']);

        // Route của phim
        Route::get('/movies', [MovieController::class, 'index']);
        Route::post('/movies', [MovieController::class, 'store']);
        Route::put('/movies/{id}', [MovieController::class, 'update']);
        Route::delete('/movies/{id}', [MovieController::class, 'destroy']);

        // Route của suất chiếu
        Route::get('/showtimes', [ShowtimeController::class, 'index']);
        Route::post('/showtimes', [ShowtimeController::class, 'store']);
        Route::delete('/showtimes/{id}', [ShowtimeController::class, 'destroy']);
    });
});



// 3. Public Routes (Không cần đăng nhập)
Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/search', [MovieController::class, 'search']);
Route::get('/rooms', [RoomController::class, 'index']);
