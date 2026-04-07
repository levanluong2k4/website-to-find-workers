@extends('layouts.app')

@section('title', 'Cập nhật giá - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800;900&family=Material+Symbols+Outlined" rel="stylesheet"/>
<style>
  :root {
    --pricing-bg: #dcecf3;
    --pricing-surface: #f8fafb;
    --pricing-panel: #f0f4f6;
    --pricing-stroke: rgba(169, 180, 183, 0.18);
    --pricing-text: #2a3437;
    --pricing-muted: #566164;
    --pricing-soft: #a9b4b7;
    --pricing-primary: #2171cc;
    --pricing-primary-soft: #d4e4f8;
    --pricing-ready: #dcfce7;
    --pricing-ready-text: #166534;
    --pricing-warning: #ffedd5;
    --pricing-warning-text: #c2410c;
    --pricing-danger: #ef4444;
    --pricing-danger-soft: rgba(239, 68, 68, 0.08);
  }

  .pricing-page {
    display: flex;
    min-height: 100vh;
    background:
      radial-gradient(circle at top left, rgba(255, 255, 255, 0.7), transparent 28rem),
      linear-gradient(180deg, #dcecf3 0%, #e9f3f7 100%);
  }

  .pricing-main {
    margin-left: 240px;
    min-height: 100vh;
    width: calc(100% - 240px);
    padding: 28px;
  }

  .pricing-shell {
    max-width: 1240px;
    margin: 0 auto;
  }

  .pricing-card {
    background: var(--pricing-surface);
    border-radius: 8px;
    box-shadow: 0 32px 64px -12px rgba(42, 52, 55, 0.15);
    overflow: hidden;
    min-height: calc(100vh - 56px);
  }

  .pricing-card,
  .pricing-card input,
  .pricing-card button,
  .pricing-card textarea,
  .pricing-card select {
    font-family: 'Public Sans', sans-serif;
  }

  .pricing-header {
    position: relative;
    padding: 32px;
    border-bottom: 1px solid rgba(169, 180, 183, 0.1);
  }

  .pricing-header-copy {
    max-width: 700px;
  }

  .pricing-eyebrow {
    color: #516071;
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 0.2em;
    text-transform: uppercase;
  }

  .pricing-title {
    margin: 6px 0 0;
    color: var(--pricing-text);
    font-size: 24px;
    font-weight: 700;
    line-height: 32px;
  }

  .pricing-subtitle {
    margin: 10px 0 0;
    max-width: 672px;
    color: var(--pricing-muted);
    font-size: 14px;
    line-height: 20px;
  }

  .pricing-header-meta {
    position: absolute;
    top: 32px;
    right: 72px;
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 8px;
    max-width: 420px;
  }

  .pricing-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    min-height: 25px;
    padding: 4px 8px;
    border-radius: 2px;
    background: var(--pricing-primary);
    color: #ffffff;
    font-size: 11px;
    font-weight: 600;
    line-height: 16.5px;
    white-space: nowrap;
  }

  .pricing-chip .material-symbols-outlined {
    font-size: 12px;
  }

  .pricing-close {
    position: absolute;
    top: 18px;
    right: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 12px;
    color: var(--pricing-text);
    text-decoration: none;
    transition: background 0.18s ease;
  }

  .pricing-close:hover {
    background: rgba(42, 52, 55, 0.06);
    color: var(--pricing-text);
  }

  .pricing-banner {
    margin-top: 24px;
    width: min(100%, 700px);
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    border-radius: 4px;
    background: var(--pricing-panel);
  }

  .pricing-banner-item,
  .pricing-banner-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 20px;
    padding-right: 16px;
    margin-right: 16px;
    border-right: 1px solid rgba(169, 180, 183, 0.2);
    color: var(--pricing-text);
    font-size: 14px;
    font-weight: 500;
    line-height: 20px;
    white-space: nowrap;
  }

  .pricing-banner-item:last-child {
    margin-right: 0;
  }

  .pricing-banner-code {
    color: var(--pricing-muted);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
  }

  .pricing-banner-item .material-symbols-outlined {
    font-size: 12px;
  }

  .pricing-banner-status {
    border-right: 0;
    margin-right: 0;
    padding: 2px 8px;
    border-radius: 2px;
    background: var(--pricing-primary-soft);
    color: #445364;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .pricing-body {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 360px;
    border-top: 1px solid rgba(169, 180, 183, 0.1);
  }

  .pricing-editor {
    padding: 32px;
    min-width: 0;
  }

  .pricing-summary {
    border-left: 1px solid rgba(169, 180, 183, 0.1);
    background: rgba(240, 244, 246, 0.5);
    padding: 32px 28px 32px 33px;
    display: flex;
    flex-direction: column;
  }

  .pricing-flow {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-bottom: 32px;
  }

  .pricing-flow-step {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--pricing-muted);
    font-size: 14px;
    font-weight: 600;
  }

  .pricing-flow-step.is-active {
    color: var(--pricing-text);
  }

  .pricing-flow-index {
    width: 24px;
    height: 24px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--pricing-soft);
    border-radius: 999px;
    font-size: 10px;
    font-weight: 600;
  }

  .pricing-flow-step.is-active .pricing-flow-index {
    background: var(--pricing-text);
    border-color: var(--pricing-text);
    color: #ffffff;
  }

  .pricing-flow-divider {
    width: 48px;
    height: 1px;
    background: rgba(169, 180, 183, 0.3);
  }

  .pricing-section {
    margin-bottom: 32px;
  }

  .pricing-section:last-child {
    margin-bottom: 0;
  }

  .pricing-section-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
  }

  .pricing-section-badge {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .pricing-section-number {
    color: rgba(169, 180, 183, 0.2);
    font-size: 30px;
    font-weight: 900;
    letter-spacing: -0.05em;
    line-height: 36px;
  }

  .pricing-section-label {
    color: var(--pricing-text);
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.025em;
    line-height: 20px;
    text-transform: uppercase;
  }

  .pricing-section-count {
    display: inline-flex;
    min-height: 16px;
    padding: 0 6px;
    border-radius: 2px;
    background: #d9e4e8;
    color: var(--pricing-muted);
    font-size: 10px;
    font-weight: 600;
    line-height: 15px;
  }

  .pricing-inline-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border: 0;
    border-radius: 4px;
    background: transparent;
    color: var(--pricing-primary);
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
  }

  .pricing-inline-action:hover {
    background: rgba(33, 113, 204, 0.06);
  }

  .pricing-labor-list,
  .pricing-part-list {
    display: grid;
    gap: 12px;
  }

  .pricing-line-item {
    background: var(--pricing-panel);
    border-left: 4px solid #516071;
    border-radius: 4px;
    padding: 16px 20px;
  }

  .pricing-labor-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 128px 24px;
    gap: 16px;
    align-items: center;
  }

  .pricing-part-row {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) 110px 100px 110px 20px;
    gap: 24px;
    align-items: start;
  }

  .pricing-field {
    display: grid;
    gap: 4px;
    min-width: 0;
  }

  .pricing-field-label {
    color: var(--pricing-muted);
    font-size: 10px;
    font-weight: 600;
    line-height: 15px;
    text-transform: uppercase;
  }

  .pricing-input,
  .pricing-select {
    width: 100%;
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--pricing-text);
    font-size: 14px;
    font-weight: 500;
    line-height: 20px;
    outline: none;
  }

  .pricing-input::placeholder {
    color: #6b7280;
  }

  .pricing-input--price {
    text-align: right;
    font-weight: 600;
  }

  .pricing-part-title {
    font-weight: 600;
  }

  .pricing-part-meta {
    color: var(--pricing-muted);
    font-size: 11px;
    line-height: 16px;
  }

  .pricing-remove {
    width: 20px;
    height: 20px;
    border: 0;
    background: transparent;
    color: var(--pricing-soft);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
  }

  .pricing-remove:hover {
    color: var(--pricing-muted);
  }

  .pricing-stepper {
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .pricing-stepper-btn {
    width: 24px;
    height: 24px;
    border: 0;
    border-radius: 4px;
    background: #ffffff;
    color: var(--pricing-text);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
  }

  .pricing-stepper-btn:hover {
    background: rgba(33, 113, 204, 0.08);
    color: var(--pricing-primary);
  }

  .pricing-stepper .pricing-input {
    width: 24px;
    text-align: center;
    font-weight: 600;
  }

  .pricing-search-row {
    display: block;
  }

  .pricing-search-wrap {
    position: relative;
  }

  .pricing-searchbox {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 44px;
    padding: 9px 16px 10px 40px;
    border-radius: 4px;
    background: var(--pricing-panel);
  }

  .pricing-searchbox:focus-within {
    box-shadow: 0 0 0 1px rgba(33, 113, 204, 0.18);
  }

  .pricing-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-size: 12px;
  }

  .pricing-search-input {
    width: 100%;
    border: 0;
    background: transparent;
    color: #6b7280;
    font-size: 14px;
    outline: none;
  }

  .pricing-catalog-status {
    margin-top: 8px;
    color: var(--pricing-muted);
    font-size: 12px;
    line-height: 18px;
  }

  .pricing-catalog-suggestions {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    z-index: 10;
    display: grid;
    gap: 8px;
    max-height: 300px;
    overflow: auto;
    padding: 10px;
    border: 1px solid rgba(169, 180, 183, 0.2);
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 24px 32px rgba(42, 52, 55, 0.12);
  }

  .pricing-suggestion,
  .pricing-catalog-option {
    width: 100%;
    display: grid;
    align-items: center;
    gap: 12px;
    border: 1px solid transparent;
    border-radius: 12px;
    background: #ffffff;
    color: inherit;
    text-align: left;
    transition: 0.18s ease;
  }

  .pricing-suggestion {
    grid-template-columns: 48px minmax(0, 1fr) auto;
    padding: 12px;
  }

  .pricing-suggestion:hover,
  .pricing-suggestion.is-active,
  .pricing-catalog-option:hover {
    border-color: rgba(33, 113, 204, 0.22);
    background: rgba(212, 228, 248, 0.26);
  }

  .pricing-suggestion.is-selected,
  .pricing-catalog-option.is-selected {
    border-color: rgba(33, 113, 204, 0.3);
    background: rgba(212, 228, 248, 0.42);
  }

  .pricing-thumb {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #eef3f6;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--pricing-soft);
  }

  .pricing-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .pricing-suggestion-title,
  .pricing-catalog-title {
    color: var(--pricing-text);
    font-size: 13px;
    font-weight: 700;
    line-height: 18px;
  }

  .pricing-suggestion-meta,
  .pricing-catalog-meta {
    color: var(--pricing-muted);
    font-size: 11px;
    line-height: 16px;
  }

  .pricing-suggestion-price,
  .pricing-catalog-price {
    color: var(--pricing-text);
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
  }

  .pricing-suggestion-badge {
    display: inline-flex;
    align-items: center;
    min-height: 18px;
    padding: 0 8px;
    border-radius: 999px;
    background: rgba(33, 113, 204, 0.12);
    color: var(--pricing-primary);
    font-size: 10px;
    font-weight: 700;
  }

  .pricing-catalog-empty {
    padding: 12px;
    border-radius: 12px;
    background: #ffffff;
    color: var(--pricing-muted);
    font-size: 12px;
    line-height: 18px;
  }

  .pricing-catalog-option {
    grid-template-columns: auto 48px minmax(0, 1fr) auto;
    padding: 12px 14px;
  }

  .pricing-catalog-option input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: var(--pricing-primary);
  }

  .pricing-fees {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }

  .pricing-fee-card {
    padding: 16px;
    border-radius: 4px;
    background: var(--pricing-panel);
  }

  .pricing-fee-title {
    margin: 0 0 8px;
    color: var(--pricing-muted);
    font-size: 10px;
    font-weight: 600;
    line-height: 15px;
    text-transform: uppercase;
  }

  .pricing-fee-input-wrap {
    position: relative;
  }

  .pricing-fee-input {
    width: 100%;
    padding: 0 16px 0 0;
    border: 0;
    background: transparent;
    color: var(--pricing-text);
    font-size: 14px;
    font-weight: 500;
    line-height: 20px;
    outline: none;
  }

  .pricing-fee-prefix {
    position: absolute;
    top: 50%;
    right: 0;
    transform: translateY(-50%);
    color: var(--pricing-muted);
    font-size: 12px;
  }

  .pricing-fee-readonly {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .pricing-fee-value {
    color: var(--pricing-text);
    font-size: 14px;
    font-weight: 700;
    line-height: 20px;
  }

  .pricing-fee-pill {
    display: inline-flex;
    align-items: center;
    min-height: 18px;
    padding: 0 8px;
    border-radius: 999px;
    background: #cfe6f2;
    color: #40555f;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
  }

  .pricing-fee-hint {
    margin-top: 8px;
    color: var(--pricing-muted);
    font-size: 11px;
    line-height: 16px;
  }

  .pricing-summary-eyebrow {
    color: var(--pricing-muted);
    font-size: 9px;
    font-weight: 900;
    letter-spacing: 0.2em;
    line-height: 13.5px;
    text-transform: uppercase;
  }

  .pricing-summary-title {
    margin: 4px 0 0;
    color: var(--pricing-text);
    font-size: 20px;
    font-weight: 700;
    line-height: 28px;
  }

  .pricing-summary-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: fit-content;
    margin-top: 16px;
    padding: 4px 12px;
    border-radius: 12px;
    background: var(--pricing-ready);
    color: var(--pricing-ready-text);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }

  .pricing-summary-status.is-attention {
    background: var(--pricing-warning);
    color: var(--pricing-warning-text);
  }

  .pricing-summary-list {
    display: grid;
    gap: 16px;
    padding-top: 28px;
  }

  .pricing-summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    color: var(--pricing-muted);
    font-size: 14px;
    line-height: 20px;
  }

  .pricing-summary-row strong {
    color: var(--pricing-text);
    font-weight: 700;
  }

  .pricing-total-card {
    position: relative;
    margin-top: 32px;
    padding: 24px;
    border-radius: 8px;
    background: var(--pricing-primary);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .pricing-total-card::after {
    content: "";
    position: absolute;
    right: -16px;
    bottom: -16px;
    width: 90px;
    height: 100px;
    opacity: 0.6;
    background:
      linear-gradient(0deg, rgba(255,255,255,0.08), rgba(255,255,255,0.08)),
      linear-gradient(90deg, transparent 0 22%, rgba(255,255,255,0.08) 22% 30%, transparent 30% 100%);
    border-radius: 8px;
  }

  .pricing-total-label {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    color: rgba(255,255,255,0.75);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
  }

  .pricing-total-label .material-symbols-outlined {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    background: rgba(255,255,255,0.18);
    color: #ffffff;
    font-size: 14px;
  }

  .pricing-total-value {
    margin-top: 8px;
    color: #ffffff;
    font-size: 36px;
    font-weight: 900;
    line-height: 40px;
    letter-spacing: -0.03em;
  }

  .pricing-total-hint {
    margin-top: 8px;
    color: rgba(255,255,255,0.78);
    font-size: 12px;
    line-height: 18px;
    max-width: 240px;
  }

  .pricing-summary-footer {
    margin-top: auto;
    padding-top: 32px;
    border-top: 1px solid rgba(169, 180, 183, 0.1);
    display: flex;
    justify-content: flex-end;
    gap: 16px;
  }

  .pricing-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 0 24px;
    border: 0;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
  }

  .pricing-btn--ghost {
    background: rgba(153, 153, 153, 0.16);
    color: #2d383b;
  }

  .pricing-btn--primary {
    background: var(--pricing-primary);
    color: #ffffff;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
  }

  .pricing-btn[disabled] {
    opacity: 0.6;
    pointer-events: none;
  }

  .pricing-page-loading,
  .pricing-page-error {
    padding: 48px 32px;
    color: var(--pricing-muted);
    font-size: 14px;
  }

  .pricing-page-error {
    color: #b91c1c;
  }

  @media (max-width: 1199.98px) {
    .pricing-header-meta {
      position: static;
      max-width: none;
      margin-top: 16px;
      justify-content: flex-start;
    }

    .pricing-banner {
      width: 100%;
    }

    .pricing-body {
      grid-template-columns: 1fr;
    }

    .pricing-summary {
      border-left: 0;
      border-top: 1px solid rgba(169, 180, 183, 0.1);
    }
  }

  @media (max-width: 991.98px) {
    .pricing-main {
      margin-left: 0;
      width: 100%;
      padding: 18px;
    }

    .pricing-card {
      min-height: auto;
    }

    .pricing-header,
    .pricing-editor,
    .pricing-summary {
      padding: 20px;
    }

    .pricing-labor-row,
    .pricing-part-row,
    .pricing-fees {
      grid-template-columns: 1fr;
    }

    .pricing-summary-footer {
      flex-direction: column;
    }

    .pricing-btn {
      width: 100%;
    }

    .pricing-flow {
      flex-wrap: wrap;
      gap: 10px 14px;
    }

    .pricing-flow-divider {
      display: none;
    }

    .pricing-banner {
      flex-wrap: wrap;
      gap: 8px;
    }

    .pricing-banner-item,
    .pricing-banner-status {
      border-right: 0;
      margin-right: 0;
      padding-right: 0;
    }
  }
