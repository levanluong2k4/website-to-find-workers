import { callApi, getCurrentUser, showToast } from '../api.js';

const LOGIN_URL = `${window.location.origin}/login?role=worker`;
const PERIODS = ['7d', '30d', 'month', 'prev-month', 'all', 'custom'];
const state = {
  bookings: [],
  visibleBookings: [],
  period: '30d',
  customStart: '',
  customEnd: '',
  filters: {
    search: '',
    status: 'all',
    payment: 'all',
    mode: 'all',
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
    renderWorkspaceError(error.message || 'Không thể tải dữ liệu doanh thu.');
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
  dom.summaryCollectionRate = document.getElementById('summaryCollectionRate');
  dom.summaryAverageTicket = document.getElementById('summaryAverageTicket');
  dom.summaryCompletionRate = document.getElementById('summaryCompletionRate');
  dom.summaryTopServiceLabel = document.getElementById('summaryTopServiceLabel');
  dom.summaryTopServiceAmount = document.getElementById('summaryTopServiceAmount');
  dom.breakdownBookingCount = document.getElementById('breakdownBookingCount');
  dom.kickerAverageRating = document.getElementById('kickerAverageRating');
  dom.kickerBookingCount = document.getElementById('kickerBookingCount');
  dom.sourceList = document.getElementById('sourceList');
  dom.modeSplitRows = document.getElementById('modeSplitRows');
  dom.paymentSplitRows = document.getElementById('paymentSplitRows');
  dom.pendingCountBadge = document.getElementById('pendingCountBadge');
  dom.cancelledCountBadge = document.getElementById('cancelledCountBadge');
  dom.pendingQueue = document.getElementById('pendingQueue');
  dom.cancelledQueue = document.getElementById('cancelledQueue');
  dom.revenueTrendChart = document.getElementById('revenueTrendChart');
  dom.revenueTrendEmpty = document.getElementById('revenueTrendEmpty');
  dom.revenueSearchInput = document.getElementById('revenueSearchInput');
  dom.statusFilter = document.getElementById('statusFilter');
  dom.paymentFilter = document.getElementById('paymentFilter');
  dom.modeFilter = document.getElementById('modeFilter');
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

  dom.exportRevenueCsvButton?.addEventListener('click', exportVisibleBookingsAsCsv);
  dom.revenueSearchInput?.addEventListener('input', (event) => {
    state.filters.search = event.target.value || '';
    renderWorkspace();
  });
  dom.statusFilter?.addEventListener('change', (event) => {
    state.filters.status = event.target.value || 'all';
    renderWorkspace();
  });
  dom.paymentFilter?.addEventListener('change', (event) => {
    state.filters.payment = event.target.value || 'all';
    renderWorkspace();
  });
  dom.modeFilter?.addEventListener('change', (event) => {
    state.filters.mode = event.target.value || 'all';
    renderWorkspace();
  });
  dom.revenueDrawerClose?.addEventListener('click', closeDrawer);
  dom.revenueDrawerOverlay?.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeDrawer();
    }
  });
}

async function loadRevenueWorkspace() {
  renderLoadingState();
  state.bookings = await fetchAllBookings();
  seedCustomRangeInputs();
  renderWorkspace();
}

async function fetchAllBookings() {
  const collected = [];
  let page = 1;
  let lastPage = 1;

  do {
    const response = await callApi(`/don-dat-lich?per_page=100&page=${page}`, 'GET');

    if (!response.ok) {
      throw new Error(response.data?.message || 'Không thể tải danh sách đơn của thợ.');
    }

    const payload = response.data || {};
    const items = Array.isArray(payload.data) ? payload.data : [];
    collected.push(...items);
    lastPage = Math.max(1, Number(payload.last_page || 1));
    page += 1;
  } while (page <= lastPage);

  return collected;
}

function seedCustomRangeInputs() {
  const dates = state.bookings
    .map((booking) => getBookingDate(booking))
    .filter(Boolean)
    .sort((a, b) => a.getTime() - b.getTime());

  if (!dates.length) {
    return;
  }

  const min = toDateInputValue(dates[0]);
  const max = toDateInputValue(dates[dates.length - 1]);

  if (dom.customStartDate) {
    dom.customStartDate.min = min;
    dom.customStartDate.max = max;
  }

  if (dom.customEndDate) {
    dom.customEndDate.min = min;
    dom.customEndDate.max = max;
  }

  if (!state.customStart) {
    state.customStart = min;
  }

  if (!state.customEnd) {
    state.customEnd = max;
  }

  if (dom.customStartDate && !dom.customStartDate.value) {
    dom.customStartDate.value = state.customStart;
  }

  if (dom.customEndDate && !dom.customEndDate.value) {
    dom.customEndDate.value = state.customEnd;
  }
}

