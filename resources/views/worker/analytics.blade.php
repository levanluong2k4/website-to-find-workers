@extends('layouts.app')
@section('title', 'Doanh thu - Thợ Tốt NTU')

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
          <span class="material-symbols-outlined">monitoring</span>
          <span>Worker Revenue Workspace</span>
        </div>
        <h1>Doanh thu</h1>
        <p>Trang này tập trung vào các câu hỏi người thợ cần xem hằng ngày: đã thu được bao nhiêu, khoản nào còn chờ xác nhận và loại việc nào đang tạo tiền tốt nhất.</p>
      </div>

      <div class="worker-revenue__header-actions">
        <a href="/worker/my-bookings" class="revenue-button revenue-button--ghost">
          <span class="material-symbols-outlined">calendar_month</span>
          <span>Đơn của tôi</span>
        </a>
        <button type="button" id="exportRevenueCsvButton" class="revenue-button revenue-button--primary">
          <span class="material-symbols-outlined">download</span>
          <span>Xuất CSV</span>
        </button>
      </div>
    </header>

    <div class="worker-revenue__body">
      <section class="revenue-overview">
        <article class="revenue-hero revenue-surface">
          <div class="revenue-pill revenue-pill--light" id="periodSummaryPill">
            <span class="material-symbols-outlined">date_range</span>
            <span>30 ngày gần đây</span>
          </div>
          <p class="revenue-hero__label">Đã thu trong kỳ</p>
          <h2 class="revenue-hero__amount" id="heroCollectedAmount">0 đ</h2>
          <p class="revenue-hero__insight" id="heroInsight">Đang tải dữ liệu doanh thu từ các đơn đã phát sinh chi phí.</p>

          <div class="revenue-hero__stats">
            <div class="revenue-hero__stat">
              <span>Đang chờ thu</span>
              <strong id="heroPendingAmount">0 đ</strong>
            </div>
            <div class="revenue-hero__stat">
              <span>Giá trị công việc</span>
              <strong id="heroGrossAmount">0 đ</strong>
            </div>
            <div class="revenue-hero__stat">
              <span>Mất vì hủy</span>
              <strong id="heroCancelledAmount">0 đ</strong>
            </div>
          </div>
        </article>

        <aside class="revenue-summary revenue-surface">
          <div class="revenue-summary__head">
            <div class="revenue-pill revenue-pill--soft">
              <span class="material-symbols-outlined">insights</span>
              <span>Tóm tắt điều hành</span>
            </div>
            <h2>Chỉ số cần nhìn trước</h2>
            <p>Những chỉ số này cho biết bạn đang giữ tiền tốt hay chỉ mới tạo việc mà chưa chốt được thu nhập.</p>
          </div>

          <div class="revenue-summary__metrics">
            <article class="summary-metric">
              <div class="summary-metric__copy">
                <span>Tỷ lệ thu tiền</span>
                <strong>Tiền đã thu trên tổng giá trị công việc</strong>
              </div>
              <div class="summary-metric__value" id="summaryCollectionRate">0%</div>
            </article>

            <article class="summary-metric">
              <div class="summary-metric__copy">
                <span>Giá trị trung bình / đơn</span>
                <strong>Giá trị các đơn có phát sinh chi phí</strong>
              </div>
              <div class="summary-metric__value" id="summaryAverageTicket">0 đ</div>
            </article>

            <article class="summary-metric">
              <div class="summary-metric__copy">
                <span>Tỷ lệ hoàn tất</span>
                <strong>So với các đơn đã kết thúc trong kỳ</strong>
              </div>
              <div class="summary-metric__value" id="summaryCompletionRate">0%</div>
            </article>

            <article class="summary-metric">
              <div class="summary-metric__copy">
                <span>Dịch vụ mang tiền tốt nhất</span>
                <strong id="summaryTopServiceLabel">Chưa có dữ liệu</strong>
              </div>
              <div class="summary-metric__value" id="summaryTopServiceAmount">0 đ</div>
            </article>
          </div>
        </aside>
      </section>

      <section class="revenue-controls revenue-surface">
        <div class="period-tabs" id="periodTabs">
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

      <section class="revenue-grid">
        <article class="revenue-panel revenue-surface">
          <div class="revenue-panel__head">
            <div>
              <h2>Dòng tiền theo thời gian</h2>
              <p>Biểu đồ tách khoản đã thu và khoản còn chờ để người thợ biết tiền đang thực sự về ở đâu.</p>
            </div>
            <div class="revenue-panel__meta">
              <span><span class="material-symbols-outlined">payments</span> Đã thu</span>
              <span><span class="material-symbols-outlined">schedule</span> Chờ thanh toán</span>
            </div>
          </div>

          <div id="revenueTrendChart"></div>
          <div id="revenueTrendEmpty" class="chart-empty">Không có đơn phát sinh chi phí trong khoảng thời gian này.</div>
        </article>

        <article class="revenue-panel revenue-surface">
          <div class="revenue-panel__head">
            <div>
              <h2>Cơ cấu doanh thu</h2>
              <p>Nhìn nhanh phần nào đang mang tiền về: dịch vụ, hình thức sửa và cách khách đang thanh toán.</p>
            </div>
            <div class="revenue-panel__meta">
              <span id="breakdownBookingCount">0 đơn có chi phí</span>
            </div>
          </div>

          <div class="revenue-kicker-list">
            <div class="revenue-kicker">
              <span>Đánh giá trung bình</span>
              <strong id="kickerAverageRating">Chưa có</strong>
            </div>
            <div class="revenue-kicker">
              <span>Tổng đơn trong kỳ</span>
              <strong id="kickerBookingCount">0 đơn</strong>
            </div>
          </div>

          <div class="source-list" id="sourceList"></div>

          <div class="split-list">
            <div class="split-card">
              <div class="split-card__title">
                <span class="material-symbols-outlined">home_repair_service</span>
                <span>Hình thức sửa</span>
              </div>
              <div class="split-card__rows" id="modeSplitRows"></div>
            </div>

            <div class="split-card">
              <div class="split-card__title">
                <span class="material-symbols-outlined">account_balance_wallet</span>
                <span>Phương thức thanh toán</span>
              </div>
              <div class="split-card__rows" id="paymentSplitRows"></div>
            </div>
          </div>
        </article>
      </section>

      <section class="revenue-grid revenue-grid--equal">
        <article class="revenue-panel revenue-surface">
          <div class="revenue-panel__head">
            <div>
              <h2>Đơn cần chốt tiền</h2>
              <p>Các đơn đã xong việc hoặc đã báo hoàn thành nhưng tiền vẫn chưa xác nhận thành công.</p>
            </div>
            <div class="revenue-panel__meta">
              <span id="pendingCountBadge">0 đơn</span>
            </div>
          </div>

          <div class="queue-list" id="pendingQueue"></div>
        </article>

        <article class="revenue-panel revenue-surface">
          <div class="revenue-panel__head">
            <div>
              <h2>Đơn bị hủy và khoản hụt</h2>
              <p>Giữ góc nhìn thực tế về thất thoát để biết khi nào tỷ lệ hủy bắt đầu ảnh hưởng đến thu nhập.</p>
            </div>
            <div class="revenue-panel__meta">
              <span id="cancelledCountBadge">0 đơn</span>
            </div>
          </div>

          <div class="queue-list" id="cancelledQueue"></div>
        </article>
      </section>

      <section class="revenue-table-panel revenue-surface">
        <div class="revenue-table-toolbar">
          <div class="revenue-panel__head revenue-panel__head--compact">
            <div>
              <h2>Lịch sử đơn tác động đến doanh thu</h2>
              <p>Danh sách này gom cả đơn đã thu, đang chờ thanh toán và đơn bị hủy có giá trị phát sinh để tiện đối soát.</p>
            </div>
          </div>
        </div>

        <div class="revenue-table-filters">
          <input type="search" id="revenueSearchInput" class="revenue-field revenue-field--search" placeholder="Tìm theo mã đơn, khách hàng hoặc dịch vụ" />
          <select id="statusFilter" class="revenue-field">
            <option value="all">Tất cả trạng thái</option>
            <option value="paid">Đã thu</option>
            <option value="pending">Chờ thanh toán</option>
            <option value="cancelled">Đã hủy</option>
          </select>
          <select id="paymentFilter" class="revenue-field">
            <option value="all">Tất cả thanh toán</option>
            <option value="cod">Tiền mặt</option>
            <option value="transfer">Chuyển khoản</option>
            <option value="paid">Đã thanh toán</option>
            <option value="unpaid">Chưa thanh toán</option>
          </select>
          <select id="modeFilter" class="revenue-field">
            <option value="all">Tất cả hình thức sửa</option>
            <option value="at_home">Sửa tại nhà</option>
            <option value="at_store">Sửa tại cửa hàng</option>
          </select>
        </div>

        <div class="revenue-table-wrap">
          <table class="revenue-table">
            <thead>
              <tr>
                <th>Mã đơn</th>
                <th>Thời điểm</th>
                <th>Khách hàng</th>
                <th>Dịch vụ</th>
                <th>Hình thức</th>
                <th>Thanh toán</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Đánh giá</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="revenueTableBody">
              <tr>
                <td colspan="10">
                  <div class="revenue-state">
                    <strong>Đang tải dữ liệu</strong>
                    Vui lòng chờ trong giây lát để hệ thống tổng hợp doanh thu từ các đơn của bạn.
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

<div id="revenueDrawerOverlay" class="revenue-drawer-overlay"></div>
<aside id="revenueDrawer" class="revenue-drawer" aria-hidden="true">
  <div class="revenue-drawer__head">
    <div>
      <h3 id="drawerTitle">Chi tiết đơn</h3>
      <p id="drawerSubtitle">Theo dõi breakdown chi phí và trạng thái thu tiền.</p>
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
