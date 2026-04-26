@extends('layouts.app')

@section('title', 'Dịch vụ & Thợ - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('assets/css/customer/search.css') }}?v={{ time() }}">
@endpush

@section('content')
<!-- Keep existing navbar as requested -->
<app-navbar></app-navbar>

<div class="search-premium-experience">
    <!-- Sidebar / Filter Portal -->
    <aside class="search-sidebar-portal" id="searchFilters">
        <div class="sidebar-header">
            <h2>Lọc Kết Quả</h2>
            <p>Refine your selection</p>
        </div>

        <!-- Sắp xếp -->
        <div class="filter-section">
            <label class="section-label">Sắp xếp</label>
            <div class="sort-toggle-group">
                <input class="filter-sort d-none" type="radio" name="sortOrder" id="sortPopular" value="jobs" checked>
                <label for="sortPopular" class="sort-btn">Phổ biến nhất</label>
                
                <input class="filter-sort d-none" type="radio" name="sortOrder" id="sortRating" value="rating">
                <label for="sortRating" class="sort-btn">Đánh giá cao</label>
                
                <!-- We still keep "value" in the DOM if needed, or JS will handle it -->
                <input class="filter-sort d-none" type="radio" name="sortOrder" id="sortValue" value="value">
                <label for="sortValue" class="sort-btn">Giá tốt</label>
            </div>
        </div>

        <!-- Thời gian rảnh -->
        <div class="filter-section">
            <label class="section-label">Thời gian rảnh</label>
            <div class="schedule-inputs">
                <div class="input-with-icon">
                    <span class="material-symbols-outlined icon">calendar_today</span>
                    <input type="date" class="premium-input form-control" id="filterDate">
                </div>
                <div class="input-with-icon">
                    <span class="material-symbols-outlined icon">schedule</span>
                    <select class="premium-input form-select" id="filterTimeSlot">
                        <option value="">Chọn khung giờ</option>
                    </select>
                </div>
            </div>
            <button id="btnApplyTimeFilter" class="btn-apply-schedule" type="button">
                Áp dụng lịch hẹn
            </button>
        </div>

        <!-- Danh mục -->
        <div class="filter-section flex-grow-1">
            <label class="section-label">Danh mục</label>
            <div id="categoryFiltersList" class="category-chip-wrap">
                <!-- JS will render chips here -->
                <div class="w-100 text-center py-2">
                    <span class="spinner-border spinner-border-sm text-primary"></span>
                </div>
            </div>
        </div>

        <button class="btn-reset-filters" id="btnResetSearchFilters" type="button">
            Xóa toàn bộ bộ lọc
        </button>
    </aside>

    <!-- Main Results Section -->
    <main class="search-main-results">
        <div class="results-header">
            <div class="header-content">
                <h1 class="results-title font-headline" id="resultsCount">Đang tải danh sách thợ</h1>
                <p class="results-subtitle d-none" id="resultsSubline">Hệ thống đang tìm kiếm...</p>
                
                <div class="active-filters-container" id="activeFiltersSection" style="display: none;">
                    <div class="filter-chips" id="activeFilterChips">
                        <!-- JS renders active filters -->
                    </div>
                </div>
            </div>
            <button class="btn-quick-book" id="btnGeneralBooking" type="button" onclick="window.BookingWizardModal?.open ? window.BookingWizardModal.open() : (window.location.href='{{ route('customer.booking') }}')">
                <span class="material-symbols-outlined">bolt</span>
                Đặt Lịch Nhanh
            </button>
        </div>

        <!-- Worker Grid -->
        <div class="worker-premium-grid" id="workersContainer">
            <!-- JS renders workers here -->
        </div>

        <!-- Pagination -->
        <div class="premium-pagination" id="paginationContainer">
            <!-- JS renders pagination -->
        </div>
    </main>
</div>

@include('customer.partials.booking-wizard-modal')
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/customer/search.js') }}?v={{ time() }}"></script>
@endpush
