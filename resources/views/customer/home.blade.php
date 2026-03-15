@extends('layouts.app')

@section('title', 'Thợ Tốt NTU - Sửa Đồ Điện Nhanh Tại Nha Trang')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700&family=Material+Symbols+Outlined" rel="stylesheet" />
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    corePlugins: {
      preflight: false
    },
    theme: {
      extend: {
        colors: {
          primary: '#BAF2E9',
          'primary-dark': '#0EA5E9',
          accent: '#0EA5E9',
          'bg-light': '#f8fafc',
          'slate-custom': '#64748b'
        },
        fontFamily: {
          poppins: ['Poppins', 'sans-serif'],
          inter: ['Inter', 'sans-serif']
        }
      }
    }
  }
</script>
<style>
  .material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-weight: normal;
    font-style: normal;
    font-size: 24px;
    display: inline-block;
    line-height: 1;
  }

  .hover\:scale-\[1\.02\]:hover {
    transform: scale(1.02);
  }

  .active\:scale-\[0\.98\]:active {
    transform: scale(0.98);
  }

  .hero-gradient {
    background: linear-gradient(135deg, rgba(186, 242, 233, 0.45) 0%, #fff 60%);
  }

  .soft-shadow {
    box-shadow: 0 4px 24px 0 rgba(14, 165, 233, 0.08);
  }

  .booking-card {
    background: #fff;
    border-radius: 1.5rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.10);
  }

  .c-button {
    color: #000;
    font-weight: 700;
    font-size: 16px;
    text-decoration: none;
    padding: 0.9em 1.6em;
    cursor: pointer;
    display: inline-block;
    vertical-align: middle;
    position: relative;
    z-index: 1;
    width: 100%;
    text-align: center;
    line-height: 1.2;
  }

  .c-button--gooey {
    color: #06c8d9;
    text-transform: uppercase;
    letter-spacing: 2px;
    border: 4px solid #06c8d9;
    border-radius: 0;
    position: relative;
    transition: all 700ms ease;
    overflow: hidden;
    background: transparent;
  }

  .c-button--gooey .c-button__blobs {
    height: 100%;
    filter: url(#goo);
    overflow: hidden;
    position: absolute;
    top: 0;
    left: 0;
    bottom: -3px;
    right: -1px;
    z-index: -1;
  }

  .c-button--gooey .c-button__blobs div {
    background-color: #06c8d9;
    width: 34%;
    height: 100%;
    border-radius: 100%;
    position: absolute;
    transform: scale(1.4) translateY(125%) translateZ(0);
    transition: all 700ms ease;
  }

  .c-button--gooey .c-button__blobs div:nth-child(1) {
    left: -5%;
  }

  .c-button--gooey .c-button__blobs div:nth-child(2) {
    left: 30%;
    transition-delay: 60ms;
  }

  .c-button--gooey .c-button__blobs div:nth-child(3) {
    left: 66%;
    transition-delay: 25ms;
  }

  .c-button--gooey:hover {
    color: #fff;
  }

  .c-button--gooey:hover .c-button__blobs div {
    transform: scale(1.4) translateY(0) translateZ(0);
  }

  /* ====================================================
   FIX: Bootstrap classes overridden by Tailwind CDN
   ==================================================== */

  /* Restore Bootstrap navbar collapse behavior */
  app-navbar .navbar-collapse.collapse {
    visibility: visible !important;
  }

  app-navbar .navbar-expand-lg .navbar-collapse {
    display: flex !important;
    flex-basis: auto !important;
  }

  /* Restore Bootstrap display utilities */
  app-navbar .d-flex {
    display: flex !important;
  }

  app-navbar .d-none {
    display: none !important;
  }

  app-navbar .d-md-block {
    display: none !important;
  }

  @media (min-width: 768px) {
    app-navbar .d-md-block {
      display: block !important;
    }
  }

  app-navbar .d-none.d-md-block {
    display: none !important;
  }

  @media (min-width: 768px) {
    app-navbar .d-none.d-md-block {
      display: block !important;
    }
  }

  /* Restore Bootstrap btn styles that Tailwind may break */
  app-navbar .btn {
    display: inline-block;
    font-weight: 400;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    cursor: pointer;
    border: 1px solid transparent;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    border-radius: 0.375rem;
    text-decoration: none !important;
  }

  app-navbar .btn-primary {
    color: #fff !important;
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
  }

  app-navbar .btn-warning {
    color: #000 !important;
    background-color: #ffc107 !important;
    border-color: #ffc107 !important;
  }

  /* Fix baseline sizing for app-navbar */
  app-navbar {
    display: block;
    width: 100%;
  }
</style>
@endpush

@section('content')
<!-- ===================== STITCH NAVBAR ===================== -->
<nav id="landingNav" style="position:sticky;top:0;z-index:1000;width:100%;background:rgba(255,255,255,0.88);backdrop-filter:blur(16px);border-bottom:1px solid rgba(0,0,0,0.06);">
  <div style="max-width:80rem;margin:0 auto;padding:0 1.5rem;height:5rem;display:flex;align-items:center;justify-content:space-between;">

    <!-- Logo -->
    <a href="/" style="display:flex;align-items:center;gap:0.625rem;text-decoration:none;">
      <img src="/assets/images/logontu.png" alt="Logo NTU" style="width:3.3rem;height:3.3rem;object-fit:contain;border-radius:0.9rem;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.08));">
      <span style="font-size:1.2rem;font-weight:800;color:#0f172a;font-family:'Poppins',sans-serif;letter-spacing:-0.5px;">
        Thợ Tốt <span style="color:#0EA5E9;">NTU</span>
      </span>
    </a>

    <!-- Center Nav Links -->
    <nav id="navLinks" style="display:none;gap:0.25rem;align-items:center;">
      <a href="#services" class="nav-link-item">Dịch vụ</a>
      <a href="#workers" class="nav-link-item">Thợ sửa</a>
      <a href="#pricing" class="nav-link-item">Bảng giá</a>
      <a href="#ai-diagnosis" class="nav-link-item">AI Chẩn đoán</a>
    </nav>

    <!-- Right Actions -->
    <div style="display:flex;align-items:center;gap:0.75rem;position:relative;">
      <button onclick="openBookingModal()" class="nav-cta-btn">
        <span class="material-symbols-outlined" style="font-size:1.1rem;">event_upcoming</span>
        Đặt lịch sửa
      </button>

      <!-- Avatar + Dropdown -->
      <div id="navUserAvatar" style="display:none;position:relative;">
        <div id="navUserAvatarBtn"
          style="width:2.5rem;height:2.5rem;border-radius:50%;border:2px solid #BAF2E9;overflow:hidden;cursor:pointer;"
          onclick="toggleUserMenu(event)">
          <div id="navUserInitial" style="width:100%;height:100%;background:#0EA5E9;color:#fff;font-weight:700;font-size:1rem;display:flex;align-items:center;justify-content:center;">U</div>
        </div>
        <!-- Dropdown menu -->
        <div id="userDropdown" style="display:none;position:absolute;top:calc(100% + 10px);right:0;min-width:200px;background:#fff;border-radius:1rem;box-shadow:0 8px 32px rgba(0,0,0,.12);border:1px solid #f1f5f9;z-index:9999;overflow:hidden;">
          <!-- User info header -->
          <div id="dropUserInfo" style="padding:.875rem 1rem .75rem;border-bottom:1px solid #f1f5f9;">
            <p id="dropUserName" style="font-weight:700;font-size:.9rem;color:#0f172a;margin:0;">Người dùng</p>
            <p id="dropUserEmail" style="font-size:.75rem;color:#64748b;margin:.1rem 0 0;"></p>
          </div>
          <!-- Menu items -->
          <a href="/customer/my-bookings" style="display:flex;align-items:center;gap:.6rem;padding:.7rem 1rem;color:#334155;font-size:.875rem;font-weight:600;text-decoration:none;transition:background .15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
            <span class="material-symbols-outlined" style="font-size:1.1rem;color:#0EA5E9;">calendar_month</span>
            Đơn đặt lịch
          </a>
          <a href="/customer/profile" style="display:flex;align-items:center;gap:.6rem;padding:.7rem 1rem;color:#334155;font-size:.875rem;font-weight:600;text-decoration:none;transition:background .15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
            <span class="material-symbols-outlined" style="font-size:1.1rem;color:#0EA5E9;">manage_accounts</span>
            Tài khoản
          </a>
          <div style="height:1px;background:#f1f5f9;margin:0 .75rem;"></div>
          <button onclick="logoutCustomer()" style="display:flex;align-items:center;gap:.6rem;width:100%;padding:.7rem 1rem;background:transparent;border:none;color:#ef4444;font-size:.875rem;font-weight:600;cursor:pointer;text-align:left;transition:background .15s;" onmouseover="this.style.background='#fff5f5'" onmouseout="this.style.background='transparent'">
            <span class="material-symbols-outlined" style="font-size:1.1rem;">logout</span>
            Đăng xuất
          </button>
        </div>
      </div>

      <a id="navLoginBtn" href="/select-role" style="display:none;background:#0EA5E9;color:#fff;font-weight:700;font-size:0.875rem;text-decoration:none;border-radius:0.75rem;padding:0.625rem 1.25rem;">Đăng nhập</a>
      <button id="navHamburger" style="display:none;background:none;border:none;cursor:pointer;padding:0.5rem;" onclick="document.getElementById('mobileMenu').style.display=document.getElementById('mobileMenu').style.display==='none'?'block':'none'">
        <span class="material-symbols-outlined" style="font-size:1.5rem;color:#475569;">menu</span>
      </button>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobileMenu" style="display:none;padding:1rem 1.5rem 1.5rem;border-top:1px solid #f1f5f9;background:#fff;">
    <a href="#services" style="display:block;padding:.75rem 0;color:#334155;font-weight:600;font-size:.9rem;text-decoration:none;">Dịch vụ</a>
    <a href="#workers" style="display:block;padding:.75rem 0;color:#334155;font-weight:600;font-size:.9rem;text-decoration:none;border-top:1px solid #f1f5f9;">Thợ sửa</a>
    <a href="#pricing" style="display:block;padding:.75rem 0;color:#334155;font-weight:600;font-size:.9rem;text-decoration:none;border-top:1px solid #f1f5f9;">Bảng giá</a>
    <a href="#ai-diagnosis" style="display:block;padding:.75rem 0;color:#334155;font-weight:600;font-size:.9rem;text-decoration:none;border-top:1px solid #f1f5f9;">AI Chẩn đoán</a>
  </div>
</nav>

<style>
  .nav-link-item {
    color: #475569;
    font-size: .875rem;
    font-weight: 600;
    text-decoration: none;
    padding: .5rem .75rem;
    border-radius: .5rem;
    transition: color .15s;
  }

  .nav-link-item:hover {
    color: #0EA5E9;
  }

  .nav-cta-btn {
    display: flex;
    align-items: center;
    gap: .4rem;
    background: #BAF2E9;
    color: #0f172a;
    font-weight: 700;
    font-size: .875rem;
    border: none;
    border-radius: .75rem;
    padding: .625rem 1.25rem;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(14, 165, 233, 0.14);
    transition: all .2s;
    font-family: 'Inter', sans-serif;
  }

  .nav-cta-btn:hover {
    background: #0EA5E9;
    color: #fff;
  }
</style>

<script>
  (function() {
    function applyNav() {
      var links = document.getElementById('navLinks');
      var ham = document.getElementById('navHamburger');
      if (!links || !ham) return;
      if (window.innerWidth >= 768) {
        links.style.display = 'flex';
        ham.style.display = 'none';
      } else {
        links.style.display = 'none';
        ham.style.display = 'block';
      }
    }
    window.addEventListener('resize', applyNav);
    applyNav();

    // Show user state & populate dropdown
    try {
      var raw = localStorage.getItem('user');
      var user = raw ? JSON.parse(raw) : null;
      if (user && user.name) {
        var av = document.getElementById('navUserAvatar');
        var init = document.getElementById('navUserInitial');
        var dn = document.getElementById('dropUserName');
        var de = document.getElementById('dropUserEmail');
        if (av) av.style.display = 'block';
        if (init) init.textContent = user.name.charAt(0).toUpperCase();
        if (dn) dn.textContent = user.name;
        if (de) de.textContent = user.email || '';
      } else {
        var btn = document.getElementById('navLoginBtn');
        if (btn) btn.style.display = 'inline-flex';
      }
    } catch (e) {
      var btn2 = document.getElementById('navLoginBtn');
      if (btn2) btn2.style.display = 'inline-flex';
    }
  })();

  // Toggle user dropdown
  window.toggleUserMenu = function(e) {
    e.stopPropagation();
    var dd = document.getElementById('userDropdown');
    if (!dd) return;
    dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
  };
  // Close dropdown when clicking outside
  document.addEventListener('click', function() {
    var dd = document.getElementById('userDropdown');
    if (dd) dd.style.display = 'none';
  });

  // Logout customer
  window.logoutCustomer = async function() {
    try {
      var token = localStorage.getItem('access_token');
      if (token && token !== 'undefined' && token !== 'null') {
        await fetch('/api/logout', {
          method: 'POST',
          headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
          }
        });
      }
    } catch (e) {} finally {
      localStorage.removeItem('access_token');
      localStorage.removeItem('user');
      window.location.href = '/';
    }
  };
