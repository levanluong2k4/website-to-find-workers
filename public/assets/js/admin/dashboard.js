import { callApi, requireRole } from '../api.js';

const DEFAULT_MAP_CENTER = [12.2388, 109.1967];
const WORKER_MAP_INFO_CARD_HIDDEN_KEY = 'adminDashboard.workerMapInfoCardHidden';
const state = {
    period: 'month',
    refreshMs: 30000,
    isFetching: false,
    autoRefreshHandle: null,
    workerMap: {
        map: null,
        tileLayer: null,
        markersLayer: null,
        hasViewport: false,
        selectedWorkerId: null,
        isInfoCardHidden: false,
    },
};
const elements = {};

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');
    cacheElements();
    bindEvents();
    syncPeriodButtons();
    startAutoRefresh();
    fetchDashboard();
});

function cacheElements() {
    elements.root = document.getElementById('adminDashboard');
    elements.refreshButton = document.getElementById('btnRefresh');
    elements.logoutLink = document.getElementById('admLogout');
    elements.periodButtons = Array.from(document.querySelectorAll('.js-period-btn'));

    elements.focusPendingBookingsValue = document.getElementById('focusPendingBookingsValue');
    elements.focusPendingBookingsNote = document.getElementById('focusPendingBookingsNote');
    elements.priorityComplaintChip = document.getElementById('priorityComplaintChip');
    elements.priorityWorkerChip = document.getElementById('priorityWorkerChip');
    elements.priorityRevenueChip = document.getElementById('priorityRevenueChip');
    elements.workerMapMeta = document.getElementById('workerMapMeta');
    elements.workerMapTrackedCount = document.getElementById('workerMapTrackedCount');
    elements.workerMapRepairingCount = document.getElementById('workerMapRepairingCount');
    elements.workerMapScheduledCount = document.getElementById('workerMapScheduledCount');
    elements.workerMapAvailableCount = document.getElementById('workerMapAvailableCount');
    elements.workerMapStatus = document.getElementById('workerMapStatus');
    elements.workerMapCanvas = document.getElementById('workerTrackingMap');
    elements.workerMapEmptyState = document.getElementById('workerMapEmptyState');
    elements.workerMapInfoCard = document.getElementById('workerMapInfoCard');
    elements.workerMapInfoHideButton = document.getElementById('workerMapInfoHideButton');
    elements.workerMapInfoShowButton = document.getElementById('workerMapInfoShowButton');
    elements.workerMapInfoName = document.getElementById('workerMapInfoName');
    elements.workerMapInfoStatus = document.getElementById('workerMapInfoStatus');
    elements.workerMapInfoDetail = document.getElementById('workerMapInfoDetail');
    elements.workerMapInfoRating = document.getElementById('workerMapInfoRating');
    elements.workerMapInfoServices = document.getElementById('workerMapInfoServices');
    elements.workerMapInfoSchedule = document.getElementById('workerMapInfoSchedule');
    elements.workerMapInfoArea = document.getElementById('workerMapInfoArea');

    state.workerMap.isInfoCardHidden = readWorkerMapInfoCardHiddenPreference();
    syncWorkerMapInfoCardVisibility();

    elements.summaryRevenueToday = document.getElementById('summaryRevenueToday');
    elements.summaryRevenueNote = document.getElementById('summaryRevenueNote');
    elements.summaryBookingsToday = document.getElementById('summaryBookingsToday');
    elements.summaryBookingsNote = document.getElementById('summaryBookingsNote');
    elements.summaryCommission = document.getElementById('summaryCommission');
    elements.summaryCommissionNote = document.getElementById('summaryCommissionNote');
    elements.summaryComplaints = document.getElementById('summaryComplaints');
    elements.summaryComplaintsNote = document.getElementById('summaryComplaintsNote');

    elements.metaUpdatedAt = document.getElementById('metaUpdatedAt');
    elements.metaPeriodLabel = document.getElementById('metaPeriodLabel');
    elements.revenuePeriodTotal = document.getElementById('revenuePeriodTotal');
    elements.revenuePeriodNote = document.getElementById('revenuePeriodNote');
    elements.revenueTopService = document.getElementById('revenueTopService');
    elements.revenueTransferShare = document.getElementById('revenueTransferShare');
    elements.revenueTransferDonut = document.getElementById('revenueTransferDonut');
    elements.revenueTransferPercent = document.getElementById('revenueTransferPercent');
    elements.revenueChart = document.getElementById('revenueChart');
    elements.revenueChartLabels = document.getElementById('revenueChartLabels');

    elements.alertList = document.getElementById('alertList');
    elements.alertFooter = document.getElementById('alertFooter');

    elements.bookingsTodayTotal = document.getElementById('bookingsTodayTotal');
    elements.bookingsPendingTotal = document.getElementById('bookingsPendingTotal');
    elements.bookingsProgressTotal = document.getElementById('bookingsProgressTotal');
    elements.bookingsCompletedTotal = document.getElementById('bookingsCompletedTotal');
    elements.bookingQueueList = document.getElementById('bookingQueueList');

    elements.workersTotal = document.getElementById('workersTotal');
    elements.workersActive = document.getElementById('workersActive');
    elements.workersPending = document.getElementById('workersPending');
    elements.workersLowRating = document.getElementById('workersLowRating');
    elements.workerWatchList = document.getElementById('workerWatchList');

    elements.revenueTableBody = document.getElementById('revenueTableBody');

    elements.complaintsNew = document.getElementById('complaintsNew');
    elements.complaintsLowRating = document.getElementById('complaintsLowRating');
    elements.complaintsCanceled = document.getElementById('complaintsCanceled');
    elements.complaintList = document.getElementById('complaintList');
}

