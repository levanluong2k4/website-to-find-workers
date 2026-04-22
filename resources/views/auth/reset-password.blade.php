@php
  $requestedRole = request('role');
  $loginParams = in_array($requestedRole, ['customer', 'worker'], true) ? ['role' => $requestedRole] : [];
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Đặt lại mật khẩu - Thợ Tốt NTU</title>
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
          <small>THIẾT LẬP MẬT KHẨU MỚI</small>
          <strong>Thợ Tốt NTU</strong>
        </span>
      </a>

      <div class="password-callout">
        <span class="password-callout__eyebrow">
          <span class="material-symbols-outlined" style="font-size:0.95rem;">verified_user</span>
          Liên kết bảo mật
        </span>
        <h2>Thiết lập mật khẩu mới để quay lại đăng nhập.</h2>
        <p>Sau khi đặt lại mật khẩu, bạn sẽ quay về màn đăng nhập. Luồng OTP vẫn được giữ nguyên cho bước đăng nhập tiếp theo.</p>

        <div class="password-stats">
          <article class="password-stat">
            <strong>Không tự đăng nhập</strong>
            <span>Reset xong, bạn vẫn đăng nhập lại theo đúng quy trình xác thực hiện tại.</span>
          </article>
          <article class="password-stat">
            <strong>Token cũ bị hủy</strong>
            <span>Hệ thống sẽ làm mới thông tin bảo mật sau khi mật khẩu được đổi thành công.</span>
          </article>
          <article class="password-stat">
            <strong>Tối thiểu 6 ký tự</strong>
            <span>Hãy dùng mật khẩu đủ dài và dễ nhớ với riêng bạn.</span>
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

        <p class="password-kicker">Đặt lại mật khẩu</p>
        <h1>Tạo mật khẩu mới</h1>
        <p class="password-copy">Nhập mật khẩu mới cho email bên dưới. Liên kết này chỉ dùng được trong thời gian ngắn.</p>

        <div class="password-summary">
          <strong>Email cần cập nhật</strong>
          <span id="resetPasswordEmailPreview">{{ request('email', 'Chưa xác định email') }}</span>
        </div>

        <form id="resetPasswordForm" class="password-form" style="margin-top:1rem;">
          <div class="password-field">
            <label for="resetPasswordEmail">Email</label>
            <div class="password-shell">
              <span class="material-symbols-outlined">mail</span>
              <input type="email" id="resetPasswordEmail" autocomplete="email" placeholder="example@gmail.com" required />
            </div>
          </div>

          <div class="password-field">
            <label for="resetPasswordValue">Mật khẩu mới</label>
            <div class="password-shell">
              <span class="material-symbols-outlined">lock</span>
              <input type="password" id="resetPasswordValue" autocomplete="new-password" placeholder="Nhập mật khẩu mới" required />
            </div>
          </div>

          <div class="password-field">
            <label for="resetPasswordConfirmation">Xác nhận mật khẩu mới</label>
            <div class="password-shell">
              <span class="material-symbols-outlined">lock_reset</span>
              <input type="password" id="resetPasswordConfirmation" autocomplete="new-password" placeholder="Nhập lại mật khẩu mới" required />
            </div>
          </div>

          <button type="submit" class="password-submit" id="resetPasswordSubmit">
            Lưu mật khẩu mới
            <span class="material-symbols-outlined" style="font-size:1rem;">check_circle</span>
          </button>
        </form>

        <div id="resetPasswordState" class="password-state"></div>

        <div class="password-meta">
          <span class="password-pill">
            <span class="material-symbols-outlined" style="font-size:0.95rem;">shield_lock</span>
            Mật khẩu mới sẽ thay thế ngay mật khẩu cũ.
          </span>
          <span>Nếu liên kết đã hết hạn, quay lại bước quên mật khẩu để yêu cầu một liên kết mới.</span>
        </div>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
  <script type="module">
    window.resetPasswordConfig = {
      apiEndpoint: '/reset-password',
      loginUrl: @json(route('login')),
      requestedRole: @json($requestedRole),
      email: @json(request('email')),
      token: @json($token),
    };
  </script>
  <script type="module" src="{{ asset('assets/js/auth/reset-password.js') }}"></script>
</body>
</html>
