import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const refs = {
        search: document.getElementById('customerSearchInput'),
        status: document.getElementById('customerStatusFilter'),
        sort: document.getElementById('customerSortFilter'),
        refresh: document.getElementById('customerRefreshButton'),
        caption: document.getElementById('customerTableCaption'),
        tableBody: document.getElementById('customerTableBody'),
        preview: document.getElementById('customerPreviewPanel'),
        statTotal: document.getElementById('customerStatTotal'),
        statNew: document.getElementById('customerStatNew'),
        statBooked: document.getElementById('customerStatBooked'),
        statActive: document.getElementById('customerStatActive'),
    };

    const state = {
        customers: [],
        selectedId: null,
        searchTimer: null,
    };

    const number = new Intl.NumberFormat('vi-VN');

    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');

    const initials = (name) => String(name || 'KH')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || 'KH';

    const formatNumber = (value) => number.format(Number(value || 0));

    const relationshipMeta = (status) => {
        switch (status) {
            case 'active_booking':
                return { label: 'Dang co don xu ly', className: 'customer-pill--active_booking' };
            case 'new_customer':
                return { label: 'Khach moi', className: 'customer-pill--new_customer' };
            case 'inactive':
                return { label: 'Lau chua quay lai', className: 'customer-pill--inactive' };
            default:
                return { label: 'Da tung dat dich vu', className: 'customer-pill--loyal' };
        }
    };

    const buildAvatar = (customer, className) => {
        if (customer.avatar) {
            return `
                <div class="${className}">
                    <img src="${escapeHtml(customer.avatar)}" alt="${escapeHtml(customer.name)}" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                    <span style="display:none;">${escapeHtml(initials(customer.name))}</span>
                </div>
            `;
        }

        return `<div class="${className}">${escapeHtml(initials(customer.name))}</div>`;
    };

    const buildQuery = () => {
        const params = new URLSearchParams();

        if (refs.search.value.trim()) {
            params.set('search', refs.search.value.trim());
        }

        if (refs.status.value) {
            params.set('status', refs.status.value);
        }

        if (refs.sort.value) {
            params.set('sort', refs.sort.value);
        }

        const query = params.toString();
        return query ? `?${query}` : '';
    };

    const syncFiltersFromUrl = () => {
        const url = new URL(window.location.href);

        refs.search.value = url.searchParams.get('search') || '';
        refs.status.value = url.searchParams.get('status') || '';
        refs.sort.value = url.searchParams.get('sort') || 'latest';
    };

    const syncFilterUrl = () => {
        const url = new URL(window.location.href);
        const params = new URLSearchParams(buildQuery().replace(/^\?/, ''));

        url.search = params.toString();
        window.history.replaceState({}, '', url);
    };

    const renderStats = (summary) => {
        refs.statTotal.textContent = formatNumber(summary?.total_customers || 0);
        refs.statNew.textContent = formatNumber(summary?.new_customers_30d || 0);
        refs.statBooked.textContent = formatNumber(summary?.booked_customers || 0);
        refs.statActive.textContent = formatNumber(summary?.active_booking_customers || 0);
    };

    const renderTable = () => {
        if (!state.customers.length) {
            refs.caption.textContent = 'Khong tim thay khach hang phu hop voi bo loc hien tai.';
            refs.tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="customer-admin-empty">Khong co du lieu khach hang phu hop.</td>
                </tr>
            `;
            renderPreview(null);
            return;
        }

        refs.caption.textContent = `${formatNumber(state.customers.length)} khach hang trong ket qua hien tai.`;

        refs.tableBody.innerHTML = state.customers.map((customer) => {
            const relationship = relationshipMeta(customer.relationship_status);

            return `
                <tr data-customer-id="${customer.id}" class="${customer.id === state.selectedId ? 'is-selected' : ''}">
                    <td>
                        <div class="customer-cell-name">
                            ${buildAvatar(customer, 'customer-avatar')}
                            <div>
                                <div class="customer-name">${escapeHtml(customer.name)}</div>
                                <div class="customer-subcopy">${escapeHtml(customer.phone || 'Chua co SDT')}</div>
                                <div class="customer-subcopy">${escapeHtml(customer.email || 'Chua co email')}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="customer-value-strong">${escapeHtml(customer.joined_label || '--')}</div>
                        <div class="customer-subcopy">Ngay tham gia</div>
                    </td>
                    <td>
                        <div class="customer-value-strong">${formatNumber(customer.order_count)} don</div>
                        <div class="customer-subcopy">${formatNumber(customer.active_booking_count)} don dang xu ly</div>
                    </td>
                    <td>
                        <span class="customer-pill ${relationship.className}">${escapeHtml(relationship.label)}</span>
                        <div class="customer-subcopy">${escapeHtml(customer.last_booking_service || 'Chua co lich su dat dich vu')}</div>
                    </td>
                    <td>
                        <a href="/admin/customers/${customer.id}" class="customer-quick-btn">Xem chi tiet</a>
                    </td>
                </tr>
            `;
        }).join('');
    };

    const renderPreview = (customer) => {
        if (!customer) {
            refs.preview.innerHTML = '<div class="customer-preview-empty">Chua chon khach hang.</div>';
            return;
        }

        const relationship = relationshipMeta(customer.relationship_status);

        refs.preview.innerHTML = `
            <div class="customer-preview-top">
                ${buildAvatar(customer, 'customer-preview-avatar')}
                <div>
                    <h3 class="customer-preview-name">${escapeHtml(customer.name)}</h3>
                    <div class="customer-preview-code">${escapeHtml(customer.code || '--')}</div>
                    <div class="mt-2">
                        <span class="customer-pill ${relationship.className}">${escapeHtml(relationship.label)}</span>
                    </div>
                </div>
            </div>

            <div class="customer-preview-grid">
                <div class="customer-preview-metric">
                    <span class="customer-preview-metric__label">So don</span>
                    <span class="customer-preview-metric__value">${formatNumber(customer.order_count)}</span>
                </div>
                <div class="customer-preview-metric">
                    <span class="customer-preview-metric__label">Dang xu ly</span>
                    <span class="customer-preview-metric__value">${formatNumber(customer.active_booking_count)}</span>
                </div>
            </div>

            <div class="customer-preview-block">
                <span class="customer-preview-block__label">Thong tin lien he</span>
                <div class="customer-preview-block__value">${escapeHtml(customer.phone || 'Chua co SDT')}</div>
                <div class="customer-subcopy">${escapeHtml(customer.email || 'Chua co email')}</div>
            </div>

            <div class="customer-preview-block">
                <span class="customer-preview-block__label">Ngay tham gia</span>
                <div class="customer-preview-block__value">${escapeHtml(customer.joined_label || '--')}</div>
            </div>

            <div class="customer-preview-block">
                <span class="customer-preview-block__label">Lich su dat dich vu</span>
                <div class="customer-preview-block__value">${escapeHtml(customer.last_booking_service || 'Chua co lich su dat dich vu')}</div>
                <div class="customer-subcopy">${escapeHtml(customer.last_booking_label || 'Chua dat lich')}</div>
            </div>

            <div class="customer-preview-block">
                <span class="customer-preview-block__label">Dia chi</span>
                <div class="customer-preview-block__value">${escapeHtml(customer.latest_address || 'Chua co dia chi')}</div>
            </div>

            <div class="customer-preview-actions">
                <a class="customer-preview-action customer-preview-action--primary" href="/admin/customers/${customer.id}">Xem chi tiet</a>
                <a class="customer-preview-action" href="/admin/customers/${customer.id}/bookings">Lich su don</a>
            </div>
        `;
    };

    const selectCustomer = (customerId) => {
        state.selectedId = Number(customerId);
        renderTable();
        renderPreview(state.customers.find((item) => item.id === state.selectedId) || null);
    };

    const loadCustomers = async ({ silent = false } = {}) => {
        if (!silent) {
            refs.caption.textContent = 'Dang tai du lieu khach hang...';
            refs.tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="customer-admin-empty">Dang tai danh sach khach hang...</td>
                </tr>
            `;
        }

        try {
            syncFilterUrl();
            const response = await callApi(`/admin/customers${buildQuery()}`, 'GET');

            if (!response?.ok) {
                throw new Error(response?.data?.message || 'Khong the tai du lieu khach hang');
            }

            const payload = response.data?.data || {};

            state.customers = Array.isArray(payload.customers) ? payload.customers : [];
            renderStats(payload.summary || {});

            if (!state.customers.some((customer) => customer.id === state.selectedId)) {
                state.selectedId = state.customers[0]?.id || null;
            }

            renderTable();
            renderPreview(state.customers.find((item) => item.id === state.selectedId) || null);
        } catch (error) {
            console.error('Load admin customers failed:', error);
            refs.caption.textContent = 'Khong the tai du lieu.';
            refs.tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="customer-admin-empty">Khong the tai du lieu khach hang.</td>
                </tr>
            `;
            renderPreview(null);
            showToast(error.message || 'Khong the tai danh sach khach hang', 'error');
        }
    };

    refs.tableBody.addEventListener('click', (event) => {
        const row = event.target.closest('[data-customer-id]');

        if (row) {
            selectCustomer(row.dataset.customerId);
        }
    });

    refs.refresh.addEventListener('click', () => loadCustomers());
    refs.status.addEventListener('change', () => loadCustomers());
    refs.sort.addEventListener('change', () => loadCustomers());
    refs.search.addEventListener('input', () => {
        if (state.searchTimer) {
            clearTimeout(state.searchTimer);
        }

        state.searchTimer = window.setTimeout(() => loadCustomers({ silent: true }), 260);
    });

    syncFiltersFromUrl();
    loadCustomers();
});
