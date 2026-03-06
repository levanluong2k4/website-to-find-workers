@extends('layouts.app')

@section('title', 'Lịch Sử Đặt Thợ')

@push('styles')
<style>
    body {
        background-color: #f8fafc;
    }

    .booking-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .booking-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    }

    .nav-pills .nav-link {
        color: #64748b;
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
        font-weight: 600;
        margin-right: 0.5rem;
    }

    .nav-pills .nav-link.active {
        background-color: var(--bs-primary);
        color: #ffffff;
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
    }

    .status-badge {
        padding: 0.4rem 1rem;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.8rem;
    }

    .status-cho_xac_nhan {
        background-color: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }

    .status-da_xac_nhan {
        background-color: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .status-dang_lam {
        background-color: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }

    .status-cho_hoan_thanh {
        background-color: #f5f3ff;
        color: #6d28d9;
        border: 1px solid #ede9fe;
    }

    .status-da_xong {
        background-color: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }

    .status-da_huy {
        background-color: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    /* Star Rating System */
    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: center;
        gap: 0.5rem;
    }

    .star-rating input {
        display: none;
    }

    .star-rating label {
        font-size: 2.5rem;
        color: #cbd5e1;
        cursor: pointer;
        transition: color 0.2s ease;
    }

    .star-rating input:checked~label,
    .star-rating label:hover,
    .star-rating label:hover~label {
        color: #fbbf24;
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="brand-font fw-bold text-dark mb-1">Lịch Sử Đặt Thợ</h2>
            <p class="text-muted">Xem lại các đơn đặt lịch và đánh giá chất lượng dịch vụ</p>
        </div>
        <a href="/customer/home" class="btn btn-outline-primary rounded-pill fw-bold">
            <i class="fas fa-plus me-1"></i> Đặt Lịch Mới
        </a>
    </div>

    <!-- Tabs Filter -->
    <ul class="nav nav-pills mb-4" id="bookingTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-filter="all" type="button">Tất cả</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-filter="active" type="button">Đang xử lý</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-filter="completed" type="button">Đã hoàn tất</button>
        </li>
    </ul>

    <!-- Bookings List -->
    <div class="row g-4" id="myBookingsContainer">
        <!-- Rendered by JS -->
    </div>
</div>

<!-- Modal Đánh Giá (Review Modal) -->
<div class="modal fade" id="modalReview" tabindex="-1" aria-labelledby="modalReviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalReviewLabel">Đánh giá dịch vụ</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2 text-center">
                <p class="text-muted mb-4" id="reviewWorkerName">Vui lòng đánh giá thợ <strong>Nguyễn Văn A</strong></p>

                <form id="formReview">
                    <input type="hidden" id="reviewBookingId" name="don_dat_lich_id">

                    <!-- 5 Stars Rating -->
                    <div class="star-rating mb-4">
                        <input type="radio" id="star5" name="so_sao" value="5" required />
                        <label for="star5" title="5 Sao"><i class="fas fa-star"></i></label>

                        <input type="radio" id="star4" name="so_sao" value="4" />
                        <label for="star4" title="4 Sao"><i class="fas fa-star"></i></label>

                        <input type="radio" id="star3" name="so_sao" value="3" />
                        <label for="star3" title="3 Sao"><i class="fas fa-star"></i></label>

                        <input type="radio" id="star2" name="so_sao" value="2" />
                        <label for="star2" title="2 Sao"><i class="fas fa-star"></i></label>

                        <input type="radio" id="star1" name="so_sao" value="1" />
                        <label for="star1" title="1 Sao"><i class="fas fa-star"></i></label>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-bold">Nhận xét chi tiết (Tùy chọn)</label>
                        <textarea class="form-control bg-light border-0" id="reviewComment" name="nhan_xet" rows="3" placeholder="Thợ làm việc có nhiệt tình không? Giá cả thế nào..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold" id="btnSubmitReview">Gửi Đánh Giá</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/customer/my-bookings.js') }}"></script>
@endpush