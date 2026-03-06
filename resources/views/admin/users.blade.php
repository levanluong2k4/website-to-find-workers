@extends('layouts.app')

@section('title', 'Quản lý Người Dùng - Thợ Tốt')

@push('styles')
<style>
    body {
        background-color: #f8fafc;
    }

    .table-custom {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .table-custom th {
        background-color: #f1f5f9;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
        padding: 1rem;
    }

    .table-custom td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
    }

    .table-custom tbody tr:hover {
        background-color: #f8fafc;
    }

    .status-badge {
        padding: 0.35em 0.8em;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="/admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Người Dùng</li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-0" style="color: #0f172a;">Quản lý Cộng đồng</h2>
        </div>

        <div class="d-flex gap-2">
            <select class="form-select form-select-sm shadow-sm" id="roleFilter" style="border-radius: 8px; width: 150px;">
                <option value="">Tất cả vai trò</option>
                <option value="customer">Khách hàng</option>
                <option value="worker">Thợ sửa chữa</option>
            </select>
            <button class="btn btn-primary shadow-sm" id="btnRefresh">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive table-custom">
        <table class="table mb-0 table-borderless">
            <thead>
                <tr>
                    <th class="ps-4">UID</th>
                    <th>Thông tin Cá nhân</th>
                    <th>Vai trò</th>
                    <th>Ngày tham gia</th>
                    <th>Trạng thái</th>
                    <th class="text-end pe-4">Thao tác</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 mb-0">Đang tải danh sách...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/users.js') }}"></script>
@endpush