@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Material+Symbols+Outlined" rel="stylesheet" />
<style>
    :root {
        --detail-primary: #1198e8;
        --detail-primary-deep: #0584d9;
        --detail-success: #10b981;
        --detail-warning: #f59e0b;
        --detail-danger: #ef4444;
        --detail-text: #111827;
        --detail-text-soft: #6b7280;
        --detail-surface: #ffffff;
        --detail-muted-surface: #f5f7fb;
        --detail-line: #e8edf3;
        --detail-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        --detail-radius-xl: 32px;
        --detail-radius-lg: 24px;
        --detail-radius-md: 18px;
    }

    body {
        font-family: 'Manrope', sans-serif;
        background: #f5f7fb;
        color: var(--detail-text);
        min-height: 100vh;
    }

    [data-customer-chat-widget],
    #customerChatWidget,
    .customer-chat-widget,
    x-customer-chat-widget {
        display: none !important;
    }

    .material-symbols-outlined {
        font-family: 'Material Symbols Outlined';
        font-weight: normal;
        font-style: normal;
        line-height: 1;
        letter-spacing: normal;
        text-transform: none;
        display: inline-block;
        white-space: nowrap;
        word-wrap: normal;
        direction: ltr;
        -webkit-font-smoothing: antialiased;
        font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24;
    }

    .detail-topbar {
        background: transparent;
        border-bottom: none;
    }

    .detail-topbar-inner,
    .detail-page-shell {
        width: min(1180px, calc(100% - 2rem));
        margin: 0 auto;
    }

    .detail-topbar-inner {
        min-height: 64px;
        display: flex;
        align-items: center;
        gap: 0.9rem;
    }

    .detail-back-link {
        width: 2.5rem;
        height: 2.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        color: var(--detail-primary);
        text-decoration: none;
        transition: background-color 0.18s ease, transform 0.18s ease;
    }

    .detail-back-link:hover {
        background: rgba(17, 152, 232, 0.08);
        transform: translateX(-1px);
    }

    .detail-topbar-copy {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.45rem;
        min-width: 0;
        font-size: 1.05rem;
        font-weight: 800;
    }

    .detail-topbar-copy strong {
        color: var(--detail-primary);
        font-weight: 800;
    }

    .booking-detail-page {
        padding: 2rem 0 3.75rem;
    }

    .detail-loading-card,
    .detail-error-card,
    .detail-card {
        background: var(--detail-surface);
        border: 1px solid var(--detail-line);
        border-radius: var(--detail-radius-xl);
        box-shadow: var(--detail-shadow);
    }

    .detail-loading-card,
    .detail-error-card {
        padding: 2.5rem 1.5rem;
        text-align: center;
    }

    .detail-loading-icon,
    .detail-error-icon {
        width: 4.75rem;
        height: 4.75rem;
        margin: 0 auto 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        color: var(--detail-primary);
        background: rgba(17, 152, 232, 0.08);
    }

    .detail-loading-card h2,
    .detail-error-card h2 {
        margin: 0;
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .detail-loading-card p,
    .detail-error-card p {
        max-width: 34rem;
        margin: 0.75rem auto 0;
        color: var(--detail-text-soft);
        line-height: 1.7;
    }

    .detail-content {
        margin-top: 0.1rem;
    }

    .detail-dashboard {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 26.5rem;
        gap: 1.5rem;
        align-items: start;
    }

    .detail-main-column,
    .detail-side-column {
        display: grid;
        gap: 1.5rem;
    }

    .detail-card {
        padding: 1.5rem;
    }

    .detail-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .detail-card-head h2 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .detail-card-head span {
        color: #374151;
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .detail-gallery-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .detail-gallery-tile,
    .detail-gallery-more,
    .detail-gallery-empty {
        position: relative;
        min-height: 12rem;
        overflow: hidden;
        border-radius: var(--detail-radius-md);
        background: #f2f5f8;
        border: 1px solid #edf1f5;
    }

    .detail-gallery-tile img,
    .detail-gallery-tile video {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
    }

    .detail-gallery-link {
        position: absolute;
        inset: 0;
        z-index: 1;
    }

    .detail-play-badge {
        position: absolute;
        top: 50%;
        left: 50%;
        z-index: 2;
        transform: translate(-50%, -50%);
        width: 3rem;
        height: 3rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.14);
    }

    .detail-gallery-more,
    .detail-gallery-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        text-align: center;
        color: #1f2937;
        font-size: 1rem;
        font-weight: 700;
        background: #f2f5f8;
    }

    .detail-gallery-empty {
        color: var(--detail-text-soft);
        font-size: 0.94rem;
        line-height: 1.6;
    }

    .detail-summary-card {
        padding: 1.55rem;
    }

    .detail-summary-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .detail-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.62rem 1rem;
        border-radius: 999px;
        font-size: 0.9rem;
        font-weight: 800;
        border: 1px solid rgba(245, 158, 11, 0.35);
        background: rgba(255, 247, 237, 0.95);
        color: #ea580c;
    }

    .detail-status-pill::before {
        content: "";
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 999px;
        background: currentColor;
    }

    .detail-status-pill.is-blue {
        color: var(--detail-primary-deep);
        background: rgba(239, 246, 255, 0.95);
        border-color: rgba(17, 152, 232, 0.24);
    }

    .detail-status-pill.is-green {
        color: #059669;
        background: rgba(236, 253, 245, 0.96);
        border-color: rgba(16, 185, 129, 0.24);
    }

    .detail-status-pill.is-red {
        color: var(--detail-danger);
        background: rgba(254, 242, 242, 0.96);
        border-color: rgba(239, 68, 68, 0.22);
    }

    .detail-summary-order {
        text-align: right;
    }

    .detail-summary-order span {
        display: block;
        color: var(--detail-text-soft);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .detail-summary-order strong {
        display: block;
        margin-top: 0.3rem;
        font-size: 0.95rem;
        font-weight: 800;
    }

    .detail-service-title {
        margin: 1.5rem 0 0;
        font-size: 2rem;
        line-height: 1.16;
        font-weight: 800;
        letter-spacing: -0.05em;
    }

    .detail-summary-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.8rem;
        color: #374151;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .detail-estimate-box {
        margin-top: 1.3rem;
        padding: 1.5rem 1rem 1.35rem;
        border-radius: 22px;
        background: var(--detail-muted-surface);
        text-align: center;
    }

    .detail-estimate-box span {
        display: block;
        color: #374151;
        font-size: 0.96rem;
        font-weight: 500;
    }

    .detail-estimate-box strong {
        display: block;
        margin-top: 0.6rem;
        color: var(--detail-primary);
        font-size: 2.25rem;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.05em;
    }

    .detail-summary-action,
    .detail-summary-note {
        margin-top: 1.35rem;
    }

    .detail-summary-action-stack {
        display: grid;
        gap: 0.75rem;
        margin-top: 1.35rem;
    }

    .detail-summary-action-stack .detail-summary-action {
        margin-top: 0;
    }

    .detail-outline-button,
    .detail-solid-button,
    .detail-ghost-button {
        width: 100%;
        min-height: 3.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        padding: 0.85rem 1.2rem;
        border-radius: 999px;
        border: none;
        text-decoration: none;
        font-size: 1rem;
        font-weight: 700;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
        cursor: pointer;
    }

    .detail-outline-button:hover,
    .detail-solid-button:hover,
    .detail-ghost-button:hover {
        transform: translateY(-1px);
    }

    .detail-outline-button {
        background: #fff;
        color: var(--detail-text);
        border: 1px solid #cfd8e3;
    }

    .detail-solid-button {
        background: linear-gradient(135deg, #1ab86d 0%, #0ea765 100%);
        color: #fff;
        box-shadow: 0 14px 26px rgba(16, 185, 129, 0.22);
    }

    .detail-ghost-button {
        background: rgba(17, 152, 232, 0.08);
        color: var(--detail-primary-deep);
    }

    .detail-summary-note {
        padding: 1rem 1.05rem;
        border-radius: 20px;
        background: #f8fbff;
        border: 1px solid rgba(17, 152, 232, 0.12);
        color: #334155;
        line-height: 1.65;
        font-weight: 500;
    }

    .detail-summary-note strong {
        display: block;
        margin-bottom: 0.2rem;
        color: var(--detail-text);
        font-size: 0.95rem;
        font-weight: 800;
    }

    .detail-summary-note--info {
        background: rgba(17, 152, 232, 0.08);
        border-color: rgba(17, 152, 232, 0.14);
    }

    .detail-worker-row {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        margin-top: 1rem;
    }

    .detail-worker-avatar-wrap {
        position: relative;
        flex-shrink: 0;
    }

    .detail-worker-avatar {
        width: 3.85rem;
        height: 3.85rem;
        border-radius: 999px;
        object-fit: cover;
        background: #eaf3ff;
    }

    .detail-worker-avatar-fallback {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--detail-primary-deep);
        font-size: 1.25rem;
        font-weight: 800;
    }

    .detail-worker-verified {
        position: absolute;
        right: -0.12rem;
        bottom: -0.12rem;
        width: 1.45rem;
        height: 1.45rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: linear-gradient(135deg, #26c281 0%, #10b981 100%);
        color: #fff;
        border: 3px solid #fff;
    }

    .detail-worker-copy {
        min-width: 0;
        flex: 1;
    }

    .detail-worker-copy strong {
        display: block;
        font-size: 0.98rem;
        font-weight: 800;
    }

    .detail-worker-phone {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        margin-top: 0.35rem;
        color: #374151;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .detail-worker-link {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        margin-top: 0.45rem;
        color: var(--detail-primary);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 700;
    }

    .detail-rating-chip {
        margin-left: auto;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.4rem 0.72rem;
        border-radius: 999px;
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
        font-size: 0.82rem;
        font-weight: 800;
    }

    .detail-empty-note {
        margin-top: 1rem;
        padding: 1rem;
        border-radius: 20px;
        background: #f8fbff;
        border: 1px dashed #d7e1ec;
        color: var(--detail-text-soft);
        line-height: 1.65;
    }

    .detail-cost-list {
        display: grid;
        gap: 0.95rem;
        margin-top: 1rem;
    }

    .detail-cost-row,
    .detail-cost-total {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .detail-cost-row span {
        color: #374151;
        font-size: 0.98rem;
        font-weight: 500;
    }

    .detail-cost-row strong {
        color: var(--detail-text);
        font-size: 0.98rem;
        font-weight: 700;
    }

    .detail-cost-row strong.is-free {
        color: #16a34a;
    }

    .detail-cost-divider {
        border-top: 1px solid var(--detail-line);
    }

    .detail-cost-total {
        align-items: baseline;
        padding-top: 0.3rem;
    }

    .detail-cost-total span {
        font-size: 1rem;
        font-weight: 800;
    }

    .detail-cost-total strong {
        color: var(--detail-primary);
        font-size: 2rem;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.05em;
    }

    .detail-cost-section {
        margin-top: 1.1rem;
    }

    .detail-cost-section__label {
        display: inline-flex;
        margin-bottom: 0.8rem;
        color: #334155;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .detail-cost-item-list {
        display: grid;
        gap: 0.8rem;
    }

    .detail-cost-item-card {
        border-radius: 18px;
        padding: 0.95rem 1rem;
        background: #f8fbff;
        border: 1px solid #dbe7f3;
    }

    .detail-cost-item-card__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .detail-cost-item-card__top strong {
        display: block;
        font-size: 0.98rem;
        color: var(--detail-text);
    }

    .detail-cost-item-card__copy {
        min-width: 0;
        flex: 1;
    }

    .detail-cost-item-card__top small {
        display: block;
        margin-top: 0.28rem;
        color: var(--detail-text-soft);
        font-size: 0.82rem;
        line-height: 1.5;
    }

    .detail-warranty-meta {
        display: grid;
        gap: 0.42rem;
        margin-top: 0.7rem;
    }

    .detail-warranty-meta p {
        margin: 0;
        color: var(--detail-text-soft);
        font-size: 0.82rem;
        line-height: 1.55;
    }

    .detail-warranty-action {
        width: fit-content;
        min-height: 2.15rem;
        padding: 0.4rem 0.8rem;
        border-radius: 999px;
        border: 1px solid rgba(17, 152, 232, 0.18);
        background: rgba(17, 152, 232, 0.08);
        color: var(--detail-primary-deep);
        font-size: 0.8rem;
        font-weight: 800;
        cursor: pointer;
        transition: transform 0.18s ease, background-color 0.18s ease;
    }

    .detail-warranty-action:hover {
        transform: translateY(-1px);
        background: rgba(17, 152, 232, 0.14);
    }

    .detail-warranty-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: fit-content;
        min-height: 1.9rem;
        padding: 0.32rem 0.72rem;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        border: 1px solid transparent;
        background: #eef2f7;
        color: #475569;
    }

    .detail-warranty-pill.is-active {
        background: rgba(236, 253, 245, 0.96);
        border-color: rgba(16, 185, 129, 0.2);
        color: #059669;
    }

    .detail-warranty-pill.is-expired,
    .detail-warranty-pill.is-used {
        background: rgba(254, 242, 242, 0.96);
        border-color: rgba(239, 68, 68, 0.2);
        color: #dc2626;
    }

    .detail-warranty-pill.is-neutral {
        background: #eff6ff;
        border-color: rgba(17, 152, 232, 0.18);
        color: var(--detail-primary-deep);
    }

    .detail-cost-item-card__top span {
      
        font-weight: 800;
        color: var(--detail-primary);
        white-space: nowrap;
    }

    .detail-payment-action-card {
        border: 2px solid rgba(17, 152, 232, 0.22);
        box-shadow: 0 10px 24px rgba(17, 152, 232, 0.06);
    }

    .detail-payment-action-head {
        display: flex;
        align-items: flex-start;
        gap: 0.9rem;
    }

    .detail-payment-icon {
        width: 3rem;
        height: 3rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: rgba(16, 185, 129, 0.12);
        color: var(--detail-success);
    }

    .detail-payment-copy {
        min-width: 0;
        flex: 1;
    }

    .detail-payment-title-row {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        flex-wrap: wrap;
    }

    .detail-payment-title-row strong {
        font-size: 1rem;
        font-weight: 800;
    }

    .detail-payment-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.24rem 0.5rem;
        border-radius: 999px;
        background: rgba(254, 226, 226, 0.95);
        color: var(--detail-danger);
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .detail-payment-copy p {
        margin: 0.4rem 0 0;
        color: var(--detail-text-soft);
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .detail-payment-action {
        margin-top: 1.2rem;
    }

    .detail-progress-list {
        position: relative;
        display: grid;
        gap: 1.15rem;
        margin-top: 1rem;
    }

    .detail-progress-list::before {
        content: "";
        position: absolute;
        top: 0.6rem;
        left: 0.95rem;
        bottom: 0.6rem;
        width: 2px;
        background: linear-gradient(180deg, rgba(16, 185, 129, 0.5) 0%, rgba(17, 152, 232, 0.18) 100%);
    }

    .detail-progress-item {
        position: relative;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 1rem;
        align-items: flex-start;
    }

    .detail-progress-icon {
        width: 2rem;
        height: 2rem;
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #e5e7eb;
        color: #6b7280;
        border: 4px solid #fff;
        box-shadow: 0 0 0 2px rgba(229, 231, 235, 0.8);
    }

    .detail-progress-item.is-complete .detail-progress-icon {
        background: linear-gradient(135deg, #26c281 0%, #10b981 100%);
        color: #fff;
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.22);
    }

    .detail-progress-item.is-active .detail-progress-icon {
        background: linear-gradient(135deg, #26a8ff 0%, #1198e8 100%);
        color: #fff;
        box-shadow: 0 0 0 5px rgba(17, 152, 232, 0.12);
    }

    .detail-progress-copy strong {
        display: block;
        font-size: 0.98rem;
        font-weight: 800;
    }

    .detail-progress-copy time,
    .detail-progress-copy p {
        display: block;
        margin-top: 0.2rem;
        color: #4b5563;
        font-size: 0.95rem;
        line-height: 1.55;
    }

    .detail-progress-item.is-disabled .detail-progress-copy strong,
    .detail-progress-item.is-disabled .detail-progress-copy time,
    .detail-progress-item.is-disabled .detail-progress-copy p {
        color: #6b7280;
    }

    .detail-request-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem 1.2rem;
        margin-top: 1rem;
    }

    .detail-request-info {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 0.9rem;
        align-items: start;
    }

    .detail-request-icon {
        width: 3.9rem;
        height: 3.9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 20px;
        background: rgba(17, 152, 232, 0.08);
        color: var(--detail-primary);
    }

    .detail-request-copy span {
        display: block;
        color: #374151;
        font-size: 0.98rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .detail-request-copy strong {
        display: block;
        margin-top: 0.4rem;
        font-size: 1rem;
        line-height: 1.6;
        font-weight: 700;
    }

    .detail-request-copy small {
        display: block;
        margin-top: 0.4rem;
        color: var(--detail-text-soft);
        font-size: 0.92rem;
        line-height: 1.55;
        font-style: italic;
    }

    .detail-service-badge {
        display: inline-flex;
        align-items: center;
        margin-top: 0.55rem;
        padding: 0.3rem 0.72rem;
        border-radius: 999px;
        background: rgba(17, 152, 232, 0.1);
        color: var(--detail-primary);
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .detail-problem-block {
        grid-column: 1 / -1;
    }

    .detail-problem-box {
        margin-top: 0.75rem;
        padding: 1.15rem 1.2rem;
        border-radius: 20px;
        background: var(--detail-muted-surface);
        border: 1px dashed #d5dee8;
        font-size: 1.02rem;
        line-height: 1.75;
    }

    .review-modal-shell .modal-content {
        border-radius: 28px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.96);
        box-shadow: 0 26px 56px rgba(15, 23, 42, 0.16);
    }

    .review-modal-shell .modal-header {
        padding: 1.45rem 1.45rem 0.4rem;
    }

    .review-modal-shell .modal-body {
        padding: 0.8rem 1.45rem 1.45rem;
    }

    .review-modal-shell .modal-title {
        font-size: 1.35rem;
        font-weight: 800;
    }

    .review-modal-shell .btn-primary {
        background: linear-gradient(135deg, #1ab86d 0%, #0ea765 100%);
        border: none;
        box-shadow: 0 16px 30px rgba(16, 185, 129, 0.18);
    }

    .detail-reschedule-modal {
        width: min(100%, 38rem);
        border-radius: 28px;
        padding: 1.2rem 1.2rem 1.35rem;
    }

    .detail-reschedule-note {
        display: grid;
        gap: 0.35rem;
        margin-bottom: 1rem;
        padding: 0.95rem 1rem;
        border-radius: 18px;
        background: rgba(17, 152, 232, 0.08);
        color: #334155;
        text-align: left;
        line-height: 1.6;
    }

    .detail-reschedule-note strong {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--detail-text);
    }

    .detail-reschedule-field {
        margin-top: 0.9rem;
        text-align: left;
    }

    .detail-reschedule-field label {
        display: block;
        margin-bottom: 0.55rem;
        color: #1f2937;
        font-size: 0.9rem;
        font-weight: 800;
    }

    .detail-reschedule-date {
        width: 100% !important;
        margin: 0 !important;
    }

    .detail-reschedule-slot-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .detail-reschedule-slot {
        min-height: 3rem;
        padding: 0.75rem 0.9rem;
        border: 1px solid #cfd8e3;
        border-radius: 16px;
        background: #fff;
        color: #111827;
        font-size: 0.95rem;
        font-weight: 700;
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
        cursor: pointer;
    }

    .detail-reschedule-slot:hover:not(:disabled) {
        transform: translateY(-1px);
        border-color: rgba(17, 152, 232, 0.45);
    }

    .detail-reschedule-slot.is-selected {
        border-color: rgba(17, 152, 232, 0.7);
        background: rgba(17, 152, 232, 0.1);
        color: var(--detail-primary-deep);
        box-shadow: 0 10px 18px rgba(17, 152, 232, 0.12);
    }

    .detail-reschedule-slot:disabled {
        background: #f8fafc;
        color: #94a3b8;
        cursor: not-allowed;
    }

    .detail-reschedule-current {
        margin-top: 1rem;
        color: #475569;
        font-size: 0.92rem;
        line-height: 1.55;
        text-align: left;
    }

    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: center;
        gap: 0.45rem;
    }

    .star-rating input {
        display: none;
    }

    .star-rating label {
        color: #cbd5e1;
        cursor: pointer;
        font-size: 2.4rem;
        line-height: 1;
        transition: transform 0.18s ease, color 0.18s ease;
    }

    .star-rating label:hover {
        transform: translateY(-1px);
    }

    .star-rating input:checked~label,
    .star-rating label:hover,
    .star-rating label:hover~label {
        color: #f59e0b;
    }

    @media (max-width: 1199.98px) {
        .detail-dashboard {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .detail-topbar-inner,
        .detail-page-shell {
            width: min(100% - 1rem, 1180px);
        }

        .detail-card,
        .detail-loading-card,
        .detail-error-card {
            padding: 1.1rem;
            border-radius: 24px;
        }

        .detail-gallery-grid,
        .detail-request-grid {
            grid-template-columns: 1fr;
        }

        .detail-reschedule-slot-grid {
            grid-template-columns: 1fr;
        }

        .detail-gallery-tile,
        .detail-gallery-more,
        .detail-gallery-empty {
            min-height: 11rem;
        }

        .detail-summary-top {
            flex-direction: column;
        }

        .detail-summary-order {
            text-align: left;
        }

        .detail-service-title {
            font-size: 1.7rem;
        }

        .detail-estimate-box strong,
        .detail-cost-total strong {
            font-size: 1.75rem;
        }
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="detail-topbar">
    <div class="detail-topbar-inner">
        <a href="/customer/my-bookings" class="detail-back-link" aria-label="Quay lại lịch sử đơn hàng">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div class="detail-topbar-copy">
            <span>Chi tiết đơn hàng</span>
            <strong id="detailTopbarOrderCode">#ORD-{{ str_pad((string) $id, 4, '0', STR_PAD_LEFT) }}</strong>
        </div>
    </div>
</div>

<div class="booking-detail-page" id="bookingDetailPage" data-booking-id="{{ $id }}">
    <div class="detail-page-shell">
        <div class="detail-loading-card" id="bookingDetailLoading">
            <div class="detail-loading-icon">
                <span class="material-symbols-outlined">progress_activity</span>
            </div>
            <h2>Đang tải thông tin đơn hàng</h2>
            <p>Hệ thống đang đồng bộ chi tiết, tiến độ xử lý và thông tin thanh toán của đơn hàng này.</p>
            <div class="mt-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
            </div>
        </div>

        <div class="detail-error-card d-none" id="bookingDetailError"></div>
        <div class="detail-content d-none" id="bookingDetailContent"></div>
    </div>
</div>

<div class="modal fade review-modal-shell" id="bookingDetailReviewModal" tabindex="-1" aria-labelledby="bookingDetailReviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0">
                <div>
                    <h5 class="modal-title fw-bold" id="bookingDetailReviewLabel">Đánh giá dịch vụ</h5>
                    <p class="text-muted mb-0 small">Chia sẻ trải nghiệm của bạn để chúng tôi phục vụ tốt hơn.</p>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted mb-4" id="bookingDetailReviewWorkerName">Hãy cho chúng tôi biết cảm nhận của bạn về kỹ thuật viên.</p>

                <form id="bookingDetailReviewForm">
                    <input type="hidden" id="bookingDetailReviewBookingId" name="don_dat_lich_id">

                    <div class="star-rating mb-4">
                        <input type="radio" id="bookingDetailStar5" name="so_sao" value="5" required />
                        <label for="bookingDetailStar5" title="5 sao">&#9733;</label>
                        <input type="radio" id="bookingDetailStar4" name="so_sao" value="4" />
                        <label for="bookingDetailStar4" title="4 sao">&#9733;</label>
                        <input type="radio" id="bookingDetailStar3" name="so_sao" value="3" />
                        <label for="bookingDetailStar3" title="3 sao">&#9733;</label>
                        <input type="radio" id="bookingDetailStar2" name="so_sao" value="2" />
                        <label for="bookingDetailStar2" title="2 sao">&#9733;</label>
                        <input type="radio" id="bookingDetailStar1" name="so_sao" value="1" />
                        <label for="bookingDetailStar1" title="1 sao">&#9733;</label>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-bold">Nhận xét chi tiết</label>
                        <textarea class="form-control bg-light border-0" id="bookingDetailReviewComment" name="nhan_xet" rows="4" placeholder="Kỹ thuật viên xử lý có đúng hẹn không? Chất lượng sửa chữa như thế nào?"></textarea>
                    </div>

                    <div class="mb-4 text-start">
                        <label class="form-label fw-bold">Media dinh kem (tuy chon)</label>
                        <p class="review-media-upload__hint">Toi da 5 anh va 1 video toi da 20 giay. Media se duoc luu tren cloud.</p>
                        <div class="review-media-upload">
                            <div class="review-media-upload__actions">
                                <label class="review-media-upload__picker">
                                    <input type="file" id="bookingDetailReviewImagesInput" accept="image/*" multiple>
                                    <span class="material-symbols-outlined">imagesmode</span>
                                    <span>Them anh</span>
                                </label>
                                <label class="review-media-upload__picker review-media-upload__picker--video">
                                    <input type="file" id="bookingDetailReviewVideoInput" accept="video/mp4,video/quicktime,video/webm,video/x-msvideo,video/x-ms-wmv">
                                    <span class="material-symbols-outlined">videocam</span>
                                    <span>Them video</span>
                                </label>
                                <div class="review-media-upload__summary" id="bookingDetailReviewMediaSummary">0/5 anh • 0/1 video</div>
                            </div>
                            <div class="review-media-gallery" id="bookingDetailReviewMediaPreview"></div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold" id="bookingDetailSubmitReview">Gửi đánh giá</button>
                </form>
            </div>
        </div>
    </div>
</div>

@include('customer.partials.booking-wizard-modal')
@endsection

@push('scripts')
<script>
    window.customerBookingDetailId = @json((int) $id);
</script>
<script type="module" src="{{ asset('assets/js/customer/my-booking-detail.js') }}"></script>
@endpush
