import { callApi, getCurrentUser, showToast } from '../api.js';
import {
  escapeHtml,
  formatMoney,
  getBookingLaborItems,
  getBookingPartItems,
  getBookingServiceNames,
  getCustomerName,
  getNumeric,
  getStoredCostItems,
} from './pricing-core.js';
import { createTopbarNotificationCenter } from './my-bookings-notifications.js';
import { createRouteGuideController } from './my-bookings-route-guide.js';
import { createWorkerBoardRenderer } from './my-bookings-board-renderer.js';
import { createBookingDetailModalController } from './my-bookings-detail-modal.js';
import { createPricingModalController } from './my-bookings-pricing-modal.js';
import { createCompleteBookingModalController } from './my-bookings-complete-modal.js';

const pageEl = document.getElementById('workerMyBookingsPage');

if (!pageEl) {
  throw new Error('Worker bookings page root not found.');
}

const baseUrl = pageEl.dataset.baseUrl || '';
const routeWorkerMarkerImage = pageEl.dataset.routeWorkerMarkerImage || '';
const user = getCurrentUser();

if (!user || !['worker', 'admin'].includes(user.role)) {
  window.location.href = `${baseUrl}/login?role=worker`;
}

const WORKER_BOARD_STATUSES = ['pending', 'upcoming', 'inprogress', 'payment', 'done', 'cancelled', 'all'];
const WORKER_BOOKING_SCOPES = ['all', 'today'];
const CUSTOMER_UNREACHABLE_STATUS = 'khong_lien_lac_duoc_voi_khach_hang';
const bookingPageParams = new URLSearchParams(window.location.search);
const initialBookingId = Number(bookingPageParams.get('booking') || 0);

window.currentStatus = WORKER_BOARD_STATUSES.includes(bookingPageParams.get('status')) ? bookingPageParams.get('status') : 'inprogress';
window.currentScope = WORKER_BOOKING_SCOPES.includes(bookingPageParams.get('scope')) ? bookingPageParams.get('scope') : 'all';
window.currentPage = 1;
window.assignedBookings = [];
window.availableBookings = [];
window.allBookings = [];
window.activeBookingId = 0;
window.pendingBookingIdToOpen = Number.isFinite(initialBookingId) && initialBookingId > 0 ? Math.trunc(initialBookingId) : 0;

const JOBS_PER_PAGE = 2;

const bookingsContainer = document.getElementById('bookingsContainer');
const bookingPagination = document.getElementById('bookingPagination');
const bookingPaginationWrap = document.getElementById('bookingPaginationWrap');
const boardStatusTabs = Array.from(document.querySelectorAll('[data-board-status]'));
const bookingScopeButtons = Array.from(document.querySelectorAll('[data-booking-scope]'));
const boardIntroEyebrow = document.getElementById('dispatchBoardIntroEyebrow');
const boardIntroTitle = document.getElementById('dispatchBoardIntroTitle');
const boardIntroSubtitle = document.getElementById('dispatchBoardIntroSubtitle');
const boardStatusChip = document.getElementById('dispatchBoardStatusChip');
const boardScopeChip = document.getElementById('dispatchBoardScopeChip');
const boardControlsMeta = document.getElementById('dispatchBoardControlsMeta');
const topNotificationButton = document.getElementById('dispatchTopNotificationButton');
const topNotificationBadge = document.getElementById('dispatchTopNotificationBadge');
const topNotificationMenu = document.getElementById('dispatchTopNotificationMenu');
const topNotificationList = document.getElementById('dispatchTopNotificationList');
const topNotificationMarkAll = document.getElementById('dispatchTopNotificationMarkAll');
const topAvatar = document.getElementById('dispatchTopAvatar');
const routePreviewSection = document.getElementById('routePreviewSection');
const routePreviewBadge = document.getElementById('routePreviewBadge');
const routePreviewTitle = document.getElementById('routePreviewTitle');
const routePreviewLocation = document.getElementById('routePreviewLocation');
const routePreviewMeta = document.getElementById('routePreviewMeta');
const routePreviewAction = document.getElementById('routePreviewAction');
const completeForm = document.getElementById('formCompleteBooking');

const routeModalEl = document.getElementById('modalRouteGuide');
const routeModalInstance = routeModalEl && typeof bootstrap !== 'undefined'
  ? new bootstrap.Modal(routeModalEl)
  : null;

const completeModalEl = document.getElementById('modalCompleteBooking');
const completeModalInstance = completeModalEl && typeof bootstrap !== 'undefined'
  ? new bootstrap.Modal(completeModalEl)
  : null;

const completeBookingId = document.getElementById('completeBookingId');
const completeCustomerName = document.getElementById('completeCustomerName');
const completeServiceName = document.getElementById('completeServiceName');
const completeBookingTotal = document.getElementById('completeBookingTotal');
const completeStatusBadge = document.getElementById('completeStatusBadge');
const completePaymentMethodTitle = document.getElementById('completePaymentMethodTitle');
const completePaymentMethodHint = document.getElementById('completePaymentMethodHint');
const completePaymentMethodBadge = document.getElementById('completePaymentMethodBadge');
const completePaymentMethodInputs = Array.from(document.querySelectorAll('input[name="phuong_thuc_thanh_toan"]'));
const completePaymentOptions = Array.from(document.querySelectorAll('.dispatch-pay-option'));
const completePricingAlert = document.getElementById('completePricingAlert');
const completeWorkflowList = document.getElementById('completeWorkflowList');
const imageUploadPreview = document.getElementById('imageUploadPreview');
const videoUploadPreview = document.getElementById('videoUploadPreview');
const inputHinhAnhKetQua = document.getElementById('inputHinhAnhKetQua');
const inputVideoKetQua = document.getElementById('inputVideoKetQua');
const btnSubmitCompleteForm = document.getElementById('btnSubmitCompleteForm');
const routeMapCanvas = document.getElementById('routeMapCanvas');
const routeMapFallback = document.getElementById('routeMapFallback');
const routeMapFallbackTitle = document.getElementById('routeMapFallbackTitle');
const routeMapFallbackText = document.getElementById('routeMapFallbackText');
const routeRefreshLocationBtn = document.getElementById('routeRefreshLocationBtn');
const routeOpenExternalBtn = document.getElementById('routeOpenExternalBtn');
const routeServiceName = document.getElementById('routeServiceName');
const routeDestinationAddress = document.getElementById('routeDestinationAddress');
const routeDestinationCoords = document.getElementById('routeDestinationCoords');
const routeDistanceValue = document.getElementById('routeDistanceValue');
const routeDistanceHint = document.getElementById('routeDistanceHint');
const routeEtaValue = document.getElementById('routeEtaValue');
const routeEtaHint = document.getElementById('routeEtaHint');
const routeCurrentCoords = document.getElementById('routeCurrentCoords');
const routeLastUpdated = document.getElementById('routeLastUpdated');
const routeTrackingStatus = document.getElementById('routeTrackingStatus');
const routeMapStatus = document.getElementById('routeMapStatus');
const routeBookingCode = document.getElementById('routeBookingCode');

