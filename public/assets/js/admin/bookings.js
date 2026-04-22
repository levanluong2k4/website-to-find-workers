import { callApi, downloadApiFile, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const refs = {
        stats: document.getElementById('bookingStatsCards'),
        slaFilterBadge: document.getElementById('orderSlaAlertBadge'),
        tableBody: document.getElementById('bookingTableBody'),
        pagination: document.getElementById('orderPagination'),
        search: document.getElementById('orderSearchInput'),
        status: document.getElementById('orderStatusFilter'),
        service: document.getElementById('orderServiceFilter'),
        worker: document.getElementById('orderWorkerFilter'),
        payment: document.getElementById('orderPaymentFilter'),
        mode: document.getElementById('orderModeFilter'),
        priority: document.getElementById('orderPriorityFilter'),
        sla: document.getElementById('orderSlaFilter'),
        slaDropdown: document.getElementById('orderSlaDropdown'),
        slaDropdownToggle: document.getElementById('orderSlaDropdownToggle'),
        slaDropdownLabel: document.getElementById('orderSlaDropdownLabel'),
        slaDropdownMenu: document.getElementById('orderSlaDropdownMenu'),
        dateFrom: document.getElementById('orderDateFromFilter'),
        dateTo: document.getElementById('orderDateToFilter'),
        sortBy: document.getElementById('orderSortByFilter'),
        sortDir: document.getElementById('orderSortDirFilter'),
        quickViews: document.getElementById('orderQuickViews'),
        refresh: document.getElementById('btnRefreshOrders'),
        exportCsv: document.getElementById('btnExportOrders'),
        selectAll: document.getElementById('selectAllBookings'),
        bulkBar: document.getElementById('bulkActionBar'),
        bulkSelectedCount: document.getElementById('bulkSelectedCount'),
        bulkAssign: document.getElementById('btnBulkAssignWorker'),
        bulkStatus: document.getElementById('btnBulkChangeStatus'),
        bulkExport: document.getElementById('btnBulkExportSelected'),
        bulkClear: document.getElementById('btnClearSelection'),
        drawer: document.getElementById('bookingDetailDrawer'),
        drawerOverlay: document.getElementById('bookingDetailOverlay'),
        drawerTitle: document.getElementById('detailDrawerTitle'),
        drawerClose: document.getElementById('btnCloseBookingDrawer'),
        detailSummary: document.getElementById('detailSummaryCards'),
        detailInfo: document.getElementById('detailInfoBlock'),
        detailMedia: document.getElementById('detailMediaGallery'),
        detailTimeline: document.getElementById('detailTimeline'),
        detailHistory: document.getElementById('detailHistory'),
        detailComplaint: document.getElementById('detailComplaint'),
        detailComplaintLink: document.getElementById('detailComplaintLink'),
        detailPayments: document.getElementById('detailPaymentsBody'),
        detailStatusSelect: document.getElementById('detailStatusSelect'),
        detailCancelReason: document.getElementById('detailCancelReasonSelect'),
        detailCancelNote: document.getElementById('detailCancelNoteInput'),
        detailWorkerSelect: document.getElementById('detailWorkerSelect'),
        detailRescheduleDate: document.getElementById('detailRescheduleDate'),
        detailRescheduleSlot: document.getElementById('detailRescheduleSlot'),
        detailLaborCost: document.getElementById('detailLaborCost'),
        detailPartCost: document.getElementById('detailPartCost'),
        detailTravelCost: document.getElementById('detailTravelCost'),
        detailTransportCost: document.getElementById('detailTransportCost'),
        detailPartNote: document.getElementById('detailPartNote'),
        detailPaymentMethod: document.getElementById('detailPaymentMethodSelect'),
        btnUpdateStatus: document.getElementById('btnUpdateBookingStatus'),
        btnAssignWorker: document.getElementById('btnAssignWorker'),
        btnReschedule: document.getElementById('btnRescheduleBooking'),
        btnUpdateCosts: document.getElementById('btnUpdateBookingCost'),
        btnUpdatePaymentMethod: document.getElementById('btnUpdatePaymentMethod'),
        btnConfirmCashPayment: document.getElementById('btnConfirmCashPayment'),
    };

    if (!refs.tableBody) {
        return;
    }

    const state = {
        filters: {
            search: '',
            status: '',
            service_id: '',
            worker_id: '',
            payment: '',
            mode: '',
            priority: '',
            sla: '',
            date_from: '',
            date_to: '',
            view: 'all',
            sort_by: 'created_at',
            sort_dir: 'desc',
            page: 1,
            per_page: 20,
        },
        options: {
            status_options: [],
            service_options: [],
            worker_options: [],
            payment_options: [],
            mode_options: [],
            priority_options: [],
            sla_options: [],
            sort_options: [],
            status_flow: [],
            cancel_reason_options: [],
            time_slots: [],
        },
        items: [],
        summary: {},
        pagination: {
            total: 0,
            per_page: 20,
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
        },
        selected: new Set(),
        activeBookingId: null,
        detail: null,
        searchTimer: null,
        loadingList: false,
        loadingDetail: false,
    };

    const currencyFormatter = new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    });
    const numberFormatter = new Intl.NumberFormat('vi-VN');

    const escapeHtml = (value = '') => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const formatMoney = (value) => currencyFormatter.format(Number(value || 0));
    const formatNumber = (value) => numberFormatter.format(Number(value || 0));

    const formatDateTime = (value) => {
        if (!value) {
            return '--';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return date.toLocaleString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const toneClass = (tone) => ({
        info: 'admin-orders-pill admin-orders-pill--info',
        success: 'admin-orders-pill admin-orders-pill--success',
        warning: 'admin-orders-pill admin-orders-pill--warning',
        danger: 'admin-orders-pill admin-orders-pill--danger',
        muted: 'admin-orders-pill admin-orders-pill--muted',
    }[tone] || 'admin-orders-pill admin-orders-pill--info');

    const buildPill = (label, tone = 'info') => `<span class="${toneClass(tone)}">${escapeHtml(label || '--')}</span>`;

    const parseAmount = (value) => {
        if (value === null || value === undefined || value === '') {
            return 0;
        }

        const normalized = Number(String(value).replaceAll(',', '').trim());
        return Number.isFinite(normalized) ? Math.max(0, normalized) : 0;
    };

    const buildQuery = (extra = {}) => {
        const params = new URLSearchParams();
        const merged = { ...state.filters, ...extra };

        Object.entries(merged).forEach(([key, value]) => {
            if (value === null || value === undefined || value === '') {
                return;
            }

            params.set(key, String(value));
        });

        const query = params.toString();
        return query ? `?${query}` : '';
    };

    const ensureSuccess = (response, fallbackMessage) => {
        if (!response?.ok) {
            throw new Error(response?.data?.message || fallbackMessage);
        }

        return response.data?.data ?? response.data ?? {};
    };

    const setTableLoading = (message = 'Đang tải danh sách đơn...') => {
        refs.tableBody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-5 text-muted">${escapeHtml(message)}</td>
            </tr>
        `;
    };

    const setQuickViewActive = () => {
        const tabs = refs.quickViews?.querySelectorAll('.admin-orders-view-tab') || [];
        tabs.forEach((tab) => {
            tab.classList.toggle('is-active', tab.dataset.view === state.filters.view);
        });
    };

    const mapOption = (item) => {
        if (!item || typeof item !== 'object') {
            return { value: '', label: '' };
        }

        if (Object.hasOwn(item, 'value')) {
            return {
                value: String(item.value ?? ''),
                label: String(item.label ?? ''),
            };
        }

        if (Object.hasOwn(item, 'id')) {
            return {
                value: String(item.id ?? ''),
                label: String(item.name ?? item.label ?? ''),
            };
        }

        return {
            value: String(item.value ?? ''),
            label: String(item.label ?? ''),
        };
    };

    const populateSelect = (select, options, selectedValue, fallbackOption = null) => {
        if (!select) {
            return;
        }

        const normalizedOptions = Array.isArray(options) ? options.map(mapOption) : [];
        const finalOptions = fallbackOption
            ? [mapOption(fallbackOption), ...normalizedOptions]
            : normalizedOptions;

        select.innerHTML = finalOptions
            .map((option) => `<option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>`)
            .join('');

        const nextValue = selectedValue === null || selectedValue === undefined ? '' : String(selectedValue);
        if (nextValue !== '' && !finalOptions.some((option) => option.value === nextValue)) {
            const dynamicOption = document.createElement('option');
            dynamicOption.value = nextValue;
            dynamicOption.textContent = nextValue;
            select.appendChild(dynamicOption);
        }
        select.value = nextValue;
    };

    const closeSlaDropdown = () => {
        if (refs.slaDropdownMenu) {
            refs.slaDropdownMenu.hidden = true;
        }
        if (refs.slaDropdownToggle) {
            refs.slaDropdownToggle.setAttribute('aria-expanded', 'false');
        }
        refs.slaDropdown?.classList.remove('is-open');
    };

    const openSlaDropdown = () => {
        if (!refs.slaDropdownMenu) {
            return;
        }
        refs.slaDropdownMenu.hidden = false;
        if (refs.slaDropdownToggle) {
            refs.slaDropdownToggle.setAttribute('aria-expanded', 'true');
        }
        refs.slaDropdown?.classList.add('is-open');
    };

    const getSlaOptionBadge = (value) => {
        const summary = state.summary || {};
        if (value === 'overdue') {
            return {
                count: Math.max(0, Number(summary.overdue_count || 0)),
                tone: 'danger',
            };
        }
        if (value === 'due_soon') {
            return {
                count: Math.max(0, Number(summary.due_soon_count || 0)),
                tone: 'warning',
            };
        }

        return { count: 0, tone: '' };
    };

    const renderSlaDropdown = () => {
        if (!refs.sla || !refs.slaDropdownMenu || !refs.slaDropdownLabel) {
            return;
        }

        const options = Array.from(refs.sla.options || []).map((option) => ({
            value: String(option.value ?? ''),
            label: String(option.textContent ?? option.label ?? ''),
        }));
        const selectedValue = String(refs.sla.value ?? '');
        const selectedOption = options.find((option) => option.value === selectedValue) || options[0] || { label: 'Tất cả SLA' };

        refs.slaDropdownLabel.textContent = selectedOption.label || 'Tất cả SLA';

        refs.slaDropdownMenu.innerHTML = options.map((option) => {
            const isActive = option.value === selectedValue;
            const badge = getSlaOptionBadge(option.value);
            const badgeHtml = badge.count > 0
                ? `<span class="admin-orders-sla-dropdown__badge ${badge.tone ? `is-${badge.tone}` : ''}">${escapeHtml(formatNumber(badge.count))}</span>`
                : '';

            return `
                <button type="button" class="admin-orders-sla-dropdown__item ${isActive ? 'is-active' : ''}" data-value="${escapeHtml(option.value)}" role="option" aria-selected="${isActive ? 'true' : 'false'}">
                    <span>${escapeHtml(option.label)}</span>
                    ${badgeHtml}
                </button>
            `;
        }).join('');

        if (refs.slaDropdownToggle) {
            refs.slaDropdownToggle.disabled = options.length <= 0;
        }
    };

    const renderFilters = () => {
        populateSelect(refs.status, state.options.status_options, state.filters.status);
        populateSelect(refs.payment, state.options.payment_options, state.filters.payment);
        populateSelect(refs.mode, state.options.mode_options, state.filters.mode);
        populateSelect(refs.priority, state.options.priority_options, state.filters.priority);
        populateSelect(refs.sla, state.options.sla_options, state.filters.sla);
        populateSelect(refs.sortBy, state.options.sort_options, state.filters.sort_by);
        populateSelect(refs.service, state.options.service_options, state.filters.service_id, { value: '', label: 'Tất cả dịch vụ' });
        populateSelect(refs.worker, state.options.worker_options, state.filters.worker_id, { value: '', label: 'Tất cả thợ' });

        if (refs.sortDir) refs.sortDir.value = state.filters.sort_dir;
        if (refs.dateFrom) refs.dateFrom.value = state.filters.date_from;
        if (refs.dateTo) refs.dateTo.value = state.filters.date_to;
        if (refs.search && refs.search.value !== state.filters.search) {
            refs.search.value = state.filters.search;
        }

        renderSlaDropdown();
        setQuickViewActive();
    };

    const renderStats = () => {
        if (!refs.stats) {
            return;
        }

        const summary = state.summary || {};
        const cards = [
            {
                label: 'Tổng đơn',
                value: formatNumber(summary.total_orders || 0),
                note: `Đã lọc ${formatNumber(summary.filtered_count || 0)} đơn`,
            },
            {
                label: 'Quá hạn SLA',
                value: formatNumber(summary.overdue_count || 0),
                note: `${formatNumber(summary.due_soon_count || 0)} đơn sắp quá hạn`,
            },
            {
                label: 'Đúng hạn SLA',
                value: formatNumber(summary.on_track_count || 0),
                note: 'Đơn đang theo đúng tiến độ',
            },
            {
                label: 'Chờ thanh toán',
                value: formatNumber(summary.unpaid_count || 0),
                note: `${formatNumber(summary.payment_issue_count || 0)} đơn lỗi thanh toán`,
            },
            {
                label: 'Khiếu nại',
                value: formatNumber(summary.complaint_count || 0),
                note: 'Đơn có phản ánh của khách',
            },
            {
                label: 'Không liên lạc được',
                value: formatNumber(summary.contact_issue_count || 0),
                note: 'Thợ đã báo admin hỗ trợ liên hệ',
            },
            {
                label: 'Chưa phân công',
                value: formatNumber(summary.unassigned_count || 0),
                note: 'Đơn chưa có thợ phụ trách',
            },
        ];

        refs.stats.innerHTML = cards.map((card) => `
            <article class="admin-orders-stat-card">
                <span class="label">${escapeHtml(card.label)}</span>
                <strong>${escapeHtml(card.value)}</strong>
                <small>${escapeHtml(card.note)}</small>
            </article>
        `).join('');
    };

    const renderSlaAlert = () => {
        const summary = state.summary || {};
        const overdueCount = Math.max(0, Number(summary.overdue_count || 0));
        const dueSoonCount = Math.max(0, Number(summary.due_soon_count || 0));
        const slaRiskCount = overdueCount + dueSoonCount;
        const hasOverdue = overdueCount > 0;
        const hasDueSoon = dueSoonCount > 0;
        if (refs.slaFilterBadge) {
            refs.slaFilterBadge.textContent = formatNumber(slaRiskCount);
            refs.slaFilterBadge.hidden = slaRiskCount <= 0;
            refs.slaFilterBadge.classList.remove('is-warning', 'is-danger');
            if (hasOverdue) {
                refs.slaFilterBadge.classList.add('is-danger');
            } else if (hasDueSoon) {
                refs.slaFilterBadge.classList.add('is-warning');
            }
        }

    };

    const renderFlags = (booking) => {
        const flags = [];

        if (booking?.flags?.is_overdue) {
            flags.push(buildPill('Quá hạn SLA', 'danger'));
        } else if (booking?.sla_state === 'due_soon') {
            flags.push(buildPill('Sắp quá hạn', 'warning'));
        }

        if (booking?.flags?.payment_issue) {
            flags.push(buildPill('Thanh toán lỗi', 'danger'));
        } else if (!(booking?.payment?.is_paid ?? false)) {
            flags.push(buildPill('Chưa thanh toán', 'warning'));
        }

        if (booking?.flags?.has_complaint) {
            flags.push(buildPill('Có khiếu nại', 'danger'));
        }

        if (booking?.flags?.has_worker_contact_issue) {
            flags.push(buildPill('Không liên lạc được', 'danger'));
        }

        if (booking?.flags?.is_unassigned) {
            flags.push(buildPill('Chưa phân công', 'muted'));
        }

        if (!flags.length) {
            return '<span class="admin-orders-row-sub">Không có cảnh báo</span>';
        }

        return `<div class="admin-orders-flag-list">${flags.join('')}</div>`;
    };

    const renderBookingRow = (booking) => {
        const checked = state.selected.has(Number(booking.id)) ? 'checked' : '';
        const complaintLink = booking?.flags?.has_complaint
            ? `<a class="btn btn-sm btn-outline-danger" href="/admin/customer-feedback?search=${encodeURIComponent(booking.code || '')}">Khiếu nại</a>`
            : '';

        return `
            <tr data-booking-id="${escapeHtml(booking.id)}">
                <td class="text-center">
                    <input type="checkbox" data-row-select="${escapeHtml(booking.id)}" ${checked}>
                </td>
                <td>
                    <div class="admin-orders-row-main">${escapeHtml(booking.code || '--')}</div>
                    <div class="admin-orders-row-sub mt-1">
                        ${buildPill(booking.status_label || '--', booking.status_tone || 'info')}
                        ${buildPill(booking.priority_label || '--', booking.priority_tone || 'muted')}
                        ${buildPill(booking.sla_label || '--', booking.sla_tone || 'muted')}
                    </div>
                </td>
                <td>
                    <div class="admin-orders-row-main">${escapeHtml(booking?.customer?.name || 'Khách hàng')}</div>
                    <div class="admin-orders-row-sub">${escapeHtml(booking?.customer?.phone || 'Chưa có SĐT')}</div>
                    <div class="admin-orders-row-sub">${escapeHtml(booking?.customer?.address || 'Chưa có địa chỉ')}</div>
                    <div class="admin-orders-row-sub mt-1">${escapeHtml(booking.mode_label || '--')}</div>
                </td>
                <td>
                    <div class="admin-orders-row-main">${escapeHtml(booking.service_label || 'Chưa xác định dịch vụ')}</div>
                    <div class="admin-orders-row-sub">${escapeHtml(booking.problem_excerpt || 'Khách chưa mô tả sự cố')}</div>
                    <div class="admin-orders-row-sub mt-1">
                        ${escapeHtml(String(booking?.media?.total || 0))} ảnh/video đính kèm
                    </div>
                </td>
                <td>
                    <div class="admin-orders-row-main">${escapeHtml(booking?.worker?.name || 'Chưa phân công')}</div>
                    <div class="admin-orders-row-sub">${escapeHtml(booking?.worker?.phone || 'Không có SĐT')}</div>
                    <div class="admin-orders-row-sub mt-1">${escapeHtml(booking?.schedule?.label || '--')}</div>
                </td>
                <td>
                    <div class="admin-orders-row-sub">Tiền công: <strong>${escapeHtml(formatMoney(booking?.costs?.labor || 0))}</strong></div>
                    <div class="admin-orders-row-sub">Linh kiện: <strong>${escapeHtml(formatMoney(booking?.costs?.parts || 0))}</strong></div>
                    <div class="admin-orders-row-sub">Di chuyển: <strong>${escapeHtml(formatMoney((booking?.costs?.travel || 0) + (booking?.costs?.transport || 0)))}</strong></div>
                    <div class="admin-orders-row-main mt-1">${escapeHtml(formatMoney(booking?.costs?.total || 0))}</div>
                </td>
                <td>
                    <div class="admin-orders-row-sub mb-1">
                        ${buildPill(booking?.payment?.status_label || '--', booking?.payment?.status_tone || 'muted')}
                    </div>
                    <div class="admin-orders-row-sub">${escapeHtml(booking?.payment?.method_label || '--')}</div>
                    <div class="admin-orders-row-sub">${escapeHtml(booking?.payment?.latest_transaction_label || 'Chưa có giao dịch')}</div>
                    <div class="mt-2">${renderFlags(booking)}</div>
                </td>
                <td>
                    <div class="admin-orders-row-sub">Tạo: ${escapeHtml(booking?.milestones?.created_label || '--')}</div>
                    <div class="admin-orders-row-sub">Cập nhật: ${escapeHtml(booking?.milestones?.updated_label || '--')}</div>
                    <div class="admin-orders-row-sub">Hoàn tất: ${escapeHtml(booking?.milestones?.completed_label || '--')}</div>
                    <div class="admin-orders-row-sub">Hủy: ${escapeHtml(booking?.milestones?.cancelled_label || '--')}</div>
                </td>
                <td>
                    <div class="admin-orders-actions">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-action="open-detail" data-id="${escapeHtml(booking.id)}">Chi tiết</button>
                        ${complaintLink}
                    </div>
                </td>
            </tr>
        `;
    };

    const syncSelectAllState = () => {
        if (!refs.selectAll) {
            return;
        }

        const pageIds = state.items.map((booking) => Number(booking.id)).filter(Boolean);
        if (pageIds.length === 0) {
            refs.selectAll.checked = false;
            refs.selectAll.indeterminate = false;
            return;
        }

        const selectedInPage = pageIds.filter((id) => state.selected.has(id)).length;
        refs.selectAll.checked = selectedInPage === pageIds.length;
        refs.selectAll.indeterminate = selectedInPage > 0 && selectedInPage < pageIds.length;
    };

    const renderTable = () => {
        if (!state.items.length) {
            refs.tableBody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">Không có đơn phù hợp với bộ lọc hiện tại.</td>
                </tr>
            `;
            syncSelectAllState();
            return;
        }

        refs.tableBody.innerHTML = state.items.map(renderBookingRow).join('');
        syncSelectAllState();
    };

    const buildPaginationModel = (current, last) => {
        if (last <= 1) {
            return [1];
        }

        const pages = new Set([1, last, current - 1, current, current + 1]);
        const normalized = Array.from(pages)
            .filter((page) => page >= 1 && page <= last)
            .sort((left, right) => left - right);

        const output = [];
        normalized.forEach((page, index) => {
            const previous = normalized[index - 1];
            if (previous && page - previous > 1) {
                output.push('ellipsis');
            }
            output.push(page);
        });

        return output;
    };

    const renderPagination = () => {
        if (!refs.pagination) {
            return;
        }

        const paging = state.pagination || {};
        const current = Number(paging.current_page || 1);
        const last = Number(paging.last_page || 1);
        const from = Number(paging.from || 0);
        const to = Number(paging.to || 0);
        const total = Number(paging.total || 0);
        const pages = buildPaginationModel(current, last);

        refs.pagination.innerHTML = `
            <div class="admin-orders-pagination-meta">
                Hiển thị ${escapeHtml(from)}-${escapeHtml(to)} / ${escapeHtml(total)} đơn
            </div>
            <div class="admin-orders-pagination-pages">
                <button type="button" data-page-action="prev" ${current <= 1 ? 'disabled' : ''}>Trước</button>
                ${pages.map((page) => {
                    if (page === 'ellipsis') {
                        return '<span class="px-1 text-muted">...</span>';
                    }
                    const active = page === current ? 'is-active' : '';
                    return `<button type="button" class="${active}" data-page="${page}">${page}</button>`;
                }).join('')}
                <button type="button" data-page-action="next" ${current >= last ? 'disabled' : ''}>Sau</button>
            </div>
        `;
    };

    const renderBulkBar = () => {
        if (!refs.bulkBar || !refs.bulkSelectedCount) {
            return;
        }

        const count = state.selected.size;
        refs.bulkBar.hidden = count === 0;
        refs.bulkSelectedCount.textContent = String(count);
    };

    const clearSelection = () => {
        state.selected.clear();
        renderBulkBar();
        syncSelectAllState();
        const rowChecks = refs.tableBody.querySelectorAll('input[data-row-select]');
        rowChecks.forEach((checkbox) => {
            checkbox.checked = false;
        });
    };

    const setFilterValue = (key, value, options = {}) => {
        const { resetPage = true, clearSelected = true } = options;
        state.filters[key] = value;
        if (resetPage) {
            state.filters.page = 1;
        }
        if (clearSelected) {
            clearSelection();
        }
    };

    const loadBookings = async ({ silent = false } = {}) => {
        if (state.loadingList) {
            return;
        }

        state.loadingList = true;
        if (!silent) {
            setTableLoading();
        }

        try {
            const response = await callApi(`/admin/bookings${buildQuery()}`, 'GET');
            const payload = ensureSuccess(response, 'Không thể tải danh sách đơn');

            state.items = Array.isArray(payload.items) ? payload.items : [];
            state.summary = payload.summary || {};
            state.pagination = payload.pagination || {
                total: 0,
                per_page: state.filters.per_page,
                current_page: state.filters.page,
                last_page: 1,
                from: 0,
                to: 0,
            };
            state.options = {
                ...state.options,
                ...(payload.filters || {}),
            };
            state.filters.page = Number(state.pagination.current_page || 1);

            renderFilters();
            renderStats();
            renderSlaAlert();
            renderTable();
            renderPagination();
            renderBulkBar();
        } catch (error) {
            console.error('Load admin bookings error:', error);
            setTableLoading('Không thể tải danh sách đơn hàng.');
            showToast(error.message || 'Không thể tải danh sách đơn', 'error');
        } finally {
            state.loadingList = false;
        }
    };

    const lockBodyScroll = (locked) => {
        document.body.style.overflow = locked ? 'hidden' : '';
    };

    const openDrawerShell = () => {
        if (refs.drawerOverlay) {
            refs.drawerOverlay.hidden = false;
        }
        if (refs.drawer) {
            refs.drawer.classList.add('is-open');
            refs.drawer.setAttribute('aria-hidden', 'false');
        }
        lockBodyScroll(true);
    };

    const closeDrawer = () => {
        if (refs.drawerOverlay) {
            refs.drawerOverlay.hidden = true;
        }
        if (refs.drawer) {
            refs.drawer.classList.remove('is-open');
            refs.drawer.setAttribute('aria-hidden', 'true');
        }
        state.activeBookingId = null;
        state.detail = null;
        lockBodyScroll(false);
    };

    const renderDetailLoading = () => {
        if (refs.drawerTitle) refs.drawerTitle.textContent = 'Đang tải...';
        if (refs.detailSummary) refs.detailSummary.innerHTML = '';
        if (refs.detailInfo) refs.detailInfo.innerHTML = '<p class="text-muted mb-0">Đang tải chi tiết đơn...</p>';
        if (refs.detailMedia) refs.detailMedia.innerHTML = '<p class="text-muted mb-0">Đang tải dữ liệu media...</p>';
        if (refs.detailTimeline) refs.detailTimeline.innerHTML = '<p class="text-muted mb-0">Đang tải timeline...</p>';
        if (refs.detailHistory) refs.detailHistory.innerHTML = '<p class="text-muted mb-0">Đang tải lịch sử thao tác...</p>';
        if (refs.detailComplaint) refs.detailComplaint.innerHTML = '<p class="text-muted mb-0">Đang tải thông tin khiếu nại...</p>';
        if (refs.detailPayments) {
            refs.detailPayments.innerHTML = `
                <tr>
                    <td colspan="5" class="text-muted py-3">Đang tải lịch sử thanh toán...</td>
                </tr>
            `;
        }
    };

    const renderDetailKv = (items) => {
        if (!refs.detailInfo) {
            return;
        }

        refs.detailInfo.innerHTML = items.map((item) => `
            <article class="admin-orders-kv-item">
                <span class="label">${escapeHtml(item.label)}</span>
                <span class="value">${escapeHtml(item.value || '--')}</span>
            </article>
        `).join('');
    };

    const renderDetailMedia = (gallery) => {
        if (!refs.detailMedia) {
            return;
        }

        const entries = [];
        (gallery.before_images || []).forEach((url) => entries.push({ kind: 'image', phase: 'Trước sửa', url }));
        (gallery.before_videos || []).forEach((url) => entries.push({ kind: 'video', phase: 'Trước sửa', url }));
        (gallery.after_images || []).forEach((url) => entries.push({ kind: 'image', phase: 'Sau sửa', url }));
        (gallery.after_videos || []).forEach((url) => entries.push({ kind: 'video', phase: 'Sau sửa', url }));

        if (!entries.length) {
            refs.detailMedia.innerHTML = '<p class="text-muted mb-0">Đơn chưa có ảnh/video trước hoặc sau sửa.</p>';
            return;
        }

        refs.detailMedia.innerHTML = entries.map((entry) => `
            <article class="admin-orders-media-item">
                ${entry.kind === 'image'
                    ? `<img src="${escapeHtml(entry.url)}" alt="${escapeHtml(entry.phase)}">`
                    : `<video src="${escapeHtml(entry.url)}" controls preload="metadata"></video>`
                }
                <span class="tag ${toneClass(entry.phase === 'Trước sửa' ? 'warning' : 'success')}">${escapeHtml(entry.phase)}</span>
            </article>
        `).join('');
    };

    const renderDetailTimeline = (timeline) => {
        if (!refs.detailTimeline) {
            return;
        }

        if (!Array.isArray(timeline) || !timeline.length) {
            refs.detailTimeline.innerHTML = '<p class="text-muted mb-0">Không có timeline chi tiết.</p>';
            return;
        }

        refs.detailTimeline.innerHTML = timeline
            .filter((item) => item?.state !== 'hidden')
            .map((item) => `
                <article class="admin-orders-timeline-item">
                    <div class="d-flex justify-content-between gap-2">
                        <strong>${escapeHtml(item.title || '--')}</strong>
                        ${buildPill(item.state === 'done' ? 'Đã xong' : 'Chờ xử lý', item.state === 'done' ? 'success' : 'muted')}
                    </div>
                    <div class="admin-orders-row-sub mt-1">${escapeHtml(item.time_label || formatDateTime(item.time))}</div>
                    <div class="admin-orders-row-sub mt-1">${escapeHtml(item.note || '--')}</div>
                </article>
            `).join('');
    };

    const renderDetailHistory = (history) => {
        if (!refs.detailHistory) {
            return;
        }

        if (!Array.isArray(history) || !history.length) {
            refs.detailHistory.innerHTML = '<p class="text-muted mb-0">Chưa có lịch sử thao tác.</p>';
            return;
        }

        refs.detailHistory.innerHTML = history.map((item) => `
            <article class="admin-orders-history-item">
                <div class="d-flex justify-content-between gap-2">
                    <strong>${escapeHtml(item.title || '--')}</strong>
                    ${buildPill(item.actor || 'Hệ thống', item.tone || 'info')}
                </div>
                <p class="mb-1 mt-1">${escapeHtml(item.detail || '--')}</p>
                <div class="meta">${escapeHtml(item.time_label || formatDateTime(item.time))}</div>
            </article>
        `).join('');
    };

    const renderDetailComplaint = (complaint, complaintUrl = '/admin/customer-feedback') => {
        if (!refs.detailComplaint) {
            return;
        }

        if (!complaint) {
            refs.detailComplaint.innerHTML = '<p class="text-muted mb-0">Đơn này chưa phát sinh khiếu nại.</p>';
            if (refs.detailComplaintLink) {
                refs.detailComplaintLink.href = complaintUrl;
            }
            return;
        }

        const imageList = Array.isArray(complaint.images) ? complaint.images : [];
        const imageHtml = imageList.length
            ? imageList.map((url) => `<a href="${escapeHtml(url)}" target="_blank" rel="noopener" class="admin-orders-link">Ảnh minh chứng</a>`).join(' · ')
            : 'Không có ảnh';
        const videoHtml = complaint.video
            ? `<a href="${escapeHtml(complaint.video)}" target="_blank" rel="noopener" class="admin-orders-link">Xem video minh chứng</a>`
            : 'Không có video';

        refs.detailComplaint.innerHTML = `
            <div class="admin-orders-kv-grid">
                <article class="admin-orders-kv-item">
                    <span class="label">Lý do</span>
                    <span class="value">${escapeHtml(complaint.reason_label || '--')}</span>
                </article>
                <article class="admin-orders-kv-item">
                    <span class="label">Trạng thái</span>
                    <span class="value">${escapeHtml(complaint.status || '--')}</span>
                </article>
                <article class="admin-orders-kv-item">
                    <span class="label">Mức ưu tiên</span>
                    <span class="value">${escapeHtml(complaint.priority || '--')}</span>
                </article>
                <article class="admin-orders-kv-item">
                    <span class="label">Admin xử lý</span>
                    <span class="value">${escapeHtml(complaint.assigned_admin || 'Chưa phân công')}</span>
                </article>
                <article class="admin-orders-kv-item">
                    <span class="label">Nội dung</span>
                    <span class="value">${escapeHtml(complaint.note || 'Khách chưa để lại ghi chú')}</span>
                </article>
                <article class="admin-orders-kv-item">
                    <span class="label">Tạo lúc</span>
                    <span class="value">${escapeHtml(complaint.created_label || formatDateTime(complaint.created_at))}</span>
                </article>
                <article class="admin-orders-kv-item">
                    <span class="label">Ảnh đính kèm</span>
                    <span class="value">${imageHtml}</span>
                </article>
                <article class="admin-orders-kv-item">
                    <span class="label">Video đính kèm</span>
                    <span class="value">${videoHtml}</span>
                </article>
            </div>
        `;

        if (refs.detailComplaintLink) {
            refs.detailComplaintLink.href = complaintUrl;
        }
    };

    const renderDetailPayments = (payments) => {
        if (!refs.detailPayments) {
            return;
        }

        if (!Array.isArray(payments) || !payments.length) {
            refs.detailPayments.innerHTML = `
                <tr>
                    <td colspan="5" class="text-muted py-3">Chưa có giao dịch thanh toán.</td>
                </tr>
            `;
            return;
        }

        refs.detailPayments.innerHTML = payments.map((payment) => `
            <tr>
                <td>${escapeHtml(payment.created_label || formatDateTime(payment.created_at))}</td>
                <td>${escapeHtml(formatMoney(payment.amount || 0))}</td>
                <td>${escapeHtml(payment.method_label || payment.method || '--')}</td>
                <td>${buildPill(payment.status || '--', (payment.status || '').toLowerCase().includes('success') ? 'success' : 'warning')}</td>
                <td>${escapeHtml(payment.transaction_code || '--')}</td>
            </tr>
        `).join('');
    };

    const syncDetailActionOptions = (detail) => {
        const actionOptions = detail.action_options || {};
        const statusFlow = Array.isArray(actionOptions.status_flow) && actionOptions.status_flow.length
            ? actionOptions.status_flow
            : (state.options.status_flow || []);
        const cancelReasons = Array.isArray(actionOptions.cancel_reason_options) && actionOptions.cancel_reason_options.length
            ? actionOptions.cancel_reason_options
            : (state.options.cancel_reason_options || []);
        const workers = Array.isArray(actionOptions.worker_options) && actionOptions.worker_options.length
            ? actionOptions.worker_options
            : (state.options.worker_options || []);
        const timeSlots = Array.isArray(actionOptions.time_slots) && actionOptions.time_slots.length
            ? actionOptions.time_slots
            : (state.options.time_slots || []);

        populateSelect(refs.detailStatusSelect, statusFlow, detail.status_key);
        populateSelect(refs.detailCancelReason, cancelReasons, detail.cancel_reason_code, { value: '', label: 'Lý do hủy (bắt buộc khi hủy)' });
        populateSelect(refs.detailWorkerSelect, workers, detail?.worker?.id ?? '', { value: '', label: 'Chọn thợ' });
        populateSelect(
            refs.detailRescheduleSlot,
            timeSlots.map((slot) => ({ value: slot, label: slot })),
            detail?.schedule?.time_slot || '',
            { value: '', label: 'Chọn khung giờ' }
        );

        if (refs.detailRescheduleDate) refs.detailRescheduleDate.value = detail?.schedule?.date || '';
        if (refs.detailCancelNote) refs.detailCancelNote.value = detail.cancel_note || '';
        if (refs.detailLaborCost) refs.detailLaborCost.value = String(Math.round(detail?.costs?.labor || 0));
        if (refs.detailPartCost) refs.detailPartCost.value = String(Math.round(detail?.costs?.parts || 0));
        if (refs.detailTravelCost) refs.detailTravelCost.value = String(Math.round(detail?.costs?.travel || 0));
        if (refs.detailTransportCost) refs.detailTransportCost.value = String(Math.round(detail?.costs?.transport || 0));
        if (refs.detailPartNote) refs.detailPartNote.value = detail?.cost_details?.part_note || '';
        if (refs.detailPaymentMethod) refs.detailPaymentMethod.value = detail?.payment?.method || 'cod';
    };

    const renderBookingDetail = (detail) => {
        if (refs.drawerTitle) {
            refs.drawerTitle.textContent = `${detail.code || `#${detail.id || '--'}`}`;
        }

        if (refs.detailSummary) {
            refs.detailSummary.innerHTML = `
                <article class="admin-orders-kv-item">
                    <span class="label">Trạng thái</span>
                    <span class="value">${buildPill(detail.status_label || '--', detail.status_tone || 'info')}</span>
                </article>
                <article class="admin-orders-kv-item">
                    <span class="label">Ưu tiên</span>
                    <span class="value">${buildPill(detail.priority_label || '--', detail.priority_tone || 'muted')}</span>
                </article>
                <article class="admin-orders-kv-item">
                    <span class="label">SLA</span>
                    <span class="value">${buildPill(detail.sla_label || '--', detail.sla_tone || 'muted')}</span>
                </article>
                <article class="admin-orders-kv-item">
                    <span class="label">Tổng tiền</span>
                    <span class="value">${escapeHtml(formatMoney(detail?.costs?.total || 0))}</span>
                </article>
            `;
        }

        renderDetailKv([
            { label: 'Mã đơn', value: detail.code },
            { label: 'Khách hàng', value: detail?.customer?.name || 'Khách hàng' },
            { label: 'SĐT khách', value: detail?.customer?.phone || 'Chưa có' },
            { label: 'Địa chỉ', value: detail?.customer?.address || '--' },
            { label: 'Loại đặt lịch', value: detail.mode_label || '--' },
            { label: 'Dịch vụ', value: detail.service_label || '--' },
            { label: 'Mô tả sự cố', value: detail.problem_description || detail.problem_excerpt || '--' },
            { label: 'Ghi chú kỹ thuật', value: detail.technical_note || '--' },
            { label: 'Thợ phụ trách', value: detail?.worker?.name || 'Chưa phân công' },
            { label: 'SĐT thợ', value: detail?.worker?.phone || '--' },
            { label: 'Lịch hẹn', value: detail?.schedule?.label || '--' },
            { label: 'Thanh toán', value: `${detail?.payment?.status_label || '--'} · ${detail?.payment?.method_label || '--'}` },
            { label: 'Hỗ trợ liên hệ', value: detail?.contact_issue?.is_reported ? `${detail.contact_issue.status_label || '--'}${detail.contact_issue.reported_label ? ` - ${detail.contact_issue.reported_label}` : ''}` : 'Chưa có báo cáo' },
            { label: 'Người vừa gọi', value: detail?.contact_issue?.reporter_name || detail?.contact_issue?.reported_by?.name || '--' },
            { label: 'Số đã gọi', value: detail?.contact_issue?.called_phone || '--' },
            { label: 'Ghi chú liên hệ', value: detail?.contact_issue?.note || '--' },
            { label: 'Tạo đơn', value: detail?.milestones?.created_label || '--' },
            { label: 'Cập nhật', value: detail?.milestones?.updated_label || '--' },
        ]);

        renderDetailMedia(detail.media_gallery || detail.media || {});
        renderDetailTimeline(detail.timeline || []);
        renderDetailHistory(detail.action_history || []);
        renderDetailComplaint(detail.complaint_detail, detail?.action_options?.complaint_url || '/admin/customer-feedback');
        renderDetailPayments(detail.payment_history || []);
        syncDetailActionOptions(detail);
    };

    const fetchBookingDetail = async (bookingId, { silent = false } = {}) => {
        if (!bookingId || state.loadingDetail) {
            return;
        }

        state.loadingDetail = true;
        if (!silent) {
            renderDetailLoading();
        }

        try {
            const response = await callApi(`/admin/bookings/${bookingId}`, 'GET');
            const detail = ensureSuccess(response, 'Không thể tải chi tiết đơn');
            state.detail = detail;
            state.activeBookingId = Number(detail.id || bookingId);
            renderBookingDetail(detail);
        } catch (error) {
            console.error('Load booking detail error:', error);
            showToast(error.message || 'Không thể tải chi tiết đơn', 'error');
            if (!silent) {
                renderDetailLoading();
                if (refs.detailInfo) {
                    refs.detailInfo.innerHTML = '<p class="text-danger mb-0">Không thể tải chi tiết đơn hàng.</p>';
                }
            }
        } finally {
            state.loadingDetail = false;
        }
    };

    const openBookingDetail = async (bookingId) => {
        if (!bookingId) {
            return;
        }

        openDrawerShell();
        await fetchBookingDetail(Number(bookingId));
    };

    const refreshAfterDetailAction = async () => {
        await loadBookings({ silent: true });
        if (state.activeBookingId) {
            await fetchBookingDetail(state.activeBookingId, { silent: true });
        }
    };

    const runButtonAction = async (button, handler) => {
        if (!button || button.dataset.loading === '1') {
            return;
        }

        button.dataset.loading = '1';
        button.disabled = true;
        try {
            await handler();
        } finally {
            button.dataset.loading = '0';
            button.disabled = false;
        }
    };

    const updateBookingStatus = async () => {
        if (!state.activeBookingId) {
            showToast('Chưa chọn đơn để cập nhật trạng thái', 'error');
            return;
        }

        const nextStatus = refs.detailStatusSelect?.value || '';
        if (!nextStatus) {
            showToast('Vui lòng chọn trạng thái mới', 'error');
            return;
        }

        const payload = { trang_thai: nextStatus };

        if (nextStatus === 'da_huy') {
            const reasonCode = refs.detailCancelReason?.value || '';
            const cancelNote = refs.detailCancelNote?.value?.trim() || '';

            if (!reasonCode) {
                showToast('Vui lòng chọn lý do hủy đơn', 'error');
                return;
            }

            payload.ma_ly_do_huy = reasonCode;
            if (cancelNote) {
                payload.ly_do_huy = cancelNote;
            }
        }

        const response = await callApi(`/don-dat-lich/${state.activeBookingId}/status`, 'PUT', payload);
        ensureSuccess(response, 'Không thể cập nhật trạng thái đơn');
        showToast(response?.data?.message || 'Cập nhật trạng thái thành công');
        await refreshAfterDetailAction();
    };

    const assignWorkerForBooking = async () => {
        if (!state.activeBookingId) {
            showToast('Chưa chọn đơn để gán thợ', 'error');
            return;
        }

        const workerId = Number(refs.detailWorkerSelect?.value || 0);
        if (!workerId) {
            showToast('Vui lòng chọn thợ kỹ thuật', 'error');
            return;
        }

        const response = await callApi(`/admin/bookings/${state.activeBookingId}/assign-worker`, 'POST', {
            worker_id: workerId,
        });
        ensureSuccess(response, 'Không thể gán thợ cho đơn');
        showToast(response?.data?.message || 'Đã cập nhật thợ phụ trách');
        await refreshAfterDetailAction();
    };

    const rescheduleBooking = async () => {
        if (!state.activeBookingId) {
            showToast('Chưa chọn đơn để đổi lịch', 'error');
            return;
        }

        const date = refs.detailRescheduleDate?.value || '';
        const slot = refs.detailRescheduleSlot?.value || '';

        if (!date || !slot) {
            showToast('Vui lòng chọn ngày và khung giờ cần đổi', 'error');
            return;
        }

        const response = await callApi(`/don-dat-lich/${state.activeBookingId}/reschedule`, 'PUT', {
            ngay_hen: date,
            khung_gio_hen: slot,
        });
        ensureSuccess(response, 'Không thể cập nhật lịch hẹn');
        showToast(response?.data?.message || 'Đã cập nhật lịch hẹn');
        await refreshAfterDetailAction();
    };

    const updateBookingCosts = async () => {
        if (!state.activeBookingId) {
            showToast('Chưa chọn đơn để cập nhật chi phí', 'error');
            return;
        }

        const labor = parseAmount(refs.detailLaborCost?.value);
        const part = parseAmount(refs.detailPartCost?.value);
        const travel = parseAmount(refs.detailTravelCost?.value);
        const transport = parseAmount(refs.detailTransportCost?.value);
        const partNote = refs.detailPartNote?.value?.trim() || '';

        const payload = {
            tien_cong: labor,
            phi_linh_kien: part,
            phi_di_lai: travel,
            tien_thue_xe: transport,
            ghi_chu_linh_kien: partNote,
            chi_tiet_tien_cong: labor > 0
                ? [{ noi_dung: 'Tiền công sửa chữa', so_tien: labor }]
                : [],
            chi_tiet_linh_kien: part > 0
                ? [{
                    noi_dung: 'Linh kiện thay thế',
                    don_gia: part,
                    so_luong: 1,
                    so_tien: part,
                    bao_hanh_thang: null,
                }]
                : [],
        };

        const response = await callApi(`/admin/bookings/${state.activeBookingId}/financials`, 'PUT', payload);
        ensureSuccess(response, 'Không thể cập nhật chi phí');
        showToast(response?.data?.message || 'Đã cập nhật chi phí đơn');
        await refreshAfterDetailAction();
    };

    const updatePaymentMethod = async () => {
        if (!state.activeBookingId) {
            showToast('Chưa chọn đơn để cập nhật thanh toán', 'error');
            return;
        }

        const paymentMethod = refs.detailPaymentMethod?.value || 'cod';
        const response = await callApi(`/bookings/${state.activeBookingId}/payment-method`, 'PUT', {
            phuong_thuc_thanh_toan: paymentMethod,
        });
        ensureSuccess(response, 'Không thể cập nhật phương thức thanh toán');
        showToast(response?.data?.message || 'Đã cập nhật phương thức thanh toán');
        await refreshAfterDetailAction();
    };

    const confirmCashPayment = async () => {
        if (!state.activeBookingId) {
            showToast('Chưa chọn đơn để xác nhận tiền mặt', 'error');
            return;
        }

        const shouldContinue = window.confirm('Xác nhận đã thu tiền mặt cho đơn này?');
        if (!shouldContinue) {
            return;
        }

        const response = await callApi(`/bookings/${state.activeBookingId}/confirm-cash-payment`, 'POST');
        ensureSuccess(response, 'Không thể xác nhận thanh toán tiền mặt');
        showToast(response?.data?.message || 'Đã xác nhận thanh toán tiền mặt');
        await refreshAfterDetailAction();
    };

    const exportBookings = async (ids = []) => {
        const extra = ids.length ? { ids: ids.join(',') } : {};
        const query = buildQuery(extra);
        await downloadApiFile(`/admin/bookings/export${query}`, 'admin-bookings.csv');
    };

    const bulkAssignWorker = async () => {
        const selectedIds = Array.from(state.selected);
        if (!selectedIds.length) {
            showToast('Vui lòng chọn ít nhất một đơn', 'error');
            return;
        }

        const workers = Array.isArray(state.options.worker_options) ? state.options.worker_options : [];
        if (!workers.length) {
            showToast('Hiện chưa có thợ khả dụng để gán', 'error');
            return;
        }

        const workerHint = workers
            .slice(0, 12)
            .map((worker) => `${worker.id}: ${worker.name}${worker.phone ? ` (${worker.phone})` : ''}`)
            .join('\n');
        const raw = window.prompt(`Nhập ID thợ để gán cho ${selectedIds.length} đơn:\n${workerHint}`);
        if (raw === null) {
            return;
        }

        const workerId = Number(raw.trim());
        if (!Number.isFinite(workerId) || workerId <= 0) {
            showToast('ID thợ không hợp lệ', 'error');
            return;
        }

        let successCount = 0;
        let failedCount = 0;

        for (const bookingId of selectedIds) {
            try {
                const response = await callApi(`/admin/bookings/${bookingId}/assign-worker`, 'POST', { worker_id: workerId });
                if (response?.ok) {
                    successCount += 1;
                } else {
                    failedCount += 1;
                }
            } catch {
                failedCount += 1;
            }
        }

        if (successCount > 0) {
            showToast(`Đã gán thợ cho ${successCount}/${selectedIds.length} đơn`);
        }
        if (failedCount > 0) {
            showToast(`${failedCount} đơn không thể gán thợ`, 'error');
        }

        clearSelection();
        await loadBookings({ silent: true });
        if (state.activeBookingId) {
            await fetchBookingDetail(state.activeBookingId, { silent: true });
        }
    };

    const bulkChangeStatus = async () => {
        const selectedIds = Array.from(state.selected);
        if (!selectedIds.length) {
            showToast('Vui lòng chọn ít nhất một đơn', 'error');
            return;
        }

        const statusFlow = Array.isArray(state.options.status_flow) ? state.options.status_flow : [];
        if (!statusFlow.length) {
            showToast('Không có danh sách trạng thái hợp lệ', 'error');
            return;
        }

        const statusHint = statusFlow.map((item) => `${item.value}: ${item.label}`).join('\n');
        const rawStatus = window.prompt(`Nhập mã trạng thái cần cập nhật cho ${selectedIds.length} đơn:\n${statusHint}`);
        if (rawStatus === null) {
            return;
        }

        const nextStatus = rawStatus.trim();
        if (!statusFlow.some((item) => item.value === nextStatus)) {
            showToast('Trạng thái không hợp lệ', 'error');
            return;
        }

        let cancelReason = '';
        let cancelNote = '';
        if (nextStatus === 'da_huy') {
            const cancelOptions = Array.isArray(state.options.cancel_reason_options)
                ? state.options.cancel_reason_options
                : [];
            const reasonHint = cancelOptions.map((item) => `${item.value}: ${item.label}`).join('\n');
            const reasonRaw = window.prompt(`Nhập mã lý do hủy:\n${reasonHint}`);
            if (reasonRaw === null) {
                return;
            }
            cancelReason = reasonRaw.trim();
            if (!cancelOptions.some((item) => item.value === cancelReason)) {
                showToast('Lý do hủy không hợp lệ', 'error');
                return;
            }
            cancelNote = window.prompt('Ghi chú hủy (có thể để trống):')?.trim() || '';
        }

        let successCount = 0;
        let failedCount = 0;

        for (const bookingId of selectedIds) {
            const payload = { trang_thai: nextStatus };
            if (nextStatus === 'da_huy') {
                payload.ma_ly_do_huy = cancelReason;
                if (cancelNote) {
                    payload.ly_do_huy = cancelNote;
                }
            }

            try {
                const response = await callApi(`/don-dat-lich/${bookingId}/status`, 'PUT', payload);
                if (response?.ok) {
                    successCount += 1;
                } else {
                    failedCount += 1;
                }
            } catch {
                failedCount += 1;
            }
        }

        if (successCount > 0) {
            showToast(`Đã cập nhật trạng thái ${successCount}/${selectedIds.length} đơn`);
        }
        if (failedCount > 0) {
            showToast(`${failedCount} đơn cập nhật trạng thái thất bại`, 'error');
        }

        clearSelection();
        await loadBookings({ silent: true });
        if (state.activeBookingId) {
            await fetchBookingDetail(state.activeBookingId, { silent: true });
        }
    };

    refs.quickViews?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-view]');
        if (!button) {
            return;
        }

        const view = button.dataset.view || 'all';
        if (state.filters.view === view) {
            return;
        }

        setFilterValue('view', view);
        setQuickViewActive();
        loadBookings({ silent: true });
    });

    refs.search?.addEventListener('input', (event) => {
        const value = event.target.value.trim();
        if (state.searchTimer) {
            clearTimeout(state.searchTimer);
        }

        state.searchTimer = window.setTimeout(() => {
            setFilterValue('search', value);
            loadBookings({ silent: true });
        }, 280);
    });

    refs.status?.addEventListener('change', (event) => {
        setFilterValue('status', event.target.value);
        loadBookings({ silent: true });
    });

    refs.service?.addEventListener('change', (event) => {
        setFilterValue('service_id', event.target.value);
        loadBookings({ silent: true });
    });

    refs.worker?.addEventListener('change', (event) => {
        setFilterValue('worker_id', event.target.value);
        loadBookings({ silent: true });
    });

    refs.payment?.addEventListener('change', (event) => {
        setFilterValue('payment', event.target.value);
        loadBookings({ silent: true });
    });

    refs.mode?.addEventListener('change', (event) => {
        setFilterValue('mode', event.target.value);
        loadBookings({ silent: true });
    });

    refs.priority?.addEventListener('change', (event) => {
        setFilterValue('priority', event.target.value);
        loadBookings({ silent: true });
    });

    refs.slaDropdownToggle?.addEventListener('click', (event) => {
        event.preventDefault();
        if (refs.slaDropdownMenu?.hidden) {
            openSlaDropdown();
            return;
        }
        closeSlaDropdown();
    });

    refs.slaDropdownMenu?.addEventListener('click', (event) => {
        const optionButton = event.target.closest('[data-value]');
        if (!optionButton || !refs.sla) {
            return;
        }

        const nextValue = optionButton.getAttribute('data-value') ?? '';
        if (refs.sla.value === nextValue) {
            closeSlaDropdown();
            return;
        }

        refs.sla.value = nextValue;
        refs.sla.dispatchEvent(new Event('change', { bubbles: true }));
    });

    refs.sla?.addEventListener('change', (event) => {
        setFilterValue('sla', event.target.value);
        closeSlaDropdown();
        loadBookings({ silent: true });
    });

    refs.dateFrom?.addEventListener('change', (event) => {
        setFilterValue('date_from', event.target.value);
        loadBookings({ silent: true });
    });

    refs.dateTo?.addEventListener('change', (event) => {
        setFilterValue('date_to', event.target.value);
        loadBookings({ silent: true });
    });

    refs.sortBy?.addEventListener('change', (event) => {
        setFilterValue('sort_by', event.target.value);
        loadBookings({ silent: true });
    });

    refs.sortDir?.addEventListener('change', (event) => {
        setFilterValue('sort_dir', event.target.value);
        loadBookings({ silent: true });
    });

    refs.refresh?.addEventListener('click', () => {
        loadBookings();
    });

    refs.exportCsv?.addEventListener('click', async () => {
        try {
            await exportBookings();
            showToast('Đã xuất danh sách đơn hàng');
        } catch (error) {
            console.error('Export bookings error:', error);
            showToast(error.message || 'Không thể xuất CSV', 'error');
        }
    });

    refs.selectAll?.addEventListener('change', (event) => {
        const pageIds = state.items.map((item) => Number(item.id)).filter(Boolean);
        if (event.target.checked) {
            pageIds.forEach((id) => state.selected.add(id));
        } else {
            pageIds.forEach((id) => state.selected.delete(id));
        }

        renderBulkBar();
        syncSelectAllState();
        const rowChecks = refs.tableBody.querySelectorAll('input[data-row-select]');
        rowChecks.forEach((checkbox) => {
            checkbox.checked = event.target.checked;
        });
    });

    refs.tableBody.addEventListener('change', (event) => {
        const checkbox = event.target.closest('input[data-row-select]');
        if (!checkbox) {
            return;
        }

        const bookingId = Number(checkbox.getAttribute('data-row-select'));
        if (!bookingId) {
            return;
        }

        if (checkbox.checked) {
            state.selected.add(bookingId);
        } else {
            state.selected.delete(bookingId);
        }

        renderBulkBar();
        syncSelectAllState();
    });

    refs.tableBody.addEventListener('click', (event) => {
        const detailButton = event.target.closest('[data-action="open-detail"]');
        if (!detailButton) {
            return;
        }

        const bookingId = Number(detailButton.dataset.id || 0);
        if (bookingId > 0) {
            openBookingDetail(bookingId);
        }
    });

    refs.pagination?.addEventListener('click', (event) => {
        const pageButton = event.target.closest('[data-page]');
        if (pageButton) {
            const page = Number(pageButton.getAttribute('data-page'));
            if (Number.isFinite(page) && page > 0 && page !== state.filters.page) {
                state.filters.page = page;
                loadBookings({ silent: true });
            }
            return;
        }

        const actionButton = event.target.closest('[data-page-action]');
        if (!actionButton) {
            return;
        }

        const action = actionButton.getAttribute('data-page-action');
        if (action === 'prev' && state.filters.page > 1) {
            state.filters.page -= 1;
            loadBookings({ silent: true });
        }
        if (action === 'next' && state.filters.page < Number(state.pagination.last_page || 1)) {
            state.filters.page += 1;
            loadBookings({ silent: true });
        }
    });

    refs.bulkAssign?.addEventListener('click', async () => {
        await runButtonAction(refs.bulkAssign, bulkAssignWorker);
    });

    refs.bulkStatus?.addEventListener('click', async () => {
        await runButtonAction(refs.bulkStatus, bulkChangeStatus);
    });

    refs.bulkExport?.addEventListener('click', async () => {
        const selectedIds = Array.from(state.selected);
        if (!selectedIds.length) {
            showToast('Vui lòng chọn ít nhất một đơn để export', 'error');
            return;
        }

        try {
            await exportBookings(selectedIds);
            showToast(`Đã xuất ${selectedIds.length} đơn đã chọn`);
        } catch (error) {
            console.error('Export selected bookings error:', error);
            showToast(error.message || 'Không thể export danh sách đã chọn', 'error');
        }
    });

    refs.bulkClear?.addEventListener('click', () => {
        clearSelection();
    });

    refs.drawerClose?.addEventListener('click', closeDrawer);
    refs.drawerOverlay?.addEventListener('click', closeDrawer);

    document.addEventListener('click', (event) => {
        if (refs.slaDropdown?.contains(event.target)) {
            return;
        }
        closeSlaDropdown();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        closeSlaDropdown();
        if (refs.drawer?.classList.contains('is-open')) {
            closeDrawer();
        }
    });

    refs.btnUpdateStatus?.addEventListener('click', async () => {
        await runButtonAction(refs.btnUpdateStatus, updateBookingStatus);
    });

    refs.btnAssignWorker?.addEventListener('click', async () => {
        await runButtonAction(refs.btnAssignWorker, assignWorkerForBooking);
    });

    refs.btnReschedule?.addEventListener('click', async () => {
        await runButtonAction(refs.btnReschedule, rescheduleBooking);
    });

    refs.btnUpdateCosts?.addEventListener('click', async () => {
        await runButtonAction(refs.btnUpdateCosts, updateBookingCosts);
    });

    refs.btnUpdatePaymentMethod?.addEventListener('click', async () => {
        await runButtonAction(refs.btnUpdatePaymentMethod, updatePaymentMethod);
    });

    refs.btnConfirmCashPayment?.addEventListener('click', async () => {
        await runButtonAction(refs.btnConfirmCashPayment, confirmCashPayment);
    });

    loadBookings();
});