function setActivePeriod(period) {
  state.period = PERIODS.includes(period) ? period : '30d';
  syncPeriodButtons();
  renderWorkspace();
}

function syncPeriodButtons() {
  dom.periodButtons.forEach((button) => {
    button.classList.toggle('is-active', button.dataset.range === state.period);
  });
  dom.customRange?.classList.toggle('is-visible', state.period === 'custom');
}

function renderLoadingState() {
  if (dom.revenueTableBody) {
    dom.revenueTableBody.innerHTML = `
      <tr>
        <td colspan="10">
          <div class="revenue-state">
            <strong>Đang tải dữ liệu</strong>
            Vui lòng chờ trong giây lát để hệ thống tổng hợp doanh thu từ các đơn của bạn.
          </div>
        </td>
      </tr>
    `;
  }
}

function renderWorkspaceError(message) {
  const content = `
    <tr>
      <td colspan="10">
        <div class="revenue-state">
          <strong>Không thể tải doanh thu</strong>
          ${escapeHtml(message)}
        </div>
      </td>
    </tr>
  `;

  if (dom.revenueTableBody) {
    dom.revenueTableBody.innerHTML = content;
  }

  if (dom.pendingQueue) {
    dom.pendingQueue.innerHTML = `<div class="revenue-empty">${escapeHtml(message)}</div>`;
  }

  if (dom.cancelledQueue) {
    dom.cancelledQueue.innerHTML = `<div class="revenue-empty">${escapeHtml(message)}</div>`;
  }
}

function getNumeric(value) {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
}

function formatMoney(value) {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
  }).format(getNumeric(value));
}

function formatPercent(value) {
  return `${Math.round(getNumeric(value))}%`;
}

