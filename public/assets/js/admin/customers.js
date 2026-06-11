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
        profileModal: document.getElementById('customerProfileModal'),
        profileModalBody: document.getElementById('customerProfileModalBody'),
        profileDetailLink: document.getElementById('cmpDetailLink'),
        bookingsModal: document.getElementById('customerBookingsModal'),
        bookingsModalBody: document.getElementById('customerBookingsModalBody'),
        bookingsDetailLink: document.getElementById('cmbDetailLink'),
        cmbStatus: document.getElementById('cmbStatusFilter'),
        cmbSort: document.getElementById('cmbSortFilter'),
    };

    const state = {
        customers: [],
        selectedId: null,
        searchTimer: null,
        bookingsCustomerId: null,
        allBookings: [],
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

    const formatCurrency = (value) => number.format(Number(value || 0)) + ' đ';

    const relationshipMeta = (status) => {
        switch (status) {
            case 'active_booking':
                return { label: 'Đang có đơn xử lý', className: 'customer-pill--active_booking' };
            case 'new_customer':
                return { label: 'Khách mới', className: 'customer-pill--new_customer' };
            case 'inactive':
                return { label: 'Lâu chưa quay lại', className: 'customer-pill--inactive' };
            default:
                return { label: 'Đã từng đặt dịch vụ', className: 'customer-pill--loyal' };
        }
    };

    const toneToPill = (tone) => {
        const map = { info: 'cmp-pill--info', success: 'cmp-pill--success', danger: 'cmp-pill--danger', warning: 'cmp-pill--warning', muted: 'cmp-pill--muted' };
        return map[tone] || 'cmp-pill--muted';
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
            refs.caption.textContent = 'Không tìm thấy khách hàng phù hợp với bộ lọc hiện tại.';
            refs.tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="customer-admin-empty">Không có dữ liệu khách hàng phù hợp.</td>
                </tr>
            `;
            renderPreview(null);
            return;
        }

        refs.caption.textContent = `${formatNumber(state.customers.length)} khách hàng trong kết quả hiện tại.`;

        refs.tableBody.innerHTML = state.customers.map((customer) => {
            const relationship = relationshipMeta(customer.relationship_status);

            return `
                <tr data-customer-id="${customer.id}" class="${customer.id === state.selectedId ? 'is-selected' : ''}">
                    <td>
                        <div class="customer-cell-name">
                            ${buildAvatar(customer, 'customer-avatar')}
                            <div>
                                <div class="customer-name">${escapeHtml(customer.name)}</div>
                                <div class="customer-subcopy">${escapeHtml(customer.phone || 'Chưa có SĐT')}</div>
                                <div class="customer-subcopy">${escapeHtml(customer.email || 'Chưa có email')}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="customer-value-strong">${escapeHtml(customer.joined_label || '--')}</div>
                        <div class="customer-subcopy">Ngày tham gia</div>
                    </td>
                    <td>
                        <div class="customer-value-strong">${formatNumber(customer.order_count)} đơn</div>
                        <div class="customer-subcopy">${formatNumber(customer.active_booking_count)} đơn đang xử lý</div>
                    </td>
                    <td>
                        <span class="customer-pill ${relationship.className}">${escapeHtml(relationship.label)}</span>
                        <div class="customer-subcopy">${escapeHtml(customer.last_booking_service || 'Chưa có lịch sử đặt dịch vụ')}</div>
                    </td>
                    <td>
                        <button type="button" class="customer-quick-btn" data-open-profile="${customer.id}">Xem chi tiết</button>
                    </td>
                </tr>
            `;
        }).join('');
    };

    const renderPreview = (customer) => {
        if (!customer) {
            refs.preview.innerHTML = '<div class="customer-preview-empty">Chưa chọn khách hàng.</div>';
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
                    <span class="customer-preview-metric__label">Số đơn</span>
                    <span class="customer-preview-metric__value">${formatNumber(customer.order_count)}</span>
                </div>
                <div class="customer-preview-metric">
                    <span class="customer-preview-metric__label">Đang xử lý</span>
                    <span class="customer-preview-metric__value">${formatNumber(customer.active_booking_count)}</span>
                </div>
            </div>

            <div class="customer-preview-block">
                <span class="customer-preview-block__label">Thông tin liên hệ</span>
                <div class="customer-preview-block__value">${escapeHtml(customer.phone || 'Chưa có SĐT')}</div>
                <div class="customer-subcopy">${escapeHtml(customer.email || 'Chưa có email')}</div>
            </div>

            <div class="customer-preview-block">
                <span class="customer-preview-block__label">Ngày tham gia</span>
                <div class="customer-preview-block__value">${escapeHtml(customer.joined_label || '--')}</div>
            </div>

            <div class="customer-preview-block">
                <span class="customer-preview-block__label">Lịch sử đặt dịch vụ</span>
                <div class="customer-preview-block__value">${escapeHtml(customer.last_booking_service || 'Chưa có lịch sử đặt dịch vụ')}</div>
                <div class="customer-subcopy">${escapeHtml(customer.last_booking_label || 'Chưa đặt lịch')}</div>
            </div>

            <div class="customer-preview-block">
                <span class="customer-preview-block__label">Địa chỉ</span>
                <div class="customer-preview-block__value">${escapeHtml(customer.latest_address || 'Chưa có địa chỉ')}</div>
            </div>

            <div class="customer-preview-actions">
                <button type="button" class="customer-preview-action customer-preview-action--primary" data-open-profile="${customer.id}">Xem chi tiết hồ sơ</button>
                <button type="button" class="customer-preview-action" data-open-bookings="${customer.id}">Lịch sử đơn</button>
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
            refs.caption.textContent = 'Đang tải dữ liệu khách hàng...';
            refs.tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="customer-admin-empty">Đang tải danh sách khách hàng...</td>
                </tr>
            `;
        }

        try {
            syncFilterUrl();
            const response = await callApi(`/admin/customers${buildQuery()}`, 'GET');

            if (!response?.ok) {
                throw new Error(response?.data?.message || 'Không thể tải dữ liệu khách hàng');
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
            refs.caption.textContent = 'Không thể tải dữ liệu.';
            refs.tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="customer-admin-empty">Không thể tải dữ liệu khách hàng.</td>
                </tr>
            `;
            renderPreview(null);
            showToast(error.message || 'Không thể tải danh sách khách hàng', 'error');
        }
    };

    // ─── Modal helpers ───────────────────────────────────────────

    const openModal = (modalEl) => {
        modalEl.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    };

    const closeModal = (modalEl) => {
        modalEl.classList.remove('is-open');
        document.body.style.overflow = '';
    };

    // Close on overlay click
    document.querySelectorAll('.cm-modal-overlay').forEach((overlay) => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal(overlay);
        });
    });

    // Close buttons
    document.querySelectorAll('[data-modal-close]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.modalClose);
            if (target) closeModal(target);
        });
    });

    // ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.cm-modal-overlay.is-open').forEach(closeModal);
        }
    });

    // ─── Profile modal ────────────────────────────────────────────

    const buildProfileModalContent = (data) => {
        const { profile, summary, recent_bookings, alerts } = data;
        const esc = escapeHtml;

        const alertsHtml = (alerts || []).map((a) => `
            <div class="alert alert-${a.tone === 'danger' ? 'danger' : a.tone === 'warning' ? 'warning' : 'info'} py-2 px-3 mb-0 rounded-3" style="font-size:.84rem;">
                <strong>${esc(a.title)}:</strong> ${esc(a.detail)}
            </div>
        `).join('');

        const recentHtml = (recent_bookings || []).slice(0, 5).map((b) => `
            <div class="cmp-recent-item">
                <div>
                    <div class="cmp-recent-service">${esc(b.service_label)}</div>
                    <div class="cmp-recent-meta">${esc(b.schedule_label)} · ${esc(b.worker_name)}</div>
                </div>
                <span class="cmp-pill cmp-pill--${b.status_tone || 'muted'}">${esc(b.status_label)}</span>
            </div>
        `).join('') || '<div style="color:#94a3b8;font-size:.85rem;">Chưa có đơn nào.</div>';

        return `
            <div class="cmp-top">
                ${buildAvatar({ avatar: profile.avatar, name: profile.name }, 'cmp-avatar')}
                <div class="cmp-identity">
                    <h3 class="cmp-name">${esc(profile.name)}</h3>
                    <div class="cmp-code">${esc(profile.code)} · Tham gia ${esc(profile.joined_label)}</div>
                    <div class="mt-2">
                        <span class="customer-pill ${relationshipMeta(profile.relationship_status).className}">${esc(profile.relationship_label || relationshipMeta(profile.relationship_status).label)}</span>
                    </div>
                </div>
                ${alertsHtml ? `<div style="flex:0 0 240px;display:flex;flex-direction:column;gap:6px;">${alertsHtml}</div>` : ''}
            </div>

            <div class="cmp-grid">
                <div class="cmp-stat">
                    <span class="cmp-stat__label">Tổng đơn</span>
                    <span class="cmp-stat__value">${formatNumber(summary.order_count)}</span>
                </div>
                <div class="cmp-stat">
                    <span class="cmp-stat__label">Hoàn thành</span>
                    <span class="cmp-stat__value">${formatNumber(summary.completed_booking_count)}</span>
                </div>
                <div class="cmp-stat">
                    <span class="cmp-stat__label">Tổng chi tiêu</span>
                    <span class="cmp-stat__value" style="font-size:1.1rem;">${formatCurrency(summary.total_spent)}</span>
                </div>
                <div class="cmp-stat">
                    <span class="cmp-stat__label">Đánh giá TB</span>
                    <span class="cmp-stat__value">${summary.average_rating ? summary.average_rating + ' ⭐' : '--'}</span>
                </div>
            </div>

            <div class="cmp-sections">
                <div class="cmp-section">
                    <span class="cmp-section__label">Thông tin liên hệ</span>
                    <div class="cmp-info-row">
                        <span class="cmp-info-row__key">SĐT</span>
                        <span class="cmp-info-row__val">${esc(profile.phone || 'Chưa có')}</span>
                    </div>
                    <div class="cmp-info-row">
                        <span class="cmp-info-row__key">Email</span>
                        <span class="cmp-info-row__val">${esc(profile.email || 'Chưa có')}</span>
                    </div>
                    <div class="cmp-info-row">
                        <span class="cmp-info-row__key">Địa chỉ</span>
                        <span class="cmp-info-row__val">${esc(profile.latest_address || 'Chưa có')}</span>
                    </div>
                    <div class="cmp-info-row">
                        <span class="cmp-info-row__key">Đặt lần cuối</span>
                        <span class="cmp-info-row__val">${esc(profile.last_booking_label || 'Chưa đặt')}</span>
                    </div>
                </div>
                <div class="cmp-section">
                    <span class="cmp-section__label">Đơn gần nhất</span>
                    ${recentHtml}
                </div>
            </div>
        `;
    };

    const openProfileModal = async (customerId) => {
        refs.profileModalBody.innerHTML = '<div class="cm-modal__loading"><span class="spinner-border spinner-border-sm me-2"></span> Đang tải hồ sơ...</div>';
        refs.profileDetailLink.href = `/admin/customers/${customerId}`;
        openModal(refs.profileModal);

        try {
            const response = await callApi(`/admin/customers/${customerId}`, 'GET');
            if (!response?.ok) throw new Error(response?.data?.message || 'Không tải được hồ sơ');
            refs.profileModalBody.innerHTML = buildProfileModalContent(response.data?.data || {});
        } catch (error) {
            refs.profileModalBody.innerHTML = `<div class="cm-modal__loading" style="color:#dc2626;">Lỗi: ${escapeHtml(error.message)}</div>`;
        }
    };

    // ─── Bookings modal ───────────────────────────────────────────

    const buildBookingsTable = (bookings) => {
        if (!bookings.length) {
            return '<div class="cm-modal__loading">Không có đơn nào phù hợp.</div>';
        }

        const rows = bookings.map((b) => `
            <tr>
                <td>
                    <div class="cmb-code">${escapeHtml(b.code)}</div>
                    <div class="cmb-service">${escapeHtml(b.service_label)}</div>
                    <div class="cmb-meta">${escapeHtml(b.problem_excerpt || '')}</div>
                </td>
                <td>${escapeHtml(b.schedule_label)}</td>
                <td>${escapeHtml(b.worker_name)}</td>
                <td><span class="cmp-pill cmp-pill--${b.status_tone || 'muted'}">${escapeHtml(b.status_label)}</span></td>
                <td class="cmb-amount">${formatCurrency(b.total_amount)}</td>
                <td>
                    <a href="/admin/bookings/${b.id}" target="_blank" class="customer-quick-btn" style="font-size:.78rem;">Chi tiết</a>
                </td>
            </tr>
        `).join('');

        return `
            <table class="cmb-table">
                <thead>
                    <tr>
                        <th>Dịch vụ</th>
                        <th>Lịch hẹn</th>
                        <th>Thợ</th>
                        <th>Trạng thái</th>
                        <th>Số tiền</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        `;
    };

    const renderBookingsModal = () => {
        const statusFilter = refs.cmbStatus.value;
        const sortFilter = refs.cmbSort.value;

        let filtered = [...state.allBookings];

        if (statusFilter) {
            filtered = filtered.filter((b) => b.status === statusFilter);
        }

        if (sortFilter === 'oldest') {
            // already sorted latest by API; reverse for oldest
            filtered.reverse();
        } else if (sortFilter === 'amount_desc') {
            filtered.sort((a, b) => (b.total_amount || 0) - (a.total_amount || 0));
        }

        refs.bookingsModalBody.innerHTML = buildBookingsTable(filtered);
    };

    const openBookingsModal = async (customerId) => {
        state.bookingsCustomerId = customerId;
        state.allBookings = [];
        refs.bookingsModalBody.innerHTML = '<div class="cm-modal__loading"><span class="spinner-border spinner-border-sm me-2"></span> Đang tải lịch sử đơn...</div>';
        refs.bookingsDetailLink.href = `/admin/customers/${customerId}/bookings`;
        refs.cmbStatus.value = '';
        refs.cmbSort.value = 'latest';
        openModal(refs.bookingsModal);

        try {
            const response = await callApi(`/admin/customers/${customerId}/bookings`, 'GET');
            if (!response?.ok) throw new Error(response?.data?.message || 'Không tải được lịch sử đơn');
            const payload = response.data?.data || {};
            state.allBookings = Array.isArray(payload.bookings) ? payload.bookings : [];
            renderBookingsModal();
        } catch (error) {
            refs.bookingsModalBody.innerHTML = `<div class="cm-modal__loading" style="color:#dc2626;">Lỗi: ${escapeHtml(error.message)}</div>`;
        }
    };

    // ─── Event delegation for dynamic buttons ────────────────────

    document.addEventListener('click', (event) => {
        const profileBtn = event.target.closest('[data-open-profile]');
        if (profileBtn) {
            openProfileModal(profileBtn.dataset.openProfile);
            return;
        }

        const bookingsBtn = event.target.closest('[data-open-bookings]');
        if (bookingsBtn) {
            openBookingsModal(bookingsBtn.dataset.openBookings);
            return;
        }
    });

    refs.tableBody.addEventListener('click', (event) => {
        const row = event.target.closest('[data-customer-id]');
        if (row && !event.target.closest('[data-open-profile]')) {
            selectCustomer(row.dataset.customerId);
        }
    });

    refs.cmbStatus.addEventListener('change', renderBookingsModal);
    refs.cmbSort.addEventListener('change', renderBookingsModal);

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
