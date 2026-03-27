<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Đăng nhập - Thợ Tốt NTU</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Roboto:ital,wght@0,100..900;1,100..900&family=Material+Symbols+Outlined" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css"/>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{min-height:100vh;display:flex;font-family:'Roboto',sans-serif;overflow:hidden;background:radial-gradient(circle at top left, rgba(255, 255, 255, 0.68) 0, rgba(255, 255, 255, 0) 24rem),radial-gradient(circle at top right, rgba(255, 255, 255, 0.58) 0, rgba(255, 255, 255, 0) 18rem),linear-gradient(180deg, #8ad0ff 0%, #c7e8ff 36%, #edf7ff 100%);}

    
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
    .logo-text { color:#fff; font-family:'DM Sans',sans-serif; font-weight:800; font-size:1rem; }

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
      font-family:'DM Sans',sans-serif; font-weight:800;
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
      font-family:'DM Sans',sans-serif; font-weight:800;
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
    .form-title{font-family:'DM Sans',sans-serif;font-weight:700;font-size:1.5rem;color:#0f172a;margin-bottom:.4rem;}
    .form-sub{color:#64748b;font-size:.85rem;margin-bottom:1.5rem;}
    .role-chip{display:inline-flex;align-items:center;gap:.4rem;background:#f0f9ff;border:1px solid #BAF2E9;color:#0369a1;border-radius:2rem;padding:.35rem .875rem;font-size:.78rem;font-weight:600;margin-bottom:1.75rem;}
    .input-group{margin-bottom:1.1rem;}
    .input-label{font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.4rem;}
    .input-wrap{position:relative;display:flex;align-items:center;}
    .input-icon{position:absolute;left:.875rem;color:#94a3b8;}
    .input-icon .material-symbols-outlined{font-size:1.1rem;}
    .input-wrap input{width:100%;border:1.5px solid #e2e8f0;border-radius:.75rem;padding:.75rem 1rem .75rem 2.75rem;font-size:.9rem;font-family:'Roboto',sans-serif;background:#f8fafc;transition:all .2s;outline:none;}
    .input-wrap input:focus{border-color:#0EA5E9;background:#fff;box-shadow:0 0 0 3px rgba(14,165,233,.1);}
    .eye-toggle{position:absolute;right:.875rem;cursor:pointer;color:#94a3b8;}
    .eye-toggle .material-symbols-outlined{font-size:1.1rem;}
    .forgot-link{display:block;text-align:right;font-size:.78rem;color:#0EA5E9;text-decoration:none;font-weight:600;margin-top:.35rem;}
    .btn-submit{width:100%;background:linear-gradient(135deg,#0EA5E9,#0284c7);color:#fff;border:none;border-radius:.875rem;padding:.9rem;font-family:'DM Sans',sans-serif;font-weight:700;font-size:.95rem;cursor:pointer;margin-top:1.25rem;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:.5rem;}
    .btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(14,165,233,.35);}
    .btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none;}
    .divider{display:flex;align-items:center;gap:.75rem;margin:1.25rem 0;color:#cbd5e1;font-size:.8rem;}
    .divider::before,.divider::after{content:'';flex:1;height:1px;background:#e2e8f0;}
    .btn-social{width:100%;display:flex;align-items:center;justify-content:center;gap:.75rem;border:1.5px solid #dbe7f3;border-radius:.875rem;padding:.85rem 1rem;background:#fff;color:#0f172a;font-family:'DM Sans',sans-serif;font-weight:700;font-size:.92rem;text-decoration:none;transition:all .2s;}
    .btn-social:hover{border-color:#0EA5E9;box-shadow:0 8px 24px rgba(14,165,233,.12);transform:translateY(-2px);}
    .btn-social.is-disabled{background:#f8fafc;border-color:#e2e8f0;color:#94a3b8;box-shadow:none;transform:none;}
    .btn-social.is-disabled:hover{border-color:#e2e8f0;box-shadow:none;transform:none;}
    .btn-social svg{width:1.1rem;height:1.1rem;flex-shrink:0;}
    .social-hint{margin-top:.7rem;text-align:center;font-size:.75rem;line-height:1.5;color:#64748b;}
    .social-config-note{margin-top:.65rem;padding:.7rem .85rem;border-radius:.85rem;background:#fff7ed;border:1px solid #fdba74;color:#9a3412;font-size:.75rem;line-height:1.5;}
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
      @php
        $googleAuthConfig = app(\App\Services\Auth\GoogleAuthConfigService::class);
        $googleAuthEnabled = $googleAuthConfig->isConfigured();
        $googleAuthMessage = $googleAuthConfig->setupMessage();
        $googleAuthMissingKeys = $googleAuthConfig->missingKeys();
        $googleAuthRedirectUri = $googleAuthConfig->redirectUri();
      @endphp
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

      <a
        href="{{ $googleAuthEnabled ? route('auth.google.redirect', ['role' => request('role', 'customer')]) : '#' }}"
        class="btn-social{{ $googleAuthEnabled ? '' : ' is-disabled' }}"
        id="googleLoginButton"
        aria-disabled="{{ $googleAuthEnabled ? 'false' : 'true' }}"
        data-google-enabled="{{ $googleAuthEnabled ? '1' : '0' }}"
        data-google-error="{{ $googleAuthMessage }}"
      >
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path fill="#EA4335" d="M12 10.2v3.9h5.4c-.24 1.26-.96 2.32-2.04 3.03l3.3 2.56c1.92-1.77 3.03-4.38 3.03-7.47 0-.71-.06-1.39-.18-2.02H12z"/>
          <path fill="#4285F4" d="M12 22c2.7 0 4.97-.9 6.63-2.44l-3.3-2.56c-.9.61-2.05.97-3.33.97-2.56 0-4.72-1.73-5.49-4.05H3.1v2.63A10 10 0 0 0 12 22z"/>
          <path fill="#FBBC05" d="M6.51 13.92A5.98 5.98 0 0 1 6.2 12c0-.67.12-1.31.31-1.92V7.45H3.1A10 10 0 0 0 2 12c0 1.61.39 3.13 1.1 4.55l3.41-2.63z"/>
          <path fill="#34A853" d="M12 6.03c1.47 0 2.79.51 3.84 1.5l2.88-2.88C16.96 3.02 14.7 2 12 2A10 10 0 0 0 3.1 7.45l3.41 2.63C7.28 7.76 9.44 6.03 12 6.03z"/>
        </svg>
        Đăng nhập với Google
      </a>
      <p class="social-hint">Lần đầu đăng nhập bằng Google, hệ thống sẽ tạo tài khoản theo vai trò bạn đã chọn.</p>
      @unless ($googleAuthEnabled)
        <p class="social-config-note">
          Đăng nhập Google đang tạm tắt. Cần cấu hình: {{ implode(', ', $googleAuthMissingKeys) }}.
          Callback URL hiện tại: {{ $googleAuthRedirectUri }}
        </p>
      @endunless

      <div class="divider">hoặc</div>
      <p class="register-link">Chưa có tài khoản? <a href="{{ route('register', ['role' => request('role')]) }}">Đăng ký ngay</a></p>
    </div>

    <a href="{{ route('select-role') }}" class="back-link">
      <span class="material-symbols-outlined" style="font-size:.9rem;">arrow_back</span>
      Quay lại chọn vai trò
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script type="module">
import { callApi, redirectAuthenticatedUser, showToast } from "{{ asset('assets/js/api.js') }}";
const baseUrl = '{{ url('/') }}';
const flashError = @json(session('auth_error'));
const googleLoginButton = document.getElementById('googleLoginButton');
const googleAuthEnabled = googleLoginButton.dataset.googleEnabled === '1';
const googleAuthError = googleLoginButton.dataset.googleError || '';

// Set role chip label from URL
const params = new URLSearchParams(window.location.search);
const role = params.get('role') || 'customer';
document.getElementById('roleLabel').textContent = role === 'worker' ? 'Thợ chuyên nghiệp' : 'Khách hàng';
document.getElementById('roleChip').style.background = role === 'worker' ? '#f0fdf4' : '#f0f9ff';
if (googleAuthEnabled) {
  googleLoginButton.href = `${baseUrl}/auth/google/redirect?role=${encodeURIComponent(role)}`;
} else {
  googleLoginButton.addEventListener('click', e => {
    e.preventDefault();
    showToast(googleAuthError || 'Đăng nhập Google chưa được cấu hình.', 'error');
  });
}

if (flashError) {
  showToast(flashError, 'error');
}

// Auth guard — redirect if already logged in
redirectAuthenticatedUser();

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
    const response = await callApi('/login', 'POST', { email, password, role });
    if (response.ok) {
      if (response.data.debug_otp) sessionStorage.setItem('debug_otp', response.data.debug_otp);
      showToast('Đã gửi mã OTP thành công!');
      setTimeout(() => { window.location.href = baseUrl + `/otp?email=${encodeURIComponent(email)}&role=${encodeURIComponent(role)}`; }, 900);
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
