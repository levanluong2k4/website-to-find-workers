import { callApi, getCurrentUser, requireRole, showToast } from '../api.js';

requireRole('customer');

const SETTINGS_KEY = 'customer_profile_preferences';
const ADDRESS_API_BASE = 'https://provinces.open-api.vn/api/v1';
const ACTIVE_BOOKING_STATUSES = ['cho_xac_nhan', 'da_xac_nhan', 'dang_lam', 'cho_thanh_toan', 'cho_hoan_thanh'];

const state = {
    user: getCurrentUser(),
    bookings: [],
    preferences: loadPreferences(),
    addressData: {
        provinces: [],
        districts: [],
        wards: [],
        hydratedFromSavedAddress: false,
    },
};

const el = {
    topbarAvatarFallback: document.getElementById('topbarAvatarFallback'),
    topbarAvatarImage: document.getElementById('topbarAvatarImage'),
    profileAvatarFallback: document.getElementById('profileAvatarFallback'),
    profileAvatarImageWrap: document.getElementById('profileAvatarImageWrap'),
    profileAvatar: document.getElementById('profileAvatar'),
    heroUserName: document.getElementById('heroUserName'),
    heroUserMeta: document.getElementById('heroUserMeta'),
    heroUserEmail: document.getElementById('heroUserEmail'),
    verificationBadge: document.getElementById('verificationBadge'),
    memberSinceInput: document.getElementById('memberSinceInput'),
    statTotalBookings: document.getElementById('statTotalBookings'),
    statProcessing: document.getElementById('statProcessing'),
    statReviews: document.getElementById('statReviews'),
    personalInfoForm: document.getElementById('personalInfoForm'),
    infoNameInput: document.getElementById('infoNameInput'),
    infoEmailInput: document.getElementById('infoEmailInput'),
    infoPhoneInput: document.getElementById('infoPhoneInput'),
    savePersonalInfoBtn: document.getElementById('savePersonalInfoBtn'),
    addressForm: document.getElementById('addressForm'),
    addressInput: document.getElementById('addressInput'),
    provinceSelect: document.getElementById('provinceSelect'),
    districtSelect: document.getElementById('districtSelect'),
    wardSelect: document.getElementById('wardSelect'),
    fullAddressPreview: document.getElementById('fullAddressPreview'),
    addressSubmitBtn: document.getElementById('addressSubmitBtn'),
    savedAddressText: document.getElementById('savedAddressText'),
    passwordForm: document.getElementById('passwordForm'),
    currentPassword: document.getElementById('currentPassword'),
    newPassword: document.getElementById('newPassword'),
    newPasswordConfirmation: document.getElementById('newPasswordConfirmation'),
    passwordSubmitBtn: document.getElementById('passwordSubmitBtn'),
    passwordStrengthLabel: document.getElementById('passwordStrengthLabel'),
    strengthSegments: Array.from(document.querySelectorAll('[data-strength-segment]')),
    avatarInput: document.getElementById('avatarInput'),
    triggerAvatarUploadBtn: document.getElementById('triggerAvatarUploadBtn'),
    focusPersonalInfoBtn: document.getElementById('focusPersonalInfoBtn'),
    focusAddressBtn: document.getElementById('focusAddressBtn'),
    editAddressBtn: document.getElementById('editAddressBtn'),
    emailUpdatesToggle: document.getElementById('emailUpdatesToggle'),
    pushUpdatesToggle: document.getElementById('pushUpdatesToggle'),
    promoUpdatesToggle: document.getElementById('promoUpdatesToggle'),
    dangerActionBtn: document.getElementById('dangerActionBtn'),
    passwordToggleButtons: Array.from(document.querySelectorAll('[data-password-target]')),
};

document.addEventListener('DOMContentLoaded', async () => {
    renderProfile(state.user);
    renderStats();
    updateAddressPreview();
    applyPreferences();
    bindEvents();

    await Promise.allSettled([fetchProfile(), fetchBookings(), loadProvinces()]);
});

