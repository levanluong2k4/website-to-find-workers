import { callApi, confirmAction, requireRole, showToast } from '../api.js';

const page = document.getElementById('workerScheduleDetailPage');
const query = new URLSearchParams(window.location.search);

const refs = {};
const state = {
    workerId: Number(page?.dataset.workerId || 0),
    anchorDate: normalizeIsoDate(query.get('date')) || toIsoDate(new Date()),
    data: null,
    worker: null,
    day: null,
    queue: [],
    queueDates: [],
    selectedQueueId: null,
    isLoading: false,
    isAssigning: false,
};

document.addEventListener('DOMContentLoaded', () => {
    if (!page || !state.workerId) {
        return;
    }

    requireRole('admin');
    cacheElements();
    bindEvents();
    renderLoadingState();
    loadPage({ preserveSelection: false });
});

function cacheElements() {
    refs.backLink = document.getElementById('workerScheduleBackLink');
    refs.syncStatus = document.getElementById('wsdSyncStatus');
    refs.refreshButton = document.getElementById('wsdRefreshButton');
    refs.profileHeader = document.getElementById('wsdProfileHeader');
    refs.prevDateButton = document.getElementById('wsdPrevDate');
    refs.nextDateButton = document.getElementById('wsdNextDate');
    refs.todayButton = document.getElementById('wsdTodayButton');
    refs.dateLabel = document.getElementById('wsdDateLabel');
    refs.timeline = document.getElementById('wsdTimeline');
    refs.queueMeta = document.getElementById('wsdQueueMeta');
    refs.queueCount = document.getElementById('wsdQueueCount');
    refs.queueList = document.getElementById('wsdQueueList');
}

function bindEvents() {
    refs.refreshButton?.addEventListener('click', () => loadPage({ preserveSelection: true }));
    refs.prevDateButton?.addEventListener('click', () => moveDateSelection(-1));
    refs.nextDateButton?.addEventListener('click', () => moveDateSelection(1));

    refs.todayButton?.addEventListener('click', () => {
        const nextDate = resolvePreferredDateOption(getAvailableDateOptions(), toIsoDate(new Date()));
        if (!nextDate || nextDate === state.anchorDate) {
            return;
        }

        state.anchorDate = nextDate;
        loadPage({ preserveSelection: false });
    });

    refs.queueList?.addEventListener('click', (event) => {
        const assignButton = event.target.closest('[data-assign-booking]');
        if (assignButton) {
            event.preventDefault();
            assignBookingToWorker(Number(assignButton.getAttribute('data-assign-booking') || 0));
            return;
        }

        const queueItem = event.target.closest('[data-select-queue]');
        if (!queueItem) {
            return;
        }

        const bookingId = Number(queueItem.getAttribute('data-select-queue') || 0);
        if (!bookingId || bookingId === state.selectedQueueId) {
            return;
        }

        state.selectedQueueId = bookingId;
        renderPage();
    });

    refs.timeline?.addEventListener('click', (event) => {
        const assignButton = event.target.closest('[data-assign-booking]');
        if (!assignButton) {
            return;
        }

        event.preventDefault();
        assignBookingToWorker(Number(assignButton.getAttribute('data-assign-booking') || 0));
    });
}

