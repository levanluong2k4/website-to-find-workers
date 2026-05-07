@extends('layouts.app')

@section('title', 'Quản lý đơn hàng - Admin')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        corePlugins: {
            preflight: false
        },
        theme: {
            extend: {
                colors: {
                    primary: '#10B981',
                    'on-primary': '#ffffff',
                    'primary-fixed-dim': '#059669',
                    secondary: '#0F172A',
                    background: '#f8fafc',
                    surface: '#ffffff',
                    'surface-container-lowest': '#ffffff',
                    'surface-container-low': '#f1f5f9',
                    'surface-container': '#e2e8f0',
                    'surface-container-high': '#cbd5e1',
                    'surface-container-highest': '#94a3b8',
                    'surface-variant': '#f1f5f9',
                    'on-surface': '#0f172a',
                    'on-surface-variant': '#475569',
                    'on-background': '#0f172a',
                    'outline-variant': '#cbd5e1',
                    'inverse-surface': '#1e293b',
                    'primary-fixed': '#d1fae5',
                    'error': '#ef4444',
                    'on-error': '#ffffff',
                    'warning': '#f59e0b',
                    'on-warning': '#ffffff',
                    'success': '#10b981',
                    'on-success': '#ffffff',
                },
                fontFamily: {
                    headline: ['Inter', 'system-ui', 'sans-serif'],
                    body: ['Inter', 'system-ui', 'sans-serif'],
                    label: ['Inter', 'system-ui', 'sans-serif'],
                },
            }
        }
    }
