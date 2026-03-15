import { callApi } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    const workerId = window.WORKER_ID;

    if (!workerId) {
        alert('Kh\u00f4ng t\u00ecm th\u1ea5y th\u00f4ng tin th\u1ee3.');
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

            throw new Error(result?.data?.message || 'Kh\u00f4ng t\u00ecm th\u1ea5y th\u00f4ng tin th\u1ee3.');
        } catch (error) {
            console.error('Error fetching worker details:', error);
            alert('L\u1ed7i k\u1ebft n\u1ed1i ho\u1eb7c kh\u00f4ng t\u00ecm th\u1ea5y th\u1ee3.');
        }
    }

    function renderWorkerInfo(worker) {
        const user = worker.user || {};
        const services = Array.isArray(user.dich_vus) ? user.dich_vus : [];
        const reviews = Array.isArray(user.danh_gias_nhan) ? user.danh_gias_nhan : [];

        const name = user.name || 'Th\u1ee3 s\u1eeda ch\u1eefa';
        const avatarUrl = user.avatar || '/assets/images/user-default.png';
        const ratingValue = Number.parseFloat(worker.danh_gia_trung_binh ?? 0);
        const rating = Number.isFinite(ratingValue) ? ratingValue.toFixed(1) : '0.0';

        document.getElementById('workerName').innerHTML = `${name} <span class="material-symbols-outlined text-blue-500 text-lg ms-1 align-middle" style="font-variation-settings: 'FILL' 1;">verified</span>`;
        document.getElementById('workerNameDetail').textContent = name;
        document.getElementById('workerAvatar').src = avatarUrl;
        document.getElementById('workerRating').textContent = rating;
        document.getElementById('workerReviewCount').textContent = worker.tong_so_danh_gia || 0;
        document.getElementById('workerExperience').textContent = worker.kinh_nghiem || 'Ch\u01b0a c\u1eadp nh\u1eadt th\u00f4ng tin kinh nghi\u1ec7m.';

        let statusHtml = '';
        if (worker.trang_thai_hoat_dong === 'dang_hoat_dong') {
            statusHtml = '<span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> S\u1eb5n s\u00e0ng</span>';
        } else if (worker.trang_thai_hoat_dong === 'dang_ban') {
            statusHtml = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> \u0110ang b\u1eadn</span>';
        } else {
            statusHtml = '<span class="badge bg-secondary">Ng\u1eebng ho\u1ea1t \u0111\u1ed9ng</span>';
        }
        document.getElementById('workerStatusBadge').innerHTML = statusHtml;

        if (services.length > 0) {
            document.getElementById('workerServices').innerHTML = services.map((service) => `
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-2" style="font-size: 0.85rem;">
                    ${service.ten_dich_vu}
                </span>
            `).join('');

            const shortDesc = services.map((service) => service.ten_dich_vu).join(' \u2022 ');
            document.getElementById('workerShortDesc').textContent = `Chuy\u00ean m\u00f4n: ${shortDesc}`;
        } else {
            document.getElementById('workerServices').innerHTML = '<span>Ch\u01b0a c\u1eadp nh\u1eadt d\u1ecbch v\u1ee5 c\u1ee5 th\u1ec3.</span>';
            document.getElementById('workerShortDesc').textContent = 'Chuy\u00ean gia s\u1eeda ch\u1eefa gia d\u1ee5ng';
        }

        const reviewsContainer = document.getElementById('reviewsContainer');
        if (reviews.length > 0) {
            reviewsContainer.innerHTML = reviews.map((review) => {
                const reviewer = review.nguoi_danh_gia || {};
                const customerName = reviewer.name || 'Kh\u00e1ch \u1ea9n danh';
                const customerAvt = reviewer.avatar || '/assets/images/customer.png';
                const dateSplit = review.created_at ? review.created_at.split('T')[0] : 'G\u1ea7n \u0111\u00e2y';

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
                            <p class="text-secondary m-0" style="font-size: 0.95rem;">${review.nhan_xet || '<i>Kh\u00f4ng c\u00f3 nh\u1eadn x\u00e9t chi ti\u1ebft.</i>'}</p>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            reviewsContainer.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-comment-slash fs-3 mb-2 opacity-50"></i><br>Ch\u01b0a c\u00f3 \u0111\u00e1nh gi\u00e1 n\u00e0o cho th\u1ee3 n\u00e0y.</div>';
        }
    }
});
