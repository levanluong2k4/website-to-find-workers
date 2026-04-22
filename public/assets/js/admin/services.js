import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const PLACEHOLDER_IMAGE = '/assets/images/logontu.png';
    const refs = {
        tabs: Array.from(document.querySelectorAll('[data-catalog-tab]')),
        panels: Array.from(document.querySelectorAll('[data-catalog-panel]')),
        tbody: document.getElementById('servicesTableBody'),
        form: document.getElementById('serviceForm'),
        modalElement: document.getElementById('serviceModal'),
        statusFilter: document.getElementById('serviceStatusFilter'),
        pageSize: document.getElementById('servicePageSize'),
        prevPage: document.getElementById('btnPrevServicePage'),
        nextPage: document.getElementById('btnNextServicePage'),
        pageIndicator: document.getElementById('servicePageIndicator'),
        paginationSummary: document.getElementById('servicePaginationSummary'),
        refresh: document.getElementById('btnRefreshServices'),
        add: document.getElementById('btnAddService'),
        statTotal: document.getElementById('serviceStatTotal'),
        statTotalMeta: document.getElementById('serviceStatTotalMeta'),
        statActive: document.getElementById('serviceStatActive'),
        statActiveMeta: document.getElementById('serviceStatActiveMeta'),
        statHidden: document.getElementById('serviceStatHidden'),
        statHiddenMeta: document.getElementById('serviceStatHiddenMeta'),
    };

    const fields = {
        id: document.getElementById('serviceId'),
        name: document.getElementById('serviceName'),
        desc: document.getElementById('serviceDesc'),
        image: document.getElementById('serviceImage'),
        preview: document.getElementById('serviceImagePreview'),
        removeImage: document.getElementById('btnRemoveServiceImage'),
        active: document.getElementById('serviceActive'),
        label: document.getElementById('serviceModalLabel'),
        save: document.getElementById('btnSaveService'),
    };

    const modal = refs.modalElement ? new bootstrap.Modal(refs.modalElement) : null;
    const number = new Intl.NumberFormat('vi-VN');
    const state = {
        items: [],
        currentImageUrl: '',
        removeCurrentImage: false,
        localPreviewUrl: null,
        activeTab: 'services',
        currentPage: 1,
        pageSize: Number(refs.pageSize?.value || 10),
    };

    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');

    const formatCount = (value) => number.format(Number(value || 0));

    const formatDateTime = (value) => {
        if (!value) {
            return '--';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '--';
        }

        return new Intl.DateTimeFormat('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    };

    const revokePreview = () => {
        if (!state.localPreviewUrl) {
            return;
        }

        URL.revokeObjectURL(state.localPreviewUrl);
        state.localPreviewUrl = null;
    };

    const updatePreview = (src = '') => {
        if (!fields.preview) {
            return;
        }

        fields.preview.src = src || (!state.removeCurrentImage && state.currentImageUrl ? state.currentImageUrl : PLACEHOLDER_IMAGE);
    };

    const setLoading = (isLoading) => {
        if (!fields.save) {
            return;
        }

        fields.save.disabled = isLoading;
        fields.save.innerHTML = isLoading
            ? '<i class="fas fa-spinner fa-spin me-2"></i>Đang lưu...'
            : '<i class="fas fa-check-circle me-2"></i>Lưu thay đổi dịch vụ';
    };

    const buildQuery = () => {
        const status = refs.statusFilter?.value;
        return status !== '' ? `?status=${status}` : '';
    };

    const resetPagination = () => {
        state.currentPage = 1;
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

    const syncActiveTabToUrl = () => {
        const url = new URL(window.location.href);

        if (state.activeTab === 'parts') {
            url.searchParams.set('tab', 'parts');
        } else {
            url.searchParams.delete('tab');
        }

        window.history.replaceState({}, '', url);
    };

    const applyActiveTab = (tabName) => {
        state.activeTab = tabName === 'parts' ? 'parts' : 'services';

        refs.tabs.forEach((tab) => {
            const isActive = tab.dataset.catalogTab === state.activeTab;
            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        refs.panels.forEach((panel) => {
            panel.classList.toggle('is-active', panel.dataset.catalogPanel === state.activeTab);
        });
    };

    const openCreateModal = () => {
        refs.form?.reset();

        if (!fields.id) {
            return;
        }

        fields.id.value = '';
        fields.active.checked = true;
        fields.label.textContent = 'Thêm dịch vụ mới';
        state.currentImageUrl = '';
        state.removeCurrentImage = false;
        revokePreview();
        updatePreview();
        setLoading(false);
    };

    const openEditModal = (service) => {
        if (!service || !modal) {
            return;
        }

        refs.form?.reset();
        fields.id.value = service.id || '';
        fields.name.value = service.ten_dich_vu || '';
        fields.desc.value = service.mo_ta || '';
        fields.image.value = '';
        fields.active.checked = Number(service.trang_thai) === 1;
        fields.label.textContent = 'Cập nhật dịch vụ';
        state.currentImageUrl = service.hinh_anh || '';
        state.removeCurrentImage = false;
        revokePreview();
        updatePreview();
        modal.show();
    };

    const renderTable = (items) => {
        if (!refs.tbody) {
            return;
        }

        if (!items.length) {
            refs.tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        Không có dịch vụ nào phù hợp.
                    </td>
                </tr>
            `;
            return;
        }

        refs.tbody.innerHTML = items.map((service) => {
            const image = service.hinh_anh || PLACEHOLDER_IMAGE;
            const statusBadge = Number(service.trang_thai) === 1
                ? '<span class="catalog-status catalog-status--active">Đang hoạt động</span>'
                : '<span class="catalog-status catalog-status--inactive">Đã ẩn</span>';
            const serviceCode = `DV-${String(service.id).padStart(4, '0')}`;

            return `
                <tr>
                    <td class="ps-4">
                        <span class="catalog-code">${serviceCode}</span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <img src="${escapeHtml(image)}" alt="${escapeHtml(service.ten_dich_vu || 'Dịch vụ')}" class="service-thumb" onerror="this.src='${PLACEHOLDER_IMAGE}'">
                            <div>
                                <div class="catalog-name">${escapeHtml(service.ten_dich_vu || '--')}</div>
                                <span class="catalog-meta">Ảnh đại diện và thông tin hiển thị</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="service-desc">
                            ${escapeHtml(service.mo_ta || '--')}
                        </div>
                    </td>
                    <td>${statusBadge}</td>
                    <td><span class="catalog-updated">${escapeHtml(formatDateTime(service.updated_at))}</span></td>
                    <td class="text-end pe-4">
                        <div class="catalog-actions">
                            <button type="button" class="catalog-action-btn" data-action="edit" data-id="${service.id}" title="Sửa dịch vụ" aria-label="Sửa dịch vụ">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" class="catalog-action-btn catalog-action-btn--danger" data-action="delete" data-id="${service.id}" title="Xóa dịch vụ" aria-label="Xóa dịch vụ">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    };

    const renderStats = () => {
        const total = state.items.length;
        const active = state.items.filter((service) => Number(service.trang_thai) === 1).length;
        const hidden = Math.max(total - active, 0);

        if (refs.statTotal) {
            refs.statTotal.textContent = formatCount(total);
        }

        if (refs.statTotalMeta) {
            refs.statTotalMeta.textContent = `${formatCount(total)} mục trong danh mục hiện tại`;
        }

        if (refs.statActive) {
            refs.statActive.textContent = formatCount(active);
        }

        if (refs.statActiveMeta) {
            refs.statActiveMeta.textContent = active > 0
                ? `${formatCount(active)} dịch vụ đang mở cho đặt lịch`
                : 'Chưa có dịch vụ đang hoạt động';
        }

        if (refs.statHidden) {
            refs.statHidden.textContent = formatCount(hidden);
        }

        if (refs.statHiddenMeta) {
            refs.statHiddenMeta.textContent = hidden > 0
                ? `${formatCount(hidden)} dịch vụ tạm ẩn`
                : 'Tất cả dịch vụ đang hiển thị';
        }
    };

    const renderPagination = (pageData) => {
        if (!refs.paginationSummary || !refs.pageIndicator || !refs.prevPage || !refs.nextPage) {
            return;
        }

        if (!pageData.total) {
            refs.paginationSummary.textContent = 'Đang hiển thị 0 / 0 dịch vụ';
            refs.pageIndicator.textContent = 'Trang 1 / 1';
            refs.prevPage.disabled = true;
            refs.nextPage.disabled = true;
            return;
        }

        refs.paginationSummary.textContent = `Đang hiển thị ${formatCount(pageData.startIndex + 1)}-${formatCount(pageData.endIndex)} / ${formatCount(pageData.total)} dịch vụ`;
        refs.pageIndicator.textContent = `Trang ${formatCount(state.currentPage)} / ${formatCount(pageData.totalPages)}`;
        refs.prevPage.disabled = state.currentPage <= 1;
        refs.nextPage.disabled = state.currentPage >= pageData.totalPages;
    };

    const renderCatalog = () => {
        renderStats();
        const pageData = paginateItems(state.items);
        renderTable(pageData.items);
        renderPagination(pageData);
    };

    const fetchServices = async () => {
        if (!refs.tbody) {
            return;
        }

        refs.tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 mb-0">Đang tải dịch vụ...</p>
                </td>
            </tr>
        `;

        try {
            const response = await callApi(`/admin/services${buildQuery()}`);

            if (!response?.ok) {
                throw new Error(response?.data?.message || 'Không thể tải danh sách dịch vụ');
            }

            state.items = Array.isArray(response.data?.data) ? response.data.data : [];
            renderCatalog();
        } catch (error) {
            console.error('Load services failed:', error);
            state.items = [];
            renderCatalog();
            refs.tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-danger">
                        Không tải được danh sách dịch vụ.
                    </td>
                </tr>
            `;
            showToast(error.message || 'Không thể tải danh sách dịch vụ', 'error');
        }
    };

    const deleteService = async (service) => {
        if (!service) {
            return;
        }

        const confirmation = await Swal.fire({
            title: 'Xóa dịch vụ?',
            text: `Dịch vụ "${service.ten_dich_vu}" sẽ bị xóa khỏi danh mục.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#dc2626',
        });

        if (!confirmation.isConfirmed) {
            return;
        }

        const response = await callApi(`/admin/services/${service.id}`, 'DELETE');

        if (!response?.ok) {
            showToast(response?.data?.message || 'Không xóa được dịch vụ', 'error');
            return;
        }

        showToast(response.data?.message || 'Đã xóa dịch vụ');

        if (state.items.length === 1 && state.currentPage > 1) {
            state.currentPage -= 1;
        }

        await fetchServices();
    };

    refs.tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            applyActiveTab(tab.dataset.catalogTab);
            syncActiveTabToUrl();
        });
    });

    refs.add?.addEventListener('click', () => {
        applyActiveTab('services');
        syncActiveTabToUrl();
        openCreateModal();
    });

    refs.statusFilter?.addEventListener('change', () => {
        resetPagination();
        fetchServices();
    });

    refs.pageSize?.addEventListener('change', () => {
        state.pageSize = Number(refs.pageSize.value || 10);
        resetPagination();
        renderCatalog();
    });

    refs.prevPage?.addEventListener('click', () => {
        if (state.currentPage <= 1) {
            return;
        }

        state.currentPage -= 1;
        renderCatalog();
    });

    refs.nextPage?.addEventListener('click', () => {
        state.currentPage += 1;
        renderCatalog();
    });

    refs.refresh?.addEventListener('click', fetchServices);

    refs.tbody?.addEventListener('click', async (event) => {
        const button = event.target instanceof Element
            ? event.target.closest('button[data-action]')
            : null;
        if (!button) {
            return;
        }

        const service = state.items.find((item) => String(item.id) === String(button.dataset.id));

        if (button.dataset.action === 'edit') {
            applyActiveTab('services');
            syncActiveTabToUrl();
            openEditModal(service);
            return;
        }

        if (button.dataset.action === 'delete') {
            await deleteService(service);
        }
    });

    fields.preview?.addEventListener('error', () => {
        fields.preview.src = PLACEHOLDER_IMAGE;
    });

    fields.image?.addEventListener('change', () => {
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

    fields.removeImage?.addEventListener('click', () => {
        fields.image.value = '';
        revokePreview();
        state.removeCurrentImage = true;
        updatePreview();
    });

    refs.modalElement?.addEventListener('hidden.bs.modal', () => {
        revokePreview();
        updatePreview();
    });

    refs.form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        setLoading(true);

        const serviceId = fields.id.value.trim();
        const endpoint = serviceId ? `/admin/services/${serviceId}` : '/admin/services';
        const formData = new FormData();

        formData.append('ten_dich_vu', fields.name.value.trim());
        formData.append('mo_ta', fields.desc.value.trim());
        formData.append('trang_thai', fields.active.checked ? '1' : '0');

        const [imageFile] = fields.image.files || [];
        if (imageFile) {
            formData.append('hinh_anh', imageFile);
        }

        if (serviceId) {
            formData.append('_method', 'PUT');

            if (state.removeCurrentImage && !imageFile) {
                formData.append('remove_image', '1');
            }
        }

        try {
            const response = await callApi(endpoint, 'POST', formData);

            if (!response?.ok) {
                showToast(response?.data?.message || 'Không lưu được dịch vụ', 'error');
                return;
            }

            showToast(response.data?.message || 'Đã lưu dịch vụ');
            modal?.hide();
            openCreateModal();
            await fetchServices();
        } finally {
            setLoading(false);
        }
    });

    const requestedTab = new URL(window.location.href).searchParams.get('tab');
    applyActiveTab(requestedTab === 'parts' ? 'parts' : 'services');
    openCreateModal();
    fetchServices();
});
