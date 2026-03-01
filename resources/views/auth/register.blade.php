@extends('layouts.app')

@section('title', 'Đăng ký - Find a Worker')

@push('styles')
<style>
    /* Nền sang trọng với các quả cầu mờ (Blobs) */
    .auth-bg {
        min-height: 100vh;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 3rem 1rem;
        position: relative;
        overflow: hidden;
    }

    /* Quả cầu trang trí 1 */
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

    /* Quả cầu trang trí 2 */
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
        max-width: 500px;
        padding: 3rem;
        z-index: 1; /* Nổi lên trên blobs */
    }

    .role-selector {
        display: flex;
        gap: 15px;
        margin-bottom: 2rem;
    }

    .role-option {
        flex: 1;
        background: rgba(255, 255, 255, 0.6);
        border: 2px solid transparent;
        border-radius: var(--border-radius-md);
        padding: 1.25rem 1rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }

    .role-option:hover {
        transform: translateY(-2px);
        background: rgba(255, 255, 255, 0.9);
        box-shadow: var(--shadow-md);
    }

    .role-option.active {
        background: white;
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    }

    .role-radio {
        display: none;
    }
    
    .role-image-container {
        height: 64px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        margin-bottom: 15px;
        transition: transform 0.3s ease;
    }
    .role-option:hover .role-image-container {
        transform: scale(1.05);
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
            <img src="{{ asset('assets/images/logo.png') }}" alt="Find Worker" style="height: 64px; object-fit: contain; filter: drop-shadow(0px 4px 6px rgba(0,0,0,0.1));">
        </div>
        <h3 class="text-center fw-bold mb-2 brand-font" style="color: var(--bs-primary);">Tạo Tải Khoản</h3>
        <p class="text-center text-muted-custom mb-4" style="font-size: 0.95rem;">Đăng ký để trải nghiệm dịch vụ tuyệt vời</p>

        <form id="registerForm">
            <label class="form-label fw-semibold mb-3">Bạn tham gia với vai trò gì?</label>
            <div class="role-selector">
                <!-- KHÁCH HÀNG -->
                <label class="role-option active" id="labelCustomer">
                    <input type="radio" name="role" value="customer" class="role-radio" checked>
                    <div class="role-image-container">
                        <img src="{{ asset('assets/images/customer.png') }}" alt="Khách hàng" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                    </div>
                    <span class="fw-bold d-block text-dark">Khách hàng</span>
                    <small class="text-muted-custom" style="font-size: 0.8rem;">Cần tìm thợ</small>
                </label>

                <!-- THỢ / ĐỐI TÁC -->
                <label class="role-option" id="labelWorker">
                    <input type="radio" name="role" value="worker" class="role-radio">
                    <div class="role-image-container">
                        <img src="{{ asset('assets/images/worker.png') }}" alt="Thợ" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                    </div>
                    <span class="fw-bold d-block text-dark">Thợ sửa chữa</span>
                    <small class="text-muted-custom" style="font-size: 0.8rem;">Tìm việc làm</small>
                </label>
            </div>

            <div class="mb-4">
                <label for="hoTen" class="form-label fw-semibold">Họ và tên</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: var(--border-radius-sm) 0 0 var(--border-radius-sm); border-color: #E2E8F0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" id="hoTen" placeholder="Nhập tên của bạn" required style="box-shadow: none;">
                </div>
            </div>

            <div class="mb-5">
                <label for="soDienThoai" class="form-label fw-semibold">Số điện thoại</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: var(--border-radius-sm) 0 0 var(--border-radius-sm); border-color: #E2E8F0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328z"/></svg>
                    </span>
                    <input type="tel" class="form-control border-start-0 ps-0" id="soDienThoai" placeholder="VD: 0987654321" required style="box-shadow: none;">
                </div>
            </div>

            <div class="d-grid mb-4">
                <button type="submit" class="btn btn-warning btn-lg" id="btnSubmit">
                    Tạo Tài Khoản
                </button>
            </div>
            
            <div class="text-center mt-3">
                <span class="text-muted-custom">Đã có tài khoản?</span>
                <a href="{{ route('login') }}" class="text-decoration-none fw-bold" style="color: var(--bs-primary); margin-left: 5px;">Đăng nhập ngay</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    import { callApi, saveUserSession } from "{{ asset('assets/js/api.js') }}";

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
        const role = document.querySelector('input[name="role"]:checked').value;

        const originalBtnText = btnSubmit.innerHTML;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';
        btnSubmit.disabled = true;

        try {
            const response = await callApi('/auth/register', 'POST', {
                name: name,
                so_dien_thoai: phone,
                role: role
            });
            
            if (response.ok) {
                Toastify({ text: "Đăng ký thành công!", duration: 2000, style: { background: "var(--bs-success)" } }).showToast();
                saveUserSession(response.data.access_token, response.data.user);
                setTimeout(() => {
                    window.location.href = role === 'worker' ? '/worker/dashboard' : '/customer/home';
                }, 1000);
            } else {
                let errorMsg = response.data.message || "Đăng ký thất bại.";
                if (response.data.errors && response.data.errors.so_dien_thoai) {
                    errorMsg = "Số điện thoại bị trùng lập!";
                }
                Toastify({ text: errorMsg, style: { background: "var(--bs-danger)" } }).showToast();
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