const resolveAvatarUrl = (avatar = '') => {
  if (!avatar) {
    return '';
  }

  if (/^https?:\/\//i.test(avatar) || avatar.startsWith('/')) {
    return avatar;
  }

  return `/storage/${avatar}`;
};

const getInitials = (name = '') => {
  const normalized = String(name || '').trim();
  if (!normalized) {
    return 'TT';
  }

  return normalized
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('') || normalized.charAt(0).toUpperCase() || 'TT';
};

const setAvatarContent = (element, avatar, fallbackName) => {
  if (!element) {
    return;
  }

  const avatarUrl = resolveAvatarUrl(avatar);
  if (avatarUrl) {
    element.innerHTML = `<img src="${escapeHtml(avatarUrl)}" alt="${escapeHtml(fallbackName || 'Avatar')}">`;
    return;
  }

  element.textContent = getInitials(fallbackName || 'Thợ kỹ thuật');
};

const formatCount = (value) => String(Number(value || 0)).padStart(2, '0');
const getApiCollection = (payload) => {
  if (Array.isArray(payload?.data)) {
    return payload.data;
  }

  return Array.isArray(payload) ? payload : [];
};
const isClaimableMarketBooking = (booking) => booking?.trang_thai === 'cho_xac_nhan' && getNumeric(booking?.tho_id) <= 0;
const isAssignedPendingBooking = (booking) => booking?.trang_thai === 'cho_xac_nhan' && getNumeric(booking?.tho_id) === getNumeric(user?.id);
const isWorkerOwnedBooking = (booking) => getNumeric(booking?.tho_id) === getNumeric(user?.id);
const normalizeWorkerBooking = (booking = {}, { isMarketJob = false } = {}) => {
  const normalizedBooking = {
    ...booking,
    is_market_job: isMarketJob,
  };

  const workerDistanceKm = getNumeric(booking?.worker_distance_km);
  if (isMarketJob && workerDistanceKm > 0) {
    normalizedBooking.khoang_cach = workerDistanceKm;
  }

  return normalizedBooking;
};
const rebuildWorkerBookings = () => {
  const bookingMap = new Map();

  window.availableBookings.forEach((booking) => {
    const bookingId = getNumeric(booking?.id);
    if (bookingId > 0) {
      bookingMap.set(bookingId, normalizeWorkerBooking(booking, { isMarketJob: true }));
    }
  });

  window.assignedBookings.forEach((booking) => {
    const bookingId = getNumeric(booking?.id);
    if (bookingId > 0) {
      bookingMap.set(bookingId, normalizeWorkerBooking(booking, { isMarketJob: false }));
    }
  });

  window.allBookings = Array.from(bookingMap.values());
};
const getTodayKey = () => new Date().toISOString().slice(0, 10);
const statusFilters = {
  pending: (booking) => booking.trang_thai === 'cho_xac_nhan',
  upcoming: (booking) => ['da_xac_nhan', CUSTOMER_UNREACHABLE_STATUS].includes(booking.trang_thai),
  inprogress: (booking) => booking.trang_thai === 'dang_lam',
  payment: (booking) => ['cho_hoan_thanh', 'cho_thanh_toan'].includes(booking.trang_thai),
  done: (booking) => booking.trang_thai === 'da_xong',
  cancelled: (booking) => booking.trang_thai === 'da_huy',
  all: () => true,
};

const statusToneMap = {
  cho_xac_nhan: 'pending',
  cho_hoan_thanh: 'payment',
  da_xac_nhan: 'upcoming',
  [CUSTOMER_UNREACHABLE_STATUS]: 'upcoming',
  dang_lam: 'inprogress',
  cho_thanh_toan: 'payment',
  da_xong: 'done',
  da_huy: 'cancelled',
};

const buildWorkerBookingsHref = ({
  status = window.currentStatus,
  scope = window.currentScope,
  bookingId = window.activeBookingId,
} = {}) => {
  const params = new URLSearchParams();

  if (WORKER_BOARD_STATUSES.includes(status) && status !== 'inprogress') {
    params.set('status', status);
  }

  if (WORKER_BOOKING_SCOPES.includes(scope) && scope !== 'all') {
    params.set('scope', scope);
  }

  const normalizedBookingId = Number(bookingId);
  if (Number.isFinite(normalizedBookingId) && normalizedBookingId > 0) {
    params.set('booking', String(Math.trunc(normalizedBookingId)));
  }

  const query = params.toString();
  return query ? `/worker/my-bookings?${query}` : '/worker/my-bookings';
};

const syncWorkerBookingsUrl = ({ bookingId = window.activeBookingId, replace = true } = {}) => {
  const targetUrl = buildWorkerBookingsHref({ bookingId });
  const nextLocation = new URL(targetUrl, window.location.origin);
  const nextHref = `${nextLocation.pathname}${nextLocation.search}`;
  const currentHref = `${window.location.pathname}${window.location.search}`;

  if (nextHref === currentHref) {
    return;
  }

  const historyMethod = replace ? 'replaceState' : 'pushState';
  window.history[historyMethod](window.history.state, '', nextHref);
};

