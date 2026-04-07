import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const refs = {
        search: document.getElementById('resolutionSearchInput'),
        serviceFilter: document.getElementById('resolutionServiceFilter'),
        causeFilter: document.getElementById('resolutionCauseFilter'),
        refresh: document.getElementById('btnRefreshResolutions'),
        add: document.getElementById('btnAddResolution'),
        tbody: document.getElementById('resolutionsTableBody'),
        statTotal: document.getElementById('resolutionStatTotal'),
        statPriced: document.getElementById('resolutionStatPriced'),
        form: document.getElementById('resolutionForm'),
        modalElement: document.getElementById('resolutionModal'),
    };

    const fields = {
        id: document.getElementById('resolutionId'),
        cause: document.getElementById('resolutionCause'),
        causeMeta: document.getElementById('resolutionCauseMeta'),
        name: document.getElementById('resolutionName'),
        price: document.getElementById('resolutionPrice'),
        description: document.getElementById('resolutionDescription'),
        save: document.getElementById('btnSaveResolution'),
        label: document.getElementById('resolutionModalLabel'),
    };

    const modal = new bootstrap.Modal(refs.modalElement);
    const number = new Intl.NumberFormat('vi-VN');
    const state = {
        items: [],
        services: [],
        causes: [],
        searchTimer: null,
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

    const buildQuery = () => {
        const params = new URLSearchParams();

        if (refs.search.value.trim()) {
            params.set('search', refs.search.value.trim());
        }

        if (refs.serviceFilter.value) {
            params.set('service_id', refs.serviceFilter.value);
        }

        if (refs.causeFilter.value) {
            params.set('cause_id', refs.causeFilter.value);
        }

        const query = params.toString();
        return query ? `?${query}` : '';
    };

    const syncFiltersFromUrl = () => {
        const url = new URL(window.location.href);

        refs.search.value = url.searchParams.get('search') || '';
        refs.serviceFilter.dataset.selected = url.searchParams.get('service_id') || '';
        refs.causeFilter.dataset.selected = url.searchParams.get('cause_id') || '';
    };

    const syncUrl = () => {
        const url = new URL(window.location.href);
        const params = new URLSearchParams(buildQuery().replace(/^\?/, ''));

        url.search = params.toString();
        window.history.replaceState({}, '', url);
    };

    const currentServiceScope = () => refs.serviceFilter.value || refs.serviceFilter.dataset.selected || '';

    const filterCausesByService = (serviceId) => {
        if (!serviceId) {
            return state.causes;
        }

        return state.causes.filter((cause) => Array.isArray(cause.service_ids) && cause.service_ids.includes(Number(serviceId)));
    };

    const causeLabel = (cause) => {
        const serviceLabel = Array.isArray(cause.service_names) && cause.service_names.length
            ? ` (${cause.service_names.join(', ')})`
            : '';

        return `${cause.ten_nguyen_nhan}${serviceLabel}`;
    };

    const populateServiceOptions = () => {
        const selectedService = refs.serviceFilter.value || refs.serviceFilter.dataset.selected || '';
        const options = state.services.map((service) => `
            <option value="${service.id}">${escapeHtml(service.ten_dich_vu)}</option>
        `).join('');

        refs.serviceFilter.innerHTML = `<option value="">Tat ca dich vu</option>${options}`;
        refs.serviceFilter.value = state.services.some((service) => String(service.id) === selectedService) ? selectedService : '';
    };

    const populateCauseOptions = () => {
        const selectedFilterCause = refs.causeFilter.value || refs.causeFilter.dataset.selected || '';
        const selectedFormCause = fields.cause.value || fields.cause.dataset.selected || '';
        const scopedCauses = filterCausesByService(currentServiceScope());
        const filterOptions = scopedCauses.map((cause) => `
            <option value="${cause.id}">${escapeHtml(causeLabel(cause))}</option>
        `).join('');
        const formOptions = state.causes.map((cause) => `
            <option value="${cause.id}">${escapeHtml(causeLabel(cause))}</option>
        `).join('');

        refs.causeFilter.innerHTML = `<option value="">Tat ca nguyen nhan</option>${filterOptions}`;
        fields.cause.innerHTML = `<option value="">Chon nguyen nhan</option>${formOptions}`;

        refs.causeFilter.value = scopedCauses.some((cause) => String(cause.id) === selectedFilterCause) ? selectedFilterCause : '';
        fields.cause.value = state.causes.some((cause) => String(cause.id) === selectedFormCause) ? selectedFormCause : '';
        updateCauseMeta();
    };

    const updateCauseMeta = () => {
        const cause = state.causes.find((item) => String(item.id) === String(fields.cause.value));

        if (!cause) {
            fields.causeMeta.textContent = 'Chon nguyen nhan da duoc tao tu danh muc trieu chung.';
            return;
        }

        const services = Array.isArray(cause.service_names) && cause.service_names.length
            ? cause.service_names.join(', ')
            : 'Chua gan dich vu';
        const symptoms = Array.isArray(cause.symptom_names) && cause.symptom_names.length
            ? cause.symptom_names.slice(0, 3).join(', ')
            : 'Chua gan trieu chung';

        fields.causeMeta.textContent = `Dich vu: ${services}. Trieu chung: ${symptoms}.`;
    };

    const renderStats = (summary) => {
        refs.statTotal.textContent = number.format(Number(summary?.total || 0));
        refs.statPriced.textContent = number.format(Number(summary?.priced || 0));
    };

    const setLoading = (isLoading) => {
        fields.save.disabled = isLoading;
        fields.save.innerHTML = isLoading
            ? '<i class="fas fa-spinner fa-spin me-2"></i>Dang luu...'
            : '<i class="fas fa-save me-2"></i>Luu huong xu ly';
    };

    const openCreateModal = () => {
        refs.form.reset();
        fields.id.value = '';
        fields.cause.dataset.selected = '';
        fields.label.textContent = 'Them huong xu ly';
        populateCauseOptions();
    };

    const openEditModal = (item) => {
        if (!item) {
            return;
        }

        refs.form.reset();
        fields.id.value = item.id;
        fields.cause.dataset.selected = String(item.nguyen_nhan_id || '');
        fields.name.value = item.ten_huong_xu_ly || '';
        fields.price.value = item.gia_tham_khao ?? '';
        fields.description.value = item.mo_ta_cong_viec || '';
        fields.label.textContent = 'Sua huong xu ly';
        populateCauseOptions();
        modal.show();
    };

    const renderServiceChips = (names) => {
        if (!Array.isArray(names) || !names.length) {
            return '<span class="text-muted">Chua gan dich vu</span>';
        }

        return names.slice(0, 3).map((name) => `
            <span class="resolution-chip">${escapeHtml(name)}</span>
        `).join('');
    };

    const renderTable = () => {
        if (!state.items.length) {
            refs.tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">Khong co huong xu ly phu hop.</td>
                </tr>
            `;
            return;
        }

        refs.tbody.innerHTML = state.items.map((item) => `
            <tr>
                <td class="ps-4 fw-semibold text-muted">#${item.id}</td>
                <td>
                    <div>${renderServiceChips(item.service_names)}</div>
                </td>
                <td>
                    <div class="fw-semibold">${escapeHtml(item.cause_name || '--')}</div>
                    <div class="text-muted small">${escapeHtml((item.symptom_names || []).slice(0, 2).join(', ') || 'Chua gan trieu chung')}</div>
                </td>
                <td class="fw-semibold">${escapeHtml(item.ten_huong_xu_ly || '--')}</td>
                <td>${escapeHtml(item.gia_label || formatMoney(item.gia_tham_khao))}</td>
                <td class="text-muted">${escapeHtml(item.mo_ta_cong_viec || 'Chua cap nhat mo ta')}</td>
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

    const fetchResolutions = async () => {
        refs.tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 mb-0">Dang tai huong xu ly...</p>
                </td>
            </tr>
        `;

        syncUrl();

        try {
            const response = await callApi(`/admin/huong-xu-ly${buildQuery()}`);

            if (!response?.ok) {
                throw new Error(response?.data?.message || 'Khong the tai danh sach huong xu ly');
            }

            const payload = response.data?.data || {};
            state.items = Array.isArray(payload.items) ? payload.items : [];
            state.services = Array.isArray(payload.service_options) ? payload.service_options : [];
            state.causes = Array.isArray(payload.cause_options) ? payload.cause_options : [];
            populateServiceOptions();
            populateCauseOptions();
            renderStats(payload.summary || {});
            renderTable();
        } catch (error) {
            console.error('Load resolutions failed:', error);
            refs.tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5 text-danger">Khong the tai danh sach huong xu ly.</td>
                </tr>
            `;
            showToast(error.message || 'Khong the tai danh sach huong xu ly', 'error');
        }
    };

    const deleteResolution = async (item) => {
        if (!item) {
            return;
        }

        const confirmation = await Swal.fire({
            title: 'Xoa huong xu ly?',
            text: `Huong xu ly "${item.ten_huong_xu_ly}" se bi xoa khoi danh muc.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Xoa',
            cancelButtonText: 'Huy',
            confirmButtonColor: '#dc2626',
        });

        if (!confirmation.isConfirmed) {
            return;
        }

        const response = await callApi(`/admin/huong-xu-ly/${item.id}`, 'DELETE');

        if (!response?.ok) {
            showToast(response?.data?.message || 'Khong xoa duoc huong xu ly', 'error');
            return;
        }

        showToast(response.data?.message || 'Da xoa huong xu ly');
        await fetchResolutions();
    };

    refs.add.addEventListener('click', openCreateModal);
    refs.refresh.addEventListener('click', fetchResolutions);
    refs.serviceFilter.addEventListener('change', () => {
        refs.causeFilter.dataset.selected = '';
        populateCauseOptions();
        fetchResolutions();
    });
    refs.causeFilter.addEventListener('change', fetchResolutions);
    refs.search.addEventListener('input', () => {
        window.clearTimeout(state.searchTimer);
        state.searchTimer = window.setTimeout(fetchResolutions, 250);
    });
    fields.cause.addEventListener('change', updateCauseMeta);

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
            await deleteResolution(item);
        }
    });

    refs.form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setLoading(true);

        const resolutionId = fields.id.value.trim();
        const endpoint = resolutionId ? `/admin/huong-xu-ly/${resolutionId}` : '/admin/huong-xu-ly';
        const payload = {
            nguyen_nhan_id: Number(fields.cause.value || 0),
            ten_huong_xu_ly: fields.name.value.trim(),
            gia_tham_khao: fields.price.value.trim(),
            mo_ta_cong_viec: fields.description.value.trim(),
        };

        try {
            const response = await callApi(endpoint, resolutionId ? 'PUT' : 'POST', payload);

            if (!response?.ok) {
                showToast(response?.data?.message || 'Khong luu duoc huong xu ly', 'error');
                return;
            }

            showToast(response.data?.message || 'Da luu huong xu ly');
            modal.hide();
            openCreateModal();
            await fetchResolutions();
        } finally {
            setLoading(false);
        }
    });

    syncFiltersFromUrl();
    openCreateModal();
    fetchResolutions();
});