function bindEvents() {
    elements.periodButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const nextPeriod = button.dataset.period;
            if (!nextPeriod || nextPeriod === state.period) {
                return;
            }

            state.period = nextPeriod;
            syncPeriodButtons();
            fetchDashboard();
        });
    });

    if (elements.refreshButton) {
        elements.refreshButton.addEventListener('click', () => {
            fetchDashboard();
        });
    }

    if (elements.workerMapInfoHideButton) {
        elements.workerMapInfoHideButton.addEventListener('click', () => {
            setWorkerMapInfoCardHidden(true);
        });
    }

    if (elements.workerMapInfoShowButton) {
        elements.workerMapInfoShowButton.addEventListener('click', () => {
            setWorkerMapInfoCardHidden(false);
        });
    }

    if (elements.logoutLink) {
        elements.logoutLink.addEventListener('click', async (event) => {
            event.preventDefault();

            try {
                await callApi('/logout', 'POST');
            } catch (error) {
                console.error('Loi dang xuat:', error);
            } finally {
                window.location.href = '/login';
            }
        });
    }

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            fetchDashboard({ silent: true });
        }
    });
}

function startAutoRefresh() {
    if (state.autoRefreshHandle !== null) {
        window.clearInterval(state.autoRefreshHandle);
    }

    state.autoRefreshHandle = window.setInterval(() => {
        if (!document.hidden) {
            fetchDashboard({ silent: true });
        }
    }, state.refreshMs);

    window.addEventListener('beforeunload', () => {
        if (state.autoRefreshHandle !== null) {
            window.clearInterval(state.autoRefreshHandle);
        }
    }, { once: true });
}

function syncPeriodButtons() {
    elements.periodButtons.forEach((button) => {
        button.classList.toggle('is-active', button.dataset.period === state.period);
    });
}

async function fetchDashboard({ silent = false } = {}) {
    if (state.isFetching) {
        return;
    }

    state.isFetching = true;

    if (!silent) {
        setLoading(true);
    }

    try {
        const response = await callApi(`/admin/dashboard?period=${state.period}`, 'GET');

        if (!response?.ok || !response.data?.data) {
            throw new Error('Khong the tai du lieu dashboard admin.');
        }

        renderDashboard(response.data.data);
    } catch (error) {
        console.error('Loi tai dashboard admin:', error);
        renderErrorState();
    } finally {
        state.isFetching = false;

        if (!silent) {
            setLoading(false);
        }
    }
}

function setLoading(isLoading) {
    if (elements.root) {
        elements.root.classList.toggle('is-loading', isLoading);
    }

    if (!elements.refreshButton) {
        return;
    }

    elements.refreshButton.disabled = isLoading;
    elements.refreshButton.innerHTML = isLoading
        ? '<i class="fa-solid fa-spinner fa-spin"></i>Dang tai'
        : '<i class="fa-solid fa-rotate"></i>Dong bo';
}