async function loadPage({ preserveSelection = true } = {}) {
    if (state.isLoading) {
        return;
    }

    state.isLoading = true;
    setLoadingState(true);

    try {
        const overviewResponse = await callApi(
            `/admin/worker-schedules/overview?view=day&preserve_date=1&date=${encodeURIComponent(state.anchorDate)}`,
            'GET'
        );
        const overviewData = ensureSuccess(overviewResponse, 'Không thể tải lịch làm việc của thợ.');
        state.data = overviewData;
        state.anchorDate = overviewData.meta?.anchor_date || state.anchorDate;

        const worker = (Array.isArray(overviewData.workers) ? overviewData.workers : [])
            .find((item) => Number(item.id) === state.workerId);

        if (!worker) {
            throw new Error('Không tìm thấy thợ kỹ thuật cần xem chi tiết.');
        }

        state.worker = worker;
        state.day = Array.isArray(worker.days) ? (worker.days[0] || null) : null;

        const queueResponse = await callApi(
            `/admin/dispatch?date=${encodeURIComponent(state.anchorDate)}`,
            'GET'
        );
        const queueData = ensureSuccess(queueResponse, 'Không thể tải hàng chờ phân công.');
        state.queueDates = Array.isArray(queueData.filters?.dates)
            ? queueData.filters.dates.map((value) => normalizeIsoDate(value)).filter(Boolean)
            : [];
        state.queue = decorateQueueItems(Array.isArray(queueData.queue) ? queueData.queue : []);
        syncSelectedQueue(preserveSelection);
        syncUrlState();
        renderPage();
        renderSyncStatus(`Đã đồng bộ lúc ${overviewData.meta?.updated_at || '--:--'}`, 'success');
    } catch (error) {
        console.error('Worker schedule detail error:', error);
        renderErrorState(error.message || 'Không thể tải trang chi tiết lịch thợ.');
        renderSyncStatus('Lỗi đồng bộ trang chi tiết', 'error');
    } finally {
        state.isLoading = false;
        setLoadingState(false);
    }
}

function ensureSuccess(response, fallbackMessage) {
    if (!response?.ok) {
        throw new Error(response?.data?.message || fallbackMessage);
    }

    return response.data?.data || {};
}

function setLoadingState(isLoading) {
    refs.refreshButton?.toggleAttribute('disabled', isLoading);
    refs.prevDateButton?.toggleAttribute('disabled', isLoading);
    refs.nextDateButton?.toggleAttribute('disabled', isLoading);
    refs.todayButton?.toggleAttribute('disabled', isLoading);

    if (isLoading) {
        renderSyncStatus('Đang đồng bộ dữ liệu...', 'loading');
    }
}

function renderLoadingState() {
    const loadingHtml = `
        <div class="wsd-loading-state">
            <div class="wsd-loading-spinner"></div>
            <strong>Đang tải dữ liệu</strong>
            <span>Vui lòng chờ trong giây lát.</span>
        </div>
    `;

    if (refs.profileHeader) {
        refs.profileHeader.innerHTML = loadingHtml;
    }

    if (refs.timeline) {
        refs.timeline.innerHTML = loadingHtml;
    }

    if (refs.queueList) {
        refs.queueList.innerHTML = loadingHtml;
    }
}

function renderErrorState(message) {
    const errorHtml = `
        <div class="wsd-empty-state">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <strong>Không tải được dữ liệu</strong>
            <span>${escapeHtml(message)}</span>
        </div>
    `;

    if (refs.profileHeader) {
        refs.profileHeader.innerHTML = errorHtml;
    }

    if (refs.timeline) {
        refs.timeline.innerHTML = errorHtml;
    }

    if (refs.queueList) {
        refs.queueList.innerHTML = errorHtml;
    }

    if (refs.queueMeta) {
        refs.queueMeta.textContent = 'Không thể tải hàng chờ.';
    }

    if (refs.queueCount) {
        refs.queueCount.textContent = '0';
    }
}

function renderPage() {
    if (!state.worker || !state.day) {
        renderErrorState('Không tìm thấy dữ liệu lịch làm việc cho thợ đã chọn.');
        return;
    }

    syncBackLink();
    renderProfileHeader();
    renderDateNavigator();
    renderTimeline();
    renderQueuePanel();
}

