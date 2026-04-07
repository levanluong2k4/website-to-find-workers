import { callApi, confirmAction, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const refs = {
        refresh: document.getElementById('customerFeedbackRefreshButton'),
        stats: document.getElementById('customerFeedbackStats'),
        caption: document.getElementById('customerFeedbackCaption'),
        search: document.getElementById('customerFeedbackSearch'),
        type: document.getElementById('customerFeedbackType'),
        status: document.getElementById('customerFeedbackStatus'),
        list: document.getElementById('customerFeedbackList'),
        preview: document.getElementById('customerFeedbackPreview'),
    };

    const state = {
        cases: [],
        selectedId: null,
        searchTimer: null,
        actionBusy: false,
    };

    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');

    const number = new Intl.NumberFormat('vi-VN');
    const formatNumber = (value) => number.format(Number(value || 0));

    const toneClass = (tone) => {
        switch (tone) {
            case 'success':
                return 'feedback-admin-pill--success';
            case 'warning':
                return 'feedback-admin-pill--warning';
            case 'danger':
                return 'feedback-admin-pill--danger';
            case 'muted':
                return 'feedback-admin-pill--muted';
            default:
                return 'feedback-admin-pill--info';
        }
    };

    const buildQuery = () => {
        const params = new URLSearchParams(window.location.search);

        params.delete('search');
        params.delete('type');
        params.delete('status');

        if (refs.search.value.trim()) {
            params.set('search', refs.search.value.trim());
        }

        if (refs.type.value) {
            params.set('type', refs.type.value);
        }

        if (refs.status.value) {
            params.set('status', refs.status.value);
        }

        const query = params.toString();
        return query ? `?${query}` : '';
    };

    const syncFiltersFromUrl = () => {
        const url = new URL(window.location.href);

        refs.search.value = url.searchParams.get('search') || '';
        refs.type.value = url.searchParams.get('type') || '';
        refs.status.value = url.searchParams.get('status') || '';
    };

    const syncUrl = () => {
        const url = new URL(window.location.href);
        const params = new URLSearchParams(buildQuery().replace(/^\?/, ''));

        url.search = params.toString();
        window.history.replaceState({}, '', url);
    };

    const getSelectedCase = () => state.cases.find((item) => item.id === state.selectedId) || null;

    const renderStats = (summary) => {
        const cards = [
            ['Tong case', formatNumber(summary?.total_cases || 0), 'Tat ca phan hoi dang hien thi'],
            ['Danh gia thap', formatNumber(summary?.review_cases || 0), 'Review can admin xem lai'],
            ['Huy don', formatNumber(summary?.cancellation_cases || 0), 'Don huy co ly do tu khach'],
            ['Dang xu ly', formatNumber(summary?.in_progress_cases || 0), 'Case admin dang theo doi'],
            ['Da xu ly', formatNumber(summary?.resolved_cases || 0), 'Case da dong'],
        ];

        refs.stats.innerHTML = cards.map(([label, value, meta]) => `
            <article class="feedback-admin-stat">
                <span class="feedback-admin-stat__label">${escapeHtml(label)}</span>
                <span class="feedback-admin-stat__value">${escapeHtml(value)}</span>
                <span class="feedback-admin-stat__meta">${escapeHtml(meta)}</span>
            </article>
        `).join('');
    };

    const renderList = () => {
        if (!state.cases.length) {
            refs.caption.textContent = 'Khong tim thay phan hoi phu hop voi bo loc hien tai.';
            refs.list.innerHTML = '<div class="feedback-admin-empty">Khong co feedback hoac khieu nai phu hop.</div>';
            renderPreview(null);
            return;
        }

        refs.caption.textContent = `${formatNumber(state.cases.length)} case trong ket qua hien tai.`;

        refs.list.innerHTML = state.cases.map((item) => `
            <article class="feedback-admin-item ${item.id === state.selectedId ? 'is-selected' : ''}" data-case-id="${escapeHtml(item.id)}">
                <div class="feedback-admin-item-top">
                    <div>
                        <div class="feedback-admin-code">${escapeHtml(item.booking_code || '--')} - ${escapeHtml(item.type_label || 'Case')}</div>
                        <div class="feedback-admin-name">${escapeHtml(item.customer_name || 'Khach hang')}</div>
                    </div>
                    <div class="feedback-admin-pill-stack">
                        <span class="feedback-admin-pill ${toneClass(item.status_tone)}">${escapeHtml(item.status_label || 'Moi')}</span>
                    </div>
                </div>
                <div class="feedback-admin-subcopy">
                    ${escapeHtml(item.service_label || 'Dich vu')}<br>
                    ${escapeHtml(item.worker_name || 'Chua gan tho')}<br>
                    ${escapeHtml(item.created_label || '--')}
                </div>
                <div class="feedback-admin-subcopy">
                    ${escapeHtml(item.summary || 'Khong co mo ta.')}
                </div>
            </article>
        `).join('');
    };

    const promptResolutionNote = async () => {
        if (typeof Swal !== 'undefined') {
            const result = await Swal.fire({
                title: 'Cap nhat ket qua xu ly',
                input: 'textarea',
                inputLabel: 'Ghi chu xu ly (khong bat buoc)',
                inputAttributes: {
                    maxlength: 1000,
                    rows: 4,
                },
                showCancelButton: true,
                confirmButtonText: 'Da xu ly',
                cancelButtonText: 'Huy',
                confirmButtonColor: '#0284c7',
                cancelButtonColor: '#cbd5e1',
            });

            if (!result.isConfirmed) {
                return null;
            }

            return (result.value || '').trim();
        }

        const confirmed = window.confirm('Danh dau case nay da xu ly?');
        if (!confirmed) {
            return null;
        }

        return (window.prompt('Ghi chu xu ly (khong bat buoc):', '') || '').trim();
    };

    const renderPreview = (item) => {
        if (!item) {
            refs.preview.innerHTML = '<div class="feedback-admin-empty">Chua chon case.</div>';
            return;
        }

        const actionDisabled = state.actionBusy ? 'disabled' : '';
        const claimLabel = item.status === 'resolved' ? 'Mo lai xu ly' : 'Nhan xu ly';
        const resolveButton = item.status === 'resolved'
            ? ''
            : `<button type="button" class="feedback-admin-action feedback-admin-action--primary" data-feedback-action="resolve" ${actionDisabled}>Da xu ly</button>`;

        refs.preview.innerHTML = `
            <div class="feedback-admin-block">
                <span class="feedback-admin-label">Khach hang</span>
                <div class="feedback-admin-value">${escapeHtml(item.customer_name || 'Khach hang')}<br>${escapeHtml(item.customer_phone || 'Chua co SDT')}</div>
            </div>
            <div class="feedback-admin-block">
                <span class="feedback-admin-label">Don lien quan</span>
                <div class="feedback-admin-value">${escapeHtml(item.booking_code || '--')}<br>${escapeHtml(item.service_label || 'Dich vu')}</div>
            </div>
            <div class="feedback-admin-block">
                <span class="feedback-admin-label">Noi dung phan hoi</span>
                <div class="feedback-admin-value">${escapeHtml(item.content || 'Khong co noi dung chi tiet.')}</div>
            </div>
            <div class="feedback-admin-block">
                <span class="feedback-admin-label">Trang thai</span>
                <div class="feedback-admin-value">
                    <span class="feedback-admin-pill ${toneClass(item.status_tone)}">${escapeHtml(item.status_label || 'Moi')}</span>
                    <div class="feedback-admin-subcopy" style="margin-top:10px;">
                        ${escapeHtml(item.created_label || '--')}<br>
                        ${escapeHtml(item.worker_name || 'Chua gan tho')}<br>
                        ${escapeHtml(item.assigned_admin_name || 'Chua co admin nhan xu ly')}
                    </div>
                </div>
            </div>
            ${item.resolution_note ? `
                <div class="feedback-admin-block">
                    <span class="feedback-admin-label">Ket qua xu ly</span>
                    <div class="feedback-admin-value">${escapeHtml(item.resolution_note)}</div>
                </div>
            ` : ''}
            <div class="feedback-admin-actions">
                <button type="button" class="feedback-admin-action" data-feedback-action="claim" ${actionDisabled}>${escapeHtml(claimLabel)}</button>
                ${resolveButton}
                ${item.customer_url ? `<a class="feedback-admin-action" href="${escapeHtml(item.customer_url)}">Mo ho so khach</a>` : ''}
                ${item.booking_url ? `<a class="feedback-admin-action" href="${escapeHtml(item.booking_url)}">Mo don lien quan</a>` : ''}
            </div>
        `;
    };

    const selectCase = (caseId) => {
        state.selectedId = caseId;
        renderList();
        renderPreview(getSelectedCase());
    };

    const runCaseAction = async (action, item) => {
        if (!item || state.actionBusy) {
            return;
        }

        try {
            state.actionBusy = true;
            renderPreview(item);

            if (action === 'claim') {
                const confirmation = await confirmAction(
                    'Nhan xu ly case?',
                    'Case se duoc danh dau dang xu ly boi admin hien tai.',
                    'Nhan xu ly'
                );

                if (!confirmation?.isConfirmed) {
                    return;
                }

                const response = await callApi(`/admin/customer-feedback/${encodeURIComponent(item.id)}/claim`, 'POST', {});

                if (!response?.ok) {
                    throw new Error(response?.data?.message || 'Khong the nhan xu ly case');
                }

                showToast(response.data?.message || 'Case da duoc nhan xu ly');
            }

            if (action === 'resolve') {
                const resolutionNote = await promptResolutionNote();

                if (resolutionNote === null) {
                    return;
                }

                const response = await callApi(`/admin/customer-feedback/${encodeURIComponent(item.id)}/resolve`, 'POST', {
                    resolution_note: resolutionNote,
                });

                if (!response?.ok) {
                    throw new Error(response?.data?.message || 'Khong the cap nhat case');
                }

                showToast(response.data?.message || 'Case da duoc danh dau da xu ly');
            }

            await loadCases({ silent: true });
        } catch (error) {
            console.error('Customer feedback action failed:', error);
            showToast(error.message || 'Khong the xu ly case', 'error');
        } finally {
            state.actionBusy = false;
            renderPreview(getSelectedCase());
        }
    };

    const loadCases = async ({ silent = false } = {}) => {
        if (!silent) {
            refs.caption.textContent = 'Dang tai danh sach case...';
            refs.list.innerHTML = '<div class="feedback-admin-empty">Dang tai du lieu...</div>';
            refs.preview.innerHTML = '<div class="feedback-admin-empty">Dang tai xem nhanh...</div>';
        }

        try {
            syncUrl();
            const response = await callApi(`/admin/customer-feedback${buildQuery()}`, 'GET');

            if (!response?.ok) {
                throw new Error(response?.data?.message || 'Khong the tai feedback khach hang');
            }

            const payload = response.data?.data || {};

            renderStats(payload.summary || {});
            state.cases = Array.isArray(payload.cases) ? payload.cases : [];

            if (!state.cases.some((item) => item.id === state.selectedId)) {
                state.selectedId = state.cases[0]?.id || null;
            }

            renderList();
            renderPreview(getSelectedCase());
        } catch (error) {
            console.error('Load feedback cases failed:', error);
            refs.caption.textContent = 'Khong the tai du lieu.';
            refs.list.innerHTML = '<div class="feedback-admin-empty">Khong the tai danh sach case.</div>';
            refs.preview.innerHTML = '<div class="feedback-admin-empty">Khong the tai chi tiet case.</div>';
            showToast(error.message || 'Khong the tai feedback khach hang', 'error');
        }
    };

    refs.list.addEventListener('click', (event) => {
        const item = event.target.closest('[data-case-id]');

        if (item) {
            selectCase(item.dataset.caseId);
        }
    });

    refs.preview.addEventListener('click', (event) => {
        const actionButton = event.target.closest('[data-feedback-action]');

        if (!actionButton) {
            return;
        }

        event.preventDefault();
        runCaseAction(actionButton.dataset.feedbackAction, getSelectedCase());
    });

    refs.refresh.addEventListener('click', () => loadCases());
    refs.type.addEventListener('change', () => loadCases());
    refs.status.addEventListener('change', () => loadCases());
    refs.search.addEventListener('input', () => {
        if (state.searchTimer) {
            clearTimeout(state.searchTimer);
        }

        state.searchTimer = window.setTimeout(() => loadCases({ silent: true }), 260);
    });

    syncFiltersFromUrl();
    loadCases();
});
