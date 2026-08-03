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
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\VoucherController;
use App\Http\Controllers\Api\ComboController;
use App\Http\Controllers\Api\ComboItemController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\BlogCategoryController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\PaymentController;

// Đăng ký / Đăng nhập
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Đăng nhập Google (OAuth)
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/search', [MovieController::class, 'search']);
Route::get('/movies/{id}', [MovieController::class, 'show']);
Route::get('/movies/{movieId}/reviews', [ReviewController::class, 'index']);
Route::get('/reviews/featured', [ReviewController::class, 'publicFeatured']);

// Blog public routes
Route::get('/blogs', [BlogController::class, 'index']); 
Route::get('/blogs/{id}', [BlogController::class, 'show']); 
Route::get('/blog-categories', [BlogCategoryController::class, 'index']); 

// Article (Top Phim) public routes
Route::get('/articles', [ArticleController::class, 'publicIndex']);
Route::get('/articles/{slug}', [ArticleController::class, 'publicShow']);

// Suất chiếu & Sơ đồ ghế công khai
Route::get('/showtimes/by-date', [ShowtimeController::class, 'getShowtimesByDate']);
Route::get('/movies/{id}/available-dates', [ShowtimeController::class, 'getAvailableDates']);
Route::get('/movies/{id}/showtimes', [ShowtimeController::class, 'getShowtimesByMovie']);
Route::get('/showtimes/{id}/seats', [ShowtimeController::class, 'getSeats']);
Route::get('/rooms', [RoomController::class, 'index']);

// Public Banner route
Route::get('/banners/public', [\App\Http\Controllers\Api\BannerController::class, 'publicFeatured']);

// Chatbot AI (Trợ lý CineGo) — công khai cho cả khách vãng lai
Route::post('/chatbot', [\App\Http\Controllers\Api\ChatbotController::class, 'chat']);

// Cấu hình công khai
Route::get('/settings/payment', [\App\Http\Controllers\Api\SettingController::class, 'getPaymentSettings']);

Route::middleware('auth:sanctum')->group(function () {

    // Đăng xuất & hồ sơ cho MỌI user đã đăng nhập (khách hàng, staff, admin)
    Route::get('/client/my-vouchers', [LoyaltyController::class, 'getMyVouchers']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', [AuthController::class, 'userProfile']);    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/change-password', [UserController::class, 'changePassword']);
    Route::post('/user/avatar', [UserController::class, 'uploadAvatar']);
    
    Route::post('/seat-holds', [SeatHoldController::class, 'hold']);
    Route::post('/seat-holds/release', [SeatHoldController::class, 'release']);

    Route::post('/bookings', [BookingController::class, 'store']);
    // Các route của Phi
    Route::get('/user/bookings', [BookingController::class, 'history']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/vouchers/verify', [VoucherController::class, 'verify']);
    Route::get('/user/available-combos', [LoyaltyController::class, 'getAvailableCombos']);
    Route::post('/vouchers/claim', [VoucherController::class, 'claimVoucher']);
    Route::post('/payments/create', [PaymentController::class, 'createPayment']);
    
    // Các route của User
        Route::post('/bookings/refund', [\App\Http\Controllers\Api\RefundController::class, 'requestRefund']);
    
    Route::post('/movies/{movieId}/reviews', [ReviewController::class, 'store']);
    Route::put('/movies/{movieId}/reviews/{reviewId}', [ReviewController::class, 'update']);
    Route::delete('/movies/{movieId}/reviews/{reviewId}', [ReviewController::class, 'destroy']);

    // Th\u00f4ng tin t\u00edch \u0111i\u1ec3m & th\u0103ng h\u1ea1ng cho kh\u00e1ch h\u00e0ng
    Route::get('/loyalty/progress', [UserController::class, 'getLoyaltyProgress']);

    // Cập nhật profile cá nhân
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::post('/profile/password', [UserController::class, 'changePassword']);
    Route::post('/profile/avatar', [UserController::class, 'uploadAvatar']);

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);

    // === CHƯƠNG TRÌNH THÀNH VIÊN & TÍCH ĐIỂM (LOYALTY) ===
    Route::prefix('loyalty')->group(function () {
        Route::get('/profile', [LoyaltyController::class, 'getProfileAndHistories']);
        Route::get('/vouchers', [LoyaltyController::class, 'getRedeemableVouchers']);
        Route::get('/combos', [LoyaltyController::class, 'getRedeemableCombos']);
        Route::post('/redeem-voucher/{voucher}', [LoyaltyController::class, 'redeemVoucher']);
        Route::post('/redeem-combo', [LoyaltyController::class, 'redeemCombo']);
    });
});

