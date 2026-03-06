@extends('layouts.app')

@section('title', 'Thống Kê Thu Nhập - Thợ Tốt')

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

    .stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 1rem;
        height: 100%;
    }

    .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .chart-container {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        height: 400px;
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="header-banner">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fw-bold text-dark mb-2" style="letter-spacing: -0.5px;">Thống Kê Thu Nhập</h1>
                <p class="text-secondary mb-0 fs-5">Theo dõi doanh thu và hiệu quả công việc của bạn.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="/worker/my-bookings" class="btn btn-primary px-4 py-2 rounded-2 shadow-sm d-inline-flex align-items-center">
                    <span class="material-symbols-outlined fs-5 me-2">receipt_long</span>
                    Xem đơn của tôi
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4 mb-5">
        <!-- Tổng doanh thu -->
        <div class="col-md-6 col-lg-3">
            <div class="stat-card border-primary" style="border-left-width: 4px !important;">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <span class="material-symbols-outlined fs-2">account_balance_wallet</span>
                </div>
                <div>
                    <p class="text-muted fw-semibold mb-1 small text-uppercase">TỔNG DOANH THU</p>
                    <h4 class="fw-bold mb-0 text-dark" id="statTongDoanhThu">0₫</h4>
                </div>
            </div>
        </div>

        <!-- Doanh thu tháng này -->
        <div class="col-md-6 col-lg-3">
            <div class="stat-card border-success" style="border-left-width: 4px !important;">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <span class="material-symbols-outlined fs-2">payments</span>
                </div>
                <div>
                    <p class="text-muted fw-semibold mb-1 small text-uppercase">THÁNG NÀY</p>
                    <h4 class="fw-bold mb-0 text-dark" id="statDoanhThuThangNay">0₫</h4>
                </div>
            </div>
        </div>

        <!-- Đơn hoàn thành -->
        <div class="col-md-6 col-lg-3">
            <div class="stat-card border-info" style="border-left-width: 4px !important;">
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <span class="material-symbols-outlined fs-2">done_all</span>
                </div>
                <div>
                    <p class="text-muted fw-semibold mb-1 small text-uppercase">ĐƠN ĐÃ HOÀN THÀNH</p>
                    <h4 class="fw-bold mb-0 text-dark" id="statDonHoanThanh">0</h4>
                </div>
            </div>
        </div>

        <!-- Đơn hủy -->
        <div class="col-md-6 col-lg-3">
            <div class="stat-card border-danger" style="border-left-width: 4px !important;">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <span class="material-symbols-outlined fs-2">cancel</span>
                </div>
                <div>
                    <p class="text-muted fw-semibold mb-1 small text-uppercase">ĐƠN BỊ HỦY</p>
                    <h4 class="fw-bold mb-0 text-dark" id="statDonHuy">0</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ -->
    <div class="row">
        <div class="col-12">
            <div class="chart-container">
                <h5 class="fw-bold mb-4">Doanh thu 7 ngày qua</h5>
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="module" src="{{ asset('assets/js/worker/analytics.js') }}?v={{ time() }}"></script>
@endpush