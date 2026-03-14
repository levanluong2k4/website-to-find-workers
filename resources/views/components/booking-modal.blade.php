<style>
    .booking-service-picker {
        position: relative;
    }

    .booking-service-trigger {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.95rem 1rem;
        border-radius: 1rem;
        background: #f8fafc;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid transparent;
    }

    .booking-service-trigger:hover,
    .booking-service-trigger.is-open {
        border-color: #93c5fd;
        box-shadow: 0 12px 28px rgba(14, 165, 233, 0.12);
    }

    .booking-service-trigger-copy {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }

    .booking-service-trigger-title {
        font-weight: 700;
        color: #0f172a;
    }

    .booking-service-trigger-icon {
        color: #0ea5e9;
        transition: transform 0.2s ease;
    }

    .booking-service-trigger.is-open .booking-service-trigger-icon {
        transform: rotate(180deg);
    }

    .booking-service-dropdown {
        position: absolute;
        top: calc(100% + 0.75rem);
        left: 0;
        right: 0;
        z-index: 1080;
        border-radius: 1.25rem;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.16);
        overflow: hidden;
    }

    .booking-service-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1rem 0.75rem;
        border-bottom: 1px solid #e2e8f0;
        background: linear-gradient(180deg, rgba(14, 165, 233, 0.08), rgba(255, 255, 255, 0));
    }

    .booking-selected-services {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding: 0.9rem 1rem 0;
    }

    .booking-selected-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.45rem 0.75rem;
        border-radius: 999px;
        background: #ecfeff;
        color: #155e75;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .booking-selected-chip-remove {
        width: 1.2rem;
        height: 1.2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        border-radius: 999px;
        background: rgba(21, 94, 117, 0.12);
        color: #155e75;
        cursor: pointer;
        transition: background 0.2s ease, color 0.2s ease;
        padding: 0;
    }

    .booking-selected-chip-remove:hover {
        background: #155e75;
        color: #fff;
    }

    .booking-service-list {
        max-height: 320px;
        overflow-y: auto;
        padding: 1rem;
        display: grid;
        gap: 0.75rem;
    }

    .booking-service-empty {
        padding: 1rem;
        text-align: center;
        color: #64748b;
        background: #f8fafc;
        border-radius: 1rem;
        border: 1px dashed #cbd5e1;
    }

    .booking-service-option {
        display: grid;
        grid-template-columns: 64px 1fr auto;
        gap: 0.9rem;
        align-items: center;
        padding: 0.85rem;
        border-radius: 1rem;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .booking-service-option:hover {
        transform: translateY(-1px);
        border-color: #67e8f9;
        box-shadow: 0 16px 30px rgba(14, 165, 233, 0.12);
    }

    .booking-service-option.is-checked {
        border-color: #0b6e4f;
        background: linear-gradient(135deg, rgba(11, 110, 79, 0.08), rgba(255, 255, 255, 0.96));
    }

    .booking-service-thumb {
        width: 64px;
        height: 64px;
        border-radius: 1rem;
        object-fit: cover;
        background: #e2e8f0;
        border: 1px solid #e2e8f0;
    }

    .booking-service-meta {
        min-width: 0;
    }

    .booking-service-meta strong {
        display: block;
        color: #0f172a;
        font-size: 0.95rem;
    }

    .booking-service-meta small {
        display: block;
        margin-top: 0.2rem;
        color: #64748b;
        line-height: 1.45;
    }

    .booking-service-option .container input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    .booking-service-option .container {
        display: block;
        position: relative;
        cursor: pointer;
        font-size: 1.5rem;
        user-select: none;
    }

    .booking-service-option .checkmark {
        --clr: #0B6E4F;
        position: relative;
        top: 0;
        left: 0;
        height: 1.3em;
        width: 1.3em;
        background-color: #ccc;
        border-radius: 50%;
        transition: 300ms;
    }

    .booking-service-option .container input:checked~.checkmark {
        background-color: var(--clr);
        border-radius: .5rem;
        animation: booking-service-pulse 500ms ease-in-out;
    }

    .booking-service-option .checkmark:after {
        content: "";
        position: absolute;
        display: none;
    }

    .booking-service-option .container input:checked~.checkmark:after {
        display: block;
    }

    .booking-service-option .container .checkmark:after {
        left: 0.45em;
        top: 0.25em;
        width: 0.25em;
        height: 0.5em;
        border: solid #E0E0E2;
        border-width: 0 0.15em 0.15em 0;
        transform: rotate(45deg);
    }

    @keyframes booking-service-pulse {
        0% {
            box-shadow: 0 0 0 #0B6E4F90;
            rotate: 20deg;
        }

        50% {
            rotate: -20deg;
        }

        75% {
            box-shadow: 0 0 0 10px #0B6E4F60;
        }

        100% {
            box-shadow: 0 0 0 13px #0B6E4F30;
            rotate: 0;
        }
    }
</style>

<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold" id="bookingModalLabel" style="color: #0f172a;">Đặt lịch sửa chữa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <form id="formBooking">
                    <input type="hidden" id="booking_tho_id" name="tho_id" value="">

                    <div class="row g-3">
                        <div class="col-12 d-none" id="bookingSelectedWorkerCard">
                            <label class="form-label fw-bold mb-2" style="font-size: 0.9rem; color: #334155;">Thợ sửa chữa đã chọn</label>
                            <div class="d-flex align-items-center gap-3 p-3 border rounded-3 shadow-sm" style="background-color: #f8fafc; border-color: #e2e8f0 !important;">
                                <img src="/assets/images/user-default.png" alt="Thợ sửa chữa" id="bookingSelectedWorkerAvatar" class="rounded-circle border bg-white" style="width: 56px; height: 56px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <div class="fw-bold" id="bookingSelectedWorkerName" style="color: #0f172a;">Đang tải thông tin thợ...</div>
                                    <div class="text-muted small" id="bookingSelectedWorkerServices">Đang tải chuyên môn...</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold" style="font-size: 0.9rem; color: #334155;">Dịch vụ cần sửa <span class="text-danger">*</span></label>
                            <div class="booking-service-picker" id="bookingServicePicker">
                                <button type="button" class="booking-service-trigger border-0 shadow-sm" id="bookingServiceTrigger" aria-expanded="false">
                                    <div class="booking-service-trigger-copy">
                                        <span class="booking-service-trigger-title">Chọn một hoặc nhiều dịch vụ</span>
                                        <small class="text-muted" id="bookingServiceSummary">Nhấn để mở danh sách dịch vụ kèm hình ảnh.</small>
                                    </div>
                                    <span class="material-symbols-outlined booking-service-trigger-icon">expand_more</span>
                                </button>

                                <div class="booking-service-dropdown d-none" id="bookingServiceDropdown">
                                    <div class="booking-service-toolbar">
                                        <div>
                                            <div class="fw-bold" style="color: #0f172a;">Danh sách dịch vụ</div>
                                            <small class="text-muted">Thợ chỉ nhận đơn nếu sửa được toàn bộ dịch vụ bạn đã chọn.</small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-light rounded-pill px-3" id="bookingServiceClear">Bỏ chọn</button>
                                    </div>

                                    <div id="bookingSelectedServices" class="booking-selected-services d-none"></div>
                                    <div class="booking-service-list" id="bookingDichVuList">
                                        <div class="booking-service-empty">Đang tải danh mục...</div>
                                    </div>
                                </div>
                            </div>
                            <div id="booking_service_ids_container"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold" style="font-size: 0.9rem; color: #334155;">Hình thức sửa chữa <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3">
                                <div class="form-check flex-grow-1 p-3 border rounded-3 position-relative" style="background-color: #f8fafc; border-color: #e2e8f0 !important; cursor: pointer;">
                                    <input class="form-check-input ms-1 mt-0 position-absolute top-50 translate-middle-y" type="radio" name="loai_dat_lich" id="loai_hom" value="at_home" checked>
                                    <label class="form-check-label w-100 ms-4 d-flex flex-column" for="loai_hom">
                                        <span class="fw-bold" style="color: #0f172a;">Sửa tận nơi</span>
                                        <small class="text-muted" style="font-size: 0.75rem;">Phí đi lại 5.000đ/km</small>
                                    </label>
                                </div>
                                <div class="form-check flex-grow-1 p-3 border rounded-3 position-relative" style="background-color: #f8fafc; border-color: #e2e8f0 !important; cursor: pointer;">
                                    <input class="form-check-input ms-1 mt-0 position-absolute top-50 translate-middle-y" type="radio" name="loai_dat_lich" id="loai_store" value="at_store">
                                    <label class="form-check-label w-100 ms-4 d-flex flex-column" for="loai_store">
                                        <span class="fw-bold" style="color: #0f172a;">Mang đến cửa hàng</span>
                                        <small class="text-muted" style="font-size: 0.75rem;">Miễn phí di chuyển</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="atHomeGroup" class="col-12">
                            <label class="form-label fw-bold" style="font-size: 0.9rem; color: #334155;">Địa chỉ của bạn <span class="text-danger">*</span></label>

                            <div class="mb-3">
                                <button class="btn btn-outline-primary w-100 rounded-3" type="button" id="btnBookingGetLocation" title="Lấy vị trí hiện tại của tôi">
                                    <i class="fas fa-crosshairs me-2"></i>Tự động lấy vị trí hiện tại
                                </button>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <select class="form-select border-0 shadow-sm bg-light" id="booking_tinh" name="tinh" style="border-radius: 0.5rem;" required>
                                        <option value="">Tỉnh/Thành phố</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select border-0 shadow-sm bg-light" id="booking_huyen" name="huyen" style="border-radius: 0.5rem;" required disabled>
                                        <option value="">Quận/Huyện</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select border-0 shadow-sm bg-light" id="booking_xa" name="xa" style="border-radius: 0.5rem;" required disabled>
                                        <option value="">Phường/Xã</option>
                                    </select>
                                </div>
                            </div>

                            <div class="input-group shadow-sm mt-2" style="border-radius: 0.5rem; overflow: hidden;">
                                <span class="input-group-text border-0 bg-white text-muted"><i class="fas fa-home"></i></span>
                                <input type="text" class="form-control border-0 bg-light" id="booking_so_nha" name="so_nha" placeholder="Số nhà, ngõ/ngách, tên đường cụ thể..." required>
                            </div>

                            <small class="text-muted d-block mt-2" id="bookingLocationStatus" style="font-size: 0.75rem;">Vui lòng lấy vị trí tự động hoặc chọn địa chỉ thủ công.</small>
                            <input type="hidden" id="booking_vi_do" name="vi_do">
                            <input type="hidden" id="booking_kinh_do" name="kinh_do">
                            <input type="hidden" id="booking_dia_chi" name="dia_chi">
                        </div>

                        <div id="atStoreGroup" class="col-12 d-none">
                            <div class="alert alert-info border-0 rounded-3 mb-0" style="font-size: 0.85rem;">
                                <i class="fas fa-info-circle me-1"></i> Vui lòng mang thiết bị đến cửa hàng tại:
                                <strong>2 Đ. Nguyễn Đình Chiểu, Vĩnh Thọ, Nha Trang, Khánh Hòa</strong>
                                đúng giờ hẹn.
                            </div>

                            <div id="transportRentalGroup" class="mt-3 d-none">
                                <div class="form-check p-3 border rounded-3 position-relative" style="background-color: #f0f9ff; border-color: #bae6fd !important; cursor: pointer;">
                                    <input class="form-check-input ms-1 mt-0 position-absolute top-50 translate-middle-y" type="checkbox" name="thue_xe_cho" id="booking_thue_xe_cho" value="1" style="transform: scale(1.2);">
                                    <label class="form-check-label w-100 ms-4 d-flex flex-column" for="booking_thue_xe_cho">
                                        <span class="fw-bold" style="color: #0284c7;"><i class="fas fa-truck me-1"></i> Tôi muốn thuê xe chở thiết bị hai chiều</span>
                                        <small class="text-muted" style="font-size: 0.75rem;">Phù hợp với tủ lạnh, máy giặt, tivi, điều hòa và thiết bị nặng.</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label fw-bold" style="font-size: 0.9rem; color: #334155;">Ngày hẹn <span class="text-danger">*</span></label>
                            <input type="date" class="form-control border-0 shadow-sm" id="booking_ngay_hen" name="ngay_hen" style="border-radius: 0.5rem; background-color: #f8fafc;" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-bold" style="font-size: 0.9rem; color: #334155;">Khung giờ <span class="text-danger">*</span></label>
                            <select class="form-select border-0 shadow-sm" id="booking_khung_gio_hen" name="khung_gio_hen" style="border-radius: 0.5rem; background-color: #f8fafc;" required>
                                <option value="">Chọn khung giờ</option>
                                <option value="08:00-10:00">08:00 - 10:00</option>
                                <option value="10:00-12:00">10:00 - 12:00</option>
                                <option value="12:00-14:00">12:00 - 14:00</option>
                                <option value="14:00-17:00">14:00 - 17:00</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold" style="font-size: 0.9rem; color: #334155;">Mô tả vấn đề</label>
                            <textarea class="form-control border-0 shadow-sm" id="booking_mo_ta" name="mo_ta_van_de" rows="3" placeholder="Ví dụ: tủ lạnh không đông đá, máy giặt kêu to..." style="border-radius: 0.5rem; background-color: #f8fafc;"></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold" style="font-size: 0.9rem; color: #334155;">Hình ảnh và video mô tả</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="upload-box border rounded-3 p-2 text-center position-relative" style="background-color: #f8fafc; border-style: dashed !important; cursor: pointer;">
                                        <input type="file" id="booking_images" name="hinh_anh_mo_ta[]" multiple accept="image/*" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;">
                                        <div class="text-primary"><i class="fas fa-images fs-4"></i></div>
                                        <small class="d-block text-muted" style="font-size: 0.7rem;">Ảnh (tối đa 5)</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="upload-box border rounded-3 p-2 text-center position-relative" style="background-color: #f8fafc; border-style: dashed !important; cursor: pointer;">
                                        <input type="file" id="booking_video" name="video_mo_ta" accept="video/*" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;">
                                        <div class="text-danger"><i class="fas fa-video fs-4"></i></div>
                                        <small class="d-block text-muted" style="font-size: 0.7rem;">Video (tối đa 20s)</small>
                                    </div>
                                </div>
                            </div>
                            <div id="mediaPreview" class="d-flex flex-wrap gap-2 mt-2"></div>
                            <small class="text-muted mt-1 d-block" style="font-size: 0.7rem;">Ảnh và video giúp thợ chẩn đoán nhanh và báo giá sát hơn.</small>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" id="btnSubmitBooking">
                            Xác nhận đặt lịch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
