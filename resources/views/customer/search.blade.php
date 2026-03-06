@extends('layouts.app')

@section('title', 'Tất cả thợ trên FindWorker')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
<style>
    /* Modern UI Refactoring from Stitch */
    .page-header {
        background: transparent;
        padding: 2rem 0;
        margin-bottom: 0;
    }

    .search-bar-wrapper {
        background: white;
        padding: 0.5rem;
        border-radius: 50px;
        box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.1), 0 4px 6px -2px rgba(16, 185, 129, 0.05);
        border: 1px solid rgba(16, 185, 129, 0.2);
        display: flex;
        align-items: center;
        width: 100%;
    }

    .filter-sidebar {
        background: white;
        border-radius: 0.75rem;
        padding: 1.5rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(203, 213, 225, 0.5);
        /* slate-200 */
        position: sticky;
        top: 80px;
    }

    .filter-title {
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        font-size: 1rem;
        margin-bottom: 1rem;
        color: #0f172a;
        /* slate-900 */
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Checkbox & Radio styling */
    .custom-control-label {
        font-size: 0.875rem;
        /* text-sm */
        font-weight: 500;
        color: #475569;
        /* slate-600 */
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: color 0.2s;
    }

    .custom-control-input:checked~.custom-control-label {
        color: var(--bs-primary);
        font-weight: 600;
    }

    .custom-control-label:hover {
        color: var(--bs-primary);
    }

    /* Category Filter List Styling */
    .category-filter-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem;
        border-radius: 0.5rem;
        color: #475569;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
        margin-bottom: 0.5rem;
    }

    .category-filter-item:hover {
        background-color: #f8fafc;
    }

    .category-filter-item.active {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--bs-primary);
    }

    /* Worker Card styling (Stitch Refactor) */
    .worker-card {
        background: white;
        border-radius: 0.75rem;
        overflow: hidden;
        border: 1px solid rgba(203, 213, 225, 0.5);
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        color: #475569;
    }

    .worker-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .worker-banner {
        height: 96px;
        /* h-24 */
        background: var(--bs-primary);
        position: relative;
    }

    .worker-avatar-container {
        position: absolute;
        bottom: -40px;
        left: 1rem;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 4px solid white;
        background: #e2e8f0;
        overflow: hidden;
    }

    .worker-avatar {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .worker-info {
        padding: 3rem 1rem 1rem 1rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .worker-name {
        font-weight: 700;
        font-size: 1rem;
        color: #0f172a;
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .verified-badge {
        color: #3b82f6;
        /* blue-500 */
    }

    .worker-service {
        font-size: 0.875rem;
        color: #64748b;
        /* slate-500 */
        margin-bottom: 1rem;
    }

    .worker-stats {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 1rem;
        border-top: 1px solid #f1f5f9;
        /* slate-100 */
        font-size: 0.875rem;
    }

    .stat-rating {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-weight: 700;
        color: #334155;
        /* slate-700 */
    }

    .stat-distance {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        color: #94a3b8;
        /* slate-400 */
    }
</style>
@endpush

@section('content')

<app-navbar></app-navbar>

<div class="page-header pb-0 border-bottom mb-4" style="background-color: transparent;">
    <div class="container pb-3">
        <!-- New Search Bar Design -->
        <div class="search-bar-wrapper">
            <div class="d-flex align-items-center flex-grow-1 px-3 border-end">
                <i class="fas fa-search text-muted me-2"></i>
                <input type="text" id="searchInput" class="form-control border-0 px-2 py-2" placeholder="Từ khóa dịch vụ (Sửa ống nước, máy lạnh...)" style="box-shadow: none; background: transparent;">
            </div>

            <div class="dropdown location-dropdown d-flex align-items-center px-3" style="min-width: 200px;">
                <i class="fas fa-map-marker-alt text-muted me-2"></i>
                <button class="btn dropdown-toggle border-0 shadow-none p-0 text-start w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="selectedLocationText" class="text-truncate" style="color: #0f172a; font-weight: 500;">Hồ Chí Minh</span>
                </button>
                <div class="search-container d-none" data-search-location="Hồ Chí Minh"></div> <!-- Hidden state container -->
                <ul class="dropdown-menu border-0 shadow-sm rounded-4 p-2 w-100 mt-2">
                    <li><button class="dropdown-item rounded py-2 fw-medium text-primary mb-1 d-flex align-items-center" id="btnGetLocation"><i class="fas fa-location-arrow me-2"></i> Sử dụng vị trí của tôi</button></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><button class="dropdown-item rounded py-2 location-item text-secondary font-medium" data-province="Hồ Chí Minh">Hồ Chí Minh</button></li>
                    <li><button class="dropdown-item rounded py-2 location-item text-secondary font-medium" data-province="Hà Nội">Hà Nội</button></li>
                    <li><button class="dropdown-item rounded py-2 location-item text-secondary font-medium" data-province="Đà Nẵng">Đà Nẵng</button></li>
                    <li><button class="dropdown-item rounded py-2 location-item text-secondary font-medium" data-province="Cần Thơ">Cần Thơ</button></li>
                </ul>
            </div>

            <button class="btn btn-primary rounded-pill px-4 py-2 m-1 fw-bold d-flex align-items-center" type="button" id="btnSearch">
                Tìm kiếm
            </button>
        </div>

        <!-- General Booking Button -->
        <div class="mt-3 text-end">
            <button class="btn btn-outline-primary rounded-pill fw-bold bg-white" id="btnGeneralBooking" data-bs-toggle="modal" data-bs-target="#bookingModal">
                <i class="fas fa-bolt text-warning me-1"></i> Đặt Lịch Nhanh (Không cần chọn thợ)
            </button>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4 lg:g-5">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="filter-sidebar">
                <!-- Sort By -->
                <div class="mb-5">
                    <h3 class="filter-title">Sắp xếp theo</h3>
                    <div class="form-check mb-3">
                        <input class="form-check-input filter-sort custom-control-input" type="radio" name="sortOrder" id="sortRating" value="rating" checked>
                        <label class="form-check-label custom-control-label" for="sortRating">Đánh giá cao</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input filter-sort custom-control-input" type="radio" name="sortOrder" id="sortJobs" value="jobs">
                        <label class="form-check-label custom-control-label" for="sortJobs">Nhiều việc nhất</label>
                    </div>
                </div>

                <hr class="text-muted" style="opacity: 0.1; margin: 1.5rem 0;">

                <!-- Filter by Time Slot (Single Store Pivot) -->
                <div class="mb-5">
                    <h3 class="filter-title">Khung giờ rảnh</h3>
                    <div class="mb-3">
                        <label class="form-label text-secondary" style="font-size: 0.875rem;">Ngày hẹn</label>
                        <input type="date" class="form-control" id="filterDate" style="border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary" style="font-size: 0.875rem;">Khung giờ</label>
                        <select class="form-select border-0 shadow-sm" id="filterTimeSlot" style="border-radius: 0.5rem; font-size: 0.875rem;">
                            <option value="">Tất cả</option>
                            <option value="08:00-10:00">08:00 - 10:00</option>
                            <option value="10:00-12:00">10:00 - 12:00</option>
                            <option value="12:00-14:00">12:00 - 14:00</option>
                            <option value="14:00-17:00">14:00 - 17:00</option>
                        </select>
                    </div>
                    <button id="btnApplyTimeFilter" class="btn btn-sm btn-outline-primary w-100 rounded-pill">Áp dụng giờ</button>
                </div>

                <hr class="text-muted" style="opacity: 0.1; margin: 1.5rem 0;">

                <!-- Filter by Category -->
                <div>
                    <h3 class="filter-title">Danh mục</h3>
                    <div id="categoryFiltersList">
                        <!-- Rendered by JS -->
                        <div class="text-center py-2"><span class="spinner-border spinner-border-sm text-primary"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Results Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold m-0" id="resultsCount" style="color: #0f172a;">Đang tải...</h5>
            </div>

            <div class="row g-4" id="workersContainer">
                <!-- Skeletons will be injected here -->
            </div>

            <!-- Pagination Controls -->
            <div class="d-flex justify-content-center mt-5" id="paginationContainer"></div>
        </div>
    </div>
</div>

<!-- Include Booking Modal Component -->
@include('components.booking-modal')

@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/customer/search.js') }}?v={{ time() }}"></script>
@endpush