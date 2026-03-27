@extends('layouts.app')
@section('title', 'Chi tiết công việc - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&family=Material+Symbols+Outlined" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('assets/css/worker/dispatch-ui.css') }}">
@endpush

@section('content')
<div class="dispatch-page">
  <div class="dispatch-shell">
    @include('worker.partials.dispatch-sidebar', [
      'context' => 'detail',
      'ctaLabel' => 'Quay lại việc mới',
      'ctaHref' => '/worker/jobs',
      'hotZoneTitle' => 'Điểm triển khai',
      'hotZoneCopy' => 'Địa điểm thực hiện sẽ được cập nhật theo thông tin của đơn hàng bạn đang xem.'
    ])

    <main class="dispatch-main">
      <header class="dispatch-topbar">
        <div class="dispatch-breadcrumb" style="gap:14px;">
          <a href="/worker/jobs" style="color:#7283a0; font-weight:500;">Nhận việc</a>
          <span style="color:#8da0bc;">›</span>
          <span style="color:#10203a; font-weight:700;">Chi tiết công việc</span>
        </div>

        <div class="dispatch-topbar-actions">
          <button type="button" class="dispatch-icon-btn" id="detailRefreshButton" onclick="loadJobDetails(event)" aria-label="Làm mới chi tiết">
            <span class="material-symbols-outlined">refresh</span>
          </button>
          <button type="button" class="dispatch-icon-btn" aria-label="Thông báo">
            <span class="material-symbols-outlined">notifications</span>
          </button>
          <div class="dispatch-avatar-btn" id="dispatchTopAvatar">TT</div>
        </div>
      </header>

      <div class="dispatch-content">
        <div id="loadingState" class="dispatch-loading-page">
          <span class="material-symbols-outlined">autorenew</span>
          <div class="dispatch-loading-copy">Đang tải chi tiết công việc...</div>
        </div>

        <div id="jobDetails" style="display:none;">
          <div class="dispatch-detail-grid">
            <div class="dispatch-detail-main">
              <section class="dispatch-detail-hero">
                <div>
                  <div class="dispatch-code-pill" id="jobCode">Mã việc: #JOB-00000</div>
                  <h1 class="dispatch-detail-title" id="detailTitle">Đang tải chi tiết yêu cầu</h1>
                  <div class="dispatch-status-row">
                    <span class="dispatch-status-pill status-pending" id="jobStatus">Chờ xác nhận</span>
                    <span id="jobPosted">Vừa đăng vài phút trước</span>
                  </div>
                </div>
              </section>

              <section class="dispatch-rich-card">
                <div class="dispatch-card-title">
                  <span class="material-symbols-outlined">description</span>
                  <span>Mô tả chi tiết</span>
                </div>
                <div class="dispatch-description" id="problemDesc">---</div>
                <div class="dispatch-chip-row" id="serviceChips"></div>
              </section>

              <div class="dispatch-info-duo">
                <section class="dispatch-info-card">
                  <div class="dispatch-info-head">
                    <div class="dispatch-detail-icon">
                      <span class="material-symbols-outlined">calendar_month</span>
                    </div>
                    <div>
                      <div class="dispatch-detail-label">Thời gian dự kiến</div>
                      <div class="dispatch-detail-value" id="jobDate">---</div>
                    </div>
                  </div>
                  <div class="dispatch-info-copy">
                    <span>Khung giờ triển khai</span>
                    <strong id="jobTime">---</strong>
                  </div>
                </section>

                <section class="dispatch-info-card">
                  <div class="dispatch-info-head">
                    <div class="dispatch-detail-icon">
                      <span class="material-symbols-outlined">place</span>
                    </div>
                    <div>
                      <div class="dispatch-detail-label">Địa điểm triển khai</div>
                      <div class="dispatch-detail-value" id="jobArea">---</div>
                    </div>
                  </div>
                  <div class="dispatch-info-copy">
                    <span>Địa chỉ chi tiết</span>
                    <strong style="font-size:1.18rem; line-height:1.4;" id="jobAddress">---</strong>
                  </div>
                </section>
              </div>

              <section>
                <div class="dispatch-gallery-title">Hình ảnh &amp; Video hiện trạng</div>
                <div class="dispatch-gallery-grid" id="jobPhotos">
                  <div class="dispatch-media-card"><span class="material-symbols-outlined">photo_camera</span></div>
                  <div class="dispatch-media-card"><span class="material-symbols-outlined">image</span></div>
                  <div class="dispatch-media-card"><span class="material-symbols-outlined">build_circle</span></div>
                  <div class="dispatch-media-card is-video"><span class="material-symbols-outlined" style="opacity:0;">play_arrow</span></div>
                </div>
              </section>
            </div>

            <aside class="dispatch-detail-side">
              <section class="dispatch-panel dispatch-earning-card">
                <div class="dispatch-panel-kicker">Dự toán thu nhập</div>
                <div class="dispatch-earning-price" id="estimatedPrice">---</div>
                <button type="button" id="btnClaim" onclick="claimJob({{ $id }}, event)" class="dispatch-primary-btn" style="width:100%; margin-top:22px;">
                  Nhận việc ngay
                </button>
                <div class="dispatch-muted-copy" id="qualityCopy">Đơn phù hợp để nhận nhanh nếu lịch trình của bạn còn trống trong ca này.</div>
              </section>

              <section class="dispatch-panel dispatch-customer-card">
                <div class="dispatch-panel-kicker" style="color:#6b7f9c;">Thông tin khách hàng</div>
                <div class="dispatch-customer-head">
                  <div class="dispatch-avatar" id="customerAvatar">KH</div>
                  <div>
                    <div class="dispatch-customer-name" id="customerName">---</div>
                    <div class="dispatch-customer-sub" id="customerMeta">Khách hệ thống</div>
                  </div>
                </div>

                <div class="dispatch-customer-row">
                  <span>Điện thoại</span>
                  <strong id="customerPhone">********</strong>
                </div>

                <div class="dispatch-customer-row">
                  <span>Loại khách</span>
                  <strong id="customerType">Khách hệ thống</strong>
                </div>

                <button type="button" class="dispatch-customer-chat" disabled>Nhắn tin trao đổi</button>
              </section>

              <section class="dispatch-panel dispatch-route-card">
                <div class="dispatch-route-map">
                  <div class="dispatch-route-pin">
                    <span class="material-symbols-outlined">location_on</span>
                  </div>
                </div>
                <div class="dispatch-route-body">
                  <div class="dispatch-muted-copy" id="routeDistance">Điểm hẹn nằm trong khu vực phục vụ của bạn.</div>
                  <a href="#" id="routeLink" target="_blank" rel="noopener noreferrer" class="dispatch-route-link">
                    Mở bản đồ dẫn đường
                    <span class="material-symbols-outlined">open_in_new</span>
                  </a>
                </div>
              </section>

              <section class="dispatch-panel dispatch-plain-card">
                <div style="display:flex; gap:14px; align-items:flex-start;">
                  <div class="dispatch-shield">
                    <span class="material-symbols-outlined">verified_user</span>
                  </div>
                  <div>
                    <div style="font-weight:800; color:#23324c;">Bảo hiểm công việc</div>
                    <div class="dispatch-muted-copy" style="margin-top:8px;">Các đơn qua hệ thống được ghi nhận và lưu vết thao tác để hỗ trợ xử lý khiếu nại hoặc phát sinh kỹ thuật.</div>
                  </div>
                </div>
              </section>
            </aside>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
@endsection

@push('scripts')
<script type="module">
import { callApi, getCurrentUser, showToast } from "{{ asset('assets/js/api.js') }}";

const baseUrl = '{{ url('/') }}';
const user = getCurrentUser();

if (!user || !['worker', 'admin'].includes(user.role)) {
  window.location.href = baseUrl + '/login?role=worker';
}

const jobId = {{ $id }};

const ui = {
  loadingState: document.getElementById('loadingState'),
  jobDetails: document.getElementById('jobDetails'),
  refreshButton: document.getElementById('detailRefreshButton'),
  jobCode: document.getElementById('jobCode'),
  detailTitle: document.getElementById('detailTitle'),
  jobStatus: document.getElementById('jobStatus'),
  jobPosted: document.getElementById('jobPosted'),
  problemDesc: document.getElementById('problemDesc'),
  serviceChips: document.getElementById('serviceChips'),
  jobDate: document.getElementById('jobDate'),
  jobTime: document.getElementById('jobTime'),
  jobArea: document.getElementById('jobArea'),
  jobAddress: document.getElementById('jobAddress'),
  jobPhotos: document.getElementById('jobPhotos'),
  estimatedPrice: document.getElementById('estimatedPrice'),
  btnClaim: document.getElementById('btnClaim'),
  qualityCopy: document.getElementById('qualityCopy'),
  customerAvatar: document.getElementById('customerAvatar'),
  customerName: document.getElementById('customerName'),
  customerMeta: document.getElementById('customerMeta'),
  customerPhone: document.getElementById('customerPhone'),
  customerType: document.getElementById('customerType'),
  routeDistance: document.getElementById('routeDistance'),
  routeLink: document.getElementById('routeLink'),
  sidebarHotZoneTitle: document.getElementById('dispatchHotZoneTitle'),
  sidebarHotZoneCopy: document.getElementById('dispatchHotZoneCopy'),
  sidebarName: document.getElementById('dispatchSidebarName'),
  sidebarRole: document.getElementById('dispatchSidebarRole'),
  sidebarAvatar: document.getElementById('dispatchSidebarAvatar'),
  topAvatar: document.getElementById('dispatchTopAvatar'),
};

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function getInitials(name) {
  const source = String(name || 'TT').trim();
  return source
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('') || 'TT';
}

function setAvatarContent(element, avatar, fallbackName) {
  if (!element) return;
  if (avatar) {
    element.innerHTML = `<img src="${escapeHtml(avatar)}" alt="${escapeHtml(fallbackName || 'Avatar')}">`;
    return;
  }

  element.textContent = getInitials(fallbackName);
}

function hydrateShellUser() {
  ui.sidebarName.textContent = user.name || 'Thợ kỹ thuật';
  ui.sidebarRole.textContent = user.role === 'admin' ? 'Quản trị vận hành' : 'Thợ kỹ thuật';
  setAvatarContent(ui.sidebarAvatar, user.avatar, user.name);
  setAvatarContent(ui.topAvatar, user.avatar, user.name);
}

function getServices(job) {
  if (Array.isArray(job.dich_vus) && job.dich_vus.length > 0) {
    return job.dich_vus.map((service) => service?.ten_dich_vu).filter(Boolean);
  }

  if (job.dich_vu?.ten_dich_vu) {
    return [job.dich_vu.ten_dich_vu];
  }

  return ['Yêu cầu sửa chữa'];
}

function getEstimate(job) {
  const direct = Number(job.estimated_price ?? job.tong_tien ?? 0);
  if (direct > 0) return direct;

  const fallback = ['phi_di_lai', 'phi_linh_kien', 'tien_cong', 'tien_thue_xe']
    .map((key) => Number(job[key] ?? 0))
    .reduce((sum, value) => sum + value, 0);

  return fallback > 0 ? fallback : 0;
}

function formatMoney(value) {
  if (!Number.isFinite(value) || value <= 0) return 'Liên hệ';
  return `${value.toLocaleString('vi-VN')}đ`;
}

function getArea(address) {
  if (!address) return 'Chưa có khu vực';
  const parts = address.split(',').map((part) => part.trim()).filter(Boolean);
  return parts.length >= 2 ? parts[parts.length - 2] : parts[0];
}

function formatFullDate(dateString) {
  if (!dateString) return 'Chưa có ngày hẹn';
  const target = new Date(dateString);
  return target.toLocaleDateString('vi-VN', {
    weekday: 'long',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
}

function formatRelativeTime(dateString) {
  if (!dateString) return 'Vừa đăng gần đây';
  const diffMinutes = Math.max(0, Math.round((Date.now() - new Date(dateString).getTime()) / 60000));

  if (diffMinutes < 1) return 'Vừa đăng';
  if (diffMinutes < 60) return `Vừa đăng ${diffMinutes} phút trước`;

  const diffHours = Math.round(diffMinutes / 60);
  if (diffHours < 24) return `Vừa đăng ${diffHours} giờ trước`;

  const diffDays = Math.round(diffHours / 24);
  return `Đăng ${diffDays} ngày trước`;
}

function maskPhone(phone) {
  const digits = String(phone || '').replace(/\D/g, '');
  if (digits.length < 7) return '********';
  return `${digits.slice(0, 4)}***${digits.slice(-3)}`;
}

function getStatusMeta(status) {
  const map = {
    cho_xac_nhan: { label: 'Chờ xác nhận', className: 'status-pending' },
    da_xac_nhan: { label: 'Đã xác nhận', className: 'status-confirmed' },
    dang_lam: { label: 'Đang làm', className: 'status-progress' },
    cho_hoan_thanh: { label: 'Chờ hoàn thành', className: 'status-progress' },
    cho_thanh_toan: { label: 'Chờ thanh toán', className: 'status-progress' },
    da_xong: { label: 'Hoàn thành', className: 'status-complete' },
    da_huy: { label: 'Đã hủy', className: 'status-cancelled' },
  };

  return map[status] || { label: 'Đang cập nhật', className: 'status-pending' };
}

function setRefreshLoading(isLoading) {
  ui.refreshButton.disabled = isLoading;
  ui.refreshButton.innerHTML = isLoading
    ? '<span class="material-symbols-outlined" style="animation:dispatchSpin 900ms linear infinite;">autorenew</span>'
    : '<span class="material-symbols-outlined">refresh</span>';
}

function renderMedia(job) {
  const media = [];

  if (job.video_mo_ta) {
    media.push(`
      <div class="dispatch-media-card is-video">
        <video src="${escapeHtml(job.video_mo_ta)}" controls></video>
      </div>
    `);
  }

  if (Array.isArray(job.hinh_anh_mo_ta) && job.hinh_anh_mo_ta.length > 0) {
    media.push(...job.hinh_anh_mo_ta.map((image) => `
      <div class="dispatch-media-card">
        <img src="${escapeHtml(image)}" alt="Ảnh hiện trạng" onclick="window.open('${escapeHtml(image)}', '_blank')">
      </div>
    `));
  }

  if (media.length === 0) {
    ui.jobPhotos.innerHTML = `
      <div class="dispatch-media-card"><span class="material-symbols-outlined">photo_camera</span></div>
      <div class="dispatch-media-card"><span class="material-symbols-outlined">image</span></div>
      <div class="dispatch-media-card"><span class="material-symbols-outlined">build_circle</span></div>
      <div class="dispatch-media-card is-video"><span class="material-symbols-outlined" style="opacity:0;">play_arrow</span></div>
    `;
    return;
  }

  ui.jobPhotos.innerHTML = media.join('');
}

function updateClaimState(job) {
  if (!job.tho_id && job.trang_thai === 'cho_xac_nhan') {
    ui.btnClaim.classList.remove('dispatch-secondary-btn');
    ui.btnClaim.classList.add('dispatch-primary-btn');
    ui.btnClaim.disabled = false;
    ui.btnClaim.innerHTML = 'Nhận việc ngay';
    return;
  }

  ui.btnClaim.disabled = true;
  ui.btnClaim.innerHTML = 'Việc đã có người nhận';
  ui.btnClaim.classList.remove('dispatch-primary-btn');
  ui.btnClaim.classList.add('dispatch-secondary-btn');
}

function renderJobDetails(job) {
  const services = getServices(job);
  const estimate = getEstimate(job);
  const status = getStatusMeta(job.trang_thai);
  const customerName = job.khach_hang?.name || 'Khách hàng';
  const address = job.dia_chi || 'Địa chỉ sẽ hiển thị khi công việc được chốt';
  const area = getArea(address);
  const mapQuery = encodeURIComponent(address);
  const codeYear = new Date(job.created_at || Date.now()).getFullYear();

  ui.jobCode.textContent = `Mã việc: #INV-${codeYear}-${String(job.id).padStart(3, '0')}`;
  ui.detailTitle.textContent = services.join(' - ');
  ui.jobStatus.className = `dispatch-status-pill ${status.className}`;
  ui.jobStatus.textContent = status.label;
  ui.jobPosted.textContent = formatRelativeTime(job.created_at);
  ui.problemDesc.textContent = job.mo_ta_van_de || 'Khách hàng cần thợ kiểm tra thêm hiện trạng và báo phương án xử lý phù hợp.';
  ui.serviceChips.innerHTML = services.map((service) => `<span class="dispatch-chip">${escapeHtml(service)}</span>`).join('');
  ui.jobDate.textContent = formatFullDate(job.ngay_hen);
  ui.jobTime.textContent = job.khung_gio_hen || 'Chưa có khung giờ';
  ui.jobArea.textContent = area;
  ui.jobAddress.textContent = address;
  ui.estimatedPrice.textContent = formatMoney(estimate);
  ui.qualityCopy.textContent = estimate >= 1000000
    ? 'Đơn giá trị cao. Nếu lịch trình của bạn đang trống, nên xác nhận sớm để khóa công việc này.'
    : 'Đơn phù hợp để nhận nhanh nếu lịch trình của bạn còn trống trong ca này.';

  setAvatarContent(ui.customerAvatar, job.khach_hang?.avatar, customerName);
  ui.customerName.textContent = customerName;
  ui.customerMeta.textContent = job.khach_hang?.phone ? 'Khách hàng đã xác minh' : 'Khách hệ thống';
  ui.customerPhone.textContent = maskPhone(job.khach_hang?.phone);
  ui.customerType.textContent = job.khach_hang?.phone ? 'Tài khoản xác minh' : 'Khách hệ thống';
  ui.routeDistance.textContent = `Điểm hẹn ở ${area}. Hệ thống khuyến nghị bạn kiểm tra tuyến di chuyển trước khi xác nhận.`;
  ui.routeLink.href = address ? `https://www.google.com/maps/search/?api=1&query=${mapQuery}` : '#';
  ui.sidebarHotZoneTitle.textContent = `Điểm triển khai: ${area}`;
  ui.sidebarHotZoneCopy.textContent = address;

  renderMedia(job);
  updateClaimState(job);
}

window.loadJobDetails = async function loadJobDetails(event) {
  if (event?.preventDefault) event.preventDefault();

  setRefreshLoading(true);

  try {
    const res = await callApi(`/don-dat-lich/${jobId}`, 'GET');

    if (!res.ok) {
      throw new Error(res.data?.message || 'Không tìm thấy thông tin công việc');
    }

    renderJobDetails(res.data);
    ui.loadingState.style.display = 'none';
    ui.jobDetails.style.display = 'block';
  } catch (error) {
    console.error(error);
    showToast(error.message || 'Lỗi kết nối máy chủ', 'error');
    ui.loadingState.innerHTML = `
      <span class="material-symbols-outlined" style="animation:none;">error</span>
      <div class="dispatch-loading-copy">Không tải được chi tiết công việc. Vui lòng thử làm mới lại.</div>
    `;
  } finally {
    setRefreshLoading(false);
  }
};

window.claimJob = async function claimJob(id, event) {
  if (!confirm('Bạn có chắc chắn muốn nhận việc này không?')) return;

  const button = event?.currentTarget || ui.btnClaim;
  const originalHtml = button.innerHTML;

  button.disabled = true;
  button.innerHTML = '<span class="material-symbols-outlined" style="animation:dispatchSpin 900ms linear infinite;">autorenew</span> Đang xử lý';

  try {
    const res = await callApi(`/don-dat-lich/${id}/claim`, 'POST');

    if (!res.ok) {
      throw new Error(res.data?.message || 'Không thể nhận việc này');
    }

    showToast('Nhận việc thành công!');
    setTimeout(() => {
      window.location.href = `${baseUrl}/worker/my-bookings`;
    }, 1000);
  } catch (error) {
    console.error(error);
    showToast(error.message || 'Lỗi kết nối máy chủ', 'error');
    button.disabled = false;
    button.innerHTML = originalHtml;
  }
};

document.addEventListener('DOMContentLoaded', () => {
  hydrateShellUser();
  window.loadJobDetails();
});
</script>
@endpush
