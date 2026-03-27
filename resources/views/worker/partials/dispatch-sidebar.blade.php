@php
  $context = $context ?? 'jobs';
  $brandDark = $brandDark ?? false;
  $ctaLabel = $ctaLabel ?? ($context === 'detail' ? 'Quay lại việc mới' : 'Điều phối mới');
  $ctaHref = $ctaHref ?? ($context === 'detail' ? '/worker/jobs' : 'javascript:void(0)');
  $ctaOnclick = $ctaOnclick ?? ($context === 'detail' ? '' : 'loadAvailableJobs()');
  $hotZoneTitle = $hotZoneTitle ?? 'Vùng nóng: Khu vực bạn đang bật';
  $hotZoneCopy = $hotZoneCopy ?? 'Tập trung nhiều việc phù hợp với nhóm dịch vụ mà bạn đã đăng ký.';

  $menu = [
      [
          'href' => '/worker/dashboard',
          'label' => 'Bảng điều khiển',
          'icon' => 'dashboard',
          'active' => request()->is('worker/dashboard'),
      ],
      [
          'href' => '/worker/jobs',
          'label' => 'Việc mới',
          'icon' => 'build',
          'active' => request()->is('worker/jobs*'),
      ],
      [
          'href' => '/worker/my-bookings',
          'label' => 'Lịch trình',
          'icon' => 'calendar_month',
          'active' => request()->is('worker/my-bookings*'),
      ],
      [
          'href' => '/worker/analytics',
          'label' => 'Thống kê',
          'icon' => 'monitoring',
          'active' => request()->is('worker/analytics*'),
      ],
      [
          'href' => '/worker/reviews',
          'label' => 'Hỗ trợ',
          'icon' => 'support_agent',
          'active' => request()->is('worker/reviews*'),
      ],
      [
          'href' => '/worker/profile',
          'label' => 'Ho so',
          'icon' => 'account_circle',
          'active' => request()->is('worker/profile*'),
      ],
  ];
@endphp

<aside class="dispatch-sidebar">
  <a href="/" class="dispatch-brand {{ $brandDark ? 'dispatch-brand--dark' : '' }}">Thợ Tốt NTU</a>

  <div>
    <div class="dispatch-operating-note">
      <span class="material-symbols-outlined">developer_board</span>
      <span>Điều phối v2.1</span>
    </div>
    <div class="dispatch-sidebar-caption">Chế độ vận hành</div>
  </div>

  <nav class="dispatch-sidebar-group">
    @foreach ($menu as $item)
      <a href="{{ $item['href'] }}" class="dispatch-nav-item {{ $item['active'] ? 'is-active' : '' }}">
        <span class="material-symbols-outlined">{{ $item['icon'] }}</span>
        <span>{{ $item['label'] }}</span>
      </a>
    @endforeach
  </nav>

  <a href="{{ $ctaHref }}" class="dispatch-sidebar-cta" @if($ctaOnclick) onclick="{{ $ctaOnclick }}" @endif>
    <span class="material-symbols-outlined">{{ $context === 'detail' ? 'arrow_back' : 'add_circle' }}</span>
    <span>{{ $ctaLabel }}</span>
  </a>

  <div class="dispatch-hot-zone">
    <div class="dispatch-hot-zone-title" id="dispatchHotZoneTitle">{{ $hotZoneTitle }}</div>
    <div class="dispatch-hot-zone-copy" id="dispatchHotZoneCopy">{{ $hotZoneCopy }}</div>
  </div>

  <div class="dispatch-sidebar-user">
    <div class="dispatch-avatar" id="dispatchSidebarAvatar">TT</div>
    <div>
      <div class="dispatch-user-name" id="dispatchSidebarName">Đang tải...</div>
      <div class="dispatch-user-role" id="dispatchSidebarRole">Thợ kỹ thuật</div>
    </div>
    <a href="/worker/profile" class="dispatch-settings-link" aria-label="Cài đặt hồ sơ">
      <span class="material-symbols-outlined">settings</span>
    </a>
  </div>
</aside>
