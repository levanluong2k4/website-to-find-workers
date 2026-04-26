export const getBookingServices = (booking) => {
  const relationServices = [
    booking?.dich_vus,
    booking?.dichVus,
    booking?.services,
  ].find((value) => Array.isArray(value) && value.length > 0);

  if (relationServices) {
    return relationServices.filter(Boolean);
  }

  const singleService = booking?.dich_vu || booking?.dichVu || null;

  return singleService ? [singleService] : [];
};

export const getBookingServiceNames = (booking) => {
  const seen = new Set();

  return getBookingServices(booking)
    .map((service) => String(service?.ten_dich_vu || service?.name || '').trim())
    .filter((serviceName) => {
      if (!serviceName) {
        return false;
      }

      const normalizedName = serviceName.toLowerCase();
      if (seen.has(normalizedName)) {
        return false;
      }

      seen.add(normalizedName);

      return true;
    });
};

export const getBookingServiceTitle = (
  booking,
  {
    fallback = 'Dịch vụ sửa chữa',
    separator = ' · ',
  } = {},
) => {
  const serviceNames = getBookingServiceNames(booking);

  return serviceNames.length ? serviceNames.join(separator) : fallback;
};

export const getBookingPaymentMethod = (booking) => booking?.phuong_thuc_thanh_toan === 'transfer' ? 'transfer' : 'cod';

export const isCashPaymentBooking = (booking) => getBookingPaymentMethod(booking) === 'cod';

export const getBookingRebookPayload = (booking) => {
  const services = getBookingServices(booking);
  const serviceIds = services
    .map((service) => Number(service?.id || 0))
    .filter((serviceId) => Number.isInteger(serviceId) && serviceId > 0);
  const firstServiceName = getBookingServiceNames(booking)[0] || '';
  const workerId = Number(booking?.tho?.id || booking?.tho_id || 0);

  return {
    workerId: workerId > 0 ? workerId : null,
    serviceIds,
    serviceName: firstServiceName,
  };
};

export const openRebookBooking = (
  booking,
  {
    bookingWizard = window.BookingWizardModal,
    targetPath = '/customer/booking',
  } = {},
) => {
  const payload = getBookingRebookPayload(booking);

  if (bookingWizard?.open) {
    bookingWizard.open(payload);
    return;
  }

  const targetUrl = new URL(targetPath, window.location.origin);

  if (payload.workerId) {
    targetUrl.searchParams.set('worker_id', String(payload.workerId));
  }

  if (payload.serviceIds.length) {
    targetUrl.searchParams.set('service_ids', payload.serviceIds.join(','));
  } else if (payload.serviceName) {
    targetUrl.searchParams.set('service_name', payload.serviceName);
  }

  window.location.href = targetUrl.toString();
};

const PAYMENT_GATEWAY_STYLE_ID = 'paymentGatewaySelectionStyles';

const escapeHtml = (value = '') => String(value)
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#039;');

const formatMoney = (value) => `${Number(value || 0).toLocaleString('vi-VN')}đ`;

const normalizeLookupKey = (value = '') => String(value)
  .toLowerCase()
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .replace(/đ/g, 'd')
  .replace(/[^a-z0-9]+/g, ' ')
  .trim();

const getStoredCostItems = (booking, key) => Array.isArray(booking?.[key]) ? booking[key].filter(Boolean) : [];

const getBookingTotal = (booking) => {
  const total = Number(booking?.tong_tien || 0);
  if (total > 0) {
    return total;
  }

  const laborItems = getStoredCostItems(booking, 'chi_tiet_tien_cong');
  const partItems = getStoredCostItems(booking, 'chi_tiet_linh_kien');
  const laborTotal = laborItems.length
    ? laborItems.reduce((sum, item) => sum + Number(item?.so_tien || 0), 0)
    : Number(booking?.tien_cong || 0);
  const partTotal = partItems.length
    ? partItems.reduce((sum, item) => sum + Number(item?.so_tien || 0), 0)
    : Number(booking?.phi_linh_kien || 0);

  return Number(booking?.phi_di_lai || 0) + laborTotal + partTotal + Number(booking?.tien_thue_xe || 0);
};

