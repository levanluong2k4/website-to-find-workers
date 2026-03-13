import { callApi } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    // --- Xử lý Đặt Lịch Chung (Booking Modal) ---
    const bookingModal = document.getElementById('bookingModal');
    const formBooking = document.getElementById('formBooking');
    const loaiDatLichRadios = document.querySelectorAll('input[name="loai_dat_lich"]');
    const atHomeGroup = document.getElementById('atHomeGroup');
    const atStoreGroup = document.getElementById('atStoreGroup');
    const bookingDichVuSelect = document.getElementById('booking_dich_vu_id');
    const btnBookingGetLocation = document.getElementById('btnBookingGetLocation');
    const bookingLocationStatus = document.getElementById('bookingLocationStatus');
    const btnSubmitBooking = document.getElementById('btnSubmitBooking');

    // Toggle loại đặt lịch
    if (loaiDatLichRadios.length > 0) {
        loaiDatLichRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                const tinhSelect = document.getElementById('booking_tinh');
                const huyenSelect = document.getElementById('booking_huyen');
                const xaSelect = document.getElementById('booking_xa');
                const soNha = document.getElementById('booking_so_nha');

                if (e.target.value === 'at_home') {
                    atHomeGroup.classList.remove('d-none');
                    atStoreGroup.classList.add('d-none');
                    if (tinhSelect) tinhSelect.required = true;
                    if (huyenSelect) huyenSelect.required = true;
                    if (xaSelect) xaSelect.required = true;
                    if (soNha) soNha.required = true;
                } else {
                    atHomeGroup.classList.add('d-none');
                    atStoreGroup.classList.remove('d-none');
                    if (tinhSelect) { tinhSelect.required = false; tinhSelect.value = ''; }
                    if (huyenSelect) { huyenSelect.required = false; huyenSelect.value = ''; huyenSelect.disabled = true; }
                    if (xaSelect) { xaSelect.required = false; xaSelect.value = ''; xaSelect.disabled = true; }
                    if (soNha) { soNha.required = false; soNha.value = ''; }
                    document.getElementById('booking_vi_do').value = '';
                    document.getElementById('booking_kinh_do').value = '';
                    document.getElementById('booking_dia_chi').value = '';
                    bookingLocationStatus.textContent = 'Vui lòng ấn nút lấy vị trí tự động hoặc chọn thao tác tay từ trên xuống.';
                }
                checkHeavyItemTransport();

                // Cập nhật lại list giờ khả dụng khi hình thức thay đổi
                if (typeof updateAvailableTimeSlots === 'function') {
                    updateAvailableTimeSlots();
                }
            });
        });
    }

    // Load dữ liệu Tỉnh/Thành Việt Nam
    let addressData = [];
    const tinhSelect = document.getElementById('booking_tinh');
    const huyenSelect = document.getElementById('booking_huyen');
    const xaSelect = document.getElementById('booking_xa');

    async function loadAddressData() {
        if (addressData.length > 0) return; // Da load
        try {
            const res = await fetch('https://provinces.open-api.vn/api/?depth=3');
            addressData = await res.json();

            if (tinhSelect) {
                let html = '<option value="">Tỉnh/Thành phố</option>';
                addressData.forEach(tinh => {
                    html += `<option value="${tinh.name}" data-code="${tinh.code}">${tinh.name}</option>`;
                });
                tinhSelect.innerHTML = html;
            }
        } catch (error) {
            console.error("Lỗi khi tải dữ liệu tỉnh thành:", error);
            if (bookingLocationStatus) bookingLocationStatus.textContent = "Lỗi khi tải dữ liệu địa chỉ. Vui lòng thử lại sau.";
        }
    }

    if (tinhSelect) {
        tinhSelect.addEventListener('change', function () {
            huyenSelect.innerHTML = '<option value="">Quận/Huyện</option>';
            xaSelect.innerHTML = '<option value="">Phường/Xã</option>';
            xaSelect.disabled = true;

            const selectedOption = this.options[this.selectedIndex];
            if (!selectedOption.value) {
                huyenSelect.disabled = true;
                return;
            }

            const code = selectedOption.getAttribute('data-code');
            const tinh = addressData.find(t => t.code == code);
            if (tinh && tinh.districts) {
                let html = '<option value="">Quận/Huyện</option>';
                tinh.districts.forEach(h => {
                    html += `<option value="${h.name}" data-code="${h.code}">${h.name}</option>`;
                });
                huyenSelect.innerHTML = html;
                huyenSelect.disabled = false;
            }
        });
    }

    if (huyenSelect) {
        huyenSelect.addEventListener('change', function () {
            xaSelect.innerHTML = '<option value="">Phường/Xã</option>';

            const selectedOption = this.options[this.selectedIndex];
            if (!selectedOption.value) {
                xaSelect.disabled = true;
                return;
            }

            const tinhCode = tinhSelect.options[tinhSelect.selectedIndex].getAttribute('data-code');
            const tinh = addressData.find(t => t.code == tinhCode);
            if (tinh) {
                const code = selectedOption.getAttribute('data-code');
                const huyen = tinh.districts.find(d => d.code == code);
                if (huyen && huyen.wards) {
                    let html = '<option value="">Phường/Xã</option>';
                    huyen.wards.forEach(x => {
                        html += `<option value="${x.name}">${x.name}</option>`;
                    });
                    xaSelect.innerHTML = html;
                    xaSelect.disabled = false;
                }
            }
        });
    }

    // Helper: format YYYY-MM-DD from Date objects with local timezone
    function getLocalDateString(dateObj) {
        const y = dateObj.getFullYear();
        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
        const d = String(dateObj.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    // Populate Dịch vụ và thiết lập ngày giờ when modal opens
    if (bookingModal && bookingDichVuSelect) {
        bookingModal.addEventListener('show.bs.modal', async () => {
            loadAddressData();

            if (window.WORKER_ID) {
                document.getElementById('booking_tho_id').value = window.WORKER_ID;
            } else {
                document.getElementById('booking_tho_id').value = '';
            }

            const ngayHenInput = document.getElementById('booking_ngay_hen');
            if (ngayHenInput) {
                const now = new Date();
                const minDate = getLocalDateString(now);
                const next2Days = new Date(now.getTime() + 2 * 24 * 60 * 60 * 1000);
                const maxDate = getLocalDateString(next2Days);
                ngayHenInput.min = minDate;
                ngayHenInput.max = maxDate;
                if (!ngayHenInput.value || ngayHenInput.value < minDate || ngayHenInput.value > maxDate) {
                    ngayHenInput.value = minDate;
                }
            }
            updateAvailableTimeSlots();

            // Helper: find option by partial text match (case-insensitive)
            function preSelectService(keyword) {
                if (!keyword) return;
                const kw = keyword.toLowerCase();
                for (let opt of bookingDichVuSelect.options) {
                    if (opt.text.toLowerCase().includes(kw)) {
                        bookingDichVuSelect.value = opt.value;
                        bookingDichVuSelect.dispatchEvent(new Event('change'));
                        break;
                    }
                }
            }

            if (bookingDichVuSelect.options.length <= 1) {
                // Options not loaded yet — fetch then pre-select
                try {
                    const result = await callApi('/danh-muc-dich-vu', 'GET');
                    if (result.data) {
                        let html = '<option value="">-- Chọn dịch vụ --</option>';
                        result.data.forEach(cat => {
                            html += `<option value="${cat.id}">${cat.ten_dich_vu}</option>`;
                        });
                        bookingDichVuSelect.innerHTML = html;
                    }
                } catch (e) {
                    bookingDichVuSelect.innerHTML = '<option value="">Lỗi tải danh mục</option>';
                }
            }

            // Pre-select service after options are ready
            preSelectService(window.PRESELECT_SERVICE);
        });

        bookingDichVuSelect.addEventListener('change', checkHeavyItemTransport);
    }

    // Logic kiểm soát danh sách khung giờ trống theo Hình thức hẹn & Thời gian thực
    function updateAvailableTimeSlots() {
        const ngayHenInput = document.getElementById('booking_ngay_hen');
        const khungGioSelect = document.getElementById('booking_khung_gio_hen');
        const isAtHome = document.getElementById('loai_hom') && document.getElementById('loai_hom').checked;
        if (!ngayHenInput || !khungGioSelect) return;

        // Reset all options
        Array.from(khungGioSelect.options).forEach((opt, idx) => {
            if (idx > 0) { // Bỏ qua option đầu tiên placeholder
                opt.disabled = false;
                opt.hidden = false;
            }
        });

        const dateVal = ngayHenInput.value; // format YYYY-MM-DD
        if (!dateVal) return;

        const now = new Date();
        const todayStr = getLocalDateString(now);

        // Nếu ngày hẹn là tương lai (> hôm nay) thì luôn cho chọn mọi khung giờ
        if (dateVal > todayStr) {
            return;
        }

        // Nếu ngày hẹn <= hôm nay, tính toán khung giờ phù hợp
        const currentMinutes = now.getHours() * 60 + now.getMinutes();

        // Target Index: Xác định khung giờ kế tiếp hiện tại
        // 0: 08:00 (480), 1: 10:00 (600), 2: 12:00 (720), 3: 14:00 (840), 4: Hết giờ đặt
        let targetIndex = 0;
        if (currentMinutes < 480) targetIndex = 0;
        else if (currentMinutes < 600) targetIndex = 1;
        else if (currentMinutes < 720) targetIndex = 2;
        else if (currentMinutes < 840) targetIndex = 3;
        else targetIndex = 4;

        // "nếu đặt thợ tới nhà thì cách một khung giờ" (So với khung giờ kế tiếp)
        if (isAtHome) {
            targetIndex += 1;
        }

        let firstEnableValue = "";
        Array.from(khungGioSelect.options).forEach((opt, idx) => {
            if (idx > 0) {
                const actualIndex = idx - 1; // mapping sang index 0->3 logic của khung giờ
                if (actualIndex < targetIndex) {
                    opt.disabled = true;
                    opt.hidden = true;
                } else if (!firstEnableValue) {
                    firstEnableValue = opt.value;
                }
            }
        });

        // Tự động clear value nếu đang chọn phải khung giờ đã bị disable
        if (khungGioSelect.selectedIndex > 0 && khungGioSelect.options[khungGioSelect.selectedIndex].disabled) {
            khungGioSelect.value = firstEnableValue;
            // Nếu không còn khung giờ hợp lệ nào, gán rỗng báo cho user biết.
            if (!firstEnableValue && targetIndex >= 4) {
                khungGioSelect.value = "";
            }
        }
    }

    // Gắn listener lắng nghe sửa đổi ngày hẹn
    const bookingNgayHen = document.getElementById('booking_ngay_hen');
    if (bookingNgayHen) {
        bookingNgayHen.addEventListener('change', updateAvailableTimeSlots);
    }

    // Media Handling (Images & Video)
    const bookingImages = document.getElementById('booking_images');
    const bookingVideo = document.getElementById('booking_video');
    const mediaPreview = document.getElementById('mediaPreview');

    if (bookingImages) {
        bookingImages.addEventListener('change', function() {
            renderMediaPreview();
        });
    }

    if (bookingVideo) {
        bookingVideo.addEventListener('change', async function() {
            if (this.files.length > 0) {
                const duration = await getVideoDuration(this.files[0]);
                if (duration > 20) {
                    alert('Video không được vượt quá 20 giây. Vui lòng chọn video ngắn hơn.');
                    this.value = '';
                }
            }
            renderMediaPreview();
        });
    }

    function renderMediaPreview() {
        if (!mediaPreview) return;
        mediaPreview.innerHTML = '';
        
        // Render Images
        if (bookingImages && bookingImages.files.length > 0) {
            Array.from(bookingImages.files).forEach((file, index) => {
                if (index < 5) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'position-relative';
                        div.style.width = '60px';
                        div.style.height = '60px';
                        div.innerHTML = `
                            <img src="${e.target.result}" class="w-100 h-100 object-cover rounded border">
                            <span class="position-absolute top-0 end-0 badge rounded-pill bg-danger" style="margin: -5px -5px 0 0; cursor: pointer; transform: scale(0.7);" onclick="removeImage(${index})">×</span>
                        `;
                        mediaPreview.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                }
            });
        }

        // Render Video
        if (bookingVideo && bookingVideo.files.length > 0) {
            const div = document.createElement('div');
            div.className = 'position-relative';
            div.style.width = '60px';
            div.style.height = '60px';
            div.innerHTML = `
                <div class="w-100 h-100 bg-dark rounded border d-flex align-items-center justify-content-center text-white">
                    <i class="fas fa-video"></i>
                </div>
                <span class="position-absolute top-0 end-0 badge rounded-pill bg-danger" style="margin: -5px -5px 0 0; cursor: pointer; transform: scale(0.7);" onclick="removeVideo()">×</span>
            `;
            mediaPreview.appendChild(div);
        }
    }

    window.removeImage = function(index) {
        const dt = new DataTransfer();
        const { files } = bookingImages;
        for (let i = 0; i < files.length; i++) {
            if (i !== index) dt.items.add(files[i]);
        }
        bookingImages.files = dt.files;
        renderMediaPreview();
    }

    window.removeVideo = function() {
        bookingVideo.value = '';
        renderMediaPreview();
    }

    function getVideoDuration(file) {
        return new Promise((resolve) => {
            const video = document.createElement('video');
            video.preload = 'metadata';
            video.onloadedmetadata = function() {
                window.URL.revokeObjectURL(video.src);
                resolve(video.duration);
            }
            video.src = URL.createObjectURL(file);
        });
    }

    // Logic kiểm tra xe chở
    function checkHeavyItemTransport() {
        if (!bookingDichVuSelect || bookingDichVuSelect.selectedIndex === -1) return;
        const text = bookingDichVuSelect.options[bookingDichVuSelect.selectedIndex].text.toLowerCase();
        const keywords = ['máy giặt', 'tủ lạnh', 'tivi', 'máy lạnh', 'điều hòa'];
        const isHeavy = keywords.some(k => text.includes(k));

        const isAtStore = document.getElementById('loai_store') && document.getElementById('loai_store').checked;
        const transportGroup = document.getElementById('transportRentalGroup');
        const transportCheckbox = document.getElementById('booking_thue_xe_cho');

        if (transportGroup && transportCheckbox) {
            if (isHeavy && isAtStore) {
                transportGroup.classList.remove('d-none');
            } else {
                transportGroup.classList.add('d-none');
                transportCheckbox.checked = false;
            }
        }
    }

    // Auto string similarity comparison helper
    function findBestMatch(targetStr, listStr) {
        if (!targetStr || !listStr || listStr.length === 0) return null;
        let match = "";
        let maxMatchCount = 0;

        targetStr = targetStr.toLowerCase();
        const targetWords = targetStr.replace(/(tỉnh|thành phố|quận|huyện|thị xã|phường|xã|thị trấn)/gi, "").trim();

        listStr.forEach(str => {
            let itemWords = str.toLowerCase().replace(/(tỉnh|thành phố|quận|huyện|thị xã|phường|xã|thị trấn)/gi, "").trim();
            if (itemWords === targetWords || targetStr.includes(itemWords) || itemWords.includes(targetWords)) {
                if (itemWords.length > maxMatchCount) {
                    match = str;
                    maxMatchCount = itemWords.length;
                }
            }
        });
        return match || listStr[0]; // fallback to first item
    }

    // Function chọn tự động option select dựa trên text
    function autoSelectOptionByText(selectEl, textToFind) {
        if (!selectEl || !textToFind) return;
        const optionsList = Array.from(selectEl.options).map(o => o.value).filter(val => val !== "");
        const matchedVal = findBestMatch(textToFind, optionsList);
        if (matchedVal) {
            selectEl.value = matchedVal;
            // Xóa required nếu auto select dc (phòng ngừa user touch thủ công lỗi)
            selectEl.dispatchEvent(new Event('change'));
        }
    }

    // Lấy vị trí Booking
    if (btnBookingGetLocation) {
        btnBookingGetLocation.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert("Trình duyệt không hỗ trợ định vị.");
                return;
            }
            bookingLocationStatus.textContent = "Đang lấy tọa độ GPS...";
            btnBookingGetLocation.disabled = true;

            navigator.geolocation.getCurrentPosition(
                async (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    document.getElementById('booking_vi_do').value = lat;
                    document.getElementById('booking_kinh_do').value = lng;

                    // Simple Reverse Geocoding via Nominatim (Free)
                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`);
                        const data = await res.json();

                        if (data && data.address) {
                            bookingLocationStatus.innerHTML = `<span class="text-success"><i class="fas fa-check-circle"></i> Đã lấy vị trí thành công</span>`;
                            const addr = data.address;

                            // 1. Tự động set Tỉnh
                            const pName = addr.city || addr.state || addr.province;
                            if (pName) {
                                autoSelectOptionByText(tinhSelect, pName);

                                // Đợi 100ms để gen Huyện
                                setTimeout(() => {
                                    // 2. Tự động set Huyện
                                    const dName = addr.county || addr.district || addr.suburb || addr.town;
                                    if (dName) {
                                        autoSelectOptionByText(huyenSelect, dName);

                                        // Đợi 100ms gen Xã
                                        setTimeout(() => {
                                            // 3. Tự động set Xã
                                            const wName = addr.village || addr.suburb || addr.quarter || addr.hamlet || addr.neighbourhood;
                                            if (wName) {
                                                autoSelectOptionByText(xaSelect, wName);
                                            }
                                        }, 100);
                                    }
                                }, 100);
                            }

                            // Lấy số nhà/đường nếu có
                            let streetAddress = "";
                            if (addr.house_number) streetAddress += addr.house_number + " ";
                            if (addr.road) streetAddress += addr.road;
                            if (streetAddress) {
                                document.getElementById('booking_so_nha').value = streetAddress.trim();
                            }

                        } else {
                            // Fallback
                            bookingLocationStatus.textContent = "Không thể phân giải cụ thể địa chỉ JSON. Vui lòng chọn tay.";
                        }
                    } catch (e) {
                        bookingLocationStatus.textContent = "Không kết nối API lấy địa chỉ. Vui lòng chọn tay.";
                    }
                    btnBookingGetLocation.disabled = false;
                },
                (err) => {
                    bookingLocationStatus.textContent = "Không lấy được vị trí. Vui lòng chọn địa chỉ thủ công.";
                    btnBookingGetLocation.disabled = false;
                }
            );
        });
    }

    // Submit Booking
    if (formBooking) {
        formBooking.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Validation
            if (document.getElementById('loai_hom') && document.getElementById('loai_hom').checked) {
                const soNha = document.getElementById('booking_so_nha').value;
                const lat = document.getElementById('booking_vi_do').value;
                const tinh = document.getElementById('booking_tinh').value;
                const huyen = document.getElementById('booking_huyen').value;
                const xa = document.getElementById('booking_xa').value;

                if (!lat) {
                    alert('Vui lòng ấn [Tự động lấy vị trí hiện tại] để hệ thống tính khoảng cách (Yêu cầu bắt buộc < 5km).');
                    return;
                }
                if (!tinh || !huyen || !xa) {
                    alert('Vui lòng chọn đầy đủ Tỉnh/Huyện/Xã.');
                    return;
                }
                if (!soNha) {
                    alert('Vui lòng điền chi tiết Số nhà, Tên Đường/Ngõ.');
                    return;
                }

                // Append address text string
                document.getElementById('booking_dia_chi').value = `${xa}, ${huyen}, ${tinh}`;
            }

            // Media Validation
            const imageInput = document.getElementById('booking_images');
            if (imageInput.files.length > 5) {
                alert('Bạn chỉ có thể chọn tối đa 5 hình ảnh.');
                return;
            }

            const videoInput = document.getElementById('booking_video');
            if (videoInput.files.length > 0) {
                const videoFile = videoInput.files[0];
                const videoDuration = await getVideoDuration(videoFile);
                if (videoDuration > 20) {
                    alert('Video không được vượt quá 20 giây. Vui lòng chọn video ngắn hơn.');
                    return;
                }
            }

            btnSubmitBooking.disabled = true;
            btnSubmitBooking.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';

            const formData = new FormData(formBooking);
            
            // Ép kiểu boolean cho checkbox tải xe
            const thueXe = document.getElementById('booking_thue_xe_cho').checked ? 1 : 0;
            formData.set('thue_xe_cho', thueXe);

            // Xử lý gộp chuỗi địa chỉ
            if (document.getElementById('loai_hom').checked) {
                const diaChi = `${formData.get('so_nha')}, ${formData.get('dia_chi')}`;
                formData.set('dia_chi', diaChi);
                formData.delete('so_nha');
            }

            if (!formData.get('tho_id')) formData.delete('tho_id');

            try {
                const res = await callApi('/don-dat-lich', 'POST', formData);

                if (!res.ok) {
                    // Show specific API error message (validation errors, distance error, etc.)
                    const errData = res.data;
                    if (errData && errData.errors) {
                        alert(Object.values(errData.errors).flat().join('\n'));
                    } else if (errData && errData.message) {
                        alert(errData.message);
                    } else {
                        alert('Có lỗi xảy ra khi đặt lịch. (HTTP ' + res.status + ')');
                    }
                    return;
                }

                // res.data = { message: '...', data: booking }
                const booking = res.data.data ?? res.data;
                alert(`Đặt lịch thành công! Mã đơn: #${booking.id}`);
                bootstrap.Modal.getInstance(bookingModal).hide();
                formBooking.reset();
                atHomeGroup.classList.remove('d-none');
                atStoreGroup.classList.add('d-none');

                setTimeout(() => {
                    window.location.href = '/customer/my-bookings';
                }, 1000);
            } catch (err) {
                console.error('Booking error:', err);
                alert(err.message || 'Mất kết nối đến máy chủ. Vui lòng thử lại.');
            } finally {
                btnSubmitBooking.disabled = false;
                btnSubmitBooking.innerHTML = 'Xác nhận Đặt Lịch';
            }
        });
    }
});