function renderProfileHeader() {
    if (!refs.profileHeader || !state.worker) {
        return;
    }

    const worker = state.worker;
    const chips = Array.isArray(worker.services) && worker.services.length
        ? worker.services.slice(0, 4).map((label) => `
            <span class="wsd-service-chip">
                <i class="fa-solid fa-bolt"></i>
                <span>${escapeHtml(label)}</span>
            </span>
        `).join('')
        : `<span class="wsd-service-chip"><span>Chưa gán nhóm dịch vụ</span></span>`;

    refs.profileHeader.innerHTML = `
        <div class="wsd-profile">
            <div class="wsd-profile__avatar-wrap">
                <img
                    src="${escapeHtml(resolveAvatarUrl(worker.avatar))}"
                    alt="${escapeHtml(worker.name || 'Kỹ thuật viên')}"
                    class="wsd-profile__avatar"
                    onerror="this.onerror=null;this.src='/assets/images/worker2.png';"
                >
                <span class="wsd-profile__status-dot is-${escapeHtml(worker.current_status?.key || 'offline')}"></span>
            </div>

            <div class="wsd-profile__copy">
                <h1 class="wsd-profile__name">${escapeHtml(worker.name || 'Kỹ thuật viên')}</h1>
                <div class="wsd-profile__rating">
                    <i class="fa-solid fa-star"></i>
                    <span>${escapeHtml(resolveRatingLabel(worker))}</span>
                </div>
                <div class="wsd-profile__chips">${chips}</div>
            </div>
        </div>
    `;
}

function renderDateNavigator() {
    const availableDates = getAvailableDateOptions();
    const currentIndex = availableDates.findIndex((option) => option.date === state.anchorDate);
    const selectedDate = getSelectedDateOption();

    if (refs.dateLabel) {
        refs.dateLabel.innerHTML = `
            <i class="fa-regular fa-calendar"></i>
            <span>${escapeHtml(formatLongDate(selectedDate?.date || state.anchorDate))}</span>
        `;
    }

    if (refs.prevDateButton) {
        refs.prevDateButton.disabled = currentIndex <= 0 || state.isLoading;
    }

    if (refs.nextDateButton) {
        refs.nextDateButton.disabled = currentIndex < 0 || currentIndex >= availableDates.length - 1 || state.isLoading;
    }

    if (refs.todayButton) {
        refs.todayButton.classList.toggle('is-active', Boolean(selectedDate?.is_today));
    }
}

function renderTimeline() {
    if (!refs.timeline || !state.day) {
        return;
    }

    const slots = Array.isArray(state.day.slots) ? state.day.slots : [];
    if (!slots.length) {
        refs.timeline.innerHTML = `
            <div class="wsd-empty-state">
                <strong>Ngày này chưa có dữ liệu timeline</strong>
                <span>Thử đổi sang ngày khác có booking để xem lịch chi tiết.</span>
            </div>
        `;
        return;
    }

    const selectedQueue = getSelectedQueue();
    refs.timeline.innerHTML = `
        ${slots.map((slot) => slot.booking
            ? buildTimelineBookingItem(slot.booking)
            : buildTimelineFreeItem(slot, selectedQueue)
        ).join('')}
        ${buildTimelineEndItem(slots[slots.length - 1] || null)}
    `;
}

function buildTimelineBookingItem(booking) {
    const visualState = resolveBookingVisualState(booking.status);
    const titleClass = visualState === 'completed' || visualState === 'cancelled' ? ' is-struck' : '';
    const address = compactAddress(booking.address);
    const price = Number(booking.total_amount || 0) > 0
        ? `
            <div class="wsd-entry-price">
                <span>
                    <i class="fa-regular fa-credit-card"></i>
                    <strong>${escapeHtml(formatCurrency(booking.total_amount))}</strong>
                </span>
            </div>
        `
        : '';

    return `
        <article class="wsd-timeline-item">
            <div class="wsd-timeline-time ${visualState === 'repairing' ? 'is-repairing' : ''}">
                ${escapeHtml(extractSlotStartTime(booking.slot_label || booking.slot || ''))}
            </div>
            <div class="wsd-timeline-rail">
                <span class="wsd-timeline-marker is-${escapeHtml(visualState)}">
                    ${visualState === 'completed' ? '<i class="fa-solid fa-check"></i>' : ''}
                </span>
            </div>
            <div class="wsd-entry-card is-${escapeHtml(visualState)}">
                <div class="wsd-entry-card__head">
                    <span class="wsd-entry-pill is-${escapeHtml(visualState)}">${escapeHtml(resolveBookingStateLabel(booking, visualState))}</span>
                    <span class="wsd-entry-slot">${escapeHtml(booking.slot_label || '')}</span>
                </div>
                <h3 class="wsd-entry-title${titleClass}">${escapeHtml(booking.service_label || 'Dịch vụ sửa chữa')}</h3>
                ${booking.problem_excerpt ? `<p class="wsd-entry-problem">${escapeHtml(booking.problem_excerpt)}</p>` : ''}
                <div class="wsd-entry-meta">
                    <span><i class="fa-regular fa-user"></i>${escapeHtml(booking.customer_name || 'Khách hàng')}</span>
                    ${address ? `<span><i class="fa-solid fa-location-dot"></i>${escapeHtml(address)}</span>` : ''}
                </div>
                ${price}
                ${buildBookingActions(booking, visualState)}
            </div>
        </article>
    `;
}