</script>

<div class="font-inter bg-white text-slate-900 overflow-x-hidden">
  <svg aria-hidden="true" style="position:absolute;width:0;height:0;pointer-events:none;">
    <defs>
      <filter id="goo">
        <feGaussianBlur in="SourceGraphic" stdDeviation="10" result="blur" />
        <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 21 -7" result="goo" />
        <feBlend in="SourceGraphic" in2="goo" />
      </filter>
    </defs>
  </svg>

  <!-- ===================== HERO ===================== -->
  <section class="hero-gradient relative pt-12 pb-24 px-6 overflow-hidden">
    <div class="max-w-7xl mx-auto grid lg:grid-cols-12 gap-12 items-center">

      <!-- Left Content -->
      <div class="lg:col-span-7 flex flex-col gap-8">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-dark/10 text-primary-dark text-xs font-bold uppercase tracking-wider w-fit">
          <span class="material-symbols-outlined text-sm">verified</span>
          Dịch vụ chuẩn 5 sao tại Nha Trang
        </div>
        <h1 class="text-5xl lg:text-[58px] font-extrabold leading-tight text-slate-900">
          Sửa đồ điện nhanh tại Nha Trang<br>
          <span class="text-primary-dark">Thợ uy tín</span> – Có cửa hàng – Sửa tận nhà
        </h1>
        <p class="text-lg text-slate-600 max-w-2xl">
          Chuyên sửa chữa thiết bị điện gia dụng và điện lạnh chuyên nghiệp. Minh bạch giá cả, bảo hành dài hạn.
        </p>

        <!-- Quick Booking Form -->
        <div class="booking-card p-8 max-w-2xl">
          <h3 class="text-xl font-bold mb-6 flex items-center gap-2 text-slate-900">
            <span class="material-symbols-outlined text-primary-dark">event_upcoming</span>
            Đặt lịch nhanh
          </h3>
          <div class="grid md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-slate-700">Thiết bị cần sửa</label>
              <div class="relative">
                <select id="heroDevice" class="w-full h-12 rounded-xl border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary-dark px-4 appearance-none text-sm">
                  <option value="">Chọn loại thiết bị</option>
                  <option>Tivi</option>
                  <option>Máy giặt</option>
                  <option>Tủ lạnh</option>
                  <option>Điều hòa</option>
                  <option>Bếp điện</option>
                  <option>Lò vi sóng</option>
                  <option>Khác</option>
                </select>
                <span class="material-symbols-outlined absolute right-3 top-3 pointer-events-none text-slate-400 text-lg">expand_more</span>
              </div>
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-slate-700">Địa chỉ tại Nha Trang</label>
              <input id="heroAddress" class="w-full h-12 rounded-xl border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary-dark px-4 text-sm" placeholder="Số nhà, tên đường..." type="text" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-slate-700">Ngày hẹn</label>
              <input id="heroDate" class="w-full h-12 rounded-xl border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary-dark px-4 text-sm" type="date" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-slate-700">Giờ hẹn</label>
              <div class="relative">
                <select id="heroTime" class="w-full h-12 rounded-xl border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary-dark px-4 appearance-none text-sm">
                  <option>Sáng (08:00 – 12:00)</option>
                  <option>Chiều (13:30 – 17:30)</option>
                  <option>Tối (18:00 – 20:00)</option>
                </select>
                <span class="material-symbols-outlined absolute right-3 top-3 pointer-events-none text-slate-400 text-lg">schedule</span>
              </div>
            </div>
          </div>
          <div class="flex flex-wrap gap-4 mt-6">
            <button id="btnHeroBook" class="flex-1 min-w-[160px] h-14 rounded-xl bg-primary-dark text-white font-bold text-base shadow-lg hover:opacity-90 transition-all flex items-center justify-center gap-2">
              <span class="material-symbols-outlined">send</span> Đặt lịch ngay
            </button>
            <button id="btnHeroAI" class="flex-1 min-w-[180px] h-14 rounded-xl bg-slate-100 text-slate-900 font-bold shadow-sm hover:bg-primary/60 transition-all flex items-center justify-center gap-2">
              <span class="material-symbols-outlined text-primary-dark">smart_toy</span> AI kiểm tra lỗi
            </button>
          </div>
        </div>
      </div>

      <!-- Right Illustration -->
      <div class="lg:col-span-5 relative hidden lg:flex flex-col items-center">
        @php
        $heroSlides = [
        ['/assets/images/carousel/Gemini_Generated_Image_7a95157a95157a95.png', 'Tiếp nhận yêu cầu nhanh', 'Thợ trực hệ thống, xác nhận thông tin và điều phối lịch hẹn ngay cho khách.'],
        ['/assets/images/suamaylanh.png', 'Chuyên sửa máy lạnh', 'Xử lý chảy nước, kém lạnh, vệ sinh dàn lạnh và nạp gas tại nhà.'],
        ['/assets/images/carousel/noichien.jpg', 'Sửa đồ gia dụng nhỏ', 'Nhận sửa nồi chiên, nồi cơm, bếp điện và các thiết bị gia dụng nhỏ tại cửa hàng.'],
        ['/assets/images/carousel/suatulanhj.jpg', 'Kiểm tra tủ lạnh tại xưởng', 'Tập kết, phân loại và xử lý các lỗi tủ lạnh, tủ mát và thiết bị lạnh kích thước lớn.'],
        ['/assets/images/carousel/suamaygiat.jpg', 'Hỗ trợ nhận vận chuyển thiết bị', 'Nhận máy giặt, tủ lạnh và thiết bị lớn về xưởng khi khách cần hỗ trợ vận chuyển.'],
        ];
        @endphp
        <div class="absolute -inset-4 bg-primary/25 blur-3xl rounded-full -z-10"></div>
        <div id="heroCarousel" class="relative rounded-[2rem] overflow-hidden border-8 border-white shadow-2xl w-full">
          <div class="relative aspect-[4/5] overflow-hidden bg-slate-100">
            @foreach($heroSlides as [$img, $title, $desc])
            <img
              class="hero-carousel-slide absolute inset-0 h-full w-full object-cover transition-opacity duration-700 {{ $loop->first ? 'opacity-100' : 'opacity-0' }}"
              src="{{ $img }}"
              alt="{{ $title }}"
              data-title="{{ $title }}"
              data-desc="{{ $desc }}" />
            @endforeach

            <div class="absolute inset-x-0 top-0 p-5 bg-gradient-to-b from-slate-950/45 to-transparent">
              <div class="inline-flex items-center gap-2 rounded-full bg-white/85 px-4 py-2 text-xs font-bold uppercase tracking-[0.25em] text-slate-700 shadow">
                <span class="material-symbols-outlined text-base text-primary-dark">home_repair_service</span>
                Dịch vụ nổi bật
              </div>
            </div>

            <div class="absolute inset-x-0 bottom-24 p-6 bg-gradient-to-t from-slate-950/75 to-transparent text-white">
              <p id="heroCarouselTitle" class="text-2xl font-black leading-tight">{{ $heroSlides[0][1] }}</p>
              <p id="heroCarouselDesc" class="mt-2 max-w-sm text-sm text-white/85">{{ $heroSlides[0][2] }}</p>
            </div>
          </div>

          <div class="absolute left-1/2 top-5 z-10 flex -translate-x-1/2 gap-2">
            @foreach($heroSlides as [$img, $title, $desc])
            <button
              type="button"
              class="hero-carousel-dot h-2.5 rounded-full bg-white/60 transition-all {{ $loop->first ? 'w-8 bg-white' : 'w-2.5' }}"
              data-index="{{ $loop->index }}"
              aria-label="Chuyển ảnh {{ $loop->iteration }}"></button>
            @endforeach
          </div>

          <div class="absolute bottom-6 left-4 right-4 bg-white/90 backdrop-blur p-4 rounded-2xl shadow-xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center text-white shrink-0">
              <span class="material-symbols-outlined">call</span>
            </div>
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase">Hỗ trợ 24/7</p>
              <p class="text-lg font-extrabold text-slate-900">0905 123 456</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== QUICK INFO ===================== -->
  <section class="max-w-7xl mx-auto px-6 py-10">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
      @foreach([['bolt','Sửa nhanh 30\'','Có mặt ngay sau khi gọi'],['verified_user','Bảo hành 12th','Yên tâm sử dụng lâu dài'],['payments','Giá minh bạch','Báo giá trước khi sửa'],['store','Có cửa hàng','Địa chỉ uy tín, rõ ràng']] as [$icon, $title, $desc])
      <div class="flex flex-col items-center text-center gap-3 p-6 rounded-2xl bg-white soft-shadow border border-slate-100">
        <span class="material-symbols-outlined text-4xl text-primary-dark">{{ $icon }}</span>
        <h4 class="font-bold text-slate-900">{{ $title }}</h4>
        <p class="text-sm text-slate-500">{{ $desc }}</p>
      </div>
      @endforeach
    </div>
  </section>

  <!-- ===================== SERVICES ===================== -->
  <section id="services" class="max-w-7xl mx-auto px-6 py-16">
    <div class="mb-10 flex items-end justify-between">
      <div>
        <h2 class="text-3xl font-black tracking-tight text-slate-900">Dịch vụ sửa chữa tại Nha Trang</h2>
        <p class="mt-2 text-slate-500">Chuyên nghiệp - Tận tâm - Giá cả minh bạch niêm yết</p>
      </div>
    </div>
    <div class="grid grid-cols-2 gap-6 sm:grid-cols-2 lg:grid-cols-4">
      @php
      $services = [
      ['Sửa Tivi','Màn hình, bo mạch, nguồn','tv','/assets/images/suativi.png'],
      ['Máy giặt','Rung lắc, không vắt, rò nước','local_laundry_service','/assets/images/suamaygiat.png'],
      ['Tủ lạnh','Kém lạnh, đóng tuyết, ồn','kitchen','/assets/images/suatulanh.png'],
      ['Điều hòa','Vệ sinh, nạp gas, hỏng tụ','ac_unit','/assets/images/suadieuhoa.png'],
      ['Bếp điện','Lỗi E0-E9, không nhận nồi','cooking','/assets/images/suabeptu.png'],
      ['Lò vi sóng','Không nóng, liệt phím','microwave','/assets/images/sualovisong.png'],
      ['Nồi chiên KD','Hỏng quạt, cảm biến nhiệt','air_purifier','/assets/images/suanoichien.png'],
      ['Ấm siêu tốc','Không vào điện, cháy rơ le','kettle','/assets/images/suaamsieutoc.png'],
      ];
      @endphp
      @foreach($services as [$name, $desc, $icon, $img])
      <div class="group flex flex-col rounded-2xl bg-white p-5 soft-shadow border border-transparent hover:border-primary-dark transition-all cursor-pointer" onclick="openBookingModal('{{ $name }}')">
        <div class="mb-4 aspect-square w-full overflow-hidden rounded-xl bg-primary/10">
          <img class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300" src="{{ $img }}" alt="{{ $name }}" />
        </div>
        <div class="flex items-start justify-between">
          <div>
            <h3 class="font-bold text-slate-900">{{ $name }}</h3>
            <p class="text-xs text-slate-500" style="width: 101%;">{{ $desc }}</p>
          </div>
          <span class="material-symbols-outlined text-primary-dark">{{ $icon }}</span>
        </div>
        <button type="button" class="c-button c-button--gooey mt-4">
          <span class="c-button__blobs">
            <div></div>
            <div></div>
            <div></div>
          </span>
          Sửa ngay
        </button>
      </div>
      @endforeach
    </div>
  </section>

  <!-- ===================== AI DIAGNOSIS ===================== -->
  <section id="ai-diagnosis" class="max-w-7xl mx-auto px-6 pb-16">
    <div class="rounded-3xl bg-white p-8 shadow-xl border border-primary/30 relative overflow-hidden">
      <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-primary/20 blur-3xl"></div>
      <div class="absolute -left-20 -bottom-20 h-64 w-64 rounded-full bg-primary/10 blur-3xl"></div>
      <div class="relative z-10 grid grid-cols-1 gap-12 lg:grid-cols-2">
        <div>
          <div class="mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined text-4xl text-primary-dark">psychology</span>
            <h2 class="text-3xl font-black text-slate-900">AI Trợ lý Chẩn đoán</h2>
          </div>
          <p class="mb-8 text-slate-500">Mô tả tình trạng lỗi của thiết bị, AI sẽ giúp bạn dự đoán nguyên nhân và chi phí ước tính.</p>
          <div class="space-y-5">
            <div>
              <label class="mb-2 block text-sm font-bold">Thiết bị cần kiểm tra</label>
              <select id="aiDevice" class="w-full rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm focus:border-primary-dark focus:ring-primary-dark">
                <option value="">Chọn loại thiết bị...</option>
                <option value="tv">Tivi</option>
                <option value="wm">Máy giặt</option>
                <option value="ref">Tủ lạnh / Tủ đông</option>
                <option value="ac">Điều hòa</option>
                <option value="stove">Bếp điện / Bếp từ</option>
              </select>
            </div>
            <div>
              <label class="mb-2 block text-sm font-bold">Mô tả hiện tượng lỗi</label>
              <textarea id="aiDesc" class="w-full rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm focus:border-primary-dark" placeholder="Ví dụ: Tivi Samsung có tiếng nhưng không có hình, màn hình có các sọc kẻ ngang..." rows="4"></textarea>
            </div>
            <div class="flex flex-wrap gap-4">
              <button class="flex items-center gap-2 rounded-xl border-2 border-dashed border-primary/40 px-6 py-4 text-sm font-bold text-slate-500 hover:bg-primary/10 transition-all">
                <span class="material-symbols-outlined">add_a_photo</span> Tải ảnh/video lỗi
              </button>
              <button id="btnAIAnalyze" class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-primary-dark py-4 text-base font-black text-white hover:opacity-90 shadow-lg transition-all">
                <span class="material-symbols-outlined">analytics</span> Phân tích lỗi với AI
              </button>
            </div>
          </div>
        </div>
        <div class="rounded-2xl bg-slate-50 p-6 border border-slate-100">
          <h3 class="mb-6 flex items-center gap-2 font-bold">
            <span class="material-symbols-outlined text-primary-dark">assignment_turned_in</span>
            Kết quả chẩn đoán dự kiến
          </h3>
          <div id="aiResult" class="space-y-5">
            <div class="rounded-xl bg-white p-4 shadow-sm border-l-4 border-primary-dark">
              <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Nguyên nhân có thể</p>
              <p class="mt-1 text-sm text-slate-600">Hệ thống đang chờ thông tin mô tả để phân tích...</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm border-l-4 border-primary-dark">
              <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Chi phí dự đoán</p>
              <div class="mt-1 flex items-baseline gap-1">
                <span class="text-lg font-black text-slate-900">---.--- ₫</span>
                <span class="text-xs text-slate-400">(Ước tính linh kiện + công)</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== PROCESS ===================== -->
  <section class="bg-slate-50 py-16 px-6">
    <div class="max-w-7xl mx-auto">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-slate-900 mb-3">Quy trình dịch vụ chuyên nghiệp</h2>
        <p class="text-slate-500">4 bước đơn giản để thiết bị của bạn hoạt động trở lại</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        @foreach([['edit_note','1. Mô tả lỗi','Cung cấp thông tin hư hỏng qua website.'],['person_search','2. Chọn thợ','Duyệt hồ sơ thợ giỏi và chọn người phù hợp.'],['build','3. Sửa chữa','Thợ đến tận nhà kiểm tra và khắc phục lỗi.'],['account_balance_wallet','4. Thanh toán','Nghiệm thu và thanh toán trực tiếp hoặc online.']] as [$i, $t, $d])
        <div class="flex flex-col items-center text-center group">
          <div class="w-16 h-16 rounded-2xl bg-primary/20 flex items-center justify-center mb-4 border border-primary/30 group-hover:bg-primary-dark group-hover:text-white text-primary-dark transition-all duration-300">
            <span class="material-symbols-outlined text-3xl">{{ $i }}</span>
          </div>
          <h3 class="text-lg font-bold text-slate-900 mb-2">{{ $t }}</h3>
          <p class="text-sm text-slate-500 leading-relaxed">{{ $d }}</p>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  <!-- ===================== TOP TECHNICIANS ===================== -->
  <section id="workers" class="max-w-7xl mx-auto px-6 py-16">
    <div class="flex items-end justify-between mb-10">
      <div>
        <h2 class="text-3xl font-bold text-slate-900">Thợ Nổi Bật</h2>
        <p class="text-slate-500 mt-2">Những chuyên gia được đánh giá cao nhất trong khu vực</p>
      </div>
      <a class="text-primary-dark font-semibold flex items-center gap-1 hover:underline underline-offset-4" href="/customer/search">
        Xem tất cả <span class="material-symbols-outlined text-sm">arrow_forward</span>
      </a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      @php
      $technicians = [
      ['Nguyễn Văn An','Điện tử - Điện lạnh','8','120','4.9','https://lh3.googleusercontent.com/aida-public/AB6AXuCHlxvrKvXJRdgRmIN9C2gjJg1qrwrCqxFDxa2bJPdZdgcZdPhP2IAnIyD8EJbJQsufm7Hwx9GJEd__kWt-GI5kvbsrfDCj0RCJgNCXbmbEKfh3ppogjQbvjtwhPgxpxXRB-itoy8hfTGUdEOzaZlZ5DWdpLwRev5du1qe8YbUF2EhiYEfVe7w-ykXhGTKfid92jn3dFb8pyUuKukF-Q7HOIieNd9C8ixkDY8Djqc2A_0pp15e9lr4XOsYxJMP4oTTHneWTzDwYUtKF'],
      ['Trần Thị Bình','Máy Lạnh - Tủ Lạnh','5','85','4.8','https://lh3.googleusercontent.com/aida-public/AB6AXuCZ6WPzDWa973i46Iti6WGzxatmssgABgRSSqC-Bq-Dg9PrkT3IemVoB5xMR0W8xtMgvpen_7iWNSZ60jEqetlvfgH79bfqqDetw3HyjFFL3SKiS8EBKTYV_IfyUUdyAssKz-Pe8GV5d5oCFwocnlXnhcN9jAAl6cix8iEJoZBkyb4e3uzX-EPXEcDDl_zcf5sb_UK3kljwBA3c7SpEU_IN8ephehzOc17So5u9TKGz1zW2LWZPxI3oU6Rdy7XamJK6XiMuzVWevocy'],
      ['Lê Văn Cường','Điện Dân Dụng','10','200','5.0','https://lh3.googleusercontent.com/aida-public/AB6AXuBurEaN2EYTRrzaP5wlyCNx92vnsNABdb2VEmrH96QumpgHLW20UC1sn1-_gETRzNWmtDkaR8VHUH_d1nRMb7dAdHBCBsC8vQ-hXpMRDR6nZNLxLK30IedFWT-3HgwUfDnf2QO3d5gl2hUcHSoYdSRR9gmnWU2mjYtouypzW-zjkWan5Xj9ncctCPPOvaQxhxXZ5M3qs7l7cMIYkJDizcJg0Uq6umLx51rSyIXiy-59kz5UMjfH5h6tX_QQDw1lH9wEH86vNvITx3hg'],
      ];
      @endphp
      @foreach($technicians as [$name,$specialty,$years,$jobs,$rating,$img])
      <div class="bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-xl transition-all duration-300 group">
        <div class="h-64 overflow-hidden relative">
          <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent z-10"></div>
          <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="{{ $img }}" alt="{{ $name }}" />
          <div class="absolute bottom-4 left-4 z-20">
            <span class="bg-primary text-slate-900 text-xs font-bold px-2 py-1 rounded-md uppercase tracking-wider">{{ $specialty }}</span>
          </div>
        </div>
        <div class="p-6">
          <div class="flex justify-between items-start mb-3">
            <h3 class="text-xl font-bold text-slate-900">{{ $name }}</h3>
            <div class="flex items-center gap-1 text-yellow-500">
              <span class="material-symbols-outlined text-sm">star</span>
              <span class="text-sm font-bold">{{ $rating }}</span>
            </div>
          </div>
          <div class="space-y-2 mb-6">
            <div class="flex items-center gap-2 text-slate-500 text-sm">
              <span class="material-symbols-outlined text-base">work_history</span>
              <span>{{ $years }} năm kinh nghiệm</span>
            </div>
            <div class="flex items-center gap-2 text-slate-500 text-sm">
              <span class="material-symbols-outlined text-base">check_circle</span>
              <span>Đã hoàn thành {{ $jobs }}+ công việc</span>
            </div>
          </div>
          <a href="/customer/search" class="w-full bg-slate-100 hover:bg-primary hover:text-slate-900 text-slate-900 font-bold py-3 rounded-lg transition-colors flex items-center justify-center gap-2">
            Xem chi tiết <span class="material-symbols-outlined text-sm">open_in_new</span>
          </a>
        </div>
      </div>
      @endforeach
    </div>
  </section>

  <!-- ===================== PRICING ===================== -->
  <section id="pricing" class="bg-slate-50 py-16 px-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-10">
        <h2 class="text-3xl font-black text-slate-900 mb-3">Bảng Giá Dịch Vụ Tham Khảo</h2>
        <div class="flex items-center gap-3 p-4 bg-primary/20 rounded-xl border border-primary/40 max-w-2xl">
          <span class="material-symbols-outlined text-primary-dark">info</span>
          <p class="text-slate-600 text-sm">Giá niêm yết là mức giá khởi điểm. Chi phí thực tế sẽ được thợ báo chính xác sau khi kiểm tra thiết bị.</p>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach([['tv','Sửa Tivi','150.000'],['local_laundry_service','Máy Giặt','200.000'],['kitchen','Tủ lạnh','250.000'],['ac_unit','Điều Hòa','300.000']] as [$icon,$name,$price])
        <div class="group flex flex-col bg-white p-6 rounded-2xl border border-slate-200 hover:border-primary-dark transition-all shadow-sm hover:shadow-xl">
          <div class="flex justify-between items-start mb-6">
            <div class="w-12 h-12 bg-primary/20 rounded-xl flex items-center justify-center text-primary-dark">
              <span class="material-symbols-outlined text-2xl">{{ $icon }}</span>
            </div>
            <span class="bg-primary-dark text-white text-xs font-bold px-2 py-1 rounded uppercase">Từ</span>
          </div>
          <h3 class="text-lg font-bold mb-2">{{ $name }}</h3>
          <div class="flex items-baseline gap-1 mb-6">
            <span class="text-3xl font-black text-primary-dark">{{ $price }}</span>
            <span class="text-sm font-bold text-slate-500">đ</span>
          </div>
          <ul class="space-y-3 mb-8 flex-1 text-sm text-slate-600">
            <li class="flex items-center gap-2"><span class="material-symbols-outlined text-primary-dark text-base">check_circle</span> Kiểm tra tận nhà</li>
            <li class="flex items-center gap-2"><span class="material-symbols-outlined text-primary-dark text-base">check_circle</span> Linh kiện chính hãng</li>
            <li class="flex items-center gap-2"><span class="material-symbols-outlined text-primary-dark text-base">check_circle</span> Bảo hành 6 tháng</li>
          </ul>
          <button class="w-full bg-slate-100 group-hover:bg-primary-dark group-hover:text-white py-3 rounded-xl font-bold transition-colors" onclick="openBookingModal('{{ $name }}')">
            Liên hệ ngay
          </button>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  <!-- ===================== MAP ===================== -->
  <section class="py-16 px-6">
    <div class="max-w-7xl mx-auto">
      <div class="flex flex-col md:flex-row gap-12 items-center">
        <div class="md:w-1/3">
          <h2 class="text-3xl font-bold mb-4">Tìm chúng tôi tại Nha Trang</h2>
          <p class="text-slate-600 mb-6">Chúng tôi phục vụ toàn bộ khu vực thành phố Nha Trang, bao gồm các phường trung tâm và khu vực lân cận.</p>
          <div class="flex items-center gap-3 mb-3">
            <span class="material-symbols-outlined text-primary-dark">pin_drop</span>
            <span class="text-slate-700">Trần Phú, Phường Lộc Thọ, Nha Trang</span>
          </div>
          <div class="flex items-center gap-3 mb-3">
            <span class="material-symbols-outlined text-primary-dark">schedule</span>
            <span class="text-slate-700">Thứ 2 – Chủ Nhật: 07:00 – 20:00</span>
          </div>
          <div class="flex items-center gap-3 mb-6">
            <span class="material-symbols-outlined text-primary-dark">call</span>
            <span class="text-slate-700 font-bold">0905 123 456</span>
          </div>
          <button class="bg-primary-dark text-white px-6 py-3 rounded-xl font-bold hover:opacity-90 transition-all shadow-lg" onclick="openBookingModal()">
            Đặt lịch đến cửa hàng
          </button>
        </div>
        <div class="md:w-2/3 w-full h-96 rounded-3xl overflow-hidden shadow-2xl">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3900.1!2d109.195!3d12.238!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x316f6c12b8c63f8d%3A0x4a0a9fc44b5e1b75!2sTr%E1%BA%A7n%20Ph%C3%BA%2C%20Nha%20Trang!5e0!3m2!1svi!2svn!4v1700000000000"
            width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== FOOTER ===================== -->
  <footer class="bg-slate-900 text-white pt-12 pb-6 px-6">
    <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-10 mb-10">
      <div>
        <div class="flex items-center gap-2 mb-4">
          <img src="/assets/images/logontu.png" alt="Logo NTU" class="w-12 h-12 rounded-xl object-contain bg-white p-1 shadow-sm">
          <span class="text-xl font-bold">Thợ Tốt <span class="text-primary-dark">NTU</span></span>
        </div>
        <p class="text-slate-400 text-sm leading-relaxed">Nền tảng kết nối thợ sửa chữa uy tín tại Nha Trang. Chuyên nghiệp – Tận tâm – Minh bạch.</p>
        <div class="flex gap-3 mt-5">
          <a href="#" class="w-9 h-9 rounded-full bg-slate-800 hover:bg-primary-dark flex items-center justify-center transition-colors">
            <span class="material-symbols-outlined text-base">public</span>
          </a>
          <a href="#" class="w-9 h-9 rounded-full bg-slate-800 hover:bg-primary-dark flex items-center justify-center transition-colors">
            <span class="material-symbols-outlined text-base">mail</span>
          </a>
          <a href="#" class="w-9 h-9 rounded-full bg-slate-800 hover:bg-primary-dark flex items-center justify-center transition-colors">
            <span class="material-symbols-outlined text-base">call</span>
          </a>
        </div>
      </div>
      <div>
        <h4 class="font-bold mb-4 text-white">Dịch vụ</h4>
        <ul class="space-y-2 text-slate-400 text-sm">
          @foreach(['Sửa Tivi','Sửa Máy giặt','Sửa Tủ lạnh','Sửa Điều hòa','Sửa Bếp điện'] as $sv)
          <li><a href="#services" class="hover:text-primary transition-colors">{{ $sv }}</a></li>
          @endforeach
        </ul>
      </div>
      <div>
        <h4 class="font-bold mb-4 text-white">Thông tin liên hệ</h4>
        <ul class="space-y-3 text-slate-400 text-sm">
          <li class="flex gap-2"><span class="material-symbols-outlined text-primary-dark text-base mt-0.5">pin_drop</span> Trần Phú, Phường Lộc Thọ, Nha Trang</li>
          <li class="flex gap-2"><span class="material-symbols-outlined text-primary-dark text-base">call</span> 0905 123 456</li>
          <li class="flex gap-2"><span class="material-symbols-outlined text-primary-dark text-base">schedule</span> Thứ 2 – CN: 07:00 – 20:00</li>
          <li class="flex gap-2"><span class="material-symbols-outlined text-primary-dark text-base">mail</span> hotro@thotot.ntu.vn</li>
        </ul>
      </div>
    </div>
    <div class="border-t border-slate-800 pt-6 text-center text-slate-500 text-sm">
      © 2024 Thợ Tốt NTU Nha Trang. Tất cả các dịch vụ đều được bảo hành từ 3–12 tháng.
    </div>
  </footer>

