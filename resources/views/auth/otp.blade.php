@extends('layouts.app')

@section('title', 'Xác minh OTP - Find a Worker')

@push('styles')
<style>
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

        0%,
        100% {
            transform: translateY(0) scale(1);
        }

        50% {
            transform: translateY(-30px) scale(1.05);
        }
    }

    .auth-card {
        width: 100%;
        max-width: 440px;
        padding: 3rem 2.5rem;
        z-index: 1;
        text-align: center;
    }

    .otp-inputs {
        display: flex;
        gap: 12px;
        justify-content: center;
        margin-bottom: 2.5rem;
    }

    .otp-input {
        width: 52px;
        height: 64px;
        text-align: center;
        font-size: 26px;
        font-weight: 700;
        font-family: 'Outfit', sans-serif;
        border-radius: var(--border-radius-sm);
        border: 2px solid #E2E8F0;
        color: var(--bs-primary);
        background: rgba(255, 255, 255, 0.8);
        transition: all 0.3s ease;
    }

    .otp-input:focus {
        border-color: var(--bs-primary);
        outline: none;
        background: white;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px rgba(16, 185, 129, 0.1);
    }
</style>
@endpush

@section('content')
<div class="auth-bg">
    <!-- Blobs -->
    <div class="blob-1"></div>
    <div class="blob-2"></div>

    <div class="card-glass auth-card fade-in-up">
        <div style="color: var(--bs-primary); margin-bottom: 1.5rem; animation-delay: 0.1s;" class="fade-in-up">
            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="currentColor" viewBox="0 0 16 16">
                <path d="M5.338 1.59a61.44 61.44 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55 0 0 0 .101.025.615.615 0 0 0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.456 62.456 0 0 1 5.072.56z" />
                <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0z" />
            </svg>
        </div>
        <h3 class="fw-bold mb-2 brand-font fade-in-up" style="color: var(--bs-primary); animation-delay: 0.2s;">Xác thực mã OTP</h3>
        <p class="text-muted-custom mb-4 fade-in-up" style="animation-delay: 0.3s; font-size: 0.95rem;">
            Mã an mật gồm 6 số đã được gửi tới <br>
            <strong id="displayPhone" style="color: var(--bs-body-color);">...</strong>
        </p>

        <form id="otpForm" class="fade-in-up" style="animation-delay: 0.4s;">
            <input type="hidden" id="soDienThoai">

            <div class="otp-inputs" id="otpContainer">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required autofocus>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
            </div>

            <div class="d-grid mb-4">
                <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm" id="btnSubmit">
                    Xác nhận
                </button>
            </div>
        </form>

        <div class="mt-4 pt-3 border-top fade-in-up" style="animation-delay: 0.5s; border-color: rgba(0,0,0,0.05) !important;">
            <span class="text-muted-custom">Chưa nhận được mã?</span>
            <a href="#" class="text-decoration-none fw-bold" style="color: var(--bs-warning); margin-left: 5px;" id="resendBtn">Gửi lại mã</a>
        </div>

        <div class="mt-3 fade-in-up" style="animation-delay: 0.6s;">
            <a href="{{ route('login') }}" class="text-muted-custom text-decoration-none small hover-primary" style="transition: color 0.3s;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1 mb-1" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z" />
                </svg> Mời nhập lại số khác
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    import {
        callApi,
        saveUserSession
    } from "{{ asset('assets/js/api.js') }}";

    const urlParams = new URLSearchParams(window.location.search);
    const phone = urlParams.get('phone');
    if (!phone) {
        window.location.href = "{{ route('login') }}";
    }

    document.getElementById('displayPhone').innerText = phone;
    document.getElementById('soDienThoai').value = phone;

    const inputs = document.querySelectorAll('.otp-input');
    inputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            if (e.target.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });

    const form = document.getElementById('otpForm');
    const btnSubmit = document.getElementById('btnSubmit');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        let otpCode = '';
        inputs.forEach(inp => otpCode += inp.value);

        const origText = btnSubmit.innerHTML;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xác thực...';
        btnSubmit.disabled = true;

        try {
            const response = await callApi('/auth/verify-otp', 'POST', {
                so_dien_thoai: phone,
                otp: otpCode
            });
            if (response.ok) {
                Toastify({
                    text: "Đăng nhập thành công!",
                    style: {
                        background: "var(--bs-success)"
                    }
                }).showToast();
                saveUserSession(response.data.access_token, response.data.user);
                setTimeout(() => {
                    window.location.href = response.data.user.role === 'worker' ? '/worker/dashboard' : '/customer/home';
                }, 1000);
            } else {
                Toastify({
                    text: "Mã OTP không đúng hoặc đã hết hạn!",
                    style: {
                        background: "var(--bs-danger)"
                    }
                }).showToast();
                inputs.forEach(inp => inp.value = '');
                inputs[0].focus();
            }
        } catch (error) {
            Toastify({
                text: "Lỗi Server",
                style: {
                    background: "var(--bs-danger)"
                }
            }).showToast();
        } finally {
            btnSubmit.innerHTML = origText;
            btnSubmit.disabled = false;
        }
    });

    document.getElementById('resendBtn').addEventListener('click', async (e) => {
        e.preventDefault();
        try {
            const res = await callApi('/auth/login', 'POST', {
                so_dien_thoai: phone
            });
            if (res.ok) {
                Toastify({
                    text: "Đã gửi lại mã OTP!",
                    style: {
                        background: "var(--bs-success)"
                    }
                }).showToast();
            }
        } catch (e) {}
    });
</script>
@endpush