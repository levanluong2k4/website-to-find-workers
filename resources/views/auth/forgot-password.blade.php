@php
  $requestedRole = request('role');
  $loginParams = in_array($requestedRole, ['customer', 'worker'], true) ? ['role' => $requestedRole] : [];
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quên mật khẩu - Thợ Tốt NTU</title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&family=Material+Symbols+Outlined&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" />
  <link rel="stylesheet" href="{{ asset('assets/css/auth/password.css') }}" />
</head>
<body>
  <main class="password-layout">
    <section class="password-hero">
      <a href="{{ route('home') }}" class="password-brand">
        <span class="password-brand__logo">
          <img src="{{ asset('assets/images/logontu.png') }}" alt="Logo Thợ Tốt NTU" />
        </span>
        <span class="password-brand__copy">
          <small>KHÔI PHỤC TRUY CẬP</small>
          <strong>Thợ Tốt NTU</strong>
        </span>
      </a>

      <div class="password-callout">
        <span class="password-callout__eyebrow">
          <span class="material-symbols-outlined" style="font-size:0.95rem;">key</span>
          Bảo mật tài khoản
        </span>
        <h2>Lấy lại quyền truy cập mà không chạm vào luồng OTP hiện tại.</h2>
        <p>Chỉ cần nhập email bạn đã đăng ký. Hệ thống sẽ gửi một liên kết đặt lại mật khẩu để bạn tự đổi mật khẩu mới, sau đó đăng nhập lại như bình thường.</p>

        <div class="password-stats">
          <article class="password-stat">
            <strong>1 email</strong>
            <span>Liên kết reset được gửi trực tiếp tới địa chỉ email đã đăng ký.</span>
          </article>
          <article class="password-stat">
            <strong>60 phút</strong>
            <span>Thời gian hiệu lực mặc định của liên kết đặt lại mật khẩu.</span>
          </article>
          <article class="password-stat">
            <strong>An toàn</strong>
            <span>Mật khẩu mới sẽ được lưu lại và các token cũ sẽ bị vô hiệu hóa.</span>
          </article>
        </div>
      </div>
    </section>

    <section class="password-stage">
      <div class="password-card">
        <a href="{{ route('login', $loginParams) }}" class="password-back">
          <span class="material-symbols-outlined" style="font-size:1rem;">arrow_back</span>
          Quay lại đăng nhập
        </a>

        <p class="password-kicker">Quên mật khẩu</p>
        <h1>Gửi liên kết đặt lại mật khẩu</h1>
        <p class="password-copy">Nhập email của bạn. Nếu tài khoản tồn tại, hệ thống sẽ gửi một liên kết để đặt lại mật khẩu mới.</p>

        <form id="forgotPasswordForm" class="password-form">
          <div class="password-field">
            <label for="forgotPasswordEmail">Email đăng nhập</label>
            <div class="password-shell">
              <span class="material-symbols-outlined">alternate_email</span>
              <input type="email" id="forgotPasswordEmail" autocomplete="email" placeholder="example@gmail.com" required />
            </div>
          </div>

          <button type="submit" class="password-submit" id="forgotPasswordSubmit">
            Gửi liên kết đặt lại
            <span class="material-symbols-outlined" style="font-size:1rem;">north_east</span>
          </button>
        </form>

        <div id="forgotPasswordState" class="password-state"></div>
        <div id="forgotPasswordDebug" class="password-debug">
          <p class="password-debug__title">Môi trường local</p>
          <div id="forgotPasswordDebugMessage"></div>
          <a id="forgotPasswordDebugLink" class="password-debug__link" href="#" hidden>
            Mở trực tiếp liên kết reset
            <span class="material-symbols-outlined" style="font-size:1rem;">open_in_new</span>
          </a>
        </div>

        <div class="password-meta">
          <span class="password-pill">
            <span class="material-symbols-outlined" style="font-size:0.95rem;">mail_lock</span>
            Kiểm tra cả hộp thư spam nếu chưa thấy email.
          </span>
          <span>Nếu bạn không còn truy cập được email cũ, cần đổi email trước rồi mới đặt lại mật khẩu được.</span>
        </div>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
  <script type="module">
    window.forgotPasswordConfig = {
      apiEndpoint: '/forgot-password',
      loginUrl: @json(route('login')),
      requestedRole: @json($requestedRole),
      prefillEmail: @json(request('email')),
    };
  </script>
  <script type="module" src="{{ asset('assets/js/auth/forgot-password.js') }}"></script>
</body>
</html>
