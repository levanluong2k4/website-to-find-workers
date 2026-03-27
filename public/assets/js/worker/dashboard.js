import { callApi, getCurrentUser, logout, showToast, confirmAction } from '../api.js';

// Kiểm tra quyền
const user = getCurrentUser();
if (!user || !['worker', 'admin'].includes(user.role)) {
    logout();
}

document.addEventListener('DOMContentLoaded', () => {
    const jobsContainer = document.getElementById('availableJobsContainer');
    const refreshBtn = document.getElementById('btnRefreshJobs');

    const loadAvailableJobs = async () => {
        try {
            // Tạm thời hiển thị loading
            jobsContainer.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            `;

            const response = await callApi('/don-dat-lich/available', 'GET');

            if (response.ok) {
                if (response.data && response.data.length > 0) {
                    renderJobs(response.data);
                } else {
                    renderEmptyState();
                }
            } else {
                throw new Error("Lỗi API");
            }
        } catch (error) {
            console.error('Error fetching jobs:', error);
            jobsContainer.innerHTML = `
                <div class="col-12 text-center py-5">
                    <p class="text-danger mb-0">Có lỗi xảy ra khi tải danh sách việc làm.</p>
                </div>
            `;
        }
    };

    const renderJobs = (jobs) => {
        jobsContainer.innerHTML = '';

        jobs.forEach(job => {
            const dateObj = new Date(job.ngay_hen);
            const dateString = dateObj.toLocaleDateString('vi-VN');
            const createdString = new Date(job.created_at).toLocaleString('vi-VN');
            const bookingServices = Array.isArray(job.dich_vus) ? job.dich_vus : [];
            const serviceTitle = bookingServices.length > 0
                ? bookingServices.map(service => service.ten_dich_vu).join(', ')
                : 'Sửa chữa điện máy';
            const serviceBadges = bookingServices.length > 0
                ? bookingServices.map(service => `<span class="badge rounded-pill text-bg-light border">${service.ten_dich_vu}</span>`).join('')
                : '';

            // Format Tiền tệ
            const distanceFeeStr = job.phi_di_lai
                ? new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(job.phi_di_lai)
                : 'Miễn phí';

            const jobCard = document.createElement('div');
            jobCard.className = 'col-md-6 col-lg-4';
            jobCard.innerHTML = `
                <div class="job-card h-100 d-flex flex-column p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <img src="${job.khach_hang?.avatar || '/assets/images/user-default.png'}" alt="Customer Avatar" class="rounded-circle" width="48" height="48" style="object-fit:cover;">
                            <div>
                                <h6 class="fw-bold text-dark mb-1 fs-5">${serviceTitle}</h6>
                                ${serviceBadges ? `<div class="d-flex flex-wrap gap-2 mt-2">${serviceBadges}</div>` : ''}
                                <p class="text-muted mb-0 lh-1" style="font-size: 0.85rem;">Khách: <strong class="text-dark">${job.khach_hang?.name || 'Khách Hàng'}</strong></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content Details -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-2 text-secondary" style="font-size: 0.9rem;">
                            <span class="material-symbols-outlined me-2" style="font-size: 1.2rem;">event</span>
                            <span>${dateString} • Lịch: <strong>${job.khung_gio_hen}</strong></span>
                        </div>
                        <div class="d-flex align-items-start mb-2 text-secondary" style="font-size: 0.9rem;">
                            <span class="material-symbols-outlined me-2" style="font-size: 1.2rem; flex-shrink: 0;">location_on</span>
                            <span class="text-truncate-2">${job.loai_dat_lich === 'at_home' ? job.dia_chi : 'Mang tới cửa hàng'} 
                            ${job.khoang_cach ? `<br><small class="text-success fw-medium">(${job.khoang_cach} km)</small>` : ''}
                            </span>
                        </div>
                        <div class="d-flex align-items-center text-secondary mb-3" style="font-size: 0.9rem;">
                            <span class="material-symbols-outlined me-2" style="font-size: 1.2rem;">local_shipping</span>
                            <span>Phí đi lại (Dự kiến): <strong class="text-dark">${distanceFeeStr}</strong></span>
                        </div>
                        
                        <div class="bg-light p-3 rounded-3 mt-3">
                            ${job.thue_xe_cho ? '<div class="mb-2"><span class="badge bg-info text-dark px-2 py-1 border border-info-subtle shadow-sm"><i class="fas fa-truck me-1"></i> Khách yêu cầu thuê xe tải chở thiết bị</span></div>' : ''}
                            <p class="mb-0 text-dark" style="font-size: 0.9rem; line-height: 1.5;">
                                <span class="fw-semibold">Mô tả sự cố:</span> 
                                ${job.mo_ta_van_de || 'Không có mô tả chi tiết'}
                            </p>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                        <small class="text-muted">Đăng lúc: ${createdString}</small>
                        <button class="btn btn-claim px-4 btn-sm fw-bold" onclick="claimJob(${job.id})">
                            Nhận Việc
                        </button>
                    </div>
                </div>
            `;
            jobsContainer.appendChild(jobCard);
        });
    };

    const renderEmptyState = () => {
        jobsContainer.innerHTML = `
            <div class="col-12">
                <div class="empty-state shadow-sm">
                    <div class="d-inline-flex justify-content-center align-items-center rounded-circle mb-3" style="width: 80px; height: 80px; background-color: #f1f5f9;">
                        <span class="material-symbols-outlined text-secondary" style="font-size: 40px;">assignment_late</span>
                    </div>
                    <h4 class="fw-bold text-dark">Chưa có việc nào mới</h4>
                    <p class="text-muted mb-4">Hiện không có đơn sửa chữa nào cần thợ. Hãy quay lại sau nhé.</p>
                </div>
            </div>
        `;
    };

    // Global action method for button
    window.claimJob = async (jobId) => {
        const confirmResult = await confirmAction('Xác nhận nhận việc?', 'Bạn có chắc chắn muốn nhận đơn này không?');
        if (!confirmResult.isConfirmed) return;

        try {
            const res = await callApi(`/don-dat-lich/${jobId}/claim`, 'POST');

            if (res.ok) {
                showToast('Đã nhận việc thành công! Vui lòng vào Việc Của Tôi để xem chi tiết.');
                loadAvailableJobs(); // Thử load lại ds
            } else {
                showToast(res.data?.message || 'Không thể nhận đơn này. Đơn có thể đã bị thợ khác nhận hoặc bị hủy.', 'error');
            }
        } catch (error) {
            console.error('Error claiming job:', error);
            showToast('Lỗi kết nối máy chủ.', 'error');
        }
    };

    // Events
    refreshBtn.addEventListener('click', loadAvailableJobs);

    // Initial Load
    loadAvailableJobs();

});
