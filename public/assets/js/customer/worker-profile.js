import { callApi } from '../api.js';
import '../components/booking-modal.js';

document.addEventListener('DOMContentLoaded', () => {
    const workerId = window.WORKER_ID;

    if (!workerId) {
        alert('Không tìm thấy thông tin thợ.');
        window.location.href = '/customer/search';
        return;
    }

    fetchWorkerDetails();

    async function fetchWorkerDetails() {
        try {
            const result = await callApi(`/ho-so-tho/${workerId}`, 'GET');

            if (result) {
                renderWorkerInfo(result);
            }
        } catch (error) {
            console.error('Error fetching worker details:', error);
            alert('Lỗi kết nối hoặc không tìm thấy thợ.');
        }
    }

    function renderWorkerInfo(worker) {
        const name = worker.user ? worker.user.name : 'Unknown';
        const avatarUrl = (worker.user && worker.user.avatar) ? worker.user.avatar : '/assets/images/user-default.png';
        const rating = parseFloat(worker.danh_gia_trung_binh).toFixed(1);

        document.getElementById('workerName').innerHTML = `${name} <span class="material-symbols-outlined text-blue-500 text-lg ms-1 align-middle" style="font-variation-settings: 'FILL' 1;">verified</span>`;
        document.getElementById('workerNameDetail').textContent = name;
        document.getElementById('workerAvatar').src = avatarUrl;
        document.getElementById('workerRating').textContent = rating;
        document.getElementById('workerReviewCount').textContent = worker.tong_so_danh_gia || 0;
        document.getElementById('workerExperience').textContent = worker.kinh_nghiem || 'Thợ chuyên nghiệp với nhiều năm kinh nghiệm xử lý các sự cố điện máy gia dụng.';

        // Status Badge
        let statusHtml = '';
        if (worker.trang_thai_hoat_dong === 'dang_hoat_dong') {
            statusHtml = '<span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Sẵn sàng</span>';
        } else if (worker.trang_thai_hoat_dong === 'dang_ban') {
            statusHtml = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Đang bận</span>';
        } else {
            statusHtml = '<span class="badge bg-secondary">Ngừng hoạt động</span>';
        }
        document.getElementById('workerStatusBadge').innerHTML = statusHtml;

        // Services
        let servicesHtml = '';
        if (worker.user && worker.user.dich_vus && worker.user.dich_vus.length > 0) {
            worker.user.dich_vus.forEach(service => {
                servicesHtml += `<span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-2" style="font-size: 0.85rem;">${service.ten_dich_vu}</span>`;
            });
            let shortDesc = worker.user.dich_vus.map(s => s.ten_dich_vu).join(' • ');
            document.getElementById('workerShortDesc').textContent = `Chuyên môn: ${shortDesc}`;
        } else {
            servicesHtml = '<span>Chưa cập nhật dịch vụ cụ thể.</span>';
            document.getElementById('workerShortDesc').textContent = 'Chuyên gia sửa chữa gia dụng';
        }
        document.getElementById('workerServices').innerHTML = servicesHtml;

        // Render Reviews
        const reviewsContainer = document.getElementById('reviewsContainer');
        if (worker.user && worker.user.danh_gias_nhan && worker.user.danh_gias_nhan.length > 0) {
            let revHtml = '';
            worker.user.danh_gias_nhan.forEach(review => {
                const customerName = review.khach_hang ? review.khach_hang.name : 'Khách ẩn danh';
                const customerAvt = (review.khach_hang && review.khach_hang.avatar) ? review.khach_hang.avatar : '/assets/images/customer.png';
                const dateSplit = review.created_at ? review.created_at.split('T')[0] : 'Gần đây';

                let starsHtml = '';
                for (let i = 1; i <= 5; i++) {
                    if (i <= review.so_sao) {
                        starsHtml += `<span class="material-symbols-outlined text-warning" style="font-size: 16px; font-variation-settings: 'FILL' 1;">star</span>`;
                    } else {
                        starsHtml += `<span class="material-symbols-outlined text-muted" style="font-size: 16px;">star</span>`;
                    }
                }

                revHtml += `
                <div class="d-flex gap-3 mb-4 pb-4 border-bottom">
                    <img src="${customerAvt}" class="rounded-circle" style="width: 48px; height: 48px; object-fit: cover;">
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="fw-bold m-0">${customerName}</h6>
                            <small class="text-muted">${dateSplit}</small>
                        </div>
                        <div class="d-flex gap-1 mb-2">
                            ${starsHtml}
                        </div>
                        <p class="text-secondary m-0" style="font-size: 0.95rem;">${review.nhan_xet || '<i>Không có nhận xét chi tiết.</i>'}</p>
                    </div>
                </div>
                `;
            });
            reviewsContainer.innerHTML = revHtml;
        } else {
            reviewsContainer.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-comment-slash fs-3 mb-2 opacity-50"></i><br>Chưa có đánh giá nào cho thợ này.</div>';
        }
    }
});
