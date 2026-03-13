@extends('layouts.app')

@section('title', 'Quan ly Dich vu - Tho Tot')

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
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        padding: 1rem;
    }

    .table-custom td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        color: #0f172a;
    }

    .service-thumb {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        background: #fff;
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
                    <li class="breadcrumb-item active" aria-current="page">Dich vu</li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-1" style="color:#0f172a;">Quan ly dich vu</h2>
            <p class="text-muted mb-0">Them, sua, an hien va xoa mem dich vu trong cua hang.</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <select class="form-select shadow-sm" id="serviceStatusFilter" style="min-width: 180px;">
                <option value="">Tat ca trang thai</option>
                <option value="1">Dang hoat dong</option>
                <option value="0">Da an / da xoa</option>
            </select>
            <button class="btn btn-outline-primary shadow-sm" id="btnRefreshServices">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#serviceModal" id="btnAddService">
                <i class="fas fa-plus me-2"></i>Them dich vu
            </button>
        </div>
    </div>

    <div class="table-responsive table-custom">
        <table class="table mb-0 table-borderless">
            <thead>
                <tr>
                    <th class="ps-4">ID</th>
                    <th>Anh</th>
                    <th>Ten dich vu</th>
                    <th>Mo ta</th>
                    <th>Trang thai</th>
                    <th class="text-end pe-4">Thao tac</th>
                </tr>
            </thead>
            <tbody id="servicesTableBody">
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 mb-0">Dang tai dich vu...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="serviceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-light border-0 px-4 py-3">
                <h5 class="modal-title fw-bold" id="serviceModalLabel">Them dich vu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="serviceForm" class="d-grid gap-3">
                    <input type="hidden" id="serviceId">

                    <div>
                        <label class="form-label fw-semibold" for="serviceName">Ten dich vu</label>
                        <input type="text" class="form-control" id="serviceName" required maxlength="255">
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="serviceDesc">Mo ta</label>
                        <textarea class="form-control" id="serviceDesc" rows="3"></textarea>
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="serviceImage">Hinh anh / icon URL</label>
                        <input type="url" class="form-control" id="serviceImage" placeholder="https://example.com/service.png">
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="serviceActive" checked>
                        <label class="form-check-label" for="serviceActive">Dang hoat dong</label>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnSaveService">
                        <i class="fas fa-save me-2"></i>Luu dich vu
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/services.js') }}"></script>
@endpush
