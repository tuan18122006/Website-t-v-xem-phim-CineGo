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
use App\Http\Controllers\Api\ComboController;
use App\Http\Controllers\Api\PricingRuleController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\VoucherController;
use App\Http\Controllers\Api\ComboItemController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\LoyaltyController;

// Đăng ký / Đăng nhập
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/search', [MovieController::class, 'search']);
Route::get('/movies/{id}', [MovieController::class, 'show']);
Route::get('/movies/{movieId}/reviews', [ReviewController::class, 'index']);
Route::get('/reviews/featured', [ReviewController::class, 'publicFeatured']);

// Suất chiếu & Sơ đồ ghế công khai
Route::get('/showtimes/by-date', [ShowtimeController::class, 'getShowtimesByDate']);
Route::get('/movies/{id}/available-dates', [ShowtimeController::class, 'getAvailableDates']);
Route::get('/movies/{id}/showtimes', [ShowtimeController::class, 'getShowtimesByMovie']);
Route::get('/showtimes/{id}/seats', [ShowtimeController::class, 'getSeats']);
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{id}/seats', [RoomController::class, 'getSeats']);

// Các Combo Bắp nước
Route::get('/combos/active', [App\Http\Controllers\Api\ComboController::class, 'getActive']);
Route::get('/combos/{combo}/items', [ComboItemController::class, 'getItems']);

// Thanh toán VNPay Return (Thường VNPay sẽ gọi GET/POST về đây)
Route::get('/payment/vnpay/return', [PaymentController::class, 'vnpayReturn']);

// Các API cần đăng nhập
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/client/my-vouchers', [LoyaltyController::class, 'getMyVouchers']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', [AuthController::class, 'userProfile']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/seat-holds', [SeatHoldController::class, 'hold']);
    Route::post('/seat-holds/release', [SeatHoldController::class, 'release']);

    Route::prefix('admin')->group(function () {
        Route::get('/user', [UserController::class, 'profile']);

        Route::put('/users/{id}', [UserController::class, 'updateProfile']);
    });

    Route::post('/user/change-password', [UserController::class, 'changePassword']);

    Route::post('/user/avatar', [UserController::class, 'uploadAvatar']);

    Route::get('/bookings', [BookingController::class, 'userHistory']);


    // Cập nhật tài khoản, mật khẩu, avatar
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/change-password', [UserController::class, 'changePassword']);
    Route::post('/user/avatar', [UserController::class, 'uploadAvatar']);

    Route::post('/seat-holds', [SeatHoldController::class, 'hold']);
    Route::post('/seat-holds/release', [SeatHoldController::class, 'release']);

    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/user/bookings', [BookingController::class, 'index']);

    // Xác thực mã giảm giá
    Route::post('/vouchers/verify', [VoucherController::class, 'verify']);
    Route::get('/user/available-combos', [LoyaltyController::class, 'getAvailableCombos']);
    Route::post('/vouchers/claim', [VoucherController::class, 'claimVoucher']);

    // Tạo link thanh toán VNPay
    Route::post('/payments/create', [PaymentController::class, 'createPayment']);
    Route::get('/bookings/history', [BookingController::class, 'history']);
    Route::post('/movies/{movieId}/reviews', [ReviewController::class, 'store']);
    Route::put('/movies/{movieId}/reviews/{reviewId}', [ReviewController::class, 'update']);
    Route::delete('/movies/{movieId}/reviews/{reviewId}', [ReviewController::class, 'destroy']);

    // === CHƯƠNG TRÌNH THÀNH VIÊN & TÍCH ĐIỂM (LOYALTY) ===
    Route::prefix('loyalty')->group(function () {
        Route::get('/profile', [LoyaltyController::class, 'getProfileAndHistories']);
        Route::get('/vouchers', [LoyaltyController::class, 'getRedeemableVouchers']);
        Route::get('/combos', [LoyaltyController::class, 'getRedeemableCombos']);
        Route::post('/redeem-voucher/{voucher}', [LoyaltyController::class, 'redeemVoucher']);
        Route::post('/redeem-combo', [LoyaltyController::class, 'redeemCombo']);
    });
});

