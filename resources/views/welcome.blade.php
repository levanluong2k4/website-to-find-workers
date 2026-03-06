@extends('layouts.app')

@section('title', 'Chọn Vai Trò - Find a Worker')

@push('styles')
<style>
    .welcome-bg {
        min-height: 100vh;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4rem 2rem;
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

        0%,
        100% {
            transform: translateY(0) scale(1);
        }

        50% {
            transform: translateY(-30px) scale(1.05);
        }
    }

    .brand-header {
        text-align: center;
        margin-bottom: 4rem;
        z-index: 1;
    }

    .role-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
        max-width: 900px;
        width: 100%;
        z-index: 1;
    }

    @media (min-width: 768px) {
        .role-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .role-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.5);
        border-radius: var(--border-radius-xl);
        padding: 3rem 2.5rem;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        overflow: hidden;
    }

    .role-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0) 100%);
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    .role-card:hover {
        transform: translateX(8px);
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 20px 40px rgba(16, 185, 129, 0.15);
        border-color: var(--bs-primary);
    }

    .role-card.worker-card:hover {
        border-color: var(--bs-warning);
        box-shadow: 0 20px 40px rgba(245, 158, 11, 0.15);
    }

    .role-card:hover::before {
        opacity: 1;
    }

    .role-icon {
        width: 100px;
        height: 100px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-md);
        position: relative;
        z-index: 2;
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .role-card:hover .role-icon {
        transform: scale(1.1) rotate(5deg);
    }

    .role-text-content {
        position: relative;
        z-index: 2;
    }
</style>
@endpush

@section('content')
<div class="welcome-bg">

    <div class="blob-1"></div>
    <div class="blob-2"></div>

    <div class="brand-header fade-in-up">
        <div class="mb-4">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Find Worker Logo" style="height: 90px; filter: drop-shadow(0 10px 15px rgba(0,0,0,0.1));">
        </div>
        <h1 class="fw-extrabold brand-font" style="color: var(--bs-primary); font-size: 3rem; letter-spacing: -0.03em;">Find a Worker</h1>
        <p class="text-muted-custom fs-5 mt-2" style="max-width: 500px; margin: 0 auto;">Nền tảng kết nối thợ chuyên nghiệp lớn nhất dành cho ngôi nhà của bạn.</p>
    </div>

    <h4 class="fw-bold mb-4 text-center fade-in-up brand-font" style="animation-delay: 0.1s; z-index: 1;">Bạn muốn tham gia với vai trò gì?</h4>

    <div class="role-grid">
        <!-- Khách Hàng -->
        <a href="{{ route('login') }}?role=customer" class="role-card fade-in-up" style="animation-delay: 0.2s;">
            <div class="role-icon">
                <img src="{{ asset('assets/images/customer.png') }}" alt="Khách hàng" style="height: 160px; object-fit: contain;">
            </div>
            <div class="role-text-content">
                <h3 class="fw-bold mb-3 brand-font" style="color: var(--bs-body-color);">Tôi là Khách Hàng</h3>
                <p class="text-muted-custom mb-0" style="font-size: 1.05rem; line-height: 1.6;">Tôi đang tìm thợ sửa chữa, bảo trì cho gia đình hoặc công ty.</p>
            </div>
            <div class="mt-4 fw-bold" style="color: var(--bs-primary); font-size: 0.95rem;">Tham gia ngay →</div>
        </a>

        <!-- Thợ / Đối Tác -->
        <a href="{{ route('login') }}?role=worker" class="role-card worker-card fade-in-up" style="animation-delay: 0.3s;">
            <div class="role-icon">
                <img src="{{ asset('assets/images/worker.png') }}" alt="Thợ" style="height: 180px; object-fit: contain;">
            </div>
            <div class="role-text-content">
                <h3 class="fw-bold mb-3 brand-font" style="color: var(--bs-body-color);">Tôi là Thợ Chuyên Nghiệp</h3>
                <p class="text-muted-custom mb-0" style="font-size: 1.05rem; line-height: 1.6;">Tôi muốn nhận việc, báo giá và quản lý khách hàng thông minh.</p>
            </div>
            <div class="mt-4 fw-bold" style="color: var(--bs-warning); font-size: 0.95rem;">Đăng ký đối tác →</div>
        </a>
    </div>

</div>
@endsection

@push('scripts')
<script type="module">
    import {
        getCurrentUser
    } from "{{ asset('assets/js/api.js') }}";

    const user = getCurrentUser();
    if (user) {
        window.location.replace(user.role === 'worker' ? "{{ route('worker.dashboard') }}" : "{{ route('customer.home') }}");
    }
</script>
@endpush