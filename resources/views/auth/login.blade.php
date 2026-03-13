<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Đăng nhập - Thợ Tốt NTU</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Material+Symbols+Outlined" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css"/>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{min-height:100vh;display:flex;font-family:'Inter',sans-serif;overflow:hidden;}

    
    /* ── LEFT ── */
    .left-panel {
      width: 44%; height: 100vh;
      background: linear-gradient(145deg,#0EA5E9 0%,#0d9fdf 40%,#38bdf8 70%,#BAF2E9 100%);
      display: flex; flex-direction: column;
      padding: 1.25rem 1.75rem;
      position: relative; overflow: hidden;
    }
    .left-panel::before {
      content:''; position:absolute; width:300px; height:300px;
      border-radius:50%; background:rgba(255,255,255,.07); top:-70px; left:-70px;
    }
    .left-panel::after {
      content:''; position:absolute; width:200px; height:200px;
      border-radius:50%; background:rgba(255,255,255,.05); bottom:-50px; right:-50px;
    }

    .logo { display:flex; align-items:center; gap:.5rem; z-index:1; position:relative; flex-shrink:0; }
    .logo-icon {
      width:2.1rem; height:2.1rem; background:rgba(255,255,255,.2);
      border-radius:.6rem; display:flex; align-items:center; justify-content:center;
      backdrop-filter:blur(4px);
    }
    .logo-text { color:#fff; font-family:'Poppins',sans-serif; font-weight:800; font-size:1rem; }

    .hero-content {
      flex:1; display:flex; flex-direction:column; justify-content:flex-start;
      padding-top:.75rem; z-index:1; position:relative; min-height:0;
    }
    .hero-tag {
      display:inline-flex; align-items:center; gap:.3rem;
      background:rgba(255,255,255,.15); backdrop-filter:blur(4px);
      color:#fff; border-radius:2rem; padding:.25rem .7rem;
      font-size:.72rem; font-weight:600; width:fit-content; margin-bottom:.5rem;
    }
    .hero-title {
      font-family:'Poppins',sans-serif; font-weight:800;
      font-size:1.45rem; color:#fff; line-height:1.2; margin-bottom:.35rem;
    }
    .hero-sub {
      color:rgba(255,255,255,.85); font-size:.8rem;
      line-height:1.5; margin-bottom:.65rem;
    }

    /* ── CAROUSEL ── */
    #heroCarousel {
      flex:1; border-radius:1rem; overflow:hidden;
      box-shadow:0 12px 40px rgba(0,0,0,.3); min-height:0; position:relative;
    }
    #heroCarousel .carousel-inner,
    #heroCarousel .carousel-item { height:100%; }
    #heroCarousel .carousel-item img {
      width:100%; height:100%; object-fit:cover; object-position:center; display:block;
    }

    /* Dark gradient overlay on each slide */
    #heroCarousel .slide-overlay {
      position:absolute; inset:0;
      background: linear-gradient(
        to top,
        rgba(0,0,0,.72) 0%,
        rgba(0,0,0,.25) 45%,
        rgba(0,0,0,.05) 100%
      );
      pointer-events:none;
    }

    /* Caption text inside slide */
    #heroCarousel .carousel-caption {
      position:absolute; bottom:0; left:0; right:0;
      padding:.75rem 1rem 1.5rem;
      text-align:left; background:none;
    }
    #heroCarousel .caption-tag {
      display:inline-flex; align-items:center; gap:.3rem;
      background:rgba(14,165,233,.85); color:#fff;
      border-radius:2rem; padding:.2rem .6rem;
      font-size:.68rem; font-weight:700; margin-bottom:.35rem;
    }
    #heroCarousel .caption-title {
      font-family:'Poppins',sans-serif; font-weight:800;
      font-size:1.05rem; color:#fff; line-height:1.25;
      margin-bottom:.2rem; text-shadow:0 1px 6px rgba(0,0,0,.4);
    }
    #heroCarousel .caption-sub {
      font-size:.75rem; color:rgba(255,255,255,.85);
      line-height:1.4; text-shadow:0 1px 4px rgba(0,0,0,.3);
    }

    #heroCarousel .carousel-control-prev-icon,
    #heroCarousel .carousel-control-next-icon { filter:drop-shadow(0 0 4px rgba(0,0,0,.6)); }


    /* RIGHT */
    .right-panel{flex:1;background:linear-gradient(135deg,#f8fafc 0%,#f0f9ff 100%);display:flex;align-items:center;justify-content:center;padding:2rem;}
    .form-card{background:#fff;border-radius:1.5rem;box-shadow:0 20px 60px rgba(14,165,233,.1);padding:2.5rem;width:100%;max-width:420px;}
    .form-title{font-family:'Poppins',sans-serif;font-weight:700;font-size:1.5rem;color:#0f172a;margin-bottom:.4rem;}
    .form-sub{color:#64748b;font-size:.85rem;margin-bottom:1.5rem;}
    .role-chip{display:inline-flex;align-items:center;gap:.4rem;background:#f0f9ff;border:1px solid #BAF2E9;color:#0369a1;border-radius:2rem;padding:.35rem .875rem;font-size:.78rem;font-weight:600;margin-bottom:1.75rem;}
    .input-group{margin-bottom:1.1rem;}
    .input-label{font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.4rem;}
    .input-wrap{position:relative;display:flex;align-items:center;}
    .input-icon{position:absolute;left:.875rem;color:#94a3b8;}
    .input-icon .material-symbols-outlined{font-size:1.1rem;}
    .input-wrap input{width:100%;border:1.5px solid #e2e8f0;border-radius:.75rem;padding:.75rem 1rem .75rem 2.75rem;font-size:.9rem;font-family:'Inter',sans-serif;background:#f8fafc;transition:all .2s;outline:none;}
    .input-wrap input:focus{border-color:#0EA5E9;background:#fff;box-shadow:0 0 0 3px rgba(14,165,233,.1);}
    .eye-toggle{position:absolute;right:.875rem;cursor:pointer;color:#94a3b8;}
    .eye-toggle .material-symbols-outlined{font-size:1.1rem;}
    .forgot-link{display:block;text-align:right;font-size:.78rem;color:#0EA5E9;text-decoration:none;font-weight:600;margin-top:.35rem;}
    .btn-submit{width:100%;background:linear-gradient(135deg,#0EA5E9,#0284c7);color:#fff;border:none;border-radius:.875rem;padding:.9rem;font-family:'Poppins',sans-serif;font-weight:700;font-size:.95rem;cursor:pointer;margin-top:1.25rem;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:.5rem;}
    .btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(14,165,233,.35);}
    .btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none;}
    .divider{display:flex;align-items:center;gap:.75rem;margin:1.25rem 0;color:#cbd5e1;font-size:.8rem;}
    .divider::before,.divider::after{content:'';flex:1;height:1px;background:#e2e8f0;}
    .register-link{text-align:center;font-size:.85rem;color:#64748b;}
    .register-link a{color:#0EA5E9;font-weight:700;text-decoration:none;}
    .back-link{display:flex;align-items:center;justify-content:center;gap:.3rem;color:#94a3b8;font-size:.78rem;text-decoration:none;margin-top:1.25rem;}
    .back-link:hover{color:#64748b;}
  </style>
</head>
<body>

<!-- LEFT PANEL -->
<div class="left-panel">
  <div class="logo">
    <div class="logo-icon">
      <span class="material-symbols-outlined" style="color:#fff;font-size:1.2rem;">home_repair_service</span>
    </div>
    <span class="logo-text">Thợ Tốt NTU</span>
  </div>

  <div class="hero-content">
    <div class="hero-tag">
      <span class="material-symbols-outlined" style="font-size:.85rem;">location_on</span>
      Nha Trang, Khánh Hòa
    </div>
    <h1 class="hero-title">Nền tảng sửa chữa điện tử #1 Nha Trang</h1>
    <p class="hero-sub">Kết nối nhanh chóng với thợ chuyên nghiệp – sửa chữa uy tín, bảo hành rõ ràng.</p>

    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4500">
      <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
      </div>

      <div class="carousel-inner">

        {{-- Slide 1: Thợ --}}
        <div class="carousel-item active">
          <img src="/assets/images/carousel/tho.jpg" alt="Thợ chuyên nghiệp" loading="eager">
          <div class="slide-overlay"></div>
          <div class="carousel-caption">
            <span class="caption-tag">
              <span class="material-symbols-outlined" style="font-size:.75rem;">engineering</span>
              Đội ngũ thợ lành nghề
            </span>
            <p class="caption-title">Thợ chuyên nghiệp tại nhà bạn</p>
            <p class="caption-sub">Hơn 500 thợ được kiểm duyệt kỹ lưỡng, phục vụ tận nơi.</p>
          </div>
        </div>

        {{-- Slide 2: Tủ lạnh --}}
        <div class="carousel-item">
          <img src="/assets/images/carousel/tulanh.jpg" alt="Sửa tủ lạnh" loading="lazy">
          <div class="slide-overlay"></div>
          <div class="carousel-caption">
            <span class="caption-tag">
              <span class="material-symbols-outlined" style="font-size:.75rem;">kitchen</span>
              Điện lạnh
            </span>
            <p class="caption-title">Sửa tủ lạnh – nhanh, đúng nguyên nhân</p>
            <p class="caption-sub">Chẩn đoán chính xác, linh kiện chính hãng, bảo hành 6 tháng.</p>
          </div>
        </div>

        {{-- Slide 3: Máy giặt --}}
        <div class="carousel-item">
          <img src="/assets/images/carousel/suamaygiat.jpg" alt="Sửa máy giặt" loading="lazy">
          <div class="slide-overlay"></div>
          <div class="carousel-caption">
            <span class="caption-tag">
              <span class="material-symbols-outlined" style="font-size:.75rem;">local_laundry_service</span>
              Máy giặt
            </span>
            <p class="caption-title">Khắc phục máy giặt mọi hãng</p>
            <p class="caption-sub">Samsung, LG, Electrolux, Panasonic... đều có thợ chuyên.</p>
          </div>
        </div>

        {{-- Slide 4: Nồi chiên --}}
        <div class="carousel-item">
          <img src="/assets/images/carousel/noichien.jpg" alt="Sửa nồi chiên không khí" loading="lazy">
          <div class="slide-overlay"></div>
          <div class="carousel-caption">
            <span class="caption-tag">
              <span class="material-symbols-outlined" style="font-size:.75rem;">blender</span>
              Đồ gia dụng
            </span>
            <p class="caption-title">Sửa đồ gia dụng – đặt lịch 1 phút</p>
            <p class="caption-sub">Nồi chiên, lò vi sóng, máy xay... sửa tại nhà trong ngày.</p>
          </div>
        </div>

      </div>

      <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Trước</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Sau</span>
      </button>
    </div>
  </div>
</div>

<!-- RIGHT PANEL -->
<div class="right-panel">
  <div>
    <div class="form-card">
      <p class="form-title">Đăng nhập</p>
      <p class="form-sub">Nhập thông tin để tiếp tục sử dụng dịch vụ</p>

      <!-- Role chip - dynamic via JS -->
      <div class="role-chip" id="roleChip">
        <span class="material-symbols-outlined" style="font-size:.9rem;">person</span>
        <span id="roleLabel">Khách hàng</span>
      </div>

      <form id="loginForm">
        <div class="input-group">
          <label class="input-label">Email</label>
          <div class="input-wrap">
            <div class="input-icon"><span class="material-symbols-outlined">mail</span></div>
            <input type="email" id="email" placeholder="example@gmail.com" required/>
          </div>
        </div>

        <div class="input-group">
          <label class="input-label">Mật khẩu</label>
          <div class="input-wrap">
            <div class="input-icon"><span class="material-symbols-outlined">lock</span></div>
            <input type="password" id="matKhau" placeholder="Nhập mật khẩu..." required/>
            <div class="eye-toggle" onclick="togglePassword()">
              <span class="material-symbols-outlined" id="eyeIcon">visibility_off</span>
            </div>
          </div>
          <a href="#" class="forgot-link">Quên mật khẩu?</a>
        </div>

        <button type="submit" class="btn-submit" id="btnSubmit">
          Tiếp tục <span class="material-symbols-outlined" style="font-size:1rem;">arrow_forward</span>
        </button>
      </form>

      <div class="divider">hoặc</div>
      <p class="register-link">Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký ngay</a></p>
    </div>

    <a href="{{ url('/') }}" class="back-link">
      <span class="material-symbols-outlined" style="font-size:.9rem;">arrow_back</span>
      Quay lại chọn vai trò
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script type="module">
import { callApi, showToast } from "{{ asset('assets/js/api.js') }}";
const baseUrl = '{{ url('/') }}';

// Set role chip label from URL
const params = new URLSearchParams(window.location.search);
const role = params.get('role') || 'customer';
document.getElementById('roleLabel').textContent = role === 'worker' ? 'Thợ chuyên nghiệp' : 'Khách hàng';
document.getElementById('roleChip').style.background = role === 'worker' ? '#f0fdf4' : '#f0f9ff';

// Auth guard — redirect if already logged in
const token = localStorage.getItem('access_token');
const user = localStorage.getItem('user');
if (token && user) {
  const userData = JSON.parse(user);
  if (userData.role === 'admin') window.location.href = baseUrl + '/admin/dashboard';
  else if (userData.role === 'worker') window.location.href = baseUrl + '/worker/dashboard';
  else window.location.href = baseUrl + '/customer/home';
}

window.togglePassword = function() {
  const input = document.getElementById('matKhau');
  const icon = document.getElementById('eyeIcon');
  if (input.type === 'password') { input.type = 'text'; icon.textContent = 'visibility'; }
  else { input.type = 'password'; icon.textContent = 'visibility_off'; }
};

document.getElementById('loginForm').addEventListener('submit', async e => {
  e.preventDefault();
  const email = document.getElementById('email').value;
  const password = document.getElementById('matKhau').value;
  const btn = document.getElementById('btnSubmit');
  btn.disabled = true;
  btn.innerHTML = '<span style="width:18px;height:18px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:inline-block;"></span> Đang xử lý...';

  try {
    const response = await callApi('/login', 'POST', { email, password });
    if (response.ok) {
      if (response.data.debug_otp) sessionStorage.setItem('debug_otp', response.data.debug_otp);
      showToast('Đã gửi mã OTP thành công!');
      setTimeout(() => { window.location.href = baseUrl + `/otp?email=${email}`; }, 900);
    } else {
      showToast(response.data.message || 'Email hoặc mật khẩu không đúng!', 'error');
      btn.disabled = false;
      btn.innerHTML = 'Tiếp tục <span class="material-symbols-outlined" style="font-size:1rem;">arrow_forward</span>';
    }
  } catch (err) {
    console.error('Login error:', err);
    showToast(err.message || 'Lỗi kết nối máy chủ', 'error');
    btn.disabled = false;
    btn.innerHTML = 'Tiếp tục <span class="material-symbols-outlined" style="font-size:1rem;">arrow_forward</span>';
  }
});
</script>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
</body>
</html>