function setWorkerMapInfoCardHidden(isHidden) {
    state.workerMap.isInfoCardHidden = Boolean(isHidden);
    syncWorkerMapInfoCardVisibility();

    try {
        window.localStorage.setItem(WORKER_MAP_INFO_CARD_HIDDEN_KEY, state.workerMap.isInfoCardHidden ? '1' : '0');
    } catch (error) {
        console.warn('Khong the luu tuy chon an bang theo doi tho:', error);
    }
}

function syncWorkerMapInfoCardVisibility() {
    if (elements.workerMapInfoCard) {
        elements.workerMapInfoCard.classList.toggle('is-hidden', state.workerMap.isInfoCardHidden);
    }

    if (elements.workerMapInfoShowButton) {
        elements.workerMapInfoShowButton.classList.toggle('is-hidden', !state.workerMap.isInfoCardHidden);
    }
}

function readWorkerMapInfoCardHiddenPreference() {
    try {
        return window.localStorage.getItem(WORKER_MAP_INFO_CARD_HIDDEN_KEY) === '1';
    } catch (error) {
        return false;
    }
}

function renderDashboard(data) {
    const summary = data.summary ?? {};
    const bookings = data.bookings ?? {};
    const workers = data.workers_summary ?? {};
    const workersMap = data.workers_map ?? {};
    const complaints = data.complaints ?? {};

    renderPriorityFocus(summary, bookings, workers, complaints);
    renderWorkerMap(workersMap, data.meta ?? {});
    renderSummary(summary);
    renderRevenueSection(data.meta ?? {}, data.revenue ?? {});
    renderAlerts(data.alerts ?? {});
    renderBookings(bookings);
    renderWorkers(workers);
    renderRevenueTable(data.revenue_table ?? []);
    renderComplaints(complaints);

    document.dispatchEvent(new CustomEvent('dashboardDataLoaded', {
        detail: {
            pendingBookings: Number(bookings.today_pending || 0),
            complaints: Number(complaints.new || 0),
        },
    }));
}

function renderPriorityFocus(summary, bookings, workers, complaints) {
    setText(elements.focusPendingBookingsValue, formatNumber(bookings.today_pending));
    setText(
        elements.focusPendingBookingsNote,
        Number(bookings.today_pending || 0) > 0
            ? `${formatNumber(bookings.today_pending)} don can xu ly ngay`
            : 'Khong co don cho xac nhan'
    );

    setText(elements.priorityComplaintChip, formatNumber(complaints.new));
    setText(elements.priorityWorkerChip, formatNumber(workers.pending_approval));
    setText(elements.priorityRevenueChip, formatMoney(summary.revenue_today?.value));
}

