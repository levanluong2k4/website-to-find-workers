<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\DanhMucDichVuController;
use App\Http\Controllers\Api\DonDatLichController;
use App\Http\Controllers\Api\HuongXuLyController;
use App\Http\Controllers\Api\LinhKienController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PhoneVerificationController;
use App\Http\Controllers\Api\TravelFeeConfigController;
use App\Http\Middleware\EnsureGuestToken;
use App\Http\Middleware\EnsurePhoneVerified;
use App\Http\Middleware\ResolveChatIdentity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Public browse APIs
Route::get('/danh-muc-dich-vu', [DanhMucDichVuController::class, 'index']);
Route::get('/danh-muc-dich-vu/{id}', [DanhMucDichVuController::class, 'show']);
Route::get('/linh-kien', [LinhKienController::class, 'index']);
Route::get('/linh-kien/{id}', [LinhKienController::class, 'show'])->whereNumber('id');
Route::get('/huong-xu-ly', [HuongXuLyController::class, 'index']);
Route::get('/travel-fee-config', [TravelFeeConfigController::class, 'public']);
Route::get('/ho-so-tho', [\App\Http\Controllers\Api\HoSoThoController::class, 'index']);
Route::get('/ho-so-tho/{id}', [\App\Http\Controllers\Api\HoSoThoController::class, 'show']);
Route::get('/ho-so-tho/{id}/busy-slots', [\App\Http\Controllers\Api\HoSoThoController::class, 'busySlots']);

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
        Route::get('/history', [ChatbotController::class, 'history'])->middleware('throttle:chat-history');
        Route::post('/send', [ChatbotController::class, 'send'])->middleware('throttle:chat-send');
        Route::post('/sync-guest', [ChatbotController::class, 'syncGuest'])->middleware('throttle:chat-sync');
        Route::post('/ai-response', [ChatbotController::class, 'aiResponse'])->middleware('throttle:chat-admin-preview');
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/phone-verification/request', [PhoneVerificationController::class, 'requestCode']);
    Route::post('/phone-verification/verify', [PhoneVerificationController::class, 'verifyCode']);

    Route::middleware([EnsurePhoneVerified::class])->group(function () {
        Route::put('/user', [\App\Http\Controllers\Api\UserController::class, 'updateProfile']);

        // User profile
        Route::post('/user/avatar', [\App\Http\Controllers\Api\UserController::class, 'uploadAvatar']);
        Route::put('/user/address', [\App\Http\Controllers\Api\UserController::class, 'updateAddress']);
        Route::put('/user/password', [\App\Http\Controllers\Api\UserController::class, 'changePassword']);

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
        Route::put('/don-dat-lich/{id}/reschedule', [\App\Http\Controllers\Api\DonDatLichController::class, 'reschedule']);
        Route::post('/don-dat-lich/{id}/claim', [\App\Http\Controllers\Api\DonDatLichController::class, 'claimJob']);
        Route::post('/don-dat-lich/{id}/report-customer-unreachable', [\App\Http\Controllers\Api\DonDatLichController::class, 'reportCustomerUnreachable']);
        Route::put('/don-dat-lich/{id}/status', [\App\Http\Controllers\Api\DonDatLichController::class, 'updateStatus']);
        Route::put('/don-dat-lich/{id}/update-costs', [\App\Http\Controllers\Api\DonDatLichController::class, 'updateCosts']);
        Route::post('/don-dat-lich/{id}/complaint', [\App\Http\Controllers\Api\DonDatLichController::class, 'submitComplaint']);
        Route::post('/don-dat-lich/{id}/parts/{partIndex}/confirm-warranty', [\App\Http\Controllers\Api\DonDatLichController::class, 'confirmPartWarranty']);
        Route::put('/bookings/{id}/payment-method', [\App\Http\Controllers\Api\DonDatLichController::class, 'updatePaymentMethod']);
        Route::post('/bookings/{id}/request-payment', [\App\Http\Controllers\Api\DonDatLichController::class, 'requestPayment']);
        Route::post('/bookings/{id}/confirm-cash-payment', [\App\Http\Controllers\Api\DonDatLichController::class, 'confirmCashPayment']);

        // Review APIs
        Route::post('/danh-gia', [\App\Http\Controllers\Api\DanhGiaController::class, 'store']);
        Route::put('/danh-gia/{id}', [\App\Http\Controllers\Api\DanhGiaController::class, 'update']);

        // Admin APIs
        Route::middleware([\App\Http\Middleware\AdminMiddleware::class])->prefix('admin')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\AdminController::class, 'getDashboardStats']);
            Route::get('/customers', [\App\Http\Controllers\Api\AdminController::class, 'getCustomers']);
            Route::get('/customers/{id}', [\App\Http\Controllers\Api\AdminController::class, 'getCustomerDetail']);
            Route::get('/customers/{id}/bookings', [\App\Http\Controllers\Api\AdminController::class, 'getCustomerBookings']);
            Route::get('/dispatch', [\App\Http\Controllers\Api\AdminDispatchController::class, 'index']);
            Route::get('/dispatch/{bookingId}', [\App\Http\Controllers\Api\AdminDispatchController::class, 'show']);
            Route::post('/dispatch/{bookingId}/assign', [\App\Http\Controllers\Api\AdminDispatchController::class, 'assign']);

            Route::get('/customer-feedback', [\App\Http\Controllers\Api\AdminController::class, 'getCustomerFeedback']);
            Route::post('/customer-feedback/{caseKey}/claim', [\App\Http\Controllers\Api\AdminController::class, 'claimCustomerFeedbackCase']);
            Route::post('/customer-feedback/{caseKey}/resolve', [\App\Http\Controllers\Api\AdminController::class, 'resolveCustomerFeedbackCase']);
            Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'getUsers']);
            Route::patch('/users/{id}/toggle-status', [\App\Http\Controllers\Api\AdminController::class, 'toggleUserStatus']);
            Route::get('/worker-profiles', [\App\Http\Controllers\Api\AdminController::class, 'getWorkerProfiles']);
            Route::get('/worker-schedules/overview', [\App\Http\Controllers\Api\AdminController::class, 'getWorkerSchedulesOverview']);
            Route::post('/workers', [\App\Http\Controllers\Api\AdminController::class, 'storeWorker']);
            Route::get('/workers/{userId}', [\App\Http\Controllers\Api\AdminController::class, 'getWorkerDetail']);
            Route::put('/workers/{userId}', [\App\Http\Controllers\Api\AdminController::class, 'updateWorker']);
            Route::delete('/workers/{userId}', [\App\Http\Controllers\Api\AdminController::class, 'destroyWorker']);
            Route::patch('/worker-profiles/{userId}/approval', [\App\Http\Controllers\Api\AdminController::class, 'updateWorkerApproval']);
            Route::get('/bookings', [\App\Http\Controllers\Api\AdminController::class, 'getAllBookings']);
            Route::get('/bookings/export', [\App\Http\Controllers\Api\AdminController::class, 'exportBookings']);
            Route::get('/bookings/{id}', [\App\Http\Controllers\Api\AdminController::class, 'getBookingDetail'])->whereNumber('id');
            Route::post('/bookings/{id}/assign-worker', [\App\Http\Controllers\Api\AdminController::class, 'assignBookingWorker'])->whereNumber('id');
            Route::put('/bookings/{id}/financials', [\App\Http\Controllers\Api\AdminController::class, 'updateBookingFinancials'])->whereNumber('id');
            Route::get('/services', [\App\Http\Controllers\Api\AdminController::class, 'getServices']);
            Route::post('/services', [\App\Http\Controllers\Api\AdminController::class, 'storeService']);
            Route::put('/services/{id}', [\App\Http\Controllers\Api\AdminController::class, 'updateService']);
            Route::delete('/services/{id}', [\App\Http\Controllers\Api\AdminController::class, 'destroyService']);
            Route::get('/linh-kien', [\App\Http\Controllers\Api\AdminController::class, 'getParts']);
            Route::post('/linh-kien', [\App\Http\Controllers\Api\AdminController::class, 'storePart']);
            Route::put('/linh-kien/{id}', [\App\Http\Controllers\Api\AdminController::class, 'updatePart']);
            Route::delete('/linh-kien/{id}', [\App\Http\Controllers\Api\AdminController::class, 'destroyPart']);
            Route::get('/trieu-chung', [\App\Http\Controllers\Api\AdminController::class, 'getSymptoms']);
            Route::post('/trieu-chung', [\App\Http\Controllers\Api\AdminController::class, 'storeSymptom']);
            Route::put('/trieu-chung/{id}', [\App\Http\Controllers\Api\AdminController::class, 'updateSymptom']);
            Route::delete('/trieu-chung/{id}', [\App\Http\Controllers\Api\AdminController::class, 'destroySymptom']);
            Route::get('/tri-thuc-sua-chua', [\App\Http\Controllers\Api\AdminController::class, 'getRepairKnowledgeTree']);
            Route::post('/nguyen-nhan', [\App\Http\Controllers\Api\AdminController::class, 'storeCause']);
            Route::put('/nguyen-nhan/{id}', [\App\Http\Controllers\Api\AdminController::class, 'updateCause']);
            Route::delete('/nguyen-nhan/{id}', [\App\Http\Controllers\Api\AdminController::class, 'destroyCause']);
            Route::get('/huong-xu-ly', [\App\Http\Controllers\Api\AdminController::class, 'getResolutions']);
            Route::post('/huong-xu-ly', [\App\Http\Controllers\Api\AdminController::class, 'storeResolution']);
            Route::put('/huong-xu-ly/{id}', [\App\Http\Controllers\Api\AdminController::class, 'updateResolution']);
            Route::delete('/huong-xu-ly/{id}', [\App\Http\Controllers\Api\AdminController::class, 'destroyResolution']);
            Route::get('/ai-knowledge', [\App\Http\Controllers\Api\AdminController::class, 'getAiKnowledgeItems']);
            Route::get('/ai-knowledge/export', [\App\Http\Controllers\Api\AdminController::class, 'exportAiKnowledge']);
            Route::post('/ai-knowledge/sync', [\App\Http\Controllers\Api\AdminController::class, 'syncAiKnowledge']);
            Route::get('/ai-knowledge/{id}', [\App\Http\Controllers\Api\AdminController::class, 'getAiKnowledgeItem']);
            Route::get('/assistant-soul', [\App\Http\Controllers\Api\AdminController::class, 'getAssistantSoulConfig']);
            Route::put('/assistant-soul', [\App\Http\Controllers\Api\AdminController::class, 'updateAssistantSoulConfig']);
            Route::delete('/assistant-soul', [\App\Http\Controllers\Api\AdminController::class, 'resetAssistantSoulConfig']);
            Route::get('/travel-fee-config', [TravelFeeConfigController::class, 'show']);
            Route::put('/travel-fee-config', [TravelFeeConfigController::class, 'update']);
        });
    });
});

// Public review APIs
Route::get('/ho-so-tho/{id}/danh-gia', [\App\Http\Controllers\Api\DanhGiaController::class, 'indexByWorker']);
Route::get('/ho-so-tho/{id}/danh-gia/summary', [\App\Http\Controllers\Api\DanhGiaController::class, 'summary']);
