<?php

use Illuminate\Support\Facades\Route;

// Trang chuyển hướng (Redirecting Page)
Route::get('/', function () {
    return view('welcome');
})->name('home');

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

// Khối Khách Hàng (Customer)
Route::prefix('customer')->group(function () {
    Route::get('/home', function () {
        return view('customer.home');
    })->name('customer.home');

    Route::get('/search', function () {
        return view('customer.search');
    })->name('customer.search');

    Route::get('/worker-profile/{id}', function ($id) {
        return view('customer.worker-profile', ['workerId' => $id]);
    })->name('customer.worker-profile');

    Route::get('/my-bookings', function () {
        return view('customer.my-bookings');
    })->name('customer.my-bookings');
});

// Khối Thợ (Worker)
Route::prefix('worker')->group(function () {
    Route::get('/dashboard', function () {
        return view('worker.dashboard');
    })->name('worker.dashboard');

    Route::get('/my-bookings', function () {
        return view('worker.my-bookings');
    })->name('worker.my-bookings');

    Route::get('/jobs', function () {
        return view('worker.jobs');
    })->name('worker.jobs');

    Route::get('/jobs/{id}', function ($id) {
        return view('worker.job-details', ['id' => $id]);
    })->name('worker.job-details');

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

    Route::get('/users', function () {
        return view('admin.users');
    })->name('admin.users');

    Route::get('/services', function () {
        return view('admin.services');
    })->name('admin.services');

    Route::get('/bookings', function () {
        return view('admin.bookings');
    })->name('admin.bookings');
});
