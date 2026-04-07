import { callApi, showToast } from '../api.js';

const state = {
    parts: [],
    services: [],
    query: '',
    serviceId: 'all',
    sort: 'featured',
    pricedOnly: false,
    currentPage: 1,
    pageSize: 6,
};

const els = {
    list: document.getElementById('partsList'),
    pagination: document.getElementById('partsPagination'),
    loading: document.getElementById('partsLoadingState'),
    empty: document.getElementById('partsEmptyState'),
    summary: document.getElementById('partsResultSummary'),
    filters: document.getElementById('partsServiceFilters'),
    searchInput: document.getElementById('partsSearchInput'),
    searchForm: document.getElementById('partsSearchForm'),
    sortSelect: document.getElementById('partsSortSelect'),
    pricedToggle: document.getElementById('partsPricedToggle'),
};

const fallbackAssets = {
    compressor: '/assets/images/customer/parts/compressor.png',
    microchip: '/assets/images/customer/parts/microchip.png',
    gear: '/assets/images/customer/parts/gear.png',
    motor: '/assets/images/customer/parts/motor.png',
    machinery: '/assets/images/customer/parts/machinery.png',
};

const fallbackCycle = [
    fallbackAssets.compressor,
    fallbackAssets.microchip,
    fallbackAssets.gear,
    fallbackAssets.motor,
];

const currencyFormatter = new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
});

const escapeHtml = (value = '') => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
}[char]));

const getNumeric = (value) => Number(value || 0);

const getPartPriceLabel = (part) => {
    const price = getNumeric(part?.gia);
    return price > 0 ? currencyFormatter.format(price) : 'Liên hệ báo giá';
};

const getFallbackImage = (part, index) => {
    const haystack = `${part?.ten_linh_kien || ''} ${part?.serviceName || ''}`.toLocaleLowerCase('vi-VN');

    if (/bo|mạch|chip|sensor|điều khiển|điện tử/.test(haystack)) {
        return fallbackAssets.microchip;
    }

    if (/motor|quạt|fan/.test(haystack)) {
        return fallbackAssets.motor;
    }

    if (/bánh răng|truyền động|cơ khí|gear/.test(haystack)) {
        return fallbackAssets.gear;
    }

    if (/block|máy lạnh|compressor|điện lạnh/.test(haystack)) {
        return fallbackAssets.compressor;
    }

    return fallbackCycle[index % fallbackCycle.length];
};

const decoratePart = (part, index) => ({
    ...part,
    serviceName: part?.dich_vu?.ten_dich_vu || part?.serviceName || 'Linh kiện',
    priceValue: getNumeric(part?.gia),
    imageUrl: part?.hinh_anh || getFallbackImage(part, index),
});

const getPartsByService = (serviceId) => state.parts.filter((part) =>
    String(part.dich_vu_id || '') === String(serviceId) && part.priceValue > 0
);

const getServiceBenchmark = (serviceId) => {
    const pricedParts = getPartsByService(serviceId)
        .map((part) => part.priceValue)
        .sort((a, b) => a - b);

    if (!pricedParts.length) {
        return 0;
    }

    const middle = Math.floor(pricedParts.length / 2);
    if (pricedParts.length % 2 === 0) {
        return (pricedParts[middle - 1] + pricedParts[middle]) / 2;
    }

    return pricedParts[middle];
};

const getTrendMeta = (part) => {
    const price = part.priceValue;
    const benchmark = getServiceBenchmark(part.dich_vu_id);

    if (price <= 0 || benchmark <= 0) {
        return null;
    }

    const ratio = price / benchmark;

    if (ratio <= 0.92) {
        return {
            tone: 'is-decrease',
            label: 'Giá giảm',
        };
    }

    if (ratio >= 1.08) {
        return {
            tone: 'is-increase',
            label: 'Giá tăng',
        };
    }

    return {
        tone: 'is-stable',
        label: 'Giá ổn định',
    };
};

