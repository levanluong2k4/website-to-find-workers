@extends('layouts.app')

@section('title', 'Quan ly Nguoi dung - Tho Tot')

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
        padding: 1rem;
    }

    .table-custom td {
        padding: 1rem;
        vertical-align: top;
        border-bottom: 1px solid #f1f5f9;
        color: #0f172a;
    }

    .chip {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.7rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        margin: 0 0.35rem 0.35rem 0;
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="/admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Nguoi dung</li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-1" style="color:#0f172a;">Quan ly cong dong va duyet ho so tho</h2>
            <p class="text-muted mb-0">Khoa/mo khoa tai khoan va duyet ho so doi tac tho ngay tai mot man hinh.</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <select class="form-select shadow-sm" id="roleFilter" style="min-width: 160px;">
                <option value="">Tat ca vai tro</option>
                <option value="customer">Khach hang</option>
                <option value="worker">Tho</option>
            </select>
            <select class="form-select shadow-sm" id="approvalFilter" style="min-width: 180px;">
                <option value="">Tat ca duyet ho so</option>
                <option value="cho_duyet">Cho duyet</option>
                <option value="da_duyet">Da duyet</option>
                <option value="tu_choi">Tu choi</option>
            </select>
            <button class="btn btn-outline-primary shadow-sm" id="btnRefresh">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <div class="table-responsive table-custom">
        <table class="table mb-0 table-borderless">
            <thead>
                <tr>
                    <th class="ps-4">UID</th>
                    <th>Thong tin</th>
                    <th>Vai tro</th>
                    <th>Ho so tho</th>
                    <th>Trang thai TK</th>
                    <th class="text-end pe-4">Thao tac</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 mb-0">Dang tai danh sach nguoi dung...</p>
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
