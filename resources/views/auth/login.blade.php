@extends('layouts.app')

@section('title', 'Đăng nhập - Find a Worker')

@push('styles')
<style>
    body {
        background-color: var(--bg-light);
    }

    .auth-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }

    .auth-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 1.5rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
        width: 100%;
        max-width: 440px;
        padding: 3rem 2.5rem;
    }

    .form-control-custom {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-left: none;
        border-radius: 0 0.75rem 0.75rem 0;
        padding: 0.8rem 1rem;
        font-weight: 500;
        transition: all 0.2s;
        box-shadow: none !important;
    }

    .input-group-text-custom {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-right: none;
        border-radius: 0.75rem 0 0 0.75rem;
        color: #64748b;
    }

    .input-group:focus-within .form-control-custom,
    .input-group:focus-within .input-group-text-custom {
        border-color: var(--bs-primary);
        background-color: #ffffff;
    }

    .btn-auth {
        border-radius: 0.75rem;
        padding: 0.9rem;
        font-weight: 600;
        letter-spacing: 0.3px;
        transition: all 0.3s ease;
    }

    .btn-auth:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(16, 185, 129, 0.2);
    }

    .logo-container {
        width: 64px;
        height: 64px;
        background: rgba(16, 185, 129, 0.1);
        color: var(--bs-primary);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="auth-wrapper">
    <div class="auth-card fade-in-up">
        <div class="text-center">
            <div class="logo-container shadow-sm">
                <!-- Using an icon for the logo matching UI-UX Pro Max -->
                <span class="material-symbols-outlined" style="font-size: 36px;">home_repair_service</span>
            </div>
            <h3 class="fw-bold text-dark mb-1 font-heading">Chào mừng trở lại</h3>
            <p class="text-muted mb-4 pb-2" style="font-size: 0.95rem;">Đăng nhập để đặt lịch hoặc nhận việc</p>
        </div>

        <form id="loginForm">
            <div class="mb-4">
                <label for="email" class="form-label fw-semibold text-secondary small text-uppercase" style="letter-spacing: 0.5px;">Email</label>
                <div class="input-group">
                    <span class="input-group-text input-group-text-custom">
                        <span class="material-symbols-outlined fs-5">mail</span>
                    </span>
                    <input type="email" class="form-control form-control-custom ps-0" id="email" placeholder="Nhập email của bạn..." required>
                </div>
            </div>

            <div class="mb-4">
                <label for="matKhau" class="form-label fw-semibold text-secondary small text-uppercase" style="letter-spacing: 0.5px;">Mật khẩu</label>
                <div class="input-group">
                    <span class="input-group-text input-group-text-custom">
                        <span class="material-symbols-outlined fs-5">lock</span>
                    </span>
                    <input type="password" class="form-control form-control-custom ps-0" id="matKhau" placeholder="Nhập mật khẩu..." required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-auth w-100 mb-4" id="btnSubmit">
                Tiếp tục
            </button>
        </form>

        <div class="text-center mt-2 pt-3 border-top">
            <p class="text-muted mb-0" style="font-size: 0.95rem;">
                Chưa có tài khoản?
                <a href="{{ route('register') }}" class="text-primary fw-bold text-decoration-none ms-1">Đăng ký ngay</a>
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    import {
        callApi,
        showToast
    } from "{{ asset('assets/js/api.js') }}";

    const baseUrl = '{{ url('/') }}';

    // Lấy Token kiểm tra xem đã login chưa
    const token = localStorage.getItem('access_token');
    const user = localStorage.getItem('user');
    if (token && user) {
        const userData = JSON.parse(user);
        if (userData.role === 'admin') window.location.href = baseUrl + '/admin/dashboard';
        else if (userData.role === 'worker') window.location.href = baseUrl + '/worker/dashboard';
        else window.location.href = baseUrl + '/customer/home';
    }

    const loginForm = document.getElementById('loginForm');
    const btnSubmit = document.getElementById('btnSubmit');

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('matKhau').value;

        const originalBtnText = btnSubmit.innerHTML;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang xử lý...';
        btnSubmit.disabled = true;

        try {
            const response = await callApi('/login', 'POST', {
                email: email,
                password: password
            });

            if (response.ok) {
                if (response.data.debug_otp) {
                    sessionStorage.setItem('debug_otp', response.data.debug_otp);
                }
                showToast("Đã gửi mã OTP thành công!");
                setTimeout(() => {
                    window.location.href = baseUrl + `/otp?email=${email}`;
                }, 1000);
            } else {
                showToast(response.data.message || "Email hoặc mật khẩu không đúng!", 'error');
            }
        } catch (error) {
            showToast("Lỗi kết nối", 'error');
        } finally {
            btnSubmit.innerHTML = originalBtnText;
            btnSubmit.disabled = false;
        }
    });
</script>
@endpush