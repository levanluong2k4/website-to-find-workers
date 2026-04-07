@extends('layouts.app')
@section('title', 'Hồ Sơ Cá Nhân - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Roboto:ital,wght@0,100..900;1,100..900&family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { corePlugins: { preflight: false } }</script>
<style>
  .worker-main { margin-left:240px; min-height:100vh; background:#f8fafc; }
  .worker-header { background:#fff; border-bottom:1px solid #e2e8f0; padding:.875rem 1.5rem; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; }
  .profile-section { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; padding:1.5rem; margin-bottom:1rem; }
  .profile-section h6 { font-family:'DM Sans',sans-serif; font-weight:700; font-size:.9rem; color:#0f172a; margin-bottom:1.25rem; display:flex; align-items:center; gap:.5rem; }
  .form-control, .form-select { border-radius:.625rem; border-color:#e2e8f0; padding:.7rem 1rem; font-size:.875rem; }
  .form-control:focus, .form-select:focus { border-color:#0EA5E9; box-shadow:0 0 0 3px rgba(14,165,233,.1); outline:none; }
  .review-card { padding:.875rem 0; border-bottom:1px solid #f1f5f9; }
  .review-card:last-child { border-bottom:none; }
  .worker-service-tags { display:flex; flex-wrap:wrap; gap:.45rem; justify-content:center; margin:.75rem 0 0; }
  .worker-service-tag { background:#ecfeff; color:#0f766e; border:1px solid #99f6e4; border-radius:999px; padding:.3rem .75rem; font-size:.72rem; font-weight:700; }
  .worker-service-tag.is-muted { background:#f8fafc; color:#64748b; border-color:#e2e8f0; }
  .worker-service-summary { margin:.75rem 0 0; font-size:.78rem; color:#64748b; font-weight:600; text-align:center; }
  .worker-service-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:.75rem; }
  .worker-service-option { display:flex; align-items:flex-start; gap:.75rem; padding:.9rem 1rem; border:1px solid #e2e8f0; border-radius:.9rem; background:#f8fafc; cursor:pointer; transition:all .2s ease; }
  .worker-service-option:hover { border-color:#7dd3fc; background:#f0f9ff; transform:translateY(-1px); }
  .worker-service-option.is-selected { border-color:#0EA5E9; background:#ecfeff; box-shadow:0 0 0 3px rgba(14,165,233,.12); }
  .worker-service-option input { margin-top:.18rem; }
  .worker-service-option-title { font-size:.84rem; font-weight:700; color:#0f172a; line-height:1.35; }
  .worker-service-option-copy { font-size:.75rem; color:#64748b; margin-top:.15rem; line-height:1.45; }
  .worker-service-toolbar { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.85rem; flex-wrap:wrap; }
  .worker-service-counter { font-size:.8rem; color:#0369a1; font-weight:700; background:#e0f2fe; padding:.35rem .7rem; border-radius:999px; }
  .worker-service-hint { font-size:.75rem; color:#64748b; margin:0 0 .9rem; }
  .worker-service-manage-button { display:inline-flex; align-items:center; gap:.4rem; border:none; border-radius:999px; background:#0ea5e9; color:#fff; padding:.55rem .9rem; font-size:.8rem; font-weight:700; cursor:pointer; box-shadow:0 12px 24px rgba(14,165,233,.18); }
  .worker-service-manage-button:hover { background:#0284c7; }
  .worker-selected-services { display:flex; flex-wrap:wrap; gap:.55rem; min-height:2.5rem; }
  .worker-service-modal { position:fixed; inset:0; background:rgba(15,23,42,.56); display:flex; align-items:center; justify-content:center; padding:1.25rem; z-index:1200; }
  .worker-service-modal.d-none { display:none; }
  .worker-service-modal-card { width:min(840px, 100%); max-height:85vh; overflow:hidden; display:flex; flex-direction:column; background:#fff; border-radius:1.25rem; box-shadow:0 24px 60px rgba(15,23,42,.22); }
  .worker-service-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.25rem 1.25rem 1rem; border-bottom:1px solid #e2e8f0; }
  .worker-service-modal-body { padding:1.25rem; overflow:auto; }
  .worker-service-modal-foot { display:flex; justify-content:flex-end; gap:.75rem; padding:1rem 1.25rem 1.25rem; border-top:1px solid #e2e8f0; }
  .worker-service-modal-btn { border:none; border-radius:.85rem; padding:.75rem 1rem; font-size:.82rem; font-weight:700; cursor:pointer; }
  .worker-service-modal-btn.is-secondary { background:#e2e8f0; color:#0f172a; }
  .worker-service-modal-btn.is-primary { background:#0ea5e9; color:#fff; }
  .worker-avatar-upload { position:relative; width:112px; height:112px; margin:0 auto 1rem; cursor:pointer; }
  .worker-avatar-preview { width:100%; height:100%; display:block; border-radius:50%; object-fit:cover; background:#fff; border:3px solid #BAF2E9; box-shadow:0 12px 28px rgba(14,165,233,.16); }
  .worker-avatar-badge { position:absolute; bottom:0; right:0; background:#0EA5E9; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #fff; }
  .worker-availability { display:flex; align-items:center; justify-content:center; gap:.75rem; padding:.875rem; border-radius:.75rem; border:1px solid transparent; transition:background-color .2s ease, border-color .2s ease, color .2s ease; }
  .worker-availability--active { background:#f0fdf4; border-color:#bbf7d0; }
  .worker-availability--inactive { background:#fff7ed; border-color:#fed7aa; }
  .worker-availability__dot { font-size:1rem; }
  .worker-availability__label { font-size:.8rem; font-weight:700; }
  .worker-availability__select { border:none; background:transparent; font-size:.8rem; font-weight:700; cursor:pointer; padding:0; }
  .worker-availability--active .worker-availability__dot,
  .worker-availability--active .worker-availability__label,
  .worker-availability--active .worker-availability__select { color:#065f46; }
  .worker-availability--inactive .worker-availability__dot,
  .worker-availability--inactive .worker-availability__label,
  .worker-availability--inactive .worker-availability__select { color:#9a3412; }
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
      <h5 style="font-family:'DM Sans',sans-serif; font-weight:700; margin:0; font-size:1rem; color:#0f172a;">Hồ sơ cá nhân</h5>
      <p style="margin:0; font-size:.75rem; color:#64748b;">Quản lý thông tin và tăng uy tín của bạn</p>
    </div>
  </div>

  <div style="padding:1.5rem; display:grid; grid-template-columns:320px 1fr; gap:1.25rem; align-items:start;">

    <!-- Left: Profile Card -->
    <div>
      <!-- Avatar + Stats -->
      <div class="profile-section" style="text-align:center;">
        <!-- Avatar upload -->
        <div class="worker-avatar-upload" onclick="document.getElementById('uploadAvatar').click()">
          <img id="workerAvatarImg" src="/assets/images/user-default.png" alt="Avatar"
               class="worker-avatar-preview">
          <div class="worker-avatar-badge">
            <span class="material-symbols-outlined" style="font-size:.85rem; color:#fff;">photo_camera</span>
          </div>
          <input type="file" id="uploadAvatar" class="d-none" accept="image/jpeg,image/png,image/jpg,image/webp">
        </div>

        <h5 id="workerName" style="font-family:'DM Sans',sans-serif; font-weight:700; color:#0f172a; margin-bottom:.25rem;">Đang tải...</h5>
        <span style="background:#BAF2E9; color:#0f172a; font-size:.72rem; font-weight:700; padding:.25rem .75rem; border-radius:2rem;">Điện lạnh · Điện tử</span>
        <p id="workerJoinDate" style="font-size:.75rem; color:#94a3b8; margin:.75rem 0 1.25rem;">Tham gia từ --/--/----</p>

        <!-- Stats row -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:1.25rem;">
          <div style="background:#f8fafc; border-radius:.75rem; padding:.875rem; text-align:center;">
            <div style="display:flex; align-items:center; justify-content:center; gap:.25rem; margin-bottom:.25rem;">
              <span class="material-symbols-outlined" style="font-size:1.1rem; color:#f59e0b; font-variation-settings:'FILL' 1;">star</span>
              <span id="statRating" style="font-family:'DM Sans',sans-serif; font-size:1.3rem; font-weight:800; color:#0f172a;">0.0</span>
            </div>
            <p style="font-size:.72rem; color:#64748b; margin:0;"><span id="statReviewCount">0</span> đánh giá</p>
          </div>
          <div style="background:#f8fafc; border-radius:.75rem; padding:.875rem; text-align:center;">
            <div style="display:flex; align-items:center; justify-content:center; gap:.25rem; margin-bottom:.25rem;">
              <span class="material-symbols-outlined" style="font-size:1.1rem; color:#10b981;">task_alt</span>
              <span id="statCompleted" style="font-family:'DM Sans',sans-serif; font-size:1.3rem; font-weight:800; color:#0f172a;">0</span>
            </div>
            <p style="font-size:.72rem; color:#64748b; margin:0;">Đã hoàn thành</p>
          </div>
        </div>

        <!-- Online toggle -->
        <div id="workerAvailabilityCard" class="worker-availability worker-availability--active">
          <span id="workerAvailabilityDot" class="material-symbols-outlined worker-availability__dot">circle</span>
          <span id="workerAvailabilityLabel" class="worker-availability__label">Sẵn sàng nhận việc</span>
          <select id="inputTrangThai" class="worker-availability__select" aria-label="Trạng thái làm việc">
            <option value="1">✓ Sẵn sàng</option>
            <option value="0">Tạm nghỉ</option>
          </select>
        </div>
      </div>

      <!-- Customer Reviews -->
      <div class="profile-section">
        <h6><span class="material-symbols-outlined" style="color:#f59e0b;">reviews</span> Đánh giá từ khách</h6>
        <div id="reviewsList">
          <div style="text-align:center; padding:1.5rem 0; color:#94a3b8; font-size:.8rem;">Đang tải đánh giá...</div>
        </div>
        <a href="/worker/reviews" style="display:block; text-align:center; font-size:.78rem; color:#0EA5E9; font-weight:600; text-decoration:none; margin-top:.75rem;">Xem tất cả đánh giá →</a>
      </div>
    </div>

    <!-- Right: Edit Form -->
    <div>
      <form id="formWorkerProfile">
        <!-- Personal Info -->
        <div class="profile-section">
          <h6><span class="material-symbols-outlined" style="color:#0EA5E9;">manage_accounts</span> Thông tin cá nhân</h6>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
            <div>
              <label style="font-size:.78rem; font-weight:600; color:#64748b; display:block; margin-bottom:.4rem;">Họ và tên</label>
              <input type="text" class="form-control" id="inputHoTen" placeholder="Nguyễn Văn A">
            </div>
            <div>
              <label style="font-size:.78rem; font-weight:600; color:#64748b; display:block; margin-bottom:.4rem;">Số điện thoại</label>
              <input type="tel" class="form-control" id="inputPhone" placeholder="0987 654 321" readonly style="background:#f8fafc;">
            </div>
          </div>
          <div style="margin-bottom:1rem;">
            <label style="font-size:.78rem; font-weight:600; color:#64748b; display:block; margin-bottom:.4rem;">Email</label>
            <input type="email" class="form-control" id="inputEmail" readonly style="background:#f8fafc;">
          </div>
          <div>
            <label style="font-size:.78rem; font-weight:600; color:#64748b; display:block; margin-bottom:.4rem;">Địa chỉ</label>
            <input type="text" class="form-control" id="inputAddress" placeholder="123 Trần Phú, Nha Trang">
          </div>
        </div>

        <!-- Experience -->
        <div class="profile-section">
          <h6><span class="material-symbols-outlined" style="color:#0EA5E9;">workspace_premium</span> Kinh nghiệm & Chuyên môn</h6>
          <div style="margin-bottom:1rem;">
            <label style="font-size:.78rem; font-weight:600; color:#64748b; display:block; margin-bottom:.4rem;">Mô tả kinh nghiệm</label>
            <textarea class="form-control" id="inputKinhNghiem" rows="4" placeholder="Mô tả kỹ năng, số năm kinh nghiệm sửa chữa của bạn..."></textarea>
          </div>

          <!-- Services -->
          <label style="font-size:.78rem; font-weight:600; color:#64748b; display:block; margin-bottom:.75rem;">Dịch vụ cung cấp</label>
          <div class="worker-service-toolbar">
            <span id="serviceSelectionCount" class="worker-service-counter">0 dich vu da chon</span>
            <button type="button" id="serviceModalTrigger" class="worker-service-manage-button">
              <span class="material-symbols-outlined" style="font-size:1rem;">add_circle</span>
              Them dich vu
            </button>
          </div>
          <p class="worker-service-hint">Chon cac huong chuyen mon ban nhan lam. Khach hang se thay cac dich vu nay tren ho so cua ban.</p>
          <div id="serviceCheckboxContainer" class="worker-service-grid">
            <div style="font-size:.8rem; color:#94a3b8;">Đang tải dịch vụ...</div>
          </div>
        </div>

        <!-- Certificates -->
        <div class="profile-section">
          <h6><span class="material-symbols-outlined" style="color:#0EA5E9;">verified</span> Chứng chỉ / Bằng cấp</h6>
          <div>
            <label style="font-size:.78rem; font-weight:600; color:#64748b; display:block; margin-bottom:.4rem;">Link chứng chỉ (Google Drive)</label>
            <input type="url" class="form-control" id="inputChungChi" placeholder="https://drive.google.com/...">
            <p style="font-size:.72rem; color:#94a3b8; margin:.5rem 0 0;">Dán link Google Drive chứa file hình ảnh hoặc PDF (mở quyền xem công khai)</p>
          </div>
        </div>

        <!-- Save button -->
        <button type="submit" id="btnUpdateProfile"
          style="width:100%; background:#BAF2E9; color:#0f172a; border:none; border-radius:.75rem; padding:1rem; font-family:'DM Sans',sans-serif; font-weight:700; font-size:.95rem; cursor:pointer; transition:all .2s; display:flex; align-items:center; justify-content:center; gap:.5rem;"
          onmouseover="this.style.background='#0EA5E9';this.style.color='#fff';"
          onmouseout="this.style.background='#BAF2E9';this.style.color='#0f172a';">
          <span class="material-symbols-outlined" style="font-size:1.1rem;">save</span> Lưu thay đổi
        </button>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script type="module">
import { callApi, getCurrentUser, showToast } from "{{ asset('assets/js/api.js') }}";
const baseUrl = '{{ url('/') }}';
const user = getCurrentUser();
if (!user || !['worker', 'admin'].includes(user.role)) { window.location.href = baseUrl + '/login?role=worker'; }
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
</script>
<script type="module" src="{{ asset('assets/js/worker/profile.js') }}?v={{ time() }}"></script>
@endpush
