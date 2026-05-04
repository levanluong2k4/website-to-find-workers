@extends('layouts.app')
@section('title', 'Tổng quan - Thợ Tốt NTU')

@push('styles')
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "surface-container-lowest": "#ffffff",
            "error-container": "#ffdad6",
            "on-tertiary-fixed": "#2c1600",
            "tertiary": "#8a5100",
            "on-tertiary-fixed-variant": "#693c00",
            "primary-fixed-dim": "#89ceff",
            "surface": "#f6fafc",
            "surface-container-high": "#e5e9eb",
            "outline": "#6e7881",
            "inverse-primary": "#89ceff",
            "primary-fixed": "#c9e6ff",
            "on-primary-fixed-variant": "#004c6e",
            "secondary-container": "#b8dffe",
            "tertiary-container": "#de8712",
            "on-background": "#171c1e",
            "tertiary-fixed-dim": "#ffb86e",
            "on-surface": "#171c1e",
            "surface-container-low": "#f0f4f6",
            "surface-container-highest": "#dfe3e5",
            "on-tertiary": "#ffffff",
            "inverse-surface": "#2c3133",
            "outline-variant": "#bec8d2",
            "on-secondary-fixed": "#001e2f",
            "surface-dim": "#d6dbdd",
            "on-secondary": "#ffffff",
            "secondary-fixed-dim": "#a5cbe9",
            "surface-bright": "#f6fafc",
            "error": "#ba1a1a",
            "surface-tint": "#006591",
            "on-surface-variant": "#3e4850",
            "background": "#f6fafc",
            "inverse-on-surface": "#edf1f3",
            "primary": "#006591",
            "on-primary-fixed": "#001e2f",
            "surface-variant": "#dfe3e5",
            "on-primary-container": "#003751",
            "primary-container": "#0ea5e9",
            "on-tertiary-container": "#4d2b00",
            "secondary": "#3c627d",
            "surface-container": "#eaeef0",
            "secondary-fixed": "#c9e6ff",
            "on-secondary-container": "#3d637d",
            "on-primary": "#ffffff",
            "on-error-container": "#93000a",
            "on-error": "#ffffff",
            "on-secondary-fixed-variant": "#234b64",
            "tertiary-fixed": "#ffdcbd"
          },
          fontFamily: {
            "headline": ["Inter"],
            "body": ["Inter"],
            "label": ["Inter"]
          },
          borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
        },
      },
    }