function bindEvents() {
    el.personalInfoForm?.addEventListener('submit', handlePersonalInfoSubmit);
    el.addressForm?.addEventListener('submit', handleAddressSubmit);
    el.passwordForm?.addEventListener('submit', handlePasswordSubmit);
    el.newPassword?.addEventListener('input', updatePasswordStrength);
    el.addressInput?.addEventListener('input', updateAddressPreview);
    el.triggerAvatarUploadBtn?.addEventListener('click', () => el.avatarInput?.click());
    el.avatarInput?.addEventListener('change', handleAvatarUpload);
    el.focusPersonalInfoBtn?.addEventListener('click', () => focusField(el.infoNameInput));
    el.focusAddressBtn?.addEventListener('click', () => focusField(el.addressInput));
    el.editAddressBtn?.addEventListener('click', () => focusField(el.addressInput));
    el.provinceSelect?.addEventListener('change', handleProvinceChange);
    el.districtSelect?.addEventListener('change', handleDistrictChange);
    el.wardSelect?.addEventListener('change', updateAddressPreview);
    el.dangerActionBtn?.addEventListener('click', () => {
        showToast('Chức năng xóa tài khoản chưa được hỗ trợ trên hệ thống hiện tại.', 'error');
    });

    el.passwordToggleButtons.forEach((button) => {
        button.addEventListener('click', () => togglePasswordVisibility(button));
    });

    [
        [el.emailUpdatesToggle, 'email'],
        [el.pushUpdatesToggle, 'push'],
        [el.promoUpdatesToggle, 'promo'],
    ].forEach(([input, key]) => {
        input?.addEventListener('change', () => {
            state.preferences[key] = Boolean(input.checked);
            persistPreferences();
        });
    });
}

async function fetchProfile() {
    try {
        const response = await callApi('/user', 'GET');
        if (!response?.ok || !response.data) {
            throw new Error(response?.data?.message || 'Không thể tải hồ sơ người dùng.');
        }

        setUserState(response.data);
        renderProfile(response.data);
        hydrateAddressFromSavedValue(response.data?.address || '');
    } catch (error) {
        console.error('Profile load failed:', error);
        showToast('Không tải được thông tin tài khoản.', 'error');
    }
}

async function fetchBookings() {
    try {
        state.bookings = await fetchAllBookings();
        renderStats();
    } catch (error) {
        console.error('Booking stats load failed:', error);
    }
}

async function fetchAllBookings() {
    const collected = [];
    let page = 1;
    let lastPage = 1;

    do {
        const response = await callApi(`/don-dat-lich?page=${page}`, 'GET');
        if (!response?.ok) {
            throw new Error(response?.data?.message || 'Không tải được lịch sử đặt lịch.');
        }

        const payload = response.data || {};
        const items = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
        collected.push(...items);

        if (!Array.isArray(payload?.data)) {
            break;
        }

        lastPage = Number(payload?.last_page || 1);
        page += 1;
    } while (page <= lastPage);

    return collected;
}

async function loadProvinces() {
    try {
        setSelectLoading(el.provinceSelect, 'Đang tải tỉnh / thành...');
        const provinces = await fetchAddressJson(`${ADDRESS_API_BASE}/p`);
        state.addressData.provinces = Array.isArray(provinces) ? provinces : [];

        populateSelect(el.provinceSelect, state.addressData.provinces, 'Chọn tỉnh / thành phố');
        hydrateAddressFromSavedValue(state.user?.address || '');
    } catch (error) {
        console.error('Province API load failed:', error);
        populateSelect(el.provinceSelect, [], 'Không tải được tỉnh / thành');
    }
}

async function handleProvinceChange() {
    const provinceCode = Number(el.provinceSelect?.value || 0);

    state.addressData.districts = [];
    state.addressData.wards = [];
    populateSelect(el.districtSelect, [], provinceCode ? 'Đang tải quận / huyện...' : 'Chọn tỉnh / thành trước', !provinceCode);
    populateSelect(el.wardSelect, [], 'Chọn quận / huyện trước', true);
    updateAddressPreview();

    if (!provinceCode) {
        return;
    }

    try {
        const payload = await fetchAddressJson(`${ADDRESS_API_BASE}/p/${provinceCode}?depth=2`);
        state.addressData.districts = Array.isArray(payload?.districts) ? payload.districts : [];
        populateSelect(el.districtSelect, state.addressData.districts, 'Chọn quận / huyện');
    } catch (error) {
        console.error('District API load failed:', error);
        populateSelect(el.districtSelect, [], 'Không tải được quận / huyện', true);
    }
}

