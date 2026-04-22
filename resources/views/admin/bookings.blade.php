@extends('layouts.app')

@section('title', 'Quản lý đơn hàng - Admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/admin/bookings.css') }}">
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container-fluid py-4 admin-orders-page" id="adminOrdersPage">
    <header class="admin-orders-header">
        <div>
            <p class="admin-orders-kicker mb-1">Operations Center</p>
            <h1 class="admin-orders-title mb-1">Quản lý đơn hàng</h1>
            <p class="admin-orders-subtitle mb-0">Theo dõi SLA, điều phối thợ, cập nhật chi phí và xử lý khiếu nại trên cùng một màn hình.</p>
        </div>
        <div class="admin-orders-header-actions">
            <button type="button" class="btn btn-outline-primary" id="btnRefreshOrders">
                <span class="material-symbols-outlined">refresh</span>
                Làm mới
            </button>
            <button type="button" class="btn btn-primary" id="btnExportOrders">
                <span class="material-symbols-outlined">download</span>
                Export CSV
            </button>
        </div>
    </header>

    <section class="admin-orders-stats" id="bookingStatsCards">
        <article class="admin-orders-stat-card">
            <span class="label">Tong don</span>
            <strong>0</strong>
            <small>Đang tải dữ liệu...</small>
        </article>
    </section>

    <section class="admin-orders-toolbar card">
        <div class="card-body">
            <div class="admin-orders-toolbar-grid">
                <label class="admin-orders-field admin-orders-field--search">
                    <span>Tìm kiếm</span>
                    <input type="search" id="orderSearchInput" class="form-control" placeholder="Mã đơn, tên khách, SĐT...">
                </label>

                <label class="admin-orders-field">
                    <span>Trạng thái</span>
                    <select id="orderStatusFilter" class="form-select"></select>
                </label>

                <label class="admin-orders-field">
                    <span>Dịch vụ</span>
                    <select id="orderServiceFilter" class="form-select">
                        <option value="">Tất cả dịch vụ</option>
                    </select>
                </label>

                <label class="admin-orders-field">
                    <span>Tho</span>
                    <select id="orderWorkerFilter" class="form-select">
                        <option value="">Tất cả thợ</option>
                    </select>
                </label>

                <label class="admin-orders-field">
                    <span>Thanh toan</span>
                    <select id="orderPaymentFilter" class="form-select"></select>
                </label>

                <label class="admin-orders-field">
                    <span>Hình thức</span>
                    <select id="orderModeFilter" class="form-select"></select>
                </label>

                <label class="admin-orders-field">
                    <span>Ưu tiên</span>
                    <select id="orderPriorityFilter" class="form-select"></select>
                </label>

                <label class="admin-orders-field">
                    <span class="admin-orders-field__label-with-badge">
                        SLA
                        <span class="admin-orders-count-badge" id="orderSlaAlertBadge" hidden>0</span>
                    </span>
                    <div class="admin-orders-sla-dropdown" id="orderSlaDropdown">
                        <button type="button" class="form-select admin-orders-sla-dropdown__toggle" id="orderSlaDropdownToggle" aria-haspopup="listbox" aria-expanded="false">
                            <span id="orderSlaDropdownLabel">Tất cả SLA</span>
                        </button>
                        <div class="admin-orders-sla-dropdown__menu" id="orderSlaDropdownMenu" role="listbox" hidden></div>
                        <select id="orderSlaFilter" class="admin-orders-sla-dropdown__native" aria-hidden="true" tabindex="-1"></select>
                    </div>
                </label>

                <label class="admin-orders-field">
                    <span>Từ ngày</span>
                    <input type="date" id="orderDateFromFilter" class="form-control">
                </label>

                <label class="admin-orders-field">
                    <span>Đến ngày</span>
                    <input type="date" id="orderDateToFilter" class="form-control">
                </label>

                <label class="admin-orders-field">
                    <span>Sắp xếp</span>
                    <select id="orderSortByFilter" class="form-select"></select>
                </label>

                <label class="admin-orders-field">
                    <span>Thứ tự</span>
                    <select id="orderSortDirFilter" class="form-select">
                        <option value="desc">Giảm dần</option>
                        <option value="asc">Tăng dần</option>
                    </select>
                </label>
            </div>

            <div class="admin-orders-view-tabs mt-3" id="orderQuickViews">
                <button type="button" class="admin-orders-view-tab is-active" data-view="all">Tất cả</button>
                <button type="button" class="admin-orders-view-tab" data-view="overdue">Đơn quá hạn</button>
                <button type="button" class="admin-orders-view-tab" data-view="unpaid">Chờ thanh toán</button>
                <button type="button" class="admin-orders-view-tab" data-view="complaint">Có khiếu nại</button>
                <button type="button" class="admin-orders-view-tab" data-view="contact_issue">Không liên lạc được</button>
                <button type="button" class="admin-orders-view-tab" data-view="unassigned">Chưa gán thợ</button>
            </div>
        </div>
    </section>

    <section class="admin-orders-bulkbar" id="bulkActionBar" hidden>
        <div>
            <strong id="bulkSelectedCount">0</strong> đơn đang được chọn
        </div>
        <div class="admin-orders-bulkbar-actions">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnBulkAssignWorker">Gán/đổi thợ</button>
            <button type="button" class="btn btn-outline-warning btn-sm" id="btnBulkChangeStatus">Đổi trạng thái</button>
            <button type="button" class="btn btn-outline-success btn-sm" id="btnBulkExportSelected">Export đã chọn</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnClearSelection">Bỏ chọn</button>
        </div>
    </section>

    <section class="admin-orders-table card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 44px;">
                            <input type="checkbox" id="selectAllBookings">
                        </th>
                        <th>Đơn & SLA</th>
                        <th>Khách hàng</th>
                        <th>Dịch vụ</th>
                        <th>Thợ & Lịch hẹn</th>
                        <th>Chi phí</th>
                        <th>Thanh toán & Cờ cảnh báo</th>
                        <th>Mốc thời gian</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="bookingTableBody">
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">Đang tải danh sách đơn...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="admin-orders-pagination" id="orderPagination"></div>
    </section>
