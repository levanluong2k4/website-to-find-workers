import { callApi, getCurrentUser, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    if (!requireRole('admin')) return;

    const state = {
        user: getCurrentUser() || {},
    };

    const el = {
        avatar: document.getElementById('adminProfileAvatar'),
        name: document.getElementById('adminProfileName'),
        email: document.getElementById('adminProfileEmail'),
        avatarInput: document.getElementById('adminAvatarInput'),
        profileForm: document.getElementById('adminProfileForm'),
        passwordForm: document.getElementById('adminPasswordForm'),
        saveProfile: document.getElementById('adminProfileSaveBtn'),
        savePassword: document.getElementById('adminPasswordSaveBtn'),
        inputName: document.getElementById('adminProfileInputName'),
        inputEmail: document.getElementById('adminProfileInputEmail'),
        inputPhone: document.getElementById('adminProfileInputPhone'),
        currentPassword: document.getElementById('adminCurrentPassword'),
        newPassword: document.getElementById('adminNewPassword'),
        newPasswordConfirmation: document.getElementById('adminNewPasswordConfirmation'),
        tabs: document.querySelectorAll('[data-profile-tab]'),
        infoSection: document.getElementById('adminInfoSection'),
        passwordSection: document.getElementById('adminPasswordSection'),
    };

    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');

    const resolveAvatarUrl = (avatar) => {
        if (!avatar) return '';
        if (/^(https?|blob):\/\//i.test(avatar) || avatar.startsWith('/')) return avatar;
        return `/storage/${avatar}`;
    };

    const getInitials = (name) => {
        const parts = String(name || 'Admin').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return 'A';
        if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
        return `${parts[0].charAt(0)}${parts[parts.length - 1].charAt(0)}`.toUpperCase();
    };

    const persistUser = (nextUser) => {
        state.user = { ...state.user, ...nextUser };
        localStorage.setItem('user', JSON.stringify(state.user));
        renderUser();
    };

    const renderUser = () => {
        const user = state.user || {};
        const avatarUrl = resolveAvatarUrl(user.avatar);
        el.avatar.innerHTML = avatarUrl
            ? `<img src="${escapeHtml(avatarUrl)}" alt="${escapeHtml(user.name || 'Avatar')}" onerror="this.remove()">`
            : escapeHtml(getInitials(user.name));
        el.name.textContent = user.name || 'Admin';
        el.email.textContent = user.email || '';
        el.inputName.value = user.name || '';
        el.inputEmail.value = user.email || '';
        el.inputPhone.value = user.phone || '';
    };

    const clearErrors = (form) => {
        form?.querySelectorAll('.invalid-feedback.api-error').forEach((node) => node.remove());
        form?.querySelectorAll('.is-invalid').forEach((node) => node.classList.remove('is-invalid'));
    };

    const showFieldError = (input, message) => {
        if (!input) return;
        input.classList.add('is-invalid');
        input.parentNode.querySelectorAll('.invalid-feedback.api-error').forEach((node) => node.remove());
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback api-error d-block';
        feedback.textContent = message;
        input.parentNode.appendChild(feedback);
        input.focus();
    };

    const mapErrors = (form, errors, fieldMap) => {
        clearErrors(form);
        const entries = Object.entries(errors || {});
        if (!entries.length) return false;

        let firstInput = null;
        entries.forEach(([key, messages]) => {
            const input = fieldMap[key];
            if (!input) return;
            const message = Array.isArray(messages) ? messages[0] : messages;
            input.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback api-error d-block';
            feedback.textContent = message || 'Dữ liệu không hợp lệ';
            input.parentNode.appendChild(feedback);
            if (!firstInput) firstInput = input;
        });

        firstInput?.focus();
        return true;
    };

    const setButtonLoading = (button, isLoading, label, loadingLabel) => {
        if (!button) return;
        button.disabled = isLoading;
        button.innerHTML = isLoading
            ? `<i class="fas fa-spinner fa-spin"></i> ${loadingLabel}`
            : label;
    };

    el.tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.profileTab;
            el.tabs.forEach((item) => item.classList.toggle('active', item === tab));
            el.infoSection.classList.toggle('active', target === 'info');
            el.passwordSection.classList.toggle('active', target === 'password');
        });
    });

    el.profileForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(el.profileForm);
        setButtonLoading(el.saveProfile, true, '<i class="fas fa-floppy-disk"></i> Lưu thông tin', 'Đang lưu...');

        try {
            const response = await callApi('/user', 'PUT', {
                name: el.inputName.value,
                email: el.inputEmail.value,
                phone: el.inputPhone.value,
            });

            if (!response.ok) {
                const handled = mapErrors(el.profileForm, response.data?.errors, {
                    name: el.inputName,
                    email: el.inputEmail,
                    phone: el.inputPhone,
                });
                if (!handled) showToast(response.data?.message || 'Không lưu được thông tin', 'error');
                return;
            }

            persistUser(response.data?.user || {});
            showToast(response.data?.message || 'Đã cập nhật thông tin');
        } catch (error) {
            showToast('Lỗi máy chủ', 'error');
        } finally {
            setButtonLoading(el.saveProfile, false, '<i class="fas fa-floppy-disk"></i> Lưu thông tin', 'Đang lưu...');
        }
    });

    el.passwordForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(el.passwordForm);

        if (el.newPassword.value !== el.newPasswordConfirmation.value) {
            showFieldError(el.newPasswordConfirmation, 'Xác nhận mật khẩu không khớp');
            return;
        }

        setButtonLoading(el.savePassword, true, '<i class="fas fa-key"></i> Đổi mật khẩu', 'Đang đổi...');

        try {
            const response = await callApi('/user/password', 'PUT', {
                current_password: el.currentPassword.value,
                new_password: el.newPassword.value,
                new_password_confirmation: el.newPasswordConfirmation.value,
            });

            if (!response.ok) {
                const handled = mapErrors(el.passwordForm, response.data?.errors, {
                    current_password: el.currentPassword,
                    new_password: el.newPassword,
                    new_password_confirmation: el.newPasswordConfirmation,
                });
                if (!handled) showToast(response.data?.message || 'Không đổi được mật khẩu', 'error');
                return;
            }

            el.passwordForm.reset();
            showToast(response.data?.message || 'Đã đổi mật khẩu');
        } catch (error) {
            showToast('Lỗi máy chủ', 'error');
        } finally {
            setButtonLoading(el.savePassword, false, '<i class="fas fa-key"></i> Đổi mật khẩu', 'Đang đổi...');
        }
    });

    el.avatarInput?.addEventListener('change', async () => {
        const file = el.avatarInput.files?.[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('avatar', file);

        try {
            const response = await callApi('/user/avatar', 'POST', formData);
            if (!response.ok) {
                showToast(response.data?.message || 'Không tải được ảnh đại diện', 'error');
                return;
            }

            persistUser({ avatar: response.data?.avatar_url || state.user.avatar });
            showToast(response.data?.message || 'Đã cập nhật ảnh đại diện');
        } catch (error) {
            showToast('Lỗi máy chủ', 'error');
        } finally {
            el.avatarInput.value = '';
        }
    });

    renderUser();
});
