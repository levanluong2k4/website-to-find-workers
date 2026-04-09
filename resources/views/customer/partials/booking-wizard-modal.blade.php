@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/customer/booking-wizard.css') }}?v={{ time() }}">
@endpush

<div
    class="booking-wizard-modal d-none"
    id="bookingWizardModal"
    data-standalone="{{ request()->routeIs('customer.booking') ? '1' : '0' }}"
    aria-hidden="true"
>
    <div class="booking-wizard-dialog" role="dialog" aria-modal="true" aria-labelledby="bookingWizardTitle">
        <button type="button" class="booking-wizard-close" id="bookingWizardCloseButton" aria-label="Đóng">×</button>

        <div class="booking-wizard-shell">
            <div class="booking-wizard-main">
                <div class="booking-wizard-head">
                    <div>
                        <div class="booking-wizard-kicker" id="bookingWizardKicker">Bước 1 trên 5</div>
                        <h1 class="booking-wizard-title" id="bookingWizardTitle">ĐẶT LỊCH SỬA CHỮA</h1>
                        <p class="booking-wizard-copy" id="bookingWizardCopy">Chọn một hoặc nhiều dịch vụ phù hợp để bắt đầu lịch hẹn với Thợ Tốt NTU.</p>
                    </div>
                    <div class="booking-wizard-step-badge" id="bookingWizardStepBadge">1 / 5</div>
                </div>

                <div class="booking-wizard-progress">
                    <div class="booking-wizard-progress-track">
                        <div class="booking-wizard-progress-fill" id="bookingWizardProgressFill"></div>
                    </div>
                    <div class="booking-wizard-steps" id="bookingWizardSteps">
                        <button type="button" class="booking-step-chip is-active" data-step-target="1">Dịch vụ</button>
                        <button type="button" class="booking-step-chip" data-step-target="2">Hình thức</button>
                        <button type="button" class="booking-step-chip" data-step-target="3">Địa chỉ</button>
                        <button type="button" class="booking-step-chip" data-step-target="4">Thời gian</button>
                        <button type="button" class="booking-step-chip" data-step-target="5">Mô tả</button>
                    </div>
                </div>

                <form id="bookingWizardForm" class="booking-wizard-form">
                    <input type="hidden" id="bookingWizardWorkerId" name="tho_id" value="">
                    <input type="hidden" id="bookingWizardRepairMode" name="loai_dat_lich" value="">
                    <input type="hidden" id="bookingWizardLat" name="vi_do" value="">
                    <input type="hidden" id="bookingWizardLng" name="kinh_do" value="">
                    <input type="hidden" id="bookingWizardDiaChi" name="dia_chi" value="">
                    <input type="hidden" id="bookingWizardDate" name="ngay_hen" value="">
                    <input type="hidden" id="bookingWizardTimeSlot" name="khung_gio_hen" value="">
                    <input type="hidden" id="bookingWizardStoreTransport" name="thue_xe_cho" value="0">
                    <input type="file" id="bookingWizardImages" name="hinh_anh_mo_ta[]" accept="image/*" multiple hidden>
                    <input type="file" id="bookingWizardVideo" name="video_mo_ta" accept="video/*" hidden>
                    <input type="file" id="bookingWizardMediaPicker" accept="image/*,video/*" multiple hidden>

                    <div class="booking-worker-banner d-none" id="bookingWizardWorkerBanner">
                        <img src="/assets/images/user-default.png" alt="Thợ đã chọn" id="bookingWizardWorkerAvatar">
                        <div>
                            <div class="booking-worker-banner-label">Thợ đã chọn</div>
                            <div class="booking-worker-banner-name" id="bookingWizardWorkerName">Đang tải thông tin thợ...</div>
                            <div class="booking-worker-banner-meta" id="bookingWizardWorkerMeta">Hệ thống đang nạp chuyên môn phù hợp.</div>
                        </div>
                    </div>

                    <div class="booking-step-stage" id="bookingWizardStage">
                        <section class="booking-step-panel is-active" data-step-panel="1">
                            <div class="booking-step-callout">
                                Bạn có thể chọn nhiều dịch vụ nếu muốn kỹ thuật viên xử lý trong cùng một lịch hẹn.
                            </div>
                            <div class="booking-card-grid" id="bookingWizardServices"></div>
                            <div class="booking-empty-state d-none" id="bookingWizardServicesEmpty">
                                <div class="booking-empty-title">Chưa có dịch vụ khả dụng</div>
                                <p class="booking-empty-copy mb-0">Hệ thống chưa tải được danh mục dịch vụ. Vui lòng thử lại sau.</p>
                            </div>
                        </section>

                        <section class="booking-step-panel" data-step-panel="2">
                            <div class="booking-choice-grid">
                                <button type="button" class="booking-choice-card" data-repair-mode="at_home">
                                    <div class="booking-choice-visual booking-choice-home">
                                        <span>GPS</span>
                                    </div>
                                    <div class="booking-choice-body">
                                        <div class="booking-choice-title">Sửa tại nhà</div>
                                        <p class="booking-choice-copy">Kỹ thuật viên đến tận nơi. Phí di chuyển được tính theo khoảng cách thực tế.</p>
                                    </div>
                                    <div class="booking-choice-check">✓</div>
                                </button>

                                <button type="button" class="booking-choice-card" data-repair-mode="at_store">
                                    <div class="booking-choice-visual booking-choice-store">
                                        <span>SHOP</span>
                                    </div>
                                    <div class="booking-choice-body">
                                        <div class="booking-choice-title">Mang đến cửa hàng</div>
                                        <p class="booking-choice-copy">Bạn tự mang thiết bị đến cửa hàng. Không phát sinh phí đi lại.</p>
                                    </div>
                                    <div class="booking-choice-check">✓</div>
                                </button>
                            </div>
                        </section>

                        <section class="booking-step-panel" data-step-panel="3">
                            <div class="booking-address-panels">
                                <div class="booking-address-panel" id="bookingWizardAtHomePanel">
                                    <div class="booking-address-map">
                                        <div class="booking-address-map-marker">◎</div>
                                        <button type="button" class="booking-location-button" id="bookingWizardGetLocation">LẤY VỊ TRÍ CỦA TÔI</button>
                                        <p class="booking-address-hint">Sử dụng GPS để hệ thống tự điền địa chỉ và tính phí di chuyển.</p>
                                    </div>

                                    <div class="booking-address-divider"><span>Hoặc nhập địa chỉ bên dưới</span></div>

                                    <div class="booking-address-grid">
                                        <label class="booking-field">
                                            <span>Tỉnh / Thành phố</span>
                                            <select id="bookingWizardTinh" name="tinh">
                                                <option value="">Chọn tỉnh / thành phố</option>
                                            </select>
                                        </label>
                                        <label class="booking-field">
                                            <span>Quận / Huyện</span>
                                            <select id="bookingWizardHuyen" name="huyen" disabled>
                                                <option value="">Chọn quận / huyện</option>
                                            </select>
                                        </label>
                                        <label class="booking-field">
                                            <span>Phường / Xã</span>
                                            <select id="bookingWizardXa" name="xa" disabled>
                                                <option value="">Chọn phường / xã</option>
                                            </select>
                                        </label>
                                    </div>

                                    <label class="booking-field booking-field-full">
                                        <span>Địa chỉ chi tiết (số nhà, tên đường)</span>
                                        <input type="text" id="bookingWizardSoNha" name="so_nha" placeholder="Ví dụ: 02 Nguyễn Đình Chiểu, hẻm 12, căn hộ 4B">
                                    </label>

                                    <div class="booking-location-status" id="bookingWizardLocationStatus">Vui lòng lấy vị trí hiện tại hoặc nhập địa chỉ thủ công.</div>
                                </div>

                                <div class="booking-store-card d-none" id="bookingWizardAtStorePanel">
                                    <div class="booking-store-badge">Miễn phí di chuyển</div>
                                    <h3>Mang thiết bị đến cửa hàng</h3>
                                    <p>Thợ Tốt NTU tiếp nhận thiết bị tại cửa hàng trong giờ hẹn đã chọn.</p>
                                    <div class="booking-store-address" id="bookingWizardStoreAddress">2 Đường Nguyễn Đình Chiểu, Vĩnh Thọ, Nha Trang, Khánh Hòa</div>

                                    <label class="booking-store-toggle d-none" id="bookingWizardTransportWrap">
                                        <input type="checkbox" id="bookingWizardTransportToggle">
                                        <span>Tôi muốn thuê xe chở thiết bị hai chiều</span>
                                    </label>

                                    <p id="bookingWizardStoreTransportNote">Bạn tự mang thiết bị đến cửa hàng nên không phát sinh phí đưa đón.</p>
                                </div>
                            </div>
                        </section>

                        <section class="booking-step-panel" data-step-panel="4">
                            <div class="booking-time-layout">
                                <div>
                                    <div class="booking-date-cards" id="bookingWizardDateCards"></div>
                                </div>
                                <div class="booking-time-slots">
                                    <div class="booking-time-slots-title">Khung giờ trống</div>
                                    <div id="bookingWizardTimeSlots"></div>
                                </div>
                            </div>
                        </section>

                        <section class="booking-step-panel" data-step-panel="5">
                            <div class="booking-detail-stack">
                                <label class="booking-field">
                                    <span>Mô tả chi tiết sự cố</span>
                                    <textarea id="bookingWizardDescription" name="mo_ta_van_de" rows="5" placeholder="Ví dụ: Điều hòa không lạnh sâu, máy chạy nhưng gió yếu và có tiếng ồn bất thường."></textarea>
                                </label>

                                <div class="booking-problem-assist">
                                    <div class="booking-problem-suggest d-none" id="bookingWizardProblemSuggest">
                                        <div class="booking-problem-suggest-head">
                                            <div class="booking-problem-suggest-title">Triệu chứng gợi ý từ danh mục</div>
                                            <div class="booking-problem-suggest-copy" id="bookingWizardProblemSuggestCopy">Bấm vào gợi ý để tự điền mô tả nhanh hơn.</div>
                                        </div>
                                        <div class="booking-problem-chip-list" id="bookingWizardProblemSuggestList"></div>
                                    </div>

                                    <div class="booking-problem-price d-none" id="bookingWizardProblemPriceCard">
                                        <div class="booking-problem-price-label">Giá tham khảo</div>
                                        <div class="booking-problem-price-value" id="bookingWizardProblemPriceValue">Liên hệ báo giá</div>
                                        <div class="booking-problem-price-meta" id="bookingWizardProblemPriceMeta">Khoảng giá sẽ hiện khi bạn chọn một triệu chứng phù hợp.</div>
                                    </div>
                                </div>

                                <div>
                                    <div class="booking-upload-label">Hình ảnh &amp; video minh họa</div>
                                    <button type="button" class="booking-upload-dropzone" id="bookingWizardUploadZone">
                                        <div class="booking-upload-icon">＋</div>
                                        <div class="booking-upload-title">Tải lên ảnh hoặc video</div>
                                        <div class="booking-upload-copy">Tối đa 5 ảnh và 1 video dưới 20 giây.</div>
                                    </button>
                                    <div class="booking-upload-preview" id="bookingWizardPreview"></div>
                                </div>
                            </div>
                        </section>
                    </div>
                </form>
            </div>

            <aside class="booking-summary">
                <div class="booking-summary-title d-none" id="bookingSummaryTitle">NỘI DUNG ĐÃ ĐIỀN</div>

                <div class="booking-summary-sheet d-none" id="bookingSummarySheet">
                    <div class="booking-summary-item d-none" id="bookingSummaryServiceCard">
                        <img src="/assets/images/logontu.png" alt="Dịch vụ đã chọn" class="booking-summary-thumb" id="bookingSummaryServiceThumb">
                        <div class="booking-summary-item-body">
                            <div class="booking-summary-item-label">Dịch vụ</div>
                            <div class="booking-summary-item-value" id="bookingSummaryServiceValue">Vui lòng chọn dịch vụ để tiếp tục</div>
                            <div class="booking-summary-item-meta" id="bookingSummaryServiceMeta">Bạn có thể bắt đầu từ trang chủ hoặc hồ sơ thợ.</div>
                        </div>
                    </div>

                    <div class="booking-summary-item d-none" id="bookingSummaryWorkerCard">
                        <img src="/assets/images/user-default.png" alt="Thợ đã chọn" class="booking-summary-thumb booking-summary-thumb-avatar" id="bookingSummaryWorkerThumb">
                        <div class="booking-summary-item-body">
                            <div class="booking-summary-item-label">Thợ đã chọn</div>
                            <div class="booking-summary-item-value" id="bookingSummaryWorkerValue">Đang tải</div>
                            <div class="booking-summary-item-meta" id="bookingSummaryWorkerMeta">Hệ thống đang nạp chuyên môn.</div>
                        </div>
                    </div>

                    <div class="booking-summary-item d-none" id="bookingSummaryModeCard">
                        <div class="booking-summary-mark" id="bookingSummaryModeMark">🏠</div>
                        <div class="booking-summary-item-body">
                            <div class="booking-summary-item-label">Hình thức</div>
                            <div class="booking-summary-item-value" id="bookingSummaryModeValue">Chưa chọn hình thức</div>
                            <div class="booking-summary-item-meta" id="bookingSummaryModeMeta">Bước tiếp theo sẽ quyết định hình thức sửa chữa.</div>
                        </div>
                    </div>

                    <div class="booking-summary-item d-none" id="bookingSummaryTimeCard">
                        <div class="booking-summary-mark">🕒</div>
                        <div class="booking-summary-item-body">
                            <div class="booking-summary-item-label">Ngày &amp; giờ</div>
                            <div class="booking-summary-item-value" id="bookingSummaryTimeValue">Chưa chọn thời gian</div>
                            <div class="booking-summary-item-meta" id="bookingSummaryTimeMeta">Hệ thống đang mở lịch trong 7 ngày tới.</div>
                        </div>
                    </div>

                    <div class="booking-summary-item d-none" id="bookingSummaryAddressCard">
                        <div class="booking-summary-mark">📍</div>
                        <div class="booking-summary-item-body">
                            <div class="booking-summary-item-label">Địa chỉ</div>
                            <div class="booking-summary-item-value" id="bookingSummaryAddressValue">Chưa có địa chỉ</div>
                            <div class="booking-summary-item-meta" id="bookingSummaryAddressMeta">Bạn có thể nhập tay hoặc dùng GPS.</div>
                        </div>
                    </div>
                </div>

                <div class="booking-summary-travel booking-summary-highlight d-none" id="bookingSummaryTravelCard">
                    <div class="booking-summary-item-body">
                        <div class="booking-summary-travel-top">
                            <div class="booking-summary-item-label">Phí đi lại dự kiến</div>
                            <div class="booking-summary-travel-price" id="bookingSummaryTravelFee">0 ₫</div>
                        </div>
                        <div class="booking-summary-item-meta" id="bookingSummaryTravelMeta">Sẽ tính sau khi bạn chọn vị trí.</div>
                    </div>
                </div>

                <div class="booking-summary-travel booking-summary-highlight d-none" id="bookingSummaryReferencePriceCard">
                    <div class="booking-summary-item-body">
                        <div class="booking-summary-travel-top">
                            <div class="booking-summary-item-label">Giá tham khảo</div>
                            <div class="booking-summary-price" id="bookingSummaryReferencePrice">Liên hệ</div>
                        </div>
                        <div class="booking-summary-item-meta" id="bookingSummaryReferenceMeta">Chỉ hiện khi mô tả khớp với một triệu chứng trong danh mục.</div>
                    </div>
                </div>

                <div class="booking-summary-actions">
                    <button type="button" class="booking-secondary-button d-none" id="bookingWizardPrevButton">Quay lại</button>
                    <button type="button" class="booking-primary-button" id="bookingWizardNextButton">Tiếp tục</button>
                </div>
            </aside>
        </div>
    </div>

    <div class="booking-success-overlay d-none" id="bookingWizardSuccess">
        <div class="booking-success-card">
            <div class="booking-success-check">✓</div>
            <h2>ĐẶT LỊCH THÀNH CÔNG!</h2>
            <p>Cảm ơn bạn đã tin tưởng Thợ Tốt NTU. Kỹ thuật viên sẽ liên hệ với bạn trong ít phút nữa.</p>
            <div class="booking-success-order">
                <span>Mã đơn hàng của bạn</span>
                <strong id="bookingWizardSuccessCode">TT-00000</strong>
            </div>
            <div class="booking-success-actions">
                <a href="{{ route('customer.my-bookings') }}" class="booking-primary-button">Xem lịch của tôi</a>
                <a href="{{ route('customer.home') }}" class="booking-secondary-button">Quay lại trang chủ</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script type="module" src="{{ asset('assets/js/customer/booking-wizard.js') }}?v={{ time() }}"></script>
@endpush
