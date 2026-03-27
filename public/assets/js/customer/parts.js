import { callApi, showToast } from '../api.js';

const state = {
    parts: [],
    services: [],
    query: '',
    serviceId: 'all',
    sort: 'featured',
    pricedOnly: false,
    currentPage: 1,
    pageSize: 12,
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
    metricCount: document.getElementById('partsMetricCount'),
    metricServices: document.getElementById('partsMetricServices'),
    metricPriced: document.getElementById('partsMetricPriced'),
    marquee: document.getElementById('partsHeroMarquee'),
};

const currencyFormatter = new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
});

const accentPalette = ['#d9f99d', '#bae6fd', '#fcd34d', '#fecdd3', '#c7d2fe', '#99f6e4'];

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

const getThumbFallback = (part) => {
    const service = String(part?.serviceName || '').trim();
    const source = service || part?.ten_linh_kien || 'LK';
    return source
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0])
        .join('')
        .toUpperCase();
};

const decoratePart = (part, index) => ({
    ...part,
    serviceName: part?.dich_vu?.ten_dich_vu || part?.serviceName || 'Linh kiện',
    priceValue: getNumeric(part?.gia),
    accent: accentPalette[index % accentPalette.length],
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
            icon: 'trending_down',
            label: 'Giá giảm',
            detail: 'Thấp hơn mặt bằng cùng nhóm',
        };
    }

    if (ratio >= 1.08) {
        return {
            tone: 'is-increase',
            icon: 'trending_up',
            label: 'Giá tăng',
            detail: 'Cao hơn mặt bằng cùng nhóm',
        };
    }

    return {
        tone: 'is-stable',
        icon: 'trending_flat',
        label: 'Giá ổn định',
        detail: 'Gần sát mặt bằng cùng nhóm',
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
            icon: 'local_fire_department',
            label: 'Giá tốt',
            detail: 'Nằm trong nhóm giá dễ tiếp cận',
        };
    }

    return null;
};

const buildBadgesMarkup = (part) => {
    const trendMeta = getTrendMeta(part);
    const valueBadge = getValueBadge(part);
    const badges = [trendMeta, valueBadge].filter(Boolean);

    if (!badges.length) {
        return '';
    }

    return `
        <div class="parts-tile__badges">
            ${badges.map((badge) => `
                <span class="parts-tile__badge ${badge.tone}" title="${escapeHtml(badge.detail)}">
                    <span class="material-symbols-outlined">${escapeHtml(badge.icon)}</span>
                    ${escapeHtml(badge.label)}
                </span>
            `).join('')}
        </div>
    `;
};

const getActiveServiceCount = () => new Set(
    state.parts
        .map((part) => String(part.dich_vu_id || ''))
        .filter(Boolean)
).size;

