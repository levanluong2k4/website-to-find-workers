@extends('layouts.app')

@section('title', 'Khach hang - Admin')

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
                <p class="customer-admin-kicker">Admin / Khach hang</p>
                <h1 class="customer-admin-title">Quan ly khach hang</h1>
                <p class="customer-admin-subtitle">Theo doi danh sach khach hang, tim kiem nhanh thong tin co ban va mo chi tiet ho so khi can.</p>
            </div>

            <div class="customer-admin-toolbar">
                <input type="search" class="form-control" id="customerSearchInput" placeholder="Tim ten, SDT, email..." style="min-width: 240px;">
                <select class="form-select" id="customerStatusFilter" style="min-width: 180px;">
                    <option value="">Tat ca trang thai</option>
                    <option value="new_customer">Khach moi</option>
                    <option value="has_booking">Da tung dat dich vu</option>
                    <option value="active_booking">Dang co don xu ly</option>
                </select>
                <select class="form-select" id="customerSortFilter" style="min-width: 180px;">
                    <option value="latest">Moi cap nhat</option>
                    <option value="name_asc">Ten A-Z</option>
                </select>
                <button type="button" class="customer-admin-refresh" id="customerRefreshButton">Lam moi</button>
            </div>
        </div>

        <div class="customer-admin-stats">
            <article class="customer-stat-card">
                <span class="customer-stat-card__label">Tong khach</span>
                <span class="customer-stat-card__value" id="customerStatTotal">0</span>
                <span class="customer-stat-card__meta">Tap khach hien trong bo loc</span>
            </article>
            <article class="customer-stat-card">
                <span class="customer-stat-card__label">Khach moi</span>
                <span class="customer-stat-card__value" id="customerStatNew">0</span>
                <span class="customer-stat-card__meta">Tai khoan tao trong 30 ngay gan day</span>
            </article>
            <article class="customer-stat-card">
                <span class="customer-stat-card__label">Da tung dat dich vu</span>
                <span class="customer-stat-card__value" id="customerStatBooked">0</span>
                <span class="customer-stat-card__meta">Khach da co lich su booking</span>
            </article>
            <article class="customer-stat-card">
                <span class="customer-stat-card__label">Dang co don xu ly</span>
                <span class="customer-stat-card__value" id="customerStatActive">0</span>
                <span class="customer-stat-card__meta">Khach hien con don dang theo doi</span>
            </article>
        </div>

        <div class="customer-admin-main">
            <section class="customer-admin-panel">
                <div class="customer-admin-panel__head">
                    <div>
                        <h2 class="customer-admin-panel__title">Danh sach khach hang</h2>
                        <p class="customer-admin-panel__copy" id="customerTableCaption">Dang tai du lieu khach hang...</p>
                    </div>
                </div>

                <div class="customer-table-wrap">
                    <table class="customer-admin-table">
                        <thead>
                            <tr>
                                <th>Khach hang</th>
                                <th>Ngay tham gia</th>
                                <th>So don</th>
                                <th>Trang thai</th>
                                <th>Thao tac</th>
                            </tr>
                        </thead>
                        <tbody id="customerTableBody">
                            <tr>
                                <td colspan="5" class="customer-admin-empty">Dang tai danh sach khach hang...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="customer-admin-panel">
                <div class="customer-admin-panel__head">
                    <div>
                        <h2 class="customer-admin-panel__title">Xem nhanh</h2>
                        <p class="customer-admin-panel__copy">Chon mot khach trong bang de xem nhanh thong tin co ban.</p>
                    </div>
                </div>

                <div class="customer-preview" id="customerPreviewPanel">
                    <div class="customer-preview-empty">Chua chon khach hang.</div>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/customers.js') }}"></script>
@endpush