const getGatewayCatalog = ({ isLocalPaymentSandbox = false } = {}) => ({
  momo_atm: {
    badge: 'MoMo',
    badgeTone: 'momo',
    title: 'Ví MoMo',
    subtitle: isLocalPaymentSandbox ? 'ATM / test card trên web' : 'Nạp tiền và thanh toán nhanh',
    supportText: isLocalPaymentSandbox
      ? 'Phù hợp cho môi trường local và tài khoản test.'
      : 'Mở ứng dụng MoMo hoặc thẻ ATM để xác nhận giao dịch.',
    summaryTitle: 'MoMo ATM',
    summaryCopy: isLocalPaymentSandbox
      ? 'Hệ thống sẽ mở trang test card để bạn xác nhận giao dịch.'
      : 'Hệ thống sẽ chuyển bạn sang MoMo để xác nhận giao dịch.',
  },
  vnpay: {
    badge: 'VNPAY',
    badgeTone: 'vnpay',
    title: 'VNPAY',
    subtitle: 'Thẻ ATM, Visa, Mastercard',
    supportText: 'Hỗ trợ thanh toán qua ngân hàng nội địa và thẻ quốc tế.',
    summaryTitle: 'Cổng VNPAY',
    summaryCopy: 'Hệ thống sẽ chuyển bạn sang cổng thanh toán ngân hàng đã chọn.',
  },
  zalopay: {
    badge: 'ZaloPay',
    badgeTone: 'zalopay',
    title: 'ZaloPay',
    subtitle: 'Ví điện tử và QR code',
    supportText: 'Phù hợp nếu bạn muốn quét QR hoặc thanh toán bằng ví ZaloPay.',
    summaryTitle: 'Ví ZaloPay',
    summaryCopy: 'Hệ thống sẽ chuyển bạn sang trang thanh toán ZaloPay để xác nhận.',
  },
});

export const buildOnlineGatewayOptions = ({ isLocalPaymentSandbox = false } = {}) => {
  const gatewayCatalog = getGatewayCatalog({ isLocalPaymentSandbox });

  return Object.fromEntries(
    Object.entries(gatewayCatalog).map(([key, option]) => [key, option.title]),
  );
};

const getServicePreview = (booking) => {
  const services = getBookingServices(booking);
  const firstService = services[0] || {};
  const normalizedServiceName = normalizeLookupKey(firstService?.ten_dich_vu || '');

  let icon = 'build';
  let accentClass = 'is-generic';

  if (normalizedServiceName.includes('dieu hoa') || normalizedServiceName.includes('may lanh')) {
    icon = 'ac_unit';
    accentClass = 'is-cool';
  } else if (normalizedServiceName.includes('giat')) {
    icon = 'local_laundry_service';
    accentClass = 'is-laundry';
  } else if (normalizedServiceName.includes('dien')) {
    icon = 'electrical_services';
    accentClass = 'is-electric';
  } else if (normalizedServiceName.includes('nuoc') || normalizedServiceName.includes('ong')) {
    icon = 'water_drop';
    accentClass = 'is-water';
  } else if (normalizedServiceName.includes('tu lanh')) {
    icon = 'kitchen';
    accentClass = 'is-cool';
  }

  const dateValue = String(booking?.ngay_hen || '').trim();
  const formattedDate = /^\d{4}-\d{2}-\d{2}$/.test(dateValue)
    ? dateValue.split('-').reverse().join('/')
    : dateValue;
  const scheduleParts = [formattedDate, String(booking?.khung_gio_hen || '').trim()].filter(Boolean);
  const scheduleText = scheduleParts.join(' • ') || 'Chờ xác nhận lịch thanh toán';

  return {
    title: getBookingServiceTitle(booking),
    meta: services.length > 1
      ? `${services.length} dịch vụ trong đơn • ${scheduleText}`
      : scheduleText,
    image: firstService?.hinh_anh || '',
    icon,
    accentClass,
  };
};

