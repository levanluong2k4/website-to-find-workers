import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const PLACEHOLDER_IMAGE = '/assets/images/logontu.png';
    const refs = {
        tbody: document.getElementById('servicesTableBody'),
        form: document.getElementById('serviceForm'),
        modalElement: document.getElementById('serviceModal'),
        statusFilter: document.getElementById('serviceStatusFilter'),
        refresh: document.getElementById('btnRefreshServices'),
        add: document.getElementById('btnAddService'),
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

    const modal = new bootstrap.Modal(refs.modalElement);
    const state = {
        items: [],
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
            ? '<i class="fas fa-spinner fa-spin me-2"></i>\u0110ang l\u01b0u...'
            : '<i class="fas fa-check-circle me-2"></i>L\u01b0u thay \u0111\u1ed3i d\u1ecbch v\u1ee5';
    };

    const buildQuery = () => {
        const status = refs.statusFilter.value;
        return status !== '' ? `?status=${status}` : '';
    };

    const openCreateModal = () => {
        refs.form.reset();
        fields.id.value = '';
        fields.active.checked = true;
        fields.label.textContent = 'Th\u00eam d\u1ecbch v\u1ee5 m\u1edbi';
        state.currentImageUrl = '';
        state.removeCurrentImage = false;
        revokePreview();
        updatePreview();
    };

    const openEditModal = (service) => {
        if (!service) {
            return;
        }

        refs.form.reset();
        fields.id.value = service.id || '';
        fields.name.value = service.ten_dich_vu || '';
        fields.desc.value = service.mo_ta || '';
        fields.image.value = '';
        fields.active.checked = Number(service.trang_thai) === 1;
        fields.label.textContent = 'S\u1eeda d\u1ecbch v\u1ee5 m\u1edbi';
        state.currentImageUrl = service.hinh_anh || '';
        state.removeCurrentImage = false;
        revokePreview();
        updatePreview();
        modal.show();
    };

    const renderTable = () => {
        if (!state.items.length) {
            refs.tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        Kh\u00f4ng c\u00f3 d\u1ecbch v\u1ee5 n\u00e0o.
                    </td>
                </tr>
            `;
            return;
        }

        refs.tbody.innerHTML = state.items.map((service) => {
            const image = service.hinh_anh || PLACEHOLDER_IMAGE;
            const statusBadge = Number(service.trang_thai) === 1
                ? '<span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill font-small">\u0110ang ho\u1ea1t \u0111\u1ed9ng</span>'
                : '<span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill font-small">\u0110\u00e3 \u1ea9n</span>';

            return `
                <tr>
                    <td class="ps-4">
                        <span class="text-muted fw-bold small">#${service.id}</span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <img src="${escapeHtml(image)}" alt="${escapeHtml(service.ten_dich_vu || 'Dich vu')}" class="service-thumb" onerror="this.src='${PLACEHOLDER_IMAGE}'">
                            <span class="fw-bold text-dark">${escapeHtml(service.ten_dich_vu || '--')}</span>
                        </div>
                    </td>
                    <td>
                        <div class="text-muted small text-truncate" style="max-width: 300px;">
                            ${escapeHtml(service.mo_ta || '--')}
                        </div>
                    </td>
                    <td>${statusBadge}</td>
                    <td class="text-end pe-4">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm btn-lumina btn-lumina-secondary px-3" data-action="edit" data-id="${service.id}">
                                <i class="fas fa-edit me-1"></i>S\u1eeda
                            </button>
                            <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0 px-2" data-action="delete" data-id="${service.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    };

    const fetchServices = async () => {
        refs.tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 mb-0">\u0110ang t\u1ea3i d\u1ecbch v\u1ee5...</p>
                </td>
            </tr>
        `;

        try {
            const response = await callApi(`/admin/services${buildQuery()}`);

            if (!response?.ok) {
                throw new Error(response?.data?.message || 'Kh\u00f4ng th\u1ec3 t\u1ea3i danh s\u00e1ch d\u1ecbch v\u1ee5');
            }

            state.items = Array.isArray(response.data?.data) ? response.data.data : [];
            renderTable();
        } catch (error) {
            console.error('Load services failed:', error);
            refs.tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-danger">
                        Kh\u00f4ng t\u1ea3i \u0111\u01b0\u1ee3c danh s\u00e1ch d\u1ecbch v\u1ee5.
                    </td>
                </tr>
            `;
            showToast(error.message || 'Kh\u00f4ng th\u1ec3 t\u1ea3i danh s\u00e1ch d\u1ecbch v\u1ee5', 'error');
        }
    };

    const deleteService = async (service) => {
        if (!service) {
            return;
        }

        const confirmation = await Swal.fire({
            title: 'X\u00f3a d\u1ecbch v\u1ee5?',
            text: `D\u1ecbch v\u1ee5 "${service.ten_dich_vu}" s\u1ebd b\u1ecb x\u00f3a kh\u1ecfi danh m\u1ee5c.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'X\u00f3a',
            cancelButtonText: 'H\u1ee7y',
            confirmButtonColor: '#dc2626',
        });

        if (!confirmation.isConfirmed) {
            return;
        }

        const response = await callApi(`/admin/services/${service.id}`, 'DELETE');

        if (!response?.ok) {
            showToast(response?.data?.message || 'Kh\u00f4ng x\u00f3a \u0111\u01b0\u1ee3c d\u1ecbch v\u1ee5', 'error');
            return;
        }

        showToast(response.data?.message || '\u0110\u00e3 x\u00f3a d\u1ecbch v\u1ee5');
        await fetchServices();
    };

    refs.add.addEventListener('click', openCreateModal);
    refs.statusFilter.addEventListener('change', fetchServices);
    refs.refresh.addEventListener('click', fetchServices);

    refs.tbody.addEventListener('click', async (event) => {
        const button = event.target instanceof Element
            ? event.target.closest('button[data-action]')
            : null;
        if (!button) {
            return;
        }

        const service = state.items.find((item) => String(item.id) === String(button.dataset.id));

        if (button.dataset.action === 'edit') {
            openEditModal(service);
            return;
        }

        if (button.dataset.action === 'delete') {
            await deleteService(service);
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
                showToast(response?.data?.message || 'Kh\u00f4ng l\u01b0u \u0111\u01b0\u1ee3c d\u1ecbch v\u1ee5', 'error');
                return;
            }

            showToast(response.data?.message || '\u0110\u00e3 l\u01b0u d\u1ecbch v\u1ee5');
            modal.hide();
            openCreateModal();
            await fetchServices();
        } finally {
            setLoading(false);
        }
    });

    openCreateModal();
    fetchServices();
});
