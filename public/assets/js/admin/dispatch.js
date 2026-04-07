import { callApi, confirmAction, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const refs = {
        search: document.getElementById('dispatchSearch'),
        todayBtn: document.getElementById('dispatchTodayBtn'),
        dateButton: document.getElementById('dispatchDateButton'),
        dateLabel: document.getElementById('dispatchDateLabel'),
        dateFilter: document.getElementById('dispatchDateFilter'),
        queueMeta: document.getElementById('dispatchQueueMeta'),
        queueList: document.getElementById('dispatchQueueList'),
        detailContent: document.getElementById('dispatchDetailContent'),
        candidateCount: document.getElementById('dispatchCandidateCount'),
        candidateList: document.getElementById('dispatchCandidatesList'),
        unavailableCount: document.getElementById('dispatchUnavailableCount'),
        unavailableList: document.getElementById('dispatchUnavailableList'),
    };

    const state = {
        queue: [],
        filters: {
            search: '',
            date: '',
        },
        activeBookingId: null,
        detail: null,
        isAssigning: false,
        detailToken: 0,
    };

    let searchDebounce = null;

    const escapeHtml = (value) => `${value ?? ''}`
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const formatDateValue = (date) => {
        const year = date.getFullYear();
        const month = `${date.getMonth() + 1}`.padStart(2, '0');
        const day = `${date.getDate()}`.padStart(2, '0');

        return `${year}-${month}-${day}`;
    };

    const todayValue = () => formatDateValue(new Date());

    const formatFriendlyDate = (value) => {
        if (!value) {
            return 'Chọn ngày';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return 'Chọn ngày';
        }

        return date.toLocaleDateString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    };

    const getInitials = (name) => {
        const parts = `${name || ''}`.trim().split(/\s+/).filter(Boolean);
        if (!parts.length) {
            return 'TT';
        }

        return parts.slice(0, 2).map((part) => part.charAt(0).toUpperCase()).join('');
    };

    const getAvatarUrl = (source) => {
        if (!source) return '';
        if (String(source).startsWith('http') || String(source).startsWith('/')) return source;
        return `/storage/${source}`;
    };

    const renderAvatar = (avatar, name, className = 'admin-dispatch-avatar') => {
        if (avatar) {
            return `
                <span class="${className}">
                    <img src="${escapeHtml(getAvatarUrl(avatar))}" alt="${escapeHtml(name)}">
                </span>
            `;
        }

        return `<span class="${className}">${escapeHtml(getInitials(name))}</span>`;
    };

    const buildQuery = () => {
        const params = new URLSearchParams();

        if (state.filters.search) {
            params.set('search', state.filters.search);
        }

        if (state.filters.date) {
            params.set('date', state.filters.date);
        }

        const query = params.toString();
        return query ? `?${query}` : '';
    };

    const syncFilterUi = () => {
        refs.search.value = state.filters.search || '';
        refs.dateFilter.value = state.filters.date || '';
        if (refs.dateLabel) {
            refs.dateLabel.textContent = state.filters.date ? formatFriendlyDate(state.filters.date) : 'Chọn ngày';
        }
        refs.todayBtn.classList.toggle('is-active', state.filters.date === todayValue());
        if (refs.dateButton) {
            refs.dateButton.classList.toggle('is-active', !!state.filters.date && state.filters.date !== todayValue());
        }
    };

    const renderQueueLoading = () => {
        refs.queueList.innerHTML = `
            <div class="admin-dispatch-skeleton"></div>
            <div class="admin-dispatch-skeleton"></div>
            <div class="admin-dispatch-skeleton"></div>
        `;
    };

    const renderQueue = () => {
        refs.queueMeta.textContent = state.queue.length
            ? `${state.queue.length} đơn hàng cần xử lý`
            : 'Không có đơn nào trong hàng chờ hiện tại';

        if (!state.queue.length) {
            refs.queueList.innerHTML = `
                <div class="admin-dispatch-empty">
                    <h2 class="admin-dispatch-empty__title">Hàng chờ đang trống</h2>
                    <p class="admin-dispatch-empty__copy">
                        Thử bộ lọc khác hoặc đợi thêm đơn mới để hệ thống hiển thị hàng chờ.
                    </p>
                </div>
            `;
            return;
        }

        refs.queueList.innerHTML = state.queue.map((item) => `
            <button
                type="button"
                class="admin-dispatch-queue-item ${item.id === state.activeBookingId ? 'is-active' : ''}"
                data-booking-id="${escapeHtml(item.id)}"
            >
                <div class="admin-dispatch-queue-item__layout">
                    ${renderAvatar(item.customer_avatar, item.customer_name, 'admin-dispatch-queue-item__avatar')}
                    <div class="admin-dispatch-queue-item__content">
                        <div class="admin-dispatch-queue-item__top">
                            <span class="admin-dispatch-code">Mã: ${escapeHtml(item.code)}</span>
                            ${(item.urgency_tone === 'danger' || item.urgency_tone === 'warning')
                                ? `<span class="admin-dispatch-badge admin-dispatch-badge--${item.urgency_tone === 'danger' ? 'danger' : 'primary'}">${escapeHtml(item.urgency_label)}</span>`
                                : ''}
                        </div>

                        <h3 class="admin-dispatch-queue-item__name">${escapeHtml(item.customer_name)}</h3>

                        <div class="admin-dispatch-line">
                            <i class="fas fa-snowflake"></i>
                            <span>${escapeHtml(item.service_label)}</span>
                        </div>

                        <div class="admin-dispatch-subline">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${escapeHtml(item.area_label || item.address)}</span>
                        </div>

                        ${item.booking_date ? `
                            <div class="admin-dispatch-subline">
                                <i class="far fa-clock"></i>
                                <span>${escapeHtml(item.schedule_label)}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </button>
        `).join('');
    };

    const renderDetailLoading = () => {
        refs.detailContent.innerHTML = `
            <div class="admin-dispatch-skeleton"></div>
        `;
    };

    const renderDetail = () => {
        const booking = state.detail?.booking;
        if (!booking) {
            refs.detailContent.innerHTML = `
                <div class="admin-dispatch-empty">
                    <h2 class="admin-dispatch-empty__title">Chọn một đơn để xem bối cảnh</h2>
                    <p class="admin-dispatch-empty__copy">Thông tin khách hàng và khung giờ sẽ hiển thị tại đây.</p>
                </div>
            `;
            return;
        }

        const customerAvatar = renderAvatar(booking.customer?.avatar || '', booking.customer?.name || 'Khách hàng');
        const activeQueueItem = state.queue.find((item) => item.id === state.activeBookingId);
        const requestedTime = booking.time_slot
            ? booking.time_slot.split('-')[0]
            : (booking.schedule_label || '');
        const serviceBadgeList = (booking.service_labels || []).map((label) => `
            <span class="admin-dispatch-chip">${escapeHtml(label)}</span>
        `).join('');

        refs.detailContent.innerHTML = `
            <article class="admin-dispatch-card">
                <div class="admin-dispatch-card__left">
                    ${customerAvatar}
                    <div class="min-w-0">
                        <div class="admin-dispatch-card__row">
                            <h2 class="admin-dispatch-card__title">${escapeHtml(booking.customer?.name || 'Khách hàng')}</h2>
                            <span class="admin-dispatch-badge admin-dispatch-badge--primary">${escapeHtml(activeQueueItem?.urgency_label || 'Chờ xử lý')}</span>
                        </div>

                        <div class="admin-dispatch-card__copy">
                            <div class="admin-dispatch-line">
                                <i class="fas fa-snowflake"></i>
                                <span>Dịch vụ: ${escapeHtml(booking.service_label)}</span>
                            </div>
                            <div class="admin-dispatch-line">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>${escapeHtml(booking.address)}</span>
                            </div>
                            <div class="admin-dispatch-chip-list">${serviceBadgeList}</div>
                        </div>
                    </div>
                </div>

                <div class="admin-dispatch-card__right">
                    <div class="admin-dispatch-card__eyebrow">Thời gian yêu cầu</div>
                    <div class="admin-dispatch-card__time">${escapeHtml(requestedTime)}</div>
                </div>
            </article>
        `;
    };

    const renderEmptyWorkers = (target, title, copy) => {
        target.innerHTML = `
            <div class="admin-dispatch-empty">
                <h3 class="admin-dispatch-empty__title">${escapeHtml(title)}</h3>
                <p class="admin-dispatch-empty__copy">${escapeHtml(copy)}</p>
            </div>
        `;
    };

    const buildMetaLine = (icon, text) => `
        <span class="admin-dispatch-worker__meta">
            <i class="${icon}"></i>
            <span>${escapeHtml(text)}</span>
        </span>
    `;

    const renderAvailableWorkers = () => {
        const workers = state.detail?.candidates || [];
        refs.candidateCount.textContent = `${workers.length}`;

        if (!workers.length) {
            renderEmptyWorkers(
                refs.candidateList,
                'Không có thợ sẵn sàng',
                'Hệ thống không tìm thấy thợ vừa đúng dịch vụ vừa trống khung giờ này.'
            );
            return;
        }

        refs.candidateList.innerHTML = workers.map((worker) => {
            const chips = (worker.services || []).slice(0, 3).map((service) => `
                <span class="admin-dispatch-chip">${escapeHtml(service)}</span>
            `).join('');
            const extraServiceCount = Math.max(0, (worker.services || []).length - 3);
            const badges = (worker.badges || []).map((badge) => `
                <span class="admin-dispatch-badge admin-dispatch-badge--success">${escapeHtml(badge)}</span>
            `).join('');
            const availabilityText = worker.same_day_booking_count > 0
                ? 'Không trùng slot yêu cầu'
                : 'Trống: Tự do';
            const distanceText = worker.distance_km !== null && worker.distance_km !== undefined
                ? `${worker.distance_km} km`
                : 'Khoảng cách chưa rõ';
            const workloadText = `${worker.active_booking_count || 0} đơn đã nhận`;

            return `
                <article class="admin-dispatch-worker">
                    <span class="admin-dispatch-worker__badge-corner">
                        ${worker.is_recommended ? 'Đề xuất' : 'Sẵn sàng'}
                    </span>
                    <div class="admin-dispatch-worker__body">
                        <div class="admin-dispatch-worker__top">
                            <div class="admin-dispatch-worker__identity">
                                ${renderAvatar(worker.avatar, worker.name, 'admin-dispatch-worker__avatar')}

                                <div class="min-w-0">
                                    <div class="admin-dispatch-worker__top">
                                        <h3 class="admin-dispatch-worker__name">${escapeHtml(worker.name)}</h3>
                                    </div>

                                    <div class="admin-dispatch-chip-list">
                                        ${chips}
                                        ${extraServiceCount > 0 ? `<span class="admin-dispatch-chip">+${extraServiceCount}</span>` : ''}
                                    </div>

                                    ${badges ? `<div class="admin-dispatch-chip-list mt-2">${badges}</div>` : ''}
                                </div>
                            </div>

                            <button
                                type="button"
                                class="admin-dispatch-btn"
                                data-assign-worker="${escapeHtml(worker.id)}"
                                ${state.isAssigning ? 'disabled' : ''}
                            >
                                Phân công
                            </button>
                        </div>

                        <div class="admin-dispatch-worker__foot">
                            <div class="admin-dispatch-worker__meta-row">
                                ${buildMetaLine('far fa-clock', availabilityText)}
                                ${buildMetaLine('fas fa-map-marker-alt', distanceText)}
                                ${buildMetaLine('far fa-check-circle', workloadText)}
                            </div>

                            <div class="admin-dispatch-rating">
                                <i class="fas fa-star"></i>
                                <span>${escapeHtml(worker.rating_avg || '--')}</span>
                            </div>
                        </div>
                    </div>
                </article>
            `;
        }).join('');
    };

    const renderUnavailableWorkers = () => {
        const workers = state.detail?.unavailable_candidates || [];
        refs.unavailableCount.textContent = `${workers.length}`;

        if (!workers.length) {
            renderEmptyWorkers(
                refs.unavailableList,
                'Không có thợ bị loại',
                'Tất cả thợ phù hợp hiện tại đều đang sẵn sàng hoặc hàng chờ chưa được chọn.'
            );
            return;
        }

        refs.unavailableList.innerHTML = workers.map((worker) => {
            const chips = (worker.services || []).slice(0, 3).map((service) => `
                <span class="admin-dispatch-chip">${escapeHtml(service)}</span>
            `).join('');
            const reasonTone = worker.availability_tone === 'danger'
                ? 'danger'
                : (worker.availability_tone === 'warning' ? 'primary' : 'muted');
            const distanceText = worker.distance_km !== null && worker.distance_km !== undefined
                ? `${worker.distance_km} km`
                : 'Khoảng cách chưa rõ';
            const scheduleText = worker.day_schedule?.length
                ? worker.day_schedule.map((slot) => slot.slot).join(', ')
                : 'Không có lịch trong ngày';

            return `
                <article class="admin-dispatch-worker is-unavailable">
                    <span class="admin-dispatch-worker__badge-corner admin-dispatch-worker__badge-corner--${reasonTone}">
                        ${escapeHtml(worker.availability_reason || 'Không khả dụng')}
                    </span>
                    <div class="admin-dispatch-worker__body">
                        <div class="admin-dispatch-worker__top">
                            <div class="admin-dispatch-worker__identity">
                                ${renderAvatar(worker.avatar, worker.name, 'admin-dispatch-worker__avatar')}

                                <div class="min-w-0">
                                    <div class="admin-dispatch-worker__top">
                                        <h3 class="admin-dispatch-worker__name">${escapeHtml(worker.name)}</h3>
                                    </div>

                                    <div class="admin-dispatch-chip-list">
                                        ${chips}
                                        ${worker.availability_reason ? `<span class="admin-dispatch-chip admin-dispatch-chip--danger">${escapeHtml(worker.availability_reason)}</span>` : ''}
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="admin-dispatch-btn" disabled>Phân công</button>
                        </div>

                        <div class="admin-dispatch-worker__foot">
                            <div class="admin-dispatch-worker__meta-row">
                                ${buildMetaLine('far fa-clock', scheduleText)}
                                ${buildMetaLine('fas fa-map-marker-alt', distanceText)}
                                ${buildMetaLine('far fa-check-circle', `${worker.active_booking_count || 0} đơn đã nhận`)}
                            </div>

                            <div class="admin-dispatch-rating">
                                <i class="fas fa-star"></i>
                                <span>${escapeHtml(worker.rating_avg || '--')}</span>
                            </div>
                        </div>
                    </div>
                </article>
            `;
        }).join('');
    };

    const renderDetailState = () => {
        renderDetail();
        renderAvailableWorkers();
        renderUnavailableWorkers();
    };

    const fetchBoard = async ({ preserveSelection = true } = {}) => {
        renderQueueLoading();
        refs.detailContent.innerHTML = `
            <div class="admin-dispatch-skeleton"></div>
        `;
        renderEmptyWorkers(refs.candidateList, 'Đang tải danh sách thợ', 'Hệ thống đang tính toán ứng viên phù hợp.');
        renderEmptyWorkers(refs.unavailableList, 'Đang tải danh sách loại trừ', 'Vui lòng chờ trong giây lát.');

        try {
            const response = await callApi(`/admin/dispatch${buildQuery()}`);

            if (!response.ok) {
                throw new Error(response.data?.message || 'Không thể tải hàng chờ điều phối.');
            }

            const data = response.data?.data || {};
            state.queue = Array.isArray(data.queue) ? data.queue : [];
            state.filters.search = data.filters?.search || '';
            state.filters.date = data.filters?.date || '';
            state.filters.dates = data.filters?.dates || [];

            if (refs.dateFilter) {
                const currentVal = state.filters.date || '';
                let optionsHtml = '<option value="">Tất cả</option>';
                const datesSet = new Set(state.filters.dates);
                
                if (currentVal && !datesSet.has(currentVal)) {
                    optionsHtml += `<option value="${currentVal}" selected>${formatFriendlyDate(currentVal)}</option>`;
                }
                
                optionsHtml += state.filters.dates.map(date => {
                    return `<option value="${date}" ${date === currentVal ? 'selected' : ''}>${formatFriendlyDate(date)}</option>`;
                }).join('');
                
                refs.dateFilter.innerHTML = optionsHtml;
            }

            if (!state.queue.length) {
                state.activeBookingId = null;
                state.detail = null;
                syncFilterUi();
                renderQueue();
                renderDetailState();
                return;
            }

            const selectionStillExists = preserveSelection && state.queue.some((item) => item.id === state.activeBookingId);
            state.activeBookingId = selectionStillExists ? state.activeBookingId : state.queue[0].id;

            syncFilterUi();
            renderQueue();
            await fetchDetail(state.activeBookingId);
        } catch (error) {
            console.error('Dispatch board error:', error);
            state.queue = [];
            state.detail = null;
            renderQueue();
            renderDetailState();
            showToast(error.message || 'Không thể tải hàng chờ điều phối', 'error');
        }
    };

    const fetchDetail = async (bookingId) => {
        if (!bookingId) {
            state.detail = null;
            renderDetailState();
            return;
        }

        renderDetailLoading();
        renderEmptyWorkers(refs.candidateList, 'Đang tải danh sách thợ', 'Hệ thống đang tính toán ứng viên phù hợp.');
        renderEmptyWorkers(refs.unavailableList, 'Đang tải danh sách loại trừ', 'Vui lòng chờ trong giây lát.');

        const token = Date.now();
        state.detailToken = token;

        try {
            const response = await callApi(`/admin/dispatch/${bookingId}`);

            if (state.detailToken !== token) {
                return;
            }

            if (!response.ok) {
                throw new Error(response.data?.message || 'Không thể tải chi tiết đơn.');
            }

            state.detail = response.data?.data || null;
            renderDetailState();
        } catch (error) {
            console.error('Dispatch detail error:', error);
            state.detail = null;
            renderDetailState();
            showToast(error.message || 'Không thể tải chi tiết đơn', 'error');
        }
    };

    const assignWorker = async (workerId) => {
        if (!state.activeBookingId || state.isAssigning || !state.detail?.booking) {
            return;
        }

        const worker = (state.detail.candidates || []).find((item) => `${item.id}` === `${workerId}`);
        if (!worker) {
            return;
        }

        const confirmation = await confirmAction(
            'Phân công đơn cho thợ này?',
            `${state.detail.booking.code} sẽ được giao cho ${worker.name}. Hệ thống sẽ khóa và kiểm tra lại xung đột trước khi lưu.`,
            'Phân công'
        );

        if (!confirmation?.isConfirmed) {
            return;
        }

        state.isAssigning = true;
        renderAvailableWorkers();

        try {
            const response = await callApi(`/admin/dispatch/${state.activeBookingId}/assign`, 'POST', {
                worker_id: Number(workerId),
            });

            if (!response.ok) {
                throw new Error(response.data?.message || 'Không thể phân công đơn này.');
            }

            showToast(response.data?.message || 'Đã phân công thành công');
            state.detail = null;
            await fetchBoard({ preserveSelection: false });
        } catch (error) {
            console.error('Dispatch assign error:', error);
            showToast(error.message || 'Không thể phân công đơn này', 'error');
            await fetchBoard({ preserveSelection: true });
        } finally {
            state.isAssigning = false;
            renderAvailableWorkers();
        }
    };

    refs.search.addEventListener('input', (event) => {
        clearTimeout(searchDebounce);
        state.filters.search = event.target.value.trim();
        searchDebounce = setTimeout(() => fetchBoard({ preserveSelection: false }), 300);
    });

    refs.todayBtn.addEventListener('click', () => {
        state.filters.date = state.filters.date === todayValue() ? '' : todayValue();
        syncFilterUi();
        fetchBoard({ preserveSelection: false });
    });

    refs.dateFilter.addEventListener('change', (event) => {
        state.filters.date = event.target.value;
        syncFilterUi();
        fetchBoard({ preserveSelection: false });
    });

    refs.queueList.addEventListener('click', (event) => {
        const button = event.target.closest('[data-booking-id]');
        if (!button) {
            return;
        }

        const bookingId = Number(button.getAttribute('data-booking-id'));
        if (!bookingId || bookingId === state.activeBookingId) {
            return;
        }

        state.activeBookingId = bookingId;
        renderQueue();
        fetchDetail(bookingId);
    });

    refs.candidateList.addEventListener('click', (event) => {
        const button = event.target.closest('[data-assign-worker]');
        if (!button) {
            return;
        }

        assignWorker(button.getAttribute('data-assign-worker'));
    });

    syncFilterUi();
    fetchBoard({ preserveSelection: false });
});
