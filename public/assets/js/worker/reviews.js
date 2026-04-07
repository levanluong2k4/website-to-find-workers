import { callApi, getCurrentUser, showToast } from '../api.js';

const currentUser = getCurrentUser();

if (!currentUser || !['worker', 'admin'].includes(currentUser.role)) {
    window.location.replace(`${window.location.origin}/login?role=worker`);
}

document.addEventListener('DOMContentLoaded', () => {
    if (!currentUser || !['worker', 'admin'].includes(currentUser.role)) {
        return;
    }

    const refs = {
        headerCopy: document.getElementById('reviewHeaderCopy'),
        averageScore: document.getElementById('reviewAverageScore'),
        averageStars: document.getElementById('reviewAverageStars'),
        heroTitle: document.getElementById('reviewHeroTitle'),
        heroCopy: document.getElementById('reviewHeroCopy'),
        totalCount: document.getElementById('reviewTotalCount'),
        fiveStarRatio: document.getElementById('reviewFiveStarRatio'),
        recentCount: document.getElementById('reviewRecentCount'),
        metricTotal: document.getElementById('reviewMetricTotal'),
        metricFiveStar: document.getElementById('reviewMetricFiveStar'),
        metricLow: document.getElementById('reviewMetricLow'),
        metricCommented: document.getElementById('reviewMetricCommented'),
        breakdownBars: document.getElementById('reviewBreakdownBars'),
        ratingFilters: document.getElementById('reviewRatingFilters'),
        serviceFilter: document.getElementById('reviewServiceFilter'),
        sortSelect: document.getElementById('reviewSortSelect'),
        hasCommentToggle: document.getElementById('reviewHasCommentToggle'),
        insightLead: document.getElementById('reviewInsightLead'),
        insightList: document.getElementById('reviewInsightList'),
        actionLead: document.getElementById('reviewActionLead'),
        actionSteps: document.getElementById('reviewActionSteps'),
        feedCount: document.getElementById('reviewFeedCount'),
        reviewsList: document.getElementById('reviewsListContainer'),
        paginationControls: document.getElementById('paginationControls'),
        paginationList: document.getElementById('paginationList'),
        refreshButton: document.getElementById('reviewRefreshButton'),
    };

    const state = {
        summary: null,
        filters: {
            rating: 'all',
            serviceId: 'all',
            sort: 'latest',
            hasComment: false,
        },
        pagination: {
            currentPage: 1,
            lastPage: 1,
            total: 0,
            perPage: 12,
            count: 0,
        },
        loading: false,
    };

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const formatNumber = (value) => new Intl.NumberFormat('vi-VN').format(Number(value) || 0);
    const asPercent = (value) => `${formatNumber(Math.round(Number(value) || 0))}%`;
    const resolveSafeCountLabel = (count, singular, plural = singular) => `${formatNumber(count)} ${Number(count) === 1 ? singular : plural}`;

    const renderStars = (rating, { muted = false } = {}) => {
        const safeRating = Number(rating) || 0;
        let html = '';

        for (let index = 1; index <= 5; index += 1) {
            let icon = 'star';
            let className = muted ? 'review-stars--muted' : 'review-stars--light';

            if (index > Math.ceil(safeRating)) {
                icon = 'star';
                className = 'review-stars--muted';
            }

            if (!Number.isInteger(safeRating) && index === Math.ceil(safeRating) && safeRating < index) {
                icon = 'star_half';
                className = muted ? 'review-stars--muted' : 'review-stars--light';
            }

            html += `<span class="material-symbols-outlined ${className}">${icon}</span>`;
        }

        return html;
    };

    const buildHeroNarrative = (summary) => {
        const totalReviews = Number(summary?.total_reviews || 0);
        const averageRating = Number(summary?.average_rating || 0);
        const lowRatingReviews = Number(summary?.low_rating_reviews || 0);
        const fiveStarRatio = Number(summary?.five_star_ratio || 0);
        const recentReviews = Number(summary?.recent_reviews || 0);

        if (totalReviews === 0) {
            return {
                title: 'Chưa có đủ dữ liệu để đánh giá.',
                copy: 'Khi khách hoàn tất đơn và để lại phản hồi, hệ thống sẽ tự động gom lại để bạn theo dõi chất lượng phục vụ theo thời gian.',
                header: 'Trang này sẽ trở nên hữu ích ngay khi các đơn hoàn thành bắt đầu nhận review từ khách hàng.',
            };
        }

        if (averageRating >= 4.8 && lowRatingReviews === 0) {
            return {
                title: 'Uy tín đang ở vùng rất tốt.',
                copy: 'Khách đang đánh giá cao trải nghiệm sửa chữa. Việc cần giữ là đúng hẹn, giải thích rõ chi phí và chốt đơn gọn gàng.',
                header: `Bạn đang có ${asPercent(fiveStarRatio)} phản hồi 5 sao, đây là tín hiệu rất mạnh về độ tin cậy của hồ sơ.`,
            };
        }

        if (lowRatingReviews > 0) {
            return {
                title: 'Có phản hồi cần xem lại.',
                copy: `Hiện có ${resolveSafeCountLabel(lowRatingReviews, 'đánh giá', 'đánh giá')} ở mức 1-3 sao. Nên mở lại các đơn liên quan để rà soát khâu hẹn giờ, báo giá hoặc cách giao tiếp.`,
                header: 'Trang này đang ưu tiên cho bạn nhìn ra các phản hồi thấp và bối cảnh đơn hàng tạo ra chúng.',
            };
        }

        if (recentReviews === 0) {
            return {
                title: 'Uy tín ổn định nhưng đang thiếu tín hiệu mới.',
                copy: 'Bạn vẫn có nền đánh giá tốt, nhưng 30 ngày gần đây chưa có nhiều review mới. Điều này làm hồ sơ kém sống động hơn khi khách cân nhắc đặt thợ cố định.',
                header: 'Nên khuyến khích khách đánh giá ngay sau các đơn hoàn tất để giữ nhịp tín hiệu mới trên hồ sơ.',
            };
        }

        return {
            title: 'Chất lượng đang ở mức ổn.',
            copy: 'Phần lớn phản hồi tích cực, nhưng vẫn còn khoảng trống để đẩy trải nghiệm lên nhóm 5 sao ổn định hơn.',
            header: 'Trang này giúp bạn nhìn ra dịch vụ nào được khen nhiều và phản hồi nào cần rút kinh nghiệm trước.',
        };
    };

    const buildInsightItems = (summary) => {
        const totalReviews = Number(summary?.total_reviews || 0);
        const commentRate = Number(summary?.comment_rate || 0);
        const lowRatingReviews = Number(summary?.low_rating_reviews || 0);
        const serviceOptions = Array.isArray(summary?.service_options) ? summary.service_options : [];

        if (totalReviews === 0) {
            return [
                {
                    title: 'Chưa có phản hồi nào',
                    detail: 'Hoàn tất thêm vài đơn đầu tiên, sau đó trang này sẽ tự động hiện xu hướng và điểm mạnh nổi bật.',
                },
                {
                    title: 'Ưu tiên tạo trải nghiệm đầu tiên thật tốt',
                    detail: 'Giai đoạn đầu chỉ cần vài review tốt là hồ sơ đã khác biệt khá mạnh khi khách chọn thợ.',
                },
            ];
        }

        const items = [
            {
                title: `Tỷ lệ 5 sao đang ở ${asPercent(summary?.five_star_ratio)}`,
                detail: Number(summary?.five_star_ratio || 0) >= 70
                    ? 'Đây là vùng tốt. Nên giữ nhịp hẹn đúng giờ và giải thích rõ chi phí trước khi sửa.'
                    : 'Tỷ lệ này vẫn có thể đẩy cao hơn bằng cách chốt lịch rõ ràng và cập nhật tình trạng cho khách trong khi làm.',
            },
            {
                title: `${asPercent(commentRate)} review có nhận xét chữ`,
                detail: commentRate >= 50
                    ? 'Bạn đang có khá nhiều phản hồi chi tiết để đọc ra nguyên nhân khen hoặc chê.'
                    : 'Khách vẫn đánh giá sao nhưng chưa để lại nhiều nhận xét. Điều này làm việc cải thiện chất lượng chậm hơn.',
            },
        ];

        if (serviceOptions.length > 0) {
            items.push({
                title: `Dịch vụ nổi bật: ${serviceOptions[0].name}`,
                detail: `${resolveSafeCountLabel(serviceOptions[0].total_reviews, 'review')} đang gắn với dịch vụ này. Đây là nơi nên giữ chuẩn trải nghiệm cao nhất.`,
            });
        }

        if (lowRatingReviews > 0) {
            items.push({
                title: `${resolveSafeCountLabel(lowRatingReviews, 'review')} cần soi lại`,
                detail: 'Ưu tiên đọc các phản hồi thấp gần đây trước. Giá trị của trang review nằm ở việc phát hiện lỗi lặp, không chỉ xem điểm trung bình.',
            });
        }

        return items.slice(0, 4);
    };

    const buildActionSteps = (summary) => {
        const totalReviews = Number(summary?.total_reviews || 0);
        const lowRatingReviews = Number(summary?.low_rating_reviews || 0);
        const recentReviews = Number(summary?.recent_reviews || 0);
        const commentRate = Number(summary?.comment_rate || 0);
        const steps = [];

        if (totalReviews === 0) {
            return [
                {
                    title: 'Hoàn tất vài đơn đầu tiên thật chỉn chu',
                    detail: 'Chất lượng 5-10 đơn đầu tiên quyết định nền uy tín ban đầu của hồ sơ thợ.',
                },
                {
                    title: 'Nhắc khách đánh giá sau khi xong việc',
                    detail: 'Khi chưa có review, khách mới rất khó tin tưởng để đặt đích danh bạn.',
                },
            ];
        }

        if (lowRatingReviews > 0) {
            steps.push({
                title: 'Mở lại các đơn 1-3 sao và đọc nguyên nhân',
                detail: 'Ưu tiên xem các lịch gần nhất để kiểm tra xem lỗi nằm ở đúng hẹn, báo giá, thái độ hay kết quả sửa chữa.',
            });
        }

        if (commentRate < 45) {
            steps.push({
                title: 'Khuyến khích khách để lại nhận xét ngắn',
                detail: 'Review có chữ luôn hữu ích hơn review chỉ có sao vì giúp bạn biết chính xác điểm cần sửa.',
            });
        }

        if (recentReviews === 0) {
            steps.push({
                title: 'Kích hoạt lại dòng phản hồi mới',
                detail: '30 ngày gần đây chưa có review mới. Hồ sơ sẽ thiếu tín hiệu tươi với khách đang cân nhắc đặt thợ cố định.',
            });
        }

        steps.push({
            title: 'Giữ chuẩn với dịch vụ được khen nhiều nhất',
            detail: 'Khi đã có một nhóm dịch vụ mang lại uy tín tốt, hãy dùng chúng như chuẩn thao tác cho các ca khác.',
        });

        return steps.slice(0, 4);
    };

    const renderSummary = (summary) => {
        const safeSummary = summary || {};
        const totalReviews = Number(safeSummary.total_reviews || 0);
        const averageRating = Number(safeSummary.average_rating || 0);
        const recentReviews = Number(safeSummary.recent_reviews || 0);
        const heroNarrative = buildHeroNarrative(safeSummary);

        refs.averageScore.textContent = averageRating.toFixed(1);
        refs.averageStars.innerHTML = renderStars(averageRating);
        refs.heroTitle.textContent = heroNarrative.title;
        refs.heroCopy.textContent = heroNarrative.copy;
        refs.headerCopy.textContent = heroNarrative.header;

        refs.totalCount.textContent = resolveSafeCountLabel(totalReviews, 'đánh giá');
        refs.fiveStarRatio.textContent = asPercent(safeSummary.five_star_ratio);
        refs.recentCount.textContent = resolveSafeCountLabel(recentReviews, 'phản hồi');

        refs.metricTotal.textContent = formatNumber(totalReviews);
        refs.metricFiveStar.textContent = formatNumber(safeSummary.five_star_reviews);
        refs.metricLow.textContent = formatNumber(safeSummary.low_rating_reviews);
        refs.metricCommented.textContent = asPercent(safeSummary.comment_rate);

        renderBreakdown(safeSummary);
        renderServiceOptions(safeSummary.service_options || []);
        renderInsights(safeSummary);
    };

    const renderBreakdown = (summary) => {
        const totalReviews = Number(summary?.total_reviews || 0);
        const breakdown = summary?.breakdown || {};
        const activeRating = String(state.filters.rating);

        refs.breakdownBars.innerHTML = [5, 4, 3, 2, 1].map((rating) => {
            const count = Number(breakdown[String(rating)] || 0);
            const width = totalReviews > 0 ? Math.max(6, Math.round((count / totalReviews) * 100)) : 0;
            const isFiltered = activeRating === String(rating);

            return `
                <button type="button" class="review-breakdown__row ${isFiltered ? 'is-filtered' : ''}" data-rating-filter="${rating}">
                    <span class="review-breakdown__label">${rating} <span class="material-symbols-outlined">star</span></span>
                    <span class="review-breakdown__bar"><span style="width:${count > 0 ? width : 0}%;"></span></span>
                    <span class="review-breakdown__count">${formatNumber(count)}</span>
                </button>
            `;
        }).join('');
    };

    const renderServiceOptions = (serviceOptions) => {
        const options = Array.isArray(serviceOptions) ? serviceOptions : [];
        const currentValue = String(state.filters.serviceId);
        const nextOptions = ['<option value="all">Tất cả dịch vụ</option>']
            .concat(options.map((service) => (
                `<option value="${service.id}">${escapeHtml(service.name)} (${formatNumber(service.total_reviews)})</option>`
            )))
            .join('');

        refs.serviceFilter.innerHTML = nextOptions;

        const hasCurrentValue = currentValue === 'all' || options.some((service) => String(service.id) === currentValue);
        refs.serviceFilter.value = hasCurrentValue ? currentValue : 'all';

        if (!hasCurrentValue) {
            state.filters.serviceId = 'all';
        }
    };

    const renderInsights = (summary) => {
        refs.insightLead.textContent = Number(summary?.total_reviews || 0) > 0
            ? 'Các insight này gom từ tỷ lệ 5 sao, số review thấp, mức độ có nhận xét chữ và dịch vụ nào xuất hiện nhiều trong phản hồi.'
            : 'Khi bắt đầu có review, khu vực này sẽ tự động chỉ ra điều bạn cần giữ và điều cần sửa.';
        refs.actionLead.textContent = Number(summary?.low_rating_reviews || 0) > 0
            ? 'Có phản hồi thấp nên hành động quan trọng nhất là xem lại bối cảnh đơn hàng thay vì chỉ nhìn điểm trung bình.'
            : 'Dù điểm đang ổn, vẫn nên duy trì nhịp review mới và giữ chuẩn phục vụ ở những dịch vụ mạnh.';

        refs.insightList.innerHTML = buildInsightItems(summary).map((item) => `
            <div class="review-insight-item">
                <strong>${escapeHtml(item.title)}</strong>
                <span>${escapeHtml(item.detail)}</span>
            </div>
        `).join('');

        refs.actionSteps.innerHTML = buildActionSteps(summary).map((item) => `
            <div class="review-insight-step">
                <strong>${escapeHtml(item.title)}</strong>
                <span>${escapeHtml(item.detail)}</span>
            </div>
        `).join('');
    };

    const renderLoadingState = () => {
        refs.reviewsList.innerHTML = `
            <div class="review-loading">
                <span class="material-symbols-outlined">hourglass_top</span>
                <div>Đang tải phản hồi từ khách hàng...</div>
            </div>
        `;
    };

    const renderEmptyState = () => {
        const hasFilters = state.filters.rating !== 'all' || state.filters.serviceId !== 'all' || state.filters.hasComment;
        refs.reviewsList.innerHTML = `
            <div class="review-empty">
                <span class="material-symbols-outlined">reviews</span>
                <strong>${hasFilters ? 'Không có review khớp bộ lọc hiện tại.' : 'Chưa có phản hồi nào từ khách hàng.'}</strong>
                <div>${hasFilters ? 'Thử bỏ bớt bộ lọc hoặc đổi cách sắp xếp để xem thêm đánh giá.' : 'Sau khi các đơn hoàn tất có khách đánh giá, danh sách sẽ xuất hiện ở đây.'}</div>
            </div>
        `;
    };

    const renderErrorState = () => {
        refs.reviewsList.innerHTML = `
            <div class="review-error">
                <span class="material-symbols-outlined">error</span>
                <strong>Không tải được danh sách đánh giá.</strong>
                <div>Kiểm tra lại kết nối hoặc bấm làm mới để thử lại.</div>
            </div>
        `;
    };

    const renderFeedCount = () => {
        const { currentPage, lastPage, total, count } = state.pagination;

        if (!total) {
            refs.feedCount.textContent = 'Chưa có dữ liệu đánh giá';
            return;
        }

        refs.feedCount.textContent = `Hiển thị ${formatNumber(count)} / ${formatNumber(total)} đánh giá • Trang ${currentPage}/${lastPage}`;
    };

    const buildCommentHtml = (review) => {
        if (!review?.has_comment || !review?.nhan_xet) {
            return '<div class="review-card__comment is-empty">Khách có chấm sao nhưng chưa để lại nhận xét chi tiết.</div>';
        }

        return `<div class="review-card__comment">${escapeHtml(review.nhan_xet)}</div>`;
    };

    const renderReviews = (reviews) => {
        const list = Array.isArray(reviews) ? reviews : [];

        if (!list.length) {
            renderEmptyState();
            return;
        }

        refs.reviewsList.innerHTML = list.map((review) => {
            const booking = review?.booking || null;
            const customer = review?.khach_hang || {};
            const toneClass = review?.tone ? `review-card--${review.tone}` : '';

            return `
                <article class="review-card ${toneClass}">
                    <div class="review-card__top">
                        <div class="review-card__user">
                            <img class="review-card__avatar" src="${escapeHtml(customer.avatar || '/assets/images/user-default.png')}" alt="${escapeHtml(customer.name || 'Khách hàng')}">
                            <div>
                                <h3 class="review-card__name">${escapeHtml(customer.name || 'Khách hàng')}</h3>
                                <div class="review-card__meta">
                                    <span class="review-card__pill review-card__pill--soft">
                                        <span class="material-symbols-outlined">calendar_month</span>
                                        <span>${escapeHtml(review?.created_label || '--')}</span>
                                    </span>
                                    ${booking?.booking_code ? `
                                        <span class="review-card__pill">
                                            <span class="material-symbols-outlined">receipt_long</span>
                                            <span>${escapeHtml(booking.booking_code)}</span>
                                        </span>
                                    ` : ''}
                                    ${booking?.mode_label ? `<span class="review-card__pill review-card__pill--soft">${escapeHtml(booking.mode_label)}</span>` : ''}
                                </div>
                            </div>
                        </div>

                        <div class="review-card__score">
                            <div class="review-stars review-stars--light">${renderStars(review?.rating || review?.so_sao || 0)}</div>
                            <strong>${escapeHtml(`${Number(review?.rating || review?.so_sao || 0).toFixed(1)}/5`)}</strong>
                        </div>
                    </div>

                    <div class="review-card__body">
                        <div class="review-card__service">${escapeHtml(review?.service_label || 'Dịch vụ chưa xác định')}</div>
                        ${booking?.schedule_label ? `
                            <div class="review-card__schedule">
                                <span class="material-symbols-outlined">schedule</span>
                                <span>${escapeHtml(booking.schedule_label)}</span>
                            </div>
                        ` : ''}
                        ${booking?.address_excerpt ? `
                            <div class="review-card__address">
                                <span class="material-symbols-outlined">location_on</span>
                                <span>${escapeHtml(booking.address_excerpt)}</span>
                            </div>
                        ` : ''}
                        ${buildCommentHtml(review)}
                        <div class="review-card__footer">
                            <span class="review-card__footer-note">Review này gắn với đơn hoàn tất của bạn.</span>
                            ${booking?.detail_url ? `
                                <a class="review-card__link" href="${escapeHtml(booking.detail_url)}">
                                    <span>Xem đơn liên quan</span>
                                    <span class="material-symbols-outlined">arrow_forward</span>
                                </a>
                            ` : ''}
                        </div>
                    </div>
                </article>
            `;
        }).join('');
    };

    const renderPagination = () => {
        const { currentPage, lastPage } = state.pagination;

        if (lastPage <= 1) {
            refs.paginationControls.classList.add('d-none');
            refs.paginationList.innerHTML = '';
            return;
        }

        refs.paginationControls.classList.remove('d-none');

        const pages = [];
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(lastPage, currentPage + 2);

        for (let page = startPage; page <= endPage; page += 1) {
            pages.push(page);
        }

        refs.paginationList.innerHTML = `
            <li>
                <button type="button" class="review-pagination__button" data-page="${currentPage - 1}" ${currentPage <= 1 ? 'disabled' : ''}>
                    ‹
                </button>
            </li>
            ${pages.map((page) => `
                <li>
                    <button type="button" class="review-pagination__button ${page === currentPage ? 'is-active' : ''}" data-page="${page}">
                        ${page}
                    </button>
                </li>
            `).join('')}
            <li>
                <button type="button" class="review-pagination__button" data-page="${currentPage + 1}" ${currentPage >= lastPage ? 'disabled' : ''}>
                    ›
                </button>
            </li>
        `;
    };

    const setActiveRatingFilter = () => {
        refs.ratingFilters.querySelectorAll('[data-rating-filter]').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.ratingFilter === String(state.filters.rating));
        });
    };

    const setCommentToggleState = () => {
        refs.hasCommentToggle.classList.toggle('is-active', state.filters.hasComment);
        refs.hasCommentToggle.setAttribute('aria-pressed', state.filters.hasComment ? 'true' : 'false');
    };

    const fetchSummary = async () => {
        const response = await callApi(`/ho-so-tho/${currentUser.id}/danh-gia/summary`, 'GET');

        if (!response.ok) {
            throw new Error(response.data?.message || 'Không tải được tổng quan đánh giá.');
        }

        state.summary = response.data || {};
        renderSummary(state.summary);
    };

    const buildReviewQuery = (page = 1) => {
        const params = new URLSearchParams({
            page: String(page),
            per_page: String(state.pagination.perPage),
            sort: state.filters.sort,
        });

        if (state.filters.rating !== 'all') {
            params.set('rating', String(state.filters.rating));
        }

        if (state.filters.serviceId !== 'all') {
            params.set('service_id', String(state.filters.serviceId));
        }

        if (state.filters.hasComment) {
            params.set('has_comment', '1');
        }

        return params.toString();
    };

    const fetchReviews = async (page = 1) => {
        renderLoadingState();

        const response = await callApi(`/ho-so-tho/${currentUser.id}/danh-gia?${buildReviewQuery(page)}`, 'GET');

        if (!response.ok) {
            throw new Error(response.data?.message || 'Không tải được danh sách đánh giá.');
        }

        const payload = response.data || {};
        state.pagination.currentPage = Number(payload.current_page || page || 1);
        state.pagination.lastPage = Number(payload.last_page || 1);
        state.pagination.total = Number(payload.total || 0);
        state.pagination.count = Array.isArray(payload.data) ? payload.data.length : 0;

        renderFeedCount();
        renderReviews(payload.data || []);
        renderPagination();
    };

    const refreshPage = async ({ includeSummary = false, page = 1 } = {}) => {
        if (state.loading) {
            return;
        }

        state.loading = true;
        refs.refreshButton.disabled = true;

        try {
            if (includeSummary || !state.summary) {
                await fetchSummary();
            }

            await fetchReviews(page);
        } catch (error) {
            console.error('Worker reviews page error:', error);
            refs.feedCount.textContent = 'Không tải được dữ liệu đánh giá';
            renderErrorState();
            refs.paginationControls.classList.add('d-none');
            showToast(error.message || 'Không tải được trang đánh giá.', 'error');
        } finally {
            refs.refreshButton.disabled = false;
            state.loading = false;
        }
    };

    refs.ratingFilters.addEventListener('click', (event) => {
        const button = event.target.closest('[data-rating-filter]');
        if (!button) {
            return;
        }

        state.filters.rating = button.dataset.ratingFilter || 'all';
        setActiveRatingFilter();
        renderBreakdown(state.summary);
        refreshPage({ page: 1 });
    });

    refs.breakdownBars.addEventListener('click', (event) => {
        const button = event.target.closest('[data-rating-filter]');
        if (!button) {
            return;
        }

        state.filters.rating = button.dataset.ratingFilter || 'all';
        setActiveRatingFilter();
        renderBreakdown(state.summary);
        refreshPage({ page: 1 });
    });

    refs.serviceFilter.addEventListener('change', () => {
        state.filters.serviceId = refs.serviceFilter.value || 'all';
        refreshPage({ page: 1 });
    });

    refs.sortSelect.addEventListener('change', () => {
        state.filters.sort = refs.sortSelect.value || 'latest';
        refreshPage({ page: 1 });
    });

    refs.hasCommentToggle.addEventListener('click', () => {
        state.filters.hasComment = !state.filters.hasComment;
        setCommentToggleState();
        refreshPage({ page: 1 });
    });

    refs.paginationList.addEventListener('click', (event) => {
        const button = event.target.closest('[data-page]');
        if (!button || button.disabled) {
            return;
        }

        const page = Number(button.dataset.page || 1);
        if (!page || page < 1 || page > state.pagination.lastPage || page === state.pagination.currentPage) {
            return;
        }

        refreshPage({ page });
    });

    refs.refreshButton.addEventListener('click', () => {
        refreshPage({ includeSummary: true, page: 1 });
    });

    setActiveRatingFilter();
    setCommentToggleState();
    refreshPage({ includeSummary: true, page: 1 });
});
