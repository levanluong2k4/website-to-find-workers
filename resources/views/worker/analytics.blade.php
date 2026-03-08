@extends('layouts.app')
@section('title', 'Lịch sử sửa chữa - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { corePlugins: { preflight: false } }</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts/dist/apexcharts.css"/>
<style>
  .worker-main { margin-left:240px; min-height:100vh; background:#f8fafc; }
  .worker-header { background:#fff; border-bottom:1px solid #e2e8f0; padding:.875rem 1.5rem; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; }
  .stat-card { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; padding:1.25rem; }
  .hist-table { width:100%; border-collapse:collapse; font-size:.82rem; }
  .hist-table th { background:#f8fafc; color:#64748b; font-weight:700; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; padding:.75rem 1rem; text-align:left; border-bottom:2px solid #e2e8f0; }
  .hist-table td { padding:.875rem 1rem; border-bottom:1px solid #f1f5f9; color:#374151; vertical-align:middle; }
  .hist-table tr:hover td { background:#f8fafc; }
  .chip { display:inline-flex; align-items:center; padding:.2rem .6rem; border-radius:2rem; font-size:.72rem; font-weight:700; }
  .chip-done { background:#d1fae5; color:#065f46; }
  .chip-cancelled { background:#fee2e2; color:#991b1b; }
  .chip-home { background:#e0f2fe; color:#0369a1; }
  .chip-store { background:#ede9fe; color:#5b21b6; }
  /* Side drawer */
  .detail-drawer { position:fixed; top:0; right:0; width:360px; height:100vh; background:#fff; box-shadow:-4px 0 24px rgba(0,0,0,.12); z-index:500; transform:translateX(100%); transition:transform .3s cubic-bezier(.4,0,.2,1); overflow-y:auto; }
  .detail-drawer.open { transform:translateX(0); }
  @media (max-width:768px) { .worker-main{margin-left:0;} }
</style>
@endpush

@section('content')
<div style="display:flex;">

<!-- SIDEBAR COMPONENT -->
<x-worker-sidebar />

<!-- MAIN -->
<div class="worker-main" style="flex:1;">
  <div class="worker-header">
    <div>
      <h5 style="font-family:'Poppins',sans-serif; font-weight:700; margin:0; font-size:1rem; color:#0f172a;">Lịch sử sửa chữa</h5>
      <p style="margin:0; font-size:.75rem; color:#64748b;">Thống kê thu nhập và lịch sử công việc</p>
    </div>
    <a href="/worker/my-bookings" style="display:flex; align-items:center; gap:.4rem; background:#BAF2E9; color:#0f172a; font-weight:700; font-size:.8rem; text-decoration:none; border-radius:.5rem; padding:.5rem 1rem;">
      <span class="material-symbols-outlined" style="font-size:.9rem;">receipt_long</span> Đơn của tôi
    </a>
  </div>

  <div style="padding:1.5rem;">
    <!-- Stat Cards -->
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.25rem;">
      <div class="stat-card" style="border-left:4px solid #0EA5E9;">
        <p style="font-size:.72rem; font-weight:700; color:#64748b; text-transform:uppercase; margin:0 0 .5rem;">Tổng doanh thu</p>
        <p id="statTongDoanhThu" style="font-family:'Poppins',sans-serif; font-size:1.4rem; font-weight:800; margin:0; color:#0f172a;">0 ₫</p>
      </div>
      <div class="stat-card" style="border-left:4px solid #10b981;">
        <p style="font-size:.72rem; font-weight:700; color:#64748b; text-transform:uppercase; margin:0 0 .5rem;">Tháng này</p>
        <p id="statDoanhThuThangNay" style="font-family:'Poppins',sans-serif; font-size:1.4rem; font-weight:800; margin:0; color:#0f172a;">0 ₫</p>
      </div>
      <div class="stat-card" style="border-left:4px solid #8b5cf6;">
        <p style="font-size:.72rem; font-weight:700; color:#64748b; text-transform:uppercase; margin:0 0 .5rem;">Đã hoàn thành</p>
        <p id="statDonHoanThanh" style="font-family:'Poppins',sans-serif; font-size:1.4rem; font-weight:800; margin:0; color:#0f172a;">0</p>
      </div>
      <div class="stat-card" style="border-left:4px solid #ef4444;">
        <p style="font-size:.72rem; font-weight:700; color:#64748b; text-transform:uppercase; margin:0 0 .5rem;">Đã hủy</p>
        <p id="statDonHuy" style="font-family:'Poppins',sans-serif; font-size:1.4rem; font-weight:800; margin:0; color:#0f172a;">0</p>
      </div>
    </div>

    <!-- Chart -->
    <div class="stat-card" style="margin-bottom:1.25rem;">
      <h6 style="font-family:'Poppins',sans-serif; font-weight:700; font-size:.875rem; color:#0f172a; margin-bottom:1rem;">Doanh thu 7 ngày qua</h6>
      <div id="revenueChartApex" style="height:220px;"></div>
    </div>

    <!-- Filter row -->
    <div class="stat-card" style="padding:.875rem 1.25rem; margin-bottom:1rem; display:flex; flex-wrap:wrap; gap:.75rem; align-items:center;">
      <input type="text" id="filterSearch" placeholder="🔍 Tìm theo tên khách..." oninput="filterTable()"
        style="flex:1; min-width:180px; border:1px solid #e2e8f0; border-radius:.5rem; padding:.5rem .875rem; font-size:.82rem; outline:none;">
      <select id="filterDevice" onchange="filterTable()" style="border:1px solid #e2e8f0; border-radius:.5rem; padding:.5rem .875rem; font-size:.82rem; outline:none; color:#374151;">
        <option value="">Tất cả thiết bị</option>
        <option>Tivi</option><option>Tủ lạnh</option><option>Máy giặt</option><option>Điều hòa</option>
      </select>
      <select id="filterStatus" onchange="filterTable()" style="border:1px solid #e2e8f0; border-radius:.5rem; padding:.5rem .875rem; font-size:.82rem; outline:none; color:#374151;">
        <option value="">Tất cả trạng thái</option>
        <option value="da_xong">Hoàn thành</option>
        <option value="da_huy">Đã hủy</option>
      </select>
    </div>

    <!-- Table -->
    <div class="stat-card" style="padding:0; overflow:hidden;">
      <table class="hist-table" id="historyTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Ngày</th>
            <th>Khách hàng</th>
            <th>Thiết bị</th>
            <th>Loại sửa</th>
            <th>Giá cuối</th>
            <th>Đánh giá</th>
            <th>Trạng thái</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="historyTableBody">
          <tr><td colspan="9" style="text-align:center; padding:2.5rem; color:#94a3b8;">
            <span class="material-symbols-outlined" style="font-size:1.5rem; display:block;">update</span>Đang tải...
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>

<!-- Side Detail Drawer -->
<div class="detail-drawer" id="detailDrawer">
  <div style="display:flex; align-items:center; justify-content:space-between; padding:1.25rem; border-bottom:1px solid #f1f5f9; position:sticky; top:0; background:#fff; z-index:10;">
    <h6 style="font-family:'Poppins',sans-serif; font-weight:700; margin:0; color:#0f172a;">Chi tiết công việc</h6>
    <button onclick="closeDrawer()" style="background:none; border:none; cursor:pointer; color:#64748b;">
      <span class="material-symbols-outlined">close</span>
    </button>
  </div>
  <div id="drawerContent" style="padding:1.25rem;"></div>
</div>
<div id="drawerOverlay" onclick="closeDrawer()" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.3); z-index:499;"></div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script type="module">
import { callApi, getCurrentUser, showToast } from "{{ asset('assets/js/api.js') }}";
const baseUrl = '{{ url('/') }}';
const user = getCurrentUser();
if (!user || user.role !== 'worker') { window.location.href = baseUrl + '/login?role=worker'; }
if (user) {
  if(document.getElementById('sidebarName')) document.getElementById('sidebarName').textContent = user.name||'Thợ';
  if(document.getElementById('sidebarAvatar')) document.getElementById('sidebarAvatar').textContent = (user.name||'T').charAt(0).toUpperCase();
}

window.logoutWorker = async function(){
  try{ await callApi('/logout','POST'); }catch(e){}
  finally{
    localStorage.removeItem('access_token'); localStorage.removeItem('user');
    window.location.href = baseUrl + '/login?role=worker';
  }
};

// Rating stars helper
function renderStars(rating) {
  return [1,2,3,4,5].map(i => `<span class="material-symbols-outlined" style="font-size:.85rem; color:${i<=rating?'#f59e0b':'#e2e8f0'}; font-variation-settings:'FILL' 1;">star</span>`).join('');
}

const fmt = n => parseInt(n||0).toLocaleString('vi-VN') + ' ₫';

let allRows = [];

async function loadHistory() {
  try {
    const res = await callApi('/worker/my-bookings', 'GET');
    if (!res.ok) return;
    allRows = (res.data.data || []).filter(b => ['da_xong','da_huy'].includes(b.status));

    // Stats
    const done = allRows.filter(b=>b.status==='da_xong');
    const cancelled = allRows.filter(b=>b.status==='da_huy');
    const total = done.reduce((s,b)=>s+parseFloat(b.total_price||0),0);
    const thisMonth = new Date().toISOString().slice(0,7);
    const monthTotal = done.filter(b=>b.booking_date?.startsWith(thisMonth)).reduce((s,b)=>s+parseFloat(b.total_price||0),0);

    document.getElementById('statTongDoanhThu').textContent = fmt(total);
    document.getElementById('statDoanhThuThangNay').textContent = fmt(monthTotal);
    document.getElementById('statDonHoanThanh').textContent = done.length;
    document.getElementById('statDonHuy').textContent = cancelled.length;

    renderTable(allRows);

    // Chart – last 7 days
    const last7 = [...Array(7)].map((_,i)=>{
      const d = new Date(); d.setDate(d.getDate()-6+i);
      return d.toISOString().slice(0,10);
    });
    const amounts = last7.map(date => done.filter(b=>b.booking_date===date).reduce((s,b)=>s+parseFloat(b.total_price||0),0));
    const labels = last7.map(d=>d.slice(5).replace('-','/'));

    new ApexCharts(document.getElementById('revenueChartApex'), {
      chart: { type:'area', height:220, toolbar:{show:false}, fontFamily:'Inter,sans-serif' },
      colors: ['#0EA5E9'],
      fill: { type:'gradient', gradient:{ shadeIntensity:1, opacityFrom:.4, opacityTo:.05 } },
      series: [{ name:'Doanh thu', data: amounts }],
      xaxis: { categories: labels, labels:{style:{fontSize:'11px'}} },
      yaxis: { labels:{ formatter: v => (v/1000).toFixed(0)+'k', style:{fontSize:'11px'} } },
      dataLabels: { enabled:false },
      stroke: { curve:'smooth', width:2 },
      grid: { borderColor:'#f1f5f9' },
      tooltip: { y:{ formatter: v => fmt(v) } }
    }).render();
  } catch(e) { console.error(e); }
}

function renderTable(rows) {
  const tbody = document.getElementById('historyTableBody');
  if(!rows.length) {
    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2.5rem;color:#94a3b8;">
      <span class="material-symbols-outlined" style="font-size:1.5rem;display:block;">inbox</span>Chưa có lịch sử
    </td></tr>`;
    return;
  }
  tbody.innerHTML = rows.map((b,i) => `
    <tr style="cursor:pointer;" onclick="openDrawer(${b.id})">
      <td style="color:#94a3b8; font-weight:600;">${i+1}</td>
      <td>${b.booking_date ? b.booking_date.split('-').reverse().join('/') : '--'}</td>
      <td>
        <div style="display:flex;align-items:center;gap:.5rem;">
          <div style="width:28px;height:28px;border-radius:50%;background:#BAF2E9;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.72rem;color:#0EA5E9;">${(b.customer_name||'K').charAt(0)}</div>
          <span style="font-weight:600;">${b.customer_name||'--'}</span>
        </div>
      </td>
      <td><span style="display:flex;align-items:center;gap:.3rem;"><span class="material-symbols-outlined" style="font-size:.9rem;color:#0EA5E9;">build_circle</span>${b.appliance_type||'--'}</span></td>
      <td><span class="chip ${b.service_type==='tai_nha'?'chip-home':'chip-store'}">${b.service_type==='tai_nha'?'Tại nhà':'Cửa hàng'}</span></td>
      <td style="font-weight:700; color:#0f172a;">${fmt(b.total_price)}</td>
      <td>${b.rating ? renderStars(b.rating) : '<span style="color:#94a3b8;font-size:.75rem;">Chưa có</span>'}</td>
      <td><span class="chip ${b.status==='da_xong'?'chip-done':'chip-cancelled'}">${b.status==='da_xong'?'Hoàn thành':'Đã hủy'}</span></td>
      <td><span class="material-symbols-outlined" style="font-size:1rem;color:#0EA5E9;">chevron_right</span></td>
    </tr>`).join('');
}

window.filterTable = function(){
  const s = document.getElementById('filterSearch').value.toLowerCase();
  const d = document.getElementById('filterDevice').value.toLowerCase();
  const st = document.getElementById('filterStatus').value;
  const filtered = allRows.filter(b =>
    (!s || (b.customer_name||'').toLowerCase().includes(s)) &&
    (!d || (b.appliance_type||'').toLowerCase().includes(d)) &&
    (!st || b.status === st)
  );
  renderTable(filtered);
};

window.openDrawer = function(id) {
  const b = allRows.find(x=>x.id===id);
  if(!b) return;
  document.getElementById('drawerContent').innerHTML = `
    <div style="margin-bottom:1.25rem;">
      <div style="display:flex;align-items:center;gap:.75rem;padding:1rem;background:#f8fafc;border-radius:.75rem;margin-bottom:1rem;">
        <div style="width:40px;height:40px;border-radius:50%;background:#BAF2E9;display:flex;align-items:center;justify-content:center;font-weight:700;color:#0EA5E9;">${(b.customer_name||'K').charAt(0)}</div>
        <div>
          <p style="font-weight:700;margin:0;font-size:.875rem;">${b.customer_name||'--'}</p>
          <p style="font-size:.75rem;color:#64748b;margin:0;">${b.customer_phone||''}</p>
        </div>
      </div>
      <table style="width:100%;font-size:.8rem;border-collapse:collapse;">
        ${[
          ['Thiết bị', b.appliance_type||'--'],
          ['Loại sửa', b.service_type==='tai_nha'?'Sửa tại nhà':'Tại cửa hàng'],
          ['Ngày sửa', b.booking_date ? b.booking_date.split('-').reverse().join('/') : '--'],
          ['Giờ sửa', b.booking_time||'--'],
          ['Giá cuối', fmt(b.total_price)],
          ['Trạng thái', b.status==='da_xong'?'✅ Hoàn thành':'❌ Đã hủy'],
        ].map(([k,v])=>`<tr><td style="color:#64748b;padding:.5rem 0;border-bottom:1px solid #f1f5f9;font-weight:600;">${k}</td><td style="padding:.5rem 0;border-bottom:1px solid #f1f5f9;font-weight:700;color:#0f172a;text-align:right;">${v}</td></tr>`).join('')}
      </table>
      ${b.rating ? `<div style="margin-top:1rem;padding:.875rem;background:#fef9c3;border-radius:.75rem;">
        <p style="font-size:.75rem;font-weight:700;color:#92400e;margin:0 0 .4rem;">Đánh giá khách hàng</p>
        <div>${renderStars(b.rating)}</div>
        ${b.review_comment ? `<p style="font-size:.78rem;color:#374151;margin:.5rem 0 0;font-style:italic;">"${b.review_comment}"</p>` : ''}
      </div>` : ''}
    </div>`;
  document.getElementById('detailDrawer').classList.add('open');
  document.getElementById('drawerOverlay').style.display = 'block';
};

window.closeDrawer = function() {
  document.getElementById('detailDrawer').classList.remove('open');
  document.getElementById('drawerOverlay').style.display = 'none';
};

loadHistory();
</script>
@endpush