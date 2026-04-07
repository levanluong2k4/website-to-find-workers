<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Đăng nhập Google - Thợ Tốt NTU</title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    *{box-sizing:border-box}
    :root{--app-font-sans:'Be Vietnam Pro',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;}
    body, body *:not(pre):not(code):not(kbd):not(samp){font-family:var(--app-font-sans)!important}
    body{margin:0;min-height:100vh;display:grid;place-items:center;background:linear-gradient(180deg,#dff3ff 0%,#f8fcff 100%);font-family:Roboto,sans-serif;color:#0f172a}
    .card{width:min(420px,calc(100vw - 2rem));background:#fff;border-radius:24px;padding:2rem;box-shadow:0 20px 60px rgba(14,165,233,.12);text-align:center}
    .spinner{width:44px;height:44px;margin:0 auto 1rem;border:4px solid rgba(14,165,233,.18);border-top-color:#0EA5E9;border-radius:50%;animation:spin .8s linear infinite}
    .title{font-size:1.2rem;font-weight:700;margin-bottom:.5rem}
    .sub{font-size:.92rem;color:#64748b;line-height:1.5}
    @keyframes spin{to{transform:rotate(360deg)}}
  </style>
</head>
<body>
  <div class="card">
    <div class="spinner"></div>
    <div class="title">Đăng nhập thành công</div>
    <div class="sub">Hệ thống đang hoàn tất phiên đăng nhập bằng Google và chuyển bạn đến đúng trang.</div>
  </div>

  <script>
    const token = @json($token);
    const user = @json($user);
    const redirectTo = @json($redirectTo);

    localStorage.setItem('access_token', token);
    localStorage.setItem('user', JSON.stringify(user));
    window.location.replace(redirectTo);
  </script>
</body>
</html>
