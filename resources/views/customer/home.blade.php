@extends('layouts.app')

@section('title', 'Thợ Tốt NTU - Sửa Đồ Điện Nhanh Tại Nha Trang')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Roboto:ital,wght@0,100..900;1,100..900&family=Material+Symbols+Outlined" rel="stylesheet" />
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
          poppins: ['DM Sans', 'sans-serif'],
          inter: ['Roboto', 'sans-serif']
        }
      }
    }
  }
</script>
<style>
  html {
    scroll-behavior: smooth;
  }

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

  body {
   background: linear-gradient(135deg, rgba(186, 242, 233, 0.45) 0%, #fff 60%) !important;
  }

  .hero-title-home {
    font-family: 'DM Sans', sans-serif;
    font-size: clamp(3rem, 6vw, 5rem);
    line-height: 0.98;
    font-weight: 800;
    letter-spacing: -0.06em;
    color: #0f172a;
    margin: 0;
  }

  .hero-title-home__dark {
    color: #14213d;
  }

  .hero-title-home__accent {
    background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
  }

  .hero-title-home__highlight {
    color: #f59e0b;
  }

  .soft-shadow {
    box-shadow: 0 4px 24px 0 rgba(14, 165, 233, 0.08);
  }

  .booking-card {
    background: #fff;
    border-radius: 1.5rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.10);
  }

  .hero-trust-card {
    position: relative;
    overflow: hidden;
    background: transparent;
    border: 0;
    box-shadow: none;
    padding: 0;
  }

  .hero-review-divider {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.18em;
    text-transform: uppercase;
  }

  .hero-review-divider::before,
  .hero-review-divider::after {
    content: '';
    height: 1px;
    flex: 1;
    background: rgba(148, 163, 184, 0.34);
  }

  .hero-review-marquee {
    position: relative;
    overflow: hidden;
    margin-top: 1.35rem;
    padding-block: 0.3rem;
  }

  .hero-review-marquee::before,
  .hero-review-marquee::after {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    z-index: 2;
    width: clamp(2rem, 4vw, 4.5rem);
    pointer-events: none;
  }

  .hero-review-marquee::before {
    left: 0;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0));
  }

  .hero-review-marquee::after {
    right: 0;
    background: linear-gradient(270deg, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0));
  }

  .hero-review-marquee__track {
    display: flex;
    width: max-content;
    animation: hero-review-marquee 34s linear infinite;
  }

  .hero-review-marquee:hover .hero-review-marquee__track {
    animation-play-state: paused;
  }

  .hero-review-marquee__group {
    display: flex;
    flex-shrink: 0;
    gap: 1.15rem;
    padding-right: 1.15rem;
  }

  .hero-review-card {
    display: flex;
    min-width: clamp(18rem, 31vw, 28rem);
    max-width: clamp(18rem, 31vw, 28rem);
    flex-direction: column;
    gap: 0.8rem;
    border-radius: 1.35rem;
    border: 1px solid rgba(148, 163, 184, 0.16);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
    padding: 1.2rem 1.25rem;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
  }

  .hero-review-card__top {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
  }

  .hero-review-card__avatar {
    width: 3rem;
    height: 3rem;
    flex-shrink: 0;
    border-radius: 999px;
    object-fit: cover;
    box-shadow: 0 10px 24px rgba(14, 165, 233, 0.16);
  }

  .hero-review-card__avatar--fallback {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.16), rgba(186, 242, 233, 0.88));
    color: #0f172a;
    font-size: 0.92rem;
    font-weight: 900;
    letter-spacing: 0.08em;
  }

  .hero-review-card__stars {
    margin-top: 0.3rem;
    display: flex;
    gap: 0.1rem;
    color: #f59e0b;
  }

  .hero-review-card__stars .material-symbols-outlined {
    font-size: 1rem;
    font-variation-settings: 'FILL' 1;
  }

  .hero-review-card__comment {
    margin: 0;
    min-height: 4.9rem;
    color: #1e293b;
    font-size: 0.94rem;
    line-height: 1.7;
  }

  .hero-review-card__subline {
    margin-top: 0.1rem;
    color: #64748b;
    font-size: 0.82rem;
    line-height: 1.55;
  }

  .hero-review-card__meta {
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.01em;
  }

  .hero-review-empty {
    display: flex;
    align-items: center;
    gap: 1rem;
    border-radius: 1.35rem;
    border: 1px dashed rgba(148, 163, 184, 0.4);
    background: rgba(255, 255, 255, 0.78);
    padding: 1rem 1.1rem;
    color: #475569;
  }

  .hero-review-empty .material-symbols-outlined {
    font-size: 2rem;
    color: #0ea5e9;
  }

  .hero-review-empty strong {
    display: block;
    margin-bottom: 0.25rem;
    color: #0f172a;
  }

  .hero-review-empty p {
    margin: 0;
    font-size: 0.92rem;
    line-height: 1.6;
  }

  @keyframes hero-review-marquee {
    from {
      transform: translate3d(0, 0, 0);
    }

    to {
      transform: translate3d(-50%, 0, 0);
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .hero-review-marquee__track {
      animation-duration: 1ms;
      animation-iteration-count: 1;
    }
  }

  @media (max-width: 767.98px) {
    .hero-review-divider {
      gap: 0.65rem;
      font-size: 0.72rem;
      letter-spacing: 0.14em;
    }

    .hero-review-card {
      min-width: min(18rem, calc(100vw - 4.5rem));
      max-width: min(18rem, calc(100vw - 4.5rem));
      padding: 1rem 1.05rem;
    }
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
    color: #0ea5e9;
    text-transform: uppercase;
    letter-spacing: 2px;
    border: 4px solid #0ea5e9;
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
    background-color: #0ea5e9;
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
    width: 100%;
    visibility: visible !important;
  }

  @media (max-width: 991.98px) {
    app-navbar .navbar-collapse.collapse:not(.show) {
      display: none !important;
    }

    app-navbar .navbar-collapse.collapse.show {
      display: block !important;
    }
  }

  @media (min-width: 992px) {
    app-navbar .navbar-expand-lg .navbar-collapse {
      display: flex !important;
      flex-basis: auto !important;
    }
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

  .customer-home-services {
    scroll-margin-top: 8rem;
  }
</style>
<script>
  (() => {
    const token = localStorage.getItem('access_token');
    const userStr = localStorage.getItem('user');

    if (!token || token === 'undefined' || token === 'null' || !userStr) return;

    try {
      const user = JSON.parse(userStr);
      const targetPath =
        user.role === 'admin' ? '/admin/dashboard' :
        user.role === 'worker' ? '/worker/dashboard' :
        user.role === 'customer' ? '/customer/home' :
        null;

      if (targetPath && window.location.pathname !== targetPath) {
        window.location.replace(targetPath);
      }
    } catch (error) {}
  })();
</script>
@endpush

@section('content')
<app-navbar></app-navbar>

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
        <h1 class="hero-title-home">
          <span class="hero-title-home__dark">Hỏng là</span>
          <span class="hero-title-home__accent">có thợ</span><br>
          <span class="hero-title-home__dark">đặt lịch hôm nay,</span><br>
          <span class="hero-title-home__accent">sửa xong trong ngày</span>
        </h1>
        <p class="text-lg text-slate-600 max-w-2xl">
          Chuyên sửa chữa thiết bị điện gia dụng và điện lạnh chuyên nghiệp. Minh bạch giá cả, bảo hành dài hạn.
        </p>

        <div class="hero-trust-card max-w-4xl">
          <div class="hero-review-divider">
            <span>Đánh giá từ khách hàng</span>
          </div>
            @if(($highlightReviews ?? collect())->isNotEmpty())
            <div class="hero-review-marquee" aria-label="Đánh giá 5 sao từ khách hàng">
              <div class="hero-review-marquee__track">
                @for($marqueeLoop = 0; $marqueeLoop < 2; $marqueeLoop++)
                <div class="hero-review-marquee__group" @if($marqueeLoop === 1) aria-hidden="true" @endif>
                  @foreach($highlightReviews as $review)
                  <article class="hero-review-card">
                    <div class="hero-review-card__top">
                      @if(!empty($review['reviewer_avatar']))
                      <img class="hero-review-card__avatar" src="{{ $review['reviewer_avatar'] }}" alt="{{ $review['reviewer_name'] }}">
                      @else
                      <div class="hero-review-card__avatar hero-review-card__avatar--fallback">{{ $review['reviewer_initials'] }}</div>
                      @endif
                      <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-black text-slate-900">{{ $review['reviewer_name'] }}</p>
                        <p class="hero-review-card__subline">{{ $review['service_label'] }} · {{ $review['mode_label'] }}</p>
                        <div class="hero-review-card__stars" aria-label="5 trên 5 sao">
                          @for($star = 0; $star < 5; $star++)
                          <span class="material-symbols-outlined">star</span>
                          @endfor
                        </div>
                      </div>
                    </div>
                    <p class="hero-review-card__comment">“{{ $review['comment'] }}”</p>
                    <p class="hero-review-card__meta">{{ $review['date_label'] }} @if(!empty($review['booking_code'])) · {{ $review['booking_code'] }} @endif</p>
                  </article>
                  @endforeach
                </div>
                @endfor
              </div>
            </div>
            @else
            <div class="hero-review-empty mt-5">
              <span class="material-symbols-outlined">reviews</span>
              <div>
                <strong>Đánh giá 5 sao sẽ tự động xuất hiện tại đây ngay khi khách hoàn tất đơn hàng.</strong>
                <p>Phần này đang chờ thêm review xác thực từ hệ thống.</p>
              </div>
            </div>
            @endif
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
  <section id="services" class="customer-home-services max-w-7xl mx-auto px-6 py-16">
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
      @foreach($services as $index => [$name, $desc, $icon, $img])
      <div class="customer-service-card {{ $index >= 4 ? 'customer-service-card--extra hidden' : '' }} group flex flex-col rounded-2xl bg-white p-5 soft-shadow border border-transparent hover:border-primary-dark transition-all cursor-pointer" onclick="openBookingModal('{{ $name }}')">
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
    @if(count($services) > 4)
    <div class="mt-8 flex justify-center">
      <button
        id="toggleServicesBtn"
        type="button"
        class="inline-flex min-w-[220px] items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-700 shadow-sm transition-all duration-300 hover:border-primary-dark hover:text-primary-dark hover:shadow-md">
        <span id="toggleServicesLabel">Xem thêm dịch vụ</span>
        <span id="toggleServicesIcon" class="material-symbols-outlined text-lg">expand_more</span>
      </button>
    </div>
    @endif
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

  // AI Section button scroll
  document.getElementById('btnHeroAI')?.addEventListener('click', () => {
    document.getElementById('ai-diagnosis')?.scrollIntoView({
      behavior: 'smooth'
    });
  });

  (() => {
    const toggleButton = document.getElementById('toggleServicesBtn');
    const toggleLabel = document.getElementById('toggleServicesLabel');
    const toggleIcon = document.getElementById('toggleServicesIcon');
    const extraCards = Array.from(document.querySelectorAll('.customer-service-card--extra'));
    const servicesSection = document.getElementById('services');

    if (!toggleButton || !toggleLabel || !toggleIcon || !extraCards.length) {
      return;
    }

    let expanded = false;

    const renderServices = () => {
      extraCards.forEach((card) => {
        card.classList.toggle('hidden', !expanded);
      });

      toggleLabel.textContent = expanded ? 'Thu gọn dịch vụ' : 'Xem thêm dịch vụ';
      toggleIcon.textContent = expanded ? 'expand_less' : 'expand_more';
    };

    toggleButton.addEventListener('click', () => {
      expanded = !expanded;
      renderServices();

      if (!expanded) {
        servicesSection?.scrollIntoView({
          behavior: 'smooth',
          block: 'start',
        });
      }
    });

    renderServices();
  })();

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
