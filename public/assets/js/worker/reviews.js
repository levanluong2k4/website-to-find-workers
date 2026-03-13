import { callApi, getCurrentUser, logout } from '../api.js';

const user = getCurrentUser();
if (!user || !['worker', 'admin'].includes(user.role)) {
    logout();
}

document.addEventListener('DOMContentLoaded', async () => {
    const avgRatingValue = document.getElementById('avgRatingValue');
    const avgRatingStars = document.getElementById('avgRatingStars');
    const totalReviewCount = document.getElementById('totalReviewCount');
    const reviewsListContainer = document.getElementById('reviewsListContainer');

    const renderStars = (rating) => {
        let starsHtml = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= rating) {
                starsHtml += `<span class="material-symbols-outlined text-warning" style="font-variation-settings: 'FILL' 1;">star</span>`;
            } else if (i - 0.5 <= rating) {
                starsHtml += `<span class="material-symbols-outlined text-warning" style="font-variation-settings: 'FILL' 1;">star_half</span>`;
            } else {
                starsHtml += `<span class="material-symbols-outlined text-muted" style="font-variation-settings: 'FILL' 0;">star</span>`;
            }
        }
        return starsHtml;
    };

    const loadSummary = async () => {
        try {
            const res = await callApi(`/ho-so-tho/${user.id}/danh-gia/summary`, 'GET');
            if (res.ok && res.data) {
                const avg = parseFloat(res.data.average_rating || 0).toFixed(1);
                avgRatingValue.innerText = avg;
                avgRatingStars.innerHTML = renderStars(avg);
                totalReviewCount.innerText = res.data.total_reviews || 0;
            }
        } catch (error) {
            console.error('Lỗi tải tóm tắt đánh giá:', error);
        }
    };

    const loadReviews = async (page = 1) => {
        try {
            reviewsListContainer.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            `;

            const res = await callApi(`/ho-so-tho/${user.id}/danh-gia?page=${page}`, 'GET');

            if (res.ok && res.data && res.data.data) {
                const reviews = res.data.data;
                const pagination = res.data; // contain links, meta

                if (reviews.length === 0) {
                    reviewsListContainer.innerHTML = `
                        <div class="text-center py-5 border p-4 bg-light rounded-3 text-muted">
                            <span class="material-symbols-outlined fs-1 mb-2">forum</span>
                            <p class="mb-0">Chưa có đánh giá nào.</p>
                        </div>
                    `;
                    return;
                }

                let html = '';
                reviews.forEach(review => {
                    const dateStr = new Date(review.created_at).toLocaleDateString('vi-VN');
                    html += `
                        <div class="review-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="${review.khach_hang?.avatar || '/assets/images/user-default.png'}" alt="Avatar" class="rounded-circle" width="48" height="48" style="object-fit:cover;">
                                    <div>
                                        <h6 class="fw-bold mb-1">${review.khach_hang?.name || 'Khách hàng'}</h6>
                                        <div class="d-flex align-items-center text-muted small">
                                            <span class="material-symbols-outlined me-1 fs-6">calendar_month</span> ${dateStr}
                                        </div>
                                    </div>
                                </div>
                                <div class="rating-stars">
                                    ${renderStars(review.so_sao)}
                                </div>
                            </div>
                            <!-- Dich vu snippet if possible --!>
                            <p class="mb-0 mt-3 text-dark" style="line-height: 1.6;">${review.nhan_xet || '<em class="text-muted">Không có nội dung đánh giá bằng chữ.</em>'}</p>
                        </div>
                    `;
                });

                reviewsListContainer.innerHTML = html;

                // Setup pagination UI later if needed (res.data.links)
            }
        } catch (error) {
            console.error('Lỗi tải danh sách đánh giá:', error);
            reviewsListContainer.innerHTML = `<p class="text-danger">Lỗi khi tải đánh giá.</p>`;
        }
    };

    await loadSummary();
    await loadReviews(1);
});
