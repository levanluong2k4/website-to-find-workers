@extends('layouts.app')

@section('title', 'Đánh Giá Của Tôi - Thợ Tốt')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
<style>
    body {
        background-color: #f8fafc;
    }

    .header-banner {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
        padding: 3rem 0;
        margin-bottom: 2rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .rating-summary-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .big-rating {
        font-size: 4rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
    }

    .stars-display .material-symbols-outlined {
        font-variation-settings: 'FILL' 1;
        color: #fbbf24;
        font-size: 2rem;
    }

    .review-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.5rem;
        transition: all 0.3s ease;
        margin-bottom: 1rem;
    }

    .review-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .rating-stars {
        color: #fbbf24;
    }

    .rating-stars i {
        font-size: 0.9rem;
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="header-banner">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fw-bold text-dark mb-2" style="letter-spacing: -0.5px;">Đánh Giá Của Khách Hàng</h1>
                <p class="text-secondary mb-0 fs-5">Xem nhận xét và độ hài lòng của khách sau khi sửa chữa.</p>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row">
        <!-- Sidebar or Header Summary -->
        <div class="col-lg-4 mb-4">
            <div class="rating-summary-card sticky-top" style="top: 100px;">
                <h5 class="fw-bold text-dark mb-4">Điểm trung bình</h5>
                <div class="big-rating mb-2" id="avgRatingValue">0.0</div>
                <div class="stars-display mb-3" id="avgRatingStars">
                    <span class="material-symbols-outlined text-muted" style="font-variation-settings: 'FILL' 0;">star</span>
                    <span class="material-symbols-outlined text-muted" style="font-variation-settings: 'FILL' 0;">star</span>
                    <span class="material-symbols-outlined text-muted" style="font-variation-settings: 'FILL' 0;">star</span>
                    <span class="material-symbols-outlined text-muted" style="font-variation-settings: 'FILL' 0;">star</span>
                    <span class="material-symbols-outlined text-muted" style="font-variation-settings: 'FILL' 0;">star</span>
                </div>
                <div class="text-secondary">Dựa trên <span class="fw-bold text-dark" id="totalReviewCount">0</span> bài đánh giá</div>
            </div>
        </div>

        <!-- Review List -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-dark mb-0">Tất cả nhận xét</h5>
            </div>

            <div id="reviewsListContainer">
                <!-- Skeleton Loading -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            </div>

            <!-- Pagination Controls -->
            <div id="paginationControls" class="d-flex justify-content-center mt-4 d-none">
                <nav aria-label="Page navigation">
                    <ul class="pagination mb-0" id="paginationList">
                        <!-- Render by JS -->
                    </ul>
                </nav>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/worker/reviews.js') }}?v={{ time() }}"></script>
@endpush