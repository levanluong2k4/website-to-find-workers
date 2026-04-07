<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

// Trang chủ (Landing Page)
Route::get('/', function () {
    return view('customer.home');
})->name('home');

// Trang chọn vai trò (Role Selection)
Route::get('/select-role', function () {
    return view('welcome');
})->name('select-role');

Route::get('/debug-tho', function () {
    return \App\Models\HoSoTho::count();
});

Route::get('/drop-obsolete', function () {
    try {
        $data = \Illuminate\Support\Facades\DB::select('SHOW CREATE TABLE don_dat_lich');
        return response()->json($data);
    } catch (\Exception $e) {
        return 'ERROR: ' . $e->getMessage();
    }
});

// Khối Authentication (Xác thực)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');
Route::get('/register', function () {
    return view('auth.register');
})->name('register');
Route::get('/otp', function () {
    return view('auth.otp');
})->name('otp');
Route::get('/verify-phone', function () {
    return view('auth.phone-verification');
})->name('verify-phone');
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

// Khối Khách Hàng (Customer)
Route::prefix('customer')->group(function () {
    Route::get('/home', function () {
        return view('customer.home');
    })->name('customer.home');

    Route::get('/booking', function () {
        return view('customer.booking');
    })->name('customer.booking');

    Route::get('/search', function () {
        return view('customer.search');
    })->name('customer.search');

    Route::get('/linh-kien', function () {
        return view('customer.parts');
    })->name('customer.parts');

    Route::get('/linh-kien/{id}', function ($id) {
        return view('customer.part-detail', ['id' => $id]);
    })->whereNumber('id')->name('customer.parts.show');

    Route::get('/worker-profile/{id}', function ($id) {
        return view('customer.worker-profile', ['workerId' => $id]);
    })->name('customer.worker-profile');

    Route::get('/my-bookings', function () {
        return view('customer.my-bookings');
    })->name('customer.my-bookings');

    Route::get('/my-bookings/{id}', function ($id) {
        return view('customer.booking-details', ['id' => $id]);
    })->whereNumber('id')->name('customer.my-bookings.show');

    Route::get('/profile', function () {
        return view('customer.profile');
    })->name('customer.profile');
});

// Khối Thợ (Worker)
Route::prefix('worker')->group(function () {
    Route::get('/dashboard', function () {
        return view('worker.dashboard');
    })->name('worker.dashboard');

    Route::get('/my-bookings', function () {
        return view('worker.my-bookings');
    })->name('worker.my-bookings');

    Route::get('/my-bookings/{id}/pricing', function ($id) {
        return redirect()->route('worker.my-bookings');
    })->whereNumber('id')->name('worker.my-bookings.pricing');

    Route::get('/jobs', function () {
        return redirect()->route('worker.my-bookings', ['status' => 'pending']);
    })->name('worker.jobs');

    Route::get('/jobs/{id}', function ($id) {
        $statusMap = [
            'cho_xac_nhan' => 'pending',
            'da_xac_nhan' => 'upcoming',
            'dang_lam' => 'inprogress',
            'cho_hoan_thanh' => 'payment',
            'cho_thanh_toan' => 'payment',
            'da_xong' => 'done',
            'da_huy' => 'cancelled',
        ];

        $bookingStatus = \App\Models\DonDatLich::query()
            ->whereKey($id)
            ->value('trang_thai');

        $redirectParams = ['booking' => $id];
        if ($bookingStatus && isset($statusMap[$bookingStatus])) {
            $redirectParams['status'] = $statusMap[$bookingStatus];
        }

        return redirect()->route('worker.my-bookings', $redirectParams);
    })->whereNumber('id')->name('worker.job-details');

    Route::get('/profile', function () {
        return view('worker.profile');
    })->name('worker.profile');

    Route::get('/analytics', function () {
        return view('worker.analytics');
    })->name('worker.analytics');

    Route::get('/reviews', function () {
        return view('worker.reviews');
    })->name('worker.reviews');

    Route::get('/calendar', function () {
        return view('worker.calendar');
    })->name('worker.calendar');
});

// Khối Quản trị viên (Admin)
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/customers', function () {
        return view('admin.customers');
    })->name('admin.customers');

    Route::get('/customers/{id}', function ($id) {
        return view('admin.customer-detail', ['id' => $id]);
    })->whereNumber('id')->name('admin.customers.show');

    Route::get('/customers/{id}/bookings', function ($id) {
        return view('admin.customer-bookings', ['id' => $id]);
    })->whereNumber('id')->name('admin.customers.bookings');

    Route::get('/customer-feedback', function () {
        return view('admin.customer-feedback');
    })->name('admin.customer-feedback');

    Route::get('/users', function () {
        return view('admin.users');
    })->name('admin.users');

    Route::get('/services', function () {
        return view('admin.services');
    })->name('admin.services');

    Route::get('/linh-kien', function () {
        return view('admin.parts');
    })->name('admin.parts');

    Route::get('/trieu-chung', function () {
        return view('admin.symptoms');
    })->name('admin.symptoms');

    Route::get('/huong-xu-ly', function () {
        return view('admin.resolutions');
    })->name('admin.resolutions');

    Route::get('/assistant-soul', function () {
        return view('admin.assistant-soul');
    })->name('admin.assistant-soul');

    Route::get('/travel-fee-config', function () {
        return view('admin.travel-fee-config');
    })->name('admin.travel-fee-config');

    Route::get('/bookings', function () {
        return view('admin.bookings');
    })->name('admin.bookings');

    Route::get('/dispatch', function () {
        return view('admin.dispatch');
    })->name('admin.dispatch');
});