const buildFilterItems = () => {
    const counts = state.parts.reduce((map, part) => {
        const serviceId = String(part.dich_vu_id || 'unknown');
        map.set(serviceId, (map.get(serviceId) || 0) + 1);
        return map;
    }, new Map());

    return [
        { id: 'all', label: 'Tất cả', count: state.parts.length },
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

const renderPagination = (totalItems) => {
    const totalPages = getPageCount(totalItems);

    if (!els.pagination) {
        return;
    }

    if (totalItems <= state.pageSize) {
        els.pagination.hidden = true;
        els.pagination.innerHTML = '';
        return;
    }

    const windowPages = [];
    const startPage = Math.max(1, state.currentPage - 2);
    const endPage = Math.min(totalPages, state.currentPage + 2);

    for (let page = startPage; page <= endPage; page += 1) {
        windowPages.push(page);
    }

    els.pagination.hidden = false;
    els.pagination.innerHTML = `
        <button class="parts-pagination__button" data-page-action="prev" ${state.currentPage === 1 ? 'disabled' : ''}>
            ‹
        </button>
        ${windowPages.map((page) => `
            <button class="parts-pagination__button ${page === state.currentPage ? 'is-active' : ''}" data-page="${page}">
                ${page}
            </button>
        `).join('')}
        <button class="parts-pagination__button" data-page-action="next" ${state.currentPage === totalPages ? 'disabled' : ''}>
            ›
        </button>
    `;
};

const renderMetrics = () => {
    const pricedCount = state.parts.filter((part) => part.priceValue > 0).length;

    els.metricCount.textContent = String(state.parts.length).padStart(2, '0');
    els.metricServices.textContent = String(getActiveServiceCount()).padStart(2, '0');
    els.metricPriced.textContent = String(pricedCount).padStart(2, '0');

    const marqueeItems = state.parts
        .filter((part) => part.priceValue > 0)
        .slice(0, 6)
        .map((part) => `<span>${escapeHtml(part.ten_linh_kien)}</span>`);

    if (marqueeItems.length) {
        els.marquee.innerHTML = marqueeItems.join('');
    }
};

const renderFilters = () => {
    const items = buildFilterItems();

    els.filters.innerHTML = items.map((item) => `
        <button
            type="button"
            class="parts-filter-pill ${String(item.id) === String(state.serviceId) ? 'is-active' : ''}"
            data-service-id="${escapeHtml(item.id)}"
        >
            <span>${escapeHtml(item.label)}</span>
            <span class="parts-filter-pill__count">${item.count}</span>
        </button>
    `).join('');
};

const renderList = () => {
    const filtered = getFilteredParts();
    clampCurrentPage(filtered.length);
    const startIndex = (state.currentPage - 1) * state.pageSize;
    const paginatedItems = filtered.slice(startIndex, startIndex + state.pageSize);

    els.loading.hidden = true;

    if (!filtered.length) {
        els.list.hidden = true;
        els.empty.hidden = false;
        if (els.pagination) {
            els.pagination.hidden = true;
            els.pagination.innerHTML = '';
        }
        els.summary.textContent = 'Không có linh kiện khớp với bộ lọc hiện tại.';
        return;
    }

    els.empty.hidden = true;
    els.list.hidden = false;
    const pageStart = startIndex + 1;
    const pageEnd = Math.min(startIndex + state.pageSize, filtered.length);
    els.summary.textContent = `Hiển thị ${pageStart}-${pageEnd} trên ${filtered.length} linh kiện phù hợp để khách hàng tham khảo giá trước khi đặt lịch.`;

    els.list.innerHTML = paginatedItems.map((part, index) => `
        <article class="parts-tile" style="--parts-thumb:${escapeHtml(part.accent)}; animation-delay:${index * 35}ms;">
            <div class="parts-tile__thumb">
                ${part.hinh_anh
                    ? `<img src="${escapeHtml(part.hinh_anh)}" alt="${escapeHtml(part.ten_linh_kien)}">`
                    : `<span>${escapeHtml(getThumbFallback(part))}</span>`}
            </div>

            <div class="parts-tile__body">
                <span class="parts-tile__service">${escapeHtml(part.serviceName)}</span>
                <h3 class="parts-tile__name">${escapeHtml(part.ten_linh_kien || 'Linh kiện')}</h3>
                ${buildBadgesMarkup(part)}
                <p class="parts-tile__note">
                    ${part.priceValue > 0
                        ? 'Giá tham khảo cho riêng linh kiện. Công thợ và kiểm tra thực tế sẽ được báo riêng khi tiếp nhận.'
                        : 'Mục này chưa có giá niêm yết cố định. Bạn có thể đặt lịch để được kỹ thuật viên báo theo tình trạng thực tế.'}
                </p>
            </div>

            <div class="parts-tile__aside">
                <div class="parts-tile__price ${part.priceValue > 0 ? '' : 'is-contact'}">${escapeHtml(getPartPriceLabel(part))}</div>
                <div class="parts-tile__actions">
                    <a class="parts-tile__link" href="/customer/linh-kien/${encodeURIComponent(part.id)}">
                        Xem chi tiết
                    </a>
                    <a class="parts-tile__action" href="/customer/booking?dich_vu_id=${encodeURIComponent(part.dich_vu_id || '')}">
                        Đặt thợ theo nhóm này
                    </a>
                </div>
            </div>
        </article>
    `).join('');

    renderPagination(filtered.length);
};

const render = () => {
    renderMetrics();
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
        els.loading.hidden = false;
        els.loading.innerHTML = `
            <span class="material-symbols-outlined">error</span>
            <h3>Không tải được bảng giá linh kiện</h3>
            <p>${escapeHtml(error.message || 'Đã có lỗi xảy ra khi đồng bộ dữ liệu. Vui lòng thử lại sau.')}</p>
        `;
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

els.filters?.addEventListener('click', (event) => {
    const button = event.target.closest('[data-service-id]');
    if (!button) {
        return;
    }

    state.serviceId = button.dataset.serviceId || 'all';
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
