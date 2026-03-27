import { callApi, requireRole } from '../api.js';

const state = {
    period: '7d',
};

const elements = {};

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');
    cacheElements();
    bindEvents();
    syncPeriodButtons();
    fetchDashboard();
});

function cacheElements() {
    elements.root = document.getElementById('adminDashboard');
    elements.refreshButton = document.getElementById('btnRefresh');
    elements.periodButtons = Array.from(document.querySelectorAll('.js-period-btn'));
    elements.focusPendingBookingsValue = document.getElementById('focusPendingBookingsValue');
    elements.focusPendingBookingsNote = document.getElementById('focusPendingBookingsNote');
    elements.priorityComplaintChip = document.getElementById('priorityComplaintChip');
    elements.priorityWorkerChip = document.getElementById('priorityWorkerChip');
    elements.priorityRevenueChip = document.getElementById('priorityRevenueChip');
    elements.focusComplaintsValue = document.getElementById('focusComplaintsValue');
    elements.focusComplaintsNote = document.getElementById('focusComplaintsNote');
    elements.focusWorkersPendingValue = document.getElementById('focusWorkersPendingValue');
    elements.focusWorkersPendingNote = document.getElementById('focusWorkersPendingNote');
    elements.focusRevenueValue = document.getElementById('focusRevenueValue');
    elements.focusRevenueNote = document.getElementById('focusRevenueNote');

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
}

function syncPeriodButtons() {
    elements.periodButtons.forEach((button) => {
        button.classList.toggle('is-active', button.dataset.period === state.period);
    });
}

async function fetchDashboard() {
    setLoading(true);

    try {
        const response = await callApi(`/admin/dashboard?period=${state.period}`, 'GET');

        if (!response?.ok || !response.data?.data) {
            throw new Error('Không thể tải dữ liệu dashboard admin.');
        }

        renderDashboard(response.data.data);
    } catch (error) {
        console.error('Lỗi tải dashboard admin:', error);
        renderErrorState();
    } finally {
        setLoading(false);
    }
}

function setLoading(isLoading) {
    if (elements.root) {
        elements.root.classList.toggle('is-loading', isLoading);
    }

    if (elements.refreshButton) {
        elements.refreshButton.disabled = isLoading;
        elements.refreshButton.textContent = isLoading ? 'Đang tải...' : 'Làm mới';
    }
}

function renderDashboard(data) {
    renderPriorityFocus(
        data.summary ?? {},
        data.bookings ?? {},
        data.workers_summary ?? {},
        data.complaints ?? {}
    );
    renderSummary(data.summary ?? {});
    renderRevenueSection(data.meta ?? {}, data.revenue ?? {});
    renderAlerts(data.alerts ?? {});
    renderBookings(data.bookings ?? {});
    renderWorkers(data.workers_summary ?? {});
    renderRevenueTable(data.revenue_table ?? []);
    renderComplaints(data.complaints ?? {});
}

function renderPriorityFocus(summary, bookings, workers, complaints) {
    setText(elements.focusPendingBookingsValue, formatNumber(bookings.today_pending));
    setText(
        elements.focusPendingBookingsNote,
        Number(bookings.today_pending || 0) > 0
            ? 'đơn chờ xác nhận trong ngày'
            : 'không có đơn chờ xác nhận'
    );

    setText(elements.priorityComplaintChip, formatNumber(complaints.new));
    setText(elements.priorityWorkerChip, formatNumber(workers.pending_approval));
    setText(elements.priorityRevenueChip, formatMoney(summary.revenue_today?.value));

    setText(elements.focusComplaintsValue, formatNumber(complaints.new));
    setText(elements.focusComplaintsNote, summary.complaints?.note ?? '0 vụ mức ưu tiên cao');

    setText(elements.focusWorkersPendingValue, formatNumber(workers.pending_approval));
    setText(
        elements.focusWorkersPendingNote,
        Number(workers.pending_approval || 0) > 0
            ? `${formatNumber(workers.pending_approval)} hồ sơ đang chờ admin duyệt`
            : 'Không có hồ sơ đang chờ duyệt'
    );

    setText(elements.focusRevenueValue, formatMoney(summary.revenue_today?.value));
    setText(elements.focusRevenueNote, summary.revenue_today?.note ?? '0% so với hôm qua');
}

