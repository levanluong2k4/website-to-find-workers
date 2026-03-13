import { callApi, showToast, getCurrentUser } from '../api.js';

const user = getCurrentUser();
if (!user || !['worker', 'admin'].includes(user.role)) {
    window.location.href = '/login?role=worker';
}

document.addEventListener('DOMContentLoaded', () => {
    const bookingsContainer = document.getElementById('myBookingsContainer');
    const tabs = document.querySelectorAll('#bookingTab .nav-link');
    let allBookings = [];
    let currentFilter = 'all';

    // Elements for Modal
    const formUpdateCosts = document.getElementById('formUpdateCosts');
    const inputPhiLinhKien = document.getElementById('inputPhiLinhKien');
    const inputGhiChuLinhKien = document.getElementById('inputGhiChuLinhKien');
    const inputTienCong = document.getElementById('inputTienCong');
    const inputTienThueXe = document.getElementById('inputTienThueXe');
    const truckFeeContainer = document.getElementById('truckFeeContainer');
    const displayPhiDiLai = document.getElementById('displayPhiDiLai');
    const costBookingId = document.getElementById('costBookingId');
    let costModalInstance = null;

    if (typeof bootstrap !== 'undefined') {
        costModalInstance = new bootstrap.Modal(document.getElementById('modalCosts'));
    }

    const loadBookings = async () => {
        try {
            bookingsContainer.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            `;

            const res = await callApi('/don-dat-lich');
            if (res.ok) {
                allBookings = res.data?.data ? res.data.data : (res.data || []);
            } else {
                throw new Error("Lỗi tải đơn");
            }

            filterAndRender();
        } catch (error) {
            console.error('Error fetching my bookings:', error);
            bookingsContainer.innerHTML = `
                <div class="col-12 text-center py-5">
                    <p class="text-danger mb-0">Lỗi khi tải danh sách đơn. Vui lòng thử lại.</p>
                </div>
            `;
        }
    };

    const filterAndRender = () => {
        let filtered = allBookings;
        if (currentFilter === 'active') {
            filtered = allBookings.filter(b => ['cho_xac_nhan', 'da_xac_nhan', 'dang_lam'].includes(b.trang_thai));
        } else if (currentFilter === 'completed') {
            filtered = allBookings.filter(b => ['cho_hoan_thanh', 'cho_thanh_toan', 'da_xong'].includes(b.trang_thai));
        }

        renderBookings(filtered);
    };

    const renderBookings = (bookings) => {
        bookingsContainer.innerHTML = '';

        if (bookings.length === 0) {
            bookingsContainer.innerHTML = `
                <div class="col-12">
                    <div class="empty-state shadow-sm">
                        <div class="d-inline-flex justify-content-center align-items-center rounded-circle mb-3" style="width: 80px; height: 80px; background-color: #f1f5f9;">
                            <span class="material-symbols-outlined text-secondary" style="font-size: 40px;">inbox</span>
                        </div>
                        <h5 class="fw-bold text-dark">Không có đơn nào</h5>
                        <p class="text-muted mb-0">Chưa có đơn hàng nào trong phân loại này.</p>
                    </div>
                </div>
            `;
            return;
        }

        bookings.forEach(booking => {
            const dateObj = new Date(booking.ngay_hen);
            const dateString = dateObj.toLocaleDateString('vi-VN');

            const formatMoney = (val) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);

            let statusLabel = '';
            let statusClass = `status-${booking.trang_thai}`;

            switch (booking.trang_thai) {
                case 'cho_xac_nhan': statusLabel = 'Chờ xác nhận'; break;
                case 'da_xac_nhan': statusLabel = 'Đã nhận chạy'; break;
                case 'dang_lam': statusLabel = 'Đang xử lý'; break;
                case 'cho_hoan_thanh':
                case 'cho_thanh_toan': statusLabel = 'Chờ khách thanh toán'; break;
                case 'da_xong': statusLabel = 'Hoàn tất'; break;
                case 'da_huy': statusLabel = 'Đã hủy'; break;
            }

            let actionHtml = '';
            if (booking.trang_thai === 'cho_xac_nhan') {
                actionHtml = `<button class="btn btn-primary btn-sm fw-bold w-100" onclick="updateStatus(${booking.id}, 'da_xac_nhan')">Bắt đầu đi lại</button>`;
            } else if (booking.trang_thai === 'da_xac_nhan') {
                actionHtml = `<button class="btn btn-warning btn-sm fw-bold w-100" onclick="updateStatus(${booking.id}, 'dang_lam')">Đã tới nơi, Bắt đầu sửa</button>`;
                actionHtml = `
                    <div class="d-flex flex-column gap-2 w-100">
                        <button class="btn btn-outline-primary btn-sm fw-bold w-100" onclick="openCostModal(${booking.id})">Cập nhật chi phí</button>
                        <button class="btn btn-success btn-sm fw-bold w-100" onclick="requestPayment(${booking.id})">Sửa xong, Yêu cầu trả tiền</button>
                    </div>`;
            } else if (booking.trang_thai === 'cho_thanh_toan' || booking.trang_thai === 'cho_hoan_thanh') {
                actionHtml = `
                    <div class="d-flex flex-column gap-2 w-100">
                        <button class="btn btn-primary btn-sm fw-bold w-100 mb-2" onclick="confirmCashPayment(${booking.id})"><i class="fas fa-money-bill-wave me-1"></i> Đã thu tiền mặt</button>
                    </div>
                `;
            }

            const card = document.createElement('div');
            card.className = 'col-lg-6 mb-4';
            card.innerHTML = `
                <div class="booking-card h-100 p-4 d-flex flex-column shadow-sm rounded border">
                    <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-3">
                        <div class="d-flex align-items-center gap-3">
                            <img src="${booking.khach_hang?.avatar || '/assets/images/user-default.png'}" alt="Avatar" class="rounded-circle" width="50" height="50" style="object-fit:cover;">
                            <div>
                                <h6 class="fw-bold mb-1 fs-5">${booking.khach_hang?.name || 'Khách Hàng'}</h6>
                                <p class="text-muted mb-0 small"><i class="fas fa-phone-alt me-1"></i> ${booking.khach_hang?.phone || 'Chưa có SĐT'}</p>
                            </div>
                        </div>
                        <span class="badge ${statusClass}">${statusLabel}</span>
                    </div>

                    <div class="mb-3 flex-grow-1">
                        ${booking.thue_xe_cho ? '<div class="mb-2"><span class="badge bg-info text-dark px-2 py-1 border border-info-subtle shadow-sm"><i class="fas fa-truck me-1"></i> Khách yêu cầu thuê xe tải chở thiết bị</span></div>' : ''}
                        <h6 class="fw-bold text-dark mb-2">${booking.dich_vu?.ten_dich_vu || 'Dịch vụ'}</h6>
                        <div class="d-flex align-items-center mb-2 text-secondary" style="font-size: 0.9rem;">
                            <span class="material-symbols-outlined me-2 fs-5">event</span> ${dateString} • ${booking.khung_gio_hen}
                        </div>
                        <div class="d-flex align-items-start mb-2 text-secondary" style="font-size: 0.9rem;">
                            <span class="material-symbols-outlined me-2 fs-5 flex-shrink-0">location_on</span>
                            <span class="text-truncate-2">${booking.loai_dat_lich === 'at_home' ? booking.dia_chi : 'Sửa tại Cửa Hàng'}</span>
                        </div>
                        
                        <div class="bg-light rounded p-3 mt-3">
                            <p class="mb-1 fw-bold text-dark fs-6 border-bottom pb-2">Chi tiết chi phí</p>
                            <div class="d-flex justify-content-between text-secondary mt-2">
                                <span>Tiền công thợ:</span>
                                <strong>${formatMoney(booking.tien_cong)}</strong>
                            </div>
                            <div class="d-flex justify-content-between text-secondary mt-1">
                                <span>Phí đi lại (hệ thống tính):</span>
                                <strong>${formatMoney(booking.phi_di_lai)}</strong>
                            </div>
                            ${booking.thue_xe_cho ? `
                            <div class="d-flex justify-content-between text-secondary mt-1">
                                <span>Phí thuê xe chở:</span>
                                <strong>${formatMoney(booking.tien_thue_xe)}</strong>
                            </div>` : ''}
                            <div class="d-flex justify-content-between text-secondary mt-1">
                                <span>Phí phát sinh linh kiện:</span>
                                <strong>${formatMoney(booking.phi_linh_kien)}</strong>
                            </div>
                            <div class="d-flex justify-content-between text-dark mt-2 pt-2 border-top">
                                <span class="fw-bold">Tổng chi phí dự kiến:</span>
                                <strong class="fs-5 text-primary">${formatMoney(Number(booking.phi_di_lai || 0) + Number(booking.phi_linh_kien || 0) + Number(booking.tien_cong || 0) + Number(booking.tien_thue_xe || 0))}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        ${actionHtml}
                    </div>
                </div>
            `;
            bookingsContainer.appendChild(card);
        });
    };

    // Tab Events
    tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            tabs.forEach(t => t.classList.remove('active'));
            e.target.classList.add('active');
            currentFilter = e.target.dataset.status;
            filterAndRender();
        });
    });

    // Global Functions
    window.updateStatus = async (id, newStatus) => {
        if (!confirm('Xác nhận cập nhật trạng thái đơn hàng này?')) return;

        try {
            await callApi(`/don-dat-lich/${id}/status`, 'PUT', { trang_thai: newStatus });
            loadBookings();
        } catch (error) {
            alert('Lỗi cập nhật trạng thái. Hoặc bạn không có quyền nảy cóc trạng thái.');
            console.error(error);
        }
    };

    window.requestPayment = async (id) => {
        if (!confirm('Bạn đã hoàn tất công việc và muốn yêu cầu khách thanh toán?')) return;
        try {
            await callApi(`/bookings/${id}/request-payment`, 'POST');
            showToast('Đã gửi yêu cầu thanh toán cho khách hàng.', 'success');
            loadBookings();
        } catch (error) {
            showToast(error.message || 'Lỗi gửi yêu cầu thanh toán', 'error');
        }
    };

    window.confirmCashPayment = async (id) => {
        if (!confirm('Xác nhận ĐÃ NHẬN ĐỦ TIỀN MẶT từ khách hàng? Đơn sẽ được đóng ngay lập tức.')) return;
        try {
            await callApi(`/bookings/${id}/confirm-cash`, 'POST');
            showToast('Đã xác nhận thu tiền mặt và đóng đơn hàng.', 'success');
            loadBookings();
        } catch (error) {
            showToast(error.message || 'Lỗi xác nhận tiền mặt', 'error');
        }
    };

    window.openCostModal = (id) => {
        const booking = allBookings.find(b => b.id === id);
        if (!booking) return;

        costBookingId.value = id;
        inputTienCong.value = booking.tien_cong || 0;
        inputPhiLinhKien.value = booking.phi_linh_kien || 0;
        inputGhiChuLinhKien.value = booking.ghi_chu_linh_kien || '';

        const formatMoney = (val) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
        displayPhiDiLai.textContent = formatMoney(booking.phi_di_lai);

        if (booking.thue_xe_cho) {
            truckFeeContainer.style.display = 'block';
            inputTienThueXe.value = booking.tien_thue_xe || 0;
        } else {
            truckFeeContainer.style.display = 'none';
            inputTienThueXe.value = 0;
        }

        if (costModalInstance) {
            costModalInstance.show();
        }
    };

    // Form Update Costs Event
    if (formUpdateCosts) {
        formUpdateCosts.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = costBookingId.value;
            const formData = {
                tien_cong: inputTienCong.value || 0,
                phi_linh_kien: inputPhiLinhKien.value || 0,
                ghi_chu_linh_kien: inputGhiChuLinhKien.value || ''
            };

            if (truckFeeContainer.style.display !== 'none') {
                formData.tien_thue_xe = inputTienThueXe.value || 0;
            }

            try {
                const res = await callApi(`/don-dat-lich/${id}/update-costs`, 'PUT', formData);

                if (res.ok) {
                    if (costModalInstance) {
                        costModalInstance.hide();
                    }
                    loadBookings();
                } else {
                    const errorMsg = res.data?.message || res.message || 'Lỗi khi cập nhật phí.';
                    alert(errorMsg);
                }
            } catch (error) {
                alert('Lỗi khi cập nhật phí. Đơn này phải đang ở trạng thái Đang Làm mới được.');
                console.error(error);
            }
        });
    }

    // Init
    loadBookings();
});
