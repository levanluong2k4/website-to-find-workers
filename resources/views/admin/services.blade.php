@extends('layouts.app')

@section('title', 'Quản lý Dịch vụ - Thợ Tốt')

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

    .img-thu-nail {
        width: 48px;
        height: 48px;
        object-fit: contain;
        border-radius: 8px;
        background: white;
        border: 1px solid #e2e8f0;
        padding: 4px;
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
                    <li class="breadcrumb-item active" aria-current="page">Dịch vụ</li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-0" style="color: #0f172a;">Danh mục Dịch vụ</h2>
        </div>

        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#serviceModal" id="btnAddService">
            <i class="fas fa-plus me-2"></i>Thêm Mới
        </button>
    </div>

    <!-- Table -->
    <div class="table-responsive table-custom">
        <table class="table mb-0 table-borderless">
            <thead>
                <tr>
                    <th class="ps-4">ID</th>
                    <th>Icon</th>
                    <th>Tên Dịch vụ</th>
                    <th>Mô tả</th>
                    <th class="text-end pe-4">Thao tác</th>
                </tr>
            </thead>
            <tbody id="servicesTableBody">
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 mb-0">Đang tải...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm/Sửa Dịch vụ -->
<div class="modal fade" id="serviceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);">
            <div class="modal-header bg-light border-bottom-0 p-4">
                <h5 class="modal-title fw-bold" id="serviceModalLabel">Thêm Dịch vụ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="serviceForm">
                    <input type="hidden" id="serviceId">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Tên dịch vụ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg bg-light border-0" id="serviceName" required placeholder="Nhập tên dịch vụ">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Mô tả chi tiết</label>
                        <textarea class="form-control form-control-lg bg-light border-0" id="serviceDesc" rows="3" placeholder="Ví dụ: Sửa chữa đường ống nước, lắp đặt bơm..."></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Icon URL (Tuỳ chọn)</label>
                        <input type="url" class="form-control" id="serviceIcon" placeholder="https://example.com/icon.png">
                        <div class="form-text">Bạn có thể dùng link icon từ Flaticon hoặc bỏ trống để dùng icon mặc định.</div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg" id="btnSaveService">
                            <i class="fas fa-save me-2"></i>Lưu Dịch Vụ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/services.js') }}"></script>
@endpush