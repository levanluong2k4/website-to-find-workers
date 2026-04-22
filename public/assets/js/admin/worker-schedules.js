import { callApi, requireRole } from '../api.js';

const AUTO_REFRESH_MS = 60000;
const initialSearchParams = new URLSearchParams(window.location.search);
const initialWorkerId = Number(initialSearchParams.get('worker') || 0);
const initialAnchorDate = normalizeIsoDateParam(initialSearchParams.get('date')) || toIsoDate(new Date());

const state = {
    anchorDate: initialAnchorDate,
    statusFilter: '',
    data: null,
    isLoading: false,
    selectedWorkerId: initialWorkerId > 0 ? initialWorkerId : null,
    autoRefreshHandle: null,
};

const refs = {};

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');
    cacheElements();
    bindEvents();
    fetchScheduleOverview();
    startAutoRefresh();
});

function cacheElements() {
    refs.page = document.getElementById('workerSchedulesPage');
    refs.layout = document.querySelector('.sch-layout');
    refs.syncStatus = document.getElementById('schSyncStatus');
    refs.refreshButton = document.getElementById('btnRefreshSch');
    refs.todayButton = document.getElementById('btnTodayRange');
    refs.dateSelect = document.getElementById('schDateSelect');
    refs.filterPills = Array.from(document.querySelectorAll('.sch-filter-pill'));
    refs.filteredSummary = document.getElementById('schFilteredSummary');

    refs.statTracked = document.getElementById('statTracked');
    refs.statAvailable = document.getElementById('statAvailable');
    refs.statBusySlots = document.getElementById('statBusySlots');
    refs.statLoadPercent = document.getElementById('statLoadPercent');
    refs.statScheduled = document.getElementById('statScheduled');
    refs.statOffline = document.getElementById('statOffline');

    refs.boardHead = document.getElementById('schBoardHead');
    refs.boardBody = document.getElementById('schBoardBody');
    refs.sideBackdrop = document.getElementById('schSideBackdrop');
    refs.sidePanel = document.getElementById('schSidePanel');
    refs.inspectorBody = document.getElementById('schInspectorBody');
}

function bindEvents() {
    refs.refreshButton?.addEventListener('click', () => {
        fetchScheduleOverview();
    });

    refs.todayButton?.addEventListener('click', () => {
        selectClosestTodayDate();
    });

    refs.dateSelect?.addEventListener('change', () => {
        closeSidePanel();

        const nextDate = refs.dateSelect.value;
        if (!nextDate || nextDate === state.anchorDate) {
            return;
        }

        state.anchorDate = nextDate;
        fetchScheduleOverview();
    });

    refs.filterPills.forEach((pill) => {
        pill.addEventListener('click', () => {
            const nextStatus = pill.dataset.status || '';
            if (nextStatus === state.statusFilter) {
                return;
            }

            closeSidePanel();
            state.statusFilter = nextStatus;
            syncFilterPills();
            renderPage();
        });
    });

    refs.boardBody?.addEventListener('click', (event) => {
        const row = event.target.closest('[data-worker-select]');
        if (!row) {
            return;
        }

        const workerId = Number(row.dataset.workerSelect || 0);
        if (!workerId) {
            return;
        }

        openSidePanel(workerId);
    });

    refs.boardBody?.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        const row = event.target.closest('[data-worker-select]');
        if (!row) {
            return;
        }

        event.preventDefault();

        const workerId = Number(row.dataset.workerSelect || 0);
        if (!workerId) {
            return;
        }

        openSidePanel(workerId);
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            fetchScheduleOverview({ silent: true });
        }
    });

    document.addEventListener('pointerdown', handleGlobalPointerDown);
    document.addEventListener('focusin', handleGlobalFocusIn);
    document.addEventListener('keydown', handleGlobalKeydown);

    refs.sideBackdrop?.addEventListener('click', () => {
        closeSidePanel();
    });
}

