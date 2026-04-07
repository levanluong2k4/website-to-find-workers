@extends('layouts.app')

@section('title', 'Quan ly linh kien - Tho Tot')

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

    .part-thumb {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        object-fit: cover;
        border: 1px solid #dbeafe;
        background: #fff;
    }

    .part-preview {
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
    <div class="catalog-shell">
        <div class="catalog-toolbar">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="/admin/dashboard" class="text-decoration-none">Bang dieu khien</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Linh kien</li>
                    </ol>
                </nav>
                <h2 class="fw-bold mb-1" style="color:#0f172a;">Quan ly linh kien</h2>
                <p class="text-muted mb-0">Quan ly danh muc linh kien theo tung dich vu de worker bao gia nhanh hon.</p>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                <div class="catalog-switch">
                    <a href="/admin/linh-kien" class="is-active">Linh kien</a>
                    <a href="/admin/trieu-chung">Trieu chung</a>
                    <a href="/admin/huong-xu-ly">Huong xu ly</a>
                </div>
                <input type="search" class="form-control shadow-sm" id="partSearchInput" placeholder="Tim ten linh kien..." style="min-width: 240px;">
                <select class="form-select shadow-sm" id="partServiceFilter" style="min-width: 220px;">
                    <option value="">Tat ca dich vu</option>
                </select>
                <button class="btn btn-outline-primary shadow-sm" id="btnRefreshParts">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button class="btn btn-primary shadow-sm" id="btnAddPart" data-bs-toggle="modal" data-bs-target="#partModal">
                    <i class="fas fa-plus me-2"></i>Them linh kien
                </button>
            </div>
        </div>

        <section class="catalog-stats">
            <article class="catalog-stat">
                <span class="catalog-stat__label">Tong linh kien</span>
                <span class="catalog-stat__value" id="partStatTotal">0</span>
            </article>
            <article class="catalog-stat">
                <span class="catalog-stat__label">Da co gia</span>
                <span class="catalog-stat__value" id="partStatPriced">0</span>
            </article>
        </section>

        <div class="table-responsive table-custom">
            <table class="table mb-0 table-borderless">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Anh</th>
                        <th>Ten linh kien</th>
                        <th>Dich vu</th>
                        <th>Gia tham khao</th>
                        <th>Cap nhat</th>
                        <th class="text-end pe-4">Thao tac</th>
                    </tr>
                </thead>
                <tbody id="partsTableBody">
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="text-muted mt-2 mb-0">Dang tai linh kien...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="partModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-light border-0 px-4 py-3">
                <h5 class="modal-title fw-bold" id="partModalLabel">Them linh kien</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="partForm" class="d-grid gap-3">
                    <input type="hidden" id="partId">

                    <div>
                        <label class="form-label fw-semibold" for="partService">Dich vu</label>
                        <select class="form-select" id="partService" required>
                            <option value="">Chon dich vu</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="partName">Ten linh kien</label>
                        <input type="text" class="form-control" id="partName" required maxlength="255">
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="partPrice">Gia tham khao</label>
                        <input type="number" class="form-control" id="partPrice" min="0" step="1000" placeholder="VD: 350000">
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="partImage">Hinh anh linh kien</label>
                        <input type="file" class="form-control" id="partImage" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp">
                        <div class="d-flex align-items-center gap-3 mt-3">
                            <img src="{{ asset('assets/images/logontu.png') }}" alt="Xem truoc linh kien" class="part-preview" id="partImagePreview">
                            <div>
                                <p class="text-muted mb-2 small">Ho tro JPG, PNG, GIF, WEBP. Toi da 5MB.</p>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRemovePartImage">Xoa anh</button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnSavePart">
                        <i class="fas fa-save me-2"></i>Luu linh kien
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/parts.js') }}"></script>
@endpush
