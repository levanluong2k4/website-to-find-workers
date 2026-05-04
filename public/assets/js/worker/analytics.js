import { callApi, getCurrentUser, showToast } from '../api.js';

const LOGIN_URL = `${window.location.origin}/login?role=worker`;
const PERIODS = ['today', '7d', '30d', 'month', 'prev-month', 'all', 'custom'];
const state = {
  bookings: [],
  visibleBookings: [],
  wallet: { so_du: 0, da_rut_trong_ky: 0 },
  settings: { tax_rate: 10, fee_rate: 20 },
  period: '30d',
  customStart: '',
  customEnd: '',
  filters: {
    search: '',
    status: 'all',
  },
  chart: null,
};

const dom = {};

document.addEventListener('DOMContentLoaded', () => {
  const user = getCurrentUser();
  if (!user || !['worker', 'admin'].includes(user.role)) {
    window.location.href = LOGIN_URL;
    return;
  }

  cacheDom();
  bindEvents();
  loadRevenueWorkspace().catch((error) => {
    console.error('Revenue workspace load failed:', error);
    renderWorkspaceError(error.message || 'Không thể tải dữ liệu thu nhập.');
  });
});

function cacheDom() {
  dom.periodButtons = Array.from(document.querySelectorAll('.period-tab'));
  dom.customRange = document.getElementById('customRange');
  dom.customStartDate = document.getElementById('customStartDate');
  dom.customEndDate = document.getElementById('customEndDate');
  dom.applyCustomRangeButton = document.getElementById('applyCustomRangeButton');
  dom.exportRevenueCsvButton = document.getElementById('exportRevenueCsvButton');
  dom.periodSummaryPill = document.getElementById('periodSummaryPill');
  
  dom.heroCollectedAmount = document.getElementById('heroCollectedAmount');
  dom.heroPendingAmount = document.getElementById('heroPendingAmount');
  dom.heroGrossAmount = document.getElementById('heroGrossAmount');
  dom.heroCancelledAmount = document.getElementById('heroCancelledAmount');
  dom.heroInsight = document.getElementById('heroInsight');
  
  dom.summaryTotalTax = document.getElementById('summaryTotalTax');
  dom.summaryTotalFee = document.getElementById('summaryTotalFee');
  dom.summaryNetRatio = document.getElementById('summaryNetRatio');
  dom.summaryWalletBalance = document.getElementById('summaryWalletBalance');
  
  dom.revenueTrendChart = document.getElementById('revenueTrendChart');
  dom.revenueTrendEmpty = document.getElementById('revenueTrendEmpty');
  
  dom.kickerAverageNet = document.getElementById('kickerAverageNet');
  dom.kickerBookingCount = document.getElementById('kickerBookingCount');
  dom.incomeSplitRows = document.getElementById('incomeSplitRows');
  
  dom.revenueSearchInput = document.getElementById('revenueSearchInput');
  dom.statusFilter = document.getElementById('statusFilter');
  dom.revenueTableBody = document.getElementById('revenueTableBody');
  
  dom.revenueDrawer = document.getElementById('revenueDrawer');
  dom.revenueDrawerOverlay = document.getElementById('revenueDrawerOverlay');
  dom.revenueDrawerClose = document.getElementById('revenueDrawerClose');
  dom.drawerTitle = document.getElementById('drawerTitle');
  dom.drawerSubtitle = document.getElementById('drawerSubtitle');
  dom.revenueDrawerBody = document.getElementById('revenueDrawerBody');
}

function bindEvents() {
  dom.periodButtons.forEach((button) => {
    button.addEventListener('click', () => setActivePeriod(button.dataset.range || '30d'));
  });

  dom.applyCustomRangeButton?.addEventListener('click', () => {
    state.customStart = dom.customStartDate?.value || '';
    state.customEnd = dom.customEndDate?.value || '';
    state.period = 'custom';
    syncPeriodButtons();
    renderWorkspace();
  });

  dom.revenueSearchInput?.addEventListener('input', (event) => {
    state.filters.search = event.target.value || '';
    renderWorkspace();
  });
  
  dom.statusFilter?.addEventListener('change', (event) => {
    state.filters.status = event.target.value || 'all';
    renderWorkspace();
  });
  
  dom.revenueDrawerClose?.addEventListener('click', closeDrawer);
  dom.revenueDrawerOverlay?.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeDrawer();
  });
}

