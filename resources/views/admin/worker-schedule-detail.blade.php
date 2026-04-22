@extends('layouts.app')

@section('title', 'Chi tiết lịch thợ - Thợ Tốt')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/admin/worker-schedule-detail.css') }}">
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="wsd-shell">
    <main
        class="wsd-page"
        id="workerScheduleDetailPage"
        data-worker-id="{{ (int) ($workerId ?? request()->route('workerId')) }}"
    >
        <section class="wsd-topbar" aria-label="Điều hướng chi tiết lịch thợ">
            <a
                class="wsd-back-link"
                id="workerScheduleBackLink"
                href="{{ route('admin.worker-schedules', array_filter([
                    'date' => request()->query('date'),
                    'worker' => (int) ($workerId ?? request()->route('workerId')),
                ], fn ($value) => $value !== null && $value !== '')) }}"
            >
                <i class="fa-solid fa-arrow-left"></i>
                <span>Quay lại board lịch thợ</span>
            </a>

            <div class="wsd-topbar__actions">
                <span class="wsd-sync-chip" id="wsdSyncStatus">
                    <i class="fas fa-sync fa-spin"></i>
                    <span>Đang tải chi tiết...</span>
                </span>
                <button type="button" class="wsd-ghost-button" id="wsdRefreshButton">Làm mới</button>
            </div>
        </section>

        <section class="wsd-grid">
            <section class="wsd-main-card" aria-label="Timeline công việc của thợ">
                <div id="wsdProfileHeader" class="wsd-profile-header"></div>

                <div class="wsd-date-nav" id="wsdDateNav">
                    <button type="button" class="wsd-date-nav__arrow" id="wsdPrevDate" aria-label="Ngày trước">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <div class="wsd-date-nav__label" id="wsdDateLabel">Đang tải ngày...</div>
                    <button type="button" class="wsd-date-nav__arrow" id="wsdNextDate" aria-label="Ngày sau">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <button type="button" class="wsd-date-nav__today" id="wsdTodayButton">Hôm nay</button>
                </div>

                <div id="wsdTimeline" class="wsd-timeline"></div>
            </section>

            <aside class="wsd-queue-card" aria-label="Danh sách đơn chờ phân công">
                <div class="wsd-queue-card__head">
                    <div>
                        <h2>Chờ phân công</h2>
                        <p id="wsdQueueMeta">Đang tải hàng chờ...</p>
                    </div>
                    <span class="wsd-queue-badge" id="wsdQueueCount">0</span>
                </div>

                <div id="wsdQueueList" class="wsd-queue-list"></div>
            </aside>
        </section>
    </main>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/worker-schedule-detail.js') }}"></script>
@endpush