</div>

<div class="admin-orders-drawer-overlay" id="bookingDetailOverlay" hidden></div>
<aside class="admin-orders-drawer" id="bookingDetailDrawer" aria-hidden="true">
    <header class="admin-orders-drawer-head">
        <div>
            <p class="mb-1 text-muted small">Chi tiết đơn</p>
            <h2 class="mb-0" id="detailDrawerTitle">--</h2>
        </div>
        <button type="button" class="btn btn-light" id="btnCloseBookingDrawer">
            <span class="material-symbols-outlined">close</span>
        </button>
    </header>

    <div class="admin-orders-drawer-body">
        <section class="admin-orders-detail-summary" id="detailSummaryCards"></section>

        <section class="admin-orders-detail-block">
            <h3>Thông tin tổng quan</h3>
            <div class="admin-orders-kv-grid" id="detailInfoBlock"></div>
        </section>

        <section class="admin-orders-detail-block">
            <h3>Gallery trước/sau sửa</h3>
            <div id="detailMediaGallery" class="admin-orders-media-grid"></div>
        </section>

        <section class="admin-orders-detail-block">
            <h3>Timeline xử lý</h3>
            <div id="detailTimeline" class="admin-orders-timeline"></div>
        </section>

        <section class="admin-orders-detail-block">
            <h3>Lịch sử thao tác</h3>
            <div id="detailHistory" class="admin-orders-history-list"></div>
        </section>

        <section class="admin-orders-detail-block">
            <h3>Khiếu nại</h3>
            <div id="detailComplaint"></div>
            <a class="btn btn-outline-danger btn-sm mt-2" id="detailComplaintLink" href="/admin/customer-feedback">Mở trang xử lý khiếu nại</a>
        </section>

        <section class="admin-orders-detail-block">
            <h3>Lịch sử thanh toán</h3>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Số tiền</th>
                            <th>Phương thức</th>
                            <th>Trạng thái</th>
                            <th>Mã giao dịch</th>
                        </tr>
                    </thead>
                    <tbody id="detailPaymentsBody">
                        <tr>
                            <td colspan="5" class="text-muted py-3">Chưa có giao dịch</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-orders-detail-block">
            <h3>Hành động nhanh</h3>

            <div class="admin-orders-form-grid">
                <div class="admin-orders-form-card">
                    <h4>Cập nhật trạng thái</h4>
                    <select id="detailStatusSelect" class="form-select mb-2"></select>
                    <select id="detailCancelReasonSelect" class="form-select mb-2">
                        <option value="">Lý do hủy (bắt buộc khi hủy)</option>
                    </select>
                    <textarea id="detailCancelNoteInput" class="form-control mb-2" rows="2" placeholder="Ghi chú hủy (tùy chọn)"></textarea>
                    <button type="button" class="btn btn-warning w-100" id="btnUpdateBookingStatus">Cập nhật trạng thái</button>
                </div>

                <div class="admin-orders-form-card">
                    <h4>Gán/đổi thợ</h4>
                    <select id="detailWorkerSelect" class="form-select mb-2">
                        <option value="">Chọn thợ</option>
                    </select>
                    <button type="button" class="btn btn-primary w-100" id="btnAssignWorker">Cập nhật thợ</button>
                </div>

                <div class="admin-orders-form-card">
                    <h4>Đổi lịch hẹn</h4>
                    <input type="date" id="detailRescheduleDate" class="form-control mb-2">
                    <select id="detailRescheduleSlot" class="form-select mb-2">
                        <option value="">Chọn khung giờ</option>
                    </select>
                    <button type="button" class="btn btn-outline-primary w-100" id="btnRescheduleBooking">Cập nhật lịch</button>
                </div>

                <div class="admin-orders-form-card">
                    <h4>Cập nhật chi phí</h4>
                    <div class="admin-orders-cost-grid">
                        <label>
                            <span>Tiền công</span>
                            <input type="number" min="0" step="1000" id="detailLaborCost" class="form-control">
                        </label>
                        <label>
                            <span>Linh kien</span>
                            <input type="number" min="0" step="1000" id="detailPartCost" class="form-control">
                        </label>
                        <label>
                            <span>Phí đi lại</span>
                            <input type="number" min="0" step="1000" id="detailTravelCost" class="form-control">
                        </label>
                        <label>
                            <span>Phí vận chuyển</span>
                            <input type="number" min="0" step="1000" id="detailTransportCost" class="form-control">
                        </label>
                    </div>
                    <textarea id="detailPartNote" class="form-control mt-2 mb-2" rows="2" placeholder="Ghi chú linh kiện"></textarea>
                    <button type="button" class="btn btn-outline-success w-100" id="btnUpdateBookingCost">Cập nhật chi phí</button>
                </div>

                <div class="admin-orders-form-card">
                    <h4>Thanh toan</h4>
                    <select id="detailPaymentMethodSelect" class="form-select mb-2">
                        <option value="cod">Tiền mặt (COD)</option>
                        <option value="transfer">Chuyển khoản</option>
                    </select>
                    <button type="button" class="btn btn-outline-dark w-100 mb-2" id="btnUpdatePaymentMethod">Cập nhật phương thức</button>
                    <button type="button" class="btn btn-success w-100" id="btnConfirmCashPayment">Xác nhận đã thu tiền mặt</button>
                </div>
            </div>
        </section>
    </div>
</aside>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/bookings.js') }}"></script>
@endpush
