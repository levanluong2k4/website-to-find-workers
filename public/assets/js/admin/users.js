import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const tbody = document.getElementById('usersTableBody');
    const roleFilter = document.getElementById('roleFilter');
    const approvalFilter = document.getElementById('approvalFilter');
    const btnRefresh = document.getElementById('btnRefresh');
    const url = new URL(window.location.href);

    roleFilter.value = url.searchParams.get('role') || '';
    approvalFilter.value = url.searchParams.get('approval_status') || '';

    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');

    const approvalLabel = (status) => {
        if (status === 'da_duyet') {
            return '<span class="chip bg-success-subtle text-success">Da duyet</span>';
        }
        if (status === 'tu_choi') {
            return '<span class="chip bg-danger-subtle text-danger">Tu choi</span>';
        }
        return '<span class="chip bg-warning-subtle text-warning">Cho duyet</span>';
    };

    const roleLabel = (role) => {
        if (role === 'worker') {
            return '<span class="chip bg-success-subtle text-success">Tho</span>';
        }
        return '<span class="chip bg-primary-subtle text-primary">Khach hang</span>';
    };

    const accountLabel = (isActive) => isActive
        ? '<span class="chip bg-success-subtle text-success">Hoat dong</span>'
        : '<span class="chip bg-danger-subtle text-danger">Da khoa</span>';

    const renderWorkerActions = (approvalStatus, userId) => {
        const normalizedStatus = approvalStatus || 'cho_duyet';
        const actions = [];

        if (normalizedStatus === 'cho_duyet') {
            actions.push(`<button class="btn btn-sm btn-outline-success me-1 btn-approve" data-id="${userId}">Duyet</button>`);
            actions.push(`<button class="btn btn-sm btn-outline-danger me-1 btn-reject" data-id="${userId}">Tu choi</button>`);
        }

        return actions.join('');
    };

    const renderUsers = (users) => {
        const filteredUsers = users.filter((user) => {
            if (approvalFilter.value === '' || user.role !== 'worker') {
                return true;
            }

            return (user.ho_so_tho?.trang_thai_duyet || 'cho_duyet') === approvalFilter.value;
        });

        if (!filteredUsers.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        Khong tim thay nguoi dung phu hop.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = filteredUsers.map((user) => {
            const workerProfile = user.ho_so_tho;
            const services = Array.isArray(user.dich_vus) ? user.dich_vus : [];
            const serviceHtml = services.length
                ? services.map((service) => `<span class="chip bg-light text-dark border">${escapeHtml(service.ten_dich_vu)}</span>`).join('')
                : '<span class="text-muted">Chua gan dich vu</span>';

            const workerInfo = user.role === 'worker'
                ? `
                    <div class="mb-2">${approvalLabel(workerProfile?.trang_thai_duyet)}</div>
                    <div class="small text-muted mb-2">Dich vu:</div>
                    <div>${serviceHtml}</div>
                    <div class="small text-muted mt-2">Ghi chu admin: ${escapeHtml(workerProfile?.ghi_chu_admin || '--')}</div>
                `
                : '<span class="text-muted">Khong ap dung</span>';

            const workerActions = user.role === 'worker'
                ? renderWorkerActions(workerProfile?.trang_thai_duyet, user.id)
                : '';

            const toggleText = user.is_active ? 'Khoa' : 'Mo khoa';
            const toggleClass = user.is_active ? 'btn-outline-danger' : 'btn-outline-primary';

            return `
                <tr>
                    <td class="ps-4 fw-semibold text-muted">#${user.id}</td>
                    <td>
                        <div class="fw-semibold">${escapeHtml(user.name)}</div>
                        <div class="small text-muted">${escapeHtml(user.email)}</div>
                        <div class="small text-muted">${escapeHtml(user.phone || '--')}</div>
                        <div class="small text-muted mt-1">Tham gia: ${new Date(user.created_at).toLocaleDateString('vi-VN')}</div>
                    </td>
                    <td>${roleLabel(user.role)}</td>
                    <td>${workerInfo}</td>
                    <td>${accountLabel(user.is_active)}</td>
                    <td class="text-end pe-4">
                        <div class="d-grid gap-2 justify-content-end">
                            <button class="btn btn-sm ${toggleClass} btn-toggle-status" data-id="${user.id}">${toggleText}</button>
                            ${workerActions}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        document.querySelectorAll('.btn-toggle-status').forEach((button) => {
            button.addEventListener('click', async () => {
                const res = await callApi(`/admin/users/${button.dataset.id}/toggle-status`, 'PATCH');
                if (!res.ok) {
                    showToast(res.data?.message || 'Khong cap nhat duoc tai khoan', 'error');
                    return;
                }
                showToast(res.data?.message || 'Da cap nhat tai khoan');
                await fetchUsers();
            });
        });

        document.querySelectorAll('.btn-approve').forEach((button) => {
            button.addEventListener('click', () => updateApproval(button.dataset.id, 'da_duyet'));
        });
        document.querySelectorAll('.btn-pending').forEach((button) => {
            button.addEventListener('click', () => updateApproval(button.dataset.id, 'cho_duyet'));
        });
        document.querySelectorAll('.btn-reject').forEach((button) => {
            button.addEventListener('click', () => updateApproval(button.dataset.id, 'tu_choi'));
        });
    };

    const updateApproval = async (userId, status) => {
        const notePrompt = await Swal.fire({
            title: 'Ghi chu admin',
            input: 'textarea',
            inputPlaceholder: 'Nhap ghi chu neu can',
            inputValue: '',
            showCancelButton: true,
            confirmButtonText: 'Luu',
            cancelButtonText: 'Huy',
        });

        if (!notePrompt.isConfirmed) {
            return;
        }

        const res = await callApi(`/admin/worker-profiles/${userId}/approval`, 'PATCH', {
            trang_thai_duyet: status,
            ghi_chu_admin: notePrompt.value || '',
        });

        if (!res.ok) {
            showToast(res.data?.message || 'Khong cap nhat duoc ho so tho', 'error');
            return;
        }

        showToast(res.data?.message || 'Da cap nhat ho so tho');
        await fetchUsers();
    };

    const fetchUsers = async () => {
        syncFilterUrl();

        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 mb-0">Dang tai danh sach nguoi dung...</p>
                </td>
            </tr>
        `;

        const query = roleFilter.value ? `?role=${roleFilter.value}` : '';
        const res = await callApi(`/admin/users${query}`);

        if (!res.ok) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-danger">
                        Khong tai duoc danh sach nguoi dung.
                    </td>
                </tr>
            `;
            return;
        }

        renderUsers(Array.isArray(res.data?.data) ? res.data.data : []);
    };

    const syncFilterUrl = () => {
        const nextUrl = new URL(window.location.href);

        if (roleFilter.value) {
            nextUrl.searchParams.set('role', roleFilter.value);
        } else {
            nextUrl.searchParams.delete('role');
        }

        if (approvalFilter.value) {
            nextUrl.searchParams.set('approval_status', approvalFilter.value);
        } else {
            nextUrl.searchParams.delete('approval_status');
        }

        window.history.replaceState({}, '', nextUrl);
    };

    roleFilter.addEventListener('change', fetchUsers);
    approvalFilter.addEventListener('change', fetchUsers);
    btnRefresh.addEventListener('click', fetchUsers);

    fetchUsers();
});
