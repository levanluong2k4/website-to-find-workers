@extends('layouts.app')

@section('title', 'Ho so khach hang - Admin')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Fira+Code:wght@500;600;700&family=Fira+Sans:wght@400;500;600;700;800&display=swap');

    :root {
        --customer-360-bg: #f8fafc;
        --customer-360-panel: rgba(255, 255, 255, 0.94);
        --customer-360-border: rgba(148, 163, 184, 0.18);
        --customer-360-text: #0f172a;
        --customer-360-muted: #64748b;
        --customer-360-primary: #0284c7;
        --customer-360-primary-soft: rgba(2, 132, 199, 0.1);
        --customer-360-success: #059669;
        --customer-360-success-soft: rgba(5, 150, 105, 0.11);
        --customer-360-warning: #ea580c;
        --customer-360-warning-soft: rgba(234, 88, 12, 0.12);
        --customer-360-danger: #dc2626;
        --customer-360-danger-soft: rgba(220, 38, 38, 0.1);
        --customer-360-shadow: 0 28px 60px rgba(15, 23, 42, 0.08);
    }

    body {
        min-height: 100vh;
        background:
            radial-gradient(circle at top left, rgba(2, 132, 199, 0.12), transparent 26%),
            radial-gradient(circle at bottom right, rgba(234, 88, 12, 0.08), transparent 20%),
            var(--customer-360-bg);
        color: var(--customer-360-text);
        font-family: 'Fira Sans', sans-serif;
    }

    .customer-360-page {
        min-height: calc(100vh - 7rem);
    }

    .customer-360-shell {
        padding: 30px;
        border-radius: 32px;
        background: rgba(255, 255, 255, 0.78);
        border: 1px solid rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(20px);
        box-shadow: var(--customer-360-shadow);
    }

    .customer-360-topbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
        margin-bottom: 22px;
    }

    .customer-360-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        color: var(--customer-360-primary);
        font-size: 0.82rem;
        font-weight: 700;
        text-decoration: none;
    }

    .customer-360-kicker {
        margin: 0 0 6px;
        color: var(--customer-360-muted);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .customer-360-title {
        margin: 0;
        color: var(--customer-360-text);
        font-family: 'Fira Code', monospace;
        font-size: clamp(1.85rem, 2vw, 2.55rem);
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .customer-360-subtitle {
        max-width: 760px;
        margin: 10px 0 0;
        color: var(--customer-360-muted);
        font-size: 0.96rem;
        line-height: 1.65;
    }

    .customer-360-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
        min-width: 260px;
    }

    .customer-360-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0 16px;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        background: #fff;
        color: var(--customer-360-text);
        font-size: 0.84rem;
        font-weight: 700;
        text-decoration: none;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .customer-360-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.08);
        border-color: rgba(2, 132, 199, 0.28);
    }

    .customer-360-action--primary {
        border-color: transparent;
        background: linear-gradient(135deg, #0284c7, #38bdf8);
        color: #fff;
    }

    .customer-360-overview {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(340px, 0.95fr);
        gap: 18px;
        margin-bottom: 18px;
    }

    .customer-360-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.88fr);
        gap: 18px;
        align-items: start;
    }

    .customer-360-main,
    .customer-360-side {
        display: grid;
        gap: 18px;
    }

    .customer-360-panel {
        padding: 20px;
        border-radius: 28px;
        background: var(--customer-360-panel);
        border: 1px solid var(--customer-360-border);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .customer-360-panel__head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 18px;
    }

    .customer-360-panel__title {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: var(--customer-360-text);
    }

    .customer-360-panel__copy {
        margin: 6px 0 0;
        color: var(--customer-360-muted);
        font-size: 0.84rem;
        line-height: 1.55;
    }

    .customer-360-profile-card {
        display: flex;
        gap: 18px;
        align-items: flex-start;
    }

    .customer-360-avatar {
        width: 76px;
        height: 76px;
        flex: 0 0 76px;
        display: grid;
        place-items: center;
        border-radius: 24px;
        background: linear-gradient(135deg, rgba(2, 132, 199, 0.14), rgba(56, 189, 248, 0.28));
        color: var(--customer-360-primary);
        font-size: 1.4rem;
        font-weight: 800;
        overflow: hidden;
    }

    .customer-360-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .customer-360-profile-name {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--customer-360-text);
    }

    .customer-360-profile-meta {
        margin-top: 6px;
        color: var(--customer-360-muted);
        font-size: 0.86rem;
        line-height: 1.6;
    }

    .customer-360-pill-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }

    .customer-360-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 30px;
        padding: 0 12px;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        white-space: nowrap;
    }

    .customer-360-pill--info {
        background: var(--customer-360-primary-soft);
        color: var(--customer-360-primary);
    }

    .customer-360-pill--success {
        background: var(--customer-360-success-soft);
        color: var(--customer-360-success);
    }

    .customer-360-pill--warning {
        background: var(--customer-360-warning-soft);
        color: var(--customer-360-warning);
    }

    .customer-360-pill--danger {
        background: var(--customer-360-danger-soft);
        color: var(--customer-360-danger);
    }

    .customer-360-pill--muted {
        background: rgba(148, 163, 184, 0.12);
        color: var(--customer-360-muted);
    }

    .customer-360-profile-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-top: 18px;
    }

    .customer-360-profile-field,
    .customer-360-stat-tile,
    .customer-360-summary-item,
    .customer-360-pattern-list li,
    .customer-360-review,
    .customer-360-alert,
    .customer-360-booking-item {
        border-radius: 20px;
        background: rgba(248, 250, 252, 0.9);
        border: 1px solid rgba(148, 163, 184, 0.12);
    }

    .customer-360-profile-field {
        padding: 14px;
    }

    .customer-360-field-label {
        display: block;
        margin-bottom: 8px;
        color: var(--customer-360-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .customer-360-field-value {
        color: var(--customer-360-text);
        font-size: 0.94rem;
        font-weight: 700;
        line-height: 1.55;
        word-break: break-word;
    }

    .customer-360-stats-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        height: 100%;
    }

    .customer-360-stat-tile {
        padding: 16px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 126px;
    }

    .customer-360-stat-label {
        color: var(--customer-360-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .customer-360-stat-value {
        margin-top: 10px;
        color: var(--customer-360-text);
        font-size: 1.7rem;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.04em;
    }

    .customer-360-stat-meta {
        margin-top: 10px;
        color: var(--customer-360-muted);
        font-size: 0.82rem;
        line-height: 1.5;
    }

    .customer-360-current {
        display: grid;
        gap: 16px;
    }

    .customer-360-current-banner {
        padding: 20px;
        border-radius: 24px;
        border: 1px solid transparent;
    }

    .customer-360-current-banner.tone-info {
        background: linear-gradient(135deg, rgba(2, 132, 199, 0.12), rgba(186, 230, 253, 0.48));
        border-color: rgba(2, 132, 199, 0.12);
    }

    .customer-360-current-banner.tone-success {
        background: linear-gradient(135deg, rgba(5, 150, 105, 0.12), rgba(209, 250, 229, 0.52));
        border-color: rgba(5, 150, 105, 0.14);
    }

    .customer-360-current-banner.tone-warning {
        background: linear-gradient(135deg, rgba(234, 88, 12, 0.12), rgba(254, 215, 170, 0.45));
        border-color: rgba(234, 88, 12, 0.14);
    }

    .customer-360-current-banner.tone-muted {
        background: linear-gradient(135deg, rgba(148, 163, 184, 0.12), rgba(226, 232, 240, 0.5));
        border-color: rgba(148, 163, 184, 0.14);
    }

    .customer-360-current-title {
        margin: 0;
        font-size: 1.12rem;
        font-weight: 800;
        color: var(--customer-360-text);
    }

    .customer-360-current-copy {
        margin: 8px 0 0;
        color: var(--customer-360-muted);
        font-size: 0.92rem;
        line-height: 1.65;
    }

    .customer-360-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .customer-360-summary-item {
        padding: 14px;
    }

    .customer-360-summary-item span {
        display: block;
    }

    .customer-360-summary-item__label {
        color: var(--customer-360-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .customer-360-summary-item__value {
        margin-top: 8px;
        color: var(--customer-360-text);
        font-size: 1.2rem;
        font-weight: 800;
    }

    .customer-360-booking-list,
    .customer-360-pattern-list,
    .customer-360-review-list,
    .customer-360-alert-list,
    .customer-360-timeline {
        display: grid;
        gap: 12px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .customer-360-booking-item {
        padding: 16px;
        display: grid;
        gap: 12px;
    }

    .customer-360-booking-top,
    .customer-360-review-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
    }

    .customer-360-booking-code,
    .customer-360-review-code {
        color: var(--customer-360-muted);
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .customer-360-booking-name,
    .customer-360-review-service {
        margin-top: 5px;
        color: var(--customer-360-text);
        font-size: 0.96rem;
        font-weight: 800;
        line-height: 1.45;
    }

    .customer-360-booking-meta,
    .customer-360-review-meta {
        color: var(--customer-360-muted);
        font-size: 0.84rem;
        line-height: 1.65;
    }

    .customer-360-booking-foot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .customer-360-booking-amount {
        color: var(--customer-360-text);
        font-size: 0.92rem;
        font-weight: 800;
    }

    .customer-360-link-inline {
        color: var(--customer-360-primary);
        font-size: 0.84rem;
        font-weight: 700;
        text-decoration: none;
    }

    .customer-360-pattern-list li,
    .customer-360-review,
    .customer-360-alert {
        padding: 16px;
    }

    .customer-360-inline-form {
        display: grid;
        gap: 10px;
        margin-bottom: 14px;
    }

    .customer-360-inline-form--tags {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
    }

    .customer-360-input,
    .customer-360-select,
    .customer-360-textarea {
        width: 100%;
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        background: #fff;
        color: var(--customer-360-text);
        font-size: 0.88rem;
        outline: none;
        box-shadow: none;
    }

    .customer-360-input,
    .customer-360-select {
        min-height: 44px;
        padding: 0 14px;
    }

    .customer-360-textarea {
        min-height: 112px;
        padding: 14px;
        resize: vertical;
        line-height: 1.6;
    }

    .customer-360-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        padding: 0 14px;
        border: 0;
        border-radius: 14px;
        background: linear-gradient(135deg, #0284c7, #38bdf8);
        color: #fff;
        font-size: 0.84rem;
        font-weight: 800;
    }

    .customer-360-button--ghost {
        background: rgba(248, 250, 252, 0.96);
        border: 1px solid rgba(148, 163, 184, 0.16);
        color: var(--customer-360-text);
    }

    .customer-360-tag-list,
    .customer-360-tag-suggestions,
    .customer-360-note-list,
    .customer-360-followup-list {
        display: grid;
        gap: 10px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .customer-360-tag-cloud {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .customer-360-tag-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 34px;
        padding: 0 12px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 800;
        border: 1px solid transparent;
    }

    .customer-360-tag-chip__remove {
        border: 0;
        background: transparent;
        color: inherit;
        font-size: 0.82rem;
        font-weight: 800;
        line-height: 1;
        padding: 0;
    }

    .customer-360-note {
        padding: 14px;
        border-radius: 18px;
        background: rgba(248, 250, 252, 0.92);
        border: 1px solid rgba(148, 163, 184, 0.12);
    }

    .customer-360-followup {
        padding: 14px;
        border-radius: 18px;
        background: rgba(248, 250, 252, 0.92);
        border: 1px solid rgba(148, 163, 184, 0.12);
    }

    .customer-360-followup-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
    }

    .customer-360-followup-title {
        color: var(--customer-360-text);
        font-size: 0.92rem;
        font-weight: 800;
        line-height: 1.55;
    }

    .customer-360-followup-meta {
        margin-top: 8px;
        color: var(--customer-360-muted);
        font-size: 0.82rem;
        line-height: 1.65;
    }

    .customer-360-followup-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }

    .customer-360-followup-summary {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 14px;
    }

    .customer-360-note-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
    }

    .customer-360-note-meta {
        color: var(--customer-360-muted);
        font-size: 0.8rem;
        line-height: 1.55;
    }

    .customer-360-note-copy {
        margin-top: 10px;
        color: var(--customer-360-text);
        font-size: 0.88rem;
        line-height: 1.65;
        white-space: pre-wrap;
    }

    .customer-360-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .customer-360-pattern-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .customer-360-pattern-label {
        color: var(--customer-360-text);
        font-size: 0.9rem;
        font-weight: 700;
    }

    .customer-360-pattern-count {
        color: var(--customer-360-muted);
        font-size: 0.84rem;
        font-weight: 700;
    }

    .customer-360-review-quote {
        margin: 12px 0 0;
        color: var(--customer-360-text);
        font-size: 0.9rem;
        line-height: 1.65;
    }

    .customer-360-alert-title {
        margin: 0;
        color: var(--customer-360-text);
        font-size: 0.92rem;
        font-weight: 800;
    }

    .customer-360-alert-copy {
        margin: 7px 0 0;
        color: var(--customer-360-muted);
        font-size: 0.84rem;
        line-height: 1.6;
    }

    .customer-360-timeline {
        position: relative;
        gap: 14px;
    }

    .customer-360-timeline::before {
        content: '';
        position: absolute;
        top: 6px;
        bottom: 6px;
        left: 11px;
        width: 2px;
        background: rgba(148, 163, 184, 0.2);
    }

    .customer-360-timeline-item {
        position: relative;
        padding-left: 34px;
    }

    .customer-360-timeline-dot {
        position: absolute;
        top: 4px;
        left: 0;
        width: 24px;
        height: 24px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        border: 1px solid transparent;
        background: #fff;
        font-size: 0.7rem;
        font-weight: 800;
    }

    .customer-360-timeline-item.tone-info .customer-360-timeline-dot {
        background: var(--customer-360-primary-soft);
        color: var(--customer-360-primary);
        border-color: rgba(2, 132, 199, 0.12);
    }

    .customer-360-timeline-item.tone-success .customer-360-timeline-dot {
        background: var(--customer-360-success-soft);
        color: var(--customer-360-success);
        border-color: rgba(5, 150, 105, 0.14);
    }

    .customer-360-timeline-item.tone-warning .customer-360-timeline-dot {
        background: var(--customer-360-warning-soft);
        color: var(--customer-360-warning);
        border-color: rgba(234, 88, 12, 0.14);
    }

    .customer-360-timeline-item.tone-danger .customer-360-timeline-dot {
        background: var(--customer-360-danger-soft);
        color: var(--customer-360-danger);
        border-color: rgba(220, 38, 38, 0.12);
    }

    .customer-360-timeline-item.tone-muted .customer-360-timeline-dot {
        background: rgba(148, 163, 184, 0.12);
        color: var(--customer-360-muted);
        border-color: rgba(148, 163, 184, 0.16);
    }

    .customer-360-timeline-title {
        color: var(--customer-360-text);
        font-size: 0.95rem;
        font-weight: 800;
    }

    .customer-360-timeline-time {
        margin-top: 4px;
        color: var(--customer-360-muted);
        font-size: 0.78rem;
        font-weight: 700;
    }

    .customer-360-timeline-copy {
        margin-top: 7px;
        color: var(--customer-360-muted);
        font-size: 0.86rem;
        line-height: 1.6;
    }

    .customer-360-empty {
        padding: 14px 0;
        color: var(--customer-360-muted);
        font-size: 0.9rem;
        line-height: 1.7;
    }

    @media (max-width: 1199.98px) {
        .customer-360-overview,
        .customer-360-grid {
            grid-template-columns: 1fr;
        }

        .customer-360-actions {
            justify-content: flex-start;
            min-width: 0;
        }
    }

    @media (max-width: 767.98px) {
        .customer-360-shell {
            padding: 20px;
            border-radius: 24px;
        }

        .customer-360-topbar {
            flex-direction: column;
        }

        .customer-360-profile-card {
            flex-direction: column;
        }

        .customer-360-profile-grid,
        .customer-360-stats-grid,
        .customer-360-summary-grid,
        .customer-360-followup-summary {
            grid-template-columns: 1fr;
        }

        .customer-360-inline-form--tags {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<section class="customer-360-page py-4">
    <div class="container-xxl">
        <div class="customer-360-shell" id="customer360App" data-customer-id="{{ $id }}">
            <div class="customer-360-topbar">
                <div>
                    <a href="{{ route('admin.customers') }}" class="customer-360-back">
                        <span>&larr;</span>
                        <span>Danh sach khach hang</span>
                    </a>
                    <p class="customer-360-kicker">Khach hang / Ho so chi tiet</p>
                    <h1 class="customer-360-title" id="customer360Title">Dang tai ho so khach hang...</h1>
                    <p class="customer-360-subtitle" id="customer360Subtitle">
                        Xem thong tin co ban, lich su dat dich vu va ghi chu noi bo cua khach hang.
                    </p>
                </div>
                <div class="customer-360-actions" id="customer360HeaderActions"></div>
            </div>

            <div class="customer-360-overview">
                <section class="customer-360-panel" id="customer360Profile"></section>
                <section class="customer-360-panel" id="customer360Stats"></section>
            </div>

            <div class="customer-360-grid">
                <div class="customer-360-main">
                    <section class="customer-360-panel" id="customer360RecentBookings"></section>
                    <section class="customer-360-panel" id="customer360Timeline"></section>
                </div>
                <div class="customer-360-side">
                    <section class="customer-360-panel" id="customer360Notes"></section>
                    <section class="customer-360-panel" id="customer360Reviews"></section>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/customer-detail.js') }}"></script>
@endpush
