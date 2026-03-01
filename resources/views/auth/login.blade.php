@extends('layouts.app')

@section('title', 'Đăng nhập - Find a Worker')

@push('styles')
<style>
    /* Nền sang trọng với các quả cầu mờ (Blobs) */
    .auth-bg {
        min-height: 100vh;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
        position: relative;
        overflow: hidden;
    }

    .blob-1 {
        position: absolute;
        top: -10%;
        left: -10%;
        width: 50vw;
        height: 50vw;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.4), rgba(5, 150, 105, 0.1));
        filter: blur(80px);
        z-index: 0;
        animation: float 10s ease-in-out infinite;
    }

    .blob-2 {
        position: absolute;
        bottom: -20%;
        right: -10%;
        width: 60vw;
        height: 60vw;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.3), rgba(217, 119, 6, 0.1));
        filter: blur(100px);
        z-index: 0;
        animation: float 12s ease-in-out infinite reverse;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0) scale(1); }
        50% { transform: translateY(-30px) scale(1.05); }
    }

    .auth-card {
        width: 100%;
        max-width: 420px;
        padding: 3rem 2.5rem;
        z-index: 1; /* Nổi lên trên blobs */
    }
</style>
@endpush

@section('content')
<div class="auth-bg">
    <!-- Blobs Trang trí nền -->
    <div class="blob-1"></div>
    <div class="blob-2"></div>

    <div class="card-glass auth-card fade-in-up">
        <div class="text-center mb-4">
            <img src="{{ asset('assets/images/logo.png') }}" class="fade-in-up" alt="Find Worker" style="height: 72px; object-fit: contain; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1)); animation-delay: 0.1s;">
        </div>
        <h3 class="text-center fw-bold mb-2 brand-font fade-in-up" style="color: var(--bs-primary); animation-delay: 0.2s;">Chào mừng trở lại!</h3>
        <p class="text-center text-muted-custom mb-4 fade-in-up" style="font-size: 0.95rem; animation-delay: 0.3s;">Mời bạn đăng nhập để tiếp tục</p>

        <form id="loginForm" class="fade-in-up" style="animation-delay: 0.4s;">
            <div class="mb-4">
                <label for="soDienThoai" class="form-label fw-semibold">Số điện thoại</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: var(--border-radius-sm) 0 0 var(--border-radius-sm); border-color: #E2E8F0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328z"/></svg>
                    </span>
                    <input type="tel" class="form-control border-start-0 ps-0" id="soDienThoai" placeholder="Nhập 09xx..." required style="box-shadow: none;">
                </div>
            </div>

            <div class="d-grid mb-4">
                <button type="submit" class="btn btn-warning btn-lg fw-bold shadow-sm" id="btnSubmit">
                    Tiếp tục
                </button>
            </div>
        </form>

        <div class="text-center mt-2 fade-in-up" style="animation-delay: 0.5s;">
            <span class="text-muted-custom">Chưa có tài khoản?</span>
            <a href="{{ route('register') }}" class="text-decoration-none fw-bold" style="color: var(--bs-primary); margin-left: 5px;">Đăng ký ngay</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    import { callApi } from "{{ asset('assets/js/api.js') }}";

    // Lấy Token kiểm tra xem đã login chưa
    const token = localStorage.getItem('access_token');
    const user = localStorage.getItem('user');
    if (token && user) {
        const userData = JSON.parse(user);
        window.location.href = userData.role === 'worker' ? '/worker/dashboard' : '/customer/home';
    }

    const loginForm = document.getElementById('loginForm');
    const btnSubmit = document.getElementById('btnSubmit');

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const phone = document.getElementById('soDienThoai').value;

        const originalBtnText = btnSubmit.innerHTML;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';
        btnSubmit.disabled = true;

        try {
            const response = await callApi('/auth/login', 'POST', { so_dien_thoai: phone });
            
            if (response.ok) {
                Toastify({ text: "Đã gửi mã OTP thành công!", duration: 3000, style: { background: "var(--bs-success)" } }).showToast();
                setTimeout(() => { window.location.href = `/otp?phone=${phone}`; }, 1000);
            } else {
                Toastify({ text: response.data.message || "Không tìm thấy SĐT trong hệ thống!", style: { background: "var(--bs-danger)" } }).showToast();
            }
        } catch (error) {
            Toastify({ text: "Lỗi kết nối", style: { background: "var(--bs-danger)" } }).showToast();
        } finally {
            btnSubmit.innerHTML = originalBtnText;
            btnSubmit.disabled = false;
        }
    });
</script>
@endpush