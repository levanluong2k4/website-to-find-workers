@extends('layouts.app')

@section('title', 'Linh kiện & giá tham khảo - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('assets/css/customer/parts.css') }}?v={{ time() }}">
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="parts-showroom">
    <section class="parts-hero">
        <div class="parts-hero__backdrop"></div>

        <div class="parts-hero__copy">
            <span class="parts-eyebrow">Catalog linh kiện</span>
            <h1 class="parts-hero__title">Xem giá linh kiện trước khi gọi thợ, để bạn chủ động hơn khi sửa chữa.</h1>
            <p class="parts-hero__summary">
                Tra cứu nhanh các linh kiện phổ biến theo từng nhóm dịch vụ. Giá trên trang là mức tham khảo riêng cho linh kiện, chưa bao gồm công thợ và chi phí phát sinh tại hiện trường.
            </p>

            <div class="parts-hero__actions">
                <a href="/customer/booking" class="parts-primary-button">
                    <span class="material-symbols-outlined">bolt</span>
                    Đặt lịch sửa chữa
                </a>
                <a href="#partsCatalog" class="parts-secondary-button">
                    Xem bảng giá
                </a>
            </div>

            <form class="parts-hero__search" id="partsSearchForm">
                <label for="partsSearchInput" class="parts-hero__search-label">Tìm theo tên linh kiện</label>
                <div class="parts-hero__search-wrap">
                    <span class="material-symbols-outlined">search</span>
                    <input
                        id="partsSearchInput"
                        type="search"
                        placeholder="Ví dụ: bo nóng Samsung, quạt dàn lạnh, sensor..."
                        autocomplete="off"
                    >
                </div>
            </form>
        </div>

        <div class="parts-hero__visual">
            <div class="parts-visual-panel">
                <div class="parts-visual-panel__label">Bảng giá nổi bật</div>
                <div class="parts-visual-panel__headline">Kho linh kiện tham khảo theo dịch vụ</div>
                <div class="parts-visual-panel__marquee" id="partsHeroMarquee">
                    <span>Bo mạch</span>
                    <span>Quạt dàn lạnh</span>
                    <span>Sensor</span>
                    <span>Motor đảo gió</span>
                </div>
            </div>

            <div class="parts-metric-strip">
                <div class="parts-metric">
                    <span class="parts-metric__label">Tổng linh kiện</span>
                    <strong class="parts-metric__value" id="partsMetricCount">--</strong>
                </div>
                <div class="parts-metric">
                    <span class="parts-metric__label">Nhóm dịch vụ</span>
                    <strong class="parts-metric__value" id="partsMetricServices">--</strong>
                </div>
                <div class="parts-metric">
                    <span class="parts-metric__label">Có niêm yết giá</span>
                    <strong class="parts-metric__value" id="partsMetricPriced">--</strong>
                </div>
            </div>
        </div>
    </section>

    <section class="parts-control-band" id="partsCatalog">
        <div class="parts-control-band__header">
            <div>
                <span class="parts-section-kicker">Chọn đúng nhóm cần xem</span>
                <h2 class="parts-section-title">Bảng giá linh kiện tham khảo</h2>
            </div>
            <p class="parts-section-note" id="partsResultSummary">Đang tải dữ liệu linh kiện...</p>
        </div>

        <div class="parts-toolbar">
            <div class="parts-filter-pills" id="partsServiceFilters"></div>

            <div class="parts-toolbar__controls">
                <label class="parts-select-wrap" for="partsSortSelect">
                    <span>Sắp xếp</span>
                    <select id="partsSortSelect">
                        <option value="featured">Ưu tiên có giá</option>
                        <option value="price_asc">Giá tăng dần</option>
                        <option value="price_desc">Giá giảm dần</option>
                        <option value="name_asc">Tên A-Z</option>
                    </select>
                </label>

                <button type="button" class="parts-toggle-button" id="partsPricedToggle" aria-pressed="false">
                    Chỉ hiện mục đã có giá
                </button>
            </div>
        </div>

        <p class="parts-toolbar__legend">
            Trạng thái <strong>Giá giảm / Giá tăng / Giá tốt</strong> đang được so sánh theo mặt bằng linh kiện cùng nhóm dịch vụ để khách dễ ước lượng nhanh.
        </p>
    </section>

    <section class="parts-results-shell">
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
    </section>

    <section class="parts-cta">
        <div>
            <span class="parts-section-kicker">Bước tiếp theo</span>
            <h2 class="parts-section-title">Đã xem giá xong, bạn có thể đặt thợ ngay để được báo đúng tình trạng thực tế.</h2>
        </div>

        <div class="parts-cta__actions">
            <a href="/customer/booking" class="parts-primary-button">
                <span class="material-symbols-outlined">build_circle</span>
                Đặt lịch kiểm tra
            </a>
            <a href="/customer/search" class="parts-secondary-button">
                Tìm thợ theo dịch vụ
            </a>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/customer/parts.js') }}?v={{ time() }}"></script>
@endpush
