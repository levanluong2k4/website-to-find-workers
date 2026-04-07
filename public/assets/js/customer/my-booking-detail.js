import { callApi, getCurrentUser, showToast } from '../api.js';

const user = getCurrentUser();
if (!user || !['customer', 'admin'].includes(user.role)) {
    window.location.href = '/login';
}

document.addEventListener('DOMContentLoaded', () => {
    const pageRoot = document.getElementById('bookingDetailPage');
    const loadingEl = document.getElementById('bookingDetailLoading');
    const errorEl = document.getElementById('bookingDetailError');
    const contentEl = document.getElementById('bookingDetailContent');
    const topbarOrderCodeEl = document.getElementById('detailTopbarOrderCode');
    const reviewModalEl = document.getElementById('bookingDetailReviewModal');
    const reviewForm = document.getElementById('bookingDetailReviewForm');
    const reviewBookingId = document.getElementById('bookingDetailReviewBookingId');
    const reviewWorkerName = document.getElementById('bookingDetailReviewWorkerName');
    const reviewComment = document.getElementById('bookingDetailReviewComment');
    const reviewSubmitButton = document.getElementById('bookingDetailSubmitReview');
    const reviewModalInstance = reviewModalEl ? new bootstrap.Modal(reviewModalEl) : null;
    const bookingId = Number(window.customerBookingDetailId || pageRoot?.dataset.bookingId || 0);
    const storeAddress = '2 Đường Nguyễn Đình Chiểu, Vĩnh Thọ, Nha Trang, Khánh Hòa';
    const isLocalPaymentSandbox = ['127.0.0.1', 'localhost'].includes(window.location.hostname);
    const cancelReasonOptions = {
        doi_y_khong_muon_dat: 'Đổi ý không muốn đặt',
        khong_co_tho_nao_nhan: 'Không có thợ nào nhận',
        cho_qua_lau: 'Chờ quá lâu',
    };
    const defaultRescheduleSlots = ['08:00-10:00', '10:00-12:00', '12:00-14:00', '14:00-17:00'];
    let currentBooking = null;

    const escapeHtml = (value = '') => String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    const ensureOk = (response, fallbackMessage) => {
        if (!response?.ok) throw new Error(response?.data?.message || fallbackMessage);
        return response;
    };
    const normalizeDateInput = (value) => (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value) ? `${value}T00:00:00` : value || null);
    const formatDate = (value) => {
        if (!value) return 'Chưa cập nhật';
        if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)) {
            const [year, month, day] = value.split('-');
            return `${day}/${month}/${year}`;
        }
        const date = new Date(normalizeDateInput(value));
        return Number.isNaN(date.getTime()) ? 'Chưa cập nhật' : date.toLocaleDateString('vi-VN');
    };
    const formatDateTime = (value) => {
        if (!value) return 'Chưa cập nhật';
        const date = new Date(normalizeDateInput(value));
        return Number.isNaN(date.getTime()) ? 'Chưa cập nhật' : date.toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' });
    };
    const formatTimelineDateTime = (value) => {
        if (!value) return 'Chưa cập nhật';
        const date = new Date(normalizeDateInput(value));
        if (Number.isNaN(date.getTime())) return 'Chưa cập nhật';
        return `${date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })}, ${date.toLocaleDateString('vi-VN')}`;
    };
    const getBookingCreatedTimeText = (booking) => formatTimelineDateTime(booking?.created_at);
    const getBookingCompletedTimeText = (booking) => booking?.thoi_gian_hoan_thanh
        ? formatTimelineDateTime(booking.thoi_gian_hoan_thanh)
        : 'Chưa hoàn thành';
    const formatMoney = (value) => `${Number(value || 0).toLocaleString('vi-VN')}đ`;
    const formatOrderCode = (id) => `#ORD-${String(id || 0).padStart(4, '0')}`;
    const getBookingRebookPayload = (booking) => {
        const services = Array.isArray(booking?.dich_vus) ? booking.dich_vus : [];
        const serviceIds = services
            .map((service) => Number(service?.id || 0))
            .filter((serviceId) => Number.isInteger(serviceId) && serviceId > 0);
        const firstServiceName = services[0]?.ten_dich_vu || '';
        const workerId = Number(booking?.tho?.id || booking?.tho_id || 0);

        return {
            workerId: workerId > 0 ? workerId : null,
            serviceIds,
            serviceName: firstServiceName,
        };
    };
    const openRebookBooking = (booking) => {
        const payload = getBookingRebookPayload(booking);

        if (window.BookingWizardModal?.open) {
            window.BookingWizardModal.open(payload);
            return;
        }

        const targetUrl = new URL('/customer/booking', window.location.origin);
        if (payload.workerId) {
            targetUrl.searchParams.set('worker_id', String(payload.workerId));
        }
        if (payload.serviceIds.length) {
            targetUrl.searchParams.set('service_ids', payload.serviceIds.join(','));
        } else if (payload.serviceName) {
            targetUrl.searchParams.set('service_name', payload.serviceName);
        }

        window.location.href = targetUrl.toString();
    };
    const maskPhone = (phone) => {
        const raw = String(phone || '').trim();
        const digits = raw.replace(/\D/g, '');
        if (!digits) return 'Chưa cập nhật';
        if (digits.length < 7) return raw;
        return `${digits.slice(0, 4)} *** ${digits.slice(-3)}`;
    };
    const getSortedItemsByCreatedAt = (items) => [...(Array.isArray(items) ? items : [])].sort((a, b) => new Date(b?.created_at || 0).getTime() - new Date(a?.created_at || 0).getTime());
    const getLatestReview = (booking) => getSortedItemsByCreatedAt(booking?.danh_gias)[0] || null;
    const getLatestPayment = (booking) => getSortedItemsByCreatedAt(booking?.thanh_toans)[0] || null;
    const getBookingPaymentMethod = (booking) => booking?.phuong_thuc_thanh_toan === 'transfer' ? 'transfer' : 'cod';
    const isCashPaymentBooking = (booking) => getBookingPaymentMethod(booking) === 'cod';
    const buildOnlineGatewayOptions = () => ({
        momo: 'Ví MoMo',
        zalopay: 'Ví ZaloPay',
        vnpay: 'VNPAY / Thẻ ngân hàng',
        ...(isLocalPaymentSandbox ? { test: 'Thanh toán test nội bộ' } : {}),
    });
    const normalizeLookupKey = (value = '') => String(value).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').replace(/[^a-z0-9()]+/g, ' ').replace(/\s+/g, ' ').trim();
    const beautifyServiceName = (value = '') => ({
        'sua dieu hoa (may lanh)': 'Sửa điều hòa (Máy lạnh)',
        'sua dieu hoa may lanh': 'Sửa điều hòa (Máy lạnh)',
        'sua dieu hoa': 'Sửa điều hòa',
        'sua may lanh': 'Sửa máy lạnh',
        'sua tu lanh': 'Sửa tủ lạnh',
        'sua may giat': 'Sửa máy giặt',
        'sua quat dien': 'Sửa quạt điện',
        'sua may nuoc nong': 'Sửa máy nước nóng',
        'sua lo vi song': 'Sửa lò vi sóng',
        'sua bep tu': 'Sửa bếp từ',
        'sua tivi': 'Sửa tivi',
        'dien nuoc dan dung': 'Điện nước dân dụng',
    }[normalizeLookupKey(value)] || value || 'Dịch vụ sửa chữa');
    const getServiceNames = (booking) => (Array.isArray(booking?.dich_vus) ? booking.dich_vus : []).map((service) => beautifyServiceName(service?.ten_dich_vu)).filter(Boolean);
    const getPrimaryServiceName = (booking) => getServiceNames(booking)[0] || 'Dịch vụ sửa chữa';
    const getStoredCostItems = (booking, key) => Array.isArray(booking?.[key]) ? booking[key].filter(Boolean) : [];
    const getBookingTotal = (booking) => {
        const total = Number(booking?.tong_tien || 0);
        if (total > 0) {
            return total;
        }

        const laborItems = getStoredCostItems(booking, 'chi_tiet_tien_cong');
        const partItems = getStoredCostItems(booking, 'chi_tiet_linh_kien');
        const laborTotal = laborItems.length
            ? laborItems.reduce((sum, item) => sum + Number(item?.so_tien || 0), 0)
            : Number(booking?.tien_cong || 0);
        const partTotal = partItems.length
            ? partItems.reduce((sum, item) => sum + Number(item?.so_tien || 0), 0)
            : Number(booking?.phi_linh_kien || 0);

        return Number(booking?.phi_di_lai || 0) + laborTotal + partTotal + Number(booking?.tien_thue_xe || 0);
    };
    const getLegacyFirstLine = (value = '', fallback = 'Linh kiện thay thế') => String(value || '').split(/\r\n|\r|\n/).map((line) => line.trim()).find(Boolean) || fallback;
    const getLaborItems = (booking) => {
        const items = getStoredCostItems(booking, 'chi_tiet_tien_cong');
        if (items.length) return items;
        return Number(booking?.tien_cong || 0) > 0
            ? [{ noi_dung: getPrimaryServiceName(booking), so_tien: Number(booking?.tien_cong || 0) }]
            : [];
    };
    const getPartItems = (booking) => {
        const items = getStoredCostItems(booking, 'chi_tiet_linh_kien');
        if (items.length) return items;
        return Number(booking?.phi_linh_kien || 0) > 0 || String(booking?.ghi_chu_linh_kien || '').trim() !== ''
            ? [{ noi_dung: getLegacyFirstLine(booking?.ghi_chu_linh_kien), don_gia: Number(booking?.phi_linh_kien || 0), so_luong: 1, so_tien: Number(booking?.phi_linh_kien || 0), bao_hanh_thang: null, bao_hanh_da_su_dung: false }]
            : [];
    };
    const getPartQuantity = (item) => {
        const quantity = Math.trunc(Number(item?.so_luong || 1));
        return Number.isFinite(quantity) && quantity > 0 ? quantity : 1;
    };
    const getPartUnitPrice = (item) => {
        const explicitUnitPrice = Number(item?.don_gia || 0);
        if (Number.isFinite(explicitUnitPrice) && explicitUnitPrice > 0) return explicitUnitPrice;

        const quantity = getPartQuantity(item);
        const total = Number(item?.so_tien || 0);
        return quantity > 0 ? total / quantity : total;
    };
    const getPartMetaText = (item, warrantyLabel = '') => {
        const quantity = getPartQuantity(item);
        const unitPrice = getPartUnitPrice(item);
        return [`SL ${quantity}`, unitPrice > 0 ? `${formatMoney(unitPrice)}/cái` : '', warrantyLabel].filter(Boolean).join(' • ');
    };
    const formatWarrantyText = (months) => {
        const value = Number(months);
        if (!Number.isFinite(value) || value <= 0) return 'Không ghi bảo hành';
        return `Bảo hành ${value} tháng`;
    };
    const parseDateValue = (value) => {
        if (!value) return null;
        const date = new Date(normalizeDateInput(value));
        return Number.isNaN(date.getTime()) ? null : date;
    };
    const addMonthsToDate = (value, months) => {
        const date = parseDateValue(value);
        const monthCount = Number(months);
        if (!date || !Number.isFinite(monthCount) || monthCount <= 0) return null;

        const result = new Date(date.getTime());
        const originalDay = result.getDate();
        result.setDate(1);
        result.setMonth(result.getMonth() + Math.trunc(monthCount));

        const lastDayOfTargetMonth = new Date(result.getFullYear(), result.getMonth() + 1, 0).getDate();
        result.setDate(Math.min(originalDay, lastDayOfTargetMonth));

        return result;
    };
    const formatWarrantyTimeLeft = (endDate, now = new Date()) => {
        const dayMs = 24 * 60 * 60 * 1000;
        const remainingDays = Math.max(0, Math.ceil((endDate.getTime() - now.getTime()) / dayMs));

        if (remainingDays <= 1) return 'còn 1 ngày';
        if (remainingDays < 30) return `còn ${remainingDays} ngày`;

        const months = Math.floor(remainingDays / 30);
        const days = remainingDays % 30;
        return days === 0 ? `còn ${months} tháng` : `còn ${months} tháng ${days} ngày`;
    };
    const formatSlotLabel = (slot = '') => String(slot || '').replace('-', ' - ');
    const getReschedulePolicy = (booking) => booking?.reschedule_policy || {};
    const getRescheduleSlots = (booking) => {
        const policySlots = getReschedulePolicy(booking)?.time_slots;
        return Array.isArray(policySlots) && policySlots.length ? policySlots : defaultRescheduleSlots;
    };
    const compareIsoDateStrings = (left = '', right = '') => String(left || '').localeCompare(String(right || ''));
    const canRescheduleBooking = (booking) => Boolean(getReschedulePolicy(booking)?.can_reschedule);
    const getRescheduleStatusText = (booking) => {
        const policy = getReschedulePolicy(booking);
        if (!policy || typeof policy !== 'object') return '';
        const windowDays = Number(policy.window_days || 7);
        if (!policy.status_allows_reschedule) return 'Chỉ được đổi lịch sau khi thợ đã xác nhận đơn.';
        if (Number(policy.remaining_changes || 0) <= 0) {
            return `Bạn đã dùng hết ${Number(policy.max_changes || 1)}/${Number(policy.max_changes || 1)} lần đổi lịch cho đơn này.`;
        }

        let rangeText = ` Lịch mới chỉ được đặt trong ${windowDays} ngày tới.`;
        if (policy.minimum_allowed_label && policy.maximum_allowed_label) {
            rangeText = ` Lịch mới phải từ ${policy.minimum_allowed_label} đến hết ngày ${policy.maximum_allowed_label}.`;
        } else if (policy.minimum_allowed_label) {
            rangeText = ` Lịch mới phải từ ${policy.minimum_allowed_label} trở đi.`;
        } else if (policy.maximum_allowed_label) {
            rangeText = ` Lịch mới chỉ được đổi đến hết ngày ${policy.maximum_allowed_label}.`;
        }

        return `Bạn còn ${Number(policy.remaining_changes || 0)}/${Number(policy.max_changes || 1)} lần đổi lịch.${rangeText}`;
    };
    const buildRescheduleNote = (booking) => {
        const message = getRescheduleStatusText(booking);
        return message
            ? `<div class="detail-summary-note detail-summary-note--info"><strong>Đổi lịch hẹn</strong>${escapeHtml(message)}</div>`
            : '';
    };
    const getRescheduleSlotOptions = (booking, selectedDate) => {
        const policy = getReschedulePolicy(booking);
        const minDate = String(policy.minimum_allowed_date || '');
        const maxDate = String(policy.maximum_allowed_date || '');
        const minSlot = String(policy.minimum_allowed_slot || '');
        const slots = getRescheduleSlots(booking);
        const minSlotIndex = slots.indexOf(minSlot);

        return slots.map((slot, index) => ({
            value: slot,
            disabled: !selectedDate
                || (minDate !== '' && compareIsoDateStrings(selectedDate, minDate) < 0)
                || (maxDate !== '' && compareIsoDateStrings(selectedDate, maxDate) > 0)
                || (selectedDate === minDate && minSlotIndex >= 0 && index < minSlotIndex),
        }));
    };
    const hasUsedWarranty = (item) => item?.bao_hanh_da_su_dung === true || item?.da_dung_bao_hanh === true || item?.used_warranty === true;
    const getWarrantyStatusMeta = (booking, item) => {
        const warrantyLabel = formatWarrantyText(item?.bao_hanh_thang);
        const warrantyMonths = Number(item?.bao_hanh_thang);
        const completedAt = parseDateValue(booking?.thoi_gian_hoan_thanh);
        const activatedAtLabel = completedAt ? formatDateTime(completedAt) : '';

        if (hasUsedWarranty(item)) {
            return {
                label: 'Hết bảo hành',
                detail: activatedAtLabel ? `Kích hoạt từ ${activatedAtLabel}. Linh kiện đã sử dụng quyền bảo hành.` : 'Linh kiện đã sử dụng quyền bảo hành.',
                tone: 'is-used',
                warrantyLabel,
            };
        }

        if (!Number.isFinite(warrantyMonths) || warrantyMonths <= 0) {
            return {
                label: 'Không ghi bảo hành',
                detail: 'Linh kiện này không có thời hạn bảo hành.',
                tone: 'is-neutral',
                warrantyLabel,
            };
        }

        if (!completedAt) {
            return {
                label: 'Chưa bắt đầu bảo hành',
                detail: 'Bảo hành được tính từ thời gian hoàn thành đơn.',
                tone: 'is-neutral',
                warrantyLabel,
            };
        }

        const warrantyEndDate = addMonthsToDate(completedAt, warrantyMonths);
        if (!warrantyEndDate) {
            return {
                label: 'Không ghi bảo hành',
                detail: 'Không xác định được thời hạn bảo hành.',
                tone: 'is-neutral',
                warrantyLabel,
            };
        }

        const now = new Date();
        if (now.getTime() <= warrantyEndDate.getTime()) {
            return {
                label: 'Còn bảo hành',
                detail: `Kích hoạt từ ${activatedAtLabel} • hiệu lực đến ${formatDate(warrantyEndDate)} • ${formatWarrantyTimeLeft(warrantyEndDate, now)}.`,
                tone: 'is-active',
                warrantyLabel,
            };
        }

        return {
            label: 'Hết hạn bảo hành',
            detail: `Kích hoạt từ ${activatedAtLabel} • đã hết hạn từ ${formatDate(warrantyEndDate)}.`,
            tone: 'is-expired',
            warrantyLabel,
        };
    };
    const canConfirmWarrantyFromDetail = (booking, item) => user?.role === 'admin' && getWarrantyStatusMeta(booking, item).tone === 'is-active';
    const buildCostLineItems = (items, type, emptyMessage, booking = null) => {
        if (!items.length) {
            return `<div class="detail-empty-note">${escapeHtml(emptyMessage)}</div>`;
        }

        return `<div class="detail-cost-item-list">${items.map((item) => `
            <div class="detail-cost-item-card">
                <div class="detail-cost-item-card__top">
                    <div class="detail-cost-item-card__copy">
                        <strong>${escapeHtml(item?.noi_dung || (type === 'part' ? 'Linh kiện' : 'Tiền công'))}</strong>
                        <small>${escapeHtml(type === 'part' ? getPartMetaText(item, formatWarrantyText(item?.bao_hanh_thang)) : 'Tiền công sửa chữa')}</small>
                    </div>
                    <span>${escapeHtml(formatMoney(item?.so_tien || 0))}</span>
                </div>
            </div>
        `).join('')}</div>`;
    };
    const buildDetailedCostLineItems = (items, type, emptyMessage, booking = null) => {
        if (!items.length) {
            return `<div class="detail-empty-note">${escapeHtml(emptyMessage)}</div>`;
        }

        return `<div class="detail-cost-item-list">${items.map((item) => {
            const warrantyMeta = type === 'part' ? getWarrantyStatusMeta(booking, item) : null;

            return `
            <div class="detail-cost-item-card">
                <div class="detail-cost-item-card__top">
                    <div class="detail-cost-item-card__copy">
                        <strong>${escapeHtml(item?.noi_dung || (type === 'part' ? 'Linh kiện' : 'Tiền công'))}</strong>
                        <small>${escapeHtml(type === 'part' ? getPartMetaText(item, warrantyMeta?.warrantyLabel) : 'Tiền công sửa chữa')}</small>
                        ${type === 'part' && warrantyMeta ? `<div class="detail-warranty-meta"><span class="detail-warranty-pill ${warrantyMeta.tone}">${escapeHtml(warrantyMeta.label)}</span><p>${escapeHtml(warrantyMeta.detail)}</p></div>` : ''}
                    </div>
                    <span>${escapeHtml(formatMoney(item?.so_tien || 0))}</span>
                </div>
            </div>
        `;
        }).join('')}</div>`;
    };
    const countFiles = (images, video) => (Array.isArray(images) ? images.filter(Boolean).length : 0) + (video ? 1 : 0);
    const getLocationText = (booking) => (booking?.loai_dat_lich === 'at_home' ? (booking?.dia_chi || 'Chưa cập nhật địa chỉ') : storeAddress);
    const getLocationModeLabel = (booking) => (booking?.loai_dat_lich === 'at_home' ? 'Dịch vụ tại nhà' : 'Tiếp nhận tại cửa hàng');
    const getDistanceText = (booking) => {
        if (booking?.loai_dat_lich !== 'at_home') return 'Khách mang thiết bị đến cửa hàng';
        const distance = Number(booking?.khoang_cach || 0);
        return distance > 0 ? `${distance.toFixed(1)} km từ điểm hỗ trợ` : 'Chưa cập nhật khoảng cách';
    };
    const formatPaymentMethod = (value) => ({ cod: 'Tiền mặt', cash: 'Tiền mặt', transfer: 'Chuyển khoản online', test: 'Thanh toán test nội bộ', vnpay: 'Ví VNPAY / Thẻ', momo: 'Ví MoMo', zalopay: 'Ví ZaloPay' }[value] || 'Phương thức thanh toán');
    const getStatusMeta = (status) => ({
        cho_xac_nhan: { label: 'Đang chờ tiếp nhận', summary: 'Hệ thống đang tìm kỹ thuật viên phù hợp cho yêu cầu của bạn.' },
        da_xac_nhan: { label: 'Đã nhận đơn', summary: 'Kỹ thuật viên đã xác nhận đơn và sẽ đến theo lịch hẹn.' },
        dang_lam: { label: 'Đang xử lý', summary: 'Thiết bị đang được kiểm tra và xử lý tại thời điểm hiện tại.' },
        cho_hoan_thanh: { label: 'Chờ thợ xác nhận COD', summary: 'Bạn thanh toán tiền mặt trực tiếp cho thợ. Đơn sẽ hoàn tất sau khi thợ xác nhận đã thu tiền.' },
        cho_thanh_toan: { label: 'Chờ thanh toán online', summary: 'Đơn đang chờ bạn chọn ví điện tử hoặc cổng thanh toán để hoàn tất.' },
        da_xong: { label: 'Hoàn tất', summary: 'Đơn hàng đã hoàn tất. Bạn có thể xem kết quả và gửi đánh giá.' },
        da_huy: { label: 'Đã hủy', summary: 'Yêu cầu này đã được hủy và không còn được xử lý.' },
    }[status] || { label: 'Đang cập nhật', summary: 'Thông tin đơn hàng đang được hệ thống cập nhật.' });
    const buildAvatar = (person) => person?.avatar
        ? `<img src="${escapeHtml(person.avatar)}" alt="${escapeHtml(person.name || 'Ảnh đại diện')}" class="detail-worker-avatar">`
        : `<div class="detail-worker-avatar detail-worker-avatar-fallback">${escapeHtml((person?.name || '?').charAt(0).toUpperCase())}</div>`;
    const buildStars = (rating) => `${'★'.repeat(Math.max(0, Math.min(5, Number(rating || 0))))}${'☆'.repeat(5 - Math.max(0, Math.min(5, Number(rating || 0))))}`;
    const buildMediaTile = (item) => {
        if (!item) return '<div class="detail-gallery-empty">Chưa có thêm file</div>';
        if (item.type === 'more') return `<div class="detail-gallery-more">+${item.count} Hình ảnh</div>`;
        if (item.type === 'video') {
            return `<div class="detail-gallery-tile"><video src="${escapeHtml(item.url)}" preload="metadata"></video><span class="detail-play-badge"><span class="material-symbols-outlined">play_arrow</span></span><a href="${escapeHtml(item.url)}" target="_blank" rel="noreferrer" class="detail-gallery-link" aria-label="Mở video"></a></div>`;
        }
        return `<div class="detail-gallery-tile"><img src="${escapeHtml(item.url)}" alt="${escapeHtml(item.alt || 'Hình ảnh minh họa')}" /><a href="${escapeHtml(item.url)}" target="_blank" rel="noreferrer" class="detail-gallery-link" aria-label="Mở hình ảnh"></a></div>`;
    };
    const buildGalleryGrid = (images, video, emptyMessage, contextLabel) => {
        const safeImages = Array.isArray(images) ? images.filter(Boolean) : [];
        const safeVideo = typeof video === 'string' && video.trim() ? video : '';
        if (!safeImages.length && !safeVideo) {
            return `<div class="detail-gallery-grid"><div class="detail-gallery-empty">${escapeHtml(emptyMessage)}</div><div class="detail-gallery-empty">${escapeHtml(emptyMessage)}</div><div class="detail-gallery-empty">${escapeHtml(emptyMessage)}</div><div class="detail-gallery-empty">${escapeHtml(emptyMessage)}</div></div>`;
        }
        const tiles = [];
        safeImages.slice(0, 2).forEach((url, index) => tiles.push({ type: 'image', url, alt: `${contextLabel} ${index + 1}` }));
        let consumedImages = Math.min(safeImages.length, 2);
        if (safeVideo) tiles.push({ type: 'video', url: safeVideo });
        else if (safeImages[consumedImages]) {
            tiles.push({ type: 'image', url: safeImages[consumedImages], alt: `${contextLabel} ${consumedImages + 1}` });
            consumedImages += 1;
        }
        const remaining = safeImages.length - consumedImages;
        if (remaining > 0) tiles.push({ type: 'more', count: remaining });
        while (tiles.length < 4) tiles.push(null);
        return `<div class="detail-gallery-grid">${tiles.slice(0, 4).map((item) => buildMediaTile(item)).join('')}</div>`;
    };
    const getSummarySchedule = (booking) => `${formatDate(booking?.ngay_hen)} • ${booking?.khung_gio_hen || 'Chưa chọn khung giờ'}`;
    const getStatusPillClass = (status) => {
        if (['cho_xac_nhan', 'da_xac_nhan', 'cho_hoan_thanh', 'cho_thanh_toan'].includes(status)) return 'detail-status-pill is-blue';
        if (status === 'da_xong') return 'detail-status-pill is-green';
        if (status === 'da_huy') return 'detail-status-pill is-red';
        return 'detail-status-pill';
    };
    const getPaymentBadgeMeta = (booking) => {
        const latestPayment = getLatestPayment(booking);
        const isPaid = booking?.trang_thai_thanh_toan || latestPayment?.trang_thai === 'success';
        const isPayable = ['cho_hoan_thanh', 'cho_thanh_toan'].includes(booking?.trang_thai);
        if (isPaid) return { label: 'ĐÃ THANH TOÁN', style: 'background: rgba(236, 253, 245, 0.95); color: #059669;' };
        if (booking?.trang_thai === 'da_huy') return { label: 'ĐÃ HỦY', style: 'background: rgba(254, 242, 242, 0.95); color: #dc2626;' };
        if (isPayable) {
            return { label: isCashPaymentBooking(booking) ? 'CHỜ XÁC NHẬN COD' : 'CHỜ THANH TOÁN ONLINE', style: '' };
        }
        return { label: 'THANH TOÁN SAU', style: 'background: rgba(241, 245, 249, 0.95); color: #475569;' };
    };
    const buildSummaryPrimaryActionV2 = (booking) => {
        const review = getLatestReview(booking);
        if (['cho_xac_nhan', 'da_xac_nhan'].includes(booking?.trang_thai)) {
            const actions = [];
            if (canRescheduleBooking(booking)) {
                actions.push('<div class="detail-summary-action"><button type="button" class="detail-outline-button" data-booking-action="reschedule">Đổi lịch hẹn</button></div>');
            }
            actions.push('<div class="detail-summary-action"><button type="button" class="detail-outline-button" data-booking-action="cancel">Hủy yêu cầu</button></div>');
            return `<div class="detail-summary-action-stack">${actions.join('')}</div>${buildRescheduleNote(booking)}`;
        }
        if (['cho_hoan_thanh', 'cho_thanh_toan'].includes(booking?.trang_thai)) {
            return '<div class="detail-summary-action"><button type="button" class="detail-outline-button" data-booking-action="pay">Chọn cách thanh toán</button></div>';
        }
        if (booking?.trang_thai === 'da_xong' && !review) return '<div class="detail-summary-action"><button type="button" class="detail-outline-button" data-booking-action="review">Gửi đánh giá</button></div>';
        if (booking?.trang_thai === 'da_huy') return '<div class="detail-summary-action"><button type="button" class="detail-outline-button" data-booking-action="rebook">Đặt lịch mới</button></div>';
        return '<div class="detail-summary-note"><strong>Cập nhật mới nhất</strong>Hệ thống sẽ tiếp tục cập nhật tiến độ xử lý tại các thẻ thông tin bên dưới.</div>';
    };
    const buildSummaryPrimaryAction = (booking) => {
        const review = getLatestReview(booking);
        if (['cho_xac_nhan', 'da_xac_nhan'].includes(booking?.trang_thai)) return '<div class="detail-summary-action"><button type="button" class="detail-outline-button" data-booking-action="cancel">Hủy yêu cầu</button></div>';
        if (['cho_hoan_thanh', 'cho_thanh_toan'].includes(booking?.trang_thai)) {
            return '<div class="detail-summary-action"><button type="button" class="detail-outline-button" data-booking-action="pay">Chọn cách thanh toán</button></div>';
        }
        if (booking?.trang_thai === 'da_xong' && !review) return '<div class="detail-summary-action"><button type="button" class="detail-outline-button" data-booking-action="review">Gửi đánh giá</button></div>';
        if (booking?.trang_thai === 'da_huy') return '<div class="detail-summary-action"><button type="button" class="detail-outline-button" data-booking-action="rebook">Đặt lịch mới</button></div>';
        return '<div class="detail-summary-note"><strong>Cập nhật mới nhất</strong>Hệ thống sẽ tiếp tục cập nhật tiến độ xử lý tại các thẻ thông tin bên dưới.</div>';
    };
    const buildSummaryCard = (booking) => `<section class="detail-card detail-summary-card"><div class="detail-summary-top"><span class="${getStatusPillClass(booking?.trang_thai)}">${escapeHtml(getStatusMeta(booking?.trang_thai).label)}</span><div class="detail-summary-order"><span>Mã đơn hàng</span><strong>${escapeHtml(formatOrderCode(booking?.id))}</strong></div></div><h1 class="detail-service-title">${escapeHtml(getPrimaryServiceName(booking))}</h1><div class="detail-summary-meta"><span class="material-symbols-outlined" style="font-size:1.15rem;">calendar_month</span><span>${escapeHtml(getSummarySchedule(booking))}</span></div><div class="detail-estimate-box"><span>Tổng phí dự kiến</span><strong>${escapeHtml(formatMoney(getBookingTotal(booking)))}</strong></div>${buildSummaryPrimaryActionV2(booking)}</section>`;
    const buildInitialMediaCard = (booking) => `<section class="detail-card"><div class="detail-card-head"><h2>Tình trạng ban đầu</h2><span>${countFiles(booking?.hinh_anh_mo_ta, booking?.video_mo_ta)} FILE</span></div>${buildGalleryGrid(booking?.hinh_anh_mo_ta, booking?.video_mo_ta, 'Chưa có thêm file', 'Tình trạng ban đầu')}</section>`;
    const buildWorkerCard = (booking) => {
        const worker = booking?.tho;
        const rating = Number(worker?.ho_so_tho?.danh_gia_trung_binh || 0);
        if (!worker?.name) return '<section class="detail-card"><div class="detail-card-head"><h2>Kỹ thuật viên</h2></div><div class="detail-empty-note">Hệ thống đang tìm kỹ thuật viên phù hợp cho đơn hàng này. Bạn sẽ nhận được cập nhật ngay khi có người nhận đơn.</div></section>';
        return `<section class="detail-card"><div class="detail-card-head"><h2>Kỹ thuật viên</h2></div><div class="detail-worker-row"><div class="detail-worker-avatar-wrap">${buildAvatar(worker)}<span class="detail-worker-verified"><span class="material-symbols-outlined" style="font-size:0.9rem;">verified</span></span></div><div class="detail-worker-copy"><strong>${escapeHtml(worker.name)}</strong><div class="detail-worker-phone"><span class="material-symbols-outlined" style="font-size:1rem;">call</span><span>${escapeHtml(maskPhone(worker.phone))}</span></div><a href="/customer/worker-profile/${escapeHtml(worker.id)}" class="detail-worker-link">Xem hồ sơ kỹ thuật viên<span class="material-symbols-outlined" style="font-size:1rem;">chevron_right</span></a></div>${rating > 0 ? `<span class="detail-rating-chip"><span class="material-symbols-outlined" style="font-size:0.95rem;">star</span>${escapeHtml(rating.toFixed(1))}</span>` : ''}</div></section>`;
    };
    const buildCostCard = (booking) => {
        const laborItems = getLaborItems(booking);
        const partItems = getPartItems(booking);
        const laborTotal = laborItems.length ? laborItems.reduce((sum, item) => sum + Number(item?.so_tien || 0), 0) : Number(booking?.tien_cong || 0);
        const partTotal = partItems.length ? partItems.reduce((sum, item) => sum + Number(item?.so_tien || 0), 0) : Number(booking?.phi_linh_kien || 0);
        const vehicleCost = Number(booking?.tien_thue_xe || 0);
        return `<section class="detail-card"><div class="detail-card-head"><h2>Chi tiết thanh toán</h2></div><div class="detail-cost-list"><div class="detail-cost-row"><span>Phí đi lại</span><strong>${escapeHtml(formatMoney(booking?.phi_di_lai || 0))}</strong></div><div class="detail-cost-row"><span>Tổng tiền công</span><strong>${escapeHtml(formatMoney(laborTotal))}</strong></div><div class="detail-cost-row"><span>Tổng linh kiện</span><strong>${escapeHtml(formatMoney(partTotal))}</strong></div><div class="detail-cost-row"><span>Phí vận chuyển</span><strong class="${vehicleCost > 0 ? '' : 'is-free'}">${vehicleCost > 0 ? escapeHtml(formatMoney(vehicleCost)) : 'Miễn phí'}</strong></div><div class="detail-cost-divider"></div><div class="detail-cost-total"><span>Tổng cộng</span><strong>${escapeHtml(formatMoney(getBookingTotal(booking)))}</strong></div></div><div class="detail-cost-section"><span class="detail-cost-section__label">Chi tiết tiền công</span>${buildCostLineItems(laborItems, 'labor', 'Chưa có dòng tiền công.')}</div><div class="detail-cost-section"><span class="detail-cost-section__label">Chi tiết linh kiện</span>${buildCostLineItems(partItems, 'part', 'Chưa có linh kiện phát sinh.')}</div></section>`;
    };
    const buildPaymentActionCard = (booking) => {
        const latestPayment = getLatestPayment(booking);
        const paymentMethod = formatPaymentMethod(latestPayment?.phuong_thuc || booking?.phuong_thuc_thanh_toan || 'transfer');
        const badge = getPaymentBadgeMeta(booking);
        const isPayable = ['cho_hoan_thanh', 'cho_thanh_toan'].includes(booking?.trang_thai);
        const isPaid = booking?.trang_thai_thanh_toan || latestPayment?.trang_thai === 'success';
        let description = 'Thông tin thanh toán sẽ mở sau khi xác nhận hoàn thành công việc.';
        if (isPayable) {
            description = isCashPaymentBooking(booking)
                ? 'Bạn thanh toán tiền mặt trực tiếp cho thợ. Đơn chỉ hoàn tất khi thợ xác nhận đã thu tiền.'
                : 'Bạn có thể đổi sang ví điện tử hoặc cổng thanh toán online. Đơn sẽ tự hoàn tất ngay khi giao dịch thành công.';
        }
        else if (isPaid) description = latestPayment?.ma_giao_dich ? `Mã giao dịch: ${latestPayment.ma_giao_dich}` : 'Đơn hàng đã được ghi nhận thanh toán thành công.';
        else if (booking?.trang_thai === 'da_huy') description = 'Đơn hàng đã hủy nên không còn chờ thanh toán.';
        return `<section class="detail-card detail-payment-action-card"><div class="detail-payment-action-head"><span class="detail-payment-icon"><span class="material-symbols-outlined">account_balance_wallet</span></span><div class="detail-payment-copy"><div class="detail-payment-title-row"><strong>${escapeHtml(paymentMethod)}</strong><span class="detail-payment-badge"${badge.style ? ` style="${badge.style}"` : ''}>${escapeHtml(badge.label)}</span></div><p>${escapeHtml(description)}</p></div></div><div class="detail-payment-action">${isPayable ? '<button type="button" class="detail-solid-button" data-booking-action="pay">Chọn cách thanh toán</button>' : '<a href="/customer/my-bookings" class="detail-ghost-button">Quay lại lịch sử</a>'}</div></section>`;
    };
    const buildProgressCard = (booking) => {
        const review = getLatestReview(booking);
        const latestPayment = getLatestPayment(booking);
        const worker = booking?.tho;
        const status = booking?.trang_thai;
        const accepted = Boolean(worker?.name) || ['da_xac_nhan', 'dang_lam', 'cho_hoan_thanh', 'cho_thanh_toan', 'da_xong'].includes(status);
        const isPaid = booking?.trang_thai_thanh_toan || latestPayment?.trang_thai === 'success';
        const paymentStepTime = ['cho_hoan_thanh', 'cho_thanh_toan'].includes(status) || isPaid
            ? formatTimelineDateTime(latestPayment?.created_at || booking?.updated_at)
            : '';
        const completionStepTime = status === 'da_xong'
            ? getBookingCompletedTimeText(booking)
            : (status === 'da_huy' ? formatTimelineDateTime(booking?.updated_at) : '');
        const payableDescription = isCashPaymentBooking(booking)
            ? 'Bạn thanh toán tiền mặt trực tiếp cho thợ. Đơn sẽ hoàn tất khi thợ xác nhận đã thu tiền.'
            : 'Bạn có thể chọn ví điện tử hoặc cổng thanh toán online để hoàn tất đơn.';
        const steps = [
            { icon: 'check', title: 'Đã tạo yêu cầu', time: getBookingCreatedTimeText(booking), description: '', stateClass: 'is-complete' },
            { icon: accepted ? 'check' : 'manage_accounts', title: accepted ? 'Đã nhận đơn' : 'Đang tìm kỹ thuật viên', time: accepted ? formatTimelineDateTime(booking?.updated_at) : '', description: accepted ? `${worker?.name || 'Kỹ thuật viên'} đã tiếp nhận đơn hàng của bạn.` : 'Hệ thống đang tìm kỹ thuật viên phù hợp.', stateClass: accepted ? 'is-complete' : (status === 'cho_xac_nhan' ? 'is-active' : 'is-disabled') },
            { icon: 'build', title: 'Đang xử lý', time: status === 'dang_lam' ? formatTimelineDateTime(booking?.updated_at) : '', description: status === 'dang_lam' ? 'Thợ đang kiểm tra thiết bị tại chỗ' : (['cho_hoan_thanh', 'cho_thanh_toan', 'da_xong'].includes(status) ? 'Bước xử lý kỹ thuật đã hoàn thành.' : 'Thiết bị sẽ được kiểm tra ngay khi kỹ thuật viên bắt đầu.'), stateClass: status === 'dang_lam' ? 'is-active' : (['cho_hoan_thanh', 'cho_thanh_toan', 'da_xong'].includes(status) ? 'is-complete' : 'is-disabled') },
            { icon: 'payments', title: 'Chờ thanh toán', time: paymentStepTime, description: isPaid ? 'Đơn hàng đã được xác nhận thanh toán.' : (['cho_hoan_thanh', 'cho_thanh_toan'].includes(status) ? payableDescription : 'Thông tin thanh toán sẽ mở sau khi hoàn tất xử lý.'), stateClass: isPaid ? 'is-complete' : (['cho_hoan_thanh', 'cho_thanh_toan'].includes(status) ? 'is-active' : 'is-disabled') },
            { icon: status === 'da_huy' ? 'cancel' : 'verified', title: status === 'da_huy' ? 'Đã hủy đơn' : 'Hoàn tất & Đánh giá', time: completionStepTime, description: status === 'da_huy' ? (booking?.ly_do_huy || 'Đơn hàng đã được hủy.') : (status === 'da_xong' ? (review ? 'Bạn đã hoàn tất và gửi đánh giá cho đơn hàng này.' : 'Bước cuối sẽ mở ngay sau khi đơn hàng được hoàn tất.') : 'Bước cuối sẽ mở sau khi đơn hàng được hoàn tất.'), stateClass: status === 'da_xong' ? (review ? 'is-complete' : 'is-active') : (status === 'da_huy' ? 'is-complete' : 'is-disabled') },
        ];
        return `<section class="detail-card"><div class="detail-card-head"><h2>Tiến độ dịch vụ</h2></div><div class="detail-progress-list">${steps.map((step) => `<div class="detail-progress-item ${step.stateClass}"><span class="detail-progress-icon"><span class="material-symbols-outlined" style="font-size:1rem;">${step.icon}</span></span><div class="detail-progress-copy"><strong>${escapeHtml(step.title)}</strong>${step.time ? `<time>${escapeHtml(step.time)}</time>` : ''}${step.description ? `<p>${escapeHtml(step.description)}</p>` : ''}</div></div>`).join('')}</div></section>`;
    };
    const buildRequestInfo = (icon, label, value, subValue = '', badge = '') => `<article class="detail-request-info"><span class="detail-request-icon"><span class="material-symbols-outlined">${icon}</span></span><div class="detail-request-copy"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value)}</strong>${subValue ? `<small>${escapeHtml(subValue)}</small>` : ''}${badge ? `<div class="detail-service-badge">${escapeHtml(badge)}</div>` : ''}</div></article>`;
    const buildRequestCardV2 = (booking) => {
        const problemText = booking?.mo_ta_van_de ? `"${booking.mo_ta_van_de}"` : 'Khách hàng chưa để lại mô tả chi tiết cho thiết bị này.';
        const rescheduleMeta = getReschedulePolicy(booking);
        const scheduleMeta = !rescheduleMeta.status_allows_reschedule
            ? `Chờ thợ xác nhận để mở ${Number(rescheduleMeta.max_changes || 1)}/${Number(rescheduleMeta.max_changes || 1)} lần đổi lịch trong ${Number(rescheduleMeta.window_days || 7)} ngày tới.`
            : Number(rescheduleMeta.remaining_changes || 0) > 0
                ? `Còn ${Number(rescheduleMeta.remaining_changes || 0)}/${Number(rescheduleMeta.max_changes || 1)} lần đổi lịch trong ${Number(rescheduleMeta.window_days || 7)} ngày tới.`
                : `Đã dùng hết ${Number(rescheduleMeta.max_changes || 1)}/${Number(rescheduleMeta.max_changes || 1)} lần đổi lịch.`;

        return `<section class="detail-card"><div class="detail-card-head"><h2>Thông tin chi tiết yêu cầu</h2></div><div class="detail-request-grid">${buildRequestInfo('location_on', 'Địa chỉ dịch vụ', getLocationText(booking), booking?.loai_dat_lich === 'at_home' ? getDistanceText(booking) : '', getLocationModeLabel(booking))}${buildRequestInfo('event_available', 'Lịch hẹn hiện tại', getSummarySchedule(booking), scheduleMeta)}${buildRequestInfo('schedule', 'Thời gian tạo yêu cầu', getBookingCreatedTimeText(booking), booking?.thoi_gian_hen ? 'Dự kiến xử lý theo lịch hẹn' : 'Dự kiến xử lý trong ngày')}${buildRequestInfo('task_alt', 'Thời gian hoàn thành', getBookingCompletedTimeText(booking), booking?.thoi_gian_hoan_thanh ? 'Đơn đã hoàn tất trên hệ thống' : 'Sẽ được ghi nhận khi đơn hoàn tất')}<div class="detail-problem-block"><div class="detail-request-info"><span class="detail-request-icon"><span class="material-symbols-outlined">description</span></span><div class="detail-request-copy"><span>Mô tả vấn đề</span><div class="detail-problem-box">${escapeHtml(problemText)}</div></div></div></div></div></section>`;
    };
    const buildRequestCard = (booking) => {
        const problemText = booking?.mo_ta_van_de ? `"${booking.mo_ta_van_de}"` : 'Khách hàng chưa để lại mô tả chi tiết cho thiết bị này.';
        return `<section class="detail-card"><div class="detail-card-head"><h2>Thông tin chi tiết yêu cầu</h2></div><div class="detail-request-grid">${buildRequestInfo('location_on', 'Địa chỉ dịch vụ', getLocationText(booking), booking?.loai_dat_lich === 'at_home' ? getDistanceText(booking) : '', getLocationModeLabel(booking))}${buildRequestInfo('schedule', 'Thời gian đặt', getBookingCreatedTimeText(booking), booking?.thoi_gian_hen ? 'Dự kiến xử lý theo lịch hẹn' : 'Dự kiến xử lý trong ngày')}${buildRequestInfo('task_alt', 'Thời gian hoàn thành', getBookingCompletedTimeText(booking), booking?.thoi_gian_hoan_thanh ? 'Đơn đã hoàn tất trên hệ thống' : 'Sẽ được ghi nhận khi đơn hoàn tất')}<div class="detail-problem-block"><div class="detail-request-info"><span class="detail-request-icon"><span class="material-symbols-outlined">description</span></span><div class="detail-request-copy"><span>Mô tả vấn đề</span><div class="detail-problem-box">${escapeHtml(problemText)}</div></div></div></div></div></section>`;
    };
    const buildResultMediaCard = (booking) => {
        const fileCount = countFiles(booking?.hinh_anh_ket_qua, booking?.video_ket_qua);
        return fileCount ? `<section class="detail-card"><div class="detail-card-head"><h2>Kết quả sau xử lý</h2><span>${fileCount} FILE</span></div>${buildGalleryGrid(booking?.hinh_anh_ket_qua, booking?.video_ket_qua, 'Chưa có thêm file', 'Kết quả sau xử lý')}</section>` : '';
    };
    const buildReviewCard = (booking) => {
        const review = getLatestReview(booking);
        if (!review && booking?.trang_thai !== 'da_xong') return '';
        return `<section class="detail-card"><div class="detail-card-head"><h2>Đánh giá của khách</h2><span>${escapeHtml(review ? `${review.so_sao}/5` : 'Chưa có')}</span></div><div class="detail-problem-box">${review ? `<div style="font-size:1.15rem; color:#f59e0b; font-weight:800; margin-bottom:0.55rem;">${escapeHtml(buildStars(review.so_sao))}</div>${escapeHtml(review.nhan_xet || 'Khách hàng đã gửi đánh giá nhưng chưa để lại nhận xét chi tiết.')}` : 'Đơn hàng đã hoàn tất nhưng khách hàng chưa gửi đánh giá.'}</div></section>`;
    };
    const buildCancellationCard = (booking) => booking?.trang_thai === 'da_huy' ? `<section class="detail-card"><div class="detail-card-head"><h2>Lý do hủy</h2></div><div class="detail-problem-box">${escapeHtml(booking?.ly_do_huy || 'Khách hàng chưa để lại lý do hủy đơn.')}</div></section>` : '';
    const hydrateWarrantyStatus = (booking) => {
        const partItems = getPartItems(booking);
        if (!partItems.length || !contentEl) return;

        const partSection = Array.from(contentEl.querySelectorAll('.detail-cost-section'))
            .find((section) => section.querySelector('.detail-cost-section__label')?.textContent?.toLowerCase().includes('linh'));

        if (!partSection) return;

        Array.from(partSection.querySelectorAll('.detail-cost-item-card')).forEach((card, index) => {
            const item = partItems[index];
            const copyBlock = card.querySelector('.detail-cost-item-card__copy') || card.querySelector('.detail-cost-item-card__top > div');
            if (!item || !copyBlock) return;

            const warrantyMeta = getWarrantyStatusMeta(booking, item);
            const metaText = copyBlock.querySelector('small');
            if (metaText) {
                metaText.textContent = warrantyMeta.warrantyLabel;
            }

            copyBlock.querySelector('.detail-warranty-meta')?.remove();
            copyBlock.insertAdjacentHTML('beforeend', `<div class="detail-warranty-meta"><span class="detail-warranty-pill ${warrantyMeta.tone}">${escapeHtml(warrantyMeta.label)}</span><p>${escapeHtml(warrantyMeta.detail)}</p>${canConfirmWarrantyFromDetail(booking, item) ? `<button type="button" class="detail-warranty-action" data-booking-action="confirm-warranty" data-part-index="${index}">Xác nhận đã bảo hành</button>` : ''}</div>`);
        });
    };
    const renderError = (message) => {
        loadingEl?.classList.add('d-none');
        contentEl?.classList.add('d-none');
        if (!errorEl) return;
        errorEl.classList.remove('d-none');
        errorEl.innerHTML = `<div class="detail-error-icon"><span class="material-symbols-outlined">error</span></div><h2>Không tải được chi tiết đơn hàng</h2><p>${escapeHtml(message || 'Đã có lỗi xảy ra khi tải dữ liệu đơn hàng. Vui lòng thử lại sau ít phút.')}</p><div style="max-width:22rem; margin:1.5rem auto 0; display:grid; gap:0.75rem;"><button type="button" class="detail-solid-button" id="btnRetryBookingDetail">Tải lại</button><a href="/customer/my-bookings" class="detail-ghost-button">Quay lại lịch sử</a></div>`;
        document.getElementById('btnRetryBookingDetail')?.addEventListener('click', () => loadBooking());
    };
    const renderBooking = (booking) => {
        currentBooking = booking;
        if (topbarOrderCodeEl) topbarOrderCodeEl.textContent = formatOrderCode(booking?.id);
        loadingEl?.classList.add('d-none');
        errorEl?.classList.add('d-none');
        contentEl?.classList.remove('d-none');
        contentEl.innerHTML = `<div class="detail-dashboard"><div class="detail-main-column">${buildInitialMediaCard(booking)}${buildProgressCard(booking)}${buildRequestCardV2(booking)}${buildResultMediaCard(booking)}${buildReviewCard(booking)}${buildCancellationCard(booking)}</div><aside class="detail-side-column">${buildSummaryCard(booking)}${buildWorkerCard(booking)}${buildCostCard(booking)}${buildPaymentActionCard(booking)}</aside></div>`;
        hydrateWarrantyStatus(booking);
        attachActionListeners();
    };
    const showCashPaymentInstructions = async () => {
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

        currentBooking = response.data?.booking || booking;
        showToast(response.data?.message || 'Đã cập nhật phương thức thanh toán.');

        return currentBooking;
    };
    const selectPendingPaymentMode = async (booking) => {
        const result = await Swal.fire({
            title: 'Chọn cách thanh toán',
            input: 'radio',
            inputOptions: {
                cod: 'Tiền mặt trực tiếp cho thợ',
                transfer: 'Chuyển khoản online / ví điện tử',
            },
            inputValue: getBookingPaymentMethod(booking),
            inputValidator: (value) => (!value ? 'Vui lòng chọn cách thanh toán.' : undefined),
            showCancelButton: true,
            confirmButtonText: 'Tiếp tục',
            cancelButtonText: 'Đóng',
        });

        return result.isConfirmed ? result.value : null;
    };
    const selectOnlineGateway = async () => {
        const gatewayOptions = buildOnlineGatewayOptions();
        const gatewayKeys = Object.keys(gatewayOptions);
        const result = await Swal.fire({
            title: 'Chọn ví điện tử',
            input: 'radio',
            inputOptions: gatewayOptions,
            inputValue: gatewayKeys[0] || 'momo',
            inputValidator: (value) => (!value ? 'Vui lòng chọn ví hoặc cổng thanh toán.' : undefined),
            showCancelButton: true,
            confirmButtonText: 'Mở thanh toán',
            cancelButtonText: 'Quay lại',
        });

        return result.isConfirmed ? result.value : null;
    };
    const startOnlinePayment = async (booking, gateway) => {
        const result = await Swal.fire({
            title: gateway === 'test' ? 'Thanh toán test nội bộ' : 'Chuyển sang cổng thanh toán',
            text: gateway === 'test'
                ? 'Đây là payment test nội bộ, không phát sinh chuyển khoản thật. Bạn muốn tiếp tục?'
                : 'Hệ thống sẽ chuyển bạn sang ví điện tử hoặc cổng thanh toán đã chọn để hoàn tất đơn hàng.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: gateway === 'test' ? 'Thanh toán test' : 'Tiếp tục',
            cancelButtonText: 'Đóng',
        });
        if (!result.isConfirmed) return;

        const response = ensureOk(
            await callApi('/payment/create', 'POST', {
                don_dat_lich_id: booking.id,
                phuong_thuc: gateway,
            }),
            gateway === 'test' ? 'Không tạo được giao dịch thanh toán test' : 'Không tạo được giao dịch thanh toán'
        );

        if (response.data?.url) {
            window.location.href = response.data.url;
            return;
        }

        showToast(response.data?.message || 'Thanh toán thành công.');
        await loadBooking();
    };
    const openPaymentAction = async (booking) => {
        if (getBookingPaymentMethod(booking) !== 'transfer') {
            await showCashPaymentInstructions();
            return;
        }

        const selectedGateway = await selectOnlineGateway();
        if (!selectedGateway) {
            return;
        }

        await startOnlinePayment(booking, selectedGateway);
    };
    const cancelBooking = async () => {
        if (!currentBooking) return;
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
        ensureOk(await callApi(`/don-dat-lich/${currentBooking.id}/status`, 'PUT', { trang_thai: 'da_huy', ma_ly_do_huy: result.value }), 'Không thể hủy đơn');
        showToast('Hủy đơn thành công');
        await loadBooking();
    };
    const openRescheduleModal = async () => {
        if (!currentBooking) return;

        const policy = getReschedulePolicy(currentBooking);
        if (!policy?.can_reschedule) {
            showToast(getRescheduleStatusText(currentBooking) || 'Đơn này hiện không thể đổi lịch.', 'error');
            return;
        }

        const minimumAllowedDate = String(policy.minimum_allowed_date || '');
        const maximumAllowedDate = String(policy.maximum_allowed_date || '');
        const currentBookingDate = String(currentBooking?.ngay_hen || '');
        const isCurrentDateWithinWindow = currentBookingDate !== ''
            && (minimumAllowedDate === '' || compareIsoDateStrings(currentBookingDate, minimumAllowedDate) >= 0)
            && (maximumAllowedDate === '' || compareIsoDateStrings(currentBookingDate, maximumAllowedDate) <= 0);
        const defaultDate = isCurrentDateWithinWindow
            ? currentBookingDate
            : (minimumAllowedDate || maximumAllowedDate || currentBookingDate);
        let selectedSlot = '';

        const result = await Swal.fire({
            title: 'Đổi lịch hẹn',
            html: `
                <div class="detail-reschedule-note">
                    <strong>Quy tắc đổi lịch</strong>
                    <span>${escapeHtml(getRescheduleStatusText(currentBooking))}</span>
                </div>
                <div class="detail-reschedule-field">
                    <label for="bookingDetailRescheduleDate">Ngày hẹn mới</label>
                    <input id="bookingDetailRescheduleDate" class="swal2-input detail-reschedule-date" type="date" min="${escapeHtml(minimumAllowedDate)}" ${maximumAllowedDate ? `max="${escapeHtml(maximumAllowedDate)}"` : ''} value="${escapeHtml(defaultDate)}">
                </div>
                <div class="detail-reschedule-field">
                    <label>Khung giờ cố định của cửa hàng</label>
                    <div class="detail-reschedule-slot-grid" id="bookingDetailRescheduleSlots"></div>
                </div>
                <div class="detail-reschedule-current">Lịch hiện tại: <strong>${escapeHtml(policy.current_schedule_label || getSummarySchedule(currentBooking))}</strong></div>
            `,
            customClass: {
                popup: 'detail-reschedule-modal',
            },
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Cập nhật lịch hẹn',
            cancelButtonText: 'Đóng',
            didOpen: () => {
                const popup = Swal.getPopup();
                const dateInput = popup?.querySelector('#bookingDetailRescheduleDate');
                const slotsWrap = popup?.querySelector('#bookingDetailRescheduleSlots');

                const renderSlots = () => {
                    const chosenDate = dateInput?.value || String(policy.minimum_allowed_date || '');
                    const slotOptions = getRescheduleSlotOptions(currentBooking, chosenDate);
                    const availableSlot = slotOptions.find((slot) => !slot.disabled)?.value || '';
                    if (!slotOptions.some((slot) => slot.value === selectedSlot && !slot.disabled)) {
                        selectedSlot = availableSlot;
                    }

                    if (!slotsWrap) return;

                    slotsWrap.innerHTML = slotOptions.map((slot) => `
                        <button
                            type="button"
                            class="detail-reschedule-slot ${slot.value === selectedSlot ? 'is-selected' : ''}"
                            data-slot-value="${slot.value}"
                            ${slot.disabled ? 'disabled' : ''}
                        >${escapeHtml(formatSlotLabel(slot.value))}</button>
                    `).join('');

                    slotsWrap.querySelectorAll('[data-slot-value]').forEach((button) => {
                        button.addEventListener('click', () => {
                            if (button.disabled) return;
                            selectedSlot = button.getAttribute('data-slot-value') || '';
                            renderSlots();
                        });
                    });
                };

                dateInput?.addEventListener('change', renderSlots);
                renderSlots();
            },
            preConfirm: () => {
                const popup = Swal.getPopup();
                const dateInput = popup?.querySelector('#bookingDetailRescheduleDate');
                const ngayHen = dateInput?.value || '';

                if (!ngayHen) {
                    Swal.showValidationMessage('Vui lòng chọn ngày hẹn mới.');
                    return false;
                }

                if (minimumAllowedDate && compareIsoDateStrings(ngayHen, minimumAllowedDate) < 0) {
                    Swal.showValidationMessage(`Lịch mới phải từ ${policy.minimum_allowed_label || minimumAllowedDate} trở đi.`);
                    return false;
                }

                if (maximumAllowedDate && compareIsoDateStrings(ngayHen, maximumAllowedDate) > 0) {
                    Swal.showValidationMessage(`Lịch mới chỉ được đổi đến hết ngày ${policy.maximum_allowed_label || maximumAllowedDate}.`);
                    return false;
                }

                if (!selectedSlot) {
                    Swal.showValidationMessage('Vui lòng chọn khung giờ hợp lệ.');
                    return false;
                }

                if (
                    String(currentBooking?.ngay_hen || '') === ngayHen
                    && String(currentBooking?.khung_gio_hen || '') === selectedSlot
                ) {
                    Swal.showValidationMessage('Lịch mới phải khác lịch hiện tại.');
                    return false;
                }

                return {
                    ngay_hen: ngayHen,
                    khung_gio_hen: selectedSlot,
                };
            },
        });

        if (!result.isConfirmed || !result.value) return;

        const response = ensureOk(
            await callApi(`/don-dat-lich/${currentBooking.id}/reschedule`, 'PUT', result.value),
            'Không thể cập nhật lịch hẹn mới'
        );
        showToast(response.data?.message || 'Đã cập nhật lịch hẹn thành công.');
        await loadBooking();
    };
    const openReviewModal = () => {
        if (!currentBooking) return;
        reviewForm?.reset();
        if (reviewBookingId) reviewBookingId.value = currentBooking.id;
        if (reviewWorkerName) reviewWorkerName.innerHTML = `Hãy cho chúng tôi biết cảm nhận của bạn về <strong>${escapeHtml(currentBooking.tho?.name || 'kỹ thuật viên')}</strong>.`;
        if (reviewComment) reviewComment.value = '';
        reviewModalInstance?.show();
    };
    const confirmWarrantyUsage = async (partIndex) => {
        if (!currentBooking) return;

        const partItem = getPartItems(currentBooking)[partIndex];
        if (!partItem) {
            showToast('Không tìm thấy linh kiện cần xác nhận bảo hành.', 'error');
            return;
        }

        const result = await Swal.fire({
            title: 'Xác nhận bảo hành',
            text: `Xác nhận linh kiện "${partItem.noi_dung || 'Linh kiện'}" đã sử dụng bảo hành?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Xác nhận',
            cancelButtonText: 'Đóng',
        });
        if (!result.isConfirmed) return;

        const response = ensureOk(
            await callApi(`/don-dat-lich/${currentBooking.id}/parts/${partIndex}/confirm-warranty`, 'POST'),
            'Không thể xác nhận bảo hành'
        );
        showToast(response.data?.message || 'Đã xác nhận sử dụng bảo hành.');
        await loadBooking();
    };
    const attachActionListeners = () => {
        document.querySelectorAll('[data-booking-action="reschedule"]').forEach((button) => button.addEventListener('click', async (event) => {
            const target = event.currentTarget;
            try {
                target.disabled = true;
                await openRescheduleModal();
            } catch (error) {
                showToast(error.message || 'Không thể đổi lịch hẹn lúc này', 'error');
            } finally {
                target.disabled = false;
            }
        }));
        document.querySelectorAll('[data-booking-action="cancel"]').forEach((button) => button.addEventListener('click', async () => {
            try {
                await cancelBooking();
            } catch (error) {
                showToast(error.message || 'Không thể hủy đơn', 'error');
            }
        }));
        document.querySelectorAll('[data-booking-action="pay"]').forEach((button) => button.addEventListener('click', async (event) => {
            const target = event.currentTarget;
            try {
                target.disabled = true;
                await openPaymentAction(currentBooking);
            } catch (error) {
                showToast(error.message || 'Không thể thanh toán đơn này', 'error');
            } finally {
                target.disabled = false;
            }
        }));
        document.querySelectorAll('[data-booking-action="review"]').forEach((button) => button.addEventListener('click', () => openReviewModal()));
        document.querySelectorAll('[data-booking-action="rebook"]').forEach((button) => button.addEventListener('click', () => {
            if (!currentBooking) {
                showToast('Không tìm thấy đơn để đặt lại.', 'error');
                return;
            }

            openRebookBooking(currentBooking);
        }));
        document.querySelectorAll('[data-booking-action="confirm-warranty"]').forEach((button) => button.addEventListener('click', async (event) => {
            const target = event.currentTarget;
            try {
                target.disabled = true;
                await confirmWarrantyUsage(Number(target.dataset.partIndex || -1));
            } catch (error) {
                showToast(error.message || 'Không thể xác nhận bảo hành', 'error');
            } finally {
                target.disabled = false;
            }
        }));
    };
    const loadBooking = async () => {
        if (!bookingId) {
            renderError('Mã đơn hàng không hợp lệ.');
            return;
        }
        loadingEl?.classList.remove('d-none');
        errorEl?.classList.add('d-none');
        contentEl?.classList.add('d-none');
        try {
            const response = ensureOk(await callApi(`/don-dat-lich/${bookingId}`, 'GET'), 'Không tải được chi tiết đơn hàng');
            renderBooking(response.data);
        } catch (error) {
            console.error('Error loading booking detail:', error);
            renderError(error.message || 'Không tải được chi tiết đơn hàng');
        }
    };

    if (reviewForm) {
        reviewForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const selectedRating = document.querySelector('input[name="so_sao"]:checked');
            if (!selectedRating) {
                showToast('Vui lòng chọn số sao đánh giá', 'error');
                return;
            }
            reviewSubmitButton.disabled = true;
            reviewSubmitButton.textContent = 'Đang gửi...';
            try {
                ensureOk(await callApi('/danh-gia', 'POST', { don_dat_lich_id: reviewBookingId.value, so_sao: selectedRating.value, nhan_xet: reviewComment.value }), 'Không gửi được đánh giá');
                showToast('Cảm ơn bạn đã gửi đánh giá');
                reviewModalInstance?.hide();
                await loadBooking();
            } catch (error) {
                showToast(error.message || 'Không gửi được đánh giá', 'error');
            } finally {
                reviewSubmitButton.disabled = false;
                reviewSubmitButton.textContent = 'Gửi đánh giá';
            }
        });
    }

    loadBooking();
});