async function handleDistrictChange() {
    const districtCode = Number(el.districtSelect?.value || 0);

    state.addressData.wards = [];
    populateSelect(el.wardSelect, [], districtCode ? 'Đang tải phường / xã...' : 'Chọn quận / huyện trước', !districtCode);
    updateAddressPreview();

    if (!districtCode) {
        return;
    }

    try {
        const payload = await fetchAddressJson(`${ADDRESS_API_BASE}/d/${districtCode}?depth=2`);
        state.addressData.wards = Array.isArray(payload?.wards) ? payload.wards : [];
        populateSelect(el.wardSelect, state.addressData.wards, 'Chọn phường / xã');
    } catch (error) {
        console.error('Ward API load failed:', error);
        populateSelect(el.wardSelect, [], 'Không tải được phường / xã', true);
    }
}

async function hydrateAddressFromSavedValue(address) {
    if (!address || !state.addressData.provinces.length || state.addressData.hydratedFromSavedAddress) {
        updateAddressPreview(address);
        return;
    }

    const province = findMatchingDivision(state.addressData.provinces, address);
    if (!province) {
        updateAddressPreview(address);
        return;
    }

    state.addressData.hydratedFromSavedAddress = true;
    el.provinceSelect.value = String(province.code);
    await handleProvinceChange();

    const district = findMatchingDivision(state.addressData.districts, address);
    if (district) {
        el.districtSelect.value = String(district.code);
        await handleDistrictChange();
    }

    const ward = findMatchingDivision(state.addressData.wards, address);
    if (ward) {
        el.wardSelect.value = String(ward.code);
    }

    const detail = stripMatchedAddressParts(address, [ward?.name, district?.name, province?.name]);
    if (el.addressInput && document.activeElement !== el.addressInput) {
        el.addressInput.value = detail || address;
    }

    updateAddressPreview(address);
}

async function handlePersonalInfoSubmit(event) {
    event.preventDefault();

    const payload = {
        name: el.infoNameInput?.value.trim(),
        email: el.infoEmailInput?.value.trim(),
        phone: el.infoPhoneInput?.value.trim(),
    };

    if (!payload.name || !payload.email || !payload.phone) {
        showToast('Vui lòng điền đầy đủ họ tên, email và số điện thoại.', 'error');
        return;
    }

    setButtonLoading(el.savePersonalInfoBtn, true, 'Đang lưu...');

    try {
        const response = await callApi('/user', 'PUT', payload);
        if (!response?.ok) {
            throw new Error(getApiErrorMessage(response, 'Cập nhật thông tin thất bại.'));
        }

        setUserState(response.data.user);
        renderProfile(response.data.user);
        showToast(response.data?.message || 'Cập nhật thông tin thành công');
    } catch (error) {
        showToast(error.message || 'Cập nhật thông tin thất bại.', 'error');
    } finally {
        setButtonLoading(el.savePersonalInfoBtn, false, 'Lưu thay đổi');
    }
}

async function handleAddressSubmit(event) {
    event.preventDefault();

    const detail = (el.addressInput?.value || '').trim();
    const provinceName = getSelectedOptionText(el.provinceSelect);
    const districtName = getSelectedOptionText(el.districtSelect);
    const wardName = getSelectedOptionText(el.wardSelect);
    const address = [detail, wardName, districtName, provinceName].filter(Boolean).join(', ');

    if (!detail) {
        showToast('Vui lòng nhập số nhà hoặc tên đường.', 'error');
        focusField(el.addressInput);
        return;
    }

    if (!provinceName || !districtName || !wardName) {
        showToast('Vui lòng chọn đầy đủ tỉnh, quận/huyện và phường/xã.', 'error');
        return;
    }

    setButtonLoading(el.addressSubmitBtn, true, 'Đang lưu địa chỉ...');

    try {
        const response = await callApi('/user/address', 'PUT', { address });
        if (!response?.ok) {
            throw new Error(getApiErrorMessage(response, 'Cập nhật địa chỉ thất bại.'));
        }

        const nextUser = response.data?.user || { ...state.user, address };
        setUserState(nextUser);
        renderProfile(nextUser);
        updateAddressPreview(address);
        showToast(response.data?.message || 'Cập nhật địa chỉ thành công');
    } catch (error) {
        showToast(error.message || 'Cập nhật địa chỉ thất bại.', 'error');
    } finally {
        setButtonLoading(el.addressSubmitBtn, false, 'Lưu địa chỉ');
    }
}

