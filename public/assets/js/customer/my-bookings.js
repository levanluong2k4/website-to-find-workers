import { callApi, getCurrentUser, showToast, confirmAction } from '../api.js';

const user = getCurrentUser();
if (!user || !['customer', 'admin'].includes(user.role)) {
    window.location.href = '/login';
}

document.addEventListener('DOMContentLoaded', () => {
    const bookingsContainer = document.getElementById('myBookingsContainer');
    const tabs = document.querySelectorAll('#bookingTab .nav-link');
    let allBookings = [];
    let currentFilter = 'all';

    // Check payment return status
    const urlParams = new URLSearchParams(window.location.search);
    const paymentStatus = urlParams.get('payment');
    if (paymentStatus === 'success') {
        Swal.fire({
            title: 'Thanh toán Thành công!',
            text: 'Cảm ơn bạn đã sử dụng dịch vụ. Giao dịch trực tuyến qua VNPAY đã hoàn tất.',
            icon: 'success',
            confirmButtonText: 'Đóng'
        });
        // Clear params cleanly
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (paymentStatus === 'failed') {
        Swal.fire({
            title: 'Thanh toán Thất bại',
            text: 'Giao dịch bị hủy hoặc xảy ra lỗi trong quá trình thanh toán VNPay.',
            icon: 'error',
            confirmButtonText: 'Đóng'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (paymentStatus === 'invalid_signature') {
        Swal.fire({
            title: 'Lỗi Chữ Ký',
            text: 'Mã băm giao dịch không khớp. Giao dịch đã bị từ chối.',
            icon: 'error',
            confirmButtonText: 'Đóng'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // UI Elements for Review Modal
    const modalReviewEl = document.getElementById('modalReview');
    const formReview = document.getElementById('formReview');
    const reviewBookingId = document.getElementById('reviewBookingId');
    const reviewWorkerName = document.getElementById('reviewWorkerName');
    const btnSubmitReview = document.getElementById('btnSubmitReview');
    let reviewModalInstance = null;

    if (modalReviewEl) {
        reviewModalInstance = new bootstrap.Modal(modalReviewEl);
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
            allBookings = res.data?.data || res.data || [];

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
            filtered = allBookings.filter(b => ['cho_hoan_thanh', 'da_xong'].includes(b.trang_thai));
        }

        renderBookings(filtered);
    };

    const renderBookings = (bookings) => {
        bookingsContainer.innerHTML = '';

        if (bookings.length === 0) {
            bookingsContainer.innerHTML = `
                <div class="col-12">
                    <div class="empty-state text-center py-5 shadow-sm bg-white rounded-4 border">
                        <div class="d-inline-flex justify-content-center align-items-center rounded-circle mb-3" style="width: 80px; height: 80px; background-color: #f1f5f9;">
                            <span class="material-symbols-outlined text-secondary" style="font-size: 40px;">inbox</span>
                        </div>
                        <h5 class="fw-bold text-dark">Chưa có đơn hàng nào</h5>
                        <p class="text-muted mb-4">Bạn chưa có lịch hẹn nào lưu trong danh sách này.</p>
                        <a href="/customer/home" class="btn btn-primary rounded-pill px-4">Tìm thợ sửa chữa ngay</a>
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
                case 'cho_xac_nhan': statusLabel = 'Chờ xác nhận (Đang gọi thợ)'; break;
                case 'da_xac_nhan': statusLabel = 'Đã có thợ nhận'; break;
                case 'dang_lam': statusLabel = 'Đang xử lý'; break;
                case 'cho_hoan_thanh': statusLabel = 'Thợ báo xong. Chờ bạn TT'; break;
                case 'da_xong': statusLabel = 'Hoàn tất'; break;
                case 'da_huy': statusLabel = 'Đã hủy'; break;
            }

            let actionHtml = '';

            if (booking.trang_thai === 'cho_xac_nhan') {
                actionHtml = `<button class="btn btn-outline-danger btn-sm fw-bold px-3 btn-cancel" data-id="${booking.id}">Hủy Đơn</button>`;
            } else if (booking.trang_thai === 'cho_hoan_thanh' || booking.trang_thai === 'cho_thanh_toan') {
                actionHtml = `
                    <div class="d-flex flex-column gap-2 w-100">
                        <button class="btn btn-outline-success btn-sm fw-bold px-3 btn-pay" data-id="${booking.id}"><i class="fas fa-hand-holding-usd"></i> Trả Tiền Mặt</button>
                        <button class="btn btn-primary btn-sm fw-bold px-3 btn-vnpay" data-id="${booking.id}"><i class="fas fa-credit-card"></i> VNPAY</button>
                        <button class="btn btn-danger btn-sm fw-bold px-3 btn-momo" data-id="${booking.id}"><i class="fas fa-wallet"></i> MOMO</button>
                        <button class="btn btn-info btn-sm fw-bold text-white px-3 btn-zalopay" data-id="${booking.id}" style="background-color: #0068ff; border-color: #0068ff;"><i class="fas fa-qrcode"></i> ZALOPAY</button>
                    </div>`;
            } else if (booking.trang_thai === 'da_xong') {
                const hasReviewed = booking.danh_gias && booking.danh_gias.length > 0;

                if (!hasReviewed) {
                    actionHtml = `<button class="btn btn-warning btn-sm fw-bold text-dark px-3 btn-review" 
                                    data-id="${booking.id}" 
                                    data-worker="${booking.tho ? booking.tho.name : 'Thợ'}">
                                    <i class="fas fa-star text-white"></i> Đánh giá Thợ
                                  </button>`;
                } else {
                    const review = booking.danh_gias[0];
                    actionHtml = `
                        <div class="text-warning">
                             ${'<i class="fas fa-star"></i>'.repeat(review.so_sao)}
                             ${'<i class="far fa-star"></i>'.repeat(5 - review.so_sao)}
                             <span class="text-success ms-2 fw-bold"><i class="fas fa-check-circle"></i> Đã đánh giá</span>
                        </div>
                    `;
                }
            } else if (booking.trang_thai === 'da_xac_nhan') {
                actionHtml = `<button class="btn btn-outline-danger btn-sm fw-bold px-3 btn-cancel" data-id="${booking.id}">Hủy Đơn</button>`;
            }

            const total = Number(booking.phi_di_lai || 0) + Number(booking.phi_linh_kien || 0);

            const card = document.createElement('div');
            card.className = 'col-lg-6';
            card.innerHTML = `
                <div class="booking-card h-100 p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-3">
                        <div class="d-flex align-items-center gap-3">
                            <img src="${booking.tho?.avatar || '/assets/images/user-default.png'}" alt="Thợ" class="rounded-circle border" width="55" height="55" style="object-fit:cover;">
                            <div>
                                <h6 class="fw-bold mb-1 fs-5 text-dark">${booking.tho?.name || 'Đang tìm thợ...'}</h6>
                                <p class="text-muted mb-0 small"><i class="fas fa-phone-alt me-1"></i> ${booking.tho?.phone || 'Chưa có'}</p>
                            </div>
                        </div>
                        <span class="status-badge ${statusClass}">${statusLabel}</span>
                    </div>

                    <div class="mb-3 flex-grow-1">
                        ${booking.thue_xe_cho ? '<div class="mb-2"><span class="badge bg-info text-dark border border-info-subtle"><i class="fas fa-truck me-1"></i> Có yêu cầu tự thuê xe chở</span></div>' : ''}
                        
                        <h5 class="fw-bold text-primary mb-3">${booking.dichVu?.ten_dich_vu || 'Sửa chữa điện máy'}</h5>
                        
                        <div class="d-flex align-items-center mb-2 text-secondary" style="font-size: 0.95rem;">
                            <i class="fas fa-calendar-alt text-muted me-2" style="width: 20px;"></i>
                            ${dateString} • ${booking.khung_gio_hen}
                        </div>
                        
                        <div class="d-flex align-items-start mb-3 text-secondary" style="font-size: 0.95rem;">
                            <i class="fas fa-map-marker-alt text-muted me-2 mt-1" style="width: 20px;"></i>
                            <span class="text-truncate-2">${booking.loai_dat_lich === 'at_home' ? booking.dia_chi : 'Bạn phải đem đồ tới Cửa hàng (2 Đ. Nguyễn Đình Chiểu)'}</span>
                        </div>
                        
                        ${booking.mo_ta_van_de ? `
                            <div class="bg-light rounded p-2 mb-2 text-secondary" style="font-size: 0.9rem; border-left: 3px solid var(--bs-gray-400);">
                                "${booking.mo_ta_van_de}"
                            </div>
                        ` : ''}

                        ${(booking.hinh_anh_mo_ta?.length > 0 || booking.video_mo_ta) ? `
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                ${booking.video_mo_ta ? `
                                    <div class="rounded border bg-dark d-flex align-items-center justify-content-center text-white" style="width: 40px; height: 40px; cursor: pointer;" onclick="window.open('${booking.video_mo_ta}', '_blank')">
                                        <i class="fas fa-video small"></i>
                                    </div>
                                ` : ''}
                                ${(booking.hinh_anh_mo_ta || []).map(img => `
                                    <img src="${img}" class="rounded border object-cover" style="width: 40px; height: 40px; cursor: pointer;" onclick="window.open('${img}', '_blank')">
                                `).join('')}
                            </div>
                        ` : ''}

                        <div class="bg-light rounded-3 p-3 mt-3 border">
                            <div class="d-flex justify-content-between text-secondary mb-1">
                                <span>Phí đi lại (Khoảng cách):</span>
                                <strong>${formatMoney(booking.phi_di_lai)}</strong>
                            </div>
                            <div class="d-flex justify-content-between text-secondary mb-2">
                                <span>Phụ phí linh kiện báo thêm:</span>
                                <strong>${formatMoney(booking.phi_linh_kien)}</strong>
                            </div>
                            <div class="d-flex justify-content-between text-dark pt-2 border-top">
                                <span class="fw-bold">Tổng phụ phí dự kiến:</span>
                                <strong class="fs-5 text-danger">${formatMoney(total)}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-end">
                        ${actionHtml}
                    </div>
                </div>
            `;
            bookingsContainer.appendChild(card);
        });

        attachActionListeners();
    };

    const attachActionListeners = () => {
        document.querySelectorAll('.btn-cancel').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const id = e.target.getAttribute('data-id');
                const { isConfirmed, value: reason } = await Swal.fire({
                    title: 'Hủy đơn đặt lịch?',
                    text: 'Bạn có chắc chắn muốn hủy yêu cầu này?',
                    icon: 'warning',
                    input: 'textarea',
                    inputLabel: 'Lý do hủy (tùy chọn)',
                    inputPlaceholder: 'Nhập lý do tại đây...',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Có, Hủy đơn',
                    cancelButtonText: 'Đóng'
                });

                if (isConfirmed) {
                    try {
                        await callApi(`/don-dat-lich/${id}/status`, 'PUT', {
                            trang_thai: 'da_huy',
                            ly_do_huy: reason
                        });
                        showToast('Hủy đơn thành công');
                        loadBookings();
                    } catch (err) {
                        showToast(err.message || 'Lỗi khi hủy đơn', 'error');
                    }
                }
            });
        });

        document.querySelectorAll('.btn-pay').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const id = e.target.getAttribute('data-id');
                const { isConfirmed } = await Swal.fire({
                    title: 'Xác nhận hoàn tất',
                    text: 'Khẳng định thợ đã sửa chữa xong và bạn đồng ý thanh toán tiền mặt/chuyển khoản cho thợ?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Đồng ý',
                    cancelButtonText: 'Đóng'
                });
                if (isConfirmed) {
                    try {
                        await callApi(`/don-dat-lich/${id}/status`, 'PUT', { trang_thai: 'da_xong' });
                        showToast('Xác nhận hoàn tất thành công!');
                        loadBookings();
                    } catch (err) {
                        showToast(err.message || 'Lỗi', 'error');
                    }
                }
            });
        });

        document.querySelectorAll('.btn-vnpay').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const id = e.target.getAttribute('data-id');
                try {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang tạo GD...';

                    const res = await callApi(`/payment/create`, 'POST', {
                        don_dat_lich_id: id,
                        phuong_thuc: 'vnpay'
                    });

                    if (res.url) {
                        window.location.href = res.url;
                    } else {
                        showToast('Không nhận được URL giao dịch', 'error');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-credit-card"></i> Thanh Toán VNPAY';
                    }
                } catch (err) {
                    showToast(err.message || 'Lỗi kết nối cổng thanh toán', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-credit-card"></i> Thanh Toán VNPAY';
                }
            });
        });

        document.querySelectorAll('.btn-momo').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const id = e.target.getAttribute('data-id');
                try {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang tạo GD...';

                    const res = await callApi(`/payment/create`, 'POST', {
                        don_dat_lich_id: id,
                        phuong_thuc: 'momo'
                    });

                    if (res.url) {
                        window.location.href = res.url;
                    } else {
                        showToast('Không tạo được giao dịch MoMo', 'error');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-wallet"></i> MOMO';
                    }
                } catch (err) {
                    showToast(err.message || 'Lỗi MoMo', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-wallet"></i> MOMO';
                }
            });
        });

        document.querySelectorAll('.btn-zalopay').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const id = e.target.getAttribute('data-id');
                try {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang tạo GD...';

                    const res = await callApi(`/payment/create`, 'POST', {
                        don_dat_lich_id: id,
                        phuong_thuc: 'zalopay'
                    });

                    if (res.url) {
                        window.location.href = res.url;
                    } else {
                        showToast('Không tạo được giao dịch ZaloPay', 'error');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-qrcode"></i> ZALOPAY';
                    }
                } catch (err) {
                    showToast(err.message || 'Lỗi ZaloPay', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-qrcode"></i> ZALOPAY';
                }
            });
        });

        document.querySelectorAll('.btn-review').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.target.getAttribute('data-id');
                const workerName = e.target.getAttribute('data-worker');

                reviewBookingId.value = id;
                reviewWorkerName.innerHTML = `Hãy cho chúng tôi biết cảm nhận của bạn về thợ <strong>${workerName}</strong>`;
                formReview.reset();

                if (reviewModalInstance) {
                    reviewModalInstance.show();
                }
            });
        });
    };

    if (formReview) {
        formReview.addEventListener('submit', async (e) => {
            e.preventDefault();

            const soSaoEl = document.querySelector('input[name="so_sao"]:checked');
            if (!soSaoEl) {
                showToast('Vui lòng chọn số sao Đánh giá', 'error');
                return;
            }

            const dataObj = {
                don_dat_lich_id: reviewBookingId.value,
                so_sao: soSaoEl.value,
                nhan_xet: document.getElementById('reviewComment').value
            };

            btnSubmitReview.disabled = true;
            btnSubmitReview.innerHTML = 'Đang gửi...';

            try {
                await callApi('/danh-gia', 'POST', dataObj);

                showToast('Cảm ơn bạn đã đánh giá!');
                reviewModalInstance.hide();
                loadBookings();
            } catch (err) {
                showToast(err.message || 'Lỗi gửi đánh giá', 'error');
            } finally {
                btnSubmitReview.disabled = false;
                btnSubmitReview.innerHTML = 'Gửi Đánh Giá';
            }
        });
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            tabs.forEach(t => t.classList.remove('active'));
            e.target.classList.add('active');
            currentFilter = e.target.getAttribute('data-filter');
            filterAndRender();
        });
    });

    loadBookings();
});
