@extends('layouts.app')

@section('title', 'Hồ Sơ Của Tôi - Thợ Tốt')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
<style>
    body {
        background-color: #f8fafc;
    }

    .header-banner {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
        padding: 3rem 0 5rem 0;
        margin-bottom: 2rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .profile-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        margin-top: -60px;
        position: relative;
        z-index: 10;
        overflow: hidden;
    }

    .form-control,
    .form-select {
        border-color: #cbd5e1;
        padding: 0.75rem 1rem;
        border-radius: 8px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.1);
    }

    .avatar-upload {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid white;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin: -60px auto 1rem auto;
        background: #e2e8f0;
        position: relative;
    }

    .avatar-upload img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-upload .overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.5);
        color: white;
        text-align: center;
        padding: 4px 0;
        font-size: 12px;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .avatar-upload:hover .overlay {
        opacity: 1;
    }

    .stat-box {
        background: #f1f5f9;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="header-banner">
    <div class="container text-center">
        <h1 class="fw-bold text-dark mb-2" style="letter-spacing: -0.5px;">Hồ Sơ Chuyên Gia</h1>
        <p class="text-secondary mb-0 fs-5">Cập nhật kinh nghiệm và chứng chỉ để thu hút khách hàng.</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="profile-card p-4 p-md-5">
                <div class="avatar-upload mx-auto mb-4 position-relative" style="cursor: pointer;" onclick="document.getElementById('uploadAvatar').click()">
                    <img src="/assets/images/user-default.png" id="workerAvatar" alt="Avatar">
                    <div class="overlay">
                        <span class="material-symbols-outlined fs-6 align-middle">photo_camera</span> Tải ảnh
                    </div>
                    <input type="file" id="uploadAvatar" class="d-none" accept="image/jpeg, image/png, image/jpg, image/webp">
                </div>

                <h3 class="fw-bold text-center mb-1 text-dark" id="workerName">Sạc thông tin...</h3>
                <p class="text-center text-muted mb-4">Tham gia từ: <span id="workerJoinDate">--/--/----</span></p>

                <div class="row g-3 mb-5">
                    <div class="col-6">
                        <div class="stat-box">
                            <span class="material-symbols-outlined text-warning mb-2 fs-1" style="font-variation-settings: 'FILL' 1;">star</span>
                            <h3 class="fw-bold text-dark mb-0" id="statRating">0.0</h3>
                            <p class="text-muted small mb-0"><span id="statReviewCount">0</span> đánh giá</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-box">
                            <span class="material-symbols-outlined text-success mb-2 fs-1">task_alt</span>
                            <h3 class="fw-bold text-dark mb-0" id="statCompleted">0</h3>
                            <p class="text-muted small mb-0">Đơn hoàn thành</p>
                        </div>
                    </div>
                </div>

                <hr class="mb-4" style="opacity: 0.1;">

                <h5 class="fw-bold text-dark mb-4">Chỉnh sửa hồ sơ</h5>
                <form id="formWorkerProfile">
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary">Trạng thái làm việc</label>
                        <select class="form-select bg-light" id="inputTrangThai" required>
                            <option value="1">Đang hoạt động (Sẵn sàng nhận lệnh)</option>
                            <option value="0">Tạm nghỉ</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary">Giới thiệu & Kinh nghiệm</label>
                        <textarea class="form-control" id="inputKinhNghiem" rows="5" placeholder="Mô tả kỹ năng, số năm kinh nghiệm sửa chữa của bạn..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary mb-3">Dịch vụ cung cấp</label>
                        <div class="row g-3" id="serviceCheckboxContainer">
                            <!-- JS will populate checkboxes here -->
                            <div class="col-12 text-muted small">Đang tải danh sách dịch vụ...</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary">Link Chứng chỉ / Bằng cấp (nếu có)</label>
                        <input type="url" class="form-control" id="inputChungChi" placeholder="https://drive.google.com/=" ...">
                        <small class="text-muted mt-1 d-block">Dán link Google Drive chứa file hình ảnh hoặc PDF cứng chỉ nghề của bạn (Nhớ mở quyền xem).</small>
                    </div>

                    <div class="d-grid mt-5">
                        <button type="submit" class="btn btn-primary py-3 fw-bold fs-6 rounded-3" id="btnUpdateProfile">
                            Lưu Thay Đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/worker/profile.js') }}?v={{ time() }}"></script>
@endpush