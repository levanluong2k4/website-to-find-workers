export function createTopbarNotificationCenter({
  callApi,
  showToast,
  escapeHtml,
  getNumeric,
  user,
  refs,
  buildWorkerBookingsHref,
  pollIntervalMs = 10000,
}) {
  const {
    button,
    badge,
    menu,
    list,
    markAllButton,
  } = refs;

  let initialized = false;
  let pollId = null;

  const getNotificationVisual = (type = 'booking_status_updated') => {
    switch (type) {
    case 'new_booking':
      return { icon: 'work', tone: 'is-warning' };
    case 'booking_claimed':
      return { icon: 'assignment_turned_in', tone: 'is-success' };
    case 'booking_in_progress':
      return { icon: 'construction', tone: '' };
    case 'booking_waiting_completion':
    case 'booking_payment_requested':
      return { icon: 'payments', tone: 'is-warning' };
    case 'booking_completed':
      return { icon: 'task_alt', tone: 'is-success' };
    case 'booking_cancelled':
      return { icon: 'cancel', tone: 'is-danger' };
    case 'booking_customer_unreachable':
      return { icon: 'phone_disabled', tone: 'is-danger' };
    default:
      return { icon: 'notifications', tone: '' };
    }
  };

  const formatNotificationTime = (value) => {
    if (!value) {
      return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
      return '';
    }

    return date.toLocaleString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const resolveNotificationDestination = (notification) => {
    const data = notification?.data || {};
    const bookingId = getNumeric(data.booking_id);
    const rawLink = typeof data.link === 'string' ? data.link.trim() : '';

    if (rawLink) {
      if (user?.role === 'worker') {
        if (bookingId > 0 && rawLink.startsWith('/customer/')) {
          return buildWorkerBookingsHref({ bookingId });
        }

        if (rawLink === '/worker/jobs') {
          return buildWorkerBookingsHref({ status: 'pending', bookingId: 0 });
        }
      }

      return rawLink;
    }

    if (bookingId > 0) {
      return user?.role === 'admin' ? '/admin/bookings' : buildWorkerBookingsHref({ bookingId });
    }

    return user?.role === 'admin' ? '/admin/bookings' : '/worker/my-bookings';
  };

  const renderNotificationList = (notifications = []) => {
    if (!list) {
      return;
    }

    if (!notifications.length) {
      list.innerHTML = `
        <div class="dispatch-board-topbar__notification-empty">
          <span class="material-symbols-outlined">notifications_off</span>
          <p>Chưa có thông báo nào.</p>
        </div>
      `;
      return;
    }

    list.innerHTML = notifications.map((notification) => {
      const data = notification?.data || {};
      const visual = getNotificationVisual(data.type || 'booking_status_updated');
      const chips = [
        data.booking_code || (data.booking_id ? `#${data.booking_id}` : ''),
        data.status_label || '',
        data.service_name || data.dich_vu_name || '',
        formatNotificationTime(notification?.created_at),
      ].filter(Boolean).slice(0, 3);

      return `
        <a
          href="${escapeHtml(resolveNotificationDestination(notification))}"
          class="dispatch-board-topbar__notification-item${notification?.read_at ? '' : ' is-unread'}"
          data-notification-id="${escapeHtml(notification?.id || '')}">
          <div class="dispatch-board-topbar__notification-row">
            <span class="dispatch-board-topbar__notification-icon ${visual.tone}">
              <span class="material-symbols-outlined">${escapeHtml(visual.icon)}</span>
            </span>
            <div class="dispatch-board-topbar__notification-copy">
              <strong>
                ${notification?.read_at ? '' : '<span class="dispatch-board-topbar__notification-unread-dot"></span>'}
                ${escapeHtml(data.title || 'Thông báo mới')}
              </strong>
              <p>${escapeHtml(data.message || 'Hệ thống vừa cập nhật tiến độ đơn sửa chữa của bạn.')}</p>
              <div class="dispatch-board-topbar__notification-meta">
                ${chips.map((chip) => `<span class="dispatch-board-topbar__notification-chip">${escapeHtml(chip)}</span>`).join('')}
              </div>
            </div>
          </div>
        </a>
      `;
    }).join('');
  };

  const setMenuState = (isOpen) => {
    if (!button || !menu) {
      return;
    }

    button.classList.toggle('is-active', isOpen);
    button.setAttribute('aria-expanded', String(isOpen));
    menu.classList.toggle('is-open', isOpen);
  };

  const refresh = async ({ showErrorToast = false } = {}) => {
    if (!badge || !list) {
      return;
    }

    try {
      const response = await callApi('/notifications/unread');
      if (!response.ok || !response.data) {
        throw new Error(response.data?.message || 'Không thể tải thông báo.');
      }

      const unreadCount = Number(response.data.unread_count || 0);
      const notifications = Array.isArray(response.data.notifications) ? response.data.notifications : [];

      renderNotificationList(notifications);

      if (unreadCount > 0) {
        badge.classList.remove('is-hidden');
        badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
      } else {
        badge.classList.add('is-hidden');
        badge.textContent = '0';
      }
    } catch (error) {
      console.error('Topbar notifications failed', error);
      if (showErrorToast) {
        showToast(error.message || 'Không thể tải thông báo.', 'error');
      }
    }
  };

  const init = () => {
    if (initialized || !button || !menu || !list || !badge) {
      return;
    }

    initialized = true;

    button.addEventListener('click', (event) => {
      event.stopPropagation();
      const willOpen = !menu.classList.contains('is-open');
      setMenuState(willOpen);

      if (willOpen) {
        refresh();
      }
    });

    markAllButton?.addEventListener('click', async (event) => {
      event.stopPropagation();

      try {
        const response = await callApi('/notifications/read-all', 'POST');
        if (!response.ok) {
          throw new Error(response.data?.message || 'Không thể đánh dấu đã đọc.');
        }

        await refresh();
      } catch (error) {
        showToast(error.message || 'Không thể đánh dấu thông báo đã đọc.', 'error');
      }
    });

    list.addEventListener('click', async (event) => {
      const target = event.target instanceof Element ? event.target.closest('[data-notification-id]') : null;
      if (!target) {
        return;
      }

      event.preventDefault();
      const notificationId = target.getAttribute('data-notification-id');
      const destination = target.getAttribute('href') || '/worker/my-bookings';

      try {
        if (notificationId) {
          await callApi(`/notifications/${notificationId}/read`, 'POST');
        }
      } catch (error) {
        console.error('Mark notification as read failed', error);
      }

      setMenuState(false);
      window.location.href = destination;
    });

    document.addEventListener('click', (event) => {
      if (!menu.contains(event.target) && !button.contains(event.target)) {
        setMenuState(false);
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setMenuState(false);
      }
    });

    refresh();
    pollId = window.setInterval(() => {
      refresh();
    }, pollIntervalMs);
  };

  const dispose = () => {
    if (pollId) {
      window.clearInterval(pollId);
      pollId = null;
    }
    setMenuState(false);
  };

  return {
    init,
    refresh,
    dispose,
    setMenuState,
  };
}
