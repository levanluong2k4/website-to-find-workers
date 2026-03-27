@php
  $currentRoute = Request::path();
  $menu = [
    ['path' => 'worker/dashboard', 'icon' => 'dashboard', 'label' => 'Tổng quan'],
    ['path' => 'worker/jobs', 'icon' => 'work', 'label' => 'Việc mới', 'badge' => 'sidebarJobBadge'],
    ['path' => 'worker/my-bookings', 'icon' => 'calendar_month', 'label' => 'Lịch làm việc'],
    ['path' => 'worker/analytics', 'icon' => 'history', 'label' => 'Lịch sử'],
    ['path' => 'worker/reviews', 'icon' => 'star', 'label' => 'Đánh giá'],
    ['path' => 'worker/profile', 'icon' => 'person', 'label' => 'Hồ sơ'],
  ];
  $mobileDock = [
    ['path' => 'worker/dashboard', 'icon' => 'dashboard', 'label' => 'Tổng quan'],
    ['path' => 'worker/jobs', 'icon' => 'work', 'label' => 'Việc mới', 'badge' => 'mobileDockJobsBadge'],
    ['path' => 'worker/my-bookings', 'icon' => 'calendar_month', 'label' => 'Lịch'],
    ['path' => 'worker/profile', 'icon' => 'person', 'label' => 'Hồ sơ'],
  ];
  $activeMenu = collect($menu)->first(fn ($item) => str_contains($currentRoute, $item['path'])) ?? $menu[0];
@endphp

