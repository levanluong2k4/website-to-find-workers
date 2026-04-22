@extends('layouts.app')

@section('title', 'Qu&#7843;n l&#253; d&#7883;ch v&#7909; - Th&#7907; T&#7889;t')

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

    /* The Intelligent Canvas - Card */
    .canvas-card {
        background-color: var(--lumina-surface-container-lowest);
        border-radius: 1.5rem; /* xl */
        border: none;
        box-shadow: 0 10px 30px rgba(0, 88, 190, 0.03);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
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
        border-bottom: 8px solid var(--lumina-surface-container-lowest); /* Vertical spacing instead of lines */
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

    /* Thumbnails */
    .service-thumb {
        width: 56px;
        height: 56px;
        border-radius: 1rem;
        object-fit: cover;
        background-color: var(--lumina-surface-container);
        padding: 2px;
    }

    .service-preview {
        width: 120px;
        height: 120px;
        border-radius: 1.5rem;
        object-fit: cover;
        background-color: var(--lumina-surface-container-low);
        border: 2px dashed var(--lumina-surface-container);
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
        background-color: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        border-radius: 2rem;
        border: 1px solid var(--lumina-surface-container-lowest);
    }

    /* Custom Badges (Override Bootstrap) */
    .badge.bg-success-subtle { background-color: #d1fae5 !important; color: #065f46 !important; }
    .badge.bg-secondary-subtle { background-color: #f1f5f9 !important; color: #475569 !important; }
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
                        <li class="breadcrumb-item"><a href="/admin/dashboard" class="text-decoration-none">B&#7843;ng &#273;i&#7873;u khi&#7875;n</a></li>
                        <li class="breadcrumb-item active" aria-current="page">D&#7883;ch v&#7909;</li>
                    </ol>
                </nav>
                <h1 class="display-md mb-2">Qu&#7843;n l&#253; d&#7883;ch v&#7909;</h1>
                <p class="text-muted mb-0">H&#7873; th&#7889;ng qu&#7843;n tr&#7883; v&#224; c&#7845;u h&#236;nh danh m&#7909;c d&#7883;ch v&#7909; s&#7917;a ch&#7919;a thi&#7873;t b&#7883;.</p>
            </div>
            <div class="col-lg-5 mt-4 mt-lg-0">
                <div class="d-flex flex-wrap justify-content-lg-end align-items-center gap-3">
                    <div class="position-relative">
                        <select class="form-select form-control-lumina ps-3 pe-5" id="serviceStatusFilter" style="min-width: 200px;">
                            <option value="">T&#7845;t c&#7843; tr&#7841;ng th&#225;i</option>
                            <option value="1">&#272;ang ho&#7841;t &#273;&#7897;ng</option>
                            <option value="0">&#272;&#227; &#7849;n</option>
                        </select>
                    </div>
                    <button class="btn btn-lumina-icon" id="btnRefreshServices" title="L&#224;m m&#7893;i">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="btn btn-lumina btn-lumina-primary" data-bs-toggle="modal" data-bs-target="#serviceModal" id="btnAddService">
                        <i class="fas fa-plus me-2"></i>Th&#234;m d&#7883;ch v&#7909;
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Content Canvas -->
    <div class="canvas-card">
        <div class="table-responsive">
            <table class="table table-tonal mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>D&#7883;ch v&#7909;</th>
                        <th>Th&#244;ng tin chi ti&#7873;t</th>
                        <th>Tr&#7841;ng th&#225;i</th>
                        <th class="text-end pe-4">Thao t&#225;c</th>
                    </tr>
                </thead>
                <tbody id="servicesTableBody">
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                            <p class="text-muted mt-3 fw-medium">&#272;ang t&#7843;i d&#7883;ch v&#7909;...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade modal-lumina" id="serviceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <h3 class="modal-title h4 mb-1" id="serviceModalLabel">Th&#234;m d&#7883;ch v&#7909; m&#7893;i</h3>
                    <p class="text-muted small mb-0">Nh&#7853;p th&#244;ng tin chi ti&#7873;t &#273;&#7875; kh&#7903;i t&#7841;o d&#7883;ch v&#7909; h&#7873; th&#7889;ng.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="serviceForm" class="d-grid gap-4">
                    <input type="hidden" id="serviceId">

                    <div class="form-group">
                        <label class="form-label fw-bold small text-uppercase spacing-1 mb-2" for="serviceName">T&#234;n d&#7883;ch v&#7909;</label>
                        <input type="text" class="form-control form-control-lumina" id="serviceName" placeholder="VD: S&#7917;a t&#7911; l&#7841;nh" required maxlength="255">
                    </div>

                    <div class="form-group">
                        <label class="form-label fw-bold small text-uppercase spacing-1 mb-2" for="serviceDesc">M&#244; t&#7843; chi ti&#7873;t</label>
                        <textarea class="form-control form-control-lumina" id="serviceDesc" rows="3" placeholder="Nh&#7853;p m&#244; t&#7843; ng&#7855;n g&#7885;n v&#7873; d&#7883;ch v&#7909;..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label fw-bold small text-uppercase spacing-1 mb-2">H&#236;nh &#7843;nh minh h&#7885;a</label>
                        <div class="canvas-card p-3 mb-2" style="background-color: var(--lumina-surface-container-low); border-radius: 1.25rem;">
                            <div class="row g-3 align-items-center">
                                <div class="col-auto">
                                    <div class="position-relative">
                                        <img src="{{ asset('assets/images/logontu.png') }}" alt="Xem tr&#432;&#7899;c" class="service-preview shadow-sm" id="serviceImagePreview">
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="ps-2">
                                        <p class="fw-bold small mb-2">T&#7843;i &#7843;nh l&#234;n</p>
                                        <input type="file" class="form-control d-none" id="serviceImage" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp">
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-lumina btn-lumina-primary px-3" onclick="document.getElementById('serviceImage').click()">
                                                <i class="fas fa-camera me-2"></i>Ch&#7885;n file
                                            </button>
                                            <button type="button" class="btn btn-sm btn-lumina-secondary px-3" id="btnRemoveServiceImage">
                                                X&#243;a
                                            </button>
                                        </div>
                                        <p class="text-muted mb-0 mt-2" style="font-size: 0.7rem;">H&#7895; tr&#7903; JPG, PNG, WEBP (Max 5MB).</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="canvas-card p-3" style="background-color: var(--lumina-surface-container-low); border-radius: 1.25rem;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="fw-bold small text-uppercase spacing-1 mb-0" for="serviceActive">Tr&#7841;ng th&#225;i ho&#7841;t &#273;&#7897;ng</label>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="serviceActive" checked style="width: 2.5em; height: 1.25em; cursor: pointer;">
                                </div>
                            </div>
                            <p class="text-muted small mb-0">Cho ph&#233;p kh&#221;ch h&#224;ng &#273;&#7863;t d&#7883;ch v&#7909; n&#224;y ngay l&#7853;p t&#7913;c.</p>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn btn-lumina btn-lumina-primary w-100 py-3 shadow-sm" id="btnSaveService">
                            <i class="fas fa-check-circle me-2"></i>L&#432;u thay &#273;&#7893;i d&#7883;ch v&#7909;
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