function startAutoRefresh() {
    if (state.autoRefreshHandle !== null) {
        window.clearInterval(state.autoRefreshHandle);
    }

    state.autoRefreshHandle = window.setInterval(() => {
        if (!document.hidden) {
            fetchScheduleOverview({ silent: true });
        }
    }, AUTO_REFRESH_MS);

    window.addEventListener('beforeunload', () => {
        if (state.autoRefreshHandle !== null) {
            window.clearInterval(state.autoRefreshHandle);
        }
    }, { once: true });
}

async function fetchScheduleOverview({ silent = false } = {}) {
    if (state.isLoading) {
        return;
    }

    state.isLoading = true;
    setLoadingState(true, silent);

    try {
        const response = await callApi(
            `/admin/worker-schedules/overview?view=day&date=${encodeURIComponent(state.anchorDate)}`,
            'GET'
        );

        if (!response?.ok || !response.data?.data) {
            throw new Error('Khong the tai lich lam viec cua doi tho.');
        }

        state.data = response.data.data;
        state.anchorDate = state.data.meta?.anchor_date || state.anchorDate;
        renderPage();
        renderSyncStatus(`Đã đồng bộ lúc ${state.data.meta?.updated_at || '--:--'}`, 'success');
    } catch (error) {
        console.error('Loi tai worker schedules overview:', error);
        renderErrorState();
        renderSyncStatus('Lỗi đồng bộ lịch thợ', 'error');
    } finally {
        state.isLoading = false;
        setLoadingState(false, silent);
    }
}

function setLoadingState(isLoading, silent) {
    if (!silent && refs.page) {
        refs.page.classList.toggle('is-loading', isLoading);
    }

    if (refs.refreshButton) {
        refs.refreshButton.disabled = isLoading;
    }

    if (refs.todayButton) {
        refs.todayButton.disabled = isLoading;
    }

    if (isLoading && !silent) {
        renderSyncStatus('Đang đồng bộ dữ liệu...', 'loading');
    }
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

    refs.syncStatus.className = `sch-sync-chip sch-sync-chip--${tone}`;
    refs.syncStatus.innerHTML = `${icon}<span>${escapeHtml(label)}</span>`;
}

function renderPage() {
    if (!state.data) {
        renderErrorState();
        return;
    }

    syncDateOptions();
    syncFilterPills();
    syncTodayButton();

    const summary = state.data.summary || {};
    const selectedDateOption = getSelectedDateOption();
    const filteredWorkers = getFilteredWorkers();
    const selectedWorker = resolveSelectedWorker(filteredWorkers);

    renderSummary(summary);
    renderBoard(state.data.meta || {}, filteredWorkers, selectedDateOption);
    renderInspector(selectedWorker, selectedDateOption);
    syncSidePanelState(Boolean(selectedWorker));

    if (refs.filteredSummary) {
        const bookingCount = Number(selectedDateOption?.booking_count || 0);
        const dateLabel = selectedDateOption?.label || 'Ngày đang xem';
        refs.filteredSummary.textContent = `${formatNumber(filteredWorkers.length)} thợ • ${dateLabel} • ${formatNumber(bookingCount)} đơn trong ngày`;
    }
}

function syncDateOptions() {
    if (!refs.dateSelect) {
        return;
    }

    const options = getAvailableDateOptions();
    if (!options.length) {
        refs.dateSelect.innerHTML = '<option value="">Chưa có ngày có đơn</option>';
        refs.dateSelect.disabled = true;
        return;
    }

    refs.dateSelect.innerHTML = options
        .map((option) => `
            <option value="${escapeHtml(option.date)}">
                ${escapeHtml(option.label)} • ${formatNumber(option.booking_count)} đơn đã nhận
            </option>
        `)
        .join('');

    refs.dateSelect.disabled = false;
    refs.dateSelect.value = options.some((option) => option.date === state.anchorDate)
        ? state.anchorDate
        : options[options.length - 1].date;
}

