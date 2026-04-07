<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Xac minh so dien thoai - Tho Tot NTU</title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Material+Symbols+Outlined&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css"/>
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    :root{--app-font-sans:'Be Vietnam Pro',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;}
    body, body *:not(.material-symbols-outlined):not(.material-symbols-rounded):not(.material-symbols-sharp):not(.fa):not(.fas):not(.far):not(.fab):not([class^="fa-"]):not([class*=" fa-"]):not(pre):not(code):not(kbd):not(samp){font-family:var(--app-font-sans)!important}
    body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.25rem;background:radial-gradient(circle at top left, rgba(255,255,255,.7) 0, rgba(255,255,255,0) 24rem),radial-gradient(circle at bottom right, rgba(186,242,233,.5) 0, rgba(186,242,233,0) 22rem),linear-gradient(180deg,#8ad0ff 0%,#dff4ff 100%);font-family:'Roboto',sans-serif;color:#0f172a}
    .shell{width:min(980px,100%);display:grid;grid-template-columns:minmax(280px,380px) minmax(320px,1fr);background:#fff;border-radius:1.5rem;overflow:hidden;box-shadow:0 24px 64px rgba(14,165,233,.14)}
    .hero{padding:2rem;background:linear-gradient(160deg,#0f3c68 0%,#0284c7 55%,#38bdf8 100%);color:#fff;display:flex;flex-direction:column;gap:1rem}
    .brand{display:flex;align-items:center;gap:.6rem}
    .brand-icon{width:3rem;height:3rem;border-radius:999px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:0 12px 28px rgba(15,23,42,.22);flex-shrink:0}
    .brand-icon img{width:100%;height:100%;object-fit:contain;display:block}
    .brand-title{font-family:'DM Sans',sans-serif;font-weight:800;font-size:1.05rem}
    .hero-copy{margin-top:auto}
    .eyebrow{display:inline-flex;align-items:center;gap:.35rem;padding:.35rem .75rem;border-radius:999px;background:rgba(255,255,255,.14);font-size:.74rem;font-weight:700}
    .hero h1{font-family:'DM Sans',sans-serif;font-size:1.9rem;line-height:1.15;margin-top:.9rem}
    .hero p{color:rgba(255,255,255,.85);line-height:1.7;font-size:.92rem;margin-top:.85rem}
    .bullet-list{display:grid;gap:.75rem;margin-top:1.25rem}
    .bullet{display:flex;gap:.6rem;align-items:flex-start;font-size:.83rem;color:rgba(255,255,255,.88)}
    .bullet .material-symbols-outlined{font-size:1rem}
    .panel{padding:2rem 2.1rem}
    .panel h2{font-family:'DM Sans',sans-serif;font-size:1.5rem;margin-bottom:.4rem}
    .sub{font-size:.88rem;color:#64748b;line-height:1.6;margin-bottom:1.3rem}
    .status-box{display:flex;align-items:flex-start;gap:.7rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:1rem;padding:.9rem 1rem;margin-bottom:1rem}
    .status-box strong{display:block;font-size:.82rem;color:#1d4ed8;margin-bottom:.18rem}
    .status-box p{font-size:.8rem;color:#475569;line-height:1.6}
    .mode-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.85rem;margin-bottom:1rem}
    .mode-card{border:1.5px solid #dbeafe;border-radius:1rem;padding:1rem;background:#f8fbff;cursor:pointer;transition:.2s}
    .mode-card.is-active{border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,.12);background:#fff}
    .mode-card.is-muted{opacity:.72}
    .mode-card h3{font-family:'DM Sans',sans-serif;font-size:.98rem;margin-bottom:.35rem}
    .mode-card p{font-size:.78rem;line-height:1.55;color:#64748b}
    .pill{display:inline-flex;align-items:center;gap:.25rem;border-radius:999px;padding:.18rem .55rem;font-size:.7rem;font-weight:700;margin-bottom:.55rem}
    .pill-demo{background:#ecfeff;color:#0f766e}
    .pill-real{background:#fff7ed;color:#c2410c}
    .pill-off{background:#fef2f2;color:#b91c1c}
    .field{margin-bottom:.95rem}
    .label{display:block;font-size:.76rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#64748b;margin-bottom:.35rem}
    .input-wrap{position:relative}
    .input-wrap .material-symbols-outlined{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);font-size:1rem;color:#94a3b8}
    .input-wrap input{width:100%;padding:.85rem 1rem .85rem 2.7rem;border:1.5px solid #dbe4f0;border-radius:.9rem;background:#f8fafc;font-size:.92rem;outline:none;transition:.2s}
    .input-wrap input:focus{background:#fff;border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,.1)}
    .helper{font-size:.78rem;line-height:1.6;color:#64748b;margin-bottom:1rem}
    .helper code{font-family:'DM Sans',sans-serif;background:#eff6ff;padding:.08rem .35rem;border-radius:.35rem;color:#1d4ed8}
    .actions{display:flex;gap:.75rem}
    .btn{border:none;border-radius:.95rem;padding:.92rem 1rem;font-family:'DM Sans',sans-serif;font-weight:700;cursor:pointer;transition:.2s}
    .btn-primary{flex:1;background:linear-gradient(135deg,#0ea5e9,#0284c7);color:#fff}
    .btn-primary:hover{transform:translateY(-1px);box-shadow:0 12px 24px rgba(14,165,233,.22)}
    .btn-primary:disabled{opacity:.6;cursor:not-allowed;transform:none;box-shadow:none}
    .btn-secondary{background:#eff6ff;color:#0f172a}
    .otp-card{margin-top:1.25rem;padding:1rem;border:1px solid #e2e8f0;border-radius:1rem;background:#fcfdff}
    .otp-row{display:flex;gap:.55rem;justify-content:center;margin:.95rem 0 1rem}
    .otp-input{width:3rem;height:3.5rem;border:2px solid #dbe4f0;border-radius:.85rem;text-align:center;font-size:1.45rem;font-weight:800;font-family:'DM Sans',sans-serif;outline:none;background:#fff;transition:.2s}
    .otp-input:focus{border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,.1)}
    .otp-input.filled{background:#0ea5e9;color:#fff;border-color:#0ea5e9}
    .otp-meta{display:flex;justify-content:space-between;gap:.75rem;align-items:center;font-size:.78rem;color:#64748b}
    .otp-meta button{background:none;border:none;color:#0ea5e9;font-weight:700;cursor:pointer}
    .back{display:inline-flex;align-items:center;gap:.35rem;margin-top:1.1rem;color:#64748b;text-decoration:none;font-size:.82rem}
    @media (max-width:860px){.shell{grid-template-columns:1fr}.hero{display:none}.panel{padding:1.4rem}}
  </style>
</head>
<body>
  @php
    $phoneVerification = app(\App\Services\Auth\PhoneVerificationService::class);
    $phoneVerificationRequired = (bool) config('phone_verification.required', false);
    $phoneModes = $phoneVerification->availableModes();
    $demoNumbers = $phoneVerification->demoNumbers();
  @endphp

  <div class="shell">
    <section class="hero">
      <div class="brand">
        <div class="brand-icon"><img src="{{ asset('assets/images/logontu.png') }}" alt="Logo Thợ Tốt NTU"></div>
        <div class="brand-title">Tho Tot NTU</div>
      </div>
      <div class="hero-copy">
        <span class="eyebrow"><span class="material-symbols-outlined" style="font-size:.92rem">verified_user</span>Xac minh bo sung</span>
        <h1>Hoan tat xac minh so dien thoai truoc khi vao he thong</h1>
        <p>Ban co the chon so demo de trinh dien do an tren local, hoac chon so that khi he thong da duoc cau hinh SMS/Zalo.</p>
        <div class="bullet-list">
          <div class="bullet"><span class="material-symbols-outlined">check_circle</span><span>Dang ky va Google login deu dung chung mot man xac minh.</span></div>
          <div class="bullet"><span class="material-symbols-outlined">check_circle</span><span>Token dang nhap van duoc giu, nhung cac API chinh se bi chan cho den khi xac minh xong.</span></div>
          <div class="bullet"><span class="material-symbols-outlined">check_circle</span><span>So demo chi dung cho local/demo, khong duoc xem la xac minh so that.</span></div>
        </div>
      </div>
    </section>

    <section class="panel">
      <h2>Xac minh so dien thoai</h2>
      <p class="sub">Chon cach xac minh, nhap so dien thoai, sau do nhan va xac thuc ma OTP 6 so.</p>

      <div class="status-box">
        <span class="material-symbols-outlined" style="font-size:1.1rem;color:#2563eb">info</span>
        <div>
          <strong id="userLabel">Dang tai thong tin tai khoan...</strong>
          <p id="statusText">He thong dang kiem tra trang thai xac minh so dien thoai hien tai.</p>
        </div>
      </div>

      <div class="mode-grid" id="modeGrid">
        @foreach ($phoneModes as $mode)
          <button
            type="button"
            class="mode-card{{ $loop->first ? ' is-active' : '' }}{{ $mode['enabled'] ? '' : ' is-muted' }}"
            data-mode="{{ $mode['key'] }}"
            data-enabled="{{ $mode['enabled'] ? '1' : '0' }}"
          >
            <span class="pill {{ $mode['key'] === 'demo' ? 'pill-demo' : 'pill-real' }}">
              {{ strtoupper($mode['key']) }}
            </span>
            @unless ($mode['enabled'])
              <span class="pill pill-off">OFF</span>
            @endunless
            <h3>{{ strtoupper($mode['label']) }}</h3>
            <p>{{ $mode['description'] }}</p>
          </button>
        @endforeach
      </div>

      <div class="field">
        <label class="label" for="phoneInput">So dien thoai</label>
        <div class="input-wrap">
          <span class="material-symbols-outlined">smartphone</span>
          <input type="tel" id="phoneInput" placeholder="0900000001" autocomplete="tel"/>
        </div>
      </div>

      <p class="helper" id="modeHelper"></p>

      <div class="actions">
        <button class="btn btn-primary" id="sendCodeButton" type="button">Gui ma OTP</button>
        <button class="btn btn-secondary" id="refreshAccountButton" type="button">Tai lai</button>
      </div>

      <div class="otp-card">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem">
          <strong style="font-family:'DM Sans',sans-serif;font-size:1rem">Nhap ma OTP</strong>
          <span id="otpModeTag" class="pill pill-demo" style="margin:0">DEMO</span>
        </div>
        <div class="otp-row" id="otpContainer">
          <input class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" type="text">
          <input class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" type="text">
          <input class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" type="text">
          <input class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" type="text">
          <input class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" type="text">
          <input class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" type="text">
        </div>
        <div class="otp-meta">
          <span id="otpStatus">Chua gui ma OTP nao.</span>
          <button type="button" id="resendCodeButton">Gui lai ma</button>
        </div>
        <button class="btn btn-primary" id="verifyCodeButton" type="button" style="width:100%;margin-top:1rem">Xac minh so dien thoai</button>
      </div>

      <a href="{{ route('login') }}" class="back"><span class="material-symbols-outlined" style="font-size:.95rem">arrow_back</span>Quay lai dang nhap</a>
    </section>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
  <script type="module">
    import { callApi, getCurrentUser, saveUserSession, showToast } from "{{ asset('assets/js/api.js') }}";

    const baseUrl = '{{ url('/') }}';
    const phoneVerificationRequired = @json($phoneVerificationRequired);
    const phoneModes = @json($phoneModes);
    const demoNumbers = @json($demoNumbers);
    const modeGrid = document.getElementById('modeGrid');
    const phoneInput = document.getElementById('phoneInput');
    const modeHelper = document.getElementById('modeHelper');
    const sendCodeButton = document.getElementById('sendCodeButton');
    const resendCodeButton = document.getElementById('resendCodeButton');
    const refreshAccountButton = document.getElementById('refreshAccountButton');
    const verifyCodeButton = document.getElementById('verifyCodeButton');
    const otpInputs = Array.from(document.querySelectorAll('.otp-input'));
    const userLabel = document.getElementById('userLabel');
    const statusText = document.getElementById('statusText');
    const otpStatus = document.getElementById('otpStatus');
    const otpModeTag = document.getElementById('otpModeTag');
    let currentUser = getCurrentUser();
    let selectedMode = phoneModes[0]?.key || 'demo';
    let sentOtpMode = selectedMode;

    if (!localStorage.getItem('access_token')) {
      window.location.href = `${baseUrl}/login`;
    }

    function resolveDashboard(user) {
      if (user.role === 'admin') return `${baseUrl}/admin/dashboard`;
      if (user.role === 'worker') return `${baseUrl}/worker/dashboard`;
      return `${baseUrl}/customer/home`;
    }

    function getModeMeta(mode) {
      return phoneModes.find(item => item.key === mode) || phoneModes[0];
    }

    function renderModeState() {
      const meta = getModeMeta(selectedMode);
      otpModeTag.textContent = selectedMode.toUpperCase();
      otpModeTag.className = `pill ${selectedMode === 'demo' ? 'pill-demo' : 'pill-real'}`;

      const demoNote = demoNumbers.length
        ? `So demo hop le: ${demoNumbers.map(number => `<code>${number}</code>`).join(', ')}.`
        : 'He thong khong gioi han danh sach so demo trong local.';
      const realNote = meta.enabled
        ? 'Che do nay se gui ma OTP toi so dien thoai that qua cong SMS/Zalo da cau hinh.'
        : 'Che do nay da hien trong giao dien nhung chua duoc cau hinh cong SMS/Zalo tren he thong.';

      modeHelper.innerHTML = selectedMode === 'demo'
        ? `${demoNote} Ma demo tren local se duoc tra ve trong toast/debug response.`
        : realNote;

      sendCodeButton.disabled = !meta.enabled;
      resendCodeButton.disabled = !meta.enabled;
      sendCodeButton.textContent = meta.enabled ? 'Gui ma OTP' : 'Can cau hinh SMS/Zalo';

      Array.from(modeGrid.children).forEach(button => {
        button.classList.toggle('is-active', button.dataset.mode === selectedMode);
      });
    }

    async function loadCurrentUser() {
      const response = await callApi('/user', 'GET');
      if (!response.ok || !response.data?.id) {
        throw new Error('Khong the tai thong tin tai khoan hien tai.');
      }

      currentUser = response.data;
      localStorage.setItem('user', JSON.stringify(currentUser));

      if (!phoneVerificationRequired) {
        statusText.textContent = 'He thong dang bo qua buoc xac minh so dien thoai. Dang quay ve trang chinh.';
        showToast('Da tat bat buoc xac minh so dien thoai.');
        setTimeout(() => { window.location.href = resolveDashboard(currentUser); }, 400);
        return;
      }

      userLabel.textContent = `${currentUser.name} (${currentUser.email})`;
      if (currentUser.phone_verified_at) {
        statusText.textContent = `So dien thoai ${currentUser.phone || ''} da duoc xac minh. He thong se dua ban vao dung trang.`;
        showToast('Tai khoan nay da xac minh so dien thoai.');
        setTimeout(() => { window.location.href = resolveDashboard(currentUser); }, 500);
        return;
      }

      statusText.textContent = currentUser.phone
        ? `Tai khoan chua xac minh so dien thoai. So hien co: ${currentUser.phone}. Ban co the sua lai truoc khi nhan ma OTP.`
        : 'Tai khoan chua co so dien thoai da xac minh. Vui long chon mode va nhap so can xac minh.';
      if (currentUser.phone) {
        phoneInput.value = currentUser.phone;
      }
    }

    function resetOtpInputs() {
      otpInputs.forEach(input => {
        input.value = '';
        input.classList.remove('filled');
      });
      otpInputs[0]?.focus();
    }

    function currentOtpCode() {
      return otpInputs.map(input => input.value).join('');
    }

    otpInputs.forEach((input, index) => {
      input.addEventListener('input', () => {
        input.value = input.value.replace(/\D/g, '').slice(0, 1);
        input.classList.toggle('filled', input.value !== '');
        if (input.value && index < otpInputs.length - 1) {
          otpInputs[index + 1].focus();
        }
      });

      input.addEventListener('keydown', event => {
        if (event.key === 'Backspace' && !input.value && index > 0) {
          otpInputs[index - 1].focus();
        }
      });

      input.addEventListener('paste', event => {
        event.preventDefault();
        const pasted = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
        pasted.split('').forEach((character, pastedIndex) => {
          if (otpInputs[pastedIndex]) {
            otpInputs[pastedIndex].value = character;
            otpInputs[pastedIndex].classList.add('filled');
          }
        });
      });
    });

    modeGrid.addEventListener('click', event => {
      const button = event.target.closest('.mode-card');
      if (!button) return;
      selectedMode = button.dataset.mode;
      renderModeState();
    });

    async function requestPhoneCode() {
      const phone = phoneInput.value.trim();
      if (!phone) {
        showToast('Vui long nhap so dien thoai.', 'error');
        return;
      }

      sendCodeButton.disabled = true;
      sendCodeButton.textContent = 'Dang gui...';

      try {
        const response = await callApi('/phone-verification/request', 'POST', {
          phone,
          mode: selectedMode,
        });

        if (!response.ok) {
          const message = response.data?.errors?.phone?.[0]
            || response.data?.errors?.mode?.[0]
            || response.data?.message
            || 'Khong the gui ma OTP so dien thoai.';
          showToast(message, 'error');
          return;
        }

        sentOtpMode = response.data.mode;
        otpStatus.textContent = `Da gui ma OTP cho ${response.data.phone} theo mode ${response.data.mode.toUpperCase()}.`;
        resetOtpInputs();

        if (response.data.debug_otp) {
          sessionStorage.setItem('debug_phone_otp', response.data.debug_otp);
          showToast(`[Local Dev] Ma OTP so dien thoai: ${response.data.debug_otp}`);
          response.data.debug_otp.split('').forEach((character, index) => {
            if (otpInputs[index]) {
              otpInputs[index].value = character;
              otpInputs[index].classList.add('filled');
            }
          });
        } else {
          showToast(response.data.message || 'Da gui ma OTP.');
        }
      } finally {
        renderModeState();
      }
    }

    async function verifyPhoneCode() {
      const phone = phoneInput.value.trim();
      const code = currentOtpCode();
      if (!phone) {
        showToast('Vui long nhap so dien thoai.', 'error');
        return;
      }
      if (code.length !== 6) {
        showToast('Vui long nhap du 6 so OTP.', 'error');
        return;
      }

      verifyCodeButton.disabled = true;
      verifyCodeButton.textContent = 'Dang xac minh...';

      try {
        const response = await callApi('/phone-verification/verify', 'POST', {
          phone,
          mode: sentOtpMode || selectedMode,
          code,
        });

        if (!response.ok) {
          const message = response.data?.errors?.code?.[0]
            || response.data?.errors?.phone?.[0]
            || response.data?.message
            || 'Xac minh so dien thoai that bai.';
          showToast(message, 'error');
          resetOtpInputs();
          return;
        }

        saveUserSession(localStorage.getItem('access_token'), response.data.user);
        try {
          await callApi('/chat/sync-guest', 'POST', {});
        } catch (syncError) {
          console.warn('Guest chat sync failed:', syncError);
        }
        showToast('Xac minh so dien thoai thanh cong.');
        setTimeout(() => {
          window.location.href = response.data.redirect_to || resolveDashboard(response.data.user);
        }, 700);
      } finally {
        verifyCodeButton.disabled = false;
        verifyCodeButton.textContent = 'Xac minh so dien thoai';
      }
    }

    sendCodeButton.addEventListener('click', requestPhoneCode);
    resendCodeButton.addEventListener('click', requestPhoneCode);
    verifyCodeButton.addEventListener('click', verifyPhoneCode);
    refreshAccountButton.addEventListener('click', async () => {
      try {
        await loadCurrentUser();
      } catch (error) {
        showToast(error.message || 'Khong the tai lai thong tin tai khoan.', 'error');
      }
    });

    renderModeState();
    loadCurrentUser().catch(error => {
      console.error(error);
      showToast(error.message || 'Khong the tai thong tin tai khoan.', 'error');
    });
  </script>
</body>
</html>
