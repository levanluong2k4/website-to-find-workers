@php
  $menu = [
    ['href' => '/worker/dashboard', 'icon' => 'dashboard', 'label' => 'Tổng quan', 'active' => request()->is('worker/dashboard')],
    ['href' => '/worker/my-bookings', 'icon' => 'calendar_month', 'label' => 'Lịch làm việc', 'active' => request()->is('worker/my-bookings*'), 'badge' => 'workerScheduleBadge', 'badgeTone' => 'info'],
    ['href' => '/worker/analytics', 'icon' => 'payments', 'label' => 'Doanh thu', 'active' => request()->is('worker/analytics')],
    ['href' => '/worker/reviews', 'icon' => 'star', 'label' => 'Đánh giá', 'active' => request()->is('worker/reviews'), 'badge' => 'workerReviewBadge', 'badgeTone' => 'warning'],
    ['href' => '/worker/profile', 'icon' => 'person', 'label' => 'Hồ sơ', 'active' => request()->is('worker/profile')],
  ];
  $activeMenu = collect($menu)->first(fn ($item) => $item['active']) ?? $menu[0];
@endphp

@push('styles')
<style>
  body.worker-sidebar-open {
    overflow: hidden;
  }

  .worker-mobile-topbar,
  .worker-sidebar-overlay {
    display: none;
  }

  .worker-sidebar {
    width: 240px;
    min-height: 100vh;
    background: #fff;
    border-right: 1px solid #e2e8f0;
    position: fixed;
    top: 0;
    left: 0;
    display: flex;
    flex-direction: column;
    z-index: 200;
  }

  .worker-sidebar__head {
    padding: 1.5rem 1.25rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    border-bottom: 1px solid #f1f5f9;
  }

  .worker-sidebar__brand {
    min-width: 0;
  }

  .worker-sidebar__brand-mark {
    width: 3rem;
    height: 3rem;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    overflow: hidden;
    box-shadow: 0 12px 28px rgba(3, 105, 161, 0.18);
  }

  .worker-sidebar__brand-mark img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
  }

  .sidebar-logo {
    font-family: 'DM Sans', sans-serif;
    font-weight: 800;
    font-size: 1.15rem;
    color: #0f172a;
    text-decoration: none;
  }

  .sidebar-logo span {
    color: #0ea5e9;
  }

  .sidebar-mobile-close {
    display: none;
    width: 2.75rem;
    height: 2.75rem;
    border: 0;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.08);
    color: inherit;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .sidebar-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.8rem 1rem;
    border-radius: 1rem;
    font-weight: 600;
    font-size: 0.92rem;
    color: #64748b;
    text-decoration: none;
    transition: background-color 160ms ease, color 160ms ease, transform 160ms ease;
    margin-bottom: 0.35rem;
  }

  .sidebar-item:hover {
    background: #f0fdfa;
    color: #0f172a;
    transform: translateX(3px);
  }

  .sidebar-item.active {
    background: #e0f2fe;
    color: #0f172a;
    box-shadow: inset 0 0 0 1px rgba(3, 105, 161, 0.12);
  }

  .sidebar-item .material-symbols-outlined {
    font-size: 1.25rem;
  }

  #sidebarAvatar {
    overflow: hidden;
    flex-shrink: 0;
  }

  #sidebarAvatar img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    border-radius: inherit;
  }

  .badge-menu {
    background: #ef4444;
    color: #fff;
    border-radius: 999px;
    font-size: 0.65rem;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
  }

  .badge-menu {
    margin-left: auto;
  }

  .badge-menu--info {
    background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
  }

  .badge-menu--warning {
    background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
  }

  .badge-menu--danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  }

  .sidebar-footer .dropdown-toggle::after {
    display: none;
  }

  .sidebar-footer .dropdown-menu {
    border: 1px solid #e2e8f0;
    box-shadow: 0 18px 48px rgba(15, 23, 42, 0.12);
    border-radius: 1rem;
    padding: 0.5rem;
    min-width: 220px;
    margin-bottom: 0.5rem !important;
  }

  .sidebar-footer .dropdown-item {
    border-radius: 0.75rem;
    padding: 0.6rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .sidebar-footer .dropdown-item:hover {
    background-color: #f8fafc;
    color: #0f172a;
  }

  .sidebar-footer .dropdown-item.text-danger:hover {
    background-color: #fef2f2;
    color: #dc2626;
  }

  @media (max-width: 768px) {
    .worker-mobile-topbar {
      position: fixed;
      top: max(12px, env(safe-area-inset-top, 0px));
      left: 12px;
      z-index: 220;
      display: inline-flex;
      align-items: center;
      gap: 0.75rem;
      max-width: calc(100vw - 24px);
      min-height: 56px;
      padding: 0.5rem 0.85rem 0.5rem 0.5rem;
      background: rgba(248, 250, 252, 0.94);
      border: 1px solid rgba(226, 232, 240, 0.92);
      border-radius: 999px;
      box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
      backdrop-filter: blur(18px);
    }

    .worker-mobile-topbar__trigger {
      width: 2.85rem;
      height: 2.85rem;
      border: 0;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      background: #fff;
      color: #0f172a;
      box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.16);
      flex-shrink: 0;
    }

    .worker-mobile-topbar__meta {
      min-width: 0;
    }

    .worker-mobile-topbar__title {
      font-family: 'DM Sans', sans-serif;
      display: block;
      font-size: 0.96rem;
      font-weight: 800;
      line-height: 1;
      color: #020617;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .worker-sidebar-overlay {
      position: fixed;
      inset: 0;
      display: block;
      z-index: 190;
      background: rgba(15, 23, 42, 0.54);
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.22s ease;
    }

    .worker-sidebar-overlay.show {
      opacity: 1;
      pointer-events: auto;
    }

    .worker-sidebar {
      max-width: min(84vw, 320px);
      border-right: 0;
      border-radius: 0 1.85rem 1.85rem 0;
      background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
      color: #fff;
      transform: translateX(-100%);
      transition: transform 0.28s ease, box-shadow 0.28s ease;
      box-shadow: none;
    }

    .worker-sidebar.show {
      transform: translateX(0);
      box-shadow: 0 24px 60px rgba(15, 23, 42, 0.3);
    }

    .worker-sidebar__head {
      border-bottom-color: rgba(148, 163, 184, 0.12);
    }

    .sidebar-logo {
      color: #fff;
    }

    .sidebar-logo span {
      color: #7dd3fc;
    }

    .sidebar-mobile-close {
      display: inline-flex;
      color: #fff;
    }

    .sidebar-item {
      color: #cbd5e1;
      padding: 0.95rem 1rem;
      margin-bottom: 0.45rem;
    }

    .sidebar-item:hover {
      background: rgba(255, 255, 255, 0.08);
      color: #fff;
      transform: none;
    }

    .sidebar-item.active {
      background: rgba(14, 165, 233, 0.18);
      color: #fff;
      box-shadow: inset 0 0 0 1px rgba(125, 211, 252, 0.18);
    }

    .sidebar-footer {
      padding-bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
      border-top-color: rgba(148, 163, 184, 0.12) !important;
    }

    .sidebar-footer .dropdown-toggle {
      background: rgba(255, 255, 255, 0.08) !important;
    }

    .sidebar-footer #sidebarName {
      color: #fff !important;
    }

    .sidebar-footer #sidebarRole {
      color: #94a3b8 !important;
    }

    .worker-main,
    .worker-dashboard-main,
    .worker-jobs-main,
    .worker-revenue {
      padding-top: calc(max(12px, env(safe-area-inset-top, 0px)) + 56px + 12px);
    }

    .dispatch-shell,
    .worker-dashboard-content,
    .jobs-board {
      padding-bottom: calc(1.25rem + env(safe-area-inset-bottom, 0px));
    }
  }
