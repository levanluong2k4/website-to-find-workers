@extends('layouts.app')

@section('title', 'Lịch Làm Việc - Thợ Tốt')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
<style>
    body {
        background-color: #f8fafc;
    }

    .header-banner {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
        padding: 3rem 0;
        margin-bottom: 2rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .calendar-container {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .fc-event {
        cursor: pointer;
        border-radius: 4px;
        padding: 2px 4px;
        border: none;
    }

    .fc-toolbar-title {
        font-weight: 700;
        color: #0f172a;
    }

    .fc-button-primary {
        background-color: #3b82f6 !important;
        border-color: #3b82f6 !important;
    }

    .fc-button-primary:hover {
        background-color: #2563eb !important;
        border-color: #2563eb !important;
    }

    .status-badge {
        font-size: 0.8rem;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="header-banner">
    <div class="container">
        <h1 class="fw-bold text-dark mb-2" style="letter-spacing: -0.5px;">Lịch Làm Việc</h1>
        <p class="text-secondary mb-0 fs-5">Quản lý và sắp xếp các ca sửa chữa của bạn một cách dễ dàng.</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row">
        <div class="col-12">
            <div class="calendar-container">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chi Tiết Đơn (Tương tự My Bookings) -->
<div class="modal fade" id="eventDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark">Chi Tiết Đơn Lịch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <img id="modalAvatar" src="/assets/images/user-default.png" class="rounded-circle me-3 border" width="50" height="50" style="object-fit:cover;">
                    <div>
                        <h6 class="fw-bold mb-1 text-dark" id="modalCustomerName">Khách hàng</h6>
                        <small class="text-muted d-block"><i class="fas fa-phone-alt me-1"></i><span id="modalCustomerPhone"></span></small>
                    </div>
                </div>

                <div class="bg-light p-3 rounded-3 mb-3">
                    <div class="mb-2"><span class="text-muted fw-semibold">Dịch vụ:</span> <span id="modalService" class="fw-bold text-dark"></span></div>
                    <div class="mb-2"><span class="text-muted fw-semibold">Thời gian hẹn:</span> <span id="modalTime" class="fw-bold text-primary"></span></div>
                    <div class="mb-2"><span class="text-muted fw-semibold">Địa chỉ:</span> <span id="modalAddress" class="fw-bold text-dark"></span></div>
                    <div><span class="text-muted fw-semibold">Trạng thái:</span> <span id="modalStatus" class="status-badge"></span></div>
                </div>

                <div class="d-grid gap-2">
                    <a href="/worker/my-bookings" class="btn btn-outline-primary fw-bold" id="btnGoToBookings">Đi tới Quản lý Đơn</a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/vi.js"></script>
<script type="module" src="{{ asset('assets/js/worker/calendar.js') }}?v={{ time() }}"></script>
@endpush