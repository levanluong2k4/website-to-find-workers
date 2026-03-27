@extends('layouts.app')
@section('title', 'Tổng quan - Thợ Tốt NTU')

@push('styles')
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script id="tailwind-config">
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
    }

    @media (max-width: 640px) {
        .worker-dashboard-topbar-actions {
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
                <a href="/worker/jobs" class="bg-gradient-to-br from-primary-container to-primary text-white px-6 py-2.5 rounded-xl font-semibold flex items-center gap-2 shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity">
                    <span class="material-symbols-outlined text-lg">add</span> Việc mới
                </a>
            </div>
        </header>

        <div class="worker-dashboard-content px-8 pb-12 space-y-8">
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
                        <a href="/worker/jobs" class="flex items-center gap-3 p-4 bg-surface-container-low hover:bg-primary hover:text-white rounded-2xl transition-all group/btn">
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
                            <a class="text-sm font-semibold text-primary hover:underline" href="/worker/jobs" id="availableJobsBadge">Xem tất cả</a>
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

  function renderTopbar(bookings, availableJobs) {
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

  function renderHero(bookings, availableJobs, stats) {
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
        <a href="/worker/jobs" class="block bg-surface-container-lowest p-5 rounded-2xl border-2 border-primary/10 hover:border-primary transition-all group">
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

  async function fetchResource(endpoint) {
    try { return await callApi(endpoint, 'GET'); } catch (error) { return { ok: false, error }; }
  }

  async function loadDashboard({ notify = false } = {}) {
    dom.refreshButton.classList.add('animate-spin');
    dom.liveStatusText.textContent = 'ĐANG ĐỒNG BỘ...';
    
    const [bookingsRes, availableRes, statsRes, profileRes] = await Promise.all([
      fetchResource('/don-dat-lich'), fetchResource('/don-dat-lich/available'), fetchResource('/worker/stats'), fetchResource(`/ho-so-tho/${currentUser.id}`)
    ]);

    const bookings = extractList(bookingsRes).sort((l, r) => `${r.ngay_hen||''} ${startTimeFromSlot(r.khung_gio_hen)}`.localeCompare(`${l.ngay_hen||''} ${startTimeFromSlot(l.khung_gio_hen)}`));
    const availableJobs = extractList(availableRes);
    const stats = statsRes?.ok ? statsRes.data : { chart_data:[] };
    const profile = profileRes?.ok ? profileRes.data : null;

    renderTopbar(bookings, availableJobs); 
    renderHero(bookings, availableJobs, stats);
    renderProfile(profile, stats); 
    renderKpis(bookings, stats, profile); 
    renderJobSpotlight(availableJobs);
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