function toDateInputValue(date) {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
    return '';
  }

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function parseDate(value) {
  if (!value) {
    return null;
  }

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

function getStoredCostItems(booking, key) {
  return Array.isArray(booking?.[key]) ? booking[key].filter(Boolean) : [];
}

function getBookingTotal(booking) {
  const explicitTotal = getNumeric(booking?.tong_tien);

  if (explicitTotal > 0) {
    return explicitTotal;
  }

  const laborItems = getStoredCostItems(booking, 'chi_tiet_tien_cong');
  const partItems = getStoredCostItems(booking, 'chi_tiet_linh_kien');
  const laborTotal = laborItems.length
    ? laborItems.reduce((total, item) => total + getNumeric(item?.so_tien), 0)
    : getNumeric(booking?.tien_cong);
  const partTotal = partItems.length
    ? partItems.reduce((total, item) => total + getNumeric(item?.so_tien), 0)
    : getNumeric(booking?.phi_linh_kien);

  return getNumeric(booking?.phi_di_lai) + laborTotal + partTotal + getNumeric(booking?.tien_thue_xe);
}

function getBookingDate(booking) {
  return parseDate(booking?.thoi_gian_hoan_thanh || booking?.ngay_hen || booking?.created_at);
}

function formatDateLabel(booking) {
  const date = getBookingDate(booking);

  if (!date) {
    return 'Chưa xác định';
  }

  return date.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
}

function formatDateTimeLabel(value) {
  const date = parseDate(value);

  if (!date) {
    return 'Chưa cập nhật';
  }

  return date.toLocaleString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
}

function getCustomerName(booking) {
  return booking?.khach_hang?.name || 'Khách hàng';
}

function getCustomerPhone(booking) {
  return booking?.khach_hang?.phone || '';
}

function getServiceNames(booking) {
  const names = Array.isArray(booking?.dich_vus)
    ? booking.dich_vus.map((service) => service?.ten_dich_vu).filter(Boolean)
    : [];

  return names.length ? names : ['Dịch vụ sửa chữa'];
}

function getServiceLabel(booking) {
  return getServiceNames(booking).join(', ');
}

function getLocationLabel(booking) {
  return booking?.loai_dat_lich === 'at_home' ? 'Sửa tại nhà' : 'Sửa tại cửa hàng';
}

function getPaymentMethodCode(booking) {
  return booking?.phuong_thuc_thanh_toan === 'transfer' ? 'transfer' : 'cod';
}

function getPaymentMethodLabel(booking) {
  return getPaymentMethodCode(booking) === 'transfer' ? 'Chuyển khoản' : 'Tiền mặt';
}

function isCancelled(booking) {
  return booking?.trang_thai === 'da_huy';
}

function isPaid(booking) {
  return Boolean(booking?.trang_thai_thanh_toan);
}

function isPendingPayment(booking) {
  return ['cho_hoan_thanh', 'cho_thanh_toan'].includes(booking?.trang_thai)
    || (booking?.trang_thai === 'da_xong' && !isPaid(booking));
}

function isRevenueBooking(booking) {
  return !isCancelled(booking) && getBookingTotal(booking) > 0;
}

function getStatusMeta(booking) {
  if (isCancelled(booking)) {
    return {
      label: 'Đã hủy',
      tone: 'cancelled',
    };
  }

  if (isPendingPayment(booking)) {
    return {
      label: 'Chờ thanh toán',
      tone: 'pending',
    };
  }

  return {
    label: 'Đã thu',
    tone: 'paid',
  };
}

function getRating(booking) {
  const rating = booking?.danh_gias?.[0]?.so_sao;
  return Math.max(0, Math.min(5, getNumeric(rating)));
}

function renderStars(rating) {
  if (rating <= 0) {
    return '<span class="table-subtle">Chưa có</span>';
  }

  return `<span class="revenue-rating">${[1, 2, 3, 4, 5].map((index) => `
    <span class="material-symbols-outlined" style="font-size:0.95rem;color:${index <= rating ? '#f59e0b' : '#cbd5e1'};font-variation-settings:'FILL' 1;">star</span>
  `).join('')}</span>`;
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function getPeriodRange() {
  const now = new Date();
  const today = endOfDay(now);

  if (state.period === '7d') {
    const start = startOfDay(new Date(now.getFullYear(), now.getMonth(), now.getDate() - 6));
    return { key: '7d', start, end: today, label: '7 ngày gần đây' };
  }

  if (state.period === 'month') {
    return {
      key: 'month',
      start: startOfDay(new Date(now.getFullYear(), now.getMonth(), 1)),
      end: today,
      label: 'Tháng này',
    };
  }

  if (state.period === 'prev-month') {
    const start = startOfDay(new Date(now.getFullYear(), now.getMonth() - 1, 1));
    const end = endOfDay(new Date(now.getFullYear(), now.getMonth(), 0));
    return { key: 'prev-month', start, end, label: 'Tháng trước' };
  }

  if (state.period === 'all') {
    return { key: 'all', start: null, end: null, label: 'Toàn bộ dữ liệu' };
  }

  if (state.period === 'custom') {
    const customStart = parseDate(state.customStart);
    const customEnd = parseDate(state.customEnd);
    return {
      key: 'custom',
      start: customStart ? startOfDay(customStart) : null,
      end: customEnd ? endOfDay(customEnd) : null,
      label: customStart && customEnd
        ? `${formatDateLabel({ ngay_hen: state.customStart })} - ${formatDateLabel({ ngay_hen: state.customEnd })}`
        : 'Khoảng tùy chọn',
    };
  }

  const defaultStart = startOfDay(new Date(now.getFullYear(), now.getMonth(), now.getDate() - 29));
  return { key: '30d', start: defaultStart, end: today, label: '30 ngày gần đây' };
}

function filterBookingsByPeriod(bookings) {
  const range = getPeriodRange();

  if (!range.start || !range.end) {
    return bookings.slice();
  }

  return bookings.filter((booking) => {
    const date = getBookingDate(booking);
    return date && date.getTime() >= range.start.getTime() && date.getTime() <= range.end.getTime();
  });
}

function applyTableFilters(bookings) {
  const search = state.filters.search.trim().toLowerCase();

  return bookings.filter((booking) => {
    const haystack = [
      `#${booking?.id || ''}`,
      getCustomerName(booking),
      getServiceLabel(booking),
    ].join(' ').toLowerCase();

    if (search && !haystack.includes(search)) {
      return false;
    }

    if (state.filters.status === 'paid' && getStatusMeta(booking).tone !== 'paid') {
      return false;
    }

    if (state.filters.status === 'pending' && getStatusMeta(booking).tone !== 'pending') {
      return false;
    }

    if (state.filters.status === 'cancelled' && getStatusMeta(booking).tone !== 'cancelled') {
      return false;
    }

    if (state.filters.payment === 'cod' && getPaymentMethodCode(booking) !== 'cod') {
      return false;
    }

    if (state.filters.payment === 'transfer' && getPaymentMethodCode(booking) !== 'transfer') {
      return false;
    }

    if (state.filters.payment === 'paid' && !isPaid(booking)) {
      return false;
    }

    if (state.filters.payment === 'unpaid' && isPaid(booking)) {
      return false;
    }

    if (state.filters.mode !== 'all' && booking?.loai_dat_lich !== state.filters.mode) {
      return false;
    }

    return true;
  }).sort((left, right) => {
    const leftTime = getBookingDate(left)?.getTime() || 0;
    const rightTime = getBookingDate(right)?.getTime() || 0;
    return rightTime - leftTime;
  });
}

function buildMetrics(bookings) {
  const revenueBookings = bookings.filter(isRevenueBooking);
  const paidBookings = revenueBookings.filter((booking) => !isPendingPayment(booking) && isPaid(booking));
  const pendingBookings = bookings.filter(isPendingPayment);
  const cancelledBookings = bookings.filter(isCancelled);

  const paidAmount = paidBookings.reduce((total, booking) => total + getBookingTotal(booking), 0);
  const pendingAmount = pendingBookings.reduce((total, booking) => total + getBookingTotal(booking), 0);
  const grossAmount = paidAmount + pendingAmount;
  const cancelledAmount = cancelledBookings.reduce((total, booking) => total + getBookingTotal(booking), 0);
  const averageTicket = revenueBookings.length ? grossAmount / revenueBookings.length : 0;
  const collectionRate = grossAmount > 0 ? (paidAmount / grossAmount) * 100 : 0;
  const completedCount = paidBookings.length + pendingBookings.length;
  const completionRate = completedCount + cancelledBookings.length > 0
    ? (completedCount / (completedCount + cancelledBookings.length)) * 100
    : 0;

  const serviceMap = new Map();
  const modeMap = new Map([
    ['at_home', { label: 'Sửa tại nhà', amount: 0, count: 0 }],
    ['at_store', { label: 'Sửa tại cửa hàng', amount: 0, count: 0 }],
  ]);
  const paymentMap = new Map([
    ['cod', { label: 'Tiền mặt', amount: 0, count: 0 }],
    ['transfer', { label: 'Chuyển khoản', amount: 0, count: 0 }],
  ]);
  const ratings = [];

  revenueBookings.forEach((booking) => {
    const total = getBookingTotal(booking);
    const services = getServiceNames(booking);
    const share = services.length ? total / services.length : total;

    services.forEach((serviceName) => {
      const existing = serviceMap.get(serviceName) || { name: serviceName, amount: 0, count: 0 };
      existing.amount += share;
      existing.count += 1;
      serviceMap.set(serviceName, existing);
    });

    const modeKey = booking?.loai_dat_lich === 'at_home' ? 'at_home' : 'at_store';
    const modeBucket = modeMap.get(modeKey);
    modeBucket.amount += total;
    modeBucket.count += 1;

    const paymentBucket = paymentMap.get(getPaymentMethodCode(booking));
    paymentBucket.amount += total;
    paymentBucket.count += 1;

    const rating = getRating(booking);
    if (rating > 0) {
      ratings.push(rating);
    }
  });

  const serviceLeaderboard = Array.from(serviceMap.values()).sort((left, right) => right.amount - left.amount);
  const topService = serviceLeaderboard[0] || null;
  const averageRating = ratings.length
    ? ratings.reduce((sum, value) => sum + value, 0) / ratings.length
    : 0;

  return {
    bookings,
    revenueBookings,
    paidBookings,
    pendingBookings,
    cancelledBookings,
    paidAmount,
    pendingAmount,
    grossAmount,
    cancelledAmount,
    averageTicket,
    collectionRate,
    completionRate,
    serviceLeaderboard,
    topService,
    modeSplit: Array.from(modeMap.values()),
    paymentSplit: Array.from(paymentMap.values()),
    averageRating,
  };
}

function buildChartSeries(bookings) {
  const range = getPeriodRange();
  const monetized = bookings.filter((booking) => isRevenueBooking(booking) || isPendingPayment(booking));
  const useMonthlyBuckets = range.key === 'all' || (range.start && range.end && daysBetween(range.start, range.end) > 45);

  if (!monetized.length) {
    return { labels: [], paid: [], pending: [] };
  }

  if (useMonthlyBuckets) {
    const monthKeys = [];
    const totals = new Map();

    monetized.forEach((booking) => {
      const date = getBookingDate(booking);
      if (!date) {
        return;
      }

      const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
      if (!totals.has(key)) {
        monthKeys.push(key);
        totals.set(key, { paid: 0, pending: 0, date });
      }

      const bucket = totals.get(key);
      if (isPendingPayment(booking)) {
        bucket.pending += getBookingTotal(booking);
      } else {
        bucket.paid += getBookingTotal(booking);
      }
    });

    const ordered = monthKeys.sort();
    return {
      labels: ordered.map((key) => {
        const [year, month] = key.split('-');
        return `${month}/${year}`;
      }),
      paid: ordered.map((key) => totals.get(key)?.paid || 0),
      pending: ordered.map((key) => totals.get(key)?.pending || 0),
    };
  }

  const start = range.start || startOfDay(getBookingDate(monetized[0]) || new Date());
  const end = range.end || endOfDay(getBookingDate(monetized[monetized.length - 1]) || new Date());
  const totalDays = daysBetween(start, end);
  const labels = [];
  const paid = [];
  const pending = [];

  for (let offset = 0; offset < totalDays; offset += 1) {
    const cursor = startOfDay(new Date(start.getFullYear(), start.getMonth(), start.getDate() + offset));
    const key = toDateInputValue(cursor);
    labels.push(cursor.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' }));

    const dayBookings = monetized.filter((booking) => toDateInputValue(getBookingDate(booking)) === key);
    paid.push(dayBookings.filter((booking) => !isPendingPayment(booking)).reduce((sum, booking) => sum + getBookingTotal(booking), 0));
    pending.push(dayBookings.filter(isPendingPayment).reduce((sum, booking) => sum + getBookingTotal(booking), 0));
  }

  return { labels, paid, pending };
}

function renderWorkspace() {
  syncPeriodButtons();

  const scopedBookings = filterBookingsByPeriod(state.bookings);
  const metrics = buildMetrics(scopedBookings);
  state.visibleBookings = applyTableFilters(scopedBookings);

  renderHero(metrics);
  renderBreakdowns(metrics);
  renderQueues(metrics);
  renderChart(scopedBookings);
  renderTable(state.visibleBookings);
}

function renderHero(metrics) {
  const range = getPeriodRange();
  if (dom.periodSummaryPill) {
    dom.periodSummaryPill.innerHTML = `
      <span class="material-symbols-outlined">date_range</span>
      <span>${escapeHtml(range.label)}</span>
    `;
  }

  if (dom.heroCollectedAmount) dom.heroCollectedAmount.textContent = formatMoney(metrics.paidAmount);
  if (dom.heroPendingAmount) dom.heroPendingAmount.textContent = formatMoney(metrics.pendingAmount);
  if (dom.heroGrossAmount) dom.heroGrossAmount.textContent = formatMoney(metrics.grossAmount);
  if (dom.heroCancelledAmount) dom.heroCancelledAmount.textContent = formatMoney(metrics.cancelledAmount);

  if (dom.heroInsight) {
    const pendingCount = metrics.pendingBookings.length;
    const topServiceText = metrics.topService
      ? `${metrics.topService.name} đang dẫn đầu với ${formatMoney(metrics.topService.amount)}.`
      : 'Chưa có dịch vụ nào đủ dữ liệu để xếp hạng.';
    dom.heroInsight.textContent = `${metrics.paidBookings.length} đơn đã thu, ${pendingCount} đơn còn chờ thanh toán. ${topServiceText}`;
  }

  if (dom.summaryCollectionRate) dom.summaryCollectionRate.textContent = formatPercent(metrics.collectionRate);
  if (dom.summaryAverageTicket) dom.summaryAverageTicket.textContent = formatMoney(metrics.averageTicket);
  if (dom.summaryCompletionRate) dom.summaryCompletionRate.textContent = formatPercent(metrics.completionRate);
  if (dom.summaryTopServiceLabel) dom.summaryTopServiceLabel.textContent = metrics.topService?.name || 'Chưa có dữ liệu';
  if (dom.summaryTopServiceAmount) dom.summaryTopServiceAmount.textContent = formatMoney(metrics.topService?.amount || 0);
  if (dom.breakdownBookingCount) dom.breakdownBookingCount.textContent = `${metrics.revenueBookings.length} đơn có chi phí`;
  if (dom.kickerAverageRating) dom.kickerAverageRating.textContent = metrics.averageRating > 0 ? `${metrics.averageRating.toFixed(1)}/5` : 'Chưa có';
  if (dom.kickerBookingCount) dom.kickerBookingCount.textContent = `${metrics.bookings.length} đơn`;
}

function renderBreakdowns(metrics) {
  renderSourceList(metrics);
  renderSplitRows(dom.modeSplitRows, metrics.modeSplit, metrics.grossAmount || 1, 'indigo');
  renderSplitRows(dom.paymentSplitRows, metrics.paymentSplit, metrics.grossAmount || 1, 'amber');
}

function renderSourceList(metrics) {
  if (!dom.sourceList) {
    return;
  }

  const topSources = metrics.serviceLeaderboard.slice(0, 4);
  if (!topSources.length) {
    dom.sourceList.innerHTML = `<div class="revenue-empty">Chưa có đủ dữ liệu để phân tích nguồn doanh thu.</div>`;
    return;
  }

  dom.sourceList.innerHTML = topSources.map((item) => {
    const percent = metrics.grossAmount > 0 ? Math.min(100, (item.amount / metrics.grossAmount) * 100) : 0;
    return `
      <article class="source-item">
        <div class="source-item__label">Nguồn tạo tiền</div>
        <div class="source-item__top">
          <div class="source-item__name">${escapeHtml(item.name)}</div>
          <div class="source-item__value">${formatMoney(item.amount)}</div>
        </div>
        <div class="source-item__bar">
          <div class="source-item__fill" style="width:${percent}%"></div>
        </div>
      </article>
    `;
  }).join('');
}

function renderSplitRows(container, rows, total, tone) {
  if (!container) {
    return;
  }

  container.innerHTML = rows.map((row) => {
    const percent = total > 0 ? Math.min(100, (row.amount / total) * 100) : 0;
    const toneClass = tone === 'amber' ? 'split-row__fill--amber' : 'split-row__fill--indigo';
    return `
      <div class="split-row">
        <div class="split-row__top">
          <span>${escapeHtml(row.label)}</span>
          <span>${formatMoney(row.amount)}</span>
        </div>
        <div class="split-row__meta">${row.count} đơn • ${formatPercent(percent)}</div>
        <div class="split-row__bar">
          <div class="split-row__fill ${toneClass}" style="width:${percent}%"></div>
        </div>
      </div>
    `;
  }).join('');
}

function renderQueues(metrics) {
  renderQueue(dom.pendingQueue, metrics.pendingBookings, 'pending');
  renderQueue(dom.cancelledQueue, metrics.cancelledBookings, 'cancelled');
  if (dom.pendingCountBadge) dom.pendingCountBadge.textContent = `${metrics.pendingBookings.length} đơn`;
  if (dom.cancelledCountBadge) dom.cancelledCountBadge.textContent = `${metrics.cancelledBookings.length} đơn`;
}

function renderQueue(container, bookings, tone) {
  if (!container) {
    return;
  }

  const shortlist = bookings.slice().sort((left, right) => {
    const leftTime = getBookingDate(left)?.getTime() || 0;
    const rightTime = getBookingDate(right)?.getTime() || 0;
    return rightTime - leftTime;
  }).slice(0, 5);

  if (!shortlist.length) {
    container.innerHTML = `<div class="revenue-empty">${tone === 'pending' ? 'Không có đơn nào đang chờ thanh toán.' : 'Không có đơn hủy nào trong giai đoạn đang xem.'}</div>`;
    return;
  }

  container.innerHTML = shortlist.map((booking) => `
    <article class="queue-item">
      <div class="queue-item__top">
        <div>
          <span class="queue-item__eyebrow">Đơn #${booking.id}</span>
          <h3 class="queue-item__name">${escapeHtml(getCustomerName(booking))}</h3>
          <p class="queue-item__meta">${escapeHtml(getServiceLabel(booking))}<br>${escapeHtml(formatDateLabel(booking))} • ${escapeHtml(getLocationLabel(booking))}</p>
        </div>
        <div class="queue-item__amount">${formatMoney(getBookingTotal(booking))}</div>
      </div>
      <div class="queue-item__footer">
        <span class="queue-item__status queue-item__status--${tone}">${tone === 'pending' ? 'Chờ thanh toán' : 'Đã hủy'}</span>
        <button type="button" class="revenue-row-button" data-booking-id="${booking.id}">Xem chi tiết</button>
      </div>
    </article>
  `).join('');

  container.querySelectorAll('[data-booking-id]').forEach((button) => {
    button.addEventListener('click', () => openDrawer(Number(button.dataset.bookingId)));
  });
}

function renderChart(bookings) {
  const chartSeries = buildChartSeries(bookings);
  const hasData = chartSeries.labels.length > 0 && [...chartSeries.paid, ...chartSeries.pending].some((value) => value > 0);

  dom.revenueTrendEmpty?.classList.toggle('is-visible', !hasData);

  if (state.chart) {
    state.chart.destroy();
    state.chart = null;
  }

  if (!hasData || !dom.revenueTrendChart || typeof ApexCharts === 'undefined') {
    return;
  }

  state.chart = new ApexCharts(dom.revenueTrendChart, {
    chart: {
      type: 'area',
      height: 320,
      toolbar: { show: false },
      fontFamily: 'Inter, sans-serif',
    },
    series: [
      { name: 'Đã thu', data: chartSeries.paid },
      { name: 'Chờ thanh toán', data: chartSeries.pending },
    ],
    colors: ['#0ea5e9', '#f59e0b'],
    stroke: {
      curve: 'smooth',
      width: [3, 2],
    },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.32,
        opacityTo: 0.04,
      },
    },
    dataLabels: { enabled: false },
    xaxis: {
      categories: chartSeries.labels,
      labels: {
        style: {
          colors: '#64748b',
          fontSize: '11px',
        },
      },
    },
    yaxis: {
      labels: {
        formatter: (value) => `${Math.round(value / 1000)}k`,
        style: {
          colors: '#64748b',
          fontSize: '11px',
        },
      },
    },
    grid: {
      borderColor: '#e2e8f0',
      strokeDashArray: 4,
    },
    legend: {
      position: 'top',
      horizontalAlign: 'left',
      labels: {
        colors: '#475569',
      },
    },
    tooltip: {
      y: {
        formatter: (value) => formatMoney(value),
      },
    },
  });

  state.chart.render();
}

function renderTable(bookings) {
  if (!dom.revenueTableBody) {
    return;
  }

  if (!bookings.length) {
    dom.revenueTableBody.innerHTML = `
      <tr>
        <td colspan="10">
          <div class="revenue-state">
            <strong>Không có đơn phù hợp</strong>
            Hãy đổi bộ lọc thời gian hoặc trạng thái để xem dữ liệu doanh thu khác.
          </div>
        </td>
      </tr>
    `;
    return;
  }

  dom.revenueTableBody.innerHTML = bookings.map((booking) => {
    const status = getStatusMeta(booking);
    const paymentLabel = isPaid(booking) ? 'Đã thanh toán' : 'Chưa thanh toán';
    const paymentTone = isPaid(booking) ? 'paid' : 'pending';
    return `
      <tr>
        <td><strong>#${booking.id}</strong></td>
        <td>
          <div>${escapeHtml(formatDateLabel(booking))}</div>
          <div class="table-subtle">${escapeHtml(formatDateTimeLabel(booking?.thoi_gian_hoan_thanh || booking?.created_at))}</div>
        </td>
        <td>
          <div class="table-customer">
            <strong>${escapeHtml(getCustomerName(booking))}</strong>
            <span>${escapeHtml(getCustomerPhone(booking) || 'Chưa có số điện thoại')}</span>
          </div>
        </td>
        <td>
          <div>${escapeHtml(getServiceLabel(booking))}</div>
          <div class="table-subtle">${escapeHtml(booking?.dia_chi || 'Không có địa chỉ')}</div>
        </td>
        <td><span class="revenue-badge revenue-badge--${booking?.loai_dat_lich === 'at_home' ? 'home' : 'store'}">${escapeHtml(getLocationLabel(booking))}</span></td>
        <td>
          <div>${escapeHtml(getPaymentMethodLabel(booking))}</div>
          <div class="table-subtle"><span class="revenue-badge revenue-badge--${paymentTone}">${escapeHtml(paymentLabel)}</span></div>
        </td>
        <td><strong>${formatMoney(getBookingTotal(booking))}</strong></td>
        <td><span class="revenue-badge revenue-badge--${status.tone}">${escapeHtml(status.label)}</span></td>
        <td>${renderStars(getRating(booking))}</td>
        <td><button type="button" class="revenue-row-button" data-booking-id="${booking.id}">Chi tiết</button></td>
      </tr>
    `;
  }).join('');

  dom.revenueTableBody.querySelectorAll('[data-booking-id]').forEach((button) => {
    button.addEventListener('click', () => openDrawer(Number(button.dataset.bookingId)));
  });
}

function openDrawer(bookingId) {
  const booking = state.bookings.find((item) => Number(item.id) === Number(bookingId));

  if (!booking || !dom.revenueDrawer || !dom.revenueDrawerBody) {
    return;
  }

  const status = getStatusMeta(booking);
  const laborItems = getStoredCostItems(booking, 'chi_tiet_tien_cong');
  const partItems = getStoredCostItems(booking, 'chi_tiet_linh_kien');
  const laborTotal = laborItems.length ? laborItems.reduce((sum, item) => sum + getNumeric(item?.so_tien), 0) : getNumeric(booking?.tien_cong);
  const partTotal = partItems.length ? partItems.reduce((sum, item) => sum + getNumeric(item?.so_tien), 0) : getNumeric(booking?.phi_linh_kien);

  if (dom.drawerTitle) {
    dom.drawerTitle.textContent = `Đơn #${booking.id} • ${getCustomerName(booking)}`;
  }

  if (dom.drawerSubtitle) {
    dom.drawerSubtitle.textContent = `${status.label} • ${getPaymentMethodLabel(booking)} • ${formatDateLabel(booking)}`;
  }

  dom.revenueDrawerBody.innerHTML = `
    <section class="drawer-card">
      <h4>Thông tin chính</h4>
      <div class="drawer-grid">
        <div class="drawer-row"><span>Khách hàng</span><strong>${escapeHtml(getCustomerName(booking))}</strong></div>
        <div class="drawer-row"><span>Điện thoại</span><strong>${escapeHtml(getCustomerPhone(booking) || 'Chưa có')}</strong></div>
        <div class="drawer-row"><span>Dịch vụ</span><strong>${escapeHtml(getServiceLabel(booking))}</strong></div>
        <div class="drawer-row"><span>Hình thức</span><strong>${escapeHtml(getLocationLabel(booking))}</strong></div>
        <div class="drawer-row"><span>Lịch hẹn</span><strong>${escapeHtml(formatDateLabel(booking))}${booking?.khung_gio_hen ? ` • ${escapeHtml(booking.khung_gio_hen)}` : ''}</strong></div>
        <div class="drawer-row"><span>Hoàn thành</span><strong>${escapeHtml(formatDateTimeLabel(booking?.thoi_gian_hoan_thanh))}</strong></div>
      </div>
    </section>

    <section class="drawer-card">
      <h4>Breakdown chi phí</h4>
      <div class="drawer-grid">
        <div class="drawer-row"><span>Phí đi lại</span><strong>${formatMoney(booking?.phi_di_lai)}</strong></div>
        <div class="drawer-row"><span>Tiền công</span><strong>${formatMoney(laborTotal)}</strong></div>
        <div class="drawer-row"><span>Linh kiện</span><strong>${formatMoney(partTotal)}</strong></div>
        <div class="drawer-row"><span>Thuê xe chở</span><strong>${formatMoney(booking?.tien_thue_xe)}</strong></div>
        <div class="drawer-row"><span>Tổng cộng</span><strong>${formatMoney(getBookingTotal(booking))}</strong></div>
      </div>
    </section>

    <section class="drawer-card">
      <h4>Chi tiết tiền công</h4>
      <div class="drawer-breakdown">
        ${laborItems.length ? laborItems.map((item) => `
          <div class="drawer-item">
            <div class="drawer-item__name">${escapeHtml(item?.noi_dung || 'Hạng mục công việc')}</div>
            <div class="drawer-item__meta">${formatMoney(item?.so_tien)}</div>
          </div>
        `).join('') : '<div class="revenue-empty">Chưa có danh sách hạng mục tiền công.</div>'}
      </div>
    </section>

    <section class="drawer-card">
      <h4>Chi tiết linh kiện</h4>
      <div class="drawer-breakdown">
        ${partItems.length ? partItems.map((item) => `
          <div class="drawer-item">
            <div class="drawer-item__name">${escapeHtml(item?.noi_dung || 'Linh kiện phát sinh')}</div>
            <div class="drawer-item__meta">${formatMoney(item?.so_tien)}${item?.bao_hanh_thang ? ` • Bảo hành ${escapeHtml(item.bao_hanh_thang)} tháng` : ''}</div>
          </div>
        `).join('') : '<div class="revenue-empty">Chưa có linh kiện phát sinh.</div>'}
      </div>
    </section>
  `;

  dom.revenueDrawer.classList.add('is-open');
  dom.revenueDrawer.setAttribute('aria-hidden', 'false');
  dom.revenueDrawerOverlay?.classList.add('is-open');
}

function closeDrawer() {
  dom.revenueDrawer?.classList.remove('is-open');
  dom.revenueDrawer?.setAttribute('aria-hidden', 'true');
  dom.revenueDrawerOverlay?.classList.remove('is-open');
}

function exportVisibleBookingsAsCsv() {
  if (!state.visibleBookings.length) {
    showToast('Không có dữ liệu để xuất.', 'error');
    return;
  }

  const rows = [
    ['Ma don', 'Ngay', 'Khach hang', 'Dich vu', 'Hinh thuc', 'Thanh toan', 'Tong tien', 'Trang thai'],
    ...state.visibleBookings.map((booking) => ([
      `#${booking.id}`,
      formatDateLabel(booking),
      getCustomerName(booking),
      getServiceLabel(booking),
      getLocationLabel(booking),
      getPaymentMethodLabel(booking),
      getBookingTotal(booking),
      getStatusMeta(booking).label,
    ])),
  ];

  const csv = rows.map((row) => row.map((value) => `"${String(value).replace(/"/g, '""')}"`).join(',')).join('\n');
  const blob = new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');

  link.href = url;
  link.download = `worker-revenue-${Date.now()}.csv`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);

  showToast('Đã xuất CSV doanh thu.');
}