function renderWorkerMap(mapPayload, meta) {
    if (!elements.workerMapCanvas) {
        return;
    }

    const workers = Array.isArray(mapPayload.workers) ? mapPayload.workers : [];
    const trackedCount = Number(mapPayload.tracked_count || 0);
    const repairingCount = Number(mapPayload.repairing_count || 0);
    const scheduledCount = Number(mapPayload.scheduled_count || 0);
    const availableCount = Number(mapPayload.available_count || 0);
    const offlineCount = Number(mapPayload.offline_count || 0);
    const missingLocationCount = Number(mapPayload.missing_location_count || 0);
    const refreshSeconds = Number(mapPayload.poll_interval_seconds || Math.round(state.refreshMs / 1000) || 30);
    const updatedAt = mapPayload.updated_at || meta.updated_at || '--:--';

    setText(elements.workerMapTrackedCount, formatNumber(trackedCount));
    setText(elements.workerMapRepairingCount, formatNumber(repairingCount));
    setText(elements.workerMapScheduledCount, formatNumber(scheduledCount));
    setText(elements.workerMapAvailableCount, formatNumber(availableCount));
    setText(elements.workerMapMeta, `Cap nhat ${updatedAt} • auto ${formatNumber(refreshSeconds)}s`);
    setText(
        elements.workerMapStatus,
        trackedCount > 0
            ? `${formatNumber(trackedCount)} tho co GPS • ${formatNumber(offlineCount)} tam nghi • ${formatNumber(missingLocationCount)} chua cap nhat toa do`
            : 'Chua co tho nao du dieu kien hien tren ban do'
    );

    if (!window.L) {
        setWorkerMapEmptyState(
            true,
            'Khong tai duoc thu vien ban do',
            'Leaflet chua san sang, vui long tai lai trang de hien thi vi tri doi tho.'
        );
        return;
    }

    const map = ensureWorkerMap(mapPayload.center);
    if (!map || !state.workerMap.markersLayer) {
        return;
    }

    state.workerMap.markersLayer.clearLayers();

    const bounds = [];

    workers.forEach((worker) => {
        const lat = Number(worker?.point?.lat);
        const lng = Number(worker?.point?.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return;
        }

        const marker = window.L.marker([lat, lng], {
            icon: buildWorkerMarkerIcon(worker),
        }).addTo(state.workerMap.markersLayer);

        marker.bindTooltip(buildWorkerMapTooltip(worker), {
            direction: 'top',
            offset: [0, -40],
            opacity: 1,
            className: 'adm-map-tooltip',
        });

        marker.on('mouseover', () => {
            selectWorkerMapCard(worker);
        });

        marker.on('click', () => {
            selectWorkerMapCard(worker);
        });

        bounds.push([lat, lng]);
    });

    setWorkerMapEmptyState(
        bounds.length === 0,
        'Chua co du lieu vi tri tho',
        'Ban do se hien khi tho co toa do hop le trong ho so va da duoc phe duyet hoat dong.'
    );

    if (bounds.length > 0) {
        if (!state.workerMap.hasViewport) {
            if (bounds.length === 1) {
                map.setView(bounds[0], 13);
            } else {
                map.fitBounds(bounds, { padding: [44, 44], maxZoom: 13 });
            }

            state.workerMap.hasViewport = true;
        }

        const activeWorker = workers.find((worker) => worker?.id === state.workerMap.selectedWorkerId)
            || workers.find((worker) => worker?.map_status === 'repairing')
            || workers[0];

        selectWorkerMapCard(activeWorker || null);
    } else {
        const fallbackCenter = [
            Number(mapPayload?.center?.lat) || DEFAULT_MAP_CENTER[0],
            Number(mapPayload?.center?.lng) || DEFAULT_MAP_CENTER[1],
        ];

        map.setView(fallbackCenter, 11);
        state.workerMap.hasViewport = false;
        selectWorkerMapCard(null);
    }

    window.setTimeout(() => {
        map.invalidateSize();
    }, 0);
}

function ensureWorkerMap(center) {
    if (!elements.workerMapCanvas || !window.L) {
        return null;
    }

    if (!state.workerMap.map) {
        state.workerMap.map = window.L.map(elements.workerMapCanvas, {
            zoomControl: false,
            attributionControl: false,
            scrollWheelZoom: true,
        });

        state.workerMap.tileLayer = window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(state.workerMap.map);

        state.workerMap.markersLayer = window.L.layerGroup().addTo(state.workerMap.map);
    }

    if (!state.workerMap.hasViewport) {
        state.workerMap.map.setView([
            Number(center?.lat) || DEFAULT_MAP_CENTER[0],
            Number(center?.lng) || DEFAULT_MAP_CENTER[1],
        ], 11);
    }

    return state.workerMap.map;
}

function buildWorkerMarkerIcon(worker) {
    const tone = sanitizeWorkerMapTone(worker?.map_tone);
    const name = escapeHtml(worker?.name || 'Tho ky thuat');
    const initials = escapeHtml(getWorkerMapInitials(worker?.name));
    const avatarUrl = resolveWorkerMapAvatarUrl(worker?.avatar);
    const hasAvatar = avatarUrl !== '';

    return window.L.divIcon({
        className: 'adm-leaflet-marker',
        html: `
            <div class="adm-worker-marker adm-worker-marker--${tone} ${hasAvatar ? 'has-avatar' : 'is-name'}" aria-label="${name}">
                <div class="adm-worker-marker__avatar">
                    ${hasAvatar
                        ? `<img src="${escapeHtml(avatarUrl)}" alt="${name}" loading="lazy" onerror="this.onerror=null; this.style.display='none'; const fallback = this.parentElement.querySelector('.adm-worker-marker__fallback'); if (fallback) { fallback.style.display='flex'; } const marker = this.closest('.adm-worker-marker'); if (marker) { marker.classList.add('is-name'); }">`
                        : ''
                    }
                    <span class="adm-worker-marker__fallback"${hasAvatar ? ' style="display:none;"' : ''}>${initials}</span>
                    <span class="adm-worker-marker__dot"></span>
                </div>
                <span class="adm-worker-marker__name">${name}</span>
            </div>
        `,
        iconSize: [110, 88],
        iconAnchor: [55, 74],
        tooltipAnchor: [0, -56],
    });
}