const buildPaymentBreakdown = (booking) => {
  const laborItems = getStoredCostItems(booking, 'chi_tiet_tien_cong');
  const partItems = getStoredCostItems(booking, 'chi_tiet_linh_kien');

  const laborTotal = laborItems.length
    ? laborItems.reduce((sum, item) => sum + Number(item?.so_tien || 0), 0)
    : Number(booking?.tien_cong || 0);
  const partTotal = partItems.length
    ? partItems.reduce((sum, item) => sum + Number(item?.so_tien || 0), 0)
    : Number(booking?.phi_linh_kien || 0);
  const travelFee = Number(booking?.phi_di_lai || 0);
  const transportFee = Number(booking?.tien_thue_xe || 0);
  const total = getBookingTotal(booking);

  const rows = [];

  if (laborTotal > 0) {
    rows.push({ label: 'Tiền công', value: laborTotal });
  }

  if (partTotal > 0) {
    rows.push({ label: 'Linh kiện', value: partTotal });
  }

  rows.push({ label: 'Phí di chuyển', value: travelFee });

  if (transportFee > 0) {
    rows.push({ label: 'Xe chở thiết bị', value: transportFee });
  }

  if (!rows.length) {
    rows.push({ label: 'Tạm tính', value: total });
  }

  return {
    rows,
    total,
  };
};

const buildGatewayPopupMarkup = ({
  booking,
  gatewayCatalog,
  defaultGateway,
}) => {
  const safeBooking = booking || {};
  const servicePreview = getServicePreview(safeBooking);
  const paymentBreakdown = buildPaymentBreakdown(safeBooking);
  const currentGateway = gatewayCatalog[defaultGateway] || gatewayCatalog.momo_atm;
  const total = paymentBreakdown.total;

  return `
    <div class="payment-gateway-shell">
      <section class="payment-gateway-panel payment-gateway-panel--form">
        <div class="payment-gateway-header">
          <button type="button" class="payment-gateway-back" data-payment-dismiss aria-label="Quay lại">
            <span class="material-symbols-outlined">arrow_back</span>
          </button>
          <div>
            <h2>Thanh toán đơn hàng</h2>
            <p>Chọn cổng thanh toán để hoàn tất đơn đang chờ thanh toán online.</p>
          </div>
        </div>

        <div class="payment-gateway-section-label">Chọn phương thức thanh toán</div>

        <div class="payment-gateway-options">
          ${Object.entries(gatewayCatalog).map(([key, option]) => `
            <label class="payment-gateway-option${key === defaultGateway ? ' is-selected' : ''}" data-gateway-option="${escapeHtml(key)}">
              <input
                class="payment-gateway-input"
                type="radio"
                name="paymentGatewayOption"
                value="${escapeHtml(key)}"
                ${key === defaultGateway ? 'checked' : ''}
              >

              <div class="payment-gateway-badge payment-gateway-badge--${escapeHtml(option.badgeTone)}">${escapeHtml(option.badge)}</div>

              <div class="payment-gateway-copy">
                <div class="payment-gateway-option-title">${escapeHtml(option.title)}</div>
                <div class="payment-gateway-option-subtitle">${escapeHtml(option.subtitle)}</div>
                <p>${escapeHtml(option.supportText)}</p>
              </div>

              <span class="payment-gateway-radio" aria-hidden="true"></span>
            </label>
          `).join('')}
        </div>
      </section>

      <aside class="payment-gateway-panel payment-gateway-panel--summary">
        <div class="payment-gateway-summary-label">CHI TIẾT ĐƠN HÀNG</div>

        <div class="payment-gateway-order-card">
          <div class="payment-gateway-order-visual ${escapeHtml(servicePreview.accentClass)}">
            ${servicePreview.image
              ? `<img src="${escapeHtml(servicePreview.image)}" alt="${escapeHtml(servicePreview.title)}">`
              : `<span class="material-symbols-outlined">${escapeHtml(servicePreview.icon)}</span>`}
          </div>
          <div class="payment-gateway-order-copy">
            <div class="payment-gateway-order-title">${escapeHtml(servicePreview.title)}</div>
            <div class="payment-gateway-order-meta">${escapeHtml(servicePreview.meta)}</div>
            <div class="payment-gateway-order-code">Mã đơn #${escapeHtml(safeBooking?.id || '')}</div>
          </div>
          <div class="payment-gateway-order-price">${formatMoney(total)}</div>
        </div>

        <div class="payment-gateway-breakdown">
          ${paymentBreakdown.rows.map((row) => `
            <div class="payment-gateway-breakdown-row">
              <span>${escapeHtml(row.label)}</span>
              <strong>${formatMoney(row.value)}</strong>
            </div>
          `).join('')}
        </div>

        <div class="payment-gateway-channel-card">
          <span>Kênh đã chọn</span>
          <strong data-selected-gateway-name>${escapeHtml(currentGateway.summaryTitle)}</strong>
          <p data-selected-gateway-copy>${escapeHtml(currentGateway.summaryCopy)}</p>
        </div>

        <div class="payment-gateway-total">
          <div>
            <span>Tổng thanh toán</span>
            <p>Đã bao gồm toàn bộ phí hiện có</p>
          </div>
          <strong>${formatMoney(total)}</strong>
        </div>

        <div class="payment-gateway-actions">
          <button type="button" class="payment-gateway-submit" data-payment-confirm>
            <span class="material-symbols-outlined">lock</span>
            Thanh toán an toàn
          </button>
          <button type="button" class="payment-gateway-cancel" data-payment-dismiss>Quay lại</button>
        </div>
      </aside>
    </div>
  `;
};

