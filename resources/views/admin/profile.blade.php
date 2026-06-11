@extends('layouts.app')

@section('title', 'Tài khoản của tôi - Thợ Tốt')

@push('styles')
<style>
    body {
        background: #f8f9ff;
    }

    .admin-profile-page {
        max-width: 1120px;
        margin: 0 auto;
        padding: 1.5rem 1rem 3rem;
    }

    .admin-profile-head {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .admin-profile-title {
        font-size: 1.875rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.02em;
    }

    .admin-profile-subtitle {
        color: #64748b;
        font-size: 0.875rem;
        margin: 0.35rem 0 0;
    }

    .admin-profile-grid {
        display: grid;
        grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
        gap: 1rem;
        align-items: start;
    }

    .admin-profile-panel {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        padding: 1.25rem;
    }

    .admin-profile-avatar-box {
        display: grid;
        justify-items: center;
        text-align: center;
        gap: 0.9rem;
    }

    .admin-profile-avatar {
        width: 124px;
        height: 124px;
        border-radius: 50%;
        background: #e8f1ff;
        color: #0f4db8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
        font-weight: 800;
        overflow: hidden;
        border: 4px solid #fff;
        box-shadow: 0 18px 36px rgba(37, 99, 235, 0.18);
    }

    .admin-profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .admin-profile-name {
        color: #0f172a;
        font-weight: 800;
        font-size: 1.05rem;
        margin: 0;
    }

    .admin-profile-role {
        color: #64748b;
        font-size: 0.84rem;
        margin: 0.2rem 0 0;
    }

    .admin-profile-upload {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: 0;
        border-radius: 999px;
        background: #0f172a;
        color: #fff;
        font-size: 0.875rem;
        font-weight: 700;
        padding: 0.65rem 1rem;
        cursor: pointer;
    }

    .admin-profile-upload:hover {
        background: #1e293b;
    }

    .admin-profile-tabs {
        display: inline-flex;
        padding: 0.25rem;
        gap: 0.25rem;
        background: #e2e8f0;
        border: 1px solid #cbd5e1;
        border-radius: 0.875rem;
        margin-bottom: 1rem;
    }

    .admin-profile-tab {
        border: 0;
        background: transparent;
        color: #475569;
        font-weight: 800;
        font-size: 0.875rem;
        border-radius: 0.625rem;
        padding: 0.625rem 1rem;
    }

    .admin-profile-tab.active {
        color: #0f172a;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
    }

    .admin-profile-section {
        display: none;
    }

    .admin-profile-section.active {
        display: block;
    }

    .admin-profile-section h2 {
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 1rem;
    }

    .admin-profile-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 1.25rem;
    }

    .admin-profile-btn {
        border: 0;
        border-radius: 999px;
        background: #2563eb;
        color: #fff;
        font-weight: 800;
        font-size: 0.875rem;
        padding: 0.7rem 1.25rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .admin-profile-btn:hover {
        background: #1d4ed8;
    }

    @media (max-width: 900px) {
        .admin-profile-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 576px) {
        .admin-profile-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .admin-profile-tabs {
            width: 100%;
        }

        .admin-profile-tab {
            flex: 1;
        }
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<main class="admin-profile-page">
    <div class="admin-profile-head">
        <div>
            <h1 class="admin-profile-title">Tài khoản của tôi</h1>
            <p class="admin-profile-subtitle">Cập nhật thông tin quản trị, ảnh đại diện và mật khẩu đăng nhập.</p>
        </div>
    </div>

    <div class="admin-profile-grid">
        <aside class="admin-profile-panel">
            <div class="admin-profile-avatar-box">
                <div class="admin-profile-avatar" id="adminProfileAvatar">A</div>
                <div>
                    <p class="admin-profile-name" id="adminProfileName">Admin</p>
                    <p class="admin-profile-role" id="adminProfileEmail">admin@example.com</p>
                </div>
                <label class="admin-profile-upload" for="adminAvatarInput">
                    <i class="fas fa-camera"></i>
                    Đổi ảnh đại diện
                </label>
                <input type="file" id="adminAvatarInput" class="d-none" accept="image/jpeg,image/png,image/jpg,image/webp,image/gif">
                <p class="admin-profile-subtitle">Ảnh JPG, PNG, WEBP hoặc GIF, tối đa 5MB.</p>
            </div>
        </aside>

        <section class="admin-profile-panel">
            <div class="admin-profile-tabs" role="tablist">
                <button type="button" class="admin-profile-tab active" data-profile-tab="info">Thông tin</button>
                <button type="button" class="admin-profile-tab" data-profile-tab="password">Mật khẩu</button>
            </div>

            <div class="admin-profile-section active" id="adminInfoSection">
                <h2>Thông tin tài khoản</h2>
                <form id="adminProfileForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Họ và tên</label>
                            <input type="text" class="form-control" id="adminProfileInputName" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" class="form-control" id="adminProfileInputEmail" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Số điện thoại</label>
                            <input type="text" class="form-control" id="adminProfileInputPhone" required>
                        </div>
                    </div>
                    <div class="admin-profile-actions">
                        <button type="submit" class="admin-profile-btn" id="adminProfileSaveBtn">
                            <i class="fas fa-floppy-disk"></i>
                            Lưu thông tin
                        </button>
                    </div>
                </form>
            </div>

            <div class="admin-profile-section" id="adminPasswordSection">
                <h2>Đổi mật khẩu</h2>
                <form id="adminPasswordForm">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Mật khẩu hiện tại</label>
                            <input type="password" class="form-control" id="adminCurrentPassword" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Mật khẩu mới</label>
                            <input type="password" class="form-control" id="adminNewPassword" minlength="6" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Xác nhận mật khẩu mới</label>
                            <input type="password" class="form-control" id="adminNewPasswordConfirmation" minlength="6" required>
                        </div>
                    </div>
                    <div class="admin-profile-actions">
                        <button type="submit" class="admin-profile-btn" id="adminPasswordSaveBtn">
                            <i class="fas fa-key"></i>
                            Đổi mật khẩu
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</main>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/profile.js') }}?v={{ filemtime(public_path('assets/js/admin/profile.js')) }}"></script>
@endpush
