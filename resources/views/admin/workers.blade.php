@extends('layouts.app')

@section('title', 'Quản lý thợ - Thợ Tốt')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --lumina-primary: #0058be;
        --lumina-primary-container: #2170e4;
        --lumina-surface: #f7f9fb;
        --lumina-surface-container-low: #f2f4f6;
        --lumina-surface-container: #eceef0;
        --lumina-surface-container-highest: #e0e3e5;
        --lumina-surface-container-lowest: #ffffff;
        --lumina-on-surface: #191c1e;
        --lumina-on-surface-variant: #424754;
        --lumina-outline-variant: rgba(194, 198, 214, 0.15);
    }

    body {
        background-color: var(--lumina-surface);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: var(--lumina-on-surface);
    }

    h1, h2, h3, .display-md {
        font-family: 'Manrope', sans-serif;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    /* Architectural Header */
    .page-header {
        padding: 2.5rem 0;
    }

    .breadcrumb-item {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
    }

    .breadcrumb-item a {
        color: var(--lumina-on-surface-variant);
    }

    /* Stats Cards */
    .stat-card {
        background-color: var(--lumina-surface-container-lowest);
        border-radius: 1.25rem;
        padding: 1.5rem;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 88, 190, 0.02);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    /* The Intelligent Canvas - Card */
    .canvas-card {
        background-color: var(--lumina-surface-container-lowest);
        border-radius: 1.5rem;
        border: none;
        box-shadow: 0 10px 30px rgba(0, 88, 190, 0.03);
        overflow: hidden;
    }

    /* Tonal Table Styling */
    .table-tonal thead th {
        background-color: var(--lumina-surface-container-low);
        color: var(--lumina-on-surface-variant);
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        padding: 1.25rem 1.5rem;
        border: none;
    }

    .table-tonal tbody td {
        padding: 1.25rem 1.5rem;
        border-bottom: 8px solid var(--lumina-surface-container-lowest);
        vertical-align: middle;
        background-color: var(--lumina-surface-container-lowest);
        transition: background-color 0.2s ease;
    }

    .table-tonal tbody tr:hover td {
        background-color: var(--lumina-surface-container-low);
    }

    /* Action Buttons */
    .btn-lumina {
        border-radius: 2rem;
        padding: 0.625rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
    }

    .btn-lumina-primary {
        background: linear-gradient(135deg, var(--lumina-primary) 0%, var(--lumina-primary-container) 100%);
        color: #ffffff;
    }

    .btn-lumina-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 88, 190, 0.2);
        color: #ffffff;
    }

    .btn-lumina-secondary {
        background-color: var(--lumina-surface-container-highest);
        color: var(--lumina-on-surface);
    }

    .btn-lumina-icon {
        width: 42px;
        height: 42px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: var(--lumina-surface-container-lowest);
        color: var(--lumina-primary);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    /* Avatar and Labels */
    .worker-avatar {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        object-fit: cover;
    }

    .skill-badge {
        background-color: var(--lumina-surface-container-low);
        color: var(--lumina-on-surface-variant);
        padding: 0.25rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.7rem;
        font-weight: 600;
        margin-right: 0.25rem;
        margin-bottom: 0.25rem;
        display: inline-block;
    }

    /* Form Overrides */
    .form-control-lumina {
        background-color: var(--lumina-surface-container-low);
        border: 2px solid transparent;
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        transition: all 0.2s ease;
    }

    .form-control-lumina:focus {
        background-color: var(--lumina-surface-container-lowest);
        border-color: rgba(0, 88, 190, 0.2);
        box-shadow: none;
    }

    /* Modals - Glassmorphism */
    .modal-lumina .modal-content {
        background-color: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 2rem;
        border: 1px solid var(--lumina-surface-container-lowest);
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container pb-5">
    <!-- Page Header -->
    <header class="page-header">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="/admin/dashboard" class="text-decoration-none">Bảng điều khiển</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Quản lý thợ</li>
                    </ol>
                </nav>
                <h1 class="display-md mb-2">Quản lý thợ</h1>
                <p class="text-muted mb-0">Hệ thống quản lý tài khoản, hồ sơ và phê duyệt thợ kỹ thuật.</p>
            </div>
            <div class="col-lg-5 mt-4 mt-lg-0 text-lg-end">
                <button class="btn btn-lumina btn-lumina-primary" data-bs-toggle="modal" data-bs-target="#workerModal" id="btnAddWorker">
                    <i class="fas fa-plus me-2"></i>Thêm thợ mới
                </button>
            </div>
        </div>
    </header>

    <!-- Stats Overview -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h3 class="h2 mb-1" id="statTotalWorkers">0</h3>
                <p class="text-muted small mb-0">Tổng số thợ</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="h2 mb-1" id="statActiveWorkers">0</h3>
                <p class="text-muted small mb-0">Đang hoạt động</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="h2 mb-1" id="statPendingApproval">0</h3>
                <p class="text-muted small mb-0">Chờ phê duyệt</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-danger-subtle text-danger">
                    <i class="fas fa-user-slash"></i>
                </div>
                <h3 class="h2 mb-1" id="statInactiveWorkers">0</h3>
                <p class="text-muted small mb-0">Đã khóa/Tạm nghỉ</p>
            </div>
        </div>
    </div>

    <!-- Filters & List -->
    <div class="canvas-card">
        <div class="p-4 border-bottom border-light">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="position-relative">
                        <i class="fas fa-search position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
                        <input type="text" class="form-control form-control-lumina ps-5" id="workerSearch" placeholder="Tìm tên, SĐT, Email...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-control-lumina" id="filterStatus">
                        <option value="">Tất cả trạng thái duyệt</option>
                        <option value="da_duyet">Đã duyệt</option>
                        <option value="cho_duyet">Chờ duyệt</option>
                        <option value="tu_choi">Từ chối</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-control-lumina" id="filterActive">
                        <option value="">Tất cả trạng thái tài khoản</option>
                        <option value="1">Đang hoạt động</option>
                        <option value="0">Bị khóa</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-lumina-icon w-100" id="btnRefreshWorkers">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-tonal mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Thợ Kỹ Thuật</th>
                        <th>Liên hệ</th>
                        <th>Dịch vụ & Kinh nghiệm</th>
                        <th>Trạng thái</th>
                        <th class="text-end pe-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="workersTableBody">
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="text-muted mt-2">Đang tải dữ liệu...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Worker Modal -->
<div class="modal fade modal-lumina" id="workerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg">
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <h3 class="modal-title h4 mb-1" id="workerModalLabel">Thêm thợ kỹ thuật mới</h3>
                    <p class="text-muted small mb-0">Cung cấp thông tin tài khoản và hồ sơ năng lực.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="workerForm">
                    <input type="hidden" id="workerId">
                    
                    <div class="row g-4">
                        <!-- Account Info -->
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3 small text-uppercase spacing-1 text-primary">Thông tin tài khoản</h5>
                            <div class="mb-3">
                                <label class="form-label fw-600 small" for="workerName">Họ và tên</label>
                                <input type="text" class="form-control form-control-lumina" id="workerName" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-600 small" for="workerEmail">Email</label>
                                <input type="email" class="form-control form-control-lumina" id="workerEmail" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-600 small" for="workerPhone">Số điện thoại</label>
                                <input type="text" class="form-control form-control-lumina" id="workerPhone" required>
                            </div>
                            <div class="mb-3" id="passwordGroup">
                                <label class="form-label fw-600 small" for="workerPassword">Mật khẩu</label>
                                <input type="password" class="form-control form-control-lumina" id="workerPassword">
                                <small class="text-muted" id="passwordHelp">Để trống nếu không muốn đổi (khi sửa).</small>
                            </div>
                        </div>

                        <!-- Profile Info -->
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3 small text-uppercase spacing-1 text-primary">Hồ sơ năng lực</h5>
                            <div class="mb-3">
                                <label class="form-label fw-600 small" for="workerCCCD">Số CCCD</label>
                                <input type="text" class="form-control form-control-lumina" id="workerCCCD" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-600 small" for="workerAddress">Địa chỉ</label>
                                <input type="text" class="form-control form-control-lumina" id="workerAddress">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-600 small" for="workerExp">Kinh nghiệm</label>
                                <textarea class="form-control form-control-lumina" id="workerExp" rows="3" placeholder="Mô tả kinh nghiệm làm việc..."></textarea>
                            </div>
                        </div>

                        <!-- Skills Selection -->
                        <div class="col-12">
                            <h5 class="fw-bold mb-3 small text-uppercase spacing-1 text-primary">Dịch vụ cung cấp</h5>
                            <div id="skillsSelection" class="d-flex flex-wrap gap-2 p-3 bg-light rounded-4" style="max-height: 200px; overflow-y: auto;">
                                <!-- Skills will be loaded here -->
                                <p class="text-muted small">Đang tải danh sách dịch vụ...</p>
                            </div>
                        </div>

                        <div class="col-12" id="statusGroup" style="display: none;">
                            <div class="canvas-card p-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="fw-bold small text-uppercase spacing-1 mb-0">Trạng thái tài khoản</label>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="workerActive" checked style="width: 2.5em; height: 1.25em;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-lumina btn-lumina-primary w-100 py-3 shadow-sm" id="btnSaveWorker">
                            <i class="fas fa-save me-2"></i>Lưu thông tin thợ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/workers.js') }}"></script>
@endpush