// ===


// ===
// 3. ADMIN ROUTES - QUẢN TRỊ VIÊN (Yêu cầu quyền Admin-only)
// ===
Route::middleware(['auth:sanctum', 'can:admin-only'])->prefix('admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'userProfile']);

    // Dashboard thống kê
    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
    Route::get('/dashboard/revenue', [DashboardController::class, 'revenue']);

    // Kiểm duyệt đánh giá (Review moderation)
    Route::get('/reviews', [ReviewController::class, 'adminIndex']);
    Route::patch('/reviews/{id}/hide', [ReviewController::class, 'toggleHide']);
    Route::patch('/reviews/{id}/feature', [ReviewController::class, 'toggleFeature']);
    Route::post('/reviews/{id}/reply', [ReviewController::class, 'reply']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'adminDestroy']);

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

    // Cấu hình giá vé
    Route::get('/pricing-rules', [PricingRuleController::class, 'index']);
    Route::put('/pricing-rules', [PricingRuleController::class, 'update']);

    // Route của suất chiếu
    Route::get('/showtimes/suggest-price', [ShowtimeController::class, 'suggestPrice']);
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
    //
    Route::apiResource('combos', ComboController::class);
    //
    Route::get('combos/{combo}/items', [ComboItemController::class, 'getItems']);

    Route::post('combo-items', [ComboItemController::class, 'store']);

    Route::put('combo-items/{id}', [ComboItemController::class, 'update']);

    Route::delete('combo-items/{id}', [ComboItemController::class, 'destroy']);

    // Route quản lý Combos
    Route::get('/combos', [App\Http\Controllers\Api\ComboController::class, 'index']);
    Route::post('/combos', [App\Http\Controllers\Api\ComboController::class, 'store']);
    Route::put('/combos/{id}', [App\Http\Controllers\Api\ComboController::class, 'update']);
    Route::delete('/combos/{id}', [App\Http\Controllers\Api\ComboController::class, 'destroy']);

    // Quản lý Combo Items (Chi tiết Combo)
    Route::post('/combo-items', [ComboItemController::class, 'store']);
    Route::put('/combo-items/{id}', [ComboItemController::class, 'update']);
    Route::delete('/combo-items/{id}', [ComboItemController::class, 'destroy']);

    // Quản lý Voucher
    Route::apiResource('vouchers', VoucherController::class);
    Route::get('movies/list', [App\Http\Controllers\Api\MovieController::class, 'listForSelection']);
    //
    Route::prefix('loyalty')->group(function () {
        Route::get('/users', [LoyaltyController::class, 'adminGetUsers']);
        Route::get('/users/{id}/histories', [LoyaltyController::class, 'adminGetUserHistories']);
        Route::post('/users/{id}/adjust-points', [LoyaltyController::class, 'adminAdjustPoints']);
    });
});

// ===
// 4. STAFF ROUTES - NHÂN VIÊN HỖ TRỢ (staff hoặc admin)
// ===
Route::middleware(['auth:sanctum', 'can:staff-or-admin'])->prefix('staff')->group(function () {
    // Tra cứu đơn hàng / Hỗ trợ khách hàng
    Route::get('/bookings/lookup', [BookingLookupController::class, 'search']);
    Route::post('/bookings/verify', [BookingLookupController::class, 'verify']);
    Route::get('/bookings/{id}', [BookingLookupController::class, 'show']);
});
Route::post('/vouchers/verify', [VoucherController::class, 'verify']);
Route::get('/payment/vnpay/return', [PaymentController::class, 'vnpayReturn']);
Route::get('/tickets/{bookingCode}', [TicketController::class, 'show']);
// Danh sách combo công khai cho client
Route::get('/combos', [ComboController::class, 'index']);