</style>
@endpush

@section('content')
<div class="pricing-page" id="pricingPage" data-booking-id="{{ $bookingId }}" data-base-url="{{ url('/') }}">
  <x-worker-sidebar />

  <main class="pricing-main">
    <div class="pricing-shell">
      <form id="pricingEditorForm" class="pricing-card">
        <input type="hidden" id="costBookingId" value="{{ $bookingId }}">
        <input type="hidden" id="inputGhiChuLinhKien" value="">

        <header class="pricing-header">
          <div class="pricing-header-copy">
            <div class="pricing-eyebrow">Pricing Desk</div>
            <h1 class="pricing-title">Cập nhật bảng giá sửa chữa</h1>
            <p class="pricing-subtitle">Điền rõ từng hạng mục để khách dễ kiểm tra, còn bạn dễ rà soát tổng tiền trước khi gửi yêu cầu thanh toán.</p>
          </div>

          <div class="pricing-header-meta">
            <div class="pricing-chip" id="costServiceModeBadge">
              <span class="material-symbols-outlined">home_repair_service</span>
              <span>Sửa tại nhà</span>
            </div>
            <div class="pricing-chip" id="costTruckBadge">
              <span class="material-symbols-outlined">local_shipping</span>
              <span>Không thuê xe chở</span>
            </div>
            <div class="pricing-chip" id="costDistanceContainer">
              <span class="material-symbols-outlined">bolt</span>
              <span id="costDistanceBadge">Phí đi lại tự động</span>
            </div>
          </div>

          <a href="{{ route('worker.my-bookings') }}" class="pricing-close" aria-label="Quay lại lịch làm việc">
            <span class="material-symbols-outlined">close</span>
          </a>

          <div class="pricing-banner">
            <div class="pricing-banner-item pricing-banner-code" id="costBookingReference">Đơn #0000</div>
            <div class="pricing-banner-item">
              <span class="material-symbols-outlined">person</span>
              <span id="costCustomerName">Khách hàng</span>
            </div>
            <div class="pricing-banner-item">
              <span class="material-symbols-outlined">construction</span>
              <span id="costServiceName">Dịch vụ sửa chữa</span>
            </div>
            <div class="pricing-banner-status" id="costBookingStatus">Đang sửa</div>
          </div>
        </header>

        <div class="pricing-body">
          <section class="pricing-editor">
            <div id="pricingPageLoading" class="pricing-page-loading">Đang tải thông tin đơn và bảng giá...</div>
            <div id="pricingPageError" class="pricing-page-error" hidden>Không tải được dữ liệu đơn sửa chữa.</div>

            <div id="pricingEditorContent" hidden>
              <div class="pricing-flow" aria-hidden="true">
                <div class="pricing-flow-step is-active">
                  <span class="pricing-flow-index">1</span>
                  <span>Tiền công</span>
                </div>
                <div class="pricing-flow-divider"></div>
                <div class="pricing-flow-step">
                  <span class="pricing-flow-index">2</span>
                  <span>Linh kiện</span>
                </div>
                <div class="pricing-flow-divider"></div>
                <div class="pricing-flow-step">
                  <span class="pricing-flow-index">3</span>
                  <span>Rà soát</span>
                </div>
              </div>

              <section class="pricing-section">
                <div class="pricing-section-head">
                  <div class="pricing-section-badge">
                    <div class="pricing-section-number">01</div>
                    <div>
                      <div class="pricing-section-label">Tiền công</div>
                      <div class="pricing-section-count" id="laborCountBadge">0 dòng</div>
                    </div>
                  </div>
                  <button type="button" class="pricing-inline-action" id="addLaborItem">
                    <span class="material-symbols-outlined" style="font-size:12px;">add</span>
                    Thêm dòng công
                  </button>
                </div>
                <div class="pricing-search-row">
                  <div class="pricing-search-wrap">
                    <div class="pricing-searchbox">
                      <span class="material-symbols-outlined pricing-search-icon">search</span>
                      <input type="search" class="pricing-search-input" id="laborCatalogSearch" placeholder="Ví dụ: thay dây curoa, sửa bo mạch..." autocomplete="off">
                    </div>
                    <div class="pricing-catalog-suggestions" id="laborCatalogSuggestions" hidden></div>
                    <div class="pricing-catalog-status" id="laborCatalogStatus">Nhập hướng xử lý để hiện gợi ý tiền công theo đúng dịch vụ của đơn.</div>
                  </div>
                </div>
                <div class="pricing-labor-list" id="laborItemsContainer"></div>
              </section>

              <section class="pricing-section">
                <div class="pricing-section-head">
                  <div class="pricing-section-badge">
                    <div class="pricing-section-number">02</div>
                    <div>
                      <div class="pricing-section-label">Linh kiện</div>
                      <div class="pricing-section-count" id="partCountBadge">0 dòng</div>
                    </div>
                  </div>
                  <button type="button" class="pricing-inline-action" id="addPartItem">
                    <span class="material-symbols-outlined" style="font-size:12px;">add</span>
                    Thêm dòng thủ công
                  </button>
                </div>

                <div class="pricing-search-row">
                  <div class="pricing-search-wrap">
                    <div class="pricing-searchbox">
                      <span class="material-symbols-outlined pricing-search-icon">search</span>
                      <input type="search" class="pricing-search-input" id="partCatalogSearch" placeholder="Ví dụ: bo nóng Samsung..." autocomplete="off">
                    </div>
                    <div class="pricing-catalog-suggestions" id="partCatalogSuggestions" hidden></div>
                    <div class="pricing-catalog-status" id="partCatalogStatus">Nhập tên linh kiện để hiện gợi ý và chọn nhanh.</div>
                  </div>
                </div>
                <div class="pricing-part-list" id="partItemsContainer"></div>
              </section>

              <section class="pricing-section">
                <div class="pricing-section-head">
                  <div class="pricing-section-badge">
                    <div class="pricing-section-number">03</div>
                    <div>
                      <div class="pricing-section-label">Phí phụ thêm</div>
                    </div>
                  </div>
                </div>

                <div class="pricing-fees">
                  <div class="pricing-fee-card" id="truckFeeContainer" style="display:none;">
                    <h3 class="pricing-fee-title">Phí thuê xe chở</h3>
                    <div class="pricing-fee-input-wrap">
                      <input type="number" class="pricing-fee-input" id="inputTienThueXe" min="0" value="0" placeholder="Nhập số tiền...">
                      <span class="pricing-fee-prefix">đ</span>
                    </div>
                  </div>

                  <div class="pricing-fee-card">
                    <h3 class="pricing-fee-title">Phí đi lại cố định</h3>
                    <div class="pricing-fee-readonly">
                      <strong class="pricing-fee-value" id="displayPhiDiLai">0 đ</strong>
                      <span class="pricing-fee-pill">Tự tính</span>
                    </div>
                    <div class="pricing-fee-hint" id="costDistanceHint">Hệ thống tính tự động theo quãng đường phục vụ.</div>
                  </div>
                </div>
              </section>
            </div>
          </section>

          <aside class="pricing-summary">
            <div class="pricing-summary-eyebrow">Bản xem trước gửi khách</div>
            <h2 class="pricing-summary-title">Tóm tắt chi phí</h2>
            <div class="pricing-summary-status" id="costDraftState">
              <span class="material-symbols-outlined" style="font-size:12px;">check_circle</span>
              <span>Sẵn sàng lưu</span>
            </div>

            <div class="pricing-summary-list">
              <div class="pricing-summary-row">
                <span>Tổng tiền công</span>
                <strong id="laborSubtotal">0 đ</strong>
              </div>
              <div class="pricing-summary-row">
                <span>Linh kiện & Vật tư</span>
                <strong id="partsSubtotal">0 đ</strong>
              </div>
              <div class="pricing-summary-row" id="travelSummaryRow">
                <span>Phí đi lại (Cố định)</span>
                <strong id="travelSubtotal">0 đ</strong>
              </div>
              <div class="pricing-summary-row" id="truckSummaryRow" style="display:none;">
                <span>Phí thuê xe</span>
                <strong id="truckSubtotal">0 đ</strong>
              </div>
            </div>

            <div class="pricing-total-card">
              <div class="pricing-total-label">
                <span class="material-symbols-outlined">receipt_long</span>
                <span>Tổng cộng tất cả chi phí</span>
              </div>
              <div class="pricing-total-value" id="costEstimateTotal">0 đ</div>
              <div class="pricing-total-hint" id="costSummaryHint">Đã cộng tiền công, linh kiện và các phụ phí của đơn này.</div>
            </div>

            <div class="pricing-summary-footer">
              <a href="{{ route('worker.my-bookings') }}" class="pricing-btn pricing-btn--ghost">Hủy</a>
              <button type="submit" class="pricing-btn pricing-btn--primary" id="btnSubmitCostUpdate">
                <span class="material-symbols-outlined" style="font-size:14px;">save</span>
                <span>Lưu chi phí</span>
              </button>
            </div>
          </aside>
        </div>
      </form>
    </div>
  </main>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/worker/pricing-editor.js') }}"></script>
@endpush
