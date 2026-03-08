<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Xác minh OTP - Thợ Tốt NTU</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Material+Symbols+Outlined" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css"/>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{min-height:100vh;background:linear-gradient(145deg,#f0fffe 0%,#f8fafc 50%,#e0f2fe 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;font-family:'Inter',sans-serif;position:relative;overflow:hidden;padding:1rem;}
    /* Decorative blobs */
    .blob{position:absolute;border-radius:50%;background:linear-gradient(135deg,#BAF2E9,#0EA5E9);opacity:.15;filter:blur(40px);}
    .blob-1{width:320px;height:320px;top:-80px;left:-80px;}
    .blob-2{width:250px;height:250px;bottom:-60px;right:-60px;}

    /* Mini brand header */
    .brand{display:flex;align-items:center;gap:.5rem;margin-bottom:2rem;z-index:1;position:relative;}
    .brand-icon{width:2rem;height:2rem;background:linear-gradient(135deg,#0EA5E9,#0284c7);border-radius:.5rem;display:flex;align-items:center;justify-content:center;}
    .brand-text{font-family:'Poppins',sans-serif;font-weight:800;font-size:1rem;color:#0f172a;}

    /* OTP Card */
    .otp-card{background:#fff;border-radius:1.75rem;box-shadow:0 24px 64px rgba(14,165,233,.12),0 4px 16px rgba(0,0,0,.05);padding:2.75rem 2.25rem;width:100%;max-width:460px;text-align:center;z-index:1;position:relative;}

    /* Shield icon with glow */
    .shield-wrap{display:flex;align-items:center;justify-content:center;margin-bottom:1.5rem;}
    .shield-icon{width:80px;height:80px;border-radius:1.25rem;background:linear-gradient(135deg,#BAF2E9,#e0f2fe);display:flex;align-items:center;justify-content:center;animation:pulse 2s ease-in-out infinite;box-shadow:0 0 0 0 rgba(14,165,233,.4);}
    @keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(14,165,233,.3);}50%{box-shadow:0 0 0 16px rgba(14,165,233,.0);}}
    .shield-icon .material-symbols-outlined{font-size:2.5rem;color:#0EA5E9;}

    .otp-title{font-family:'Poppins',sans-serif;font-weight:700;font-size:1.5rem;color:#0f172a;margin-bottom:.5rem;}
    .otp-sub{font-size:.85rem;color:#64748b;line-height:1.6;margin-bottom:1.75rem;}
    .otp-email{font-weight:700;color:#0EA5E9;}

    /* OTP Inputs */
    .otp-inputs{display:flex;gap:.625rem;justify-content:center;margin-bottom:1.5rem;}
    .otp-input{width:58px;height:70px;border:2px solid #e2e8f0;border-radius:.875rem;font-family:'Poppins',sans-serif;font-size:1.75rem;font-weight:800;text-align:center;color:#0f172a;background:#f8fafc;transition:all .2s;outline:none;}
    .otp-input:focus{border-color:#0EA5E9;background:#f0f9ff;box-shadow:0 0 0 3px rgba(14,165,233,.15);}
    .otp-input.filled{border-color:#0EA5E9;background:#0EA5E9;color:#fff;}

    /* Timer */
    .timer-badge{display:inline-flex;align-items:center;gap:.35rem;background:#f0f9ff;border:1px solid #BAF2E9;color:#0369a1;border-radius:2rem;padding:.4rem 1rem;font-size:.8rem;font-weight:700;margin-bottom:1.5rem;}

    /* Submit button */
    .btn-submit{width:100%;background:linear-gradient(135deg,#0EA5E9,#0284c7);color:#fff;border:none;border-radius:1rem;padding:1rem;font-family:'Poppins',sans-serif;font-weight:700;font-size:1rem;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:.5rem;margin-bottom:1.25rem;}
    .btn-submit:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(14,165,233,.35);}
    .btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none;}

    .resend-link{font-size:.83rem;color:#64748b;margin-bottom:.75rem;}
    .resend-link a,.resend-link button{color:#0EA5E9;font-weight:700;text-decoration:none;background:none;border:none;cursor:pointer;font-size:.83rem;padding:0;}
    .resend-link a:hover,.resend-link button:hover{text-decoration:underline;}
    .back-link{display:flex;align-items:center;justify-content:center;gap:.3rem;color:#94a3b8;font-size:.78rem;text-decoration:none;cursor:pointer;background:none;border:none;}
    .back-link:hover{color:#64748b;}
  </style>
</head>
<body>
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>

  <!-- Mini brand -->
  <div class="brand">
    <div class="brand-icon"><span class="material-symbols-outlined" style="color:#fff;font-size:1rem;">home_repair_service</span></div>
    <span class="brand-text">Thợ Tốt NTU</span>
  </div>

  <!-- OTP Card -->
  <div class="otp-card" id="otpCard">
    <div class="shield-wrap">
      <div class="shield-icon">
        <span class="material-symbols-outlined">lock_open</span>
      </div>
    </div>

    <h1 class="otp-title">Xác thực OTP</h1>
    <p class="otp-sub">
      Mã bảo mật gồm 6 số đã được gửi tới<br>
      <span class="otp-email" id="displayEmail">email@example.com</span>
    </p>

    <!-- 6 OTP Inputs -->
    <form id="otpForm">
      <div class="otp-inputs" id="otpContainer">
        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" required autofocus>
        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" required>
        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" required>
        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" required>
        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" required>
        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" required>
      </div>

      <!-- Timer -->
      <div class="timer-badge">
        <span class="material-symbols-outlined" style="font-size:.95rem;">timer</span>
        Mã hết hạn sau <span id="timerDisplay">05:00</span>
      </div>

      <button type="submit" class="btn-submit" id="btnVerify">
        <span class="material-symbols-outlined" style="font-size:1.1rem;">verified</span>
        Xác nhận
      </button>
    </form>

    <p class="resend-link">
      Chưa nhận được mã? <button onclick="resendOtp()" id="btnResend">Gửi lại mã</button>
    </p>
    <button class="back-link" onclick="history.back()">
      <span class="material-symbols-outlined" style="font-size:.85rem;">arrow_back</span>
      Nhập lại số khác
    </button>
  </div>

<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script type="module">
import { callApi, saveUserSession, showToast } from "{{ asset('assets/js/api.js') }}";
const baseUrl = '{{ url('/') }}';

// Get email from URL
const params = new URLSearchParams(window.location.search);
const email = params.get('email') || '';
const isNew = params.get('is_new') === '1';
document.getElementById('displayEmail').textContent = email;

// Auto-fill debug OTP
const debugOtp = sessionStorage.getItem('debug_otp');
const inputs = document.querySelectorAll('.otp-input');
if (debugOtp) {
  const arr = debugOtp.split('');
  inputs.forEach((inp, idx) => { if (arr[idx]) { inp.value = arr[idx]; inp.classList.add('filled'); } });
  sessionStorage.removeItem('debug_otp');
  showToast(`[Local Dev] Mã OTP: ${debugOtp}`);
}

// OTP input auto-advance & styling
inputs.forEach((inp, idx) => {
  inp.addEventListener('input', e => {
    inp.classList.toggle('filled', inp.value !== '');
    if (inp.value && idx < inputs.length - 1) inputs[idx+1].focus();
  });
  inp.addEventListener('keydown', e => {
    if (e.key === 'Backspace' && !inp.value && idx > 0) {
      inputs[idx-1].focus();
      inputs[idx-1].classList.remove('filled');
    }
  });
  inp.addEventListener('paste', e => {
    e.preventDefault();
    const pasted = e.clipboardData.getData('text').replace(/\D/g,'').slice(0,6);
    pasted.split('').forEach((ch, i) => { if (inputs[i]) { inputs[i].value = ch; inputs[i].classList.add('filled'); } });
    if(inputs[Math.min(pasted.length, 5)]) inputs[Math.min(pasted.length, 5)].focus();
  });
});

// Countdown timer
let secs = 300;
const timerEl = document.getElementById('timerDisplay');
const interval = setInterval(() => {
  secs--;
  const m = String(Math.floor(secs/60)).padStart(2,'0');
  const s = String(secs%60).padStart(2,'0');
  timerEl.textContent = `${m}:${s}`;
  if(secs <= 0) { clearInterval(interval); timerEl.closest('.timer-badge').style.background='#fef2f2'; timerEl.textContent='Hết hạn'; }
}, 1000);

// Submit OTP
document.getElementById('otpForm').addEventListener('submit', async e => {
  e.preventDefault();
  let otpCode = '';
  inputs.forEach(i => otpCode += i.value);
  if (otpCode.length < 6) return showToast('Vui lòng nhập đủ 6 số OTP', 'error');

  const btn = document.getElementById('btnVerify');
  btn.disabled = true;
  btn.innerHTML = '<span style="width:18px;height:18px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:inline-block;"></span> Đang xác nhận...';

  try {
    const res = await callApi('/verify-otp', 'POST', { email, code: otpCode });
    if (res.ok) {
      const { access_token, user } = res.data;
      saveUserSession(access_token, user);
      showToast('Xác thực thành công!');
      setTimeout(() => {
        if (user.role === 'admin') window.location.href = baseUrl + '/admin/dashboard';
        else if (user.role === 'worker') window.location.href = baseUrl + '/worker/dashboard';
        else window.location.href = baseUrl + '/customer/home';
      }, 800);
    } else {
      showToast(res.data.message || 'Mã OTP không đúng hoặc đã hết hạn!', 'error');
      inputs.forEach(i => { i.value=''; i.classList.remove('filled'); });
      inputs[0].focus();
      btn.disabled = false;
      btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:1.1rem;">verified</span> Xác nhận';
    }
  } catch { showToast('Lỗi kết nối','error'); btn.disabled=false; btn.innerHTML='Xác nhận'; }
});

// Resend OTP
window.resendOtp = async function() {
  const btn = document.getElementById('btnResend');
  btn.disabled = true; btn.textContent = 'Đang gửi...';
  try {
    const res = await callApi('/resend-otp', 'POST', { email });
    if (res.ok) {
      if (res.data.debug_otp) {
        const arr = res.data.debug_otp.split('');
        inputs.forEach((inp,i) => { if(arr[i]){ inp.value=arr[i]; inp.classList.add('filled'); } });
        showToast(`[Local Dev] Mã OTP mới: ${res.data.debug_otp}`);
      } else showToast('Đã gửi lại mã OTP!');
      secs = 300;
    } else showToast(res.data.message||'Lỗi gửi lại mã','error');
  } catch { showToast('Lỗi kết nối','error'); }
  setTimeout(() => { btn.disabled=false; btn.textContent='Gửi lại mã'; }, 60000);
};
</script>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
</body>
</html>