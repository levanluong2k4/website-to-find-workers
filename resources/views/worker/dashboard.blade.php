@extends('layouts.app')

@section('title', 'Bảng Việc Làm - Thợ Tốt')

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

    .job-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .job-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        border-color: #cbd5e1;
    }

    .icon-box {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-claim {
        background-color: #0f172a;
        color: white;
        font-weight: 600;
        border-radius: 8px;
        transition: 0.2s ease;
    }

    .btn-claim:hover {
        background-color: #1e293b;
        color: white;
    }

    .custom-badge {
        font-weight: 500;
        padding: 6px 10px;
        border-radius: 6px;
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
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fw-bold text-dark mb-2" style="letter-spacing: -0.5px;">Bảng Việc Làm</h1>
                <p class="text-secondary mb-0 fs-5">Khám phá các đơn sửa chữa đang cần thợ ngay lúc này.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="d-inline-flex align-items-center bg-white border px-4 py-2 rounded-2 shadow-sm">
                    <span class="material-symbols-outlined text-success me-2">verified</span>
                    <span class="fw-semibold">Cửa hàng Nha Trang</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <!-- Phân vùng Tabs / Filter nhanh -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0 text-dark">Việc Mới Nhất</h4>
        <button class="btn btn-light border btn-sm d-flex align-items-center" id="btnRefreshJobs">
            <span class="material-symbols-outlined fs-6 me-1">refresh</span> Làm mới
        </button>
    </div>

    <!-- Container chứa Danh sách đơn -->
    <div class="row g-4" id="availableJobsContainer">
        <!-- Skeleton Loading -->
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
            <p class="text-muted mt-3">Đang tải danh sách việc làm...</p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/worker/dashboard.js') }}?v={{ time() }}"></script>
@endpush