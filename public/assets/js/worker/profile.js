import { callApi, getCurrentUser, logout, showToast } from '../api.js';

const state = {
    user: getCurrentUser(),
    profile: null,
    services: [],
    selectedServiceIds: [],
    pendingServiceIds: [],
};

if (!state.user || !['worker', 'admin'].includes(state.user.role)) {
    logout();
}

document.addEventListener('DOMContentLoaded', async () => {
    const el = {
        workerName: document.getElementById('workerName'),
        workerJoinDate: document.getElementById('workerJoinDate'),
        workerAvatar: document.getElementById('workerAvatarImg'),
        statRating: document.getElementById('statRating'),
        statReviewCount: document.getElementById('statReviewCount'),
        statCompleted: document.getElementById('statCompleted'),
        workerServiceTags: ensureWorkerServiceTags(),
        workerServiceSummary: ensureWorkerServiceSummary(),
        reviewsList: document.getElementById('reviewsList'),
        formProfile: document.getElementById('formWorkerProfile'),
        inputHoTen: document.getElementById('inputHoTen'),
        inputPhone: document.getElementById('inputPhone'),
        inputEmail: document.getElementById('inputEmail'),
        inputAddress: document.getElementById('inputAddress'),
        inputTrangThai: document.getElementById('inputTrangThai'),
        inputKinhNghiem: document.getElementById('inputKinhNghiem'),
        inputChungChi: document.getElementById('inputChungChi'),
        btnUpdateProfile: document.getElementById('btnUpdateProfile'),
        serviceCheckboxContainer: document.getElementById('serviceCheckboxContainer'),
        serviceSelectionCount: document.getElementById('serviceSelectionCount'),
        serviceModalTrigger: document.getElementById('serviceModalTrigger'),
        uploadAvatar: document.getElementById('uploadAvatar'),
    };

    if (!el.formProfile) return;

    prepareServiceManagerUi(el);

    bindAvatarUpload(el);
    bindFormSubmit(el);

    populateUserFields(el, state.user);
    renderSelectedServices(el);

    await Promise.allSettled([
        loadProfile(el),
        loadCompletedStats(el),
        loadReviews(el),
    ]);
});

function ensureWorkerServiceTags() {
    let node = document.getElementById('workerServiceTags');
    if (node) return node;

    const workerName = document.getElementById('workerName');
    if (!workerName?.parentElement) return null;

    const legacyBadge = workerName.nextElementSibling;
    if (legacyBadge?.tagName === 'SPAN' && !legacyBadge.id) {
        legacyBadge.style.display = 'none';
    }

    node = document.createElement('div');
    node.id = 'workerServiceTags';
    node.className = 'worker-service-tags';
    workerName.insertAdjacentElement('afterend', node);
    return node;
}

function ensureWorkerServiceSummary() {
    let node = document.getElementById('workerServiceSummary');
    if (node) return node;

    const serviceTags = document.getElementById('workerServiceTags');
    if (!serviceTags?.parentElement) return null;

    node = document.createElement('p');
    node.id = 'workerServiceSummary';
    node.className = 'worker-service-summary';
    serviceTags.insertAdjacentElement('afterend', node);
    return node;
}

