@extends('layouts.app')

@section('title', 'Quản lý tài khoản - Thợ Tốt')

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

    .avatar-photo {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: block;
        object-fit: cover;
        background: #e2e8f0;
        flex-shrink: 0;
    }

    .worker-avatar-preview {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .worker-avatar-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: none;
    }

    .worker-avatar-preview.has-image img {
        display: block;
    }

    .worker-avatar-preview.has-image .worker-avatar-preview__fallback {
        display: none;
    }

    .worker-avatar-preview__fallback {
        font-size: 1.125rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
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

    .section-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin: 2rem 0 1rem;
    }

    .section-heading h2 {
        font-size: 1.125rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }

    .account-switcher {
        display: inline-flex;
        gap: 0.25rem;
        padding: 0.25rem;
        background: #e2e8f0;
        border-radius: 0.875rem;
        border: 1px solid #cbd5e1;
    }

    .account-switcher__item {
        border: 0;
        background: transparent;
        color: #475569;
        border-radius: 0.625rem;
        padding: 0.625rem 1rem;
        font-size: 0.875rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .account-switcher__item:hover {
        color: #0f172a;
        background: rgba(255, 255, 255, 0.55);
    }

    .account-switcher__item.active {
        color: #0f172a;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
    }

    .account-panel {
        display: none;
    }

    .account-panel.active {
        display: block;
    }

    .account-panel-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    @media (max-width: 576px) {
        .account-switcher,
        .account-switcher__item {
            width: 100%;
        }

        .account-switcher__item {
            justify-content: center;
        }

        .account-panel-top {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container py-4" style="max-width: 1280px;">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="admin-page-title mb-0">Quản lý tài khoản</h1>
            <p class="admin-page-subtitle mb-0">Quản lý tài khoản admin, thợ, xét duyệt hồ sơ và theo dõi trạng thái đối tác.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn-lumina-secondary" id="btnRefresh" title="Làm mới">
                <i class="fas fa-sync-alt"></i> Làm mới
            </button>
            <button class="btn-lumina-secondary" data-bs-toggle="modal" data-bs-target="#adminModal" id="btnAddAdmin">
                <i class="fas fa-user-shield"></i> Thêm admin
            </button>
            <button class="btn-lumina-primary" data-bs-toggle="modal" data-bs-target="#workerModal" id="btnAddWorker">
                <i class="fas fa-plus"></i> Thêm thợ
            </button>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center gap-3 mb-4 flex-wrap">
        <div class="account-switcher" role="tablist" aria-label="Chuyển loại tài khoản">
            <button type="button" class="account-switcher__item active" data-account-tab="workers" role="tab" aria-selected="true">
                <i class="fas fa-user-cog"></i> Thợ
            </button>
            <button type="button" class="account-switcher__item" data-account-tab="admins" role="tab" aria-selected="false">
                <i class="fas fa-user-shield"></i> Admin
            </button>
        </div>
        <p class="admin-page-subtitle mb-0">Chọn nhóm tài khoản để thao tác nhanh, không cần kéo xuống cuối trang.</p>
    </div>

    <section class="account-panel active" id="workersPanel">
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

    <div class="account-panel-top">
        <div>
            <h2 class="mb-1" style="font-size: 1.125rem; font-weight: 800; color: #0f172a;">Danh sách thợ</h2>
            <p class="admin-page-subtitle mb-0">Theo dõi hồ sơ, trạng thái hoạt động và kỹ năng của thợ.</p>
        </div>
        <button class="btn-lumina-secondary" data-bs-toggle="modal" data-bs-target="#interviewEmailModal" id="btnOpenInterviewEmail">
            <i class="fas fa-envelope"></i> Gửi mail phỏng vấn
        </button>
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
    </section>

    <section class="account-panel" id="adminsPanel">
    <div class="account-panel-top">
        <div>
            <h2 class="mb-1" style="font-size: 1.125rem; font-weight: 800; color: #0f172a;">Tài khoản admin</h2>
            <p class="admin-page-subtitle mb-0">Tạo admin mới và khóa hoặc mở khóa tài khoản quản trị.</p>
        </div>
        <button class="btn-lumina-primary" data-bs-toggle="modal" data-bs-target="#adminModal">
            <i class="fas fa-user-shield"></i> Tạo admin
        </button>
    </div>

    <div class="table-responsive table-container">
        <table class="table table-borderless table-lumina">
            <thead>
                <tr>
                    <th class="ps-4">UID</th>
                    <th>Admin</th>
                    <th>Liên hệ</th>
                    <th>Ngày tạo</th>
                    <th>Trạng thái</th>
                    <th class="text-end pe-4">Thao tác</th>
                </tr>
            </thead>
            <tbody id="adminsTableBody">
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 mb-0">Đang tải danh sách admin...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    </section>
</div>

<!-- Interview Email Modal -->
<div class="modal fade" id="interviewEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">Gửi email phỏng vấn thợ</h5>
                    <p class="admin-page-subtitle mb-0">Chọn thợ theo trạng thái duyệt, chỉnh tiêu đề và nội dung trước khi gửi.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="interviewEmailForm">
                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="d-flex justify-content-between align-items-end gap-3 mb-3">
                                <div class="flex-grow-1">
                                    <label class="form-label small fw-semibold">Lọc theo trạng thái</label>
                                    <select class="form-select" id="interviewStatusFilter">
                                        <option value="cho_duyet">Chưa duyệt</option>
                                        <option value="da_duyet">Đã duyệt</option>
                                        <option value="">Tất cả</option>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-light border" id="btnSelectAllInterviewWorkers">Chọn tất cả</button>
                            </div>
                            <div class="border rounded bg-light p-3" style="max-height: 420px; overflow: auto;" id="interviewWorkerList">
                                <p class="text-muted small mb-0">Đang tải danh sách thợ...</p>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Tiêu đề</label>
                                <input type="text" class="form-control" id="interviewEmailSubject" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nội dung</label>
                                <textarea class="form-control" id="interviewEmailBody" rows="12" required></textarea>
                                <small class="text-muted">Có thể dùng biến: {name}, {email}, {phone}, {approval_status}. Nội dung bạn sửa sẽ được lưu làm mặc định trên trình duyệt này.</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center gap-3 flex-wrap">
                        <span class="text-muted small" id="interviewSelectedCount">Chưa chọn thợ nào</span>
                        <div>
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary px-4" id="btnSendInterviewEmail">
                                <i class="fas fa-paper-plane"></i> Gửi email
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Admin Modal -->
<div class="modal fade" id="adminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Tạo tài khoản admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="adminForm">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Họ và tên</label>
                        <input type="text" class="form-control" id="adminName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email</label>
                        <input type="email" class="form-control" id="adminEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Số điện thoại</label>
                        <input type="text" class="form-control" id="adminPhone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Mật khẩu</label>
                        <input type="password" class="form-control" id="adminPassword" minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Xác nhận mật khẩu</label>
                        <input type="password" class="form-control" id="adminPasswordConfirmation" minlength="6" required>
                    </div>
                    <div class="form-check form-switch border p-3 rounded bg-light">
                        <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="adminActive" checked style="width: 2.5em;">
                        <label class="form-check-label fw-bold">Tài khoản hoạt động</label>
                    </div>
                    <div class="mt-4 pt-3 border-top text-end">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary px-4" id="btnSaveAdmin">Tạo admin</button>
                    </div>
                </form>
            </div>
        </div>
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
                            <div class="mb-3" id="passwordConfirmGroup">
                                <label class="form-label small fw-semibold">Xác nhận mật khẩu</label>
                                <input type="password" class="form-control" id="workerPasswordConfirmation">
                                <small class="text-muted" id="passwordConfirmHelp">Nhập lại mật khẩu để tránh sai sót.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Ảnh đại diện</label>
                                <input type="file" class="form-control" id="workerAvatar" accept="image/*">
                                <div class="d-flex align-items-center gap-3 mt-3">
                                    <div class="worker-avatar-preview" id="workerAvatarPreview">
                                        <img id="workerAvatarPreviewImage" alt="Avatar preview">
                                        <span class="worker-avatar-preview__fallback" id="workerAvatarPreviewFallback">TT</span>
                                    </div>
                                    <div class="small text-muted">
                                        áº¢nh hiá»‡n táº¡i hoáº·c áº£nh báº¡n vá»«a chá»n sáº½ hiá»‡n á»Ÿ Ä‘Ã¢y.
                                    </div>
                                </div>
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
<script type="module" src="{{ asset('assets/js/admin/users.js') }}?v={{ filemtime(public_path('assets/js/admin/users.js')) }}"></script>
@endpush

