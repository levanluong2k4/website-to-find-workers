@extends('layouts.app')

@section('title', 'Dashboard admin - Thợ Tốt')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Fira+Code:wght@500;600;700&family=Fira+Sans:wght@400;500;600;700;800&display=swap');

    :root {
        --admin-bg: #f8fafc;
        --admin-text: #1e293b;
        --admin-muted: #64748b;
        --admin-border: rgba(148, 163, 184, 0.18);
        --admin-panel: #ffffff;
        --admin-strong: #3b82f6;
        --admin-warm: #f97316;
        --admin-green: #16a34a;
        --admin-red: #ef4444;
        --admin-navy: #0f172a;
        --admin-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    }

    body {
        margin: 0;
        min-height: 100vh;
        background:
            radial-gradient(circle at top left, rgba(59, 130, 246, 0.12), transparent 28%),
            radial-gradient(circle at bottom right, rgba(249, 115, 22, 0.08), transparent 22%),
            var(--admin-bg);
        color: var(--admin-text);
        font-family: 'Fira Sans', sans-serif;
    }

    .admin-dashboard {
        min-height: calc(100vh - 7rem);
    }

    .admin-workspace {
        padding: 30px;
        border-radius: 30px;
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.65);
        box-shadow: var(--admin-shadow);
    }

    .admin-topbar {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        margin-bottom: 22px;
    }

    .admin-kicker {
        margin: 0 0 6px;
        color: var(--admin-muted);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .admin-title {
        margin: 0;
        color: var(--admin-navy);
        font-family: 'Fira Code', monospace;
        font-size: clamp(1.8rem, 2vw, 2.5rem);
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .admin-subtitle {
        max-width: 720px;
        margin: 10px 0 0;
        color: var(--admin-muted);
        font-size: 0.96rem;
    }

    .admin-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
        align-items: center;
    }

    .period-switcher {
        display: inline-flex;
        padding: 6px;
        gap: 6px;
        border-radius: 999px;
        background: rgba(226, 232, 240, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.8);
    }

    .period-switcher button,
    .toolbar-link,
    .toolbar-refresh {
        border: 0;
        border-radius: 999px;
        padding: 10px 14px;
        font-size: 0.82rem;
        font-weight: 700;
        line-height: 1;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, color 0.2s ease;
    }

    .period-switcher button {
        background: transparent;
        color: var(--admin-muted);
    }

    .period-switcher button.is-active {
        background: #ffffff;
        color: var(--admin-strong);
        box-shadow: 0 10px 20px rgba(148, 163, 184, 0.22);
    }

    .toolbar-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        color: var(--admin-navy);
        text-decoration: none;
        box-shadow: 0 14px 32px rgba(148, 163, 184, 0.14);
    }

    .toolbar-refresh {
        background: var(--admin-warm);
        color: #ffffff;
        box-shadow: 0 16px 34px rgba(249, 115, 22, 0.24);
    }

    .toolbar-link:hover,
    .toolbar-refresh:hover,
    .period-switcher button:hover {
        transform: translateY(-1px);
    }

    .dashboard-grid {
        display: grid;
        gap: 18px;
    }

    .priority-grid {
        grid-template-columns: minmax(0, 1.45fr) minmax(300px, 0.95fr);
        align-items: start;
        margin-bottom: 18px;
    }

    .panel--full {
        grid-column: 1 / -1;
    }

    .priority-hero {
        padding: 24px;
        background:
            radial-gradient(circle at top right, rgba(249, 115, 22, 0.12), transparent 32%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0.92));
        border: 1px solid rgba(249, 115, 22, 0.14);
    }

    .priority-hero__header {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        margin-bottom: 18px;
    }

    .priority-hero__title {
        margin: 0;
        color: var(--admin-navy);
        font-family: 'Fira Code', monospace;
        font-size: 1.08rem;
        font-weight: 700;
    }

    .priority-hero__subtitle {
        margin: 8px 0 0;
        max-width: 520px;
        color: var(--admin-muted);
        font-size: 0.84rem;
        line-height: 1.55;
    }

    .priority-hero__stat {
        min-width: 150px;
        padding: 14px 16px;
        border-radius: 20px;
        background: rgba(249, 115, 22, 0.08);
        border: 1px solid rgba(249, 115, 22, 0.12);
    }

    .priority-hero__value {
        display: block;
        color: var(--admin-warm);
        font-family: 'Fira Code', monospace;
        font-size: 1.8rem;
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .priority-hero__label {
        display: block;
        margin-top: 6px;
        color: #9a3412;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .priority-hero__chips {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 16px;
    }

    .priority-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 38px;
        padding: 0 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(148, 163, 184, 0.14);
        color: var(--admin-muted);
        font-size: 0.78rem;
        font-weight: 700;
    }

    .priority-chip strong {
        color: var(--admin-navy);
        font-family: 'Fira Code', monospace;
        font-size: 0.82rem;
    }

    .focus-stack {
        display: grid;
        gap: 14px;
    }

    .focus-card {
        padding: 20px 18px;
        border-radius: 22px;
        border: 1px solid rgba(148, 163, 184, 0.14);
        box-shadow: 0 16px 34px rgba(148, 163, 184, 0.08);
    }

    .focus-card__label {
        margin: 0 0 10px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .focus-card__value {
        margin: 0;
        font-family: 'Fira Code', monospace;
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .focus-card__note {
        margin: 8px 0 0;
        font-size: 0.82rem;
        line-height: 1.55;
        font-weight: 600;
    }

    .focus-card--danger {
        background: linear-gradient(180deg, rgba(254, 242, 242, 0.94), rgba(255, 255, 255, 0.98));
        border-color: rgba(239, 68, 68, 0.16);
    }

    .focus-card--danger .focus-card__label,
    .focus-card--danger .focus-card__value {
        color: #b91c1c;
    }

    .focus-card--warning {
        background: linear-gradient(180deg, rgba(255, 247, 237, 0.94), rgba(255, 255, 255, 0.98));
        border-color: rgba(249, 115, 22, 0.16);
    }

    .focus-card--warning .focus-card__label,
    .focus-card--warning .focus-card__value {
        color: #c2410c;
    }

    .focus-card--primary {
        background: linear-gradient(180deg, rgba(239, 246, 255, 0.95), rgba(255, 255, 255, 0.98));
        border-color: rgba(59, 130, 246, 0.18);
    }

    .focus-card--primary .focus-card__label,
    .focus-card--primary .focus-card__value {
        color: #1d4ed8;
    }

    .summary-strip {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        overflow: hidden;
        border-radius: 24px;
        background: var(--admin-panel);
        border: 1px solid var(--admin-border);
        margin-bottom: 18px;
    }

    .summary-card {
        padding: 20px 22px;
        border-right: 1px solid rgba(148, 163, 184, 0.16);
    }

    .summary-card:last-child {
        border-right: 0;
    }

    .summary-label,
    .panel-kicker {
        margin: 0 0 8px;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--admin-muted);
    }

    .summary-value,
    .metric-value {
        margin: 0;
        color: var(--admin-navy);
        font-family: 'Fira Code', monospace;
        font-size: clamp(1.2rem, 1.4vw, 1.6rem);
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .summary-note,
    .panel-note {
        margin: 6px 0 0;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .tone-positive {
        color: var(--admin-green);
    }

    .tone-warning {
        color: var(--admin-warm);
    }

    .tone-danger {
        color: var(--admin-red);
    }

    .tone-muted {
        color: var(--admin-muted);
    }

    .hero-grid {
        grid-template-columns: minmax(0, 1.85fr) minmax(290px, 0.82fr);
    }

    .mid-grid {
        grid-template-columns: minmax(0, 1.85fr) minmax(280px, 0.82fr);
    }

    .bottom-grid {
        grid-template-columns: minmax(0, 1.7fr) minmax(300px, 0.9fr);
    }

    .panel {
        padding: 22px;
        border-radius: 24px;
        background: var(--admin-panel);
        border: 1px solid var(--admin-border);
        box-shadow: 0 18px 42px rgba(148, 163, 184, 0.08);
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 18px;
    }

    .panel-title {
        margin: 0;
        color: var(--admin-navy);
        font-family: 'Fira Code', monospace;
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: -0.03em;
    }

    .panel-subtitle {
        margin: 6px 0 0;
        color: var(--admin-muted);
        font-size: 0.82rem;
    }

    .panel-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 999px;
        background: rgba(22, 163, 74, 0.1);
        color: var(--admin-green);
        font-size: 0.74rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .revenue-highlight {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 16px;
        margin-bottom: 18px;
        padding: 20px;
        border-radius: 22px;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(59, 130, 246, 0.04));
    }

    .revenue-chip-group {
        display: grid;
        gap: 10px;
        align-content: start;
    }

    .revenue-chip {
        padding: 12px 14px;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid rgba(148, 163, 184, 0.16);
    }

    .revenue-chip strong,
    .revenue-chip span {
        display: block;
    }

    .revenue-chip strong {
        color: var(--admin-navy);
        font-size: 0.78rem;
    }

    .revenue-chip span {
        margin-top: 4px;
        color: var(--admin-muted);
        font-size: 0.76rem;
    }

    .chart-shell {
        position: relative;
        padding: 18px 16px 14px;
        border-radius: 20px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: linear-gradient(180deg, rgba(248, 250, 252, 0.8), #ffffff);
    }

    .chart-label {
        margin: 0 0 16px;
        color: var(--admin-muted);
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .revenue-chart {
        width: 100%;
        height: 220px;
        display: block;
    }

    .chart-axis {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
        margin-top: 6px;
        color: var(--admin-muted);
        font-size: 0.74rem;
        font-weight: 700;
        text-align: center;
    }

    .priority-list,
    .queue-list,
    .watch-list,
    .complaint-list {
        display: grid;
        gap: 10px;
    }

    .priority-item,
    .queue-item,
    .watch-item,
    .complaint-item {
        border-radius: 18px;
        padding: 14px 16px;
        border: 1px solid rgba(148, 163, 184, 0.16);
    }

    .priority-item {
        background: linear-gradient(180deg, rgba(248, 250, 252, 0.85), #ffffff);
    }

    .priority-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 6px;
    }

    .priority-tag {
        display: inline-flex;
        align-items: center;
        padding: 5px 9px;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .priority-title {
        margin: 0;
        color: var(--admin-navy);
        font-size: 0.9rem;
        font-weight: 700;
    }

    .priority-detail {
        margin: 0;
        color: var(--admin-muted);
        font-size: 0.82rem;
        line-height: 1.55;
    }

    .priority-footer {
        margin-top: 12px;
        color: var(--admin-muted);
        font-size: 0.72rem;
    }

    .priority-item.warning .priority-tag,
    .queue-item.warning {
        color: #b45309;
        background: rgba(245, 158, 11, 0.12);
    }

    .priority-item.info .priority-tag,
    .queue-item.info {
        color: #1d4ed8;
        background: rgba(59, 130, 246, 0.12);
    }

    .priority-item.danger .priority-tag,
    .queue-item.danger,
    .complaint-item.danger {
        color: #b91c1c;
        background: rgba(239, 68, 68, 0.1);
    }

    .complaint-item.warning {
        color: #9a3412;
        background: rgba(249, 115, 22, 0.1);
    }

    .booking-stat-grid,
    .worker-stat-grid,
    .complaint-stat-grid {
        display: grid;
        gap: 12px;
    }

    .booking-stat-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-bottom: 14px;
    }

    .worker-stat-grid,
    .complaint-stat-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-bottom: 14px;
    }

    .stat-tile {
        padding: 14px 16px;
        border-radius: 18px;
    }

    .stat-tile strong {
        display: block;
        color: var(--admin-muted);
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .stat-tile span {
        display: block;
        margin-top: 6px;
        color: var(--admin-navy);
        font-family: 'Fira Code', monospace;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .stat-tile.neutral {
        background: rgba(59, 130, 246, 0.08);
    }

    .stat-tile.warning {
        background: rgba(249, 115, 22, 0.1);
    }

    .stat-tile.info {
        background: rgba(59, 130, 246, 0.12);
    }

    .stat-tile.success {
        background: rgba(22, 163, 74, 0.1);
    }

    .stat-tile.danger {
        background: rgba(239, 68, 68, 0.1);
    }

    .watch-item {
        color: var(--admin-muted);
        font-size: 0.82rem;
        line-height: 1.55;
        background: linear-gradient(180deg, rgba(248, 250, 252, 0.85), #ffffff);
    }

    .watch-item::before {
        content: '•';
        margin-right: 8px;
        color: var(--admin-strong);
    }

    .revenue-table {
        width: 100%;
        border-collapse: collapse;
    }

    .revenue-table th,
    .revenue-table td {
        padding: 14px 10px;
        text-align: left;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
    }

    .revenue-table th {
        color: var(--admin-muted);
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .revenue-table td {
        color: var(--admin-navy);
        font-size: 0.86rem;
        vertical-align: middle;
    }

    .revenue-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .table-code {
        color: var(--admin-navy);
        font-family: 'Fira Code', monospace;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .table-note {
        display: block;
        margin-top: 4px;
        color: var(--admin-muted);
        font-size: 0.76rem;
    }

    .table-money {
        color: var(--admin-navy);
        font-family: 'Fira Code', monospace;
        font-weight: 700;
    }

    .table-money--warm {
        color: var(--admin-warm);
    }

    .complaint-item__code {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: inherit;
        font-family: 'Fira Code', monospace;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .complaint-item__summary {
        margin: 10px 0 0;
        color: var(--admin-navy);
        font-size: 0.82rem;
        line-height: 1.55;
    }

    .admin-empty {
        color: var(--admin-muted);
        font-size: 0.82rem;
    }

    .is-loading [data-loading-text] {
        color: transparent;
        background: linear-gradient(90deg, rgba(226, 232, 240, 0.7), rgba(226, 232, 240, 0.28), rgba(226, 232, 240, 0.7));
        background-size: 200% 100%;
        border-radius: 10px;
        animation: dashboardPulse 1.4s linear infinite;
    }

    .is-loading .chart-shell {
        overflow: hidden;
    }

    .is-loading .chart-shell::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.66), transparent);
        transform: translateX(-100%);
        animation: dashboardShimmer 1.4s linear infinite;
    }

    @keyframes dashboardPulse {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }

    @keyframes dashboardShimmer {
        to {
            transform: translateX(100%);
        }
    }

    @media (max-width: 1199.98px) {
        .priority-grid,
        .summary-strip,
        .booking-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .hero-grid,
        .mid-grid,
        .bottom-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 991.98px) {
        .priority-hero__header {
            flex-direction: column;
        }

        .admin-topbar {
            flex-direction: column;
        }

        .admin-toolbar {
            justify-content: flex-start;
        }
    }

    @media (max-width: 767.98px) {
        .admin-dashboard {
            min-height: auto;
        }

        .admin-workspace {
            padding: 18px;
            border-radius: 24px;
        }

        .priority-grid,
        .summary-strip,
        .booking-stat-grid,
        .worker-stat-grid,
        .complaint-stat-grid {
            grid-template-columns: 1fr;
        }

        .revenue-highlight {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container py-5">
    <div class="admin-dashboard is-loading" id="adminDashboard">
        <main class="admin-workspace">
            <div class="admin-topbar">
                <div>
                    <p class="admin-kicker">Admin Control Center</p>
                    <h1 class="admin-title">Dashboard admin</h1>
                    <p class="admin-subtitle">
                        Theo dõi doanh thu, lịch hẹn, đội thợ và phản ánh của khách hàng trong một màn hình điều hành.
                    </p>
                </div>

                <div class="admin-toolbar">
                    <div class="period-switcher" role="group" aria-label="Chọn khoảng thời gian">
                        <button type="button" class="js-period-btn" data-period="today">Hôm nay</button>
                        <button type="button" class="js-period-btn is-active" data-period="7d">7 ngày</button>
                        <button type="button" class="js-period-btn" data-period="30d">30 ngày</button>
                    </div>
                    <a class="toolbar-link" href="/admin/bookings">Xem báo cáo</a>
                    <button type="button" class="toolbar-refresh" id="btnRefresh">Làm mới</button>
                </div>
            </div>

            <section class="dashboard-grid priority-grid">
                <article class="panel priority-hero">
                    <div class="priority-hero__header">
                        <div>
                            <p class="panel-kicker">Trọng tâm vận hành</p>
                            <h2 class="priority-hero__title">Những việc admin nên nhìn đầu tiên</h2>
                            <p class="priority-hero__subtitle">
                                Ưu tiên xử lý các đơn đang chờ, khiếu nại mới và hồ sơ thợ tồn đọng trước khi xem biểu đồ hay báo cáo chi tiết.
                            </p>
                        </div>

                        <div class="priority-hero__stat">
                            <span class="priority-hero__value" id="focusPendingBookingsValue" data-loading-text>0</span>
                            <span class="priority-hero__label" id="focusPendingBookingsNote" data-loading-text>đơn chờ xác nhận</span>
                        </div>
                    </div>

                    <div class="priority-hero__chips">
                        <div class="priority-chip">Khiếu nại mới <strong id="priorityComplaintChip" data-loading-text>0</strong></div>
                        <div class="priority-chip">Hồ sơ chờ duyệt <strong id="priorityWorkerChip" data-loading-text>0</strong></div>
                        <div class="priority-chip">Doanh thu hôm nay <strong id="priorityRevenueChip" data-loading-text>0 đ</strong></div>
                    </div>

                    <div class="priority-list" id="alertList">
                        <div class="priority-item warning">
                            <div class="priority-top">
                                <span class="priority-tag">P1</span>
                                <h4 class="priority-title">Đang tải dữ liệu</h4>
                            </div>
                            <p class="priority-detail">Hệ thống đang lấy các tín hiệu vận hành mới nhất.</p>
                        </div>
                    </div>

                    <p class="priority-footer" id="alertFooter" data-loading-text>Cập nhật 00:00 · Nguồn: dữ liệu dashboard admin</p>
                </article>

                <div class="focus-stack">
                    <article class="focus-card focus-card--danger">
                        <p class="focus-card__label">Khiếu nại mới</p>
                        <h2 class="focus-card__value" id="focusComplaintsValue" data-loading-text>0</h2>
                        <p class="focus-card__note" id="focusComplaintsNote" data-loading-text>0 vụ mức ưu tiên cao</p>
                    </article>

                    <article class="focus-card focus-card--warning">
                        <p class="focus-card__label">Hồ sơ thợ chờ duyệt</p>
                        <h2 class="focus-card__value" id="focusWorkersPendingValue" data-loading-text>0</h2>
                        <p class="focus-card__note" id="focusWorkersPendingNote" data-loading-text>0 hồ sơ đang chờ admin duyệt</p>
                    </article>

                    <article class="focus-card focus-card--primary">
                        <p class="focus-card__label">Doanh thu hôm nay</p>
                        <h2 class="focus-card__value" id="focusRevenueValue" data-loading-text>0 đ</h2>
                        <p class="focus-card__note" id="focusRevenueNote" data-loading-text>0% so với hôm qua</p>
                    </article>
                </div>
            </section>

            <section class="dashboard-grid summary-strip">
                <article class="summary-card">
                    <p class="summary-label">Doanh thu hôm nay</p>
                    <h2 class="summary-value" id="summaryRevenueToday" data-loading-text>0 đ</h2>
                    <p class="summary-note tone-positive" id="summaryRevenueNote" data-loading-text>0% so với hôm qua</p>
                </article>
                <article class="summary-card">
                    <p class="summary-label">Đơn đặt lịch hôm nay</p>
                    <h2 class="summary-value" id="summaryBookingsToday" data-loading-text>0</h2>
                    <p class="summary-note tone-warning" id="summaryBookingsNote" data-loading-text>0 đơn cần xác nhận</p>
                </article>
                <article class="summary-card">
                    <p class="summary-label">Hoa hồng hệ thống</p>
                    <h2 class="summary-value" id="summaryCommission" data-loading-text>0 đ</h2>
                    <p class="summary-note tone-muted" id="summaryCommissionNote" data-loading-text>0% giao dịch chuyển khoản</p>
                </article>
                <article class="summary-card">
                    <p class="summary-label">Khiếu nại mới</p>
                    <h2 class="summary-value" id="summaryComplaints" data-loading-text>0</h2>
                    <p class="summary-note tone-danger" id="summaryComplaintsNote" data-loading-text>0 vụ mức ưu tiên cao</p>
                </article>
            </section>

            <section class="dashboard-grid hero-grid">
                <article class="panel panel--full">
                    <div class="panel-header">
                        <div>
                            <p class="panel-kicker">Doanh thu theo xu hướng</p>
                            <h3 class="panel-title">Doanh thu theo xu hướng</h3>
                            <p class="panel-subtitle">
                                Line chart 7 ngày để nhìn nhanh xu hướng tăng trưởng và dao động doanh thu.
                            </p>
                        </div>
                        <span class="panel-badge" id="metaUpdatedAt" data-loading-text>Cập nhật 00:00</span>
                    </div>

                    <div class="revenue-highlight">
                        <div>
                            <p class="summary-label">Tổng doanh thu <span id="metaPeriodLabel" data-loading-text>7 ngày</span></p>
                            <h2 class="metric-value" id="revenuePeriodTotal" data-loading-text>0 đ</h2>
                            <p class="panel-note tone-positive" id="revenuePeriodNote" data-loading-text>0% so với kỳ trước</p>
                        </div>

                        <div class="revenue-chip-group">
                            <div class="revenue-chip">
                                <strong>Top dịch vụ</strong>
                                <span id="revenueTopService" data-loading-text>Chưa có dữ liệu</span>
                            </div>
                            <div class="revenue-chip">
                                <strong>Tỷ trọng chuyển khoản</strong>
                                <span id="revenueTransferShare" data-loading-text>0% doanh thu đến từ chuyển khoản</span>
                            </div>
                        </div>
                    </div>

                    <div class="chart-shell">
                        <p class="chart-label">Revenue trend</p>
                        <svg class="revenue-chart" id="revenueChart" viewBox="0 0 640 220" preserveAspectRatio="none" aria-label="Biểu đồ doanh thu"></svg>
                        <div class="chart-axis" id="revenueChartLabels"></div>
                    </div>
                </article>
            </section>

            <section class="dashboard-grid mid-grid">
                <article class="panel">
                    <div class="panel-header">
                        <div>
                            <p class="panel-kicker">Đơn đặt lịch</p>
                            <h3 class="panel-title">Đơn đặt lịch</h3>
                            <p class="panel-subtitle">
                                Tổng quan đơn trong ngày và các đơn cần admin can thiệp ngay lập tức.
                            </p>
                        </div>
                    </div>

                    <div class="booking-stat-grid">
                        <div class="stat-tile neutral">
                            <strong>Hôm nay</strong>
                            <span id="bookingsTodayTotal" data-loading-text>0</span>
                        </div>
                        <div class="stat-tile warning">
                            <strong>Chờ xác nhận</strong>
                            <span id="bookingsPendingTotal" data-loading-text>0</span>
                        </div>
                        <div class="stat-tile info">
                            <strong>Đang làm</strong>
                            <span id="bookingsProgressTotal" data-loading-text>0</span>
                        </div>
                        <div class="stat-tile success">
                            <strong>Hoàn thành</strong>
                            <span id="bookingsCompletedTotal" data-loading-text>0</span>
                        </div>
                    </div>

                    <div class="queue-list" id="bookingQueueList">
                        <div class="queue-item warning">Đang tải hàng đợi vận hành...</div>
                    </div>
                </article>

                <article class="panel">
                    <div class="panel-header">
                        <div>
                            <p class="panel-kicker">Đội thợ</p>
                            <h3 class="panel-title">Đội thợ</h3>
                            <p class="panel-subtitle">
                                Sức khỏe nguồn cung và các hồ sơ admin cần theo dõi sát trong ca.
                            </p>
                        </div>
                    </div>

                    <div class="booking-stat-grid">
                        <div class="stat-tile neutral">
                            <strong>Tổng số thợ</strong>
                            <span id="workersTotal" data-loading-text>0</span>
                        </div>
                        <div class="stat-tile success">
                            <strong>Đang hoạt động</strong>
                            <span id="workersActive" data-loading-text>0</span>
                        </div>
                        <div class="stat-tile warning">
                            <strong>Chờ duyệt</strong>
                            <span id="workersPending" data-loading-text>0</span>
                        </div>
                        <div class="stat-tile info">
                            <strong>Điểm thấp</strong>
                            <span id="workersLowRating" data-loading-text>0</span>
                        </div>
                    </div>

                    <div class="watch-list" id="workerWatchList">
                        <div class="watch-item">Đang tải các tín hiệu cần theo dõi...</div>
                    </div>
                </article>
            </section>

            <section class="dashboard-grid bottom-grid">
                <article class="panel">
                    <div class="panel-header">
                        <div>
                            <p class="panel-kicker">Chi tiết doanh thu</p>
                            <h3 class="panel-title">Chi tiết doanh thu</h3>
                            <p class="panel-subtitle">
                                Bảng nhanh để đối soát mã đơn, thời gian, tổng tiền và hoa hồng.
                            </p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="revenue-table">
                            <thead>
                                <tr>
                                    <th>Đơn / dịch vụ</th>
                                    <th>Ngày</th>
                                    <th>Tổng tiền</th>
                                    <th>Hoa hồng</th>
                                </tr>
                            </thead>
                            <tbody id="revenueTableBody">
                                <tr>
                                    <td colspan="4" class="admin-empty">Đang tải dữ liệu doanh thu...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="panel">
                    <div class="panel-header">
                        <div>
                            <p class="panel-kicker">Khiếu nại / phản ánh</p>
                            <h3 class="panel-title">Khiếu nại / phản ánh</h3>
                            <p class="panel-subtitle">
                                Tổng hợp phản hồi khách hàng dựa trên đánh giá thấp và đơn hủy có lý do.
                            </p>
                        </div>
                    </div>

                    <div class="complaint-stat-grid">
                        <div class="stat-tile danger">
                            <strong>Mới</strong>
                            <span id="complaintsNew" data-loading-text>0</span>
                        </div>
                        <div class="stat-tile warning">
                            <strong>Đánh giá thấp</strong>
                            <span id="complaintsLowRating" data-loading-text>0</span>
                        </div>
                        <div class="stat-tile neutral">
                            <strong>Đơn hủy</strong>
                            <span id="complaintsCanceled" data-loading-text>0</span>
                        </div>
                    </div>

                    <div class="complaint-list" id="complaintList">
                        <div class="complaint-item danger">
                            <div class="complaint-item__code">Đang tải phản ánh</div>
                            <p class="complaint-item__summary">Hệ thống đang gom phản hồi từ đánh giá và đơn đặt lịch.</p>
                        </div>
                    </div>
                </article>
            </section>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/dashboard.js') }}"></script>
@endpush