function buildTimelineFreeItem(slot, selectedQueue) {
    const isTarget = Boolean(
        selectedQueue
        && selectedQueue.isAssignable
        && normalizeTimeSlot(selectedQueue.time_slot) === normalizeTimeSlot(slot.slot || '')
    );

    return `
        <article class="wsd-timeline-item">
            <div class="wsd-timeline-time">${escapeHtml(extractSlotStartTime(slot.label || slot.slot || ''))}</div>
            <div class="wsd-timeline-rail">
                <span class="wsd-timeline-marker is-upcoming"><i class="fa-solid fa-plus"></i></span>
            </div>
            <div class="wsd-entry-card is-free${isTarget ? ' is-target' : ''}">
                <div class="wsd-free-caption">${escapeHtml((slot.label || slot.slot || '') + ' (Trống)')}</div>
                <div class="wsd-free-note">
                    ${escapeHtml(isTarget
                        ? `${selectedQueue.customer_name || 'Khách hàng'} • ${selectedQueue.service_label || 'Đơn chờ giao'}`
                        : (selectedQueue
                            ? 'Khung giờ này chưa khớp với đơn đang chọn hoặc đơn chưa đủ điều kiện để giao.'
                            : 'Chọn một công việc ở cột phải để giao nhanh cho thợ trong khung giờ phù hợp.'))}
                </div>
                ${isTarget ? `
                    <div class="wsd-entry-actions">
                        <button type="button" class="wsd-queue-button" data-assign-booking="${escapeHtml(selectedQueue.id)}" ${state.isAssigning ? 'disabled' : ''}>
                            ${state.isAssigning ? 'Đang giao việc...' : 'Giao việc vào khung này'}
                        </button>
                    </div>
                ` : ''}
            </div>
        </article>
    `;
}

function buildTimelineEndItem(lastSlot) {
    return `
        <div class="wsd-timeline-end">
            <div class="wsd-end-label">${escapeHtml(extractSlotEndTime(lastSlot?.label || lastSlot?.slot || ''))}</div>
            <div class="wsd-timeline-rail">
                <span class="wsd-timeline-marker is-end"></span>
            </div>
            <div class="wsd-end-label">Kết thúc ca làm việc</div>
        </div>
    `;
}

function buildBookingActions(booking, visualState) {
    const bookingUrl = `/admin/bookings?booking=${encodeURIComponent(booking.id)}`;

    if (visualState === 'repairing') {
        return `
            <div class="wsd-entry-actions">
                <a class="wsd-timeline-button" href="${bookingUrl}">Xem chi tiết</a>
                <a class="wsd-queue-link" href="${bookingUrl}">Cập nhật tiến độ</a>
            </div>
        `;
    }

    return `
        <div class="wsd-entry-actions">
            <a class="wsd-timeline-button" href="${bookingUrl}">Xem chi tiết</a>
        </div>
    `;
}