function renderSummary(summary) {
    setText(elements.summaryRevenueToday, formatMoney(summary.revenue_today?.value));
    setText(elements.summaryRevenueNote, summary.revenue_today?.note ?? '0% so với hôm qua');
    setTone(elements.summaryRevenueNote, summary.revenue_today?.change_percent >= 0 ? 'tone-positive' : 'tone-danger');

    setText(elements.summaryBookingsToday, formatNumber(summary.bookings_today?.value));
    setText(elements.summaryBookingsNote, summary.bookings_today?.note ?? '0 đơn cần xác nhận');
    setTone(elements.summaryBookingsNote, 'tone-warning');

    setText(elements.summaryCommission, formatMoney(summary.commission?.value));
    setText(elements.summaryCommissionNote, summary.commission?.note ?? '0% giao dịch chuyển khoản');
    setTone(elements.summaryCommissionNote, 'tone-muted');

    setText(elements.summaryComplaints, formatNumber(summary.complaints?.value));
    setText(elements.summaryComplaintsNote, summary.complaints?.note ?? '0 vụ mức ưu tiên cao');
    setTone(elements.summaryComplaintsNote, 'tone-danger');
}

function renderRevenueSection(meta, revenue) {
    setText(elements.metaUpdatedAt, `Cập nhật ${meta.updated_at ?? '--:--'}`);
    setText(elements.metaPeriodLabel, meta.period_label ?? '7 ngày');
    setText(elements.revenuePeriodTotal, formatMoney(revenue.total_revenue));
    setText(elements.revenuePeriodNote, revenue.change_note ?? '0% so với kỳ trước');
    setTone(elements.revenuePeriodNote, (revenue.change_percent ?? 0) >= 0 ? 'tone-positive' : 'tone-danger');
    setText(elements.revenueTopService, revenue.top_service ?? 'Chưa có dữ liệu');
    setText(elements.revenueTransferShare, `${formatNumber(revenue.transfer_share)}% doanh thu đến từ chuyển khoản`);
    renderRevenueChart(revenue.trend ?? []);
}

function renderRevenueChart(trend) {
    if (!elements.revenueChart || !elements.revenueChartLabels) {
        return;
    }

    const points = Array.isArray(trend) && trend.length > 0
        ? trend
        : [{ label: 'T2', value: 0 }];

    const width = 640;
    const height = 220;
    const padding = { top: 18, right: 24, bottom: 28, left: 18 };
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;
    const maxValue = Math.max(...points.map((item) => Number(item.value) || 0), 1);

    const coordinates = points.map((item, index) => {
        const x = padding.left + (points.length === 1 ? chartWidth / 2 : (chartWidth / (points.length - 1)) * index);
        const y = padding.top + chartHeight - ((Number(item.value) || 0) / maxValue) * chartHeight;
        return { x, y, value: Number(item.value) || 0 };
    });

    const linePoints = coordinates.map((point) => `${point.x},${point.y}`).join(' ');
    const areaPoints = `${padding.left},${height - padding.bottom} ${linePoints} ${padding.left + chartWidth},${height - padding.bottom}`;
    const peakValue = Math.max(...coordinates.map((point) => point.value), 0);

    const gridLines = Array.from({ length: 4 }, (_, index) => {
        const y = padding.top + (chartHeight / 3) * index;
        return `<line x1="${padding.left}" y1="${y}" x2="${padding.left + chartWidth}" y2="${y}" stroke="rgba(148,163,184,0.18)" stroke-width="1" />`;
    }).join('');

    const circles = coordinates.map((point) => {
        const isPeak = point.value === peakValue && peakValue > 0;
        return `
            <circle cx="${point.x}" cy="${point.y}" r="${isPeak ? 8 : 6.5}" fill="${isPeak ? 'rgba(249,115,22,0.12)' : 'rgba(59,130,246,0.12)'}" />
            <circle cx="${point.x}" cy="${point.y}" r="${isPeak ? 4.4 : 3.6}" fill="${isPeak ? '#f97316' : '#3b82f6'}" />
        `;
    }).join('');

    elements.revenueChart.innerHTML = `
        <defs>
            <linearGradient id="dashboardRevenueArea" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0%" stop-color="rgba(59,130,246,0.18)" />
                <stop offset="100%" stop-color="rgba(59,130,246,0.02)" />
            </linearGradient>
        </defs>
        ${gridLines}
        <polygon points="${areaPoints}" fill="url(#dashboardRevenueArea)"></polygon>
        <polyline points="${linePoints}" fill="none" stroke="#3b82f6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
        ${circles}
    `;

    elements.revenueChartLabels.innerHTML = points
        .map((item) => `<span>${escapeHtml(item.label ?? '')}</span>`)
        .join('');
}