</script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
<link rel="stylesheet" href="{{ asset('assets/css/admin/bookings.css') }}">
<style>
    /* Override Bootstrap table styles for this page */
    #adminOrdersPage table {
        border-collapse: collapse !important;
        width: 100% !important;
        margin-bottom: 0 !important;
    }

    #adminOrdersPage table> :not(caption)>* {
        border-color: transparent !important;
    }

    #adminOrdersPage table> :not(caption)>*>* {
        border-bottom-width: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
    }

    #adminOrdersPage tr {
        background-color: transparent !important;
    }

    /* Allow Tailwind padding on td/th */
    #adminOrdersPage td.p-4,
    #adminOrdersPage th.p-4 {
        padding: 1rem !important;
    }

    #adminOrdersPage td.p-3,
    #adminOrdersPage th.p-3 {
        padding: 0.75rem !important;
    }

    /* Remove Bootstrap global body background that interferes */
    #adminOrdersPage {
        position: relative;
    }

    #adminOrdersPage *,
    #bookingDetailDrawer * {
        box-sizing: border-box;
    }

    #adminOrdersPage .btn {
        all: unset;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    body {
        overflow-x: hidden;
    }

    .material-symbols-outlined {
        font-family: 'Material Symbols Outlined';
        font-weight: normal;
        font-style: normal;
        font-size: 24px;
        line-height: 1;
        letter-spacing: normal;
        text-transform: none;
        display: inline-block;
        white-space: nowrap;
        direction: ltr;
        -webkit-font-smoothing: antialiased;
    }

    /* Bootstrap thead override */
    #adminOrdersPage thead tr {
        border-bottom: 1px solid #e2e8f0 !important;
        background-color: transparent !important;
    }

    #adminOrdersPage thead th {
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        vertical-align: middle !important;
    }

    /* Flex utilities fix for Bootstrap container */
    #adminOrdersPage .flex {
        display: flex !important;
    }

    #adminOrdersPage .flex-col {
        flex-direction: column !important;
    }

    #adminOrdersPage .items-center {
        align-items: center !important;
    }

    #adminOrdersPage .justify-between {
        justify-content: space-between !important;
    }

    #adminOrdersPage .gap-8>*+* {
        margin-top: 2rem;
    }

    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    /* Booking table rows compact */
    #adminOrdersPage tbody tr td {
        vertical-align: top;
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<main class="flex-1 p-6 md:p-8 max-w-[1600px] mx-auto w-full flex flex-col gap-8 bg-background text-on-surface" id="adminOrdersPage">
    <!-- Page Header & Stats -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <h2 class="text-3xl font-extrabold font-headline text-on-surface tracking-tight mb-2">Quản lý đơn đặt lịch</h2>
            <p class="text-on-surface-variant font-body">Tổng quan và quản lý tất cả đơn đặt dịch vụ.</p>
        </div>
        <div class="flex items-center gap-3">
            <button id="btnRefreshOrders" class="flex items-center gap-2 px-4 py-2 bg-surface-container-highest text-on-surface rounded-full font-medium text-sm hover:bg-surface-variant transition-colors">
                <span class="material-symbols-outlined text-[18px]">refresh</span>
                Làm mới
            </button>
            <button id="btnExportOrders" class="flex items-center gap-2 px-4 py-2 bg-primary text-on-primary rounded-full font-medium text-sm hover:bg-primary/90 transition-colors shadow-sm">
                <span class="material-symbols-outlined text-[18px]">download</span>
                Xuất CSV
            </button>
        </div>
    </div>

    <!-- Bento Grid Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="bookingStatsCards">
        <article class="admin-orders-stat-card bg-surface-container-lowest rounded-xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border-l-4 border-primary">
            <p class="text-xs font-label uppercase tracking-wider text-on-surface-variant mb-1">Đang tải dữ liệu...</p>
            <div class="flex items-baseline gap-2">
                <h3 class="text-4xl font-headline font-bold text-on-surface">0</h3>
            </div>
        </article>
    </div>

    <!-- Complex Data Section -->
    <div class="bg-surface-container-lowest rounded-xl shadow-[0_8px_30px_-4px_rgba(0,0,0,0.04)] flex flex-col overflow-hidden">

        <!-- Filters & Search Bar -->
        <div class="p-5 border-b border-surface-container flex flex-wrap gap-4 items-center bg-surface/50">
            <div class="relative flex-1 min-w-[250px]">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                <input id="orderSearchInput" class="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border-none rounded-lg text-sm focus:ring-1 focus:ring-outline-variant focus:bg-surface-container-lowest transition-all" placeholder="Tìm kiếm (Mã đơn, tên, SĐT)..." type="text" />
            </div>
            <select id="orderStatusFilter" class="bg-surface-container-low border-none text-sm rounded-lg py-2.5 pl-4 pr-10 focus:ring-1 focus:ring-outline-variant text-on-surface-variant font-medium">
                <option value="">Tất cả trạng thái</option>
            </select>
            <select id="orderServiceFilter" class="bg-surface-container-low border-none text-sm rounded-lg py-2.5 pl-4 pr-10 focus:ring-1 focus:ring-outline-variant text-on-surface-variant font-medium">
                <option value="">Tất cả dịch vụ</option>
            </select>
            <button id="btnToggleMoreFilters" type="button" class="flex items-center gap-2 px-4 py-2.5 bg-surface-container-low text-on-surface-variant rounded-lg font-medium text-sm hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[18px]">filter_list</span> Bộ lọc khác
            </button>
        </div>

        <!-- More Filters (Collapsible) -->
        <div id="moreFiltersSection" class="bg-surface-container-low/50 border-b border-surface-container p-6 transition-all duration-300 hidden">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <!-- Technician -->
                <div class="flex flex-col gap-2">
                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Thợ phụ trách</label>
                    <select id="orderWorkerFilter" class="w-full py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary">
                        <option value="">Tất cả thợ</option>
                    </select>
                </div>
                <!-- Payment Status -->
                <div class="flex flex-col gap-2">
                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Trạng thái thanh toán</label>
                    <select id="orderPaymentFilter" class="w-full py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary">
                        <option value="">Tất cả thanh toán</option>
                    </select>
                </div>
                <!-- Booking Type / Mode -->
                <div class="flex flex-col gap-2">
                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Hình thức</label>
                    <select id="orderModeFilter" class="w-full py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary">
                        <option value="">Tất cả hình thức</option>
                    </select>
                </div>
                <!-- Priority -->
                <div class="flex flex-col gap-2">
                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Mức độ ưu tiên</label>
                    <select id="orderPriorityFilter" class="w-full py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary">
                        <option value="">Tất cả ưu tiên</option>
                    </select>
                </div>
                <!-- SLA Status -->
                <div class="flex flex-col gap-2">
                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                        Trạng thái SLA
                        <span class="inline-block bg-error text-white text-[10px] px-1.5 rounded ml-1" id="orderSlaAlertBadge" hidden>0</span>
                    </label>
                    <div id="orderSlaDropdown" class="hidden">
                        <button type="button" id="orderSlaDropdownToggle"><span id="orderSlaDropdownLabel"></span></button>
                        <div id="orderSlaDropdownMenu"></div>
                    </div>
                    <select id="orderSlaFilter" class="w-full py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary">
                        <option value="">Tất cả SLA</option>
                    </select>
                </div>
                <!-- Date Range -->
                <div class="md:col-span-2 flex flex-col gap-2">
                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Khoảng thời gian</label>
                    <div class="flex items-center gap-3">
                        <input id="orderDateFromFilter" class="flex-1 py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary" type="date" />
                        <span class="text-on-surface-variant">đến</span>
                        <input id="orderDateToFilter" class="flex-1 py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary" type="date" />
                    </div>
                </div>
                <!-- Hidden Sorts -->
                <div class="hidden">
                    <select id="orderSortByFilter">
                        <option value="created_at">Ngày tạo</option>
                    </select>
                    <select id="orderSortDirFilter">
                        <option value="desc">Giảm dần</option>
                        <option value="asc">Tăng dần</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex overflow-x-auto hide-scrollbar border-b border-surface-container px-2" id="orderQuickViews">
            <button class="admin-orders-view-tab px-6 py-4 text-sm font-headline font-bold border-b-2 whitespace-nowrap text-primary border-primary is-active" data-view="all">Tất cả đơn</button>
            <button class="admin-orders-view-tab px-6 py-4 text-sm font-headline font-medium text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap" data-view="overdue">Quá hạn</button>
            <button class="admin-orders-view-tab px-6 py-4 text-sm font-headline font-medium text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap" data-view="unpaid">Chờ thanh toán</button>
            <button class="admin-orders-view-tab px-6 py-4 text-sm font-headline font-medium text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap" data-view="complaint">bảo hành</button>
            <button class="admin-orders-view-tab px-6 py-4 text-sm font-headline font-medium text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap" data-view="contact_issue">Không liên lạc được</button>
            <button class="admin-orders-view-tab px-6 py-4 text-sm font-headline font-medium text-on-surface-variant hover:text-primary transition-colors whitespace-nowrap" data-view="unassigned">Chưa phân công</button>
        </div>

        <!-- Bulk Actions -->
        <div id="bulkActionBar" class="bg-primary-fixed/30 px-6 py-3 flex items-center justify-between border-b border-surface-container" hidden>
            <span class="text-sm font-medium text-on-surface">Đã chọn <strong id="bulkSelectedCount">0</strong> đơn</span>
            <div class="flex gap-2">
                <button type="button" id="btnBulkAssignWorker" class="text-xs font-medium px-3 py-1.5 bg-surface-container-lowest rounded-md shadow-sm hover:bg-surface transition-colors border border-outline-variant/30">Gán/đổi thợ</button>
                <button type="button" id="btnBulkChangeStatus" class="text-xs font-medium px-3 py-1.5 bg-surface-container-lowest rounded-md shadow-sm hover:bg-surface transition-colors border border-outline-variant/30">Đổi trạng thái</button>
                <button type="button" id="btnBulkExportSelected" class="text-xs font-medium px-3 py-1.5 bg-surface-container-lowest rounded-md shadow-sm hover:bg-surface transition-colors border border-outline-variant/30">Export đã chọn</button>
                <button type="button" id="btnClearSelection" class="text-xs font-medium px-3 py-1.5 bg-surface-container-lowest rounded-md shadow-sm hover:bg-surface transition-colors border border-outline-variant/30">Bỏ chọn</button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface/50 text-xs font-label uppercase tracking-wider text-on-surface-variant border-b border-surface-container">
                        <th class="p-4 w-12 text-center">
                            <input type="checkbox" id="selectAllBookings" class="rounded border-outline-variant text-primary focus:ring-primary/50" />
                        </th>
                        <th class="p-4 font-medium">Đơn hàng & SLA</th>
                        <th class="p-4 font-medium">Khách hàng</th>
                        <th class="p-4 font-medium">Dịch vụ</th>
                        <th class="p-4 font-medium">Thợ & Lịch hẹn</th>
                        <th class="p-4 font-medium">Chi phí</th>
                        <th class="p-4 font-medium">Thanh toán & Cảnh báo</th>
                        <th class="p-4 font-medium text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="bookingTableBody" class="text-sm font-body divide-y divide-surface-container/50">
                    <tr>
                        <td colspan="8" class="text-center py-5 text-on-surface-variant">Đang tải danh sách đơn...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="orderPagination" class="p-4 border-t border-surface-container flex items-center justify-between text-sm text-on-surface-variant bg-surface/30">
        </div>
    </div>
