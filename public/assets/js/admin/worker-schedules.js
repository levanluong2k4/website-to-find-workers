import { callApi, requireRole } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const refs = {
        grid: document.getElementById('workerScheduleGrid'),
        search: document.getElementById('schSearch'),
        statusFilter: document.getElementById('schStatusFilter'),
        btnRefresh: document.getElementById('btnRefreshSch'),
        syncStatus: document.getElementById('schSyncStatus'),
        stats: {
            tracked: document.getElementById('statTracked'),
            available: document.getElementById('statAvailable'),
            scheduled: document.getElementById('statScheduled'),
            offline: document.getElementById('statOffline'),
        }
    };

    let workersData = [];

    const escapeHtml = (value) => (value ?? '').toString().replace(/[&<>"']/g, (m) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[m]);

    const determineWorkerStatus = (workerProfile, bookings) => {
        const user = workerProfile.user || workerProfile;
        
        // Filter bookings assigned to this worker that are not completed/canceled
        const activeBookings = bookings.filter(b => 
            b.tho_id === user.id && 
            ['cho_xac_nhan', 'da_xac_nhan', 'dang_lam', 'cho_hoan_thanh', 'cho_thanh_toan'].includes(b.trang_thai)
        );

        // workerProfile is HoSoTho, so the properties are directly on it
        const isActive = user.is_active && workerProfile.dang_hoat_dong && workerProfile.trang_thai_hoat_dong !== 'ngung_hoat_dong' && workerProfile.trang_thai_hoat_dong !== 'tam_khoa';
        
        if (!isActive) {
            return {
                key: 'offline',
                label: 'Tạm khoá / Offline',
                icon: 'fa-user-slash',
                currentBooking: null
            };
        }

        if (activeBookings.length > 0) {
            // Sort to find the most pressing booking (dang_lam takes precedence)
            const sorted = activeBookings.sort((a, b) => {
                if (a.trang_thai === 'dang_lam') return -1;
                if (b.trang_thai === 'dang_lam') return 1;
                return new Date(a.ngay_hen || a.created_at) - new Date(b.ngay_hen || b.created_at);
            });

            const current = sorted[0];
            if (current.trang_thai === 'dang_lam') {
                return { key: 'repairing', label: 'Đang làm', icon: 'fa-tools', currentBooking: current };
            }
            return { key: 'scheduled', label: 'Đang có lịch', icon: 'fa-calendar-check', currentBooking: current };
        }

        return { key: 'available', label: 'Trong lịch (Rảnh)', icon: 'fa-user-check', currentBooking: null };
    };

    const fetchTrackingData = async () => {
        refs.syncStatus.innerHTML = '<i class="fas fa-sync fa-spin me-2"></i>Đang đồng bộ...';
        
        try {
            // Fetch workers and bookings in parallel
            // For bookings, we request up to 100 to ensure we capture active schedules
            const [workersRes, bookingsRes] = await Promise.all([
                callApi('/admin/worker-profiles?per_page=100'),
                callApi('/admin/bookings?per_page=100')
            ]);

            if (workersRes.ok && bookingsRes.ok) {
                const workers = workersRes.data?.data || [];
                
                // Bookings response structure is nested due to pagination and standard wrapper
                let bookings = [];
                if (bookingsRes.data?.data?.items) {
                    bookings = bookingsRes.data.data.items;
                } else if (Array.isArray(bookingsRes.data?.data)) {
                    bookings = bookingsRes.data.data;
                }

                workersData = workers.map(workerProfile => {
                    const statusObj = determineWorkerStatus(workerProfile, bookings);
                    return {
                        ...workerProfile,
                        user: workerProfile.user || workerProfile, // Normalize user object
                        trackingStatus: statusObj
                    };
                });

                updateStats();
                renderGrid();
                
                refs.syncStatus.innerHTML = '<i class="fas fa-check-circle me-2"></i>Cập nhật lúc ' + new Date().toLocaleTimeString('vi-VN');
            } else {
                throw new Error("API Error");
            }
        } catch (error) {
            console.error(error);
            refs.syncStatus.innerHTML = '<i class="fas fa-exclamation-triangle me-2 text-danger"></i>Lỗi đồng bộ';
            refs.grid.innerHTML = `<div class="col-12 text-center text-danger py-5">Không thể tải dữ liệu. Vui lòng thử lại.</div>`;
        }
    };

    const updateStats = () => {
        refs.stats.tracked.textContent = workersData.length;
        refs.stats.available.textContent = workersData.filter(w => w.trackingStatus.key === 'available').length;
        refs.stats.scheduled.textContent = workersData.filter(w => ['scheduled', 'repairing'].includes(w.trackingStatus.key)).length;
        refs.stats.offline.textContent = workersData.filter(w => w.trackingStatus.key === 'offline').length;
    };

    const renderGrid = () => {
        const searchTerm = refs.search.value.toLowerCase();
        const statusFilter = refs.statusFilter.value;

        const filtered = workersData.filter(item => {
            const user = item.user;
            const matchesSearch = !searchTerm || 
                (user.name || '').toLowerCase().includes(searchTerm) ||
                (user.phone || '').includes(searchTerm);
            
            const matchesStatus = !statusFilter || item.trackingStatus.key === statusFilter;

            return matchesSearch && matchesStatus;
        });

        if (filtered.length === 0) {
            refs.grid.innerHTML = `<div class="col-12 text-center text-muted py-5">Không tìm thấy thợ phù hợp.</div>`;
            return;
        }

        refs.grid.innerHTML = filtered.map(item => {
            const user = item.user;
            const status = item.trackingStatus;
            const avatar = user.avatar || '/assets/images/user-default.png';
            const services = (user.dich_vus || []).map(s => s.ten_dich_vu).join(', ') || 'Chưa định cấu hình';
            
            let jobHtml = `<div class="sch-job-title text-muted">Không có lịch đang chờ</div>`;
            if (status.currentBooking) {
                const b = status.currentBooking;
                const bDate = b.ngay_hen ? new Date(b.ngay_hen).toLocaleDateString('vi-VN') : 'Sắp tới';
                const bServices = (b.dich_vus || []).map(s => s.ten_dich_vu).join(', ') || 'Sửa chữa';
                
                jobHtml = `
                    <div class="sch-job-title">${escapeHtml(bServices)}</div>
                    <div class="sch-job-time">
                        <i class="far fa-clock"></i> ${escapeHtml(b.khung_gio_hen || '')} • ${bDate} | Khách: ${escapeHtml(b.khach_hang?.name || 'Khách')}
                    </div>
                `;
            }

            return `
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="sch-card">
                        <div class="sch-card-head">
                            <img src="${avatar}" alt="Avatar" class="sch-card-avatar shadow-sm" onerror="this.src='/assets/images/user-default.png'">
                            <div>
                                <h3 class="sch-card-name">${escapeHtml(user.name)}</h3>
                                <div class="sch-card-meta">${escapeHtml(user.phone)}</div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="sch-status-badge sch-status--${status.key}">
                                <i class="fas ${status.icon}"></i> ${status.label}
                            </div>
                        </div>

                        <div class="sch-job-area">
                            <div class="sch-job-label">Job hiện tại / Tiếp theo</div>
                            ${jobHtml}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    };

    refs.search.addEventListener('input', renderGrid);
    refs.statusFilter.addEventListener('change', renderGrid);
    refs.btnRefresh.addEventListener('click', fetchTrackingData);

    // Initial load
    fetchTrackingData();
    setInterval(fetchTrackingData, 60000); // Auto refresh every minute
});