const statusLabelMap = {
  cho_xac_nhan: 'Chờ xác nhận',
  cho_hoan_thanh: 'Chờ xác nhận COD',
  da_xac_nhan: 'Sắp tới',
  [CUSTOMER_UNREACHABLE_STATUS]: 'Kh?ng li?n l?c ???c',
  dang_lam: 'Đang sửa',
  cho_thanh_toan: 'Chờ thanh toán online',
  da_xong: 'Hoàn thành',
  da_huy: 'Đã hủy',
};

const boardViewCopy = {
  pending: {
    eyebrow: 'Nhận việc',
    title: 'Việc mới đang chờ nhận',
    subtitle: 'Duyệt nhanh các yêu cầu mới, kiểm tra mô tả sự cố và quyết định nhận việc ngay trong một luồng gọn.',
    badgeLabel: 'Đơn mới',
  },
  upcoming: {
    eyebrow: 'Lịch làm việc',
    title: 'Lịch làm việc sắp tới',
    subtitle: 'Theo dõi các ca đã xác nhận, nhìn rõ thời gian hẹn và địa điểm để chuẩn bị lộ trình hợp lý.',
    badgeLabel: 'Sắp tới',
  },
  inprogress: {
    eyebrow: 'Đang sửa',
    title: 'Các đơn đang được xử lý',
    subtitle: 'Giữ nhịp cho các ca đang sửa, cập nhật giá và chốt bước tiếp theo mà không phải rời khỏi bảng công việc.',
    badgeLabel: 'Đang sửa',
  },
  payment: {
    eyebrow: 'Thanh toán',
    title: 'Các đơn chờ thanh toán',
    subtitle: 'Ưu tiên các công việc đã hoàn tất sửa chữa nhưng còn bước thanh toán để dòng tiền không bị chậm lại.',
    badgeLabel: 'Chờ thanh toán',
  },
  done: {
    eyebrow: 'Hoàn thành',
    title: 'Lịch sử công việc đã hoàn thành',
    subtitle: 'Xem lại các đơn đã chốt, tổng hợp kết quả xử lý và kiểm tra những lần hoàn thành gần nhất.',
    badgeLabel: 'Hoàn thành',
  },
  cancelled: {
    eyebrow: 'Đã hủy',
    title: 'Những lịch đã bị hủy',
    subtitle: 'Theo dõi các ca không tiếp tục triển khai để đối chiếu nguyên nhân và tránh bỏ sót thông tin quan trọng.',
    badgeLabel: 'Đã hủy',
  },
  all: {
    eyebrow: 'Lịch làm việc',
    title: 'Toàn bộ bảng công việc',
    subtitle: 'Tập trung toàn bộ lịch sửa chữa vào một mặt bằng trực quan để bạn đổi trạng thái và theo dõi nhanh.',
    badgeLabel: 'Toàn bộ',
  },
};

const getBoardViewConfig = (status = window.currentStatus) => boardViewCopy[status] || boardViewCopy.all;

const serviceBadgePresets = [
  { keywords: ['máy lạnh', 'điều hòa'], label: 'AIR CONDITIONING' },
  { keywords: ['tủ lạnh', 'tủ đông'], label: 'COOLING SERVICE' },
  { keywords: ['máy giặt'], label: 'LAUNDRY CARE' },
  { keywords: ['tivi', 'tv'], label: 'ELECTRONIC REPAIR' },
  { keywords: ['bồn cầu', 'vòi', 'ống nước'], label: 'PLUMBING' },
  { keywords: ['điện', 'ổ cắm', 'cầu dao'], label: 'ELECTRIC SERVICE' },
];

const getBookingPaymentMethod = (booking) => booking?.phuong_thuc_thanh_toan === 'transfer' ? 'transfer' : 'cod';
const isCashPaymentBooking = (booking) => getBookingPaymentMethod(booking) === 'cod';

const getServiceBadge = (booking) => {
  const haystack = getBookingServiceNames(booking).toLowerCase();
  const preset = serviceBadgePresets.find((item) => item.keywords.some((keyword) => haystack.includes(keyword)));
  return preset ? preset.label : 'HOME SERVICE';
};

const getPhoneNumber = (booking) => booking?.khach_hang?.phone || '';
const getPhoneHref = (booking) => `tel:${getPhoneNumber(booking).replace(/[^\d+]/g, '')}`;
const getAddress = (booking) => booking?.dia_chi || 'Chưa có địa chỉ';
const getCoordinateValue = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
};
const isValidCoordinatePair = (lat, lng) => Number.isFinite(lat)
  && Number.isFinite(lng)
  && Math.abs(lat) <= 90
  && Math.abs(lng) <= 180
  && !(lat === 0 && lng === 0);
const getBookingDestination = (booking) => {
  const lat = getCoordinateValue(booking?.vi_do);
  const lng = getCoordinateValue(booking?.kinh_do);

  return isValidCoordinatePair(lat, lng) ? { lat, lng } : null;
};
const canOpenRouteGuide = (booking) => booking?.loai_dat_lich === 'at_home'
  && ['da_xac_nhan', CUSTOMER_UNREACHABLE_STATUS, 'dang_lam', 'cho_hoan_thanh', 'cho_thanh_toan'].includes(booking?.trang_thai)
  && Boolean(getBookingDestination(booking));
const formatCoordinatePair = (point) => point && isValidCoordinatePair(point.lat, point.lng)
  ? `${point.lat.toFixed(6)}, ${point.lng.toFixed(6)}`
  : 'Chưa có tọa độ';