function buildWorkerMapTooltip(worker) {
    const tone = sanitizeWorkerMapTone(worker?.map_tone);
    return `
        <div class="adm-map-tooltip__bubble">
            <span class="adm-map-tooltip__dot adm-map-tooltip__dot--${tone}"></span>
            <span>${escapeHtml(worker?.name || 'Tho ky thuat')} • ${escapeHtml(worker?.map_status_label || 'Dang cap nhat')}</span>
        </div>
    `;
}

function selectWorkerMapCard(worker) {
    if (!worker) {
        state.workerMap.selectedWorkerId = null;
        setText(elements.workerMapInfoName, 'Di chuot vao avatar tho');
        setText(elements.workerMapInfoDetail, 'Hover vao avatar tren ban do de xem nhanh tinh trang cua tung tho.');
        setText(elements.workerMapInfoRating, 'Chua co danh gia');
        setText(elements.workerMapInfoServices, 'Chua co nhom dich vu');
        setText(elements.workerMapInfoSchedule, 'Chua co lich dang mo');
        setText(elements.workerMapInfoArea, 'Chua co khu vuc');

        if (elements.workerMapInfoStatus) {
            elements.workerMapInfoStatus.textContent = 'Trong lich';
            elements.workerMapInfoStatus.className = 'adm-map-info__status adm-map-info__status--free';
        }

        return;
    }

    state.workerMap.selectedWorkerId = worker.id ?? null;
    const tone = sanitizeWorkerMapTone(worker?.map_tone);

    setText(elements.workerMapInfoName, worker?.name || 'Tho ky thuat');
    setText(elements.workerMapInfoDetail, worker?.status_detail || 'Dang cap nhat tinh trang tho.');
    setText(elements.workerMapInfoRating, worker?.rating_label || 'Chua co danh gia');
    setText(elements.workerMapInfoServices, worker?.services_label || 'Chua co nhom dich vu');
    setText(elements.workerMapInfoSchedule, worker?.schedule_label || 'Chua co lich dang mo');
    setText(elements.workerMapInfoArea, worker?.area_label || 'Chua co khu vuc');

    if (elements.workerMapInfoStatus) {
        elements.workerMapInfoStatus.textContent = worker?.map_status_label || 'Dang cap nhat';
        elements.workerMapInfoStatus.className = `adm-map-info__status adm-map-info__status--${tone}`;
    }
}

function setWorkerMapEmptyState(isEmpty, title, copy) {
    if (!elements.workerMapEmptyState) {
        return;
    }

    elements.workerMapEmptyState.classList.toggle('is-hidden', !isEmpty);

    if (!isEmpty) {
        return;
    }

    const titleEl = elements.workerMapEmptyState.querySelector('strong');
    const copyEl = elements.workerMapEmptyState.querySelector('p');

    if (titleEl) {
        titleEl.textContent = title;
    }

    if (copyEl) {
        copyEl.textContent = copy;
    }
}

function renderSummary(summary) {
    setText(elements.summaryRevenueToday, formatMoney(summary.revenue_today?.value));
    setText(elements.summaryRevenueNote, summary.revenue_today?.note ?? '0%');
    setTone(elements.summaryRevenueNote, summary.revenue_today?.change_percent >= 0 ? 'tone-positive' : 'tone-danger');

    setText(elements.summaryBookingsToday, formatNumber(summary.bookings_today?.value));
    setText(elements.summaryBookingsNote, summary.bookings_today?.note ?? 'Stable');
    setTone(elements.summaryBookingsNote, Number(summary.bookings_today?.value || 0) > 0 ? 'tone-warning' : 'tone-muted');

    setText(elements.summaryCommission, formatMoney(summary.commission?.value));
    setText(elements.summaryCommissionNote, summary.commission?.note ?? '0%');
    setTone(elements.summaryCommissionNote, 'tone-positive');

    setText(elements.summaryComplaints, formatNumber(summary.complaints?.value));
    setText(elements.summaryComplaintsNote, summary.complaints?.note ?? '0');
    setTone(elements.summaryComplaintsNote, Number(summary.complaints?.value || 0) > 0 ? 'tone-danger' : 'tone-muted');
}

