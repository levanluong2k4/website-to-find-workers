import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const refs = {
        tbody: document.getElementById('workersTableBody'),
        form: document.getElementById('workerForm'),
        modalElement: document.getElementById('workerModal'),
        search: document.getElementById('workerSearch'),
        filterStatus: document.getElementById('filterStatus'),
        filterActive: document.getElementById('filterActive'),
        refresh: document.getElementById('btnRefreshWorkers'),
        add: document.getElementById('btnAddWorker'),
        skillsSelection: document.getElementById('skillsSelection'),
        stats: {
            total: document.getElementById('statTotalWorkers'),
            active: document.getElementById('statActiveWorkers'),
            pending: document.getElementById('statPendingApproval'),
            inactive: document.getElementById('statInactiveWorkers'),
        }
    };

    const fields = {
        id: document.getElementById('workerId'),
        name: document.getElementById('workerName'),
        email: document.getElementById('workerEmail'),
        phone: document.getElementById('workerPhone'),
        password: document.getElementById('workerPassword'),
        cccd: document.getElementById('workerCCCD'),
        address: document.getElementById('workerAddress'),
        exp: document.getElementById('workerExp'),
        active: document.getElementById('workerActive'),
        label: document.getElementById('workerModalLabel'),
        save: document.getElementById('btnSaveWorker'),
        statusGroup: document.getElementById('statusGroup'),
        passwordHelp: document.getElementById('passwordHelp'),
    };

    const modal = new bootstrap.Modal(refs.modalElement);
    const state = {
        items: [],
        allServices: [],
        isLoading: false
    };

    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');

    const setLoading = (isLoading) => {
        state.isLoading = isLoading;
        fields.save.disabled = isLoading;
        fields.save.innerHTML = isLoading
            ? '<i class="fas fa-spinner fa-spin me-2"></i>Đang lưu...'
            : '<i class="fas fa-save me-2"></i>Lưu thông tin thợ';
    };

    const fetchAllServices = async () => {
        try {
            const response = await callApi('/admin/services');
            if (response?.ok) {
                state.allServices = response.data?.data || [];
                renderSkillsSelection();
            }
        } catch (error) {
            console.error('Failed to load services:', error);
        }
    };

    const renderSkillsSelection = () => {
        if (!state.allServices.length) {
            refs.skillsSelection.innerHTML = '<p class="text-muted small">Không có dịch vụ nào để chọn.</p>';
            return;
        }

        refs.skillsSelection.innerHTML = state.allServices.map(service => `
            <div class="form-check me-3">
                <input class="form-check-input" type="checkbox" name="dich_vu_ids" value="${service.id}" id="skill_${service.id}">
                <label class="form-check-label small" for="skill_${service.id}">
                    ${escapeHtml(service.ten_dich_vu)}
                </label>
            </div>
        `).join('');
    };

    const updateStats = () => {
        const total = state.items.length;
        const active = state.items.filter(w => w.user?.is_active && w.trang_thai_duyet === 'da_duyet').length;
        const pending = state.items.filter(w => w.trang_thai_duyet === 'cho_duyet').length;
        const inactive = state.items.filter(w => !w.user?.is_active || w.trang_thai_duyet === 'tu_choi').length;

        refs.stats.total.textContent = total;
        refs.stats.active.textContent = active;
        refs.stats.pending.textContent = pending;
        refs.stats.inactive.textContent = inactive;
    };

    const renderTable = () => {
        const searchTerm = refs.search.value.toLowerCase();
        const statusFilter = refs.filterStatus.value;
        const activeFilter = refs.filterActive.value;

        const filtered = state.items.filter(item => {
            const user = item.user || {};
            const matchesSearch = !searchTerm || 
                (user.name || '').toLowerCase().includes(searchTerm) ||
                (user.phone || '').includes(searchTerm) ||
                (user.email || '').toLowerCase().includes(searchTerm);
            
            const matchesStatus = !statusFilter || item.trang_thai_duyet === statusFilter;
            const matchesActive = activeFilter === '' || String(user.is_active ? '1' : '0') === activeFilter;

            return matchesSearch && matchesStatus && matchesActive;
        });

        if (!filtered.length) {
            refs.tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        Không tìm thấy thợ nào phù hợp.
                    </td>
                </tr>
            `;
            return;
        }

        refs.tbody.innerHTML = filtered.map(item => {
            const user = item.user || {};
            const services = user.dich_vus || [];
            const avatar = user.avatar || '/assets/images/user-default.png';
            
            let statusClass = 'bg-secondary-subtle text-secondary';
            let statusLabel = 'Chưa xác định';
            
            if (item.trang_thai_duyet === 'da_duyet') {
                statusClass = 'bg-success-subtle text-success';
                statusLabel = 'Đã duyệt';
            } else if (item.trang_thai_duyet === 'cho_duyet') {
                statusClass = 'bg-warning-subtle text-warning';
                statusLabel = 'Chờ duyệt';
            } else if (item.trang_thai_duyet === 'tu_choi') {
                statusClass = 'bg-danger-subtle text-danger';
                statusLabel = 'Từ chối';
            }

            const activeBadge = user.is_active 
                ? '<span class="ms-1 badge bg-info-subtle text-info" style="font-size: 0.6rem;">Active</span>' 
                : '<span class="ms-1 badge bg-critical-subtle text-danger" style="font-size: 0.6rem;">Locked</span>';

            return `
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-3">
                            <img src="${avatar}" class="worker-avatar shadow-sm" onerror="this.src='/assets/images/user-default.png'">
                            <div>
                                <div class="fw-bold text-dark">${escapeHtml(user.name)} ${activeBadge}</div>
                                <div class="text-muted small">CCCD: ${escapeHtml(item.cccd || 'N/A')}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="small fw-500"><i class="fas fa-phone me-2 text-muted"></i>${escapeHtml(user.phone)}</div>
                        <div class="small text-muted"><i class="fas fa-envelope me-2 text-muted"></i>${escapeHtml(user.email)}</div>
                    </td>
                    <td>
                        <div class="mb-1">
                            ${services.map(s => `<span class="skill-badge">${escapeHtml(s.ten_dich_vu)}</span>`).join('') || '<span class="text-muted small">Chưa gán dịch vụ</span>'}
                        </div>
                        <div class="text-muted small text-truncate" style="max-width: 250px;">
                            ${escapeHtml(item.kinh_nghiem || 'Chưa cập nhật kinh nghiệm')}
                        </div>
                    </td>
                    <td>
                        <span class="badge ${statusClass} px-3 py-2 rounded-pill font-small">${statusLabel}</span>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-flex justify-content-end gap-2">
                            <button class="btn btn-sm btn-lumina btn-lumina-secondary px-3" data-action="edit" data-id="${user.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-link text-danger p-0 px-2" data-action="toggle" data-id="${user.id}" title="${user.is_active ? 'Khóa' : 'Mở khóa'}">
                                <i class="fas ${user.is_active ? 'fa-user-slash' : 'fa-user-check'}"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    };

    const fetchWorkers = async () => {
        try {
            const response = await callApi('/admin/worker-profiles');
            if (response?.ok) {
                state.items = response.data?.data || [];
                renderTable();
                updateStats();
            }
        } catch (error) {
            console.error('Fetch workers failed:', error);
            showToast('Không thể tải danh sách thợ', 'error');
        }
    };

    const openCreateModal = () => {
        refs.form.reset();
        fields.id.value = '';
        fields.label.textContent = 'Thêm thợ kỹ thuật mới';
        fields.statusGroup.style.display = 'none';
        fields.passwordGroup.style.display = 'block';
        fields.passwordHelp.style.display = 'none';
        fields.password.required = true;
        
        // Reset skill checkboxes
        document.querySelectorAll('input[name="dich_vu_ids"]').forEach(cb => cb.checked = false);
        modal.show();
    };

    const openEditModal = async (userId) => {
        try {
            const response = await callApi(`/admin/workers/${userId}`);
            if (!response?.ok) throw new Error();

            const worker = response.data?.data;
            const profile = worker.ho_so_tho || {};
            const serviceIds = (worker.dich_vus || []).map(s => s.id);

            fields.id.value = worker.id;
            fields.name.value = worker.name;
            fields.email.value = worker.email;
            fields.phone.value = worker.phone;
            fields.password.value = '';
            fields.password.required = false;
            fields.cccd.value = profile.cccd || '';
            fields.address.value = worker.address || '';
            fields.exp.value = profile.kinh_nghiem || '';
            fields.active.checked = worker.is_active;

            fields.label.textContent = 'Cập nhật thông tin thợ';
            fields.statusGroup.style.display = 'block';
            fields.passwordHelp.style.display = 'block';

            // Set skill checkboxes
            document.querySelectorAll('input[name="dich_vu_ids"]').forEach(cb => {
                cb.checked = serviceIds.includes(parseInt(cb.value));
            });

            modal.show();
        } catch (error) {
            showToast('Không thể lấy thông tin chi tiết thợ', 'error');
        }
    };

    const toggleStatus = async (userId) => {
        try {
            const response = await callApi(`/admin/users/${userId}/toggle-status`, 'PATCH');
            if (response?.ok) {
                showToast(response.data?.message || 'Đã thay đổi trạng thái');
                fetchWorkers();
            }
        } catch (error) {
            showToast('Lỗi khi thay đổi trạng thái', 'error');
        }
    };

    refs.add.addEventListener('click', openCreateModal);
    refs.refresh.addEventListener('click', fetchWorkers);
    refs.search.addEventListener('input', renderTable);
    refs.filterStatus.addEventListener('change', renderTable);
    refs.filterActive.addEventListener('change', renderTable);

    refs.tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;

        const id = btn.dataset.id;
        const action = btn.dataset.action;

        if (action === 'edit') openEditModal(id);
        if (action === 'toggle') toggleStatus(id);
    });

    refs.form.addEventListener('submit', async (e) => {
        e.preventDefault();
        setLoading(true);

        const id = fields.id.value;
        const isEdit = !!id;
        const endpoint = isEdit ? `/admin/workers/${id}` : '/admin/workers';
        const method = isEdit ? 'PUT' : 'POST';

        const selectedSkills = Array.from(document.querySelectorAll('input[name="dich_vu_ids"]:checked'))
            .map(cb => parseInt(cb.value));

        const data = {
            name: fields.name.value,
            email: fields.email.value,
            phone: fields.phone.value,
            cccd: fields.cccd.value,
            address: fields.address.value,
            kinh_nghiem: fields.exp.value,
            dich_vu_ids: selectedSkills,
            is_active: fields.active.checked
        };

        if (fields.password.value) {
            data.password = fields.password.value;
        }

        try {
            const response = await callApi(endpoint, method, data);
            if (response?.ok) {
                showToast(response.data?.message || 'Đã lưu thông tin');
                modal.hide();
                fetchWorkers();
            } else {
                showToast(response.data?.message || 'Có lỗi xảy ra', 'error');
            }
        } catch (error) {
            showToast('Lỗi kết nối máy chủ', 'error');
        } finally {
            setLoading(false);
        }
    });

    // Init
    fetchAllServices();
    fetchWorkers();
});
