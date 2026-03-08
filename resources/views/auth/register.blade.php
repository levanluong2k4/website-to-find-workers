<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Đăng ký - Thợ Tốt NTU</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Material+Symbols+Outlined" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css"/>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { height: 100vh; overflow: hidden; font-family: 'Inter', sans-serif; display: flex; }

    /* ===== LEFT PANEL: gradient background with framed carousel ===== */
    .left-panel {
      position: relative;
      width: 48%;
      flex-shrink: 0;
      height: 100vh;
      overflow: hidden;
      background: linear-gradient(155deg, #0c2a4a 0%, #0369a1 45%, #0EA5E9 85%, #38bdf8 100%);
      display: flex;
      flex-direction: column;
      padding: 1.25rem 1.5rem 1.5rem;
    }
    .left-panel::before {
      content: '';
      position: absolute;
      width: 320px; height: 320px;
      border-radius: 50%;
      background: rgba(255,255,255,.05);
      top: -80px; right: -80px;
      pointer-events: none;
    }
    .left-panel::after {
      content: '';
      position: absolute;
      width: 200px; height: 200px;
      border-radius: 50%;
      background: rgba(255,255,255,.04);
      bottom: -60px; left: -60px;
      pointer-events: none;
    }

    /* Logo row */
    .left-logo {
      display: flex;
      align-items: center;
      gap: .6rem;
      position: relative;
      z-index: 2;
      margin-bottom: 1rem;
      flex-shrink: 0;
    }
    .left-logo-icon {
      width: 2.25rem; height: 2.25rem;
      background: rgba(255,255,255,.15);
      border-radius: .625rem;
      display: flex; align-items: center; justify-content: center;
      border: 1px solid rgba(255,255,255,.2);
    }
    .left-logo-text {
      color: #fff;
      font-family: 'Poppins', sans-serif;
      font-weight: 800;
      font-size: 1.05rem;
    }
    .left-logo-sub {
      display: block;
      color: rgba(255,255,255,.6);
      font-size: .68rem;
      margin-top: .05rem;
    }

    /* ===== FRAMED CAROUSEL BOX ===== */
    .carousel-frame {
      flex: 1;
      min-height: 0;
      position: relative;
      z-index: 2;
      /* Glowing blue border frame */
      border-radius: 1.125rem;
      padding: 3px; /* space for gradient border */
      background: linear-gradient(135deg, #38bdf8, #0EA5E9, #0284c7, #38bdf8);
      box-shadow:
        0 0 0 1px rgba(56,189,248,.3),
        0 8px 32px rgba(2,132,199,.4),
        0 0 60px rgba(14,165,233,.15);
    }
    .carousel-frame-inner {
      width: 100%;
      height: 100%;
      border-radius: calc(1.125rem - 3px);
      overflow: hidden;
      position: relative;
    }

    /* Carousel: explicit pixel-based height via JS; inline style set on load */
    #regCarousel,
    #regCarousel .carousel-inner,
    #regCarousel .carousel-item {
      height: 100%;
      width: 100%;
    }
    #regCarousel .carousel-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center top;
      display: block;
    }

    /* Dark overlay */
    .slide-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(
        to top,
        rgba(1,18,38,.82) 0%,
        rgba(1,18,38,.3) 45%,
        transparent 100%
      );
      pointer-events: none;
      z-index: 1;
    }

    /* Caption */
    .left-caption {
      position: absolute;
      bottom: 0; left: 0; right: 0;
      z-index: 10;
      padding: 1rem 1.125rem 1.25rem;
    }
    .caption-chip {
      display: inline-flex; align-items: center; gap: .3rem;
      background: rgba(14,165,233,.9);
      color: #fff;
      border-radius: 2rem;
      padding: .2rem .7rem;
      font-size: .68rem; font-weight: 700;
      margin-bottom: .4rem;
    }
    .caption-chip .material-symbols-outlined { font-size: .78rem; }
    .caption-title {
      font-family: 'Poppins', sans-serif;
      font-weight: 800; font-size: 1.15rem;
      color: #fff; line-height: 1.2; margin-bottom: .3rem;
      text-shadow: 0 2px 10px rgba(0,0,0,.5);
    }
    .caption-sub {
      font-size: .76rem;
      color: rgba(255,255,255,.85);
      line-height: 1.5;
      text-shadow: 0 1px 4px rgba(0,0,0,.4);
    }

    /* Carousel indicators */
    #regCarousel .carousel-indicators {
      bottom: .5rem;
      margin-bottom: 0;
      z-index: 20;
    }
    #regCarousel .carousel-indicators [data-bs-target] {
      width: 24px; height: 4px;
      border-radius: 2px;
      background: rgba(255,255,255,.4);
      border: none; opacity: 1;
      transition: all .3s;
    }
    #regCarousel .carousel-indicators .active {
      background: #fff;
      width: 36px;
    }
    #regCarousel .carousel-control-prev,
    #regCarousel .carousel-control-next { display: none; }

    /* ===== RIGHT PANEL ===== */
    .right-panel {
      flex: 1;
      height: 100vh;
      overflow-y: auto;
      background: #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }
    .form-wrap {
      padding-top: 100px;
      width: 100%;
      max-width: 500px;
      display: flex;
      flex-direction: column;
      gap: 0;
    }
    .form-card {
      background: #fff;
      border-radius: 1.125rem;
      box-shadow: 0 4px 24px rgba(14,165,233,.08), 0 1px 4px rgba(0,0,0,.06);
      padding: 1.875rem 2rem;
    }
    .form-title {
      font-family: 'Poppins', sans-serif;
      font-weight: 700;
      font-size: 1.35rem;
      color: #0f172a;
      margin-bottom: .2rem;
    }
    .form-sub {
      color: #64748b;
      font-size: .8rem;
      margin-bottom: 1.25rem;
    }

    /* Role Tabs */
    .role-tabs {
      display: flex;
      background: #f1f5f9;
      border-radius: .75rem;
      padding: .2rem;
      margin-bottom: 1.125rem;
      gap: .2rem;
    }
    .role-tab {
      flex: 1;
      text-align: center;
      padding: .5rem .75rem;
      border-radius: .55rem;
      font-size: .8rem;
      font-weight: 600;
      cursor: pointer;
      color: #64748b;
      transition: all .2s;
      border: none;
      background: transparent;
    }
    .role-tab.active {
      background: #0EA5E9;
      color: #fff;
      box-shadow: 0 2px 8px rgba(14,165,233,.3);
    }

    /* Form fields */
    .field-group { margin-bottom: .8rem; }
    .field-label {
      font-size: .68rem;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: .05em;
      display: block;
      margin-bottom: .3rem;
    }
    .field-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }
    .field-icon {
      position: absolute;
      left: .875rem;
      color: #94a3b8;
      display: flex;
    }
    .field-icon .material-symbols-outlined { font-size: 1rem; }
    .field-wrap input {
      width: 100%;
      border: 1.5px solid #e2e8f0;
      border-radius: .75rem;
      padding: .65rem 1rem .65rem 2.6rem;
      font-size: .85rem;
      font-family: 'Inter', sans-serif;
      background: #f8fafc;
      transition: all .2s;
      outline: none;
      color: #0f172a;
    }
    .field-wrap input:focus {
      border-color: #0EA5E9;
      background: #fff;
      box-shadow: 0 0 0 3px rgba(14,165,233,.1);
    }
    .field-wrap input::placeholder { color: #cbd5e1; }

    /* Terms */
    .terms {
      display: flex;
      align-items: flex-start;
      gap: .5rem;
      font-size: .73rem;
      color: #64748b;
      margin-top: .5rem;
      margin-bottom: .1rem;
    }
    .terms input { margin-top: 2px; accent-color: #0EA5E9; flex-shrink: 0; }
    .terms a { color: #0EA5E9; text-decoration: none; font-weight: 600; }

    /* Submit */
    .btn-submit {
      width: 100%;
      background: linear-gradient(135deg, #0EA5E9, #0284c7);
      color: #fff;
      border: none;
      border-radius: .875rem;
      padding: .8rem;
      font-family: 'Poppins', sans-serif;
      font-weight: 700;
      font-size: .9rem;
      cursor: pointer;
      margin-top: 1rem;
      transition: all .2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(14,165,233,.35); }
    .btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }

    .login-link { text-align: center; font-size: .8rem; color: #64748b; margin-top: 1rem; }
    .login-link a { color: #0EA5E9; font-weight: 700; text-decoration: none; }

    .back-link {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .3rem;
      color: #94a3b8;
      font-size: .73rem;
      text-decoration: none;
      margin-top: .875rem;
      transition: color .2s;
    }
    .back-link:hover { color: #64748b; }
    .back-link .material-symbols-outlined { font-size: .85rem; }
  </style>
</head>
<body>

<!-- ===== LEFT: Gradient Panel with Framed Carousel ===== -->
<div class="left-panel">

  <!-- Logo top -->
  <div class="left-logo">
    <div class="left-logo-icon">
      <span class="material-symbols-outlined" style="color:#fff;font-size:1.15rem;">home_repair_service</span>
    </div>
    <div>
      <span class="left-logo-text">Thợ Tốt NTU</span>
      <span class="left-logo-sub">2 Nguyễn Đình Chiểu, Vĩnh Thọ, Nha Trang</span>
    </div>
  </div>

  <!-- Fixed-size framed carousel -->
  <div class="carousel-frame">
    <div class="carousel-frame-inner">
      <div id="regCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4500">
        <div class="carousel-inner">
          <div class="carousel-item active">
            <img src="/assets/images/carousel/sile_tuyendung1.png" alt="Tuyển dụng thợ" loading="eager">
            <div class="slide-overlay"></div>
            <div class="left-caption">
              <span class="caption-chip"><span class="material-symbols-outlined">engineering</span>Tuyển dụng thợ chính hãng</span>
              <p class="caption-title">Gia nhập đội ngũ<br>Thợ Tốt NTU</p>
              <p class="caption-sub">Nhận việc ngay – thu nhập ổn định, bảo hiểm đầy đủ.</p>
            </div>
          </div>
          <div class="carousel-item">
            <img src="/assets/images/carousel/tuyendung2.png" alt="Môi trường làm việc" loading="lazy">
            <div class="slide-overlay"></div>
            <div class="left-caption">
              <span class="caption-chip"><span class="material-symbols-outlined">workspace_premium</span>Môi trường chuyên nghiệp</span>
              <p class="caption-title">Xưởng hiện đại,<br>trang thiết bị đồng bộ</p>
              <p class="caption-sub">Phòng sạch, dụng cụ chuẩn, hỗ trợ đào tạo từ A–Z.</p>
            </div>
          </div>
          <div class="carousel-item">
            <img src="/assets/images/carousel/tuyendung3.jpg" alt="Thợ sửa chữa tại nhà" loading="lazy">
            <div class="slide-overlay"></div>
            <div class="left-caption">
              <span class="caption-chip"><span class="material-symbols-outlined">home_repair_service</span>Sửa chữa tại nhà</span>
              <p class="caption-title">Thợ đến tận nơi –<br>chủ động thời gian</p>
              <p class="caption-sub">Tự chọn lịch, nhận việc gần bạn, thu nhập tăng theo hiệu quả.</p>
            </div>
          </div>
          <div class="carousel-item">
            <img src="/assets/images/carousel/Gemini_Generated_Image_7a95157a95157a95.png" alt="AI hỗ trợ" loading="lazy">
            <div class="slide-overlay"></div>
            <div class="left-caption">
              <span class="caption-chip"><span class="material-symbols-outlined">psychology</span>Công nghệ AI hỗ trợ</span>
              <p class="caption-title">AI gợi ý việc phù hợp<br>với kỹ năng bạn</p>
              <p class="caption-sub">Kết nối đúng thợ, đúng việc, đúng thời điểm.</p>
            </div>
          </div>
        </div>
        <div class="carousel-indicators">
          <button type="button" data-bs-target="#regCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
          <button type="button" data-bs-target="#regCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
          <button type="button" data-bs-target="#regCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
          <button type="button" data-bs-target="#regCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ===== RIGHT: Registration Form ===== -->
<div class="right-panel">
  <div class="form-wrap">
    <div class="form-card">
      <p class="form-title">Tạo tài khoản</p>
      <p class="form-sub">Tham gia cùng 1,200+ người dùng Thợ Tốt NTU</p>

      <div class="role-tabs">
        <button class="role-tab active" id="tabCustomer" onclick="switchRole('customer')">
          <img src="{{ asset('assets/images/customer.png') }}" alt="" style="width: 15px; height: auto">
        Khách hàng</button>
        <button class="role-tab" id="tabWorker" onclick="switchRole('worker')">
          <img src="{{ asset('assets/images/worker2.png') }}" alt="" style="width: 15px; height: auto">
        Thợ sửa chữa</button>
      </div>
      <input type="hidden" id="selectedRole" value="customer">

      <form id="registerForm">
        <div class="field-group">
          <label class="field-label">Họ và tên</label>
          <div class="field-wrap">
            <div class="field-icon"><span class="material-symbols-outlined">person</span></div>
            <input type="text" id="hoTen" placeholder="Nguyễn Văn A" required/>
          </div>
        </div>
        <div class="field-group">
          <label class="field-label">Số điện thoại</label>
          <div class="field-wrap">
            <div class="field-icon"><span class="material-symbols-outlined">phone_iphone</span></div>
            <input type="tel" id="soDienThoai" placeholder="090 123 4567" required/>
          </div>
        </div>
        <div class="field-group">
          <label class="field-label">Email</label>
          <div class="field-wrap">
            <div class="field-icon"><span class="material-symbols-outlined">mail</span></div>
            <input type="email" id="email" placeholder="example@gmail.com" required/>
          </div>
        </div>
        <div class="field-group">
          <label class="field-label">Mật khẩu</label>
          <div class="field-wrap">
            <div class="field-icon"><span class="material-symbols-outlined">lock</span></div>
            <input type="password" id="matKhau" placeholder="Tối thiểu 6 ký tự" required minlength="6"/>
          </div>
        </div>

        <label class="terms">
          <input type="checkbox" required/>
          Tôi đồng ý với <a href="#">Điều khoản dịch vụ</a> và <a href="#">Chính sách bảo mật</a>
        </label>

        <button type="submit" class="btn-submit" id="btnSubmit">
          Tạo tài khoản <span class="material-symbols-outlined" style="font-size:1rem;">arrow_forward</span>
        </button>
      </form>

      <p class="login-link">Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a></p>
    </div>

    <a href="{{ url('/') }}" class="back-link">
      <span class="material-symbols-outlined">arrow_back</span>
      Quay lại chọn vai trò
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script type="module">
import { callApi, showToast } from "{{ asset('assets/js/api.js') }}";
const baseUrl = '{{ url('/') }}';

// Role toggle
window.switchRole = function(role) {
  document.getElementById('selectedRole').value = role;
  document.getElementById('tabCustomer').classList.toggle('active', role === 'customer');
  document.getElementById('tabWorker').classList.toggle('active', role === 'worker');
};

// Pre-select from URL
const params = new URLSearchParams(window.location.search);
if (params.get('role') === 'worker') switchRole('worker');

document.getElementById('registerForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('btnSubmit');
  btn.disabled = true;
  btn.innerHTML = '<span style="width:16px;height:16px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:inline-block;"></span> Đang xử lý...';

  const body = {
    name: document.getElementById('hoTen').value,
    phone: document.getElementById('soDienThoai').value,
    email: document.getElementById('email').value,
    password: document.getElementById('matKhau').value,
    role: document.getElementById('selectedRole').value
  };

  try {
    const res = await callApi('/register', 'POST', body);
    if (res.ok) {
      if (res.data.debug_otp) sessionStorage.setItem('debug_otp', res.data.debug_otp);
      showToast('Đăng ký thành công! Kiểm tra mã OTP trong email của bạn.');
      setTimeout(() => { window.location.href = baseUrl + `/otp?email=${res.data.email}&is_new=1`; }, 1200);
    } else {
      let msg = res.data.message || 'Đăng ký thất bại.';
      if (res.data.errors?.email) msg = 'Email đã tồn tại!';
      else if (res.data.errors?.phone) msg = 'Số điện thoại đã được đăng ký!';
      showToast(msg, 'error');
      btn.disabled = false;
      btn.innerHTML = 'Tạo tài khoản <span class="material-symbols-outlined" style="font-size:1rem;">arrow_forward</span>';
    }
  } catch { showToast('Lỗi kết nối', 'error'); btn.disabled = false; btn.innerHTML = 'Tạo tài khoản'; }
});
</script>
<style>@keyframes spin { to { transform: rotate(360deg) } }</style>
</body>
</html>