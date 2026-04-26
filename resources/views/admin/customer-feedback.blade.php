@extends('layouts.app')

@section('title', 'Feedback và khiếu nại - Admin')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap');

    :root {
        --feedback-page-bg: #f3f4f8;
        --feedback-panel-bg: rgba(255, 255, 255, 0.96);
        --feedback-panel-border: rgba(15, 23, 42, 0.08);
        --feedback-text: #0f172a;
        --feedback-muted: #64748b;
        --feedback-soft: #94a3b8;
        --feedback-primary: #2a72ff;
        --feedback-primary-soft: rgba(42, 114, 255, 0.1);
        --feedback-danger: #ef5a4c;
        --feedback-danger-soft: rgba(239, 90, 76, 0.12);
        --feedback-success: #11956a;
        --feedback-success-soft: rgba(17, 149, 106, 0.12);
        --feedback-warning: #f59e0b;
        --feedback-warning-soft: rgba(245, 158, 11, 0.14);
        --feedback-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    }

    body {
        background:
            radial-gradient(circle at top left, rgba(42, 114, 255, 0.12), transparent 24%),
            radial-gradient(circle at right center, rgba(239, 90, 76, 0.08), transparent 18%),
            var(--feedback-page-bg);
        color: var(--feedback-text);
        font-family: 'Manrope', sans-serif;
    }

    .feedback-admin-page {
        min-height: calc(100vh - 6.5rem);
    }

    .feedback-admin-shell {
        display: grid;
        gap: 22px;
    }

    .feedback-admin-hero,
    .feedback-admin-toolbar-panel,
    .feedback-admin-panel {
        background: var(--feedback-panel-bg);
        border: 1px solid var(--feedback-panel-border);
        border-radius: 28px;
        box-shadow: var(--feedback-shadow);
    }

    .feedback-admin-hero {
        padding: 28px 30px 26px;
    }

    .feedback-admin-hero__top {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
    }

    .feedback-admin-kicker {
        margin: 0 0 10px;
        color: var(--feedback-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.18em;
        text-transform: uppercase;
    }

    .feedback-admin-title {
        margin: 0;
        font-family: 'Space Grotesk', sans-serif;
        font-size: clamp(2rem, 2.4vw, 3rem);
        line-height: 1;
        font-weight: 700;
        letter-spacing: -0.05em;
    }

    .feedback-admin-subtitle {
        margin: 12px 0 0;
        max-width: 760px;
        color: var(--feedback-muted);
        font-size: 0.95rem;
        line-height: 1.72;
    }

    .feedback-admin-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: flex-end;
    }

    .feedback-admin-button,
    .feedback-admin-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        padding: 0 18px;
        border-radius: 16px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        background: #fff;
        color: var(--feedback-text);
        font-size: 0.88rem;
        font-weight: 700;
        text-decoration: none;
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .feedback-admin-button:hover,
    .feedback-admin-link:hover {
        transform: translateY(-1px);
        border-color: rgba(42, 114, 255, 0.16);
        box-shadow: 0 14px 28px rgba(42, 114, 255, 0.08);
    }

    .feedback-admin-button {
        cursor: pointer;
    }

    .feedback-admin-button:disabled {
        cursor: not-allowed;
        opacity: 0.65;
        transform: none;
        box-shadow: none;
    }

    .feedback-admin-button--primary {
        background: linear-gradient(135deg, #2a72ff, #538dff);
        border-color: transparent;
        color: #fff;
    }

    .feedback-admin-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 14px;
        margin-top: 24px;
    }

    .feedback-admin-stat {
        padding: 18px 18px 16px;
        border-radius: 22px;
        background: rgba(247, 248, 251, 0.95);
        border: 1px solid rgba(15, 23, 42, 0.06);
    }

    .feedback-admin-stat__label {
        display: block;
        color: var(--feedback-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .feedback-admin-stat__value {
        display: block;
        margin-top: 10px;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.85rem;
        line-height: 1;
        font-weight: 700;
    }

    .feedback-admin-stat__meta {
        display: block;
        margin-top: 8px;
        color: var(--feedback-muted);
        font-size: 0.82rem;
        line-height: 1.5;
    }

    .feedback-admin-toolbar-panel {
        padding: 18px;
    }

    .feedback-admin-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }

    .feedback-admin-field {
        display: grid;
        gap: 7px;
        flex: 1 1 220px;
        min-width: 0;
    }

    .feedback-admin-field--search {
        flex-basis: 320px;
    }

    .feedback-admin-field__label {
        color: var(--feedback-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .feedback-admin-input,
    .feedback-admin-select {
        min-height: 48px;
        width: 100%;
        padding: 0 16px;
        border-radius: 16px;
        border: 1px solid rgba(15, 23, 42, 0.1);
        background: #f8fafc;
        color: var(--feedback-text);
        font-size: 0.94rem;
        outline: none;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .feedback-admin-input:focus,
    .feedback-admin-select:focus {
        border-color: rgba(42, 114, 255, 0.28);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(42, 114, 255, 0.08);
    }

    .feedback-admin-main {
        display: grid;
        grid-template-columns: minmax(300px, 360px) minmax(0, 1fr);
        gap: 22px;
        align-items: start;
    }

    .feedback-admin-panel {
        overflow: hidden;
    }

    .feedback-admin-panel__head {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
        padding: 22px 24px 18px;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    }

    .feedback-admin-panel__title {
        margin: 0;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.15rem;
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .feedback-admin-panel__copy {
        margin: 8px 0 0;
        color: var(--feedback-muted);
        font-size: 0.86rem;
        line-height: 1.6;
    }

    .feedback-admin-list-panel {
        position: sticky;
        top: 84px;
    }

    .feedback-admin-list {
        display: grid;
        gap: 12px;
        max-height: calc(100vh - 280px);
        padding: 18px;
        overflow: auto;
    }

    .feedback-admin-item {
        display: grid;
        gap: 14px;
        padding: 18px;
        border-radius: 24px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        background: #fbfcfe;
        cursor: pointer;
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .feedback-admin-item:hover,
    .feedback-admin-item.is-selected {
        transform: translateY(-1px);
        border-color: rgba(42, 114, 255, 0.18);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(242, 247, 255, 0.96));
        box-shadow: 0 20px 36px rgba(42, 114, 255, 0.08);
    }

    .feedback-admin-item__top,
    .feedback-case-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
    }

    .feedback-admin-item__eyebrow {
        color: var(--feedback-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .feedback-admin-item__title {
        margin-top: 6px;
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.45;
    }

    .feedback-admin-item__meta,
    .feedback-admin-item__summary {
        color: var(--feedback-muted);
        font-size: 0.84rem;
        line-height: 1.7;
    }

    .feedback-admin-badges {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .feedback-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 31px;
        padding: 0 12px;
        border-radius: 999px;
        font-size: 0.73rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .feedback-badge--info {
        background: rgba(42, 114, 255, 0.12);
        color: #2a72ff;
    }

    .feedback-badge--success {
        background: var(--feedback-success-soft);
        color: var(--feedback-success);
    }

    .feedback-badge--warning {
        background: var(--feedback-warning-soft);
        color: #b97707;
    }

    .feedback-badge--danger {
        background: var(--feedback-danger-soft);
        color: var(--feedback-danger);
    }

    .feedback-badge--muted {
        background: rgba(148, 163, 184, 0.16);
        color: #64748b;
    }

    .feedback-admin-preview {
        min-height: 720px;
        padding: 26px;
    }

    .feedback-admin-empty {
        display: grid;
        place-items: center;
        min-height: 280px;
        padding: 36px 28px;
        border-radius: 24px;
        border: 1px dashed rgba(148, 163, 184, 0.34);
        background: linear-gradient(180deg, rgba(248, 250, 252, 0.9), rgba(255, 255, 255, 0.96));
        color: var(--feedback-muted);
        text-align: center;
        line-height: 1.8;
    }

    .feedback-case-layout {
        display: grid;
        gap: 18px;
    }

    .feedback-case-header__copy {
        display: grid;
        gap: 10px;
    }

    .feedback-case-kicker {
        color: var(--feedback-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .feedback-case-title {
        margin: 0;
        font-family: 'Space Grotesk', sans-serif;
        font-size: clamp(1.9rem, 2vw, 2.5rem);
        line-height: 1;
        font-weight: 700;
        letter-spacing: -0.05em;
    }

    .feedback-case-subtitle {
        margin: 0;
        color: var(--feedback-muted);
        font-size: 0.94rem;
        line-height: 1.7;
    }

    .feedback-case-header__side {
        display: grid;
        gap: 10px;
        justify-items: end;
    }

    .feedback-case-header__meta {
        color: var(--feedback-muted);
        font-size: 0.84rem;
        line-height: 1.6;
        text-align: right;
    }

    .feedback-case-links {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }

    .feedback-case-link {
        display: inline-flex;
        align-items: center;
        min-height: 34px;
        padding: 0 12px;
        border-radius: 999px;
        background: #f4f7fb;
        border: 1px solid rgba(15, 23, 42, 0.06);
        color: var(--feedback-text);
        font-size: 0.76rem;
        font-weight: 700;
        text-decoration: none;
    }

    .feedback-case-link:hover {
        border-color: rgba(42, 114, 255, 0.16);
        color: #2a72ff;
    }

    .feedback-case-card {
        padding: 22px 24px;
        border-radius: 28px;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.04);
    }

    .feedback-case-card--issue {
        position: relative;
        overflow: hidden;
        border-color: rgba(239, 90, 76, 0.16);
    }

    .feedback-case-card--issue::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        background: linear-gradient(180deg, #ef5a4c, #ff7f6e);
    }

    .feedback-case-card__eyebrow {
        color: var(--feedback-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }

    .feedback-case-card__headline {
        margin: 10px 0 0;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.36rem;
        line-height: 1.24;
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .feedback-case-card__copy {
        margin: 12px 0 0;
        color: var(--feedback-muted);
        font-size: 0.95rem;
        line-height: 1.82;
    }

    .feedback-case-media-grid,
    .feedback-case-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        margin-top: 20px;
    }

    .feedback-case-summary-grid {
        gap: 14px;
    }

    .feedback-case-media-section,
    .feedback-case-summary-item {
        padding: 18px;
        border-radius: 22px;
        background: #f8fafc;
        border: 1px solid rgba(15, 23, 42, 0.06);
    }

    .feedback-case-media-section__title,
    .feedback-case-summary-item__label,
    .feedback-case-field__label {
        display: block;
        color: var(--feedback-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .feedback-case-media-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        margin-top: 14px;
    }

    .feedback-case-media-thumb {
        display: grid;
        gap: 10px;
        text-decoration: none;
        color: inherit;
    }

    .feedback-case-media-thumb img,
    .feedback-case-media-thumb video {
        width: 100%;
        aspect-ratio: 16 / 11;
        border-radius: 18px;
        object-fit: cover;
        background: #e2e8f0;
    }

    .feedback-case-media-thumb figcaption {
        color: var(--feedback-muted);
        font-size: 0.76rem;
        line-height: 1.5;
    }

    .feedback-case-media-empty {
        margin-top: 14px;
        padding: 18px;
        border-radius: 18px;
        border: 1px dashed rgba(148, 163, 184, 0.34);
        color: var(--feedback-muted);
        font-size: 0.86rem;
        line-height: 1.7;
        text-align: center;
    }

    .feedback-case-summary-item__value {
        margin-top: 9px;
        color: var(--feedback-text);
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1.7;
    }

    .feedback-case-summary-item__value--muted {
        color: var(--feedback-muted);
        font-weight: 600;
    }

    .feedback-case-total {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: center;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid rgba(15, 23, 42, 0.08);
    }

    .feedback-case-total__label {
        color: var(--feedback-muted);
        font-size: 0.86rem;
        font-weight: 700;
    }

    .feedback-case-total__value {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.5rem;
        line-height: 1;
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .feedback-case-decision-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-top: 18px;
    }

    .feedback-case-decision {
        display: grid;
        gap: 8px;
        min-height: 108px;
        padding: 16px;
        border-radius: 22px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        background: #f8fafc;
        color: var(--feedback-text);
        text-align: left;
        cursor: pointer;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease, transform 0.18s ease;
    }

    .feedback-case-decision:hover {
        transform: translateY(-1px);
        border-color: rgba(42, 114, 255, 0.18);
        box-shadow: 0 16px 30px rgba(42, 114, 255, 0.08);
    }

    .feedback-case-decision:disabled {
        cursor: not-allowed;
        opacity: 0.68;
        transform: none;
        box-shadow: none;
    }

    .feedback-case-decision.is-active {
        background: linear-gradient(180deg, rgba(42, 114, 255, 0.12), rgba(255, 255, 255, 0.98));
        border-color: rgba(42, 114, 255, 0.28);
        box-shadow: 0 18px 30px rgba(42, 114, 255, 0.12);
    }

    .feedback-case-decision__title {
        font-size: 0.9rem;
        font-weight: 800;
        line-height: 1.4;
    }

    .feedback-case-decision__meta {
        color: var(--feedback-muted);
        font-size: 0.78rem;
        line-height: 1.55;
    }

    .feedback-case-field {
        display: grid;
        gap: 10px;
        margin-top: 18px;
    }

    .feedback-case-textarea {
        width: 100%;
        min-height: 144px;
        padding: 16px 18px;
        border-radius: 22px;
        border: 1px solid rgba(15, 23, 42, 0.1);
        background: #f8fafc;
        color: var(--feedback-text);
        font: inherit;
        line-height: 1.8;
        resize: vertical;
        outline: none;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .feedback-case-textarea:focus {
        border-color: rgba(42, 114, 255, 0.28);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(42, 114, 255, 0.08);
    }

    .feedback-case-note {
        margin-top: 16px;
        padding: 16px 18px;
        border-radius: 20px;
        font-size: 0.88rem;
        line-height: 1.7;
    }

    .feedback-case-note--success {
        background: var(--feedback-success-soft);
        color: #0c6b4c;
    }

    .feedback-case-note--info {
        background: rgba(42, 114, 255, 0.08);
        color: #255fd3;
    }

    .feedback-case-footer {
        display: flex;
        justify-content: flex-end;
        gap: 14px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 20px;
    }

    .feedback-case-footer__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: flex-end;
    }

    .feedback-case-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 48px;
        padding: 0 20px;
        border-radius: 16px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        background: #fff;
        color: var(--feedback-text);
        font-size: 0.9rem;
        font-weight: 800;
        cursor: pointer;
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .feedback-case-button:hover {
        transform: translateY(-1px);
        border-color: rgba(42, 114, 255, 0.16);
        box-shadow: 0 16px 30px rgba(42, 114, 255, 0.08);
    }

    .feedback-case-button:disabled {
        cursor: not-allowed;
        opacity: 0.65;
        transform: none;
        box-shadow: none;
    }

    .feedback-case-button--primary {
        border-color: transparent;
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: #fff;
    }

    .feedback-case-button--ghost {
        background: #f8fafc;
    }

    @media (max-width: 1279.98px) {
        .feedback-admin-main {
            grid-template-columns: 1fr;
        }

        .feedback-admin-list-panel {
            position: static;
        }

        .feedback-admin-list {
            max-height: none;
        }
    }

    @media (max-width: 991.98px) {
        .feedback-admin-hero {
            padding: 24px 22px 22px;
        }

        .feedback-admin-hero__top,
        .feedback-case-header {
            flex-direction: column;
        }

        .feedback-admin-actions,
        .feedback-case-links,
        .feedback-case-footer__actions {
            justify-content: flex-start;
        }

        .feedback-case-header__side {
            justify-items: start;
        }

        .feedback-case-header__meta {
            text-align: left;
        }

        .feedback-case-media-grid,
        .feedback-case-summary-grid,
        .feedback-case-decision-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .feedback-admin-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .feedback-admin-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .feedback-admin-field,
        .feedback-admin-field--search {
            flex-basis: auto;
        }

        .feedback-admin-preview {
            padding: 18px;
        }

        .feedback-case-card,
        .feedback-case-media-section,
        .feedback-case-summary-item,
        .feedback-admin-item {
            border-radius: 22px;
        }

        .feedback-case-footer {
            align-items: stretch;
        }

        .feedback-case-footer__actions,
        .feedback-case-button {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<section class="feedback-admin-page py-4">
    <div class="container-xxl">
        <div class="feedback-admin-shell" id="customerFeedbackApp">
            <section class="feedback-admin-hero">
                <div class="feedback-admin-hero__top">
                    <div>
                        <p class="feedback-admin-kicker">Chăm sóc khách hàng / Feedback desk</p>
                        <h1 class="feedback-admin-title">Feedback và khiếu nại</h1>
                        <p class="feedback-admin-subtitle">
                            Tổng hợp các đánh giá thấp, hủy đơn và khiếu nại sau sửa chữa. Chọn từng case để xem bằng chứng,
                            thông tin đơn liên quan và cập nhật hướng xử lý ngay trên panel chi tiết.
                        </p>
                    </div>

                    <div class="feedback-admin-actions">
                        <a class="feedback-admin-link" href="{{ route('admin.customers') }}">Mở danh sách khách hàng</a>
                        <button type="button" class="feedback-admin-button feedback-admin-button--primary" id="customerFeedbackRefreshButton">
                            Làm mới dữ liệu
                        </button>
                    </div>
                </div>

                <div class="feedback-admin-stats" id="customerFeedbackStats"></div>
            </section>

            <section class="feedback-admin-toolbar-panel">
                <div class="feedback-admin-toolbar">
                    <label class="feedback-admin-field feedback-admin-field--search">
                        <span class="feedback-admin-field__label">Tìm case</span>
                        <input
                            type="search"
                            class="feedback-admin-input"
                            id="customerFeedbackSearch"
                            placeholder="Tìm theo khách hàng, mã đơn, dịch vụ, ghi chú..."
                        >
                    </label>

                    <label class="feedback-admin-field">
                        <span class="feedback-admin-field__label">Loại case</span>
                        <select class="feedback-admin-select" id="customerFeedbackType">
                            <option value="">Tất cả loại</option>
                            <option value="low_rating">Đánh giá thấp</option>
                            <option value="cancellation">Hủy đơn</option>
                            <option value="customer_complaint">Khiếu nại</option>
                        </select>
                    </label>

                    <label class="feedback-admin-field">
                        <span class="feedback-admin-field__label">Trạng thái</span>
                        <select class="feedback-admin-select" id="customerFeedbackStatus">
                            <option value="">Tất cả trạng thái</option>
                            <option value="new">Mới</option>
                            <option value="in_progress">Đang xử lý</option>
                            <option value="resolved">Đã xử lý</option>
                        </select>
                    </label>
                </div>
            </section>

            <div class="feedback-admin-main">
                <section class="feedback-admin-panel feedback-admin-list-panel">
                    <div class="feedback-admin-panel__head">
                        <div>
                            <h2 class="feedback-admin-panel__title">Danh sách case</h2>
                            <p class="feedback-admin-panel__copy" id="customerFeedbackCaption">Đang tải dữ liệu...</p>
                        </div>
                    </div>

                    <div class="feedback-admin-list" id="customerFeedbackList"></div>
                </section>

                <aside class="feedback-admin-panel">
                    <div class="feedback-admin-preview" id="customerFeedbackPreview"></div>
                </aside>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/customer-feedback.js') }}"></script>
@endpush
