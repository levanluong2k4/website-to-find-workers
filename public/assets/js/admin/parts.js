import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const PLACEHOLDER_IMAGE = '/assets/images/logontu.png';
    const refs = {
        search: document.getElementById('partSearchInput'),
        serviceFilter: document.getElementById('partServiceFilter'),
        sort: document.getElementById('partSortSelect'),
        pageSize: document.getElementById('partPageSize'),
        refresh: document.getElementById('btnRefreshParts'),
        add: document.getElementById('btnAddPart'),
        prevPage: document.getElementById('btnPrevPartPage'),
        nextPage: document.getElementById('btnNextPartPage'),
        pageIndicator: document.getElementById('partPageIndicator'),
        visibleCount: document.getElementById('partVisibleCount'),
        paginationSummary: document.getElementById('partPaginationSummary'),
        tbody: document.getElementById('partsTableBody'),
        statTotal: document.getElementById('partStatTotal'),
        statTotalMeta: document.getElementById('partStatTotalMeta'),
        statPriced: document.getElementById('partStatPriced'),
        statPricedMeta: document.getElementById('partStatPricedMeta'),
        statUnpriced: document.getElementById('partStatUnpriced'),
        statUnpricedMeta: document.getElementById('partStatUnpricedMeta'),
        statServices: document.getElementById('partStatServices'),
        form: document.getElementById('partForm'),
        formAlert: document.getElementById('partFormAlert'),
        modalElement: document.getElementById('partModal'),
    };

    const fields = {
        id: document.getElementById('partId'),
        service: document.getElementById('partService'),
        name: document.getElementById('partName'),
        price: document.getElementById('partPrice'),
        stock: document.getElementById('partStock'),
        expiry: document.getElementById('partExpiry'),
        image: document.getElementById('partImage'),
        preview: document.getElementById('partImagePreview'),
        removeImage: document.getElementById('btnRemovePartImage'),
        save: document.getElementById('btnSavePart'),
        label: document.getElementById('partModalLabel'),
    };

    const validationRefs = {
        dich_vu_id: {
            input: fields.service,
            error: document.getElementById('partServiceError'),
        },
        ten_linh_kien: {
            input: fields.name,
            error: document.getElementById('partNameError'),
        },
        gia: {
            input: fields.price,
            error: document.getElementById('partPriceError'),
        },
        so_luong_ton_kho: {
            input: fields.stock,
            error: document.getElementById('partStockError'),
        },
        han_su_dung: {
            input: fields.expiry,
            error: document.getElementById('partExpiryError'),
        },
        hinh_anh: {
            input: fields.image,
            error: document.getElementById('partImageError'),
        },
    };

    const modal = new bootstrap.Modal(refs.modalElement);
    const number = new Intl.NumberFormat('vi-VN');
    const state = {
        items: [],
        services: [],
        summary: {
            total: 0,
            priced: 0,
        },
        searchTimer: null,
        currentImageUrl: '',
        removeCurrentImage: false,
        localPreviewUrl: null,
        currentPage: 1,
        pageSize: Number(refs.pageSize?.value || 12),
        sortBy: refs.sort?.value || 'updated_desc',
    };

    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');

    const formatMoney = (value) => {
        if (value === null || value === undefined || value === '') {
            return 'Chua cap nhat';
        }

        return `${number.format(Number(value || 0))} \u0111`;
    };

    const formatCount = (value) => number.format(Number(value || 0));

    const formatCompactMoney = (value) => {
        const amount = Number(value || 0);

        if (amount >= 1_000_000_000) {
            const scaled = amount / 1_000_000_000;
            return `${scaled.toFixed(scaled >= 10 ? 0 : 1).replace(/\.0$/, '')}B đ`;
        }

        if (amount >= 1_000_000) {
            const scaled = amount / 1_000_000;
            return `${scaled.toFixed(scaled >= 10 ? 0 : 1).replace(/\.0$/, '')}M đ`;
        }

        return formatMoney(amount);
    };

    const revokePreview = () => {
        if (!state.localPreviewUrl) {
            return;
        }

        URL.revokeObjectURL(state.localPreviewUrl);
        state.localPreviewUrl = null;
    };

    const updatePreview = (src = '') => {
        fields.preview.src = src || (!state.removeCurrentImage && state.currentImageUrl ? state.currentImageUrl : PLACEHOLDER_IMAGE);
    };

    const setLoading = (isLoading) => {
        fields.save.disabled = isLoading;
        fields.save.innerHTML = isLoading
            ? '<i class="fas fa-spinner fa-spin me-2"></i>Đang lưu...'
            : '<i class="fas fa-save me-2"></i>Lưu linh kiện';
    };

    const setFormAlert = (message = '') => {
        refs.formAlert.textContent = message;
        refs.formAlert.classList.toggle('d-none', !message);
    };

    const clearValidation = () => {
        Object.values(validationRefs).forEach(({ input, error }) => {
            input?.classList.remove('is-invalid');
            if (error) {
                error.textContent = '';
            }
        });

        setFormAlert('');
    };

    const clearFieldValidation = (fieldName) => {
        const ref = validationRefs[fieldName];

        if (!ref) {
            return;
        }

        ref.input?.classList.remove('is-invalid');
        if (ref.error) {
            ref.error.textContent = '';
        }
    };

    const applyValidationErrors = (errors = {}, fallbackMessage = 'Du lieu linh kien khong hop le') => {
        clearValidation();

        const entries = Object.entries(errors);
        if (!entries.length) {
            setFormAlert(fallbackMessage);
            return;
        }

        entries.forEach(([fieldName, messages]) => {
            const ref = validationRefs[fieldName];
            const message = Array.isArray(messages) ? messages[0] : messages;

            if (!ref) {
                return;
            }

            ref.input?.classList.add('is-invalid');
            if (ref.error) {
                ref.error.textContent = message || fallbackMessage;
            }
        });

        setFormAlert(fallbackMessage);
    };

    const resetPagination = () => {
        state.currentPage = 1;
    };

    const buildQuery = () => {
        const params = new URLSearchParams();

        if (refs.search.value.trim()) {
            params.set('search', refs.search.value.trim());
        }

        if (refs.serviceFilter.value) {
            params.set('service_id', refs.serviceFilter.value);
        }

        const query = params.toString();
        return query ? `?${query}` : '';
    };

    const syncFiltersFromUrl = () => {
        const url = new URL(window.location.href);

        refs.search.value = url.searchParams.get('search') || '';
        refs.serviceFilter.dataset.selected = url.searchParams.get('service_id') || '';
    };

    const syncUrl = () => {
        const url = new URL(window.location.href);
        const params = url.searchParams;
        const searchValue = refs.search.value.trim();
        const serviceValue = refs.serviceFilter.value;

        if (searchValue) {
            params.set('search', searchValue);
        } else {
            params.delete('search');
        }

        if (serviceValue) {
            params.set('service_id', serviceValue);
        } else {
            params.delete('service_id');
        }

        url.search = params.toString();
        window.history.replaceState({}, '', url);
    };

    const populateServiceOptions = () => {
        const selectedFilter = refs.serviceFilter.value || refs.serviceFilter.dataset.selected || '';
        const selectedForm = fields.service.value || fields.service.dataset.selected || '';
        const options = state.services.map((service) => `
            <option value="${service.id}">${escapeHtml(service.ten_dich_vu)}</option>
        `).join('');

        refs.serviceFilter.innerHTML = `<option value="">Tất cả dịch vụ</option>${options}`;
        fields.service.innerHTML = `<option value="">Chọn dịch vụ</option>${options}`;

        refs.serviceFilter.value = state.services.some((service) => String(service.id) === selectedFilter) ? selectedFilter : '';
        fields.service.value = state.services.some((service) => String(service.id) === selectedForm) ? selectedForm : '';
    };

    const renderStats = () => {
        const totalStock = state.items.reduce((sum, item) => sum + Number(item.so_luong_ton_kho || 0), 0);
        const inStockCount = state.items.filter((item) => Number(item.so_luong_ton_kho || 0) > 0).length;
        const lowStockCount = state.items.filter((item) => Number(item.so_luong_ton_kho || 0) <= 5).length;
        const outOfStockCount = state.items.filter((item) => Number(item.so_luong_ton_kho || 0) === 0).length;
        const inventoryValue = state.items.reduce((sum, item) => {
            return sum + (Number(item.so_luong_ton_kho || 0) * Number(item.gia || 0));
        }, 0);
        const pricedCount = state.items.filter((item) => Number(item.gia || 0) > 0).length;
        const serviceCount = new Set(
            state.items
                .map((item) => String(item.dich_vu_id || ''))
                .filter(Boolean)
        ).size;

        refs.statTotal.textContent = formatCount(totalStock);
        refs.statPriced.textContent = formatCount(lowStockCount);
        refs.statUnpriced.textContent = formatCompactMoney(inventoryValue);

        if (refs.statTotalMeta) {
            refs.statTotalMeta.textContent = inStockCount > 0
                ? `${formatCount(inStockCount)} mã linh kiện còn hàng`
                : 'Chưa có linh kiện còn hàng';
        }

        if (refs.statPricedMeta) {
            refs.statPricedMeta.textContent = outOfStockCount > 0
                ? `${formatCount(outOfStockCount)} mục đã hết tồn`
                : 'Không có mục cần xử lý gấp';
        }

        if (refs.statUnpricedMeta) {
            refs.statUnpricedMeta.textContent = pricedCount > 0
                ? `${formatCount(pricedCount)} mục đã có giá trên ${formatCount(serviceCount)} dịch vụ`
                : 'Chưa có linh kiện có giá trị tồn kho';
        }

        if (refs.statServices) {
            refs.statServices.textContent = formatCount(serviceCount);
        }
    };

    const compareText = (left, right) => left.localeCompare(right, 'vi', { sensitivity: 'base' });

    const compareNumber = (left, right) => Number(left || 0) - Number(right || 0);

    const compareDate = (left, right) => new Date(left || 0).getTime() - new Date(right || 0).getTime();

    const sortItems = (items) => {
        const sortedItems = [...items];

        sortedItems.sort((left, right) => {
            switch (state.sortBy) {
            case 'updated_asc':
                return compareDate(left.updated_at, right.updated_at);
            case 'name_asc':
                return compareText(left.ten_linh_kien || '', right.ten_linh_kien || '');
            case 'price_desc':
                return compareNumber(right.gia, left.gia);
            case 'price_asc':
                return compareNumber(left.gia, right.gia);
            case 'updated_desc':
            default:
                return compareDate(right.updated_at, left.updated_at);
            }
        });

        return sortedItems;
    };

    const paginateItems = (items) => {
        const total = items.length;
        const totalPages = Math.max(1, Math.ceil(total / state.pageSize));
        state.currentPage = Math.min(state.currentPage, totalPages);

        const startIndex = total ? (state.currentPage - 1) * state.pageSize : 0;
        const endIndex = Math.min(startIndex + state.pageSize, total);

        return {
            items: items.slice(startIndex, endIndex),
            total,
            totalPages,
            startIndex,
            endIndex,
        };
    };

    const renderPagination = (pageData) => {
        if (!pageData.total) {
            refs.visibleCount.textContent = 'Không có linh kiện phù hợp';
            refs.paginationSummary.textContent = 'Không có linh kiện nào để hiển thị';
            refs.pageIndicator.textContent = 'Trang 1 / 1';
            refs.prevPage.disabled = true;
            refs.nextPage.disabled = true;
            return;
        }

        refs.visibleCount.textContent = `${formatCount(pageData.total)} linh kiện khớp bộ lọc`;
        refs.paginationSummary.textContent = `Đang hiển thị ${formatCount(pageData.startIndex + 1)}-${formatCount(pageData.endIndex)} / ${formatCount(pageData.total)} linh kiện`;
        refs.pageIndicator.textContent = `Trang ${formatCount(state.currentPage)} / ${formatCount(pageData.totalPages)}`;
        refs.prevPage.disabled = state.currentPage <= 1;
        refs.nextPage.disabled = state.currentPage >= pageData.totalPages;
    };

    const renderTable = (items) => {
        if (!items.length) {
            refs.tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        Không có linh kiện phù hợp với bộ lọc hiện tại.
                    </td>
                </tr>
            `;
            return;
        }

        refs.tbody.innerHTML = items.map((item) => {
            const stockValue = Number(item.so_luong_ton_kho || 0);
            const stockClass = stockValue <= 0
                ? 'stock-pill stock-pill--empty'
                : stockValue <= 5
                    ? 'stock-pill stock-pill--low'
                    : 'stock-pill';
            const expiryClass = item.han_su_dung_state === 'expired'
                ? 'expiry-label expiry-label--expired'
                : item.han_su_dung_state === 'expiring_soon'
                    ? 'expiry-label expiry-label--soon'
                    : 'expiry-label';
            const expiryBadge = item.han_su_dung_state === 'expired'
                ? '<span class="expiry-warning-badge expiry-warning-badge--expired">Quá hạn</span>'
                : item.han_su_dung_state === 'expiring_soon'
                    ? '<span class="expiry-warning-badge">Cận date</span>'
                    : '';
            const hasPrice = item.gia !== null && item.gia !== undefined && item.gia !== '';
            const partCode = `LK-${String(item.id).padStart(4, '0')}`;

            return `
                <tr>
                    <td>
                        <span class="catalog-code">${partCode}</span>
                    </td>
                    <td>
                        <div class="catalog-name">${escapeHtml(item.ten_linh_kien || '--')}</div>
                        <span class="catalog-meta">${escapeHtml(item.service_name || 'Chưa gán dịch vụ')}</span>
                    </td>
                    <td><span class="${stockClass}">${escapeHtml(item.so_luong_ton_kho_label || formatCount(stockValue))}</span></td>
                    <td><span class="${hasPrice ? 'catalog-money' : 'catalog-money catalog-money--empty'}">${escapeHtml(item.gia_label || formatMoney(item.gia))}</span></td>
                    <td>
                        <div class="expiry-cell">
                            <span class="${expiryClass} ${!item.han_su_dung ? 'expiry-label--none' : ''}">${escapeHtml(item.han_su_dung_label || 'Không có')}</span>
                            ${expiryBadge}
                        </div>
                    </td>
                    <td class="text-end pe-4">
                        <div class="catalog-actions">
                            <button type="button" class="catalog-action-btn" data-action="edit" data-id="${item.id}" title="Sửa linh kiện" aria-label="Sửa linh kiện">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" class="catalog-action-btn catalog-action-btn--danger" data-action="delete" data-id="${item.id}" title="Xóa linh kiện" aria-label="Xóa linh kiện">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    };

    const renderCatalog = () => {
        renderStats();

        const sortedItems = sortItems(state.items);
        const pageData = paginateItems(sortedItems);

        renderTable(pageData.items);
        renderPagination(pageData);
    };

    const openCreateModal = () => {
        refs.form.reset();
        clearValidation();
        fields.id.value = '';
        fields.image.value = '';
        fields.stock.value = '0';
        fields.expiry.value = '';
        fields.service.dataset.selected = '';
        fields.label.textContent = 'Thêm linh kiện';
        state.currentImageUrl = '';
        state.removeCurrentImage = false;
        revokePreview();
        updatePreview();
        populateServiceOptions();
        setLoading(false);
    };

    const openEditModal = (item) => {
        if (!item) {
            return;
        }

        refs.form.reset();
        clearValidation();
        fields.id.value = item.id;
        fields.image.value = '';
        fields.service.dataset.selected = String(item.dich_vu_id || '');
        fields.name.value = item.ten_linh_kien || '';
        fields.price.value = item.gia ?? '';
        fields.stock.value = item.so_luong_ton_kho ?? 0;
        fields.expiry.value = item.han_su_dung || '';
        fields.label.textContent = 'Sửa linh kiện';
        state.currentImageUrl = item.hinh_anh || '';
        state.removeCurrentImage = false;
        revokePreview();
        populateServiceOptions();
        updatePreview();
        setLoading(false);
        modal.show();
    };

    const fetchParts = async () => {
        refs.tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 mb-0">Đang tải linh kiện...</p>
                </td>
            </tr>
        `;

        syncUrl();

        try {
            const response = await callApi(`/admin/linh-kien${buildQuery()}`);

            if (!response?.ok) {
                throw new Error(response?.data?.message || 'Không thể tải danh sách linh kiện');
            }

            const payload = response.data?.data || {};
            state.items = Array.isArray(payload.items) ? payload.items : [];
            state.services = Array.isArray(payload.service_options) ? payload.service_options : [];
            state.summary = payload.summary || {
                total: state.items.length,
                priced: state.items.filter((item) => Number(item.gia || 0) > 0).length,
            };

            populateServiceOptions();
            renderCatalog();
        } catch (error) {
            console.error('Load parts failed:', error);
            state.items = [];
            state.summary = { total: 0, priced: 0 };
            renderCatalog();
            refs.tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-danger">Không thể tải danh sách linh kiện.</td>
                </tr>
            `;
            refs.visibleCount.textContent = 'Không tải được dữ liệu';
            refs.paginationSummary.textContent = 'Thử tải lại để tiếp tục quản lý linh kiện';
            refs.pageIndicator.textContent = 'Trang 1 / 1';
            refs.prevPage.disabled = true;
            refs.nextPage.disabled = true;
            showToast(error.message || 'Không thể tải danh sách linh kiện', 'error');
        }
    };

    const deletePart = async (item) => {
        if (!item) {
            return;
        }

        const confirmation = await Swal.fire({
            title: 'Xóa linh kiện?',
            text: `Linh kiện "${item.ten_linh_kien}" sẽ bị xóa khỏi danh mục.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#dc2626',
        });

        if (!confirmation.isConfirmed) {
            return;
        }

        try {
            const response = await callApi(`/admin/linh-kien/${item.id}`, 'DELETE');

            if (!response?.ok) {
                showToast(response?.data?.message || 'Không xóa được linh kiện', 'error');
                return;
            }

            showToast(response.data?.message || 'Đã xóa linh kiện');

            if (state.items.length === 1 && state.currentPage > 1) {
                state.currentPage -= 1;
            }

            await fetchParts();
        } catch (error) {
            showToast(error.message || 'Không xóa được linh kiện', 'error');
        }
    };

    const refetchFromFirstPage = () => {
        resetPagination();
        fetchParts();
    };

    refs.add.addEventListener('click', openCreateModal);
    refs.refresh.addEventListener('click', fetchParts);
    refs.serviceFilter.addEventListener('change', refetchFromFirstPage);
    refs.sort.addEventListener('change', () => {
        state.sortBy = refs.sort.value;
        resetPagination();
        renderCatalog();
    });
    refs.pageSize.addEventListener('change', () => {
        state.pageSize = Number(refs.pageSize.value || 12);
        resetPagination();
        renderCatalog();
    });
    refs.prevPage.addEventListener('click', () => {
        if (state.currentPage <= 1) {
            return;
        }

        state.currentPage -= 1;
        renderCatalog();
    });
    refs.nextPage.addEventListener('click', () => {
        state.currentPage += 1;
        renderCatalog();
    });
    refs.search.addEventListener('input', () => {
        window.clearTimeout(state.searchTimer);
        state.searchTimer = window.setTimeout(refetchFromFirstPage, 250);
    });

    refs.tbody.addEventListener('click', async (event) => {
        const button = event.target instanceof Element
            ? event.target.closest('button[data-action]')
            : null;
        if (!button) {
            return;
        }

        const item = state.items.find((entry) => String(entry.id) === String(button.dataset.id));

        if (button.dataset.action === 'edit') {
            openEditModal(item);
            return;
        }

        if (button.dataset.action === 'delete') {
            await deletePart(item);
        }
    });

    fields.preview.addEventListener('error', () => {
        fields.preview.src = PLACEHOLDER_IMAGE;
    });

    fields.service.addEventListener('change', () => clearFieldValidation('dich_vu_id'));
    fields.name.addEventListener('input', () => clearFieldValidation('ten_linh_kien'));
    fields.price.addEventListener('input', () => clearFieldValidation('gia'));
    fields.stock.addEventListener('input', () => clearFieldValidation('so_luong_ton_kho'));
    fields.expiry.addEventListener('change', () => clearFieldValidation('han_su_dung'));

    fields.image.addEventListener('change', () => {
        clearFieldValidation('hinh_anh');
        revokePreview();

        const [file] = fields.image.files || [];
        if (!file) {
            updatePreview();
            return;
        }

        state.removeCurrentImage = false;
        state.localPreviewUrl = URL.createObjectURL(file);
        updatePreview(state.localPreviewUrl);
    });

    fields.removeImage.addEventListener('click', () => {
        clearFieldValidation('hinh_anh');
        fields.image.value = '';
        revokePreview();
        state.removeCurrentImage = true;
        updatePreview();
    });

    refs.modalElement.addEventListener('hidden.bs.modal', () => {
        revokePreview();
        openCreateModal();
    });

    refs.form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearValidation();
        setLoading(true);

        const partId = fields.id.value.trim();
        const endpoint = partId ? `/admin/linh-kien/${partId}` : '/admin/linh-kien';
        const formData = new FormData();

        formData.append('dich_vu_id', fields.service.value);
        formData.append('ten_linh_kien', fields.name.value.trim());
        formData.append('gia', fields.price.value.trim());
        formData.append('so_luong_ton_kho', fields.stock.value.trim() || '0');
        formData.append('han_su_dung', fields.expiry.value || '');

        const [imageFile] = fields.image.files || [];
        if (imageFile) {
            formData.append('hinh_anh', imageFile);
        }

        if (partId) {
            formData.append('_method', 'PUT');

            if (state.removeCurrentImage && !imageFile) {
                formData.append('remove_image', '1');
            }
        }

        try {
            const response = await callApi(endpoint, 'POST', formData);

            if (!response?.ok) {
                const message = response?.data?.message || 'Không lưu được linh kiện';

                if (response.status === 422) {
                    applyValidationErrors(response?.data?.errors || {}, message);
                } else {
                    setFormAlert(message);
                }

                showToast(message, 'error');
                return;
            }

            showToast(response.data?.message || 'Da luu linh kien');
            modal.hide();
            await fetchParts();
        } catch (error) {
            const message = error.message || 'Không lưu được linh kiện';
            setFormAlert(message);
            showToast(message, 'error');
        } finally {
            setLoading(false);
        }
    });

    syncFiltersFromUrl();
    openCreateModal();
    fetchParts();
});