async function handlePasswordSubmit(event) {
    event.preventDefault();

    const currentPassword = el.currentPassword?.value || '';
    const newPassword = el.newPassword?.value || '';
    const confirmation = el.newPasswordConfirmation?.value || '';

    if (!currentPassword || !newPassword || !confirmation) {
        showToast('Vui lòng nhập đầy đủ thông tin mật khẩu.', 'error');
        return;
    }

    if (newPassword.length < 6) {
        showToast('Mật khẩu mới phải có ít nhất 6 ký tự.', 'error');
        focusField(el.newPassword);
        return;
    }

    if (newPassword !== confirmation) {
        showToast('Xác nhận mật khẩu mới không khớp.', 'error');
        focusField(el.newPasswordConfirmation);
        return;
    }

    setButtonLoading(el.passwordSubmitBtn, true, 'Đang đổi mật khẩu...');

    try {
        const response = await callApi('/user/password', 'PUT', {
            current_password: currentPassword,
            new_password: newPassword,
            new_password_confirmation: confirmation,
        });

        if (!response?.ok) {
            throw new Error(getApiErrorMessage(response, 'Đổi mật khẩu thất bại.'));
        }

        el.passwordForm?.reset();
        updatePasswordStrength();
        showToast(response.data?.message || 'Đổi mật khẩu thành công');
    } catch (error) {
        showToast(error.message || 'Đổi mật khẩu thất bại.', 'error');
    } finally {
        setButtonLoading(el.passwordSubmitBtn, false, 'Đổi mật khẩu');
    }
}

async function handleAvatarUpload(event) {
    const file = event.target.files?.[0];
    if (!file) {
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        showToast('Ảnh vượt quá giới hạn 5MB.', 'error');
        el.avatarInput.value = '';
        return;
    }

    const formData = new FormData();
    formData.append('avatar', file);

    setButtonLoading(el.triggerAvatarUploadBtn, true, 'Đang tải ảnh...');

    try {
        const response = await callApi('/user/avatar', 'POST', formData);
        if (!response?.ok) {
            throw new Error(getApiErrorMessage(response, 'Tải ảnh đại diện thất bại.'));
        }

        const nextUser = { ...state.user, avatar: response.data?.avatar_url || state.user?.avatar };
        setUserState(nextUser);
        renderProfile(nextUser);
        showToast(response.data?.message || 'Cập nhật ảnh đại diện thành công');
    } catch (error) {
        showToast(error.message || 'Tải ảnh đại diện thất bại.', 'error');
    } finally {
        setButtonLoading(el.triggerAvatarUploadBtn, false, 'Cập nhật ảnh đại diện');
        el.avatarInput.value = '';
    }
}

function renderProfile(user) {
    const name = user?.name || 'Người dùng';
    const email = user?.email || 'Chưa cập nhật email';
    const phone = user?.phone || '';
    const address = user?.address || 'Chưa cập nhật địa chỉ mặc định.';
    const initials = getInitials(name);
    const avatarUrl = resolveAvatarUrl(user?.avatar);
    const joinDate = formatDate(user?.created_at);
    const joinYear = formatJoinYear(user?.created_at);
    const isVerified = Boolean(user?.email_verified_at);

    if (el.heroUserName) el.heroUserName.textContent = name;
    if (el.heroUserMeta) el.heroUserMeta.textContent = joinYear ? `Thành viên từ ${joinYear}` : 'Thành viên mới';
    if (el.heroUserEmail) el.heroUserEmail.textContent = email;
    if (el.memberSinceInput) el.memberSinceInput.value = joinDate;
    if (el.infoNameInput) el.infoNameInput.value = name;
    if (el.infoEmailInput) el.infoEmailInput.value = email;
    if (el.infoPhoneInput) el.infoPhoneInput.value = phone;
    if (el.savedAddressText) el.savedAddressText.textContent = address;

    if (el.verificationBadge) {
        el.verificationBadge.textContent = isVerified ? 'Tài khoản xác thực' : 'Chưa xác thực email';
        el.verificationBadge.classList.toggle('profile-pill--success', isVerified);
        el.verificationBadge.classList.toggle('profile-pill--muted', !isVerified);
    }

    renderAvatar(el.profileAvatarImageWrap, el.profileAvatar, el.profileAvatarFallback, avatarUrl, initials);
    renderAvatar(null, el.topbarAvatarImage, el.topbarAvatarFallback, avatarUrl, initials);
    updateAddressPreview(address);
}