const ensureGatewayPopupStyles = () => {
  if (document.getElementById(PAYMENT_GATEWAY_STYLE_ID)) {
    return;
  }

  const style = document.createElement('style');
  style.id = PAYMENT_GATEWAY_STYLE_ID;
  style.textContent = `
    .payment-gateway-popup {
      width: min(1040px, calc(100vw - 1.5rem)) !important;
      max-width: min(1040px, calc(100vw - 1.5rem)) !important;
      padding: 0 !important;
      border-radius: 32px !important;
      overflow: hidden !important;
      background: #f4f8ff !important;
      box-shadow: 0 32px 90px rgba(15, 23, 42, 0.24) !important;
    }

    .payment-gateway-popup .swal2-html-container {
      margin: 0 !important;
      padding: 0 !important;
      overflow: visible !important;
    }

    .payment-gateway-popup .swal2-validation-message {
      margin: 0 2rem 1.5rem !important;
      border-radius: 16px !important;
      background: #fff4f4 !important;
      color: #d14343 !important;
      font-size: 0.92rem !important;
    }

    .payment-gateway-shell {
      display: grid;
      grid-template-columns: minmax(0, 1.45fr) minmax(320px, 0.95fr);
      min-height: 560px;
      color: #0f172a;
      text-align: left;
    }

    .payment-gateway-panel {
      padding: 2rem;
    }

    .payment-gateway-panel--form {
      background:
        radial-gradient(circle at top left, rgba(96, 165, 250, 0.14), transparent 18rem),
        linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
    }

    .payment-gateway-panel--summary {
      border-left: 1px solid rgba(191, 219, 254, 0.65);
      background:
        radial-gradient(circle at top right, rgba(59, 130, 246, 0.14), transparent 16rem),
        linear-gradient(180deg, #f9fbff 0%, #f2f7ff 100%);
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
    }

    .payment-gateway-header {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      margin-bottom: 1.6rem;
    }

    .payment-gateway-header h2 {
      margin: 0;
      font-size: clamp(1.8rem, 2.6vw, 2.35rem);
      line-height: 1.05;
      font-weight: 800;
      letter-spacing: -0.04em;
      color: #0f172a;
    }

    .payment-gateway-header p {
      margin: 0.55rem 0 0;
      color: #64748b;
      font-size: 0.98rem;
      line-height: 1.6;
      max-width: 32rem;
    }

    .payment-gateway-back {
      width: 3rem;
      height: 3rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid #d8e4f2;
      border-radius: 999px;
      background: #fff;
      color: #173b7a;
      box-shadow: 0 8px 18px rgba(37, 99, 235, 0.08);
      transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
    }

    .payment-gateway-back:hover {
      transform: translateX(-1px);
      border-color: #93c5fd;
      box-shadow: 0 14px 24px rgba(37, 99, 235, 0.14);
    }

    .payment-gateway-back .material-symbols-outlined {
      font-size: 1.35rem;
    }

    .payment-gateway-section-label,
    .payment-gateway-summary-label {
      color: #0f172a;
      font-size: 0.95rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .payment-gateway-summary-label {
      color: #1e293b;
    }

    .payment-gateway-options {
      display: grid;
      gap: 1rem;
      margin-top: 1rem;
    }

    .payment-gateway-option {
      position: relative;
      display: grid;
      grid-template-columns: auto minmax(0, 1fr) auto;
      align-items: center;
      gap: 1rem;
      padding: 1.15rem 1.2rem;
      border-radius: 22px;
      border: 1.5px solid #dbe6f2;
      background: rgba(255, 255, 255, 0.92);
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
      cursor: pointer;
      transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease, background 0.16s ease;
    }

    .payment-gateway-option:hover {
      transform: translateY(-1px);
      border-color: #93c5fd;
      box-shadow: 0 16px 30px rgba(37, 99, 235, 0.12);
    }

    .payment-gateway-option.is-selected {
      border-color: #1d4ed8;
      background: linear-gradient(180deg, rgba(239, 246, 255, 0.98), rgba(230, 238, 255, 0.96));
      box-shadow: 0 18px 36px rgba(37, 99, 235, 0.18);
    }

    .payment-gateway-input {
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }

    .payment-gateway-badge {
      min-width: 4.75rem;
      height: 3.1rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 16px;
      padding: 0 0.85rem;
      color: #fff;
      font-size: 0.88rem;
      font-weight: 800;
      letter-spacing: 0.02em;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2);
    }

    .payment-gateway-badge--momo {
      background: linear-gradient(135deg, #ae2070 0%, #d946ef 100%);
    }

    .payment-gateway-badge--vnpay {
      background: linear-gradient(135deg, #003087 0%, #2563eb 100%);
    }

    .payment-gateway-badge--zalopay {
      background: linear-gradient(135deg, #0f62fe 0%, #06b6d4 100%);
    }

    .payment-gateway-copy {
      min-width: 0;
    }

    .payment-gateway-option-title {
      color: #0f172a;
      font-size: 1.25rem;
      font-weight: 800;
      letter-spacing: -0.03em;
    }

    .payment-gateway-option-subtitle {
      margin-top: 0.2rem;
      color: #1d4ed8;
      font-size: 0.92rem;
      font-weight: 700;
    }

    .payment-gateway-option p {
      margin: 0.5rem 0 0;
      color: #64748b;
      font-size: 0.9rem;
      line-height: 1.55;
    }

    .payment-gateway-radio {
      width: 1.7rem;
      height: 1.7rem;
      flex-shrink: 0;
      border-radius: 999px;
      border: 2px solid #c7d2fe;
      position: relative;
      transition: border-color 0.16s ease, background 0.16s ease;
    }

    .payment-gateway-radio::after {
      content: '';
      position: absolute;
      inset: 0.22rem;
      border-radius: 999px;
      background: transparent;
      transition: background 0.16s ease;
    }

    .payment-gateway-option.is-selected .payment-gateway-radio {
      border-color: #1d4ed8;
    }

    .payment-gateway-option.is-selected .payment-gateway-radio::after {
      background: #1d4ed8;
    }

    .payment-gateway-order-card {
      display: grid;
      grid-template-columns: auto minmax(0, 1fr) auto;
      gap: 1rem;
      align-items: center;
      padding: 1.1rem;
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(191, 219, 254, 0.9);
      box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    }

    .payment-gateway-order-visual {
      width: 5rem;
      height: 5rem;
      border-radius: 20px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
      color: #1d4ed8;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.55);
    }

    .payment-gateway-order-visual img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .payment-gateway-order-visual .material-symbols-outlined {
      font-size: 1.9rem;
    }

    .payment-gateway-order-visual.is-cool {
      background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    }

    .payment-gateway-order-visual.is-water {
      background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%);
    }

    .payment-gateway-order-visual.is-electric {
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      color: #b45309;
    }

    .payment-gateway-order-visual.is-laundry {
      background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
      color: #6d28d9;
    }

    .payment-gateway-order-title {
      color: #111827;
      font-size: 1.2rem;
      font-weight: 800;
      line-height: 1.3;
      letter-spacing: -0.03em;
    }

    .payment-gateway-order-meta,
    .payment-gateway-order-code {
      color: #64748b;
      font-size: 0.88rem;
      line-height: 1.5;
    }

    .payment-gateway-order-code {
      margin-top: 0.3rem;
      font-weight: 700;
    }

    .payment-gateway-order-price {
      color: #0f172a;
      font-size: 1.35rem;
      font-weight: 800;
      letter-spacing: -0.03em;
      white-space: nowrap;
    }

    .payment-gateway-breakdown {
      display: grid;
      gap: 0.78rem;
      padding-bottom: 1rem;
      border-bottom: 1px dashed rgba(148, 163, 184, 0.55);
    }

    .payment-gateway-breakdown-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      color: #334155;
      font-size: 1rem;
    }

    .payment-gateway-breakdown-row strong {
      color: #0f172a;
      font-weight: 700;
      white-space: nowrap;
    }

    .payment-gateway-channel-card {
      padding: 1rem 1.05rem;
      border-radius: 20px;
      background: rgba(219, 234, 254, 0.72);
      color: #173b7a;
    }

    .payment-gateway-channel-card span {
      display: block;
      font-size: 0.78rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #2563eb;
    }

    .payment-gateway-channel-card strong {
      display: block;
      margin-top: 0.35rem;
      font-size: 1.02rem;
      font-weight: 800;
    }

    .payment-gateway-channel-card p {
      margin: 0.45rem 0 0;
      color: #4b5563;
      font-size: 0.9rem;
      line-height: 1.55;
    }

    .payment-gateway-total {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 1rem;
    }

    .payment-gateway-total span {
      display: block;
      color: #111827;
      font-size: 0.98rem;
      font-weight: 800;
      letter-spacing: -0.02em;
    }

    .payment-gateway-total p {
      margin: 0.32rem 0 0;
      color: #64748b;
      font-size: 0.88rem;
      line-height: 1.5;
    }

    .payment-gateway-total strong {
      color: #1d4ed8;
      font-size: clamp(2rem, 3vw, 2.55rem);
      line-height: 1;
      font-weight: 900;
      letter-spacing: -0.05em;
      white-space: nowrap;
    }

    .payment-gateway-actions {
      display: grid;
      gap: 0.78rem;
      margin-top: auto;
    }

    .payment-gateway-submit,
    .payment-gateway-cancel {
      width: 100%;
      min-height: 3.7rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.65rem;
      border: none;
      border-radius: 18px;
      font-size: 1.02rem;
      font-weight: 800;
      transition: transform 0.16s ease, box-shadow 0.16s ease, opacity 0.16s ease;
    }

    .payment-gateway-submit {
      background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
      color: #fff;
      box-shadow: 0 18px 30px rgba(37, 99, 235, 0.22);
    }

    .payment-gateway-submit:hover,
    .payment-gateway-cancel:hover {
      transform: translateY(-1px);
    }

    .payment-gateway-submit .material-symbols-outlined {
      font-size: 1.2rem;
    }

    .payment-gateway-cancel {
      background: rgba(255, 255, 255, 0.9);
      color: #334155;
      border: 1px solid #dbe6f2;
      box-shadow: 0 10px 20px rgba(15, 23, 42, 0.05);
    }

    @media (max-width: 900px) {
      .payment-gateway-shell {
        grid-template-columns: 1fr;
      }

      .payment-gateway-panel--summary {
        border-left: none;
        border-top: 1px solid rgba(191, 219, 254, 0.65);
      }
    }

    @media (max-width: 640px) {
      .payment-gateway-popup {
        width: calc(100vw - 1rem) !important;
        max-width: calc(100vw - 1rem) !important;
        border-radius: 26px !important;
      }

      .payment-gateway-panel {
        padding: 1.1rem;
      }

      .payment-gateway-header {
        gap: 0.8rem;
        margin-bottom: 1.2rem;
      }

      .payment-gateway-header h2 {
        font-size: 1.55rem;
      }

      .payment-gateway-option {
        grid-template-columns: 1fr auto;
        gap: 0.9rem;
      }

      .payment-gateway-badge {
        grid-column: 1 / 2;
        width: fit-content;
        min-width: 0;
        margin-bottom: 0.2rem;
      }

      .payment-gateway-copy {
        grid-column: 1 / 2;
      }

      .payment-gateway-radio {
        grid-column: 2 / 3;
        grid-row: 1 / 3;
        align-self: center;
      }

      .payment-gateway-order-card,
      .payment-gateway-total {
        grid-template-columns: 1fr;
        align-items: flex-start;
      }

      .payment-gateway-order-price,
      .payment-gateway-total strong {
        white-space: normal;
      }
    }
  `;

  document.head.appendChild(style);
};

