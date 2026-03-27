import { callApi, requireRole } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const tbody = document.getElementById('bookingsTableBody');
    const statusFilter = document.getElementById('statusFilter');
    const btnRefresh = document.getElementById('btnRefresh');

    const fetchBookings = async () => {
        try {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 mb-0">Đang tải biểu ghi...</p>
                    </td>
                </tr>`;

            const status = statusFilter.value;
            const query = status ? `?status=${status}` : '';

            const res = await callApi(`/admin/bookings${query}`);

            if (res.ok && res.data && res.data.data) {
                renderBookings(res.data.data);
            } else if (res.ok && res.data) {
                // Fallback in case Backend returns array directly
                renderBookings(res.data.data || res.data);
            }
        } catch (error) {
            console.error('Fetch bookings error:', error);
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Lỗi kết nối máy chủ!</td></tr>`;
        }
    };

    const getStatusHtml = (status) => {
        const map = {
            'cho_tho_nhan': '<span class="status-badge bg-secondary bg-opacity-10 text-secondary"><i class="fas fa-search me-1"></i>Chờ thợ nhận</span>',
            'da_xac_nhan': '<span class="status-badge bg-primary bg-opacity-10 text-primary"><i class="fas fa-calendar-check me-1"></i>Đã nhận việc</span>',
            'dang_thuc_hien': '<span class="status-badge bg-warning bg-opacity-10 text-warning"><i class="fas fa-tools me-1"></i>Đang sửa chữa</span>',
            'hoan_thanh': '<span class="status-badge bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle me-1"></i>Hoàn thành</span>',
            'da_huy': '<span class="status-badge bg-danger bg-opacity-10 text-danger"><i class="fas fa-times-circle me-1"></i>Đã hủy</span>',
        };
        return map[status] || `<span class="status-badge bg-light text-dark">${status}</span>`;
    };

    const formatMoney = (amount) => {
        if (!amount && amount !== 0) return '0đ';
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
    };

    const renderBookings = (bookings) => {
        const getBookingServices = (booking) => Array.isArray(booking.dich_vus) ? booking.dich_vus : [];
        const getBookingServiceLabel = (booking) => {
            const services = getBookingServices(booking)
                .map(service => service.ten_dich_vu)
                .filter(Boolean);

            return services.length > 0 ? services.join(', ') : 'Dịch vụ';
        };

        if (!bookings || bookings.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fas fa-folder-open fs-1 opacity-25 mb-3"></i>
                        <p class="mb-0 fw-semibold">Chưa có giao dịch nào.</p>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = bookings.map(b => {
            const timeInfo = b.khung_gio_hen && b.ngay_hen
                ? `<span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-clock text-warning me-1"></i>${b.khung_gio_hen} (${b.ngay_hen})</span>`
                : `<span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-bolt text-danger me-1"></i>Sớm nhất có thể</span>`;

            const customerPhone = b.khach_hang ? b.khach_hang.phone : 'N/A';
            const workerInfo = b.tho ? `<span class="fw-bold text-dark">${b.tho.name}</span><br><small class="text-muted">${b.tho.phone}</small>` : `<span class="text-muted fst-italic">Chưa có thợ</span>`;

            return `
                <tr>
                    <td class="ps-4"><a href="/customer/my-bookings/${b.id}" class="text-primary fw-bold text-decoration-none">#${b.id}</a></td>
                    <td>
                        <div class="fw-bold text-dark mb-1">${getBookingServiceLabel(b)}</div>
                        ${timeInfo}
                    </td>
                    <td>
                        <span class="fw-bold text-dark">${b.khach_hang ? b.khach_hang.name : 'Unknown'}</span><br>
                        <small class="text-muted"><i class="fas fa-phone-alt me-1"></i>${customerPhone}</small><br>
                        <small class="text-muted text-truncate d-inline-block" style="max-width: 150px;" title="${b.dia_chi}"><i class="fas fa-map-marker-alt me-1 text-danger"></i>${b.dia_chi}</small>
                    </td>
                    <td>${workerInfo}</td>
                    <td>
                        <span class="fw-bold text-success">${formatMoney(b.tong_chi_phi)}</span>
                    </td>
                    <td class="text-end pe-4">
                        ${getStatusHtml(b.trang_thai)}
                    </td>
                </tr>
            `;
        }).join('');
    };

    statusFilter.addEventListener('change', fetchBookings);

    btnRefresh.addEventListener('click', () => {
        const icon = btnRefresh.querySelector('i');
        icon.classList.add('fa-spin');
        fetchBookings().finally(() => setTimeout(() => icon.classList.remove('fa-spin'), 500));
    });

    // Load initial data
    fetchBookings();
});
