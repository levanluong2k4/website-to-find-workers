
import re

with open("stitch_travel3.html", "r", encoding="utf-8") as f:
    text = f.read()

# Extract main content between <!-- Main Content Canvas --> and </main>
match_main = re.search(r'<!-- Main Content Canvas -->(.*?)</main>', text, re.DOTALL)
main_content = match_main.group(1).strip() if match_main else ""

# Remove the static sidebar/info rows from the table tbody (they're placeholders)
# Replace static tbody rows with empty tbody that JS will populate
main_content = re.sub(
    r'<tbody class="tw-divide-y tw-divide-surface-container">.*?</tbody>',
    '<tbody class="tw-divide-y tw-divide-surface-container" id="travelTierList"></tbody>',
    main_content,
    flags=re.DOTALL
)

# Add IDs for JS hooks - store address input
main_content = main_content.replace(
    'type="text" value="123 Đường Láng, Đống Đa, Hà Nội"',
    'type="text" value="" id="travelFeeStoreAddress" placeholder="VD: 2 Đường Nguyễn Đình Chiểu, Vĩnh Thọ, Nha Trang"'
)

# Complaint window input
main_content = main_content.replace(
    'type="number" value="7"',
    'type="number" id="travelFeeComplaintWindowDays" value="" placeholder="3" min="1" max="30" step="1"'
)

# Add id to reset button (Làm mới)
main_content = main_content.replace(
    'class="tw-px-5 tw-py-2 tw-rounded-full tw-border tw-border-outline-variant tw-text-sm tw-font-semibold hover:tw-bg-surface-container-low tw-transition-colors tw-flex tw-items-center tw-gap-2">\n<span class="material-symbols-outlined tw-text-sm">refresh</span>\n                                    Làm mới',
    'id="btnResetTravelFeeForm" class="tw-px-5 tw-py-2 tw-rounded-full tw-border tw-border-outline-variant tw-text-sm tw-font-semibold hover:tw-bg-surface-container-low tw-transition-colors tw-flex tw-items-center tw-gap-2">\n<span class="material-symbols-outlined tw-text-sm">refresh</span>\n                                    Làm mới'
)

# Add id to save button
main_content = main_content.replace(
    'class="tw-px-6 tw-py-2 tw-rounded-full tw-bg-primary tw-text-white tw-text-sm tw-font-bold hover:tw-bg-primary-container tw-transition-all active:tw-scale-95 tw-shadow-lg tw-shadow-primary/20">\n                                    Lưu thay đổi',
    'id="btnSaveTravelFee" form="travelFeeForm" type="submit" class="tw-px-6 tw-py-2 tw-rounded-full tw-bg-primary tw-text-white tw-text-sm tw-font-bold hover:tw-bg-primary-container tw-transition-all active:tw-scale-95 tw-shadow-lg tw-shadow-primary/20">\n                                    Lưu thay đổi'
)

# Add id to add tier button
main_content = main_content.replace(
    'class="tw-flex tw-items-center tw-gap-2 tw-text-primary tw-font-bold tw-text-sm hover:tw-underline">',
    'id="btnAddTravelTier" type="button" class="tw-flex tw-items-center tw-gap-2 tw-text-primary tw-font-bold tw-text-sm hover:tw-underline">'
)

# Replace simulator slider content
main_content = main_content.replace(
    'max="30" min="0" type="range" value="18.5"/',
    'id="travelFeeDistanceSlider" max="30" min="0" step="0.1" type="range" value="3"/'
)

# Replace static distance display
main_content = main_content.replace(
    '<span class="tw-text-3xl tw-font-headline tw-font-black">18.5 <span class="tw-text-sm tw-font-normal">km</span></span>',
    '<span class="tw-text-3xl tw-font-headline tw-font-black" id="travelFeeDistanceBadge">3 <span class="tw-text-sm tw-font-normal">km</span></span>'
)

# Replace static transport fee
main_content = main_content.replace(
    '<span class="tw-font-bold">250.000đ</span>\n</div>\n<div class="tw-flex tw-justify-between tw-items-center tw-p-4 tw-bg-white/10',
    '<span class="tw-font-bold" id="travelFeeTransportPreview">0 đ</span>\n</div>\n<div class="tw-flex tw-justify-between tw-items-center tw-p-4 tw-bg-white/10'
)

# Replace static travel fee
main_content = main_content.replace(
    '<span class="tw-font-bold">80.000đ</span>',
    '<span class="tw-font-bold" id="travelFeeTravelPreview">0 đ</span>'
)

# Replace total fee
main_content = main_content.replace(
    '<span class="tw-text-2xl tw-font-headline tw-font-black tw-text-on-background">330.000đ</span>',
    '<span class="tw-text-2xl tw-font-headline tw-font-black tw-text-on-background" id="travelFeeActivePrice">0 đ</span>'
)

# Replace tier badge 
main_content = main_content.replace(
    '<span class="tw-text-[10px] tw-font-bold tw-uppercase">Bậc 3</span>',
    '<span class="tw-text-[10px] tw-font-bold tw-uppercase" id="travelFeeRangePreview">--</span>'
)