function renderQueuePanel() {
    if (!refs.queueList || !refs.queueMeta || !refs.queueCount) {
        return;
    }

    refs.queueCount.textContent = formatNumber(state.queue.length);
    refs.queueMeta.textContent = state.queue.length
        ? `${formatNumber(state.queue.length)} đơn trong ngày ${formatShortDate(state.anchorDate)}`
        : `Không có đơn chờ cho ngày ${formatShortDate(state.anchorDate)}`;

    if (!state.queue.length) {
        refs.queueList.innerHTML = `
            <div class="wsd-empty-state">
                <strong>Hàng chờ đang trống</strong>
                <span>Ngày này hiện chưa có đơn nào cần phân công thêm.</span>
            </div>
        `;
        return;
    }

    refs.queueList.innerHTML = state.queue.map((item) => buildQueueItem(item)).join('');
}

function buildQueueItem(item) {
    const isSelected = Number(item.id) === Number(state.selectedQueueId);
    const urgencyClass = item.urgency_tone === 'danger'
        ? 'is-danger'
        : (item.urgency_tone === 'warning' ? 'is-warning' : 'is-muted');
    const extraPills = [];

    if (!item.supportsService) {
        extraPills.push('<span class="wsd-queue-pill is-danger">Khác nhóm dịch vụ</span>');
    } else if (item.slotState === 'free') {
        extraPills.push('<span class="wsd-queue-pill is-warning">Có thể giao ngay</span>');
    } else if (item.slotState === 'offline') {
        extraPills.push('<span class="wsd-queue-pill is-danger">Thợ đang nghỉ</span>');
    } else {
        extraPills.push('<span class="wsd-queue-pill is-muted">Trùng lịch</span>');
    }

    return `
        <article
            class="wsd-queue-item${isSelected ? ' is-selected' : ''}${item.isAssignable ? ' is-assignable' : ' is-disabled'}${item.urgency_tone === 'danger' ? ' is-danger' : ''}"
            data-select-queue="${escapeHtml(item.id)}"
        >
            <div class="wsd-queue-item__top">
                <img
                    class="wsd-queue-item__avatar"
                    src="${escapeHtml(resolveAvatarUrl(item.customer_avatar))}"
                    alt="${escapeHtml(item.customer_name || 'Khách hàng')}"
                    onerror="this.onerror=null;this.src='/assets/images/user-default.png';"
                >
                <div>
                    <h3 class="wsd-queue-item__name">${escapeHtml(item.customer_name || 'Khách hàng')}</h3>
                    <div class="wsd-queue-item__badges">
                        <span class="wsd-queue-pill ${urgencyClass}">${escapeHtml(resolveUrgencyLabel(item))}</span>
                        ${extraPills.join('')}
                    </div>
                </div>
                <div class="wsd-queue-item__time">
                    <small>Yêu cầu lúc</small>
                    <strong>${escapeHtml(extractSlotStartTime(item.time_slot || item.schedule_label || ''))}</strong>
                </div>
            </div>

            <div class="wsd-queue-item__meta">
                <div class="wsd-queue-item__meta-line">
                    <i class="fa-regular fa-snowflake"></i>
                    <strong>Dịch vụ:</strong>
                    <span>${escapeHtml(item.service_label || 'Dịch vụ sửa chữa')}</span>
                </div>
                <div class="wsd-queue-item__meta-line">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>${escapeHtml(item.address || 'Chưa cập nhật địa chỉ')}</span>
                </div>
            </div>

            ${isSelected ? `
                <div class="wsd-queue-item__footer">
                    <div class="wsd-queue-item__hint">${escapeHtml(item.assignHint)}</div>
                    <button
                        type="button"
                        class="wsd-queue-button"
                        data-assign-booking="${escapeHtml(item.id)}"
                        ${(!item.isAssignable || state.isAssigning) ? 'disabled' : ''}
                    >
                        ${escapeHtml(state.isAssigning ? 'Đang giao việc...' : 'Giao việc mới')}
                    </button>
                    <a class="wsd-queue-link" href="/admin/bookings?booking=${encodeURIComponent(item.id)}">Xem chi tiết đơn</a>
                </div>
            ` : ''}
        </article>
    `;
}

