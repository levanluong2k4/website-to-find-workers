@extends('layouts.app')

@section('title', 'Đơn Của Tôi - Thợ Tốt')

@push('styles')
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

    .nav-pills-custom .nav-link {
        color: #64748b;
        background-color: transparent;
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .nav-pills-custom .nav-link:hover {
        background-color: #e2e8f0;
        color: #0f172a;
    }

    .nav-pills-custom .nav-link.active {
        background-color: #0f172a;
        color: #ffffff;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .booking-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        transition: 0.2s ease;
    }

    .booking-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .status-cho_xac_nhan {
        background-color: #fef9c3;
        color: #a16207;
    }

    .status-da_xac_nhan {
        background-color: #e0f2fe;
        color: #0284c7;
    }

    .status-dang_lam {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .status-cho_hoan_thanh {
        background-color: #ffedd5;
        color: #c2410c;
    }

    .status-da_xong {
        background-color: #dcfce7;
        color: #15803d;
    }

    .status-da_huy {
        background-color: #fee2e2;
        color: #b91c1c;
    }

    .btn-update {
        background-color: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #334155;
        font-weight: 500;
        transition: 0.2s;
    }

    .btn-update:hover {
        background-color: #f1f5f9;
        border-color: #94a3b8;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 16px;
        border: 1px dashed #cbd5e1;
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="header-banner">
    <div class="container">
        <h1 class="fw-bold text-dark mb-2" style="letter-spacing: -0.5px;">Đơn Của Tôi</h1>
        <p class="text-secondary mb-0 fs-5">Quản lý và cập nhật trạng thái các đơn sửa chữa bạn đang nhận.</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills nav-pills-custom gap-2 d-flex overflow-auto flex-nowrap pb-2" id="bookingTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active whitespace-nowrap" id="tab-all" data-status="all" type="button" role="tab">Tất cả</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link whitespace-nowrap" id="tab-active" data-status="active" type="button" role="tab">Đang xử lý</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link whitespace-nowrap" id="tab-completed" data-status="completed" type="button" role="tab">Đã hoàn thành</button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Container chứa Danh sách đơn -->
    <div class="row g-4" id="myBookingsContainer">
        <!-- Skeleton Loading -->
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
            <p class="text-muted mt-3">Đang tải danh sách đơn...</p>
        </div>
    </div>
</div>

<!-- Modal Cập Nhật Chi Phí -->
<div class="modal fade" id="modalCosts" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark">Cập Nhật Chi Phí Đơn Hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formUpdateCosts">
                    <input type="hidden" id="costBookingId">

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Tiền công thợ (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control bg-light" id="inputTienCong" placeholder="VD: 150000" required min="0">
                    </div>

                    <div class="mb-3" id="truckFeeContainer" style="display: none;">
                        <label class="form-label fw-semibold text-secondary">Phí thuê xe chở (VNĐ)</label>
                        <input type="number" class="form-control bg-light" id="inputTienThueXe" placeholder="VD: 200000" min="0">
                        <small class="text-info"><i class="fas fa-info-circle"></i> Khách có yêu cầu thuê xe chở thiết bị.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Chi phí phát sinh linh kiện (VNĐ)</label>
                        <input type="number" class="form-control bg-light" id="inputPhiLinhKien" placeholder="VD: 50000" required min="0" value="0">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary">Ghi chú linh kiện (Nếu có)</label>
                        <textarea class="form-control bg-light" id="inputGhiChuLinhKien" rows="2" placeholder="Ghi chú chi tiết vật tư đã thay..."></textarea>
                    </div>

                    <div class="alert alert-light border border-info-subtle mb-4">
                        <p class="mb-1 fw-bold text-dark">Phí đi lại (Hệ thống tính): <span id="displayPhiDiLai" class="text-primary float-end">0 đ</span></p>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3">Cập Nhật Tổng Chi Phí</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/worker/my-bookings.js') }}?v={{ time() }}"></script>
@endpush