const getValueBadge = (part) => {
    const price = part.priceValue;
    const serviceParts = getPartsByService(part.dich_vu_id)
        .sort((a, b) => a.priceValue - b.priceValue);

    if (price <= 0 || serviceParts.length < 3) {
        return null;
    }

    const thresholdIndex = Math.max(0, Math.floor(serviceParts.length * 0.3) - 1);
    const thresholdPrice = serviceParts[thresholdIndex]?.priceValue ?? serviceParts[0]?.priceValue ?? 0;

    if (price <= thresholdPrice) {
        return {
            tone: 'is-good',
            label: 'Giá tốt',
        };
    }

    return null;
};

const getPrimaryBadge = (part) => getValueBadge(part) || getTrendMeta(part);

const buildFilterItems = () => {
    const counts = state.parts.reduce((map, part) => {
        const serviceId = String(part.dich_vu_id || 'unknown');
        map.set(serviceId, (map.get(serviceId) || 0) + 1);
        return map;
    }, new Map());

    return [
        { id: 'all', label: 'Tất cả danh mục', count: state.parts.length },
        ...state.services.map((service) => ({
            id: String(service.id),
            label: service.ten_dich_vu,
            count: counts.get(String(service.id)) || 0,
        })).filter((service) => service.count > 0),
    ];
};

const getFilteredParts = () => {
    const keyword = state.query.trim().toLocaleLowerCase('vi-VN');

    const filtered = state.parts.filter((part) => {
        const matchesService = state.serviceId === 'all' || String(part.dich_vu_id) === String(state.serviceId);
        const matchesPrice = !state.pricedOnly || part.priceValue > 0;
        const haystack = `${part.ten_linh_kien || ''} ${part.serviceName || ''}`.toLocaleLowerCase('vi-VN');
        const matchesQuery = keyword === '' || haystack.includes(keyword);

        return matchesService && matchesPrice && matchesQuery;
    });

    return filtered.sort((a, b) => {
        if (state.sort === 'price_asc') {
            return (a.priceValue || Number.MAX_SAFE_INTEGER) - (b.priceValue || Number.MAX_SAFE_INTEGER);
        }

        if (state.sort === 'price_desc') {
            return (b.priceValue || 0) - (a.priceValue || 0);
        }

        if (state.sort === 'name_asc') {
            return String(a.ten_linh_kien || '').localeCompare(String(b.ten_linh_kien || ''), 'vi');
        }

        if (a.priceValue > 0 && b.priceValue <= 0) return -1;
        if (a.priceValue <= 0 && b.priceValue > 0) return 1;
        return String(a.ten_linh_kien || '').localeCompare(String(b.ten_linh_kien || ''), 'vi');
    });
};

const getPageCount = (count) => Math.max(1, Math.ceil(count / state.pageSize));

const clampCurrentPage = (totalItems) => {
    state.currentPage = Math.min(state.currentPage, getPageCount(totalItems));
    state.currentPage = Math.max(1, state.currentPage);
};

const getPaginationItems = (totalPages) => {
    if (totalPages <= 5) {
        return Array.from({ length: totalPages }, (_, index) => index + 1);
    }

    if (state.currentPage <= 3) {
        return [1, 2, 3, 'ellipsis', totalPages];
    }

    if (state.currentPage >= totalPages - 2) {
        return [1, 'ellipsis', totalPages - 2, totalPages - 1, totalPages];
    }

    return [1, 'ellipsis', state.currentPage - 1, state.currentPage, state.currentPage + 1, 'ellipsis', totalPages];
};