const toRadians = (value) => (value * Math.PI) / 180;
const calculateHaversineKm = (fromLat, fromLng, toLat, toLng) => {
  const earthRadiusKm = 6371;
  const dLat = toRadians(toLat - fromLat);
  const dLng = toRadians(toLng - fromLng);
  const a = Math.sin(dLat / 2) ** 2
    + Math.cos(toRadians(fromLat)) * Math.cos(toRadians(toLat)) * Math.sin(dLng / 2) ** 2;
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return earthRadiusKm * c;
};
const formatDistanceLabel = (distanceKm) => {
  if (!Number.isFinite(distanceKm)) {
    return '--';
  }

  if (distanceKm < 1) {
    return `${Math.max(1, Math.round(distanceKm * 1000))} m`;
  }

  return `${distanceKm < 10 ? distanceKm.toFixed(1) : distanceKm.toFixed(0)} km`;
};
const formatLiveUpdatedAt = (value = new Date()) => new Date(value).toLocaleTimeString('vi-VN', {
  hour: '2-digit',
  minute: '2-digit',
  second: '2-digit',
});
const buildExternalDirectionsUrl = (destination, origin = null) => {
  if (origin && destination && isValidCoordinatePair(origin.lat, origin.lng) && isValidCoordinatePair(destination.lat, destination.lng)) {
    const url = new URL('https://www.openstreetmap.org/directions');
    url.searchParams.set('engine', 'fossgis_osrm_car');
    url.searchParams.set('route', `${origin.lat},${origin.lng};${destination.lat},${destination.lng}`);
    return url.toString();
  }

  const url = new URL('https://www.openstreetmap.org/');
  if (destination && isValidCoordinatePair(destination.lat, destination.lng)) {
    url.searchParams.set('mlat', `${destination.lat}`);
    url.searchParams.set('mlon', `${destination.lng}`);
    url.hash = `map=16/${destination.lat}/${destination.lng}`;
  }
  return url.toString();
};
const formatEtaLabel = (seconds) => {
  const totalSeconds = Number(seconds);
  if (!Number.isFinite(totalSeconds) || totalSeconds <= 0) {
    return '--';
  }

  const totalMinutes = Math.max(1, Math.round(totalSeconds / 60));
  if (totalMinutes < 60) {
    return `${totalMinutes} phút`;
  }

  const hours = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;

  if (minutes === 0) {
    return `${hours} giờ`;
  }

  return `${hours} giờ ${minutes} phút`;
};
const hasUpdatedPricing = (booking) => Boolean(booking?.gia_da_cap_nhat);
const getBookingTotal = (booking) => {
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

  return getNumeric(booking?.phi_di_lai)
    + laborTotal
    + partTotal
    + getNumeric(booking?.tien_thue_xe);
};

