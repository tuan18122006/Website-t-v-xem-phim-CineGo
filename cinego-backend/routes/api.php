<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\ShowtimeController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SeatHoldController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\BookingLookupController;


// Đăng ký / Đăng nhập
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/search', [MovieController::class, 'search']);
Route::get('/movies/{id}', [MovieController::class, 'show']);
Route::get('/movies/{movieId}/reviews', [ReviewController::class, 'index']);

// Suất chiếu & Sơ đồ ghế công khai
Route::get('/showtimes/by-date', [ShowtimeController::class, 'getShowtimesByDate']);
Route::get('/movies/{id}/available-dates', [ShowtimeController::class, 'getAvailableDates']);
Route::get('/movies/{id}/showtimes', [ShowtimeController::class, 'getShowtimesByMovie']);
Route::get('/showtimes/{id}/seats', [ShowtimeController::class, 'getSeats']);
Route::get('/rooms', [RoomController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {

    // Đăng xuất & hồ sơ cho MỌI user đã đăng nhập (khách hàng, staff, admin)
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'userProfile']);

    Route::post('/seat-holds', [SeatHoldController::class, 'hold']);
    Route::post('/seat-holds/release', [SeatHoldController::class, 'release']);

    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/history', [BookingController::class, 'history']);
    Route::post('/movies/{movieId}/reviews', [ReviewController::class, 'store']);
    Route::put('/movies/{movieId}/reviews/{reviewId}', [ReviewController::class, 'update']);
    Route::delete('/movies/{movieId}/reviews/{reviewId}', [ReviewController::class, 'destroy']);
});

// =========================================================================
// 3. ADMIN ROUTES - QUẢN TRỊ VIÊN (Yêu cầu quyền Admin-only)
// =========================================================================
Route::middleware(['auth:sanctum', 'can:admin-only'])->prefix('admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'userProfile']);

    // Dashboard thống kê
    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
    Route::get('/dashboard/revenue', [DashboardController::class, 'revenue']);

    // Quản lý đơn hàng
    Route::get('/orders', [BookingController::class, 'index']);
    Route::get('/orders/{id}', [BookingLookupController::class, 'show']);

    // Quản lý tài khoản User
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::patch('/users/{id}/status', [UserController::class, 'toggleStatus']);
    Route::patch('/users/{id}/role', [UserController::class, 'updateRole']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Quản lý nghiệp vụ Rạp phim
    Route::get('/genres', [GenreController::class, 'index']);
    Route::post('/genres', [GenreController::class, 'store']);
    Route::put('/genres/{genre}', [GenreController::class, 'update']);
    Route::delete('/genres/{genre}', [GenreController::class, 'destroy']);

    Route::get('/movies', [MovieController::class, 'index']);
    Route::post('/movies', [MovieController::class, 'store']);
    Route::put('/movies/{id}', [MovieController::class, 'update']);
    Route::delete('/movies/{id}', [MovieController::class, 'destroy']);

    // Route của suất chiếu
    Route::get('/showtimes', [ShowtimeController::class, 'index']);
    Route::post('/showtimes', [ShowtimeController::class, 'store']);
    Route::put('/showtimes/{id}', [ShowtimeController::class, 'update']);
    Route::delete('/showtimes/{id}', [ShowtimeController::class, 'destroy']);

    // Route của rooms
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::get('/rooms/{id}', [RoomController::class, 'show']);
    Route::put('/rooms/{id}/update-seat-map', [RoomController::class, 'updateSeatMap']);
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);
});

// =========================================================================
// 4. STAFF ROUTES - NHÂN VIÊN HỖ TRỢ (staff hoặc admin)
// =========================================================================
Route::middleware(['auth:sanctum', 'can:staff-or-admin'])->prefix('staff')->group(function () {
    // Tra cứu đơn hàng / Hỗ trợ khách hàng
    Route::get('/bookings/lookup', [BookingLookupController::class, 'search']);
    Route::get('/bookings/{id}', [BookingLookupController::class, 'show']);
});