async function assignBookingToWorker(bookingId) {
    const queueItem = state.queue.find((item) => Number(item.id) === Number(bookingId));
    if (!queueItem) {
        return;
    }

    if (!queueItem.isAssignable) {
        showToast(queueItem.assignHint || 'Đơn này chưa thể giao cho thợ hiện tại.', 'error');
        return;
    }

    if (state.isAssigning || !state.worker) {
        return;
    }

    const confirmation = await confirmAction(
        'Giao việc mới cho thợ này?',
        `${queueItem.service_label || 'Đơn chờ xử lý'} sẽ được giao cho ${state.worker.name} trong khung ${queueItem.slotLabel || queueItem.time_slot || '--'}.`,
        'Giao việc'
    );

    if (!confirmation?.isConfirmed) {
        return;
    }

    state.isAssigning = true;
    renderPage();

    try {
        const response = await callApi(`/admin/dispatch/${queueItem.id}/assign`, 'POST', {
            worker_id: Number(state.workerId),
        });

        ensureSuccess(response, 'Không thể giao việc cho thợ.');
        showToast(response.data?.message || 'Đã giao việc thành công');
        await loadPage({ preserveSelection: false });
    } catch (error) {
        console.error('Assign booking from worker detail error:', error);
        showToast(error.message || 'Không thể giao việc cho thợ này', 'error');
    } finally {
        state.isAssigning = false;
        renderPage();
    }
}

function decorateQueueItems(queue) {
    return queue
        .map((item) => {
            const queueServiceIds = Array.isArray(item.service_ids)
                ? item.service_ids.map((value) => Number(value) || 0).filter(Boolean)
                : [];
            const workerServiceIds = Array.isArray(state.worker?.service_ids)
                ? state.worker.service_ids.map((value) => Number(value) || 0).filter(Boolean)
                : [];
            const supportsService = queueServiceIds.length === 0
                || queueServiceIds.every((serviceId) => workerServiceIds.includes(serviceId));
            const matchingSlot = findDaySlot(item.time_slot);
            const slotState = matchingSlot?.state || 'offline';

            return {
                ...item,
                supportsService,
                slotState,
                slotLabel: matchingSlot?.label || item.schedule_label || '',
                isAssignable: supportsService && slotState === 'free',
                assignHint: resolveQueueAssignHint(supportsService, slotState, matchingSlot?.label || item.time_slot || ''),
            };
        })
        .sort((left, right) => {
            const availabilityDiff = Number(right.isAssignable) - Number(left.isAssignable);
            if (availabilityDiff !== 0) {
                return availabilityDiff;
            }

            const urgencyRank = (tone) => tone === 'danger' ? 2 : (tone === 'warning' ? 1 : 0);
            const urgencyDiff = urgencyRank(right.urgency_tone) - urgencyRank(left.urgency_tone);
            if (urgencyDiff !== 0) {
                return urgencyDiff;
            }

            return String(left.time_slot || '').localeCompare(String(right.time_slot || ''), 'vi');
        });
}

function resolveQueueAssignHint(supportsService, slotState, slotLabel) {
    if (!supportsService) {
        return 'Đơn này không thuộc nhóm dịch vụ mà thợ hiện tại có thể xử lý.';
    }

    if (slotState === 'free') {
        return `Thợ hiện đang trống ở khung ${slotLabel || '--'} và có thể nhận đơn này ngay.`;
    }

    if (slotState === 'offline') {
        return 'Thợ đang tạm nghỉ hoặc không mở lịch ở khung giờ của đơn này.';
    }

    return `Thợ đã có việc trong khung ${slotLabel || '--'}, cần chọn thợ khác hoặc đổi lịch.`;
}

