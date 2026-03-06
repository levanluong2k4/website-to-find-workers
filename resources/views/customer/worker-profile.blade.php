@extends('layouts.app')

@section('title', 'Thông Tin Chuyên Gia')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
<style>
    .profile-header {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
        padding: 4rem 0 2rem 0;
        margin-bottom: 2rem;
    }

    .profile-avatar-container {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid white;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin: -40px auto 1rem auto;
        background: white;
    }

    .profile-avatar {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .sticky-booking-card {
        position: sticky;
        top: 100px;
        z-index: 10;
        background: white;
        border-radius: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        padding: 1.5rem;
        border: 1px solid rgba(203, 213, 225, 0.5);
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="profile-header">
    <div class="container text-center">
        <h1 class="fw-bold mb-0" style="color: #0f172a;" id="workerName">Đang tải...</h1>
        <p class="text-muted mt-2" id="workerShortDesc">Xin vui lòng đợi trong giây lát</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4 lg:g-5">
        <!-- Main Info and Reviews -->
        <div class="col-lg-8">
            <div class="bg-white rounded-4 p-4 shadow-sm border mb-4">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        <div class="profile-avatar-container m-0" style="width: 80px; height: 80px;">
                            <img src="/assets/images/user-default.png" class="profile-avatar" id="workerAvatar">
                        </div>
                        <div>
                            <h3 class="fw-bold mb-1" id="workerNameDetail">Thợ</h3>
                            <div class="d-flex align-items-center gap-3 text-secondary" style="font-size: 0.9rem;">
                                <span class="d-flex align-items-center gap-1">
                                    <span class="material-symbols-outlined text-warning" style="font-size: 18px; font-variation-settings: 'FILL' 1;">star</span>
                                    <strong class="text-dark" id="workerRating">0.0</strong> (<span id="workerReviewCount">0</span> đánh giá)
                                </span>
                                <span class="d-flex align-items-center gap-1" id="workerStatusBadge">
                                    <!-- Status Badge Here -->
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-3" style="color: #0f172a;">Giới thiệu kỹ năng & Kinh nghiệm</h5>
                    <p class="text-secondary" id="workerExperience" style="line-height: 1.6;">Đang tải thông tin kinh nghiệm...</p>
                </div>

                <div>
                    <h5 class="fw-bold mb-3" style="color: #0f172a;">Dịch vụ cung cấp</h5>
                    <div id="workerServices" class="d-flex flex-wrap gap-2">
                        <!-- Services dynamically loaded -->
                        <span class="badge bg-light text-dark fw-normal border px-3 py-2">Đang tải...</span>
                    </div>
                </div>
            </div>

            <!-- Reviews Section -->
            <div class="bg-white rounded-4 p-4 shadow-sm border">
                <h5 class="fw-bold mb-4" style="color: #0f172a;">Đánh giá từ khách hàng</h5>
                <div id="reviewsContainer">
                    <div class="text-center text-muted py-4">Đang tải đánh giá...</div>
                </div>
            </div>
        </div>

        <!-- Sticky Booking Sidebar -->
        <div class="col-lg-4">
            <div class="sticky-booking-card">
                <h4 class="fw-bold mb-3">Đặt Lịch Ngay</h4>
                <p class="text-muted" style="font-size: 0.9rem; line-height: 1.5;">Chọn chuyên gia này để giải quyết gọn gàng các sự cố điện máy của bạn trong khung giờ ưu thích.</p>

                <hr class="my-4" style="opacity: 0.1;">

                <button class="btn btn-primary w-100 py-3 rounded-pill fw-bold text-lg d-flex align-items-center justify-content-center" id="btnOpenBookingModal" data-bs-toggle="modal" data-bs-target="#bookingModal">
                    <span class="material-symbols-outlined me-2">calendar_add_on</span> Đặt Lịch Sửa Chữa
                </button>
                <div class="text-center mt-3">
                    <small class="text-muted"><i class="fas fa-shield-alt text-success me-1"></i> Đảm bảo chất lượng từ Cửa hàng</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Global Booking Modal -->
@include('components.booking-modal')

@endsection

@push('scripts')
<script>
    // Pass worker ID from Laravel routing to JS
    window.WORKER_ID = parseInt("{{ $workerId }}", 10);
</script>
<script type="module" src="{{ asset('assets/js/customer/worker-profile.js') }}?v={{ time() }}"></script>
@endpush