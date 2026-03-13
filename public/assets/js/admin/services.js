import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

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
        active: document.getElementById('serviceActive'),
        label: document.getElementById('serviceModalLabel'),
        save: document.getElementById('btnSaveService'),
    };

    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');

    const setLoading = (isLoading) => {
        fields.save.disabled = isLoading;
        fields.save.innerHTML = isLoading
            ? '<i class="fas fa-spinner fa-spin me-2"></i>Dang luu...'
            : '<i class="fas fa-save me-2"></i>Luu dich vu';
    };

    const resetForm = () => {
        form.reset();
        fields.id.value = '';
        fields.active.checked = true;
        fields.label.textContent = 'Them dich vu';
    };

    const renderTable = (services) => {
        if (!services.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        Khong co dich vu nao.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = services.map((service) => {
            const image = service.hinh_anh || '/assets/images/placeholder.png';
            const statusBadge = Number(service.trang_thai) === 1
                ? '<span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">Dang hoat dong</span>'
                : '<span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill">Da an</span>';

            return `
                <tr>
                    <td class="ps-4 fw-semibold text-muted">#${service.id}</td>
                    <td><img src="${escapeHtml(image)}" alt="${escapeHtml(service.ten_dich_vu)}" class="service-thumb"></td>
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
                        >
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete-service" data-id="${service.id}" data-name="${escapeHtml(service.ten_dich_vu)}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        document.querySelectorAll('.btn-edit-service').forEach((button) => {
            button.addEventListener('click', () => {
                fields.label.textContent = 'Sua dich vu';
                fields.id.value = button.dataset.id || '';
                fields.name.value = button.dataset.name || '';
                fields.desc.value = button.dataset.desc || '';
                fields.image.value = button.dataset.image || '';
                fields.active.checked = button.dataset.active === '1';
                modal.show();
            });
        });

        document.querySelectorAll('.btn-delete-service').forEach((button) => {
            button.addEventListener('click', async () => {
                const confirmed = await Swal.fire({
                    title: 'Xoa dich vu?',
                    text: `Dich vu "${button.dataset.name}" se bi an khoi he thong.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Xoa',
                    cancelButtonText: 'Huy',
                    confirmButtonColor: '#dc2626',
                });

                if (!confirmed.isConfirmed) {
                    return;
                }

                const res = await callApi(`/admin/services/${button.dataset.id}`, 'DELETE');
                if (!res.ok) {
                    showToast(res.data?.message || 'Khong xoa duoc dich vu', 'error');
                    return;
                }

                showToast(res.data?.message || 'Da xoa dich vu');
                await fetchServices();
            });
        });
    };

    const fetchServices = async () => {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 mb-0">Dang tai dich vu...</p>
                </td>
            </tr>
        `;

        const query = statusFilter.value !== '' ? `?status=${statusFilter.value}` : '';
        const res = await callApi(`/admin/services${query}`);

        if (!res.ok) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-danger">
                        Khong tai duoc danh sach dich vu.
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

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setLoading(true);

        const payload = {
            ten_dich_vu: fields.name.value.trim(),
            mo_ta: fields.desc.value.trim(),
            hinh_anh: fields.image.value.trim(),
            trang_thai: fields.active.checked,
        };

        const serviceId = fields.id.value.trim();
        const endpoint = serviceId ? `/admin/services/${serviceId}` : '/admin/services';
        const method = serviceId ? 'PUT' : 'POST';

        try {
            const res = await callApi(endpoint, method, payload);
            if (!res.ok) {
                showToast(res.data?.message || 'Khong luu duoc dich vu', 'error');
                return;
            }

            showToast(res.data?.message || 'Da luu dich vu');
            modal.hide();
            resetForm();
            await fetchServices();
        } finally {
            setLoading(false);
        }
    });

    fetchServices();
});