function syncSelectedQueue(preserveSelection) {
    if (!state.queue.length) {
        state.selectedQueueId = null;
        return;
    }

    if (preserveSelection && state.queue.some((item) => Number(item.id) === Number(state.selectedQueueId))) {
        return;
    }

    const firstAssignable = state.queue.find((item) => item.isAssignable);
    state.selectedQueueId = firstAssignable?.id || state.queue[0].id;
}

function moveDateSelection(direction) {
    const availableDates = getAvailableDateOptions();
    const currentIndex = availableDates.findIndex((option) => option.date === state.anchorDate);
    const nextDate = availableDates[currentIndex + direction]?.date || '';

    if (!nextDate || nextDate === state.anchorDate) {
        return;
    }

    state.anchorDate = nextDate;
    loadPage({ preserveSelection: false });
}

function findDaySlot(timeSlot) {
    const normalized = normalizeTimeSlot(timeSlot);
    const slots = Array.isArray(state.day?.slots) ? state.day.slots : [];
    return slots.find((slot) => normalizeTimeSlot(slot.slot || slot.label || '') === normalized) || null;
}

function getAvailableDateOptions() {
    const scheduleOptions = Array.isArray(state.data?.meta?.available_dates) ? state.data.meta.available_dates : [];
    const optionMap = new Map(
        scheduleOptions.map((option) => [option.date, option])
    );

    state.queueDates.forEach((date) => {
        if (!optionMap.has(date)) {
            optionMap.set(date, buildDateOption(date));
        }
    });

    return Array.from(optionMap.values()).sort((left, right) => String(left.date).localeCompare(String(right.date)));
}

function getSelectedDateOption() {
    return getAvailableDateOptions().find((option) => option.date === state.anchorDate) || null;
}

function getSelectedQueue() {
    return state.queue.find((item) => Number(item.id) === Number(state.selectedQueueId)) || null;
}

function syncUrlState() {
    const nextParams = new URLSearchParams(window.location.search);
    nextParams.set('date', state.anchorDate);
    nextParams.set('worker', String(state.workerId));
    const nextUrl = `${window.location.pathname}?${nextParams.toString()}`;
    window.history.replaceState({}, '', nextUrl);
}

function syncBackLink() {
    if (!refs.backLink) {
        return;
    }

    const params = new URLSearchParams();
    params.set('date', state.anchorDate);
    params.set('worker', String(state.workerId));
    refs.backLink.href = `/admin/worker-schedules?${params.toString()}`;
}

function renderSyncStatus(label, tone = 'info') {
    if (!refs.syncStatus) {
        return;
    }

    const icon = tone === 'loading'
        ? '<i class="fas fa-sync fa-spin"></i>'
        : (tone === 'error'
            ? '<i class="fas fa-triangle-exclamation"></i>'
            : '<i class="fas fa-circle-check"></i>');

    refs.syncStatus.className = `wsd-sync-chip${tone === 'success' ? ' wsd-sync-chip--success' : ''}${tone === 'error' ? ' wsd-sync-chip--error' : ''}`;
    refs.syncStatus.innerHTML = `${icon}<span>${escapeHtml(label)}</span>`;
}

function resolveRatingLabel(worker) {
    const rating = Number(worker.rating || 0);
    const reviewCount = Number(worker.review_count || 0);

    if (rating > 0 && reviewCount > 0) {
        return `${rating.toFixed(1)} (${formatNumber(reviewCount)} đánh giá)`;
    }

    if (rating > 0) {
        return `${rating.toFixed(1)} sao`;
    }

    return 'Chưa có đánh giá';
}

function resolveBookingVisualState(status) {
    if (status === 'dang_lam') {
        return 'repairing';
    }

    if (status === 'da_xong') {
        return 'completed';
    }

    if (status === 'da_huy') {
        return 'cancelled';
    }

    return 'busy';
}