function syncFilterPills() {
    refs.filterPills.forEach((pill) => {
        pill.classList.toggle('is-active', (pill.dataset.status || '') === state.statusFilter);
    });
}

function syncTodayButton() {
    if (!refs.todayButton) {
        return;
    }

    const selectedDateOption = getSelectedDateOption();
    refs.todayButton.classList.toggle('is-active', Boolean(selectedDateOption?.is_today));
}

function selectClosestTodayDate() {
    const options = getAvailableDateOptions();
    const today = toIsoDate(new Date());
    const nextDate = resolvePreferredDateOption(options, today);

    if (!nextDate || nextDate === state.anchorDate) {
        return;
    }

    state.anchorDate = nextDate;
    fetchScheduleOverview();
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

function renderSummary(summary) {
    setText(refs.statTracked, formatNumber(summary.total_workers));
    setText(refs.statAvailable, formatNumber(summary.workers_with_free_slots));
    setText(refs.statBusySlots, formatNumber(summary.total_busy_slots));
    setText(refs.statLoadPercent, `${formatNumber(summary.utilization_percent)}%`);
    setText(refs.statScheduled, formatNumber((Number(summary.scheduled_workers) || 0) + (Number(summary.repairing_workers) || 0)));
    setText(refs.statOffline, formatNumber(summary.offline_workers));
}

function renderBoard(meta, workers, selectedDateOption) {
    if (!refs.boardHead || !refs.boardBody) {
        return;
    }

    const timeSlots = Array.isArray(meta.time_slots) ? meta.time_slots : [];
    const gridTemplate = createBoardGridTemplate(timeSlots.length);
    const dayLabel = selectedDateOption?.is_today ? 'Hôm nay' : 'Ngày chọn';

    refs.boardHead.innerHTML = `
        <div class="sch-board-grid sch-board-grid--head" style="grid-template-columns:${gridTemplate}">
            <div class="sch-head-cell sch-head-cell--worker">Thợ</div>
            <div class="sch-head-cell sch-head-cell--summary">${escapeHtml(dayLabel)}</div>
            ${timeSlots.map((slot) => `
                <div class="sch-head-cell sch-head-cell--slot">${escapeHtml(slot.label || slot.value || '')}</div>
            `).join('')}
        </div>
    `;

    if (!workers.length) {
        refs.boardBody.innerHTML = `
            <div class="sch-board-empty">
                <i class="fa-solid fa-filter-circle-xmark"></i>
                <strong>Không có thợ phù hợp với bộ lọc hiện tại</strong>
                <p>Thử đổi nhóm trạng thái hoặc chọn ngày có đơn khác để xem lại board.</p>
            </div>
        `;
        return;
    }

    refs.boardBody.innerHTML = workers
        .map((worker) => buildWorkerRow(worker, timeSlots.length, gridTemplate))
        .join('');
}

function buildWorkerRow(worker, slotCount, gridTemplate) {
    const day = getWorkerDay(worker);
    const isSelected = Number(worker.id) === Number(state.selectedWorkerId);
    const isOfflineDay = shouldMergeOfflineDay(day);

    return `
        <div class="sch-row${isSelected ? ' is-selected' : ''}" data-worker-select="${worker.id}" tabindex="0">
            <div class="sch-board-grid" style="grid-template-columns:${gridTemplate}">
                ${buildWorkerIdentity(worker)}
                ${buildDaySummary(day)}
                ${isOfflineDay
                    ? buildMergedOfflineCell(slotCount)
                    : buildSlotCells(day, slotCount)
                }
            </div>
        </div>
    `;
}

function buildWorkerIdentity(worker) {
    const statusKey = worker.current_status?.key || 'offline';
    const rating = Number(worker.rating || 0);

    return `
        <div class="sch-worker-cell">
            <div class="sch-worker-avatar-wrap">
                <img src="${escapeHtml(resolveAvatarUrl(worker.avatar))}" alt="${escapeHtml(worker.name)}" class="sch-worker-avatar" onerror="this.onerror=null;this.src='/assets/images/worker2.png';">
                <span class="sch-worker-dot sch-worker-dot--${escapeHtml(statusKey)}"></span>
            </div>
            <div class="sch-worker-copy">
                <div class="sch-worker-name-row">
                    <h3 class="sch-worker-name">${escapeHtml(worker.name || 'Kỹ thuật viên')}</h3>
                    ${rating > 0 ? `<span class="sch-worker-rating"><i class="fa-solid fa-star"></i>${escapeHtml(rating.toFixed(1))}</span>` : ''}
                </div>
                <div class="sch-worker-phone">${escapeHtml(worker.phone || 'Chưa có số điện thoại')}</div>
            </div>
        </div>
    `;
}

function buildDaySummary(day) {
    const busyCount = Number(day?.busy_count || 0);
    const freeCount = Number(day?.free_count || 0);
    const isOffline = shouldMergeOfflineDay(day);

    if (isOffline) {
        return `
            <div class="sch-day-summary sch-day-summary--offline">
                <span>Tạm nghỉ</span>
            </div>
        `;
    }

    return `
        <div class="sch-day-summary">
            <span class="sch-day-summary__busy">${formatNumber(busyCount)} bận</span>
            <span class="sch-day-summary__free">${formatNumber(freeCount)} trống</span>
        </div>
    `;
}

function buildMergedOfflineCell(slotCount) {
    return `
        <div class="sch-slot-merged sch-slot-merged--offline" style="grid-column: 3 / span ${Math.max(slotCount, 1)};">
            <i class="fa-regular fa-circle-pause"></i>
            <span>Tạm nghỉ phép hôm nay</span>
        </div>
    `;
}

function buildSlotCells(day, slotCount) {
    const slots = Array.isArray(day?.slots) ? day.slots : [];

    return Array.from({ length: slotCount }, (_, index) => {
        const slot = slots[index] || { state: 'free', booking: null };
        return buildSlotCell(slot);
    }).join('');
}

function buildSlotCell(slot) {
    const booking = slot.booking || null;
    const stateKey = slot.state || 'free';
    const title = booking?.service_label || (stateKey === 'free' ? 'Trống' : 'Không mở lịch');
    const subtitle = resolveSlotSubtitle(stateKey, booking);
    const badge = resolveSlotBadge(stateKey);
    const strikeClass = stateKey === 'cancelled' ? ' is-struck' : '';

    return `
        <div class="sch-slot-cell">
            <div class="sch-slot-card sch-slot-card--${escapeHtml(stateKey)}">
                <div class="sch-slot-card__head">
                    <span class="sch-slot-card__title${strikeClass}">${escapeHtml(truncateText(title, 22))}</span>
                    ${badge}
                </div>
                <div class="sch-slot-card__meta${strikeClass}">${escapeHtml(subtitle)}</div>
            </div>
        </div>
    `;
}

function resolveSlotBadge(stateKey) {
    if (stateKey === 'repairing') {
        return '<span class="sch-slot-badge">Đang làm</span>';
    }

    if (stateKey === 'completed') {
        return '<span class="sch-slot-badge sch-slot-badge--soft"><i class="fa-regular fa-circle-check"></i></span>';
    }

    if (stateKey === 'cancelled') {
        return '<span class="sch-slot-badge sch-slot-badge--soft"><i class="fa-regular fa-circle-xmark"></i></span>';
    }

    return '';
}

function resolveSlotSubtitle(stateKey, booking) {
    if (stateKey === 'free') {
        return 'Trống';
    }

    if (!booking) {
        return stateKey === 'offline' ? 'Không mở lịch' : 'Chưa có chi tiết';
    }

    const compactArea = compactAddress(booking.address);
    if (stateKey === 'repairing') {
        return `${booking.customer_name || 'Khách hàng'}${compactArea ? ` - ${compactArea}` : ''}`;
    }

    if (stateKey === 'completed' || stateKey === 'cancelled' || stateKey === 'busy') {
        return `${booking.customer_name || 'Khách hàng'}${compactArea ? ` - ${compactArea}` : ''}`;
    }

    return compactArea || booking.customer_name || 'Khách hàng';
}

function renderInspector(worker, selectedDateOption) {
    if (!refs.inspectorBody) {
        return;
    }

    if (!worker) {
        refs.inspectorBody.innerHTML = `
            <div class="sch-inspector-empty">
                <i class="fa-solid fa-user-check"></i>
                <strong>Chọn một thợ để xem chi tiết</strong>
                <p>Board bên trái sẽ đồng bộ thông tin booking trong ngày cho thợ đang được chọn.</p>
            </div>
        `;
        return;
    }

    const day = getWorkerDay(worker);
    const bookings = Array.isArray(day?.bookings) ? day.bookings : [];
    const currentStatus = worker.current_status || {};

    refs.inspectorBody.innerHTML = `
        <section class="sch-side-card sch-side-card--hero">
            <div class="sch-side-worker">
                <img src="${escapeHtml(resolveAvatarUrl(worker.avatar))}" alt="${escapeHtml(worker.name)}" class="sch-side-worker__avatar" onerror="this.onerror=null;this.src='/assets/images/worker2.png';">
                <div class="sch-side-worker__copy">
                    <strong>${escapeHtml(worker.name || 'Kỹ thuật viên')}</strong>
                    <span>${escapeHtml(worker.phone || 'Chưa có số điện thoại')}</span>
                </div>
            </div>

            <div class="sch-load-row">
                <span>Tải công việc</span>
                <strong>${formatNumber(worker.utilization_percent)}%</strong>
            </div>
            <div class="sch-load-bar">
                <div class="sch-load-bar__fill" style="width:${Math.max(0, Math.min(100, Number(worker.utilization_percent || 0)))}%"></div>
            </div>

            <div class="sch-mini-stats">
                <div class="sch-mini-stat">
                    <strong>${formatNumber(worker.busy_slot_count)}</strong>
                    <span>Bận</span>
                </div>
                <div class="sch-mini-stat sch-mini-stat--green">
                    <strong>${formatNumber(worker.free_slot_count)}</strong>
                    <span>Trống</span>
                </div>
                <div class="sch-mini-stat">
                    <strong>${formatNumber(worker.capacity_slot_count)}</strong>
                    <span>Sức chứa</span>
                </div>
            </div>
        </section>

        <section class="sch-side-card">
            <div class="sch-info-row">
                <span>Nhóm dịch vụ</span>
                <strong>${escapeHtml(worker.services_label || 'Chưa gán')}</strong>
            </div>
            <div class="sch-info-row">
                <span>Khu vực ưu tiên</span>
                <strong>${escapeHtml(worker.area_label || 'Chưa có khu vực')}</strong>
            </div>
            <div class="sch-info-row">
                <span>Lịch hiện tại</span>
                <strong class="sch-info-row__accent">${escapeHtml(resolveInspectorSchedule(worker, currentStatus, selectedDateOption))}</strong>
            </div>
        </section>

        <section class="sch-side-card sch-side-card--list">
            <div class="sch-side-card__title">Booking trong ngày</div>
            <div class="sch-booking-list">
                ${bookings.length
                    ? bookings.map((booking) => buildBookingListItem(booking)).join('')
                    : `<div class="sch-booking-empty">Không có booking nào trong ngày đang chọn.</div>`
                }
            </div>
        </section>

        <a class="sch-assign-button" href="/admin/worker-schedules/${encodeURIComponent(worker.id)}?date=${encodeURIComponent(selectedDateOption?.date || state.anchorDate)}&worker=${encodeURIComponent(worker.id)}">Xem chi tiết</a>
    `;
}

function buildBookingListItem(booking) {
    const visualState = resolveBookingVisualState(booking.status);
    const address = compactAddress(booking.address);
    const extra = visualState === 'repairing' ? 'Đang làm' : '';

    return `
        <article class="sch-booking-item sch-booking-item--${escapeHtml(visualState)}">
            <div class="sch-booking-item__head">
                <strong>${escapeHtml(booking.service_label || 'Sửa chữa')}</strong>
                <span>${escapeHtml(booking.slot_label || '')}</span>
            </div>
            <div class="sch-booking-item__body">
                ${escapeHtml(booking.customer_name || 'Khách hàng')}${address ? ` - ${escapeHtml(address)}` : ''}
            </div>
            ${extra ? `<div class="sch-booking-item__tag">${escapeHtml(extra)}</div>` : ''}
        </article>
    `;
}

function resolveInspectorSchedule(worker, currentStatus, selectedDateOption) {
    if (currentStatus.key === 'repairing') {
        return worker.current_booking_label || 'Đang sửa';
    }

    if (currentStatus.key === 'scheduled') {
        return worker.schedule_label || 'Đang có lịch';
    }

    if (currentStatus.key === 'offline') {
        return selectedDateOption?.is_today ? 'Tạm nghỉ hôm nay' : 'Không mở lịch';
    }

    return worker.next_free_slot_label || 'Sẵn sàng nhận việc';
}

function getFilteredWorkers() {
    const workers = Array.isArray(state.data?.workers) ? state.data.workers : [];

    return workers
        .filter((worker) => !state.statusFilter || (worker.current_status?.key || '') === state.statusFilter)
        .slice()
        .sort((left, right) => {
            const leftBusy = Number(getWorkerDay(left)?.busy_count || 0);
            const rightBusy = Number(getWorkerDay(right)?.busy_count || 0);
            if (leftBusy !== rightBusy) {
                return rightBusy - leftBusy;
            }

            const leftStatus = resolveWorkerDisplayPriority(left.current_status?.key || '');
            const rightStatus = resolveWorkerDisplayPriority(right.current_status?.key || '');
            if (leftStatus !== rightStatus) {
                return leftStatus - rightStatus;
            }

            return String(left.name || '').localeCompare(String(right.name || ''), 'vi');
        });
}

function resolveSelectedWorker(filteredWorkers) {
    if (!filteredWorkers.length) {
        state.selectedWorkerId = null;
        return null;
    }

    const existingWorker = filteredWorkers.find((worker) => Number(worker.id) === Number(state.selectedWorkerId));
    if (existingWorker) {
        return existingWorker;
    }

    state.selectedWorkerId = null;
    return null;
}

function getWorkerDay(worker) {
    return Array.isArray(worker?.days) ? (worker.days[0] || null) : null;
}

function shouldMergeOfflineDay(day) {
    if (!day) {
        return false;
    }

    const slots = Array.isArray(day.slots) ? day.slots : [];
    return slots.length > 0 && slots.every((slot) => (slot.state || 'offline') === 'offline');
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

function resolveWorkerDisplayPriority(statusKey) {
    if (statusKey === 'repairing') {
        return 1;
    }

    if (statusKey === 'scheduled') {
        return 2;
    }

    if (statusKey === 'available') {
        return 3;
    }

    return 4;
}

function renderErrorState() {
    state.selectedWorkerId = null;

    if (refs.boardHead) {
        refs.boardHead.innerHTML = '';
    }

    if (refs.boardBody) {
        refs.boardBody.innerHTML = `
            <div class="sch-board-empty">
                <i class="fa-solid fa-circle-exclamation"></i>
                <strong>Không thể tải board lịch làm việc</strong>
                <p>Kiểm tra API admin worker schedules overview hoặc thử làm mới lại.</p>
            </div>
        `;
    }

    if (refs.inspectorBody) {
        refs.inspectorBody.innerHTML = `
            <div class="sch-inspector-empty">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <strong>Chưa có dữ liệu chi tiết</strong>
                <p>Board chưa tải được nên panel bên phải chưa thể hiển thị.</p>
            </div>
        `;
    }

    if (refs.dateSelect) {
        refs.dateSelect.innerHTML = '<option value="">Không tải được ngày có đơn</option>';
        refs.dateSelect.disabled = true;
    }

    syncSidePanelState(false);
}

function openSidePanel(workerId) {
    state.selectedWorkerId = workerId;
    renderPage();

    window.requestAnimationFrame(() => {
        refs.sidePanel?.focus({ preventScroll: true });
    });
}

function closeSidePanel() {
    if (state.selectedWorkerId === null) {
        return;
    }

    state.selectedWorkerId = null;
    renderPage();
}

function syncSidePanelState(isOpen) {
    refs.layout?.classList.toggle('is-panel-open', isOpen);
    refs.sideBackdrop?.classList.toggle('is-open', isOpen);
    refs.sidePanel?.classList.toggle('is-open', isOpen);

    if (refs.sideBackdrop) {
        refs.sideBackdrop.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    }

    if (refs.sidePanel) {
        refs.sidePanel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    }

    document.body.classList.toggle('sch-panel-open', isOpen);
}

function handleGlobalPointerDown(event) {
    if (state.selectedWorkerId === null) {
        return;
    }

    const target = event.target;
    if (!(target instanceof Element)) {
        return;
    }

    if (target.closest('.sch-side-panel') || target.closest('[data-worker-select]')) {
        return;
    }

    closeSidePanel();
}

function handleGlobalFocusIn(event) {
    if (state.selectedWorkerId === null) {
        return;
    }

    const target = event.target;
    if (!(target instanceof Element)) {
        return;
    }

    if (target.closest('.sch-side-panel') || target.closest('[data-worker-select]')) {
        return;
    }

    closeSidePanel();
}

function handleGlobalKeydown(event) {
    if (event.key === 'Escape' && state.selectedWorkerId !== null) {
        closeSidePanel();
    }
}

function getAvailableDateOptions() {
    return Array.isArray(state.data?.meta?.available_dates) ? state.data.meta.available_dates : [];
}

function getSelectedDateOption() {
    return getAvailableDateOptions().find((option) => option.date === state.anchorDate) || null;
}

function createBoardGridTemplate(slotCount) {
    return `300px 108px repeat(${Math.max(slotCount, 1)}, minmax(118px, 1fr))`;
}

function compactAddress(address) {
    const parts = String(address || '')
        .split(',')
        .map((part) => part.trim())
        .filter(Boolean);

    if (!parts.length) {
        return '';
    }

    return parts.slice(-2).join(', ');
}

function resolveAvatarUrl(avatar) {
    const value = String(avatar || '').trim();

    if (!value) {
        return '/assets/images/worker2.png';
    }

    if (/^https?:\/\//i.test(value) || value.startsWith('/')) {
        return value;
    }

    if (value.startsWith('storage/')) {
        return `/${value}`;
    }

    return `/storage/${value.replace(/^\/+/, '')}`;
}

function truncateText(value, maxLength) {
    const text = String(value || '').trim();
    if (text.length <= maxLength) {
        return text;
    }

    return `${text.slice(0, Math.max(0, maxLength - 1)).trim()}…`;
}

function setText(element, value) {
    if (element) {
        element.textContent = value ?? '--';
    }
}

function formatNumber(value) {
    return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(Number(value) || 0);
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
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function normalizeIsoDateParam(value) {
    const raw = String(value || '').trim();
    return /^\d{4}-\d{2}-\d{2}$/.test(raw) ? raw : '';
}