function prepareServiceManagerUi(el) {
    const serviceSection = el.serviceCheckboxContainer?.parentElement;
    if (!serviceSection || !el.serviceCheckboxContainer) return;

    const triggerAnchor = el.workerServiceSummary || el.workerServiceTags;
    if (el.serviceModalTrigger && triggerAnchor) {
        el.serviceModalTrigger.style.marginTop = '0.85rem';
        triggerAnchor.insertAdjacentElement('afterend', el.serviceModalTrigger);
    }

    const serviceToolbar = el.serviceSelectionCount?.parentElement || null;
    const serviceTitle = serviceToolbar?.previousElementSibling || null;
    const serviceHint = serviceToolbar?.nextElementSibling || null;

    if (serviceTitle) serviceTitle.style.display = 'none';
    if (serviceToolbar) serviceToolbar.style.display = 'none';
    if (serviceHint) serviceHint.style.display = 'none';

    let modal = document.getElementById('serviceManagerModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'serviceManagerModal';
        modal.className = 'worker-service-modal d-none';
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = `
            <div class="worker-service-modal-card" role="dialog" aria-modal="true" aria-labelledby="serviceManagerTitle">
                <div class="worker-service-modal-head">
                    <div>
                        <h3 id="serviceManagerTitle" style="margin:0; font-family:'DM Sans',sans-serif; font-size:1.05rem; font-weight:800; color:#0f172a;">Quan ly dich vu lam viec</h3>
                        <p style="margin:.35rem 0 0; font-size:.8rem; color:#64748b;">Chon them hoac bo dich vu ma ban co the nhan sua.</p>
                    </div>
                    <button type="button" id="serviceModalClose" class="worker-service-modal-btn is-secondary">Dong</button>
                </div>
                <div class="worker-service-modal-body"></div>
                <div class="worker-service-modal-foot">
                    <button type="button" id="serviceModalCancel" class="worker-service-modal-btn is-secondary">Huy</button>
                    <button type="button" id="serviceModalApply" class="worker-service-modal-btn is-primary">Cap nhat danh sach</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    const modalBody = modal.querySelector('.worker-service-modal-body');
    if (modalBody && el.serviceCheckboxContainer.parentElement !== modalBody) {
        modalBody.appendChild(el.serviceCheckboxContainer);
    }

    el.serviceManagerModal = modal;
    el.serviceModalClose = modal.querySelector('#serviceModalClose');
    el.serviceModalCancel = modal.querySelector('#serviceModalCancel');
    el.serviceModalApply = modal.querySelector('#serviceModalApply');

    bindServiceModalEvents(el);
}

function setCheckedServiceIds(serviceIds = []) {
    const selected = new Set(serviceIds.map((value) => Number(value)));
    document.querySelectorAll('.service-checkbox').forEach((checkbox) => {
        checkbox.checked = selected.has(Number(checkbox.value));
    });
}

function openServiceModal(el) {
    if (!el.serviceManagerModal) return;

    state.pendingServiceIds = getSelectedServiceIdsFromDom();
    el.serviceManagerModal.classList.remove('d-none');
    el.serviceManagerModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeServiceModal(el, { restore = false } = {}) {
    if (!el.serviceManagerModal) return;

    if (restore) {
        setCheckedServiceIds(state.pendingServiceIds);
        renderSelectedServices(el);
    }

    el.serviceManagerModal.classList.add('d-none');
    el.serviceManagerModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function bindServiceModalEvents(el) {
    if (el.serviceModalEventsBound) return;
    el.serviceModalEventsBound = true;

    el.serviceModalTrigger?.addEventListener('click', () => openServiceModal(el));
    el.serviceModalClose?.addEventListener('click', () => closeServiceModal(el, { restore: true }));
    el.serviceModalCancel?.addEventListener('click', () => closeServiceModal(el, { restore: true }));
    el.serviceModalApply?.addEventListener('click', () => {
        closeServiceModal(el, { restore: false });
        showToast('Da cap nhat danh sach tam thoi. Bam "Luu thay doi" de luu len ho so.');
    });
    el.serviceManagerModal?.addEventListener('click', (event) => {
        if (event.target === el.serviceManagerModal) {
            closeServiceModal(el, { restore: true });
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && el.serviceManagerModal && !el.serviceManagerModal.classList.contains('d-none')) {
            closeServiceModal(el, { restore: true });
        }
    });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function getAvatarUrl(source) {
    if (!source) return '/assets/images/user-default.png';
    if (String(source).startsWith('http')) return source;
    if (String(source).startsWith('/')) return source;
    return `/storage/${source}`;
}

function setAvatarContent(element, avatarUrl, fallbackName = 'T') {
    if (!element) return;

    const safeFallback = String(fallbackName || 'T').charAt(0).toUpperCase();
    if (!avatarUrl) {
        element.innerHTML = safeFallback;
        return;
    }

    element.innerHTML = `<img src="${escapeHtml(avatarUrl)}" alt="${escapeHtml(fallbackName)}" style="width:100%;height:100%;display:block;object-fit:cover;border-radius:inherit;">`;
}

function formatJoinDate(dateValue) {
    if (!dateValue) return '--/--/----';

    const parsed = new Date(dateValue);
    if (Number.isNaN(parsed.getTime())) return '--/--/----';

    return parsed.toLocaleDateString('vi-VN');
}

function updateLocalUser(nextUser) {
    state.user = {
        ...state.user,
        ...nextUser,
    };

    localStorage.setItem('user', JSON.stringify(state.user));

    const sidebarName = document.getElementById('sidebarName');
    if (sidebarName) sidebarName.textContent = state.user.name || 'Tho';

    const sidebarAvatar = document.getElementById('sidebarAvatar');
    setAvatarContent(sidebarAvatar, getAvatarUrl(state.user.avatar), state.user.name || 'T');

    const dispatchSidebarName = document.getElementById('dispatchSidebarName');
    if (dispatchSidebarName) dispatchSidebarName.textContent = state.user.name || 'Tho ky thuat';

    const dispatchSidebarAvatar = document.getElementById('dispatchSidebarAvatar');
    setAvatarContent(dispatchSidebarAvatar, getAvatarUrl(state.user.avatar), state.user.name || 'T');
}

function populateUserFields(el, userData, profileData = null) {
    const mergedUser = {
        ...userData,
        ...(profileData?.user || {}),
    };

    if (el.workerName) el.workerName.textContent = mergedUser.name || 'Tho Tot NTU';
    if (el.workerJoinDate) el.workerJoinDate.textContent = `Tham gia tu ${formatJoinDate(mergedUser.created_at || userData?.created_at)}`;
    if (el.workerAvatar) el.workerAvatar.src = getAvatarUrl(mergedUser.avatar);

    if (el.inputHoTen) el.inputHoTen.value = mergedUser.name || '';
    if (el.inputPhone) el.inputPhone.value = mergedUser.phone || '';
    if (el.inputEmail) el.inputEmail.value = mergedUser.email || '';
    if (el.inputAddress) el.inputAddress.value = mergedUser.address || '';
}

function getSelectedServiceIdsFromDom() {
    return Array.from(document.querySelectorAll('.service-checkbox:checked'))
        .map((checkbox) => Number(checkbox.value))
        .filter((value) => Number.isInteger(value) && value > 0);
}

function renderSelectedServices(el) {
    state.selectedServiceIds = getSelectedServiceIdsFromDom();
    const selectedServices = state.services.filter((service) => state.selectedServiceIds.includes(Number(service.id)));

    if (el.serviceSelectionCount) {
        el.serviceSelectionCount.textContent = `${selectedServices.length} dich vu da chon`;
    }

    if (el.workerServiceSummary) {
        el.workerServiceSummary.textContent = selectedServices.length > 0
            ? `Chuyen mon hien tai: ${selectedServices.length} dich vu`
            : 'Chua cap nhat danh muc lam viec';
    }

    if (el.workerServiceTags) {
        el.workerServiceTags.innerHTML = selectedServices.length > 0
            ? selectedServices.map((service) => `<span class="worker-service-tag">${escapeHtml(service.ten_dich_vu)}</span>`).join('')
            : '<span class="worker-service-tag is-muted">Chua chon dich vu</span>';
    }

    document.querySelectorAll('.worker-service-option').forEach((option) => {
        const checkbox = option.querySelector('.service-checkbox');
        option.classList.toggle('is-selected', Boolean(checkbox?.checked));
    });
}

function renderServices(el, selectedIds = []) {
    if (!el.serviceCheckboxContainer) return;

    if (!Array.isArray(state.services) || state.services.length === 0) {
        el.serviceCheckboxContainer.innerHTML = '<div style="font-size:.82rem; color:#ef4444;">Khong tai duoc danh muc dich vu.</div>';
        renderSelectedServices(el);
        return;
    }

    el.serviceCheckboxContainer.innerHTML = state.services.map((service) => {
        const serviceId = Number(service.id);
        const isChecked = selectedIds.includes(serviceId);
        const description = service.mo_ta || 'Them dich vu nay vao ho so de khach hang co the dat dung chuyen mon.';

        return `
            <label class="worker-service-option ${isChecked ? 'is-selected' : ''}" for="srv_${serviceId}">
                <input class="form-check-input service-checkbox" type="checkbox" value="${serviceId}" id="srv_${serviceId}" ${isChecked ? 'checked' : ''}>
                <div>
                    <div class="worker-service-option-title">${escapeHtml(service.ten_dich_vu)}</div>
                    <div class="worker-service-option-copy">${escapeHtml(description)}</div>
                </div>
            </label>
        `;
    }).join('');

    el.serviceCheckboxContainer.querySelectorAll('.service-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', () => renderSelectedServices(el));
    });

    renderSelectedServices(el);
}

async function loadServices(el, selectedIds = []) {
    try {
        const response = await callApi('/danh-muc-dich-vu', 'GET');
        state.services = Array.isArray(response.data) ? response.data : [];
        renderServices(el, selectedIds);
    } catch (error) {
        if (el.serviceCheckboxContainer) {
            el.serviceCheckboxContainer.innerHTML = '<div style="font-size:.82rem; color:#ef4444;">Loi tai danh sach dich vu.</div>';
        }
    }
}

async function loadProfile(el) {
    try {
        const profileResponse = await callApi(`/ho-so-tho/${state.user.id}`, 'GET');
        if (!profileResponse.ok || !profileResponse.data) {
            await loadServices(el, []);
            return;
        }

        state.profile = profileResponse.data;
        const workerProfile = profileResponse.data;
        const selectedIds = Array.isArray(workerProfile?.user?.dich_vus)
            ? workerProfile.user.dich_vus.map((service) => Number(service.id)).filter(Boolean)
            : [];

        if (el.inputTrangThai) el.inputTrangThai.value = workerProfile.dang_hoat_dong ? '1' : '0';
        if (el.inputKinhNghiem) el.inputKinhNghiem.value = workerProfile.kinh_nghiem || '';
        if (el.inputChungChi) el.inputChungChi.value = workerProfile.chung_chi || '';
        if (el.statRating) el.statRating.textContent = Number(workerProfile.danh_gia_trung_binh || 0).toFixed(1);
        if (el.statReviewCount) el.statReviewCount.textContent = String(workerProfile.tong_so_danh_gia || 0);

        populateUserFields(el, state.user, workerProfile);
        updateLocalUser(workerProfile.user || {});

        await loadServices(el, selectedIds);
    } catch (error) {
        await loadServices(el, []);
        showToast('Khong tai duoc ho so tho.', 'error');
    }
}

async function loadCompletedStats(el) {
    try {
        const response = await callApi('/don-dat-lich', 'GET');
        if (!response.ok) return;

        const bookings = Array.isArray(response.data?.data)
            ? response.data.data
            : (Array.isArray(response.data) ? response.data : []);
        const completedCount = bookings.filter((booking) => booking?.trang_thai === 'da_xong').length;

        if (el.statCompleted) el.statCompleted.textContent = String(completedCount);
    } catch (error) {
        if (el.statCompleted) el.statCompleted.textContent = '0';
    }
}

function renderReviews(el, reviews = []) {
    if (!el.reviewsList) return;

    if (!Array.isArray(reviews) || reviews.length === 0) {
        el.reviewsList.innerHTML = '<div style="text-align:center; padding:1rem 0; color:#94a3b8; font-size:.8rem;">Chua co danh gia nao.</div>';
        return;
    }

    el.reviewsList.innerHTML = reviews.slice(0, 3).map((review) => {
        const reviewerName = review?.nguoi_danh_gia?.name || 'Khach hang';
        const comment = review?.nhan_xet || 'Khong co nhan xet chi tiet.';
        const stars = '★'.repeat(Number(review?.so_sao || 0)) + '☆'.repeat(Math.max(0, 5 - Number(review?.so_sao || 0)));

        return `
            <div class="review-card">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:.5rem;">
                    <strong style="font-size:.82rem; color:#0f172a;">${escapeHtml(reviewerName)}</strong>
                    <span style="font-size:.8rem; color:#f59e0b; letter-spacing:.08em;">${stars}</span>
                </div>
                <p style="font-size:.78rem; color:#64748b; margin:.45rem 0 0;">${escapeHtml(comment)}</p>
            </div>
        `;
    }).join('');
}

async function loadReviews(el) {
    try {
        const response = await callApi(`/ho-so-tho/${state.user.id}/danh-gia`, 'GET');
        const reviews = Array.isArray(response.data?.data) ? response.data.data : [];
        renderReviews(el, reviews);
    } catch (error) {
        if (el.reviewsList) {
            el.reviewsList.innerHTML = '<div style="text-align:center; padding:1rem 0; color:#ef4444; font-size:.8rem;">Khong tai duoc danh gia.</div>';
        }
    }
}

function bindAvatarUpload(el) {
    if (!el.uploadAvatar) return;

    el.uploadAvatar.addEventListener('change', async (event) => {
        const file = event.target.files?.[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            showToast('Vui long chon dung tep hinh anh.', 'error');
            el.uploadAvatar.value = '';
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            showToast('File anh qua lon, vui long chon file duoi 5MB.', 'error');
            el.uploadAvatar.value = '';
            return;
        }

        const previousAvatarUrl = getAvatarUrl(state.user?.avatar);
        const previewUrl = URL.createObjectURL(file);

        if (el.workerAvatar) {
            el.workerAvatar.src = previewUrl;
        }

        setAvatarContent(document.getElementById('sidebarAvatar'), previewUrl, state.user?.name || 'T');
        setAvatarContent(document.getElementById('dispatchSidebarAvatar'), previewUrl, state.user?.name || 'T');
        setAvatarContent(document.getElementById('dispatchTopAvatar'), previewUrl, state.user?.name || 'T');

        const formData = new FormData();
        formData.append('avatar', file);

        try {
            showToast('Dang tai anh len...', 'success');
            const response = await callApi('/user/avatar', 'POST', formData);

            if (!response.ok) {
                showToast(response.data?.message || 'Tai anh that bai.', 'error');
                return;
            }

            const avatarUrl = response.data?.avatar_url || '';
            if (el.workerAvatar) el.workerAvatar.src = avatarUrl || '/assets/images/user-default.png';
            updateLocalUser({ avatar: avatarUrl });
            showToast('Cap nhat anh dai dien thanh cong.');
        } catch (error) {
            if (el.workerAvatar) {
                el.workerAvatar.src = previousAvatarUrl;
            }
            setAvatarContent(document.getElementById('sidebarAvatar'), previousAvatarUrl, state.user?.name || 'T');
            setAvatarContent(document.getElementById('dispatchSidebarAvatar'), previousAvatarUrl, state.user?.name || 'T');
            setAvatarContent(document.getElementById('dispatchTopAvatar'), previousAvatarUrl, state.user?.name || 'T');
            showToast('Loi ket noi khi tai anh.', 'error');
        } finally {
            URL.revokeObjectURL(previewUrl);
            el.uploadAvatar.value = '';
        }
    });
}

function setSubmitLoading(el, isLoading) {
    if (!el.btnUpdateProfile) return;

    if (isLoading) {
        el.btnUpdateProfile.disabled = true;
        el.btnUpdateProfile.dataset.originalText = el.btnUpdateProfile.innerHTML;
        el.btnUpdateProfile.innerHTML = '<span class="material-symbols-outlined" style="font-size:1.1rem;">progress_activity</span> Dang luu...';
        return;
    }

    el.btnUpdateProfile.disabled = false;
    el.btnUpdateProfile.innerHTML = el.btnUpdateProfile.dataset.originalText || '<span class="material-symbols-outlined" style="font-size:1.1rem;">save</span> Luu thay doi';
    delete el.btnUpdateProfile.dataset.originalText;
}

function bindFormSubmit(el) {
    el.formProfile.addEventListener('submit', async (event) => {
        event.preventDefault();

        const selectedServiceIds = getSelectedServiceIdsFromDom();
        if (selectedServiceIds.length === 0) {
            showToast('Vui long chon it nhat mot dich vu ban nhan lam.', 'error');
            return;
        }

        setSubmitLoading(el, true);

        try {
            const userPayload = {
                name: el.inputHoTen?.value?.trim() || '',
                email: el.inputEmail?.value?.trim() || '',
                phone: el.inputPhone?.value?.trim() || '',
            };

            const userResponse = await callApi('/user', 'PUT', userPayload);
            if (!userResponse.ok) {
                throw new Error(userResponse.data?.message || 'Khong cap nhat duoc thong tin ca nhan.');
            }

            const addressValue = el.inputAddress?.value?.trim() || '';
            if (addressValue) {
                const addressResponse = await callApi('/user/address', 'PUT', { address: addressValue });
                if (!addressResponse.ok) {
                    throw new Error(addressResponse.data?.message || 'Khong cap nhat duoc dia chi.');
                }
                updateLocalUser(addressResponse.data?.user || {});
            }

            const workerPayload = {
                dang_hoat_dong: el.inputTrangThai?.value === '1',
                kinh_nghiem: el.inputKinhNghiem?.value?.trim() || '',
                chung_chi: el.inputChungChi?.value?.trim() || '',
                dich_vu_ids: selectedServiceIds,
            };

            const workerResponse = await callApi('/ho-so-tho', 'PUT', workerPayload);
            if (!workerResponse.ok) {
                throw new Error(workerResponse.data?.message || 'Khong cap nhat duoc ho so tho.');
            }

            updateLocalUser(userResponse.data?.user || {});
            populateUserFields(el, state.user, workerResponse.data?.data || state.profile);
            state.selectedServiceIds = selectedServiceIds;
            renderSelectedServices(el);
            showToast('Da cap nhat ho so thanh cong.');
        } catch (error) {
            showToast(error.message || 'Co loi xay ra, vui long thu lai.', 'error');
        } finally {
            setSubmitLoading(el, false);
        }
    });
}
