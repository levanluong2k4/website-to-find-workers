@extends('layouts.app')
@section('title', 'Tổng quan - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Material+Symbols+Outlined" rel="stylesheet" />
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    corePlugins: {
      preflight: false
    }
  }
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts/dist/apexcharts.css" />
<style>
  .worker-main {
    margin-left: 240px;
    min-height: 100vh;
    background: #f8fafc;
  }

  .worker-header {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: .875rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
  }

  .stat-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.25rem 1.5rem;
    transition: all .2s;
  }

  .stat-card:hover {
    box-shadow: 0 8px 24px rgba(14, 165, 233, .1);
    border-color: #BAF2E9;
  }

  .activity-item {
    display: flex;
    align-items: center;
    gap: .875rem;
    padding: .75rem 0;
    border-bottom: 1px solid #f1f5f9;
  }

  .activity-item:last-child {
    border-bottom: none;
  }

  @media (max-width: 768px) {
    .worker-main {
      margin-left: 0;
    }
  }
</style>
@endpush

@section('content')
<div style="display:flex;">

  <!-- SIDEBAR COMPONENT -->
  <x-worker-sidebar />

  <!-- ===== MAIN CONTENT ===== -->
  <div class="worker-main" style="flex:1;">

    <!-- Top Header -->
    <div class="worker-header">
      <div>
        <h5 style="font-family:'Poppins',sans-serif; font-weight:700; margin:0; font-size:1rem; color:#0f172a;">Tổng quan Dashboard</h5>
        <p style="margin:0; font-size:.75rem; color:#64748b;" id="headerDate">Hôm nay, Thứ 2/03/2026</p>
      </div>
      <div style="display:flex; align-items:center; gap:1rem;">
        <!-- Notification Bell -->
        <div style="position:relative; cursor:pointer;">
          <span class="material-symbols-outlined" style="font-size:1.4rem; color:#64748b;">notifications</span>
          <span style="position:absolute; top:-4px; right:-4px; background:#ef4444; color:#fff; border-radius:50%; width:16px; height:16px; font-size:.6rem; font-weight:700; display:flex; align-items:center; justify-content:center;">3</span>
        </div>
        <!-- Shop badge -->
        <div style="display:flex; align-items:center; gap:.4rem; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:.5rem; padding:.35rem .75rem; font-size:.75rem; font-weight:600; color:#15803d;">
          <span class="material-symbols-outlined" style="font-size:.9rem;">storefront</span>
          Nha Trang
        </div>
      </div>
    </div>

    <div style="padding:1.5rem;">
      <!-- AI Suggestion Banner -->
      <div id="aiSuggestionBanner" style="display:none; background:linear-gradient(135deg,#BAF2E9,#e0f2fe); border:1px solid #BAF2E9; border-radius:1rem; padding:1rem 1.25rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:.875rem;">
        <span class="material-symbols-outlined" style="font-size:1.5rem; color:#0EA5E9;">psychology</span>
        <div style="flex:1;">
          <p style="font-weight:700; font-size:.875rem; margin:0; color:#0f172a;">AI Gợi ý việc gần bạn</p>
          <p style="font-size:.8rem; color:#475569; margin:0;" id="aiSuggestionText">Đang tìm việc phù hợp...</p>
        </div>
        <a href="/worker/dashboard" style="background:#0EA5E9; color:#fff; font-weight:600; font-size:.8rem; text-decoration:none; border-radius:.5rem; padding:.5rem 1rem; white-space:nowrap;">Xem việc</a>
      </div>

      <!-- Stat Cards -->
      <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem;">
        @php
        $statCards = [
        ['label'=>'Việc hôm nay','val'=>'0','icon'=>'work','color'=>'#0EA5E9','bg'=>'#e0f2fe'],
        ['label'=>'Đang sửa', 'val'=>'0','icon'=>'build','color'=>'#f59e0b','bg'=>'#fef3c7'],
        ['label'=>'Hoàn thành', 'val'=>'0','icon'=>'check_circle','color'=>'#10b981','bg'=>'#d1fae5'],
        ['label'=>'Đánh giá TB','val'=>'--','icon'=>'star','color'=>'#8b5cf6','bg'=>'#ede9fe'],
        ];
        @endphp
        @foreach($statCards as $i => $card)
        <div class="stat-card">
          <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem;">
            <span style="font-size:.8rem; font-weight:600; color:#64748b;">{{ $card['label'] }}</span>
            <div style="width:2.25rem; height:2.25rem; border-radius:.75rem; background:{{ $card['bg'] }}; display:flex; align-items:center; justify-content:center;">
              <span class="material-symbols-outlined" style="font-size:1.1rem; color:{{ $card['color'] }};">{{ $card['icon'] }}</span>
            </div>
          </div>
          <p data-stat="{{ $i }}" style="font-family:'Poppins',sans-serif; font-size:1.75rem; font-weight:800; margin:0; color:#0f172a;">{{ $card['val'] }}</p>
        </div>
        @endforeach
      </div>

      <!-- Charts Row -->
      <div style="display:grid; grid-template-columns:1.65fr 1fr; gap:1rem; margin-bottom:1.5rem;">
        <div class="stat-card">
          <h6 style="font-family:'Poppins',sans-serif; font-weight:700; font-size:.875rem; color:#0f172a; margin-bottom:1rem;">Việc hoàn thành theo tuần</h6>
          <div id="chartWeekly" style="height:200px;"></div>
        </div>
        <div class="stat-card">
          <h6 style="font-family:'Poppins',sans-serif; font-weight:700; font-size:.875rem; color:#0f172a; margin-bottom:1rem;">Phân loại thiết bị</h6>
          <div id="chartDeviceType" style="height:200px;"></div>
        </div>
      </div>

      <!-- Bottom Row -->
      <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:1rem;">

        <!-- Recent Activity -->
        <div class="stat-card">
          <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
            <h6 style="font-family:'Poppins',sans-serif; font-weight:700; font-size:.875rem; color:#0f172a; margin:0;">Hoạt động gần đây</h6>
            <a href="/worker/my-bookings" style="font-size:.75rem; color:#0EA5E9; text-decoration:none; font-weight:600;">Xem tất cả</a>
          </div>
          <div id="recentActivityList">
            <div style="text-align:center; padding:2rem 0; color:#94a3b8; font-size:.875rem;">Đang tải...</div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div style="display:flex; flex-direction:column; gap:1rem;">
          <div class="stat-card">
            <h6 style="font-family:'Poppins',sans-serif; font-weight:700; font-size:.875rem; color:#0f172a; margin-bottom:1rem;">Thao tác nhanh</h6>
            <div style="display:flex; flex-direction:column; gap:.65rem;">
              <button onclick="window.location.href='/worker/dashboard'" style="display:flex; align-items:center; gap:.625rem; background:#BAF2E9; color:#0f172a; border:none; border-radius:.75rem; padding:.75rem 1rem; font-weight:700; font-size:.875rem; cursor:pointer; width:100%; transition:all .2s;" onmouseover="this.style.background='#0EA5E9';this.style.color='#fff'" onmouseout="this.style.background='#BAF2E9';this.style.color='#0f172a'">
                <span class="material-symbols-outlined" style="font-size:1.1rem;">add_task</span> Nhận việc mới
              </button>
              <button onclick="window.location.href='/worker/my-bookings'" style="display:flex; align-items:center; gap:.625rem; background:#f1f5f9; color:#334155; border:none; border-radius:.75rem; padding:.75rem 1rem; font-weight:600; font-size:.875rem; cursor:pointer; width:100%; transition:all .2s;">
                <span class="material-symbols-outlined" style="font-size:1.1rem; color:#0EA5E9;">calendar_month</span> Xem lịch
              </button>
              <button style="display:flex; align-items:center; gap:.625rem; background:#f1f5f9; color:#334155; border:none; border-radius:.75rem; padding:.75rem 1rem; font-weight:600; font-size:.875rem; cursor:pointer; width:100%; transition:all .2s;">
                <span class="material-symbols-outlined" style="font-size:1.1rem; color:#0EA5E9;">chat</span> Mở chat
              </button>
            </div>
          </div>

          <!-- Upcoming today -->
          <div class="stat-card">
            <h6 style="font-family:'Poppins',sans-serif; font-weight:700; font-size:.875rem; color:#0f172a; margin-bottom:.75rem;">
              <span class="material-symbols-outlined" style="font-size:1rem; vertical-align:middle; color:#f59e0b;">schedule</span>
              Lịch hôm nay
            </h6>
            <div id="todayScheduleList" style="font-size:.8rem; color:#64748b;">Đang tải lịch...</div>
          </div>
        </div>
      </div>
    </div>
  </div><!-- end worker-main -->
