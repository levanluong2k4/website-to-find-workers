@extends('layouts.app')

@section('title', 'Quản lý thợ - Thợ Tốt')

@push('styles')
<style>
    body {
        background-color: #f8fafc;
    }
    
    .admin-page-title {
        font-size: 1.875rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.025em;
    }

    .admin-page-subtitle {
        color: #64748b;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    .btn-lumina-secondary {
        background-color: #f1f5f9;
        color: #334155;
        border: none;
        border-radius: 9999px;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }
    .btn-lumina-secondary:hover {
        background-color: #e2e8f0;
    }

    .btn-lumina-primary {
        background-color: #2563eb;
        color: white;
        border: none;
        border-radius: 9999px;
        padding: 0.5rem 1.25rem;
        font-size: 0.875rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2), 0 2px 4px -2px rgba(37, 99, 235, 0.2);
        transition: all 0.2s;
    }
    .btn-lumina-primary:hover {
        background-color: #1d4ed8;
        color: white;
    }

    .bento-card {
        background: white;
        border-radius: 1rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        padding: 1.25rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    /* Filter Pills Container */
    .filter-pills-container {
        display: flex;
        gap: 0.25rem;
        padding: 0.25rem;
        flex-wrap: wrap;
        width: 100%;
    }
    .filter-pill {
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
        border: none;
        background: transparent;
        color: #64748b;
        transition: all 0.2s;
        white-space: nowrap;
        flex: 1;
        text-align: center;
    }
    @media (max-width: 768px) {
        .filter-pill {
            flex: 1 1 45%;
        }
    }
    .filter-pill:hover {
        background-color: #f8fafc;
        color: #0f172a;
    }
    .filter-pill.active {
        background-color: #0f172a;
        color: white;
    }

    /* Stat Cards */
    .stat-label {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 500;
        margin-bottom: 0.25rem;
    }
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
    }
    .stat-value.active { color: #16a34a; }
    .stat-value.pending { color: #ca8a04; }
    .stat-value.locked { color: #dc2626; }

    /* Table Component */
    .table-container {
        background: white;
        border-radius: 1rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .table-lumina {
        width: 100%;
        margin-bottom: 0;
    }
    .table-lumina th {
        background-color: #f8fafc;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .table-lumina td {
        padding: 1rem 1.5rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        font-size: 0.875rem;
    }
    .table-lumina tbody tr:last-child td {
        border-bottom: none;
    }
    .table-lumina tbody tr {
        transition: background-color 0.15s;
    }
    .table-lumina tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Name and Initials */
    .avatar-initials {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
        color: white;
    }
    
    .worker-name { margin-bottom: 0; font-weight: 600; font-size: 0.875rem; color: #0f172a; }
    .worker-contact { font-size: 0.75rem; color: #64748b; margin: 0; }

    /* Tags / Chips */
    .chip-lumina {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.625rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1.25rem;
        margin: 0.125rem 0;
    }

    /* Actions */
    .action-container {
        display: flex;
        gap: 0.25rem;
        justify-content: flex-end;
    }
    
    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 0.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent;
        color: #64748b;
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .action-btn i {
        font-size: 14px;
    }

    .action-btn.edit:hover { background-color: #eff6ff; color: #2563eb; }
    .action-btn.lock:hover { background-color: #fef2f2; color: #dc2626; }
    .action-btn.unlock:hover { background-color: #f0fdf4; color: #16a34a; }
    .action-btn.delete:hover { background-color: #fee2e2; color: #ef4444; }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container py-4" style="max-width: 1280px;">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="admin-page-title mb-0">Quản lý thợ</h1>
            <p class="admin-page-subtitle mb-0">Quản lý tài khoản, xét duyệt hồ sơ và theo dõi trạng thái đối tác.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn-lumina-secondary" id="btnRefresh" title="Làm mới">
                <i class="fas fa-sync-alt"></i> Làm mới
            </button>
            <button class="btn-lumina-primary" data-bs-toggle="modal" data-bs-target="#workerModal" id="btnAddWorker">
                <i class="fas fa-plus"></i> Thêm thợ
            </button>
        </div>
    </div>

    <!-- Bento Grid Stats & Filters -->
    <div class="row g-3 mb-4">
        <!-- Filters -->
        <div class="col-md-5 col-lg-6">
            <div class="bento-card p-2">
                <div class="filter-pills-container" id="filterPillsContainer">
                    <button class="filter-pill active" data-value="">Tất cả</button>
                    <button class="filter-pill" data-value="cho_duyet">Chờ duyệt</button>
                    <button class="filter-pill" data-value="da_duyet">Đã duyệt</button>
                    <button class="filter-pill" data-value="tu_choi">Từ chối</button>
                </div>
            </div>
        </div>
        <!-- Stats -->
        <div class="col-md-7 col-lg-6">
            <div class="row g-3 h-100">
                <div class="col-4">
                    <div class="bento-card">
                        <div class="stat-label">Thợ hoạt động</div>
                        <div class="stat-value active" id="stat-active">0</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="bento-card">
                        <div class="stat-label">Đang chờ xử lý</div>
                        <div class="stat-value pending" id="stat-pending">0</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="bento-card">
                        <div class="stat-label">Tài khoản khóa</div>
                        <div class="stat-value locked" id="stat-locked">0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="table-responsive table-container">
        <table class="table table-borderless table-lumina">
            <thead>
                <tr>
                    <th class="ps-4">UID</th>
                    <th>Thợ / Đối tác</th>
                    <th>Hồ sơ / Kỹ năng</th>
                    <th>Duyệt</th>
                    <th>Trạng thái</th>
                    <th class="text-end pe-4">Thao tác</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 mb-0">Đang tải danh sách thợ...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Worker Modal -->
<div class="modal fade" id="workerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="workerModalLabel">Thêm thợ kỹ thuật mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="workerForm">
                    <input type="hidden" id="workerId">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3 text-primary">Thông tin tài khoản</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Họ và tên</label>
                                <input type="text" class="form-control" id="workerName" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Email</label>
                                <input type="email" class="form-control" id="workerEmail" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Số điện thoại</label>
                                <input type="text" class="form-control" id="workerPhone" required>
                            </div>
                            <div class="mb-3" id="passwordGroup">
                                <label class="form-label small fw-semibold">Mật khẩu</label>
                                <input type="password" class="form-control" id="workerPassword">
                                <small class="text-muted" id="passwordHelp">Để trống nếu không đổi (khi sửa).</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Ảnh đại diện</label>
                                <input type="file" class="form-control" id="workerAvatar" accept="image/*">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3 text-primary">Hồ sơ năng lực</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Số CCCD</label>
                                <input type="text" class="form-control" id="workerCCCD" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Địa chỉ</label>
                                <input type="text" class="form-control" id="workerAddress">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Kinh nghiệm</label>
                                <textarea class="form-control" id="workerExp" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-12">
                            <h6 class="fw-bold mb-3 text-primary">Dịch vụ cung cấp</h6>
                            <div id="skillsSelection" class="d-flex flex-wrap gap-2 p-3 bg-light rounded border">
                                <p class="text-muted small mb-0">Đang tải...</p>
                            </div>
                        </div>
                        <div class="col-12" id="statusGroup" style="display: none;">
                            <div class="form-check form-switch border p-3 rounded bg-light">
                                <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="workerActive" checked style="width: 2.5em;">
                                <label class="form-check-label fw-bold">Tài khoản hoạt động</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-top text-end">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary px-4" id="btnSaveWorker">Lưu thông tin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/users.js') }}"></script>
@endpush
