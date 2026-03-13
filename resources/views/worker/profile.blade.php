@extends('layouts.app')
@section('title', 'Hồ Sơ Cá Nhân - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { corePlugins: { preflight: false } }</script>
<style>
  .worker-main { margin-left:240px; min-height:100vh; background:#f8fafc; }
  .worker-header { background:#fff; border-bottom:1px solid #e2e8f0; padding:.875rem 1.5rem; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; }
  .profile-section { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; padding:1.5rem; margin-bottom:1rem; }
  .profile-section h6 { font-family:'Poppins',sans-serif; font-weight:700; font-size:.9rem; color:#0f172a; margin-bottom:1.25rem; display:flex; align-items:center; gap:.5rem; }
  .form-control, .form-select { border-radius:.625rem; border-color:#e2e8f0; padding:.7rem 1rem; font-size:.875rem; }
  .form-control:focus, .form-select:focus { border-color:#0EA5E9; box-shadow:0 0 0 3px rgba(14,165,233,.1); outline:none; }
  .review-card { padding:.875rem 0; border-bottom:1px solid #f1f5f9; }
  .review-card:last-child { border-bottom:none; }
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
      <h5 style="font-family:'Poppins',sans-serif; font-weight:700; margin:0; font-size:1rem; color:#0f172a;">Hồ sơ cá nhân</h5>
      <p style="margin:0; font-size:.75rem; color:#64748b;">Quản lý thông tin và tăng uy tín của bạn</p>
    </div>
  </div>

  <div style="padding:1.5rem; display:grid; grid-template-columns:320px 1fr; gap:1.25rem; align-items:start;">

    <!-- Left: Profile Card -->
    <div>
      <!-- Avatar + Stats -->
      <div class="profile-section" style="text-align:center;">
        <!-- Avatar upload -->
        <div style="position:relative; width:100px; height:100px; margin:0 auto 1rem; cursor:pointer;" onclick="document.getElementById('uploadAvatar').click()">
          <img id="workerAvatarImg" src="/assets/images/user-default.png" alt="Avatar"
               style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid #BAF2E9;">
          <div style="position:absolute; bottom:0; right:0; background:#0EA5E9; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #fff;">
            <span class="material-symbols-outlined" style="font-size:.85rem; color:#fff;">photo_camera</span>
          </div>
          <input type="file" id="uploadAvatar" class="d-none" accept="image/jpeg,image/png,image/jpg,image/webp">
        </div>

        <h5 id="workerName" style="font-family:'Poppins',sans-serif; font-weight:700; color:#0f172a; margin-bottom:.25rem;">Đang tải...</h5>
        <span style="background:#BAF2E9; color:#0f172a; font-size:.72rem; font-weight:700; padding:.25rem .75rem; border-radius:2rem;">Điện lạnh · Điện tử</span>
        <p id="workerJoinDate" style="font-size:.75rem; color:#94a3b8; margin:.75rem 0 1.25rem;">Tham gia từ --/--/----</p>

        <!-- Stats row -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:1.25rem;">
          <div style="background:#f8fafc; border-radius:.75rem; padding:.875rem; text-align:center;">
            <div style="display:flex; align-items:center; justify-content:center; gap:.25rem; margin-bottom:.25rem;">
              <span class="material-symbols-outlined" style="font-size:1.1rem; color:#f59e0b; font-variation-settings:'FILL' 1;">star</span>
              <span id="statRating" style="font-family:'Poppins',sans-serif; font-size:1.3rem; font-weight:800; color:#0f172a;">0.0</span>
            </div>
            <p style="font-size:.72rem; color:#64748b; margin:0;"><span id="statReviewCount">0</span> đánh giá</p>
          </div>
          <div style="background:#f8fafc; border-radius:.75rem; padding:.875rem; text-align:center;">
            <div style="display:flex; align-items:center; justify-content:center; gap:.25rem; margin-bottom:.25rem;">
              <span class="material-symbols-outlined" style="font-size:1.1rem; color:#10b981;">task_alt</span>
              <span id="statCompleted" style="font-family:'Poppins',sans-serif; font-size:1.3rem; font-weight:800; color:#0f172a;">0</span>
            </div>
            <p style="font-size:.72rem; color:#64748b; margin:0;">Đã hoàn thành</p>
          </div>
        </div>

        <!-- Online toggle -->
        <div style="display:flex; align-items:center; justify-content:center; gap:.75rem; padding:.875rem; background:#f0fdf4; border-radius:.75rem; border:1px solid #bbf7d0;">
          <span class="material-symbols-outlined" style="font-size:1rem; color:#10b981;">circle</span>
          <span style="font-size:.8rem; font-weight:600; color:#065f46;">Đang hoạt động</span>
          <select id="inputTrangThai" style="border:none; background:transparent; font-size:.8rem; font-weight:600; color:#065f46; cursor:pointer; padding:0;">
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
          <div id="serviceCheckboxContainer" style="display:grid; grid-template-columns:repeat(2,1fr); gap:.5rem;">
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
          style="width:100%; background:#BAF2E9; color:#0f172a; border:none; border-radius:.75rem; padding:1rem; font-family:'Poppins',sans-serif; font-weight:700; font-size:.95rem; cursor:pointer; transition:all .2s; display:flex; align-items:center; justify-content:center; gap:.5rem;"
          onmouseover="this.style.background='#0EA5E9';this.style.color='#fff';"
          onmouseout="this.style.background='#BAF2E9';this.style.color='#0f172a';">
          <span class="material-symbols-outlined" style="font-size:1.1rem;">save</span> Lưu thay đổi
        </button>
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
