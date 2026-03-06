<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DanhMucDichVuController;
use App\Http\Controllers\Api\BaiDangController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\DonDatLichController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

// Route công khai (khách xem danh sách và chi tiết thợ)
Route::get('/danh-muc-dich-vu', [DanhMucDichVuController::class, 'index']);
Route::get('/danh-muc-dich-vu/{id}', [DanhMucDichVuController::class, 'show']);
Route::get('/ho-so-tho', [\App\Http\Controllers\Api\HoSoThoController::class, 'index']);
Route::get('/ho-so-tho/{id}', [\App\Http\Controllers\Api\HoSoThoController::class, 'show']);

// Webhook Cổng Thanh Toán (Không có auth token)
Route::get('/payment/vnpay-ipn', [PaymentController::class, 'vnpayIpn']);
Route::post('/payment/momo-ipn', [PaymentController::class, 'momoIpn']);
Route::post('/payment/zalopay-ipn', [PaymentController::class, 'zalopayIpn']);

// Routes yêu cầu đăng nhập
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Quản lý ảnh đại diện
    Route::post('/user/avatar', [\App\Http\Controllers\Api\UserController::class, 'uploadAvatar']);

    // Quản lý Thông báo
    Route::get('/notifications/unread', [NotificationController::class, 'getUnread']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Booking actions
    Route::post('/bookings/cancel/{id}', [DonDatLichController::class, 'cancelBooking']);

    // Luồng Thanh Toán (Khách hàng)
    Route::post('/payment/create', [PaymentController::class, 'createPaymentUrl']);
    Route::get('/payment/vnpay-return', [PaymentController::class, 'vnpayReturn'])->name('payment.vnpay.return');
    Route::get('/payment/momo-return', [PaymentController::class, 'momoReturn'])->name('payment.momo.return');
    Route::get('/payment/zalopay-return', [PaymentController::class, 'zalopayReturn'])->name('payment.zalopay.return');

    // Thêm, Sửa, Xóa danh mục dịch vụ
    Route::post('/danh-muc-dich-vu', [DanhMucDichVuController::class, 'store']);
    Route::put('/danh-muc-dich-vu/{id}', [DanhMucDichVuController::class, 'update']);
    Route::delete('/danh-muc-dich-vu/{id}', [DanhMucDichVuController::class, 'destroy']);

    // Quản lý Hồ sơ thợ (chỉ cho thợ đã đăng nhập)
    Route::put('/ho-so-tho', [\App\Http\Controllers\Api\HoSoThoController::class, 'update']);
    Route::get('/worker/stats', [\App\Http\Controllers\Api\HoSoThoController::class, 'stats']);

    // Quản lý Đặt Lịch (Bookings)
    Route::post('/don-dat-lich', [\App\Http\Controllers\Api\DonDatLichController::class, 'store']); // Đặt lịch mới
    Route::get('/don-dat-lich/available', [\App\Http\Controllers\Api\DonDatLichController::class, 'availableJobs']); // Lấy đơn chờ thợ nhận
    Route::get('/don-dat-lich/calendar', [App\Http\Controllers\Api\DonDatLichController::class, 'getCalendarView']);
    Route::get('/don-dat-lich', [\App\Http\Controllers\Api\DonDatLichController::class, 'index']);
    Route::get('/don-dat-lich/{id}', [\App\Http\Controllers\Api\DonDatLichController::class, 'show']);
    Route::post('/don-dat-lich/{id}/claim', [\App\Http\Controllers\Api\DonDatLichController::class, 'claimJob']); // Thợ nhận việc
    Route::put('/don-dat-lich/{id}/status', [\App\Http\Controllers\Api\DonDatLichController::class, 'updateStatus']);
    Route::put('/don-dat-lich/{id}/update-costs', [\App\Http\Controllers\Api\DonDatLichController::class, 'updateCosts']);

    // Quản lý Đánh Giá (Reviews)
    Route::post('/danh-gia', [\App\Http\Controllers\Api\DanhGiaController::class, 'store']); // Khách hàng đánh giá
    Route::put('/danh-gia/{id}', [\App\Http\Controllers\Api\DanhGiaController::class, 'update']); // Khách hàng sửa đánh giá

    // Admin APIs
    Route::middleware([\App\Http\Middleware\AdminMiddleware::class])->prefix('admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\AdminController::class, 'getDashboardStats']);
        Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'getUsers']);
        Route::patch('/users/{id}/toggle-status', [\App\Http\Controllers\Api\AdminController::class, 'toggleUserStatus']);
        Route::get('/bookings', [\App\Http\Controllers\Api\AdminController::class, 'getAllBookings']);
    });
});

// Route công khai cho Đánh Giá (Ai cũng có thể coi)
Route::get('/ho-so-tho/{id}/danh-gia', [\App\Http\Controllers\Api\DanhGiaController::class, 'indexByWorker']);
Route::get('/ho-so-tho/{id}/danh-gia/summary', [\App\Http\Controllers\Api\DanhGiaController::class, 'summary']);
