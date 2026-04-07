import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const PLACEHOLDER_IMAGE = '/assets/images/logontu.png';
    const tbody = document.getElementById('servicesTableBody');
    const form = document.getElementById('serviceForm');
    const modalElement = document.getElementById('serviceModal');
    const modal = new bootstrap.Modal(modalElement);
    const statusFilter = document.getElementById('serviceStatusFilter');
    const btnRefresh = document.getElementById('btnRefreshServices');
    const btnAdd = document.getElementById('btnAddService');

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

    let currentImageUrl = '';
    let removeCurrentImage = false;
    let localPreviewUrl = null;

    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');

    const revokeLocalPreview = () => {
        if (!localPreviewUrl) {
            return;
        }

        URL.revokeObjectURL(localPreviewUrl);
        localPreviewUrl = null;
    };

    const updatePreview = (src = '') => {
        fields.preview.src = src || (!removeCurrentImage && currentImageUrl ? currentImageUrl : PLACEHOLDER_IMAGE);
    };

    const setLoading = (isLoading) => {
        fields.save.disabled = isLoading;
        fields.save.innerHTML = isLoading
            ? '<i class="fas fa-spinner fa-spin me-2"></i>\u0110ang l\u01b0u...'
            : '<i class="fas fa-save me-2"></i>L\u01b0u d\u1ecbch v\u1ee5';
    };

    const resetForm = () => {
        form.reset();
        fields.id.value = '';
        fields.active.checked = true;
        fields.label.textContent = 'Th\u00eam d\u1ecbch v\u1ee5';
        currentImageUrl = '';
        removeCurrentImage = false;
        revokeLocalPreview();
        updatePreview();
    };

    const renderTable = (services) => {
        if (!services.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        Kh\u00f4ng c\u00f3 d\u1ecbch v\u1ee5 n\u00e0o.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = services.map((service) => {
            const image = service.hinh_anh || PLACEHOLDER_IMAGE;
            const statusBadge = Number(service.trang_thai) === 1
                ? '<span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">\u0110ang ho\u1ea1t \u0111\u1ed9ng</span>'
                : '<span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill">\u0110\u00e3 \u1ea9n</span>';

            return `
                <tr>
                    <td class="ps-4 fw-semibold text-muted">#${service.id}</td>
                    <td><img src="${escapeHtml(image)}" alt="${escapeHtml(service.ten_dich_vu)}" class="service-thumb" onerror="this.src='${PLACEHOLDER_IMAGE}'"></td>
                    <td class="fw-semibold">${escapeHtml(service.ten_dich_vu)}</td>
                    <td class="text-muted">${escapeHtml(service.mo_ta || '--')}</td>
                    <td>${statusBadge}</td>
                    <td class="text-end pe-4">
                        <button
                            class="btn btn-sm btn-outline-primary me-1 btn-edit-service"
                            data-id="${service.id}"
                            data-name="${escapeHtml(service.ten_dich_vu)}"
                            data-desc="${escapeHtml(service.mo_ta || '')}"
                            data-image="${escapeHtml(service.hinh_anh || '')}"
                            data-active="${Number(service.trang_thai) === 1 ? '1' : '0'}"
                            title="S\u1eeda d\u1ecbch v\u1ee5"
                        <button class="btn btn-sm btn-outline-danger btn-delete-service" data-id="${service.id}" data-name="${escapeHtml(service.ten_dich_vu)}" title="X\u00f3a d\u1ecbch v\u1ee5">
                            <i class="fas fa-trash me-1"></i>X\u00f3a
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        document.querySelectorAll('.btn-edit-service').forEach((button) => {
            button.addEventListener('click', () => {
                fields.label.textContent = 'S\u1eeda d\u1ecbch v\u1ee5';
                fields.id.value = button.dataset.id || '';
                fields.name.value = button.dataset.name || '';
                fields.desc.value = button.dataset.desc || '';
                fields.image.value = '';
                fields.active.checked = button.dataset.active === '1';
                currentImageUrl = button.dataset.image || '';
                removeCurrentImage = false;
                revokeLocalPreview();
                updatePreview();
                modal.show();
            });
        });

        document.querySelectorAll('.btn-delete-service').forEach((button) => {
            button.addEventListener('click', async () => {
                const confirmed = await Swal.fire({
                    title: 'X\u00f3a d\u1ecbch v\u1ee5?',
                    text: `D\u1ecbch v\u1ee5 "${button.dataset.name}" s\u1ebd b\u1ecb \u1ea9n kh\u1ecfi h\u1ec7 th\u1ed1ng.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'X\u00f3a',
                    cancelButtonText: 'H\u1ee7y',
                    confirmButtonColor: '#dc2626',
                });

                if (!confirmed.isConfirmed) {
                    return;
                }

                const res = await callApi(`/admin/services/${button.dataset.id}`, 'DELETE');
                if (!res.ok) {
                    showToast(res.data?.message || 'Kh\u00f4ng x\u00f3a \u0111\u01b0\u1ee3c d\u1ecbch v\u1ee5', 'error');
                    return;
                }

                showToast(res.data?.message || '\u0110\u00e3 x\u00f3a d\u1ecbch v\u1ee5');
                await fetchServices();
            });
        });
    };

    const fetchServices = async () => {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 mb-0">\u0110ang t\u1ea3i d\u1ecbch v\u1ee5...</p>
                </td>
            </tr>
        `;

        const query = statusFilter.value !== '' ? `?status=${statusFilter.value}` : '';
        const res = await callApi(`/admin/services${query}`);

        if (!res.ok) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-danger">
                        Kh\u00f4ng t\u1ea3i \u0111\u01b0\u1ee3c danh s\u00e1ch d\u1ecbch v\u1ee5.
                    </td>
                </tr>
            `;
            return;
        }

        renderTable(Array.isArray(res.data?.data) ? res.data.data : []);
    };

    btnAdd.addEventListener('click', resetForm);
    statusFilter.addEventListener('change', fetchServices);
    btnRefresh.addEventListener('click', fetchServices);
    fields.preview.addEventListener('error', () => {
        fields.preview.src = PLACEHOLDER_IMAGE;
    });
    fields.image.addEventListener('change', () => {
        revokeLocalPreview();

        const [file] = fields.image.files || [];
        if (!file) {
            updatePreview();
            return;
        }

        removeCurrentImage = false;
        localPreviewUrl = URL.createObjectURL(file);
        updatePreview(localPreviewUrl);
    });
    fields.removeImage.addEventListener('click', () => {
        fields.image.value = '';
        revokeLocalPreview();
        removeCurrentImage = true;
        updatePreview();
    });
    modalElement.addEventListener('hidden.bs.modal', () => {
        revokeLocalPreview();
        updatePreview();
    });

    form.addEventListener('submit', async (event) => {
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

            if (removeCurrentImage && !imageFile) {
                formData.append('remove_image', '1');
            }
        }

        try {
            const res = await callApi(endpoint, 'POST', formData);
            if (!res.ok) {
                showToast(res.data?.message || 'Kh\u00f4ng l\u01b0u \u0111\u01b0\u1ee3c d\u1ecbch v\u1ee5', 'error');
                return;
            }

            showToast(res.data?.message || '\u0110\u00e3 l\u01b0u d\u1ecbch v\u1ee5');
            modal.hide();
            resetForm();
            await fetchServices();
        } finally {
            setLoading(false);
        }
    });

    fetchServices();
});
