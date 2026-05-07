@extends('layouts.app')
@section('title', 'Doanh thu & Lương thợ - Admin')
@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>tailwind.config={prefix:'tw-',darkMode:'class',theme:{extend:{colors:{'primary':'#0058be','on-primary':'#fff','surface':'#f7f9fb','on-background':'#191c1e','surface-container-lowest':'#fff','surface-container-low':'#f2f4f6','surface-container':'#eceef0','outline-variant':'#c2c6d6','error':'#ba1a1a'}}}}</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<style>
  body{font-family:'Inter',sans-serif;}
  .rev-kpi{background:#fff;border-radius:1.5rem;padding:1.5rem;box-shadow:0 1px 4px rgba(0,0,0,.07);}
  .badge{display:inline-flex;align-items:center;padding:.25rem .75rem;border-radius:999px;font-size:.72rem;font-weight:700;}
  .badge-pending{background:#fef3c7;color:#92400e;}
  .badge-success{background:#d1fae5;color:#065f46;}
  .badge-fail{background:#fee2e2;color:#991b1b;}
  .period-btn{padding:.4rem 1rem;border-radius:999px;font-size:.8rem;font-weight:600;cursor:pointer;border:1.5px solid #c2c6d6;color:#424754;background:#fff;transition:all .15s;}
  .period-btn.active{background:#0058be;color:#fff;border-color:#0058be;}
  table{border-collapse:collapse;width:100%;}
  th{background:#f2f4f6;padding:.75rem 1rem;text-align:left;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#727785;}
  td{padding:.75rem 1rem;border-bottom:1px solid #eceef0;font-size:.83rem;color:#191c1e;}
  tr:hover td{background:#f7f9fb;}
  .tab-btn{padding:.5rem 1.25rem;border-radius:999px;font-size:.8rem;font-weight:600;cursor:pointer;border:1.5px solid #c2c6d6;background:#fff;color:#424754;}
  .tab-btn.active{background:#0058be;color:#fff;border-color:#0058be;}
</style>
@endpush

@section('content')
<app-navbar></app-navbar>
<div class="tw-bg-surface tw-min-h-screen tw-p-6 md:tw-p-10 tw-max-w-[1600px] tw-mx-auto">

  {{-- Header --}}
  <div class="tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-4 tw-mb-8">
    <div>
      <h1 class="tw-text-2xl tw-font-extrabold" style="font-family:Manrope">📊 Doanh thu & Lương thợ</h1>
      <p class="tw-text-sm tw-text-slate-500 tw-mt-1">Tổng quan tài chính hệ thống theo từng kỳ</p>
    </div>
    <div class="tw-flex tw-flex-wrap tw-gap-2" id="periodBar">
      <button class="period-btn active" data-period="today">Hôm nay</button>
      <button class="period-btn" data-period="7d">7 ngày</button>
      <button class="period-btn" data-period="30d">30 ngày</button>
      <button class="period-btn" data-period="month">Tháng này</button>
      <button class="period-btn" data-period="prev-month">Tháng trước</button>
      <button class="period-btn" data-period="all">Toàn bộ</button>
    </div>
  </div>

  {{-- KPI Cards --}}
  <div class="tw-grid tw-grid-cols-2 lg:tw-grid-cols-6 tw-gap-4 tw-mb-8" id="kpiRow">
    <div class="rev-kpi tw-col-span-1">
      <p class="tw-text-xs tw-text-slate-400 tw-font-bold tw-uppercase tw-tracking-widest tw-mb-1">Doanh thu gộp</p>
      <p class="tw-text-2xl tw-font-extrabold tw-text-blue-600" id="kpiGop">—</p>
    </div>
    <div class="rev-kpi">
      <p class="tw-text-xs tw-text-slate-400 tw-font-bold tw-uppercase tw-tracking-widest tw-mb-1">Thuế nhà nước</p>
      <p class="tw-text-2xl tw-font-extrabold tw-text-red-500" id="kpiThue">—</p>
    </div>
    <div class="rev-kpi">
      <p class="tw-text-xs tw-text-slate-400 tw-font-bold tw-uppercase tw-tracking-widest tw-mb-1">Phí nền tảng</p>
      <p class="tw-text-2xl tw-font-extrabold tw-text-orange-500" id="kpiPhi">—</p>
    </div>
    <div class="rev-kpi">
      <p class="tw-text-xs tw-text-slate-400 tw-font-bold tw-uppercase tw-tracking-widest tw-mb-1">Lương thợ</p>
      <p class="tw-text-2xl tw-font-extrabold tw-text-green-600" id="kpiLuong">—</p>
    </div>
    <div class="rev-kpi">
      <p class="tw-text-xs tw-text-slate-400 tw-font-bold tw-uppercase tw-tracking-widest tw-mb-1">Đã rút</p>
      <p class="tw-text-2xl tw-font-extrabold tw-text-slate-700" id="kpiRut">—</p>
    </div>
    <div class="rev-kpi">
      <p class="tw-text-xs tw-text-slate-400 tw-font-bold tw-uppercase tw-tracking-widest tw-mb-1">Thợ hoạt động</p>
      <p class="tw-text-2xl tw-font-extrabold tw-text-slate-700" id="kpiTho">—</p>
    </div>
  </div>

  {{-- Wage Config Banner --}}
  <div class="tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-2xl tw-p-4 tw-flex tw-flex-wrap tw-items-center tw-gap-6 tw-mb-8 tw-text-sm" id="wageConfigBanner">
    <span class="material-symbols-outlined tw-text-blue-500">info</span>
    <span>Thuế nhà nước: <strong id="cfgTax" class="tw-text-red-600">—%</strong></span>
    <span>Phí nền tảng: <strong id="cfgFee" class="tw-text-orange-500">—%</strong></span>
    <span>Thợ thực nhận: <strong id="cfgNet" class="tw-text-green-600">—%</strong></span>
    <a href="/admin/travel-fee-config" class="tw-ml-auto tw-text-blue-600 tw-font-semibold hover:tw-underline">Chỉnh sửa →</a>
  </div>

  {{-- Chart --}}
  <div class="rev-kpi tw-mb-8">
    <h2 class="tw-font-bold tw-text-base tw-mb-4" style="font-family:Manrope">📈 Doanh thu theo ngày</h2>
    <canvas id="revenueChart" height="90"></canvas>
  </div>

  {{-- Two cols: top workers + salary table tabs --}}
  <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-12 tw-gap-6 tw-mb-8">
    {{-- Top workers --}}
    <div class="rev-kpi lg:tw-col-span-4">
      <h2 class="tw-font-bold tw-text-base tw-mb-4" style="font-family:Manrope">🏆 Top thợ doanh thu cao</h2>
      <div id="topWorkerList" class="tw-space-y-3"></div>
    </div>

    {{-- Salary table --}}
    <div class="rev-kpi lg:tw-col-span-8 tw-overflow-auto">
      <h2 class="tw-font-bold tw-text-base tw-mb-4" style="font-family:Manrope">💼 Bảng lương thợ</h2>
      <table>
        <thead>
          <tr>
            <th>Thợ</th>
            <th>Số đơn</th>
            <th>Tiền gộp</th>
            <th>Thuế</th>
            <th>Phí app</th>
            <th class="tw-text-green-600">Thực nhận</th>
            <th>Số dư ví</th>
            <th>Đã rút</th>
            <th>Trạng thái</th>
          </tr>
        </thead>
        <tbody id="salaryTable"><tr><td colspan="9" class="tw-text-center tw-py-8 tw-text-slate-400">Đang tải...</td></tr></tbody>
      </table>
    </div>
  </div>

  {{-- Withdrawal section --}}
  <div class="rev-kpi">
    <div class="tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-4 tw-mb-4">
      <h2 class="tw-font-bold tw-text-base" style="font-family:Manrope">💸 Lịch sử rút tiền</h2>
      <div class="tw-flex tw-gap-2 tw-flex-wrap">
        <button class="tab-btn active" data-wstatus="all">Tất cả</button>
        <button class="tab-btn" data-wstatus="dang_xu_ly">
          <span id="cntPending">0</span> Đang xử lý
        </button>
        <button class="tab-btn" data-wstatus="thanh_cong">
          <span id="cntSuccess">0</span> Thành công
        </button>
        <button class="tab-btn" data-wstatus="that_bai">Thất bại</button>
      </div>
      <input type="text" id="wSearch" placeholder="Tìm tên hoặc SĐT..." class="tw-border tw-border-slate-200 tw-rounded-xl tw-px-4 tw-py-2 tw-text-sm tw-w-56 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-300"/>
    </div>
    <div class="tw-overflow-auto">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Thợ</th>
            <th>SĐT</th>
            <th>Số tiền rút</th>
            <th>Số dư sau</th>
            <th>Thời gian</th>
            <th>Trạng thái</th>
          </tr>
        </thead>
        <tbody id="wTable"><tr><td colspan="7" class="tw-text-center tw-py-8 tw-text-slate-400">Đang tải...</td></tr></tbody>
      </table>
    </div>
    <div class="tw-flex tw-items-center tw-justify-between tw-mt-4 tw-text-sm tw-text-slate-500">
      <span id="wPagInfo">—</span>
      <div class="tw-flex tw-gap-2">
        <button id="wPrev" class="tw-px-4 tw-py-1.5 tw-rounded-lg tw-border tw-border-slate-200 hover:tw-bg-slate-50">← Trước</button>
        <button id="wNext" class="tw-px-4 tw-py-1.5 tw-rounded-lg tw-border tw-border-slate-200 hover:tw-bg-slate-50">Tiếp →</button>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/revenue.js') }}"></script>
@endpush
