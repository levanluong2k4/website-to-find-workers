<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Chọn Vai Trò - Thợ Tốt NTU</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Material+Symbols+Outlined" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <style>
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    html, body { height:100vh; overflow:hidden; font-family:'Inter',sans-serif; display:flex; }

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

    /* ── RIGHT ── */
    .right-panel {
      flex:1; height:100vh; overflow:hidden; background:#fff;
      display:flex; align-items:center; justify-content:center;
      padding:1.5rem 2rem;
    }
    .right-inner { width:100%; max-width:480px; }

    .right-heading {
      font-family:'Poppins',sans-serif; font-weight:800;
      font-size:1.45rem; color:#0f172a; margin-bottom:.3rem; line-height:1.2;
    }
    .right-sub { color:#64748b; font-size:.83rem; margin-bottom:1.25rem; }

    /* Role cards — 2 columns */
    .role-cards-grid {
      display:grid; grid-template-columns:1fr 1fr; gap:.875rem; margin-bottom:.875rem;
    }
    .role-card {
      border:2px solid #e2e8f0; border-radius:1rem;
      padding:1.25rem .875rem;
      cursor:pointer; transition:all .25s cubic-bezier(.4,0,.2,1);
      text-decoration:none; display:flex; flex-direction:column;
      align-items:center; text-align:center; background:#fff;
    }
    .role-card:hover { box-shadow:0 8px 28px rgba(14,165,233,.18); transform:translateY(-4px); }
    .role-card.customer:hover { border-color:#0EA5E9; }
    .role-card.worker:hover   { border-color:#0f172a; box-shadow:0 8px 28px rgba(15,23,42,.14); }

    .role-img-wrap {
      width:90px; height:90px; display:flex; align-items:center; justify-content:center;
      margin-bottom:.65rem;
    }
    .role-img-wrap img { width:auto; height:90px; object-fit:contain; }

    .role-title {
      font-family:'Poppins',sans-serif; font-weight:700;
      font-size:.95rem; color:#0f172a; margin-bottom:.25rem;
    }
    .role-desc { color:#64748b; font-size:.78rem; line-height:1.45; margin-bottom:.65rem; flex:1; }
    .role-cta {
      display:inline-flex; align-items:center; gap:.3rem;
      font-weight:700; font-size:.8rem; border-radius:.5rem;
      padding:.45rem .9rem; transition:all .2s; border:none; cursor:pointer;
    }
    .cta-customer { background:#0EA5E9; color:#fff; }
    .cta-customer:hover { background:#0284c7; }
    .cta-worker { background:#0f172a; color:#fff; }
    .cta-worker:hover { background:#1e293b; }

    .divider-or {
      display:flex; align-items:center; gap:.5rem;
      color:#cbd5e1; font-size:.72rem; margin-bottom:.875rem;
    }
    .divider-or::before, .divider-or::after {
      content:''; flex:1; height:1px; background:#e2e8f0;
    }

    .security-note {
      display:flex; align-items:center; gap:.4rem;
      justify-content:center; font-size:.72rem; color:#94a3b8;
    }
  </style>
</head>
<body>

<!-- LEFT PANEL -->
<div class="left-panel">
  <a href="/" class="logo" style="text-decoration:none;">
    <div class="logo-icon">
      <span class="material-symbols-outlined" style="color:#fff;font-size:1.2rem;">home_repair_service</span>
    </div>
    <span class="logo-text">Thợ Tốt NTU</span>
  </a>

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
  <div class="right-inner">
    <h2 class="right-heading">Bạn tham gia<br>với vai trò gì?</h2>
    <p class="right-sub">Chọn vai trò phù hợp với bạn để tiếp tục</p>

    <div class="role-cards-grid">
      <!-- Customer -->
      <a class="role-card customer" href="{{ route('login') }}?role=customer">
        <div class="role-img-wrap">
          <img src="{{ asset('assets/images/customer.png') }}" alt="Khách hàng" loading="eager">
        </div>
        <p class="role-title">Tôi là Khách Hàng</p>
        <p class="role-desc">Tìm thợ sửa chữa tận nhà, nhanh chóng, uy tín và bảo hành rõ ràng.</p>
        <button class="role-cta cta-customer">
          Bắt đầu đặt lịch
          <span class="material-symbols-outlined" style="font-size:.9rem;">arrow_forward</span>
        </button>
      </a>

      <!-- Worker -->
      <a class="role-card worker" href="{{ route('login') }}?role=worker">
        <div class="role-img-wrap">
          <img src="{{ asset('assets/images/worker2.png') }}" alt="Thợ chuyên nghiệp" loading="eager">
        </div>
        <p class="role-title">Tôi là Thợ</p>
        <p class="role-desc">Nhận việc gần bạn, quản lý lịch làm việc và tăng thu nhập mỗi ngày.</p>
        <button class="role-cta cta-worker">
          Đăng ký làm thợ
          <span class="material-symbols-outlined" style="font-size:.9rem;">arrow_forward</span>
        </button>
      </a>
    </div>

    <div class="divider-or">Thông tin bảo mật</div>

    <div class="security-note">
      <span class="material-symbols-outlined" style="font-size:.85rem;">shield</span>
      Thông tin của bạn được bảo mật hoàn toàn bởi hệ thống mã hóa SSL
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>