@extends('layouts.app')

@section('title', 'Phí đi lại - Admin')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
tailwind.config = {
  prefix: 'tw-',
  darkMode: "class",
  theme: {
    extend: {
      "colors": {
        "surface-dim": "#d8dadc",
        "tertiary": "#924700",
        "on-tertiary-fixed": "#311400",
        "inverse-on-surface": "#eff1f3",
        "secondary-fixed": "#d8e2ff",
        "tertiary-fixed-dim": "#ffb786",
        "surface-container-highest": "#e0e3e5",
        "on-background": "#191c1e",
        "on-tertiary-fixed-variant": "#723600",
        "on-error-container": "#93000a",
        "tertiary-container": "#b75b00",
        "surface-container-high": "#e6e8ea",
        "on-secondary": "#ffffff",
        "surface-container-low": "#f2f4f6",
        "tertiary-fixed": "#ffdcc6",
        "secondary-fixed-dim": "#b1c6f9",
        "surface-variant": "#e0e3e5",
        "on-surface": "#191c1e",
        "on-secondary-fixed": "#001a42",
        "surface-bright": "#f7f9fb",
        "secondary-container": "#b6ccff",
        "surface": "#f7f9fb",
        "on-secondary-fixed-variant": "#304671",
        "on-error": "#ffffff",
        "surface-tint": "#005ac2",
        "secondary": "#495e8a",
        "on-tertiary": "#ffffff",
        "background": "#f7f9fb",
        "error": "#ba1a1a",
        "on-primary-fixed": "#001a42",
        "primary-fixed-dim": "#adc6ff",
        "outline-variant": "#c2c6d6",
        "on-tertiary-container": "#fffbff",
        "on-surface-variant": "#424754",
        "inverse-surface": "#2d3133",
        "surface-container": "#eceef0",
        "primary-container": "#2170e4",
        "on-primary-fixed-variant": "#004395",
        "on-primary": "#ffffff",
        "inverse-primary": "#adc6ff",
        "on-secondary-container": "#405682",
        "error-container": "#ffdad6",
        "outline": "#727785",
        "primary-fixed": "#d8e2ff",
        "surface-container-lowest": "#ffffff",
        "on-primary-container": "#fefcff",
        "primary": "#0058be"
      },
      "borderRadius": {
        "DEFAULT": "0.25rem",
        "lg": "0.5rem",
        "xl": "0.75rem",
        "full": "9999px"
      },
      "fontFamily": {
        "headline": ["Manrope"],
        "body": ["Inter"],
        "label": ["Inter"]
      }
    },
  },
}
</script>
<style>
  .tfc-page-wrap { font-family: 'Inter', sans-serif; }
  .tfc-page-wrap h1, .tfc-page-wrap h2, .tfc-page-wrap h3, .tfc-page-wrap .font-headline { font-family: 'Manrope', sans-serif; }
  .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
  input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none; appearance: none;
    width: 20px; height: 20px;
    background: #0058be; border-radius: 50%; cursor: pointer;
  }
  /* status chip tones */
  .tfc-status-chip[data-tone="success"] { background:#dcfce7;color:#166534; }
  .tfc-status-chip[data-tone="danger"]  { background:#fee2e2;color:#991b1b; }
  .tfc-status-chip[data-tone="info"]    { background:#dbeafe;color:#1e40af; }
  /* tier table */
  .tfc-tier-row-invalid input { border: 1.5px solid #ba1a1a !important; }
  .tfc-field-error { color:#ba1a1a; font-size:0.75rem; margin-top:2px; min-height:1rem; }
  /* active tier row highlight */
  [data-tier-row].is-active { background: #eff6ff; }
  .tfc-coverage-stage {
    position: relative;
    min-height: 18rem;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.65);
    border-radius: 2rem;
    background:
      radial-gradient(circle at 22% 18%, rgba(94, 234, 212, 0.24), transparent 34%),
      radial-gradient(circle at 82% 78%, rgba(56, 189, 248, 0.2), transparent 30%),
      linear-gradient(135deg, #f5f8fd 0%, #d9e6f4 100%);
    box-shadow: 0 20px 48px rgba(15, 23, 42, 0.12);
    isolation: isolate;
  }
  .tfc-coverage-stage > img,
  .tfc-coverage-stage > .tw-absolute.tw-inset-0.tw-bg-gradient-to-t {
    display: none !important;
  }
  .tfc-coverage-map,
  .tfc-coverage-map .leaflet-container {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    background: transparent;
    font-family: 'Inter', sans-serif;
  }
  .tfc-coverage-map .leaflet-control-attribution {
    display: none;
  }
  .tfc-coverage-map .leaflet-top,
  .tfc-coverage-map .leaflet-right {
    z-index: 460;
  }
  .tfc-coverage-map .leaflet-control-zoom {
    margin: 0.85rem;
    border: 0;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.18);
  }
  .tfc-coverage-map .leaflet-control-zoom a {
    width: 2.15rem;
    height: 2.15rem;
    line-height: 2.05rem;
    border: 0;
    color: #17304f;
    background: rgba(255,255,255,0.94);
    backdrop-filter: blur(12px);
  }
  .tfc-coverage-map .leaflet-control-zoom a:hover {
    background: #ffffff;
    color: #0058be;
  }
  .tfc-coverage-stage__radius-chip {
    position: absolute;
    top: 0.85rem;
    right: 0.85rem;
    z-index: 455;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.7rem;
    border-radius: 999px;
    background: rgba(255,255,255,0.94);
    color: #17304f;
    font-size: 0.76rem;
    font-weight: 800;
    line-height: 1;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.16);
    backdrop-filter: blur(12px);
    pointer-events: none;
  }
  .tfc-coverage-stage__radius-chip .material-symbols-outlined {
    font-size: 1rem;
    color: #0058be;
  }
  .tfc-coverage-stage__veil {
    position: absolute;
    inset: 0;
    background:
      linear-gradient(180deg, rgba(248, 250, 252, 0.05) 0%, rgba(248, 250, 252, 0) 30%, rgba(15, 23, 42, 0.34) 100%),
      radial-gradient(circle at 50% 50%, rgba(255,255,255,0.08), transparent 60%);
    pointer-events: none;
    z-index: 360;
  }
  .tfc-coverage-stage__top,
  .tfc-coverage-stage__bottom {
    position: absolute;
    left: 1rem;
    right: 1rem;
    z-index: 420;
  }
  .tfc-coverage-stage__top,
  .tfc-coverage-stage__bottom,
  .tfc-coverage-stage__veil {
    display: none !important;
  }
  .tfc-coverage-stage__top {
    top: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.75rem;
  }
  .tfc-coverage-stage__badge,
  .tfc-coverage-stage__hint {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.65rem 0.9rem;
    border-radius: 999px;
    background: rgba(255,255,255,0.84);
    backdrop-filter: blur(14px);
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.16);
    color: #17304f;
  }
  .tfc-coverage-stage__badge {
    font-size: 0.76rem;
    font-weight: 800;
  }
  .tfc-coverage-stage__badge strong {
    font-size: 0.92rem;
    font-weight: 900;
    color: #0058be;
  }
  .tfc-coverage-stage__hint {
    max-width: 14rem;
    font-size: 0.7rem;
    font-weight: 700;
    line-height: 1.45;
  }
  .tfc-coverage-stage__bottom {
    bottom: 1rem;
    display: flex;
    align-items: flex-end;
    gap: 0.9rem;
  }
  .tfc-coverage-stage__pin {
    width: 3rem;
    height: 3rem;
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: linear-gradient(135deg, #0d6cf2 0%, #0058be 100%);
    color: #fff;
    box-shadow: 0 16px 30px rgba(0, 88, 190, 0.36);
  }
  .tfc-coverage-stage__copy {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.18rem;
    padding: 0.9rem 1rem;
    border-radius: 1.35rem;
    background: rgba(248,250,252,0.86);
    backdrop-filter: blur(14px);
    box-shadow: 0 14px 36px rgba(15, 23, 42, 0.18);
  }
  .tfc-coverage-stage__eyebrow {
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(23, 48, 79, 0.7);
  }
  .tfc-coverage-stage__address {
    font-family: 'Manrope', sans-serif;
    font-size: 0.96rem;
    font-weight: 800;
    line-height: 1.35;
    color: #0f172a;
  }
  .tfc-coverage-stage__meta {
    font-size: 0.74rem;
    font-weight: 700;
    color: rgba(15, 23, 42, 0.72);
  }
  .tfc-coverage-stage__fallback {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    text-align: center;
    font-size: 0.82rem;
    font-weight: 700;
    color: #17304f;
    background: linear-gradient(180deg, rgba(255,255,255,0.16), rgba(255,255,255,0.04));
    z-index: 300;
  }
  .tfc-coverage-map .leaflet-div-icon {
    background: transparent;
    border: 0;
  }
  .tfc-coverage-marker {
    position: relative;
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    border: 3px solid rgba(255,255,255,0.92);
    background: linear-gradient(135deg, #0d6cf2 0%, #0058be 100%);
    box-shadow: 0 0 0 10px rgba(0, 88, 190, 0.16), 0 12px 22px rgba(0, 88, 190, 0.28);
  }
  .tfc-coverage-marker::after {
    content: "";
    position: absolute;
    inset: 50% auto auto 50%;
    width: 56px;
    height: 56px;
    border-radius: 999px;
    border: 1px solid rgba(0, 88, 190, 0.28);
    transform: translate(-50%, -50%);
  }
  .tfc-coverage-marker__icon {
    font-size: 1.15rem;
    color: #ffffff;
    line-height: 1;
  }
  .tfc-coverage-map .tfc-coverage-tooltip {
    width: 13.5rem;
    max-width: 13.5rem;
    padding: 0.65rem 0.85rem;
    border: 0;
    border-radius: 1rem;
    background: rgba(15, 23, 42, 0.9);
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 700;
    line-height: 1.4;
    text-align: center;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.22);
    backdrop-filter: blur(12px);
    white-space: normal !important;
    word-break: normal;
    overflow-wrap: break-word;
  }
  .tfc-coverage-map .tfc-coverage-tooltip.leaflet-tooltip-top::before {
    border-top-color: rgba(15, 23, 42, 0.9);
  }
  @media (max-width: 768px) {
    .tfc-coverage-stage {
      min-height: 16rem;
    }
    .tfc-coverage-stage__top {
      flex-direction: column;
      align-items: flex-start;
    }
    .tfc-coverage-stage__hint {
      max-width: none;
    }
    .tfc-coverage-stage__bottom {
      left: 0.75rem;
      right: 0.75rem;
      gap: 0.75rem;
    }
    .tfc-coverage-stage__copy {
      padding: 0.8rem 0.9rem;
    }
  }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>
<div class="tfc-page-wrap tw-bg-surface tw-text-on-background tw-min-h-screen">
<form id="travelFeeForm" novalidate>
<main class="tw-flex-1 tw-overflow-y-auto tw-p-6 md:tw-p-10 tw-space-y-10 tw-max-w-[1600px] tw-mx-auto tw-w-full">
<!-- Alert Banner -->
<div class="tw-bg-primary-fixed tw-text-on-primary-fixed tw-p-5 tw-rounded-2xl tw-flex tw-items-start tw-gap-4">
<span class="material-symbols-outlined tw-text-primary" style="font-variation-settings: 'FILL' 1;">info</span>
<div>
<p class="tw-font-bold tw-text-sm">Lưu ý về thứ tự ưu tiên:</p>
<p class="tw-text-xs tw-opacity-80 tw-mt-1 tw-leading-relaxed">Hệ thống sẽ tự động áp dụng bậc phí thấp nhất khớp với khoảng cách thực tế. Hãy đảm bảo các khoảng cách không bị chồng lấn để kết quả tính toán chính xác nhất.</p>
</div>
</div>
<div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-12 tw-gap-8 tw-items-start">
<!-- Section 1 & 2: Main Settings -->
<div class="lg:tw-col-span-8 tw-space-y-8">
<!-- Section 1: Cấu hình cơ bản -->
<div class="tw-bg-surface-container-lowest tw-p-8 tw-rounded-3xl tw-shadow-sm">
<div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
<h3 class="tw-text-lg tw-font-bold tw-font-headline tw-flex tw-items-center tw-gap-2">
<span class="material-symbols-outlined tw-text-primary">charging_station</span>
                                Cấu hình cơ bản
                            </h3>
<div class="tw-flex tw-gap-3">
<button id="btnResetTravelFeeForm" class="tw-px-5 tw-py-2 tw-rounded-full tw-border tw-border-outline-variant tw-text-sm tw-font-semibold hover:tw-bg-surface-container-low tw-transition-colors tw-flex tw-items-center tw-gap-2">
<span class="material-symbols-outlined tw-text-sm">refresh</span>
                                    Làm mới
                                </button>
<button id="btnSaveTravelFee" form="travelFeeForm" type="submit" class="tw-px-6 tw-py-2 tw-rounded-full tw-bg-primary tw-text-white tw-text-sm tw-font-bold hover:tw-bg-primary-container tw-transition-all active:tw-scale-95 tw-shadow-lg tw-shadow-primary/20">
                                    Lưu thay đổi
                                </button>
</div>
</div>
<div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
<div class="tw-space-y-2">
<label class="tw-text-[10px] tw-font-bold tw-uppercase tw-tracking-widest tw-text-slate-500">Địa chỉ cửa hàng</label>
<div class="tw-relative group">
<span class="material-symbols-outlined tw-absolute tw-left-4 tw-top-1/2 tw--translate-y-1/2 tw-text-slate-400 group-focus-within:tw-text-primary tw-transition-colors">location_on</span>
<input class="tw-w-full tw-pl-12 tw-pr-4 tw-py-4 tw-bg-surface-container-low tw-border-none tw-rounded-2xl focus:tw-ring-2 focus:tw-ring-primary/20 focus:tw-bg-surface-container-lowest tw-transition-all tw-text-sm tw-font-medium" type="text" value="" id="travelFeeStoreAddress" placeholder="VD: 2 Đường Nguyễn Đình Chiểu, Vĩnh Thọ, Nha Trang"/>
</div>
<p class="tw-text-xs tw-text-slate-500">Hiá»‡n cho khÃ¡ch khi há»i Ä‘á»‹a chá»‰ cá»­a hÃ ng vÃ  link báº£n Ä‘á»“.</p>
<div class="tfc-field-error" data-error-for="store_address"></div>
</div>
<div class="tw-space-y-2">
<label class="tw-text-[10px] tw-font-bold tw-uppercase tw-tracking-widest tw-text-slate-500">VÄ© Ä‘á»™ cá»­a hÃ ng</label>
<div class="tw-relative group">
<span class="material-symbols-outlined tw-absolute tw-left-4 tw-top-1/2 tw--translate-y-1/2 tw-text-slate-400 group-focus-within:tw-text-primary tw-transition-colors">my_location</span>
<input class="tw-w-full tw-pl-12 tw-pr-4 tw-py-4 tw-bg-surface-container-low tw-border-none tw-rounded-2xl focus:tw-ring-2 focus:tw-ring-primary/20 focus:tw-bg-surface-container-lowest tw-transition-all tw-text-sm tw-font-medium" type="number" value="" id="travelFeeStoreLatitude" placeholder="VD: 12.2618" min="-90" max="90" step="0.000001"/>
</div>
<p class="tw-text-xs tw-text-slate-500">Nháº­p vÄ© Ä‘á»™ GPS tÆ°Æ¡ng á»©ng vá»›i Ä‘á»‹a chá»‰ cá»­a hÃ ng.</p>
<div class="tfc-field-error" data-error-for="store_latitude"></div>
</div>
<div class="tw-space-y-2">
<label class="tw-text-[10px] tw-font-bold tw-uppercase tw-tracking-widest tw-text-slate-500">Kinh Ä‘á»™ cá»­a hÃ ng</label>
<div class="tw-relative group">
<span class="material-symbols-outlined tw-absolute tw-left-4 tw-top-1/2 tw--translate-y-1/2 tw-text-slate-400 group-focus-within:tw-text-primary tw-transition-colors">pin_drop</span>
<input class="tw-w-full tw-pl-12 tw-pr-4 tw-py-4 tw-bg-surface-container-low tw-border-none tw-rounded-2xl focus:tw-ring-2 focus:tw-ring-primary/20 focus:tw-bg-surface-container-lowest tw-transition-all tw-text-sm tw-font-medium" type="number" value="" id="travelFeeStoreLongitude" placeholder="VD: 109.1995" min="-180" max="180" step="0.000001"/>
</div>
<p class="tw-text-xs tw-text-slate-500">Nháº­p kinh Ä‘á»™ GPS Ä‘á»ƒ há»‡ thá»‘ng tÃ­nh khoáº£ng cÃ¡ch tá»« cá»­a hÃ ng.</p>
<div class="tfc-field-error" data-error-for="store_longitude"></div>
</div>
<div class="tw-space-y-2">
<label class="tw-text-[10px] tw-font-bold tw-uppercase tw-tracking-widest tw-text-slate-500">Hotline cá»­a hÃ ng</label>
<div class="tw-relative group">
<span class="material-symbols-outlined tw-absolute tw-left-4 tw-top-1/2 tw--translate-y-1/2 tw-text-slate-400 group-focus-within:tw-text-primary tw-transition-colors">call</span>
<input class="tw-w-full tw-pl-12 tw-pr-4 tw-py-4 tw-bg-surface-container-low tw-border-none tw-rounded-2xl focus:tw-ring-2 focus:tw-ring-primary/20 focus:tw-bg-surface-container-lowest tw-transition-all tw-text-sm tw-font-medium" type="text" value="" id="travelFeeStoreHotline" placeholder="VD: 0905 123 456"/>
</div>
<p class="tw-text-xs tw-text-slate-500">Chatbot vÃ  trang khÃ¡ch sáº½ dÃ¹ng sá»‘ nÃ y khi há»i hotline.</p>
<div class="tfc-field-error" data-error-for="store_hotline"></div>
</div>
<div class="tw-space-y-3 md:tw-col-span-2">
<div class="tw-flex tw-items-center tw-justify-between tw-gap-4">
<div>
<label class="tw-text-[10px] tw-font-bold tw-uppercase tw-tracking-widest tw-text-slate-500">Khung giờ khách có thể đặt</label>
<p class="tw-text-xs tw-text-slate-500 tw-mt-1">Danh sách này sẽ hiển thị cho khách khi đặt đơn. Bạn có thể chỉnh sửa, thêm hoặc xóa từng khung giờ.</p>
</div>
<button id="btnAddBookingTimeSlot" type="button" class="tw-inline-flex tw-items-center tw-gap-2 tw-px-4 tw-py-2 tw-rounded-full tw-bg-primary/10 tw-text-primary tw-text-sm tw-font-bold hover:tw-bg-primary/15 tw-transition-colors">
<span class="material-symbols-outlined tw-text-base">add_circle</span>
Thêm khung giờ
</button>
</div>
<div id="travelFeeBookingSlotList" class="tw-space-y-3"></div>
<div class="tfc-field-error" data-error-for="booking_time_slots"></div>
</div>
<div class="tw-space-y-2">
<label class="tw-text-[10px] tw-font-bold tw-uppercase tw-tracking-widest tw-text-slate-500">Pháº¡m vi phá»¥c vá»¥ tá»‘i Ä‘a (km)</label>
<div class="tw-relative group">
<span class="material-symbols-outlined tw-absolute tw-left-4 tw-top-1/2 tw--translate-y-1/2 tw-text-slate-400 group-focus-within:tw-text-primary tw-transition-colors">straighten</span>
<input class="tw-w-full tw-pl-12 tw-pr-4 tw-py-4 tw-bg-surface-container-low tw-border-none tw-rounded-2xl focus:tw-ring-2 focus:tw-ring-primary/20 focus:tw-bg-surface-container-lowest tw-transition-all tw-text-sm tw-font-medium" type="number" id="travelFeeMaxServiceDistance" value="" placeholder="8" min="0" max="1000" step="0.1"/>
</div>
<p class="tw-text-xs tw-text-slate-500">Giá»›i háº¡n tá»‘i Ä‘a cho Ä‘Æ¡n sá»­a táº¡i nhÃ . Náº¿u Ä‘Ã£ chá»n thá»£, há»‡ thá»‘ng sáº½ láº¥y má»©c tháº¥p hÆ¡n giá»¯a báº¡n kÃ­nh thá»£ vÃ  má»©c nÃ y.</p>
<div class="tfc-field-error" data-error-for="max_service_distance_km"></div>
</div>
<div class="tw-space-y-2">
<label class="tw-text-[10px] tw-font-bold tw-uppercase tw-tracking-widest tw-text-slate-500">Thời hạn khiếu nại (Ngày)</label>
<div class="tw-relative group">
<span class="material-symbols-outlined tw-absolute tw-left-4 tw-top-1/2 tw--translate-y-1/2 tw-text-slate-400 group-focus-within:tw-text-primary tw-transition-colors">history</span>
<input class="tw-w-full tw-pl-12 tw-pr-4 tw-py-4 tw-bg-surface-container-low tw-border-none tw-rounded-2xl focus:tw-ring-2 focus:tw-ring-primary/20 focus:tw-bg-surface-container-lowest tw-transition-all tw-text-sm tw-font-medium" type="number" id="travelFeeComplaintWindowDays" value="" placeholder="3" min="1" max="30" step="1"/>
</div>
<p class="tw-text-xs tw-text-slate-500">DÃ¹ng cho quy táº¯c há»— trá»£ sau sá»­a chá»¯a vÃ  khiáº¿u náº¡i.</p>
<div class="tfc-field-error" data-error-for="complaint_window_days"></div>
</div>
</div>
</div>
<!-- Section 2: Bảng cấu hình khoảng cách -->
<div class="tw-bg-surface-container-lowest tw-p-8 tw-rounded-3xl tw-shadow-sm">
<div class="tw-flex tw-justify-between tw-items-center tw-mb-8">
<h3 class="tw-text-lg tw-font-bold tw-font-headline tw-flex tw-items-center tw-gap-2">
<span class="material-symbols-outlined tw-text-primary">distance</span>
                                Bảng cấu hình khoảng cách
                            </h3>
<button id="btnAddTravelTier" type="button" class="tw-flex tw-items-center tw-gap-2 tw-text-primary tw-font-bold tw-text-sm hover:tw-underline">
<span class="material-symbols-outlined tw-text-lg">add_circle</span>
                                Thêm khoảng mới
                            </button>
</div>
<div class="tw-overflow-hidden tw-rounded-2xl">
<table class="tw-w-full tw-text-left">
<thead>
<tr class="tw-bg-surface-container-low">
<th class="tw-px-6 tw-py-4 tw-text-[10px] tw-font-bold tw-uppercase tw-tracking-widest tw-text-slate-500">Từ km</th>
<th class="tw-px-6 tw-py-4 tw-text-[10px] tw-font-bold tw-uppercase tw-tracking-widest tw-text-slate-500">Đến km</th>
<th class="tw-px-6 tw-py-4 tw-text-[10px] tw-font-bold tw-uppercase tw-tracking-widest tw-text-slate-500">Phí thuê xe (VNĐ)</th>
<th class="tw-px-6 tw-py-4 tw-text-[10px] tw-font-bold tw-uppercase tw-tracking-widest tw-text-slate-500">Phí đi lại (VNĐ)</th>
<th class="tw-px-6 tw-py-4 tw-text-[10px] tw-font-bold tw-uppercase tw-tracking-widest tw-text-slate-500 tw-text-center">Thao tác</th>
</tr>
</thead>
<tbody class="tw-divide-y tw-divide-surface-container" id="travelTierList"></tbody>
</table>
</div>
</div>
<!-- Configuration Tips -->
<div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
<div class="tw-bg-surface-container-low tw-p-6 tw-rounded-3xl tw-border tw-border-white/50">
<h4 class="tw-font-bold tw-text-sm tw-mb-3 tw-flex tw-items-center tw-gap-2">
<span class="material-symbols-outlined tw-text-tertiary">lightbulb</span>
                                Gợi ý tối ưu phí
                            </h4>
<p class="tw-text-xs tw-text-slate-500 tw-leading-relaxed">Dựa trên dữ liệu 30 ngày qua, các đơn hàng trong khoảng 5-10km đang chiếm tỷ trọng 60%. Bạn có thể cân nhắc giảm phí đi lại ở bậc này để thu hút thêm khách hàng.</p>
</div>
<div class="tw-bg-surface-container-low tw-p-6 tw-rounded-3xl tw-border tw-border-white/50">
<h4 class="tw-font-bold tw-text-sm tw-mb-3 tw-flex tw-items-center tw-gap-2">
<span class="material-symbols-outlined tw-text-primary">trending_up</span>
                                Phân tích đối thủ
                            </h4>
<p class="tw-text-xs tw-text-slate-500 tw-leading-relaxed">Phí vận chuyển trung bình của khu vực cho bán kính 10km là 150.000 VNĐ. Cấu hình hiện tại của bạn đang cao hơn 10% so với thị trường.</p>
</div>
</div>
</div>
<!-- Section 3: Simulator (Sticky) -->
<aside class="lg:tw-col-span-4 tw-sticky tw-top-6">
<div class="tw-bg-primary tw-text-white tw-p-8 tw-rounded-[2rem] tw-shadow-2xl tw-shadow-primary/30 tw-relative tw-overflow-hidden">
<div class="tw-absolute tw-top-0 tw-right-0 tw-w-32 tw-h-32 tw-bg-white/10 tw-rounded-full tw--mr-10 tw--mt-10 tw-blur-2xl"></div>
<div class="tw-flex tw-justify-between tw-items-center tw-mb-8 tw-relative tw-z-10">
<h3 class="tw-font-headline tw-font-extrabold tw-text-xl">Mô phỏng</h3>
<div class="tw-flex tw-items-center tw-gap-2 tw-bg-white/20 tw-px-3 tw-py-1.5 tw-rounded-full tw-backdrop-blur-md">
<span class="tw-text-[10px] tw-font-bold tw-uppercase" id="travelFeeRangePreview">--</span>
<span class="tw-w-2 tw-h-2 tw-bg-green-400 tw-rounded-full tw-animate-pulse"></span>
</div>
</div>
<div class="tw-space-y-8 tw-relative tw-z-10">
<!-- Slider Section -->
<div class="tw-space-y-4">
<div class="tw-flex tw-justify-between tw-items-end">
<span class="tw-text-sm tw-font-medium tw-opacity-80">Khoảng cách di chuyển</span>
<span class="tw-text-3xl tw-font-headline tw-font-black" id="travelFeeDistanceBadge">3 <span class="tw-text-sm tw-font-normal">km</span></span>
</div>
<input class="tw-w-full tw-h-2 tw-bg-white/30 tw-rounded-lg tw-appearance-none tw-cursor-pointer" id="travelFeeDistanceSlider" max="30" min="0" step="0.1" type="range" value="3"/>
<input type="number" id="travelFeeDistanceNumber" min="0" step="0.1" value="3" style="display:none"/>
<div class="tw-grid tw-grid-cols-4 tw-gap-2">
<button data-preview-distance="1" class="tw-bg-white/10 hover:tw-bg-white/20 tw-py-2 tw-rounded-xl tw-text-[10px] tw-font-bold tw-transition-colors">1KM</button>
<button data-preview-distance="5" class="tw-bg-white/10 hover:tw-bg-white/20 tw-py-2 tw-rounded-xl tw-text-[10px] tw-font-bold tw-transition-colors">5KM</button>
<button data-preview-distance="10" class="tw-bg-white/10 hover:tw-bg-white/20 tw-py-2 tw-rounded-xl tw-text-[10px] tw-font-bold tw-transition-colors">10KM</button>
<button data-preview-distance="20" class="tw-bg-white/40 tw-py-2 tw-rounded-xl tw-text-[10px] tw-font-bold tw-shadow-sm">20KM</button>
</div>
</div>
<!-- Result Cards -->
<div class="tw-space-y-3 tw-pt-6 tw-border-t tw-border-white/10">
<div class="tw-flex tw-justify-between tw-items-center tw-p-4 tw-bg-white/10 tw-rounded-2xl tw-backdrop-blur-sm">
<div class="tw-flex tw-items-center tw-gap-3">
<span class="material-symbols-outlined tw-text-blue-200">commute</span>
<span class="tw-text-xs tw-font-semibold">Phí thuê xe</span>
</div>
<span class="tw-font-bold" id="travelFeeTransportPreview">0 đ</span>
</div>
<div class="tw-flex tw-justify-between tw-items-center tw-p-4 tw-bg-white/10 tw-rounded-2xl tw-backdrop-blur-sm">
<div class="tw-flex tw-items-center tw-gap-3">
<span class="material-symbols-outlined tw-text-blue-200">route</span>
<span class="tw-text-xs tw-font-semibold">Phí đi lại</span>
</div>
<span class="tw-font-bold" id="travelFeeTravelPreview">0 đ</span>
</div>
<div class="tw-flex tw-justify-between tw-items-center tw-p-5 tw-bg-white tw-rounded-[1.5rem] tw-mt-4">
<div class="tw-flex tw-flex-col">
<span class="tw-text-[10px] tw-font-black tw-uppercase tw-text-primary tw-tracking-widest" id="travelFeeActiveRuleLabel">Tổng phí dự kiến</span>
<span class="tw-text-2xl tw-font-headline tw-font-black tw-text-on-background" id="travelFeeActivePrice">0 đ</span>
</div>
<span class="material-symbols-outlined tw-text-primary tw-text-3xl" style="font-variation-settings: 'FILL' 1;">account_balance_wallet</span>
</div>
</div>
<!-- Toggle View -->
<div class="tw-flex tw-items-center tw-justify-between tw-pt-4">
<span class="tw-text-xs tw-font-medium tw-opacity-80">Chế độ xem kết quả</span>
<div class="tw-flex tw-bg-white/10 tw-p-1 tw-rounded-full">
<button data-preview-mode="travel_fee" class="tw-px-3 tw-py-1 tw-text-[10px] tw-font-bold tw-bg-white tw-text-primary tw-rounded-full">Phí</button>
<button data-preview-mode="tiered" class="tw-px-3 tw-py-1 tw-text-[10px] tw-font-bold tw-text-white tw-opacity-60">Bậc</button>
</div>
</div>
</div>
</div>
<!-- Map/Visual Insight -->
<div class="tw-mt-6 tfc-coverage-stage">
<div id="travelFeeCoverageMap" class="tfc-coverage-map" aria-label="Bản đồ phạm vi phục vụ cửa hàng"></div>
<div class="tfc-coverage-stage__radius-chip">
<span class="material-symbols-outlined" aria-hidden="true">straighten</span>
<span id="travelFeeCoverageRadius">8 km</span>
</div>
<div class="tfc-coverage-stage__fallback" id="travelFeeCoverageFallback">Dang tai ban do vung phuc vu...</div>
<div class="tfc-coverage-stage__veil"></div>
<div class="tfc-coverage-stage__top">
<div class="tfc-coverage-stage__badge">
<span class="material-symbols-outlined tw-text-primary">radio_button_checked</span>
<span>Ban kinh hien tai <strong>8 km</strong></span>
</div>
<div class="tfc-coverage-stage__hint" id="travelFeeCoverageHint">Tam ban do dang bam vao toa do cua hang trong cau hinh admin.</div>
</div>
<div class="tfc-coverage-stage__bottom">
<div class="tfc-coverage-stage__pin">
<span class="material-symbols-outlined">my_location</span>
</div>
<div class="tfc-coverage-stage__copy">
<span class="tfc-coverage-stage__eyebrow">Vung phu song hien tai</span>
<span class="tfc-coverage-stage__address" id="travelFeeCoverageAddress">Dia chi cua hang</span>
<span class="tfc-coverage-stage__meta" id="travelFeeCoverageCoordinates">Lat --, Lng --</span>
</div>
</div>
<img alt="Map Visualization" class="tw-w-full tw-h-full tw-object-cover tw-opacity-60 group-hover:tw-scale-110 tw-transition-transform tw-duration-700" data-alt="Abstract aerial city map layout with blue glowing transit lines and minimalist design, soft focus, high tech dashboard style" data-location="Hanoi" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCTOIzBBOFpRE7cN-ibbygV6k-0p57AcsDXVq0wIGlIcs2A7jo4enLogdiyCktXNn4lCqPQrkbYtnHyIYDnM9psATIJazWUz6oKdFaCGB7mqujTocysM4ccwI7tcA0nMqY5vLKywQGDJxSOOsbkchBxoBUf3Vz5U6Y4MCCIPmk_NRI6xcjIjXiCelDrB0RbVgU6uxFK6Crl99WVEiLT0w4Z5Sazd_NpslGsLkx5-qHrk64bLy1iyurFr69uHa2qoUnks355cOUXVzo"/>
<div class="tw-absolute tw-inset-0 tw-bg-gradient-to-t tw-from-surface-container-highest/80 tw-to-transparent tw-flex tw-items-end tw-p-6">
<div class="tw-flex tw-items-center tw-gap-3">
<div class="tw-w-8 tw-h-8 tw-rounded-full tw-bg-primary tw-flex tw-items-center tw-justify-center tw-text-white">
<span class="material-symbols-outlined tw-text-sm">my_location</span>
</div>
<span class="tw-text-xs tw-font-bold tw-text-on-surface">Vùng phủ sóng hiện tại: Đống Đa, Hà Nội</span>
</div>
</div>
</div>
</aside>
</div>
</form>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script type="module" src="{{ asset('assets/js/admin/travel-fee-config.js') }}"></script>
@endpush
