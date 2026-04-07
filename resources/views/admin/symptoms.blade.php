@extends('layouts.app')

@section('title', 'Quan ly trieu chung - Tho Tot')

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

    .symptom-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.7rem;
        margin: 0 0.35rem 0.35rem 0;
        border-radius: 999px;
        background: #e0f2fe;
        color: #0c4a6e;
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
                        <li class="breadcrumb-item active" aria-current="page">Trieu chung</li>
                    </ol>
                </nav>
                <h2 class="fw-bold mb-1" style="color:#0f172a;">Quan ly trieu chung</h2>
                <p class="text-muted mb-0">Quan ly trieu chung va cac nguyen nhan lien quan cho tung nhom dich vu.</p>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                <div class="catalog-switch">
                    <a href="/admin/linh-kien">Linh kien</a>
                    <a href="/admin/trieu-chung" class="is-active">Trieu chung</a>
                    <a href="/admin/huong-xu-ly">Huong xu ly</a>
                </div>
                <input type="search" class="form-control shadow-sm" id="symptomSearchInput" placeholder="Tim trieu chung hoac nguyen nhan..." style="min-width: 260px;">
                <select class="form-select shadow-sm" id="symptomServiceFilter" style="min-width: 220px;">
                    <option value="">Tat ca dich vu</option>
                </select>
                <button class="btn btn-outline-primary shadow-sm" id="btnRefreshSymptoms">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button class="btn btn-primary shadow-sm" id="btnAddSymptom" data-bs-toggle="modal" data-bs-target="#symptomModal">
                    <i class="fas fa-plus me-2"></i>Them trieu chung
                </button>
            </div>
        </div>

        <section class="catalog-stats">
            <article class="catalog-stat">
                <span class="catalog-stat__label">Tong trieu chung</span>
                <span class="catalog-stat__value" id="symptomStatTotal">0</span>
            </article>
            <article class="catalog-stat">
                <span class="catalog-stat__label">Lien ket nguyen nhan</span>
                <span class="catalog-stat__value" id="symptomStatCauses">0</span>
            </article>
        </section>

        <div class="table-responsive table-custom">
            <table class="table mb-0 table-borderless">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Dich vu</th>
                        <th>Trieu chung</th>
                        <th>Nguyen nhan lien quan</th>
                        <th>Cap nhat</th>
                        <th class="text-end pe-4">Thao tac</th>
                    </tr>
                </thead>
                <tbody id="symptomsTableBody">
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="text-muted mt-2 mb-0">Dang tai trieu chung...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="symptomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-light border-0 px-4 py-3">
                <h5 class="modal-title fw-bold" id="symptomModalLabel">Them trieu chung</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="symptomForm" class="d-grid gap-3">
                    <input type="hidden" id="symptomId">

                    <div>
                        <label class="form-label fw-semibold" for="symptomService">Dich vu</label>
                        <select class="form-select" id="symptomService" required>
                            <option value="">Chon dich vu</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="symptomName">Ten trieu chung</label>
                        <input type="text" class="form-control" id="symptomName" required maxlength="255">
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="symptomCauses">Nguyen nhan lien quan</label>
                        <textarea class="form-control" id="symptomCauses" rows="5" placeholder="Moi dong mot nguyen nhan"></textarea>
                        <p class="text-muted small mt-2 mb-0">Neu nguyen nhan chua ton tai, he thong se tu tao moi va gan vao trieu chung.</p>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnSaveSymptom">
                        <i class="fas fa-save me-2"></i>Luu trieu chung
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/symptoms.js') }}"></script>
@endpush
