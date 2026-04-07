@extends('layouts.app')

@section('title', 'Qu&#7843;n l&#253; d&#7883;ch v&#7909; - Th&#7907; T&#7889;t')

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

    .service-preview {
        width: 96px;
        height: 96px;
        border-radius: 20px;
        object-fit: cover;
        border: 1px solid #dbeafe;
        background: #f8fafc;
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
                    <li class="breadcrumb-item"><a href="/admin/dashboard" class="text-decoration-none">B&#7843;ng &#273;i&#7873;u khi&#7875;n</a></li>
                    <li class="breadcrumb-item active" aria-current="page">D&#7883;ch v&#7909;</li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-1" style="color:#0f172a;">Qu&#7843;n l&#253; d&#7883;ch v&#7909;</h2>
            <p class="text-muted mb-0">Th&#234;m, s&#7917;a, &#7849;n hi&#7879;n v&#224; x&#243;a d&#7883;ch v&#7909; trong c&#7917;a h&#224;ng.</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <select class="form-select shadow-sm" id="serviceStatusFilter" style="min-width: 180px;">
                <option value="">T&#7845;t c&#7843; tr&#7841;ng th&#225;i</option>
                <option value="1">&#272;ang ho&#7841;t &#273;&#7897;ng</option>
                <option value="0">&#272;&#227; &#7849;n / &#273;&#227; x&#243;a</option>
            </select>
            <button class="btn btn-outline-primary shadow-sm" id="btnRefreshServices">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#serviceModal" id="btnAddService">
                <i class="fas fa-plus me-2"></i>Th&#234;m d&#7883;ch v&#7909;
            </button>
        </div>
    </div>

    <div class="table-responsive table-custom">
        <table class="table mb-0 table-borderless">
            <thead>
                <tr>
                    <th class="ps-4">ID</th>
                    <th>&#7842;nh</th>
                    <th>T&#234;n d&#7883;ch v&#7909;</th>
                    <th>M&#244; t&#7843;</th>
                    <th>Tr&#7841;ng th&#225;i</th>
                    <th class="text-end pe-4">Thao t&#225;c</th>
                </tr>
            </thead>
            <tbody id="servicesTableBody">
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 mb-0">&#272;ang t&#7843;i d&#7883;ch v&#7909;...</p>
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
                <h5 class="modal-title fw-bold" id="serviceModalLabel">Th&#234;m d&#7883;ch v&#7909;</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="serviceForm" class="d-grid gap-3">
                    <input type="hidden" id="serviceId">

                    <div>
                        <label class="form-label fw-semibold" for="serviceName">T&#234;n d&#7883;ch v&#7909;</label>
                        <input type="text" class="form-control" id="serviceName" required maxlength="255">
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="serviceDesc">M&#244; t&#7843;</label>
                        <textarea class="form-control" id="serviceDesc" rows="3"></textarea>
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="serviceImage">H&#236;nh &#7843;nh d&#7883;ch v&#7909;</label>
                        <input type="file" class="form-control" id="serviceImage" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp">
                        <div class="d-flex align-items-center gap-3 mt-3">
                            <img src="{{ asset('assets/images/logontu.png') }}" alt="Xem tr&#432;&#7899;c &#7843;nh d&#7883;ch v&#7909;" class="service-preview" id="serviceImagePreview">
                            <div>
                                <p class="text-muted mb-2 small">T&#7843;i l&#234;n &#7843;nh JPG, PNG, GIF ho&#7863;c WEBP. T&#7889;i &#273;a 5MB.</p>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRemoveServiceImage">X&#243;a &#7843;nh</button>
                            </div>
                        </div>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="serviceActive" checked>
                        <label class="form-check-label" for="serviceActive">&#272;ang ho&#7841;t &#273;&#7897;ng</label>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnSaveService">
                        <i class="fas fa-save me-2"></i>L&#432;u d&#7883;ch v&#7909;
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