const renderPagination = (totalItems) => {
    if (!els.pagination) {
        return;
    }

    const totalPages = getPageCount(totalItems);

    if (totalPages <= 1) {
        els.pagination.hidden = true;
        els.pagination.innerHTML = '';
        return;
    }

    const items = getPaginationItems(totalPages);

    els.pagination.hidden = false;
    els.pagination.innerHTML = `
        <button class="parts-pagination__button" data-page-action="prev" ${state.currentPage === 1 ? 'disabled' : ''} aria-label="Trang trước">
            <span class="material-symbols-outlined">chevron_left</span>
        </button>
        ${items.map((item) => {
            if (item === 'ellipsis') {
                return '<span class="parts-pagination__ellipsis">...</span>';
            }

            return `
                <button
                    class="parts-pagination__button ${item === state.currentPage ? 'is-active' : ''}"
                    data-page="${item}"
                    aria-label="Trang ${item}"
                >
                    ${item}
                </button>
            `;
        }).join('')}
        <button class="parts-pagination__button" data-page-action="next" ${state.currentPage === totalPages ? 'disabled' : ''} aria-label="Trang sau">
            <span class="material-symbols-outlined">chevron_right</span>
        </button>
    `;
};

const renderFilters = () => {
    if (!els.filters) {
        return;
    }

    const items = buildFilterItems();

    els.filters.innerHTML = items.map((item) => `
        <option
            value="${escapeHtml(item.id)}"
            ${String(item.id) === String(state.serviceId) ? 'selected' : ''}
        >
            ${escapeHtml(item.label)}
        </option>
    `).join('');
};

const buildBadgeMarkup = (part) => {
    const badge = getPrimaryBadge(part);

    if (!badge) {
        return '';
    }

    return `<span class="parts-card__badge ${escapeHtml(badge.tone)}">${escapeHtml(badge.label)}</span>`;
};

const buildPartCardMarkup = (part) => `
    <article class="parts-card">
        <div class="parts-card__media">
            ${buildBadgeMarkup(part)}
            <img src="${escapeHtml(part.imageUrl)}" alt="${escapeHtml(part.ten_linh_kien || 'Linh kiện')}">
        </div>
        <div class="parts-card__content">
            <div class="parts-card__text">
                <div class="parts-card__service">${escapeHtml(part.serviceName)}</div>
                <h3 class="parts-card__name">${escapeHtml(part.ten_linh_kien || 'Linh kiện')}</h3>
                <p class="parts-card__description">
                    ${escapeHtml(
                        part.mo_ta
                        || part.ghi_chu
                        || 'Linh kiện kỹ thuật chính hãng với thông số rõ ràng, hỗ trợ lắp đặt và kiểm tra tại nơi.'
                    )}
                </p>
            </div>
            <div class="parts-card__footer">
                <div class="parts-card__price ${part.priceValue > 0 ? '' : 'is-contact'}">${escapeHtml(getPartPriceLabel(part))}</div>
                <div class="parts-card__actions">
                    <a class="parts-card__link" href="/customer/linh-kien/${encodeURIComponent(part.id)}">Xem chi tiết</a>
                    <a class="parts-card__action" href="/customer/booking?dich_vu_id=${encodeURIComponent(part.dich_vu_id || '')}">Đặt thợ ngay</a>
                </div>
            </div>
        </div>
    </article>
`;

const buildFeatureCardMarkup = () => `
    <article class="parts-feature-card">
        <div class="parts-feature-card__body">
            <span class="parts-feature-card__eyebrow">Ưu đãi kỹ thuật</span>
            <h2 class="parts-feature-card__title">Gói linh kiện bảo trì định kỳ hệ thống chiller</h2>
            <p class="parts-feature-card__description">
                Tiết kiệm 15% khi mua trọn bộ linh kiện bảo trì. Bao gồm lọc gas, dầu lạnh và cảm biến áp suất.
            </p>
            <a class="parts-feature-card__action" href="/customer/booking">Nhận báo giá ngay</a>
        </div>
        <div class="parts-feature-card__media">
            <img src="${escapeHtml(fallbackAssets.machinery)}" alt="Gói linh kiện bảo trì định kỳ hệ thống chiller">
        </div>
    </article>
`;

const shouldShowFeatureCard = (pageItems) => (
    state.currentPage === 1
    && state.serviceId === 'all'
    && state.query.trim() === ''
    && !state.pricedOnly
    && pageItems.length >= 5
);