function renderAlerts(alerts) {
    const items = Array.isArray(alerts.items) ? alerts.items : [];

    if (!items.length) {
        elements.alertList.innerHTML = `
            <div class="priority-item info">
                <div class="priority-top">
                    <span class="priority-tag">P2</span>
                    <h4 class="priority-title">Không có cảnh báo mới</h4>
                </div>
                <p class="priority-detail">Ca hiện tại chưa có tín hiệu cần tăng mức xử lý thêm.</p>
            </div>
        `;
    } else {
        elements.alertList.innerHTML = items.map((item) => `
            <div class="priority-item ${escapeClass(item.tone)}">
                <div class="priority-top">
                    <span class="priority-tag">${escapeHtml(item.priority ?? 'P2')}</span>
                    <h4 class="priority-title">${escapeHtml(item.title ?? 'Tác vụ')}</h4>
                </div>
                <p class="priority-detail">${escapeHtml(item.detail ?? '')}</p>
            </div>
        `).join('');
    }

    setText(elements.alertFooter, alerts.footer ?? 'Cập nhật mới nhất từ dữ liệu dashboard admin');
}

function renderBookings(bookings) {
    setText(elements.bookingsTodayTotal, formatNumber(bookings.today));
    setText(elements.bookingsPendingTotal, formatNumber(bookings.today_pending));
    setText(elements.bookingsProgressTotal, formatNumber(bookings.today_in_progress));
    setText(elements.bookingsCompletedTotal, formatNumber(bookings.today_completed));

    const queue = Array.isArray(bookings.queue) ? bookings.queue : [];
    elements.bookingQueueList.innerHTML = queue.length
        ? queue.map((item) => `<div class="queue-item ${escapeClass(item.tone)}">${escapeHtml(item.label ?? '')}</div>`).join('')
        : '<div class="queue-item info">Chưa có đơn nào cần can thiệp thêm trong ca này.</div>';
}

function renderWorkers(workers) {
    setText(elements.workersTotal, formatNumber(workers.total));
    setText(elements.workersActive, formatNumber(workers.active));
    setText(elements.workersPending, formatNumber(workers.pending_approval));
    setText(elements.workersLowRating, formatNumber(workers.low_rating));

    const watchItems = Array.isArray(workers.watch_items) ? workers.watch_items : [];
    elements.workerWatchList.innerHTML = watchItems.length
        ? watchItems.map((item) => `<div class="watch-item">${escapeHtml(item)}</div>`).join('')
        : '<div class="watch-item">Chưa có tín hiệu bất thường ở đội thợ.</div>';
}

function renderRevenueTable(rows) {
    const items = Array.isArray(rows) ? rows : [];

    if (!items.length) {
        elements.revenueTableBody.innerHTML = `
            <tr>
                <td colspan="4" class="admin-empty">Chưa có dữ liệu doanh thu trong khoảng đã chọn.</td>
            </tr>
        `;
        return;
    }

    elements.revenueTableBody.innerHTML = items.map((row) => `
        <tr>
            <td>
                <span class="table-code">${escapeHtml(row.booking_code ?? '--')}</span>
                <span class="table-note">${escapeHtml(row.service_name ?? 'Dịch vụ chưa gán')}</span>
            </td>
            <td>${escapeHtml(row.date_label ?? '--')}</td>
            <td><span class="table-money">${formatMoney(row.total_amount)}</span></td>
            <td><span class="table-money table-money--warm">${formatMoney(row.commission_amount)}</span></td>
        </tr>
    `).join('');
}

function renderComplaints(complaints) {
    setText(elements.complaintsNew, formatNumber(complaints.new));
    setText(elements.complaintsLowRating, formatNumber(complaints.low_rating));
    setText(elements.complaintsCanceled, formatNumber(complaints.canceled));

    const items = Array.isArray(complaints.items) ? complaints.items : [];
    elements.complaintList.innerHTML = items.length
        ? items.map((item) => `
            <div class="complaint-item ${escapeClass(item.tone)}">
                <div class="complaint-item__code">${escapeHtml(item.date ?? '--')} · ${escapeHtml(item.booking_code ?? '--')}</div>
                <p class="complaint-item__summary">${escapeHtml(item.summary ?? '')}</p>
            </div>
        `).join('')
        : `
            <div class="complaint-item danger">
                <div class="complaint-item__code">Ổn định</div>
                <p class="complaint-item__summary">Chưa có phản ánh mới trong khoảng thời gian đã chọn.</p>
            </div>
        `;
}

function renderErrorState() {
    setText(elements.metaUpdatedAt, 'Lỗi tải dữ liệu');
    elements.alertList.innerHTML = `
        <div class="priority-item danger">
            <div class="priority-top">
                <span class="priority-tag">P1</span>
                <h4 class="priority-title">Không tải được dashboard</h4>
            </div>
            <p class="priority-detail">Kiểm tra API hoặc thử làm mới lại trang để đồng bộ dữ liệu.</p>
        </div>
    `;
}

function setText(element, value) {
    if (!element) {
        return;
    }

    element.textContent = value ?? '--';
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
    return new Intl.NumberFormat('vi-VN', {
        maximumFractionDigits: 0,
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
