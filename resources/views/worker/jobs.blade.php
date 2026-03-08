@extends('layouts.app')
@section('title', 'Việc mới - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { corePlugins: { preflight: false } }</script>
<style>
  .worker-main { margin-left:240px; min-height:100vh; background:#f8fafc; }
  .worker-header { background:#fff; border-bottom:1px solid #e2e8f0; padding:.875rem 1.5rem; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; }
  
  /* Job card */
  .job-card { 
    background:#fff; 
    border:1px solid #e2e8f0; 
    border-radius:1.25rem; 
    padding:1.5rem; 
    margin-bottom:1rem; 
    display:flex; 
    flex-direction:column;
    gap:1rem; 
    transition:all .3s cubic-bezier(0.4, 0, 0.2, 1);
    position:relative;
    overflow:hidden;
  }
  .job-card:hover { 
    transform:translateY(-4px);
    box-shadow:0 12px 24px rgba(14,165,233,.1);
    border-color:#BAF2E9;
  }
  
  .job-badge {
    position:absolute;
    top:0;
    right:0;
    background:#0EA5E9;
    color:#fff;
    font-size:.7rem;
    font-weight:700;
    padding:.3rem 1rem;
    border-bottom-left-radius:1rem;
    text-transform:uppercase;
    letter-spacing:.05em;
  }

  .info-row { display:flex; align-items:center; gap:.5rem; color:#64748b; font-size:.85rem; }
  .info-row .material-symbols-outlined { font-size:1.1rem; color:#0EA5E9; }

  .price-tag {
    font-size:1.25rem;
    font-weight:800;
    color:#0f172a;
    font-family:'Poppins',sans-serif;
  }

  .btn-claim {
    background:linear-gradient(135deg, #0EA5E9 0%, #0284c7 100%);
    color:#fff;
    border:none;
    border-radius:.75rem;
    padding:.75rem 1.5rem;
    font-weight:700;
    font-size:.9rem;
    cursor:pointer;
    transition:all .2s;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:.5rem;
    width:100%;
  }
  .btn-claim:hover {
    transform:scale(1.02);
    box-shadow:0 8px 20px rgba(14,165,233,.3);
  }
  .btn-claim:disabled {
    opacity:.6;
    cursor:not-allowed;
    transform:none;
  }

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
      <h5 style="font-family:'Poppins',sans-serif; font-weight:700; margin:0; font-size:1rem; color:#0f172a;">Việc mới có sẵn</h5>
      <p style="margin:0; font-size:.75rem; color:#64748b;">Danh sách các yêu cầu sửa chữa đang chờ thợ nhận</p>
    </div>
    <div style="display:flex; gap:.75rem; align-items:center;">
      <button onclick="loadAvailableJobs()" style="display:flex; align-items:center; gap:.4rem; background:#f1f5f9; border:none; border-radius:.5rem; padding:.5rem .875rem; font-size:.8rem; font-weight:600; color:#334155; cursor:pointer;">
        <span class="material-symbols-outlined" style="font-size:.9rem;">refresh</span> Làm mới
      </button>
    </div>
  </div>

  <div style="padding:1.5rem;">
    <!-- AI Hint -->
    <div id="aiHint" style="display:none; background:#ecfeff; border:1px solid #cffafe; border-radius:1rem; padding:1rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:.75rem;">
      <div style="width:2.5rem; height:2.5rem; border-radius:50%; background:#fff; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(0,0,0,.05);">
        <span class="material-symbols-outlined" style="color:#0EA5E9;">psychology</span>
      </div>
      <p style="margin:0; font-size:.85rem; color:#164e63; font-weight:500;">
        Dựa trên kỹ năng của bạn, chúng tôi gợi ý các việc có nhãn <span style="color:#0EA5E9; font-weight:700;">"Phù hợp nhất"</span>.
      </p>
    </div>

    <!-- Jobs Grid -->
    <div id="jobsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Skeleton -->
      @foreach(range(1,6) as $i)
      <div class="bg-white rounded-[1.25rem] p-6 border border-slate-100 animate-pulse">
        <div class="h-4 bg-slate-100 rounded-full w-2/3 mb-4"></div>
        <div class="h-3 bg-slate-50 rounded-full w-full mb-2"></div>
        <div class="h-3 bg-slate-50 rounded-full w-1/2 mb-6"></div>
        <div class="h-10 bg-slate-100 rounded-xl w-full"></div>
      </div>
      @endforeach
    </div>

    <!-- Empty State -->
    <div id="emptyState" style="display:none; text-align:center; padding:5rem 2rem; background:#fff; border-radius:1.5rem; border:1px dashed #e2e8f0;">
      <div style="width:80px; height:80px; background:#f1f5f9; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem;">
        <span class="material-symbols-outlined" style="font-size:2.5rem; color:#94a3b8;">work_off</span>
      </div>
      <h6 style="font-family:'Poppins',sans-serif; font-weight:700; color:#0f172a; margin-bottom:.5rem;">Hiện chưa có việc mới</h6>
      <p style="color:#64748b; font-size:.875rem; max-width:300px; margin:0 auto;">Vui lòng quay lại sau hoặc làm mới trang để cập nhật danh sách.</p>
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

async function loadAvailableJobs() {
  const grid = document.getElementById('jobsGrid');
  const empty = document.getElementById('emptyState');
  const aiHint = document.getElementById('aiHint');

  try {
    const res = await callApi('/don-dat-lich/available', 'GET');
    if (res.ok) {
      const jobs = res.data.data || res.data || [];
      if (jobs.length === 0) {
        grid.style.display = 'none';
        empty.style.display = 'block';
        if(aiHint) aiHint.style.display = 'none';
        return;
      }

      grid.style.display = 'grid';
      empty.style.display = 'none';
      if(aiHint) aiHint.style.display = 'flex';

      grid.innerHTML = jobs.map((j, idx) => {
        const isHighlyRecommended = idx === 0; // Mock logic: first job is recommended
        return `
          <div class="job-card">
            ${isHighlyRecommended ? '<div class="job-badge">Phù hợp nhất</div>' : ''}
            <div>
              <h3 class="font-bold text-slate-900 text-lg mb-1 leading-tight">${(j.dich_vu ? j.dich_vu.ten_dich_vu : 'Dịch vụ')}</h3>
              <p class="text-slate-500 text-xs mb-4 line-clamp-2">${j.mo_ta_van_de || 'Mô tả lỗi đang chờ thợ kiểm tra chi tiết.'}</p>
            </div>
            
            <div class="space-y-2.5">
              <div class="info-row">
                <span class="material-symbols-outlined">pin_drop</span>
                <span class="truncate">${j.dia_chi || 'Địa chỉ ẩn (Sẽ hiện khi nhận)'}</span>
              </div>
              <div class="info-row">
                <span class="material-symbols-outlined">schedule</span>
                <span>Hẹn: ${j.ngay_hen ? new Date(j.ngay_hen).toLocaleDateString('vi-VN') : ''} · ${j.khung_gio_hen || ''}</span>
              </div>
              <div class="info-row">
                <span class="material-symbols-outlined">person</span>
                <span>${j.khach_hang ? j.khach_hang.name : 'Khách hàng'}</span>
              </div>
            </div>

            <div class="mt-2 pt-4 border-t border-slate-50 flex items-center justify-between gap-2">
              <div class="flex-grow">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none mb-1">Dự toán</p>
                <div class="price-tag">${j.estimated_price ? parseInt(j.estimated_price).toLocaleString('vi-VN') + ' ₫' : 'Liên hệ'}</div>
              </div>
              <div class="flex gap-2">
                <a href="${baseUrl}/worker/jobs/${j.id}" class="btn-claim !w-auto !px-4 py-2.5 bg-slate-100 !text-slate-600 hover:!bg-slate-200" style="text-decoration:none;">
                  Chi tiết
                </a>
                <button onclick="claimJob(${j.id})" class="btn-claim !w-auto !px-6 py-2.5">
                  Nhận việc
                </button>
              </div>
            </div>
          </div>
        `;
      }).join('');
    } else {
      showToast('Không thể tải danh sách việc', 'error');
    }
  } catch (e) {
    console.error(e);
    showToast('Lỗi kết nối máy chủ', 'error');
  }
}

window.claimJob = async function(id) {
  if(!confirm('Bạn có chắc chắn muốn nhận việc này không?')) return;
  
  const btn = event.target;
  const originalHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span style="width:18px;height:18px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:inline-block;"></span>';

  try {
    const res = await callApi(`/don-dat-lich/${id}/claim`, 'POST');
    if (res.ok) {
      showToast('Chúc mừng! Bạn đã nhận việc thành công.');
      setTimeout(() => {
        window.location.href = baseUrl + '/worker/my-bookings';
      }, 1000);
    } else {
      showToast(res.data.message || 'Không thể nhận việc này (Có thể đã có thợ khác nhận)', 'error');
      btn.disabled = false;
      btn.innerHTML = originalHtml;
      loadAvailableJobs();
    }
  } catch (e) {
    showToast('Lỗi kết nối máy chủ', 'error');
    btn.disabled = false;
    btn.innerHTML = originalHtml;
  }
};

document.addEventListener('DOMContentLoaded', loadAvailableJobs);
</script>
<style>@keyframes spin { to { transform: rotate(360deg) } }</style>
@endpush
