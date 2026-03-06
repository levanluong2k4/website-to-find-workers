@extends('layouts.app')

@section('title', 'Admin Dashboard - Thợ Tốt')

@push('styles')
<style>
    body {
        background-color: #f8fafc;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.03);
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: var(--bs-primary);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .stat-card:hover::before {
        opacity: 1;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .bg-primary-light {
        background: rgba(37, 99, 235, 0.1);
        color: #2563eb;
    }

    .bg-success-light {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }

    .bg-warning-light {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }

    .bg-danger-light {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }

    .loading-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        background: #e2e8f0;
        color: transparent;
        border-radius: 4px;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: .5;
        }
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1" style="color: #0f172a;">Tổng quan Hệ thống</h2>
            <p class="text-muted mb-0">Theo dõi các chỉ số hoạt động kinh doanh (Real-time)</p>
        </div>
        <button class="btn btn-outline-primary shadow-sm" id="btnRefresh">
            <i class="fas fa-sync-alt me-2"></i>Làm mới
        </button>
    </div>

    <!-- Stats Grid -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 fw-semibold" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Tổng Khách Hàng</p>
                        <h3 class="fw-bold mb-0" style="color: #0f172a;" id="statCustomers"><span class="loading-pulse px-4 py-1"></span></h3>
                    </div>
                    <div class="stat-icon bg-primary-light">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card" style="border-top-color: #10b981;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 fw-semibold" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Đối Tác Thợ</p>
                        <h3 class="fw-bold mb-0" style="color: #0f172a;" id="statWorkers"><span class="loading-pulse px-4 py-1"></span></h3>
                    </div>
                    <div class="stat-icon bg-success-light">
                        <i class="fas fa-tools"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card" style="border-top-color: #f59e0b;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 fw-semibold" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Tổng Đơn Hàng</p>
                        <h3 class="fw-bold mb-0" style="color: #0f172a;" id="statBookings"><span class="loading-pulse px-4 py-1"></span></h3>
                        <small class="text-success fw-bold d-none" id="statCompletedBookings"><i class="fas fa-arrow-up me-1"></i></small>
                    </div>
                    <div class="stat-icon bg-warning-light">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card" style="border-top-color: #ef4444;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 fw-semibold" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Hoa Hồng (10%)</p>
                        <h3 class="fw-bold mb-0 text-danger" id="statCommission"><span class="loading-pulse px-4 py-1"></span></h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Trên tổng: <span id="statRevenue">0</span>đ</small>
                    </div>
                    <div class="stat-icon bg-danger-light">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <h4 class="fw-bold mb-3" style="color: #0f172a;">Lối tắt Công cụ</h4>
    <div class="row g-4">
        <div class="col-md-4">
            <a href="/admin/users" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 16px; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center shadow" style="width: 50px; height: 50px;">
                            <i class="fas fa-user-shield fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1 text-dark">Xét Duyệt Tài Khoản</h5>
                            <p class="text-muted mb-0 small">Quản lý kích hoạt, khóa tài khoản thợ và khách hàng.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="/admin/services" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 16px; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success text-white rounded-circle d-flex justify-content-center align-items-center shadow" style="width: 50px; height: 50px;">
                            <i class="fas fa-list-alt fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1 text-dark">Cấu Hình Dịch Vụ</h5>
                            <p class="text-muted mb-0 small">Thêm mới, chỉnh sửa giá tham khảo của các dịch vụ.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="/admin/bookings" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 16px; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-warning text-dark rounded-circle d-flex justify-content-center align-items-center shadow" style="width: 50px; height: 50px;">
                            <i class="fas fa-search-dollar fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1 text-dark">Giám Sát Giao Dịch</h5>
                            <p class="text-muted mb-0 small">Theo dõi luồng công việc, trạng thái thanh toán và hủy đơn.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/dashboard.js') }}"></script>
@endpush