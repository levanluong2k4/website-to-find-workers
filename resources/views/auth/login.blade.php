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

    /* LEFT */
    .left-panel{width:40%;min-height:100vh;background:linear-gradient(145deg,#0369a1 0%,#0EA5E9 50%,#BAF2E9 100%);display:flex;flex-direction:column;padding:2rem 2.5rem;position:relative;overflow:hidden;}
    .left-panel::before{content:'';position:absolute;width:300px;height:300px;border-radius:50%;background:rgba(255,255,255,.07);top:-60px;right:-60px;}
    .left-panel::after{content:'';position:absolute;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.05);bottom:-40px;left:-40px;}
    .logo{display:flex;align-items:center;gap:.65rem;z-index:1;position:relative;}
    .logo-icon{width:2.5rem;height:2.5rem;background:rgba(255,255,255,.2);border-radius:.75rem;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);}
    .logo-text{color:#fff;font-family:'Poppins',sans-serif;font-weight:800;font-size:1.15rem;}
    .hero-content{flex:1;display:flex;flex-direction:column;justify-content:center;z-index:1;position:relative;}
    .hero-title{font-family:'Poppins',sans-serif;font-weight:800;font-size:2.1rem;color:#fff;line-height:1.2;margin-bottom:.875rem;}
    .hero-sub{color:rgba(255,255,255,.85);font-size:.9rem;line-height:1.6;margin-bottom:2rem;}
    .avatars-row{display:flex;align-items:center;gap:.5rem;margin-bottom:1.5rem;}
    .avatar-circle{width:36px;height:36px;border-radius:50%;border:2px solid #fff;margin-left:-10px;font-size:1.25rem;display:flex;align-items:center;justify-content:center;background:#fff;}
    .avatar-circle:first-child{margin-left:0;}
    .trust-text{color:#fff;font-size:.8rem;font-weight:600;}
    .feature-list{display:flex;flex-direction:column;gap:.75rem;}
    .feature-item{display:flex;align-items:center;gap:.75rem;color:rgba(255,255,255,.9);font-size:.85rem;}
    .feature-icon{width:1.75rem;height:1.75rem;background:rgba(255,255,255,.15);border-radius:.5rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .feature-icon .material-symbols-outlined{font-size:.95rem;color:#fff;}
    .illustration{text-align:center;font-size:4.5rem;margin-top:2rem;filter:drop-shadow(0 8px 20px rgba(0,0,0,.2));}

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
    <div class="logo-icon"><span class="material-symbols-outlined" style="color:#fff;font-size:1.3rem;">home_repair_service</span></div>
    <span class="logo-text">Thợ Tốt NTU</span>
  </div>

  <div class="hero-content">
    <h2 class="hero-title">Chào mừng<br>trở lại! 👋</h2>
    <p class="hero-sub">Đăng nhập để đặt lịch hoặc nhận việc ngay hôm nay – nhanh chóng và bảo mật.</p>

    <div class="avatars-row">
      <div class="avatar-circle">😊</div>
      <div class="avatar-circle">👩</div>
      <div class="avatar-circle">🧑</div>
      <span class="trust-text" style="margin-left:.5rem;">+1,200 khách hàng tin tưởng</span>
    </div>

    <div class="feature-list">
      <div class="feature-item">
        <div class="feature-icon"><span class="material-symbols-outlined">bolt</span></div>
        Đặt lịch trong 60 giây
      </div>
      <div class="feature-item">
        <div class="feature-icon"><span class="material-symbols-outlined">shield</span></div>
        Thanh toán được bảo vệ
      </div>
      <div class="feature-item">
        <div class="feature-icon"><span class="material-symbols-outlined">verified</span></div>
        Thợ được kiểm duyệt 100%
      </div>
    </div>

    <div class="illustration">🏠✨</div>
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