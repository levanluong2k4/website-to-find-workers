@extends('layouts.app')

@section('title', 'Lịch & Trạng thái Thợ - Thợ Tốt')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/admin/worker-schedules.css') }}">
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="sch-shell sch-shell--no-sidebar">
    <div class="sch-main-shell">
        <header class="sch-top">
            <div class="sch-top-left">
                <nav aria-label="breadcrumb" class="sch-breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <a href="#">Operations</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Schedules</span>
                </nav>
                <h3>Trạng thái hoạt động & Lịch thợ</h3>
            </div>
            <div class="sch-top-right">
                <div class="sch-status-chips">
                    <span class="sch-chip sch-chip--info" id="schSyncStatus">
                        <i class="fas fa-sync fa-spin me-2"></i>Đang lấy dữ liệu...
                    </span>
                </div>
            </div>
        </header>

        <main class="sch-content">
            <section class="sch-page-head">
                <div class="sch-page-head__text">
                    <div class="sch-eyebrow">
                        <i class="fa-solid fa-calendar-days"></i>
                        Workforce Tracking
                    </div>
                    <h1>Theo dõi Lịch & Trạng thái</h1>
                    <p>Giám sát tình trạng rảnh/bận, lịch đang nhận và thông tin làm việc thực tế của đội ngũ kỹ thuật viên theo thời gian thực.</p>
                </div>
                <div class="sch-page-head__actions">
                    <button type="button" class="sch-btn sch-btn--secondary" id="btnRefreshSch">
                        <i class="fa-solid fa-rotate"></i>
                        Làm mới
                    </button>
                </div>
            </section>

            <!-- Stats Bar -->
            <div class="sch-stat-grid mb-4">
                <div class="sch-stat-card">
                    <div class="sch-stat-card__icon sch-stat-card__icon--blue">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="sch-stat-card__label">Tổng số thợ Tracking</div>
                    <div class="sch-stat-card__value" id="statTracked">0</div>
                </div>

                <div class="sch-stat-card">
                    <div class="sch-stat-card__icon sch-stat-card__icon--teal">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div class="sch-stat-card__label">Sẵn sàng nhận khách</div>
                    <div class="sch-stat-card__value" id="statAvailable">0</div>
                </div>

                <div class="sch-stat-card">
                    <div class="sch-stat-card__icon sch-stat-card__icon--orange">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                    <div class="sch-stat-card__label">Đang có lịch / Đang làm</div>
                    <div class="sch-stat-card__value" id="statScheduled">0</div>
                </div>

                <div class="sch-stat-card">
                    <div class="sch-stat-card__icon sch-stat-card__icon--purple">
                        <i class="fa-solid fa-user-slash"></i>
                    </div>
                    <div class="sch-stat-card__label">Tạm nghỉ / Offline</div>
                    <div class="sch-stat-card__value" id="statOffline">0</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="sch-filters card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-3 d-flex gap-3 align-items-center flex-wrap">
                    <div class="position-relative" style="min-width: 300px;">
                        <i class="fas fa-search position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
                        <input type="text" class="form-control form-control-lg bg-light border-0 ps-5 rounded-3" id="schSearch" placeholder="Tìm tên thợ...">
                    </div>
                    <select class="form-select form-select-lg bg-light border-0 w-auto rounded-3" id="schStatusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="available">Trong lịch (Rảnh)</option>
                        <option value="scheduled">Đang có lịch</option>
                        <option value="repairing">Đang làm</option>
                        <option value="offline">Tạm khoá / Offline</option>
                    </select>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="row g-4" id="workerScheduleGrid">
                <!-- Data injected here via JS -->
                 <div class="col-12 py-5 text-center text-muted">
                    <div class="spinner-border mb-3" role="status"></div>
                    <p>Đang tải dữ liệu mạng lưới...</p>
                 </div>
            </div>
            
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/worker-schedules.js') }}"></script>
@endpush
