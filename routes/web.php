<?php

use Illuminate\Support\Facades\Route;

// Trang chuyển hướng (Redirecting Page)
Route::get('/', function () {
    return view('welcome');
})->name('home');

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
});

// Khối Thợ (Worker)
Route::prefix('worker')->group(function () {
    Route::get('/dashboard', function () {
        return view('worker.dashboard');
    })->name('worker.dashboard');
});