</script>
<style>
    body { font-family: 'Inter', sans-serif; }
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        vertical-align: middle;
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
    }

    .worker-dashboard-main {
        margin-left: 240px;
    }

    .worker-dashboard-topbar-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .worker-priority-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .worker-priority-card {
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        min-height: 0;
        padding: 1.25rem;
        border-radius: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.16);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }

    .worker-priority-card::after {
        content: '';
        position: absolute;
        right: -2rem;
        bottom: -2.5rem;
        width: 7rem;
        height: 7rem;
        border-radius: 9999px;
        opacity: 0.8;
        pointer-events: none;
    }

    .worker-priority-card--revenue {
        background: linear-gradient(145deg, #082f49 0%, #0369a1 48%, #38bdf8 100%);
        color: #f0f9ff;
    }

    .worker-priority-card--revenue::after {
        background: rgba(255, 255, 255, 0.16);
    }

    .worker-priority-card--schedule {
        background: linear-gradient(145deg, #f8fbff 0%, #e0f2fe 52%, #ffffff 100%);
        color: #0f172a;
    }

    .worker-priority-card--schedule::after {
        background: rgba(14, 165, 233, 0.14);
    }

    .worker-priority-card--pending {
        background: linear-gradient(145deg, #fff7ed 0%, #ffedd5 52%, #ffffff 100%);
        color: #7c2d12;
    }

    .worker-priority-card--pending::after {
        background: rgba(249, 115, 22, 0.14);
    }

    .worker-priority-card__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        width: fit-content;
        padding: 0.45rem 0.8rem;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .worker-priority-card--revenue .worker-priority-card__eyebrow {
        background: rgba(255, 255, 255, 0.14);
        color: rgba(240, 249, 255, 0.92);
    }

    .worker-priority-card--schedule .worker-priority-card__eyebrow {
        background: rgba(14, 165, 233, 0.12);
        color: #075985;
    }

    .worker-priority-card--pending .worker-priority-card__eyebrow {
        background: rgba(249, 115, 22, 0.12);
        color: #9a3412;
    }

    .worker-priority-card__value {
        font-size: clamp(2.1rem, 3.4vw, 2.9rem);
        line-height: 0.95;
        font-weight: 800;
        letter-spacing: -0.05em;
    }

    .worker-priority-card__meta {
        max-width: none;
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.4;
    }

    .worker-priority-card__hint {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.82;
    }

    .worker-priority-preview {
        display: grid;
        gap: 0.5rem;
        padding-top: 0;
        margin-top: 0.25rem;
    }

    .worker-priority-card--revenue .worker-priority-preview {
        border-top: 0;
    }

    .worker-priority-card--schedule .worker-priority-preview,
    .worker-priority-card--pending .worker-priority-preview {
        border-top: 0;
    }

    .worker-priority-preview-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.7rem 0.8rem;
        border-radius: 1rem;
        color: inherit;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }

    .worker-priority-card--revenue .worker-priority-preview-item {
        background: rgba(255, 255, 255, 0.1);
    }

    .worker-priority-card--schedule .worker-priority-preview-item,
    .worker-priority-card--pending .worker-priority-preview-item {
        background: rgba(255, 255, 255, 0.76);
    }

    .worker-priority-preview-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.08);
    }

    .worker-priority-preview-copy {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        min-width: 0;
    }

    .worker-priority-preview-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 2rem;
        height: 2rem;
        border-radius: 9999px;
    }

    .worker-priority-card--revenue .worker-priority-preview-icon {
        background: rgba(255, 255, 255, 0.14);
    }

    .worker-priority-card--schedule .worker-priority-preview-icon {
        background: rgba(14, 165, 233, 0.12);
        color: #0284c7;
    }

    .worker-priority-card--pending .worker-priority-preview-icon {
        background: rgba(249, 115, 22, 0.12);
        color: #ea580c;
    }

    .worker-priority-preview-title {
        display: block;
        font-size: 0.92rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .worker-priority-preview-subtitle {
        display: none;
    }

    .worker-priority-preview-chip {
        flex-shrink: 0;
        padding: 0.35rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.64rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .worker-priority-card--revenue .worker-priority-preview-chip {
        background: rgba(255, 255, 255, 0.14);
        color: #f0f9ff;
    }

    .worker-priority-card--schedule .worker-priority-preview-chip {
        background: rgba(14, 165, 233, 0.12);
        color: #0c4a6e;
    }

    .worker-priority-card--pending .worker-priority-preview-chip {
        background: rgba(249, 115, 22, 0.12);
        color: #9a3412;
    }

    .dashboard-map-shell {
        position: relative;
        overflow: hidden;
        border-radius: 2rem;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 20px 54px rgba(15, 23, 42, 0.07);
    }

    .dashboard-map-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.5rem 0.75rem;
    }

    .dashboard-map-meta {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .dashboard-map-stage {
        position: relative;
        min-height: 28rem;
        padding: 0 1.5rem 1.5rem;
    }

    .dashboard-map-canvas {
        width: 100%;
        height: 28rem;
        border-radius: 1.5rem;
        overflow: hidden;
        background: linear-gradient(135deg, #dbeafe 0%, #f8fbff 55%, #ffffff 100%);
    }

    .dashboard-map-canvas .leaflet-control-attribution,
    .dashboard-map-canvas .leaflet-control-zoom {
        display: none;
    }

    .dashboard-map-status {
        position: absolute;
        top: 1.25rem;
        left: 2.5rem;
        z-index: 450;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.7rem 0.9rem;
        border-radius: 9999px;
        background: rgba(15, 23, 42, 0.8);
        color: #f8fafc;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        backdrop-filter: blur(12px);
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.18);
    }

    .dashboard-map-legend {
        position: absolute;
        top: 1.25rem;
        right: 2.5rem;
        z-index: 450;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: flex-end;
    }

    .dashboard-map-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.6rem 0.8rem;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.86);
        color: #0f172a;
        font-size: 0.72rem;
        font-weight: 700;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(12px);
    }

    .dashboard-map-chip__dot {
        width: 0.6rem;
        height: 0.6rem;
        border-radius: 9999px;
        flex-shrink: 0;
    }

    .dashboard-map-chip__dot--worker {
        background: #0284c7;
    }

    .dashboard-map-chip__dot--mine {
        background: #0f766e;
    }

    .dashboard-map-chip__dot--open {
        background: #ea580c;
    }

    .dashboard-map-card {
        position: absolute;
        left: 2.5rem;
        bottom: 2.5rem;
        z-index: 450;
        width: min(24rem, calc(100% - 5rem));
        padding: 1rem;
        border-radius: 1.4rem;
        background: rgba(255, 255, 255, 0.94);
        color: #0f172a;
        box-shadow: 0 28px 60px rgba(15, 23, 42, 0.16);
        backdrop-filter: blur(18px);
    }

    .dashboard-map-card.is-hidden {
        display: none;
    }

    .dashboard-map-card__eyebrow {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .dashboard-map-card__status {
        padding: 0.35rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .dashboard-map-card__status--mine {
        background: rgba(15, 118, 110, 0.1);
        color: #0f766e;
    }

    .dashboard-map-card__status--open {
        background: rgba(234, 88, 12, 0.12);
        color: #c2410c;
    }

    .dashboard-map-card__title {
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.35;
    }

    .dashboard-map-card__meta {
        margin-top: 0.35rem;
        color: #475569;
        font-size: 0.86rem;
        line-height: 1.5;
    }

    .dashboard-map-card__stack {
        display: grid;
        gap: 0.55rem;
        margin-top: 0.9rem;
    }

    .dashboard-map-card__line {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        color: #334155;
        font-size: 0.84rem;
    }

    .dashboard-map-card__line .material-symbols-outlined {
        font-size: 1rem;
        color: #0284c7;
    }

    .dashboard-map-card__actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .dashboard-map-card__actions > * {
        flex: 1;
    }

    .dashboard-map-primary-btn,
    .dashboard-map-secondary-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        min-height: 2.75rem;
        padding: 0.8rem 1rem;
        border-radius: 0.95rem;
        border: 1px solid transparent;
        font-size: 0.84rem;
        font-weight: 700;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
    }

    .dashboard-map-primary-btn {
        background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
        color: #ffffff;
        box-shadow: 0 14px 28px rgba(2, 132, 199, 0.22);
    }

    .dashboard-map-primary-btn:hover,
    .dashboard-map-secondary-btn:hover {
        transform: translateY(-1px);
    }

    .dashboard-map-primary-btn[disabled] {
        cursor: default;
        opacity: 0.55;
        box-shadow: none;
    }

    .dashboard-map-secondary-btn {
        border-color: rgba(148, 163, 184, 0.28);
        background: #ffffff;
        color: #0f172a;
    }

    .dashboard-map-empty {
        position: absolute;
        inset: 4.75rem 2.5rem 2.5rem;
        z-index: 410;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.9rem;
        border-radius: 1.5rem;
        border: 1px dashed rgba(148, 163, 184, 0.34);
        background: rgba(255, 255, 255, 0.82);
        color: #475569;
        text-align: center;
        padding: 2rem;
    }

    .dashboard-map-empty .material-symbols-outlined {
        font-size: 2rem;
        color: #0284c7;
    }

    .dashboard-map-empty.is-hidden {
        display: none;
    }

    .dashboard-map-marker {
        position: relative;
        width: 2.45rem;
        height: 2.45rem;
        border-radius: 9999px;
        border: 3px solid #ffffff;
        box-shadow: 0 14px 24px rgba(15, 23, 42, 0.18);
    }

    .dashboard-map-marker::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: -0.55rem;
        width: 0.8rem;
        height: 0.8rem;
        background: inherit;
        transform: translateX(-50%) rotate(45deg);
        border-radius: 0.15rem;
    }

    .dashboard-map-marker::before {
        content: '';
        position: absolute;
        inset: 0.5rem;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.92);
        z-index: 1;
    }

    .dashboard-map-marker > span {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        color: #0f172a;
        z-index: 2;
    }

    .dashboard-map-marker--worker {
        background: #0284c7;
    }

    .dashboard-map-marker--mine {
        background: #0f766e;
    }

    .dashboard-map-marker--open {
        background: #ea580c;
    }

    @media (max-width: 768px) {
        .worker-dashboard-main {
            margin-left: 0;
            padding-top: 96px;
        }

        .worker-dashboard-topbar {
            position: static;
            width: auto;
            margin: 0 1rem 1rem;
            padding: 1.15rem;
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 1.5rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.92));
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
        }

        .worker-dashboard-topbar-meta {
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .worker-dashboard-topbar-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
            gap: 0.75rem;
        }

        .worker-dashboard-topbar-actions > * {
            width: 100%;
            min-width: 0;
            justify-content: center;
        }

        .worker-dashboard-content {
            padding: 0 1rem 6.75rem;
        }

        .worker-priority-card {
            min-height: 0;
            padding: 1rem;
            border-radius: 1.25rem;
        }

        .worker-priority-preview-item {
            padding: 0.65rem 0.75rem;
        }

        .dashboard-map-head {
            padding: 1rem 1rem 0.75rem;
        }

        .dashboard-map-stage {
            min-height: 31rem;
            padding: 0 1rem 1rem;
        }

        .dashboard-map-canvas {
            height: 31rem;
            border-radius: 1.25rem;
        }

        .dashboard-map-status {
            top: 1rem;
            left: 2rem;
            right: 2rem;
            justify-content: center;
        }

        .dashboard-map-legend {
            top: 4.4rem;
            right: 2rem;
            left: 2rem;
            justify-content: flex-start;
        }

        .dashboard-map-card {
            left: 2rem;
            right: 2rem;
            bottom: 2rem;
            width: auto;
        }

        .dashboard-map-empty {
            inset: 7.5rem 2rem 2rem;
        }
    }

    @media (max-width: 640px) {
        .worker-dashboard-topbar-actions {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 1279px) {
        .worker-priority-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="bg-surface text-on-surface min-h-screen flex" style="background-color: var(--page-bg, #f6fafc);">
    <!-- Navigation Drawer -->
    <x-worker-sidebar />

    <!-- Main Content Canvas -->
    <main class="worker-dashboard-main flex-1 flex flex-col min-h-screen">
        <!-- TopAppBar -->
        <header class="worker-dashboard-topbar flex justify-between items-center w-full px-8 py-6 sticky top-0 bg-[#f6fafc] dark:bg-slate-950 z-40 transition-opacity">
            <div class="flex flex-col">
                <h1 class="text-2xl font-semibold text-on-surface">Dashboard</h1>
                <div class="worker-dashboard-topbar-meta flex items-center gap-3 mt-1">
                    <p class="text-sm text-on-surface-variant font-medium" id="headerDate">Đang tải ngày...</p>
                    <span class="px-2 py-0.5 bg-secondary-container text-on-secondary-container text-[10px] font-bold rounded-full flex items-center gap-1">
                        <span class="w-1 h-1 rounded-full bg-primary animate-pulse"></span>
                        <span id="liveStatusText">ĐANG CẬP NHẬT...</span>
                    </span>
                </div>
            </div>
            <div class="worker-dashboard-topbar-actions">
                <button id="dashboardRefreshButton" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-container-low hover:bg-surface-container-high transition-colors text-on-surface-variant">
                    <span class="material-symbols-outlined">refresh</span>
                </button>
                <a href="/worker/my-bookings?status=pending" class="bg-gradient-to-br from-primary-container to-primary text-white px-6 py-2.5 rounded-xl font-semibold flex items-center gap-2 shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity">
                    <span class="material-symbols-outlined text-lg">add</span> Việc mới
                </a>
            </div>
        </header>

        <div class="worker-dashboard-content px-8 pb-12 space-y-8">
            <!-- Priority Summary Section -->
            <section class="worker-priority-grid">
                <article class="worker-priority-card worker-priority-card--revenue">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-5">
                            <span class="worker-priority-card__eyebrow">
                                <span class="material-symbols-outlined text-base">payments</span>
                                Doanh thu hôm nay
                            </span>
                            <div class="space-y-3">
                                <p class="worker-priority-card__value" id="priorityTodayRevenue">0 ₫</p>
                                <p class="worker-priority-card__meta text-white/90" id="priorityRevenueMeta">Chưa có doanh thu hôm nay.</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-[2.25rem] text-white/70">trending_up</span>
                    </div>
                    <p class="worker-priority-card__hint text-white/80" id="priorityRevenueHint">0 đơn chờ thanh toán</p>
                    <div class="worker-priority-preview" id="priorityRevenuePreview">
                        <div class="worker-priority-preview-item">
                            <span class="worker-priority-preview-copy">
                                <span class="worker-priority-preview-icon">
                                    <span class="material-symbols-outlined text-[18px]">hourglass_top</span>
                                </span>
                                <span>
                                    <span class="worker-priority-preview-title">Chưa có giao dịch</span>
                                    <span class="worker-priority-preview-subtitle">Hệ thống đang gom các đơn đã có doanh thu.</span>
                                </span>
                            </span>
                            <span class="worker-priority-preview-chip">0 đ</span>
                        </div>
                    </div>
                    <a href="/worker/my-bookings" class="inline-flex items-center gap-2 text-sm font-semibold text-white/95 hover:text-white transition-colors">
                        Xem đơn
                        <span class="material-symbols-outlined text-base">arrow_outward</span>
                    </a>
                </article>

                <article class="worker-priority-card worker-priority-card--schedule">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-5">
                            <span class="worker-priority-card__eyebrow">
                                <span class="material-symbols-outlined text-base">calendar_clock</span>
                                Lịch làm hôm nay
                            </span>
                            <div class="space-y-3">
                                <p class="worker-priority-card__value" id="priorityTodayScheduleCount">00</p>
                                <p class="worker-priority-card__meta text-slate-700" id="priorityTodayScheduleMeta">Hôm nay chưa có lịch.</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-[2.25rem] text-sky-700/60">event_note</span>
                    </div>
                    <p class="worker-priority-card__hint text-sky-900/70" id="priorityScheduleHint">0 lịch đang xử lý</p>
                    <div class="worker-priority-preview" id="prioritySchedulePreview">
                        <div class="worker-priority-preview-item">
                            <span class="worker-priority-preview-copy">
                                <span class="worker-priority-preview-icon">
                                    <span class="material-symbols-outlined text-[18px]">schedule</span>
                                </span>
                                <span>
                                    <span class="worker-priority-preview-title">Trống lịch</span>
                                    <span class="worker-priority-preview-subtitle">Lịch gần nhất sẽ hiện ở đây.</span>
                                </span>
                            </span>
                            <span class="worker-priority-preview-chip">Hôm nay</span>
                        </div>
                    </div>
                    <a href="/worker/my-bookings" class="inline-flex items-center gap-2 text-sm font-semibold text-sky-900 hover:text-sky-700 transition-colors">
                        Mở lịch
                        <span class="material-symbols-outlined text-base">arrow_outward</span>
                    </a>
                </article>

                <article class="worker-priority-card worker-priority-card--pending">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-5">
                            <span class="worker-priority-card__eyebrow">
                                <span class="material-symbols-outlined text-base">pending_actions</span>
                                Đơn mới chờ xác nhận
                            </span>
                            <div class="space-y-3">
                                <p class="worker-priority-card__value" id="priorityPendingCount">00</p>
                                <p class="worker-priority-card__meta text-orange-950/85" id="priorityPendingMeta">Không có đơn chờ xác nhận.</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-[2.25rem] text-orange-700/60">notification_important</span>
                    </div>
                    <p class="worker-priority-card__hint text-orange-900/70" id="priorityPendingHint">0 việc mới quanh bạn</p>
                    <div class="worker-priority-preview" id="priorityPendingPreview">
                        <div class="worker-priority-preview-item">
                            <span class="worker-priority-preview-copy">
                                <span class="worker-priority-preview-icon">
                                    <span class="material-symbols-outlined text-[18px]">mark_email_unread</span>
                                </span>
                                <span>
                                    <span class="worker-priority-preview-title">Hộp chờ trống</span>
                                    <span class="worker-priority-preview-subtitle">Danh sách ưu tiên sẽ xuất hiện ở đây.</span>
                                </span>
                            </span>
                            <span class="worker-priority-preview-chip">Ổn</span>
                        </div>
                    </div>
                    <a href="/worker/my-bookings?status=pending" class="inline-flex items-center gap-2 text-sm font-semibold text-orange-950 hover:text-orange-700 transition-colors">
                        Xem việc mới
                        <span class="material-symbols-outlined text-base">arrow_outward</span>
                    </a>
                </article>
            </section>

            <!-- Hero Summary Section -->
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-surface-container-lowest p-8 rounded-[2rem] flex flex-col md:flex-row justify-between items-center gap-8 relative overflow-hidden group">
                    <div class="absolute -right-12 -top-12 w-48 h-48 bg-primary/5 rounded-full blur-3xl group-hover:bg-primary/10 transition-colors"></div>
                    <div class="flex-1 space-y-6">
                        <div>
                            <h2 class="text-3xl font-bold tracking-tight text-on-surface">Chào <span id="heroWorkerName">bạn</span>,</h2>
                            <p class="text-on-surface-variant mt-2" id="heroSummaryText">Hôm nay có vẻ là một ngày bận rộn. Hãy kiểm tra các lịch hẹn sắp tới!</p>
                        </div>
                        <div class="flex flex-wrap gap-8">
                            <div class="space-y-1">
                                <p class="text-on-surface-variant text-sm font-medium" id="heroAvailableMeta">Việc mới</p>
                                <p class="text-3xl font-extrabold text-primary" id="heroAvailableJobs">0</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-on-surface-variant text-sm font-medium" id="heroTodayMeta">Lịch hôm nay</p>
                                <p class="text-3xl font-extrabold text-on-surface" id="heroTodayJobs">0</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-on-surface-variant text-sm font-medium" id="heroRevenueMeta">Doanh thu tháng</p>
                                <p class="text-3xl font-extrabold text-on-surface" id="heroMonthRevenue">0 ₫</p>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 w-full md:w-56">
                        <a href="/worker/my-bookings?status=pending" class="flex items-center gap-3 p-4 bg-surface-container-low hover:bg-primary hover:text-white rounded-2xl transition-all group/btn">
                            <span class="material-symbols-outlined text-primary group-hover/btn:text-white">assignment_add</span>
                            <span class="text-sm font-semibold">Nhận việc mới</span>
                        </a>
                        <a href="/worker/my-bookings" class="flex items-center gap-3 p-4 bg-surface-container-low hover:bg-primary hover:text-white rounded-2xl transition-all group/btn">
                            <span class="material-symbols-outlined text-primary group-hover/btn:text-white">event_note</span>
                            <span class="text-sm font-semibold">Xem lịch</span>
                        </a>
                        <a href="/worker/profile" class="flex items-center gap-3 p-4 bg-surface-container-low hover:bg-primary hover:text-white rounded-2xl transition-all group/btn">
                            <span class="material-symbols-outlined text-primary group-hover/btn:text-white">account_circle</span>
                            <span class="text-sm font-semibold">Cập nhật hồ sơ</span>
                        </a>
                    </div>
                </div>

                <!-- Profile Health / Quick Stats -->
                <div class="bg-surface-container-lowest p-8 rounded-[2rem] flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <h3 class="text-lg font-bold">Chỉ số hồ sơ</h3>
                        <span id="profileStatusBadge" class="px-3 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-full uppercase">ĐANG TẢI</span>
                    </div>
                    <div class="space-y-6 mt-6">
                        <div class="flex items-end justify-between">
                            <div>
                                <p class="text-sm text-on-surface-variant mb-1">Điểm đánh giá</p>
                                <div class="flex items-center gap-2">
                                    <span class="text-3xl font-extrabold text-on-surface" id="profileRatingValue">0.0</span>
                                    <span class="text-primary font-bold">/ 5</span>
                                </div>
                            </div>
                            <div class="flex gap-1 mb-1" id="profileRatingStars">
                                <span class="material-symbols-outlined text-primary text-lg" style="font-variation-settings: 'FILL' 1;">star</span>
                            </div>
                        </div>
                        <div class="h-2 w-full bg-surface-container-high rounded-full overflow-hidden">
                            <div class="h-full bg-primary rounded-full transition-all" id="profileCompletedBar" style="width: 100%"></div>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-on-surface-variant">Hủy đơn: <span class="font-bold text-error" id="profileCancelledMonth">0</span></span>
                            <span class="text-on-surface-variant">Lượt đánh giá: <span class="font-bold text-on-surface" id="profileReviewCount">0</span></span>
                            <span class="hidden" id="profileRadiusValue"></span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main Dashboard Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Left Column: Job Spotlight & Schedule -->
                <div class="lg:col-span-8 space-y-8">
                    <!-- Job Spotlight -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xl font-bold flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">near_me</span> Việc mới gần bạn
                            </h3>
                            <a class="text-sm font-semibold text-primary hover:underline" href="/worker/my-bookings?status=pending" id="availableJobsBadge">Xem tất cả</a>
                        </div>
                        <div id="jobSpotlightList" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="col-span-3 text-center text-sm text-on-surface-variant p-8 border border-dashed rounded-2xl">Đang tải...</div>
                        </div>
                    </div>

                    <!-- Today Schedule -->
                    <div class="bg-surface-container-lowest rounded-[2rem] overflow-hidden">
                        <div class="p-6 border-b border-surface-container flex items-center justify-between">
                            <h3 class="text-lg font-bold">Lịch trình hôm nay</h3>
                            <div class="flex gap-2">
                                <span class="flex items-center gap-1 text-xs font-medium text-on-surface-variant">
                                    <span class="w-2 h-2 rounded-full bg-blue-400"></span> Chờ làm
                                </span>
                                <span class="flex items-center gap-1 text-xs font-medium text-on-surface-variant">
                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span> Đang làm
                                </span>
                                <span class="flex items-center gap-1 text-xs font-medium text-on-surface-variant">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Xong
                                </span>
                            </div>
                        </div>
                        <div class="p-0">
                            <div class="grid grid-cols-1" id="todayScheduleList">
                                <div class="text-center text-sm text-on-surface-variant p-8">Đang tải lịch...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Chart Area -->
                    <div class="bg-surface-container-lowest p-8 rounded-[2rem] space-y-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-bold">Biểu đồ doanh thu</h3>
                                <p class="text-sm text-on-surface-variant">Thống kê 7 ngày gần nhất</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-on-surface-variant">Tổng:</span>
                                <span class="text-xl font-extrabold text-primary" id="chartRevenueSummary">0 ₫</span>
                            </div>
                        </div>
                        <div class="h-64 w-full relative" id="revenueChart">
                            <!-- ApexCharts injects here -->
                        </div>
                    </div>
                </div>

                <!-- Right Column: Recent Activity & KPI Strip -->
                <div class="lg:col-span-4 space-y-8">
                    <!-- KPI Strip -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-surface-container-low p-5 rounded-2xl space-y-2">
                            <span class="material-symbols-outlined text-primary">pending_actions</span>
                            <p class="text-xs font-bold text-on-surface-variant uppercase" id="statTodayMeta">Lịch chờ</p>
                            <p class="text-2xl font-black" id="statTodayJobs">0</p>
                        </div>
                        <div class="bg-surface-container-low p-5 rounded-2xl space-y-2">
                            <span class="material-symbols-outlined text-primary">sync</span>
                            <p class="text-xs font-bold text-on-surface-variant uppercase" id="statInProgressMeta">Đang làm</p>
                            <p class="text-2xl font-black" id="statInProgress">0</p>
                        </div>
                        <div class="bg-surface-container-low p-5 rounded-2xl space-y-2">
                            <span class="material-symbols-outlined text-primary">task_alt</span>
                            <p class="text-xs font-bold text-on-surface-variant uppercase" id="statCompletedMeta">Hoàn thành tháng</p>
                            <p class="text-2xl font-black" id="statCompletedMonth">0</p>
                        </div>
                        <div class="bg-surface-container-low p-5 rounded-2xl space-y-2">
                            <span class="material-symbols-outlined text-primary">reviews</span>
                            <p class="text-xs font-bold text-on-surface-variant uppercase" id="statRatingMeta">Đánh giá chung</p>
                            <p class="text-2xl font-black" id="statRating">0.0</p>
                        </div>
                    </div>

                    <!-- Status Distribution (Donut Chart) -->
                    <div class="bg-surface-container-lowest p-8 rounded-[2rem] space-y-6">
                        <h3 class="text-lg font-bold text-center">Trạng thái đơn hàng</h3>
                        <div class="flex justify-center relative h-32" id="statusChart">
                            <!-- Apexchart Donut injects here -->
                        </div>
                        <div class="absolute inset-0 flex items-center justify-center flex-col pointer-events-none opacity-0">
                            <span class="text-xl font-bold" id="statusDonutSummary">0</span>
                            <span class="text-[10px] font-bold text-on-surface-variant uppercase">Tổng</span>
                        </div>
                        <div class="space-y-3" id="statusLegend">
                            <!-- Dynamic Legend -->
                        </div>
                    </div>

                    <!-- Recent Activity Timeline -->
                    <div class="bg-surface-container-lowest p-8 rounded-[2rem] space-y-6">
                        <h3 class="text-lg font-bold">Hoạt động gần nhất</h3>
                        <div class="space-y-6 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-surface-container" id="recentActivityList">
                            <div class="text-center text-sm text-on-surface-variant p-4">Đang tải...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Dummy element for unused profileAreaValue binding to avoid JS error -->
<span id="profileAreaValue" class="hidden"></span>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script type="module">
  import { callApi, getCurrentUser, showToast } from "{{ asset('assets/js/api.js') }}";

  const baseUrl = "{{ url('/') }}";
  const currentUser = getCurrentUser();

  if (!currentUser || !['worker', 'admin'].includes(currentUser.role)) {
    window.location.href = `${baseUrl}/login?role=worker`;
  }

  const $ = (id) => document.getElementById(id);
  const dom = {
    headerDate: $('headerDate'),
    liveStatusText: $('liveStatusText'),
    refreshButton: $('dashboardRefreshButton'),
    priorityTodayRevenue: $('priorityTodayRevenue'),
    priorityRevenueMeta: $('priorityRevenueMeta'),
    priorityRevenueHint: $('priorityRevenueHint'),
    priorityRevenuePreview: $('priorityRevenuePreview'),
    priorityTodayScheduleCount: $('priorityTodayScheduleCount'),
    priorityTodayScheduleMeta: $('priorityTodayScheduleMeta'),
    priorityScheduleHint: $('priorityScheduleHint'),
    prioritySchedulePreview: $('prioritySchedulePreview'),
    priorityPendingCount: $('priorityPendingCount'),
    priorityPendingMeta: $('priorityPendingMeta'),
    priorityPendingHint: $('priorityPendingHint'),
    priorityPendingPreview: $('priorityPendingPreview'),
    heroWorkerName: $('heroWorkerName'),
    heroSummaryText: $('heroSummaryText'),
    heroAvailableJobs: $('heroAvailableJobs'),
    heroAvailableMeta: $('heroAvailableMeta'),
    heroTodayJobs: $('heroTodayJobs'),
    heroTodayMeta: $('heroTodayMeta'),
    heroMonthRevenue: $('heroMonthRevenue'),
    heroRevenueMeta: $('heroRevenueMeta'),
    availableJobsBadge: $('availableJobsBadge'),
    jobSpotlightList: $('jobSpotlightList'),
    profileStatusBadge: $('profileStatusBadge'),
    profileAreaValue: $('profileAreaValue'),
    profileRatingValue: $('profileRatingValue'),
    profileRadiusValue: $('profileRadiusValue'),
    profileReviewCount: $('profileReviewCount'),
    profileCompletedBar: $('profileCompletedBar'),
    profileCancelledMonth: $('profileCancelledMonth'),
    statTodayJobs: $('statTodayJobs'),
    statTodayMeta: $('statTodayMeta'),
    statInProgress: $('statInProgress'),
    statInProgressMeta: $('statInProgressMeta'),
    statCompletedMonth: $('statCompletedMonth'),
    statCompletedMeta: $('statCompletedMeta'),
    statRating: $('statRating'),
    statRatingMeta: $('statRatingMeta'),
    chartRevenueSummary: $('chartRevenueSummary'),
    revenueChart: $('revenueChart'),
    statusDonutSummary: $('statusDonutSummary'),
    statusChart: $('statusChart'),
    statusLegend: $('statusLegend'),
    recentActivityList: $('recentActivityList'),
    todayScheduleList: $('todayScheduleList'),
  };

  const STATUS_META = {
    cho_xac_nhan: { label: 'Chờ xác nhận', border: 'border-slate-500', bg: 'bg-slate-50', text: 'text-slate-900', chip: 'bg-slate-100 text-slate-600', dot: 'bg-slate-400', icon: 'hourglass_top' },
    da_xac_nhan: { label: 'Đã xác nhận', border: 'border-blue-500', bg: 'bg-blue-50', text: 'text-blue-900', chip: 'bg-blue-100 text-blue-600', dot: 'bg-blue-400', icon: 'directions_car' },
    dang_lam: { label: 'Đang làm', border: 'border-amber-500', bg: 'bg-amber-50', text: 'text-amber-900', chip: 'bg-amber-100 text-amber-600', dot: 'bg-amber-400', icon: 'build' },
    cho_hoan_thanh: { label: 'Chờ duyệt', border: 'border-amber-500', bg: 'bg-amber-50', text: 'text-amber-900', chip: 'bg-amber-100 text-amber-600', dot: 'bg-amber-400', icon: 'hourglass_bottom' },
    cho_thanh_toan: { label: 'Chờ thanh toán', border: 'border-indigo-500', bg: 'bg-indigo-50', text: 'text-indigo-900', chip: 'bg-indigo-100 text-indigo-600', dot: 'bg-indigo-400', icon: 'payments' },
    da_xong: { label: 'Hoàn thành', border: 'border-emerald-500', bg: 'bg-emerald-50', text: 'text-emerald-900', chip: 'bg-emerald-100 text-emerald-600', dot: 'bg-emerald-400', icon: 'task_alt' },
    da_huy: { label: 'Đã hủy', border: 'border-red-500', bg: 'bg-red-50', text: 'text-red-900', chip: 'bg-red-100 text-red-600', dot: 'bg-red-400', icon: 'cancel' },
  };

  let revenueChartInstance = null;
  let statusChartInstance = null;
  const workerMapState = {
    map: null,
    tileLayer: null,
    markersLayer: null,
    workerMarker: null,
    refs: null,
    selectedBookingId: null,
    hoveredBookingId: null,
    workerPosition: null,
    hasRequestedLocation: false,
    lastPayload: null,
    hideCardTimeout: null,
    isHoveringInfoCard: false,
  };

  const escapeHtml = (value) => String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  const formatMoney = (value) => `${Math.round(Number(value) || 0).toLocaleString('vi-VN')} ₫`;
  const formatCompactMoney = (value) => {
    const amount = Number(value) || 0;
    if (amount >= 1000000) return `${(amount / 1000000).toFixed(amount >= 10000000 ? 0 : 1)}M`;
    if (amount >= 1000) return `${Math.round(amount / 1000)}k`;
    return `${Math.round(amount)} ₫`;
  };

  const localDateKey = (date = new Date()) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  const weekdayLabel = (date = new Date()) => new Intl.DateTimeFormat('vi-VN', { weekday: 'long' }).format(date);
  const extractList = (response) => {
    if (!response?.ok) return [];
    if (Array.isArray(response.data?.data)) return response.data.data;
    if (Array.isArray(response.data)) return response.data;
    if (Array.isArray(response.data?.data?.data)) return response.data.data.data;
    return [];
  };

  const getStatusMeta = (status) => STATUS_META[status] || STATUS_META.cho_xac_nhan;
  const getServices = (booking) => Array.isArray(booking?.dich_vus) ? booking.dich_vus : (Array.isArray(booking?.dichVus) ? booking.dichVus : []);
  const getServiceNames = (booking) => getServices(booking).map((service) => service?.ten_dich_vu).filter(Boolean);
  const getServiceSummary = (booking, limit = 2) => {
    const names = getServiceNames(booking);
    if (!names.length) return 'Dịch vụ sửa chữa';
    return `${names.slice(0, limit).join(', ')}${names.length > limit ? ` +${names.length - limit}` : ''}`;
  };

  const getCustomer = (booking) => booking?.khach_hang || booking?.khachHang || {};
  const bookingTotal = (booking) => {
    const explicit = Number(booking?.tong_tien ?? booking?.tongTien);
    if (Number.isFinite(explicit) && explicit > 0) return explicit;
    return Number(booking?.phi_di_lai || 0) + Number(booking?.phi_linh_kien || 0) + Number(booking?.tien_cong || 0) + Number(booking?.tien_thue_xe || 0);
  };

  const startTimeFromSlot = (slot) => String(slot || '').split('-')[0]?.trim() || '00:00';
  const formatShortDate = (value) => value ? new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit' }).format(new Date(`${value}T00:00:00`)) : 'Chưa hẹn';
  const buildDateHeader = () => {
    const now = new Date();
    const capitalizedWeekday = weekdayLabel(now).replace(/^\w/, (c) => c.toUpperCase());
    return `${capitalizedWeekday}, ${now.getDate()} Tháng ${now.getMonth()+1}, ${now.getFullYear()}`;
  };

  const formatMetricValue = (value) => String(value > 0 && value < 10 ? `0${value}` : value);
  const bookingDateKey = (value) => String(value ?? '').slice(0, 10);
  const getBookingCode = (booking) => booking?.ma_don ? `#${String(booking.ma_don).slice(0, 8).toUpperCase()}` : `#${String(booking?.id ?? '').padStart(4, '0')}`;
  const buildDashboardPriorityState = (bookings, availableJobs) => {
    const todayKey = localDateKey();
    const scheduleStatuses = ['da_xac_nhan', 'khong_lien_lac_duoc_voi_khach_hang', 'dang_lam', 'cho_hoan_thanh', 'cho_thanh_toan', 'da_xong', 'cho_xac_nhan'];
    const todaySchedule = [...bookings]
      .filter((booking) => bookingDateKey(booking?.ngay_hen) === todayKey && scheduleStatuses.includes(booking?.trang_thai) && booking?.trang_thai !== 'da_huy')
      .sort((left, right) => startTimeFromSlot(left?.khung_gio_hen).localeCompare(startTimeFromSlot(right?.khung_gio_hen)));
    const pendingConfirm = [...bookings]
      .filter((booking) => booking?.trang_thai === 'cho_xac_nhan')
      .sort((left, right) => `${bookingDateKey(left?.ngay_hen)} ${startTimeFromSlot(left?.khung_gio_hen)}`.localeCompare(`${bookingDateKey(right?.ngay_hen)} ${startTimeFromSlot(right?.khung_gio_hen)}`));
    const inProgress = bookings.filter((booking) => booking?.trang_thai === 'dang_lam');
    const revenueStatuses = ['cho_hoan_thanh', 'cho_thanh_toan', 'da_xong'];
    const todayRevenueBookings = [...bookings]
      .filter((booking) => revenueStatuses.includes(booking?.trang_thai))
      .filter((booking) => {
        const completedToday = bookingDateKey(booking?.thoi_gian_hoan_thanh) === todayKey;
        const scheduledToday = bookingDateKey(booking?.ngay_hen) === todayKey;
        return completedToday || (scheduledToday && bookingTotal(booking) > 0);
      });

    return {
      todayKey,
      todaySchedule,
      pendingConfirm,
      inProgress,
      availableJobsCount: availableJobs.length,
      nextSchedule: todaySchedule.find((booking) => !['da_xong', 'cho_hoan_thanh', 'cho_thanh_toan'].includes(booking?.trang_thai)) || todaySchedule[0] || null,
      todayRevenueBookings: [...todayRevenueBookings].sort((left, right) => bookingTotal(right) - bookingTotal(left)),
      todayRevenue: todayRevenueBookings.reduce((sum, booking) => sum + bookingTotal(booking), 0),
      todayCollectedCount: todayRevenueBookings.filter((booking) => booking?.trang_thai === 'da_xong').length,
      todayWaitingPayoutCount: todayRevenueBookings.filter((booking) => ['cho_hoan_thanh', 'cho_thanh_toan'].includes(booking?.trang_thai)).length,
    };
  };
  const buildPriorityPreviewItem = ({ href, icon, title, subtitle, chip }) => `
    <a href="${href}" class="worker-priority-preview-item">
      <span class="worker-priority-preview-copy">
        <span class="worker-priority-preview-icon">
          <span class="material-symbols-outlined text-[18px]">${icon}</span>
        </span>
        <span class="min-w-0">
          <span class="worker-priority-preview-title">${escapeHtml(title)}</span>
          <span class="worker-priority-preview-subtitle">${escapeHtml(subtitle)}</span>
        </span>
      </span>
      <span class="worker-priority-preview-chip">${escapeHtml(chip)}</span>
    </a>
  `;
  const getCoordinateValue = (value) => {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : null;
  };
  const isValidCoordinatePair = (lat, lng) => Number.isFinite(lat)
    && Number.isFinite(lng)
    && Math.abs(lat) <= 90
    && Math.abs(lng) <= 180
    && !(lat === 0 && lng === 0);
  const getBookingPoint = (booking) => {
    const lat = getCoordinateValue(booking?.vi_do);
    const lng = getCoordinateValue(booking?.kinh_do);
    return isValidCoordinatePair(lat, lng) ? { lat, lng } : null;
  };
  const toRadians = (value) => (value * Math.PI) / 180;
  const calculateHaversineKm = (fromLat, fromLng, toLat, toLng) => {
    const earthRadiusKm = 6371;
    const dLat = toRadians(toLat - fromLat);
    const dLng = toRadians(toLng - fromLng);
    const a = Math.sin(dLat / 2) ** 2
      + Math.cos(toRadians(fromLat)) * Math.cos(toRadians(toLat)) * Math.sin(dLng / 2) ** 2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return earthRadiusKm * c;
  };
  const formatDistanceLabel = (distanceKm) => {
    if (!Number.isFinite(distanceKm)) return 'Chưa có GPS';
    if (distanceKm < 1) return `${Math.max(1, Math.round(distanceKm * 1000))} m`;
    return `${distanceKm < 10 ? distanceKm.toFixed(1) : distanceKm.toFixed(0)} km`;
  };
  const formatBookingDateLabel = (value) => {
    if (!value) return 'Chưa có ngày';
    return new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit' }).format(new Date(`${bookingDateKey(value)}T00:00:00`));
  };
  const isClaimableBooking = (booking) => !booking?.tho_id && booking?.trang_thai === 'cho_xac_nhan';
  const getBookingDetailHref = (booking) => `/worker/my-bookings?status=pending&booking=${booking.id}`;
  const getWorkerPointFromProfile = (profile) => {
    const lat = getCoordinateValue(profile?.vi_do);
    const lng = getCoordinateValue(profile?.kinh_do);
    return isValidCoordinatePair(lat, lng) ? { lat, lng } : null;
  };
  const buildMapMarkerIcon = (variant, icon) => window.L.divIcon({
    className: '',
    html: `<div class="dashboard-map-marker dashboard-map-marker--${variant}"><span class="material-symbols-outlined">${icon}</span></div>`,
    iconSize: [40, 50],
    iconAnchor: [20, 42],
    tooltipAnchor: [0, -30],
  });
  const ensureJobMapShell = () => {
    if (workerMapState.refs?.canvas && document.body.contains(workerMapState.refs.canvas)) {
      return workerMapState.refs;
    }

    dom.jobSpotlightList.className = 'block';
    dom.jobSpotlightList.innerHTML = `
      <div class="dashboard-map-shell">
        <div class="dashboard-map-head">
          <div>
            <p class="text-sm font-semibold text-slate-900">Bản đồ khách hàng</p>
            <p class="text-xs text-slate-500">Hiện vị trí của bạn, đơn đã nhận và đơn chưa có thợ.</p>
          </div>
          <div class="dashboard-map-meta">
            <span id="jobMapCountText">Đang tải dữ liệu bản đồ</span>
          </div>
        </div>
        <div class="dashboard-map-stage">
          <div id="jobMapCanvas" class="dashboard-map-canvas"></div>
          <div id="jobMapStatus" class="dashboard-map-status">
            <span class="material-symbols-outlined text-base">my_location</span>
            Đang tìm vị trí của bạn...
          </div>
          <div class="dashboard-map-legend">
            <span class="dashboard-map-chip">
              <span class="dashboard-map-chip__dot dashboard-map-chip__dot--worker"></span>
              Vị trí của bạn
            </span>
            <span class="dashboard-map-chip">
              <span class="dashboard-map-chip__dot dashboard-map-chip__dot--mine"></span>
              Đơn của tôi
            </span>
            <span class="dashboard-map-chip">
              <span class="dashboard-map-chip__dot dashboard-map-chip__dot--open"></span>
              Đơn chưa có thợ
            </span>
          </div>
          <div id="jobMapEmptyState" class="dashboard-map-empty">
            <span class="material-symbols-outlined">map_search</span>
            <div class="space-y-1">
              <p class="font-bold text-slate-900">Đang tải đơn và vị trí trên bản đồ</p>
              <p class="text-sm text-slate-500">Marker khách sẽ hiện tại đây khi hệ thống có tọa độ hợp lệ.</p>
            </div>
          </div>
          <div id="jobMapInfoCard" class="dashboard-map-card is-hidden">
            <div class="dashboard-map-card__eyebrow">
              <span id="jobMapInfoEyebrow">Đơn trên bản đồ</span>
              <span id="jobMapInfoStatus" class="dashboard-map-card__status dashboard-map-card__status--open">Chờ xác nhận</span>
            </div>
            <p id="jobMapInfoTitle" class="dashboard-map-card__title">Di chuột vào marker để xem nhanh đơn đặt lịch.</p>
            <p id="jobMapInfoMeta" class="dashboard-map-card__meta">Bạn có thể nhận đơn ngay tại đây hoặc mở trang chi tiết.</p>
            <div class="dashboard-map-card__stack">
              <div class="dashboard-map-card__line">
                <span class="material-symbols-outlined">person</span>
                <span id="jobMapInfoCustomer">Khách hàng</span>
              </div>
              <div class="dashboard-map-card__line">
                <span class="material-symbols-outlined">schedule</span>
                <span id="jobMapInfoSchedule">Ngày hẹn • Khung giờ</span>
              </div>
              <div class="dashboard-map-card__line">
                <span class="material-symbols-outlined">distance</span>
                <span id="jobMapInfoDistance">Khoảng cách sẽ hiện khi có GPS.</span>
              </div>
              <div class="dashboard-map-card__line">
                <span class="material-symbols-outlined">place</span>
                <span id="jobMapInfoAddress">Địa chỉ khách hàng</span>
              </div>
            </div>
            <div class="dashboard-map-card__actions">
              <button type="button" id="jobMapClaimButton" class="dashboard-map-primary-btn">
                <span class="material-symbols-outlined text-base">task_alt</span>
                Xác nhận đơn
              </button>
              <a id="jobMapDetailLink" href="/worker/my-bookings?status=pending" class="dashboard-map-secondary-btn">
                <span class="material-symbols-outlined text-base">open_in_new</span>
                Xem chi tiết
              </a>
            </div>
          </div>
        </div>
      </div>
    `;

    workerMapState.refs = {
      countText: $('jobMapCountText'),
      status: $('jobMapStatus'),
      canvas: $('jobMapCanvas'),
      emptyState: $('jobMapEmptyState'),
      infoCard: $('jobMapInfoCard'),
      infoEyebrow: $('jobMapInfoEyebrow'),
      infoStatus: $('jobMapInfoStatus'),
      infoTitle: $('jobMapInfoTitle'),
      infoMeta: $('jobMapInfoMeta'),
      infoCustomer: $('jobMapInfoCustomer'),
      infoSchedule: $('jobMapInfoSchedule'),
      infoDistance: $('jobMapInfoDistance'),
      infoAddress: $('jobMapInfoAddress'),
      claimButton: $('jobMapClaimButton'),
      detailLink: $('jobMapDetailLink'),
    };

    workerMapState.refs.infoCard?.addEventListener('mouseenter', () => {
      workerMapState.isHoveringInfoCard = true;
      clearJobMapHideTimeout();
    });

    workerMapState.refs.infoCard?.addEventListener('mouseleave', () => {
      workerMapState.isHoveringInfoCard = false;
      scheduleMapBookingCardHide(workerMapState.refs, 120);
    });

    return workerMapState.refs;
  };
  const ensureJobMap = (refs) => {
    if (!refs?.canvas || !window.L) return null;
    if (workerMapState.map) return workerMapState.map;

    workerMapState.map = window.L.map(refs.canvas, {
      zoomControl: false,
      attributionControl: false,
      scrollWheelZoom: true,
    }).setView([12.2388, 109.1967], 12);

    workerMapState.tileLayer = window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
    }).addTo(workerMapState.map);

    workerMapState.markersLayer = window.L.layerGroup().addTo(workerMapState.map);
    workerMapState.map.on('click', () => {
      hideMapBookingCard(refs);
    });

    return workerMapState.map;
  };
  const updateJobMapStatus = (refs, message) => {
    if (refs?.status) refs.status.lastChild.textContent = ` ${message}`;
  };
  const setJobMapEmptyState = (refs, visible, title, copy) => {
    if (!refs?.emptyState) return;
    refs.emptyState.classList.toggle('is-hidden', !visible);
    if (!visible) return;
    const titleNode = refs.emptyState.querySelector('p.font-bold');
    const copyNode = refs.emptyState.querySelector('p.text-sm');
    if (titleNode) titleNode.textContent = title;
    if (copyNode) copyNode.textContent = copy;
  };
  const clearJobMapHideTimeout = () => {
    if (workerMapState.hideCardTimeout) {
      clearTimeout(workerMapState.hideCardTimeout);
      workerMapState.hideCardTimeout = null;
    }
  };
  const hideMapBookingCard = (refs) => {
    clearJobMapHideTimeout();
    workerMapState.selectedBookingId = null;
    workerMapState.hoveredBookingId = null;
    workerMapState.isHoveringInfoCard = false;
    if (refs?.infoCard) {
      refs.infoCard.classList.add('is-hidden');
    }
  };
  const scheduleMapBookingCardHide = (refs, delay = 900) => {
    clearJobMapHideTimeout();
    workerMapState.hideCardTimeout = window.setTimeout(() => {
      if (workerMapState.isHoveringInfoCard || workerMapState.hoveredBookingId !== null) {
        return;
      }
      hideMapBookingCard(refs);
    }, delay);
  };
  const buildDashboardMapBookings = (bookings, availableJobs) => {
    const combined = [
      ...bookings
        .filter((booking) => !['da_xong', 'da_huy'].includes(booking?.trang_thai))
        .map((booking) => ({ ...booking, mapSource: 'mine' })),
      ...availableJobs.map((booking) => ({ ...booking, mapSource: 'open' })),
    ];

    const deduped = [];
    const seen = new Set();

    combined.forEach((booking) => {
      if (!booking?.id || seen.has(booking.id)) return;
      seen.add(booking.id);

      const point = getBookingPoint(booking);
      if (!point) return;

      deduped.push({
        ...booking,
        point,
        isClaimable: isClaimableBooking(booking),
      });
    });

    return deduped;
  };
  const selectMapBooking = (booking, refs) => {
    if (!booking || !refs) return;

    clearJobMapHideTimeout();
    workerMapState.selectedBookingId = booking.id;
    refs.infoCard.classList.remove('is-hidden');
    refs.infoEyebrow.textContent = booking.mapSource === 'mine' ? 'Đơn của tôi' : 'Đơn chưa có thợ';
    refs.infoStatus.textContent = getStatusMeta(booking.trang_thai).label;
    refs.infoStatus.className = `dashboard-map-card__status ${booking.mapSource === 'mine' ? 'dashboard-map-card__status--mine' : 'dashboard-map-card__status--open'}`;
    refs.infoTitle.textContent = getServiceSummary(booking, 1);
    refs.infoMeta.textContent = booking.mapSource === 'mine'
      ? 'Đây là đơn hiện đang thuộc lịch xử lý của bạn.'
      : 'Đơn này đang chờ thợ xác nhận. Bạn có thể nhận trực tiếp trên bản đồ.';
    refs.infoCustomer.textContent = getCustomer(booking).name || 'Khách hàng';
    refs.infoSchedule.textContent = `${formatBookingDateLabel(booking.ngay_hen)} • ${booking.khung_gio_hen || 'Chưa có khung giờ'}`;
    refs.infoAddress.textContent = booking.dia_chi || 'Chưa có địa chỉ';

    const distanceKm = workerMapState.workerPosition
      ? calculateHaversineKm(workerMapState.workerPosition.lat, workerMapState.workerPosition.lng, booking.point.lat, booking.point.lng)
      : Number.NaN;
    refs.infoDistance.textContent = Number.isFinite(distanceKm)
      ? `${formatDistanceLabel(distanceKm)} từ vị trí của bạn`
      : 'Cần bật GPS để tính khoảng cách';

    refs.detailLink.href = getBookingDetailHref(booking);

    if (booking.isClaimable) {
      refs.claimButton.hidden = false;
      refs.claimButton.disabled = false;
      refs.claimButton.innerHTML = '<span class="material-symbols-outlined text-base">task_alt</span>Xác nhận đơn';
      refs.claimButton.onclick = () => claimJobFromDashboardMap(booking.id, refs.claimButton);
    } else {
      refs.claimButton.hidden = booking.mapSource === 'mine';
      refs.claimButton.disabled = true;
      refs.claimButton.innerHTML = `<span class="material-symbols-outlined text-base">${booking.mapSource === 'mine' ? 'assignment_turned_in' : 'block'}</span>${booking.mapSource === 'mine' ? 'Đã nhận' : 'Không khả dụng'}`;
      refs.claimButton.onclick = null;
    }
  };
  async function claimJobFromDashboardMap(bookingId, button) {
    if (!confirm('Bạn có chắc chắn muốn nhận đơn này không?')) return;

    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="material-symbols-outlined text-base">progress_activity</span>Đang xử lý';

    try {
      const response = await callApi(`/don-dat-lich/${bookingId}/claim`, 'POST');
      if (!response?.ok) {
        throw new Error(response?.data?.message || 'Không thể nhận đơn này');
      }

      showToast('Xác nhận đơn thành công', 'success');
      await loadDashboard();
    } catch (error) {
      console.error('Claim job from dashboard map failed:', error);
      showToast(error.message || 'Không thể xác nhận đơn', 'error');
      button.disabled = false;
      button.innerHTML = originalHtml;
    }
  }
  const requestWorkerLocation = (profile) => {
    const profilePoint = getWorkerPointFromProfile(profile);
    if (!workerMapState.workerPosition && profilePoint) {
      workerMapState.workerPosition = profilePoint;
    }

    if (workerMapState.hasRequestedLocation || !navigator.geolocation) {
      return;
    }

    workerMapState.hasRequestedLocation = true;
    navigator.geolocation.getCurrentPosition(
      (position) => {
        workerMapState.workerPosition = {
          lat: position.coords.latitude,
          lng: position.coords.longitude,
        };

        if (workerMapState.lastPayload) {
          renderJobMapSpotlight(
            workerMapState.lastPayload.bookings,
            workerMapState.lastPayload.availableJobs,
            workerMapState.lastPayload.profile
          );
        }
      },
      () => {
        if (workerMapState.lastPayload) {
          renderJobMapSpotlight(
            workerMapState.lastPayload.bookings,
            workerMapState.lastPayload.availableJobs,
            workerMapState.lastPayload.profile
          );
        }
      },
      {
        enableHighAccuracy: true,
        timeout: 6000,
        maximumAge: 60000,
      }
    );
  };

  function renderPrioritySummary(bookings, availableJobs) {
    const priorityState = buildDashboardPriorityState(bookings, availableJobs);
    const revenueItem = priorityState.todayRevenueBookings[0] || null;
    const scheduleItem = priorityState.todaySchedule[0] || null;
    const pendingItem = priorityState.pendingConfirm[0] || null;

    dom.priorityTodayRevenue.textContent = formatMoney(priorityState.todayRevenue);
    dom.priorityRevenueMeta.textContent = priorityState.todayRevenue > 0
      ? `${priorityState.todayRevenueBookings.length} đơn có doanh thu hôm nay.`
      : 'Chưa có doanh thu hôm nay.';
    dom.priorityRevenueHint.textContent = priorityState.todayWaitingPayoutCount > 0
      ? `${priorityState.todayWaitingPayoutCount} đơn chờ thanh toán`
      : 'Không có đơn chờ';
    dom.priorityRevenuePreview.innerHTML = revenueItem
      ? buildPriorityPreviewItem({
          href: `/worker/bookings/${revenueItem.id}`,
          icon: 'payments',
          title: getServiceSummary(revenueItem, 1),
          subtitle: `${getCustomer(revenueItem).name || 'Khách hàng'} • ${getStatusMeta(revenueItem.trang_thai).label}`,
          chip: formatCompactMoney(bookingTotal(revenueItem)),
        })
      : buildPriorityPreviewItem({
          href: '/worker/my-bookings',
          icon: 'event_available',
          title: 'Chưa có giao dịch',
          subtitle: 'Doanh thu sẽ hiện ở đây.',
          chip: '0 đ',
        });

    dom.priorityTodayScheduleCount.textContent = formatMetricValue(priorityState.todaySchedule.length);
    dom.priorityTodayScheduleMeta.textContent = priorityState.nextSchedule
      ? `${startTimeFromSlot(priorityState.nextSchedule.khung_gio_hen)} • ${getServiceSummary(priorityState.nextSchedule, 1)}`
      : 'Hôm nay chưa có lịch.';
    dom.priorityScheduleHint.textContent = priorityState.inProgress.length > 0
      ? `${priorityState.inProgress.length} đơn đang làm`
      : `${priorityState.todaySchedule.length} lịch hôm nay`;
    dom.prioritySchedulePreview.innerHTML = scheduleItem
      ? buildPriorityPreviewItem({
          href: `/worker/bookings/${scheduleItem.id}`,
          icon: 'schedule',
          title: `${startTimeFromSlot(scheduleItem.khung_gio_hen)} • ${getServiceSummary(scheduleItem, 1)}`,
          subtitle: `${getCustomer(scheduleItem).name || 'Khách hàng'} • ${getStatusMeta(scheduleItem.trang_thai).label}`,
          chip: getStatusMeta(scheduleItem.trang_thai).label,
        })
      : buildPriorityPreviewItem({
          href: '/worker/my-bookings',
          icon: 'free_cancellation',
          title: 'Trống lịch',
          subtitle: 'Bạn có thể nhận thêm việc.',
          chip: 'Trống',
        });

    dom.priorityPendingCount.textContent = formatMetricValue(priorityState.pendingConfirm.length);
    dom.priorityPendingMeta.textContent = priorityState.pendingConfirm.length > 0
      ? `${priorityState.pendingConfirm.length} đơn cần xác nhận.`
      : 'Không có đơn chờ xác nhận.';
    dom.priorityPendingHint.textContent = priorityState.availableJobsCount > 0
      ? `${priorityState.availableJobsCount} việc mới quanh bạn`
      : 'Hộp chờ đang trống';
    dom.priorityPendingPreview.innerHTML = pendingItem
      ? buildPriorityPreviewItem({
          href: `/worker/bookings/${pendingItem.id}`,
          icon: 'mark_email_unread',
          title: getServiceSummary(pendingItem, 1),
          subtitle: `${formatShortDate(pendingItem.ngay_hen)} • ${startTimeFromSlot(pendingItem.khung_gio_hen)} • ${getCustomer(pendingItem).name || 'Khách hàng'}`,
          chip: getBookingCode(pendingItem),
        })
      : buildPriorityPreviewItem({
          href: '/worker/my-bookings?status=pending',
          icon: 'verified',
          title: priorityState.availableJobsCount > 0 ? 'Có việc mới để nhận' : 'Hộp chờ trống',
          subtitle: priorityState.availableJobsCount > 0 ? 'Mở danh sách việc mới.' : 'Chưa có đơn cần xác nhận.',
          chip: priorityState.availableJobsCount > 0 ? `${priorityState.availableJobsCount} việc` : 'Ổn',
        });
  }

  function renderPriorityTopbar(bookings, availableJobs) {
    const todayKey = localDateKey();
    const todayOpen = bookings.filter((booking) => booking.ngay_hen === todayKey && !['da_xong', 'da_huy'].includes(booking.trang_thai));
    const inProgress = bookings.filter((booking) => booking.trang_thai === 'dang_lam');
    const workerName = String(currentUser?.name || 'bạn').trim().split(' ').pop() || currentUser?.name || 'bạn';

    dom.headerDate.textContent = buildDateHeader();
    dom.heroWorkerName.textContent = workerName;

    if (inProgress.length > 0) {
      dom.liveStatusText.textContent = `${inProgress.length} ĐƠN ĐANG XỬ LÝ`;
      dom.heroSummaryText.textContent = `Bạn đang xử lý ${inProgress.length} đơn. Ưu tiên hoàn tất ${todayOpen.length} lịch còn lại.`;
      return;
    }
    if (availableJobs.length > 0) {
      dom.liveStatusText.textContent = `${availableJobs.length} VIỆC MỚI`;
      dom.heroSummaryText.textContent = `Có ${availableJobs.length} việc mới quanh bạn. Bấm "Nhận việc mới" để xem.`;
      return;
    }

    dom.liveStatusText.textContent = 'DỮ LIỆU ĐÃ ĐỒNG BỘ';
    dom.heroSummaryText.textContent = todayOpen.length > 0
      ? `Hôm nay có ${todayOpen.length} lịch hẹn. Hãy kiểm tra các lịch hẹn sắp tới!`
      : 'Thảnh thơi nhâm nhi ly cà phê, hoặc kiểm tra lại hồ sơ nhé!';
  }

  function renderHeroOverview(bookings, availableJobs, stats) {
    const todayKey = localDateKey();
    const todayJobs = bookings.filter((booking) => booking.ngay_hen === todayKey);
    const todayOpen = todayJobs.filter((b) => !['da_xong', 'da_huy'].includes(b.trang_thai));
    const monthRevenue = Number(stats?.doanh_thu_thang_nay || 0);

    dom.heroAvailableJobs.textContent = String(availableJobs.length < 10 && availableJobs.length > 0 ? `0${availableJobs.length}` : availableJobs.length);
    dom.heroTodayJobs.textContent = String(todayOpen.length < 10 && todayOpen.length > 0 ? `0${todayOpen.length}` : todayOpen.length);
    dom.heroMonthRevenue.textContent = formatCompactMoney(monthRevenue);
  }

  function renderProfile(profile, stats) {
    const canceled = Number(stats?.don_huy_thang_nay || 0);
    const completed = Number(stats?.don_hoan_thanh_thang_nay || 0);
    const total = canceled + completed;
    const rate = total > 0 ? ((completed / total) * 100).toFixed(0) : 100;

    if (!profile) {
      dom.profileStatusBadge.textContent = 'CHƯA CÓ HỒ SƠ';
      dom.profileStatusBadge.className = 'px-3 py-1 bg-slate-100 text-slate-700 text-[10px] font-bold rounded-full';
      dom.profileRatingValue.textContent = '0.0';
      dom.profileReviewCount.textContent = '0';
      dom.profileCancelledMonth.textContent = canceled;
      dom.profileCompletedBar.style.width = '0%';
      return;
    }

    const isApp = profile.trang_thai_duyet === 'da_duyet';
    const isAct = Boolean(profile.dang_hoat_dong);
    dom.profileStatusBadge.textContent = isApp && isAct ? 'SẴN SÀNG' : (isApp ? 'TẠM DỪNG' : 'CHỜ DUYỆT');
    dom.profileStatusBadge.className = isApp && isAct 
      ? 'px-3 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-full'
      : 'px-3 py-1 bg-amber-100 text-amber-700 text-[10px] font-bold rounded-full';

    dom.profileRatingValue.textContent = Number(profile.danh_gia_trung_binh || 0).toFixed(1);
    dom.profileReviewCount.textContent = `${Number(profile.tong_so_danh_gia || 0)}`;
    dom.profileCancelledMonth.textContent = `${canceled}`;
    dom.profileCompletedBar.style.width = `${rate}%`;

    const sCount = Math.round(Number(profile.danh_gia_trung_binh || 0));
    $('profileRatingStars').innerHTML = Array.from({length: 5}).map((_, i) => `<span class="material-symbols-outlined text-primary text-lg" style="font-variation-settings: 'FILL' ${i < sCount ? 1 : 0};">star</span>`).join('');
  }

  function renderKpis(bookings, stats, profile) {
    const todayKey = localDateKey();
    const todayOpen = bookings.filter((b) => b.ngay_hen === todayKey && !['da_xong', 'da_huy'].includes(b.trang_thai));
    const inProgress = bookings.filter((b) => b.trang_thai === 'dang_lam');
    
    dom.statTodayJobs.textContent = String(todayOpen.length < 10 ? `0${todayOpen.length}` : todayOpen.length);
    dom.statInProgress.textContent = String(inProgress.length < 10 ? `0${inProgress.length}` : inProgress.length);
    dom.statCompletedMonth.textContent = String(stats?.don_hoan_thanh_thang_nay || 0);
    dom.statRating.textContent = `${Number(profile?.danh_gia_trung_binh || 0).toFixed(1)}/5`;
  }

  function renderJobSpotlight(availableJobs) {
    dom.availableJobsBadge.textContent = availableJobs.length > 0 ? `Có ${availableJobs.length} việc mới` : 'Xem tất cả';
    if (!availableJobs.length) {
      dom.jobSpotlightList.innerHTML = `<div class="col-span-3 text-center text-sm text-on-surface-variant p-8 border border-dashed rounded-2xl">Không có việc mới nào quanh khu vực của bạn.</div>`;
      return;
    }
    const icons = ['ac_unit', 'local_laundry_service', 'electric_bolt', 'plumbing', 'home_repair_service'];
    
    dom.jobSpotlightList.innerHTML = availableJobs.slice(0, 3).map((job, idx) => {
      const icon = icons[idx % icons.length];
      const isPriority = idx === 0;
      const tBg = isPriority ? 'bg-amber-100 text-amber-600' : 'bg-primary/10 text-primary';
      const pColor = isPriority ? 'text-amber-600' : 'text-primary';
      const price = formatCompactMoney(bookingTotal(job) || job.phi_dich_vu);
      
      return `
        <a href="${getBookingDetailHref(job)}" class="block bg-surface-container-lowest p-5 rounded-2xl border-2 border-primary/10 hover:border-primary transition-all group">
            <div class="flex justify-between mb-4">
                <span class="p-2 ${tBg} rounded-lg bg-opacity-50">
                    <span class="material-symbols-outlined">${icon}</span>
                </span>
                <span class="text-xs font-bold text-on-surface-variant">${startTimeFromSlot(job.khung_gio_hen)}</span>
            </div>
            <h4 class="font-bold text-on-surface group-hover:${pColor} transition-colors">${escapeHtml(getServiceSummary(job))}</h4>
            <p class="text-sm text-on-surface-variant mt-1 truncate">${escapeHtml(getCustomer(job).name || 'Khách')}</p>
            <div class="mt-4 pt-4 border-t border-dashed border-outline-variant flex justify-between items-center">
                <span class="text-sm font-bold ${pColor}">${price}</span>
                <span class="material-symbols-outlined text-on-surface-variant group-hover:translate-x-1 transition-transform">arrow_forward_ios</span>
            </div>
        </a>
      `;
    }).join('');
  }

  function renderJobMapSpotlight(bookings, availableJobs, profile) {
    workerMapState.lastPayload = { bookings, availableJobs, profile };

    const refs = ensureJobMapShell();
    const map = ensureJobMap(refs);
    const mapBookings = buildDashboardMapBookings(bookings, availableJobs);
    const mineCount = mapBookings.filter((booking) => booking.mapSource === 'mine').length;
    const openCount = mapBookings.filter((booking) => booking.mapSource === 'open').length;
    const profilePoint = getWorkerPointFromProfile(profile);

    dom.availableJobsBadge.textContent = openCount > 0 ? `${openCount} đơn chưa có thợ` : 'Xem danh sách';
    refs.countText.textContent = `${mapBookings.length} điểm khách • ${mineCount} đơn của tôi • ${openCount} đơn mới`;
    updateJobMapStatus(
      refs,
      workerMapState.workerPosition
        ? 'Đã định vị vị trí của bạn'
        : (profilePoint ? 'Đang dùng vị trí trong hồ sơ' : 'Bật GPS để tính khoảng cách')
    );

    setJobMapEmptyState(
      refs,
      mapBookings.length === 0,
      'Chưa có khách nào có tọa độ hợp lệ',
      'Đơn đã nhận hoặc đơn mới chưa có thợ sẽ hiện tại đây khi khách có vị trí GPS.'
    );

    requestWorkerLocation(profile);

    if (!map || !workerMapState.markersLayer) {
      return;
    }

    workerMapState.markersLayer.clearLayers();

    if (workerMapState.workerMarker) {
      workerMapState.map.removeLayer(workerMapState.workerMarker);
      workerMapState.workerMarker = null;
    }

    const allBounds = [];

    if (workerMapState.workerPosition) {
      workerMapState.workerMarker = window.L.marker(
        [workerMapState.workerPosition.lat, workerMapState.workerPosition.lng],
        { icon: buildMapMarkerIcon('worker', 'my_location') }
      )
        .addTo(workerMapState.map)
        .bindTooltip('Vị trí của bạn', { direction: 'top', offset: [0, -24] });

      allBounds.push([workerMapState.workerPosition.lat, workerMapState.workerPosition.lng]);
    }

    mapBookings.forEach((booking) => {
      const variant = booking.mapSource === 'mine' ? 'mine' : 'open';
      const marker = window.L.marker(
        [booking.point.lat, booking.point.lng],
        {
          icon: buildMapMarkerIcon(
            variant,
            booking.mapSource === 'mine' ? 'construction' : 'person_pin_circle'
          ),
        }
      ).addTo(workerMapState.markersLayer);

      marker.bindTooltip(
        `${getCustomer(booking).name || 'Khách hàng'} • ${getServiceSummary(booking, 1)}`,
        { direction: 'top', offset: [0, -24], opacity: 0.92 }
      );

      marker.on('mouseover', () => {
        workerMapState.hoveredBookingId = booking.id;
        selectMapBooking(booking, refs);
      });
      marker.on('mouseout', () => {
        if (workerMapState.hoveredBookingId === booking.id) {
          workerMapState.hoveredBookingId = null;
        }
        scheduleMapBookingCardHide(refs);
      });
      marker.on('click', () => {
        workerMapState.hoveredBookingId = booking.id;
        selectMapBooking(booking, refs);
      });

      allBounds.push([booking.point.lat, booking.point.lng]);
    });

    if (allBounds.length > 0) {
      workerMapState.map.fitBounds(allBounds, {
        padding: [60, 60],
        maxZoom: allBounds.length === 1 ? 15 : 14,
      });
    } else {
      workerMapState.map.setView(
        workerMapState.workerPosition
          ? [workerMapState.workerPosition.lat, workerMapState.workerPosition.lng]
          : [12.2388, 109.1967],
        workerMapState.workerPosition ? 14 : 12
      );
    }

    const nextSelected = mapBookings.find((booking) => booking.id === workerMapState.hoveredBookingId)
      || (workerMapState.isHoveringInfoCard
        ? mapBookings.find((booking) => booking.id === workerMapState.selectedBookingId)
        : null)
      || null;

    if (nextSelected) {
      selectMapBooking(nextSelected, refs);
    } else {
      hideMapBookingCard(refs);
    }

    setTimeout(() => workerMapState.map.invalidateSize(), 0);
  }

  function renderTodaySchedule(bookings) {
    const todayKey = localDateKey();
    const todayJobs = [...bookings].filter((b) => b.ngay_hen === todayKey && b.trang_thai !== 'da_huy').sort((a,b) => startTimeFromSlot(a.khung_gio_hen).localeCompare(startTimeFromSlot(b.khung_gio_hen)));

    if (!todayJobs.length) {
      dom.todayScheduleList.innerHTML = `<div class="text-center text-sm text-on-surface-variant p-8">Bạn không có lịch làm việc nào trong hôm nay.</div>`;
      return;
    }

    const htmls = [];
    let lastTime = '';
    
    todayJobs.slice(0, 6).forEach(booking => {
        const time = startTimeFromSlot(booking.khung_gio_hen);
        const st = getStatusMeta(booking.trang_thai);
        const code = booking.ma_don ? `#${booking.ma_don.slice(0, 8).toUpperCase()}` : '';
        const addr = booking.dia_chi ? (booking.dia_chi.split(',')[0].substring(0, 15) + '...') : '';
        const title = `${escapeHtml(getServiceSummary(booking, 1))} - ${addr}`;
        
        // Output empty slots if needed to pad timeline? No, just output real ones.
        htmls.push(`
            <div class="flex border-b border-surface-container last:border-0">
                <div class="w-16 md:w-20 p-4 text-xs font-bold text-on-surface-variant border-r border-surface-container flex items-center justify-center">${time}</div>
                <div class="flex-1 p-4">
                    <a href="/worker/bookings/${booking.id}" class="${st.bg} border-l-4 ${st.border} p-3 rounded-lg flex flex-col md:flex-row justify-between items-start md:items-center gap-2 hover:opacity-80 transition-opacity">
                        <div>
                            <p class="text-sm font-bold ${st.text}">${title}</p>
                            <p class="text-xs ${st.text} opacity-80">Mã đơn: ${code}</p>
                        </div>
                        <span class="px-2 py-1 ${st.chip} text-[10px] font-bold rounded uppercase whitespace-nowrap">${st.label}</span>
                    </a>
                </div>
            </div>
        `);
    });
    
    dom.todayScheduleList.innerHTML = htmls.join('');
  }

  function renderRecentActivity(bookings) {
    if (!bookings.length) {
      dom.recentActivityList.innerHTML = `<div class="text-center text-sm text-on-surface-variant p-4">Chưa có hoạt động nào.</div>`;
      return;
    }

    dom.recentActivityList.innerHTML = bookings.slice(0, 5).map((booking) => {
      const st = getStatusMeta(booking.trang_thai);
      const isOk = ['da_xong'].includes(booking.trang_thai) ? 'emerald' : (['dang_lam', 'da_xac_nhan'].includes(booking.trang_thai) ? 'blue' : 'slate');
      const timeAgo = formatShortDate(booking.updated_at ? booking.updated_at.split('T')[0] : booking.ngay_hen);
      const sub = `${escapeHtml(getServiceSummary(booking))} • ${timeAgo}`;
      
      return `
        <div class="flex gap-4 relative">
            <div class="w-6 h-6 rounded-full bg-${isOk}-100 flex items-center justify-center z-10 shrink-0">
                <span class="material-symbols-outlined text-[14px] text-${isOk}-600 font-bold">${st.icon}</span>
            </div>
            <div>
                <p class="text-sm font-bold">${st.label} đơn hàng</p>
                <p class="text-xs text-on-surface-variant">${sub}</p>
            </div>
        </div>
      `;
    }).join('');
  }

  function renderRevenueChart(stats) {
    const chartData = Array.isArray(stats?.chart_data) ? stats.chart_data : [];
    const labels = chartData.length ? chartData.map((item) => item.date) : [];
    const values = chartData.length ? chartData.map((item) => Number(item.revenue || 0)) : [];
    
    dom.chartRevenueSummary.textContent = formatCompactMoney(values.reduce((a,b) => a+b, 0));

    if (revenueChartInstance) { revenueChartInstance.destroy(); }
    dom.revenueChart.innerHTML = '';
    
    if (chartData.length === 0) {
        dom.revenueChart.innerHTML = `<div class="flex items-center justify-center h-full text-on-surface-variant text-sm">Chưa có dữ liệu thống kê</div>`;
        return;
    }

    revenueChartInstance = new ApexCharts(dom.revenueChart, {
      chart: { type: 'area', height: 260, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
      series: [{ name: 'Doanh thu', data: values }],
      colors: ['#0ea5e9'],
      stroke: { curve: 'smooth', width: 3 },
      fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.0, stops: [0, 95] } },
      markers: { size: 4, strokeWidth: 0, hover: { size: 6 } },
      dataLabels: { enabled: false },
      xaxis: { categories: labels, labels: { style: { colors: '#6e7881', fontSize: '12px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { formatter: (val) => formatCompactMoney(val), style: { colors: '#6e7881', fontSize: '12px' } } },
      grid: { borderColor: '#eaeef0', strokeDashArray: 3 },
      tooltip: { y: { formatter: (val) => formatMoney(val) } },
    });
    revenueChartInstance.render();
  }

  function renderStatusChart(bookings) {
    const counts = { da_xong: 0, dang_lam: 0, da_huy: 0, cho: 0 };
    bookings.forEach(b => {
        if (b.trang_thai === 'da_xong') counts.da_xong++;
        else if (b.trang_thai === 'dang_lam') counts.dang_lam++;
        else if (b.trang_thai === 'da_huy') counts.da_huy++;
        else counts.cho++;
    });
    const total = counts.da_xong + counts.dang_lam + counts.da_huy + counts.cho;

    if (total === 0) {
        dom.statusChart.innerHTML = `<div class="flex items-center justify-center h-full text-sm">Chưa có dữ liệu</div>`;
        dom.statusLegend.innerHTML = '';
        return;
    }

    if (statusChartInstance) { statusChartInstance.destroy(); }
    
    const slices = [
        { name: 'Xong', val: counts.da_xong, color: '#006591', dot: 'bg-primary' },
        { name: 'Làm', val: counts.dang_lam, color: '#0ea5e9', dot: 'bg-primary-container' },
        { name: 'Chờ', val: counts.cho, color: '#89ceff', dot: 'bg-[#89ceff]' },
        { name: 'Hủy', val: counts.da_huy, color: '#dfe3e5', dot: 'bg-surface-container-highest' },
    ].filter(s => s.val > 0);

    statusChartInstance = new ApexCharts(dom.statusChart, {
      chart: { type: 'donut', height: 180, fontFamily: 'Inter, sans-serif' },
      series: slices.map(s => s.val), labels: slices.map(s => s.name), colors: slices.map(s => s.color),
      dataLabels: { enabled: false }, legend: { show: false }, stroke: { width: 0 },
      plotOptions: { pie: { donut: { size: '80%', labels: { show: true, name: {show: true}, value: {show: true, fontSize: '24px', fontWeight: 'bold'}, total: {show: true, showAlways: true, label: 'Tổng'} } } } },
    });
    statusChartInstance.render();

    dom.statusLegend.innerHTML = slices.map(s => `
        <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full ${s.dot}"></span>
                <span>${s.name}</span>
            </div>
            <span class="font-bold">${s.val}</span>
        </div>
    `).join('');
  }

  function renderTopbar(bookings, availableJobs) {
    const priorityState = buildDashboardPriorityState(bookings, availableJobs);
    const workerName = String(currentUser?.name || 'bạn').trim().split(' ').pop() || currentUser?.name || 'bạn';

    dom.headerDate.textContent = buildDateHeader();
    dom.heroWorkerName.textContent = workerName;

    if (priorityState.pendingConfirm.length > 0) {
      dom.liveStatusText.textContent = `${priorityState.pendingConfirm.length} ĐƠN CHỜ XÁC NHẬN`;
      dom.heroSummaryText.textContent = `Bạn có ${priorityState.pendingConfirm.length} đơn mới cần xác nhận. Kiểm tra ngay để không bỏ lỡ lượt nhận việc trong hôm nay.`;
      return;
    }

    if (priorityState.inProgress.length > 0) {
      dom.liveStatusText.textContent = `${priorityState.inProgress.length} ĐƠN ĐANG XỬ LÝ`;
      dom.heroSummaryText.textContent = `Bạn đang xử lý ${priorityState.inProgress.length} đơn. Ưu tiên hoàn tất các lịch gần nhất để giữ tiến độ trong ngày.`;
      return;
    }

    if (priorityState.todaySchedule.length > 0) {
      dom.liveStatusText.textContent = `${priorityState.todaySchedule.length} LỊCH HÔM NAY`;
      dom.heroSummaryText.textContent = `Hôm nay có ${priorityState.todaySchedule.length} lịch làm việc. Lịch gần nhất đã được đẩy lên cụm ưu tiên ở đầu trang.`;
      return;
    }

    if (priorityState.availableJobsCount > 0) {
      dom.liveStatusText.textContent = `${priorityState.availableJobsCount} VIỆC MỚI`;
      dom.heroSummaryText.textContent = `Có ${priorityState.availableJobsCount} việc mới quanh bạn. Mở ngay danh sách nhận việc để lấp lịch trống trong hôm nay.`;
      return;
    }

    dom.liveStatusText.textContent = 'DỮ LIỆU ĐÃ ĐỒNG BỘ';
    dom.heroSummaryText.textContent = 'Lịch hôm nay đang trống. Bạn có thể tranh thủ kiểm tra hồ sơ hoặc nhận thêm việc mới quanh khu vực.';
  }

  function renderHero(bookings, availableJobs, stats) {
    const priorityState = buildDashboardPriorityState(bookings, availableJobs);
    const monthRevenue = Number(stats?.doanh_thu_thang_nay || 0);

    dom.heroAvailableMeta.textContent = 'Việc quanh bạn';
    dom.heroTodayMeta.textContent = 'Đang làm';
    dom.heroRevenueMeta.textContent = 'Doanh thu tháng';
    dom.heroAvailableJobs.textContent = formatMetricValue(priorityState.availableJobsCount);
    dom.heroTodayJobs.textContent = formatMetricValue(priorityState.inProgress.length);
    dom.heroMonthRevenue.textContent = formatCompactMoney(monthRevenue);
  }

  async function fetchResource(endpoint) {
    try { return await callApi(endpoint, 'GET'); } catch (error) { return { ok: false, error }; }
  }

  async function loadDashboard({ notify = false } = {}) {
    dom.refreshButton.classList.add('animate-spin');
    dom.liveStatusText.textContent = 'ĐANG ĐỒNG BỘ...';
    
    const [bookingsRes, availableRes, statsRes, profileRes] = await Promise.all([
      fetchResource('/don-dat-lich?per_page=100'), fetchResource('/don-dat-lich/available'), fetchResource('/worker/stats'), fetchResource(`/ho-so-tho/${currentUser.id}`)
    ]);

    const bookings = extractList(bookingsRes).sort((l, r) => `${r.ngay_hen||''} ${startTimeFromSlot(r.khung_gio_hen)}`.localeCompare(`${l.ngay_hen||''} ${startTimeFromSlot(l.khung_gio_hen)}`));
    const availableJobs = extractList(availableRes);
    const stats = statsRes?.ok ? statsRes.data : { chart_data:[] };
    const profile = profileRes?.ok ? profileRes.data : null;

    renderPrioritySummary(bookings, availableJobs);
    renderTopbar(bookings, availableJobs); 
    renderHero(bookings, availableJobs, stats);
    renderProfile(profile, stats); 
    renderKpis(bookings, stats, profile); 
    renderJobMapSpotlight(bookings, availableJobs, profile);
    renderRecentActivity(bookings); 
    renderTodaySchedule(bookings); 
    renderRevenueChart(stats); 
    renderStatusChart(bookings);

    setTimeout(() => {
        dom.refreshButton.classList.remove('animate-spin');
        if (notify) showToast('Đã làm mới', 'success');
    }, 500);
  }

  dom.refreshButton?.addEventListener('click', () => loadDashboard({ notify: true }));
  loadDashboard();
</script>
@endpush
