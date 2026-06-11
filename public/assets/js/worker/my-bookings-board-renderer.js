export function createWorkerBoardRenderer({
  refs,
  jobsPerPage,
  getCurrentStatus,
  getCurrentScope,
  getCurrentPage,
  setCurrentPage,
  getScopedBookings,
  getTotalPages,
  buildPaginationModel,
  updateBoardSurface,
  routeGuideController,
  helpers,
}) {
  const {
    bookingsContainer,
    bookingPagination,
    bookingPaginationWrap,
  } = refs;

  const {
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
    getBookingComplaintCase,
    hasOpenComplaintCase,
  } = helpers;

  let repairTimers = {};

  const renderLoadingState = () => {
    updateBoardSurface(getCurrentStatus(), 0);
    if (bookingPaginationWrap) {
      bookingPaginationWrap.hidden = true;
    }
    bookingsContainer.innerHTML = `
      <div class="dispatch-board-empty">
        <div>
          <span class="material-symbols-outlined">hourglass_top</span>
          <h3>Đang tải lịch làm việc</h3>
          <p>Hệ thống đang đồng bộ các đơn sửa chữa của bạn.</p>
        </div>
      </div>
    `;
  };

  const renderEmptyState = (scope = getCurrentScope()) => {
    const status = getCurrentStatus();
    const isWarrantyBoard = status === 'warranty';
    bookingsContainer.innerHTML = `
      <div class="dispatch-board-empty">
        <div>
          <span class="material-symbols-outlined">${isWarrantyBoard ? 'verified_user' : 'inventory_2'}</span>
          <h3>${isWarrantyBoard
            ? 'Chưa có yêu cầu bảo hành nào'
            : (scope === 'today' ? 'Không có lịch trong hôm nay' : 'Không có lịch làm việc phù hợp')}</h3>
          <p>${isWarrantyBoard
            ? 'Khi khách gửi bảo hành, case sẽ hiển thị tại tab này để bạn nhận xử lý và cập nhật tiến độ.'
            : (scope === 'today'
              ? 'Hệ thống chưa ghi nhận đơn nào diễn ra trong hôm nay cho tài khoản này.'
              : 'Khi có lịch sửa chữa mới, hệ thống sẽ hiển thị trực tiếp tại đây.')}</p>
        </div>
      </div>
    `;
  };

  const clearRepairTimers = () => {
    Object.values(repairTimers).forEach((timer) => clearInterval(timer));
    repairTimers = {};
  };

  const renderWorkflow = (booking) => {
    const pricingReady = hasUpdatedPricing(booking);

    return `
      <div class="dispatch-workflow">
        <p class="dispatch-workflow__title">Quy trình hiện tại</p>
        <div class="dispatch-workflow__list">
          <div class="dispatch-workflow__item is-done">
            <span class="dispatch-workflow__icon material-symbols-outlined">check</span>
            <span>Đã bắt đầu sửa</span>
          </div>
          <div class="dispatch-workflow__item ${pricingReady ? 'is-done' : 'is-current'}">
            <span class="dispatch-workflow__icon material-symbols-outlined">${pricingReady ? 'check' : 'priority_high'}</span>
            <span>${pricingReady ? 'Đã cập nhật chi phí' : 'Cần cập nhật chi phí'}</span>
          </div>
          <div class="dispatch-workflow__item ${pricingReady ? 'is-current' : 'is-locked'}">
            <span class="dispatch-workflow__icon material-symbols-outlined">${pricingReady ? 'arrow_forward' : 'lock'}</span>
            <span>${pricingReady ? 'Sẵn sàng báo hoàn thành' : 'Khóa cho đến khi cập nhật giá'}</span>
          </div>
        </div>
      </div>
    `;
  };

  const renderSummaryBox = (booking) => `
    <div class="dispatch-summary-box">
      <span class="dispatch-summary-box__label">Tổng chi phí</span>
      <span class="dispatch-summary-box__value">${formatMoney(getBookingTotal(booking))}</span>
      <span class="dispatch-summary-box__hint">Đã sẵn sàng để khách thanh toán</span>
    </div>
  `;

  const getBookingLaborTotal = (booking) => getBookingLaborItems(booking)
    .reduce((total, item) => total + getNumeric(item?.so_tien), 0);

  const getBookingPartsTotal = (booking) => getBookingPartItems(booking)
    .reduce((total, item) => total + getNumeric(item?.so_tien), 0);

  const getBookingSurchargeTotal = (booking) => getNumeric(booking?.phi_di_lai) + getNumeric(booking?.tien_thue_xe);

  const getPaymentStageMeta = (booking) => {
    if (isCashPaymentBooking(booking)) {
      return {
        eyebrow: 'Chưa thanh toán COD',
        method: 'Tiền mặt',
        hint: booking.trang_thai === 'cho_hoan_thanh'
          ? 'Bạn đã báo hoàn thành. Chỉ còn bước thu đủ tiền mặt rồi xác nhận để chốt đơn.'
          : 'Đơn đang giữ phương thức tiền mặt. Kiểm tra đã nhận đủ tiền trước khi xác nhận hoàn tất.',
        tone: 'cash',
      };
    }

    return {
      eyebrow: 'Chờ thanh toán online',
      method: 'Chuyển khoản',
      hint: 'Hệ thống đang chờ khách hoàn tất giao dịch trực tuyến. Khi thanh toán thành công, đơn sẽ tự chuyển hoàn thành.',
      tone: 'transfer',
    };
  };

  const renderBoardPaymentPanel = (booking) => {
    if (!['cho_thanh_toan', 'cho_hoan_thanh'].includes(booking?.trang_thai)) {
      return '';
    }

    const paymentMeta = getPaymentStageMeta(booking);

    return `
      <div class="dispatch-board-payment dispatch-board-payment--${paymentMeta.tone}">
        <div class="dispatch-board-payment__top">
          <div>
            <span class="dispatch-board-payment__eyebrow">${escapeHtml(paymentMeta.eyebrow)}</span>
            <div class="dispatch-board-payment__total">${formatMoney(getBookingTotal(booking))}</div>
          </div>
          <span class="dispatch-board-payment__method">${escapeHtml(paymentMeta.method)}</span>
        </div>

        <div class="dispatch-board-payment__stats">
          <div class="dispatch-board-payment__stat">
            <span class="dispatch-board-payment__stat-label">Tiền công</span>
            <span class="dispatch-board-payment__stat-value">${formatMoney(getBookingLaborTotal(booking))}</span>
          </div>
          <div class="dispatch-board-payment__stat">
            <span class="dispatch-board-payment__stat-label">Linh kiện</span>
            <span class="dispatch-board-payment__stat-value">${formatMoney(getBookingPartsTotal(booking))}</span>
          </div>
          <div class="dispatch-board-payment__stat">
            <span class="dispatch-board-payment__stat-label">Phụ phí</span>
            <span class="dispatch-board-payment__stat-value">${formatMoney(getBookingSurchargeTotal(booking))}</span>
          </div>
        </div>

        <p class="dispatch-board-payment__hint">${escapeHtml(paymentMeta.hint)}</p>
      </div>
    `;
  };

  const getComplaintCaseStatusLabel = (complaintCase) => complaintCase?.status_label || ({
    new: 'Khách vừa tạo case',
    worker_notified: 'Đã gửi cho thợ',
    accepted: 'Thợ đã nhận',
    in_progress: 'Đang xử lý',
    completed: 'Đã hoàn tất',
    rejected: 'Đã từ chối',
    expired: 'Hết hạn',
  }[String(complaintCase?.status || '')] || 'Bảo hành');

  const getComplaintCaseTone = (complaintCase) => {
    const status = String(complaintCase?.status || '');
    if (['completed'].includes(status)) {
      return 'success';
    }
    if (['rejected', 'expired'].includes(status)) {
      return 'danger';
    }
    return 'warning';
  };

  const renderComplaintCaseActions = (booking, complaintCase) => {
    const status = String(complaintCase?.status || '');
    const actions = [
      renderBoardButton({
        variant: 'secondary',
        icon: 'visibility',
        label: 'Chi tiết',
        onclick: `openViewDetailsModal(${booking.id})`,
      }),
    ];

    if (status === 'new' || status === 'worker_notified') {
      actions.unshift(renderBoardButton({
        variant: 'main-success',
        icon: 'task_alt',
        label: 'Nhận bảo hành',
        onclick: `updateComplaintStatus(${booking.id}, 'accepted')`,
      }));
      actions.push(renderBoardButton({
        variant: 'secondary',
        icon: 'close',
        label: 'Từ chối',
        onclick: `updateComplaintStatus(${booking.id}, 'rejected')`,
      }));
      return actions.join('');
    }

    if (status === 'accepted') {
      actions.unshift(renderBoardButton({
        variant: 'main',
        icon: 'play_arrow',
        label: 'Bắt đầu xử lý',
        onclick: `updateComplaintStatus(${booking.id}, 'in_progress')`,
      }));
      actions.push(renderBoardButton({
        variant: 'secondary',
        icon: 'close',
        label: 'Từ chối',
        onclick: `updateComplaintStatus(${booking.id}, 'rejected')`,
      }));
      return actions.join('');
    }

    if (status === 'in_progress') {
      actions.unshift(renderBoardButton({
        variant: 'main-warm',
        icon: 'task_alt',
        label: 'Hoàn tất bảo hành',
        onclick: `updateComplaintStatus(${booking.id}, 'completed')`,
      }));
      actions.push(renderBoardButton({
        variant: 'secondary',
        icon: 'close',
        label: 'Từ chối',
        onclick: `updateComplaintStatus(${booking.id}, 'rejected')`,
      }));
      return actions.join('');
    }

    return actions.join('');
  };

  const renderInlineNote = (booking) => {
    if (booking.trang_thai === 'dang_lam' && !hasUpdatedPricing(booking)) {
      return '<div class="dispatch-inline-note dispatch-inline-note--danger">Bạn cần cập nhật giá trước khi sử dụng nút báo hoàn thành.</div>';
    }

    if (booking.trang_thai === 'da_xac_nhan') {
      return '<div class="dispatch-inline-note">Ưu tiên bắt đầu đúng khung giờ để giữ trải nghiệm đúng hẹn cho khách.</div>';
    }

    if (booking.trang_thai === 'cho_thanh_toan') {
      return isCashPaymentBooking(booking)
        ? '<div class="dispatch-inline-note">Khách sẽ thanh toán tiền mặt trực tiếp. Chỉ xác nhận hoàn tất sau khi bạn đã thu đủ tiền mặt.</div>'
        : '<div class="dispatch-inline-note">Đơn đã được báo hoàn thành và đang chờ khách thanh toán trực tuyến. Hệ thống sẽ tự chốt đơn khi giao dịch thành công.</div>';
    }

    if (booking.trang_thai === 'cho_hoan_thanh') {
      return '<div class="dispatch-inline-note">Khách thanh toán tiền mặt trực tiếp. Sau khi thu đủ tiền, bạn cần xác nhận để chốt hoàn tất đơn.</div>';
    }

    if (booking.trang_thai === 'da_xong') {
      return '<div class="dispatch-inline-note">Công việc đã hoàn tất và được lưu vào lịch sử xử lý.</div>';
    }

    return '';
  };

  const stripHtmlTags = (value = '') => String(value || '').replace(/<br\s*\/?>/gi, ' ').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();

  const getServiceIconName = (booking) => {
    const haystack = getBookingServiceNames(booking).toLowerCase();

    if (haystack.includes('giat')) {
      return 'local_laundry_service';
    }
    if (haystack.includes('lanh') || haystack.includes('dieu hoa')) {
      return 'mode_fan';
    }
    if (haystack.includes('tu lanh')) {
      return 'kitchen';
    }
    if (haystack.includes('tivi')) {
      return 'tv';
    }
    if (haystack.includes('nuoc')) {
      return 'water_drop';
    }

    return 'home_repair_service';
  };

  const getBoardNoteConfig = (booking) => {
    const complaintCase = getBookingComplaintCase(booking);
    if (complaintCase) {
      return {
        tone: getComplaintCaseTone(complaintCase),
        icon: 'verified_user',
        title: getComplaintCaseStatusLabel(complaintCase),
        body: complaintCase?.worker_response_note
          || complaintCase?.note,
          
      };
    }

    if (booking?.worker_contact_issue?.is_open) {
      const reporterName = booking?.worker_contact_issue?.reporter_name || booking?.worker_contact_issue?.reported_by?.name || 'Thợ phụ trách';
      const calledPhone = booking?.worker_contact_issue?.called_phone || booking?.khach_hang?.phone || '';
      const noteParts = [
        `Người báo: ${reporterName}`,
        calledPhone ? `Số vừa gọi: ${calledPhone}` : '',
        booking?.worker_contact_issue?.note || 'Thợ đã báo admin vì chưa liên lạc được với khách hàng.',
      ].filter(Boolean);

      return {
        tone: 'danger',
        icon: 'support_agent',
        title: 'Admin đang được yêu cầu hỗ trợ liên hệ',
        body: noteParts.join(' • '),
      };
    }

    if (booking.trang_thai === 'da_xac_nhan') {
      return {
        tone: 'default',
        icon: 'info',
        title: 'Ghi chú nhắc bắt đầu đúng giờ',
        body: booking.mo_ta_van_de || 'Khách đã chốt lịch. Vui lòng đến đúng khung giờ để tránh trễ hẹn.',
      };
    }

    if (booking.trang_thai === 'dang_lam') {
      if (hasUpdatedPricing(booking)) {
        return {
          tone: 'info',
          icon: 'price_check',
          title: 'Dịch vụ đang sửa và đã có báo giá',
          body: `Tổng chi phí tạm tính hiện tại là ${formatMoney(getBookingTotal(booking))}. Khi thiết bị đã ổn định, bạn có thể báo hoàn thành ngay trên thẻ này.`,
        };
      }

      return {
        tone: 'danger',
        icon: 'warning',
        title: 'Dịch vụ đang sửa, chờ cập nhật chi phí',
        body: 'Hãy điền tiền công, linh kiện và phụ phí trước khi chuyển sang bước báo hoàn thành cho khách.',
      };
    }

    if (booking.trang_thai === 'cho_thanh_toan' || booking.trang_thai === 'cho_hoan_thanh') {
      return isCashPaymentBooking(booking)
        ? {
            tone: 'info',
            icon: 'payments',
            title: 'Đơn đang chờ xác nhận COD',
            body: 'Chỉ xác nhận hoàn tất sau khi bạn đã thu đủ tiền trực tiếp từ khách hàng.',
          }
        : {
            tone: 'info',
            icon: 'credit_card',
            title: 'Đơn đang chờ thanh toán online',
            body: 'Hệ thống sẽ tự chốt đơn khi giao dịch trực tuyến của khách thành công.',
          };
    }

    if (booking.trang_thai === 'da_xong') {
      return {
        tone: 'success',
        icon: 'task_alt',
        title: 'Công việc đã hoàn tất',
        body: `Tổng chi phí đã chốt là ${formatMoney(getBookingTotal(booking))}. Đơn này hiện nằm trong lịch sử xử lý.`,
      };
    }

    if (booking.trang_thai === 'da_huy') {
      return {
        tone: 'danger',
        icon: 'cancel',
        title: 'Đơn đã bị hủy',
        body: 'Giữ lại chi tiết để đối chiếu nếu cần kiểm tra nguyên nhân hủy hoặc lịch sử làm việc.',
      };
    }

    return {
      tone: 'info',
      icon: 'schedule',
      title: 'Đơn đang chờ xác nhận',
      body: 'Kiểm tra kỹ mô tả và thông tin liên hệ trước khi thực hiện các bước tiếp theo.',
    };
  };

  const renderBoardNote = (booking) => {
    const note = getBoardNoteConfig(booking);
    const toneClass = note.tone && note.tone !== 'default' ? ` dispatch-board-note--${note.tone}` : '';

    return `
      <div class="dispatch-board-note${toneClass}">
        <div class="dispatch-board-note__title">
          <span class="material-symbols-outlined">${escapeHtml(note.icon)}</span>
          <span>${escapeHtml(note.title)}</span>
        </div>
        <p class="dispatch-board-note__body">${escapeHtml(note.body)}</p>
      </div>
    `;
  };

  const formatBookingCode = (booking) => `#${String(Math.max(0, Math.trunc(getNumeric(booking?.id)))).padStart(4, '0')}`;

  const renderBoardButton = ({
    variant = 'secondary',
    icon = 'open_in_new',
    label = '',
    title = '',
    onclick = '',
    href = '',
    disabled = false,
  }) => {
    const className = variant === 'main'
      ? 'dispatch-board-card__action-main'
      : variant === 'main-warm'
        ? 'dispatch-board-card__action-main dispatch-board-card__action-main--warm'
        : variant === 'main-success'
          ? 'dispatch-board-card__action-main dispatch-board-card__action-main--success'
          : variant === 'main-disabled'
            ? 'dispatch-board-card__action-main dispatch-board-card__action-main--disabled'
            : variant === 'icon'
              ? 'dispatch-board-card__action-icon'
              : 'dispatch-board-card__action-secondary';
    const labelHtml = label ? `<span>${escapeHtml(label)}</span>` : '';
    const titleAttr = title ? ` title="${escapeHtml(title)}"` : '';

    if (href) {
      return `
        <a href="${escapeHtml(href)}" class="${className}"${titleAttr}>
          <span class="material-symbols-outlined">${escapeHtml(icon)}</span>
          ${labelHtml}
        </a>
      `;
    }

    return `
      <button type="button" class="${className}"${disabled ? ' disabled' : ''}${titleAttr}${onclick && !disabled ? ` onclick="${onclick}"` : ''}>
        <span class="material-symbols-outlined">${escapeHtml(icon)}</span>
        ${labelHtml}
      </button>
    `;
  };

  const renderActionButtons = (booking) => {
    const actions = [];
    const utilityActions = [];
    const pricingReady = booking.trang_thai === 'dang_lam' ? hasUpdatedPricing(booking) : false;
    const complaintCase = getBookingComplaintCase(booking);

    if (complaintCase) {
      return renderComplaintCaseActions(booking, complaintCase);
    }

    if (isClaimableMarketBooking(booking)) {
      actions.push(renderBoardButton({
        variant: 'main-success',
        icon: 'assignment_turned_in',
        label: 'Nhận đơn',
        onclick: `claimJob(${booking.id})`,
      }));
      actions.push(renderBoardButton({
        variant: 'secondary',
        icon: 'visibility',
        label: 'Chi tiết',
        onclick: `openViewDetailsModal(${booking.id})`,
      }));
    } else if (isAssignedPendingBooking(booking)) {
      actions.push(renderBoardButton({
        variant: 'main-success',
        icon: 'task_alt',
        label: 'Xác nhận đơn',
        onclick: `updateStatus(${booking.id}, 'da_xac_nhan')`,
      }));
      actions.push(renderBoardButton({
        variant: 'secondary',
        icon: 'visibility',
        label: 'Chi tiết',
        onclick: `openViewDetailsModal(${booking.id})`,
      }));
    } else if (booking.trang_thai === 'da_xac_nhan' || booking.trang_thai === 'khong_lien_lac_duoc_voi_khach_hang') {
      const contactIssueOpen = Boolean(booking?.worker_contact_issue?.is_open);

      actions.push(renderBoardButton({
        variant: contactIssueOpen ? 'main-disabled' : 'main',
        icon: contactIssueOpen ? 'hourglass_top' : 'play_arrow',
        label: contactIssueOpen ? 'Chờ admin xử lý' : 'Bắt đầu sửa',
        onclick: `updateStatus(${booking.id}, 'dang_lam')`,
        disabled: contactIssueOpen,
        title: contactIssueOpen
          ? 'Đơn đang được admin hỗ trợ liên hệ khách hàng'
          : 'Bắt đầu xử lý đơn sắp tới',
      }));
      actions.push(renderBoardButton({
        variant: 'secondary',
        icon: 'visibility',
        label: 'Chi tiết',
        onclick: `openViewDetailsModal(${booking.id})`,
      }));

      utilityActions.push(renderBoardButton({
        variant: 'icon',
        icon: contactIssueOpen ? 'support_agent' : 'phone_missed',
        onclick: `reportCustomerUnreachable(${booking.id})`,
        title: contactIssueOpen
          ? 'Cập nhật báo cáo không liên lạc được'
          : 'Báo admin hỗ trợ liên hệ khách hàng',
      }));
    } else if (booking.trang_thai === 'dang_lam') {
      actions.push(renderBoardButton({
        variant: pricingReady ? 'main-warm' : 'main',
        icon: pricingReady ? 'task_alt' : 'price_change',
        label: pricingReady ? 'Báo hoàn thành' : 'Cập nhật giá',
        onclick: pricingReady ? `openCompleteModal(${booking.id})` : `openCostModal(${booking.id})`,
        title: pricingReady ? 'Sẵn sàng báo bảo hành' : 'Cập nhật bảng giá sửa chữa',
      }));
      actions.push(renderBoardButton({
        variant: 'secondary',
        icon: pricingReady ? 'price_change' : 'visibility',
        label: pricingReady ? 'Cập nhật giá' : 'Chi tiết',
        onclick: pricingReady ? `openCostModal(${booking.id})` : `openViewDetailsModal(${booking.id})`,
      }));
      if (pricingReady) {
        utilityActions.push(renderBoardButton({
          variant: 'icon',
          icon: 'visibility',
          onclick: `openViewDetailsModal(${booking.id})`,
          title: 'Xem chi tiết dịch vụ đang sửa',
        }));
      }
    } else if (booking.trang_thai === 'cho_thanh_toan' || booking.trang_thai === 'cho_hoan_thanh') {
      actions.push(renderBoardButton({
        variant: 'main-warm',
        icon: 'payments',
        label: isCashPaymentBooking(booking) ? 'Xác nhận đã thu' : 'Theo dõi TT',
        onclick: isCashPaymentBooking(booking) ? `confirmCashPayment(${booking.id})` : `openViewDetailsModal(${booking.id})`,
      }));
      actions.push(renderBoardButton({
        variant: 'secondary',
        icon: 'receipt_long',
        label: 'Chi tiết',
        onclick: `openViewDetailsModal(${booking.id})`,
      }));
    } else {
      actions.push(renderBoardButton({
        variant: 'secondary',
        icon: 'visibility',
        label: 'Chi tiết',
        onclick: `openViewDetailsModal(${booking.id})`,
      }));
    }

    const utilityLimit = 3;

    if (canOpenRouteGuide(booking) && utilityActions.length < utilityLimit) {
      utilityActions.push(renderBoardButton({
        variant: 'icon',
        icon: 'near_me',
        onclick: `openRouteGuide(${booking.id})`,
        title: 'Mở chỉ đường',
      }));
    }

    if (getPhoneNumber(booking) && utilityActions.length < utilityLimit) {
      utilityActions.push(renderBoardButton({
        variant: 'icon',
        icon: 'call',
        href: getPhoneHref(booking),
        title: `Gọi ${getCustomerName(booking)}`,
      }));
    }

    return actions.concat(utilityActions).join('');
  };

  const renderCard = (booking) => {
    const tone = getStatusTone(booking);
    const title = getBookingServiceNames(booking);
    const serviceBadge = getServiceBadge(booking);
    const customerName = getCustomerName(booking);
    const customerPhone = getPhoneNumber(booking) || 'Chưa có số liên hệ';
    const noteMarkup = renderBoardNote(booking);
    const paymentMarkup = renderBoardPaymentPanel(booking);
    const scheduleDateText = getBookingCardDateLabel(booking);
    const scheduleTimeText = getBookingPrimaryTimeLabel(booking);
    const bookingCode = formatBookingCode(booking);
    const location = getAddress(booking);
    const locationLabel = getLocationLabel(booking);
    const statusLabel = getStatusLabel(booking);
    const locationIcon = booking?.loai_dat_lich === 'at_home' ? 'home_repair_service' : 'storefront';

    return `
      <article class="dispatch-board-card dispatch-board-card--${tone}">
        <span class="dispatch-board-card__status">${escapeHtml(statusLabel)}</span>
        <div class="dispatch-board-card__content">
          <div class="dispatch-board-card__header">
            <div class="dispatch-board-card__lead">
              <div class="dispatch-board-card__icon">
                <span class="material-symbols-outlined">${escapeHtml(getServiceIconName(booking))}</span>
              </div>
              <div class="dispatch-board-card__summary">
                <span class="dispatch-board-card__eyebrow">${escapeHtml(serviceBadge)}</span>
                <h3 class="dispatch-board-card__title">${escapeHtml(title)}</h3>
                <div class="dispatch-board-card__support">
                  <span class="dispatch-board-card__support-item">
                    <span class="material-symbols-outlined">tag</span>
                    <span>Mã đơn ${escapeHtml(bookingCode)}</span>
                  </span>
                  <span class="dispatch-board-card__support-item">
                    <span class="material-symbols-outlined">person</span>
                    <span>${escapeHtml(customerName)}</span>
                  </span>
                  <span class="dispatch-board-card__support-item">
                    <span class="material-symbols-outlined">call</span>
                    <span>${escapeHtml(customerPhone)}</span>
                  </span>
                </div>
              </div>
            </div>

            <div class="dispatch-board-card__schedule">
              <span class="dispatch-board-card__time">${escapeHtml(scheduleTimeText)}</span>
              <span class="dispatch-board-card__date">${escapeHtml(scheduleDateText)}</span>
            </div>
          </div>

          <div class="dispatch-board-card__body">
            <div class="dispatch-board-card__info-grid">
              <div class="dispatch-board-card__info dispatch-board-card__info--full">
                <span class="dispatch-board-card__info-icon">
                  <span class="material-symbols-outlined">location_on</span>
                </span>
                <span class="dispatch-board-card__info-copy">
                  <span class="dispatch-board-card__info-label">Địa điểm</span>
                  <span class="dispatch-board-card__info-value">${escapeHtml(location)}</span>
                </span>
              </div>

              <div class="dispatch-board-card__info">
                <span class="dispatch-board-card__info-icon">
                  <span class="material-symbols-outlined">${escapeHtml(locationIcon)}</span>
                </span>
                <span class="dispatch-board-card__info-copy">
                  <span class="dispatch-board-card__info-label">Hình thức</span>
                  <span class="dispatch-board-card__info-value">${escapeHtml(locationLabel)}</span>
                </span>
              </div>

              <div class="dispatch-board-card__info">
                <span class="dispatch-board-card__info-icon">
                  <span class="material-symbols-outlined">event_note</span>
                </span>
                <span class="dispatch-board-card__info-copy">
                  <span class="dispatch-board-card__info-label">Lịch hẹn</span>
                  <span class="dispatch-board-card__info-value">${escapeHtml(booking?.khung_gio_hen || 'Chưa chọn giờ')}</span>
                </span>
              </div>
            </div>
            ${paymentMarkup}
            ${noteMarkup}
          </div>

          <div class="dispatch-board-card__footer">
            ${renderActionButtons(booking)}
          </div>
        </div>
      </article>
    `;
  };

  const renderPagination = (totalItems) => {
    if (!bookingPagination) {
      return;
    }

    const totalPages = Math.max(1, Math.ceil(totalItems / jobsPerPage));
    setCurrentPage(Math.min(Math.max(1, getCurrentPage()), totalPages));

    if (bookingPaginationWrap) {
      bookingPaginationWrap.hidden = totalPages <= 1;
    }

    if (totalPages <= 1) {
      bookingPagination.innerHTML = '';
      return;
    }

    const items = buildPaginationModel(totalPages, getCurrentPage());
    const prevDisabled = getCurrentPage() <= 1;
    const nextDisabled = getCurrentPage() >= totalPages;

    bookingPagination.innerHTML = `
      <button type="button" class="dispatch-pagination__btn${prevDisabled ? ' is-disabled' : ''}" data-page-action="prev" aria-label="Trang truoc">
        <span class="material-symbols-outlined">chevron_left</span>
      </button>
      ${items.map((item) => item === 'ellipsis'
        ? '<span class="dispatch-pagination__ellipsis">...</span>'
        : `<button type="button" class="dispatch-pagination__page${item === getCurrentPage() ? ' is-active' : ''}" data-page-number="${item}">${item}</button>`).join('')}
      <button type="button" class="dispatch-pagination__btn${nextDisabled ? ' is-disabled' : ''}" data-page-action="next" aria-label="Trang sau">
        <span class="material-symbols-outlined">chevron_right</span>
      </button>
    `;
  };

  const refreshRepairTimers = (bookings) => {
    clearRepairTimers();

    bookings
      .filter((booking) => booking.trang_thai === 'dang_lam')
      .forEach((booking) => {
        const el = document.getElementById(`timer-${booking.id}`);
        if (!el) {
          return;
        }

        let seconds = 0;
        repairTimers[booking.id] = setInterval(() => {
          seconds += 1;
          const hours = String(Math.floor(seconds / 3600)).padStart(2, '0');
          const minutes = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
          const secs = String(seconds % 60).padStart(2, '0');
          el.textContent = `${hours}:${minutes}:${secs}`;
        }, 1000);
      });
  };

  const renderBookings = (status = getCurrentStatus()) => {
    const list = getScopedBookings(status, getCurrentScope());
    const totalItems = list.length;

    renderPagination(totalItems);
    routeGuideController?.renderPreview(list);
    updateBoardSurface(status, totalItems);

    if (!totalItems) {
      clearRepairTimers();
      renderEmptyState(getCurrentScope());
      return;
    }

    const startIndex = (getCurrentPage() - 1) * jobsPerPage;
    const visibleList = list.slice(startIndex, startIndex + jobsPerPage);

    bookingsContainer.innerHTML = visibleList.map((booking) => renderCard(booking)).join('');
    refreshRepairTimers(visibleList);
  };

  return {
    clearRepairTimers,
    renderEmptyState,
    renderLoadingState,
    renderBookings,
  };
}