</style>
@endpush

<div class="worker-mobile-topbar">
  <button
    type="button"
    class="worker-mobile-topbar__trigger"
    id="workerSidebarToggle"
    aria-label="Mở menu điều hướng"
  >
    <span class="material-symbols-outlined">menu</span>
  </button>

  <div class="worker-mobile-topbar__meta">
    <strong class="worker-mobile-topbar__title">{{ $activeMenu['label'] }}</strong>
  </div>
</div>

<div class="worker-sidebar-overlay" id="workerSidebarOverlay"></div>

<aside class="worker-sidebar" id="workerSidebar">
  <div class="worker-sidebar__head">
    <a href="/" class="d-flex align-items-center gap-2 text-decoration-none worker-sidebar__brand" data-worker-sidebar-brand>
      <div class="worker-sidebar__brand-mark">
        <img src="{{ asset('assets/images/logontu.png') }}" alt="Logo Thợ Tốt NTU">
      </div>
      <span class="sidebar-logo">Thợ Tốt <span>NTU</span></span>
    </a>

    <button type="button" class="sidebar-mobile-close" id="workerSidebarClose" aria-label="Đóng menu">
      <span class="material-symbols-outlined">close</span>
    </button>
  </div>

  <nav style="padding:1.25rem .75rem; flex:1; display:flex; flex-direction:column;">
    @foreach ($menu as $item)
      <a
        href="{{ $item['href'] }}"
        class="sidebar-item {{ $item['active'] ? 'active' : '' }}"
        data-sidebar-link
        @if (isset($item['badge'])) id="{{ $item['badge'] }}" @endif
      >
        <span class="material-symbols-outlined">{{ $item['icon'] }}</span>
        {{ $item['label'] }}
        @if (isset($item['badge']))
          <span class="badge-menu badge-menu--{{ $item['badgeTone'] ?? 'danger' }}" id="{{ $item['badge'] }}Count" style="display:none;">0</span>
        @endif
      </a>
    @endforeach
  </nav>

  <div class="sidebar-footer" style="padding:1rem; border-top:1px solid #f1f5f9;">
    <div class="dropup">
      <div
        class="dropdown-toggle d-flex align-items-center gap-3 p-2 rounded-3"
        style="background:#f8fafc; cursor:pointer;"
        data-bs-toggle="dropdown"
        aria-expanded="false"
      >
        <div
          id="sidebarAvatar"
          style="width:2.5rem; height:2.5rem; border-radius:50%; background:linear-gradient(135deg, #0EA5E9 0%, #2563eb 100%); color:#fff; font-weight:700; font-size:1rem; display:flex; align-items:center; justify-content:center; font-family:'DM Sans',sans-serif; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
        >
          T
        </div>
        <div class="flex-grow-1 overflow-hidden">
          <p id="sidebarName" style="font-weight:600; font-size:.875rem; margin:0; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Đang tải...</p>
          <p id="sidebarRole" style="font-size:.75rem; color:#64748b; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Thợ kỹ thuật</p>
        </div>
        <span class="material-symbols-outlined text-secondary" style="font-size: 1.25rem;">unfold_more</span>
      </div>

      <ul class="dropdown-menu w-100 shadow-lg border-0">
        <li>
          <div class="px-3 py-2 border-bottom mb-2">
            <p id="dropdownHeaderName" class="mb-0 fw-bold text-dark small"></p>
            <p id="dropdownHeaderEmail" class="mb-0 text-muted extra-small" style="font-size: 0.7rem;"></p>
          </div>
        </li>
        <li>
          <a class="dropdown-item" href="/worker/profile">
            <span class="material-symbols-outlined">account_circle</span> Tài khoản
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="/worker/my-bookings">
            <span class="material-symbols-outlined">assignment</span> Đơn đặt lịch
          </a>
        </li>
        <li><hr class="dropdown-divider mx-2"></li>
        <li>
          <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="logoutWorker()">
            <span class="material-symbols-outlined">logout</span> Đăng xuất
          </a>
        </li>
      </ul>
    </div>
  </div>
