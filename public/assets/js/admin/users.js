import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const tbody = document.getElementById('usersTableBody');
    const roleFilter = document.getElementById('roleFilter');
    const btnRefresh = document.getElementById('btnRefresh');

    const fetchUsers = async () => {
        try {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 mb-0">Đang tải danh sách...</p>
                    </td>
                </tr>`;

            const role = roleFilter.value;
            const query = role ? `?role=${role}` : '';

            const res = await callApi(`/admin/users${query}`);

            if (res.ok && res.data && res.data.data) {
                renderUsers(res.data.data);
            } else if (res.ok && res.data) {
                // Fallback in case Backend returns array directly
                renderUsers(res.data.data || res.data);
            }
        } catch (error) {
            console.error('Fetch users error:', error);
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Lỗi kết nối máy chủ!</td></tr>`;
        }
    };

    const renderUsers = (users) => {
        if (!users || users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fas fa-users-slash fs-1 opacity-25 mb-3"></i>
                        <p class="mb-0 fw-semibold">Không tìm thấy người dùng nào.</p>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = users.map(user => {
            const roleBadge = user.role === 'worker'
                ? '<span class="status-badge bg-success bg-opacity-10 text-success"><i class="fas fa-tools me-1"></i>Thợ Sửa Chữa</span>'
                : '<span class="status-badge bg-primary bg-opacity-10 text-primary"><i class="fas fa-user me-1"></i>Khách Cài Đặt</span>';

            const activeBadge = user.is_active
                ? '<span class="badge bg-success bg-opacity-25 text-success px-3 py-2 rounded-pill"><i class="fas fa-check-circle me-1"></i>Hoạt động</span>'
                : '<span class="badge bg-danger bg-opacity-25 text-danger px-3 py-2 rounded-pill"><i class="fas fa-ban me-1"></i>Đã Khóa</span>';

            const toggleBtnClass = user.is_active ? 'btn-outline-danger' : 'btn-outline-success';
            const toggleIcon = user.is_active ? 'fa-lock' : 'fa-unlock';
            const toggleText = user.is_active ? 'Khóa' : 'Mở Khóa';

            return `
                <tr>
                    <td class="ps-4 text-muted fw-bold">#${user.id}</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center text-primary fw-bold" style="width: 40px; height: 40px;">
                                ${user.name.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold" style="color: #0f172a;">${user.name}</h6>
                                <small class="text-muted"><i class="fas fa-envelope me-1"></i>${user.email}</small><br>
                                <small class="text-muted"><i class="fas fa-phone me-1"></i>${user.phone || 'Chưa cập nhật'}</small>
                            </div>
                        </div>
                    </td>
                    <td>${roleBadge}</td>
                    <td><small class="text-muted fw-semibold">${new Date(user.created_at).toLocaleDateString('vi-VN')}</small></td>
                    <td>${activeBadge}</td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm ${toggleBtnClass} rounded-3 btn-toggle-status" data-id="${user.id}" data-name="${user.name}" data-action="${user.is_active ? 'lock' : 'unlock'}">
                            <i class="fas ${toggleIcon} me-1"></i>${toggleText}
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        // Gắn sự kiện khóa / mở khóa
        document.querySelectorAll('.btn-toggle-status').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const id = e.currentTarget.getAttribute('data-id');
                const name = e.currentTarget.getAttribute('data-name');
                const action = e.currentTarget.getAttribute('data-action');
                const actionTxt = action === 'lock' ? 'KHÓA' : 'MỞ KHÓA';

                Swal.fire({
                    title: `Xác nhận ${actionTxt} tài khoản?`,
                    text: `Bạn có chắc chắn muốn ${actionTxt.toLowerCase()} tài khoản của "${name}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: action === 'lock' ? '#ef4444' : '#10b981',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-check me-2"></i>Đồng ý',
                    cancelButtonText: 'Hủy'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const res = await callApi(`/admin/users/${id}/toggle-status`, 'PATCH');
                            if (res.ok) {
                                showToast(res.message, 'success');
                                fetchUsers(); // reload table
                            } else {
                                showToast(res.message || 'Lỗi server', 'error');
                            }
                        } catch (err) {
                            showToast('Lỗi mạng', 'error');
                        }
                    }
                });
            });
        });
    };

    roleFilter.addEventListener('change', fetchUsers);
    btnRefresh.addEventListener('click', () => {
        const icon = btnRefresh.querySelector('i');
        icon.classList.add('fa-spin');
        fetchUsers().finally(() => setTimeout(() => icon.classList.remove('fa-spin'), 500));
    });

    // Tải dữ liệu lần đầu
    fetchUsers();
});
