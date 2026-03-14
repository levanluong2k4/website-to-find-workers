import { callApi } from '../api.js';
import '../components/booking-modal.js';

document.addEventListener('DOMContentLoaded', () => {
    const workerId = window.WORKER_ID;

    if (!workerId) {
        alert('Khong tim thay thong tin tho.');
        window.location.href = '/customer/search';
        return;
    }

    fetchWorkerDetails();

    async function fetchWorkerDetails() {
        try {
            const result = await callApi(`/ho-so-tho/${workerId}`, 'GET');

            if (result?.ok && result.data) {
                renderWorkerInfo(result.data);
                return;
            }

            throw new Error(result?.data?.message || 'Khong tim thay thong tin tho.');
        } catch (error) {
            console.error('Error fetching worker details:', error);
            alert('Loi ket noi hoac khong tim thay tho.');
        }
    }

    function renderWorkerInfo(worker) {
        const user = worker.user || {};
        const services = Array.isArray(user.dich_vus) ? user.dich_vus : [];
        const reviews = Array.isArray(user.danh_gias_nhan) ? user.danh_gias_nhan : [];

        const name = user.name || 'Tho sua chua';
        const avatarUrl = user.avatar || '/assets/images/user-default.png';
        const ratingValue = Number.parseFloat(worker.danh_gia_trung_binh ?? 0);
        const rating = Number.isFinite(ratingValue) ? ratingValue.toFixed(1) : '0.0';

        document.getElementById('workerName').innerHTML = `${name} <span class="material-symbols-outlined text-blue-500 text-lg ms-1 align-middle" style="font-variation-settings: 'FILL' 1;">verified</span>`;
        document.getElementById('workerNameDetail').textContent = name;
        document.getElementById('workerAvatar').src = avatarUrl;
        document.getElementById('workerRating').textContent = rating;
        document.getElementById('workerReviewCount').textContent = worker.tong_so_danh_gia || 0;
        document.getElementById('workerExperience').textContent = worker.kinh_nghiem || 'Chua cap nhat thong tin kinh nghiem.';

        let statusHtml = '';
        if (worker.trang_thai_hoat_dong === 'dang_hoat_dong') {
            statusHtml = '<span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> San sang</span>';
        } else if (worker.trang_thai_hoat_dong === 'dang_ban') {
            statusHtml = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Dang ban</span>';
        } else {
            statusHtml = '<span class="badge bg-secondary">Ngung hoat dong</span>';
        }
        document.getElementById('workerStatusBadge').innerHTML = statusHtml;

        if (services.length > 0) {
            document.getElementById('workerServices').innerHTML = services.map((service) => `
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-2" style="font-size: 0.85rem;">
                    ${service.ten_dich_vu}
                </span>
            `).join('');

            const shortDesc = services.map((service) => service.ten_dich_vu).join(' • ');
            document.getElementById('workerShortDesc').textContent = `Chuyen mon: ${shortDesc}`;
        } else {
            document.getElementById('workerServices').innerHTML = '<span>Chua cap nhat dich vu cu the.</span>';
            document.getElementById('workerShortDesc').textContent = 'Chuyen gia sua chua gia dung';
        }

        const reviewsContainer = document.getElementById('reviewsContainer');
        if (reviews.length > 0) {
            reviewsContainer.innerHTML = reviews.map((review) => {
                const reviewer = review.nguoi_danh_gia || {};
                const customerName = reviewer.name || 'Khach an danh';
                const customerAvt = reviewer.avatar || '/assets/images/customer.png';
                const dateSplit = review.created_at ? review.created_at.split('T')[0] : 'Gan day';

                let starsHtml = '';
                for (let i = 1; i <= 5; i++) {
                    if (i <= review.so_sao) {
                        starsHtml += `<span class="material-symbols-outlined text-warning" style="font-size: 16px; font-variation-settings: 'FILL' 1;">star</span>`;
                    } else {
                        starsHtml += `<span class="material-symbols-outlined text-muted" style="font-size: 16px;">star</span>`;
                    }
                }

                return `
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
                            <p class="text-secondary m-0" style="font-size: 0.95rem;">${review.nhan_xet || '<i>Khong co nhan xet chi tiet.</i>'}</p>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            reviewsContainer.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-comment-slash fs-3 mb-2 opacity-50"></i><br>Chua co danh gia nao cho tho nay.</div>';
        }
    }
});
