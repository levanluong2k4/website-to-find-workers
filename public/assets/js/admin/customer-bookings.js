import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const root = document.getElementById('customerHistoryApp');
    const customerId = root?.dataset.customerId;

    if (!root || !customerId) {
        return;
    }

    const refs = {
        title: document.getElementById('customerHistoryTitle'),
        subtitle: document.getElementById('customerHistorySubtitle'),
        detailLink: document.getElementById('customerHistoryDetailLink'),
        refresh: document.getElementById('customerHistoryRefreshButton'),
        stats: document.getElementById('customerHistoryStats'),
        caption: document.getElementById('customerHistoryCaption'),
        search: document.getElementById('customerHistorySearch'),
        status: document.getElementById('customerHistoryStatus'),
        payment: document.getElementById('customerHistoryPayment'),
        mode: document.getElementById('customerHistoryMode'),
        service: document.getElementById('customerHistoryService'),
        worker: document.getElementById('customerHistoryWorker'),
        dateFrom: document.getElementById('customerHistoryDateFrom'),
        dateTo: document.getElementById('customerHistoryDateTo'),
        amountMin: document.getElementById('customerHistoryAmountMin'),
        tableBody: document.getElementById('customerHistoryTableBody'),
        preview: document.getElementById('customerHistoryPreview'),
    };

    const state = {
        bookings: [],
        selectedId: null,
        searchTimer: null,
    };

    const currency = new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    });
    const number = new Intl.NumberFormat('vi-VN');

    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');

    const formatMoney = (value) => currency.format(Number(value || 0));
    const formatNumber = (value) => number.format(Number(value || 0));
    const buildLogisticsBreakdown = (booking) => {
        const travelFee = Number(booking?.travel_fee || 0);
        const transportFee = Number(booking?.transport_fee || 0);
        const transportRequested = booking?.transport_requested === true || booking?.transport_requested === 1 || booking?.transport_requested === '1';
        const items = [];

        if (travelFee > 0) {
            items.push(`Đi lại ${formatMoney(travelFee)}`);
        }

        if (transportRequested || transportFee > 0) {
            items.push(`Thuê xe ${formatMoney(transportFee)}`);
        }

        return items.length ? items.join(' • ') : 'Không có phụ phí logistics';
    };

    const toneClass = (tone) => {
        switch (tone) {
            case 'success':
                return 'customer-history-pill--success';
            case 'warning':
                return 'customer-history-pill--warning';
            case 'danger':
                return 'customer-history-pill--danger';
            case 'muted':
                return 'customer-history-pill--muted';
            default:
                return 'customer-history-pill--info';
        }
    };

    const buildQuery = () => {
        const params = new URLSearchParams();

        if (refs.search.value.trim()) params.set('search', refs.search.value.trim());
        if (refs.status.value) params.set('status', refs.status.value);
        if (refs.payment.value) params.set('payment', refs.payment.value);
        if (refs.mode.value) params.set('mode', refs.mode.value);
        if (refs.service.value) params.set('service', refs.service.value);
        if (refs.worker.value) params.set('worker_id', refs.worker.value);
        if (refs.dateFrom.value) params.set('date_from', refs.dateFrom.value);
        if (refs.dateTo.value) params.set('date_to', refs.dateTo.value);
        if (refs.amountMin.value) params.set('amount_min', refs.amountMin.value);

        const query = params.toString();
        return query ? `?${query}` : '';
    };

    const populateServiceFilter = (services = []) => {
        const currentValue = refs.service.value;
        refs.service.innerHTML = `
            <option value="">Tat ca dich vu</option>
            ${services.map((service) => `<option value="${escapeHtml(service)}">${escapeHtml(service)}</option>`).join('')}
        `;
        refs.service.value = currentValue || '';
    };

    const populateWorkerFilter = (workers = []) => {
        const currentValue = refs.worker.value;
        refs.worker.innerHTML = `
            <option value="">Tat ca tho</option>
            ${workers.map((worker) => `<option value="${escapeHtml(String(worker.id))}">${escapeHtml(worker.name || 'Tho')}</option>`).join('')}
        `;
        refs.worker.value = currentValue || '';
    };

    const renderStats = (summary) => {
        const cards = [
            ['Tong don', formatNumber(summary?.order_count || 0), `${formatNumber(summary?.filtered_count || 0)} don trong ket qua`],
            ['Don dang mo', formatNumber(summary?.active_booking_count || 0), `${formatNumber(summary?.completed_booking_count || 0)} don hoan thanh`],
            ['Da huy', formatNumber(summary?.canceled_booking_count || 0), 'Tong don huy cua khach'],
            ['Tong chi tieu', formatMoney(summary?.total_spent || 0), 'Tinh tren don hoan thanh'],
            ['Da loc', formatNumber(summary?.filtered_count || 0), 'So don dang hien thi'],
        ];

        refs.stats.innerHTML = cards.map(([label, value, meta]) => `
            <article class="customer-history-stat">
                <span class="customer-history-stat__label">${escapeHtml(label)}</span>
                <span class="customer-history-stat__value">${escapeHtml(value)}</span>
                <span class="customer-history-stat__meta">${escapeHtml(meta)}</span>
            </article>
        `).join('');
    };

    const renderTable = () => {
        if (!state.bookings.length) {
            refs.caption.textContent = 'Khong tim thay don hang phu hop voi bo loc hien tai.';
            refs.tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="customer-history-empty">Khong co don hang phu hop.</td>
                </tr>
            `;
            renderPreview(null);
            return;
        }

        refs.caption.textContent = `${formatNumber(state.bookings.length)} don trong ket qua hien tai.`;

        refs.tableBody.innerHTML = state.bookings.map((booking) => `
            <tr class="customer-history-row ${booking.id === state.selectedId ? 'is-selected' : ''}" data-booking-id="${booking.id}">
                <td>
                    <div class="customer-history-code">${escapeHtml(booking.code || '--')}</div>
                    <div class="customer-history-name">${escapeHtml(booking.service_label || 'Don dat lich')}</div>
                    <div class="customer-history-subcopy">${escapeHtml(booking.address || 'Chua co dia chi')}</div>
                </td>
                <td>
                    <div class="customer-history-name">${escapeHtml(booking.schedule_label || '--')}</div>
                    <div class="customer-history-subcopy">${escapeHtml(booking.mode_label || '--')}</div>
                </td>
                <td>
                    <div class="customer-history-name">${escapeHtml(booking.worker_name || 'Chua gan tho')}</div>
                    <div class="customer-history-subcopy">${escapeHtml(booking.review_label || 'Chua review')}</div>
                </td>
                <td><span class="customer-history-pill ${toneClass(booking.status_tone)}">${escapeHtml(booking.status_label || '--')}</span></td>
                <td><span class="customer-history-pill ${toneClass(booking.payment_tone)}">${escapeHtml(booking.payment_label || '--')}</span></td>
                <td>
                    <div class="customer-history-name">${escapeHtml(formatMoney(booking.total_amount || 0))}</div>
                    <div class="customer-history-subcopy">${escapeHtml(buildLogisticsBreakdown(booking))}</div>
                </td>
            </tr>
        `).join('');
    };

    const renderPreview = (booking) => {
        if (!booking) {
            refs.preview.innerHTML = '<div class="customer-history-empty">Chua chon don hang.</div>';
            return;
        }

        refs.preview.innerHTML = `
            <div class="customer-history-preview-block">
                <span class="customer-history-preview-label">Don hang</span>
                <div class="customer-history-preview-value">${escapeHtml(booking.code || '--')}<br>${escapeHtml(booking.service_label || 'Don dat lich')}</div>
            </div>
            <div class="customer-history-preview-block">
                <span class="customer-history-preview-label">Lich hen va hinh thuc</span>
                <div class="customer-history-preview-value">${escapeHtml(booking.schedule_label || '--')}<br>${escapeHtml(booking.mode_label || '--')}</div>
            </div>
            <div class="customer-history-preview-block">
                <span class="customer-history-preview-label">Tho phu trach</span>
                <div class="customer-history-preview-value">${escapeHtml(booking.worker_name || 'Chua gan tho')}</div>
            </div>
            <div class="customer-history-preview-block">
                <span class="customer-history-preview-label">Dia diem</span>
                <div class="customer-history-preview-value">${escapeHtml(booking.address || 'Chua cap nhat')}</div>
            </div>
            <div class="customer-history-preview-block">
                <span class="customer-history-preview-label">Mo ta van de</span>
                <div class="customer-history-preview-value">${escapeHtml(booking.problem_excerpt || 'Khach chua de mo ta van de.')}</div>
            </div>
            <div class="customer-history-preview-block">
                <span class="customer-history-preview-label">Trang thai va thanh toan</span>
                <div class="customer-history-preview-value">
                    <span class="customer-history-pill ${toneClass(booking.status_tone)}">${escapeHtml(booking.status_label || '--')}</span>
                    <span class="customer-history-pill ${toneClass(booking.payment_tone)}" style="margin-left:8px;">${escapeHtml(booking.payment_label || '--')}</span>
                    <div class="customer-history-subcopy" style="margin-top:10px;">Tong tien ${escapeHtml(formatMoney(booking.total_amount || 0))}</div>
                    <div class="customer-history-subcopy" style="margin-top:6px;">${escapeHtml(buildLogisticsBreakdown(booking))}</div>
                </div>
            </div>
            <div class="customer-history-preview-actions">
                <a class="customer-history-action customer-history-action--primary" href="${escapeHtml(booking.detail_url || '#')}">Xem chi tiet don</a>
                <a class="customer-history-action" href="/admin/customers/${customerId}">Ve ho so 360</a>
            </div>
        `;
    };

    const selectBooking = (bookingId) => {
        state.selectedId = Number(bookingId);
        renderTable();
        renderPreview(state.bookings.find((item) => item.id === state.selectedId) || null);
    };

    const loadBookings = async ({ silent = false } = {}) => {
        if (!silent) {
            refs.caption.textContent = 'Dang tai lich su don...';
            refs.tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="customer-history-empty">Dang tai danh sach don hang...</td>
                </tr>
            `;
            refs.preview.innerHTML = '<div class="customer-history-empty">Dang tai xem nhanh...</div>';
        }

        try {
            const response = await callApi(`/admin/customers/${customerId}/bookings${buildQuery()}`, 'GET');

            if (!response?.ok) {
                throw new Error(response?.data?.message || 'Khong the tai lich su don');
            }

            const payload = response.data?.data || {};
            const customer = payload.customer || {};
            const summary = payload.summary || {};

            refs.title.textContent = `Lich su don - ${customer.name || 'Khach hang'}`;
            refs.subtitle.textContent = `${customer.code || 'KH'} • ${customer.phone || 'Chua co SDT'} • ${customer.email || 'Chua co email'}`;
            refs.detailLink.href = customer.detail_url || `/admin/customers/${customerId}`;

            renderStats(summary);
            populateServiceFilter(payload.filters?.available_services || []);
            populateWorkerFilter(payload.filters?.available_workers || []);

            if (payload.filters?.service) refs.service.value = payload.filters.service;
            if (payload.filters?.worker_id) refs.worker.value = payload.filters.worker_id;
            if (payload.filters?.amount_min) refs.amountMin.value = payload.filters.amount_min;

            state.bookings = Array.isArray(payload.bookings) ? payload.bookings : [];
            if (!state.bookings.some((booking) => booking.id === state.selectedId)) {
                state.selectedId = state.bookings[0]?.id || null;
            }

            renderTable();
            renderPreview(state.bookings.find((item) => item.id === state.selectedId) || null);
        } catch (error) {
            console.error('Load customer bookings failed:', error);
            refs.caption.textContent = 'Khong the tai du lieu.';
            refs.tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="customer-history-empty">Khong the tai lich su don hang.</td>
                </tr>
            `;
            refs.preview.innerHTML = '<div class="customer-history-empty">Khong the tai xem nhanh.</div>';
            showToast(error.message || 'Khong the tai lich su don', 'error');
        }
    };

    refs.tableBody.addEventListener('click', (event) => {
        const row = event.target.closest('[data-booking-id]');
        if (row) {
            selectBooking(row.dataset.bookingId);
        }
    });

    refs.refresh.addEventListener('click', () => loadBookings());
    refs.status.addEventListener('change', () => loadBookings());
    refs.payment.addEventListener('change', () => loadBookings());
    refs.mode.addEventListener('change', () => loadBookings());
    refs.service.addEventListener('change', () => loadBookings());
    refs.worker.addEventListener('change', () => loadBookings());
    refs.dateFrom.addEventListener('change', () => loadBookings());
    refs.dateTo.addEventListener('change', () => loadBookings());
    refs.amountMin.addEventListener('input', () => {
        if (state.searchTimer) {
            clearTimeout(state.searchTimer);
        }

        state.searchTimer = window.setTimeout(() => loadBookings({ silent: true }), 260);
    });
    refs.search.addEventListener('input', () => {
        if (state.searchTimer) {
            clearTimeout(state.searchTimer);
        }

        state.searchTimer = window.setTimeout(() => loadBookings({ silent: true }), 260);
    });
    loadBookings();
});
