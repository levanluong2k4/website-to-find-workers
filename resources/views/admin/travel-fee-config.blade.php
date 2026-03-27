@extends('layouts.app')

@section('title', 'Phí đi lại - Admin')

@push('styles')
<style>
    body {
        background: #f8fafc;
    }

    .travel-fee-shell {
        display: grid;
        gap: 1.5rem;
    }

    .travel-fee-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.05);
    }

    .travel-fee-card--soft {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .travel-fee-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 0.9rem;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 700;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .travel-fee-note {
        border-radius: 18px;
        padding: 1rem 1.1rem;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e3a8a;
        font-size: 0.92rem;
    }

    .travel-fee-note strong {
        color: #0f172a;
    }

    .travel-tier-list {
        display: grid;
        gap: 0.85rem;
    }

    .travel-tier-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1.2fr) auto;
        gap: 0.85rem;
        align-items: end;
        padding: 1rem;
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .travel-tier-row__index {
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #dbeafe;
        color: #1d4ed8;
        font-weight: 700;
        font-size: 0.85rem;
        margin-bottom: 0.85rem;
    }

    .travel-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 0.9rem;
    }

    .travel-preview-card {
        padding: 1rem;
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .travel-preview-card__label {
        font-size: 0.82rem;
        color: #64748b;
        margin-bottom: 0.35rem;
    }

    .travel-preview-card__value {
        font-size: 1.15rem;
        font-weight: 800;
        color: #0f172a;
    }

    .travel-preview-list {
        display: grid;
        gap: 0.75rem;
    }

    .travel-preview-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.9rem 1rem;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .travel-preview-item__title {
        font-weight: 700;
        color: #0f172a;
    }

    .travel-preview-item__meta {
        font-size: 0.82rem;
        color: #64748b;
    }

    .travel-tier-empty {
        padding: 1rem;
        border-radius: 18px;
        border: 1px dashed #cbd5e1;
        color: #64748b;
        text-align: center;
        background: #fff;
    }

    @media (max-width: 991.98px) {
        .travel-tier-row {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container py-5">
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="/admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Phí đi lại</li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-1" style="color:#0f172a;">Cấu hình phí đi lại theo khoảng cách</h2>
            <p class="text-muted mb-0">Admin có thể đặt các khoảng km cố định để hệ thống tự áp phí khi khách đặt sửa tại nhà.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="travel-fee-chip" id="travelFeeStatusChip">Đang tải cấu hình...</span>
            <span class="travel-fee-chip d-none" id="travelFeeUpdatedChip"></span>
        </div>
    </div>

    <div class="travel-fee-shell">
        <div class="travel-fee-card travel-fee-card--soft p-4">
            <div class="travel-fee-note">
                <strong>Nguyên tắc áp phí:</strong> hệ thống sẽ ưu tiên các khoảng km bạn cấu hình. Nếu không có khoảng nào khớp,
                hệ thống quay về cách tính mặc định theo <span class="fw-semibold">đơn giá / km</span>.
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-7">
                <div class="travel-fee-card p-4 p-lg-5 h-100">
                    <form id="travelFeeForm" class="d-grid gap-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-7">
                                <label for="travelFeeDefaultPerKm" class="form-label fw-semibold">Đơn giá mặc định ngoài khoảng</label>
                                <div class="input-group">
                                    <input type="number" min="0" step="1000" class="form-control" id="travelFeeDefaultPerKm" placeholder="5000">
                                    <span class="input-group-text">đ / km</span>
                                </div>
                                <div class="form-text">Dùng khi chưa có khoảng phù hợp hoặc admin chưa cấu hình bảng bậc giá.</div>
                            </div>
                            <div class="col-lg-5 d-flex justify-content-lg-end gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="btnResetTravelFeeForm">
                                    Đặt lại 5000 đ/km
                                </button>
                                <button type="button" class="btn btn-outline-primary" id="btnAddTravelTier">
                                    <i class="fas fa-plus me-2"></i>Thêm khoảng
                                </button>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1" style="color:#0f172a;">Bảng khoảng cách</h5>
                                    <p class="text-muted mb-0 small">Mỗi dòng gồm khoảng km và mức phí cố định tương ứng.</p>
                                </div>
                            </div>
                            <div id="travelTierList" class="travel-tier-list"></div>
                        </div>

                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pt-2">
                            <p class="text-muted mb-0 small">
                                Mẹo: bạn có thể đặt các mốc như <span class="fw-semibold">0 - 2 km</span>,
                                <span class="fw-semibold">2 - 5 km</span>, <span class="fw-semibold">5 - 8 km</span>.
                            </p>
                            <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btnSaveTravelFee">
                                <i class="fas fa-save me-2"></i>Lưu cấu hình
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="travel-fee-card p-4 p-lg-5 h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                        <div>
                            <h5 class="fw-bold mb-1" style="color:#0f172a;">Preview áp phí</h5>
                            <p class="text-muted mb-0">Kiểm tra nhanh số tiền hệ thống sẽ áp cho vài mốc khoảng cách thường gặp.</p>
                        </div>
                        <span class="travel-fee-chip" id="travelFeeModeChip">Đơn giá / km</span>
                    </div>

                    <div class="travel-preview-grid mb-4" id="travelFeeSampleGrid"></div>

                    <div class="mb-3">
                        <h6 class="fw-bold mb-2" style="color:#0f172a;">Các khoảng đang áp dụng</h6>
                        <div class="travel-preview-list" id="travelFeeTierPreview"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/travel-fee-config.js') }}"></script>
@endpush
