@extends('layouts.app')
@section('title', 'Lịch làm việc - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { corePlugins: { preflight: false } }</script>
<style>
  .worker-main { margin-left:240px; min-height:100vh; background:#f8fafc; }
  .worker-header { background:#fff; border-bottom:1px solid #e2e8f0; padding:.875rem 1.5rem; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; }
  
  @media (max-width: 768px) { .worker-main{margin-left:0;} }
</style>
@endpush

@push('styles')
<style>
  .worker-main { margin-left:240px; min-height:100vh; background:#f8fafc; }
  .worker-header { background:#fff; border-bottom:1px solid #e2e8f0; padding:.875rem 1.5rem; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; }
  
  /* Status pills */
  .status-tab { padding:.45rem 1.1rem; border-radius:2rem; font-size:.78rem; font-weight:700; cursor:pointer; border:2px solid transparent; transition:all .15s; }
  .status-tab.active-tab { border-color:#0EA5E9; color:#0EA5E9; background:#e0f2fe; }
  .status-tab:not(.active-tab) { color:#64748b; background:#f1f5f9; }

  /* Booking card */
  .booking-card-new { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; padding:1.25rem; margin-bottom:.875rem; display:flex; gap:1rem; transition:box-shadow .2s; }
  .booking-card-new:hover { box-shadow:0 4px 18px rgba(0,0,0,.07); }
  .status-bar { width:.3rem; border-radius:.25rem; flex-shrink:0; align-self:stretch; }
  .status-bar.upcoming { background:#0EA5E9; }
  .status-bar.inprogress { background:#f59e0b; }
  .status-bar.done { background:#10b981; }
  .status-bar.cancelled { background:#ef4444; }
  .status-bar.waiting { background:#f59e0b; }

  /* Timer */
  .repair-timer { display:inline-flex; align-items:center; gap:.4rem; background:#fef3c7; color:#92400e; border-radius:.5rem; padding:.25rem .65rem; font-weight:700; font-size:.78rem; }

  /* Status chip */
  .chip { display:inline-flex; align-items:center; gap:.25rem; padding:.25rem .65rem; border-radius:2rem; font-size:.72rem; font-weight:700; }
  .chip-upcoming { background:#e0f2fe; color:#0369a1; }
  .chip-inprogress { background:#fef3c7; color:#92400e; }
  .chip-waiting { background:#ffedd5; color:#c2410c; }
  .chip-done { background:#d1fae5; color:#065f46; }
  .chip-cancelled { background:#fee2e2; color:#991b1b; }

  @media (max-width: 768px) { .worker-main{margin-left:0;} }
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
      <h5 style="font-family:'Poppins',sans-serif; font-weight:700; margin:0; font-size:1rem; color:#0f172a;">Lịch làm việc</h5>
      <p style="margin:0; font-size:.75rem; color:#64748b;">Quản lý các đơn sửa chữa của bạn</p>
    </div>
    <div style="display:flex; gap:.75rem; align-items:center;">
      <button onclick="loadMyBookings(currentStatus)" style="display:flex; align-items:center; gap:.4rem; background:#f1f5f9; border:none; border-radius:.5rem; padding:.5rem .875rem; font-size:.8rem; font-weight:600; color:#334155; cursor:pointer;">
        <span class="material-symbols-outlined" style="font-size:.9rem;">refresh</span> Làm mới
      </button>
    </div>
  </div>

  <div style="padding:1.5rem;">
    <!-- Status Tabs -->
    <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.25rem;">
      <button class="status-tab active-tab" data-status="all" onclick="switchTab(this,'all')">
        Tất cả <span id="cnt-all" style="background:#0EA5E9;color:#fff;border-radius:50%;padding:0 6px;font-size:.7rem;margin-left:.25rem;">0</span>
      </button>
      <button class="status-tab" data-status="upcoming" onclick="switchTab(this,'upcoming')">
        🔵 Sắp tới <span id="cnt-upcoming" style="background:#64748b;color:#fff;border-radius:50%;padding:0 6px;font-size:.7rem;margin-left:.25rem;">0</span>
      </button>
      <button class="status-tab" data-status="inprogress" onclick="switchTab(this,'inprogress')">
        🟠 Đang sửa <span id="cnt-inprogress">0</span>
      </button>
      <button class="status-tab" data-status="waiting" onclick="switchTab(this,'waiting')">
        🟡 Chờ xác nhận
      </button>
      <button class="status-tab" data-status="done" onclick="switchTab(this,'done')">
        🟢 Hoàn thành
      </button>
      <button class="status-tab" data-status="cancelled" onclick="switchTab(this,'cancelled')">
        🔴 Đã hủy
      </button>
    </div>

    <!-- Jobs Container -->
    <div id="bookingsContainer">
      <div style="text-align:center; padding:3rem; color:#94a3b8;">
        <span class="material-symbols-outlined" style="font-size:2rem; display:block; margin-bottom:.75rem;">update</span>
        Đang tải danh sách...
      </div>
    </div>
  </div>
</div>
</div>

<!-- Modal Cập Nhật Chi Phí -->
<div class="modal fade" id="modalCosts" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:1rem;">
      <div class="modal-header border-0 pb-0 pt-4 px-4">
        <h5 class="modal-title fw-bold text-dark">Cập Nhật Chi Phí</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <form id="formUpdateCosts">
          <input type="hidden" id="costBookingId">
          <div class="mb-3">
            <label class="form-label fw-semibold text-secondary small">Tiền công thợ (₫) <span class="text-danger">*</span></label>
            <input type="number" class="form-control bg-light" id="inputTienCong" placeholder="VD: 150.000" required min="0">
          </div>
          <div class="mb-3" id="truckFeeContainer" style="display:none;">
            <label class="form-label fw-semibold text-secondary small">Phí thuê xe chở (₫)</label>
            <input type="number" class="form-control bg-light" id="inputTienThueXe" placeholder="VD: 200.000" min="0">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold text-secondary small">Chi phí linh kiện thêm (₫)</label>
            <input type="number" class="form-control bg-light" id="inputPhiLinhKien" placeholder="VD: 50.000" min="0" value="0">
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold text-secondary small">Ghi chú linh kiện</label>
            <textarea class="form-control bg-light" id="inputGhiChuLinhKien" rows="2" placeholder="Ghi chú chi tiết vật tư đã thay..."></textarea>
          </div>
          <div class="alert alert-light border mb-4">
            <p class="mb-0 fw-bold text-dark">Phí đi lại: <span id="displayPhiDiLai" class="text-primary float-end">0 ₫</span></p>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3">Lưu & Cập Nhật</button>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script type="module">
import { callApi, getCurrentUser, showToast } from "{{ asset('assets/js/api.js') }}";
const baseUrl = '{{ url('/') }}';
const user = getCurrentUser();
if (!user || user.role !== 'worker') { window.location.href = baseUrl + '/login?role=worker'; }

window.currentStatus = 'all';
window.allBookings = [];

window.switchTab = function(el, status) {
  document.querySelectorAll('.status-tab').forEach(t => t.classList.remove('active-tab'));
  el.classList.add('active-tab');
  window.currentStatus = status;
  renderBookings(status);
};

const statusBarMap = { da_xac_nhan:'upcoming', dang_lam:'inprogress', cho_hoan_thanh:'waiting', da_xong:'done', da_huy:'cancelled', cho_xac_nhan:'waiting' };
const statusLabelMap = { da_xac_nhan:'Đã xác nhận', dang_lam:'Đang sửa', cho_hoan_thanh:'Chờ hoàn thành', da_xong:'Đã xong', da_huy:'Đã hủy', cho_xac_nhan:'Chờ xác nhận' };
const chipClassMap = { da_xac_nhan:'chip-upcoming', dang_lam:'chip-inprogress', cho_hoan_thanh:'chip-waiting', da_xong:'chip-done', da_huy:'chip-cancelled', cho_xac_nhan:'chip-waiting' };

let repairTimers = {};

function renderBookings(status) {
  let list = window.allBookings;
  if(status === 'upcoming') list = list.filter(b => b.trang_thai === 'da_xac_nhan');
  else if(status === 'inprogress') list = list.filter(b => b.trang_thai === 'dang_lam');
  else if(status === 'waiting') list = list.filter(b => ['cho_xac_nhan','cho_hoan_thanh'].includes(b.trang_thai));
  else if(status === 'done') list = list.filter(b => b.trang_thai === 'da_xong');
  else if(status === 'cancelled') list = list.filter(b => b.trang_thai === 'da_huy');

  const container = document.getElementById('bookingsContainer');
  if(list.length === 0) {
    container.innerHTML = `<div style="text-align:center;padding:3rem;color:#94a3b8;background:#fff;border-radius:1rem;border:1px dashed #e2e8f0;">
      <span class="material-symbols-outlined" style="font-size:2.5rem;display:block;">inbox</span>
      <p style="margin:.75rem 0 0;font-weight:600;">Không có đơn nào</p>
    </div>`;
    return;
  }

  container.innerHTML = list.map(b => {
    const barClass = statusBarMap[b.trang_thai] || 'upcoming';
    const chipClass = chipClassMap[b.trang_thai] || 'chip-upcoming';
    const label = statusLabelMap[b.trang_thai] || b.trang_thai;
    const isInProgress = b.trang_thai === 'dang_lam';
    const timerHtml = isInProgress ? `<div class="repair-timer"><span class="material-symbols-outlined" style="font-size:.85rem;">timer</span> Đang sửa <span id="timer-${b.id}">00:00:00</span></div>` : '';
    const actionBtns = b.trang_thai === 'da_xac_nhan'
      ? `<button onclick="updateStatus(${b.id},'dang_lam')" style="background:#BAF2E9;border:none;border-radius:.5rem;padding:.45rem .9rem;font-size:.78rem;font-weight:700;color:#0f172a;cursor:pointer;transition:.15s;" onmouseover="this.style.background='#0EA5E9';this.style.color='#fff';" onmouseout="this.style.background='#BAF2E9';this.style.color='#0f172a';">▶ Bắt đầu sửa</button>`
      : b.trang_thai === 'dang_lam'
      ? `<button onclick="updateStatus(${b.id},'cho_hoan_thanh')" style="background:#fef3c7;border:none;border-radius:.5rem;padding:.45rem .9rem;font-size:.78rem;font-weight:700;color:#92400e;cursor:pointer;">✓ Báo hoàn thành</button>
         <button onclick="openCostModal(${b.id})" style="background:#f1f5f9;border:none;border-radius:.5rem;padding:.45rem .9rem;font-size:.78rem;font-weight:600;color:#334155;cursor:pointer;margin-left:.35rem;">💰 Cập nhật giá</button>`
      : '';
    return `<div class="booking-card-new">
      <div class="status-bar ${barClass}"></div>
      <div style="flex:1;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.5rem;">
          <div>
            <p style="font-family:'Poppins',sans-serif;font-weight:700;font-size:.95rem;margin:0;color:#0f172a;">${b.khach_hang ? b.khach_hang.name : 'Khách hàng'}</p>
            <p style="margin:.15rem 0 0;font-size:.78rem;color:#64748b;">${b.dich_vu ? b.dich_vu.ten_dich_vu : 'Dịch vụ'} · ${b.dia_chi || ''}</p>
          </div>
          <span class="chip ${chipClass}"><span class="material-symbols-outlined" style="font-size:.75rem;">circle</span>${label}</span>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:.75rem;margin:.75rem 0;font-size:.78rem;color:#64748b;">
          <span><span class="material-symbols-outlined" style="font-size:.9rem;vertical-align:middle;">schedule</span> ${b.ngay_hen ? new Date(b.ngay_hen).toLocaleDateString('vi-VN') : ''} ${b.khung_gio_hen || ''}</span>
          <span><span class="material-symbols-outlined" style="font-size:.9rem;vertical-align:middle;">home</span> ${b.loai_dat_lich === 'at_home' ? 'Sửa tại nhà' : 'Tại cửa hàng'}</span>
          ${b.tong_tien ? `<span><span class="material-symbols-outlined" style="font-size:.9rem;vertical-align:middle;">payments</span> ${parseInt(b.tong_tien).toLocaleString('vi-VN')} ₫</span>` : ''}
        </div>
        ${timerHtml}
        <div style="display:flex;gap:.5rem;margin-top:.5rem;flex-wrap:wrap;">
          ${actionBtns}
          <a href="tel:${b.khach_hang ? b.khach_hang.phone : ''}" style="background:#f1f5f9;border-radius:.5rem;padding:.45rem .75rem;font-size:.78rem;font-weight:600;color:#334155;text-decoration:none;display:flex;align-items:center;gap:.25rem;">
            <span class="material-symbols-outlined" style="font-size:.85rem;">call</span>${b.khach_hang ? b.khach_hang.phone : ''}
          </a>
        </div>
      </div>
    </div>`;
  }).join('');

  // Start timers for in-progress jobs
  list.filter(b=>b.trang_thai==='dang_lam').forEach(b=>{
    const el = document.getElementById(`timer-${b.id}`);
    if(!el) return;
    clearInterval(repairTimers[b.id]);
    let secs = 0;
    repairTimers[b.id] = setInterval(()=>{
      secs++;
      const h = String(Math.floor(secs/3600)).padStart(2,'0');
      const m = String(Math.floor((secs%3600)/60)).padStart(2,'0');
      const s = String(secs%60).padStart(2,'0');
      el.textContent = `${h}:${m}:${s}`;
    }, 1000);
  });
}

window.loadMyBookings = async function(status='all') {
  try {
    const res = await callApi('/don-dat-lich', 'GET');
    if(!res.ok) return;
    window.allBookings = res.data.data || res.data || [];
    // Update badge counts
    document.getElementById('cnt-all').textContent = window.allBookings.length;
    document.getElementById('cnt-upcoming').textContent = window.allBookings.filter(b=>b.trang_thai==='da_xac_nhan').length;
    document.getElementById('cnt-inprogress').textContent = window.allBookings.filter(b=>b.trang_thai==='dang_lam').length;
    renderBookings(window.currentStatus);
  } catch(e) { console.error(e); }
};

window.updateStatus = async function(id, newStatus) {
  try {
    const res = await callApi(`/don-dat-lich/${id}/status`, 'PUT', { trang_thai: newStatus });
    if(res.ok) { showToast('Đã cập nhật trạng thái!'); loadMyBookings(); }
    else showToast(res.data.message||'Lỗi cập nhật', 'error');
  } catch(e) { showToast('Lỗi kết nối','error'); }
};

window.openCostModal = function(id) {
  document.getElementById('costBookingId').value = id;
  const modal = new bootstrap.Modal(document.getElementById('modalCosts'));
  modal.show();
};

document.getElementById('formUpdateCosts').addEventListener('submit', async e => {
  e.preventDefault();
  const id = document.getElementById('costBookingId').value;
  const body = {
    tien_cong: parseInt(document.getElementById('inputTienCong').value)||0,
    phi_linh_kien: parseInt(document.getElementById('inputPhiLinhKien').value)||0,
    ghi_chu_linh_kien: document.getElementById('inputGhiChuLinhKien').value,
    tien_thue_xe: parseInt(document.getElementById('inputTienThueXe').value)||0
  };
  try {
    const res = await callApi(`/don-dat-lich/${id}/update-costs`, 'PUT', body);
    if(res.ok) { showToast('Đã cập nhật chi phí!'); bootstrap.Modal.getInstance(document.getElementById('modalCosts')).hide(); loadMyBookings(); }
    else showToast(res.data.message||'Lỗi','error');
  } catch(e) { showToast('Lỗi kết nối','error'); }
});

loadMyBookings();
</script>
@endpush