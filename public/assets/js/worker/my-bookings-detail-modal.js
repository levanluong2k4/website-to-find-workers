import { showToast } from '../api.js';
import {
  escapeHtml,
  formatMoney,
  getBookingLaborItems,
  getBookingPartItems,
  getBookingServiceNames,
  getCustomerName,
  getNumeric,
  getPartQuantity,
  getPartUnitPrice,
} from './pricing-core.js';

export function createBookingDetailModalController({
  getAllBookings,
  getActiveBookingId,
  setActiveBookingId,
  syncWorkerBookingsUrl,
  workerId,
  getBookingDateLabel,
  getPhoneNumber,
  getPhoneHref,
  getAddress,
  getBookingTotal,
  getStatusLabel,
  getLocationLabel,
}) {
  const content = document.getElementById('bookingDetailContent');
  const modalEl = document.getElementById('modalViewDetails');
  const modalInstance = modalEl && typeof bootstrap !== 'undefined'
    ? new bootstrap.Modal(modalEl)
    : null;

  let initialized = false;

  const nl2brSafe = (value = '') => escapeHtml(value).replace(/\n/g, '<br>');

  const formatDateTimeLabel = (value, fallback = 'Chưa cập nhật') => {
    if (!value) {
      return fallback;
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
      return fallback;
    }

    return parsed.toLocaleString('vi-VN', {
      hour: '2-digit',
      minute: '2-digit',
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    });
  };

  const formatPartQuantityMeta = (item, warrantyLabel = '') => {
    const quantity = getPartQuantity(item);
    const unitPrice = getPartUnitPrice(item);
    const segments = [
      `SL ${quantity}`,
      unitPrice > 0 ? `${formatMoney(unitPrice)}/cái` : '',
      warrantyLabel,
    ].filter(Boolean);

    return segments.join(' • ');
  };

  const formatWarrantyText = (months) => {
    const value = Number(months);
    if (!Number.isFinite(value) || value <= 0) {
      return 'Không ghi bảo hành';
    }

    return `Bảo hành ${value} tháng`;
  };

  const parseWarrantyDate = (value) => {
    if (!value) {
      return null;
    }

    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
  };

  const addWarrantyMonths = (value, months) => {
    const date = parseWarrantyDate(value);
    const monthCount = Number(months);
    if (!date || !Number.isFinite(monthCount) || monthCount <= 0) {
      return null;
    }

    const result = new Date(date.getTime());
    const originalDay = result.getDate();
    result.setDate(1);
    result.setMonth(result.getMonth() + Math.trunc(monthCount));

    const lastDay = new Date(result.getFullYear(), result.getMonth() + 1, 0).getDate();
    result.setDate(Math.min(originalDay, lastDay));

    return result;
  };

  const hasUsedWarranty = (item) => item?.bao_hanh_da_su_dung === true
    || item?.da_dung_bao_hanh === true
    || item?.used_warranty === true;

  const formatWarrantyRemaining = (endDate, now = new Date()) => {
    const remainingDays = Math.max(0, Math.ceil((endDate.getTime() - now.getTime()) / (24 * 60 * 60 * 1000)));
    if (remainingDays <= 1) {
      return 'còn 1 ngày';
    }

    if (remainingDays < 30) {
      return `còn ${remainingDays} ngày`;
    }

    const months = Math.floor(remainingDays / 30);
    const days = remainingDays % 30;
    return days === 0 ? `còn ${months} tháng` : `còn ${months} tháng ${days} ngày`;
  };

  const getWarrantyStatusMeta = (booking, item) => {
    const warrantyLabel = formatWarrantyText(item?.bao_hanh_thang);
    const warrantyMonths = Number(item?.bao_hanh_thang);
    const completedAt = parseWarrantyDate(booking?.thoi_gian_hoan_thanh);
    const activatedAtLabel = completedAt ? formatDateTimeLabel(completedAt, '') : '';

    if (hasUsedWarranty(item)) {
      return {
        label: 'Hết bảo hành',
        detail: activatedAtLabel
          ? `Kích hoạt từ ${activatedAtLabel}. Linh kiện đã sử dụng quyền bảo hành.`
          : 'Linh kiện đã sử dụng quyền bảo hành.',
        tone: 'is-used',
        warrantyLabel,
        canConfirm: false,
      };
    }

    if (!Number.isFinite(warrantyMonths) || warrantyMonths <= 0) {
      return {
        label: 'Không ghi bảo hành',
        detail: 'Linh kiện này không có thời hạn bảo hành.',
        tone: 'is-neutral',
        warrantyLabel,
        canConfirm: false,
      };
    }

    if (!completedAt) {
      return {
        label: 'Chưa bắt đầu bảo hành',
        detail: 'Bảo hành được tính từ thời gian hoàn thành đơn.',
        tone: 'is-neutral',
        warrantyLabel,
        canConfirm: false,
      };
    }

    const warrantyEndDate = addWarrantyMonths(completedAt, warrantyMonths);
    if (!warrantyEndDate) {
      return {
        label: 'Không ghi bảo hành',
        detail: 'Không xác định được thời hạn bảo hành.',
        tone: 'is-neutral',
        warrantyLabel,
        canConfirm: false,
      };
    }

    const now = new Date();
    if (now.getTime() <= warrantyEndDate.getTime()) {
      return {
        label: 'Còn bảo hành',
        detail: `Kích hoạt từ ${activatedAtLabel} • hiệu lực đến ${warrantyEndDate.toLocaleDateString('vi-VN')} • ${formatWarrantyRemaining(warrantyEndDate, now)}.`,
        tone: 'is-active',
        warrantyLabel,
        canConfirm: true,
      };
    }

    return {
      label: 'Hết hạn bảo hành',
      detail: `Kích hoạt từ ${activatedAtLabel} • đã hết hạn từ ${warrantyEndDate.toLocaleDateString('vi-VN')}.`,
      tone: 'is-expired',
      warrantyLabel,
      canConfirm: false,
    };
  };

  const canConfirmWarranty = (booking, item) => Boolean(
    Number(booking?.tho_id || 0) === Number(workerId || 0) && getWarrantyStatusMeta(booking, item).canConfirm,
  );

  const renderCostItemCards = (items, emptyMessage, type, booking = null) => {
    if (!items.length) {
      return `<div class="dispatch-inline-note">${emptyMessage}</div>`;
    }

    return `
      <div class="dispatch-cost-item-list">
        ${items.map((item, index) => {
          const warrantyMeta = type === 'part' ? getWarrantyStatusMeta(booking, item) : null;

          return `
            <div class="dispatch-cost-item-card">
              <div class="dispatch-cost-item-card__top">
                <div>
                  <div class="dispatch-cost-item-card__title">${escapeHtml(item?.noi_dung || (type === 'part' ? 'Linh kiện' : 'Tiền công'))}</div>
                  <div class="dispatch-cost-item-card__meta">
                    ${type === 'part'
                      ? escapeHtml(formatPartQuantityMeta(item, warrantyMeta?.warrantyLabel || formatWarrantyText(item?.bao_hanh_thang)))
                      : 'Tiền công sửa chữa'}
                  </div>
                  ${type === 'part' && warrantyMeta
                    ? `<div class="dispatch-warranty-pill ${warrantyMeta.tone}">${escapeHtml(warrantyMeta.label)}</div>
                       <div class="dispatch-cost-item-card__note">${escapeHtml(warrantyMeta.detail)}</div>
                       ${canConfirmWarranty(booking, item) ? `<button type="button" class="dispatch-warranty-action" onclick="confirmPartWarranty(${booking.id}, ${index})">Xác nhận đã bảo hành</button>` : ''}`
                    : ''}
                </div>
                <strong>${formatMoney(getNumeric(item?.so_tien))}</strong>
              </div>
            </div>
          `;
        }).join('')}
      </div>
    `;
  };

  const renderMediaGrid = (images = [], video = '') => {
    const imageCards = images.length
      ? `<div class="dispatch-media-grid">${images.map((img) => `
          <a class="dispatch-media-card" href="${escapeHtml(img)}" target="_blank" rel="noopener">
            <img src="${escapeHtml(img)}" alt="Ảnh mô tả">
          </a>
        `).join('')}</div>`
      : '<div class="dispatch-inline-note">Khách hàng chưa gửi ảnh mô tả.</div>';

    const videoCard = video
      ? `
          <div class="dispatch-media-grid">
            <a class="dispatch-media-card" href="${escapeHtml(video)}" target="_blank" rel="noopener">
              <video src="${escapeHtml(video)}"></video>
            </a>
          </div>
        `
      : '';

    return `${imageCards}${videoCard}`;
  };

  const open = (id, { syncUrl = true } = {}) => {
    const booking = getAllBookings().find((item) => getNumeric(item?.id) === getNumeric(id));

    if (!booking || !content) {
      showToast('Không tìm thấy chi tiết đơn.', 'error');
      return;
    }

    setActiveBookingId(booking.id);
    if (syncUrl) {
      syncWorkerBookingsUrl({ bookingId: booking.id });
    }

    const distanceInfo = booking.loai_dat_lich === 'at_home'
      ? `Khoảng cách đo được: ${getNumeric(booking.khoang_cach).toFixed(1)} km`
      : 'Khách tự mang thiết bị đến cửa hàng';

    const contactIssue = booking?.worker_contact_issue || null;
    const canReportCustomerUnreachable = Number(booking?.tho_id || 0) === Number(workerId || 0)
      && ['da_xac_nhan', 'khong_lien_lac_duoc_voi_khach_hang'].includes(booking?.trang_thai);
    const contactIssueReporter = contactIssue?.reporter_name || contactIssue?.reported_by?.name || '';
    const contactIssuePhone = contactIssue?.called_phone || '';
    const contactIssueSummary = contactIssue?.is_reported
      ? `${contactIssue.is_open ? 'Đã báo admin, đang chờ hỗ trợ.' : 'Báo cáo đã đóng.'}${contactIssue.reported_label ? ` Cập nhật lúc ${contactIssue.reported_label}.` : ''}`
      : 'Nếu đã gọi nhiều lần mà khách chưa nghe máy, bạn có thể báo admin hỗ trợ ngay từ đây.';
    const contactIssueMeta = contactIssue?.is_reported
      ? `
          <div class="dispatch-detail-list mt-3">
            <div class="dispatch-detail-item">
              <span class="dispatch-detail-item__label">Người vừa gọi</span>
              <div class="dispatch-detail-item__value">${escapeHtml(contactIssueReporter || '--')}</div>
            </div>
            <div class="dispatch-detail-item">
              <span class="dispatch-detail-item__label">Số điện thoại đã gọi</span>
              <div class="dispatch-detail-item__value">${escapeHtml(contactIssuePhone || '--')}</div>
            </div>
          </div>
        `
      : '';
    const contactIssueMarkup = (canReportCustomerUnreachable || contactIssue?.is_reported)
      ? `
          <div class="dispatch-note-card mt-4">
            <div class="dispatch-note-card__label">Hỗ trợ liên hệ khách hàng</div>
            <div class="dispatch-note-card__hint">${escapeHtml(contactIssueSummary)}</div>
            ${contactIssueMeta}
            ${contactIssue?.note
              ? `<div class="dispatch-inline-note mt-3">${nl2brSafe(contactIssue.note)}</div>`
              : ''}
            ${canReportCustomerUnreachable
              ? `<button type="button" class="dispatch-btn dispatch-btn--secondary mt-3" onclick="reportCustomerUnreachable(${booking.id})">
                  <span class="material-symbols-outlined">support_agent</span>
                  <span>${escapeHtml(contactIssue?.is_open ? 'Cập nhật báo admin' : 'Báo admin hỗ trợ')}</span>
                </button>`
              : ''}
          </div>
        `
      : '';

    const truckInfo = booking.thue_xe_cho
      ? '<div class="dispatch-inline-note dispatch-inline-note--danger">Khách có yêu cầu thuê xe chở hoặc vận chuyển thiết bị cồng kềnh.</div>'
      : '<div class="dispatch-inline-note">Không phát sinh yêu cầu xe chở riêng cho đơn này.</div>';

    content.innerHTML = `
      <div class="dispatch-detail-grid">
        <div class="dispatch-panel">
          <h3 class="dispatch-panel__title">Thông tin khách hàng</h3>

          <div class="dispatch-detail-list">
            <div class="dispatch-detail-item">
              <span class="dispatch-detail-item__label">Khách hàng</span>
              <div class="dispatch-detail-item__value">${escapeHtml(getCustomerName(booking))}</div>
            </div>
            <div class="dispatch-detail-item">
              <span class="dispatch-detail-item__label">Điện thoại</span>
              <div class="dispatch-detail-item__value">
                ${getPhoneNumber(booking)
                  ? `<a href="${escapeHtml(getPhoneHref(booking))}" class="text-decoration-none">${escapeHtml(getPhoneNumber(booking))}</a>`
                  : 'Chưa có số điện thoại'}
              </div>
            </div>
            <div class="dispatch-detail-item">
              <span class="dispatch-detail-item__label">Địa chỉ</span>
              <div class="dispatch-detail-item__value">${escapeHtml(getAddress(booking))}</div>
            </div>
          </div>

          <h3 class="dispatch-panel__title mt-4">Yêu cầu sửa chữa</h3>

          <div class="dispatch-detail-list">
            <div class="dispatch-detail-item">
              <span class="dispatch-detail-item__label">Dịch vụ</span>
              <div class="dispatch-detail-item__value">${escapeHtml(getBookingServiceNames(booking))}</div>
            </div>
            <div class="dispatch-detail-item">
              <span class="dispatch-detail-item__label">Lịch hẹn</span>
              <div class="dispatch-detail-item__value">${escapeHtml(getBookingDateLabel(booking))} • ${escapeHtml(booking.khung_gio_hen || 'Chưa chọn giờ')}</div>
            </div>
            <div class="dispatch-detail-item">
              <span class="dispatch-detail-item__label">Thời gian đặt lịch</span>
              <div class="dispatch-detail-item__value">${escapeHtml(formatDateTimeLabel(booking.created_at))}</div>
            </div>
            <div class="dispatch-detail-item">
              <span class="dispatch-detail-item__label">Thời gian hoàn thành</span>
              <div class="dispatch-detail-item__value">${escapeHtml(formatDateTimeLabel(booking.thoi_gian_hoan_thanh, 'Chưa hoàn thành'))}</div>
            </div>
            <div class="dispatch-detail-item">
              <span class="dispatch-detail-item__label">Mô tả lỗi</span>
              <div class="dispatch-detail-item__value">${nl2brSafe(booking.mo_ta_van_de || 'Khách hàng chưa nhập mô tả chi tiết.')}</div>
            </div>
          </div>

          ${truckInfo}

          <h3 class="dispatch-panel__title mt-4">Hình ảnh / video từ khách</h3>
          ${renderMediaGrid(Array.isArray(booking.hinh_anh_mo_ta) ? booking.hinh_anh_mo_ta : [], booking.video_mo_ta || '')}
        </div>

        <div class="dispatch-panel">
          <h3 class="dispatch-panel__title">Breakdown chi phí</h3>

          <div class="dispatch-cost-breakdown">
            <div class="dispatch-cost-row">
              <span>Phí đi lại</span>
              <strong>${formatMoney(getNumeric(booking.phi_di_lai))}</strong>
            </div>
            <div class="dispatch-cost-row">
              <span>Tiền công thợ</span>
              <strong>${formatMoney(getNumeric(booking.tien_cong))}</strong>
            </div>
            <div class="dispatch-cost-row">
              <span>Phí linh kiện</span>
              <strong>${formatMoney(getNumeric(booking.phi_linh_kien))}</strong>
            </div>
            ${booking.thue_xe_cho ? `
              <div class="dispatch-cost-row">
                <span>Phí thuê xe chở</span>
                <strong>${formatMoney(getNumeric(booking.tien_thue_xe))}</strong>
              </div>
            ` : ''}
          </div>

          <div class="mt-4">
            <span class="dispatch-detail-item__label">Chi tiết tiền công</span>
            ${renderCostItemCards(getBookingLaborItems(booking), 'Chưa có dòng tiền công.', 'labor', booking)}
          </div>

          <div class="mt-4">
            <span class="dispatch-detail-item__label">Chi tiết linh kiện</span>
            ${renderCostItemCards(getBookingPartItems(booking), 'Chưa có linh kiện phát sinh.', 'part', booking)}
          </div>

          <div class="dispatch-inline-note mt-4">${escapeHtml(distanceInfo)}</div>

          <div class="dispatch-cost-total">
            <span class="dispatch-cost-total__label">Tổng chi phí dự kiến</span>
            <span class="dispatch-cost-total__value">${formatMoney(getBookingTotal(booking))}</span>
          </div>

          <div class="dispatch-detail-list mt-4">
            <div class="dispatch-detail-item">
              <span class="dispatch-detail-item__label">Trạng thái đơn</span>
              <div class="dispatch-detail-item__value">${escapeHtml(getStatusLabel(booking))}</div>
            </div>
            <div class="dispatch-detail-item">
              <span class="dispatch-detail-item__label">Ghi chú linh kiện</span>
              <div class="dispatch-detail-item__value">${nl2brSafe(booking.ghi_chu_linh_kien || 'Chưa có ghi chú linh kiện.')}</div>
            </div>
            <div class="dispatch-detail-item">
              <span class="dispatch-detail-item__label">Hình thức phục vụ</span>
              <div class="dispatch-detail-item__value">${escapeHtml(getLocationLabel(booking))}</div>
            </div>
          </div>

          ${contactIssueMarkup}
        </div>
      </div>
    `;

    modalInstance?.show();
  };

  const init = () => {
    if (initialized) {
      return;
    }

    initialized = true;
    modalEl?.addEventListener('hidden.bs.modal', () => {
      const hadActiveBooking = Number(getActiveBookingId?.() || 0) > 0;
      setActiveBookingId(0);

      if (hadActiveBooking) {
        syncWorkerBookingsUrl({ bookingId: 0 });
      }
    });
  };

  return {
    init,
    open,
  };
}



