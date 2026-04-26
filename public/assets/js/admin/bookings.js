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
        detailReadonlyNotice: document.getElementById('detailReadonlyNotice'),
        detailActionsGrid: document.getElementById('detailActionsGrid'),
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
        detailPartCatalogSearch: document.getElementById('detailPartCatalogSearch'),
        detailPartCatalogSuggestions: document.getElementById('detailPartCatalogSuggestions'),
        detailPartCatalogResults: document.getElementById('detailPartCatalogResults'),
        detailPartCatalogStatus: document.getElementById('detailPartCatalogStatus'),
        detailPartItemsEditor: document.getElementById('detailPartItemsEditor'),
        detailPartNote: document.getElementById('detailPartNote'),
        detailPaymentMethod: document.getElementById('detailPaymentMethodSelect'),
        btnUpdateStatus: document.getElementById('btnUpdateBookingStatus'),
        btnAssignWorker: document.getElementById('btnAssignWorker'),
        btnReschedule: document.getElementById('btnRescheduleBooking'),
        btnUpdateCosts: document.getElementById('btnUpdateBookingCost'),
        btnAddBookingPartRow: document.getElementById('btnAddBookingPartRow'),
        btnAddManualBookingPartRow: document.getElementById('btnAddManualBookingPartRow'),
        btnUpdatePaymentMethod: document.getElementById('btnUpdatePaymentMethod'),
        btnConfirmCashPayment: document.getElementById('btnConfirmCashPayment'),
        btnToggleMoreFilters: document.getElementById('btnToggleMoreFilters'),
        moreFiltersSection: document.getElementById('moreFiltersSection'),
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
        partCatalog: {
            items: [],
            cache: new Map(),
            fallbackItems: [],
            fallbackCache: new Map(),
            selectedIds: new Set(),
            activeSuggestionIndex: -1,
            requestId: 0,
            searchRequestId: 0,
            serviceIds: [],
        },
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
    const initialUrlParams = new URLSearchParams(window.location.search);
    const requestedBookingId = Number(initialUrlParams.get('booking') || 0);
    let hasAppliedInitialBooking = false;

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

    const parseInteger = (value, minimum = 0, fallback = minimum) => {
        if (value === null || value === undefined || value === '') {
            return fallback;
        }

        const normalized = Number(String(value).trim());
        if (!Number.isFinite(normalized)) {
            return fallback;
        }

        return Math.max(minimum, Math.round(normalized));
    };

    const completedBookingStatuses = new Set(['da_xong', 'hoan_thanh']);
    const isCompletedBookingStatus = (status) => completedBookingStatuses.has(String(status || '').trim());
    const isLockedBookingDetail = (detail = state.detail) => isCompletedBookingStatus(detail?.status_key || detail?.status || detail?.trang_thai);

    const setMutationLockState = (control, isLocked) => {
        if (!control) {
            return;
        }

        control.dataset.mutationLocked = isLocked ? '1' : '0';
        control.disabled = Boolean(isLocked);
    };

    const buildWarrantyOptionsMarkup = (value = '', { placeholder = 'Bao hanh' } = {}) => {
        const normalizedValue = value === '' ? '' : Math.max(0, Math.trunc(parseAmount(value)));
        const presets = ['', 0, 1, 3, 6, 12, 24];

        if (normalizedValue !== '' && !presets.includes(normalizedValue)) {
            presets.push(normalizedValue);
        }

        return presets
            .filter((option, index, array) => array.indexOf(option) === index)
            .sort((left, right) => {
                if (left === '') {
                    return -1;
                }

                if (right === '') {
                    return 1;
                }

                return Number(left) - Number(right);
            })
            .map((option) => {
                const selected = option === normalizedValue ? 'selected' : '';
                const label = option === ''
                    ? placeholder
                    : option === 0
                        ? '0 Thang'
                        : `${option} Thang`;

                return `<option value="${option}" ${selected}>${label}</option>`;
            })
            .join('');
    };

    const buildDetailPartItem = (item = {}) => {
        const quantity = parseInteger(item?.so_luong, 1, 1);
        const total = parseAmount(item?.so_tien);
        const unitPrice = parseAmount(item?.don_gia);
        const warrantyMonths = item?.bao_hanh_thang === null || item?.bao_hanh_thang === undefined || item?.bao_hanh_thang === ''
            ? null
            : parseInteger(item?.bao_hanh_thang, 0, 0);

        return {
            noi_dung: String(item?.noi_dung || '').trim(),
            so_luong: quantity,
            don_gia: unitPrice > 0 ? unitPrice : (quantity > 0 ? total / quantity : total),
            so_tien: total > 0 ? total : (unitPrice * quantity),
            bao_hanh_thang: warrantyMonths,
        };
    };

    const buildDetailPartRowMarkup = (item = {}) => {
        const normalizedItem = buildDetailPartItem(item);

        return `
            <div class="admin-orders-part-row">
                <label>
                    <span>Nội dung linh kiện</span>
                    <input type="text" class="form-control js-booking-part-name" maxlength="255" value="${escapeHtml(normalizedItem.noi_dung)}" placeholder="Ví dụ: Tụ quạt dàn nóng">
                </label>
                <label>
                    <span>Số lượng</span>
                    <input type="number" class="form-control js-booking-part-qty" min="1" max="999" step="1" value="${escapeHtml(normalizedItem.so_luong)}">
                </label>
                <label>
                    <span>Đơn giá</span>
                    <input type="number" class="form-control js-booking-part-price" min="0" step="1000" value="${escapeHtml(Math.round(normalizedItem.don_gia || 0))}">
                </label>
                <label>
                    <span>Bảo hành</span>
                    <input type="number" class="form-control js-booking-part-warranty" min="0" max="60" step="1" placeholder="Tháng" value="${escapeHtml(normalizedItem.bao_hanh_thang ?? '')}">
                </label>
                <div class="admin-orders-part-row__meta">
                    <span>Tạm tính</span>
                    <strong class="js-booking-part-total" data-value="${escapeHtml(normalizedItem.so_tien)}">${escapeHtml(formatMoney(normalizedItem.so_tien))}</strong>
                </div>
                <button type="button" class="admin-orders-part-row__remove js-remove-booking-part-row">Xóa</button>
            </div>
        `;
    };

    const collectDetailPartItems = () => {
        if (!refs.detailPartItemsEditor) {
            return [];
        }

        return Array.from(refs.detailPartItemsEditor.querySelectorAll('.admin-orders-part-row'))
            .map((row) => buildDetailPartItem({
                noi_dung: row.querySelector('.js-booking-part-name')?.value || '',
                so_luong: row.querySelector('.js-booking-part-qty')?.value || 1,
                don_gia: row.querySelector('.js-booking-part-price')?.value || 0,
                bao_hanh_thang: row.querySelector('.js-booking-part-warranty')?.value || null,
                so_tien: row.querySelector('.js-booking-part-total')?.dataset.value || 0,
            }))
            .filter((item) => item.noi_dung !== '' || item.so_tien > 0 || item.don_gia > 0);
    };

    const syncDetailPartCost = () => {
        if (!refs.detailPartItemsEditor) {
            return 0;
        }

        let grandTotal = 0;

        Array.from(refs.detailPartItemsEditor.querySelectorAll('.admin-orders-part-row')).forEach((row) => {
            const quantityInput = row.querySelector('.js-booking-part-qty');
            const priceInput = row.querySelector('.js-booking-part-price');
            const warrantyInput = row.querySelector('.js-booking-part-warranty');
            const totalNode = row.querySelector('.js-booking-part-total');

            const quantity = parseInteger(quantityInput?.value, 1, 1);
            const unitPrice = parseAmount(priceInput?.value);
            const warrantyMonths = warrantyInput?.value === '' ? '' : String(parseInteger(warrantyInput?.value, 0, 0));
            const lineTotal = quantity * unitPrice;

            if (quantityInput) {
                quantityInput.value = String(quantity);
            }
            if (priceInput) {
                priceInput.value = unitPrice > 0 ? String(Math.round(unitPrice)) : '0';
            }
            if (warrantyInput) {
                warrantyInput.value = warrantyMonths;
            }
            if (totalNode) {
                totalNode.dataset.value = String(lineTotal);
                totalNode.textContent = formatMoney(lineTotal);
            }

            grandTotal += lineTotal;
        });

        if (refs.detailPartCost) {
            refs.detailPartCost.value = String(Math.round(grandTotal));
        }

        return grandTotal;
    };

    const renderDetailPartItems = (items = []) => {
        if (!refs.detailPartItemsEditor) {
            return;
        }

        const normalizedItems = Array.isArray(items)
            ? items
                .map(buildDetailPartItem)
                .filter((item) => item.noi_dung !== '' || item.so_tien > 0 || item.don_gia > 0)
            : [];

        if (!normalizedItems.length) {
            refs.detailPartItemsEditor.innerHTML = `
                <div class="admin-orders-part-editor__empty">
                    Chưa có dòng linh kiện. Nhấn "Thêm linh kiện" để bổ sung.
                </div>
            `;
            if (refs.detailPartCost) {
                refs.detailPartCost.value = '0';
            }
            return;
        }

        refs.detailPartItemsEditor.innerHTML = normalizedItems.map((item) => buildDetailPartRowMarkup(item)).join('');
        syncDetailPartCost();
    };

    const getAdminPartCatalogKeyword = () => String(refs.detailPartCatalogSearch?.value || '').trim().toLocaleLowerCase('vi-VN');
    const getAdminPartCatalogItemName = (item) => String(item?.ten_linh_kien || '').trim();
    const getAdminPartCatalogServiceName = (item) => String(item?.service_name || item?.dich_vu?.ten_dich_vu || state.detail?.service_label || 'Danh muc').trim();

    const normalizeAdminPartItem = (item = {}) => {
        const quantity = parseInteger(item?.so_luong, 1, 1);
        const total = parseAmount(item?.so_tien);
        const unitPrice = parseAmount(item?.don_gia);
        const warrantyMonths = item?.bao_hanh_thang === null || item?.bao_hanh_thang === undefined || item?.bao_hanh_thang === ''
            ? null
            : parseInteger(item?.bao_hanh_thang, 0, 0);
        const catalogPartId = parseInteger(item?.linh_kien_id, 0, 0);
        const serviceId = parseInteger(item?.dich_vu_id, 0, 0);

        return {
            linh_kien_id: catalogPartId > 0 ? catalogPartId : null,
            dich_vu_id: serviceId > 0 ? serviceId : null,
            service_name: String(item?.service_name || item?.dich_vu?.ten_dich_vu || '').trim(),
            hinh_anh: String(item?.hinh_anh || '').trim(),
            noi_dung: String(item?.noi_dung || item?.ten_linh_kien || '').trim(),
            so_luong: quantity,
            don_gia: unitPrice > 0 ? unitPrice : (quantity > 0 ? total / quantity : total),
            so_tien: total > 0 ? total : (unitPrice * quantity),
            bao_hanh_thang: warrantyMonths,
        };
    };

    const buildAdminPartRowMarkup = (item = {}) => {
        const normalizedItem = normalizeAdminPartItem(item);
        const isCatalogItem = normalizedItem.linh_kien_id !== null;
        const amountValue = normalizedItem.don_gia > 0 ? normalizedItem.don_gia : '';
        const quantityValue = normalizedItem.so_luong > 0 ? normalizedItem.so_luong : 1;
        const warrantyValue = normalizedItem.bao_hanh_thang ?? '';
        const metaText = normalizedItem.service_name || (isCatalogItem ? 'Tu danh muc linh kien' : 'Tu nhap thu cong');

        return `
            <div class="dispatch-line-item dispatch-pricing-v2-part-card admin-orders-part-row" data-line-type="part" data-catalog-part-id="${escapeHtml(normalizedItem.linh_kien_id || '')}">
                <input type="hidden" class="js-line-part-id" value="${escapeHtml(normalizedItem.linh_kien_id || '')}">
                <input type="hidden" class="js-line-service-id" value="${escapeHtml(normalizedItem.dich_vu_id || '')}">
                <input type="hidden" class="js-line-image" value="${escapeHtml(normalizedItem.hinh_anh || '')}">
                <input type="hidden" class="js-line-service-name" value="${escapeHtml(normalizedItem.service_name || '')}">
                <div class="dispatch-pricing-v2-part-card-inner">
                    <div class="dispatch-pricing-v2-part-main">
                        <div class="dispatch-pricing-v2-field-label">Ten linh kien / Vat tu</div>
                        <input type="text" class="dispatch-pricing-v2-input-dark js-line-description dispatch-pricing-v2-inline-input dispatch-pricing-v2-part-title" value="${escapeHtml(normalizedItem.noi_dung)}" placeholder="Bo mach chu Samsung" ${isCatalogItem ? 'readonly' : ''}>
                        <div class="dispatch-pricing-v2-part-meta">${escapeHtml(metaText)}</div>
                    </div>
                    <div class="dispatch-pricing-v2-part-col">
                        <div class="dispatch-pricing-v2-field-label">Don gia (d)</div>
                        <input type="number" class="dispatch-pricing-v2-input-dark js-line-amount dispatch-pricing-v2-inline-input dispatch-pricing-v2-inline-input--price" value="${escapeHtml(amountValue)}" placeholder="650000" ${isCatalogItem ? 'readonly' : ''}>
                    </div>
                    <div class="dispatch-pricing-v2-part-col">
                        <div class="dispatch-pricing-v2-field-label">So luong</div>
                        <div class="dispatch-pricing-v2-stepper">
                            <button type="button" class="dispatch-pricing-v2-stepper-btn js-quantity-step" data-step="-1" aria-label="Giam so luong">
                                <span class="material-symbols-outlined" style="font-size: 14px;">remove</span>
                            </button>
                            <input type="number" class="dispatch-pricing-v2-input-dark js-line-quantity dispatch-pricing-v2-inline-input" min="1" step="1" value="${escapeHtml(quantityValue)}" placeholder="1">
                            <button type="button" class="dispatch-pricing-v2-stepper-btn js-quantity-step" data-step="1" aria-label="Tang so luong">
                                <span class="material-symbols-outlined" style="font-size: 14px;">add</span>
                            </button>
                        </div>
                    </div>
                    <div class="dispatch-pricing-v2-part-col">
                        <div class="dispatch-pricing-v2-field-label">Bao hanh</div>
                        <select class="js-line-warranty dispatch-pricing-v2-select">
                            ${buildWarrantyOptionsMarkup(warrantyValue)}
                        </select>
                    </div>
                    <button type="button" class="dispatch-pricing-v2-part-remove dispatch-line-item__remove js-remove-booking-part-row" aria-label="Xoa dong">
                        <span class="material-symbols-outlined" style="font-size: 14px;">delete</span>
                    </button>
                </div>
            </div>
        `;
    };

    const collectAdminPartItems = () => {
        if (!refs.detailPartItemsEditor) {
            return [];
        }

        return Array.from(refs.detailPartItemsEditor.querySelectorAll('.dispatch-line-item'))
            .map((row) => normalizeAdminPartItem({
                linh_kien_id: row.querySelector('.js-line-part-id')?.value || '',
                dich_vu_id: row.querySelector('.js-line-service-id')?.value || '',
                service_name: row.querySelector('.js-line-service-name')?.value || '',
                hinh_anh: row.querySelector('.js-line-image')?.value || '',
                noi_dung: row.querySelector('.js-line-description')?.value || '',
                so_luong: row.querySelector('.js-line-quantity')?.value || 1,
                don_gia: row.querySelector('.js-line-amount')?.value || 0,
                bao_hanh_thang: row.querySelector('.js-line-warranty')?.value || null,
                so_tien: parseAmount(row.querySelector('.js-line-amount')?.value || 0) * Math.max(1, parseInteger(row.querySelector('.js-line-quantity')?.value || 1, 1, 1)),
            }))
            .filter((item) => item.noi_dung !== '' || item.so_tien > 0 || item.don_gia > 0);
    };

    const syncAdminPartCost = () => {
        if (!refs.detailPartItemsEditor) {
            return 0;
        }

        let grandTotal = 0;

        Array.from(refs.detailPartItemsEditor.querySelectorAll('.dispatch-line-item')).forEach((row) => {
            const quantityInput = row.querySelector('.js-line-quantity');
            const priceInput = row.querySelector('.js-line-amount');
            const warrantyInput = row.querySelector('.js-line-warranty');

            const quantity = parseInteger(quantityInput?.value, 1, 1);
            const unitPrice = parseAmount(priceInput?.value);
            const warrantyMonths = warrantyInput?.value === '' ? '' : String(parseInteger(warrantyInput?.value, 0, 0));
            const lineTotal = quantity * unitPrice;

            if (quantityInput) {
                quantityInput.value = String(quantity);
            }
            if (priceInput) {
                priceInput.value = unitPrice > 0 ? String(Math.round(unitPrice)) : '0';
            }
            if (warrantyInput) {
                warrantyInput.value = warrantyMonths;
            }

            grandTotal += lineTotal;
        });

        if (refs.detailPartCost) {
            refs.detailPartCost.value = String(Math.round(grandTotal));
        }

        return grandTotal;
    };

    const renderAdminPartItems = (items = []) => {
        if (!refs.detailPartItemsEditor) {
            return;
        }

        refs.detailPartItemsEditor.classList.add('dispatch-pricing-v2-parts-list');

        const normalizedItems = Array.isArray(items)
            ? items
                .map(normalizeAdminPartItem)
                .filter((item) => item.noi_dung !== '' || item.so_tien > 0 || item.don_gia > 0)
            : [];

        if (!normalizedItems.length) {
            refs.detailPartItemsEditor.innerHTML = `
                <div class="admin-orders-part-editor__empty dispatch-part-suggestion-empty">
                    Chua co dong linh kien. Chon o danh muc ben tren hoac bam "Them tay".
                </div>
            `;
            if (refs.detailPartCost) {
                refs.detailPartCost.value = '0';
            }
            syncAdminPartEditorLock();
            return;
        }

        refs.detailPartItemsEditor.innerHTML = normalizedItems.map((item) => buildAdminPartRowMarkup(item)).join('');
        syncAdminPartCost();
        syncAdminPartEditorLock();
    };

    const updateAdminPartAddButtonState = () => {
        if (!refs.btnAddBookingPartRow) {
            return;
        }

        const isLocked = isLockedBookingDetail();
        const selectedCount = state.partCatalog.selectedIds.size;
        refs.btnAddBookingPartRow.disabled = isLocked || selectedCount === 0;
        refs.btnAddBookingPartRow.innerHTML = `
            <span class="material-symbols-outlined">playlist_add</span>
            ${selectedCount > 0 ? `Them ${selectedCount} linh kien` : 'Them linh kien da chon'}
        `;

        if (refs.btnAddManualBookingPartRow) {
            refs.btnAddManualBookingPartRow.disabled = isLocked;
            refs.btnAddManualBookingPartRow.innerHTML = `
                <span class="material-symbols-outlined">edit_square</span>
                Them tay
            `;
        }
    };

    const syncAdminPartEditorLock = (isLocked = isLockedBookingDetail()) => {
        if (!refs.detailPartItemsEditor) {
            return;
        }

        refs.detailPartItemsEditor.classList.toggle('is-readonly', isLocked);
        refs.detailPartItemsEditor
            .querySelectorAll('.js-line-description, .js-line-amount, .js-line-quantity, .js-line-warranty, .js-remove-booking-part-row, .js-quantity-step')
            .forEach((control) => {
                setMutationLockState(control, isLocked);
            });
    };

    const syncDetailMutationLock = (detail = state.detail) => {
        const isLocked = isLockedBookingDetail(detail);
        if (refs.detailReadonlyNotice) {
            refs.detailReadonlyNotice.classList.toggle('hidden', !isLocked);
        }
        if (refs.detailActionsGrid) {
            refs.detailActionsGrid.classList.toggle('hidden', isLocked);
        }

        [
            refs.detailStatusSelect,
            refs.detailCancelReason,
            refs.detailCancelNote,
            refs.detailWorkerSelect,
            refs.detailRescheduleDate,
            refs.detailRescheduleSlot,
            refs.detailLaborCost,
            refs.detailPartCost,
            refs.detailTravelCost,
            refs.detailTransportCost,
            refs.detailPartCatalogSearch,
            refs.detailPartNote,
            refs.detailPaymentMethod,
            refs.btnUpdateStatus,
            refs.btnAssignWorker,
            refs.btnReschedule,
            refs.btnUpdateCosts,
            refs.btnUpdatePaymentMethod,
            refs.btnConfirmCashPayment,
        ].forEach((control) => setMutationLockState(control, isLocked));

        syncAdminPartEditorLock(isLocked);
        updateAdminPartAddButtonState();
        if (isLocked) {
            hideAdminPartCatalogSuggestions();
        }

        return isLocked;
    };

    const hydrateAdminPartCatalogCopy = () => {
        const editorRoot = refs.btnAddBookingPartRow?.closest('.admin-orders-part-editor');
        const editorTitle = editorRoot?.querySelector('.admin-orders-part-editor__head strong');
        const editorCopy = editorRoot?.querySelector('.admin-orders-part-editor__head p');
        const catalogRoot = refs.detailPartCatalogSearch?.closest('.admin-orders-part-catalog');
        const catalogField = refs.detailPartCatalogSearch?.closest('.admin-orders-part-catalog__field');
        const catalogLabel = catalogField?.querySelector('span');
        const searchShell = refs.detailPartCatalogSearch?.closest('.admin-orders-part-catalog__search-shell');
        const searchIcon = searchShell?.querySelector('.material-symbols-outlined');

        editorRoot?.classList.add('dispatch-part-admin-surface');
        catalogRoot?.classList.add('dispatch-part-catalog');
        catalogField?.classList.add('dispatch-part-catalog__field');
        searchShell?.classList.add('dispatch-part-catalog__search-shell');
        refs.detailPartCatalogSuggestions?.classList.add('dispatch-part-catalog__suggestions');
        refs.detailPartCatalogStatus?.classList.add('dispatch-part-catalog__status');
        refs.detailPartCatalogResults?.classList.add('dispatch-part-catalog__results');
        refs.detailPartItemsEditor?.classList.add('dispatch-pricing-v2-parts-list');

        if (searchIcon) {
            searchIcon.classList.add('dispatch-pricing-v2-search-icon');
        }

        if (refs.btnAddBookingPartRow) {
            refs.btnAddBookingPartRow.classList.remove('btn', 'btn-sm', 'btn-primary');
            refs.btnAddBookingPartRow.classList.add('dispatch-pricing-v2-inline-add', 'dispatch-pricing-v2-inline-add--primary');
        }

        if (refs.btnAddManualBookingPartRow) {
            refs.btnAddManualBookingPartRow.classList.remove('btn', 'btn-sm', 'btn-outline-secondary');
            refs.btnAddManualBookingPartRow.classList.add('dispatch-pricing-v2-inline-add');
        }

        if (editorTitle) {
            editorTitle.textContent = 'Linh kien thay the';
        }

        if (editorCopy) {
            editorCopy.textContent = 'Lay lai dung picker linh kien cua worker: tim nhanh, goi y, chon nhieu va dua vao bang chi phi.';
        }

        if (catalogLabel) {
            catalogLabel.textContent = 'Tim trong danh muc linh kien';
        }

        if (refs.detailPartCatalogSearch) {
            refs.detailPartCatalogSearch.classList.add('dispatch-pricing-v2-searchbox');
            refs.detailPartCatalogSearch.classList.remove('form-control');
            refs.detailPartCatalogSearch.placeholder = 'Tim linh kien theo ten';
        }

        if (refs.detailPartCatalogStatus && !state.detail) {
            refs.detailPartCatalogStatus.textContent = 'Mo chi tiet don de tai danh muc linh kien theo dich vu.';
        }
    };

    const hideAdminPartCatalogSuggestions = () => {
        state.partCatalog.activeSuggestionIndex = -1;

        if (refs.detailPartCatalogSuggestions) {
            refs.detailPartCatalogSuggestions.innerHTML = '';
            refs.detailPartCatalogSuggestions.hidden = true;
        }
        refs.detailPartCatalogSearch?.setAttribute('aria-expanded', 'false');
    };

    const getVisibleAdminPartCatalogItems = () => {
        const keyword = getAdminPartCatalogKeyword();
        const filteredItems = state.partCatalog.items.filter((item) => getAdminPartCatalogItemName(item)
            .toLocaleLowerCase('vi-VN')
            .includes(keyword));

        if (!keyword) {
            return filteredItems;
        }

        return filteredItems.slice().sort((left, right) => {
            const nameA = getAdminPartCatalogItemName(left).toLocaleLowerCase('vi-VN');
            const nameB = getAdminPartCatalogItemName(right).toLocaleLowerCase('vi-VN');
            const prefixDiff = Number(!nameA.startsWith(keyword)) - Number(!nameB.startsWith(keyword));

            if (prefixDiff !== 0) {
                return prefixDiff;
            }

            const matchDiff = nameA.indexOf(keyword) - nameB.indexOf(keyword);
            if (matchDiff !== 0) {
                return matchDiff;
            }

            const lengthDiff = nameA.length - nameB.length;
            if (lengthDiff !== 0) {
                return lengthDiff;
            }

            return nameA.localeCompare(nameB, 'vi');
        });
    };

    const getKnownAdminPartCatalogItems = () => {
        const itemMap = new Map();

        [...state.partCatalog.items, ...state.partCatalog.fallbackItems].forEach((item) => {
            const partId = parseInteger(item?.id, 0, 0);
            if (partId > 0 && !itemMap.has(partId)) {
                itemMap.set(partId, item);
            }
        });

        return Array.from(itemMap.values());
    };

    const getSuggestionAdminPartCatalogItems = (visibleItems = getVisibleAdminPartCatalogItems()) => (
        visibleItems.length ? visibleItems : state.partCatalog.fallbackItems
    );

    const hasLoadedAdminFallbackSuggestionsForKeyword = (keyword) => {
        const cacheKey = String(keyword || '').trim().toLocaleLowerCase('vi-VN');
        return cacheKey !== '' && state.partCatalog.fallbackCache.has(cacheKey);
    };

    const renderAdminPartCatalogSuggestions = (visibleItems = getSuggestionAdminPartCatalogItems()) => {
        if (!refs.detailPartCatalogSuggestions) {
            return;
        }

        const rawKeyword = String(refs.detailPartCatalogSearch?.value || '').trim();
        const isLocked = isLockedBookingDetail();
        if (!rawKeyword) {
            hideAdminPartCatalogSuggestions();
            return;
        }

        if (!visibleItems.length) {
            refs.detailPartCatalogSuggestions.innerHTML = '<div class="dispatch-part-suggestion-empty">Khong tim thay linh kien phu hop voi tu khoa dang nhap.</div>';
            refs.detailPartCatalogSuggestions.hidden = false;
            refs.detailPartCatalogSearch?.setAttribute('aria-expanded', 'true');
            return;
        }

        const suggestionItems = visibleItems.slice(0, 6);
        if (state.partCatalog.activeSuggestionIndex >= suggestionItems.length) {
            state.partCatalog.activeSuggestionIndex = suggestionItems.length - 1;
        }

        refs.detailPartCatalogSuggestions.innerHTML = suggestionItems.map((item, index) => {
            const partId = parseInteger(item?.id, 0, 0);
            const hasPrice = parseAmount(item?.gia) > 0;
            const isSelected = state.partCatalog.selectedIds.has(partId);
            const serviceName = getAdminPartCatalogServiceName(item);

            return `
                <button
                    type="button"
                    class="dispatch-part-suggestion js-admin-part-suggestion ${index === state.partCatalog.activeSuggestionIndex ? 'is-active' : ''} ${isSelected ? 'is-selected' : ''} ${hasPrice ? '' : 'is-disabled'}"
                    data-part-id="${partId}"
                    data-index="${index}"
                    ${hasPrice && !isLocked ? '' : 'disabled'}
                >
                    <span class="dispatch-part-suggestion__thumb">
                        ${item?.hinh_anh
                            ? `<img src="${escapeHtml(item.hinh_anh)}" alt="${escapeHtml(getAdminPartCatalogItemName(item) || 'Linh kien')}">`
                            : '<span class="material-symbols-outlined">image_not_supported</span>'}
                    </span>
                    <span class="dispatch-part-suggestion__body">
                        <span class="dispatch-part-suggestion__title">${escapeHtml(getAdminPartCatalogItemName(item) || 'Linh kien')}</span>
                        <span class="dispatch-part-suggestion__meta">${escapeHtml(serviceName)}</span>
                    </span>
                    <span class="dispatch-part-suggestion__aside">
                        <strong class="dispatch-part-suggestion__price">${hasPrice ? formatMoney(item?.gia) : 'Chua co gia'}</strong>
                        ${isSelected ? '<span class="dispatch-part-suggestion__badge">Da chon</span>' : ''}
                    </span>
                </button>
            `;
        }).join('');

        refs.detailPartCatalogSuggestions.hidden = false;
        refs.detailPartCatalogSearch?.setAttribute('aria-expanded', 'true');
    };

    const renderAdminPartCatalogResults = () => {
        if (!refs.detailPartCatalogResults || !refs.detailPartCatalogStatus) {
            return;
        }

        const rawKeyword = String(refs.detailPartCatalogSearch?.value || '').trim();
        const isLocked = isLockedBookingDetail();
        const visibleItems = getVisibleAdminPartCatalogItems();
        const suggestionItems = getSuggestionAdminPartCatalogItems(visibleItems);
        const isShowingFallback = !visibleItems.length && suggestionItems.length > 0;

        if (!state.partCatalog.items.length) {
            if (!state.detail) {
                refs.detailPartCatalogStatus.textContent = 'Mo don de tai danh muc linh kien dung theo dich vu cua don.';
            } else if (isShowingFallback) {
                refs.detailPartCatalogStatus.textContent = `Dich vu cua don nay chua co linh kien mau. Dang goi y ${suggestionItems.length} linh kien tu toan bo kho theo tu khoa "${rawKeyword}".`;
            } else if (rawKeyword && hasLoadedAdminFallbackSuggestionsForKeyword(rawKeyword)) {
                refs.detailPartCatalogStatus.textContent = `Khong tim thay linh kien nao trong toan bo kho theo tu khoa "${rawKeyword}".`;
            } else if (rawKeyword) {
                refs.detailPartCatalogStatus.textContent = 'Dich vu cua don nay chua co linh kien mau. Tiep tuc nhap de tim tren toan bo kho linh kien.';
            } else {
                refs.detailPartCatalogStatus.textContent = 'Dich vu cua don nay chua co linh kien mau hoac chua dong bo danh muc.';
            }

            refs.detailPartCatalogResults.innerHTML = '';
            renderAdminPartCatalogSuggestions(suggestionItems);
            updateAdminPartAddButtonState();
            return;
        }

        refs.detailPartCatalogStatus.textContent = visibleItems.length
            ? `Dang hien thi ${visibleItems.length}/${state.partCatalog.items.length} linh kien phu hop voi dich vu cua don.`
            : isShowingFallback
                ? `Khong thay linh kien khop trong dich vu cua don. Dang goi y ${suggestionItems.length} linh kien tu toan bo kho theo tu khoa "${rawKeyword}".`
                : hasLoadedAdminFallbackSuggestionsForKeyword(rawKeyword)
                    ? `Khong tim thay linh kien khop voi tu khoa "${rawKeyword}" trong dich vu cua don hoac toan bo kho.`
                    : `Khong tim thay linh kien khop voi tu khoa "${refs.detailPartCatalogSearch?.value || ''}".`;

        refs.detailPartCatalogResults.innerHTML = visibleItems.map((item) => {
            const partId = parseInteger(item?.id, 0, 0);
            const hasPrice = parseAmount(item?.gia) > 0;
            const isSelected = state.partCatalog.selectedIds.has(partId);
            const serviceName = getAdminPartCatalogServiceName(item);

            return `
                <label class="dispatch-part-option ${isSelected ? 'is-selected' : ''} ${hasPrice && !isLocked ? '' : 'is-disabled'}">
                    <input type="checkbox" class="dispatch-part-option__check js-admin-part-check" value="${partId}" ${isSelected ? 'checked' : ''} ${hasPrice && !isLocked ? '' : 'disabled'}>
                    <div class="dispatch-part-option__thumb">
                        ${item?.hinh_anh
                            ? `<img src="${escapeHtml(item.hinh_anh)}" alt="${escapeHtml(getAdminPartCatalogItemName(item) || 'Linh kien')}">`
                            : '<span class="material-symbols-outlined">image_not_supported</span>'}
                    </div>
                    <div class="dispatch-part-option__body">
                        <div class="dispatch-part-option__title">${escapeHtml(getAdminPartCatalogItemName(item) || 'Linh kien')}</div>
                        <div class="dispatch-part-option__meta">${escapeHtml(serviceName)}</div>
                    </div>
                    <div class="dispatch-part-option__price">${hasPrice ? formatMoney(item?.gia) : 'Chua co gia'}</div>
                </label>
            `;
        }).join('');

        renderAdminPartCatalogSuggestions(suggestionItems);
        updateAdminPartAddButtonState();
    };

    const loadAdminFallbackPartSuggestions = async () => {
        const rawKeyword = String(refs.detailPartCatalogSearch?.value || '').trim();
        const visibleItems = getVisibleAdminPartCatalogItems();

        if (!rawKeyword || visibleItems.length) {
            state.partCatalog.fallbackItems = [];
            renderAdminPartCatalogResults();
            return;
        }

        const cacheKey = rawKeyword.toLocaleLowerCase('vi-VN');
        if (state.partCatalog.fallbackCache.has(cacheKey)) {
            state.partCatalog.fallbackItems = state.partCatalog.fallbackCache.get(cacheKey) || [];
            renderAdminPartCatalogResults();
            return;
        }

        const requestId = state.partCatalog.searchRequestId;

        try {
            const params = new URLSearchParams({ keyword: rawKeyword });
            const response = await callApi(`/linh-kien?${params.toString()}`, 'GET');

            if (requestId !== state.partCatalog.searchRequestId) {
                return;
            }

            if (!response.ok) {
                throw new Error(response.data?.message || 'Khong the tim linh kien.');
            }

            const items = Array.isArray(response.data) ? response.data : [];
            state.partCatalog.fallbackItems = items;
            state.partCatalog.fallbackCache.set(cacheKey, items);
            renderAdminPartCatalogResults();
        } catch {
            if (requestId !== state.partCatalog.searchRequestId) {
                return;
            }

            state.partCatalog.fallbackItems = [];
            renderAdminPartCatalogResults();
        }
    };

    const refreshAdminPartCatalogSearch = async () => {
        state.partCatalog.searchRequestId += 1;
        renderAdminPartCatalogResults();
        await loadAdminFallbackPartSuggestions();
    };

    const setAdminPartCatalogSelectionState = (partId, isSelected) => {
        if (partId <= 0 || isLockedBookingDetail()) {
            return;
        }

        if (isSelected) {
            state.partCatalog.selectedIds.add(partId);
        } else {
            state.partCatalog.selectedIds.delete(partId);
        }

        updateAdminPartAddButtonState();
    };

    const selectAdminPartCatalogSuggestion = (partId) => {
        if (isLockedBookingDetail()) {
            return;
        }

        const selectedItem = getKnownAdminPartCatalogItems().find((item) => parseInteger(item?.id, 0, 0) === partId);
        if (!selectedItem || parseAmount(selectedItem?.gia) <= 0) {
            return;
        }

        setAdminPartCatalogSelectionState(partId, true);
        if (refs.detailPartCatalogSearch) {
            refs.detailPartCatalogSearch.value = getAdminPartCatalogItemName(selectedItem);
        }

        state.partCatalog.activeSuggestionIndex = -1;
        renderAdminPartCatalogResults();
        hideAdminPartCatalogSuggestions();

        const selectedCheckbox = refs.detailPartCatalogResults?.querySelector(`.js-admin-part-check[value="${partId}"]`);
        selectedCheckbox?.closest('.dispatch-part-option')?.scrollIntoView({
            block: 'nearest',
            behavior: 'smooth',
        });
    };

    const loadAdminPartCatalogForBooking = async (detail) => {
        state.partCatalog.requestId += 1;
        const requestId = state.partCatalog.requestId;
        const serviceIds = Array.isArray(detail?.service_ids)
            ? detail.service_ids.map((id) => parseInteger(id, 0, 0)).filter((id) => id > 0)
            : [];

        state.partCatalog.serviceIds = serviceIds;
        state.partCatalog.selectedIds = new Set();
        state.partCatalog.activeSuggestionIndex = -1;
        state.partCatalog.fallbackItems = [];
        state.partCatalog.searchRequestId += 1;

        if (refs.detailPartCatalogSearch) {
            refs.detailPartCatalogSearch.value = '';
            refs.detailPartCatalogSearch.placeholder = 'Tim linh kien theo ten';
        }

        hideAdminPartCatalogSuggestions();
        updateAdminPartAddButtonState();

        if (!refs.detailPartCatalogStatus || !refs.detailPartCatalogResults) {
            return;
        }

        if (!serviceIds.length) {
            state.partCatalog.items = [];
            renderAdminPartCatalogResults();
            return;
        }

        const cacheKey = serviceIds.slice().sort((left, right) => left - right).join(',');
        if (state.partCatalog.cache.has(cacheKey)) {
            state.partCatalog.items = state.partCatalog.cache.get(cacheKey) || [];
            renderAdminPartCatalogResults();
            return;
        }

        refs.detailPartCatalogStatus.textContent = 'Dang tai danh muc linh kien theo dich vu cua don...';
        refs.detailPartCatalogResults.innerHTML = '';

        try {
            const responses = await Promise.all(serviceIds.map((serviceId) => callApi(`/admin/linh-kien?service_id=${serviceId}`, 'GET')));

            if (requestId !== state.partCatalog.requestId) {
                return;
            }

            const itemMap = new Map();
            responses.forEach((response) => {
                const payload = ensureSuccess(response, 'Khong the tai danh muc linh kien');
                const items = Array.isArray(payload?.items) ? payload.items : [];
                items.forEach((item) => {
                    const partId = parseInteger(item?.id, 0, 0);
                    if (partId > 0 && !itemMap.has(partId)) {
                        itemMap.set(partId, item);
                    }
                });
            });

            state.partCatalog.items = Array.from(itemMap.values()).sort((left, right) => (
                String(left?.ten_linh_kien || '').localeCompare(String(right?.ten_linh_kien || ''), 'vi')
            ));
            state.partCatalog.cache.set(cacheKey, state.partCatalog.items);
            renderAdminPartCatalogResults();
        } catch (error) {
            if (requestId !== state.partCatalog.requestId) {
                return;
            }

            state.partCatalog.items = [];
            refs.detailPartCatalogStatus.textContent = error.message || 'Khong the tai danh muc linh kien.';
            refs.detailPartCatalogResults.innerHTML = '';
        }
    };

    const addSelectedAdminCatalogParts = () => {
        if (isLockedBookingDetail()) {
            showToast('Don da hoan thanh, khong the chinh sua nua.', 'error');
            return;
        }

        const selectedParts = getKnownAdminPartCatalogItems().filter((item) => state.partCatalog.selectedIds.has(parseInteger(item?.id, 0, 0)));
        if (!selectedParts.length) {
            showToast('Vui long chon it nhat 1 linh kien trong danh muc.', 'error');
            return;
        }

        const existingIds = new Set(Array.from(refs.detailPartItemsEditor?.querySelectorAll('.js-line-part-id') || [])
            .map((input) => parseInteger(input?.value, 0, 0))
            .filter((id) => id > 0));

        let addedCount = 0;

        if (!refs.detailPartItemsEditor?.querySelector('.admin-orders-part-row')) {
            refs.detailPartItemsEditor.innerHTML = '';
        }

        selectedParts.forEach((item) => {
            const partId = parseInteger(item?.id, 0, 0);
            const partPrice = parseAmount(item?.gia);

            if (partId <= 0 || partPrice <= 0 || existingIds.has(partId)) {
                return;
            }

            refs.detailPartItemsEditor?.insertAdjacentHTML('beforeend', buildAdminPartRowMarkup({
                linh_kien_id: partId,
                dich_vu_id: parseInteger(item?.dich_vu_id || item?.dich_vu?.id, 0, 0),
                service_name: getAdminPartCatalogServiceName(item),
                hinh_anh: item?.hinh_anh || '',
                noi_dung: getAdminPartCatalogItemName(item) || 'Linh kien',
                don_gia: partPrice,
                so_luong: 1,
                so_tien: partPrice,
                bao_hanh_thang: null,
            }));

            existingIds.add(partId);
            addedCount += 1;
        });

        if (addedCount === 0) {
            showToast('Linh kien da duoc them truoc do hoac chua co gia.', 'error');
            return;
        }

        state.partCatalog.selectedIds = new Set();
        state.partCatalog.fallbackItems = [];
        if (refs.detailPartCatalogSearch) {
            refs.detailPartCatalogSearch.value = '';
        }
        renderAdminPartCatalogResults();
        hideAdminPartCatalogSuggestions();
        syncAdminPartCost();
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
        const isCompleted = isCompletedBookingStatus(booking?.status_key || booking?.status);
        const checked = !isCompleted && state.selected.has(Number(booking.id)) ? 'checked' : '';
        const complaintLink = booking?.flags?.has_complaint
            ? `<a class="text-xs font-medium text-error hover:underline mt-1 inline-block" href="/admin/customer-feedback?search=${encodeURIComponent(booking.code || '')}">Xem khiếu nại</a>`
            : '';

        return `
            <tr data-booking-id="${escapeHtml(booking.id)}" class="hover:bg-surface-container-lowest/50 transition-colors group">
                <td class="p-4 text-center border-t border-surface-container/50">
                    <input type="checkbox" data-row-select="${escapeHtml(booking.id)}" class="rounded border-outline-variant text-primary focus:ring-primary/50" ${checked} ${isCompleted ? 'disabled title="Don da hoan thanh"' : ''}>
                </td>
                <td class="p-4 border-t border-surface-container/50 align-top">
                    <div class="font-headline font-bold text-on-surface">${escapeHtml(booking.code || '--')}</div>
                    <div class="flex flex-wrap gap-1 mt-1.5">
                        ${buildPill(booking.status_label || '--', booking.status_tone || 'info')}
                        ${buildPill(booking.priority_label || '--', booking.priority_tone || 'muted')}
                        ${buildPill(booking.sla_label || '--', booking.sla_tone || 'muted')}
                    </div>
                </td>
                <td class="p-4 border-t border-surface-container/50 align-top">
                    <div class="font-medium text-on-surface">${escapeHtml(booking?.customer?.name || 'Khách hàng')}</div>
                    <div class="text-xs text-on-surface-variant mt-0.5">${escapeHtml(booking?.customer?.phone || 'Chưa có SĐT')}</div>
                    <div class="text-xs text-on-surface-variant mt-0.5 line-clamp-1" title="${escapeHtml(booking?.customer?.address || 'Chưa có địa chỉ')}">${escapeHtml(booking?.customer?.address || 'Chưa có địa chỉ')}</div>
                    <div class="text-xs font-medium text-primary mt-1.5">${escapeHtml(booking.mode_label || '--')}</div>
                </td>
                <td class="p-4 border-t border-surface-container/50 align-top">
                    <div class="font-medium text-on-surface">${escapeHtml(booking.service_label || 'Chưa xác định dịch vụ')}</div>
                    <div class="text-xs text-on-surface-variant mt-0.5 line-clamp-2" title="${escapeHtml(booking.problem_excerpt || 'Khách chưa mô tả sự cố')}">${escapeHtml(booking.problem_excerpt || 'Khách chưa mô tả sự cố')}</div>
                    <div class="text-xs text-on-surface-variant mt-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">attachment</span>
                        ${escapeHtml(String(booking?.media?.total || 0))} đính kèm
                    </div>
                </td>
                <td class="p-4 border-t border-surface-container/50 align-top">
                    <div class="font-medium text-on-surface">${escapeHtml(booking?.worker?.name || 'Chưa phân công')}</div>
                    <div class="text-xs text-on-surface-variant mt-0.5">${escapeHtml(booking?.worker?.phone || 'Không có SĐT')}</div>
                    <div class="text-xs font-medium text-primary-fixed-dim mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">event</span>
                        ${escapeHtml(booking?.schedule?.label || '--')}
                    </div>
                </td>
                <td class="p-4 border-t border-surface-container/50 align-top">
                    <div class="text-xs text-on-surface-variant flex justify-between gap-2"><span>Tiền công:</span> <strong class="text-on-surface">${escapeHtml(formatMoney(booking?.costs?.labor || 0))}</strong></div>
                    <div class="text-xs text-on-surface-variant flex justify-between gap-2 mt-0.5"><span>Linh kiện:</span> <strong class="text-on-surface">${escapeHtml(formatMoney(booking?.costs?.parts || 0))}</strong></div>
                    <div class="text-xs text-on-surface-variant flex justify-between gap-2 mt-0.5 border-b border-outline-variant/30 pb-1"><span>Di chuyển:</span> <strong class="text-on-surface">${escapeHtml(formatMoney((booking?.costs?.travel || 0) + (booking?.costs?.transport || 0)))}</strong></div>
                    <div class="text-sm font-bold text-primary mt-1 text-right">${escapeHtml(formatMoney(booking?.costs?.total || 0))}</div>
                </td>
                <td class="p-4 border-t border-surface-container/50 align-top">
                    <div class="mb-1.5">
                        ${buildPill(booking?.payment?.status_label || '--', booking?.payment?.status_tone || 'muted')}
                    </div>
                    <div class="text-xs text-on-surface-variant">${escapeHtml(booking?.payment?.method_label || '--')}</div>
                    <div class="text-[10px] text-on-surface-variant mt-0.5 truncate max-w-[150px]" title="${escapeHtml(booking?.payment?.latest_transaction_label || 'Chưa có giao dịch')}">${escapeHtml(booking?.payment?.latest_transaction_label || 'Chưa có giao dịch')}</div>
                    <div class="mt-2 flex flex-col gap-1">${renderFlags(booking)}</div>
                </td>
                <td class="p-4 border-t border-surface-container/50 align-top text-right">
                    <div class="flex flex-col items-end gap-2">
                        <button type="button" class="text-xs font-medium px-3 py-1.5 bg-surface-container text-primary rounded-md hover:bg-surface-container-high transition-colors border border-outline-variant/20" data-action="open-detail" data-id="${escapeHtml(booking.id)}">
                            Chi tiết
                        </button>
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

        const pageIds = state.items
            .filter((booking) => !isCompletedBookingStatus(booking?.status_key || booking?.status))
            .map((booking) => Number(booking.id))
            .filter(Boolean);
        if (pageIds.length === 0) {
            refs.selectAll.checked = false;
            refs.selectAll.indeterminate = false;
            refs.selectAll.disabled = true;
            return;
        }

        refs.selectAll.disabled = false;
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

        state.items
            .filter((booking) => isCompletedBookingStatus(booking?.status_key || booking?.status))
            .forEach((booking) => state.selected.delete(Number(booking.id)));

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
            if (!hasAppliedInitialBooking && requestedBookingId > 0) {
                hasAppliedInitialBooking = true;
                openBookingDetail(requestedBookingId);
            }
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
            refs.drawerOverlay.classList.remove('hidden');
        }
        if (refs.drawer) {
            refs.drawer.classList.remove('translate-x-full');
            refs.drawer.setAttribute('aria-hidden', 'false');
        }
        lockBodyScroll(true);
    };

    const closeDrawer = () => {
        if (refs.drawerOverlay) {
            refs.drawerOverlay.classList.add('hidden');
        }
        if (refs.drawer) {
            refs.drawer.classList.add('translate-x-full');
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
        if (refs.detailReadonlyNotice) refs.detailReadonlyNotice.classList.add('hidden');
        if (refs.detailActionsGrid) refs.detailActionsGrid.classList.remove('hidden');
    };

    const renderDetailKv = (items) => {
        if (!refs.detailInfo) {
            return;
        }

        refs.detailInfo.innerHTML = items.map((item) => `
            <div class="flex flex-col gap-1 p-3 bg-surface rounded-lg border border-surface-container">
                <span class="text-xs text-on-surface-variant font-medium">${escapeHtml(item.label)}</span>
                <span class="text-sm text-on-surface font-semibold">${escapeHtml(item.value || '--')}</span>
            </div>
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
        if (refs.detailTravelCost) refs.detailTravelCost.value = String(Math.round(detail?.costs?.travel || 0));
        if (refs.detailTransportCost) refs.detailTransportCost.value = String(Math.round(detail?.costs?.transport || 0));
        if (refs.detailPartNote) refs.detailPartNote.value = detail?.cost_details?.part_note || '';
        if (refs.detailPaymentMethod) refs.detailPaymentMethod.value = detail?.payment?.method || 'cod';

        const detailPartItems = Array.isArray(detail?.cost_details?.part_items)
            ? detail.cost_details.part_items
            : [];
        const fallbackPartItems = detailPartItems.length === 0 && Number(detail?.costs?.parts || 0) > 0
            ? [{
                noi_dung: 'Linh kiện thay thế',
                so_luong: 1,
                don_gia: Number(detail?.costs?.parts || 0),
                so_tien: Number(detail?.costs?.parts || 0),
                bao_hanh_thang: null,
            }]
            : [];

        renderAdminPartItems(detailPartItems.length ? detailPartItems : fallbackPartItems);
        syncDetailMutationLock(detail);
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
            await loadAdminPartCatalogForBooking(detail);
            syncDetailMutationLock(detail);
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
            button.disabled = button.dataset.mutationLocked === '1';
        }
    };

    const updateBookingStatus = async () => {
        if (!state.activeBookingId) {
            showToast('Chưa chọn đơn để cập nhật trạng thái', 'error');
            return;
        }

        if (isLockedBookingDetail()) {
            showToast('Don da hoan thanh, khong the chinh sua nua.', 'error');
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

        if (isLockedBookingDetail()) {
            showToast('Don da hoan thanh, khong the chinh sua nua.', 'error');
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

        if (isLockedBookingDetail()) {
            showToast('Don da hoan thanh, khong the chinh sua nua.', 'error');
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

        if (isLockedBookingDetail()) {
            showToast('Don da hoan thanh, khong the chinh sua nua.', 'error');
            return;
        }

        const labor = parseAmount(refs.detailLaborCost?.value);
        syncAdminPartCost();
        const partItems = collectAdminPartItems();
        const part = partItems.reduce((sum, item) => sum + Number(item.so_tien || 0), 0);
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
        payload.chi_tiet_linh_kien = partItems;

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

        if (isLockedBookingDetail()) {
            showToast('Don da hoan thanh, khong the chinh sua nua.', 'error');
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

        if (isLockedBookingDetail()) {
            showToast('Don da hoan thanh, khong the chinh sua nua.', 'error');
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

    refs.btnToggleMoreFilters?.addEventListener('click', () => {
        refs.moreFiltersSection?.classList.toggle('hidden');
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
        const pageIds = state.items
            .filter((item) => !isCompletedBookingStatus(item?.status_key || item?.status))
            .map((item) => Number(item.id))
            .filter(Boolean);
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

        if (!refs.detailPartCatalogSearch?.closest('.admin-orders-part-catalog')?.contains(event.target)) {
            hideAdminPartCatalogSuggestions();
        }
        closeSlaDropdown();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        closeSlaDropdown();
        if (!refs.drawer?.classList.contains('translate-x-full')) {
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

    refs.btnAddBookingPartRow?.addEventListener('click', () => {
        addSelectedAdminCatalogParts();
    });

    refs.btnAddManualBookingPartRow?.addEventListener('click', () => {
        if (isLockedBookingDetail()) {
            return;
        }

        if (!refs.detailPartItemsEditor) {
            return;
        }

        if (!refs.detailPartItemsEditor.querySelector('.admin-orders-part-row')) {
            refs.detailPartItemsEditor.innerHTML = '';
        }

        refs.detailPartItemsEditor.insertAdjacentHTML('beforeend', buildAdminPartRowMarkup({
            noi_dung: '',
            so_luong: 1,
            don_gia: 0,
            so_tien: 0,
            bao_hanh_thang: null,
        }));
        syncAdminPartCost();

        const rows = refs.detailPartItemsEditor.querySelectorAll('.admin-orders-part-row');
        rows[rows.length - 1]?.querySelector('.js-line-description')?.focus();
    });

    refs.detailPartCatalogSearch?.addEventListener('input', async () => {
        state.partCatalog.activeSuggestionIndex = -1;
        await refreshAdminPartCatalogSearch();
    });

    refs.detailPartCatalogSearch?.addEventListener('focus', async () => {
        if (String(refs.detailPartCatalogSearch.value || '').trim()) {
            await refreshAdminPartCatalogSearch();
        }
    });

    refs.detailPartCatalogSearch?.addEventListener('blur', () => {
        window.setTimeout(() => {
            if (document.activeElement !== refs.detailPartCatalogSearch) {
                hideAdminPartCatalogSuggestions();
            }
        }, 120);
    });

    refs.detailPartCatalogSearch?.addEventListener('keydown', (event) => {
        const visibleItems = getSuggestionAdminPartCatalogItems().slice(0, 6);

        if (event.key === 'Escape') {
            hideAdminPartCatalogSuggestions();
            return;
        }

        if (!visibleItems.length) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            state.partCatalog.activeSuggestionIndex = (state.partCatalog.activeSuggestionIndex + 1 + visibleItems.length) % visibleItems.length;
            renderAdminPartCatalogSuggestions();
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            state.partCatalog.activeSuggestionIndex = state.partCatalog.activeSuggestionIndex <= 0
                ? visibleItems.length - 1
                : state.partCatalog.activeSuggestionIndex - 1;
            renderAdminPartCatalogSuggestions();
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            const selectedIndex = state.partCatalog.activeSuggestionIndex >= 0 ? state.partCatalog.activeSuggestionIndex : 0;
            const selectedItem = visibleItems[selectedIndex];
            if (selectedItem) {
                selectAdminPartCatalogSuggestion(parseInteger(selectedItem?.id, 0, 0));
            }
        }
    });

    refs.detailPartCatalogSuggestions?.addEventListener('mousedown', (event) => {
        if (event.target.closest('.js-admin-part-suggestion')) {
            event.preventDefault();
        }
    });

    refs.detailPartCatalogSuggestions?.addEventListener('click', (event) => {
        const suggestion = event.target.closest('.js-admin-part-suggestion');
        if (!suggestion) {
            return;
        }

        selectAdminPartCatalogSuggestion(parseInteger(suggestion.dataset.partId, 0, 0));
    });

    refs.detailPartCatalogResults?.addEventListener('change', (event) => {
        const input = event.target.closest('.js-admin-part-check');
        if (!input) {
            return;
        }

        const partId = parseInteger(input.value, 0, 0);
        setAdminPartCatalogSelectionState(partId, input.checked);
        input.closest('.dispatch-part-option')?.classList.toggle('is-selected', input.checked);
        renderAdminPartCatalogSuggestions();
    });

    refs.detailPartItemsEditor?.addEventListener('input', (event) => {
        if (!event.target.closest('.admin-orders-part-row')) {
            return;
        }

        syncAdminPartCost();
    });

    refs.detailPartItemsEditor?.addEventListener('change', (event) => {
        if (!event.target.closest('.admin-orders-part-row')) {
            return;
        }

        syncAdminPartCost();
    });

    refs.detailPartItemsEditor?.addEventListener('click', (event) => {
        const quantityStepButton = event.target.closest('.js-quantity-step');
        if (quantityStepButton) {
            const lineItem = quantityStepButton.closest('.dispatch-line-item');
            const quantityInput = lineItem?.querySelector('.js-line-quantity');

            if (quantityInput) {
                const step = Math.trunc(Number(quantityStepButton.dataset.step || 0));
                const nextValue = Math.max(1, parseInteger(quantityInput.value || 1, 1, 1) + step);
                quantityInput.value = String(nextValue);
                syncAdminPartCost();
            }

            return;
        }

        const removeButton = event.target.closest('.js-remove-booking-part-row');
        if (!removeButton) {
            return;
        }

        removeButton.closest('.admin-orders-part-row')?.remove();

        if (!refs.detailPartItemsEditor?.querySelector('.admin-orders-part-row')) {
            renderAdminPartItems([]);
            return;
        }

        syncAdminPartCost();
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

    hydrateAdminPartCatalogCopy();
    updateAdminPartAddButtonState();
    loadBookings();
});
