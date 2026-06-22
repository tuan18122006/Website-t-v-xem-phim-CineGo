<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\ShowtimeController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SeatHoldController;
use App\Http\Controllers\Api\BookingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Hệ thống phân quyền & xác thực (Sanctum) cùng Gate 'admin-only' được giữ lại.
| Tất cả các route nghiệp vụ khác (Phim, Lịch chiếu, Ghế, Đặt vé) đã được dọn dẹp sạch sẽ 
| để thành viên trong nhóm tự lập trình các Controller và Route từ đầu.
|
*/

// 1. Routes Xác thực & Phân quyền (Public Routes)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes nghiệp vụ ghế & suất chiếu công khai
Route::get('/showtimes/{id}/seats', [ShowtimeController::class, 'getSeats']);




// 2. Protected Routes (Yêu cầu đăng nhập qua Sanctum để kiểm tra quyền)
Route::middleware(['auth:sanctum', 'can:admin-only'])->prefix('admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'userProfile']); // Trả về thông tin user + vai trò (customer/admin)

    // Quản lý tài khoản (chỉ Admin)
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::patch('/users/{id}/status', [UserController::class, 'toggleStatus']);
    Route::patch('/users/{id}/role', [UserController::class, 'updateRole']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);


    // Admin Routes (Yêu cầu vai trò quản trị viên - admin-only)
    Route::middleware('can:admin-only')->group(function () {
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

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/seat-holds', [SeatHoldController::class, 'hold']);
    Route::post('/seat-holds/release', [SeatHoldController::class, 'release']);
    Route::post('/bookings', [BookingController::class, 'store']);
});



// 3. Public Routes (Không cần đăng nhập)
Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/search', [MovieController::class, 'search']);
Route::get('/rooms', [RoomController::class, 'index']);