const getBookingDateLabel = (booking) => {
  if (!booking?.ngay_hen) {
    return 'Chưa xác định';
  }

  const bookingDate = new Date(booking.ngay_hen);
  if (Number.isNaN(bookingDate.getTime())) {
    return 'Chưa xác định';
  }

  return bookingDate.toLocaleDateString('vi-VN', {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const getBookingCardDateLabel = (booking) => {
  if (!booking?.ngay_hen) {
    return 'Chưa chốt ngày';
  }

  const bookingDate = new Date(booking.ngay_hen);
  if (Number.isNaN(bookingDate.getTime())) {
    return 'Chưa chốt ngày';
  }

  const weekdayMap = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
  const weekday = weekdayMap[bookingDate.getDay()] || 'Lịch hẹn';

  return `${weekday}, ${bookingDate.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
  })}`;
};

const isTodayBooking = (booking) => String(booking?.ngay_hen || '').slice(0, 10) === getTodayKey();

const getScheduleLabel = (booking) => {
  const timeRange = booking?.khung_gio_hen || 'Chưa chọn giờ';
  return isTodayBooking(booking) ? `${timeRange} (Hôm nay)` : `${timeRange} · ${getBookingDateLabel(booking)}`;
};

const getBookingPrimaryTimeLabel = (booking) => {
  const timeRange = String(booking?.khung_gio_hen || '').trim();
  const startTime = timeRange.split('-')[0]?.trim();

  if (!startTime) {
    return 'Chưa chốt giờ';
  }

  const normalized = startTime.replace(/\./g, ':');
  const match = normalized.match(/^(\d{1,2}):(\d{2})$/);
  if (!match) {
    return startTime;
  }

  const hours = Number(match[1]);
  const minutes = Number(match[2]);
  if (!Number.isFinite(hours) || !Number.isFinite(minutes)) {
    return startTime;
  }

  const candidate = new Date();
  candidate.setHours(hours, minutes, 0, 0);
  return candidate.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  });
};

const updateBoardSurface = (status = window.currentStatus, totalItems = 0) => {
  const view = getBoardViewConfig(status);
  const totalPages = getTotalPages(status, window.currentScope);
  const startIndex = totalItems ? ((window.currentPage - 1) * JOBS_PER_PAGE) + 1 : 0;
  const endIndex = totalItems ? Math.min(window.currentPage * JOBS_PER_PAGE, totalItems) : 0;

  if (boardIntroEyebrow) {
    boardIntroEyebrow.textContent = view.eyebrow;
  }
  if (boardIntroTitle) {
    boardIntroTitle.textContent = view.title;
  }
  if (boardIntroSubtitle) {
    boardIntroSubtitle.textContent = view.subtitle;
  }
  if (boardStatusChip) {
    boardStatusChip.textContent = `${view.badgeLabel} · ${totalItems} lịch`;
  }
  if (boardScopeChip) {
    boardScopeChip.textContent = window.currentScope === 'today' ? 'Phạm vi: hôm nay' : 'Phạm vi: toàn bộ';
  }
  if (boardControlsMeta) {
    boardControlsMeta.textContent = totalItems
      ? `Hiển thị ${startIndex}-${endIndex} / ${totalItems} lịch · Trang ${window.currentPage}/${totalPages}`
      : 'Chưa có lịch phù hợp trong bộ lọc hiện tại';
  }
};

const getLocationLabel = (booking) => booking?.loai_dat_lich === 'at_home' ? 'Sửa tại nhà' : 'Sửa tại cửa hàng';
const getStatusTone = (booking) => statusToneMap[booking?.trang_thai] || 'upcoming';
const getStatusLabel = (booking) => statusLabelMap[booking?.trang_thai] || 'Đơn công việc';
const getStatusCodeLabel = (booking) => String(booking?.trang_thai || 'booking')
  .replace(/[^a-z0-9_]+/gi, '_')
  .replace(/^_+|_+$/g, '')
  .toUpperCase();

const boardStatusPriority = {
  da_xac_nhan: 1,
  dang_lam: 2,
  cho_hoan_thanh: 3,
  cho_thanh_toan: 4,
  cho_xac_nhan: 5,
  da_xong: 6,
  da_huy: 7,
};

const parseBookingStartDateTime = (booking) => {
  const dateText = String(booking?.ngay_hen || '').slice(0, 10);
  if (!dateText) {
    return Number.MAX_SAFE_INTEGER;
  }

  const timeRange = String(booking?.khung_gio_hen || '');
  const startTime = timeRange.split('-')[0]?.trim() || '00:00';
  const candidate = new Date(`${dateText}T${startTime.length === 5 ? `${startTime}:00` : startTime}`);
  return Number.isNaN(candidate.getTime()) ? Number.MAX_SAFE_INTEGER : candidate.getTime();
};

const compareBoardBookings = (left, right) => {
  const startDiff = parseBookingStartDateTime(left) - parseBookingStartDateTime(right);
  if (startDiff !== 0) {
    return startDiff;
  }

  const statusDiff = (boardStatusPriority[left?.trang_thai] || 99) - (boardStatusPriority[right?.trang_thai] || 99);
  if (statusDiff !== 0) {
    return statusDiff;
  }

  return getNumeric(left?.id) - getNumeric(right?.id);
};

const getScopedBookings = (status = window.currentStatus, scope = window.currentScope) => {
  const filteredByStatus = status === 'all'
    ? [...window.allBookings]
    : window.allBookings.filter((booking) => (statusFilters[status] || statusFilters.all)(booking));

  const filteredByScope = scope === 'today'
    ? filteredByStatus.filter((booking) => isTodayBooking(booking))
    : filteredByStatus;

  return filteredByScope.sort(compareBoardBookings);
};

const getFilterCount = (status) => getScopedBookings(status, 'all').length;
const getTotalPages = (status = window.currentStatus, scope = window.currentScope) => Math.max(1, Math.ceil(getScopedBookings(status, scope).length / JOBS_PER_PAGE));

const buildPaginationModel = (totalPages, currentPage) => {
  if (totalPages <= 1) {
    return [1];
  }

  const items = new Set([1, totalPages, currentPage, currentPage - 1, currentPage + 1]);
  const normalized = Array.from(items)
    .filter((page) => page >= 1 && page <= totalPages)
    .sort((left, right) => left - right);

  return normalized.flatMap((page, index) => {
    const previous = normalized[index - 1];
    if (previous && page - previous > 1) {
      return ['ellipsis', page];
    }
    return [page];
  });
};

const getFirstAddressSegment = (address) => String(address || '')
  .split(',')
  .map((part) => part.trim())
  .filter(Boolean)[0] || 'Điểm đến tiếp theo';

const estimateDriveMinutes = (booking) => {
  const distanceKm = getNumeric(booking?.khoang_cach);
  if (distanceKm <= 0) {
    return null;
  }

  return Math.max(8, Math.round(distanceKm * 2.6));
};

const routeGuideController = createRouteGuideController({
  refs: {
    previewSection: routePreviewSection,
    previewBadge: routePreviewBadge,
    previewTitle: routePreviewTitle,
    previewLocation: routePreviewLocation,
    previewMeta: routePreviewMeta,
    previewAction: routePreviewAction,
    modalEl: routeModalEl,
    modalInstance: routeModalInstance,
    mapCanvas: routeMapCanvas,
    mapFallback: routeMapFallback,
    mapFallbackTitle: routeMapFallbackTitle,
    mapFallbackText: routeMapFallbackText,
    refreshLocationButton: routeRefreshLocationBtn,
    openExternalButton: routeOpenExternalBtn,
    serviceName: routeServiceName,
    destinationAddress: routeDestinationAddress,
    destinationCoords: routeDestinationCoords,
    distanceValue: routeDistanceValue,
    distanceHint: routeDistanceHint,
    etaValue: routeEtaValue,
    etaHint: routeEtaHint,
    currentCoords: routeCurrentCoords,
    lastUpdated: routeLastUpdated,
    trackingStatus: routeTrackingStatus,
    mapStatus: routeMapStatus,
    bookingCode: routeBookingCode,
  },
  routeWorkerMarkerImage,
  getAllBookings: () => window.allBookings,
  openBookingDetails: (bookingId) => window.openViewDetailsModal?.(bookingId),
  showToast,
  escapeHtml,
  getNumeric,
  getBookingServiceNames,
  getAddress,
  getBookingDestination,
  canOpenRouteGuide,
  formatCoordinatePair,
  calculateHaversineKm,
  formatDistanceLabel,
  formatEtaLabel,
  formatLiveUpdatedAt,
  buildExternalDirectionsUrl,
  getLocationLabel,
  getFirstAddressSegment,
  estimateDriveMinutes,
  getScheduleLabel,
});

window.openRouteGuide = (id) => routeGuideController.open(id);

const boardRenderer = createWorkerBoardRenderer({
  refs: {
    bookingsContainer,
    bookingPagination,
    bookingPaginationWrap,
  },
  jobsPerPage: JOBS_PER_PAGE,
  getCurrentStatus: () => window.currentStatus,
  getCurrentScope: () => window.currentScope,
  getCurrentPage: () => window.currentPage,
  setCurrentPage: (page) => {
    window.currentPage = page;
  },
  getScopedBookings,
  getTotalPages,
  buildPaginationModel,
  updateBoardSurface,
  routeGuideController,
  helpers: {
    escapeHtml,
    formatMoney,
    getBookingLaborItems,
    getBookingPartItems,
    getNumeric,
    getBookingServiceNames,
    getCustomerName,
    getPhoneNumber,
    getPhoneHref,
    getAddress,
    getLocationLabel,
    getStatusLabel,
    getStatusTone,
    getBookingCardDateLabel,
    getBookingPrimaryTimeLabel,
    getServiceBadge,
    getBookingTotal,
    hasUpdatedPricing,
    isCashPaymentBooking,
    isClaimableMarketBooking,
    isAssignedPendingBooking,
    canOpenRouteGuide,
  },
});

const {
  renderEmptyState,
  renderLoadingState,
  renderBookings,
} = boardRenderer;

function updateSummary() {
  const summaryBookings = window.allBookings.filter((booking) => isWorkerOwnedBooking(booking));
  const todayBookings = summaryBookings.filter((booking) => isTodayBooking(booking));
  const inProgress = summaryBookings.filter((booking) => booking.trang_thai === 'dang_lam');
  const paymentPending = summaryBookings.filter((booking) => ['cho_hoan_thanh', 'cho_thanh_toan'].includes(booking.trang_thai));
  const projectedIncome = summaryBookings
    .filter((booking) => booking.trang_thai !== 'da_huy')
    .reduce((total, booking) => total + getBookingTotal(booking), 0);

  const summaryTodayCount = document.getElementById('summaryTodayCount');
  const summaryInProgressCount = document.getElementById('summaryInProgressCount');
  const summaryPendingPaymentCount = document.getElementById('summaryPendingPaymentCount');
  const summaryIncomeValue = document.getElementById('summaryIncomeValue');
  const summaryLastUpdated = document.getElementById('summaryLastUpdated');

  if (!summaryTodayCount || !summaryInProgressCount || !summaryPendingPaymentCount || !summaryIncomeValue || !summaryLastUpdated) {
    return;
  }

  summaryTodayCount.textContent = formatCount(todayBookings.length);
  summaryInProgressCount.textContent = formatCount(inProgress.length);
  summaryPendingPaymentCount.textContent = formatCount(paymentPending.length);
  summaryIncomeValue.textContent = formatMoney(projectedIncome);
  summaryLastUpdated.textContent = `Cập nhật lần cuối: ${new Date().toLocaleTimeString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
  })}`;
}

function updateCounters() {
  ['pending', 'upcoming', 'inprogress', 'payment', 'done', 'cancelled'].forEach((status) => {
    const counter = document.getElementById(`cnt-${status}`);
    if (counter) {
      counter.textContent = getFilterCount(status);
    }
  });
}

function hydrateWorkerSummary() {
  const name = user?.name || 'Thợ kỹ thuật';
  const role = user?.role === 'admin' ? 'Quản trị viên kỹ thuật' : 'Thợ kỹ thuật';
  const initial = name.trim().charAt(0).toUpperCase() || 'T';

  const scheduleWorkerName = document.getElementById('scheduleWorkerName');
  const scheduleWorkerRole = document.getElementById('scheduleWorkerRole');
  const scheduleWorkerInitial = document.getElementById('scheduleWorkerInitial');

  if (!scheduleWorkerName || !scheduleWorkerRole || !scheduleWorkerInitial) {
    return;
  }

  scheduleWorkerName.textContent = name;
  scheduleWorkerRole.textContent = role;
  scheduleWorkerInitial.textContent = initial;
}

function hydrateTopbarIdentity() {
  setAvatarContent(topAvatar, user?.avatar, user?.name || 'Thợ kỹ thuật');
}

const topbarNotificationCenter = createTopbarNotificationCenter({
  callApi,
  showToast,
  escapeHtml,
  getNumeric,
  user,
  refs: {
    button: topNotificationButton,
    badge: topNotificationBadge,
    menu: topNotificationMenu,
    list: topNotificationList,
    markAllButton: topNotificationMarkAll,
  },
  buildWorkerBookingsHref,
});

function syncBoardStatusTabs() {
  boardStatusTabs.forEach((tab) => {
    tab.classList.toggle('is-active', tab.dataset.boardStatus === window.currentStatus);
  });
}

function syncBookingScopeButtons() {
  bookingScopeButtons.forEach((button) => {
    button.classList.toggle('is-active', button.dataset.bookingScope === window.currentScope);
  });
}

function focusBookingFromQuery() {
  const bookingId = Number(window.pendingBookingIdToOpen || 0);
  if (!Number.isFinite(bookingId) || bookingId <= 0) {
    return;
  }

  const booking = window.allBookings.find((item) => item.id === bookingId);
  window.pendingBookingIdToOpen = 0;

  if (!booking) {
    syncWorkerBookingsUrl({ bookingId: 0 });
    return;
  }

  window.currentStatus = statusToneMap[booking.trang_thai] || window.currentStatus;
  window.currentScope = 'all';
  window.currentPage = 1;
  syncBoardStatusTabs();
  syncBookingScopeButtons();
  renderBookings(window.currentStatus);

  window.requestAnimationFrame(() => {
    window.openViewDetailsModal(booking.id, { syncUrl: false });
  });
}

boardStatusTabs.forEach((tab) => {
  tab.addEventListener('click', () => {
    const status = tab.dataset.boardStatus || 'all';
    if (status === window.currentStatus) {
      return;
    }

    window.currentStatus = status;
    window.currentPage = 1;
    syncBoardStatusTabs();
    syncWorkerBookingsUrl({ bookingId: 0 });
    renderBookings(status);
  });
});

bookingScopeButtons.forEach((button) => {
  button.addEventListener('click', () => {
    const scope = button.dataset.bookingScope || 'all';
    if (scope === window.currentScope) {
      return;
    }

    window.currentScope = scope;
    window.currentPage = 1;
    syncBookingScopeButtons();
    syncWorkerBookingsUrl({ bookingId: 0 });
    renderBookings(window.currentStatus);
  });
});

bookingPagination?.addEventListener('click', (event) => {
  const target = event.target instanceof Element ? event.target.closest('[data-page-number], [data-page-action]') : null;
  if (!target) {
    return;
  }

  if (target.hasAttribute('data-page-number')) {
    const nextPage = getNumeric(target.getAttribute('data-page-number'));
    if (nextPage > 0) {
      window.currentPage = nextPage;
      renderBookings(window.currentStatus);
    }
    return;
  }

  const action = target.getAttribute('data-page-action');
  if (action === 'prev' && window.currentPage > 1) {
    window.currentPage -= 1;
    renderBookings(window.currentStatus);
  }
  if (action === 'next' && window.currentPage < getTotalPages(window.currentStatus, window.currentScope)) {
    window.currentPage += 1;
    renderBookings(window.currentStatus);
  }
});

window.switchTab = function(el, status) {
  window.currentStatus = status;
  window.currentPage = 1;
  syncBoardStatusTabs();
  syncWorkerBookingsUrl({ bookingId: 0 });
  renderBookings(status);
};

window.loadMyBookings = async function(status = window.currentStatus) {
  if (!window.allBookings.length) {
    renderLoadingState();
  }

  try {
    const [assignedResponse, availableResponse] = await Promise.all([
      callApi('/don-dat-lich', 'GET'),
      callApi('/don-dat-lich/available', 'GET'),
    ]);

    if (!assignedResponse.ok) {
      showToast(assignedResponse.data?.message || 'Không thể tải lịch làm việc.', 'error');
      renderEmptyState(status);
      return;
    }

    if (!availableResponse.ok) {
      showToast(availableResponse.data?.message || 'Không thể tải danh sách nhận việc.', 'error');
      renderEmptyState(status);
      return;
    }

    window.assignedBookings = getApiCollection(assignedResponse.data);
    window.availableBookings = getApiCollection(availableResponse.data);
    rebuildWorkerBookings();
    updateCounters();
    updateSummary();
    renderBookings(status);
    focusBookingFromQuery();
  } catch (error) {
    console.error(error);
    showToast('Lỗi kết nối khi tải lịch làm việc.', 'error');
    renderEmptyState(status);
  }
};

window.claimJob = async function(id) {
  try {
    const response = await callApi(`/don-dat-lich/${id}/claim`, 'POST');

    if (!response.ok) {
      showToast(response.data?.message || 'Không thể nhận đơn này.', 'error');
      return;
    }

    showToast(response.data?.message || 'Đã nhận đơn thành công.');
    window.currentStatus = 'upcoming';
    window.currentPage = 1;
    syncBoardStatusTabs();
    syncWorkerBookingsUrl({ bookingId: id });
    await loadMyBookings('upcoming');
  } catch (error) {
    console.error(error);
    showToast('Lỗi kết nối khi nhận đơn.', 'error');
  }
};

window.updateStatus = async function(id, newStatus) {
  try {
    const response = await callApi(`/don-dat-lich/${id}/status`, 'PUT', { trang_thai: newStatus });

    if (!response.ok) {
      showToast(response.data?.message || 'Không thể cập nhật trạng thái đơn.', 'error');
      return;
    }

    showToast('Đã cập nhật trạng thái thành công.');
    await loadMyBookings(window.currentStatus);
  } catch (error) {
    showToast('Lỗi kết nối khi cập nhật trạng thái.', 'error');
  }
};

window.reportCustomerUnreachable = async function(id) {
  const booking = window.allBookings.find((item) => Number(item?.id) === Number(id));
  if (!booking) {
    showToast('Không tìm thấy đơn cần báo admin.', 'error');
    return;
  }

  const currentReporterName = booking?.worker_contact_issue?.reporter_name || user?.name || '';
  const currentCalledPhone = booking?.worker_contact_issue?.called_phone || getPhoneNumber(booking) || '';
  let reporterName = currentReporterName.trim();
  let calledPhone = currentCalledPhone.trim();

  if (typeof Swal !== 'undefined') {
    const promptResult = await Swal.fire({
      title: 'Không liên lạc được với khách hàng',
      html: `
        <div style="display:grid; gap:0.9rem; text-align:left;">
          <div>
            <label for="workerContactReporterName" style="display:block; margin-bottom:0.35rem; font-weight:700; color:#1f2937;">
              Nhập tên người vừa gọi cho khách hàng
            </label>
            <input
              id="workerContactReporterName"
              class="swal2-input"
              style="margin:0; width:100%;"
              placeholder="Ví dụ: Lê Văn Lương"
              value="${escapeHtml(currentReporterName)}"
            >
          </div>
          <div>
            <label for="workerContactCalledPhone" style="display:block; margin-bottom:0.35rem; font-weight:700; color:#1f2937;">
              Nhập số điện thoại bạn vừa gọi cho khách
            </label>
            <input
              id="workerContactCalledPhone"
              class="swal2-input"
              style="margin:0; width:100%;"
              placeholder="Ví dụ: 0799462980"
              value="${escapeHtml(currentCalledPhone)}"
            >
          </div>
        </div>
      `,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: 'Xác nhận báo admin',
      cancelButtonText: 'Đóng',
      preConfirm: () => {
        const popup = Swal.getPopup();
        const reporterInput = popup?.querySelector('#workerContactReporterName');
        const calledPhoneField = popup?.querySelector('#workerContactCalledPhone');
        const nextReporterName = String(reporterInput?.value || '').trim();
        const nextCalledPhone = String(calledPhoneField?.value || '').trim();

        if (!nextReporterName) {
          Swal.showValidationMessage('Vui lòng nhập tên người vừa gọi cho khách.');
          return false;
        }

        if (!nextCalledPhone) {
          Swal.showValidationMessage('Vui lòng nhập số điện thoại bạn vừa gọi cho khách.');
          return false;
        }

        return {
          reporterName: nextReporterName,
          calledPhone: nextCalledPhone,
        };
      },
    });

    if (!promptResult.isConfirmed) {
      return;
    }

    reporterName = promptResult.value?.reporterName || '';
    calledPhone = promptResult.value?.calledPhone || '';
  } else {
    const reporterNameInput = window.prompt(
      'Nhập tên người vừa gọi cho khách hàng:',
      currentReporterName,
    );

    if (reporterNameInput === null) {
      return;
    }

    reporterName = reporterNameInput.trim();
    if (!reporterName) {
      showToast('Vui lòng nhập tên người vừa gọi cho khách.', 'error');
      return;
    }

    const calledPhoneInput = window.prompt(
      'Nhập số điện thoại bạn vừa gọi cho khách:',
      currentCalledPhone,
    );

    if (calledPhoneInput === null) {
      return;
    }

    calledPhone = calledPhoneInput.trim();
    if (!calledPhone) {
      showToast('Vui lòng nhập số điện thoại bạn vừa gọi cho khách.', 'error');
      return;
    }
  }

  try {
    const response = await callApi(`/don-dat-lich/${id}/report-customer-unreachable`, 'POST', {
      reporter_name: reporterName,
      called_phone: calledPhone,
    });

    if (!response.ok) {
      showToast(response.data?.message || 'Không thể báo admin hỗ trợ liên hệ.', 'error');
      return;
    }

    showToast(response.data?.message || 'Đã báo admin hỗ trợ liên hệ khách hàng.');
    const shouldRefreshDetail = Number(window.activeBookingId || 0) === Number(id);
    await loadMyBookings(window.currentStatus);
    if (shouldRefreshDetail) {
      openViewDetailsModal(id, { syncUrl: false });
    }
  } catch (error) {
    console.error(error);
    showToast('Lỗi kết nối khi gửi báo cáo liên hệ.', 'error');
  }
};

window.confirmCashPayment = async function(id) {
  if (!confirm('Bạn xác nhận đã thu đủ tiền mặt cho đơn này?')) {
    return;
  }

  try {
    const response = await callApi(`/bookings/${id}/confirm-cash-payment`, 'POST');

    if (!response.ok) {
      showToast(response.data?.message || 'Không thể xác nhận đã thu tiền mặt.', 'error');
      return;
    }

    showToast(response.data?.message || 'Đã xác nhận thu tiền mặt và hoàn tất đơn.');
    await loadMyBookings(window.currentStatus);
  } catch (error) {
    showToast('Lỗi kết nối khi xác nhận tiền mặt.', 'error');
  }
};

window.confirmPartWarranty = async function(id, partIndex) {
  const booking = window.allBookings.find((item) => item.id === id);
  const partItem = getBookingPartItems(booking || {})[partIndex];

  if (!booking || !partItem) {
    showToast('Không tìm thấy linh kiện cần xác nhận bảo hành.', 'error');
    return;
  }

  if (!confirm(`Xác nhận linh kiện "${partItem.noi_dung || 'Linh kiện'}" đã sử dụng bảo hành?`)) {
    return;
  }

  try {
    const response = await callApi(`/don-dat-lich/${id}/parts/${partIndex}/confirm-warranty`, 'POST');

    if (!response.ok) {
      showToast(response.data?.message || 'Không thể xác nhận bảo hành.', 'error');
      return;
    }

    showToast(response.data?.message || 'Đã xác nhận sử dụng bảo hành.');
    await loadMyBookings(window.currentStatus);
    openViewDetailsModal(id);
  } catch (error) {
    showToast('Lỗi kết nối khi xác nhận bảo hành.', 'error');
  }
};

const pricingModalController = createPricingModalController({
  getAllBookings: () => window.allBookings,
  afterSubmit: async () => {
    await loadMyBookings(window.currentStatus);
  },
});

window.openCostModal = (id) => pricingModalController.open(id);

const bookingDetailModalController = createBookingDetailModalController({
  getAllBookings: () => window.allBookings,
  getActiveBookingId: () => window.activeBookingId,
  setActiveBookingId: (bookingId) => {
    window.activeBookingId = Number(bookingId || 0);
  },
  syncWorkerBookingsUrl,
  workerId: user?.id,
  getBookingDateLabel,
  getPhoneNumber,
  getPhoneHref,
  getAddress,
  getBookingTotal,
  getStatusLabel,
  getLocationLabel,
});

window.openViewDetailsModal = (id, options) => bookingDetailModalController.open(id, options);
const completeBookingModalController = createCompleteBookingModalController({
  baseUrl,
  refs: {
    form: completeForm,
    modalInstance: completeModalInstance,
    bookingIdInput: completeBookingId,
    customerName: completeCustomerName,
    serviceName: completeServiceName,
    bookingTotal: completeBookingTotal,
    statusBadge: completeStatusBadge,
    paymentMethodTitle: completePaymentMethodTitle,
    paymentMethodHint: completePaymentMethodHint,
    paymentMethodBadge: completePaymentMethodBadge,
    paymentMethodInputs: completePaymentMethodInputs,
    paymentOptions: completePaymentOptions,
    pricingAlert: completePricingAlert,
    workflowList: completeWorkflowList,
    imageUploadPreview,
    videoUploadPreview,
    imageInput: inputHinhAnhKetQua,
    videoInput: inputVideoKetQua,
    submitButton: btnSubmitCompleteForm,
  },
  getAllBookings: () => window.allBookings,
  hasUpdatedPricing,
  openCostModal: (id) => window.openCostModal?.(id),
  showToast,
  escapeHtml,
  formatMoney,
  getCustomerName,
  getBookingServiceNames,
  getBookingTotal,
  getBookingPaymentMethod,
  afterSubmit: async ({ bookingId, paymentMethod }) => {
    window.currentStatus = paymentMethod === 'transfer' ? 'payment' : 'done';
    window.currentPage = 1;
    syncBoardStatusTabs();
    syncWorkerBookingsUrl({ bookingId: paymentMethod === 'transfer' ? bookingId : 0 });
    await loadMyBookings(window.currentStatus);
  },
});

window.openCompleteModal = (id) => completeBookingModalController.open(id);

hydrateWorkerSummary();
hydrateTopbarIdentity();
pricingModalController.init();
bookingDetailModalController.init();
syncBoardStatusTabs();
syncBookingScopeButtons();
completeBookingModalController.init();
routeGuideController.init();
topbarNotificationCenter.init();
loadMyBookings(window.currentStatus);