async function loadRevenueWorkspace() {
  renderLoadingState();
  const [bookings, statsResponse] = await Promise.all([
    fetchAllBookings(),
    callApi('/worker/stats', 'GET')
  ]);
  
  state.bookings = bookings;
  if (statsResponse.ok && statsResponse.data) {
      if (statsResponse.data.wallet) state.wallet = statsResponse.data.wallet;
      if (statsResponse.data.settings) state.settings = statsResponse.data.settings;
  }
  
  seedCustomRangeInputs();
  renderWorkspace();
}

async function fetchAllBookings() {
  const collected = [];
  let page = 1;
  let lastPage = 1;

  do {
    const response = await callApi(`/don-dat-lich?per_page=100&page=${page}`, 'GET');
    if (!response.ok) throw new Error(response.data?.message || 'Không thể tải danh sách đơn.');
    
    const payload = response.data || {};
    const items = Array.isArray(payload.data) ? payload.data : [];
    collected.push(...items);
    lastPage = Math.max(1, Number(payload.last_page || 1));
    page += 1;
  } while (page <= lastPage);

  return collected;
}

function getNumeric(value) {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
}

function formatMoney(value) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(getNumeric(value));
}

function formatPercent(value) {
  return `${Math.round(getNumeric(value))}%`;
}