</main>

<div class="fixed inset-0 bg-inverse-surface/40 z-40 hidden transition-opacity duration-300" id="bookingDetailOverlay"></div>
<aside class="fixed top-0 right-0 h-screen w-full max-w-2xl bg-surface shadow-2xl z-50 transform translate-x-full transition-transform duration-300 overflow-hidden flex flex-col" id="bookingDetailDrawer" aria-hidden="true">
    <header class="flex items-center justify-between p-6 border-b border-surface-container bg-surface-container-lowest">
        <div>
            <p class="mb-1 text-xs font-bold text-primary uppercase tracking-wider">Chi tiết đơn</p>
            <h2 class="text-2xl font-headline font-bold text-on-surface mb-0" id="detailDrawerTitle">--</h2>
        </div>
        <button type="button" class="w-10 h-10 rounded-full flex items-center justify-center bg-surface-container hover:bg-surface-container-high text-on-surface-variant transition-colors" id="btnCloseBookingDrawer">
            <span class="material-symbols-outlined">close</span>
        </button>
    </header>

    <div class="flex-1 overflow-y-auto p-6 space-y-8 bg-surface-container-lowest">
        <section id="detailSummaryCards" class="flex flex-wrap gap-4"></section>

        <section>
            <h3 class="text-sm font-bold text-on-surface mb-4">Thông tin tổng quan</h3>
            <div id="detailInfoBlock" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
        </section>

        <section>
            <h3 class="text-sm font-bold text-on-surface mb-4">Gallery trước/sau sửa</h3>
            <div id="detailMediaGallery" class="grid grid-cols-2 md:grid-cols-4 gap-4"></div>
        </section>

        <section>
            <h3 class="text-sm font-bold text-on-surface mb-4">Timeline xử lý</h3>
            <div id="detailTimeline" class="space-y-4"></div>
        </section>

        <section>
            <h3 class="text-sm font-bold text-on-surface mb-4">Lịch sử thao tác</h3>
            <div id="detailHistory" class="space-y-2"></div>
        </section>

        <section>
            <h3 class="text-sm font-bold text-on-surface mb-4">bảo hành</h3>
            <div id="detailComplaint"></div>
            <a class="inline-flex items-center justify-center px-4 py-2 mt-2 border border-error text-error hover:bg-error/10 rounded-lg text-sm font-medium transition-colors" id="detailComplaintLink" href="/admin/customer-feedback">Mở trang xử lý bảo hành</a>
        </section>

        <section>
            <h3 class="text-sm font-bold text-on-surface mb-4">Lịch sử thanh toán</h3>
            <div class="overflow-x-auto rounded-lg border border-surface-container">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-surface/50 border-b border-surface-container">
                            <th class="p-3 font-medium text-on-surface-variant">Thời gian</th>
                            <th class="p-3 font-medium text-on-surface-variant">Số tiền</th>
                            <th class="p-3 font-medium text-on-surface-variant">Phương thức</th>
                            <th class="p-3 font-medium text-on-surface-variant">Trạng thái</th>
                            <th class="p-3 font-medium text-on-surface-variant">Mã giao dịch</th>
                        </tr>
                    </thead>
                    <tbody id="detailPaymentsBody" class="divide-y divide-surface-container/50">
                        <tr>
                            <td colspan="5" class="p-3 text-center text-on-surface-variant">Chưa có giao dịch</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-orders-detail-block">
            <h3 class="text-sm font-bold text-on-surface mb-4">Hành động nhanh</h3>

            <div id="detailReadonlyNotice" class="hidden p-4 rounded-xl bg-surface-container border border-surface-container-high mb-4">
                <p class="text-sm text-on-surface-variant flex items-center gap-2">
                    <span class="material-symbols-outlined text-warning">lock</span>
                    Đơn hàng đã hoàn tất hoặc đã hủy. Không thể thực hiện thêm thao tác.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="detailActionsGrid">
                <div class="bg-surface p-4 rounded-xl border border-surface-container">
                    <h4 class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-3">Cập nhật trạng thái</h4>
                    <select id="detailStatusSelect" class="w-full py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary mb-2"></select>
                    <select id="detailCancelReasonSelect" class="w-full py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary mb-2">
                        <option value="">Lý do hủy (bắt buộc khi hủy)</option>
                    </select>
                    <textarea id="detailCancelNoteInput" class="w-full py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary mb-3" rows="2" placeholder="Ghi chú hủy (tùy chọn)"></textarea>
                    <button type="button" class="w-full py-2 px-4 bg-warning text-on-warning hover:bg-warning/90 rounded-lg text-sm font-medium transition-colors" id="btnUpdateBookingStatus">Cập nhật trạng thái</button>
                </div>

                <div class="bg-surface p-4 rounded-xl border border-surface-container">
                    <h4 class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-3">Gán/đổi thợ</h4>
                    <select id="detailWorkerSelect" class="w-full py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary mb-3">
                        <option value="">Chọn thợ</option>
                    </select>
                    <button type="button" class="w-full py-2 px-4 bg-primary text-on-primary hover:bg-primary/90 rounded-lg text-sm font-medium transition-colors" id="btnAssignWorker">Cập nhật thợ</button>
                </div>

                <div class="bg-surface p-4 rounded-xl border border-surface-container">
                    <h4 class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-3">Đổi lịch hẹn</h4>
                    <input type="date" id="detailRescheduleDate" class="w-full py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary mb-2">
                    <select id="detailRescheduleSlot" class="w-full py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary mb-3">
                        <option value="">Chọn khung giờ</option>
                    </select>
                    <button type="button" class="w-full py-2 px-4 bg-surface-container hover:bg-surface-container-high text-primary rounded-lg text-sm font-medium transition-colors border border-primary/20" id="btnRescheduleBooking">Cập nhật lịch</button>
                </div>

                <div class="md:col-span-2 bg-surface rounded-xl border border-surface-container overflow-hidden">
                    <div class="p-4 border-b border-surface-container flex items-center justify-between">
                        <h4 class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Cập nhật chi phí</h4>
                        <button type="button" class="py-1.5 px-4 bg-success text-on-success hover:bg-success/90 rounded-lg text-xs font-medium transition-colors" id="btnUpdateBookingCost">
                            <span class="material-symbols-outlined text-[14px] align-middle mr-1">save</span>Lưu chi phí
                        </button>
                    </div>

                    {{-- Labor / Tiền công --}}
                    <div class="p-4 border-b border-surface-container">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-on-surface uppercase tracking-wider flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-primary">handyman</span>
                                Tiền công
                            </span>
                            <span class="text-xs font-mono text-primary" id="detailLaborCostDisplay">0 ₫</span>
                        </div>

                        {{-- Hidden selects (used by JS for state) --}}
                        <select id="adminLaborSymptomSelect" hidden></select>
                        <select id="adminLaborCauseSelect" hidden></select>
                        <select id="adminLaborResolutionSelect" hidden></select>

                        {{-- Cascade pickers --}}
                        <div class="grid grid-cols-1 gap-2 mb-3">
                            {{-- Triệu chứng --}}
                            <div class="dispatch-search-picker" id="adminLaborSymptomPicker">
                                <button type="button" id="adminLaborSymptomTrigger" class="dispatch-search-picker__trigger w-full flex items-center justify-between gap-2 py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm" aria-expanded="false" aria-haspopup="listbox">
                                    <span id="adminLaborSymptomTriggerLabel" class="text-on-surface-variant">Chọn triệu chứng</span>
                                    <span class="material-symbols-outlined text-[16px] text-on-surface-variant">expand_more</span>
                                </button>
                                <div id="adminLaborSymptomPanel" class="dispatch-search-picker__panel" hidden>
                                    <div class="dispatch-search-picker__search-wrap">
                                        <span class="material-symbols-outlined text-[14px] text-on-surface-variant">search</span>
                                        <input type="text" id="adminLaborSymptomSearch" class="dispatch-search-picker__search" placeholder="Tìm triệu chứng...">
                                    </div>
                                    <div id="adminLaborSymptomOptions" class="dispatch-search-picker__options" role="listbox"></div>
                                </div>
                            </div>

                            {{-- Nguyên nhân --}}
                            <div class="dispatch-search-picker" id="adminLaborCausePicker">
                                <button type="button" id="adminLaborCauseTrigger" class="dispatch-search-picker__trigger w-full flex items-center justify-between gap-2 py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm" aria-expanded="false" aria-haspopup="listbox" disabled>
                                    <span id="adminLaborCauseTriggerLabel" class="text-on-surface-variant">Chọn nguyên nhân</span>
                                    <span class="material-symbols-outlined text-[16px] text-on-surface-variant">expand_more</span>
                                </button>
                                <div id="adminLaborCausePanel" class="dispatch-search-picker__panel" hidden>
                                    <div class="dispatch-search-picker__search-wrap">
                                        <span class="material-symbols-outlined text-[14px] text-on-surface-variant">search</span>
                                        <input type="text" id="adminLaborCauseSearch" class="dispatch-search-picker__search" placeholder="Tìm nguyên nhân...">
                                    </div>
                                    <div id="adminLaborCauseOptions" class="dispatch-search-picker__options" role="listbox"></div>
                                </div>
                            </div>

                            {{-- Hướng xử lý --}}
                            <div class="flex gap-2">
                                <select id="adminLaborResolutionSelectVisible" class="flex-1 py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary" disabled>
                                    <option value="">Chọn hướng xử lý</option>
                                </select>
                                <button type="button" id="btnAdminAddLaborItem" class="flex items-center gap-1.5 py-2 px-4 bg-primary text-on-primary rounded-lg text-xs font-semibold transition-colors hover:bg-primary/90 disabled:opacity-40 disabled:cursor-not-allowed" disabled>
                                    <span class="material-symbols-outlined text-[15px]">add</span> Thêm tiền công
                                </button>
                            </div>
                        </div>

                        {{-- Status / price hint --}}
                        <p class="text-[11px] text-on-surface-variant mb-1" id="detailLaborCatalogStatus">Mở chi tiết đơn để tải danh mục tiền công theo dịch vụ.</p>
                        <p class="text-[11px] text-primary font-medium mb-3" id="adminLaborResolutionPrice"></p>

                        {{-- Labor items list --}}
                        <div id="detailLaborItemsEditor" class="space-y-2">
                            <div class="text-xs text-on-surface-variant italic text-center py-4 border border-dashed border-outline-variant/50 rounded-lg" id="detailLaborItemsEmpty">
                                Chưa có tiền công. Chọn hướng xử lý ở trên hoặc nhập thủ công.
                            </div>
                        </div>

                        <div class="flex gap-2 mt-3">
                            <button type="button" id="btnAddManualLaborRow" class="flex items-center gap-1 py-2 px-3 text-xs bg-surface-container hover:bg-surface-container-high text-on-surface-variant rounded-lg font-medium transition-colors">
                                <span class="material-symbols-outlined text-[14px]">edit_square</span> Thêm tay
                            </button>
                        </div>
                    </div>

                    {{-- Parts / Linh kiện --}}
                    <div class="p-4 border-b border-surface-container">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-on-surface uppercase tracking-wider flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-secondary">memory</span>
                                Linh kiện thay thế
                            </span>
                            <span class="text-xs font-mono text-secondary" id="detailPartCostDisplay">0 ₫</span>
                        </div>

                        <div class="admin-orders-part-editor" id="detailPartEditorWrap">
                            <div class="admin-orders-part-catalog mb-3">
                                <div class="admin-orders-part-catalog__field">
                                    <span class="text-[11px] text-on-surface-variant mb-1 block">Tìm trong danh mục linh kiện</span>
                                    <div class="admin-orders-part-catalog__search-shell flex items-center gap-2 bg-surface-container-lowest border border-outline-variant rounded-lg px-3 py-2 focus-within:ring-1 focus-within:ring-primary relative">
                                        <span class="material-symbols-outlined text-on-surface-variant text-[16px]">hardware</span>
                                        <input type="text" id="detailPartCatalogSearch" class="flex-1 bg-transparent border-none outline-none text-sm" placeholder="Tìm linh kiện theo tên" role="combobox" aria-autocomplete="list" aria-expanded="false">
                                        <div class="dispatch-part-catalog__suggestions" id="detailPartCatalogSuggestions" hidden></div>
                                    </div>
                                </div>
                                <p class="text-[11px] text-on-surface-variant mt-1 mb-3" id="detailPartCatalogStatus">Đang chờ tải danh mục linh kiện theo dịch vụ...</p>
                                <div class="dispatch-part-catalog__results grid grid-cols-1 gap-2" id="detailPartCatalogResults"></div>
                            </div>

                            <div id="detailPartItemsEditor" class="dispatch-pricing-v2-parts-list"></div>

                            <div class="flex gap-2 mt-3">
                                <button type="button" id="btnAddBookingPartRow" class="flex-1 flex items-center justify-center gap-1 py-2 text-xs bg-secondary/10 hover:bg-secondary/20 text-secondary rounded-lg font-medium transition-colors" disabled>
                                    <span class="material-symbols-outlined text-[14px]">playlist_add</span> Thêm đã chọn
                                </button>
                                <button type="button" id="btnAddManualBookingPartRow" class="flex items-center gap-1 py-2 px-3 text-xs bg-surface-container hover:bg-surface-container-high text-on-surface-variant rounded-lg font-medium transition-colors">
                                    <span class="material-symbols-outlined text-[14px]">edit_square</span> Thêm tay
                                </button>
                            </div>

                            <div class="mt-3">
                                <textarea id="detailPartNote" class="w-full py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary" rows="2" placeholder="Ghi chú linh kiện (không bắt buộc)"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Travel fees --}}
                    <div class="p-4">
                        <span class="text-xs font-bold text-on-surface uppercase tracking-wider flex items-center gap-1.5 mb-3">
                            <span class="material-symbols-outlined text-[16px] text-tertiary">directions_car</span>
                            Phí di chuyển
                        </span>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex flex-col gap-1">
                                <label class="text-xs text-on-surface-variant" for="detailTravelCost">Phí đi lại (₫)</label>
                                <input type="number" min="0" step="1000" id="detailTravelCost" class="w-full py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-xs text-on-surface-variant" for="detailTransportCost">Phí vận chuyển (₫)</label>
                                <input type="number" min="0" step="1000" id="detailTransportCost" class="w-full py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary">
                            </div>
                        </div>
                        <input type="hidden" id="detailLaborCost">
                        <input type="hidden" id="detailPartCost">
                    </div>
                </div>

                <div class="bg-surface p-4 rounded-xl border border-surface-container">
                    <h4 class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-3">Thanh toán</h4>
                    <select id="detailPaymentMethodSelect" class="w-full py-2 px-3 bg-surface-container-lowest border border-outline-variant rounded-lg text-sm focus:ring-1 focus:ring-primary mb-3">
                        <option value="cod">Tiền mặt (COD)</option>
                        <option value="transfer">Chuyển khoản</option>
                    </select>
                    <button type="button" class="w-full py-2 px-4 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-lg text-sm font-medium transition-colors mb-2" id="btnUpdatePaymentMethod">Cập nhật phương thức</button>
                    <button type="button" class="w-full py-2 px-4 bg-success text-on-success hover:bg-success/90 rounded-lg text-sm font-medium transition-colors" id="btnConfirmCashPayment">Xác nhận đã thu tiền mặt</button>
                </div>
            </div>
        </section>
    </div>
</aside>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/bookings.js') }}"></script>
@endpush