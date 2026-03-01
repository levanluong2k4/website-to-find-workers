@extends('layouts.app')

@section('title', 'Tất cả thợ trên FindWorker')

@push('styles')
<style>
    .page-header {
        background: linear-gradient(135deg, #F0FDF4 0%, #D1FAE5 50%, #ECFDF5 100%);
        padding: 4rem 0 2rem 0;
        margin-bottom: 2rem;
    }

    .filter-sidebar {
        background: white;
        border-radius: var(--border-radius-lg);
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid rgba(0, 0, 0, 0.05);
        position: sticky;
        top: 80px;
    }

    .filter-title {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 1.25rem;
        margin-bottom: 1.5rem;
        color: var(--bs-secondary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Checkbox & Radio styling */
    .custom-control-label {
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .custom-control-input:checked~.custom-control-label {
        color: var(--bs-primary);
        font-weight: 600;
    }

    /* Worker Card styling */
    .worker-card {
        background: white;
        border-radius: var(--border-radius-lg);
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .worker-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: rgba(16, 185, 129, 0.2);
    }

    .worker-banner {
        height: 80px;
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        position: relative;
    }

    .worker-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 4px solid white;
        object-fit: cover;
        position: absolute;
        bottom: -40px;
        left: 20px;
        background: white;
        box-shadow: var(--shadow-sm);
    }

    .worker-badge-status {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-weight: 600;
        backdrop-filter: blur(4px);
    }

    .status-active {
        background: rgba(255, 255, 255, 0.9);
        color: var(--bs-success);
    }

    .status-busy {
        background: rgba(255, 255, 255, 0.9);
        color: var(--bs-warning);
    }

    .worker-info {
        padding: 50px 20px 20px 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .worker-name {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 1.25rem;
        color: var(--bs-gray-900);
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
    }

    .verified-badge {
        color: var(--bs-primary);
        margin-left: 0.5rem;
    }

    .worker-stats {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px dashed rgba(0, 0, 0, 0.1);
        font-size: 0.9rem;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        color: var(--bs-gray-600);
    }

    .skeleton-box {
        background: #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
        position: relative;
    }

    .skeleton-box::after {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        transform: translateX(-100%);
        background-image: linear-gradient(90deg,
                rgba(255, 255, 255, 0) 0,
                rgba(255, 255, 255, 0.4) 20%,
                rgba(255, 255, 255, 0.4) 60%,
                rgba(255, 255, 255, 0));
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        100% {
            transform: translateX(100%);
        }
    }
</style>
@endpush

@section('content')

<app-navbar></app-navbar>

<div class="page-header">
    <div class="container">
        <h1 class="brand-font fw-extrabold" style="color: var(--bs-secondary);">Tìm Kiếm Chuyên Gia</h1>
        <p class="text-muted fs-5">Kết nối với hơn 10,000 thợ lành nghề trên toàn quốc</p>

        <!-- Search Input Re-use from Home -->
        <div class="row mt-4 justify-content-center">
            <div class="col-lg-10">
                <div class="search-container shadow-sm d-flex bg-white pe-2 py-1 align-items-center" style="border-radius: 50px; border: 1px solid rgba(16,185,129,0.3);">
                    <!-- Nút chọn Vị trí -->
                    <div class="dropdown location-dropdown border-end">
                        <button class="btn dropdown-toggle border-0 focus-ring-0 bg-transparent py-3 ps-3 pe-4 text-start fw-semibold text-dark d-flex align-items-center"
                            type="button" data-bs-toggle="dropdown" aria-expanded="false" style="min-width: 180px;">
                            <svg width="20" height="20" fill="var(--bs-primary)" class="me-2" viewBox="0 0 16 16">
                                <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z" />
                            </svg>
                            <span id="selectedLocationText" class="text-truncate">Toàn quốc</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-start shadow border-0 rounded-4 p-2" style="width: 250px;">
                            <li><button class="dropdown-item rounded py-2 fw-medium text-primary mb-1 d-flex align-items-center" id="btnGetLocation"><svg width="16" height="16" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z" />
                                    </svg> Sử dụng vị trí hiện tại</button></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <h6 class="dropdown-header fw-bold">Hoặc chọn Tỉnh/Thành</h6>
                            </li>
                            <li><button class="dropdown-item rounded py-2 location-item" data-province="Hồ Chí Minh">Hồ Chí Minh</button></li>
                            <li><button class="dropdown-item rounded py-2 location-item" data-province="Hà Nội">Hà Nội</button></li>
                            <li><button class="dropdown-item rounded py-2 location-item" data-province="Đà Nẵng">Đà Nẵng</button></li>
                            <li><button class="dropdown-item rounded py-2 location-item" data-province="Cần Thơ">Cần Thơ</button></li>
                            <li><button class="dropdown-item rounded py-2 location-item" data-province="Hải Phòng">Hải Phòng</button></li>
                        </ul>
                    </div>

                    <span class="input-group-text bg-white border-0 ps-3 pe-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="var(--bs-gray-500)" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" />
                        </svg>
                    </span>
                    <input type="text" id="searchInput" class="search-input form-control border-0 px-2 py-3 bg-transparent" placeholder="Bạn đang tìm thợ sửa chữa gì? (VD: Máy lạnh, Ống nước...)" style="box-shadow: none;">

                    <button class="btn btn-primary px-4 py-2 search-btn rounded-pill fw-bold font-outfit m-1" type="button" id="btnSearch">Tìm Kiếm</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5 pb-5 mt-4">
    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="filter-sidebar">
                <div class="filter-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2h-11z" />
                    </svg>
                    Bộ Lọc Tìm Kiếm
                </div>

                <!-- Sort By -->
                <div class="mb-4">
                    <h6 class="fw-bold mb-3 text-dark">Sắp xếp theo</h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input filter-sort custom-control-input" type="radio" name="sortOrder" id="sortNearest" value="nearest" checked>
                        <label class="form-check-label custom-control-label" for="sortNearest">Gần tôi nhất</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input filter-sort custom-control-input" type="radio" name="sortOrder" id="sortRating" value="rating">
                        <label class="form-check-label custom-control-label" for="sortRating">Đánh giá cao</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input filter-sort custom-control-input" type="radio" name="sortOrder" id="sortJobs" value="jobs">
                        <label class="form-check-label custom-control-label" for="sortJobs">Nhiều việc nhất</label>
                    </div>
                </div>

                <!-- Filter by Category -->
                <div>
                    <h6 class="fw-bold mb-3 text-dark">Danh mục Dịch vụ</h6>
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
                <h4 class="fw-bold m-0" id="resultsCount">Đang tìm kiếm...</h4>
            </div>

            <div class="row g-4" id="workersContainer">
                <!-- Skeleton Loading Items -->
                <div class="col-md-6 col-xl-4 skeleton-item">
                    <div class="worker-card">
                        <div class="worker-banner skeleton-box"></div>
                        <div class="worker-info">
                            <div class="skeleton-box mb-2" style="width: 70%; height: 24px;"></div>
                            <div class="skeleton-box mb-3" style="width: 50%; height: 16px;"></div>
                            <div class="skeleton-box w-100 mt-auto" style="height: 38px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4 skeleton-item">
                    <!-- Similar Skeleton -->
                    <div class="worker-card">
                        <div class="worker-banner skeleton-box"></div>
                        <div class="worker-info">
                            <div class="skeleton-box mb-2" style="width: 80%; height: 24px;"></div>
                            <div class="skeleton-box mb-3" style="width: 60%; height: 16px;"></div>
                            <div class="skeleton-box w-100 mt-auto" style="height: 38px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination Controls -->
            <div class="d-flex justify-content-center mt-5" id="paginationContainer"></div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/customer/search.js') }}"></script>
@endpush