function renderStats() {
    const total = state.bookings.length;
    const processing = state.bookings.filter((booking) => ACTIVE_BOOKING_STATUSES.includes(booking.trang_thai)).length;
    const reviews = state.bookings.filter((booking) => Array.isArray(booking.danh_gias) && booking.danh_gias.length > 0).length;

    if (el.statTotalBookings) el.statTotalBookings.textContent = formatStatNumber(total);
    if (el.statProcessing) el.statProcessing.textContent = formatStatNumber(processing);
    if (el.statReviews) el.statReviews.textContent = formatStatNumber(reviews);
}

function updateAddressPreview(fallbackAddress = '') {
    const detail = (el.addressInput?.value || '').trim();
    const provinceName = getSelectedOptionText(el.provinceSelect);
    const districtName = getSelectedOptionText(el.districtSelect);
    const wardName = getSelectedOptionText(el.wardSelect);
    const composed = [detail, wardName, districtName, provinceName].filter(Boolean).join(', ');
    const displayText = composed || fallbackAddress || 'Chưa chọn đủ thông tin địa chỉ.';

    if (el.fullAddressPreview) {
        el.fullAddressPreview.textContent = displayText;
    }
}

function renderAvatar(wrapper, imageNode, fallbackNode, avatarUrl, initials) {
    if (avatarUrl && imageNode) {
        imageNode.src = avatarUrl;
        imageNode.classList.remove('is-hidden');
        fallbackNode?.classList.add('is-hidden');
        wrapper?.classList.remove('is-hidden');
        return;
    }

    if (imageNode) {
        imageNode.src = '';
        imageNode.classList.add('is-hidden');
    }
    if (fallbackNode) {
        fallbackNode.textContent = initials;
        fallbackNode.classList.remove('is-hidden');
    }
    wrapper?.classList.add('is-hidden');
}

function updatePasswordStrength() {
    const value = el.newPassword?.value || '';
    const score = calculatePasswordScore(value);
    const colors = ['#dbe4ef', '#dbe4ef', '#dbe4ef', '#dbe4ef'];
    let label = 'Độ mạnh mật khẩu: chưa có';

    if (score > 0) {
        label = score <= 1 ? 'Độ mạnh mật khẩu: yếu' : score === 2 ? 'Độ mạnh mật khẩu: trung bình' : score === 3 ? 'Độ mạnh mật khẩu: khá' : 'Độ mạnh mật khẩu: mạnh';
    }

    for (let index = 0; index < score; index += 1) {
        colors[index] = score <= 1 ? '#ef4444' : score === 2 ? '#f59e0b' : '#1697e5';
        if (score === 4) {
            colors[index] = '#10b981';
        }
    }

    el.strengthSegments.forEach((segment, index) => {
        segment.style.backgroundColor = colors[index];
    });

    if (el.passwordStrengthLabel) {
        el.passwordStrengthLabel.textContent = label;
    }
}

function togglePasswordVisibility(button) {
    const targetId = button.dataset.passwordTarget;
    const input = targetId ? document.getElementById(targetId) : null;
    const icon = button.querySelector('.material-symbols-outlined');
    if (!input || !icon) {
        return;
    }

    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    icon.textContent = isPassword ? 'visibility_off' : 'visibility';
}

function calculatePasswordScore(value) {
    let score = 0;
    if (value.length >= 6) score += 1;
    if (/[A-Za-z]/.test(value) && /[0-9]/.test(value)) score += 1;
    if (/[^A-Za-z0-9]/.test(value)) score += 1;
    if (value.length >= 10) score += 1;
    return Math.min(score, 4);
}

function populateSelect(selectNode, items, placeholder, disabled = false) {
    if (!selectNode) {
        return;
    }

    const options = [`<option value="">${placeholder}</option>`]
        .concat(
            items.map((item) => `<option value="${item.code}">${escapeHtml(item.name)}</option>`)
        )
        .join('');

    selectNode.innerHTML = options;
    selectNode.disabled = disabled || !items.length;
}

