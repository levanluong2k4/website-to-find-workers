<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DanhMucDichVuController;
use App\Http\Controllers\Api\BaiDangController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

// Route công khai (khách xem danh sách và chi tiết thợ)
Route::get('/danh-muc-dich-vu', [DanhMucDichVuController::class, 'index']);
Route::get('/danh-muc-dich-vu/{id}', [DanhMucDichVuController::class, 'show']);
Route::get('/ho-so-tho', [\App\Http\Controllers\Api\HoSoThoController::class, 'index']);
Route::get('/ho-so-tho/{id}', [\App\Http\Controllers\Api\HoSoThoController::class, 'show']);

// Nhóm Route cho Bài Đăng (Public)
Route::get('/bai-dang', [BaiDangController::class, 'index']); // Thợ vào xem danh sách bài đăng
Route::get('/bai-dang/{id}', [BaiDangController::class, 'show']);

// Routes yêu cầu đăng nhập
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Thêm, Sửa, Xóa danh mục dịch vụ
    Route::post('/danh-muc-dich-vu', [DanhMucDichVuController::class, 'store']);
    Route::put('/danh-muc-dich-vu/{id}', [DanhMucDichVuController::class, 'update']);
    Route::delete('/danh-muc-dich-vu/{id}', [DanhMucDichVuController::class, 'destroy']);

    // Quản lý Hồ sơ thợ (chỉ cho thợ đã đăng nhập)
    Route::put('/ho-so-tho', [\App\Http\Controllers\Api\HoSoThoController::class, 'update']);

    // Quản lý Bài Đăng (Dành cho Khách hàng)
    Route::get('/user/bai-dang', [BaiDangController::class, 'myPosts']);
    Route::post('/bai-dang', [BaiDangController::class, 'store']);
    Route::put('/bai-dang/{id}', [BaiDangController::class, 'update']);
    Route::delete('/bai-dang/{id}', [BaiDangController::class, 'destroy']);

    // Quản lý Báo Giá (Quotes)
    Route::post('/bao-gia', [\App\Http\Controllers\Api\BaoGiaController::class, 'store']); // Thợ nộp báo giá
    Route::get('/user/bao-gia', [\App\Http\Controllers\Api\BaoGiaController::class, 'myQuotes']); // Thợ xem list báo giá của mình
    Route::get('/bai-dang/{id}/bao-gia', [\App\Http\Controllers\Api\BaoGiaController::class, 'indexByBaiDang']); // Khách xem báo giá của 1 bài đăng
    Route::post('/bao-gia/{id}/accept', [\App\Http\Controllers\Api\BaoGiaController::class, 'accept']); // Khách chọn 1 báo giá

    // Quản lý Đặt Lịch (Bookings)
    Route::get('/don-dat-lich', [\App\Http\Controllers\Api\DonDatLichController::class, 'index']);
    Route::get('/don-dat-lich/{id}', [\App\Http\Controllers\Api\DonDatLichController::class, 'show']);
    Route::put('/don-dat-lich/{id}/status', [\App\Http\Controllers\Api\DonDatLichController::class, 'updateStatus']);
});
