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

    const decisionOptions = [
        {
            value: 'refund',
            label: 'Hoàn tiền',
            hint: 'Trả lại chi phí cho khách hàng theo phạm vi được duyệt.',
        },
        {
            value: 'compensation',
            label: 'Bồi thường',
            hint: 'Bổ sung khoản bồi thường khi có tổn thất hoặc bất tiện.',
        },
        {
            value: 'free_repair',
            label: 'Sửa lại miễn phí',
            hint: 'Sắp xếp thợ xử lý lại, không thu thêm chi phí.',
        },
        {
            value: 'reject',
            label: 'Từ chối',
            hint: 'Giữ nguyên kết quả nếu khiếu nại không hợp lệ.',
        },
    ];

    const state = {
        cases: [],
        selectedId: null,
        searchTimer: null,
        actionBusy: false,
        drafts: new Map(),
    };

    const numberFormatter = new Intl.NumberFormat('vi-VN');
    const currencyFormatter = new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    });

    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');

    const formatNumber = (value) => numberFormatter.format(Number(value || 0));
    const formatCurrency = (value) => currencyFormatter.format(Number(value || 0));

    const toneClass = (tone) => {
        switch (tone) {
            case 'success':
                return 'feedback-badge--success';
            case 'warning':
                return 'feedback-badge--warning';
            case 'danger':
                return 'feedback-badge--danger';
            case 'muted':
                return 'feedback-badge--muted';
            default:
                return 'feedback-badge--info';
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

    const getCaseDraft = (item) => {
        const savedDraft = state.drafts.get(item.id) || {};

        return {
            action: savedDraft.action ?? (item.resolution_action || ''),
            note: savedDraft.note ?? (item.resolution_note || item.assignment_note || ''),
            dirty: Boolean(savedDraft.dirty),
        };
    };

    const setCaseDraft = (item, patch) => {
        const currentDraft = getCaseDraft(item);

        state.drafts.set(item.id, {
            ...currentDraft,
            ...patch,
            dirty: true,
        });
    };

    const clearCaseDraft = (caseId) => {
        state.drafts.delete(caseId);
    };

    const buildActionLinks = (item) => {
        const links = [];

        if (item.customer_url) {
            links.push(`<a class="feedback-case-link" href="${escapeHtml(item.customer_url)}">Mở hồ sơ khách</a>`);
        }

        if (item.booking_url) {
            links.push(`<a class="feedback-case-link" href="${escapeHtml(item.booking_url)}">Mở đơn liên quan</a>`);
        }

        return links.length ? links.join('') : '<span class="feedback-case-link">Không có liên kết bổ sung</span>';
    };

    const buildSummaryItem = (label, value) => `
        <div class="feedback-case-summary-item">
            <span class="feedback-case-summary-item__label">${escapeHtml(label)}</span>
            <div class="feedback-case-summary-item__value">${value}</div>
        </div>
    `;

    const buildMediaSection = (title, images = [], videos = [], emptyText = 'Chưa có tệp được gửi.') => {
        const mediaItems = [
            ...images.map((src, index) => ({
                type: 'image',
                src,
                caption: `Ảnh ${index + 1}`,
            })),
            ...videos.map((src, index) => ({
                type: 'video',
                src,
                caption: `Video ${index + 1}`,
            })),
        ];

        const content = mediaItems.length
            ? `<div class="feedback-case-media-list">
                ${mediaItems.map((item) => {
                    if (item.type === 'video') {
                        return `
                            <figure class="feedback-case-media-thumb">
                                <video controls preload="metadata" playsinline src="${escapeHtml(item.src)}"></video>
                                <figcaption>
                                    ${escapeHtml(item.caption)}
                                    <br>
                                    <a href="${escapeHtml(item.src)}" target="_blank" rel="noopener">Mở video gốc</a>
                                </figcaption>
                            </figure>
                        `;
                    }

                    return `
                        <a class="feedback-case-media-thumb" href="${escapeHtml(item.src)}" target="_blank" rel="noopener">
                            <img src="${escapeHtml(item.src)}" alt="${escapeHtml(item.caption)}" loading="lazy">
                            <figcaption>${escapeHtml(item.caption)} · Bấm để mở tệp gốc</figcaption>
                        </a>
                    `;
                }).join('')}
            </div>`
            : `<div class="feedback-case-media-empty">${escapeHtml(emptyText)}</div>`;

        return `
            <div class="feedback-case-media-section">
                <span class="feedback-case-media-section__title">${escapeHtml(title)}</span>
                ${content}
            </div>
        `;
    };

    const buildFooterActions = (item, requireDecision = false) => {
        const actionDisabled = state.actionBusy ? 'disabled' : '';

        if (item.status === 'resolved') {
            return `
                <div class="feedback-case-footer">
                    <div class="feedback-case-footer__actions">
                        <button type="button" class="feedback-case-button feedback-case-button--ghost" data-feedback-action="claim" ${actionDisabled}>
                            Mở lại xử lý
                        </button>
                    </div>
                </div>
            `;
        }

        return `
            <div class="feedback-case-footer">
                <div class="feedback-case-footer__actions">
                    <button type="button" class="feedback-case-button feedback-case-button--ghost" data-feedback-action="save-draft" ${actionDisabled}>
                        Lưu nháp
                    </button>
                    <button
                        type="button"
                        class="feedback-case-button feedback-case-button--primary"
                        data-feedback-action="resolve-inline"
                        data-require-decision="${requireDecision ? '1' : '0'}"
                        ${actionDisabled}
                    >
                        Cập nhật trạng thái
                    </button>
                </div>
            </div>
        `;
    };

    const renderStats = (summary) => {
        const cards = [
            ['Tổng case', formatNumber(summary?.total_cases || 0), 'Tất cả feedback đang được hiển thị'],
            ['Khiếu nại', formatNumber(summary?.complaint_cases || 0), 'Case cần đối soát bằng chứng và quyết định xử lý'],
            ['Đánh giá thấp', formatNumber(summary?.review_cases || 0), 'Review cần admin xem lại chất lượng dịch vụ'],
            ['Hủy đơn', formatNumber(summary?.cancellation_cases || 0), 'Đơn hủy có lý do từ khách hàng hoặc hệ thống'],
            ['Đang xử lý', formatNumber(summary?.in_progress_cases || 0), 'Case đã được admin tiếp nhận'],
            ['Đã xử lý', formatNumber(summary?.resolved_cases || 0), 'Case đã được đóng và có kết quả cuối cùng'],
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
            refs.caption.textContent = 'Không tìm thấy case phù hợp với bộ lọc hiện tại.';
            refs.list.innerHTML = '<div class="feedback-admin-empty">Không có case nào khớp với bộ lọc đang áp dụng.</div>';
            renderPreview(null);
            return;
        }

        refs.caption.textContent = `${formatNumber(state.cases.length)} case trong kết quả hiện tại.`;

        refs.list.innerHTML = state.cases.map((item) => `
            <article class="feedback-admin-item ${item.id === state.selectedId ? 'is-selected' : ''}" data-case-id="${escapeHtml(item.id)}">
                <div class="feedback-admin-item__top">
                    <div>
                        <div class="feedback-admin-item__eyebrow">${escapeHtml(item.booking_code || '--')} · ${escapeHtml(item.type_label || 'Case')}</div>
                        <div class="feedback-admin-item__title">${escapeHtml(item.customer_name || 'Khách hàng')}</div>
                    </div>

                    <div class="feedback-admin-badges">
                        <span class="feedback-badge ${toneClass(item.priority_tone)}">${escapeHtml(item.priority_label || 'Thường')}</span>
                        <span class="feedback-badge ${toneClass(item.status_tone)}">${escapeHtml(item.status_label || 'Mới')}</span>
                    </div>
                </div>

                <div class="feedback-admin-item__meta">
                    ${escapeHtml(item.service_label || 'Chưa rõ dịch vụ')}<br>
                    ${escapeHtml(item.worker_name || 'Chưa gán thợ')}<br>
                    ${escapeHtml(item.created_label || '--')}
                </div>

                <div class="feedback-admin-item__summary">${escapeHtml(item.summary || 'Không có mô tả.')}</div>
            </article>
        `).join('');
    };

    const buildComplaintDetail = (item) => {
        const draft = getCaseDraft(item);
        const actionDisabled = state.actionBusy ? 'disabled' : '';
        const selectedOption = decisionOptions.find((option) => option.value === draft.action);
        const beforeMediaCount = (item.booking_before_images?.length || 0) + (item.booking_before_videos?.length || 0);
        const decisionNote = item.status === 'resolved' && item.resolution_note
            ? `<div class="feedback-case-note feedback-case-note--success">
                Kết quả đã được chốt lúc ${escapeHtml(item.resolved_label || '--')}.
                ${selectedOption ? `Hướng xử lý: <strong>${escapeHtml(selectedOption.label)}</strong>.` : ''}
                <br>
                ${escapeHtml(item.resolution_note)}
            </div>`
            : item.assignment_note
                ? `<div class="feedback-case-note feedback-case-note--info">Bản nháp gần nhất: ${escapeHtml(item.assignment_note)}</div>`
                : '';

        return `
            <div class="feedback-case-layout">
                <header class="feedback-case-header">
                    <div class="feedback-case-header__copy">
                        <span class="feedback-case-kicker">Chi tiết khiếu nại</span>
                        <h2 class="feedback-case-title">${escapeHtml(item.booking_code || 'Case')}</h2>
                        <p class="feedback-case-subtitle">
                            ${escapeHtml(item.service_label || 'Chưa rõ dịch vụ')} · ${escapeHtml(item.customer_name || 'Khách hàng')}
                        </p>
                    </div>

                    <div class="feedback-case-header__side">
                        <span class="feedback-badge ${toneClass(item.status_tone)}">${escapeHtml(item.status_label || 'Mới')}</span>
                        <div class="feedback-case-header__meta">
                            Tạo lúc ${escapeHtml(item.created_label || '--')}<br>
                            Admin: ${escapeHtml(item.assigned_admin_name || 'Chưa tiếp nhận')}
                        </div>
                        <div class="feedback-case-links">${buildActionLinks(item)}</div>
                    </div>
                </header>

                <section class="feedback-case-card feedback-case-card--issue">
                    <span class="feedback-case-card__eyebrow">Nội dung khiếu nại</span>
                    <h3 class="feedback-case-card__headline">${escapeHtml(item.complaint_reason_label || item.summary || 'Khiếu nại khác')}</h3>
                    <p class="feedback-case-card__copy">
                        ${escapeHtml(item.complaint_note || item.content || 'Khách hàng gửi khiếu nại cho đơn này.')}
                    </p>

                    <div class="feedback-case-media-grid">
                        ${buildMediaSection(
                            'Bằng chứng khách hàng cung cấp',
                            item.complaint_images || [],
                            item.complaint_video ? [item.complaint_video] : [],
                            'Khách hàng chưa gửi ảnh hoặc video kèm theo.'
                        )}
                        ${buildMediaSection(
                            'Kết quả thợ bàn giao',
                            item.booking_after_images || [],
                            item.booking_after_videos || [],
                            'Chưa có ảnh hoặc video kết quả sau sửa chữa.'
                        )}
                    </div>
                </section>

                <section class="feedback-case-card">
                    <span class="feedback-case-card__eyebrow">Thông tin đơn hàng liên quan</span>
                    <h3 class="feedback-case-card__headline">Tổng hợp booking và bên liên quan</h3>

                    <div class="feedback-case-summary-grid">
                        ${buildSummaryItem('Khách hàng', `
                            ${escapeHtml(item.customer_name || 'Khách hàng')}
                            ${item.customer_phone ? `<br><span class="feedback-case-summary-item__value--muted">${escapeHtml(item.customer_phone)}</span>` : ''}
                        `)}
                        ${buildSummaryItem('Thợ phụ trách', `
                            ${escapeHtml(item.worker_name || 'Chưa gán thợ')}
                            ${item.worker_phone ? `<br><span class="feedback-case-summary-item__value--muted">${escapeHtml(item.worker_phone)}</span>` : ''}
                        `)}
                        ${buildSummaryItem('Dịch vụ', escapeHtml(item.service_label || 'Chưa rõ dịch vụ'))}
                        ${buildSummaryItem('Địa điểm xử lý', escapeHtml(item.location_label || 'Chưa cập nhật'))}
                        ${buildSummaryItem('Trạng thái booking', escapeHtml(item.booking_code || '--'))}
                        ${buildSummaryItem('Dữ liệu trước sửa', beforeMediaCount > 0 ? `${escapeHtml(formatNumber(beforeMediaCount))} tệp đã lưu` : 'Chưa có tệp trước sửa')}
                    </div>

                    <div class="feedback-case-total">
                        <span class="feedback-case-total__label">Tổng thanh toán hiện tại của đơn</span>
                        <span class="feedback-case-total__value">${escapeHtml(formatCurrency(item.booking_total || 0))}</span>
                    </div>
                </section>

                <section class="feedback-case-card">
                    <span class="feedback-case-card__eyebrow">Quyết định xử lý</span>
                    <h3 class="feedback-case-card__headline">Chọn hướng xử lý và ghi chú nội bộ</h3>

                    <div class="feedback-case-decision-grid">
                        ${decisionOptions.map((option) => `
                            <button
                                type="button"
                                class="feedback-case-decision ${draft.action === option.value ? 'is-active' : ''}"
                                data-resolution-action="${escapeHtml(option.value)}"
                                ${actionDisabled}
                            >
                                <span class="feedback-case-decision__title">${escapeHtml(option.label)}</span>
                                <span class="feedback-case-decision__meta">${escapeHtml(option.hint)}</span>
                            </button>
                        `).join('')}
                    </div>

                    <label class="feedback-case-field">
                        <span class="feedback-case-field__label">Ghi chú xử lý</span>
                        <textarea class="feedback-case-textarea" data-resolution-note ${actionDisabled} placeholder="Nhập cách đối soát, kết quả làm việc với khách và hướng xử lý dự kiến...">${escapeHtml(draft.note)}</textarea>
                    </label>

                    ${decisionNote}
                    ${buildFooterActions(item, true)}
                </section>
            </div>
        `;
    };

    const buildGenericDetail = (item) => {
        const draft = getCaseDraft(item);
        const actionDisabled = state.actionBusy ? 'disabled' : '';
        const resolutionBlock = item.status === 'resolved' && item.resolution_note
            ? `<div class="feedback-case-note feedback-case-note--success">
                Case đã được chốt lúc ${escapeHtml(item.resolved_label || '--')}.<br>
                ${escapeHtml(item.resolution_note)}
            </div>`
            : item.assignment_note
                ? `<div class="feedback-case-note feedback-case-note--info">Bản nháp gần nhất: ${escapeHtml(item.assignment_note)}</div>`
                : '';

        return `
            <div class="feedback-case-layout">
                <header class="feedback-case-header">
                    <div class="feedback-case-header__copy">
                        <span class="feedback-case-kicker">${escapeHtml(item.type_label || 'Case')}</span>
                        <h2 class="feedback-case-title">${escapeHtml(item.booking_code || 'Case')}</h2>
                        <p class="feedback-case-subtitle">${escapeHtml(item.summary || item.content || 'Không có mô tả chi tiết.')}</p>
                    </div>

                    <div class="feedback-case-header__side">
                        <span class="feedback-badge ${toneClass(item.status_tone)}">${escapeHtml(item.status_label || 'Mới')}</span>
                        <div class="feedback-case-header__meta">
                            Tạo lúc ${escapeHtml(item.created_label || '--')}<br>
                            Admin: ${escapeHtml(item.assigned_admin_name || 'Chưa tiếp nhận')}
                        </div>
                        <div class="feedback-case-links">${buildActionLinks(item)}</div>
                    </div>
                </header>

                <section class="feedback-case-card">
                    <span class="feedback-case-card__eyebrow">Nội dung case</span>
                    <h3 class="feedback-case-card__headline">${escapeHtml(item.type_label || 'Case')}</h3>
                    <p class="feedback-case-card__copy">${escapeHtml(item.content || 'Không có nội dung chi tiết.')}</p>
                </section>

                <section class="feedback-case-card">
                    <span class="feedback-case-card__eyebrow">Thông tin liên quan</span>
                    <h3 class="feedback-case-card__headline">Khách hàng, đơn hàng và người phụ trách</h3>

                    <div class="feedback-case-summary-grid">
                        ${buildSummaryItem('Khách hàng', `
                            ${escapeHtml(item.customer_name || 'Khách hàng')}
                            ${item.customer_phone ? `<br><span class="feedback-case-summary-item__value--muted">${escapeHtml(item.customer_phone)}</span>` : ''}
                        `)}
                        ${buildSummaryItem('Thợ phụ trách', escapeHtml(item.worker_name || 'Chưa gán thợ'))}
                        ${buildSummaryItem('Dịch vụ', escapeHtml(item.service_label || 'Chưa rõ dịch vụ'))}
                        ${buildSummaryItem('Địa điểm', escapeHtml(item.location_label || 'Chưa cập nhật'))}
                    </div>
                </section>

                <section class="feedback-case-card">
                    <span class="feedback-case-card__eyebrow">Cập nhật xử lý</span>
                    <h3 class="feedback-case-card__headline">Ghi chú điều phối và kết quả đối soát</h3>

                    <label class="feedback-case-field">
                        <span class="feedback-case-field__label">Ghi chú xử lý</span>
                        <textarea class="feedback-case-textarea" data-resolution-note ${actionDisabled} placeholder="Nhập cách xử lý, thông tin đã làm việc và ghi chú điều phối...">${escapeHtml(draft.note)}</textarea>
                    </label>

                    ${resolutionBlock}
                    ${buildFooterActions(item, false)}
                </section>
            </div>
        `;
    };

    const renderPreview = (item) => {
        if (!item) {
            refs.preview.innerHTML = `
                <div class="feedback-admin-empty">
                    Chưa chọn case.<br>
                    Chọn một feedback ở cột bên trái để xem chi tiết và cập nhật xử lý.
                </div>
            `;
            return;
        }

        refs.preview.innerHTML = item.type === 'customer_complaint'
            ? buildComplaintDetail(item)
            : buildGenericDetail(item);
    };

    const selectCase = (caseId) => {
        state.selectedId = caseId;
        renderList();
        renderPreview(getSelectedCase());
    };

    const runCaseAction = async (action, item, requireDecision = false) => {
        if (!item || state.actionBusy) {
            return;
        }

        const draft = getCaseDraft(item);
        const note = draft.note.trim();
        const resolutionAction = draft.action || null;

        try {
            state.actionBusy = true;
            renderPreview(item);

            if (action === 'save-draft') {
                const response = await callApi(`/admin/customer-feedback/${encodeURIComponent(item.id)}/claim`, 'POST', {
                    assignment_note: note || null,
                    resolution_action: resolutionAction,
                });

                if (!response?.ok) {
                    throw new Error(response?.data?.message || 'Không thể lưu nháp case');
                }

                clearCaseDraft(item.id);
                showToast('Đã lưu nháp hướng xử lý');
            }

            if (action === 'claim') {
                const confirmation = await confirmAction(
                    'Mở lại case này?',
                    'Case sẽ được đưa về trạng thái đang xử lý để cập nhật lại hướng xử lý.',
                    'Mở lại xử lý'
                );

                if (!confirmation?.isConfirmed) {
                    return;
                }

                const response = await callApi(`/admin/customer-feedback/${encodeURIComponent(item.id)}/claim`, 'POST', {
                    assignment_note: note || null,
                    resolution_action: resolutionAction,
                });

                if (!response?.ok) {
                    throw new Error(response?.data?.message || 'Không thể mở lại case');
                }

                clearCaseDraft(item.id);
                showToast('Case đã được mở lại xử lý');
            }

            if (action === 'resolve-inline') {
                if (requireDecision && !resolutionAction) {
                    showToast('Vui lòng chọn hướng xử lý trước khi cập nhật trạng thái', 'error');
                    return;
                }

                const confirmation = await confirmAction(
                    'Chốt xử lý case này?',
                    'Case sẽ được đánh dấu đã xử lý và đóng trong dashboard feedback.',
                    'Cập nhật'
                );

                if (!confirmation?.isConfirmed) {
                    return;
                }

                const response = await callApi(`/admin/customer-feedback/${encodeURIComponent(item.id)}/resolve`, 'POST', {
                    resolution_note: note || null,
                    resolution_action: resolutionAction,
                });

                if (!response?.ok) {
                    throw new Error(response?.data?.message || 'Không thể cập nhật case');
                }

                clearCaseDraft(item.id);
                showToast('Case đã được đánh dấu đã xử lý');
            }

            await loadCases({ silent: true });
        } catch (error) {
            console.error('Customer feedback action failed:', error);
            showToast(error.message || 'Không thể xử lý case', 'error');
        } finally {
            state.actionBusy = false;
            renderPreview(getSelectedCase());
        }
    };

    const loadCases = async ({ silent = false } = {}) => {
        if (!silent) {
            refs.caption.textContent = 'Đang tải danh sách case...';
            refs.list.innerHTML = '<div class="feedback-admin-empty">Đang tải danh sách case...</div>';
            refs.preview.innerHTML = '<div class="feedback-admin-empty">Đang tải workspace chi tiết...</div>';
        }

        try {
            syncUrl();
            const response = await callApi(`/admin/customer-feedback${buildQuery()}`, 'GET');

            if (!response?.ok) {
                throw new Error(response?.data?.message || 'Không thể tải feedback khách hàng');
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
            refs.caption.textContent = 'Không thể tải dữ liệu.';
            refs.list.innerHTML = '<div class="feedback-admin-empty">Không thể tải danh sách case.</div>';
            refs.preview.innerHTML = '<div class="feedback-admin-empty">Không thể tải chi tiết case.</div>';
            showToast(error.message || 'Không thể tải feedback khách hàng', 'error');
        }
    };

    refs.list.addEventListener('click', (event) => {
        const item = event.target.closest('[data-case-id]');

        if (item) {
            selectCase(item.dataset.caseId);
        }
    });

    refs.preview.addEventListener('click', (event) => {
        const actionToggle = event.target.closest('[data-resolution-action]');

        if (actionToggle) {
            const item = getSelectedCase();

            if (!item || state.actionBusy) {
                return;
            }

            event.preventDefault();
            setCaseDraft(item, { action: actionToggle.dataset.resolutionAction });
            renderPreview(item);
            return;
        }

        const actionButton = event.target.closest('[data-feedback-action]');

        if (!actionButton) {
            return;
        }

        event.preventDefault();
        runCaseAction(
            actionButton.dataset.feedbackAction,
            getSelectedCase(),
            actionButton.dataset.requireDecision === '1'
        );
    });

    refs.preview.addEventListener('input', (event) => {
        const noteField = event.target.closest('[data-resolution-note]');

        if (!noteField) {
            return;
        }

        const item = getSelectedCase();

        if (!item) {
            return;
        }

        setCaseDraft(item, { note: noteField.value });
    });

    refs.refresh.addEventListener('click', () => loadCases());
    refs.type.addEventListener('change', () => loadCases());
    refs.status.addEventListener('change', () => loadCases());
    refs.search.addEventListener('input', () => {
        if (state.searchTimer) {
            clearTimeout(state.searchTimer);
        }

        state.searchTimer = window.setTimeout(() => loadCases({ silent: true }), 280);
    });

    syncFiltersFromUrl();
    loadCases();
});
