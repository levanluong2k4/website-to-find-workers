@extends('layouts.app')
@section('title', 'Chi tiết công việc - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { corePlugins: { preflight: false } }</script>
<style>
  .worker-main { margin-left:240px; min-height:100vh; background:#f8fafc; }
  .worker-header { background:#fff; border-bottom:1px solid #e2e8f0; padding:.875rem 1.5rem; display:flex; align-items:center; gap:1rem; position:sticky; top:0; z-index:100; }
  
  .detail-card { background:#fff; border:1px solid #e2e8f0; border-radius:1.5rem; padding:2rem; margin-bottom:1.5rem; }
  .section-title { font-family:'Poppins',sans-serif; font-weight:700; font-size:1.1rem; color:#0f172a; margin-bottom:1.25rem; display:flex; align-items:center; gap:.5rem; }
  .section-title .material-symbols-outlined { color:#0EA5E9; }

  .label-text { font-size:.75rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.25rem; }
  .value-text { font-weight:600; color:#1e293b; font-size:.95rem; }

  .status-badge { padding:.4rem 1rem; border-radius:2rem; font-size:.75rem; font-weight:700; display:inline-flex; align-items:center; gap:.4rem; }
  .status-cho_xac_nhan { background:#fef3c7; color:#92400e; }
  .status-da_xac_nhan { background:#e0f2fe; color:#0369a1; }

  .btn-action {
    border-radius:.75rem;
    padding:.875rem 1.5rem;
    font-weight:700;
    font-size:.95rem;
    cursor:pointer;
    transition:all .2s;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:.6rem;
    border:none;
  }
  .btn-primary-gradient {
    background:linear-gradient(135deg, #0EA5E9 0%, #0284c7 100%);
    color:#fff;
  }
  .btn-primary-gradient:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(14,165,233,.3); }

  @media (max-width: 768px) { .worker-main{margin-left:0;} }
</style>
@endpush

@section('content')
<div style="display:flex;">

<x-worker-sidebar />

<div class="worker-main" style="flex:1;">
  <div class="worker-header">
    <a href="/worker/jobs" class="p-2 hover:bg-slate-50 rounded-full transition-colors" style="text-decoration:none; color:#64748b;">
      <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <div>
      <h5 style="font-family:'Poppins',sans-serif; font-weight:700; margin:0; font-size:1rem; color:#0f172a;">Chi tiết yêu cầu #{{ $id }}</h5>
      <p style="margin:0; font-size:.75rem; color:#64748b;">Xem thông tin chi tiết trước khi nhận việc</p>
    </div>
  </div>

  <div id="jobDetails" style="padding:1.5rem; max-width:1000px; margin:0 auto; display:none;">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Left Column: Main Info -->
      <div class="lg:col-span-2">
        <div class="detail-card">
          <div class="section-title">
            <span class="material-symbols-outlined">receipt_long</span> Thông tin sửa chữa
          </div>
          
          <div class="grid grid-cols-2 gap-y-6 mb-8">
            <div>
              <p class="label-text">Thiết bị</p>
              <p class="value-text" id="applianceType">---</p>
            </div>
            <div>
              <p class="label-text">Trạng thái</p>
              <span id="jobStatus" class="status-badge status-cho_xac_nhan">
                <span class="material-symbols-outlined" style="font-size:1rem;">schedule</span> Đang chờ
              </span>
            </div>
            <div class="col-span-2">
              <p class="label-text">Mô tả lỗi</p>
              <p class="value-text leading-relaxed" id="problemDesc">---</p>
            </div>
          </div>

          <div class="section-title">
            <span class="material-symbols-outlined">pin_drop</span> Địa điểm & Thời gian
          </div>
          <div class="grid grid-cols-2 gap-y-6">
            <div class="col-span-2">
              <p class="label-text">Địa chỉ khách hàng</p>
              <p class="value-text" id="jobAddress">---</p>
            </div>
            <div>
              <p class="label-text">Ngày hẹn</p>
              <p class="value-text" id="jobDate">---</p>
            </div>
            <div>
              <p class="label-text">Khung giờ</p>
              <p class="value-text" id="jobTime">---</p>
            </div>
          </div>
        </div>

        <!-- Job Photos if any -->
        <div class="detail-card">
          <div class="section-title">
            <span class="material-symbols-outlined">image</span> Hình ảnh hiện trạng
          </div>
          <div id="jobPhotos" class="grid grid-cols-3 gap-4">
            <p class="text-slate-400 italic text-sm col-span-3">Không có hình ảnh đính kèm</p>
          </div>
        </div>
      </div>

      <!-- Right Column: Customer & Action -->
      <div class="flex flex-direction-column gap-6">
        <div class="detail-card" style="padding:1.5rem;">
          <div class="section-title" style="margin-bottom:1.5rem;">
            <span class="material-symbols-outlined">person</span> Khách hàng
          </div>
          <div class="flex items-center gap-3 mb-6">
            <div id="customerAvatar" style="width:3.5rem; height:3.5rem; border-radius:50%; background:#f1f5f9; display:flex; align-items:center; justify-content:center; font-weight:800; color:#0EA5E9; font-size:1.2rem;">?</div>
            <div>
              <p id="customerName" class="font-bold text-slate-900 mb-0">---</p>
              <p id="customerRole" class="text-xs text-slate-500 mb-0">Khách hàng hệ thống</p>
            </div>
          </div>
          <div class="space-y-4">
            <div>
              <p class="label-text">Số điện thoại</p>
              <p class="value-text text-slate-400">******** (Sẽ hiện khi nhận việc)</p>
            </div>
          </div>
        </div>

        <div class="detail-card" style="background:#f8fafc; border-style:dashed;">
          <p class="label-text">Dự toán thu nhập</p>
          <p class="price-tag text-teal-600 mb-6" id="estimatedPrice">--- ₫</p>
          
          <button id="btnClaim" onclick="claimJob({{ $id }})" class="btn-action btn-primary-gradient w-full">
            <span class="material-symbols-outlined">add_task</span> Nhận việc ngay
          </button>
          <p class="text-[10px] text-center text-slate-400 mt-3 italic">Bằng cách nhấn nhận việc, bạn cam kết sẽ đến đúng giờ hẹn.</p>
        </div>
      </div>

    </div>
  </div>

  <!-- Loading State -->
  <div id="loadingState" style="text-align:center; padding:5rem 2rem;">
    <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-slate-200 border-t-blue-500 mb-4"></div>
    <p class="text-slate-500 font-medium">Đang tải chi tiết công việc...</p>
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

const jobId = {{ $id }};

async function loadJobDetails() {
  try {
    const res = await callApi(`/don-dat-lich/${jobId}`, 'GET');
    if (res.ok) {
      const j = res.data;
      
      // Update UI elements
      document.getElementById('applianceType').textContent = j.dich_vu ? j.dich_vu.ten_dich_vu : (j.appliance_type || 'Thiết bị');
      document.getElementById('problemDesc').textContent = j.mo_ta_van_de || 'Không có mô tả chi tiết.';
      document.getElementById('jobAddress').textContent = j.dia_chi || 'Địa chỉ ẩn';
      document.getElementById('jobDate').textContent = j.ngay_hen ? new Date(j.ngay_hen).toLocaleDateString('vi-VN') : '';
      document.getElementById('jobTime').textContent = j.khung_gio_hen || '';
      document.getElementById('customerName').textContent = j.khach_hang ? j.khach_hang.name : 'Khách hàng';
      document.getElementById('estimatedPrice').textContent = j.estimated_price ? parseInt(j.estimated_price).toLocaleString('vi-VN') + ' ₫' : 'Liên hệ';
      
      const avatar = document.getElementById('customerAvatar');
      if (j.khach_hang && j.khach_hang.avatar) {
        avatar.innerHTML = `<img src="${j.khach_hang.avatar}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
      } else {
        avatar.textContent = (j.khach_hang ? j.khach_hang.name : 'K').charAt(0).toUpperCase();
      }

      // Photos
      const photosContainer = document.getElementById('jobPhotos');
      if (j.bai_dang && j.bai_dang.hinh_anhs && j.bai_dang.hinh_anhs.length > 0) {
        photosContainer.innerHTML = j.bai_dang.hinh_anhs.map(img => `
          <div class="rounded-xl overflow-hidden aspect-square bg-slate-100">
            <img src="${img.url}" class="w-full h-full object-cover">
          </div>
        `).join('');
      }

      // Hide loading, show content
      document.getElementById('loadingState').style.display = 'none';
      document.getElementById('jobDetails').style.display = 'block';

      // Disable button if already joined
      if (j.tho_id || j.trang_thai !== 'cho_xac_nhan') {
        const btn = document.getElementById('btnClaim');
        btn.disabled = true;
        btn.textContent = 'Việc đã có người nhận';
        btn.classList.remove('btn-primary-gradient');
        btn.classList.add('bg-slate-200', 'text-slate-500');
      }

    } else {
      showToast('Không tìm thấy thông tin công việc', 'error');
    }
  } catch (e) {
    console.error(e);
    showToast('Lỗi kết nối máy chủ', 'error');
  }
}

window.claimJob = async function(id) {
  if(!confirm('Bạn có chắc chắn muốn nhận việc này không?')) return;
  
  const btn = document.getElementById('btnClaim');
  const originalHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="animate-spin rounded-full h-5 w-5 border-2 border-white/40 border-t-white inline-block"></span> Đang xử lý...';

  try {
    const res = await callApi(`/don-dat-lich/${id}/claim`, 'POST');
    if (res.ok) {
      showToast('Nhận việc thành công!');
      setTimeout(() => { window.location.href = baseUrl + '/worker/my-bookings'; }, 1000);
    } else {
      showToast(res.data.message || 'Lỗi nhận việc', 'error');
      btn.disabled = false;
      btn.innerHTML = originalHtml;
    }
  } catch (e) {
    showToast('Lỗi kết nối', 'error');
    btn.disabled = false;
    btn.innerHTML = originalHtml;
  }
};

document.addEventListener('DOMContentLoaded', loadJobDetails);
</script>
@endpush