function toDateInputValue(date) {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function parseDate(value) {
  if (!value) return null;
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? null : date;
}

function startOfDay(date) {
  const clone = new Date(date);
  clone.setHours(0, 0, 0, 0);
  return clone;
}

function endOfDay(date) {
  const clone = new Date(date);
  clone.setHours(23, 59, 59, 999);
  return clone;
}

function daysBetween(start, end) {
  const distance = endOfDay(end).getTime() - startOfDay(start).getTime();
  return Math.max(1, Math.round(distance / 86400000) + 1);
}

function getBookingDate(booking) {
  return parseDate(booking?.thoi_gian_hoan_thanh || booking?.ngay_hen || booking?.created_at);
}

function formatDateLabel(booking) {
  const date = getBookingDate(booking);
  if (!date) return 'Chưa xác định';
  return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function getCustomerName(booking) {
  return booking?.khach_hang?.name || 'Khách hàng';
}

function getServiceNames(booking) {
  const names = Array.isArray(booking?.dich_vus) ? booking.dich_vus.map((service) => service?.ten_dich_vu).filter(Boolean) : [];
  return names.length ? names : ['Dịch vụ'];
}

function getServiceLabel(booking) {
  return getServiceNames(booking).join(', ');
}

// Income calculations
function calculateIncome(booking) {
    const isCompleted = booking?.trang_thai === 'da_xong';
    const isCancelled = booking?.trang_thai === 'da_huy';
    
    // Tiền công gộp
    const explicitLabor = getNumeric(booking?.tien_cong);
    const laborItems = Array.isArray(booking?.chi_tiet_tien_cong) ? booking.chi_tiet_tien_cong.filter(Boolean) : [];
    let grossWage = explicitLabor;
    if (laborItems.length) {
        grossWage = laborItems.reduce((total, item) => total + getNumeric(item?.so_tien), 0);
    }
    
    // Nếu chưa có tiền công và chưa hủy, ước tính dựa trên dịch vụ hoặc 0
    if (grossWage === 0 && !isCancelled && booking?.dich_vus?.length) {
        // Có thể để 0 cho đến khi cập nhật chi phí
    }
    
    const taxRate = state.settings.tax_rate / 100;
    const feeRate = state.settings.fee_rate / 100;
    
    const tax = grossWage * taxRate;
    const fee = grossWage * feeRate;
    const net = grossWage - tax - fee;
    
    const isPaid = Boolean(booking?.trang_thai_thanh_toan) && isCompleted;
    const isPending = !isCancelled && (!isCompleted || !isPaid) && grossWage > 0;
    
    return {
        gross: grossWage,
        tax: tax,
        fee: fee,
        net: net,
        isCompleted,
        isCancelled,
        isPaid,
        isPending
    };
}

function getStatusMeta(income) {
  if (income.isCancelled) return { label: 'Đã hủy', tone: 'cancelled' };
  if (income.isPaid) return { label: 'Đã cộng ví', tone: 'paid' };
  return { label: 'Chờ cộng ví', tone: 'pending' };
}

function escapeHtml(value) {
  return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function getPeriodRange() {
  const now = new Date();
  const today = endOfDay(now);

  if (state.period === 'today') return { key: 'today', start: startOfDay(now), end: today, label: 'Hôm nay' };
  if (state.period === '7d') return { key: '7d', start: startOfDay(new Date(now.getFullYear(), now.getMonth(), now.getDate() - 6)), end: today, label: '7 ngày gần đây' };
  if (state.period === 'month') return { key: 'month', start: startOfDay(new Date(now.getFullYear(), now.getMonth(), 1)), end: today, label: 'Tháng này' };
  if (state.period === 'prev-month') return { key: 'prev-month', start: startOfDay(new Date(now.getFullYear(), now.getMonth() - 1, 1)), end: endOfDay(new Date(now.getFullYear(), now.getMonth(), 0)), label: 'Tháng trước' };
  if (state.period === 'all') return { key: 'all', start: null, end: null, label: 'Toàn bộ dữ liệu' };
  if (state.period === 'custom') {
    const customStart = parseDate(state.customStart);
    const customEnd = parseDate(state.customEnd);
    return { key: 'custom', start: customStart ? startOfDay(customStart) : null, end: customEnd ? endOfDay(customEnd) : null, label: 'Khoảng tùy chọn' };
  }
  return { key: '30d', start: startOfDay(new Date(now.getFullYear(), now.getMonth(), now.getDate() - 29)), end: today, label: '30 ngày gần đây' };
}

function seedCustomRangeInputs() {
    const dates = state.bookings.map(getBookingDate).filter(Boolean).sort((a, b) => a.getTime() - b.getTime());
    if (!dates.length) return;
    const min = toDateInputValue(dates[0]);
    const max = toDateInputValue(dates[dates.length - 1]);
    if (dom.customStartDate) { dom.customStartDate.min = min; dom.customStartDate.max = max; }
    if (dom.customEndDate) { dom.customEndDate.min = min; dom.customEndDate.max = max; }
    if (!state.customStart) state.customStart = min;
    if (!state.customEnd) state.customEnd = max;
    if (dom.customStartDate && !dom.customStartDate.value) dom.customStartDate.value = state.customStart;
    if (dom.customEndDate && !dom.customEndDate.value) dom.customEndDate.value = state.customEnd;
}

function setActivePeriod(period) {
  state.period = PERIODS.includes(period) ? period : '30d';
  syncPeriodButtons();
  renderWorkspace();
}

function syncPeriodButtons() {
  dom.periodButtons.forEach((button) => button.classList.toggle('is-active', button.dataset.range === state.period));
  dom.customRange?.classList.toggle('is-visible', state.period === 'custom');
}

function renderLoadingState() {
  if (dom.revenueTableBody) dom.revenueTableBody.innerHTML = `<tr><td colspan="10"><div class="revenue-state"><strong>Đang tải dữ liệu</strong> Vui lòng chờ trong giây lát...</div></td></tr>`;
}

function renderWorkspaceError(message) {
  const content = `<tr><td colspan="10"><div class="revenue-state"><strong>Lỗi dữ liệu</strong> ${escapeHtml(message)}</div></td></tr>`;
  if (dom.revenueTableBody) dom.revenueTableBody.innerHTML = content;
}

function filterBookingsByPeriod(bookings) {
  const range = getPeriodRange();
  if (!range.start || !range.end) return bookings.slice();
  return bookings.filter((booking) => {
    const date = getBookingDate(booking);
    return date && date.getTime() >= range.start.getTime() && date.getTime() <= range.end.getTime();
  });
}

function applyTableFilters(bookings) {
  const search = state.filters.search.trim().toLowerCase();
  return bookings.filter((booking) => {
    const income = calculateIncome(booking);
    const haystack = [`#${booking?.id || ''}`, getCustomerName(booking), getServiceLabel(booking)].join(' ').toLowerCase();
    
    if (search && !haystack.includes(search)) return false;
    if (state.filters.status === 'paid' && !income.isPaid) return false;
    if (state.filters.status === 'pending' && (!income.isPending || income.isPaid)) return false;
    if (state.filters.status === 'cancelled' && !income.isCancelled) return false;
    
    return true;
  }).sort((left, right) => {
    const leftTime = getBookingDate(left)?.getTime() || 0;
    const rightTime = getBookingDate(right)?.getTime() || 0;
    return rightTime - leftTime;
  });
}

function buildMetrics(bookings) {
    let paidNet = 0;
    let pendingNet = 0;
    let totalGross = 0;
    let totalTax = 0;
    let totalFee = 0;
    let totalNet = 0;
    let paidCount = 0;
    let incomeCount = 0;
    
    bookings.forEach(booking => {
        const income = calculateIncome(booking);
        if (income.isCancelled || income.gross <= 0) return;
        
        incomeCount++;
        totalGross += income.gross;
        totalTax += income.tax;
        totalFee += income.fee;
        totalNet += income.net;
        
        if (income.isPaid) {
            paidNet += income.net;
            paidCount++;
        } else if (income.isPending) {
            pendingNet += income.net;
        }
    });
    
    const netRatio = totalGross > 0 ? (totalNet / totalGross) * 100 : 0;
    const averageNet = paidCount > 0 ? paidNet / paidCount : 0;

    return {
        paidNet,
        pendingNet,
        totalGross,
        totalTax,
        totalFee,
        totalNet,
        netRatio,
        averageNet,
        incomeCount,
        bookings
    };
}

function buildChartSeries(bookings) {
  const range = getPeriodRange();
  const monetized = bookings.filter(b => {
      const inc = calculateIncome(b);
      return !inc.isCancelled && inc.gross > 0;
  });
  
  if (!monetized.length) return { labels: [], gross: [], net: [], deduct: [] };

  const start = range.start || startOfDay(getBookingDate(monetized[0]) || new Date());
  const end = range.end || endOfDay(getBookingDate(monetized[monetized.length - 1]) || new Date());
  const totalDays = daysBetween(start, end);
  
  const labels = [];
  const gross = [];
  const net = [];
  const deduct = [];

  for (let offset = 0; offset < totalDays; offset += 1) {
    const cursor = startOfDay(new Date(start.getFullYear(), start.getMonth(), start.getDate() + offset));
    const key = toDateInputValue(cursor);
    labels.push(cursor.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' }));

    const dayBookings = monetized.filter((booking) => toDateInputValue(getBookingDate(booking)) === key);
    
    let dGross = 0, dNet = 0, dDeduct = 0;
    dayBookings.forEach(b => {
        const inc = calculateIncome(b);
        dGross += inc.gross;
        if (inc.isPaid) {
            dNet += inc.net;
            dDeduct += (inc.tax + inc.fee);
        }
    });
    
    gross.push(dGross);
    net.push(dNet);
    deduct.push(dDeduct);
  }

  return { labels, gross, net, deduct };
}

function renderWorkspace() {
  syncPeriodButtons();
  const scopedBookings = filterBookingsByPeriod(state.bookings);
  const metrics = buildMetrics(scopedBookings);
  state.visibleBookings = applyTableFilters(scopedBookings);

  renderHero(metrics);
  renderChart(scopedBookings);
  renderTable(state.visibleBookings);
}

function renderHero(metrics) {
  const range = getPeriodRange();
  if (dom.periodSummaryPill) {
    dom.periodSummaryPill.innerHTML = `<span class="material-symbols-outlined">date_range</span><span>${escapeHtml(range.label)}</span>`;
  }

  if (dom.heroCollectedAmount) dom.heroCollectedAmount.textContent = formatMoney(metrics.paidNet);
  if (dom.heroPendingAmount) dom.heroPendingAmount.textContent = formatMoney(metrics.pendingNet);
  if (dom.heroGrossAmount) dom.heroGrossAmount.textContent = formatMoney(metrics.totalGross);
  if (dom.heroCancelledAmount) dom.heroCancelledAmount.textContent = formatMoney(state.wallet.da_rut_trong_ky);

  if (dom.heroInsight) {
    dom.heroInsight.textContent = `Tháng này bạn đã thực nhận ${formatMoney(metrics.paidNet)} từ ${metrics.incomeCount} đơn hoàn thành.`;
  }

  if (dom.summaryTotalTax) dom.summaryTotalTax.textContent = formatMoney(metrics.totalTax);
  if (dom.summaryTotalFee) dom.summaryTotalFee.textContent = formatMoney(metrics.totalFee);
  if (dom.summaryNetRatio) dom.summaryNetRatio.textContent = formatPercent(metrics.netRatio);
  if (dom.summaryWalletBalance) dom.summaryWalletBalance.textContent = formatMoney(state.wallet.so_du);

  if (dom.kickerAverageNet) dom.kickerAverageNet.textContent = formatMoney(metrics.averageNet);
  if (dom.kickerBookingCount) dom.kickerBookingCount.textContent = `${metrics.bookings.length} đơn`;
  
  if (dom.incomeSplitRows) {
      dom.incomeSplitRows.innerHTML = `
        <div class="split-row">
            <div class="split-row__top"><span>Thực nhận</span><span>${formatMoney(metrics.totalNet)}</span></div>
            <div class="split-row__meta">${formatPercent(metrics.netRatio)}</div>
            <div class="split-row__bar"><div class="split-row__fill split-row__fill--indigo" style="width:${metrics.netRatio}%"></div></div>
        </div>
        <div class="split-row">
            <div class="split-row__top"><span>Thuế & Phí</span><span>${formatMoney(metrics.totalTax + metrics.totalFee)}</span></div>
            <div class="split-row__meta">${formatPercent(100 - metrics.netRatio)}</div>
            <div class="split-row__bar"><div class="split-row__fill split-row__fill--amber" style="width:${100 - metrics.netRatio}%"></div></div>
        </div>
      `;
  }
}

function renderChart(bookings) {
  if (!dom.revenueTrendChart || !dom.revenueTrendEmpty) return;
  const series = buildChartSeries(bookings);
  
  if (!series.labels.length) {
    dom.revenueTrendChart.style.display = 'none';
    dom.revenueTrendEmpty.style.display = 'flex';
    if (state.chart) { state.chart.destroy(); state.chart = null; }
    return;
  }

  dom.revenueTrendChart.style.display = 'block';
  dom.revenueTrendEmpty.style.display = 'none';

  const options = {
    chart: { type: 'area', height: 320, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
    colors: ['#cbd5e1', '#10b981', '#ef4444'],
    series: [
      { name: 'Công gộp', data: series.gross },
      { name: 'Thực nhận (cộng ví)', data: series.net },
      { name: 'Thuế & Phí', data: series.deduct },
    ],
    xaxis: { categories: series.labels, tooltip: { enabled: false }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { formatter: (value) => value >= 1000000 ? `${(value / 1000000).toFixed(1)}M` : `${value / 1000}k` } },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.2, opacityTo: 0.05, stops: [0, 100] } },
    legend: { show: false },
    grid: { borderColor: '#f1f5f9', strokeDashArray: 4, yaxis: { lines: { show: true } } },
    tooltip: { y: { formatter: (value) => formatMoney(value) } },
  };

  if (state.chart) {
    state.chart.updateOptions(options);
  } else {
    state.chart = new window.ApexCharts(dom.revenueTrendChart, options);
    state.chart.render();
  }
}

function renderTable(bookings) {
  if (!dom.revenueTableBody) return;
  if (!bookings.length) {
    dom.revenueTableBody.innerHTML = `<tr><td colspan="10"><div class="revenue-state"><strong>Không có đơn hàng</strong> Không tìm thấy đơn nào khớp với bộ lọc hiện tại.</div></td></tr>`;
    return;
  }

  const html = bookings.map((booking) => {
    const income = calculateIncome(booking);
    const meta = getStatusMeta(income);

    return `
      <tr>
        <td class="table-id">#${booking.id}</td>
        <td>${formatDateLabel(booking)}</td>
        <td>${escapeHtml(getServiceLabel(booking))}</td>
        <td>
            <div style="font-weight: 500;">${escapeHtml(getCustomerName(booking))}</div>
        </td>
        <td style="font-weight: 500;">${formatMoney(income.gross)}</td>
        <td style="color: #ef4444;">${formatMoney(income.tax)}</td>
        <td style="color: #ef4444;">${formatMoney(income.fee)}</td>
        <td style="font-weight: 700; color: #10b981;">${formatMoney(income.net)}</td>
        <td><span class="revenue-status revenue-status--${meta.tone}">${escapeHtml(meta.label)}</span></td>
        <td><button type="button" class="action-btn" data-id="${booking.id}">Chi tiết</button></td>
      </tr>
    `;
  }).join('');

  dom.revenueTableBody.innerHTML = html;
  
  dom.revenueTableBody.querySelectorAll('.action-btn').forEach((button) => {
    button.addEventListener('click', () => openDrawer(button.dataset.id));
  });
}

function openDrawer(bookingId) {
  const booking = state.visibleBookings.find((b) => String(b.id) === String(bookingId));
  if (!booking) return;

  const income = calculateIncome(booking);
  const meta = getStatusMeta(income);

  if (dom.drawerTitle) dom.drawerTitle.textContent = `Phiếu lương đơn #${booking.id}`;
  if (dom.drawerSubtitle) dom.drawerSubtitle.textContent = escapeHtml(getServiceLabel(booking));

  if (dom.revenueDrawerBody) {
    dom.revenueDrawerBody.innerHTML = `
      <div class="drawer-section">
        <div class="drawer-kv"><span>Trạng thái lương</span><span class="revenue-status revenue-status--${meta.tone}">${escapeHtml(meta.label)}</span></div>
        <div class="drawer-kv"><span>Khách hàng</span><strong>${escapeHtml(getCustomerName(booking))}</strong></div>
        <div class="drawer-kv"><span>Hoàn thành</span><strong>${formatDateLabel(booking)}</strong></div>
      </div>
      
      <div class="drawer-section">
        <h4 style="margin-bottom: 0.75rem; font-weight: 600;">Chi tiết tính lương</h4>
        <div class="drawer-kv"><span>Tiền công gộp</span><strong>${formatMoney(income.gross)}</strong></div>
        <div class="drawer-kv"><span>Thuế nhà nước (${state.settings.tax_rate}%)</span><strong style="color:#ef4444;">-${formatMoney(income.tax)}</strong></div>
        <div class="drawer-kv"><span>Phí nền tảng (${state.settings.fee_rate}%)</span><strong style="color:#ef4444;">-${formatMoney(income.fee)}</strong></div>
        <div class="drawer-divider" style="height: 1px; background: #e2e8f0; margin: 0.75rem 0;"></div>
        <div class="drawer-kv" style="font-size: 1.1rem;"><span>Thực nhận</span><strong style="color:#10b981;">${formatMoney(income.net)}</strong></div>
      </div>
      
      <div class="drawer-section" style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
        <p style="font-size: 0.85rem; color: #64748b; margin: 0; line-height: 1.4;">
            ${income.isPaid ? 'Khoản thực nhận này đã được cộng vào số dư ví thợ của bạn.' : (income.isPending ? 'Đơn này đang chờ khách thanh toán hoặc hệ thống xử lý để cộng ví.' : 'Đơn chưa phát sinh doanh thu công.')}
        </p>
      </div>
    `;
  }

  dom.revenueDrawerOverlay?.classList.add('is-visible');
  dom.revenueDrawer?.classList.add('is-visible');
  dom.revenueDrawer?.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
}

function closeDrawer() {
  dom.revenueDrawerOverlay?.classList.remove('is-visible');
  dom.revenueDrawer?.classList.remove('is-visible');
  dom.revenueDrawer?.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}
