@extends('layouts.app')

@section('title', 'Hồ sơ người dùng - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Material+Symbols+Outlined&display=swap" rel="stylesheet" />
<style>
    :root {
        --profile-bg: #f3f6fb;
        --profile-surface: #ffffff;
        --profile-border: #d9e5f2;
        --profile-border-soft: #e8eef6;
        --profile-text: #111827;
        --profile-muted: #64748b;
        --profile-primary: #1697e5;
        --profile-primary-dark: #0d86d1;
        --profile-primary-soft: #e6f5ff;
        --profile-success: #10b981;
        --profile-success-soft: #dcfce7;
        --profile-warning: #f59e0b;
        --profile-shadow: 0 16px 40px rgba(15, 23, 42, 0.05);
        --profile-radius-xl: 28px;
    }

    body {
        margin: 0;
        background:
            radial-gradient(circle at top left, rgba(255, 255, 255, 0.68) 0, rgba(255, 255, 255, 0) 24rem),
            radial-gradient(circle at top right, rgba(255, 255, 255, 0.58) 0, rgba(255, 255, 255, 0) 18rem),
            linear-gradient(180deg, #8ad0ff 0%, #c7e8ff 36%, #edf7ff 100%);
        color: var(--profile-text);
        font-family: 'Be Vietnam Pro', sans-serif;
    }

    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24;
    }

    .profile-page {
        min-height: 100vh;
        background: transparent;
    }

    .profile-shell {
        max-width: 1280px;
        margin: 0 auto;
        padding: 36px 24px 48px;
    }

    .profile-hero-grid {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(0, 1.55fr);
        gap: 24px;
        align-items: stretch;
    }

    .profile-card {
        background: var(--profile-surface);
        border: 1px solid var(--profile-border);
        border-radius: var(--profile-radius-xl);
        box-shadow: var(--profile-shadow);
    }

    .profile-hero-card {
        padding: 34px 38px;
        display: flex;
        align-items: center;
        gap: 28px;
        min-height: 236px;
    }

    .profile-avatar-shell {
        position: relative;
        flex: 0 0 auto;
    }

    .profile-avatar-large {
        width: 142px;
        height: 142px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0f9be9 0%, #38bdf8 100%);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 42px;
        font-weight: 800;
        border: 4px solid #fff;
        box-shadow: 0 18px 34px rgba(14, 165, 233, 0.18);
        overflow: hidden;
    }

    .profile-avatar-large img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-verified-badge {
        position: absolute;
        right: 6px;
        bottom: 8px;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: 4px solid #fff;
        background: var(--profile-primary);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 22px rgba(22, 151, 229, 0.25);
    }

    .profile-verified-badge .material-symbols-outlined {
        font-size: 18px;
        font-variation-settings: 'FILL' 1, 'wght' 700, 'GRAD' 0, 'opsz' 24;
    }

    .profile-hero-copy {
        min-width: 0;
        flex: 1;
    }

    .profile-hero-copy h1 {
        margin: 0;
        font-size: 24px;
        line-height: 1.2;
        font-weight: 800;
    }

    .profile-hero-copy p {
        margin: 8px 0 0;
        font-size: 16px;
        color: var(--profile-muted);
    }

    .profile-pill-row {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
    }

    .profile-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 700;
    }

    .profile-pill--success {
        background: var(--profile-success-soft);
        color: #0f9b62;
    }

    .profile-pill--muted {
        background: #eef4fa;
        color: #526173;
    }

    .profile-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 22px;
    }

    .profile-btn,
    .profile-btn-secondary,
    .profile-btn-light,
    .profile-link-btn {
        border: 0;
        border-radius: 999px;
        font-family: inherit;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease, color 0.2s ease;
    }

    .profile-btn {
        min-height: 48px;
        padding: 0 22px;
        background: linear-gradient(135deg, var(--profile-primary) 0%, #30b4ff 100%);
        color: #fff;
        box-shadow: 0 16px 24px rgba(22, 151, 229, 0.2);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .profile-btn:hover,
    .profile-btn:focus-visible {
        transform: translateY(-1px);
        background: linear-gradient(135deg, var(--profile-primary-dark) 0%, #1da4f0 100%);
        color: #fff;
    }

    .profile-btn-secondary {
        min-height: 48px;
        padding: 0 20px;
        background: #eef5fb;
        color: #2f445c;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .profile-btn-secondary:hover,
    .profile-btn-light:hover {
        transform: translateY(-1px);
        background: #e1edf8;
        color: var(--profile-primary);
    }

    .profile-btn-light {
        min-height: 44px;
        padding: 0 16px;
        background: #eef6fd;
        color: var(--profile-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .profile-link-btn {
        background: transparent;
        color: var(--profile-primary);
        padding: 0;
    }

    .profile-link-btn:hover {
        color: var(--profile-primary-dark);
    }

    .profile-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    .profile-stat-card {
        min-height: 236px;
        padding: 22px 22px 26px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .profile-stat-card h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #506176;
    }

    .profile-stat-foot {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 12px;
    }

    .profile-stat-value {
        font-size: 48px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.04em;
    }

    .profile-stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f4f8fc;
    }

    .profile-stat-icon .material-symbols-outlined {
        font-size: 30px;
    }

    .profile-stat-card--blue .profile-stat-value,
    .profile-stat-card--blue .profile-stat-icon {
        color: var(--profile-primary);
    }

    .profile-stat-card--amber .profile-stat-value,
    .profile-stat-card--amber .profile-stat-icon {
        color: var(--profile-warning);
    }

    .profile-stat-card--green .profile-stat-value,
    .profile-stat-card--green .profile-stat-icon {
        color: var(--profile-success);
    }

    .profile-content-grid {
        margin-top: 34px;
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.65fr);
        gap: 34px;
        align-items: start;
    }

    .profile-stack {
        display: grid;
        gap: 28px;
    }

    .profile-panel {
        overflow: hidden;
    }

    .profile-panel__header {
        padding: 26px 28px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        border-bottom: 1px solid var(--profile-border-soft);
    }

    .profile-panel__heading {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .profile-panel__icon {
        width: 38px;
        height: 38px;
        border-radius: 14px;
        background: var(--profile-primary-soft);
        color: var(--profile-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .profile-panel__icon .material-symbols-outlined {
        font-size: 20px;
        font-variation-settings: 'FILL' 1, 'wght' 600, 'GRAD' 0, 'opsz' 24;
    }

    .profile-panel__title {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
    }

    .profile-panel__subtitle {
        margin: 4px 0 0;
        color: var(--profile-muted);
        font-size: 14px;
    }

    .profile-panel__body {
        padding: 28px;
    }

    .profile-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 22px 24px;
    }

    .profile-field {
        display: grid;
        gap: 10px;
    }

    .profile-field label {
        font-size: 14px;
        font-weight: 700;
        color: #1f2937;
    }

    .profile-input-shell {
        position: relative;
    }

    .profile-input-shell .material-symbols-outlined {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 22px;
    }

    .profile-input,
    .profile-textarea,
    .profile-select {
        width: 100%;
        border: 1px solid var(--profile-border);
        background: #fff;
        border-radius: 18px;
        padding: 0 18px;
        min-height: 58px;
        font-size: 16px;
        color: var(--profile-text);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .profile-input[readonly] {
        background: #f8fbfe;
        color: #6b7280;
    }

    .profile-textarea {
        min-height: 112px;
        padding: 16px 18px;
        resize: vertical;
    }

    .profile-select {
        appearance: none;
        background-image:
            linear-gradient(45deg, transparent 50%, #8aa0b8 50%),
            linear-gradient(135deg, #8aa0b8 50%, transparent 50%);
        background-position:
            calc(100% - 22px) calc(50% - 3px),
            calc(100% - 16px) calc(50% - 3px);
        background-size: 6px 6px, 6px 6px;
        background-repeat: no-repeat;
        padding-right: 42px;
    }

    .profile-input:focus,
    .profile-textarea:focus,
    .profile-select:focus {
        outline: none;
        border-color: rgba(22, 151, 229, 0.55);
        box-shadow: 0 0 0 4px rgba(22, 151, 229, 0.12);
    }

    .profile-field-hint {
        margin: -2px 0 0;
        color: var(--profile-muted);
        font-size: 13px;
        line-height: 1.5;
    }

    .profile-address-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        margin-top: 18px;
    }

    .profile-address-preview {
        margin-top: 18px;
        border: 1px dashed rgba(22, 151, 229, 0.24);
        border-radius: 18px;
        background: #f8fbff;
        padding: 16px 18px;
    }

    .profile-address-preview strong {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .profile-address-preview p {
        margin: 0;
        color: var(--profile-muted);
        font-size: 14px;
        line-height: 1.6;
    }

    .profile-address-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .profile-address-note {
        font-size: 14px;
        color: var(--profile-muted);
        margin: 0;
    }

    .saved-addresses {
        display: grid;
        gap: 16px;
        margin-top: 24px;
    }

    .saved-address-card {
        display: flex;
        align-items: center;
        gap: 18px;
        padding: 22px 20px;
        border-radius: 22px;
        border: 1px solid var(--profile-border-soft);
        background: #fff;
    }

    .saved-address-card.is-primary {
        background: linear-gradient(180deg, #f3faff 0%, #eff7ff 100%);
        border-color: rgba(22, 151, 229, 0.24);
    }

    .saved-address-card.is-empty {
        border-style: dashed;
        background: #fbfdff;
    }

    .saved-address-icon {
        width: 46px;
        height: 46px;
        border-radius: 16px;
        background: #d9eefc;
        color: var(--profile-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .saved-address-card.is-empty .saved-address-icon {
        background: #eef4fa;
        color: #94a3b8;
    }

    .saved-address-copy {
        min-width: 0;
        flex: 1;
    }

    .saved-address-title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 18px;
        font-weight: 800;
    }

    .saved-address-tag {
        padding: 4px 10px;
        border-radius: 999px;
        background: var(--profile-primary);
        color: #fff;
        font-size: 12px;
        font-weight: 800;
    }

    .saved-address-copy p {
        margin: 6px 0 0;
        color: var(--profile-muted);
        font-size: 16px;
        line-height: 1.5;
    }

    .saved-address-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 0 0 auto;
    }

    .saved-address-btn {
        width: 40px;
        height: 40px;
        border: 0;
        border-radius: 12px;
        background: rgba(148, 163, 184, 0.14);
        color: #8192a8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s ease, background-color 0.2s ease, color 0.2s ease;
    }

    .saved-address-btn:hover {
        transform: translateY(-1px);
        background: rgba(22, 151, 229, 0.12);
        color: var(--profile-primary);
    }

    .saved-address-btn[disabled] {
        cursor: not-allowed;
        opacity: 0.55;
    }

    .password-meter {
        display: grid;
        gap: 10px;
        margin-top: 12px;
    }

    .password-meter__bars {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 8px;
    }

    .password-meter__segment {
        height: 4px;
        border-radius: 999px;
        background: #dbe4ef;
        transition: background-color 0.2s ease, opacity 0.2s ease;
    }

    .password-meter__label {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: #64748b;
        text-transform: uppercase;
    }

    .profile-submit-btn {
        width: 100%;
        min-height: 52px;
        margin-top: 22px;
    }

    .profile-setting-list {
        display: grid;
        gap: 22px;
    }

    .profile-setting-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .profile-setting-copy h4 {
        margin: 0;
        font-size: 15px;
        font-weight: 800;
    }

    .profile-setting-copy p {
        margin: 6px 0 0;
        color: var(--profile-muted);
        font-size: 14px;
    }

    .profile-switch {
        position: relative;
        width: 52px;
        height: 30px;
        flex: 0 0 auto;
    }

    .profile-switch input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .profile-switch__slider {
        position: absolute;
        inset: 0;
        border-radius: 999px;
        background: #d7dee9;
        transition: background-color 0.2s ease;
    }

    .profile-switch__slider::before {
        content: '';
        position: absolute;
        top: 4px;
        left: 4px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.16);
        transition: transform 0.2s ease;
    }

    .profile-switch input:checked + .profile-switch__slider {
        background: var(--profile-primary);
    }

    .profile-switch input:checked + .profile-switch__slider::before {
        transform: translateX(22px);
    }

    .profile-panel--danger {
        background: linear-gradient(180deg, #fff7f7 0%, #fff1f2 100%);
        border-color: rgba(239, 68, 68, 0.18);
    }

    .profile-danger-title {
        margin: 0;
        color: #dc2626;
        font-size: 18px;
        font-weight: 800;
    }

    .profile-danger-copy {
        margin: 10px 0 18px;
        color: #7c5261;
        font-size: 15px;
        line-height: 1.6;
    }

    .profile-danger-link {
        border: 0;
        background: transparent;
        color: #dc2626;
        font-size: 16px;
        font-weight: 800;
        padding: 0;
        cursor: pointer;
    }

    .profile-danger-link:hover {
        color: #b91c1c;
    }

    .profile-footer {
        max-width: 1280px;
        margin: 0 auto;
        padding: 16px 24px 40px;
        color: var(--profile-muted);
        font-size: 14px;
        text-align: center;
    }

    .is-hidden {
        display: none !important;
    }

    @media (max-width: 1180px) {
        .profile-hero-grid,
        .profile-content-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 920px) {
        .profile-hero-card {
            flex-direction: column;
            align-items: flex-start;
        }

        .profile-form-grid {
            grid-template-columns: 1fr;
        }

        .profile-address-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 720px) {
        .profile-shell {
            padding-left: 16px;
            padding-right: 16px;
        }

        .profile-footer {
            padding-left: 16px;
            padding-right: 16px;
        }

        .profile-panel__header,
        .profile-panel__body,
        .profile-hero-card {
            padding-left: 20px;
            padding-right: 20px;
        }

        .profile-stats-grid {
            grid-template-columns: 1fr;
        }

        .profile-stat-card {
            min-height: 160px;
        }

        .saved-address-card {
            flex-wrap: wrap;
        }

        .saved-address-actions {
            width: 100%;
            justify-content: flex-end;
        }
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="profile-page">
    <main class="profile-shell">
        <section class="profile-hero-grid">
            <article class="profile-card profile-hero-card">
                <div class="profile-avatar-shell">
                    <div class="profile-avatar-large" id="profileAvatarFallback">U</div>
                    <div class="profile-avatar-large is-hidden" id="profileAvatarImageWrap">
                        <img id="profileAvatar" alt="Ảnh đại diện">
                    </div>
                    <div class="profile-verified-badge" title="Trạng thái xác minh tài khoản">
                        <span class="material-symbols-outlined">verified</span>
                    </div>
                </div>

                <div class="profile-hero-copy">
                    <h1 id="heroUserName">Đang tải...</h1>
                    <p id="heroUserMeta">Thành viên từ --</p>

                    <div class="profile-pill-row">
                        <span class="profile-pill profile-pill--success" id="verificationBadge">Tài khoản xác thực</span>
                        <span class="profile-pill profile-pill--muted" id="heroUserEmail">--</span>
                    </div>

                    <div class="profile-hero-actions">
                        <button class="profile-btn" type="button" id="triggerAvatarUploadBtn">
                            <span class="material-symbols-outlined">photo_camera</span>
                            Cập nhật ảnh đại diện
                        </button>
                        <input type="file" id="avatarInput" class="is-hidden" accept="image/jpeg,image/png,image/jpg,image/webp,image/gif">
                        <button class="profile-btn-secondary" type="button" id="focusPersonalInfoBtn">
                            <span class="material-symbols-outlined">edit_square</span>
                            Chỉnh sửa hồ sơ
                        </button>
                    </div>
                </div>
            </article>

            <div class="profile-stats-grid">
                <article class="profile-card profile-stat-card profile-stat-card--blue">
                    <h3>Tổng đặt lịch</h3>
                    <div class="profile-stat-foot">
                        <div class="profile-stat-value" id="statTotalBookings">0</div>
                        <div class="profile-stat-icon">
                            <span class="material-symbols-outlined">calendar_month</span>
                        </div>
                    </div>
                </article>

                <article class="profile-card profile-stat-card profile-stat-card--amber">
                    <h3>Đang xử lý</h3>
                    <div class="profile-stat-foot">
                        <div class="profile-stat-value" id="statProcessing">0</div>
                        <div class="profile-stat-icon">
                            <span class="material-symbols-outlined">hourglass_top</span>
                        </div>
                    </div>
                </article>

                <article class="profile-card profile-stat-card profile-stat-card--green">
                    <h3>Đánh giá</h3>
                    <div class="profile-stat-foot">
                        <div class="profile-stat-value" id="statReviews">0</div>
                        <div class="profile-stat-icon">
                            <span class="material-symbols-outlined">star</span>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="profile-content-grid">
            <div class="profile-stack">
                <article class="profile-card profile-panel">
                    <div class="profile-panel__header">
                        <div class="profile-panel__heading">
                            <div class="profile-panel__icon">
                                <span class="material-symbols-outlined">person</span>
                            </div>
                            <div>
                                <h2 class="profile-panel__title">Thông tin cá nhân</h2>
                                <p class="profile-panel__subtitle">Cập nhật thông tin cơ bản hiển thị trên tài khoản của bạn.</p>
                            </div>
                        </div>
                        <button class="profile-link-btn" type="submit" form="personalInfoForm" id="savePersonalInfoBtn">Lưu thay đổi</button>
                    </div>

                    <div class="profile-panel__body">
                        <form id="personalInfoForm">
                            <div class="profile-form-grid">
                                <div class="profile-field">
                                    <label for="infoNameInput">Họ và Tên</label>
                                    <input class="profile-input" id="infoNameInput" type="text" placeholder="Nguyễn Văn A">
                                </div>

                                <div class="profile-field">
                                    <label for="infoEmailInput">Email Address</label>
                                    <input class="profile-input" id="infoEmailInput" type="email" placeholder="nguyen.vana@example.com">
                                </div>

                                <div class="profile-field">
                                    <label for="infoPhoneInput">Số điện thoại</label>
                                    <input class="profile-input" id="infoPhoneInput" type="text" placeholder="+84 987 654 321">
                                </div>

                                <div class="profile-field">
                                    <label for="memberSinceInput">Ngày tham gia</label>
                                    <div class="profile-input-shell">
                                        <input class="profile-input" id="memberSinceInput" type="text" readonly>
                                        <span class="material-symbols-outlined">calendar_today</span>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>

                <article class="profile-card profile-panel">
                    <div class="profile-panel__header">
                        <div class="profile-panel__heading">
                            <div class="profile-panel__icon">
                                <span class="material-symbols-outlined">location_on</span>
                            </div>
                            <div>
                                <h2 class="profile-panel__title">Địa chỉ đã lưu</h2>
                                <p class="profile-panel__subtitle">Lưu địa chỉ mặc định để đặt lịch nhanh hơn ở lần tiếp theo.</p>
                            </div>
                        </div>
                        <button class="profile-btn-light" type="button" id="focusAddressBtn">
                            <span class="material-symbols-outlined">add</span>
                            Thêm mới
                        </button>
                    </div>

                    <div class="profile-panel__body">
                        <form id="addressForm">
                            <div class="profile-address-toolbar">
                                <p class="profile-address-note">Bạn hiện có một địa chỉ mặc định được dùng cho các lịch sửa tại nhà.</p>
                            </div>

                            <div class="profile-field">
                                <label for="addressInput">Số nhà, tên đường</label>
                                <textarea class="profile-textarea" id="addressInput" placeholder="123 Đường Trần Phú"></textarea>
                                <p class="profile-field-hint">Phần tỉnh/thành, quận/huyện, phường/xã sẽ lấy từ dữ liệu địa giới Việt Nam để bạn chọn.</p>
                            </div>

                            <div class="profile-address-grid">
                                <div class="profile-field">
                                    <label for="provinceSelect">Tỉnh / Thành phố</label>
                                    <select class="profile-select" id="provinceSelect">
                                        <option value="">Đang tải tỉnh / thành...</option>
                                    </select>
                                </div>

                                <div class="profile-field">
                                    <label for="districtSelect">Quận / Huyện</label>
                                    <select class="profile-select" id="districtSelect" disabled>
                                        <option value="">Chọn tỉnh / thành trước</option>
                                    </select>
                                </div>

                                <div class="profile-field">
                                    <label for="wardSelect">Phường / Xã</label>
                                    <select class="profile-select" id="wardSelect" disabled>
                                        <option value="">Chọn quận / huyện trước</option>
                                    </select>
                                </div>
                            </div>

                            <div class="profile-address-preview">
                                <strong>Địa chỉ hoàn chỉnh</strong>
                                <p id="fullAddressPreview">Chưa chọn đủ thông tin địa chỉ.</p>
                            </div>

                            <button class="profile-btn profile-submit-btn" type="submit" id="addressSubmitBtn">
                                <span class="material-symbols-outlined">pin_drop</span>
                                Lưu địa chỉ
                            </button>
                        </form>

                        <div class="saved-addresses">
                            <article class="saved-address-card is-primary" id="savedAddressCard">
                                <div class="saved-address-icon">
                                    <span class="material-symbols-outlined">home</span>
                                </div>
                                <div class="saved-address-copy">
                                    <div class="saved-address-title">
                                        Nhà riêng
                                        <span class="saved-address-tag">Mặc định</span>
                                    </div>
                                    <p id="savedAddressText">Chưa cập nhật địa chỉ mặc định.</p>
                                </div>
                                <div class="saved-address-actions">
                                    <button class="saved-address-btn" type="button" id="editAddressBtn" title="Chỉnh sửa địa chỉ">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button class="saved-address-btn" type="button" id="removeAddressBtn" title="Xóa địa chỉ" disabled>
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </div>
                            </article>

                            <article class="saved-address-card is-empty" id="savedAddressEmptyCard">
                                <div class="saved-address-icon">
                                    <span class="material-symbols-outlined">work</span>
                                </div>
                                <div class="saved-address-copy">
                                    <div class="saved-address-title">Địa chỉ phụ</div>
                                    <p>Hệ thống hiện hỗ trợ một địa chỉ mặc định. Bạn có thể cập nhật địa chỉ này bất cứ lúc nào ở ô phía trên.</p>
                                </div>
                            </article>
                        </div>
                    </div>
                </article>
            </div>

            <div class="profile-stack">
                <article class="profile-card profile-panel">
                    <div class="profile-panel__header">
                        <div class="profile-panel__heading">
                            <div class="profile-panel__icon">
                                <span class="material-symbols-outlined">shield_lock</span>
                            </div>
                            <div>
                                <h2 class="profile-panel__title">Bảo mật</h2>
                                <p class="profile-panel__subtitle">Đổi mật khẩu định kỳ để giữ an toàn cho tài khoản của bạn.</p>
                            </div>
                        </div>
                    </div>

                    <div class="profile-panel__body">
                        <form id="passwordForm">
                            <div class="profile-field">
                                <label for="currentPassword">Mật khẩu hiện tại</label>
                                <div class="profile-input-shell">
                                    <input class="profile-input" id="currentPassword" type="password" placeholder="••••••••">
                                    <button class="saved-address-btn" type="button" data-password-target="currentPassword" style="position:absolute; top:9px; right:10px;">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                </div>
                            </div>

                            <div class="profile-field" style="margin-top: 18px;">
                                <label for="newPassword">Mật khẩu mới</label>
                                <div class="profile-input-shell">
                                    <input class="profile-input" id="newPassword" type="password" placeholder="Ít nhất 6 ký tự">
                                    <button class="saved-address-btn" type="button" data-password-target="newPassword" style="position:absolute; top:9px; right:10px;">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                </div>

                                <div class="password-meter">
                                    <div class="password-meter__bars">
                                        <span class="password-meter__segment" data-strength-segment></span>
                                        <span class="password-meter__segment" data-strength-segment></span>
                                        <span class="password-meter__segment" data-strength-segment></span>
                                        <span class="password-meter__segment" data-strength-segment></span>
                                    </div>
                                    <div class="password-meter__label" id="passwordStrengthLabel">Độ mạnh mật khẩu: chưa có</div>
                                </div>
                            </div>

                            <div class="profile-field" style="margin-top: 18px;">
                                <label for="newPasswordConfirmation">Xác nhận mật khẩu mới</label>
                                <div class="profile-input-shell">
                                    <input class="profile-input" id="newPasswordConfirmation" type="password" placeholder="Nhập lại mật khẩu mới">
                                    <button class="saved-address-btn" type="button" data-password-target="newPasswordConfirmation" style="position:absolute; top:9px; right:10px;">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                </div>
                            </div>

                            <button class="profile-btn profile-submit-btn" type="submit" id="passwordSubmitBtn">
                                <span class="material-symbols-outlined">lock_reset</span>
                                Đổi mật khẩu
                            </button>
                        </form>
                    </div>
                </article>

                <article class="profile-card profile-panel">
                    <div class="profile-panel__header">
                        <div class="profile-panel__heading">
                            <div class="profile-panel__icon">
                                <span class="material-symbols-outlined">tune</span>
                            </div>
                            <div>
                                <h2 class="profile-panel__title">Cài đặt nhanh</h2>
                                <p class="profile-panel__subtitle">Các tùy chọn này được lưu ngay trên trình duyệt hiện tại.</p>
                            </div>
                        </div>
                    </div>

                    <div class="profile-panel__body">
                        <div class="profile-setting-list">
                            <div class="profile-setting-item">
                                <div class="profile-setting-copy">
                                    <h4>Thông báo Email</h4>
                                    <p>Cập nhật về đơn hàng và thay đổi trạng thái lịch sửa.</p>
                                </div>
                                <label class="profile-switch">
                                    <input type="checkbox" id="emailUpdatesToggle">
                                    <span class="profile-switch__slider"></span>
                                </label>
                            </div>

                            <div class="profile-setting-item">
                                <div class="profile-setting-copy">
                                    <h4>Thông báo Đẩy</h4>
                                    <p>Nhận thông báo nhanh trên trình duyệt của thiết bị này.</p>
                                </div>
                                <label class="profile-switch">
                                    <input type="checkbox" id="pushUpdatesToggle">
                                    <span class="profile-switch__slider"></span>
                                </label>
                            </div>

                            <div class="profile-setting-item">
                                <div class="profile-setting-copy">
                                    <h4>Tin nhắn khuyến mãi</h4>
                                    <p>Nhận ưu đãi theo mùa và khuyến mãi dành riêng cho khách hàng cũ.</p>
                                </div>
                                <label class="profile-switch">
                                    <input type="checkbox" id="promoUpdatesToggle">
                                    <span class="profile-switch__slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="profile-card profile-panel profile-panel--danger">
                    <div class="profile-panel__body">
                        <h2 class="profile-danger-title">Vùng nguy hiểm</h2>
                        <p class="profile-danger-copy">Xóa tài khoản và toàn bộ dữ liệu là hành động không thể hoàn tác. Hiện tại hệ thống chưa mở API xóa tài khoản tự phục vụ.</p>
                        <button class="profile-danger-link" type="button" id="dangerActionBtn">Xóa tài khoản</button>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <footer class="profile-footer">
        © 2024 Thợ Tốt NTU. Nền tảng kết nối thợ chuyên nghiệp.
    </footer>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/customer/profile.js') }}?v={{ time() }}"></script>
@endpush
