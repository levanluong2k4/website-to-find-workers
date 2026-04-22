import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const tbody = document.getElementById('usersTableBody');
    const roleFilter = document.getElementById('roleFilter');
    const btnRefresh = document.getElementById('btnRefresh');
    const url = new URL(window.location.href);

    let currentApprovalFilter = url.searchParams.get('approval_status') || '';
    
    // Khởi tạo trạng thái filter pills
    const filterPills = document.querySelectorAll('.filter-pill');
    filterPills.forEach(pill => {
        if (pill.dataset.value === currentApprovalFilter) {
            filterPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
        }
        pill.addEventListener('click', () => {
            filterPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            currentApprovalFilter = pill.dataset.value;
            fetchUsers();
        });
    });

    // Elements cho Thợ CRUD
    const btnAddWorker = document.getElementById('btnAddWorker');
    const workerModalEl = document.getElementById('workerModal');
    const workerModal = workerModalEl ? new bootstrap.Modal(workerModalEl) : null;
    const workerForm = document.getElementById('workerForm');
    const skillsSelection = document.getElementById('skillsSelection');
    
    const wFields = {
        id: document.getElementById('workerId'),
        name: document.getElementById('workerName'),
        email: document.getElementById('workerEmail'),
        phone: document.getElementById('workerPhone'),
        password: document.getElementById('workerPassword'),
        cccd: document.getElementById('workerCCCD'),
        address: document.getElementById('workerAddress'),
        exp: document.getElementById('workerExp'),
        active: document.getElementById('workerActive'),
        avatar: document.getElementById('workerAvatar'),
        avatarPreview: document.getElementById('workerAvatarPreview'),
        avatarPreviewImage: document.getElementById('workerAvatarPreviewImage'),
        avatarPreviewFallback: document.getElementById('workerAvatarPreviewFallback'),
        avatarPreviewHint: document.querySelector('#workerAvatarPreview + .small.text-muted'),
        label: document.getElementById('workerModalLabel'),
        save: document.getElementById('btnSaveWorker'),
        statusGroup: document.getElementById('statusGroup'),
        passwordHelp: document.getElementById('passwordHelp'),
    };

    let allServices = [];

    if (wFields.avatarPreviewHint) {
        wFields.avatarPreviewHint.textContent = 'Anh hien tai hoac anh vua chon se hien o day.';
    }

    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');

    const approvalLabel = (status) => {
        if (status === 'da_duyet') {
            return '<span class="chip-lumina bg-success-subtle text-success">\u0110\u00e3 duy\u1ec7t</span>';
        }
        if (status === 'tu_choi') {
            return '<span class="chip-lumina bg-danger-subtle text-danger">T\u1eeb ch\u1ed1i</span>';
        }
        return '<span class="chip-lumina bg-warning-subtle text-warning">Ch\u1edd duy\u1ec7t</span>';
    };

    const roleLabel = (role) => {
        if (role === 'worker') {
            return '<span class="chip-lumina bg-success-subtle text-success">Th\u1ee3</span>';
        }
        return '<span class="chip-lumina bg-primary-subtle text-primary">Kh\u00e1ch h\u00e0ng</span>';
    };

    const accountLabel = (user) => {
        if (user.is_active) return '<span class="chip-lumina bg-success-subtle text-success">Hoạt động</span>';
        let label = '<span class="chip-lumina bg-danger-subtle text-danger">Đã khóa</span>';
        if (user.locked_until) {
            label += `<div class="small text-muted mt-1">Đến: ${new Date(user.locked_until).toLocaleString('vi-VN')}</div>`;
        }
        return label;
    };

    const renderWorkerActions = (approvalStatus, userId) => {
        const normalizedStatus = approvalStatus || 'cho_duyet';
        const actions = [];

        if (normalizedStatus === 'cho_duyet') {
            actions.push(`<button class="action-btn unlock btn-approve" data-id="${userId}" title="Duyệt hồ sơ"><i class="fas fa-check"></i></button>`);
            actions.push(`<button class="action-btn delete btn-reject" data-id="${userId}" title="Từ chối hồ sơ"><i class="fas fa-times"></i></button>`);
        }
        
        actions.push(`<button class="action-btn edit btn-edit-worker" data-id="${userId}" title="Sửa thông tin"><i class="fas fa-edit"></i></button>`);
        actions.push(`<button class="action-btn delete btn-delete-worker" data-id="${userId}" title="Xóa thợ"><i class="fas fa-trash"></i></button>`);

        return actions.join('');
    };

    const getInitials = (name) => {
        if (!name) return '??';
        const parts = name.trim().split(' ');
        if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
        return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    };

    const getRandomColorClass = (str) => {
        const colors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning text-dark', 'bg-danger', 'bg-secondary'];
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }
        return colors[Math.abs(hash) % colors.length];
    };

    const resolveAvatarUrl = (avatar) => {
        if (!avatar) return '';
        if (/^https?:\/\//i.test(avatar) || avatar.startsWith('/')) {
            return avatar;
        }

        return `/storage/${avatar}`;
    };

    const buildAvatarMarkup = (user) => {
        const avatarUrl = resolveAvatarUrl(user.avatar);
        if (avatarUrl) {
            return `<img src="${escapeHtml(avatarUrl)}" alt="${escapeHtml(user.name || 'Avatar')}" class="avatar-photo shadow-sm" onerror="this.src='/assets/images/user-default.png'">`;
        }

        return `
            <div class="avatar-initials ${getRandomColorClass(user.name || 'A')} shadow-sm">
                ${escapeHtml(getInitials(user.name))}
            </div>
        `;
    };

    let currentAvatarPreviewObjectUrl = '';
    let currentModalAvatarUrl = '';

    const revokeAvatarPreviewObjectUrl = () => {
        if (!currentAvatarPreviewObjectUrl) return;
        URL.revokeObjectURL(currentAvatarPreviewObjectUrl);
        currentAvatarPreviewObjectUrl = '';
    };

    const renderWorkerAvatarPreview = (avatarUrl, name = '') => {
        if (!wFields.avatarPreview || !wFields.avatarPreviewImage || !wFields.avatarPreviewFallback) {
            return;
        }

        const resolvedAvatarUrl = resolveAvatarUrl(avatarUrl);
        wFields.avatarPreviewFallback.textContent = name ? getInitials(name) : 'TT';

        if (resolvedAvatarUrl) {
            wFields.avatarPreviewImage.src = resolvedAvatarUrl;
            wFields.avatarPreview.classList.add('has-image');
            return;
        }

        wFields.avatarPreviewImage.removeAttribute('src');
        wFields.avatarPreview.classList.remove('has-image');
    };

    const renderUsers = (users) => {
        // Cập nhật thống kê
        let activeCount = 0;
        let pendingCount = 0;
        let lockedCount = 0;

        users.forEach(u => {
            if (u.role === 'worker') {
                if (!u.is_active) lockedCount++;
                else activeCount++;
                
                if ((u.ho_so_tho?.trang_thai_duyet || 'cho_duyet') === 'cho_duyet') {
                    pendingCount++;
                }
            }
        });

        document.getElementById('stat-active').textContent = activeCount;
        document.getElementById('stat-pending').textContent = pendingCount;
        document.getElementById('stat-locked').textContent = lockedCount;

        const filteredUsers = users.filter((user) => {
            if (user.role !== 'worker') return false;
            if (currentApprovalFilter === '') return true;
            return (user.ho_so_tho?.trang_thai_duyet || 'cho_duyet') === currentApprovalFilter;
        });

        if (!filteredUsers.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        Không tìm thấy thợ phù hợp.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = filteredUsers.map((user) => {
            const workerProfile = user.ho_so_tho;
            const services = Array.isArray(user.dich_vus) ? user.dich_vus : [];
            const serviceHtml = services.length
                ? services.map((service) => `<span class="chip-lumina btn-lumina-secondary">${escapeHtml(service.ten_dich_vu)}</span>`).join(' ')
                : '<span class="text-muted small">Chưa phân công</span>';

            const workerInfo = user.role === 'worker'
                ? `
                    <div>${serviceHtml}</div>
                    <p class="worker-contact mt-1"><i class="fas fa-info-circle me-1"></i>${escapeHtml(workerProfile?.ghi_chu_admin || 'Chưa có ghi chú')}</p>
                `
                : '<span class="text-muted">Không áp dụng</span>';

            const workerActions = user.role === 'worker'
                ? renderWorkerActions(workerProfile?.trang_thai_duyet, user.id)
                : '';

            const toggleIcon = user.is_active ? '<i class="fas fa-lock"></i>' : '<i class="fas fa-lock-open"></i>';
            const toggleClass = user.is_active ? 'lock' : 'unlock';
            const toggleTitle = user.is_active ? 'Khóa thợ' : 'Mở khóa thợ';
            const isActiveStr = user.is_active ? 'true' : 'false';

            return `
                <tr>
                    <td class="ps-4 fw-semibold text-muted">#${user.id}</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            ${buildAvatarMarkup(user)}
                            <div>
                                <p class="worker-name">${escapeHtml(user.name)}</p>
                                <p class="worker-contact">${escapeHtml(user.email)} / ${escapeHtml(user.phone || '--')}</p>
                                <p class="worker-contact mt-1">TG: ${new Date(user.created_at).toLocaleDateString('vi-VN')}</p>
                            </div>
                        </div>
                    </td>
                    <td style="max-width: 250px;">${workerInfo}</td>
                    <td>${approvalLabel(workerProfile?.trang_thai_duyet)}</td>
                    <td>${accountLabel(user.is_active)}</td>
                    <td class="text-end pe-4">
                        <div class="action-container">
                            <button class="action-btn ${toggleClass} btn-toggle-status" data-id="${user.id}" data-name="${escapeHtml(user.name)}" data-isactive="${isActiveStr}" title="${toggleTitle}">${toggleIcon}</button>
                            ${workerActions}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        document.querySelectorAll('.btn-toggle-status').forEach((button) => {
            button.addEventListener('click', async () => {
                const userId = button.dataset.id;
                const userName = button.dataset.name;
                const isActive = button.dataset.isactive === 'true';

                if (isActive) {
                    await handleLockUser(userId, userName);
                } else {
                    await handleUnlockUser(userId);
                }
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
        document.querySelectorAll('.btn-edit-worker').forEach((button) => {
            button.addEventListener('click', () => openEditWorkerModal(button.dataset.id));
        });
        document.querySelectorAll('.btn-delete-worker').forEach((button) => {
            button.addEventListener('click', () => deleteWorker(button.dataset.id));
        });
    };

    const handleUnlockUser = async (userId) => {
        const confirm = await Swal.fire({
            title: 'Mở khóa tài khoản?',
            text: 'Tài khoản này sẽ có thể đăng nhập và hoạt động bình thường.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Mở khóa',
            cancelButtonText: 'Hủy'
        });
        if (!confirm.isConfirmed) return;

        const res = await callApi(`/admin/users/${userId}/toggle-status`, 'PATCH', { action: 'unlock' });
        if (!res.ok) {
            showToast(res.data?.message || 'Không mở khóa được', 'error');
            return;
        }
        showToast(res.data?.message || 'Đã mở khóa tài khoản');
        await fetchUsers();
    };

    const handleLockUser = async (userId, userName) => {
        const { value: formValues } = await Swal.fire({
            title: 'Khóa tài khoản',
            html: `
                <style>
                    .lumina-modal { border-radius: 1.5rem !important; padding: 1.5rem !important; }
                    .lumina-modal .swal2-title { font-size: 1.5rem !important; font-weight: 800 !important; color: #0f172a !important; text-align: left !important; padding: 0 !important; margin-bottom: 0.5rem !important; display: block; }
                    .lumina-subtitle { font-size: 0.875rem; color: #64748b; margin-bottom: 1.25rem; text-align: left; }
                    .lumina-radio-options { display: flex; flex-direction: column; gap: 0.75rem; text-align: left; }
                    .l-opt { 
                        display: flex; align-items: center; justify-content: space-between; 
                        padding: 1rem 1.25rem; border: 1.5px solid #e2e8f0; border-radius: 0.75rem; 
                        cursor: pointer; transition: all 0.2s ease; background: #fff;
                    }
                    .l-opt:hover { border-color: #cbd5e1; background: #f8fafc; }
                    .l-opt.active { border-color: #dc2626; background: #fef2f2; }
                    .l-opt.active .l-radio-circle { border-color: #dc2626; border-width: 5px; }
                    .l-opt.active .l-title { color: #b91c1c; }
                    .l-radio-circle { width: 22px; height: 22px; border-radius: 50%; border: 2px solid #cbd5e1; transition: all 0.2s; background: #fff; }
                    .l-opt.active .l-radio-circle { border-width: 6px; }
                    .l-title { font-weight: 700; font-size: 0.95rem; color: #334155; margin: 0; }
                    .l-desc { font-size: 0.8rem; color: #64748b; margin: 0.2rem 0 0 0; }
                    .l-custom-input-wrapper { margin-top: 0.75rem; display: none; animation: l-fadeIn 0.2s ease-out; text-align: left;}
                    .l-custom-input-wrapper.show { display: block; }
                    .l-custom-input { width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; border: 1.5px solid #cbd5e1; outline: none; font-size: 0.9rem; font-weight: 500; color: #0f172a; transition: all 0.2s;}
                    .l-custom-input:focus { border-color: #dc2626; box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1); }
                    @keyframes l-fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
                    .lumina-modal .swal2-actions { margin-top: 2rem !important; justify-content: flex-end; width: 100%; gap: 1rem; }
                    .lumina-modal .swal2-confirm { background-color: #dc2626 !important; border-radius: 9999px !important; padding: 0.625rem 1.5rem !important; font-weight: 600 !important; font-size: 0.875rem !important; transition: all 0.2s; }
                    .lumina-modal .swal2-confirm:hover { background-color: #b91c1c !important; transform: translateY(-1px); }
                    .lumina-modal .swal2-cancel { background-color: #f1f5f9 !important; color: #475569 !important; border-radius: 9999px !important; padding: 0.625rem 1.5rem !important; font-weight: 600 !important; font-size: 0.875rem !important; transition: all 0.2s; }
                    .lumina-modal .swal2-cancel:hover { background-color: #e2e8f0 !important; color: #1e293b !important; }
                </style>
                <div class="lumina-subtitle">Chọn thời gian khóa đối với <strong style="color: #0f172a;">${escapeHtml(userName)}</strong>:</div>
                <div class="lumina-radio-options" id="luminaLockOptions">
                    <div class="l-opt" data-val="1">
                        <div><p class="l-title">1 Ngày</p></div>
                        <div class="l-radio-circle"></div>
                    </div>
                    <div class="l-opt" data-val="3">
                        <div><p class="l-title">3 Ngày</p></div>
                        <div class="l-radio-circle"></div>
                    </div>
                    <div class="l-opt" data-val="7">
                        <div><p class="l-title">7 Ngày</p></div>
                        <div class="l-radio-circle"></div>
                    </div>
                    <div class="l-opt active" data-val="forever">
                        <div>
                            <p class="l-title">Khóa vĩnh viễn</p>
                            <p class="l-desc">Tài khoản sẽ không thể tự mở khóa</p>
                        </div>
                        <div class="l-radio-circle"></div>
                    </div>
                    <div class="l-opt" data-val="custom">
                        <div><p class="l-title">Tùy chỉnh số ngày...</p></div>
                        <div class="l-radio-circle"></div>
                    </div>
                </div>
                <div class="l-custom-input-wrapper" id="lCustomDaysWrapper">
                    <input type="number" id="lCustomDays" class="l-custom-input" min="1" placeholder="Nhập số ngày (VD: 14)">
                </div>
            `,
            customClass: { popup: 'lumina-modal', actions: 'w-100', title: 'w-100', htmlContainer: 'm-0' },
            width: '28rem',
            showCancelButton: true,
            confirmButtonText: 'Khóa tài khoản',
            cancelButtonText: 'Hủy bỏ',
            buttonsStyling: false,
            focusConfirm: false,
            didOpen: () => {
                const options = document.querySelectorAll('.l-opt');
                const customWrapper = document.getElementById('lCustomDaysWrapper');
                const customInput = document.getElementById('lCustomDays');

                window.lockSelectedValue = 'forever';

                options.forEach(opt => {
                    opt.addEventListener('click', () => {
                        options.forEach(o => o.classList.remove('active'));
                        opt.classList.add('active');
                        window.lockSelectedValue = opt.dataset.val;

                        if (window.lockSelectedValue === 'custom') {
                            customWrapper.classList.add('show');
                            customInput.focus();
                        } else {
                            customWrapper.classList.remove('show');
                        }
                    });
                });
                
                document.querySelector('.swal2-confirm').classList.add('swal2-confirm');
                document.querySelector('.swal2-cancel').classList.add('swal2-cancel');
            },
            preConfirm: () => {
                const val = window.lockSelectedValue;
                if (val === 'custom') {
                    const customDays = document.getElementById('lCustomDays').value;
                    if (!customDays || customDays <= 0) {
                        Swal.showValidationMessage('Vui lòng nhập số ngày hợp lệ (lớn hơn 0).');
                        return false;
                    }
                    return { duration_days: customDays };
                }
                if (val === 'forever') {
                    return { duration_days: null };
                }
                return { duration_days: val };
            }
        });

        if (!formValues) return;

        // Bỏ focus của body scroll etc nếu cần, Swal lo vụ này.
        const res = await callApi(`/admin/users/${userId}/toggle-status`, 'PATCH', {
            action: 'lock',
            duration_days: formValues.duration_days
        });

        if (!res.ok) {
            showToast(res.data?.message || 'Không khóa được tài khoản', 'error');
            return;
        }
        showToast(res.data?.message || 'Đã khóa tài khoản');
        await fetchUsers();
    };

    const updateApproval = async (userId, status) => {
        const notePrompt = await Swal.fire({
            title: 'Ghi ch\u00fa admin',
            input: 'textarea',
            inputPlaceholder: 'Nh\u1eadp ghi ch\u00fa n\u1ebfu c\u1ea7n',
            inputValue: '',
            showCancelButton: true,
            confirmButtonText: 'L\u01b0u',
            cancelButtonText: 'H\u1ee7y',
        });

        if (!notePrompt.isConfirmed) {
            return;
        }

        const res = await callApi(`/admin/worker-profiles/${userId}/approval`, 'PATCH', {
            trang_thai_duyet: status,
            ghi_chu_admin: notePrompt.value || '',
        });

        if (!res.ok) {
            showToast(res.data?.message || 'Kh\u00f4ng c\u1eadp nh\u1eadt \u0111\u01b0\u1ee3c h\u1ed3 s\u01a1 th\u1ee3', 'error');
            return;
        }

        showToast(res.data?.message || '\u0110\u00e3 c\u1eadp nh\u1eadt h\u1ed3 s\u01a1 th\u1ee3');
        await fetchUsers();
    };

    const deleteWorker = async (userId) => {
        const confirm = await Swal.fire({
            title: 'Xóa tài khoản thợ?',
            text: 'Dữ liệu hồ sơ thợ sẽ bị xóa vĩnh viễn và không thể khôi phục!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Có, xóa thợ',
            cancelButtonText: 'Hủy'
        });

        if (!confirm.isConfirmed) return;

        const res = await callApi(`/admin/workers/${userId}`, 'DELETE');
        if (!res.ok) {
            Swal.fire('Lỗi', res.data?.message || 'Không thể xóa thợ này', 'error');
            return;
        }

        showToast(res.data?.message || 'Đã xóa tài khoản thợ thành công');
        await fetchUsers();
    };

    const fetchUsers = async () => {
        syncFilterUrl();

        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 mb-0">Đang tải danh sách thợ...</p>
                </td>
            </tr>
        `;

        // Lấy tất cả user (hoặc chỉ worker) để thống kê chính xác, sau đó filter ở client nếu cần.
        const res = await callApi(`/admin/users?role=worker`);

        if (!res.ok) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-danger">
                        Không tải được danh sách thợ.
                    </td>
                </tr>
            `;
            return;
        }

        renderUsers(Array.isArray(res.data?.data) ? res.data.data : []);
    };

    const syncFilterUrl = () => {
        const nextUrl = new URL(window.location.href);

        if (currentApprovalFilter) {
            nextUrl.searchParams.set('approval_status', currentApprovalFilter);
        } else {
            nextUrl.searchParams.delete('approval_status');
        }

        window.history.replaceState({}, '', nextUrl);
    };

    btnRefresh.addEventListener('click', fetchUsers);

    // Xử lý Services và Modal Thợ
    const fetchAllServices = async () => {
        try {
            const res = await callApi('/admin/services');
            if (res?.ok) {
                allServices = res.data?.data || [];
                renderSkillsSelection();
            }
        } catch (error) {}
    };

    const renderSkillsSelection = () => {
        if (!allServices.length) {
            if(skillsSelection) skillsSelection.innerHTML = '<p class="text-muted small">Không có dịch vụ.</p>';
            return;
        }
        if(skillsSelection) {
            skillsSelection.innerHTML = allServices.map(s => `
                <div class="form-check me-3">
                    <input class="form-check-input" type="checkbox" name="dich_vu_ids" value="${s.id}" id="skill_${s.id}">
                    <label class="form-check-label small" for="skill_${s.id}">${escapeHtml(s.ten_dich_vu)}</label>
                </div>
            `).join('');
        }
    };

    if (btnAddWorker) {
        btnAddWorker.addEventListener('click', () => {
            workerForm.reset();
            revokeAvatarPreviewObjectUrl();
            currentModalAvatarUrl = '';
            wFields.id.value = '';
            wFields.label.textContent = 'Thêm thợ kỹ thuật mới';
            wFields.statusGroup.style.display = 'none';
            wFields.passwordGroup.style.display = 'block';
            wFields.passwordHelp.style.display = 'none';
            wFields.password.required = true;
            if (wFields.avatar) wFields.avatar.value = '';
            renderWorkerAvatarPreview(currentModalAvatarUrl, wFields.name.value);
            document.querySelectorAll('input[name="dich_vu_ids"]').forEach(cb => cb.checked = false);
        });
    }

    const openEditWorkerModal = async (userId) => {
        try {
            const res = await callApi(`/admin/workers/${userId}`);
            if (!res?.ok) return showToast('Lỗi khi lấy thông tin thợ', 'error');

            const worker = res.data?.data;
            const profile = worker.ho_so_tho || {};
            const serviceIds = (worker.dich_vus || []).map(s => s.id);

            wFields.id.value = worker.id;
            wFields.name.value = worker.name;
            wFields.email.value = worker.email;
            wFields.phone.value = worker.phone;
            wFields.password.value = '';
            wFields.password.required = false;
            wFields.cccd.value = profile.cccd || '';
            wFields.address.value = worker.address || '';
            wFields.exp.value = profile.kinh_nghiem || '';
            wFields.active.checked = worker.is_active;
            if (wFields.avatar) wFields.avatar.value = '';
            revokeAvatarPreviewObjectUrl();
            currentModalAvatarUrl = worker.avatar || '';
            renderWorkerAvatarPreview(currentModalAvatarUrl, worker.name || '');

            wFields.label.textContent = 'Cập nhật thợ';
            wFields.statusGroup.style.display = 'block';
            wFields.passwordHelp.style.display = 'block';

            document.querySelectorAll('input[name="dich_vu_ids"]').forEach(cb => {
                cb.checked = serviceIds.includes(parseInt(cb.value));
            });

            workerModal.show();
        } catch (error) {
            showToast('Lỗi lấy thông tin thợ', 'error');
        }
    };

    if (wFields.avatar) {
        wFields.avatar.addEventListener('change', () => {
            revokeAvatarPreviewObjectUrl();

            const file = wFields.avatar.files?.[0];
            if (!file) {
                renderWorkerAvatarPreview(currentModalAvatarUrl, wFields.name.value || '');
                return;
            }

            currentAvatarPreviewObjectUrl = URL.createObjectURL(file);
            renderWorkerAvatarPreview(currentAvatarPreviewObjectUrl, wFields.name.value || '');
        });
    }

    if (wFields.name) {
        wFields.name.addEventListener('input', () => {
            if (wFields.avatar?.files?.[0]) return;
            if (wFields.id.value) return;
            renderWorkerAvatarPreview('', wFields.name.value || '');
        });
    }

    if (workerForm) {
        workerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = wFields.id.value;
            const isEdit = !!id;
            // Dùng POST và __method=PUT cho FormData
            const endpoint = isEdit ? `/admin/workers/${id}` : '/admin/workers';
            const method = 'POST'; // File upload qua FormData luôn dùng POST

            const selectedSkills = Array.from(document.querySelectorAll('input[name="dich_vu_ids"]:checked')).map(cb => parseInt(cb.value));

            const formData = new FormData();
            if (isEdit) formData.append('_method', 'PUT');
            
            formData.append('name', wFields.name.value);
            formData.append('email', wFields.email.value);
            formData.append('phone', wFields.phone.value);
            formData.append('cccd', wFields.cccd.value);
            formData.append('address', wFields.address.value);
            formData.append('kinh_nghiem', wFields.exp.value);
            formData.append('is_active', wFields.active.checked ? 1 : 0);

            if (wFields.password.value) formData.append('password', wFields.password.value);
            
            selectedSkills.forEach(skillId => {
                formData.append('dich_vu_ids[]', skillId);
            });

            if (wFields.avatar && wFields.avatar.files[0]) {
                formData.append('avatar', wFields.avatar.files[0]);
            }

            wFields.save.disabled = true;
            wFields.save.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';

            try {
                const res = await callApi(endpoint, method, formData);
                if (res?.ok) {
                    showToast(res.data?.message || 'Đã lưu thông tin thợ');
                    workerModal.hide();
                    revokeAvatarPreviewObjectUrl();
                    currentModalAvatarUrl = '';
                    renderWorkerAvatarPreview('', '');
                    await fetchUsers();
                } else {
                    showToast(res.data?.message || 'Lỗi', 'error');
                }
            } catch (error) {
                showToast('Lỗi máy chủ', 'error');
            } finally {
                wFields.save.disabled = false;
                wFields.save.innerHTML = 'Lưu thông tin';
            }
        });
    }

    fetchAllServices();
    fetchUsers();
});
