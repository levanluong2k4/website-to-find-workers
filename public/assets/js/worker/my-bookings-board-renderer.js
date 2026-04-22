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
          <h3>Dang tai lich lam viec</h3>
          <p>He thong dang dong bo cac don sua chua cua ban.</p>
        </div>
      </div>
    `;
  };

  const renderEmptyState = (scope = getCurrentScope()) => {
    bookingsContainer.innerHTML = `
      <div class="dispatch-board-empty">
        <div>
          <span class="material-symbols-outlined">inventory_2</span>
          <h3>${scope === 'today' ? 'Khong co lich trong hom nay' : 'Khong co lich lam viec phu hop'}</h3>
          <p>${scope === 'today'
            ? 'He thong chua ghi nhan don nao dien ra trong hom nay cho tai khoan nay.'
            : 'Khi co lich sua chua moi, he thong se hien thi truc tiep tai day.'}</p>
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
        <p class="dispatch-workflow__title">Quy trinh hien tai</p>
        <div class="dispatch-workflow__list">
          <div class="dispatch-workflow__item is-done">
            <span class="dispatch-workflow__icon material-symbols-outlined">check</span>
            <span>Da bat dau sua</span>
          </div>
          <div class="dispatch-workflow__item ${pricingReady ? 'is-done' : 'is-current'}">
            <span class="dispatch-workflow__icon material-symbols-outlined">${pricingReady ? 'check' : 'priority_high'}</span>
            <span>${pricingReady ? 'Da cap nhat chi phi' : 'Can cap nhat chi phi'}</span>
          </div>
          <div class="dispatch-workflow__item ${pricingReady ? 'is-current' : 'is-locked'}">
            <span class="dispatch-workflow__icon material-symbols-outlined">${pricingReady ? 'arrow_forward' : 'lock'}</span>
            <span>${pricingReady ? 'San sang bao hoan thanh' : 'Khoa cho den khi cap nhat gia'}</span>
          </div>
        </div>
      </div>
    `;
  };

  const renderSummaryBox = (booking) => `
    <div class="dispatch-summary-box">
      <span class="dispatch-summary-box__label">Tong chi phi</span>
      <span class="dispatch-summary-box__value">${formatMoney(getBookingTotal(booking))}</span>
      <span class="dispatch-summary-box__hint">Da san sang de khach thanh toan.</span>
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
        eyebrow: 'Chua thanh toan COD',
        method: 'Tien mat',
        hint: booking.trang_thai === 'cho_hoan_thanh'
          ? 'Ban da bao hoan thanh. Chi con buoc thu du tien mat roi xac nhan de chot don.'
          : 'Don dang giu phuong thuc tien mat. Kiem tra da nhan du tien truoc khi xac nhan hoan tat.',
        tone: 'cash',
      };
    }

    return {
      eyebrow: 'Cho thanh toan online',
      method: 'Chuyen khoan',
      hint: 'He thong dang cho khach hoan tat giao dich truc tuyen. Khi thanh toan thanh cong, don se tu chuyen hoan thanh.',
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
            <span class="dispatch-board-payment__stat-label">Tien cong</span>
            <span class="dispatch-board-payment__stat-value">${formatMoney(getBookingLaborTotal(booking))}</span>
          </div>
          <div class="dispatch-board-payment__stat">
            <span class="dispatch-board-payment__stat-label">Linh kien</span>
            <span class="dispatch-board-payment__stat-value">${formatMoney(getBookingPartsTotal(booking))}</span>
          </div>
          <div class="dispatch-board-payment__stat">
            <span class="dispatch-board-payment__stat-label">Phu phi</span>
            <span class="dispatch-board-payment__stat-value">${formatMoney(getBookingSurchargeTotal(booking))}</span>
          </div>
        </div>

        <p class="dispatch-board-payment__hint">${escapeHtml(paymentMeta.hint)}</p>
      </div>
    `;
  };

  const renderInlineNote = (booking) => {
    if (booking.trang_thai === 'dang_lam' && !hasUpdatedPricing(booking)) {
      return '<div class="dispatch-inline-note dispatch-inline-note--danger">Ban can cap nhat gia truoc khi su dung nut bao hoan thanh.</div>';
    }

    if (booking.trang_thai === 'da_xac_nhan') {
      return '<div class="dispatch-inline-note">Uu tien bat dau dung khung gio de giu trai nghiem dung hen cho khach.</div>';
    }

    if (booking.trang_thai === 'cho_thanh_toan') {
      return isCashPaymentBooking(booking)
        ? '<div class="dispatch-inline-note">Khach se thanh toan tien mat truc tiep. Chi xac nhan hoan tat sau khi ban da thu du tien mat.</div>'
        : '<div class="dispatch-inline-note">Don da duoc bao hoan thanh va dang cho khach thanh toan truc tuyen. He thong se tu chot don khi giao dich thanh cong.</div>';
    }

    if (booking.trang_thai === 'cho_hoan_thanh') {
      return '<div class="dispatch-inline-note">Khach thanh toan tien mat truc tiep. Sau khi thu du tien, ban can xac nhan de chot hoan tat don.</div>';
    }

    if (booking.trang_thai === 'da_xong') {
      return '<div class="dispatch-inline-note">Cong viec da hoan tat va duoc luu vao lich su xu ly.</div>';
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
        title: 'Ghi chu nhac bat dau dung gio',
        body: booking.mo_ta_van_de || 'Khach da chot lich. Vui long den dung khung gio de tranh tre hen.',
      };
    }

    if (booking.trang_thai === 'dang_lam') {
      if (hasUpdatedPricing(booking)) {
        return {
          tone: 'info',
          icon: 'price_check',
          title: 'Dich vu dang sua va da co bao gia',
          body: `Tong chi phi tam tinh hien tai la ${formatMoney(getBookingTotal(booking))}. Khi thiet bi da on dinh, ban co the bao hoan thanh ngay tren the nay.`,
        };
      }

      return {
        tone: 'danger',
        icon: 'warning',
        title: 'Dich vu dang sua, cho cap nhat chi phi',
        body: 'Hay dien tien cong, linh kien va phu phi truoc khi chuyen sang buoc bao hoan thanh cho khach.',
      };
    }

    if (booking.trang_thai === 'cho_thanh_toan' || booking.trang_thai === 'cho_hoan_thanh') {
      return isCashPaymentBooking(booking)
        ? {
            tone: 'info',
            icon: 'payments',
            title: 'Don dang cho xac nhan COD',
            body: 'Chi xac nhan hoan tat sau khi ban da thu du tien truc tiep tu khach hang.',
          }
        : {
            tone: 'info',
            icon: 'credit_card',
            title: 'Don dang cho thanh toan online',
            body: 'He thong se tu chot don khi giao dich truc tuyen cua khach thanh cong.',
          };
    }

    if (booking.trang_thai === 'da_xong') {
      return {
        tone: 'success',
        icon: 'task_alt',
        title: 'Cong viec da hoan tat',
        body: `Tong chi phi da chot la ${formatMoney(getBookingTotal(booking))}. Don nay hien nam trong lich su xu ly.`,
      };
    }

    if (booking.trang_thai === 'da_huy') {
      return {
        tone: 'danger',
        icon: 'cancel',
        title: 'Don da bi huy',
        body: 'Giu lai chi tiet de doi chieu neu can kiem tra nguyen nhan huy hoac lich su lam viec.',
      };
    }

    return {
      tone: 'info',
      icon: 'schedule',
      title: 'Don dang cho xac nhan',
      body: 'Kiem tra ky mo ta va thong tin lien he truoc khi thuc hien cac buoc tiep theo.',
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

    if (isClaimableMarketBooking(booking)) {
      actions.push(renderBoardButton({
        variant: 'main-success',
        icon: 'assignment_turned_in',
        label: 'Nhan don',
        onclick: `claimJob(${booking.id})`,
      }));
      actions.push(renderBoardButton({
        variant: 'secondary',
        icon: 'visibility',
        label: 'Chi tiet',
        onclick: `openViewDetailsModal(${booking.id})`,
      }));
    } else if (isAssignedPendingBooking(booking)) {
      actions.push(renderBoardButton({
        variant: 'main-success',
        icon: 'task_alt',
        label: 'Xac nhan don',
        onclick: `updateStatus(${booking.id}, 'da_xac_nhan')`,
      }));
      actions.push(renderBoardButton({
        variant: 'secondary',
        icon: 'visibility',
        label: 'Chi tiet',
        onclick: `openViewDetailsModal(${booking.id})`,
      }));
    } else if (booking.trang_thai === 'da_xac_nhan' || booking.trang_thai === 'khong_lien_lac_duoc_voi_khach_hang') {
      const contactIssueOpen = Boolean(booking?.worker_contact_issue?.is_open);

      actions.push(renderBoardButton({
        variant: contactIssueOpen ? 'main-disabled' : 'main',
        icon: contactIssueOpen ? 'hourglass_top' : 'play_arrow',
        label: contactIssueOpen ? 'Cho admin xu ly' : 'Bat dau sua',
        onclick: `updateStatus(${booking.id}, 'dang_lam')`,
        disabled: contactIssueOpen,
        title: contactIssueOpen
          ? 'Don dang duoc admin ho tro lien he khach hang'
          : 'Bat dau xu ly don sap toi',
      }));
      actions.push(renderBoardButton({
        variant: 'secondary',
        icon: 'visibility',
        label: 'Chi tiet',
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
        label: pricingReady ? 'Bao hoan thanh' : 'Cap nhat gia',
        onclick: pricingReady ? `openCompleteModal(${booking.id})` : `openCostModal(${booking.id})`,
        title: pricingReady ? 'San sang bao hoan thanh' : 'Cap nhat bang gia sua chua',
      }));
      actions.push(renderBoardButton({
        variant: 'secondary',
        icon: pricingReady ? 'price_change' : 'visibility',
        label: pricingReady ? 'Cap nhat gia' : 'Chi tiet',
        onclick: pricingReady ? `openCostModal(${booking.id})` : `openViewDetailsModal(${booking.id})`,
      }));
      if (pricingReady) {
        utilityActions.push(renderBoardButton({
          variant: 'icon',
          icon: 'visibility',
          onclick: `openViewDetailsModal(${booking.id})`,
          title: 'Xem chi tiet dich vu dang sua',
        }));
      }
    } else if (booking.trang_thai === 'cho_thanh_toan' || booking.trang_thai === 'cho_hoan_thanh') {
      actions.push(renderBoardButton({
        variant: 'main-warm',
        icon: 'payments',
        label: isCashPaymentBooking(booking) ? 'Xac nhan da thu' : 'Theo doi TT',
        onclick: isCashPaymentBooking(booking) ? `confirmCashPayment(${booking.id})` : `openViewDetailsModal(${booking.id})`,
      }));
      actions.push(renderBoardButton({
        variant: 'secondary',
        icon: 'receipt_long',
        label: 'Chi tiet',
        onclick: `openViewDetailsModal(${booking.id})`,
      }));
    } else {
      actions.push(renderBoardButton({
        variant: 'secondary',
        icon: 'visibility',
        label: 'Chi tiet',
        onclick: `openViewDetailsModal(${booking.id})`,
      }));
    }

    const utilityLimit = 3;

    if (canOpenRouteGuide(booking) && utilityActions.length < utilityLimit) {
      utilityActions.push(renderBoardButton({
        variant: 'icon',
        icon: 'near_me',
        onclick: `openRouteGuide(${booking.id})`,
        title: 'Mo chi duong',
      }));
    }

    if (getPhoneNumber(booking) && utilityActions.length < utilityLimit) {
      utilityActions.push(renderBoardButton({
        variant: 'icon',
        icon: 'call',
        href: getPhoneHref(booking),
        title: `Goi ${getCustomerName(booking)}`,
      }));
    }

    return actions.concat(utilityActions).join('');
  };

  const renderCard = (booking) => {
    const tone = getStatusTone(booking);
    const title = getBookingServiceNames(booking);
    const serviceBadge = getServiceBadge(booking);
    const customerName = getCustomerName(booking);
    const customerPhone = getPhoneNumber(booking) || 'Chua co so lien he';
    const noteMarkup = renderBoardNote(booking);
    const paymentMarkup = renderBoardPaymentPanel(booking);
    const scheduleDateText = getBookingCardDateLabel(booking);
    const scheduleTimeText = getBookingPrimaryTimeLabel(booking);
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
                  <span class="dispatch-board-card__info-label">Dia diem</span>
                  <span class="dispatch-board-card__info-value">${escapeHtml(location)}</span>
                </span>
              </div>

              <div class="dispatch-board-card__info">
                <span class="dispatch-board-card__info-icon">
                  <span class="material-symbols-outlined">${escapeHtml(locationIcon)}</span>
                </span>
                <span class="dispatch-board-card__info-copy">
                  <span class="dispatch-board-card__info-label">Hinh thuc</span>
                  <span class="dispatch-board-card__info-value">${escapeHtml(locationLabel)}</span>
                </span>
              </div>

              <div class="dispatch-board-card__info">
                <span class="dispatch-board-card__info-icon">
                  <span class="material-symbols-outlined">event_note</span>
                </span>
                <span class="dispatch-board-card__info-copy">
                  <span class="dispatch-board-card__info-label">Lich hen</span>
                  <span class="dispatch-board-card__info-value">${escapeHtml(booking?.khung_gio_hen || 'Chua chon gio')}</span>
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



