@extends('layouts.app')

@section('title', 'Giám sát Đơn hàng - Thợ Tốt')

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
        font-size: 0.9rem;
    }

    .table-custom tbody tr:hover {
        background-color: #f8fafc;
    }

    .status-badge {
        padding: 0.35em 0.8em;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
        white-space: nowrap;
    }

    .booking-cost-stack {
        display: grid;
        gap: 0.45rem;
    }

    .booking-cost-total {
        font-weight: 800;
        color: #15803d;
    }

    .booking-cost-breakdown {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }

    .booking-cost-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.28rem 0.62rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }

    .booking-cost-chip--travel {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .booking-cost-chip--transport {
        background: #ffedd5;
        color: #c2410c;
    }

    .booking-cost-chip--muted {
        background: #f1f5f9;
        color: #64748b;
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
                    <li class="breadcrumb-item active" aria-current="page">Đơn hàng</li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-0" style="color: #0f172a;">Giám sát Giao dịch</h2>
        </div>

        <div class="d-flex gap-2">
            <select class="form-select form-select-sm shadow-sm" id="statusFilter" style="border-radius: 8px; min-width: 150px;">
                <option value="">Tất cả trạng thái</option>
                <option value="cho_tho_nhan">Chờ thợ nhận</option>
                <option value="da_xac_nhan">Đã xác nhận</option>
                <option value="dang_thuc_hien">Đang thực hiện</option>
                <option value="hoan_thanh">Đã hoàn thành</option>
                <option value="da_huy">Đã hủy</option>
            </select>
            <button class="btn btn-outline-primary shadow-sm" id="btnRefresh">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive table-custom">
        <table class="table mb-0 table-borderless">
            <thead>
                <tr>
                    <th class="ps-4">Mã Đơn</th>
                    <th>Dịch vụ & Lịch</th>
                    <th>Khách hàng</th>
                    <th>Thợ phụ trách</th>
                    <th>Tổng phí & Logistics</th>
                    <th class="text-end pe-4">Trạng thái</th>
                </tr>
            </thead>
            <tbody id="bookingsTableBody">
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 mb-0">Đang tải biểu ghi...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/bookings.js') }}"></script>
@endpush