</div><!-- end font-inter wrapper -->

@include('customer.partials.booking-wizard-modal')

@endsection

@push('scripts')
<script>
  // Helper to open booking modal and pre-fill service
  function openBookingModal(serviceName = '') {
    if (window.BookingWizardModal?.open) {
      window.BookingWizardModal.open({
        serviceName
      });
      return;
    }
    const targetUrl = new URL('{{ route('customer.booking') }}', window.location.origin);
    if (serviceName) targetUrl.searchParams.set('service_name', serviceName);
    window.location.href = targetUrl.toString();
  }

  // Hero booking form quick submit
  document.getElementById('btnHeroBook')?.addEventListener('click', () => {
    const device = document.getElementById('heroDevice')?.value;
    const address = document.getElementById('heroAddress')?.value;
    const date = document.getElementById('heroDate')?.value;
    if (!device) {
      alert('Vui lòng chọn thiết bị cần sửa');
      return;
    }
    openBookingModal(device);
  });

  // AI Section button scroll
  document.getElementById('btnHeroAI')?.addEventListener('click', () => {
    document.getElementById('ai-diagnosis')?.scrollIntoView({
      behavior: 'smooth'
    });
  });

  // Hero carousel
  (() => {
    const carousel = document.getElementById('heroCarousel');
    if (!carousel) return;

    const slides = Array.from(carousel.querySelectorAll('.hero-carousel-slide'));
    const dots = Array.from(carousel.querySelectorAll('.hero-carousel-dot'));
    const title = document.getElementById('heroCarouselTitle');
    const desc = document.getElementById('heroCarouselDesc');

    if (!slides.length || !dots.length || !title || !desc) return;

    let activeIndex = 0;
    let intervalId = null;

    const renderSlide = (index) => {
      activeIndex = index;

      slides.forEach((slide, slideIndex) => {
        slide.classList.toggle('opacity-100', slideIndex === index);
        slide.classList.toggle('opacity-0', slideIndex !== index);
      });

      dots.forEach((dot, dotIndex) => {
        dot.classList.toggle('w-8', dotIndex === index);
        dot.classList.toggle('bg-white', dotIndex === index);
        dot.classList.toggle('w-2.5', dotIndex !== index);
        dot.classList.toggle('bg-white/60', dotIndex !== index);
      });

      title.textContent = slides[index].dataset.title || '';
      desc.textContent = slides[index].dataset.desc || '';
    };

    const startAutoPlay = () => {
      intervalId = window.setInterval(() => {
        renderSlide((activeIndex + 1) % slides.length);
      }, 3500);
    };

    const stopAutoPlay = () => {
      if (intervalId) {
        window.clearInterval(intervalId);
        intervalId = null;
      }
    };

    dots.forEach((dot, index) => {
      dot.addEventListener('click', () => {
        renderSlide(index);
        stopAutoPlay();
        startAutoPlay();
      });
    });

    carousel.addEventListener('mouseenter', stopAutoPlay);
    carousel.addEventListener('mouseleave', startAutoPlay);

    renderSlide(0);
    startAutoPlay();
  })();

  // AI Analysis button (mock)
  document.getElementById('btnAIAnalyze')?.addEventListener('click', () => {
    const device = document.getElementById('aiDevice')?.value;
    const desc = document.getElementById('aiDesc')?.value;
    const result = document.getElementById('aiResult');
    if (!device || !desc) {
      alert('Vui lòng chọn thiết bị và mô tả lỗi');
      return;
    }
    result.innerHTML = `
    <div class="rounded-xl bg-white p-4 shadow-sm border-l-4 border-primary-dark animate-pulse">
      <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Đang phân tích...</p>
      <p class="mt-1 text-sm text-slate-600">AI đang xử lý thông tin của bạn, vui lòng chờ...</p>
    </div>`;
    setTimeout(() => {
      result.innerHTML = `
      <div class="rounded-xl bg-white p-4 shadow-sm border-l-4 border-primary-dark">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Nguyên nhân có thể</p>
        <p class="mt-1 text-sm text-slate-600">Dựa trên mô tả, thiết bị có thể bị hỏng bo mạch điều khiển hoặc lỗi cảm biến nhiệt độ. Nên kiểm tra nguồn điện và các kết nối.</p>
      </div>
      <div class="rounded-xl bg-white p-4 shadow-sm border-l-4 border-primary-dark">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Chi phí dự đoán</p>
        <div class="mt-1 flex items-baseline gap-1">
          <span class="text-lg font-black text-slate-900">350.000 – 800.000 ₫</span>
          <span class="text-xs text-slate-400">(Ước tính linh kiện + công)</span>
        </div>
      </div>
      <button class="w-full bg-primary-dark text-white py-3 rounded-xl font-bold hover:opacity-90 transition-all" onclick="openBookingModal()">
        Đặt lịch với thợ
      </button>`;
    }, 1500);
  });
</script>
@endpush
