<!-- Booking Modal Component -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold" id="bookingModalLabel" style="color: #0f172a;">Đặt Lịch Sửa Chữa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <form id="formBooking">
                    <input type="hidden" id="booking_tho_id" name="tho_id" value="">

                    <div class="row g-3">
                        <!-- Chọn loại dịch vụ -->
                        <div class="col-12">
                            <label class="form-label fw-bold" style="font-size: 0.9rem; color: #334155;">Dịch vụ cần sửa <span class="text-danger">*</span></label>
                            <select class="form-select border-0 shadow-sm" id="booking_dich_vu_id" name="dich_vu_id" style="border-radius: 0.5rem; background-color: #f8fafc;" required>
                                <option value="">Đang tải danh mục...</option>
                            </select>
                        </div>

                        <!-- Loại đặt lịch -->
                        <div class="col-12">
                            <label class="form-label fw-bold" style="font-size: 0.9rem; color: #334155;">Hình thức sửa chữa <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3">
                                <div class="form-check flex-grow-1 p-3 border rounded-3 position-relative" style="background-color: #f8fafc; border-color: #e2e8f0 !important; cursor: pointer;">
                                    <input class="form-check-input ms-1 mt-0 position-absolute top-50 translate-middle-y" type="radio" name="loai_dat_lich" id="loai_hom" value="at_home" checked>
                                    <label class="form-check-label w-100 ms-4 d-flex flex-column" for="loai_hom">
                                        <span class="fw-bold" style="color: #0f172a;">Sửa tận nơi</span>
                                        <small class="text-muted" style="font-size: 0.75rem;">Phí đi lại 5,000đ/km (Bán kính 5km)</small>
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

                        <!-- Group Mặc định hiển thị dựa theo loai_dat_lich at_home -->
                        <div id="atHomeGroup" class="col-12">
                            <label class="form-label fw-bold" style="font-size: 0.9rem; color: #334155;">Địa chỉ của bạn <span class="text-danger">*</span></label>

                            <!-- Nút tự động lấy vị trí hiện tại -->
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

                            <small class="text-muted d-block mt-2" id="bookingLocationStatus" style="font-size: 0.75rem;">Vui lòng ấn nút lấy vị trí tự động hoặc chọn thao tác tay từ trên xuống.</small>
                            <input type="hidden" id="booking_vi_do" name="vi_do">
                            <input type="hidden" id="booking_kinh_do" name="kinh_do">
                            <!-- Chỗ giữ địa chỉ gộp để submit API -->
                            <input type="hidden" id="booking_dia_chi" name="dia_chi">
                        </div>

                        <div id="atStoreGroup" class="col-12 d-none">
                            <div class="alert alert-info border-0 rounded-3 mb-0" style="font-size: 0.85rem;">
                                <i class="fas fa-info-circle me-1"></i> Vui lòng mang thiết bị đến cửa hàng tại: <strong>2 Đ. Nguyễn Đình Chiểu, Vĩnh Thọ, Nha Trang, Khánh Hòa</strong> đúng giờ hẹn.
                            </div>

                            <!-- Tính năng thuê xe cho thiết bị nặng -->
                            <div id="transportRentalGroup" class="mt-3 d-none">
                                <div class="form-check p-3 border rounded-3 position-relative" style="background-color: #f0f9ff; border-color: #bae6fd !important; cursor: pointer;">
                                    <input class="form-check-input ms-1 mt-0 position-absolute top-50 translate-middle-y" type="checkbox" name="thue_xe_cho" id="booking_thue_xe_cho" value="1" style="transform: scale(1.2);">
                                    <label class="form-check-label w-100 ms-4 d-flex flex-column" for="booking_thue_xe_cho">
                                        <span class="fw-bold" style="color: #0284c7;"><i class="fas fa-truck me-1"></i> Tôi muốn thuê xe tải chở thiết bị (2 chiều)</span>
                                        <small class="text-muted" style="font-size: 0.75rem;">Đội ngũ thợ sẽ tự đến chở Tủ lạnh, Máy giặt, Tivi... về cửa hàng. Ước tính phí 50k - 100k tùy khoảng cách.</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Lịch hẹn -->
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

                        <!-- Mô tả vấn đề -->
                        <div class="col-12">
                            <label class="form-label fw-bold" style="font-size: 0.9rem; color: #334155;">Mô tả vấn đề</label>
                            <textarea class="form-control border-0 shadow-sm" id="booking_mo_ta" name="mo_ta_van_de" rows="3" placeholder="Tủ lạnh không đông đá, máy giặt kêu to..." style="border-radius: 0.5rem; background-color: #f8fafc;"></textarea>
                        </div>
                    </div>

                    <!-- Form actions -->
                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" id="btnSubmitBooking">
                            Xác nhận Đặt Lịch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>