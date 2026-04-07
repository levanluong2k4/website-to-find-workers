import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const PLACEHOLDER_IMAGE = '/assets/images/logontu.png';
    const refs = {
        search: document.getElementById('partSearchInput'),
        serviceFilter: document.getElementById('partServiceFilter'),
        refresh: document.getElementById('btnRefreshParts'),
        add: document.getElementById('btnAddPart'),
        tbody: document.getElementById('partsTableBody'),
        statTotal: document.getElementById('partStatTotal'),
        statPriced: document.getElementById('partStatPriced'),
        form: document.getElementById('partForm'),
        modalElement: document.getElementById('partModal'),
    };

    const fields = {
        id: document.getElementById('partId'),
        service: document.getElementById('partService'),
        name: document.getElementById('partName'),
        price: document.getElementById('partPrice'),
        image: document.getElementById('partImage'),
        preview: document.getElementById('partImagePreview'),
        removeImage: document.getElementById('btnRemovePartImage'),
        save: document.getElementById('btnSavePart'),
        label: document.getElementById('partModalLabel'),
    };

    const modal = new bootstrap.Modal(refs.modalElement);
    const number = new Intl.NumberFormat('vi-VN');
    const state = {
        items: [],
        services: [],
        searchTimer: null,
        currentImageUrl: '',
        removeCurrentImage: false,
        localPreviewUrl: null,
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

        return `${number.format(Number(value || 0))} đ`;
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
            ? '<i class="fas fa-spinner fa-spin me-2"></i>Dang luu...'
            : '<i class="fas fa-save me-2"></i>Luu linh kien';
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
        const params = new URLSearchParams(buildQuery().replace(/^\?/, ''));

        url.search = params.toString();
        window.history.replaceState({}, '', url);
    };

    const populateServiceOptions = () => {
        const selectedFilter = refs.serviceFilter.value || refs.serviceFilter.dataset.selected || '';
        const selectedForm = fields.service.value || fields.service.dataset.selected || '';
        const options = state.services.map((service) => `
            <option value="${service.id}">${escapeHtml(service.ten_dich_vu)}</option>
        `).join('');

        refs.serviceFilter.innerHTML = `<option value="">Tat ca dich vu</option>${options}`;
        fields.service.innerHTML = `<option value="">Chon dich vu</option>${options}`;

        refs.serviceFilter.value = state.services.some((service) => String(service.id) === selectedFilter) ? selectedFilter : '';
        fields.service.value = state.services.some((service) => String(service.id) === selectedForm) ? selectedForm : '';
    };

    const renderStats = (summary) => {
        refs.statTotal.textContent = number.format(Number(summary?.total || 0));
        refs.statPriced.textContent = number.format(Number(summary?.priced || 0));
    };

    const openCreateModal = () => {
        refs.form.reset();
        fields.id.value = '';
        fields.service.dataset.selected = '';
        fields.label.textContent = 'Them linh kien';
        state.currentImageUrl = '';
        state.removeCurrentImage = false;
        revokePreview();
        updatePreview();
        populateServiceOptions();
    };

    const openEditModal = (item) => {
        if (!item) {
            return;
        }

        refs.form.reset();
        fields.id.value = item.id;
        fields.service.dataset.selected = String(item.dich_vu_id || '');
        fields.name.value = item.ten_linh_kien || '';
        fields.price.value = item.gia ?? '';
        fields.label.textContent = 'Sua linh kien';
        state.currentImageUrl = item.hinh_anh || '';
        state.removeCurrentImage = false;
        revokePreview();
        populateServiceOptions();
        updatePreview();
        modal.show();
    };

    const renderTable = () => {
        if (!state.items.length) {
            refs.tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">Khong co linh kien phu hop.</td>
                </tr>
            `;
            return;
        }

        refs.tbody.innerHTML = state.items.map((item) => `
            <tr>
                <td class="ps-4 fw-semibold text-muted">#${item.id}</td>
                <td>
                    <img src="${escapeHtml(item.hinh_anh || PLACEHOLDER_IMAGE)}" alt="${escapeHtml(item.ten_linh_kien || 'Linh kien')}" class="part-thumb" onerror="this.src='${PLACEHOLDER_IMAGE}'">
                </td>
                <td class="fw-semibold">${escapeHtml(item.ten_linh_kien || '--')}</td>
                <td>${escapeHtml(item.service_name || '--')}</td>
                <td>${escapeHtml(item.gia_label || formatMoney(item.gia))}</td>
                <td class="text-muted">${escapeHtml(item.updated_label || '--')}</td>
                <td class="text-end pe-4">
                    <button type="button" class="btn btn-sm btn-outline-primary me-1" data-action="edit" data-id="${item.id}">
                        <i class="fas fa-edit me-1"></i>Sua
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${item.id}">
                        <i class="fas fa-trash me-1"></i>Xoa
                    </button>
                </td>
            </tr>
        `).join('');
    };

    const fetchParts = async () => {
        refs.tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 mb-0">Dang tai linh kien...</p>
                </td>
            </tr>
        `;

        syncUrl();

        try {
            const response = await callApi(`/admin/linh-kien${buildQuery()}`);

            if (!response?.ok) {
                throw new Error(response?.data?.message || 'Khong the tai danh sach linh kien');
            }

            const payload = response.data?.data || {};
            state.items = Array.isArray(payload.items) ? payload.items : [];
            state.services = Array.isArray(payload.service_options) ? payload.service_options : [];
            populateServiceOptions();
            renderStats(payload.summary || {});
            renderTable();
        } catch (error) {
            console.error('Load parts failed:', error);
            refs.tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5 text-danger">Khong the tai danh sach linh kien.</td>
                </tr>
            `;
            showToast(error.message || 'Khong the tai danh sach linh kien', 'error');
        }
    };

    const deletePart = async (item) => {
        if (!item) {
            return;
        }

        const confirmation = await Swal.fire({
            title: 'Xoa linh kien?',
            text: `Linh kien "${item.ten_linh_kien}" se bi xoa khoi danh muc.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Xoa',
            cancelButtonText: 'Huy',
            confirmButtonColor: '#dc2626',
        });

        if (!confirmation.isConfirmed) {
            return;
        }

        const response = await callApi(`/admin/linh-kien/${item.id}`, 'DELETE');

        if (!response?.ok) {
            showToast(response?.data?.message || 'Khong xoa duoc linh kien', 'error');
            return;
        }

        showToast(response.data?.message || 'Da xoa linh kien');
        await fetchParts();
    };

    refs.add.addEventListener('click', openCreateModal);
    refs.refresh.addEventListener('click', fetchParts);
    refs.serviceFilter.addEventListener('change', fetchParts);
    refs.search.addEventListener('input', () => {
        window.clearTimeout(state.searchTimer);
        state.searchTimer = window.setTimeout(fetchParts, 250);
    });

    refs.tbody.addEventListener('click', async (event) => {
        const button = event.target.closest('button[data-action]');
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

    fields.image.addEventListener('change', () => {
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
        fields.image.value = '';
        revokePreview();
        state.removeCurrentImage = true;
        updatePreview();
    });

    refs.modalElement.addEventListener('hidden.bs.modal', () => {
        revokePreview();
        updatePreview();
    });

    refs.form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setLoading(true);

        const partId = fields.id.value.trim();
        const endpoint = partId ? `/admin/linh-kien/${partId}` : '/admin/linh-kien';
        const formData = new FormData();

        formData.append('dich_vu_id', fields.service.value);
        formData.append('ten_linh_kien', fields.name.value.trim());
        formData.append('gia', fields.price.value.trim());

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
                showToast(response?.data?.message || 'Khong luu duoc linh kien', 'error');
                return;
            }

            showToast(response.data?.message || 'Da luu linh kien');
            modal.hide();
            openCreateModal();
            await fetchParts();
        } finally {
            setLoading(false);
        }
    });

    syncFiltersFromUrl();
    openCreateModal();
    fetchParts();
});
