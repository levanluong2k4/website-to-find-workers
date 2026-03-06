@extends('layouts.app')

@section('title', 'Xác minh OTP - Find a Worker')

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
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        color: var(--bs-primary);
        background: #f8fafc;
        transition: all 0.3s ease;
    }

    .otp-input:focus {
        border-color: var(--bs-primary);
        outline: none;
        background: white;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px rgba(16, 185, 129, 0.1);
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
                <span class="material-symbols-outlined" style="font-size: 36px;">lock_open</span>
            </div>
            <h3 class="fw-bold text-dark mb-2 font-heading">Xác thực OTP</h3>
            <p class="text-muted mb-4 pb-2" style="font-size: 0.95rem;">
                Mã bảo mật gồm 6 số đã được gửi tới <br>
                <strong id="displayPhone" class="text-dark">...</strong>
            </p>
        </div>

        <form id="otpForm">
            <input type="hidden" id="emailInput">

            <div class="otp-inputs" id="otpContainer">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required autofocus>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
            </div>

            <button type="submit" class="btn btn-primary btn-auth w-100 mb-4" id="btnSubmit">
                Xác nhận
            </button>
        </form>

        <div class="mt-2 pt-3 border-top">
            <p class="text-muted mb-2" style="font-size: 0.95rem;">
                Chưa nhận được mã?
                <a href="#" class="text-primary fw-bold text-decoration-none ms-1" id="resendBtn">Gửi lại mã</a>
            </p>
            <a href="{{ route('login') }}" class="text-secondary text-decoration-none small hover-primary" style="transition: color 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                <span class="material-symbols-outlined" style="font-size: 16px;">arrow_back</span> Nhập lại số khác
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    import {
        callApi,
        saveUserSession,
        showToast
    } from "{{ asset('assets/js/api.js') }}";

    const urlParams = new URLSearchParams(window.location.search);
    const email = urlParams.get('email');
    if (!email) {
        window.location.href = "{{ route('login') }}";
    }

    document.getElementById('displayPhone').innerText = email;
    document.getElementById('emailInput').value = email;

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

    // Tự động điền nếu có debug_otp (Local Dev)
    const debugOtp = sessionStorage.getItem('debug_otp');
    if (debugOtp) {
        const otpArr = debugOtp.split('');
        inputs.forEach((inp, idx) => {
            if (otpArr[idx]) inp.value = otpArr[idx];
        });
        showToast(`[Local Dev] Đã tự động điền mã OTP: ${debugOtp}`);
        sessionStorage.removeItem('debug_otp');
    }

    const form = document.getElementById('otpForm');
    const btnSubmit = document.getElementById('btnSubmit');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        let otpCode = '';
        inputs.forEach(inp => otpCode += inp.value);

        const origText = btnSubmit.innerHTML;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang xác thực...';
        btnSubmit.disabled = true;

        try {
            const response = await callApi('/verify-otp', 'POST', {
                email: email,
                code: otpCode
            });
            if (response.ok) {
                showToast("Đăng nhập thành công!");
                saveUserSession(response.data.access_token, response.data.user);
                setTimeout(() => {
                    const isNew = urlParams.get('is_new');
                    if (isNew === '1' && response.data.user.role === 'worker') {
                        window.location.href = '/worker/profile';
                    } else {
                        if (response.data.user.role === 'admin') window.location.href = '/admin/dashboard';
                        else if (response.data.user.role === 'worker') window.location.href = '/worker/dashboard';
                        else window.location.href = '/customer/home';
                    }
                }, 1000);
            } else {
                showToast("Mã OTP không đúng hoặc đã hết hạn!", 'error');
                inputs.forEach(inp => inp.value = '');
                inputs[0].focus();
            }
        } catch (error) {
            showToast("Lỗi Server", 'error');
        } finally {
            btnSubmit.innerHTML = origText;
            btnSubmit.disabled = false;
        }
    });

    document.getElementById('resendBtn').addEventListener('click', async (e) => {
        e.preventDefault();
        try {
            const res = await callApi('/resend-otp', 'POST', {
                email: email
            });
            if (res.ok) {
                if (res.data.debug_otp) {
                    const otpArr = res.data.debug_otp.split('');
                    inputs.forEach((inp, idx) => {
                        if (otpArr[idx]) inp.value = otpArr[idx];
                    });
                    showToast(`[Local Dev] Mới nhận mã OTP: ${res.data.debug_otp}`);
                } else {
                    showToast("Đã gửi lại mã OTP!");
                }
            }
        } catch (e) {}
    });
</script>
@endpush