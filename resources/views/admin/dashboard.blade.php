@extends('layouts.app')

@section('title', 'Dashboard admin - Thợ Tốt')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
    /* Lumina Core Dashboard Design - Modern Glassmorphism & Tonal Layering */
    :root {
        --lumina-surface: #f8f9ff;
        --lumina-on-surface: #0b1c30;
        --lumina-primary: #0058be;
        --lumina-primary-container: #d8e2ff;
        --lumina-on-primary-container: #001a41;
        --lumina-secondary-container: #e0e2ec;
        --lumina-outline-variant: rgba(194, 198, 214, 0.2);
        
        --shadow-sm: 0 2px 8px rgba(11, 28, 48, 0.04);
        --shadow-md: 0 12px 32px rgba(11, 28, 48, 0.08);
        --shadow-lg: 0 24px 64px rgba(11, 28, 48, 0.12);
        
        --tone-positive: #006c49;
        --tone-positive-bg: #e6f6ef;
        --tone-danger: #ba1a1a;
        --tone-danger-bg: #ffdad6;
        --tone-warning: #765a00;
        --tone-warning-bg: #ffe08d;
    }

    body.app-admin-shell {
        background-color: var(--lumina-surface) !important;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .adm-main {
        padding: 0;
        animation: fadeIn 0.6s cubic-bezier(0.22, 1, 0.36, 1);
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .adm-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 2.5rem;
        gap: 1.5rem;
    }

    .adm-title h1 {
        font-family: 'Manrope', sans-serif;
        font-weight: 800;
        font-size: 2.25rem;
        letter-spacing: -0.04em;
        color: var(--lumina-on-surface);
        margin: 0;
    }

    .adm-title p {
        color: #64748b;
        font-size: 1rem;
        margin: 0.5rem 0 0;
    }

    .adm-tools {
        display: flex;
        gap: 0.75rem;
    }

    .adm-tabs {
        display: flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 12px;
        gap: 4px;
    }

    .js-period-btn {
        padding: 8px 16px;
        border: none;
        background: transparent;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.8125rem;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .js-period-btn.is-active {
        background: #fff;
        color: var(--lumina-primary);
        box-shadow: var(--shadow-sm);
    }

    .adm-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 12px;
        border: 1px solid var(--lumina-outline-variant);
        background: #fff;
        font-weight: 700;
        font-size: 0.8125rem;
        color: var(--lumina-on-surface);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .adm-kpis {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .adm-kpi {
        background: #ffffff;
        border: 1px solid var(--lumina-outline-variant);
        border-radius: 1.5rem;
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
    }

    .adm-kpi-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .adm-kpi-ic {
        width: 3rem;
        height: 3rem;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        background: var(--lumina-primary-container);
        color: var(--lumina-primary);
    }

    .adm-kpi-2 .adm-kpi-ic { background: #e0f2fe; color: #0369a1; }
    .adm-kpi-3 .adm-kpi-ic { background: #f0fdf4; color: #15803d; }
    .adm-kpi-4 .adm-kpi-ic { background: #fef2f2; color: #b91c1c; }

    .adm-pill {
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .tone-positive { background: var(--tone-positive-bg); color: var(--tone-positive); }
    .tone-danger { background: var(--tone-danger-bg); color: var(--tone-danger); }
    .tone-warning { background: var(--tone-warning-bg); color: var(--tone-warning); }
    .tone-muted { background: #f1f5f9; color: #475569; }

    .adm-kpi strong {
        display: block;
        font-size: 1.75rem;
        font-weight: 800;
        font-family: 'Manrope';
        letter-spacing: -0.02em;
        color: var(--lumina-on-surface);
    }

    .adm-kpi small {
        color: #64748b;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .adm-board {
        display: grid;
        grid-template-columns: minmax(0, 1.72fr) minmax(270px, 0.78fr);
        gap: 22px;
        align-items: start;
    }

    .adm-stack { display: grid; gap: 22px; }

    .adm-card {
        background: #ffffff;
        border: 1px solid var(--lumina-outline-variant);
        border-radius: 1.5rem;
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
    }

    .adm-card-h {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }

    .adm-card-h h2 {
        font-size: 1.25rem;
        font-weight: 700;
        margin: 0;
        color: var(--lumina-on-surface);
    }

    .adm-card-h p {
        font-size: 0.875rem;
        color: #64748b;
        margin: 0.25rem 0 0;
    }

    .adm-map-stage {
        position: relative;
        background: #f1f5f9;
        border-radius: 1rem;
        height: 480px;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .adm-map-canvas {
        width: 100%;
        height: 100%;
        z-index: 1;
    }

    .adm-table-wrap { padding-top: 1rem; overflow-x: auto; }
    .adm-table { width: 100%; border-collapse: collapse; }
    .adm-table th {
        padding: 1rem;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        border-bottom: 1px solid var(--lumina-outline-variant);
    }
    .adm-table td {
        padding: 1rem;
        border-bottom: 1px solid rgba(0,0,0,0.02);
        font-size: 0.875rem;
    }

    .adm-side-card { padding-bottom: 1.5rem; }
    .adm-side-grid, .adm-split {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        padding-top: 1rem;
    }

    .adm-s {
        padding: 1rem;
        border-radius: 1rem;
        background: #f8fafc;
        text-align: center;
    }

    .adm-s strong {
        display: block;
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--lumina-on-surface);
    }

    .adm-s span {
        font-size: 0.625rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
    }

    .adm-side-label { padding: 1.5rem 0 0.5rem; font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; }

    .adm-queue-item {
        padding: 1rem;
        border-radius: 1rem;
        background: #f8fafc;
        border-left: 4px solid var(--lumina-primary);
        margin-bottom: 0.75rem;
    }

    .adm-workers { display: flex; align-items: center; justify-content: space-between; padding-top: 1rem; }
    .adm-avatars { display: flex; }
    .adm-avatars span {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: 2px solid #fff;
        margin-left: -8px;
        background: #000;
        color: #fff;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .adm-fab {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 50%;
        background: var(--lumina-primary);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: var(--shadow-lg);
        border: none;
        cursor: pointer;
    }

</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container-fluid py-4" style="max-width: 1600px; margin: 0 auto;">
    <main class="adm-main" id="adminDashboard">
        <section class="adm-head">
            <div class="adm-title">
                <div>
                    <h1 class="adm-page-title">Bảng điều hành admin</h1>
                    <p>Theo dõi hoạt động vận hành hệ thống Lumina theo thời gian thực.</p>
                </div>
            </div>
            <div class="adm-tools">
                <div class="adm-tabs" role="group" aria-label="Chọn khoảng thời gian">
                    <button type="button" class="js-period-btn" data-period="day">Ngày</button>
                    <button type="button" class="js-period-btn is-active" data-period="month">Tháng</button>
                    <button type="button" class="js-period-btn" data-period="year">Năm</button>
                </div>
                <button type="button" class="adm-btn" id="btnRefresh"><i class="fa-solid fa-rotate"></i>Đồng bộ</button>
            </div>
        </section>

            <section class="adm-kpis">
                <article class="adm-kpi adm-kpi-1"><div class="adm-kpi-top"><div class="adm-kpi-ic"><i class="fa-solid fa-money-bill-wave"></i></div><span class="adm-pill tone-positive" id="summaryRevenueNote">+0%</span></div><small>Doanh thu hôm nay</small><strong id="summaryRevenueToday">0 đ</strong></article>
                <article class="adm-kpi adm-kpi-2"><div class="adm-kpi-top"><div class="adm-kpi-ic"><i class="fa-regular fa-calendar"></i></div><span class="adm-pill tone-muted" id="summaryBookingsNote">Stable</span></div><small>Đơn đặt lịch hôm nay</small><strong id="summaryBookingsToday">0</strong></article>
                <article class="adm-kpi adm-kpi-3"><div class="adm-kpi-top"><div class="adm-kpi-ic"><i class="fa-solid fa-building-columns"></i></div><span class="adm-pill tone-positive" id="summaryCommissionNote">+0%</span></div><small>Hoa hồng hệ thống</small><strong id="summaryCommission">0 đ</strong></article>
                <article class="adm-kpi adm-kpi-4"><div class="adm-kpi-top"><div class="adm-kpi-ic"><i class="fa-regular fa-flag"></i></div><span class="adm-pill tone-danger" id="summaryComplaintsNote">0</span></div><small>Khiếu nại mới</small><strong id="summaryComplaints">0</strong></article>
            </section>

            <section class="adm-board">
                <div class="adm-stack">
                    <article class="adm-card adm-map-card">
                        <div class="adm-card-h">
                            <div>
                                <h2>Bản đồ theo dõi đội thợ</h2>
                                <p>Avatar thợ hiển thị trực tiếp trên bản đồ. Hover vào từng điểm để xem trạng thái đang sửa, đang có lịch hay trống lịch.</p>
                            </div>
                            <span class="adm-meta" id="workerMapMeta">Cập nhật mới nhất</span>
                        </div>
                        <div class="adm-map-summary">
                            <div class="adm-map-summary__item"><span>Thợ có GPS</span><strong id="workerMapTrackedCount">0</strong></div>
                            <div class="adm-map-summary__item"><span>Đang sửa</span><strong id="workerMapRepairingCount">0</strong></div>
                            <div class="adm-map-summary__item"><span>Đang có lịch</span><strong id="workerMapScheduledCount">0</strong></div>
                            <div class="adm-map-summary__item"><span>Trống lịch</span><strong id="workerMapAvailableCount">0</strong></div>
                        </div>
                        <div class="adm-map-stage">
                            <div id="workerTrackingMap" class="adm-map-canvas" aria-label="Bản đồ theo dõi vị trí thợ"></div>
                            <div id="workerMapStatus" class="adm-map-status">Đang tải dữ liệu vị trí đội thợ...</div>
                            <div class="adm-map-legend">
                                <span class="adm-map-legend__chip"><span class="adm-map-legend__dot adm-map-legend__dot--busy"></span>Đang sửa</span>
                                <span class="adm-map-legend__chip"><span class="adm-map-legend__dot adm-map-legend__dot--scheduled"></span>Đang có lịch</span>
                                <span class="adm-map-legend__chip"><span class="adm-map-legend__dot adm-map-legend__dot--free"></span>Trống lịch</span>
                                <span class="adm-map-legend__chip"><span class="adm-map-legend__dot adm-map-legend__dot--offline"></span>Tạm nghỉ</span>
                            </div>
                            <div id="workerMapEmptyState" class="adm-map-empty">
                                <i class="fa-solid fa-location-crosshairs"></i>
                                <strong>Chưa có dữ liệu vị trí thợ</strong>
                                <p>Bản đồ sẽ hiển thị khi thợ có tọa độ hợp lệ trong hồ sơ và đã được phê duyệt hoạt động.</p>
                            </div>
                            <div id="workerMapInfoCard" class="adm-map-info">
                                <div class="adm-map-info__top">
                                    <div>
                                        <span class="adm-map-info__eyebrow">Theo dõi lúc này</span>
                                        <h3 id="workerMapInfoName">Di chuột vào avatar thợ</h3>
                                    </div>
                                    <span id="workerMapInfoStatus" class="adm-map-info__status adm-map-info__status--free">Trống lịch</span>
                                </div>
                                <p id="workerMapInfoDetail" class="adm-map-info__detail">Hover vào avatar trên bản đồ để xem nhanh tình trạng của từng thợ.</p>
                                <div class="adm-map-info__grid">
                                    <div class="adm-map-info__line"><i class="fa-regular fa-star"></i><span id="workerMapInfoRating">Chưa có đánh giá</span></div>
                                    <div class="adm-map-info__line"><i class="fa-solid fa-screwdriver-wrench"></i><span id="workerMapInfoServices">Chưa có nhóm dịch vụ</span></div>
                                    <div class="adm-map-info__line"><i class="fa-regular fa-calendar-check"></i><span id="workerMapInfoSchedule">Chưa có lịch đang mở</span></div>
                                    <div class="adm-map-info__line"><i class="fa-solid fa-location-dot"></i><span id="workerMapInfoArea">Chưa có khu vực</span></div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="adm-card">
                        <div class="adm-card-h">
                            <div><h2>Khối doanh thu theo xu hướng</h2><p>Thống kê cho <span id="metaPeriodLabel">hôm nay</span></p></div>
                            <div class="adm-rev-num"><strong id="revenuePeriodTotal">0 đ</strong><span class="tone-positive" id="revenuePeriodNote">0%</span><div class="adm-meta" id="metaUpdatedAt">Cập nhật --:--</div></div>
                        </div>
                        <div class="adm-rev">
                            <div class="adm-chart"><svg id="revenueChart" viewBox="0 0 720 268" preserveAspectRatio="none" aria-label="Biểu đồ doanh thu"></svg><div class="adm-chart-labels" id="revenueChartLabels"></div></div>
                            <div class="adm-rev-foot">
                                <div class="adm-mini">
                                    <div><span class="adm-tt">Top services</span><strong id="revenueTopService">Chưa có dữ liệu</strong><div class="adm-line"><span style="width:42%"></span></div></div>
                                    <div><span class="adm-tt">Tỷ trọng chuyển khoản</span><strong id="revenueTransferShare">0% doanh thu</strong><div class="adm-line"><span style="width:28%"></span></div></div>
                                </div>
                                <div class="adm-donut-card">
                                    <div class="adm-donut" id="revenueTransferDonut"><b id="revenueTransferPercent">0%</b></div>
                                    <small><span><i class="fa-solid fa-circle" style="color:var(--blue)"></i> Banking</span><span><i class="fa-solid fa-circle" style="color:#b8c3d6"></i> Tiền mặt</span></small>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="adm-card">
                        <div class="adm-card-h"><h2>Bảng chi tiết doanh thu</h2><a href="/admin/bookings" class="adm-meta" style="text-decoration:none;color:var(--blue);font-weight:700">Xuất báo cáo</a></div>
                        <div class="adm-table-wrap">
                            <table class="adm-table">
                                <thead><tr><th>Mã đơn / Dịch vụ</th><th>Ngày</th><th>Tổng tiền</th><th>Tiền công</th><th>Trạng thái</th></tr></thead>
                                <tbody id="revenueTableBody"><tr><td colspan="5" class="adm-empty">Đang tải dữ liệu doanh thu...</td></tr></tbody>
                            </table>
                        </div>
                    </article>
                </div>

                <aside class="adm-stack">
                    <article class="adm-card adm-side-card">
                        <div class="adm-card-h"><h2>Khối cận xử lý</h2><span class="adm-chip adm-chip-blue">Hôm nay</span></div>
                        <div class="adm-side-grid">
                            <div class="adm-s"><strong id="bookingsTodayTotal">0</strong><span>Tổng đơn</span></div>
                            <div class="adm-s adm-s-warn"><strong id="bookingsPendingTotal">0</strong><span>Chờ xác nhận</span></div>
                            <div class="adm-s adm-s-primary"><strong id="bookingsProgressTotal">0</strong><span>Đang thực hiện</span></div>
                            <div class="adm-s adm-s-ok"><strong id="bookingsCompletedTotal">0</strong><span>Hoàn tất</span></div>
                        </div>
                        <div class="adm-side-label">Operational queue</div>
                        <div class="adm-queue" id="bookingQueueList"><div class="adm-queue-item adm-queue-item--info"><h4>Đang tải hàng đợi vận hành...</h4></div></div>
                    </article>

                    <article class="adm-card adm-side-card">
                        <div class="adm-card-h"><h2>Khối đội thợ</h2></div>
                        <div class="adm-workers"><div class="adm-avatars"><span>A</span><span>D</span><span>M</span><span class="light">+85</span></div><div><strong><span id="workersTotal">0</span> ThV</strong><small><span id="workersActive">0</span> đang online</small></div></div>
                        <div class="adm-split"><div class="adm-s"><strong id="workersPending">0</strong><span>Profile chờ duyệt</span></div><div class="adm-s"><strong id="workersLowRating">0</strong><span>Thợ bị rate thấp</span></div></div>
                        <div class="adm-side-label">Signals</div>
                        <div class="adm-signals" id="workerWatchList"><div>Đang tải tín hiệu từ đội thợ...</div></div>
                    </article>

                    <article class="adm-card adm-side-card">
                        <div class="adm-card-h"><h2>Khối khiếu nại/phản ánh</h2></div>
                        <div class="adm-complaints">
                            <div class="adm-complaint-item adm-complaint-item--danger"><div><strong>Khiếu nại chưa xử lý</strong><p>Mức độ ưu tiên: cao</p></div><span id="complaintsNew">0</span></div>
                            <div class="adm-complaint-item adm-complaint-item--warning"><div><strong>Đánh giá dưới 3 sao</strong><p>Theo dõi trong ngày</p></div><span id="complaintsLowRating">0</span></div>
                            <div class="adm-complaint-item"><div><strong>Đơn hủy có lý do</strong><p>Nguy cơ cần kiểm tra</p></div><span id="complaintsCanceled">0</span></div>
                        </div>
                        <div class="adm-side-label">Feedback mới nhất</div>
                        <div class="adm-feedback" id="complaintList"><div class="adm-feedback-item"><i></i><div><h4>Đang tải phản ánh...</h4><p>Hệ thống đang tổng hợp phản hồi từ đánh giá và đơn đặt lịch.</p></div></div></div>
                    </article>
                </aside>
            </section>
        </main>
</div>

<button class="adm-fab" type="button" title="Tác vụ nhanh"><i class="fa-solid fa-sparkles"></i></button>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script type="module" src="{{ asset('assets/js/admin/dashboard.js') }}"></script>
<script>
    document.addEventListener('dashboardDataLoaded', (event) => {
        const detail = event.detail || {};
        const pendingBadge = document.getElementById('sidebarPendingCount');
        const complaintBadge = document.getElementById('sidebarComplaintCount');
        if (pendingBadge && detail.pendingBookings !== undefined) pendingBadge.textContent = detail.pendingBookings;
        if (complaintBadge && detail.complaints !== undefined) complaintBadge.textContent = detail.complaints;
    });
</script>
@endpush
