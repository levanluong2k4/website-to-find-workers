
@extends('layouts.app')
@section('title', 'Báo cáo thu nhập - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts/dist/apexcharts.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/worker/revenue.css') }}"/>
@endpush

@section('content')
<div class="worker-revenue-shell">
  <x-worker-sidebar />

  <main class="worker-revenue">
    <header class="worker-revenue__header">
      <div>
        <div class="worker-revenue__eyebrow">
          <span class="material-symbols-outlined">account_balance_wallet</span>
          <span>Báo cáo lương thợ</span>
        </div>
        <h1>Thu nhập thực nhận</h1>
        <p>Báo cáo lương chi tiết: Tiền công tạo ra, khoản bị khấu trừ và thực nhận cộng vào ví của bạn.</p>
      </div>

      <div class="worker-revenue__header-actions">
        <a href="/worker/profile" class="revenue-button revenue-button--ghost">
          <span class="material-symbols-outlined">account_balance</span>
          <span>Ví của tôi</span>
        </a>
        <button type="button" id="exportRevenueCsvButton" class="revenue-button revenue-button--primary">
          <span class="material-symbols-outlined">download</span>
          <span>Xuất bảng lương</span>
        </button>
      </div>
    </header>

    <div class="worker-revenue__body">
      <section class="revenue-controls revenue-surface" style="margin-bottom: 24px;">
        <div class="period-tabs" id="periodTabs">
          <button type="button" class="period-tab" data-range="today">Hôm nay</button>
          <button type="button" class="period-tab" data-range="7d">7 ngày</button>
          <button type="button" class="period-tab is-active" data-range="30d">30 ngày</button>
          <button type="button" class="period-tab" data-range="month">Tháng này</button>
          <button type="button" class="period-tab" data-range="prev-month">Tháng trước</button>
          <button type="button" class="period-tab" data-range="all">Toàn bộ</button>
          <button type="button" class="period-tab" data-range="custom">Tùy chọn</button>
        </div>

        <div class="custom-range" id="customRange">
          <input type="date" id="customStartDate" class="revenue-field" />
          <input type="date" id="customEndDate" class="revenue-field" />
          <button type="button" id="applyCustomRangeButton" class="revenue-button revenue-button--ghost">
            <span class="material-symbols-outlined">check</span>
            <span>Áp dụng</span>
          </button>
        </div>
      </section>

      <!-- 1. Khối tổng quan thu nhập -->
      <section class="revenue-overview">
        <article class="revenue-hero revenue-surface">
          <div class="revenue-pill revenue-pill--light" id="periodSummaryPill">
            <span class="material-symbols-outlined">date_range</span>
            <span>30 ngày gần đây</span>
          </div>
          <p class="revenue-hero__label">Thực nhận trong kỳ (Cộng ví)</p>
          <h2 class="revenue-hero__amount" id="heroCollectedAmount">0 đ</h2>
          <p class="revenue-hero__insight" id="heroInsight">Đang tải dữ liệu thu nhập...</p>

          <div class="revenue-hero__stats">
            <div class="revenue-hero__stat">
              <span>Chờ cộng ví</span>
              <strong id="heroPendingAmount" style="color: #f59e0b;">0 đ</strong>
            </div>
            <div class="revenue-hero__stat">
              <span>Tổng tiền công gộp</span>
              <strong id="heroGrossAmount">0 đ</strong>
            </div>
            <div class="revenue-hero__stat">
              <span>Đã rút trong kỳ</span>
              <strong id="heroCancelledAmount" style="color: #10b981;">0 đ</strong>
            </div>
          </div>
        </article>

        <!-- 2. Khối khấu trừ -->
        <aside class="revenue-summary revenue-surface">
          <div class="revenue-summary__head">
            <div class="revenue-pill revenue-pill--soft">
              <span class="material-symbols-outlined">calculate</span>
              <span>Chi tiết khấu trừ</span>
            </div>
            <h2>Tại sao tiền vào ví ít hơn?</h2>
            <p>Tiền công gộp - Thuế - Phí nền tảng = Tiền thực nhận</p>
          </div>

          <div class="revenue-summary__metrics">
            <article class="summary-metric">
              <div class="summary-metric__copy">
                <span>Tổng thuế đã trừ</span>
                <strong>Thuế nhà nước (Tạm thu)</strong>
              </div>
              <div class="summary-metric__value" id="summaryTotalTax" style="color: #ef4444;">0 đ</div>
            </article>

            <article class="summary-metric">
              <div class="summary-metric__copy">
                <span>Phí nền tảng</span>
                <strong>Phí duy trì ứng dụng</strong>
              </div>
              <div class="summary-metric__value" id="summaryTotalFee" style="color: #ef4444;">0 đ</div>
            </article>

            <article class="summary-metric">
              <div class="summary-metric__copy">
                <span>Tỷ lệ thực nhận</span>
                <strong>Thực nhận / Tiền công gộp</strong>
              </div>
              <div class="summary-metric__value" id="summaryNetRatio" style="color: #10b981;">0%</div>
            </article>

            <article class="summary-metric">
              <div class="summary-metric__copy">
                <span>Số dư ví hiện tại</span>
                <strong>Sẵn sàng để rút</strong>
              </div>
              <div class="summary-metric__value" id="summaryWalletBalance" style="color: #3b82f6;">0 đ</div>
            </article>
          </div>
        </aside>
      </section>


      <!-- 3. Biểu đồ dòng tiền -->
      <section class="revenue-grid">
        <article class="revenue-panel revenue-surface">
          <div class="revenue-panel__head">
            <div>
              <h2>Biểu đồ thu nhập thực nhận</h2>
              <p>Tiền công bạn làm ra so với thực nhận sau khi trừ phí.</p>
            </div>
            <div class="revenue-panel__meta">
              <span><span class="material-symbols-outlined" style="color: #3b82f6">payments</span> Công gộp</span>
              <span><span class="material-symbols-outlined" style="color: #10b981">wallet</span> Thực nhận</span>
              <span><span class="material-symbols-outlined" style="color: #ef4444">money_off</span> Thuế & Phí</span>
            </div>
          </div>

          <div id="revenueTrendChart"></div>
          <div id="revenueTrendEmpty" class="chart-empty">Không có đơn phát sinh chi phí trong khoảng thời gian này.</div>
        </article>

        <article class="revenue-panel revenue-surface">
          <div class="revenue-panel__head">
            <div>
              <h2>Trạng thái thu nhập</h2>
              <p>Tiền của bạn đang ở đâu?</p>
            </div>
            <div class="revenue-panel__meta">
              <span id="breakdownBookingCount">0 đơn có chi phí</span>
            </div>
          </div>

          <div class="revenue-kicker-list">
            <div class="revenue-kicker">
              <span>Thu nhập trung bình/đơn</span>
              <strong id="kickerAverageNet">0 đ</strong>
            </div>
            <div class="revenue-kicker">
              <span>Tổng đơn trong kỳ</span>
              <strong id="kickerBookingCount">0 đơn</strong>
            </div>
          </div>

          <div class="split-list" style="margin-top: 1.5rem;">
            <div class="split-card" style="width: 100%;">
              <div class="split-card__title">
                <span class="material-symbols-outlined">monetization_on</span>
                <span>Phân bổ dòng tiền công</span>
              </div>
              <div class="split-card__rows" id="incomeSplitRows"></div>
            </div>
          </div>
        </article>
      </section>

      <!-- 4. Bảng chi tiết đơn -->
      <section class="revenue-table-panel revenue-surface">
        <div class="revenue-table-toolbar">
          <div class="revenue-panel__head revenue-panel__head--compact">
            <div>
              <h2>Bảng lương theo đơn</h2>
              <p>Chi tiết tiền công gộp, khấu trừ và thực nhận cho từng đơn hàng.</p>
            </div>
          </div>
        </div>

        <div class="revenue-table-filters">
          <input type="search" id="revenueSearchInput" class="revenue-field revenue-field--search" placeholder="Tìm theo mã đơn, khách hàng" />
          <select id="statusFilter" class="revenue-field">
            <option value="all">Tất cả trạng thái</option>
            <option value="paid">Đã cộng ví</option>
            <option value="pending">Chờ cộng ví</option>
            <option value="cancelled">Đã hủy</option>
          </select>
        </div>

        <div class="revenue-table-wrap">
          <table class="revenue-table">
            <thead>
              <tr>
                <th>Mã đơn</th>
                <th>Hoàn thành</th>
                <th>Dịch vụ</th>
                <th>Khách hàng</th>
                <th>Công gộp</th>
                <th>Thuế</th>
                <th>Phí nền tảng</th>
                <th>Thực nhận</th>
                <th>Trạng thái</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="revenueTableBody">
              <tr>
                <td colspan="10">
                  <div class="revenue-state">
                    <strong>Đang tải dữ liệu</strong>
                    Vui lòng chờ trong giây lát...
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </main>
</div>

<!-- 5. Drawer -->
<div id="revenueDrawerOverlay" class="revenue-drawer-overlay"></div>
<aside id="revenueDrawer" class="revenue-drawer" aria-hidden="true">
  <div class="revenue-drawer__head">
    <div>
      <h3 id="drawerTitle">Phiếu lương đơn hàng</h3>
      <p id="drawerSubtitle">Chi tiết các khoản khấu trừ và thực nhận</p>
    </div>
    <button type="button" id="revenueDrawerClose" class="revenue-drawer__close" aria-label="Đóng">
      <span class="material-symbols-outlined">close</span>
    </button>
  </div>

  <div id="revenueDrawerBody" class="revenue-drawer__body"></div>
</aside>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script type="module" src="{{ asset('assets/js/worker/analytics.js') }}"></script>
@endpush
