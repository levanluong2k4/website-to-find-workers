@extends('layouts.app')

@section('title', 'Khách hàng - Admin')

@push('styles')
<style>
    :root {
        --customer-admin-bg: #f8fafc;
        --customer-admin-panel: rgba(255, 255, 255, 0.92);
        --customer-admin-border: rgba(148, 163, 184, 0.18);
        --customer-admin-text: #0f172a;
        --customer-admin-muted: #64748b;
        --customer-admin-primary: #0284c7;
        --customer-admin-primary-soft: rgba(2, 132, 199, 0.08);
        --customer-admin-warn: #ea580c;
        --customer-admin-warn-soft: rgba(234, 88, 12, 0.1);
        --customer-admin-green: #0f9f7c;
        --customer-admin-green-soft: rgba(15, 159, 124, 0.1);
        --customer-admin-red: #dc2626;
        --customer-admin-red-soft: rgba(220, 38, 38, 0.1);
        --customer-admin-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    }

    body {
        background:
            radial-gradient(circle at top left, rgba(14, 165, 233, 0.12), transparent 24%),
            radial-gradient(circle at bottom right, rgba(249, 115, 22, 0.08), transparent 18%),
            var(--customer-admin-bg);
    }

    .customer-admin-shell {
        padding: 28px;
        border-radius: 30px;
        background: rgba(255, 255, 255, 0.76);
        border: 1px solid rgba(255, 255, 255, 0.75);
        backdrop-filter: blur(18px);
        box-shadow: var(--customer-admin-shadow);
    }

    .customer-admin-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
        margin-bottom: 20px;
    }

    .customer-admin-kicker {
        margin: 0 0 8px;
        color: var(--customer-admin-muted);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .customer-admin-title {
        margin: 0;
        color: var(--customer-admin-text);
        font-size: clamp(1.8rem, 2vw, 2.45rem);
        font-weight: 800;
        letter-spacing: -0.04em;
    }

    .customer-admin-subtitle {
        max-width: 760px;
        margin: 10px 0 0;
        color: var(--customer-admin-muted);
        font-size: 0.96rem;
        line-height: 1.6;
    }

    .customer-admin-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
        align-items: center;
    }

    .customer-admin-refresh,
    .customer-admin-toolbar .form-control,
    .customer-admin-toolbar .form-select {
        min-height: 44px;
        border-radius: 16px;
        border-color: rgba(148, 163, 184, 0.2);
        box-shadow: none;
    }

    .customer-admin-refresh {
        border: 0;
        padding: 0 16px;
        background: linear-gradient(135deg, #0284c7, #38bdf8);
        color: #fff;
        font-weight: 700;
        box-shadow: 0 16px 34px rgba(2, 132, 199, 0.2);
    }

    .customer-admin-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .customer-stat-card {
        padding: 18px;
        border-radius: 22px;
        background: var(--customer-admin-panel);
        border: 1px solid var(--customer-admin-border);
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
    }

    .customer-stat-card__label {
        display: block;
        color: var(--customer-admin-muted);
        font-size: 0.76rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .customer-stat-card__value {
        display: block;
        margin-top: 10px;
        color: var(--customer-admin-text);
        font-size: 1.9rem;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.04em;
    }

    .customer-stat-card__meta {
        display: block;
        margin-top: 8px;
        color: var(--customer-admin-muted);
        font-size: 0.82rem;
    }

    .customer-admin-main {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(320px, 0.9fr);
        gap: 18px;
        align-items: start;
    }

    .customer-admin-panel {
        border-radius: 26px;
        background: var(--customer-admin-panel);
        border: 1px solid var(--customer-admin-border);
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .customer-admin-panel__head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 18px 20px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.14);
    }

    .customer-admin-panel__title {
        margin: 0;
        color: var(--customer-admin-text);
        font-size: 1rem;
        font-weight: 800;
    }

    .customer-admin-panel__copy {
        margin: 6px 0 0;
        color: var(--customer-admin-muted);
        font-size: 0.84rem;
    }

    .customer-table-wrap {
        overflow: auto;
    }

    .customer-admin-table {
        width: 100%;
        border-collapse: collapse;
    }

    .customer-admin-table th {
        padding: 14px 20px;
        background: rgba(248, 250, 252, 0.92);
        color: var(--customer-admin-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .customer-admin-table td {
        padding: 16px 20px;
        border-top: 1px solid rgba(148, 163, 184, 0.12);
        color: var(--customer-admin-text);
        vertical-align: top;
    }

    .customer-admin-table tr {
        cursor: pointer;
        transition: background 0.18s ease;
    }

    .customer-admin-table tr:hover,
    .customer-admin-table tr.is-selected {
        background: rgba(2, 132, 199, 0.04);
    }

    .customer-cell-name {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-width: 220px;
    }

    .customer-avatar {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        flex: 0 0 46px;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(2, 132, 199, 0.14), rgba(56, 189, 248, 0.24));
        color: var(--customer-admin-primary);
        font-weight: 800;
        overflow: hidden;
    }

    .customer-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .customer-name {
        font-weight: 800;
    }

    .customer-subcopy {
        margin-top: 3px;
        color: var(--customer-admin-muted);
        font-size: 0.82rem;
        line-height: 1.45;
    }

    .customer-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.34rem 0.72rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .customer-pill--active_booking {
        background: var(--customer-admin-primary-soft);
        color: var(--customer-admin-primary);
    }

    .customer-pill--needs_attention {
        background: var(--customer-admin-red-soft);
        color: var(--customer-admin-red);
    }

    .customer-pill--new_customer {
        background: rgba(14, 165, 233, 0.12);
        color: #0369a1;
    }

    .customer-pill--inactive {
        background: rgba(148, 163, 184, 0.14);
        color: #475569;
    }

    .customer-pill--loyal {
        background: var(--customer-admin-green-soft);
        color: var(--customer-admin-green);
    }

    .customer-pill--healthy {
        background: rgba(234, 88, 12, 0.1);
        color: var(--customer-admin-warn);
    }

    .customer-value-strong {
        font-weight: 800;
    }

    .customer-quick-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 38px;
        padding: 0 14px;
        border: 1px solid rgba(2, 132, 199, 0.18);
        border-radius: 12px;
        background: #fff;
        color: var(--customer-admin-primary);
        font-size: 0.8rem;
        font-weight: 700;
    }

    .customer-preview {
        padding: 20px;
    }

    .customer-preview-empty {
        padding: 38px 24px;
        color: var(--customer-admin-muted);
        text-align: center;
    }

    .customer-preview-top {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        margin-bottom: 18px;
    }

    .customer-preview-avatar {
        width: 58px;
        height: 58px;
        display: grid;
        place-items: center;
        flex: 0 0 58px;
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(2, 132, 199, 0.16), rgba(56, 189, 248, 0.26));
        color: var(--customer-admin-primary);
        font-size: 1.05rem;
        font-weight: 800;
        overflow: hidden;
    }

    .customer-preview-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .customer-preview-name {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--customer-admin-text);
    }

    .customer-preview-code {
        margin-top: 4px;
        color: var(--customer-admin-muted);
        font-size: 0.84rem;
    }

    .customer-preview-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 16px;
    }

    .customer-preview-metric {
        padding: 14px;
        border-radius: 18px;
        background: rgba(248, 250, 252, 0.88);
        border: 1px solid rgba(148, 163, 184, 0.12);
    }

    .customer-preview-metric span {
        display: block;
    }

    .customer-preview-metric__label {
        color: var(--customer-admin-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .customer-preview-metric__value {
        margin-top: 8px;
        color: var(--customer-admin-text);
        font-size: 1.08rem;
        font-weight: 800;
    }

    .customer-preview-block {
        padding: 16px;
        border-radius: 20px;
        background: rgba(248, 250, 252, 0.88);
        border: 1px solid rgba(148, 163, 184, 0.12);
        margin-bottom: 12px;
    }

    .customer-preview-block__label {
        display: block;
        margin-bottom: 8px;
        color: var(--customer-admin-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .customer-preview-block__value {
        color: var(--customer-admin-text);
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1.5;
    }

    .customer-preview-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 18px;
    }

    .customer-preview-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        background: #fff;
        color: var(--customer-admin-text);
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .customer-preview-action--primary {
        background: linear-gradient(135deg, #0284c7, #38bdf8);
        border-color: transparent;
        color: #fff;
    }

    .customer-admin-empty {
        padding: 46px 24px;
        color: var(--customer-admin-muted);
        text-align: center;
    }

    /* ─── Modal overlay ─── */
    .cm-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1080;
        background: rgba(15, 23, 42, 0.48);
        backdrop-filter: blur(6px);
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .cm-modal-overlay.is-open {
        display: flex;
    }

    .cm-modal {
        position: relative;
        width: 100%;
        max-width: 840px;
        max-height: 90vh;
        border-radius: 28px;
        background: #fff;
        box-shadow: 0 40px 90px rgba(15, 23, 42, 0.22);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: cmSlideUp 0.28s cubic-bezier(0.22, 1, 0.36, 1);
    }

    .cm-modal--wide {
        max-width: 1060px;
    }

    @keyframes cmSlideUp {
        from { opacity: 0; transform: translateY(28px) scale(0.97); }
        to   { opacity: 1; transform: none; }
    }

    .cm-modal__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 22px 28px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        flex: 0 0 auto;
    }

    .cm-modal__title {
        margin: 0;
        font-size: 1.18rem;
        font-weight: 800;
        color: var(--customer-admin-text);
    }

    .cm-modal__close {
        width: 36px;
        height: 36px;
        display: grid;
        place-items: center;
        border: none;
        border-radius: 50%;
        background: rgba(148, 163, 184, 0.12);
        cursor: pointer;
        color: var(--customer-admin-muted);
        font-size: 1.1rem;
        transition: background 0.16s;
    }

    .cm-modal__close:hover { background: rgba(148, 163, 184, 0.22); }

    .cm-modal__body {
        overflow-y: auto;
        flex: 1 1 0;
        padding: 0;
    }

    .cm-modal__loading {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 60px 24px;
        color: var(--customer-admin-muted);
        font-size: 0.95rem;
    }

    /* ─── Profile modal layout ─── */
    .cmp-top {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        padding: 24px 28px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
    }

    .cmp-avatar {
        width: 72px;
        height: 72px;
        flex: 0 0 72px;
        border-radius: 22px;
        background: linear-gradient(135deg, rgba(2,132,199,0.16), rgba(56,189,248,0.28));
        color: var(--customer-admin-primary);
        font-size: 1.25rem;
        font-weight: 800;
        display: grid;
        place-items: center;
        overflow: hidden;
    }

    .cmp-avatar img { width: 100%; height: 100%; object-fit: cover; }

    .cmp-identity { flex: 1; }
    .cmp-name { margin: 0; font-size: 1.3rem; font-weight: 800; }
    .cmp-code { margin-top: 4px; font-size: 0.85rem; color: var(--customer-admin-muted); }

    .cmp-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
    }

    .cmp-stat {
        padding: 18px 22px;
        border-right: 1px solid rgba(148, 163, 184, 0.12);
    }

    .cmp-stat:last-child { border-right: none; }
    .cmp-stat__label { display: block; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--customer-admin-muted); margin-bottom: 6px; }
    .cmp-stat__value { font-size: 1.5rem; font-weight: 800; color: var(--customer-admin-text); }

    .cmp-sections {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
    }

    .cmp-section {
        padding: 22px 28px;
        border-right: 1px solid rgba(148, 163, 184, 0.1);
        border-top: 1px solid rgba(148, 163, 184, 0.1);
    }

    .cmp-section:nth-child(even) { border-right: none; }
    .cmp-section:nth-child(1), .cmp-section:nth-child(2) { border-top: none; }

    .cmp-section__label {
        display: block;
        margin-bottom: 14px;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--customer-admin-muted);
    }

    .cmp-info-row {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 10px;
        font-size: 0.88rem;
    }

    .cmp-info-row__key { color: var(--customer-admin-muted); min-width: 80px; flex: 0 0 80px; }
    .cmp-info-row__val { font-weight: 700; word-break: break-word; }

    .cmp-recent-item {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 14px;
        background: rgba(248, 250, 252, 0.9);
        border: 1px solid rgba(148, 163, 184, 0.1);
        margin-bottom: 8px;
        font-size: 0.85rem;
    }

    .cmp-recent-service { font-weight: 700; margin-bottom: 2px; }
    .cmp-recent-meta { font-size: 0.78rem; color: var(--customer-admin-muted); }

    .cmp-pill {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 800;
        white-space: nowrap;
        flex: 0 0 auto;
    }

    .cmp-pill--info    { background: rgba(2,132,199,0.1); color: #0284c7; }
    .cmp-pill--success { background: rgba(15,159,124,0.1); color: #0f9f7c; }
    .cmp-pill--danger  { background: rgba(220,38,38,0.1); color: #dc2626; }
    .cmp-pill--warning { background: rgba(234,88,12,0.1); color: #ea580c; }
    .cmp-pill--muted   { background: rgba(148,163,184,0.14); color: #64748b; }

    /* ─── Booking history modal ─── */
    .cmb-filters {
        display: flex;
        gap: 10px;
        align-items: center;
        padding: 16px 24px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        flex-wrap: wrap;
    }

    .cmb-filters .form-select {
        min-height: 40px;
        border-radius: 12px;
        font-size: 0.85rem;
    }

    .cmb-table-wrap { overflow-x: auto; }

    .cmb-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.86rem;
    }

    .cmb-table th {
        padding: 12px 16px;
        background: rgba(248,250,252,0.95);
        color: var(--customer-admin-muted);
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        white-space: nowrap;
        border-bottom: 1px solid rgba(148,163,184,0.14);
    }

    .cmb-table td {
        padding: 14px 16px;
        border-bottom: 1px solid rgba(148,163,184,0.1);
        vertical-align: top;
        color: var(--customer-admin-text);
    }

    .cmb-table tr:last-child td { border-bottom: none; }
    .cmb-code { font-weight: 800; font-size: 0.8rem; color: var(--customer-admin-muted); }
    .cmb-service { font-weight: 700; }
    .cmb-meta { font-size: 0.78rem; color: var(--customer-admin-muted); margin-top: 2px; }
    .cmb-amount { font-weight: 800; }

    .cm-modal__foot {
        padding: 16px 28px;
        border-top: 1px solid rgba(148,163,184,0.12);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        flex: 0 0 auto;
    }

    .cm-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        padding: 0 20px;
        border-radius: 14px;
        font-size: 0.85rem;
        font-weight: 700;
        border: 1px solid rgba(148,163,184,0.2);
        background: #fff;
        color: var(--customer-admin-text);
        cursor: pointer;
        text-decoration: none;
        transition: background 0.16s;
    }

    .cm-btn--primary {
        background: linear-gradient(135deg, #0284c7, #38bdf8);
        border-color: transparent;
        color: #fff;
    }

    .cm-btn:hover { filter: brightness(0.96); }
    .cm-btn--primary:hover { filter: brightness(1.04); }

    @media (max-width: 767.98px) {
        .cmp-grid { grid-template-columns: repeat(2, 1fr); }
        .cmp-sections { grid-template-columns: 1fr; }
        .cmp-section:nth-child(even) { border-right: none; }
        .cmp-section:nth-child(1), .cmp-section:nth-child(2) { border-top: 1px solid rgba(148,163,184,0.1); }
        .cmp-section:first-child { border-top: none; }
    }

    @media (max-width: 1279.98px) {
        .customer-admin-stats {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .customer-admin-main {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .customer-admin-shell {
            padding: 18px;
            border-radius: 22px;
        }

        .customer-admin-head {
            flex-direction: column;
        }

        .customer-admin-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .customer-preview-grid {
            grid-template-columns: 1fr;
        }

        .customer-preview-actions {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container py-4 py-lg-5" id="adminCustomersPage">
    <div class="customer-admin-shell">
        <div class="customer-admin-head">
            <div>
                <p class="customer-admin-kicker">Admin / Khách hàng</p>
                <h1 class="customer-admin-title">Quản lý khách hàng</h1>
                <p class="customer-admin-subtitle">Theo dõi danh sách khách hàng, tìm kiếm nhanh thông tin cơ bản và mở chi tiết hồ sơ khi cần.</p>
            </div>

            <div class="customer-admin-toolbar">
                <input type="search" class="form-control" id="customerSearchInput" placeholder="Tìm tên, SĐT, email..." style="min-width: 240px;">
                <select class="form-select" id="customerStatusFilter" style="min-width: 180px;">
                    <option value="">Tất cả trạng thái</option>
                    <option value="new_customer">Khách mới</option>
                    <option value="has_booking">Đã từng đặt dịch vụ</option>
                    <option value="no_booking">Chưa đặt dịch vụ</option>
                    <option value="active_booking">Đang có đơn xử lý</option>
                </select>
                <select class="form-select" id="customerSortFilter" style="min-width: 180px;">
                    <option value="latest">Mới cập nhật</option>
                    <option value="name_asc">Tên A-Z</option>
                </select>
                <button type="button" class="customer-admin-refresh" id="customerRefreshButton">Làm mới</button>
            </div>
        </div>

        <div class="customer-admin-stats">
            <article class="customer-stat-card">
                <span class="customer-stat-card__label">Tổng khách</span>
                <span class="customer-stat-card__value" id="customerStatTotal">0</span>
                <span class="customer-stat-card__meta">Tập khách hiện trong bộ lọc</span>
            </article>
            <article class="customer-stat-card">
                <span class="customer-stat-card__label">Khách mới</span>
                <span class="customer-stat-card__value" id="customerStatNew">0</span>
                <span class="customer-stat-card__meta">Tài khoản tạo trong 30 ngày gần đây</span>
            </article>
            <article class="customer-stat-card">
                <span class="customer-stat-card__label">Đã từng đặt dịch vụ</span>
                <span class="customer-stat-card__value" id="customerStatBooked">0</span>
                <span class="customer-stat-card__meta">Khách đã có lịch sử booking</span>
            </article>
            <article class="customer-stat-card">
                <span class="customer-stat-card__label">Đang có đơn xử lý</span>
                <span class="customer-stat-card__value" id="customerStatActive">0</span>
                <span class="customer-stat-card__meta">Khách hiện còn đơn đang theo dõi</span>
            </article>
        </div>

        <div class="customer-admin-main">
            <section class="customer-admin-panel">
                <div class="customer-admin-panel__head">
                    <div>
                        <h2 class="customer-admin-panel__title">Danh sách khách hàng</h2>
                        <p class="customer-admin-panel__copy" id="customerTableCaption">Đang tải dữ liệu khách hàng...</p>
                    </div>
                </div>

                <div class="customer-table-wrap">
                    <table class="customer-admin-table">
                        <thead>
                            <tr>
                                <th>Khách hàng</th>
                                <th>Ngày tham gia</th>
                                <th>Số đơn</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="customerTableBody">
                            <tr>
                                <td colspan="5" class="customer-admin-empty">Đang tải danh sách khách hàng...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="customer-admin-panel">
                <div class="customer-admin-panel__head">
                    <div>
                        <h2 class="customer-admin-panel__title">Xem nhanh</h2>
                        <p class="customer-admin-panel__copy">Chọn một khách trong bảng để xem nhanh thông tin cơ bản.</p>
                    </div>
                </div>

                <div class="customer-preview" id="customerPreviewPanel">
                    <div class="customer-preview-empty">Chưa chọn khách hàng.</div>
                </div>
            </aside>
        </div>
    </div>
</div>

{{-- ─── Modal: Hồ sơ khách hàng ─── --}}
<div class="cm-modal-overlay" id="customerProfileModal" role="dialog" aria-modal="true" aria-labelledby="cmpModalTitle">
    <div class="cm-modal">
        <div class="cm-modal__head">
            <h2 class="cm-modal__title" id="cmpModalTitle">Hồ sơ khách hàng</h2>
            <button class="cm-modal__close" data-modal-close="customerProfileModal" aria-label="Đóng">&#x2715;</button>
        </div>
        <div class="cm-modal__body" id="customerProfileModalBody">
            <div class="cm-modal__loading"><span class="spinner-border spinner-border-sm me-2"></span> Đang tải hồ sơ...</div>
        </div>
        <div class="cm-modal__foot">
            <button class="cm-btn" data-modal-close="customerProfileModal">Đóng</button>
            <a class="cm-btn cm-btn--primary" id="cmpDetailLink" href="#" target="_blank">Mở trang chi tiết ↗</a>
        </div>
    </div>
</div>

{{-- ─── Modal: Lịch sử đặt dịch vụ ─── --}}
<div class="cm-modal-overlay" id="customerBookingsModal" role="dialog" aria-modal="true" aria-labelledby="cmbModalTitle">
    <div class="cm-modal cm-modal--wide">
        <div class="cm-modal__head">
            <h2 class="cm-modal__title" id="cmbModalTitle">Lịch sử đơn đặt dịch vụ</h2>
            <button class="cm-modal__close" data-modal-close="customerBookingsModal" aria-label="Đóng">&#x2715;</button>
        </div>
        <div class="cm-modal__body">
            <div class="cmb-filters">
                <select class="form-select" id="cmbStatusFilter" style="min-width:160px;">
                    <option value="">Tất cả trạng thái</option>
                    <option value="dang_cho">Đang chờ</option>
                    <option value="da_nhan">Đã nhận</option>
                    <option value="dang_lam">Đang làm</option>
                    <option value="hoan_thanh">Hoàn thành</option>
                    <option value="da_huy">Đã hủy</option>
                </select>
                <select class="form-select" id="cmbSortFilter" style="min-width:160px;">
                    <option value="latest">Mới nhất</option>
                    <option value="oldest">Cũ nhất</option>
                    <option value="amount_desc">Chi tiêu cao nhất</option>
                </select>
            </div>
            <div class="cmb-table-wrap" id="customerBookingsModalBody">
                <div class="cm-modal__loading"><span class="spinner-border spinner-border-sm me-2"></span> Đang tải lịch sử đơn...</div>
            </div>
        </div>
        <div class="cm-modal__foot">
            <button class="cm-btn" data-modal-close="customerBookingsModal">Đóng</button>
            <a class="cm-btn cm-btn--primary" id="cmbDetailLink" href="#" target="_blank">Xem trang chi tiết ↗</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/customers.js') }}"></script>
@endpush