# Status chip
main_content = main_content.replace(
    '<span class="tw-text-[10px] tw-font-black tw-uppercase tw-text-primary tw-tracking-widest">Tổng phí dự kiến</span>',
    '<span class="tw-text-[10px] tw-font-black tw-uppercase tw-text-primary tw-tracking-widest" id="travelFeeActiveRuleLabel">Tổng phí dự kiến</span>'
)

# Mode toggle buttons
main_content = main_content.replace(
    '<button class="tw-px-3 tw-py-1 tw-text-[10px] tw-font-bold tw-bg-white tw-text-primary tw-rounded-full">Phí</button>',
    '<button data-preview-mode="travel_fee" class="tw-px-3 tw-py-1 tw-text-[10px] tw-font-bold tw-bg-white tw-text-primary tw-rounded-full">Phí</button>'
)
main_content = main_content.replace(
    '<button class="tw-px-3 tw-py-1 tw-text-[10px] tw-font-bold tw-text-white tw-opacity-60">Bậc</button>',
    '<button data-preview-mode="tiered" class="tw-px-3 tw-py-1 tw-text-[10px] tw-font-bold tw-text-white tw-opacity-60">Bậc</button>'
)

# Quick action buttons  
main_content = main_content.replace(
    '<button class="tw-bg-white/10 hover:tw-bg-white/20 tw-py-2 tw-rounded-xl tw-text-[10px] tw-font-bold tw-transition-colors">1KM</button>',
    '<button data-preview-distance="1" class="tw-bg-white/10 hover:tw-bg-white/20 tw-py-2 tw-rounded-xl tw-text-[10px] tw-font-bold tw-transition-colors">1KM</button>'
)
main_content = main_content.replace(
    '<button class="tw-bg-white/10 hover:tw-bg-white/20 tw-py-2 tw-rounded-xl tw-text-[10px] tw-font-bold tw-transition-colors">5KM</button>',
    '<button data-preview-distance="5" class="tw-bg-white/10 hover:tw-bg-white/20 tw-py-2 tw-rounded-xl tw-text-[10px] tw-font-bold tw-transition-colors">5KM</button>'
)
main_content = main_content.replace(
    '<button class="tw-bg-white/10 hover:tw-bg-white/20 tw-py-2 tw-rounded-xl tw-text-[10px] tw-font-bold tw-transition-colors">10KM</button>',
    '<button data-preview-distance="10" class="tw-bg-white/10 hover:tw-bg-white/20 tw-py-2 tw-rounded-xl tw-text-[10px] tw-font-bold tw-transition-colors">10KM</button>'
)
main_content = main_content.replace(
    '<button class="tw-bg-white/40 tw-py-2 tw-rounded-xl tw-text-[10px] tw-font-bold tw-shadow-sm">20KM</button>',
    '<button data-preview-distance="20" class="tw-bg-white/40 tw-py-2 tw-rounded-xl tw-text-[10px] tw-font-bold tw-shadow-sm">20KM</button>'
)

blade = """@extends('layouts.app')

@section('title', 'Phí đi lại - Admin')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
</style>
@endpush

@section('content')
<app-navbar></app-navbar>
<div class="tfc-page-wrap tw-bg-surface tw-text-on-background tw-min-h-screen">
<form id="travelFeeForm" novalidate>
""" + main_content + """
</form>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/travel-fee-config.js') }}"></script>
<script>
// Wire status chip id
document.addEventListener('DOMContentLoaded', () => {
  // Add hidden status chip for JS hooks
  const chip = document.createElement('span');
  chip.id = 'travelFeeStatusChip';
  chip.className = 'tfc-status-chip tw-hidden';
  chip.dataset.tone = 'info';
  document.body.appendChild(chip);

  const updatedChip = document.createElement('span');
  updatedChip.id = 'travelFeeUpdatedChip';
  updatedChip.className = 'd-none';
  document.body.appendChild(updatedChip);

  const modeChip = document.createElement('span');
  modeChip.id = 'travelFeeModeChip';
  modeChip.className = 'tw-hidden';
  document.body.appendChild(modeChip);

  // Wire storeAddress preview placeholder
  const storeAddrPreview = document.getElementById('travelFeeStoreAddressPreview');
  if (!storeAddrPreview) {
    const el = document.createElement('span');
    el.id = 'travelFeeStoreAddressPreview';
    el.className = 'tw-hidden';
    document.body.appendChild(el);
  }

  // activeRuleCopy, rulePreview, sampleGrid placeholders  
  ['travelFeeActiveRuleCopy','travelFeeActiveTransportMeta','travelFeeRulePreview','travelFeeSampleGrid'].forEach(id => {
    if (!document.getElementById(id)) {
      const el = document.createElement('div');
      el.id = id;
      el.className = 'tw-hidden';
      document.body.appendChild(el);
    }
  });
});
</script>
@endpush
"""

with open("result_blade.php", "w", encoding="utf-8") as f:
    f.write(blade)

print("Done!")