function renderRevenueSection(meta, revenue) {
    const transferShare = Number(revenue.transfer_share || 0);

    setText(elements.metaUpdatedAt, `Cap nhat ${meta.updated_at ?? '--:--'}`);
    setText(elements.metaPeriodLabel, meta.period_label ?? 'hom nay');
    setText(elements.revenuePeriodTotal, formatMoney(revenue.total_revenue));
    setText(elements.revenuePeriodNote, revenue.change_note ?? '0%');
    setTone(elements.revenuePeriodNote, (revenue.change_percent ?? 0) >= 0 ? 'tone-positive' : 'tone-danger');
    setText(elements.revenueTopService, revenue.top_service ?? 'Chua co du lieu');
    setText(elements.revenueTransferShare, `${formatNumber(transferShare)}% doanh thu`);
    setText(elements.revenueTransferPercent, `${formatNumber(transferShare)}%`);

    if (elements.revenueTransferDonut) {
        elements.revenueTransferDonut.style.setProperty('--p', `${Math.max(0, Math.min(360, transferShare * 3.6))}deg`);
    }

    renderRevenueChart(revenue.trend ?? [], revenue.change_percent ?? 0);
}

function renderRevenueChart(trend, changePercent = 0) {
    if (!elements.revenueChart || !elements.revenueChartLabels) {
        return;
    }

    const points = Array.isArray(trend) && trend.length
        ? trend.map((item) => ({
            label: item.label ?? '',
            value: Number(item.value) || 0,
        }))
        : [{ label: '00h', value: 0 }];
    const width = 720;
    const height = 268;
    const padding = { top: 18, right: 18, bottom: 24, left: 18 };
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;
    const maxValue = Math.max(...points.map((item) => item.value), 1);
    const baselineY = padding.top + chartHeight;
    const segmentCount = Math.max(points.length - 1, 1);
    const isNegativeTrend = Number(changePercent) < 0;
    const strokeColor = isNegativeTrend ? '#ef4444' : '#1b6ce3';
    const gradientStart = isNegativeTrend ? 'rgba(239,68,68,0.18)' : 'rgba(27,108,227,0.18)';

    const plottedPoints = points.map((item, index) => {
        const x = points.length === 1
            ? padding.left + chartWidth / 2
            : padding.left + (index / segmentCount) * chartWidth;
        const y = baselineY - (item.value / maxValue) * chartHeight;

        return {
            ...item,
            x,
            y,
        };
    });

    const grid = Array.from({ length: 4 }, (_, index) => {
        const ratio = index / 3;
        const y = padding.top + chartHeight * ratio;
        const value = maxValue * (1 - ratio);

        return `
            <g>
                <line x1="${padding.left}" y1="${y}" x2="${width - padding.right}" y2="${y}" stroke="rgba(219,227,238,0.95)" stroke-width="1"/>
                <text x="${width - padding.right}" y="${Math.max(14, y - 6)}" text-anchor="end" font-size="12" font-weight="700" fill="#9aa7bb">
                    ${formatCompactMoney(value)}
                </text>
            </g>
        `;
    }).join('');

    const linePath = plottedPoints
        .map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x.toFixed(2)} ${point.y.toFixed(2)}`)
        .join(' ');

    const firstPoint = plottedPoints[0];
    const lastPoint = plottedPoints[plottedPoints.length - 1];
    const areaPath = `${linePath} L ${lastPoint.x.toFixed(2)} ${baselineY.toFixed(2)} L ${firstPoint.x.toFixed(2)} ${baselineY.toFixed(2)} Z`;
    const markers = plottedPoints
        .map((point, index) => {
            const isLastPoint = index === plottedPoints.length - 1;

            return `
                <circle cx="${point.x.toFixed(2)}" cy="${point.y.toFixed(2)}" r="${isLastPoint ? 6 : 4}" fill="${isLastPoint ? strokeColor : '#ffffff'}" stroke="${strokeColor}" stroke-width="${isLastPoint ? 4 : 3}"/>
            `;
        })
        .join('');

    elements.revenueChart.innerHTML = `
        <defs>
            <linearGradient id="admRevenueArea" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="${gradientStart}"/>
                <stop offset="100%" stop-color="rgba(255,255,255,0)"/>
            </linearGradient>
            <filter id="admRevenueGlow" x="-20%" y="-20%" width="140%" height="140%">
                <feDropShadow dx="0" dy="10" stdDeviation="12" flood-color="${strokeColor}" flood-opacity="0.14"/>
            </filter>
        </defs>
        ${grid}
        <rect x="${padding.left}" y="${padding.top}" width="${chartWidth}" height="${chartHeight}" rx="24" fill="rgba(247,249,253,0.45)"/>
        <path d="${areaPath}" fill="url(#admRevenueArea)"/>
        <path d="${linePath}" fill="none" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" filter="url(#admRevenueGlow)"/>
        ${markers}
    `;

    const labelStep = points.length > 12 ? Math.ceil(points.length / 8) : 1;
    elements.revenueChartLabels.style.gridTemplateColumns = `repeat(${points.length}, minmax(0, 1fr))`;
    elements.revenueChartLabels.innerHTML = points
        .map((item, index) => {
            const shouldShowLabel = index === 0 || index === points.length - 1 || index % labelStep === 0;
            return `<span class="${shouldShowLabel ? '' : 'is-ghost'}">${shouldShowLabel ? escapeHtml(item.label ?? '') : '.'}</span>`;
        })
        .join('');
}

function renderAlerts(alerts) {
    const items = Array.isArray(alerts.items) ? alerts.items : [];

    if (!elements.alertList) {
        return;
    }

    if (!items.length) {
        elements.alertList.innerHTML = `
            <div class="adm-priority-item adm-priority-item--info">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <h4>Khong co canh bao moi</h4>
                    <p>Ca hien tai chua co tin hieu can tang muc xu ly them.</p>
                </div>
            </div>
        `;
    } else {
        const iconMap = { warning: 'fa-triangle-exclamation', danger: 'fa-circle-xmark', info: 'fa-circle-info' };
        elements.alertList.innerHTML = items.map((item) => {
            const tone = escapeClass(item.tone);
            return `
                <div class="adm-priority-item adm-priority-item--${tone}">
                    <i class="fa-solid ${iconMap[tone] || iconMap.info}"></i>
                    <div>
                        <h4>[${escapeHtml(item.priority ?? 'P2')}] ${escapeHtml(item.title ?? 'Tac vu')}</h4>
                        <p>${escapeHtml(item.detail ?? '')}</p>
                    </div>
                </div>
            `;
        }).join('');
    }

    setText(elements.alertFooter, alerts.footer ?? 'Cap nhat moi nhat tu du lieu dashboard admin');
}

function renderBookings(bookings) {
    setText(elements.bookingsTodayTotal, formatNumber(bookings.today));
    setText(elements.bookingsPendingTotal, formatNumber(bookings.today_pending));
    setText(elements.bookingsProgressTotal, formatNumber(bookings.today_in_progress));
    setText(elements.bookingsCompletedTotal, formatNumber(bookings.today_completed));

    if (!elements.bookingQueueList) {
        return;
    }

    const queue = Array.isArray(bookings.queue) ? bookings.queue : [];
    elements.bookingQueueList.innerHTML = queue.length
        ? queue.map((item) => {
            const tone = escapeClass(item.tone);
            return `
                <div class="adm-queue-item adm-queue-item--${tone}">
                    <h4>${escapeHtml(item.label ?? '')}</h4>
                    <p>${escapeHtml(item.detail ?? '')}</p>
                </div>
            `;
        }).join('')
        : '<div class="adm-queue-item adm-queue-item--info"><h4>Khong co don can can thiep them</h4></div>';
}

function renderWorkers(workers) {
    setText(elements.workersTotal, formatNumber(workers.total));
    setText(elements.workersActive, formatNumber(workers.active));
    setText(elements.workersPending, formatNumber(workers.pending_approval));
    setText(elements.workersLowRating, formatNumber(workers.low_rating));

    if (!elements.workerWatchList) {
        return;
    }

    const watchItems = Array.isArray(workers.watch_items) ? workers.watch_items : [];
    elements.workerWatchList.innerHTML = watchItems.length
        ? watchItems.map((item) => `<div>${escapeHtml(item)}</div>`).join('')
        : '<div>Chua co tin hieu bat thuong o doi tho.</div>';
}

function renderRevenueTable(rows) {
    if (!elements.revenueTableBody) {
        return;
    }

    const items = Array.isArray(rows) ? rows : [];
    if (!items.length) {
        elements.revenueTableBody.innerHTML = '<tr><td colspan="5" class="adm-empty">Chua co du lieu doanh thu trong khoang da chon.</td></tr>';
        return;
    }

    elements.revenueTableBody.innerHTML = items.map((row) => `
        <tr>
            <td>
                <span class="adm-code">${escapeHtml(row.booking_code ?? '--')}</span>
                <span class="adm-note">${escapeHtml(row.service_name ?? 'Dich vu chua gan')}</span>
            </td>
            <td>${escapeHtml(row.date_label ?? '--')}</td>
            <td><span class="adm-money">${formatMoney(row.total_amount)}</span></td>
            <td><span class="adm-money">${formatMoney(row.commission_amount)}</span></td>
            <td><span class="adm-tag">Hoan tat</span></td>
        </tr>
    `).join('');
}

function renderComplaints(complaints) {
    setText(elements.complaintsNew, formatNumber(complaints.new));
    setText(elements.complaintsLowRating, formatNumber(complaints.low_rating));
    setText(elements.complaintsCanceled, formatNumber(complaints.canceled));

    if (!elements.complaintList) {
        return;
    }

    const items = Array.isArray(complaints.items) ? complaints.items : [];
    elements.complaintList.innerHTML = items.length
        ? items.map((item) => `
            <div class="adm-feedback-item">
                <i></i>
                <div>
                    <h4>${escapeHtml(item.booking_code ?? item.date ?? 'Phan anh')}</h4>
                    <p>${escapeHtml(item.summary ?? '')}</p>
                </div>
            </div>
        `).join('')
        : `
            <div class="adm-feedback-item">
                <i></i>
                <div>
                    <h4>He thong on dinh</h4>
                    <p>Chua co phan anh moi trong khoang thoi gian da chon.</p>
                </div>
            </div>
        `;
}

function renderErrorState() {
    setText(elements.metaUpdatedAt, 'Loi tai du lieu');
    setText(elements.workerMapStatus, 'Khong the cap nhat ban do doi tho');
    if (state.workerMap.markersLayer) {
        state.workerMap.markersLayer.clearLayers();
    }
    setWorkerMapEmptyState(
        true,
        'Khong tai duoc du lieu ban do',
        'Kiem tra API dashboard admin hoac thu dong bo lai trang de tai vi tri tho.'
    );
    if (elements.alertList) {
        elements.alertList.innerHTML = `
            <div class="adm-priority-item adm-priority-item--danger">
                <i class="fa-solid fa-circle-xmark"></i>
                <div>
                    <h4>[P1] Khong tai duoc dashboard</h4>
                    <p>Kiem tra API hoac thu dong bo lai trang de cap nhat du lieu.</p>
                </div>
            </div>
        `;
    }
}

function setText(element, value) {
    if (element) {
        element.textContent = value ?? '--';
    }
}

function setTone(element, toneClass) {
    if (!element) {
        return;
    }

    element.classList.remove('tone-positive', 'tone-warning', 'tone-danger', 'tone-muted');
    if (toneClass) {
        element.classList.add(toneClass);
    }
}

function formatMoney(value) {
    return `${formatNumber(value)} đ`;
}

function formatNumber(value) {
    return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(Number(value) || 0);
}

function formatCompactMoney(value) {
    return new Intl.NumberFormat('vi-VN', {
        notation: 'compact',
        compactDisplay: 'short',
        maximumFractionDigits: 1,
    }).format(Number(value) || 0);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function escapeClass(value) {
    return ['warning', 'info', 'danger'].includes(value) ? value : 'info';
}

function resolveWorkerMapAvatarUrl(avatar) {
    const normalizedAvatar = String(avatar ?? '').trim();

    if (!normalizedAvatar || normalizedAvatar === '/assets/images/user-default.png') {
        return '';
    }

    if (/^https?:\/\//i.test(normalizedAvatar) || normalizedAvatar.startsWith('/')) {
        return normalizedAvatar;
    }

    if (normalizedAvatar.startsWith('storage/')) {
        return `/${normalizedAvatar}`;
    }

    return `/storage/${normalizedAvatar.replace(/^\/+/, '')}`;
}

function getWorkerMapInitials(name) {
    const parts = String(name ?? '')
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    if (!parts.length) {
        return 'TK';
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return `${parts[0].charAt(0)}${parts[parts.length - 1].charAt(0)}`.toUpperCase();
}

function sanitizeWorkerMapTone(value) {
    return ['busy', 'scheduled', 'free', 'offline'].includes(value) ? value : 'scheduled';
}
