@push('styles')
<style>
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

  .sidebar-logo {
    font-family: 'Poppins', sans-serif;
    font-weight: 800;
    font-size: 1.15rem;
    color: #0f172a;
    text-decoration: none;
  }

  .sidebar-logo span {
    color: #0EA5E9;
  }

  .sidebar-item {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .7rem 1.25rem;
    border-radius: .75rem;
    font-weight: 500;
    font-size: .875rem;
    color: #64748b;
    text-decoration: none;
    transition: all .2s cubic-bezier(0.4, 0, 0.2, 1);
    margin-bottom: 2px;
  }

  .sidebar-item:hover {
    background: #f0fdfa;
    color: #0d9488;
    transform: translateX(4px);
  }

  .sidebar-item.active {
    background: #e6fcf5;
    color: #0f172a;
    font-weight: 600;
    box-shadow: inset 4px 0 0 #2dd4bf;
  }

  .sidebar-item .material-symbols-outlined {
    font-size: 1.25rem;
  }

  .badge-menu {
    background: #ef4444;
    color: #fff;
    border-radius: 50%;
    font-size: .65rem;
    font-weight: 700;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: auto;
  }

  /* Dropdown Styles */
  .sidebar-footer .dropdown-toggle::after {
    display: none;
  }
  
  .sidebar-footer .dropdown-menu {
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border-radius: 0.75rem;
    padding: 0.5rem;
    min-width: 200px;
    margin-bottom: 0.5rem !important;
  }

  .sidebar-footer .dropdown-item {
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
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
    .worker-sidebar {
      transform: translateX(-100%);
      transition: transform 0.3s ease;
    }
    .worker-sidebar.show {
      transform: translateX(0);
    }
  }
</style>
@endpush

<aside class="worker-sidebar">
  <div style="padding:1.5rem 1.25rem 1rem; display:flex; align-items:center; gap:.75rem; border-bottom:1px solid #f1f5f9;">
    <a href="/" class="d-flex align-items-center gap-2 text-decoration-none">
      <div style="width:2.25rem; height:2.25rem; background:linear-gradient(135deg, #BAF2E9 0%, #0EA5E9 100%); border-radius:.75rem; display:flex; align-items:center; justify-content:center; box-shadow: 0 4px 6px -1px rgba(14, 165, 233, 0.2);">
        <span class="material-symbols-outlined" style="font-size:1.25rem; color:#fff;">home_repair_service</span>
      </div>
      <span class="sidebar-logo">Thợ Tốt <span>NTU</span></span>
    </a>
  </div>

  <nav style="padding:1.25rem .75rem; flex:1; display:flex; flex-direction:column;">
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
    @endphp

    @foreach($menu as $item)
      <a href="/{{ $item['path'] }}" class="sidebar-item {{ str_contains($currentRoute, $item['path']) ? 'active' : '' }}" 
         @if(isset($item['badge'])) id="{{ $item['badge'] }}" @endif>
        <span class="material-symbols-outlined">{{ $item['icon'] }}</span>
        {{ $item['label'] }}
        @if(isset($item['badge']))
          <span class="badge-menu" id="{{ $item['badge'] }}Count" style="display:none;">0</span>
        @endif
      </a>
    @endforeach
  </nav>

  <!-- Sidebar Footer with Dropdown -->
  <div class="sidebar-footer" style="padding:1rem; border-top:1px solid #f1f5f9;">
    <div class="dropup">
      <div class="dropdown-toggle d-flex align-items-center gap-3 p-2 rounded-3" style="background:#f8fafc; cursor:pointer;" data-bs-toggle="dropdown" aria-expanded="false">
        <div id="sidebarAvatar" 
             style="width:2.5rem; height:2.5rem; border-radius:50%; background:linear-gradient(135deg, #0EA5E9 0%, #2563eb 100%); color:#fff; font-weight:700; font-size:1rem; display:flex; align-items:center; justify-content:center; font-family:'Poppins',sans-serif; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
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
  import { callApi, getCurrentUser, showToast } from "{{ asset('assets/js/api.js') }}";

  document.addEventListener('DOMContentLoaded', () => {
    const user = getCurrentUser();
    if (!user || user.role !== 'worker') {
      window.location.href = '/login?role=worker';
      return;
    }

    // Update Sidebar/Dropdown UI with user info
    const elements = {
      sidebarName: document.getElementById('sidebarName'),
      sidebarAvatar: document.getElementById('sidebarAvatar'),
      dropdownHeaderName: document.getElementById('dropdownHeaderName'),
      dropdownHeaderEmail: document.getElementById('dropdownHeaderEmail')
    };

    if (elements.sidebarName) elements.sidebarName.textContent = user.name || 'Thợ';
    if (elements.sidebarAvatar) elements.sidebarAvatar.textContent = (user.name || 'T').charAt(0).toUpperCase();
    if (elements.dropdownHeaderName) elements.dropdownHeaderName.textContent = user.name || 'Thợ';
    if (elements.dropdownHeaderEmail) elements.dropdownHeaderEmail.textContent = user.email || '';

    // Global logout function for workers
    window.logoutWorker = async function() {
      try {
        await callApi('/logout', 'POST');
      } catch (e) {
        console.error('Logout API error:', e);
      } finally {
        localStorage.removeItem('access_token');
        localStorage.removeItem('user');
        window.location.href = '/login?role=worker';
      }
    };

    // Load available jobs count badge
    async function updateJobBadge() {
      try {
        const res = await callApi('/don-dat-lich/available', 'GET');
        if (res.ok) {
          const jobs = res.data.data || res.data || [];
          const count = jobs.length;
          const badge = document.getElementById('sidebarJobBadgeCount');
          if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
          }
        }
      } catch (e) { console.warn('Badge error:', e); }
    }
    updateJobBadge();
  });
</script>
@endpush
