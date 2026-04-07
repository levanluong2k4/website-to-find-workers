@extends('layouts.app')

@section('title', 'Quan ly huong xu ly - Tho Tot')

@push('styles')
<style>
    body {
        background: #f8fafc;
    }

    .catalog-shell {
        display: grid;
        gap: 1.25rem;
    }

    .catalog-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .catalog-switch {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .catalog-switch a {
        border-radius: 999px;
        padding: 0.65rem 1rem;
        text-decoration: none;
        color: #334155;
        font-weight: 600;
    }

    .catalog-switch a.is-active {
        background: #0f172a;
        color: #fff;
    }

    .catalog-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
    }

    .catalog-stat {
        background: #fff;
        border-radius: 18px;
        padding: 1rem 1.1rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .catalog-stat__label {
        display: block;
        font-size: 0.82rem;
        color: #64748b;
        margin-bottom: 0.45rem;
    }

    .catalog-stat__value {
        display: block;
        font-size: 1.7rem;
        font-weight: 700;
        color: #0f172a;
    }

    .table-custom {
        background: #fff;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }

    .table-custom th {
        background: #f1f5f9;
        color: #475569;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        padding: 1rem;
    }

    .table-custom td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #eef2f7;
        color: #0f172a;
    }

    .resolution-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.7rem;
        margin: 0 0.35rem 0.35rem 0;
        border-radius: 999px;
        background: #fef3c7;
        color: #92400e;
        font-size: 0.82rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container py-5">
    <div class="catalog-shell">
        <div class="catalog-toolbar">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="/admin/dashboard" class="text-decoration-none">Bang dieu khien</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Huong xu ly</li>
                    </ol>
                </nav>
                <h2 class="fw-bold mb-1" style="color:#0f172a;">Quan ly huong xu ly</h2>
                <p class="text-muted mb-0">Quan ly bang gia tham khao va mo ta cong viec cho tung nguyen nhan sua chua.</p>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                <div class="catalog-switch">
                    <a href="/admin/linh-kien">Linh kien</a>
                    <a href="/admin/trieu-chung">Trieu chung</a>
                    <a href="/admin/huong-xu-ly" class="is-active">Huong xu ly</a>
                </div>
                <input type="search" class="form-control shadow-sm" id="resolutionSearchInput" placeholder="Tim huong xu ly..." style="min-width: 240px;">
                <select class="form-select shadow-sm" id="resolutionServiceFilter" style="min-width: 220px;">
                    <option value="">Tat ca dich vu</option>
                </select>
                <select class="form-select shadow-sm" id="resolutionCauseFilter" style="min-width: 240px;">
                    <option value="">Tat ca nguyen nhan</option>
                </select>
                <button class="btn btn-outline-primary shadow-sm" id="btnRefreshResolutions">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button class="btn btn-primary shadow-sm" id="btnAddResolution" data-bs-toggle="modal" data-bs-target="#resolutionModal">
                    <i class="fas fa-plus me-2"></i>Them huong xu ly
                </button>
            </div>
        </div>

        <section class="catalog-stats">
            <article class="catalog-stat">
                <span class="catalog-stat__label">Tong huong xu ly</span>
                <span class="catalog-stat__value" id="resolutionStatTotal">0</span>
            </article>
            <article class="catalog-stat">
                <span class="catalog-stat__label">Da co gia tham khao</span>
                <span class="catalog-stat__value" id="resolutionStatPriced">0</span>
            </article>
        </section>

        <div class="table-responsive table-custom">
            <table class="table mb-0 table-borderless">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Dich vu</th>
                        <th>Nguyen nhan</th>
                        <th>Huong xu ly</th>
                        <th>Gia tham khao</th>
                        <th>Mo ta cong viec</th>
                        <th class="text-end pe-4">Thao tac</th>
                    </tr>
                </thead>
                <tbody id="resolutionsTableBody">
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="text-muted mt-2 mb-0">Dang tai huong xu ly...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="resolutionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-light border-0 px-4 py-3">
                <h5 class="modal-title fw-bold" id="resolutionModalLabel">Them huong xu ly</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="resolutionForm" class="d-grid gap-3">
                    <input type="hidden" id="resolutionId">

                    <div>
                        <label class="form-label fw-semibold" for="resolutionCause">Nguyen nhan</label>
                        <select class="form-select" id="resolutionCause" required>
                            <option value="">Chon nguyen nhan</option>
                        </select>
                        <p class="text-muted small mt-2 mb-0" id="resolutionCauseMeta">Chon nguyen nhan da duoc tao tu danh muc trieu chung.</p>
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="resolutionName">Ten huong xu ly</label>
                        <input type="text" class="form-control" id="resolutionName" required maxlength="255">
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="resolutionPrice">Gia tham khao</label>
                        <input type="number" class="form-control" id="resolutionPrice" min="0" step="1000" placeholder="VD: 450000">
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="resolutionDescription">Mo ta cong viec</label>
                        <textarea class="form-control" id="resolutionDescription" rows="5" placeholder="Mo ta cong viec can lam, vat tu, quy trinh..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnSaveResolution">
                        <i class="fas fa-save me-2"></i>Luu huong xu ly
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/resolutions.js') }}"></script>
@endpush