// ==================================================================// 3. ADMIN ROUTES - QUẢN TRỊ VIÊN (Yêu cầu quyền Admin-only)
// ==================================================================
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

    // Quản lý Banners
    Route::get('/banners', [\App\Http\Controllers\Api\BannerController::class, 'index']);
    Route::post('/banners', [\App\Http\Controllers\Api\BannerController::class, 'store']);
    Route::post('/banners/{id}', [\App\Http\Controllers\Api\BannerController::class, 'update']);
    Route::put('/banners/{id}', [\App\Http\Controllers\Api\BannerController::class, 'update']);
    Route::patch('/banners/{id}/toggle', [\App\Http\Controllers\Api\BannerController::class, 'toggleActive']);
    Route::delete('/banners/{id}', [\App\Http\Controllers\Api\BannerController::class, 'destroy']);

    // Đối soát ca
    Route::get('/shifts/pending-audits', [\App\Http\Controllers\Api\ShiftController::class, 'pendingAudits']);
    Route::post('/shifts/{id}/audit', [\App\Http\Controllers\Api\ShiftController::class, 'audit']);

    // Quản lý đơn hàng
    Route::get('/orders', [BookingController::class, 'index']);
    Route::get('/orders/{id}', [BookingLookupController::class, 'show']);
    Route::patch('/orders/{id}/status', [BookingController::class, 'updateStatus']);
    Route::post('/orders/{id}/refund', [\App\Http\Controllers\Api\RefundController::class, 'requestRefundById']);
    Route::get('/refunds/pending', [\App\Http\Controllers\Api\RefundController::class, 'pendingRefunds']);
    Route::post('/refunds/{id}/approve', [\App\Http\Controllers\Api\RefundController::class, 'approveRefund']);

    // Cấu hình thanh toán
    Route::post('/settings/payment', [\App\Http\Controllers\Api\SettingController::class, 'updatePaymentSettings']);

    // Quản lý tài khoản User
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::patch('/users/{id}/status', [UserController::class, 'toggleStatus']);
    Route::patch('/users/{id}/role', [UserController::class, 'updateRole']);
    Route::patch('/users/{id}/tier', [UserController::class, 'updateTier']);
    Route::post('/users/{id}/adjust-points', [UserController::class, 'adjustPoints']);
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
    
    // Cấu hình giá hệ thống
    Route::get('/pricing-rules', [\App\Http\Controllers\Api\PricingRuleController::class, 'index']);
    Route::put('/pricing-rules', [\App\Http\Controllers\Api\PricingRuleController::class, 'update']);

    // Route của rooms
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::get('/rooms/{id}', [RoomController::class, 'show']);
    Route::put('/rooms/{id}/update-seat-map', [RoomController::class, 'updateSeatMap']);
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);
    // Quản lý Combo Items (Chi tiết Combo)
    Route::apiResource('combos', ComboController::class);
    Route::get('combos/{combo}/items', [ComboItemController::class, 'getItems']);
    Route::post('/combo-items', [ComboItemController::class, 'store']);
    Route::put('/combo-items/{id}', [ComboItemController::class, 'update']);
    Route::delete('/combo-items/{id}', [ComboItemController::class, 'destroy']);

    // Quản lý Voucher
    Route::apiResource('vouchers', VoucherController::class);
    Route::get('movies/list', [App\Http\Controllers\Api\MovieController::class, 'listForSelection']);
    // Quản lý Blog
    Route::apiResource('blogs', BlogController::class);
    Route::apiResource('blog-categories', BlogCategoryController::class);

    // Quản lý Top Phim (Articles)
    Route::apiResource('articles', ArticleController::class);

    Route::prefix('loyalty')->group(function () {
        Route::get('/users', [LoyaltyController::class, 'adminGetUsers']);
        Route::get('/users/{id}/histories', [LoyaltyController::class, 'adminGetUserHistories']);
        Route::post('/users/{id}/adjust-points', [LoyaltyController::class, 'adminAdjustPoints']);
    });
});

// ==================================================================
// 4. STAFF ROUTES - NHÂN VIÊN HỖ TRỢ (staff hoặc admin)
// ==================================================================
Route::middleware(['auth:sanctum', 'can:staff-or-admin'])->prefix('staff')->group(function () {
    // POS (Bán vé tại quầy)
    Route::get('/customers/search', [\App\Http\Controllers\Api\POSController::class, 'searchCustomer']);
    Route::post('/bookings/pos', [\App\Http\Controllers\Api\POSController::class, 'storePOSBooking']);

    // Tra cứu đơn hàng / Hỗ trợ khách hàng
    Route::get('/bookings/lookup', [BookingLookupController::class, 'search']);
    Route::get('/bookings/{id}', [BookingLookupController::class, 'show']);
    
    // Đối soát ca trực (Dành cho nhân viên)
    Route::post('/shifts/start', [\App\Http\Controllers\Api\ShiftController::class, 'checkIn']);
    Route::post('/shifts/end', [\App\Http\Controllers\Api\ShiftController::class, 'checkOut']);
    Route::get('/shifts/active', [\App\Http\Controllers\Api\ShiftController::class, 'activeShift']);
});
Route::post('/vouchers/verify', [VoucherController::class, 'verify']);
Route::get('/payment/vnpay/return', [PaymentController::class, 'vnpayReturn']);
Route::get('/tickets/{bookingCode}', [TicketController::class, 'show']);
// Danh sách combo công khai cho client
Route::get('/combos/active', [ComboController::class, 'getActive']);
Route::get('/combos', [ComboController::class, 'index']);


