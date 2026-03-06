@extends('layouts.app')

@section('title', 'Đăng ký - Find a Worker')

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
        max-width: 500px;
        padding: 2.5rem 2rem;
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

    .role-selector {
        display: flex;
        gap: 15px;
        margin-bottom: 2rem;
    }

    .role-option {
        flex: 1;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.25rem 1rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .role-option:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .role-option.active {
        background: rgba(16, 185, 129, 0.05);
        border-color: var(--bs-primary);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
    }

    .role-radio {
        display: none;
    }

    .role-image-container {
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
        transition: transform 0.3s ease;
    }

    .role-option:hover .role-image-container {
        transform: scale(1.05);
    }
</style>
@endpush

@section('content')
<div class="auth-wrapper">
    <div class="auth-card fade-in-up">
        <div class="text-center">
            <div class="logo-container shadow-sm">
                <span class="material-symbols-outlined" style="font-size: 36px;">person_add</span>
            </div>
            <h3 class="fw-bold text-dark mb-1 font-heading">Tạo Tài Khoản</h3>
            <p class="text-muted mb-4 pb-2" style="font-size: 0.95rem;">Đăng ký để trải nghiệm dịch vụ</p>
        </div>

        <form id="registerForm">
            <label class="form-label fw-semibold text-secondary small text-uppercase mb-3" style="letter-spacing: 0.5px;">Bạn tham gia với vai trò gì?</label>
            <div class="role-selector">
                <!-- KHÁCH HÀNG -->
                <label class="role-option active" id="labelCustomer">
                    <input type="radio" name="role" value="customer" class="role-radio" checked>
                    <div class="role-image-container">
                        <img src="{{ asset('assets/images/customer.png') }}" alt="Khách hàng" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                    </div>
                    <span class="fw-bold d-block text-dark">Khách hàng</span>
                    <small class="text-muted" style="font-size: 0.8rem;">Cần tìm thợ</small>
                </label>

                <!-- THỢ -->
                <label class="role-option" id="labelWorker">
                    <input type="radio" name="role" value="worker" class="role-radio">
                    <div class="role-image-container">
                        <img src="{{ asset('assets/images/worker.png') }}" alt="Thợ" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                    </div>
                    <span class="fw-bold d-block text-dark">Thợ sửa chữa</span>
                    <small class="text-muted" style="font-size: 0.8rem;">Đăng ký nhận việc</small>
                </label>
            </div>

            <div class="mb-4">
                <label for="hoTen" class="form-label fw-semibold text-secondary small text-uppercase" style="letter-spacing: 0.5px;">Họ và tên</label>
                <div class="input-group">
                    <span class="input-group-text input-group-text-custom">
                        <span class="material-symbols-outlined fs-5">person</span>
                    </span>
                    <input type="text" class="form-control form-control-custom ps-0" id="hoTen" placeholder="Nhập tên của bạn" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="soDienThoai" class="form-label fw-semibold text-secondary small text-uppercase" style="letter-spacing: 0.5px;">Số điện thoại</label>
                <div class="input-group">
                    <span class="input-group-text input-group-text-custom">
                        <span class="material-symbols-outlined fs-5">phone_iphone</span>
                    </span>
                    <input type="tel" class="form-control form-control-custom ps-0" id="soDienThoai" placeholder="VD: 0987654321" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="email" class="form-label fw-semibold text-secondary small text-uppercase" style="letter-spacing: 0.5px;">Email</label>
                <div class="input-group">
                    <span class="input-group-text input-group-text-custom">
                        <span class="material-symbols-outlined fs-5">mail</span>
                    </span>
                    <input type="email" class="form-control form-control-custom ps-0" id="email" placeholder="VD: nguyenvan@gmail.com" required>
                </div>
            </div>

            <div class="mb-4 pb-2">
                <label for="matKhau" class="form-label fw-semibold text-secondary small text-uppercase" style="letter-spacing: 0.5px;">Mật khẩu</label>
                <div class="input-group">
                    <span class="input-group-text input-group-text-custom">
                        <span class="material-symbols-outlined fs-5">lock</span>
                    </span>
                    <input type="password" class="form-control form-control-custom ps-0" id="matKhau" placeholder="Tối thiểu 6 ký tự" required minlength="6">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-auth w-100 mb-4" id="btnSubmit">
                Tạo Tài Khoản
            </button>

            <div class="text-center mt-2 pt-3 border-top">
                <p class="text-muted mb-0" style="font-size: 0.95rem;">
                    Đã có tài khoản?
                    <a href="{{ route('login') }}" class="text-primary fw-bold text-decoration-none ms-1">Đăng nhập</a>
                </p>
            </div>
        </form>
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

    const baseUrl = '{{ url('/') }}';

    const urlParams = new URLSearchParams(window.location.search);
    const preselectedRole = urlParams.get('role');

    if (preselectedRole === 'worker') {
        document.querySelector('input[name="role"][value="worker"]').checked = true;
        document.getElementById('labelWorker').classList.add('active');
        document.getElementById('labelCustomer').classList.remove('active');
    } else if (preselectedRole === 'customer') {
        document.querySelector('input[name="role"][value="customer"]').checked = true;
        document.getElementById('labelCustomer').classList.add('active');
        document.getElementById('labelWorker').classList.remove('active');
    }

    const roleRadios = document.querySelectorAll('.role-radio');
    roleRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            document.querySelectorAll('.role-option').forEach(el => el.classList.remove('active'));
            e.target.closest('.role-option').classList.add('active');
        });
    });

    const form = document.getElementById('registerForm');
    const btnSubmit = document.getElementById('btnSubmit');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const name = document.getElementById('hoTen').value;
        const phone = document.getElementById('soDienThoai').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('matKhau').value;
        const role = document.querySelector('input[name="role"]:checked').value;

        const originalBtnText = btnSubmit.innerHTML;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang xử lý...';
        btnSubmit.disabled = true;

        try {
            const response = await callApi('/register', 'POST', {
                name: name,
                phone: phone,
                email: email,
                password: password,
                role: role
            });

            if (response.ok) {
                if (response.data.debug_otp) {
                    sessionStorage.setItem('debug_otp', response.data.debug_otp);
                }
                showToast("Đăng ký thành công! Hãy kiểm tra mã OTP trong Email của bạn.");
                setTimeout(() => {
                    window.location.href = baseUrl + `/otp?email=${response.data.email}&is_new=1`;
                }, 1500);
            } else {
                let errorMsg = response.data.message || "Đăng ký thất bại.";
                if (response.data.errors && response.data.errors.email) {
                    errorMsg = "Email đã tồn tại!";
                } else if (response.data.errors && response.data.errors.phone) {
                    errorMsg = "Số điện thoại bị trùng lập!";
                }
                showToast(errorMsg, 'error');
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