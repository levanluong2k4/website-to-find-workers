@extends('layouts.app')

@section('title', 'Điều phối đơn - Thợ Tốt')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@700;800&display=swap');

    :root {
        --dispatch-bg: #f8f9ff;
        --dispatch-surface: #ffffff;
        --dispatch-soft: #eff4ff;
        --dispatch-primary: #0058be;
        --dispatch-primary-soft: #d8e2ff;
        --dispatch-line: rgba(194, 198, 214, 0.28);
        --dispatch-line-soft: rgba(194, 198, 214, 0.1);
        --dispatch-text: #0b1c30;
        --dispatch-muted: #424754;
        --dispatch-muted-soft: #6b7280;
        --dispatch-danger: #ba1a1a;
        --dispatch-success: #16a34a;
        --dispatch-success-soft: #d1fae5;
        --dispatch-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        --dispatch-shadow-soft: 0 12px 32px rgba(11, 28, 48, 0.06);
    }

    body {
        background: var(--dispatch-bg) !important;
    }

    body.app-admin-shell {
        background: var(--dispatch-bg) !important;
    }

    .admin-dispatch-page {
        height: calc(100vh - 80px);
        background: var(--dispatch-bg);
        overflow: hidden;
    }

    .admin-dispatch-shell {
        display: grid;
        grid-template-columns: 35% 65%;
        height: 100%;
    }

    .admin-dispatch-queue {
        border-right: 1px solid var(--dispatch-line-soft);
        background: var(--dispatch-bg);
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    }

    .admin-dispatch-main {
        background: var(--dispatch-bg);
        height: 100%;
        overflow-y: auto;
    }

    .admin-dispatch-main::-webkit-scrollbar {
        width: 6px;
    }
    .admin-dispatch-main::-webkit-scrollbar-track {
        background: transparent;
    }
    .admin-dispatch-main::-webkit-scrollbar-thumb {
        background-color: rgba(194, 198, 214, 0.4);
        border-radius: 10px;
    }

    .admin-dispatch-queue__head {
        padding: 2rem;
        flex-shrink: 0;
    }

    .admin-dispatch-queue__title,
    .admin-dispatch-section__title,
    .admin-dispatch-card__title,
    .admin-dispatch-worker__name {
        margin: 0;
        color: var(--dispatch-text);
        font-family: 'Manrope', sans-serif;
        font-weight: 800;
    }

    .admin-dispatch-queue__title {
        font-size: 1.95rem;
        line-height: 1.2;
    }

    .admin-dispatch-queue__meta {
        margin-top: 0.15rem;
        color: var(--dispatch-danger);
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .admin-dispatch-filters {
        display: grid;
        gap: 1rem;
        margin-top: 1.75rem;
    }

    .admin-dispatch-segmented {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
        padding: 0.25rem;
        border-radius: 0.75rem;
        background: var(--dispatch-soft);
    }

    .admin-dispatch-segmented__btn,
    .admin-dispatch-date {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        min-height: 2.75rem;
        border-right: 2px solid #000;
        border-radius: 0.5rem;
        background: transparent;
        color: var(--dispatch-muted);
        font-family: 'Inter', sans-serif;
        font-size: 0.78rem;
        font-weight: 600;
        box-shadow: none;
        transition: all 0.2s ease;
    }

    .admin-dispatch-date {
        padding-right: 1.5rem !important;
        background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2394a3b8%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 0.65rem auto;
    }

    .admin-dispatch-segmented__btn.is-active,
    .admin-dispatch-date.is-active {
        background-color: #fff;
        color: var(--dispatch-primary);
        box-shadow: var(--dispatch-shadow);
    }

    .admin-dispatch-date.is-active {
        background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%230058be%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
    }

    .admin-dispatch-search {
        position: relative;
    }

    .admin-dispatch-search input {
        width: 100%;
        min-height: 3rem;
        padding: 0.8rem 1rem 0.8rem 2.75rem;
        border: 0;
        border-radius: 0.75rem;
        background: #fff;
        box-shadow: var(--dispatch-shadow);
        font-family: 'Inter', sans-serif;
        font-size: 0.88rem;
        color: var(--dispatch-text);
    }

    .admin-dispatch-search input::placeholder {
        color: #c2c6d6;
    }

    .admin-dispatch-search i {
        position: absolute;
        left: 1rem;
        top: 50%;
        color: #a7b0c1;
        transform: translateY(-50%);
        font-size: 0.95rem;
    }

    .admin-dispatch-queue__list {
        padding: 0 2rem 2rem;
        display: grid;
        align-content: start;
        gap: 1rem;
        overflow-y: auto;
        flex: 1;
    }

    .admin-dispatch-queue__list::-webkit-scrollbar {
        width: 6px;
    }
    .admin-dispatch-queue__list::-webkit-scrollbar-track {
        background: transparent;
    }
    .admin-dispatch-queue__list::-webkit-scrollbar-thumb {
        background-color: rgba(194, 198, 214, 0.4);
        border-radius: 10px;
    }

    .admin-dispatch-queue-item {
        display: block;
        width: 100%;
        padding: 1.25rem 1.25rem 1.25rem 1.5rem;
        border: 0;
        border-radius: 0.75rem;
        background: #fff;
        box-shadow: var(--dispatch-shadow);
        text-align: left;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .admin-dispatch-queue-item:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 32px rgba(11, 28, 48, 0.08);
    }

    .admin-dispatch-queue-item.is-active {
        background: var(--dispatch-primary-soft);
        border: 2px solid var(--dispatch-primary);
        padding-left: calc(1.5rem - 4px);
    }

    .admin-dispatch-queue-item__top,
    .admin-dispatch-worker__top,
    .admin-dispatch-worker__foot,
    .admin-dispatch-card__row,
    .admin-dispatch-section__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .admin-dispatch-code {
        color: var(--dispatch-primary);
        font-family: 'Inter', sans-serif;
        font-size: 0.66rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .admin-dispatch-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-family: 'Inter', sans-serif;
        font-size: 0.64rem;
        font-weight: 700;
        line-height: 1.4;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .admin-dispatch-badge--primary {
        background: var(--dispatch-primary-soft);
        color: var(--dispatch-primary);
    }

    .admin-dispatch-badge--success {
        background: var(--dispatch-success-soft);
        color: var(--dispatch-success);
    }

    .admin-dispatch-badge--danger {
        background: rgba(186, 26, 26, 0.14);
        color: var(--dispatch-danger);
    }

    .admin-dispatch-badge--muted {
        background: rgba(194, 198, 214, 0.4);
        color: var(--dispatch-muted);
    }

    .admin-dispatch-badge--soft {
        background: var(--dispatch-soft);
        color: var(--dispatch-muted);
    }

    .admin-dispatch-queue-item__layout {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .admin-dispatch-queue-item__avatar {
        width: 3rem;
        height: 3rem;
        border-radius: 0.75rem;
        background: linear-gradient(135deg, #0b1c30, #2170e4);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: 'Manrope', sans-serif;
        font-size: 0.9rem;
        font-weight: 800;
        overflow: hidden;
        flex-shrink: 0;
        margin-top: 0.2rem;
    }

    .admin-dispatch-queue-item__avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .admin-dispatch-queue-item__content {
        flex: 1;
        min-width: 0;
    }

    .admin-dispatch-queue-item__name {
        margin: 0.35rem 0 0;
        color: var(--dispatch-text);
        font-family: 'Manrope', sans-serif;
        font-size: 1.1rem;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .admin-dispatch-line,
    .admin-dispatch-subline,
    .admin-dispatch-chip,
    .admin-dispatch-empty__copy,
    .admin-dispatch-card__copy,
    .admin-dispatch-worker__meta {
        color: var(--dispatch-muted);
        font-family: 'Inter', sans-serif;
    }

    .admin-dispatch-line,
    .admin-dispatch-subline {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        margin-top: 0.35rem;
        font-size: 0.8rem;
        line-height: 1.45;
    }

    .admin-dispatch-line i,
    .admin-dispatch-subline i,
    .admin-dispatch-card__meta i,
    .admin-dispatch-worker__meta i {
        color: #7b8497;
        font-size: 0.82rem;
    }

    .admin-dispatch-main__inner {
        display: grid;
        gap: 2rem;
        padding: 2.5rem;
    }

    .admin-dispatch-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 1.5rem;
        padding: 1.5rem 2rem;
        border-radius: 1.5rem;
        border: 2px solid var(--dispatch-primary);
        background: var(--dispatch-soft);
        box-shadow: var(--dispatch-shadow);
    }

    .admin-dispatch-card__left {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        min-width: 0;
    }

    .admin-dispatch-avatar {
        width: 5rem;
        height: 5rem;
        border-radius: 1rem;
        border: 4px solid #fff;
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        background: linear-gradient(135deg, #0b1c30, #2170e4);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: 'Manrope', sans-serif;
        font-size: 1.35rem;
        font-weight: 800;
        overflow: hidden;
        flex-shrink: 0;
    }

    .admin-dispatch-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .admin-dispatch-card__title {
        font-size: 2rem;
        line-height: 1.1;
    }

    .admin-dispatch-card__copy {
        margin-top: 0.35rem;
        font-size: 1rem;
        line-height: 1.55;
    }

    .admin-dispatch-card__right {
        text-align: right;
    }

    .admin-dispatch-card__eyebrow {
        color: var(--dispatch-muted-soft);
        font-family: 'Inter', sans-serif;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .admin-dispatch-card__time {
        margin-top: 0.2rem;
        color: var(--dispatch-text);
        font-family: 'Manrope', sans-serif;
        font-size: 2rem;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }

    .admin-dispatch-section {
        display: grid;
        gap: 1rem;
    }

    .admin-dispatch-section__title {
        font-size: 1.65rem;
        line-height: 1.15;
    }

    .admin-dispatch-section__count {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .admin-dispatch-section__count-value {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2rem;
        padding: 0.2rem 0.65rem;
        border-radius: 999px;
        background: var(--dispatch-success-soft);
        color: var(--dispatch-success);
        font-family: 'Inter', sans-serif;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .admin-dispatch-section--unavailable .admin-dispatch-section__count-value {
        background: rgba(194, 198, 214, 0.32);
        color: var(--dispatch-muted);
    }

    .admin-dispatch-link {
        color: var(--dispatch-primary);
        font-family: 'Inter', sans-serif;
        font-size: 0.82rem;
        font-weight: 700;
        text-decoration: none;
    }

    .admin-dispatch-worker-list {
        display: grid;
        align-content: start;
        gap: 1rem;
        max-height: 55vh;
        overflow-y: auto;
        padding-right: 0.5rem;
    }

    .admin-dispatch-worker-list::-webkit-scrollbar {
        width: 6px;
    }
    .admin-dispatch-worker-list::-webkit-scrollbar-track {
        background: transparent;
    }
    .admin-dispatch-worker-list::-webkit-scrollbar-thumb {
        background-color: rgba(194, 198, 214, 0.4);
        border-radius: 10px;
    }

    .admin-dispatch-worker {
        padding: 1.25rem 1rem 1rem 1.1rem;
        border-radius: 1.125rem;
        background: var(--dispatch-surface);
        box-shadow: var(--dispatch-shadow);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
    }

    .admin-dispatch-worker__badge-corner {
        position: absolute;
        top: 0;
        right: 0;
        border-radius: 0 1.125rem 0 0.75rem;
        padding: 0.35rem 0.75rem;
        font-family: 'Inter', sans-serif;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: var(--dispatch-success-soft);
        color: var(--dispatch-success);
        z-index: 1;
    }

    .admin-dispatch-worker__badge-corner--muted {
        background: rgba(194, 198, 214, 0.4);
        color: var(--dispatch-muted);
    }

    .admin-dispatch-worker__badge-corner--danger {
        background: rgba(255, 214, 214, 0.85);
        color: var(--dispatch-danger);
    }

    .admin-dispatch-worker__badge-corner--warning {
        background: rgba(254, 243, 199, 1);
        color: #d97706;
    }

    .admin-dispatch-worker:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 28px rgba(11, 28, 48, 0.08);
    }

    .admin-dispatch-worker.is-unavailable {
        background: rgba(239, 244, 255, 0.72);
        color: rgba(11, 28, 48, 0.6);
    }

    .admin-dispatch-worker__body {
        display: grid;
        gap: 0.95rem;
    }

    .admin-dispatch-worker__identity {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }

    .admin-dispatch-worker__avatar {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 0.75rem;
        background: linear-gradient(135deg, #0b1c30, #2170e4);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: 'Manrope', sans-serif;
        font-size: 1rem;
        font-weight: 800;
        overflow: hidden;
        flex-shrink: 0;
    }

    .admin-dispatch-worker__avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .admin-dispatch-worker__name {
        font-size: 1.1rem;
        line-height: 1.25;
    }

    .admin-dispatch-chip-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-top: 0.55rem;
    }

    .admin-dispatch-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.45rem;
        border-radius: 0.3rem;
        background: var(--dispatch-soft);
        font-size: 0.68rem;
        font-weight: 600;
        line-height: 1.4;
    }

    .admin-dispatch-chip--danger {
        background: rgba(255, 214, 214, 0.85);
        color: var(--dispatch-danger);
    }

    .admin-dispatch-worker__meta-row {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: center;
    }

    .admin-dispatch-worker__meta {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.78rem;
        font-weight: 400;
    }

    .admin-dispatch-rating {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        color: #d97706;
        font-family: 'Inter', sans-serif;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .admin-dispatch-btn {
        border: 0;
        border-radius: 0.75rem;
        min-width: 8rem;
        min-height: 2.875rem;
        padding: 0.75rem 1rem;
        background: linear-gradient(168deg, #0058be 0%, #2170e4 100%);
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        color: #fff;
        font-family: 'Inter', sans-serif;
        font-size: 0.9rem;
        font-weight: 700;
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .admin-dispatch-btn:hover:not(:disabled) {
        transform: translateY(-1px);
    }

    .admin-dispatch-btn:disabled {
        background: #c2c6d6;
        box-shadow: none;
        color: rgba(255, 255, 255, 0.85);
        cursor: not-allowed;
    }

    .admin-dispatch-empty,
    .admin-dispatch-skeleton {
        border-radius: 1rem;
        background: #fff;
        box-shadow: var(--dispatch-shadow);
    }

    .admin-dispatch-empty {
        padding: 2rem;
        text-align: center;
    }

    .admin-dispatch-empty__title {
        margin: 0;
        color: var(--dispatch-text);
        font-family: 'Manrope', sans-serif;
        font-size: 1.1rem;
        font-weight: 700;
    }

    .admin-dispatch-empty__copy {
        margin: 0.45rem 0 0;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .admin-dispatch-skeleton {
        height: 6.5rem;
        position: relative;
        overflow: hidden;
    }

    .admin-dispatch-skeleton::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(239, 244, 255, 0.85) 0%, rgba(255, 255, 255, 0.98) 50%, rgba(239, 244, 255, 0.85) 100%);
        animation: dispatch-shimmer 1.3s infinite;
    }

    @keyframes dispatch-shimmer {
        0% {
            transform: translateX(-100%);
        }

        100% {
            transform: translateX(100%);
        }
    }

    @media (max-width: 1200px) {
        .admin-dispatch-card {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 991.98px) {
        .admin-dispatch-page {
            height: auto;
            overflow: visible;
        }

        .admin-dispatch-shell {
            grid-template-columns: 1fr;
            height: auto;
        }

        .admin-dispatch-queue {
            border-right: 0;
            border-bottom: 1px solid var(--dispatch-line-soft);
            height: auto;
            overflow: visible;
        }

        .admin-dispatch-queue__list {
            overflow-y: visible;
        }

        .admin-dispatch-main {
            height: auto;
            overflow: visible;
        }
    }

    @media (max-width: 767.98px) {

        .admin-dispatch-queue__head,
        .admin-dispatch-queue__list,
        .admin-dispatch-main__inner {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .admin-dispatch-main__inner {
            padding-top: 1.25rem;
            padding-bottom: 1.25rem;
        }

        .admin-dispatch-card,
        .admin-dispatch-worker__top,
        .admin-dispatch-worker__foot,
        .admin-dispatch-section__head {
            gap: 1rem;
        }

        .admin-dispatch-card__left,
        .admin-dispatch-worker__top,
        .admin-dispatch-worker__foot {
            flex-direction: column;
            align-items: flex-start;
        }

        .admin-dispatch-worker__foot .admin-dispatch-btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="admin-dispatch-page">
    <div class="admin-dispatch-shell">
        <aside class="admin-dispatch-queue">
            <div class="admin-dispatch-queue__head">
                <h1 class="admin-dispatch-queue__title">Danh sách chờ</h1>
                <div class="admin-dispatch-queue__meta" id="dispatchQueueMeta">Đang tải hàng chờ điều phối...</div>

                <div class="admin-dispatch-filters">
                    <div class="admin-dispatch-segmented">
                        <button type="button" class="admin-dispatch-segmented__btn" id="dispatchTodayBtn">Hôm nay</button>
                        <select id="dispatchDateFilter" class="admin-dispatch-date" style="outline: none; cursor: pointer; padding: 0 1rem; text-align: center; appearance: none; -webkit-appearance: none;">
                            <option value="">Tất cả ngày</option>
                        </select>
                    </div>

                    <div class="admin-dispatch-search">
                        <i class="fas fa-search"></i>
                        <input
                            type="search"
                            id="dispatchSearch"
                            placeholder="Tìm tên khách hàng..."
                            autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="admin-dispatch-queue__list" id="dispatchQueueList">
                <div class="admin-dispatch-skeleton"></div>
                <div class="admin-dispatch-skeleton"></div>
                <div class="admin-dispatch-skeleton"></div>
            </div>
        </aside>

        <section class="admin-dispatch-main">
            <div class="admin-dispatch-main__inner">
                <div id="dispatchDetailContent">
                    <div class="admin-dispatch-empty">
                        <h2 class="admin-dispatch-empty__title">Chọn một đơn để xem bối cảnh</h2>
                        <p class="admin-dispatch-empty__copy">Thông tin khách hàng, yêu cầu và khung giờ sẽ hiện ở đây.</p>
                    </div>
                </div>

                <section class="admin-dispatch-section">
                    <div class="admin-dispatch-section__head">
                        <div class="admin-dispatch-section__count">
                            <h2 class="admin-dispatch-section__title">Thợ sẵn sàng</h2>
                            <span class="admin-dispatch-section__count-value" id="dispatchCandidateCount">0</span>
                        </div>
                        <a href="/admin/users" class="admin-dispatch-link">Xem tất cả</a>
                    </div>

                    <div class="admin-dispatch-worker-list" id="dispatchCandidatesList">
                        <div class="admin-dispatch-empty">
                            <h3 class="admin-dispatch-empty__title">Đang đợi dữ liệu</h3>
                            <p class="admin-dispatch-empty__copy">Hệ thống sẽ gọi danh sách thợ sau khi bạn chọn một đơn.</p>
                        </div>
                    </div>
                </section>

                <section class="admin-dispatch-section admin-dispatch-section--unavailable">
                    <div class="admin-dispatch-section__head">
                        <div class="admin-dispatch-section__count">
                            <h2 class="admin-dispatch-section__title">Thợ không khả dụng</h2>
                            <span class="admin-dispatch-section__count-value" id="dispatchUnavailableCount">0</span>
                        </div>
                    </div>

                    <div class="admin-dispatch-worker-list" id="dispatchUnavailableList">
                        <div class="admin-dispatch-empty">
                            <h3 class="admin-dispatch-empty__title">Chưa có dữ liệu loại trừ</h3>
                            <p class="admin-dispatch-empty__copy">Lý do không khả dụng sẽ hiển thị tại đây để admin ra quyết định nhanh.</p>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/dispatch.js') }}"></script>
@endpush