export const showCashPaymentInstructions = async ({ swal }) => {
  await swal.fire({
    title: 'Thanh toán tiền mặt',
    html: `
      <div style="text-align:left; line-height:1.65;">
        <p style="margin-bottom:0.75rem;">Thợ đã chốt đơn này với phương thức <strong>tiền mặt</strong>.</p>
        <p style="margin-bottom:0.75rem;">Bạn thanh toán trực tiếp cho thợ sau khi kiểm tra kết quả sửa chữa.</p>
        <p style="margin:0;">Nếu đơn chưa hoàn tất ngay, vui lòng liên hệ thợ hoặc cửa hàng để được hỗ trợ đối soát.</p>
      </div>
    `,
    icon: 'info',
    confirmButtonText: 'Đã hiểu',
  });
};

export const selectOnlineGateway = async ({
  booking = null,
  isLocalPaymentSandbox = false,
  swal,
}) => {
  ensureGatewayPopupStyles();

  const gatewayCatalog = getGatewayCatalog({ isLocalPaymentSandbox });
  const gatewayKeys = Object.keys(gatewayCatalog);
  const defaultGateway = gatewayKeys[0] || 'momo_atm';

  const result = await swal.fire({
    width: '1040px',
    padding: 0,
    showConfirmButton: false,
    showCloseButton: false,
    customClass: {
      popup: 'payment-gateway-popup',
    },
    html: buildGatewayPopupMarkup({
      booking,
      gatewayCatalog,
      defaultGateway,
    }),
    backdrop: 'rgba(15, 23, 42, 0.52)',
    focusConfirm: false,
    didOpen: (popup) => {
      const optionInputs = Array.from(popup.querySelectorAll('input[name="paymentGatewayOption"]'));
      const optionCards = Array.from(popup.querySelectorAll('[data-gateway-option]'));
      const gatewayName = popup.querySelector('[data-selected-gateway-name]');
      const gatewayCopy = popup.querySelector('[data-selected-gateway-copy]');

      const updateGatewayState = () => {
        const checkedInput = popup.querySelector('input[name="paymentGatewayOption"]:checked');
        const selectedKey = checkedInput?.value || defaultGateway;
        const gatewayMeta = gatewayCatalog[selectedKey] || gatewayCatalog[defaultGateway];

        optionCards.forEach((card) => {
          card.classList.toggle('is-selected', card.dataset.gatewayOption === selectedKey);
        });

        if (gatewayName) {
          gatewayName.textContent = gatewayMeta?.summaryTitle || '';
        }

        if (gatewayCopy) {
          gatewayCopy.textContent = gatewayMeta?.summaryCopy || '';
        }
      };

      optionInputs.forEach((input) => {
        input.addEventListener('change', updateGatewayState);
      });

      popup.querySelectorAll('[data-payment-dismiss]').forEach((button) => {
        button.addEventListener('click', () => swal.close());
      });

      popup.querySelector('[data-payment-confirm]')?.addEventListener('click', () => {
        swal.clickConfirm();
      });

      updateGatewayState();
    },
    preConfirm: () => {
      const selectedInput = swal.getPopup()?.querySelector('input[name="paymentGatewayOption"]:checked');

      if (!selectedInput?.value) {
        swal.showValidationMessage('Vui lòng chọn ví hoặc cổng thanh toán.');
        return false;
      }

      return selectedInput.value;
    },
  });

  return result.isConfirmed ? result.value : null;
};
