import { callApi, getCurrentUser, showToast } from '../api.js';
import { createReviewMediaController } from './review-media.js';
import { setupReviewLightbox } from '../review-lightbox.js';
import {
    getBookingPaymentMethod,
    getBookingServices,
    isCashPaymentBooking,
    openRebookBooking,
    selectOnlineGateway as selectSharedOnlineGateway,
    showCashPaymentInstructions as showSharedCashPaymentInstructions,
} from './booking-actions-shared.js';

const user = getCurrentUser();
if (!user || !['customer', 'admin'].includes(user.role)) {
    window.location.href = '/login';
}

document.addEventListener('DOMContentLoaded', () => {
    setupReviewLightbox(document);

    const bookingsContainer = document.getElementById('myBookingsContainer');
    const paginationContainer = document.getElementById('bookingPagination');
    const paginationShell = paginationContainer?.parentElement || null;
    const tabs = Array.from(document.querySelectorAll('#bookingTab .booking-filter-pill'));
    const serviceFilter = document.getElementById('bookingServiceFilter');
    const modalReviewEl = document.getElementById('modalReview');
    const formReview = document.getElementById('formReview');
    const reviewBookingId = document.getElementById('reviewBookingId');
    const reviewRecordId = document.getElementById('reviewRecordId');
    const reviewWorkerName = document.getElementById('reviewWorkerName');
    const reviewRatingCaption = document.getElementById('reviewRatingCaption');
    const reviewComment = document.getElementById('reviewComment');
    const reviewModalLabel = document.getElementById('modalReviewLabel');
    const btnSubmitReview = document.getElementById('btnSubmitReview');
    const reviewModalInstance = modalReviewEl ? new bootstrap.Modal(modalReviewEl) : null;
    const reviewRatingInputs = Array.from(document.querySelectorAll('input[name="so_sao"]'));
    const reviewImagesInput = document.getElementById('reviewImagesInput');
    const reviewVideoInput = document.getElementById('reviewVideoInput');
    const reviewMediaPreview = document.getElementById('reviewMediaPreview');
    const reviewMediaSummary = document.getElementById('reviewMediaSummary');
    const modalComplaintEl = document.getElementById('modalComplaint');
    const complaintModalInstance = modalComplaintEl ? new bootstrap.Modal(modalComplaintEl) : null;
    const formComplaint = document.getElementById('formComplaint');
    const complaintBookingId = document.getElementById('complaintBookingId');
    const complaintBookingContext = document.getElementById('complaintBookingContext');
    const complaintNote = document.getElementById('complaintNote');
    const complaintImagesInput = document.getElementById('complaintImagesInput');
    const complaintVideoInput = document.getElementById('complaintVideoInput');
    const btnSubmitComplaint = document.getElementById('btnSubmitComplaint');

    const reviewMediaController = createReviewMediaController({
        imageInput: reviewImagesInput,
        videoInput: reviewVideoInput,
        previewContainer: reviewMediaPreview,
        summaryElement: reviewMediaSummary,
    });

    const storeAddress = 'Mang thiết bị tới cửa hàng (2 Đ. Nguyễn Đình Chiểu)';
    const statusConfig = {
        cho_xac_nhan: { label: 'Đang tìm thợ', className: 'status-cho_xac_nhan' },
        da_xac_nhan: { label: 'Đã có thợ nhận', className: 'status-da_xac_nhan' },
        khong_lien_lac_duoc_voi_khach_hang: { label: 'Không liên lạc được', className: 'status-da_huy' },
        dang_lam: { label: 'Đang xử lý', className: 'status-dang_lam' },
        cho_hoan_thanh: { label: 'Cho tho xac nhan COD', className: 'status-cho_hoan_thanh' },
        cho_thanh_toan: { label: 'Cho thanh toan online', className: 'status-cho_thanh_toan' },
        da_xong: { label: 'Da hoan tat', className: 'status-da_xong' },
        da_huy: { label: 'Da huy', className: 'status-da_huy' },
    };
    const isLocalPaymentSandbox = ['127.0.0.1', 'localhost'].includes(window.location.hostname);
    const cancelReasonOptions = {
        doi_y_khong_muon_dat: 'Đổi ý không muốn đặt',
        khong_co_tho_nao_nhan: 'Không có thợ nào nhận',
        cho_qua_lau: 'Chờ quá lâu',
    };

    let allBookings = [];
    let currentFilter = 'all';
    let currentServiceFilter = 'all';
    let currentPage = 1;

    const PAGE_SIZE = 9;

    const escapeHtml = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const formatMoney = (value) => new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));

    const renderReviewStars = (rating) => {
        const safeRating = Math.max(0, Math.min(5, Number(rating) || 0));
        let html = '';

        for (let star = 1; star <= 5; star += 1) {
            const isFilled = star <= safeRating;
            html += `
                <span class="material-symbols-outlined" style="${isFilled ? '' : "color:#cbd5e1;font-variation-settings:'FILL' 0,'wght' 500,'GRAD' 0,'opsz' 24;"}">star</span>
            `;
        }

        return html;
    };

    const updateReviewRatingCaption = (rating) => {
        if (!reviewRatingCaption) return;

        const numericRating = Number(rating || 0);
        if (!numericRating) {
            reviewRatingCaption.textContent = 'Chưa chọn số sao đánh giá';
            return;
        }

        const labelMap = {
            1: 'Ráº¥t tá»‡',
            2: 'Tệ',
            3: 'á»”n',
            4: 'Tốt',
            5: 'Rất hài lòng',
        };

        const emojiMap = {
            1: 'ðŸ˜¡',
            2: 'ðŸ˜•',
            3: 'ðŸ™‚',
            4: 'ðŸ˜Š',
            5: 'ðŸ˜',
        };

        reviewRatingCaption.textContent = [emojiMap[numericRating], labelMap[numericRating] || `${numericRating} sao`]
            .filter(Boolean)
            .join(' ');
    };

    const resetReviewFormState = () => {
        formReview?.reset();
        if (reviewRecordId) {
            reviewRecordId.value = '';
        }
        if (reviewModalLabel) {
            reviewModalLabel.textContent = 'Đánh giá dịch vụ';
        }
        if (btnSubmitReview) {
            btnSubmitReview.textContent = 'Gửi đánh giá';
        }
        reviewMediaController.reset();
        updateReviewRatingCaption();
    };

    const openReviewModal = ({ bookingId, workerName, review = null } = {}) => {
        if (!reviewBookingId || !reviewWorkerName || !formReview) return;

        resetReviewFormState();
        reviewBookingId.value = bookingId || '';
        reviewWorkerName.innerHTML = `Hãy cho chúng tôi biết cảm nhận của bạn về <strong>${escapeHtml(workerName || 'thợ đã hỗ trợ đơn này')}</strong>.`;

        if (review) {
            if (reviewRecordId) {
                reviewRecordId.value = review.id || '';
            }
            if (reviewModalLabel) {
                reviewModalLabel.textContent = 'Đánh giá lại dịch vụ';
            }
            if (btnSubmitReview) {
                btnSubmitReview.textContent = 'Cập nhật đánh giá';
            }
            if (reviewComment) {
                reviewComment.value = review.nhan_xet || '';
            }

            reviewMediaController.reset(review);

            const currentRatingInput = document.querySelector(`input[name="so_sao"][value="${Number(review.so_sao || 0)}"]`);
            if (currentRatingInput) {
                currentRatingInput.checked = true;
                updateReviewRatingCaption(currentRatingInput.value);
            }
        }

        reviewModalInstance?.show();
    };

    const complaintReasonLabels = {
        loi_tai_phat: 'Lỗi tái phát',
        linh_kien_kem_chat_luong: 'Linh kiện thay thế kém chất lượng',
    };

    const resetComplaintFormState = () => {
        formComplaint?.reset();
        if (complaintBookingId) {
            complaintBookingId.value = '';
        }
        if (complaintBookingContext) {
            complaintBookingContext.textContent = 'Khiếu nại cho đơn hàng đã hoàn tất.';
        }
        if (complaintImagesInput) {
            complaintImagesInput.value = '';
        }
        if (complaintVideoInput) {
            complaintVideoInput.value = '';
        }
        if (btnSubmitComplaint) {
            btnSubmitComplaint.textContent = 'Gá»­i khiáº¿u náº¡i';
            btnSubmitComplaint.disabled = false;
        }
        if (complaintNote) {
            complaintNote.value = '';
        }
    };

    const openComplaintModal = (booking) => {
        if (!booking || !formComplaint || !complaintBookingId) {
            return;
        }

        resetComplaintFormState();
        complaintBookingId.value = booking.id || '';
        if (complaintBookingContext) {
            const workerName = booking?.tho?.name || 'thợ xử lý đơn';
            complaintBookingContext.innerHTML = `Khiếu nại cho đơn #${escapeHtml(booking.id)} - ${escapeHtml(workerName)}.`;
        }
        complaintModalInstance?.show();
    };

    const getComplaintPolicy = (booking) => {
        const policy = booking?.complaint_policy || {};
        return {
            canComplain: Boolean(policy.can_complain),
            reason: String(policy.reason || ''),
            complaintCase: booking?.complaint_case || null,
        };
    };

    const buildComplaintActionHtml = (booking) => {
        const policy = getComplaintPolicy(booking);
        const caseInfo = policy.complaintCase;

        if (caseInfo) {
            const reasonLabel = complaintReasonLabels[caseInfo.reason_code] || caseInfo.reason_label || 'Khiáº¿u náº¡i';
            return `<p class="booking-complaint-state">Đã gửi khiếu nại: ${escapeHtml(reasonLabel)}</p>`;
        }

        if (policy.canComplain) {
            return `
                <button class="booking-action-button booking-action-button--danger btn-complaint" type="button" data-id="${booking.id}">
                    <span class="material-symbols-outlined">report_problem</span>Khiáº¿u náº¡i
                </button>
            `;
        }

        if (policy.reason === 'expired') {
            return '<p class="booking-complaint-state">Đã hết hạn gửi khiếu nại cho đơn này.</p>';
        }

        return '';
    };

    const buildReviewActionHtml = (booking) => {
        const review = getLatestReview(booking);
        const complaintActionHtml = buildComplaintActionHtml(booking);
        if (!review) {
            return `
                <button class="booking-action-button booking-action-button--primary btn-review" type="button" data-id="${booking.id}" data-worker="${escapeHtml(booking.tho?.name || 'thợ đã hỗ trợ đơn này')}">
                    <span class="material-symbols-outlined">star</span>Đánh giá
                </button>
                ${complaintActionHtml}
            `;
        }

        const canEditReview = Number(review.so_lan_sua || 0) < 1;

        return `
            <div class="booking-review-summary">
                <span class="booking-review-stars">${renderReviewStars(review.so_sao)}</span>
                <span class="booking-review-score">${Number(review.so_sao || 0).toFixed(1)}/5</span>
                <span class="booking-review-state">${canEditReview ? 'Có thể sửa 1 lần' : 'Đã khóa chỉnh sửa'}</span>
            </div>
            ${canEditReview ? `
                <button
                    class="booking-action-button booking-action-button--secondary btn-review-edit"
                    type="button"
                    data-id="${booking.id}"
                    data-worker="${escapeHtml(booking.tho?.name || 'thợ đã hỗ trợ đơn này')}"
                    data-review-id="${review.id}"
                    data-review-rating="${Number(review.so_sao || 0)}"
                    data-review-comment="${escapeHtml(review.nhan_xet || '')}">
                    <span class="material-symbols-outlined">edit_square</span>Chỉnh sửa đánh giá
                </button>
            ` : ''}
            ${complaintActionHtml}
        `;
    };

    const formatDate = (dateValue) => {
        if (!dateValue) return 'Chưa có ngày hẹn';
        if (typeof dateValue === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateValue)) {
            const [year, month, day] = dateValue.split('-');
            return `${day}/${month}/${year}`;
        }
        const date = new Date(dateValue);
        return Number.isNaN(date.getTime()) ? 'Chưa có ngày hẹn' : date.toLocaleDateString('vi-VN');
    };

    const getLatestReview = (booking) => Array.isArray(booking?.danh_gias) ? booking.danh_gias[0] || null : null;

    const buildReviewMediaMeta = (review) => {
        const imageCount = Array.isArray(review?.hinh_anh_danh_gia) ? review.hinh_anh_danh_gia.filter(Boolean).length : 0;
        const hasVideo = Boolean(review?.video_danh_gia);

        if (!imageCount && !hasVideo) {
            return '';
        }

        return [imageCount ? `${imageCount} anh` : '', hasVideo ? '1 video' : '']
            .filter(Boolean)
            .join(' â€¢ ');
    };

    const normalizeServiceName = (value = '') => String(value).trim().toLowerCase();
    const buildOnlineGatewayOptions = () => ({
        momo_atm: 'MoMo ATM / test card',
        zalopay: 'Ví ZaloPay',
        vnpay: 'VNPAY / Thẻ ngân hàng',
    });

    const getStatusMeta = (status) => statusConfig[status] || {
        label: 'Đang cập nhật',
        className: 'status-da_xac_nhan',
    };

    const getStoredCostItems = (booking, key) => Array.isArray(booking?.[key]) ? booking[key].filter(Boolean) : [];

    const getBookingTotal = (booking) => {
        const total = Number(booking.tong_tien || 0);
        if (total > 0) return total;

        const laborItems = getStoredCostItems(booking, 'chi_tiet_tien_cong');
        const partItems = getStoredCostItems(booking, 'chi_tiet_linh_kien');
        const laborTotal = laborItems.length
            ? laborItems.reduce((sum, item) => sum + Number(item?.so_tien || 0), 0)
            : Number(booking.tien_cong || 0);
        const partTotal = partItems.length
            ? partItems.reduce((sum, item) => sum + Number(item?.so_tien || 0), 0)
            : Number(booking.phi_linh_kien || 0);

        return Number(booking.phi_di_lai || 0)
            + partTotal
            + laborTotal
            + Number(booking.tien_thue_xe || 0);
    };

    const getServiceVisual = (booking, services) => {
        const firstService = services[0] || {};
        const serviceName = String(firstService.ten_dich_vu || '').toLowerCase();
        let theme = booking.loai_dat_lich === 'at_store' ? 'theme-store' : 'theme-generic';
        let icon = 'build_circle';

        if (serviceName.includes('máy lạnh') || serviceName.includes('điều hòa') || serviceName.includes('dieu hoa')) {
            theme = 'theme-cool';
            icon = 'ac_unit';
        } else if (serviceName.includes('nước') || serviceName.includes('ống') || serviceName.includes('nuoc')) {
            theme = 'theme-water';
            icon = 'water_drop';
        } else if (serviceName.includes('giáº·t') || serviceName.includes('giat')) {
            theme = 'theme-laundry';
            icon = 'local_laundry_service';
        } else if (serviceName.includes('điện') || serviceName.includes('dien')) {
            theme = 'theme-electric';
            icon = 'electrical_services';
        } else if (serviceName.includes('tá»§ láº¡nh') || serviceName.includes('tu lanh')) {
            theme = 'theme-store';
            icon = 'kitchen';
        }

        if (firstService.hinh_anh) {
            return {
                theme,
                html: `<img src="${escapeHtml(firstService.hinh_anh)}" alt="${escapeHtml(firstService.ten_dich_vu || 'Dá»‹ch vá»¥')}" class="booking-service-image">`,
            };
        }

        return {
            theme,
            html: `<div class="booking-service-icon"><span class="material-symbols-outlined">${icon}</span></div>`,
        };
    };

    const buildWorkerHtml = (booking) => {
        const worker = booking.tho || null;
        if (!worker || !worker.name) {
            return `
                <div class="booking-assignee-pending">
                    <span class="material-symbols-outlined">person_search</span>
                    <div>
                        <strong>Đang tìm thợ</strong>
                        <span>Chưa có thợ nhận đơn.</span>
                    </div>
                </div>
            `;
        }

        return `
            <div class="booking-assignee-info">
                <img src="${escapeHtml(worker.avatar || '/assets/images/user-default.png')}" alt="${escapeHtml(worker.name)}" class="booking-assignee-avatar">
                <div>
                    <p class="booking-assignee-name">${escapeHtml(worker.name)}</p>
                    <p class="booking-assignee-phone">${escapeHtml(worker.phone || '')}</p>
                </div>
            </div>
        `;
    };

    const buildActionHtml = (booking) => {
        if (booking.trang_thai === 'da_xong') {
            return buildReviewActionHtml(booking);
        }

        if (['cho_xac_nhan', 'da_xac_nhan', 'khong_lien_lac_duoc_voi_khach_hang'].includes(booking.trang_thai)) {
            return '<button class="booking-action-button booking-action-button--danger btn-cancel" type="button" data-id="' + booking.id + '">Hủy yêu cầu</button>';
        }

        if (booking.trang_thai === 'dang_lam') {
            return `
                <div class="booking-review-summary">
                    <span class="material-symbols-outlined">autorenew</span>
                    Thợ đang xử lý yêu cầu
                </div>
            `;
        }

        if (booking.trang_thai === 'cho_hoan_thanh' || booking.trang_thai === 'cho_thanh_toan') {
            const isCash = isCashPaymentBooking(booking);
            if (isCash) {
                return `
                    <div class="booking-review-summary">
                        <span class="material-symbols-outlined">payments</span>
                        Thanh toán tiền mặt, chờ thợ xác nhận
                    </div>
                `;
            }

            return `
                <button class="booking-action-button booking-action-button--primary btn-payment-choice" type="button" data-id="${booking.id}">
                    <span class="material-symbols-outlined">account_balance_wallet</span>Chọn cách thanh toán
                </button>
            `;
        }

        if (booking.trang_thai === 'da_xong') {
            const review = Array.isArray(booking.danh_gias) ? booking.danh_gias[0] : null;
            if (!review) {
                return `
                    <button class="booking-action-button booking-action-button--primary btn-review" type="button" data-id="${booking.id}" data-worker="${escapeHtml(booking.tho?.name || 'thợ đã hỗ trợ đơn này')}">
                        <span class="material-symbols-outlined">star</span>Đánh giá
                    </button>
                `;
            }

            return `
                <div class="booking-review-summary">
                    <span class="booking-review-stars">${'<i class="fas fa-star"></i>'.repeat(Number(review.so_sao || 0))}${'<i class="far fa-star"></i>'.repeat(5 - Number(review.so_sao || 0))}</span>
                    <span>Đã đánh giá</span>
                </div>
            `;
        }

        if (booking.trang_thai === 'da_huy') {
            return `
                <button class="booking-action-button booking-action-button--primary btn-rebook" type="button" data-id="${booking.id}">
                    <span class="material-symbols-outlined">refresh</span>Đặt lại
                </button>
            `;
        }

        return '';
    };

    const renderFeedbackCard = ({ icon, title, text, actionHtml = '', extraHtml = '' }) => `
        <div class="col-12">
            <div class="history-feedback-card">
                <div class="history-feedback-icon"><span class="material-symbols-outlined">${icon}</span></div>
                <h3>${title}</h3>
                <p>${text}</p>
                ${extraHtml}
                ${actionHtml}
            </div>
        </div>
    `;

    const ensureOk = (response, fallbackMessage) => {
        if (!response?.ok) {
            throw new Error(response?.data?.message || fallbackMessage);
        }

        return response;
    };

    const clearPagination = () => {
        if (paginationShell) {
            paginationShell.classList.add('d-none');
        }
        if (paginationContainer) {
            paginationContainer.innerHTML = '';
        }
    };

    const renderLoading = () => {
        clearPagination();
        bookingsContainer.innerHTML = renderFeedbackCard({
            icon: 'progress_activity',
            title: 'Đang tải danh sách',
            text: 'Hệ thống đang lấy dữ liệu booking mới nhất của bạn.',
            extraHtml: `
                <div class="mt-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            `,
        });
    };

    const renderError = () => {
        clearPagination();
        bookingsContainer.innerHTML = renderFeedbackCard({
            icon: 'error',
            title: 'Không tải được lịch sử đặt lịch',
            text: 'Đã có lỗi xảy ra khi lấy danh sách booking. Vui lòng thử tải lại trong ít phút nữa.',
            actionHtml: `
                <button class="history-cta border-0 mt-3" type="button" id="btnRetryBookings">
                    <span class="material-symbols-outlined">refresh</span>Tải lại
                </button>
            `,
        });
        document.getElementById('btnRetryBookings')?.addEventListener('click', () => loadBookings());
    };

    const renderEmpty = (isFiltered = false) => {
        clearPagination();
        bookingsContainer.innerHTML = renderFeedbackCard({
            icon: isFiltered ? 'filter_alt_off' : 'calendar_month',
            title: isFiltered ? 'Không có đơn phù hợp' : 'Chưa có lịch đặt nào',
            text: isFiltered
                ? 'Không có đơn nào khớp với trạng thái hoặc dịch vụ bạn đang chọn. Hãy đổi bộ lọc để xem thêm.'
                : 'Bạn chưa có lịch hẹn nào được lưu. Hãy đặt lịch mới để bắt đầu.',
            actionHtml: `
                <a href="/customer/home" class="history-cta">
                    <span class="material-symbols-outlined">add_circle</span>Đặt lịch mới
                </a>
            `,
        });
    };

    const upgradeReviewSummaryCards = (bookings) => {
        const reviewedBookings = bookings.filter((booking) => (
            booking.trang_thai === 'da_xong'
            && Array.isArray(booking.danh_gias)
            && booking.danh_gias.length > 0
        ));

        const reviewSummaryNodes = Array.from(
            bookingsContainer.querySelectorAll('.booking-review-summary')
        ).filter((node) => node.querySelector('.booking-review-stars'));

        reviewSummaryNodes.forEach((node, index) => {
            const review = reviewedBookings[index]?.danh_gias?.[0];
            if (!review) return;
            const canEditReview = Number(review.so_lan_sua || 0) < 1;

            node.innerHTML = `
                <span class="booking-review-stars">${renderReviewStars(review.so_sao)}</span>
                <span class="booking-review-score">${Number(review.so_sao || 0).toFixed(1)}/5</span>
                <span class="booking-review-state">${canEditReview ? 'Có thể sửa 1 lần' : 'Đã khóa chỉnh sửa'}</span>
            `;
        });
    };

    const renderBookings = (bookings) => {
        if (!bookings.length) {
            renderEmpty(currentFilter !== 'all' || currentServiceFilter !== 'all');
            return;
        }

        const totalPages = Math.max(1, Math.ceil(bookings.length / PAGE_SIZE));
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        const startIndex = (currentPage - 1) * PAGE_SIZE;
        const paginatedBookings = bookings.slice(startIndex, startIndex + PAGE_SIZE);

        bookingsContainer.innerHTML = paginatedBookings.map((booking) => {
            const services = getBookingServices(booking);
            const firstServiceName = services[0]?.ten_dich_vu || 'Dá»‹ch vá»¥ sá»­a chá»¯a';
            const statusMeta = getStatusMeta(booking.trang_thai);
            const visual = getServiceVisual(booking, services);
            const locationText = booking.loai_dat_lich === 'at_home' ? (booking.dia_chi || 'Chưa cập nhật địa chỉ') : (booking.dia_chi || storeAddress);
            const visibleTags = services.slice(0, 2).map((service) => `<span class="service-tag">${escapeHtml(service.ten_dich_vu)}</span>`);
            if (services.length > 2) {
                visibleTags.push(`<span class="service-tag service-tag--muted">+${services.length - 2}</span>`);
            }
            const serviceTags = services.length > 1 ? `<div class="service-tags">${visibleTags.join('')}</div>` : '';
            const totalAmount = getBookingTotal(booking);

            return `
                <div class="col-xl-4 col-lg-4 col-md-6">
                    <article
                        class="booking-showcase-card booking-showcase-card--compact booking-showcase-card--interactive"
                        data-booking-detail-url="/customer/my-bookings/${booking.id}"
                        tabindex="0"
                        role="link"
                        aria-label="Xem chi tiet don #${escapeHtml(booking.id)}"
                    >
                        <div class="booking-card-media ${visual.theme}">
                            <span class="booking-code-chip">#${escapeHtml(booking.id)}</span>
                            ${visual.html}
                        </div>
                        <div class="booking-card-body booking-card-body--compact">
                            <h3 class="booking-card-title">${escapeHtml(firstServiceName)}</h3>
                            <div class="booking-card-meta">
                                <span class="material-symbols-outlined">tag</span>Mã đơn #${escapeHtml(booking.id)}
                            </div>
                            <div class="booking-card-meta">
                                <span class="material-symbols-outlined">schedule</span>${formatDate(booking.ngay_hen)} - ${escapeHtml(booking.khung_gio_hen || 'Chưa chọn giờ')}
                            </div>
                            <div class="booking-compact-row">
                                <span class="booking-status-chip ${statusMeta.className}">${statusMeta.label}</span>
                                ${serviceTags}
                            </div>
                            <div class="booking-quick-stack">
                                <div class="booking-address-line">
                                    <span class="material-symbols-outlined">pin_drop</span>
                                    <span>${escapeHtml(locationText)}</span>
                                </div>
                                ${booking.thue_xe_cho ? `
                                    <div class="booking-transport-line">
                                        <span class="material-symbols-outlined">local_shipping</span>
                                        <span>Có yêu cầu thuê xe chở thiết bị</span>
                                    </div>
                                ` : ''}
                            </div>
                            <div class="booking-card-footer">
                                <div class="booking-assignee booking-assignee--compact">${buildWorkerHtml(booking)}</div>
                                <div class="booking-total-mini">
                                    <span>Tổng</span>
                                    <strong>${formatMoney(totalAmount)}</strong>
                                </div>
                            </div>
                            <div class="booking-action-area booking-action-area--compact">${buildActionHtml(booking)}</div>
                        </div>
                    </article>
                </div>
            `;
        }).join('');

        upgradeReviewSummaryCards(paginatedBookings);
        attachActionListeners();
        attachCardNavigationListeners();
        renderPagination(bookings.length, totalPages);
    };

    const buildPaginationModel = (totalPages, page) => {
        if (totalPages <= 7) {
            return Array.from({ length: totalPages }, (_, index) => index + 1);
        }

        const items = [1];

        if (page > 3) {
            items.push('ellipsis-left');
        }

        const start = Math.max(2, page - 1);
        const end = Math.min(totalPages - 1, page + 1);
        for (let value = start; value <= end; value += 1) {
            items.push(value);
        }

        if (page < totalPages - 2) {
            items.push('ellipsis-right');
        }

        items.push(totalPages);
        return items;
    };

    const renderPagination = (totalItems, totalPages) => {
        if (!paginationContainer) {
            return;
        }

        if (totalItems <= PAGE_SIZE || totalPages <= 1) {
            clearPagination();
            return;
        }

        paginationShell?.classList.remove('d-none');

        const items = buildPaginationModel(totalPages, currentPage);

        paginationContainer.innerHTML = [
            `<button class="booking-page-btn is-nav" type="button" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>Trước</button>`,
            ...items.map((item) => {
                if (typeof item !== 'number') {
                    return '<span class="booking-page-btn is-ellipsis">...</span>';
                }

                return `<button class="booking-page-btn ${item === currentPage ? 'is-active' : ''}" type="button" data-page="${item}">${item}</button>`;
            }),
            `<button class="booking-page-btn is-nav" type="button" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>Sau</button>`,
        ].join('');

        paginationContainer.querySelectorAll('[data-page]').forEach((button) => {
            button.addEventListener('click', () => {
                const nextPage = Number(button.dataset.page || 1);
                if (!Number.isFinite(nextPage) || nextPage < 1 || nextPage === currentPage || nextPage > totalPages) {
                    return;
                }

                currentPage = nextPage;
                filterAndRender();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    };

    const populateServiceFilter = () => {
        if (!serviceFilter) return;

        const serviceMap = new Map();
        allBookings.forEach((booking) => {
            getBookingServices(booking).forEach((service) => {
                const serviceName = String(service?.ten_dich_vu || '').trim();
                const normalizedName = normalizeServiceName(serviceName);
                if (serviceName && normalizedName && !serviceMap.has(normalizedName)) {
                    serviceMap.set(normalizedName, serviceName);
                }
            });
        });

        const optionsHtml = ['<option value="all">Tất cả dịch vụ</option>']
            .concat(
                Array.from(serviceMap.entries())
                    .sort((a, b) => a[1].localeCompare(b[1], 'vi'))
                    .map(([value, label]) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`)
            )
            .join('');

        serviceFilter.innerHTML = optionsHtml;
        serviceFilter.value = serviceMap.has(currentServiceFilter) ? currentServiceFilter : 'all';
        currentServiceFilter = serviceFilter.value;
    };

    const filterAndRender = () => {
        let filtered = [...allBookings];

        if (currentFilter === 'active') {
            filtered = filtered.filter((booking) => ['cho_xac_nhan', 'da_xac_nhan', 'khong_lien_lac_duoc_voi_khach_hang', 'dang_lam'].includes(booking.trang_thai));
        } else if (currentFilter === 'payment') {
            filtered = filtered.filter((booking) => ['cho_hoan_thanh', 'cho_thanh_toan'].includes(booking.trang_thai));
        } else if (currentFilter === 'completed') {
            filtered = filtered.filter((booking) => booking.trang_thai === 'da_xong');
        } else if (currentFilter === 'cancelled') {
            filtered = filtered.filter((booking) => booking.trang_thai === 'da_huy');
        }

        if (currentServiceFilter !== 'all') {
            filtered = filtered.filter((booking) => getBookingServices(booking).some((service) => (
                normalizeServiceName(service?.ten_dich_vu || '') === currentServiceFilter
            )));
        }

        renderBookings(filtered);
    };

    const fetchAllBookings = async () => {
        let page = 1;
        let lastPage = 1;
        const collected = [];

        do {
            const res = ensureOk(await callApi(`/don-dat-lich?page=${page}`), 'Không tải được danh sách booking');
            const payload = res.data || {};
            const items = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);

            collected.push(...items);

            if (!Array.isArray(payload?.data)) {
                break;
            }

            lastPage = Number(payload?.last_page || 1);
            page += 1;
        } while (page <= lastPage);

        return collected;
    };

    const loadBookings = async () => {
        renderLoading();

        try {
            allBookings = await fetchAllBookings();
            populateServiceFilter();
            filterAndRender();
        } catch (error) {
            console.error('Error fetching my bookings:', error);
            renderError();
        }
    };

    const showLegacyCashPaymentInstructions = async (booking) => {
        await Swal.fire({
            title: 'Thanh toán tiền mặt',
            html: `
                <div style="text-align:left; line-height:1.65;">
                    <p style="margin-bottom:0.75rem;">Thợ đã chốt đơn này với phương thức <strong>tiền mặt</strong>.</p>
                    <p style="margin-bottom:0.75rem;">Bạn thanh toán trực tiếp cho thợ sau khi kiểm tra kết quả sửa chữa.</p>
                    <p style="margin:0;">Nếu đơn chưa hoàn tất ngay, vui lòng liên hệ thợ hoặc cửa hàng để được hỗ trợ đối soát.</p>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'Đã hiểu',
        });
    };

    const showCashPaymentInstructions = async () => showSharedCashPaymentInstructions({ swal: Swal });

    const syncBookingPaymentMethod = async (booking, paymentMethod) => {
        if (getBookingPaymentMethod(booking) === paymentMethod) {
            return booking;
        }

        const response = ensureOk(
            await callApi(`/bookings/${booking.id}/payment-method`, 'PUT', {
                phuong_thuc_thanh_toan: paymentMethod,
            }),
            'Không thể cập nhật phương thức thanh toán'
        );

        Object.assign(booking, response.data?.booking || {});
        showToast(response.data?.message || 'Đã cập nhật phương thức thanh toán.');

        return booking;
    };

    const selectPendingPaymentMode = async (booking) => {
        const result = await Swal.fire({
            title: 'Chọn cách thanh toán',
            input: 'radio',
            inputOptions: {
                cod: 'Tiá»n máº·t trá»±c tiáº¿p cho thá»£',
                transfer: 'Chuyển khoản online / ví điện tử',
            },
            inputValue: getBookingPaymentMethod(booking),
            inputValidator: (value) => (!value ? 'Vui lòng chọn cách thanh toán.' : undefined),
            showCancelButton: true,
            confirmButtonText: 'Tiáº¿p tá»¥c',
            cancelButtonText: 'Đóng',
        });

        return result.isConfirmed ? result.value : null;
    };

    const selectLegacyOnlineGateway = async () => {
        const gatewayOptions = buildOnlineGatewayOptions();
        const gatewayKeys = Object.keys(gatewayOptions);
        const result = await Swal.fire({
            title: 'Chọn cổng thanh toán',
            input: 'radio',
            inputOptions: gatewayOptions,
            inputValue: gatewayKeys[0] || 'momo',
            inputValidator: (value) => (!value ? 'Vui lòng chọn ví hoặc cổng thanh toán.' : undefined),
            showCancelButton: true,
            confirmButtonText: 'Mở thanh toán',
            cancelButtonText: 'Quay láº¡i',
        });

        return result.isConfirmed ? result.value : null;
    };

    const selectOnlineGateway = async (booking) => selectSharedOnlineGateway({
        booking,
        isLocalPaymentSandbox,
        swal: Swal,
    });

    const startOnlinePayment = async (booking, gateway) => {
        const isMomoAtmPayment = gateway === 'momo_atm';
        const result = await Swal.fire({
            title: isMomoAtmPayment ? 'Chuyển sang MoMo ATM / test card' : 'Chuyển sang cổng thanh toán',
            text: isMomoAtmPayment
                ? 'Hệ thống sẽ chuyển bạn sang trang MoMo ATM/test card để nhập thẻ test trực tiếp trên web.'
                : 'Hệ thống sẽ chuyển bạn sang ví điện tử hoặc cổng thanh toán đã chọn để hoàn tất đơn hàng.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Tiếp tục',
            cancelButtonText: 'Đóng',
        });

        if (!result.isConfirmed) return;

        const res = ensureOk(await callApi('/payment/create', 'POST', {
            don_dat_lich_id: booking.id,
            phuong_thuc: gateway,
        }), 'Không tạo được giao dịch thanh toán');

        if (res.data?.url) {
            window.location.href = res.data.url;
            return;
        }

        showToast(res.data?.message || 'Thanh toán thành công.');
        await loadBookings();
    };

    const openPaymentAction = async (booking) => {
        if (getBookingPaymentMethod(booking) !== 'transfer') {
            await showCashPaymentInstructions(booking);
            return;
        }

        const selectedGateway = await selectOnlineGateway(booking);
        if (!selectedGateway) {
            return;
        }

        await startOnlinePayment(booking, selectedGateway);
    };

    const attachCardNavigationListeners = () => {
        document.querySelectorAll('[data-booking-detail-url]').forEach((card) => {
            const navigateToDetail = () => {
                const destination = card.dataset.bookingDetailUrl;
                if (destination) {
                    window.location.href = destination;
                }
            };

            card.addEventListener('click', (event) => {
                if (event.target.closest('button, a, input, select, textarea, label, .booking-action-area')) {
                    return;
                }

                navigateToDetail();
            });

            card.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                if (event.target.closest('button, a, input, select, textarea, label')) {
                    return;
                }

                event.preventDefault();
                navigateToDetail();
            });
        });
    };

    const attachActionListeners = () => {
        document.querySelectorAll('.btn-cancel').forEach((button) => {
            button.addEventListener('click', async (event) => {
                const id = event.currentTarget.dataset.id;
                const result = await Swal.fire({
                    title: 'Hủy đơn đặt lịch?',
                    icon: 'warning',
                    input: 'radio',
                    inputOptions: cancelReasonOptions,
                    inputLabel: 'Chọn lý do hủy',
                    inputValidator: (value) => (!value ? 'Vui lòng chọn lý do hủy đơn.' : undefined),
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Có, hủy đơn',
                    cancelButtonText: 'Đóng',
                });

                if (!result.isConfirmed) return;

                try {
                    ensureOk(await callApi(`/don-dat-lich/${id}/status`, 'PUT', {
                        trang_thai: 'da_huy',
                        ma_ly_do_huy: result.value,
                    }), 'Lỗi khi hủy đơn');
                    showToast('Hủy đơn thành công');
                    loadBookings();
                } catch (error) {
                    showToast(error.message || 'Lỗi khi hủy đơn', 'error');
                }
            });
        });

        document.querySelectorAll('.btn-payment-choice').forEach((button) => {
            button.addEventListener('click', async (event) => {
                const currentButton = event.currentTarget;
                const id = currentButton.dataset.id;
                /*
                const booking = allBookings.find((item) => String(item.id) === String(currentButton.dataset.id || ''));
                const review = getLatestReview(booking);

                if (!review) {
                    showToast('Không tìm thấy đánh giá để chỉnh sửa.', 'error');
                    return;
                }

                openReviewModal({
                    bookingId: currentButton.dataset.id || '',
                    workerName: currentButton.dataset.worker || 'thợ đã hỗ trợ đơn này',
                    review,
                });
                return;
                */
                try {
                    currentButton.disabled = true;
                    const booking = allBookings.find((item) => String(item.id) === String(id));
                    if (!booking) {
                        throw new Error('Không tìm thấy đơn để xử lý thanh toán.');
                    }
                    await openPaymentAction(booking);
                } catch (error) {
                    showToast(error.message || 'Không thể thanh toán đơn này', 'error');
                } finally {
                    currentButton.disabled = false;
                }
            });
        });

        document.querySelectorAll('.btn-rebook').forEach((button) => {
            button.addEventListener('click', (event) => {
                const booking = allBookings.find((item) => String(item.id) === String(event.currentTarget.dataset.id || ''));
                if (!booking) {
                    showToast('Không tìm thấy đơn để đặt lại.', 'error');
                    return;
                }

                openRebookBooking(booking);
            });
        });

        document.querySelectorAll('.btn-complaint').forEach((button) => {
            button.addEventListener('click', (event) => {
                const bookingId = event.currentTarget.dataset.id || '';
                const booking = allBookings.find((item) => String(item.id) === String(bookingId));
                if (!booking) {
                    showToast('Không tìm thấy đơn để gửi khiếu nại.', 'error');
                    return;
                }

                openComplaintModal(booking);
            });
        });

        document.querySelectorAll('.btn-review').forEach((button) => {
            button.addEventListener('click', (event) => {
                const currentButton = event.currentTarget;
                openReviewModal({
                    bookingId: currentButton.dataset.id || '',
                    workerName: currentButton.dataset.worker || 'thợ đã hỗ trợ đơn này',
                });
                return;
                if (reviewRecordId) {
                    reviewRecordId.value = '';
                }
                if (reviewModalLabel) {
                    reviewModalLabel.textContent = 'Đánh giá dịch vụ';
                }
                if (btnSubmitReview) {
                    btnSubmitReview.textContent = 'Gửi đánh giá';
                }
                reviewWorkerName.innerHTML = `Hãy cho chúng tôi biết cảm nhận của bạn về <strong>${escapeHtml(currentButton.dataset.worker || 'thợ đã hỗ trợ đơn này')}</strong>.`;
                formReview.reset();
                updateReviewRatingCaption();
                reviewModalInstance?.show();
            });
        });

        document.querySelectorAll('.btn-review-edit').forEach((button) => {
            button.addEventListener('click', (event) => {
                const currentButton = event.currentTarget;
                openReviewModal({
                    bookingId: currentButton.dataset.id || '',
                    workerName: currentButton.dataset.worker || 'thợ đã hỗ trợ đơn này',
                    review: {
                        id: currentButton.dataset.reviewId || '',
                        so_sao: currentButton.dataset.reviewRating || 0,
                        nhan_xet: currentButton.dataset.reviewComment || '',
                    },
                });
            });
        });
    };

    const handlePaymentStatusNotice = () => {
        const paymentStatus = new URLSearchParams(window.location.search).get('payment');
        if (!paymentStatus) return;

        if (paymentStatus === 'success') {
            Swal.fire({
                title: 'Thanh toán thành công',
                text: 'Giao dịch trực tuyến đã hoàn tất và đơn của bạn đã được cập nhật.',
                icon: 'success',
                confirmButtonText: 'Đóng',
            });
        } else if (paymentStatus === 'failed') {
            Swal.fire({
                title: 'Thanh toán thất bại',
                text: 'Giao dịch đã bị hủy hoặc có lỗi trong quá trình thanh toán.',
                icon: 'error',
                confirmButtonText: 'Đóng',
            });
        } else if (paymentStatus === 'invalid_signature') {
            Swal.fire({
                title: 'Giao dịch không hợp lệ',
                text: 'Mã xác thực thanh toán không khớp nên giao dịch đã bị từ chối.',
                icon: 'error',
                confirmButtonText: 'Đóng',
            });
        }

        window.history.replaceState({}, document.title, window.location.pathname);
    };

    document.addEventListener('click', (event) => {
        const reviewTrigger = event.target.closest('.btn-review, .btn-review-edit');
        if (!reviewTrigger) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        const bookingId = reviewTrigger.dataset.id || '';
        const workerName = reviewTrigger.dataset.worker || 'tho da ho tro don nay';

        if (reviewTrigger.classList.contains('btn-review-edit')) {
            const booking = allBookings.find((item) => String(item.id) === String(bookingId));
            const review = getLatestReview(booking);

            if (!review) {
                showToast('Không tìm thấy đánh giá để chỉnh sửa.', 'error');
                return;
            }

            openReviewModal({
                bookingId,
                workerName,
                review,
            });
            return;
        }

        openReviewModal({
            bookingId,
            workerName,
        });
    }, true);

    if (formReview) {
        reviewRatingInputs.forEach((input) => {
            input.addEventListener('change', () => {
                updateReviewRatingCaption(input.value);
            });
        });

        modalReviewEl?.addEventListener('hidden.bs.modal', () => {
            resetReviewFormState();
        });

        formReview.addEventListener('submit', async (event) => {
            event.preventDefault();
            const selectedRating = document.querySelector('input[name="so_sao"]:checked');
            if (!selectedRating) {
                showToast('Vui lòng chọn số sao đánh giá', 'error');
                return;
            }

            btnSubmitReview.disabled = true;
            btnSubmitReview.textContent = 'Đang gửi...';

            try {
                const editingReviewId = reviewRecordId?.value || '';
                const formData = new FormData();
                formData.append('don_dat_lich_id', reviewBookingId.value || '');
                formData.append('so_sao', selectedRating.value);
                formData.append('nhan_xet', reviewComment?.value || '');
                reviewMediaController.appendToFormData(formData);
                if (editingReviewId) {
                    formData.append('_method', 'PUT');
                    ensureOk(await callApi(`/danh-gia/${editingReviewId}`, 'POST', formData), 'Loi cap nhat danh gia');
                    /* legacy review payload removed
                    legacy review payload removed
                    }), 'Lỗi cập nhật đánh giá');
                    showToast('Cập nhật đánh giá thành công!');
                    */ showToast('Cap nhat danh gia thanh cong!'); reviewModalInstance?.hide();
                    loadBookings();
                    return;
                }

                ensureOk(await callApi('/danh-gia', 'POST', formData), 'Loi gui danh gia');
                /*
                    don_dat_lich_id: reviewBookingId.value,
                    so_sao: selectedRating.value,
                    nhan_xet: document.getElementById('reviewComment').value,
                }), 'Lỗi gửi đánh giá');
                showToast('Cảm ơn bạn đã đánh giá!');
                */ showToast('Cam on ban da danh gia!'); reviewModalInstance?.hide();
                loadBookings();
            } catch (error) {
                showToast(error.message || 'Lỗi gửi đánh giá', 'error');
            } finally {
                btnSubmitReview.disabled = false;
                setTimeout(() => {
                    btnSubmitReview.textContent = reviewRecordId?.value ? 'Cap nhat danh gia' : 'Gui danh gia';
                }, 0);
                btnSubmitReview.textContent = 'Gửi đánh giá';
            }
        });
    }

    modalComplaintEl?.addEventListener('hidden.bs.modal', () => {
        resetComplaintFormState();
    });

    if (formComplaint) {
        formComplaint.addEventListener('submit', async (event) => {
            event.preventDefault();

            const bookingId = complaintBookingId?.value || '';
            const selectedReason = formComplaint.querySelector('input[name="ly_do_khieu_nai"]:checked');
            if (!bookingId) {
                showToast('Không xác định được đơn cần khiếu nại.', 'error');
                return;
            }

            if (!selectedReason) {
                showToast('Vui lòng chọn lý do khiếu nại.', 'error');
                return;
            }

            const imageFiles = Array.from(complaintImagesInput?.files || []);
            if (imageFiles.length > 5) {
                showToast('Bạn chỉ được tải tối đa 5 ảnh.', 'error');
                return;
            }

            if (btnSubmitComplaint) {
                btnSubmitComplaint.disabled = true;
                btnSubmitComplaint.textContent = 'Đang gửi...';
            }

            try {
                const formData = new FormData();
                formData.append('ly_do_khieu_nai', selectedReason.value);
                formData.append('ghi_chu', complaintNote?.value || '');

                imageFiles.forEach((file) => {
                    formData.append('hinh_anh_khieu_nai[]', file);
                });

                const videoFile = complaintVideoInput?.files?.[0] || null;
                if (videoFile) {
                    formData.append('video_khieu_nai', videoFile);
                }

                ensureOk(await callApi(`/don-dat-lich/${bookingId}/complaint`, 'POST', formData), 'Lá»—i gá»­i khiáº¿u náº¡i');
                showToast('Đã gửi khiếu nại thành công.');
                complaintModalInstance?.hide();
                await loadBookings();
            } catch (error) {
                showToast(error.message || 'Không thể gửi khiếu nại', 'error');
            } finally {
                if (btnSubmitComplaint) {
                    btnSubmitComplaint.disabled = false;
                    btnSubmitComplaint.textContent = 'Gá»­i khiáº¿u náº¡i';
                }
            }
        });
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((item) => item.classList.remove('active'));
            tab.classList.add('active');
            currentFilter = tab.dataset.filter || 'all';
            currentPage = 1;
            filterAndRender();
        });
    });

    serviceFilter?.addEventListener('change', (event) => {
        currentServiceFilter = event.currentTarget.value || 'all';
        currentPage = 1;
        filterAndRender();
    });

    updateReviewRatingCaption();
    handlePaymentStatusNotice();
    loadBookings();
});
