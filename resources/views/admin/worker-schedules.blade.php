@extends('layouts.app')

@section('title', 'Lịch & Trạng Thái Thợ - Thợ Tốt')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/admin/worker-schedules.css') }}">
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="sch-page-shell">
    <main class="sch-page" id="workerSchedulesPage">
        <section class="sch-toolbar" aria-label="Điều khiển lịch làm việc">
            <div class="sch-toolbar__group sch-toolbar__group--meta">
                <span class="sch-sync-chip" id="schSyncStatus">
                    <i class="fas fa-sync fa-spin"></i>
                    <span>Đang lấy dữ liệu...</span>
                </span>
                <button type="button" class="sch-link-button" id="btnRefreshSch">Làm mới</button>
            </div>

            <div class="sch-toolbar__group sch-toolbar__group--date">
                <button type="button" class="sch-date-pill" id="btnTodayRange">Hôm nay</button>
                <label class="sch-date-select" for="schDateSelect">
                    <i class="fa-regular fa-calendar"></i>
                    <select id="schDateSelect" aria-label="Chọn ngày có booking">
                        <option value="">Đang tải ngày có đơn...</option>
                    </select>
                </label>
            </div>
        </section>

        <section class="sch-filter-pills" aria-label="Lọc trạng thái thợ">
            <button type="button" class="sch-filter-pill is-active" data-status="">Tất cả</button>
            <button type="button" class="sch-filter-pill" data-status="available">Trống lịch</button>
            <button type="button" class="sch-filter-pill" data-status="scheduled">Đang có lịch</button>
            <button type="button" class="sch-filter-pill" data-status="repairing">Đang sửa</button>
            <button type="button" class="sch-filter-pill" data-status="offline">Tạm nghỉ</button>
        </section>

        <section class="sch-summary-grid" aria-label="Tóm tắt lịch làm việc">
            <article class="sch-summary-card">
                <span class="sch-summary-card__label">Tổng thợ</span>
                <strong class="sch-summary-card__value" id="statTracked">0</strong>
            </article>
            <article class="sch-summary-card">
                <span class="sch-summary-card__label">Có slot rảnh</span>
                <strong class="sch-summary-card__value sch-summary-card__value--green" id="statAvailable">0</strong>
            </article>
            <article class="sch-summary-card">
                <span class="sch-summary-card__label">Slot bận</span>
                <strong class="sch-summary-card__value sch-summary-card__value--slate" id="statBusySlots">0</strong>
            </article>
            <article class="sch-summary-card">
                <span class="sch-summary-card__label">Độ tải</span>
                <strong class="sch-summary-card__value sch-summary-card__value--blue" id="statLoadPercent">0%</strong>
            </article>
            <article class="sch-summary-card">
                <span class="sch-summary-card__label">Đang có lịch</span>
                <strong class="sch-summary-card__value sch-summary-card__value--amber" id="statScheduled">0</strong>
            </article>
            <article class="sch-summary-card">
                <span class="sch-summary-card__label">Tạm nghỉ</span>
                <strong class="sch-summary-card__value sch-summary-card__value--slate" id="statOffline">0</strong>
            </article>
        </section>

        <section class="sch-layout">
            <div class="sch-board-card">
                <div class="sch-board-card__legend">
                    <span class="sch-legend-title">Chú giải:</span>
                    <span class="sch-legend-item"><span class="sch-legend-dot sch-legend-dot--repairing"></span>Đang thực hiện</span>
                    <span class="sch-legend-item"><span class="sch-legend-dot sch-legend-dot--busy"></span>Đã giữ lịch</span>
                    <span class="sch-legend-item"><span class="sch-legend-dot sch-legend-dot--completed"></span>Hoàn thành</span>
                    <span class="sch-legend-item"><span class="sch-legend-dot sch-legend-dot--cancelled"></span>Đã hủy</span>
                    <span class="sch-legend-item"><span class="sch-legend-dot sch-legend-dot--free"></span>Trống</span>
                </div>

                <div class="sch-board-status" id="schFilteredSummary">Đang tổng hợp dữ liệu thợ và slot trong ngày...</div>

                <div class="sch-board-table">
                    <div id="schBoardHead" class="sch-board-head"></div>
                    <div id="schBoardBody" class="sch-board-body">
                        <div class="sch-board-empty">
                            <div class="sch-board-empty__spinner"></div>
                            <strong>Đang tải lịch đội thợ...</strong>
                            <p>Hệ thống đang gom slot theo ngày đã chọn và dựng board điều phối.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sch-side-backdrop" id="schSideBackdrop" aria-hidden="true"></div>

            <aside class="sch-side-panel" id="schSidePanel" tabindex="-1" aria-hidden="true">
                <div id="schInspectorBody" class="sch-side-panel__body">
                    <div class="sch-inspector-empty">
                        <i class="fa-solid fa-user-check"></i>
                        <strong>Chọn một thợ để xem chi tiết</strong>
                        <p>Board bên trái sẽ đồng bộ thông tin booking trong ngày cho thợ đang được chọn.</p>
                    </div>
                </div>
            </aside>
        </section>
    </main>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/worker-schedules.js') }}"></script>
@endpush