function setSelectLoading(selectNode, placeholder) {
    if (!selectNode) {
        return;
    }

    selectNode.innerHTML = `<option value="">${escapeHtml(placeholder)}</option>`;
    selectNode.disabled = true;
}

async function fetchAddressJson(url) {
    const response = await fetch(url, {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error(`Address API failed with status ${response.status}`);
    }

    return response.json();
}

function findMatchingDivision(collection, address) {
    const normalizedAddress = normalizeText(address);
    return collection.find((item) => matchesDivision(normalizedAddress, item.name)) || null;
}

function normalizeText(value) {
    return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\b(thanh pho|tinh|quan|huyen|thi xa|thi tran|phuong|xa)\b/g, '')
        .replace(/[^a-z0-9]+/g, ' ')
        .trim();
}

function matchesDivision(addressText, divisionName) {
    if (!addressText || !divisionName) {
        return false;
    }

    const normalizedDivision = normalizeText(divisionName);
    return addressText.includes(normalizedDivision);
}

function stripMatchedAddressParts(address, parts) {
    let cleaned = String(address || '');
    parts.filter(Boolean).forEach((part) => {
        const escaped = escapeRegExp(part);
        cleaned = cleaned.replace(new RegExp(`,?\\s*${escaped}\\s*`, 'i'), ', ');
    });

    return cleaned.replace(/\s+,/g, ',').replace(/,\s*,/g, ', ').replace(/^,\s*|\s*,$/g, '').trim();
}

function getSelectedOptionText(selectNode) {
    if (!selectNode || !selectNode.value) {
        return '';
    }

    return selectNode.options[selectNode.selectedIndex]?.text || '';
}

function escapeHtml(value = '') {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeRegExp(value) {
    return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function focusField(field) {
    if (!field) {
        return;
    }

    field.scrollIntoView({ behavior: 'smooth', block: 'center' });
    window.setTimeout(() => field.focus(), 200);
}

function setUserState(user) {
    state.user = user;
    localStorage.setItem('user', JSON.stringify(user));
    window.dispatchEvent(new CustomEvent('user-updated', {
        detail: { user },
    }));
}

function setButtonLoading(button, isLoading, label) {
    if (!button) {
        return;
    }

    if (!button.dataset.defaultHtml) {
        button.dataset.defaultHtml = button.innerHTML;
    }

    button.disabled = isLoading;
    button.innerHTML = isLoading ? label : (button.dataset.defaultHtml || label);
}

function formatDate(value) {
    if (!value) {
        return '--';
    }

    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? '--'
        : new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(date);
}

function formatJoinYear(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '' : String(date.getFullYear());
}

function formatStatNumber(value) {
    return value < 10 ? String(value).padStart(2, '0') : String(value);
}

function getInitials(name) {
    return String(name || 'U')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase();
}

function resolveAvatarUrl(avatar) {
    if (!avatar) {
        return '';
    }

    if (/^https?:\/\//i.test(avatar) || avatar.startsWith('/')) {
        return avatar;
    }

    return `/storage/${avatar}`;
}

function getApiErrorMessage(response, fallbackMessage) {
    const errors = response?.data?.errors;
    if (errors && typeof errors === 'object') {
        const firstError = Object.values(errors)[0];
        if (Array.isArray(firstError) && firstError[0]) {
            return firstError[0];
        }
    }

    return response?.data?.message || fallbackMessage;
}

function loadPreferences() {
    try {
        const raw = localStorage.getItem(SETTINGS_KEY);
        const parsed = raw ? JSON.parse(raw) : {};
        return {
            email: parsed.email ?? true,
            push: parsed.push ?? true,
            promo: parsed.promo ?? false,
        };
    } catch (error) {
        return { email: true, push: true, promo: false };
    }
}

function applyPreferences() {
    if (el.emailUpdatesToggle) el.emailUpdatesToggle.checked = state.preferences.email;
    if (el.pushUpdatesToggle) el.pushUpdatesToggle.checked = state.preferences.push;
    if (el.promoUpdatesToggle) el.promoUpdatesToggle.checked = state.preferences.promo;
}

function persistPreferences() {
    localStorage.setItem(SETTINGS_KEY, JSON.stringify(state.preferences));
}
