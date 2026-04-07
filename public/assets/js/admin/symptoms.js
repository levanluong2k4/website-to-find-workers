import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const refs = {
        search: document.getElementById('symptomSearchInput'),
        serviceFilter: document.getElementById('symptomServiceFilter'),
        refresh: document.getElementById('btnRefreshSymptoms'),
        add: document.getElementById('btnAddSymptom'),
        tbody: document.getElementById('symptomsTableBody'),
        statTotal: document.getElementById('symptomStatTotal'),
        statCauses: document.getElementById('symptomStatCauses'),
        form: document.getElementById('symptomForm'),
        modalElement: document.getElementById('symptomModal'),
    };

    const fields = {
        id: document.getElementById('symptomId'),
        service: document.getElementById('symptomService'),
        name: document.getElementById('symptomName'),
        causes: document.getElementById('symptomCauses'),
        save: document.getElementById('btnSaveSymptom'),
        label: document.getElementById('symptomModalLabel'),
    };

    const modal = new bootstrap.Modal(refs.modalElement);
    const number = new Intl.NumberFormat('vi-VN');
    const state = {
        items: [],
        services: [],
        searchTimer: null,
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
            : '<i class="fas fa-save me-2"></i>Luu trieu chung';
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
        refs.statCauses.textContent = number.format(Number(summary?.linked_causes || 0));
    };

    const openCreateModal = () => {
        refs.form.reset();
        fields.id.value = '';
        fields.service.dataset.selected = '';
        fields.label.textContent = 'Them trieu chung';
        populateServiceOptions();
    };

    const openEditModal = (item) => {
        if (!item) {
            return;
        }

        refs.form.reset();
        fields.id.value = item.id;
        fields.service.dataset.selected = String(item.dich_vu_id || '');
        fields.name.value = item.ten_trieu_chung || '';
        fields.causes.value = item.nguyen_nhans_text || '';
        fields.label.textContent = 'Sua trieu chung';
        populateServiceOptions();
        modal.show();
    };

    const renderCauseChips = (names) => {
        if (!Array.isArray(names) || !names.length) {
            return '<span class="text-muted">Chua gan nguyen nhan</span>';
        }

        return names.slice(0, 4).map((name) => `
            <span class="symptom-chip">${escapeHtml(name)}</span>
        `).join('');
    };

    const renderTable = () => {
        if (!state.items.length) {
            refs.tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">Khong co trieu chung phu hop.</td>
                </tr>
            `;
            return;
        }

        refs.tbody.innerHTML = state.items.map((item) => `
            <tr>
                <td class="ps-4 fw-semibold text-muted">#${item.id}</td>
                <td>${escapeHtml(item.service_name || '--')}</td>
                <td class="fw-semibold">${escapeHtml(item.ten_trieu_chung || '--')}</td>
                <td>
                    <div>${renderCauseChips(item.nguyen_nhan_names)}</div>
                    <div class="text-muted small mt-2">${number.format(Number(item.nguyen_nhan_count || 0))} nguyen nhan</div>
                </td>
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

    const fetchSymptoms = async () => {
        refs.tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 mb-0">Dang tai trieu chung...</p>
                </td>
            </tr>
        `;

        syncUrl();

        try {
            const response = await callApi(`/admin/trieu-chung${buildQuery()}`);

            if (!response?.ok) {
                throw new Error(response?.data?.message || 'Khong the tai danh sach trieu chung');
            }

            const payload = response.data?.data || {};
            state.items = Array.isArray(payload.items) ? payload.items : [];
            state.services = Array.isArray(payload.service_options) ? payload.service_options : [];
            populateServiceOptions();
            renderStats(payload.summary || {});
            renderTable();
        } catch (error) {
            console.error('Load symptoms failed:', error);
            refs.tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-danger">Khong the tai danh sach trieu chung.</td>
                </tr>
            `;
            showToast(error.message || 'Khong the tai danh sach trieu chung', 'error');
        }
    };

    const deleteSymptom = async (item) => {
        if (!item) {
            return;
        }

        const confirmation = await Swal.fire({
            title: 'Xoa trieu chung?',
            text: `Trieu chung "${item.ten_trieu_chung}" se bi xoa khoi danh muc.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Xoa',
            cancelButtonText: 'Huy',
            confirmButtonColor: '#dc2626',
        });

        if (!confirmation.isConfirmed) {
            return;
        }

        const response = await callApi(`/admin/trieu-chung/${item.id}`, 'DELETE');

        if (!response?.ok) {
            showToast(response?.data?.message || 'Khong xoa duoc trieu chung', 'error');
            return;
        }

        showToast(response.data?.message || 'Da xoa trieu chung');
        await fetchSymptoms();
    };

    refs.add.addEventListener('click', openCreateModal);
    refs.refresh.addEventListener('click', fetchSymptoms);
    refs.serviceFilter.addEventListener('change', fetchSymptoms);
    refs.search.addEventListener('input', () => {
        window.clearTimeout(state.searchTimer);
        state.searchTimer = window.setTimeout(fetchSymptoms, 250);
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
            await deleteSymptom(item);
        }
    });

    refs.form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setLoading(true);

        const symptomId = fields.id.value.trim();
        const endpoint = symptomId ? `/admin/trieu-chung/${symptomId}` : '/admin/trieu-chung';
        const payload = {
            dich_vu_id: Number(fields.service.value || 0),
            ten_trieu_chung: fields.name.value.trim(),
            nguyen_nhans_text: fields.causes.value,
        };

        try {
            const response = await callApi(endpoint, symptomId ? 'PUT' : 'POST', payload);

            if (!response?.ok) {
                showToast(response?.data?.message || 'Khong luu duoc trieu chung', 'error');
                return;
            }

            showToast(response.data?.message || 'Da luu trieu chung');
            modal.hide();
            openCreateModal();
            await fetchSymptoms();
        } finally {
            setLoading(false);
        }
    });

    syncFiltersFromUrl();
    openCreateModal();
    fetchSymptoms();
});