const renderList = () => {
    const filtered = getFilteredParts();
    clampCurrentPage(filtered.length);
    const startIndex = (state.currentPage - 1) * state.pageSize;
    const paginatedItems = filtered.slice(startIndex, startIndex + state.pageSize);

    if (els.loading) {
        els.loading.hidden = true;
    }

    if (!filtered.length) {
        els.list.hidden = true;
        els.empty.hidden = false;
        if (els.pagination) {
            els.pagination.hidden = true;
            els.pagination.innerHTML = '';
        }
        if (els.summary) {
            els.summary.textContent = 'Không có linh kiện khớp với bộ lọc hiện tại.';
        }
        return;
    }

    els.empty.hidden = true;
    els.list.hidden = false;

    const pageStart = startIndex + 1;
    const pageEnd = Math.min(startIndex + state.pageSize, filtered.length);

    if (els.summary) {
        els.summary.textContent = `Hiển thị ${pageStart}-${pageEnd} trên ${filtered.length} linh kiện phù hợp.`;
    }

    const blocks = paginatedItems.map((part) => buildPartCardMarkup(part));

    if (shouldShowFeatureCard(paginatedItems)) {
        blocks.splice(4, 0, buildFeatureCardMarkup());
    }

    els.list.innerHTML = blocks.join('');
    renderPagination(filtered.length);
};

const render = () => {
    renderFilters();
    renderList();
};

const loadData = async () => {
    try {
        const [partsResponse, servicesResponse] = await Promise.all([
            callApi('/linh-kien', 'GET'),
            callApi('/danh-muc-dich-vu', 'GET'),
        ]);

        if (!partsResponse.ok) {
            throw new Error(partsResponse.data?.message || 'Không thể tải danh sách linh kiện.');
        }

        if (!servicesResponse.ok) {
            throw new Error(servicesResponse.data?.message || 'Không thể tải danh mục dịch vụ.');
        }

        state.parts = (Array.isArray(partsResponse.data) ? partsResponse.data : []).map(decoratePart);
        state.services = Array.isArray(servicesResponse.data) ? servicesResponse.data : [];

        render();
    } catch (error) {
        if (els.loading) {
            els.loading.hidden = false;
            els.loading.innerHTML = `
                <span class="material-symbols-outlined">error</span>
                <h3>Không tải được bảng giá linh kiện</h3>
                <p>${escapeHtml(error.message || 'Đã có lỗi xảy ra khi đồng bộ dữ liệu. Vui lòng thử lại sau.')}</p>
            `;
        }

        showToast(error.message || 'Không tải được dữ liệu linh kiện.', 'error');
    }
};

els.searchForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    state.query = els.searchInput?.value || '';
    state.currentPage = 1;
    renderList();
});

els.searchInput?.addEventListener('input', (event) => {
    state.query = event.target.value || '';
    state.currentPage = 1;
    renderList();
});

els.sortSelect?.addEventListener('change', (event) => {
    state.sort = event.target.value || 'featured';
    state.currentPage = 1;
    renderList();
});

els.pricedToggle?.addEventListener('click', () => {
    state.pricedOnly = !state.pricedOnly;
    els.pricedToggle.classList.toggle('is-active', state.pricedOnly);
    els.pricedToggle.setAttribute('aria-pressed', state.pricedOnly ? 'true' : 'false');
    state.currentPage = 1;
    renderList();
});

els.filters?.addEventListener('change', (event) => {
    state.serviceId = event.target.value || 'all';
    state.currentPage = 1;
    render();
});

els.pagination?.addEventListener('click', (event) => {
    const pageButton = event.target.closest('[data-page]');
    if (pageButton) {
        state.currentPage = getNumeric(pageButton.dataset.page) || 1;
        renderList();
        return;
    }

    const actionButton = event.target.closest('[data-page-action]');
    if (!actionButton) {
        return;
    }

    if (actionButton.dataset.pageAction === 'prev') {
        state.currentPage = Math.max(1, state.currentPage - 1);
    }

    if (actionButton.dataset.pageAction === 'next') {
        state.currentPage += 1;
    }

    renderList();
});

loadData();
