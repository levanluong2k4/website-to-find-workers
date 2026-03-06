import { callApi, getCurrentUser, logout } from '../api.js';

const user = getCurrentUser();
if (!user || user.role !== 'worker') {
    logout();
}

document.addEventListener('DOMContentLoaded', async () => {
    const calendarEl = document.getElementById('calendar');

    // Modal elements
    const detailModal = new bootstrap.Modal(document.getElementById('eventDetailModal'));
    const modalAvatar = document.getElementById('modalAvatar');
    const modalCustomerName = document.getElementById('modalCustomerName');
    const modalCustomerPhone = document.getElementById('modalCustomerPhone');
    const modalService = document.getElementById('modalService');
    const modalTime = document.getElementById('modalTime');
    const modalAddress = document.getElementById('modalAddress');
    const modalStatus = document.getElementById('modalStatus');

    const statusMap = {
        'cho_xac_nhan': { label: 'Chờ thợ nhận', class: 'bg-warning text-dark' },
        'da_xac_nhan': { label: 'Sắp tới', class: 'bg-info text-dark' },
        'dang_lam': { label: 'Đang sửa chữa', class: 'bg-primary text-white' },
        'cho_hoan_thanh': { label: 'Chờ khách nghiệm thu', class: 'bg-secondary text-white' },
        'da_xong': { label: 'Hoàn thành', class: 'bg-success text-white' },
        'da_huy': { label: 'Đã hủy', class: 'bg-danger text-white' }
    };

    const getStatusInfo = (status) => {
        return statusMap[status] || { label: 'Không rõ', class: 'bg-secondary text-white' };
    };

    // Load Events from API
    const fetchEvents = async () => {
        try {
            const res = await callApi('/don-dat-lich?per_page=100', 'GET'); // Lấy nhiều xíu cho calendar
            if (res.ok && res.data && res.data.data) {
                const bookings = res.data.data;

                const events = bookings.map(b => {
                    let color = '#3b82f6'; // default primary
                    if (b.trang_thai === 'da_xong') color = '#10b981'; // success
                    else if (b.trang_thai === 'da_huy') color = '#ef4444'; // danger
                    else if (b.trang_thai === 'cho_xac_nhan' || b.trang_thai === 'da_xac_nhan') color = '#0ea5e9'; // info

                    // Convert thoi_gian_hen to valid ISO string if it exists
                    // Fallback to ngay_hen if time is weird
                    let startDateTime = b.thoi_gian_hen;
                    if (!startDateTime) {
                        const gioBanDau = b.khung_gio_hen ? b.khung_gio_hen.split('-')[0].trim() : '08:00';
                        startDateTime = `${b.ngay_hen}T${gioBanDau}:00`;
                    }

                    return {
                        id: b.id,
                        title: b.dich_vu?.ten_dich_vu || 'Sửa chữa',
                        start: startDateTime,
                        backgroundColor: color,
                        borderColor: color,
                        extendedProps: {
                            booking: b
                        }
                    };
                });
                return events;
            }
            return [];
        } catch (error) {
            console.error('Lỗi tải sự kiện lịch:', error);
            return [];
        }
    };

    const initCalendar = async () => {
        const eventsData = await fetchEvents();

        const calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'vi',
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            slotMinTime: '06:00:00',
            slotMaxTime: '22:00:00',
            allDaySlot: false,
            events: eventsData,
            eventClick: function (info) {
                const b = info.event.extendedProps.booking;

                // Điền dữ liệu vào Modal
                modalAvatar.src = b.khach_hang?.avatar || '/assets/images/user-default.png';
                modalCustomerName.innerText = b.khach_hang?.name || 'Khách hàng';
                modalCustomerPhone.innerText = b.khach_hang?.phone || 'Chưa cập nhật SĐT';
                modalService.innerText = b.dich_vu?.ten_dich_vu || 'Chưa rõ';

                const timeStr = b.thoi_gian_hen ? new Date(b.thoi_gian_hen).toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' }) : (b.ngay_hen + ' ' + b.khung_gio_hen);
                modalTime.innerText = timeStr;

                modalAddress.innerText = b.loai_dat_lich === 'at_home' ? (b.dia_chi || 'Khách hàng không cung cấp địa chỉ') : 'Khách mang tới Cửa hàng';

                const statusInfo = getStatusInfo(b.trang_thai);
                modalStatus.className = `status-badge ${statusInfo.class}`;
                modalStatus.innerText = statusInfo.label;

                detailModal.show();
            }
        });

        calendar.render();
    };

    initCalendar();
});