function resolveBookingStateLabel(booking, visualState) {
    if (visualState === 'repairing') {
        return 'Đang thực hiện';
    }

    if (visualState === 'completed') {
        return 'Đã hoàn thành';
    }

    if (visualState === 'cancelled') {
        return 'Đã hủy';
    }

    if ((booking.status || '') === 'da_xac_nhan') {
        return 'Sắp tới';
    }

    return booking.status_label || 'Lịch trình';
}

function resolveUrgencyLabel(item) {
    if (item.urgency_tone === 'danger') {
        return 'Khẩn cấp';
    }

    if (item.urgency_tone === 'warning') {
        return 'Sắp đến giờ';
    }

    return 'Lịch trình';
}

function resolvePreferredDateOption(options, targetDate) {
    if (!options.length) {
        return '';
    }

    const exactOption = options.find((option) => option.date === targetDate);
    if (exactOption) {
        return exactOption.date;
    }

    const nextOption = options.find((option) => option.date >= targetDate);
    if (nextOption) {
        return nextOption.date;
    }

    return options[options.length - 1]?.date || '';
}

function buildDateOption(date) {
    const normalized = normalizeIsoDate(date);
    if (!normalized) {
        return {
            date: '',
            label: '',
            full_label: '',
            booking_count: 0,
            is_today: false,
        };
    }

    const instance = createDateFromIso(normalized);
    if (!instance) {
        return {
            date: normalized,
            label: normalized,
            full_label: normalized,
            booking_count: 0,
            is_today: false,
        };
    }

    const shortWeekday = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][instance.getDay()] || 'CN';
    return {
        date: normalized,
        label: `${shortWeekday} ${formatShortDate(normalized)}`,
        full_label: formatLongDate(normalized),
        booking_count: 0,
        is_today: normalized === toIsoDate(new Date()),
    };
}

function formatLongDate(value) {
    const date = createDateFromIso(value);
    if (!date) {
        return 'Ngày chưa xác định';
    }

    const weekday = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'][date.getDay()] || 'Ngày';
    return `${weekday}, ${date.getDate()} Tháng ${date.getMonth() + 1}`;
}

function formatShortDate(value) {
    const date = createDateFromIso(value);
    if (!date) {
        return '--/--/----';
    }

    const day = `${date.getDate()}`.padStart(2, '0');
    const month = `${date.getMonth() + 1}`.padStart(2, '0');
    return `${day}/${month}/${date.getFullYear()}`;
}

function createDateFromIso(value) {
    const normalized = normalizeIsoDate(value);
    if (!normalized) {
        return null;
    }

    const date = new Date(`${normalized}T00:00:00`);
    return Number.isNaN(date.getTime()) ? null : date;
}

function compactAddress(value) {
    return String(value || '')
        .split(',')
        .map((part) => part.trim())
        .filter(Boolean)
        .slice(0, 3)
        .join(', ');
}

function resolveAvatarUrl(source) {
    const value = String(source || '').trim();

    if (!value) {
        return '/assets/images/user-default.png';
    }

    if (/^https?:\/\//i.test(value) || value.startsWith('/')) {
        return value;
    }

    return `/storage/${value}`;
}

function extractSlotStartTime(slotLabel) {
    return String(slotLabel || '').split('-')[0].trim() || '--:--';
}

function extractSlotEndTime(slotLabel) {
    const parts = String(slotLabel || '').split('-');
    return parts.length > 1 ? parts[parts.length - 1].trim() : '--:--';
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(Number(amount) || 0);
}

function formatNumber(value) {
    return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(Number(value) || 0);
}

function normalizeIsoDate(value) {
    const raw = String(value || '').trim();
    return /^\d{4}-\d{2}-\d{2}$/.test(raw) ? raw : '';
}

function normalizeTimeSlot(value) {
    return String(value || '').replace(/\s+/g, '');
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function toIsoDate(date) {
    const year = date.getFullYear();
    const month = `${date.getMonth() + 1}`.padStart(2, '0');
    const day = `${date.getDate()}`.padStart(2, '0');

    return `${year}-${month}-${day}`;
}
