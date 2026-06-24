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


// Đăng ký / Đăng nhập
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/search', [MovieController::class, 'search']);
Route::get('/movies/{id}', [MovieController::class, 'show']);

// Suất chiếu & Sơ đồ ghế công khai
Route::get('/movies/{id}/showtimes', [ShowtimeController::class, 'getShowtimesByMovie']);
Route::get('/showtimes/{id}/seats', [ShowtimeController::class, 'getSeats']);
Route::get('/rooms', [RoomController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/seat-holds', [SeatHoldController::class, 'hold']);
    Route::post('/seat-holds/release', [SeatHoldController::class, 'release']);

    Route::post('/bookings', [BookingController::class, 'store']);
});

// =========================================================================
// 3. ADMIN ROUTES - QUẢN TRỊ VIÊN (Yêu cầu quyền Admin-only)
// =========================================================================
Route::middleware(['auth:sanctum', 'can:admin-only'])->prefix('admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'userProfile']);

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

    Route::get('/showtimes', [ShowtimeController::class, 'index']);
    Route::post('/showtimes', [ShowtimeController::class, 'store']);
    Route::delete('/showtimes/{id}', [ShowtimeController::class, 'destroy']);
});