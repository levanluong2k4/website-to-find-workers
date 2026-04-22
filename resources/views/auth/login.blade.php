@php
  $googleAuthConfig = app(\App\Services\Auth\GoogleAuthConfigService::class);
  $googleAuthEnabled = $googleAuthConfig->isConfigured();
  $googleAuthMessage = $googleAuthConfig->setupMessage();
  $googleAuthMissingKeys = $googleAuthConfig->missingKeys();
  $googleAuthRedirectUri = $googleAuthConfig->redirectUri();
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Đăng nhập - Thợ Tốt NTU</title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&family=Material+Symbols+Outlined&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" />
  <link rel="stylesheet" href="{{ asset('assets/css/auth/login.css') }}" />
</head>
<body>
  <main class="auth-page">
    <section class="auth-showcase">
      <a href="{{ route('home') }}" class="auth-brand">
        <span class="auth-brand__logo">
          <img src="{{ asset('assets/images/logontu.png') }}" alt="Logo Thợ Tốt NTU" />
        </span>
        <span class="auth-brand__copy">
          <small>HỆ THỐNG DỊCH VỤ</small>
          <strong>Thợ Tốt NTU</strong>
        </span>
      </a>

      <div class="showcase-frame">
        <div class="showcase-carousel" id="authCarousel"></div>
        <div class="showcase-dots" id="authCarouselDots" aria-label="Điều hướng carousel"></div>
      </div>

      <div class="showcase-summary">
        <span class="showcase-summary__eyebrow" id="showcaseEyebrow">Dịch vụ tận nơi</span>
        <h1 id="showcaseTitle">Sửa chữa nhanh, linh kiện rõ nguồn gốc và theo dõi minh bạch.</h1>
        <p id="showcaseDescription">Đăng nhập để quay lại đúng luồng đang cần, từ lịch hẹn mới đến các đơn đang xử lý và đánh giá sau sửa chữa.</p>
        <div class="showcase-metrics" id="showcaseMetrics"></div>
      </div>
    </section>

    <section class="auth-panel">
      <div class="auth-card">
        <p class="auth-kicker">
          <span class="material-symbols-outlined">mark_email_read</span>
          Xác thực bằng OTP sau khi đăng nhập
        </p>
        <div class="auth-copy">
          <h2 id="authTitle">Đăng nhập để tiếp tục theo dõi đơn sửa chữa.</h2>
          <p id="authDescription">Chọn đúng vai trò ngay trong màn hình này. Nội dung giới thiệu bên trái sẽ đổi theo lựa chọn của bạn.</p>
        </div>

        <div class="role-switch" id="roleSwitch" aria-label="Chọn vai trò">
          <button type="button" class="role-switch__option is-active" data-role-option="customer">Khách hàng</button>
          <button type="button" class="role-switch__option" data-role-option="worker">Thợ</button>
        </div>

        <form id="loginForm" class="auth-form">
          <div class="form-field">
            <label for="email">Email</label>
            <div class="field-shell">
              <span class="material-symbols-outlined">alternate_email</span>
              <input type="email" id="email" placeholder="example@gmail.com" required />
            </div>
          </div>

          <div class="form-field">
            <label for="matKhau">Mật khẩu</label>
            <div class="field-shell">
              <span class="material-symbols-outlined">lock</span>
              <input type="password" id="matKhau" placeholder="Nhập mật khẩu của bạn" required />
              <button type="button" class="field-shell__toggle" id="togglePasswordButton" aria-label="Hiện hoặc ẩn mật khẩu">
                <span class="material-symbols-outlined" id="eyeIcon">visibility_off</span>
              </button>
            </div>
          </div>

          <div class="auth-form__meta">
            <span class="auth-note"><span class="material-symbols-outlined">shield_lock</span>Mã OTP sẽ được gửi ngay sau bước này.</span>
            <button type="button" class="auth-link-button" id="forgotPasswordTrigger">Quên mật khẩu?</button>
          </div>

          <button type="submit" class="auth-submit" id="btnSubmit">
            Tiếp tục đăng nhập
            <span class="material-symbols-outlined" style="font-size:1rem;">arrow_forward</span>
          </button>
        </form>

        <div class="auth-divider">hoặc tiếp tục nhanh</div>

        <a
          href="{{ $googleAuthEnabled ? route('auth.google.redirect', ['role' => request('role', 'customer')]) : '#' }}"
          class="auth-google{{ $googleAuthEnabled ? '' : ' is-disabled' }}"
          id="googleLoginButton"
          aria-disabled="{{ $googleAuthEnabled ? 'false' : 'true' }}"
          data-google-enabled="{{ $googleAuthEnabled ? '1' : '0' }}"
          data-google-error="{{ $googleAuthMessage }}"
        >
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#EA4335" d="M12 10.2v3.9h5.4c-.24 1.26-.96 2.32-2.04 3.03l3.3 2.56c1.92-1.77 3.03-4.38 3.03-7.47 0-.71-.06-1.39-.18-2.02H12z" />
            <path fill="#4285F4" d="M12 22c2.7 0 4.97-.9 6.63-2.44l-3.3-2.56c-.9.61-2.05.97-3.33.97-2.56 0-4.72-1.73-5.49-4.05H3.1v2.63A10 10 0 0 0 12 22z" />
            <path fill="#FBBC05" d="M6.51 13.92A5.98 5.98 0 0 1 6.2 12c0-.67.12-1.31.31-1.92V7.45H3.1A10 10 0 0 0 2 12c0 1.61.39 3.13 1.1 4.55l3.41-2.63z" />
            <path fill="#34A853" d="M12 6.03c1.47 0 2.79.51 3.84 1.5l2.88-2.88C16.96 3.02 14.7 2 12 2A10 10 0 0 0 3.1 7.45l3.41 2.63C7.28 7.76 9.44 6.03 12 6.03z" />
          </svg>
          <span>Đăng nhập với Google</span>
        </a>

        <p class="auth-hint" id="authHint">Nếu đây là lần đầu đăng nhập bằng Google, hệ thống sẽ tạo tài khoản đúng theo vai trò bạn đang chọn.</p>

        @unless ($googleAuthEnabled)
          <p class="auth-config-note">
            Đăng nhập Google đang tạm tắt. Cần cấu hình: {{ implode(', ', $googleAuthMissingKeys) }}.
            Callback URL hiện tại: {{ $googleAuthRedirectUri }}
          </p>
        @endunless

        <p class="auth-register">
          Chưa có tài khoản?
          <a href="{{ route('register', ['role' => request('role', 'customer')]) }}" id="registerLink">Đăng ký ngay</a>
        </p>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
  <script type="module">
    window.authLoginConfig = {
      baseUrl: @json(url('/')),
      homeUrl: @json(route('home')),
      registerUrl: @json(route('register')),
      forgotPasswordUrl: @json(route('password.request')),
      googleRedirectUrl: @json(url('/auth/google/redirect')),
      flashError: @json(session('auth_error')),
      initialRole: @json(request('role', 'customer')),
    };
  </script>
  <script type="module" src="{{ asset('assets/js/auth/login.js') }}"></script>
</body>
</html>