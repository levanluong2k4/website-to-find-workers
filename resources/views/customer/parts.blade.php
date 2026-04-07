@extends('layouts.app')

@section('title', 'Linh kiện & giá tham khảo - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('assets/css/customer/parts.css') }}?v={{ time() }}">
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="parts-showroom">
    <section class="parts-catalog-shell" id="partsCatalog">
        <div class="parts-catalog-head">
            <h1 class="parts-section-title">Bảng giá linh kiện tham khảo</h1>
            <p class="parts-catalog-head__summary">
                Hệ thống cung cấp linh kiện kỹ thuật chính hãng với thông số kỹ thuật chi tiết. Cam kết chất lượng và hỗ trợ lắp đặt tận nơi từ đội ngũ kỹ thuật viên chuyên nghiệp.
            </p>
            <p class="parts-section-note" id="partsResultSummary">Đang tải dữ liệu linh kiện...</p>
        </div>

        <div class="parts-toolbar">
            <div class="parts-toolbar__main">
                <div class="parts-toolbar__row">
                    <form class="parts-catalog-search" id="partsSearchForm">
                        <label for="partsSearchInput" class="parts-visually-hidden">Tìm theo tên linh kiện</label>
                        <div class="parts-catalog-search__field">
                            <span class="material-symbols-outlined">search</span>
                            <input
                                id="partsSearchInput"
                                type="search"
                                placeholder="Nhập tên linh kiện, mã SKU..."
                                autocomplete="off"
                            >
                        </div>
                    </form>

                    <label class="parts-select-wrap" for="partsSortSelect">
                        <span class="parts-visually-hidden">Sắp xếp linh kiện</span>
                        <select id="partsSortSelect" aria-label="Sắp xếp linh kiện">
                            <option value="featured">Mặc định</option>
                            <option value="price_asc">Giá tăng dần</option>
                            <option value="price_desc">Giá giảm dần</option>
                            <option value="name_asc">Tên A-Z</option>
                        </select>
                    </label>
                </div>

                <button type="button" class="parts-toggle-button parts-switch" id="partsPricedToggle" aria-pressed="false">
                    <span class="parts-switch__track" aria-hidden="true">
                        <span class="parts-switch__thumb"></span>
                    </span>
                    <span class="parts-switch__label">Chỉ hiện mục đã có giá</span>
                </button>
            </div>

            <div class="parts-toolbar__filters">
                <label class="parts-select-wrap parts-select-wrap--service" for="partsServiceFilters">
                    <span class="parts-visually-hidden">Lọc theo danh mục linh kiện</span>
                    <select id="partsServiceFilters" aria-label="Lọc theo danh mục linh kiện"></select>
                </label>
            </div>
        </div>

        <div class="parts-results-shell">
            <div class="parts-results-state" id="partsLoadingState">
                <div class="parts-loading-orb"></div>
                <h3>Đang đồng bộ bảng giá linh kiện</h3>
                <p>Hệ thống đang tải danh mục và giá tham khảo để bạn so sánh nhanh.</p>
            </div>

            <div class="parts-results-state" id="partsEmptyState" hidden>
                <span class="material-symbols-outlined">inventory_2</span>
                <h3>Không tìm thấy linh kiện phù hợp</h3>
                <p>Thử đổi từ khóa tìm kiếm hoặc chuyển sang nhóm dịch vụ khác.</p>
            </div>

            <div class="parts-list" id="partsList" hidden></div>
            <div class="parts-pagination" id="partsPagination" hidden></div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/customer/parts.js') }}?v={{ time() }}"></script>
@endpush
