import { callApi, showToast } from '../api.js';

const root = document.querySelector('.part-detail-page');
const partId = root?.dataset.partId || '';
const loadingEl = document.getElementById('partDetailLoading');
const errorEl = document.getElementById('partDetailError');
const contentEl = document.getElementById('partDetailContent');

const currencyFormatter = new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
});

const accents = ['#d9f99d', '#bae6fd', '#fcd34d', '#fecdd3', '#c7d2fe', '#99f6e4'];

const escapeHtml = (value = '') => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
}[char]));

const getNumeric = (value) => Number(value || 0);

const getFallbackLetters = (value = '') => String(value)
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word[0])
    .join('')
    .toUpperCase();

const getPriceLabel = (part) => {
    const price = getNumeric(part?.gia);
    return price > 0 ? currencyFormatter.format(price) : 'Liên hệ báo giá';
};

const renderError = (message) => {
    loadingEl.hidden = true;
    errorEl.hidden = false;
    errorEl.innerHTML = `
        <div class="part-detail-state">
            <span class="material-symbols-outlined">error</span>
            <h2>Không tải được chi tiết linh kiện</h2>
            <p>${escapeHtml(message || 'Đã có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau.')}</p>
        </div>
    `;
};

const renderRelated = (items = []) => {
    if (!items.length) {
        return '';
    }

    return `
        <section class="part-detail-related">
            <div class="part-detail-related__head">
                <div>
                    <span class="part-detail-visual__eyebrow" style="color: var(--part-detail-cyan);">Cùng nhóm dịch vụ</span>
                    <h2 class="part-detail-related__title">Linh kiện liên quan</h2>
                </div>
                <p class="part-detail-related__note">Xem nhanh các linh kiện khác trong cùng nhóm để đối chiếu giá trước khi đặt lịch.</p>
            </div>
            <div class="part-detail-related__grid">
                ${items.map((item, index) => `
                    <a class="part-detail-related__item" href="/customer/linh-kien/${encodeURIComponent(item.id)}">
                        <div class="part-detail-related__thumb" style="--part-related-thumb:${escapeHtml(accents[index % accents.length])};">
                            ${item.hinh_anh
                                ? `<img src="${escapeHtml(item.hinh_anh)}" alt="${escapeHtml(item.ten_linh_kien)}">`
                                : `<span>${escapeHtml(getFallbackLetters(item.ten_linh_kien || item?.dich_vu?.ten_dich_vu || 'LK'))}</span>`}
                        </div>
                        <div class="part-detail-related__name">${escapeHtml(item.ten_linh_kien || 'Linh kiện')}</div>
                        <div class="part-detail-related__price">${escapeHtml(getPriceLabel(item))}</div>
                    </a>
                `).join('')}
            </div>
        </section>
    `;
};

const renderContent = (payload) => {
    const part = payload?.data;
    const related = Array.isArray(payload?.related) ? payload.related : [];
    const serviceName = part?.dich_vu?.ten_dich_vu || 'Linh kiện';
    const serviceDescription = part?.dich_vu?.mo_ta || 'Giá trên trang là giá tham khảo cho riêng linh kiện. Khi kỹ thuật viên kiểm tra thực tế, báo giá có thể thay đổi theo model và tình trạng máy.';
    const priceValue = getNumeric(part?.gia);

    loadingEl.hidden = true;
    errorEl.hidden = true;
    contentEl.hidden = false;
    contentEl.className = 'part-detail-content';
    contentEl.innerHTML = `
        <section class="part-detail-hero">
            <div class="part-detail-visual">
                <span class="part-detail-visual__eyebrow">Linh kiện tham khảo</span>
                <div class="part-detail-visual__thumb" style="--part-thumb:${escapeHtml(accents[getNumeric(part?.id) % accents.length])};">
                    ${part?.hinh_anh
                        ? `<img src="${escapeHtml(part.hinh_anh)}" alt="${escapeHtml(part.ten_linh_kien)}">`
                        : `<span>${escapeHtml(getFallbackLetters(part?.ten_linh_kien || serviceName))}</span>`}
                </div>
                <div class="part-detail-visual__meta">
                    <span class="part-detail-visual__chip">${escapeHtml(serviceName)}</span>
                    <span class="part-detail-visual__chip">${escapeHtml(priceValue > 0 ? 'Đã có giá tham khảo' : 'Chưa niêm yết giá')}</span>
                </div>
            </div>

            <div class="part-detail-panel">
                <a class="part-detail-breadcrumb" href="/customer/linh-kien">
                    <span class="material-symbols-outlined">west</span>
                    Quay lại catalog linh kiện
                </a>

                <span class="part-detail-service">${escapeHtml(serviceName)}</span>
                <h1 class="part-detail-title">${escapeHtml(part?.ten_linh_kien || 'Linh kiện')}</h1>
                <p class="part-detail-description">${escapeHtml(serviceDescription)}</p>

                <div class="part-detail-price-row">
                    <div class="part-detail-price ${priceValue > 0 ? '' : 'is-contact'}">${escapeHtml(getPriceLabel(part))}</div>
                    <div class="part-detail-price-note">
                        Giá chỉ mang tính tham khảo cho phần linh kiện.
                        Công thợ, kiểm tra, thay thế và phát sinh thực tế sẽ được báo riêng khi tiếp nhận.
                    </div>
                </div>

                <div class="part-detail-actions">
                    <a class="part-detail-primary" href="/customer/booking?dich_vu_id=${encodeURIComponent(part?.dich_vu_id || '')}">
                        <span class="material-symbols-outlined">build_circle</span>
                        Đặt lịch theo nhóm này
                    </a>
                    <a class="part-detail-secondary" href="/customer/search">
                        Tìm thợ phù hợp
                    </a>
                </div>
            </div>
        </section>

        <section class="part-detail-grid">
            <article class="part-detail-info">
                <span class="part-detail-info__label">Nhóm dịch vụ</span>
                <strong class="part-detail-info__value">${escapeHtml(serviceName)}</strong>
            </article>
            <article class="part-detail-info">
                <span class="part-detail-info__label">Tổng linh kiện cùng nhóm</span>
                <strong class="part-detail-info__value">${escapeHtml(String(payload?.service_part_count || 0))} mục</strong>
            </article>
            <article class="part-detail-info">
                <span class="part-detail-info__label">Tình trạng giá</span>
                <strong class="part-detail-info__value">${escapeHtml(priceValue > 0 ? 'Đã có giá tham khảo' : 'Báo theo tình trạng thực tế')}</strong>
            </article>
        </section>

        ${renderRelated(related)}
    `;
};

const loadPartDetail = async () => {
    if (!partId) {
        renderError('Không xác định được mã linh kiện.');
        return;
    }

    try {
        const response = await callApi(`/linh-kien/${partId}`, 'GET');

        if (!response.ok) {
            throw new Error(response.data?.message || 'Không thể tải chi tiết linh kiện.');
        }

        renderContent(response.data || {});
    } catch (error) {
        renderError(error.message);
        showToast(error.message || 'Không tải được chi tiết linh kiện.', 'error');
    }
};

loadPartDetail();