</div><!-- end flex wrapper -->

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script type="module">
  import {
    callApi,
    getCurrentUser,
    showToast
  } from "{{ asset('assets/js/api.js') }}";

  const baseUrl = '{{ url('/') }}';
  const user = getCurrentUser();
  if (!user || !['worker', 'admin'].includes(user.role)) {
    window.location.href = baseUrl + '/login?role=worker';
  }

  // Update date
  const days = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
  const now = new Date();
  const headerDate = document.getElementById('headerDate');
  if (headerDate) {
    headerDate.textContent = `${days[now.getDay()]}, ${now.toLocaleDateString('vi-VN')}`;
  }

  // Load dashboard stats
  async function loadStats() {
    try {
      const bookings = await callApi('/worker/my-bookings', 'GET');
      if (!bookings.ok) return;
      const all = bookings.data.data || [];
      const today = now.toISOString().slice(0, 10);
      const todayJobs = all.filter(b => b.booking_date === today);
      const inProgress = all.filter(b => ['da_xac_nhan', 'dang_lam'].includes(b.status));
      const completed = all.filter(b => b.status === 'da_xong');

      // Update stat cards by data-stat index
      const s0 = document.querySelector('[data-stat="0"]');
      const s1 = document.querySelector('[data-stat="1"]');
      const s2 = document.querySelector('[data-stat="2"]');
      if (s0) s0.textContent = todayJobs.length;
      if (s1) s1.textContent = inProgress.length;
      if (s2) s2.textContent = completed.length;

      // Sidebar badge
      const available = await callApi('/bookings/available', 'GET');
      if (available.ok) {
        const cnt = (available.data.data || []).length;
        const badge = document.getElementById('sidebarJobBadge');
        if (badge) badge.textContent = cnt;
        if (cnt > 0) {
          document.getElementById('aiSuggestionBanner').style.display = 'flex';
          document.getElementById('aiSuggestionText').textContent = `Có ${cnt} việc gần bạn đang chờ nhận – AI gợi ý việc phù hợp nhất!`;
        }
      }

      // Recent activity
      const actList = document.getElementById('recentActivityList');
      if (actList) {
        const recent = all.slice(0, 5);
        if (recent.length === 0) {
          actList.innerHTML = '<div style="text-align:center;padding:1.5rem;color:#94a3b8;font-size:.875rem;">Chưa có hoạt động nào</div>';
        } else {
          actList.innerHTML = recent.map(b => {
            const icons = {
              da_xac_nhan: 'check_circle',
              dang_lam: 'build',
              da_xong: 'done_all',
              da_huy: 'cancel',
              cho_xac_nhan: 'schedule'
            };
            const colors = {
              da_xac_nhan: '#0EA5E9',
              dang_lam: '#f59e0b',
              da_xong: '#10b981',
              da_huy: '#ef4444',
              cho_xac_nhan: '#64748b'
            };
            const icon = icons[b.status] || 'info';
            const color = colors[b.status] || '#64748b';
            return `<div class="activity-item">
            <div style="width:2rem;height:2rem;border-radius:50%;background:${color}20;display:flex;align-items:center;justify-content:center;">
              <span class="material-symbols-outlined" style="font-size:.9rem;color:${color};">${icon}</span>
            </div>
            <div style="flex:1;">
              <p style="margin:0;font-size:.8rem;font-weight:600;color:#0f172a;">${b.customer_name || 'Khách hàng'}</p>
              <p style="margin:0;font-size:.72rem;color:#64748b;">${b.appliance_type || 'Thiết bị'} · ${b.booking_date || ''}</p>
            </div>
          </div>`;
          }).join('');
        }
      }

      // Today schedule
      const todaySched = document.getElementById('todayScheduleList');
      if (todaySched) {
        if (todayJobs.length === 0) {
          todaySched.innerHTML = '<p style="text-align:center;margin:1rem 0;color:#94a3b8;">Không có lịch hôm nay</p>';
        } else {
          todaySched.innerHTML = todayJobs.slice(0, 3).map(b => `
          <div style="display:flex;align-items:center;gap:.5rem;padding:.4rem 0;border-bottom:1px solid #f1f5f9;">
            <span class="material-symbols-outlined" style="font-size:.85rem;color:#0EA5E9;">schedule</span>
            <div>
              <span style="font-weight:600;color:#0f172a;">${b.customer_name || 'KH'}</span>
              <span style="color:#64748b;margin-left:.25rem;">${b.booking_time || ''}</span>
            </div>
          </div>`).join('');
        }
      }
    } catch (e) {
      console.error(e);
    }
  }
  loadStats();

  // Weekly chart
  const chartWeekly = new ApexCharts(document.getElementById('chartWeekly'), {
    chart: {
      type: 'bar',
      height: 200,
      toolbar: {
        show: false
      },
      fontFamily: 'Inter,sans-serif'
    },
    colors: ['#0EA5E9'],
    series: [{
      name: 'Việc hoàn thành',
      data: [2, 5, 3, 7, 4, 6, 3]
    }],
    xaxis: {
      categories: ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'],
      labels: {
        style: {
          fontSize: '11px'
        }
      }
    },
    yaxis: {
      labels: {
        style: {
          fontSize: '11px'
        }
      }
    },
    plotOptions: {
      bar: {
        borderRadius: 6,
        columnWidth: '55%'
      }
    },
    dataLabels: {
      enabled: false
    },
    grid: {
      borderColor: '#f1f5f9'
    }
  });
  chartWeekly.render();

  // Donut chart
  const chartDevice = new ApexCharts(document.getElementById('chartDeviceType'), {
    chart: {
      type: 'donut',
      height: 200,
      fontFamily: 'Inter,sans-serif'
    },
    colors: ['#0EA5E9', '#BAF2E9', '#f59e0b', '#818cf8', '#10b981'],
    series: [35, 25, 20, 15, 5],
    labels: ['Tivi', 'Máy giặt', 'Tủ lạnh', 'Điều hòa', 'Khác'],
    legend: {
      position: 'bottom',
      fontSize: '11px'
    },
    dataLabels: {
      enabled: false
    },
    plotOptions: {
      pie: {
        donut: {
          size: '60%'
        }
      }
    }
  });
  chartDevice.render();
</script>
@endpush