@push('styles')
<style>
  body.worker-sidebar-open {
    overflow: hidden;
  }

  .worker-mobile-topbar,
  .worker-mobile-dock,
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
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    background: linear-gradient(135deg, #38bdf8 0%, #0369a1 100%);
    box-shadow: 0 8px 20px rgba(3, 105, 161, 0.2);
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

  #sidebarAvatar,
  #workerMobileAvatar {
    overflow: hidden;
    flex-shrink: 0;
  }

  #sidebarAvatar img,
  #workerMobileAvatar img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    border-radius: inherit;
  }

  .badge-menu,
  .worker-mobile-dock__badge {
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
      right: 12px;
      z-index: 220;
      height: 68px;
      display: flex;
      align-items: center;
      gap: 0.85rem;
      padding: 0.85rem 0.95rem;
      background: rgba(248, 250, 252, 0.9);
      border: 1px solid rgba(226, 232, 240, 0.92);
      border-radius: 1.5rem;
      box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
      backdrop-filter: blur(18px);
    }

    .worker-mobile-topbar__trigger,
    .worker-mobile-topbar__avatar {
      width: 2.85rem;
      height: 2.85rem;
      border: 0;
      border-radius: 1rem;
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
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 0.15rem;
    }

    .worker-mobile-topbar__eyebrow {
      font-size: 0.65rem;
      font-weight: 800;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: #64748b;
    }

    .worker-mobile-topbar__title {
      font-family: 'DM Sans', sans-serif;
      font-size: 1.02rem;
      font-weight: 700;
      line-height: 1.15;
      color: #020617;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .worker-mobile-topbar__hint {
      font-size: 0.74rem;
      font-weight: 600;
      color: #0369a1;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .worker-mobile-dock {
      position: fixed;
      left: 12px;
      right: 12px;
      bottom: max(12px, env(safe-area-inset-bottom, 0px));
      z-index: 220;
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 0.45rem;
      padding: 0.6rem;
      background: rgba(15, 23, 42, 0.96);
      border: 1px solid rgba(148, 163, 184, 0.14);
      border-radius: 1.75rem;
      box-shadow: 0 24px 42px rgba(15, 23, 42, 0.22);
      backdrop-filter: blur(20px);
    }

    .worker-mobile-dock__item {
      min-height: 56px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 0.28rem;
      border-radius: 1.1rem;
      color: #94a3b8;
      text-decoration: none;
      position: relative;
      transition: background-color 160ms ease, color 160ms ease, transform 160ms ease;
    }

    .worker-mobile-dock__item.active {
      background: linear-gradient(135deg, rgba(14, 165, 233, 0.26), rgba(3, 105, 161, 0.42));
      color: #fff;
      transform: translateY(-1px);
    }

    .worker-mobile-dock__icon-wrap {
      position: relative;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .worker-mobile-dock__item .material-symbols-outlined {
      font-size: 1.3rem;
    }

    .worker-mobile-dock__label {
      font-size: 0.7rem;
      font-weight: 700;
      line-height: 1;
    }

    .worker-mobile-dock__badge {
      position: absolute;
      top: -6px;
      right: -10px;
      min-width: 16px;
      height: 16px;
      font-size: 0.6rem;
      padding: 0 4px;
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
    <span class="worker-mobile-topbar__eyebrow">Thợ Tốt NTU</span>
    <strong class="worker-mobile-topbar__title">{{ $activeMenu['label'] }}</strong>
    <span class="worker-mobile-topbar__hint">Điều hướng nhanh cho thợ</span>
  </div>

  <a href="/worker/profile" class="worker-mobile-topbar__avatar" aria-label="Mở hồ sơ">
    <div
      id="workerMobileAvatar"
      style="width:2rem; height:2rem; border-radius:999px; background:linear-gradient(135deg, #0EA5E9 0%, #2563eb 100%); color:#fff; font-weight:700; font-size:0.82rem; display:flex; align-items:center; justify-content:center; font-family:'DM Sans',sans-serif;"
    >
      T
    </div>
  </a>
</div>

<nav class="worker-mobile-dock" aria-label="Điều hướng nhanh">
  @foreach ($mobileDock as $item)
    <a
      href="/{{ $item['path'] }}"
      class="worker-mobile-dock__item {{ str_contains($currentRoute, $item['path']) ? 'active' : '' }}"
    >
      <span class="worker-mobile-dock__icon-wrap">
        <span class="material-symbols-outlined">{{ $item['icon'] }}</span>
        @if (isset($item['badge']))
          <span class="worker-mobile-dock__badge" id="{{ $item['badge'] }}Count" style="display:none;">0</span>
        @endif
      </span>
      <span class="worker-mobile-dock__label">{{ $item['label'] }}</span>
    </a>
  @endforeach
</nav>

<div class="worker-sidebar-overlay" id="workerSidebarOverlay"></div>

<aside class="worker-sidebar" id="workerSidebar">
  <div class="worker-sidebar__head">
    <a href="/" class="d-flex align-items-center gap-2 text-decoration-none worker-sidebar__brand" data-worker-sidebar-brand>
      <div class="worker-sidebar__brand-mark">
        <span class="material-symbols-outlined" style="font-size:1.25rem;">home_repair_service</span>
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
        href="/{{ $item['path'] }}"
        class="sidebar-item {{ str_contains($currentRoute, $item['path']) ? 'active' : '' }}"
        data-sidebar-link
        @if (isset($item['badge'])) id="{{ $item['badge'] }}" @endif
      >
        <span class="material-symbols-outlined">{{ $item['icon'] }}</span>
        {{ $item['label'] }}
        @if (isset($item['badge']))
          <span class="badge-menu" id="{{ $item['badge'] }}Count" style="display:none;">0</span>
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
    const user = getCurrentUser();
    if (!user || !['worker', 'admin'].includes(user.role)) {
      window.location.href = '/login?role=worker';
      return;
    }

    const elements = {
      sidebarName: document.getElementById('sidebarName'),
      sidebarAvatar: document.getElementById('sidebarAvatar'),
      workerMobileAvatar: document.getElementById('workerMobileAvatar'),
      dropdownHeaderName: document.getElementById('dropdownHeaderName'),
      dropdownHeaderEmail: document.getElementById('dropdownHeaderEmail'),
      sidebar: document.getElementById('workerSidebar'),
      sidebarOverlay: document.getElementById('workerSidebarOverlay'),
      sidebarToggle: document.getElementById('workerSidebarToggle'),
      sidebarClose: document.getElementById('workerSidebarClose'),
      sidebarLinks: document.querySelectorAll('[data-sidebar-link], [data-worker-sidebar-brand]'),
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

    const avatarLetter = (user.name || 'T').charAt(0).toUpperCase();
    if (elements.sidebarName) {
      elements.sidebarName.textContent = user.name || 'Thợ';
    }
    if (elements.sidebarAvatar) {
      elements.sidebarAvatar.textContent = avatarLetter;
    }
    if (elements.workerMobileAvatar) {
      elements.workerMobileAvatar.textContent = avatarLetter;
    }
    if (elements.dropdownHeaderName) {
      elements.dropdownHeaderName.textContent = user.name || 'Thợ';
    }
    if (elements.dropdownHeaderEmail) {
      elements.dropdownHeaderEmail.textContent = user.email || '';
    }

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

    async function updateJobBadge() {
      try {
        const res = await callApi('/don-dat-lich/available', 'GET');
        if (res.ok) {
          const jobs = res.data.data || res.data || [];
          const count = jobs.length;
          ['sidebarJobBadgeCount', 'mobileDockJobsBadgeCount'].forEach((badgeId) => {
            const badge = document.getElementById(badgeId);
            if (badge) {
              badge.textContent = count;
              badge.style.display = count > 0 ? 'inline-flex' : 'none';
            }
          });
        }
      } catch (error) {
        console.warn('Badge error:', error);
      }
    }

    updateJobBadge();
  });
</script>
@endpush
