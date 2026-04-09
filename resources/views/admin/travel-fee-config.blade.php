@extends('layouts.app')

@section('title', 'Phí đi lại - Admin')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/admin/travel-fee-config.css') }}">
@endpush

@section('content')
<app-navbar></app-navbar>
<div class="tfc-shell tfc-shell--no-sidebar">
    <div class="tfc-main-shell">
        <header class="tfc-top">
            <div class="tfc-top-left">
                <nav aria-label="breadcrumb" class="tfc-breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <a href="#">Settings</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Shipping</span>
                </nav>
                <h3>Cấu hình phí vận chuyển</h3>
            </div>
            <div class="tfc-top-right">
                <div class="tfc-status-chips">
                    <span class="tfc-chip tfc-chip--info" id="travelFeeStatusChip">Đang tải cấu hình...</span>
                    <span class="tfc-chip tfc-chip--neutral d-none" id="travelFeeUpdatedChip"></span>
                </div>
                <div class="tfc-user-pill">
                    <div class="tfc-avatar">AD</div>
                    <div>
                        <strong>Admin User</strong>
                        <span>System Manager</span>
                    </div>
                </div>
            </div>
        </header>

        <main class="tfc-content">
            <section class="tfc-page-head">
                <div class="tfc-page-head__text">
                    <div class="tfc-eyebrow">
                        <i class="fa-solid fa-truck-fast"></i>
                        Distance-Based Pricing
                    </div>
                    <h1>Cấu hình phí vận chuyển theo khoảng cách</h1>
                    <p>Quản lý từng khoảng km với hai mức phí riêng: phí thuê xe và phí đi lại. Preview sẽ đổi realtime để admin nhìn ngay rule đang áp dụng.</p>
                </div>
                <div class="tfc-page-head__actions">
                    <button type="button" class="tfc-btn tfc-btn--secondary" id="btnResetTravelFeeForm">
                        <i class="fa-solid fa-rotate-left"></i>
                        Đặt lại
                    </button>
                    <button type="button" class="tfc-btn tfc-btn--primary" id="btnSaveTravelFee" form="travelFeeForm">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Lưu cấu hình
                    </button>
                </div>
            </section>

            <div class="tfc-info-banner">
                <div class="tfc-info-banner__icon">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <div class="tfc-info-banner__body">
                    <strong>Nguyên tắc áp phí</strong>
                    <p>Mỗi khoảng cách có thể mang hai mức phí: phí thuê xe và phí đi lại. Booking tại nhà ưu tiên phí đi lại theo khoảng, còn các luồng đang dùng phí thuê xe sẽ tiếp tục lấy từ cấu hình hệ thống hiện có để giữ tương thích.</p>
                </div>
            </div>

            <div class="tfc-workspace">
                <section class="tfc-card tfc-card--config">
                    <form id="travelFeeForm" novalidate>
                        <div class="tfc-form-section">
                            <div class="tfc-section-head">
                                <div class="tfc-section-head__icon tfc-section-head__icon--blue">
                                    <i class="fa-solid fa-store"></i>
                                </div>
                                <div>
                                    <p class="tfc-section-head__eyebrow">Store Setup</p>
                                    <h2 class="tfc-section-head__title">Địa chỉ cửa hàng</h2>
                                    <p class="tfc-section-head__desc">Hiển thị cho khách khi cần mang thiết bị đến cửa hàng hoặc tham chiếu vị trí khi mô phỏng khoảng cách.</p>
                                </div>
                            </div>

                            <div class="tfc-field">
                                <label class="tfc-field__label" for="travelFeeStoreAddress">
                                    Địa chỉ cửa hàng
                                    <span class="tfc-required">*</span>
                                </label>
                                <div class="tfc-input-wrap tfc-input-wrap--textarea">
                                    <span class="tfc-input-wrap__icon">
                                        <i class="fa-solid fa-location-dot"></i>
                                    </span>
                                    <textarea
                                        id="travelFeeStoreAddress"
                                        rows="3"
                                        placeholder="VD: 245 Nguyễn Trãi, Phường 2, Quận 5, TP.HCM"
                                    ></textarea>
                                </div>
                                <p class="tfc-field__hint">Bắt buộc để booking và preview hiển thị đúng thông tin cửa hàng.</p>
                                <div class="tfc-field__error" data-error-for="store_address"></div>
                            </div>
                        </div>

                        <div class="tfc-form-section">
                            <div class="tfc-section-head">
                                <div class="tfc-section-head__icon tfc-section-head__icon--purple">
                                    <i class="fa-solid fa-table-list"></i>
                                </div>
                                <div class="tfc-section-head__text">
                                    <p class="tfc-section-head__eyebrow">Distance Table</p>
                                    <h2 class="tfc-section-head__title">Bảng cấu hình khoảng cách</h2>
                                    <p class="tfc-section-head__desc">Mỗi dòng gồm khoảng km, phí thuê xe và phí đi lại tương ứng. Chỉnh trực tiếp từng dòng và preview sẽ cập nhật ngay.</p>
                                </div>
                                <button type="button" class="tfc-btn tfc-btn--ghost" id="btnAddTravelTier">
                                    <i class="fa-solid fa-plus"></i>
                                    Thêm khoảng
                                </button>
                            </div>

                            <div class="tfc-tier-table">
                                <div class="tfc-tier-table__head">
                                    <div>Từ km</div>
                                    <div>Đến km</div>
                                    <div>Phí thuê xe</div>
                                    <div>Phí đi lại</div>
                                    <div class="text-center">Xóa</div>
                                </div>
                                <div class="tfc-tier-table__body" id="travelTierList"></div>
                            </div>

                            <div class="tfc-rule-hint">
                                <i class="fa-solid fa-circle-info"></i>
                                Ví dụ: 0 → 1 km có thể đặt 0 đ cho cả hai loại phí; 1 → 5 km có thể đặt phí thuê xe 50.000 đ và phí đi lại 17.000 đ.
                            </div>
                        </div>

                        <div class="tfc-form-actions">
                            <button type="button" class="tfc-btn tfc-btn--secondary" id="btnResetTravelFeeFormBottom">
                                <i class="fa-solid fa-rotate-left"></i>
                                Đặt lại mặc định
                            </button>
                            <button type="submit" class="tfc-btn tfc-btn--primary" id="btnSaveTravelFeeBottom">
                                <i class="fa-solid fa-floppy-disk"></i>
                                Lưu cấu hình
                            </button>
                        </div>
                    </form>
                </section>

                <aside class="tfc-card tfc-card--preview">
                    <div class="tfc-preview">
                        <div class="tfc-preview__head">
                            <div>
                                <p class="tfc-section-head__eyebrow">Realtime Preview</p>
                                <h2 class="tfc-preview__title">Mô phỏng khoảng phí đang active</h2>
                            </div>
                            <div class="tfc-mode-toggle-group" role="tablist" aria-label="Chế độ preview">
                                <button type="button" class="tfc-mode-toggle" data-preview-mode="travel_fee">Phí đi lại</button>
                                <button type="button" class="tfc-mode-toggle" data-preview-mode="tiered">Bậc khoảng cách</button>
                            </div>
                        </div>

                        <div class="tfc-stat-grid">
                            <div class="tfc-stat-card">
                                <div class="tfc-stat-card__icon tfc-stat-card__icon--blue">
                                    <i class="fa-solid fa-truck-fast"></i>
                                </div>
                                <div class="tfc-stat-card__label">Phí thuê xe</div>
                                <div class="tfc-stat-card__value" id="travelFeeTransportPreview">0 đ</div>
                                <div class="tfc-stat-card__subvalue">Theo khoảng đang chọn</div>
                            </div>

                            <div class="tfc-stat-card">
                                <div class="tfc-stat-card__icon tfc-stat-card__icon--orange">
                                    <i class="fa-solid fa-road"></i>
                                </div>
                                <div class="tfc-stat-card__label">Phí đi lại</div>
                                <div class="tfc-stat-card__value" id="travelFeeTravelPreview">0 đ</div>
                                <div class="tfc-stat-card__subvalue">Theo khoảng đang chọn</div>
                            </div>

                            <div class="tfc-stat-card">
                                <div class="tfc-stat-card__icon tfc-stat-card__icon--teal">
                                    <i class="fa-solid fa-ruler-horizontal"></i>
                                </div>
                                <div class="tfc-stat-card__label">Khoảng đang áp dụng</div>
                                <div class="tfc-stat-card__value" id="travelFeeRangePreview">Chưa có</div>
                                <div class="tfc-stat-card__subvalue">Highlight đổi theo simulator</div>
                            </div>

                            <div class="tfc-stat-card tfc-stat-card--wide">
                                <div class="tfc-stat-card__icon tfc-stat-card__icon--blue">
                                    <i class="fa-solid fa-location-dot"></i>
                                </div>
                                <div class="tfc-stat-card__label">Địa chỉ cửa hàng</div>
                                <div class="tfc-stat-card__value" id="travelFeeStoreAddressPreview">Chưa nhập địa chỉ</div>
                            </div>
                        </div>

                        <div class="tfc-simulator">
                            <div class="tfc-simulator__head">
                                <div>
                                    <p class="tfc-simulator__label">Khoảng cách kiểm tra</p>
                                    <h3 class="tfc-simulator__title">Chọn mốc xem rule active</h3>
                                </div>
                                <div class="tfc-distance-badge" id="travelFeeDistanceBadge">3 km</div>
                            </div>

                            <div class="tfc-slider-wrap">
                                <input type="range" id="travelFeeDistanceSlider" min="0" max="30" step="0.1" value="3">
                                <div class="tfc-distance-input-wrap">
                                    <input type="number" id="travelFeeDistanceNumber" min="0" step="0.1" value="3">
                                    <span>km</span>
                                </div>
                            </div>

                            <div class="tfc-active-price-card">
                                <div class="tfc-active-price-card__eyebrow" id="travelFeeActiveRuleLabel">Phí đi lại đang áp dụng</div>
                                <div class="tfc-active-price-card__value" id="travelFeeActivePrice">0 đ</div>
                                <div class="tfc-active-price-card__sub" id="travelFeeActiveTransportMeta">Phí thuê xe: 0 đ</div>
                                <p class="tfc-active-price-card__desc" id="travelFeeActiveRuleCopy">
                                    Chỉnh dữ liệu ở cột trái để xem cách hệ thống áp dụng phí theo từng mốc khoảng cách.
                                </p>
                            </div>
                        </div>

                        <div class="tfc-rule-group">
                            <div class="tfc-rule-group__head">
                                <div>
                                    <h3>Các khoảng đang áp dụng</h3>
                                    <p>Highlight tự động đổi theo mốc đang chọn trong simulator.</p>
                                </div>
                                <span class="tfc-chip tfc-chip--info" id="travelFeeModeChip">Phí đi lại</span>
                            </div>
                            <div class="tfc-rule-list" id="travelFeeRulePreview"></div>
                        </div>

                        <div class="tfc-rule-group">
                            <div class="tfc-rule-group__head">
                                <div>
                                    <h3>Mốc xem nhanh</h3>
                                    <p>Bấm vào từng thẻ để nhảy đến khoảng cách thường gặp.</p>
                                </div>
                            </div>
                            <div class="tfc-sample-grid" id="travelFeeSampleGrid"></div>
                        </div>

                        <div class="tfc-tip-card">
                            <div class="tfc-tip-card__head">
                                <i class="fa-solid fa-lightbulb"></i>
                                <strong>Gợi ý cấu hình</strong>
                            </div>
                            <p>Bạn có thể bắt đầu với các mốc gần như 0 → 1 km miễn phí, 1 → 5 km áp dụng mức phí cố định, sau đó mở rộng dần các khoảng xa hơn khi có thêm dữ liệu thực tế.</p>
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/travel-fee-config.js') }}"></script>
<script>
    document.getElementById('btnResetTravelFeeFormBottom')?.addEventListener('click', () => {
        document.getElementById('btnResetTravelFeeForm')?.click();
    });
    document.getElementById('btnSaveTravelFeeBottom')?.addEventListener('click', () => {
        document.getElementById('travelFeeForm')?.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    });
</script>
@endpush