</aside>

@push('scripts')
<script type="module">
  import { callApi, getCurrentUser } from "{{ asset('assets/js/api.js') }}";

  document.addEventListener('DOMContentLoaded', () => {
    const ACTIVE_BOOKING_STATUSES = ['cho_xac_nhan', 'da_xac_nhan', 'khong_lien_lac_duoc_voi_khach_hang', 'dang_lam', 'cho_hoan_thanh', 'cho_thanh_toan'];
    const user = getCurrentUser();
    if (!user || !['worker', 'admin'].includes(user.role)) {
      window.location.href = '/login?role=worker';
      return;
    }

    const elements = {
      sidebarName: document.getElementById('sidebarName'),
      sidebarAvatar: document.getElementById('sidebarAvatar'),
      dropdownHeaderName: document.getElementById('dropdownHeaderName'),
      dropdownHeaderEmail: document.getElementById('dropdownHeaderEmail'),
      sidebar: document.getElementById('workerSidebar'),
      sidebarOverlay: document.getElementById('workerSidebarOverlay'),
      sidebarToggle: document.getElementById('workerSidebarToggle'),
      sidebarClose: document.getElementById('workerSidebarClose'),
      sidebarLinks: document.querySelectorAll('[data-sidebar-link], [data-worker-sidebar-brand]'),
      scheduleBadge: document.getElementById('workerScheduleBadgeCount'),
      reviewBadge: document.getElementById('workerReviewBadgeCount'),
    };
    const storageKey = `worker-sidebar-read-state:${user.id}`;
    const currentPath = window.location.pathname || '';

    const loadReadState = () => {
      try {
        const payload = JSON.parse(localStorage.getItem(storageKey) || '{}');
        return {
          bookingsSeenAt: Number(payload?.bookingsSeenAt || 0),
          reviewsSeenTotal: Number(payload?.reviewsSeenTotal || 0),
        };
      } catch (error) {
        return {
          bookingsSeenAt: 0,
          reviewsSeenTotal: 0,
        };
      }
    };

    const readState = loadReadState();

    const persistReadState = () => {
      localStorage.setItem(storageKey, JSON.stringify(readState));
    };

    const isBookingsPage = () => currentPath.startsWith('/worker/my-bookings');
    const isReviewsPage = () => currentPath.startsWith('/worker/reviews');
    const toTimestamp = (value) => {
      const parsed = Date.parse(value || '');
      return Number.isNaN(parsed) ? 0 : parsed;
    };
    const getAvatarUrl = (source) => {
      if (!source) return '';
      if (String(source).startsWith('http')) return source;
      if (String(source).startsWith('/')) return source;
      return `/storage/${source}`;
    };
    const setAvatarContent = (element, avatarUrl, fallbackName = 'T') => {
      if (!element) {
        return;
      }

      const safeFallback = String(fallbackName || 'T').charAt(0).toUpperCase();
      if (!avatarUrl) {
        element.textContent = safeFallback;
        return;
      }

      element.innerHTML = `<img src="${avatarUrl}" alt="${fallbackName}" style="width:100%;height:100%;display:block;object-fit:cover;border-radius:inherit;">`;
    };
    const syncSidebarIdentity = (payload = {}) => {
      const nextUser = {
        ...user,
        ...payload,
      };

      if (elements.sidebarName) {
        elements.sidebarName.textContent = nextUser.name || 'Thợ';
      }
      if (elements.dropdownHeaderName) {
        elements.dropdownHeaderName.textContent = nextUser.name || 'Thợ';
      }
      if (elements.dropdownHeaderEmail) {
        elements.dropdownHeaderEmail.textContent = nextUser.email || '';
      }

      setAvatarContent(elements.sidebarAvatar, getAvatarUrl(nextUser.avatar), nextUser.name || 'T');

      if (payload && Object.keys(payload).length > 0) {
        localStorage.setItem('user', JSON.stringify(nextUser));
      }
    };

    const isMobileSidebar = () => window.innerWidth <= 768;

    const closeSidebar = () => {
      if (!elements.sidebar || !elements.sidebarOverlay) {
        return;
      }

      elements.sidebar.classList.remove('show');
      elements.sidebarOverlay.classList.remove('show');
      document.body.classList.remove('worker-sidebar-open');
    };

    const openSidebar = () => {
      if (!isMobileSidebar() || !elements.sidebar || !elements.sidebarOverlay) {
        return;
      }

      elements.sidebar.classList.add('show');
      elements.sidebarOverlay.classList.add('show');
      document.body.classList.add('worker-sidebar-open');
    };

    elements.sidebarToggle?.addEventListener('click', openSidebar);
    elements.sidebarClose?.addEventListener('click', closeSidebar);
    elements.sidebarOverlay?.addEventListener('click', closeSidebar);
    elements.sidebarLinks.forEach((link) => {
      link.addEventListener('click', () => {
        if (isMobileSidebar()) {
          closeSidebar();
        }
      });
    });

    window.addEventListener('resize', () => {
      if (!isMobileSidebar()) {
        closeSidebar();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeSidebar();
      }
    });

    syncSidebarIdentity();

    const setMenuBadge = (badge, count, { tone = 'danger', label = '' } = {}) => {
      if (!badge) {
        return;
      }

      const safeCount = Number(count) || 0;
      badge.classList.remove('badge-menu--info', 'badge-menu--warning', 'badge-menu--danger');
      badge.classList.add(`badge-menu--${tone}`);

      if (safeCount <= 0) {
        badge.style.display = 'none';
        badge.textContent = '0';
        badge.removeAttribute('title');
        badge.removeAttribute('aria-label');
        return;
      }

      badge.style.display = 'inline-flex';
      badge.textContent = safeCount > 99 ? '99+' : String(safeCount);

      if (label) {
        badge.title = `${label}: ${safeCount}`;
        badge.setAttribute('aria-label', `${label}: ${safeCount}`);
      }
    };

    const refreshSidebarBadges = async () => {
      const [bookingResult, reviewResult] = await Promise.allSettled([
        callApi('/don-dat-lich?per_page=100', 'GET'),
        callApi(`/ho-so-tho/${user.id}/danh-gia/summary`, 'GET'),
      ]);

      if (bookingResult.status === 'fulfilled' && bookingResult.value?.ok) {
        const bookings = Array.isArray(bookingResult.value.data?.data)
          ? bookingResult.value.data.data
          : [];
        const activeBookings = bookings.filter((booking) => ACTIVE_BOOKING_STATUSES.includes(booking?.trang_thai));
        const latestActiveBookingAt = activeBookings.reduce((latestTimestamp, booking) => {
          const bookingTimestamp = toTimestamp(booking?.updated_at || booking?.created_at);
          return bookingTimestamp > latestTimestamp ? bookingTimestamp : latestTimestamp;
        }, 0);

        if (isBookingsPage()) {
          readState.bookingsSeenAt = latestActiveBookingAt;
          persistReadState();
        }

        const unreadBookingsCount = activeBookings.filter((booking) => {
          const bookingTimestamp = toTimestamp(booking?.updated_at || booking?.created_at);
          return bookingTimestamp > Number(readState.bookingsSeenAt || 0);
        }).length;

        setMenuBadge(elements.scheduleBadge, unreadBookingsCount, {
          tone: 'info',
          label: 'Việc mới chưa xem',
        });
      }

      if (reviewResult.status === 'fulfilled' && reviewResult.value?.ok) {
        const totalReviews = Number(reviewResult.value.data?.total_reviews || 0);
        const lowRatingReviews = Number(reviewResult.value.data?.low_rating_reviews || 0);

        if (isReviewsPage()) {
          readState.reviewsSeenTotal = totalReviews;
          persistReadState();
        }

        const unreadReviewsCount = Math.max(0, totalReviews - Number(readState.reviewsSeenTotal || 0));

        setMenuBadge(elements.reviewBadge, unreadReviewsCount, {
          tone: lowRatingReviews > 0 ? 'warning' : 'info',
          label: 'Đánh giá mới chưa xem',
        });
      }
    };

    const hydrateSidebarAvatar = async () => {
      if (getAvatarUrl(user.avatar)) {
        return;
      }

      try {
        const profileResult = await callApi(`/ho-so-tho/${user.id}`, 'GET');
        if (!profileResult.ok || !profileResult.data?.user) {
          return;
        }

        const profileUser = profileResult.data.user;
        syncSidebarIdentity({
          name: profileUser.name || user.name,
          email: profileUser.email || user.email,
          avatar: profileUser.avatar || '',
        });
      } catch (error) {
        console.error('Sidebar avatar sync error:', error);
      }
    };

    hydrateSidebarAvatar();
    refreshSidebarBadges();

    window.logoutWorker = async function logoutWorker() {
      try {
        await callApi('/logout', 'POST');
      } catch (error) {
        console.error('Logout API error:', error);
      } finally {
        localStorage.removeItem('access_token');
        localStorage.removeItem('user');
        window.location.href = '/login?role=worker';
      }
    };

  });
</script>
@endpush
