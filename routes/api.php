<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\DanhMucDichVuController;
use App\Http\Controllers\Api\DonDatLichController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Middleware\EnsureGuestToken;
use App\Http\Middleware\ResolveChatIdentity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

// Public browse APIs
Route::get('/danh-muc-dich-vu', [DanhMucDichVuController::class, 'index']);
Route::get('/danh-muc-dich-vu/{id}', [DanhMucDichVuController::class, 'show']);
Route::get('/ho-so-tho', [\App\Http\Controllers\Api\HoSoThoController::class, 'index']);
Route::get('/ho-so-tho/{id}', [\App\Http\Controllers\Api\HoSoThoController::class, 'show']);

// Payment webhooks
Route::get('/payment/vnpay-ipn', [PaymentController::class, 'vnpayIpn']);
Route::post('/payment/momo-ipn', [PaymentController::class, 'momoIpn']);
Route::post('/payment/zalopay-ipn', [PaymentController::class, 'zalopayIpn']);
Route::get('/payment/vnpay-return', [PaymentController::class, 'vnpayReturn'])->name('payment.vnpay.return');
Route::get('/payment/momo-return', [PaymentController::class, 'momoReturn'])->name('payment.momo.return');
Route::get('/payment/zalopay-return', [PaymentController::class, 'zalopayReturn'])->name('payment.zalopay.return');

// Chatbot APIs (guest + optional bearer token identity)
Route::prefix('chat')
    ->middleware([ResolveChatIdentity::class, EnsureGuestToken::class])
    ->group(function () {
        Route::get('/history', [ChatbotController::class, 'history']);
        Route::post('/send', [ChatbotController::class, 'send']);
        Route::post('/sync-guest', [ChatbotController::class, 'syncGuest']);
        Route::post('/ai-response', [ChatbotController::class, 'aiResponse']);
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // User profile
    Route::post('/user/avatar', [\App\Http\Controllers\Api\UserController::class, 'uploadAvatar']);

    // Notifications
    Route::get('/notifications/unread', [NotificationController::class, 'getUnread']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Booking actions
    Route::post('/bookings/cancel/{id}', [DonDatLichController::class, 'cancelBooking']);

    // Payment actions
    Route::post('/payment/create', [PaymentController::class, 'createPaymentUrl']);

    // Service category CRUD
    Route::post('/danh-muc-dich-vu', [DanhMucDichVuController::class, 'store']);
    Route::put('/danh-muc-dich-vu/{id}', [DanhMucDichVuController::class, 'update']);
    Route::delete('/danh-muc-dich-vu/{id}', [DanhMucDichVuController::class, 'destroy']);

    // Worker profile APIs
    Route::put('/ho-so-tho', [\App\Http\Controllers\Api\HoSoThoController::class, 'update']);
    Route::get('/worker/stats', [\App\Http\Controllers\Api\HoSoThoController::class, 'stats']);

    // Booking APIs
    Route::post('/don-dat-lich', [\App\Http\Controllers\Api\DonDatLichController::class, 'store']);
    Route::get('/don-dat-lich/available', [\App\Http\Controllers\Api\DonDatLichController::class, 'availableJobs']);
    Route::get('/don-dat-lich/calendar', [\App\Http\Controllers\Api\DonDatLichController::class, 'getCalendarView']);
    Route::get('/don-dat-lich', [\App\Http\Controllers\Api\DonDatLichController::class, 'index']);
    Route::get('/don-dat-lich/{id}', [\App\Http\Controllers\Api\DonDatLichController::class, 'show']);
    Route::post('/don-dat-lich/{id}/claim', [\App\Http\Controllers\Api\DonDatLichController::class, 'claimJob']);
    Route::put('/don-dat-lich/{id}/status', [\App\Http\Controllers\Api\DonDatLichController::class, 'updateStatus']);
    Route::put('/don-dat-lich/{id}/update-costs', [\App\Http\Controllers\Api\DonDatLichController::class, 'updateCosts']);
    Route::post('/bookings/{id}/request-payment', [\App\Http\Controllers\Api\DonDatLichController::class, 'requestPayment']);
    Route::post('/bookings/{id}/confirm-cash-payment', [\App\Http\Controllers\Api\DonDatLichController::class, 'confirmCashPayment']);

    // Review APIs
    Route::post('/danh-gia', [\App\Http\Controllers\Api\DanhGiaController::class, 'store']);
    Route::put('/danh-gia/{id}', [\App\Http\Controllers\Api\DanhGiaController::class, 'update']);

    // Admin APIs
    Route::middleware([\App\Http\Middleware\AdminMiddleware::class])->prefix('admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\AdminController::class, 'getDashboardStats']);
        Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'getUsers']);
        Route::patch('/users/{id}/toggle-status', [\App\Http\Controllers\Api\AdminController::class, 'toggleUserStatus']);
        Route::get('/worker-profiles', [\App\Http\Controllers\Api\AdminController::class, 'getWorkerProfiles']);
        Route::patch('/worker-profiles/{userId}/approval', [\App\Http\Controllers\Api\AdminController::class, 'updateWorkerApproval']);
        Route::get('/bookings', [\App\Http\Controllers\Api\AdminController::class, 'getAllBookings']);
        Route::get('/services', [\App\Http\Controllers\Api\AdminController::class, 'getServices']);
        Route::post('/services', [\App\Http\Controllers\Api\AdminController::class, 'storeService']);
        Route::put('/services/{id}', [\App\Http\Controllers\Api\AdminController::class, 'updateService']);
        Route::delete('/services/{id}', [\App\Http\Controllers\Api\AdminController::class, 'destroyService']);
        Route::get('/assistant-soul', [\App\Http\Controllers\Api\AdminController::class, 'getAssistantSoulConfig']);
        Route::put('/assistant-soul', [\App\Http\Controllers\Api\AdminController::class, 'updateAssistantSoulConfig']);
        Route::delete('/assistant-soul', [\App\Http\Controllers\Api\AdminController::class, 'resetAssistantSoulConfig']);
    });
});

// Public review APIs
Route::get('/ho-so-tho/{id}/danh-gia', [\App\Http\Controllers\Api\DanhGiaController::class, 'indexByWorker']);
Route::get('/ho-so-tho/{id}/danh-gia/summary', [\App\Http\Controllers\Api\DanhGiaController::class, 'summary']);
