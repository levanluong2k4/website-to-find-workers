@extends('layouts.app')

@section('title', 'Lịch Sử Đặt Thợ')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Roboto:ital,wght@0,100..900;1,100..900&family=Material+Symbols+Outlined" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
<style>
    body {
        font-family: 'DM Sans', sans-serif;
        background:
            radial-gradient(circle at top left, rgba(255, 255, 255, 0.68) 0, rgba(255, 255, 255, 0) 24rem),
            radial-gradient(circle at top right, rgba(255, 255, 255, 0.58) 0, rgba(255, 255, 255, 0) 18rem),
            linear-gradient(180deg, #8ad0ff 0%, #c7e8ff 36%, #edf7ff 100%);
        min-height: 100vh;
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

    .booking-history-shell {
        font-family: 'DM Sans', sans-serif;
        padding: 2rem 0 4rem;
        position: relative;
    }

    .history-minimal-bar {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 0.4rem 0 0;
    }

    .history-breadcrumb {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        font-family: 'Roboto', sans-serif;
        font-size: 1.02rem;
        color: #6a8094;
    }

    .history-breadcrumb a {
        color: #6a8094;
        text-decoration: none;
    }

    .history-breadcrumb strong {
        color: #27507b;
        font-family: 'DM Sans', sans-serif;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .history-breadcrumb .divider {
        color: #9ab0c2;
    }

    .history-filter-wrap {
        padding: 0.4rem;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid rgba(219, 232, 243, 0.95);
        box-shadow: 0 16px 34px rgba(71, 130, 194, 0.1);
    }

    .history-filter-controls {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .history-service-filter {
        position: relative;
        min-width: 15rem;
        flex: 0 0 auto;
    }

    .history-service-filter .material-symbols-outlined {
        position: absolute;
        top: 50%;
        left: 1rem;
        transform: translateY(-50%);
        font-size: 1.15rem;
        color: #3d6b95;
        pointer-events: none;
    }

    .history-service-filter select {
        width: 100%;
        min-height: 3.45rem;
        padding: 0.9rem 2.9rem 0.9rem 2.8rem;
        border-radius: 999px;
        border: 1px solid #dbe6ef;
        background: #fff;
        color: #20394f;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.95rem;
        font-weight: 700;
        box-shadow: 0 6px 14px rgba(148, 163, 184, 0.08);
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        transition: border-color 0.16s ease, box-shadow 0.16s ease;
    }

    .history-service-filter::after {
        content: "expand_more";
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        font-family: 'Material Symbols Outlined';
        font-size: 1.2rem;
        color: #3d6b95;
        pointer-events: none;
    }

    .history-service-filter select:focus {
        outline: none;
        border-color: #93c5fd;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.14);
    }

    .booking-history-shell h1,
    .booking-history-shell h2,
    .booking-history-shell h3,
    .booking-history-shell h4,
    .booking-history-shell h5,
    .booking-history-shell h6,
    .review-modal-shell h1,
    .review-modal-shell h2,
    .review-modal-shell h3,
    .review-modal-shell h4,
    .review-modal-shell h5,
    .review-modal-shell h6 {
        font-family: 'DM Sans', sans-serif;
    }

    .history-hero {
        position: relative;
        overflow: hidden;
        border-radius: 38px;
        padding: clamp(1.5rem, 2vw, 2.5rem);
        background:
            radial-gradient(circle at 0% 12%, rgba(255, 255, 255, 0.45) 0, rgba(255, 255, 255, 0) 18rem),
            radial-gradient(circle at 100% 18%, rgba(255, 255, 255, 0.4) 0, rgba(255, 255, 255, 0) 16rem),
            linear-gradient(145deg, rgba(188, 231, 255, 0.9) 0%, rgba(113, 193, 255, 0.92) 40%, rgba(164, 225, 255, 0.86) 100%);
        border: 1px solid rgba(255, 255, 255, 0.55);
        box-shadow: 0 26px 60px rgba(37, 99, 235, 0.14);
    }

    .history-hero::before,
    .history-hero::after {
        content: "";
        position: absolute;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.35);
        pointer-events: none;
    }

    .history-hero::before {
        width: 26rem;
        height: 26rem;
        top: -14rem;
        left: -12rem;
    }

    .history-hero::after {
        width: 22rem;
        height: 22rem;
        right: -9rem;
        top: -7rem;
    }

    .history-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.55rem 1rem;
        margin-bottom: 1rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.45);
        color: #0f4c81;
        font-size: 0.82rem;
        font-family: 'Roboto', sans-serif;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        box-shadow: 0 8px 24px rgba(59, 130, 246, 0.12);
    }

    .history-heading h1 {
        margin: 0;
        font-size: clamp(2.8rem, 6vw, 4.2rem);
        line-height: 0.94;
        letter-spacing: -0.06em;
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: 0.2em 0.32em;
        text-wrap: balance;
    }

    .history-title-main {
        font-family: 'DM Sans', sans-serif;
        font-weight: 900;
        color: #10203a;
        text-shadow: 0 14px 30px rgba(16, 32, 58, 0.12);
    }

    .history-title-accent {
        position: relative;
        font-family: 'Libre Baskerville', serif;
        font-style: italic;
        font-weight: 700;
        letter-spacing: -0.03em;
        color: transparent;
        background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 45%, #153b8a 100%);
        -webkit-background-clip: text;
        background-clip: text;
        text-shadow: none;
    }

    .history-title-accent::after {
        content: "";
        position: absolute;
        left: 0.08em;
        right: 0.08em;
        bottom: -0.1em;
        height: 0.16em;
        border-radius: 999px;
        background: linear-gradient(90deg, rgba(14, 165, 233, 0.12), rgba(37, 99, 235, 0.38), rgba(37, 99, 235, 0.08));
    }

    .history-heading p {
        margin: 1rem 0 0;
        max-width: 34rem;
        color: #26557f;
        font-size: 1rem;
        line-height: 1.7;
        font-family: 'Roboto', sans-serif;
        font-weight: 400;
    }

    .history-cta {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        margin-top: 1.75rem;
        border-radius: 999px;
        padding: 0.95rem 1.4rem;
        background: linear-gradient(135deg, #1b8fff 0%, #2563eb 100%);
        color: #fff;
        text-decoration: none;
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        letter-spacing: -0.01em;
        box-shadow: 0 18px 34px rgba(37, 99, 235, 0.28);
    }

    .history-cta:hover {
        color: #fff;
        transform: translateY(-1px);
    }

    .metric-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .metric-card {
        min-height: 8.5rem;
        border-radius: 28px;
        padding: 1.25rem 1.35rem;
        color: #fff;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 20px 40px rgba(59, 130, 246, 0.24);
    }

    .metric-card span {
        font-size: 0.95rem;
        font-family: 'Roboto', sans-serif;
        opacity: 0.92;
        font-weight: 500;
    }

    .metric-card strong {
        font-family: 'Roboto', sans-serif;
        font-weight: 800;
        font-size: clamp(2.25rem, 5vw, 3.1rem);
        line-height: 1;
    }

    .metric-card--total {
        background: linear-gradient(180deg, #36a4ff 0%, #2583f7 100%);
    }

    .metric-card--active {
        background: linear-gradient(180deg, #ffb625 0%, #ff9d17 100%);
    }

    .metric-card--payment {
        background: linear-gradient(180deg, #ff5f9f 0%, #ef3d84 100%);
    }

    .history-toolbar {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-top: 2rem;
    }

    .history-searchbar {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 0.9rem;
        min-height: 4.35rem;
        padding: 0 1.5rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.45);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }

    .history-searchbar i {
        font-size: 1.4rem;
        color: #7295b7;
    }

    .history-searchbar input {
        flex: 1;
        border: none;
        background: transparent;
        padding: 0;
        color: #15314e;
        font-size: 1.12rem;
        font-family: 'DM Sans', sans-serif;
        font-weight: 500;
    }

    .history-searchbar input::placeholder {
        color: #6b89a6;
    }

    .history-searchbar input:focus {
        outline: none;
        box-shadow: none;
    }

    .history-filter-trigger {
        width: 4.35rem;
        height: 4.35rem;
        border: none;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.5);
        color: #5b7fa7;
        box-shadow: 0 18px 30px rgba(88, 153, 214, 0.18);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        font-size: 1.35rem;
    }

    .history-filter-trigger:hover {
        color: #2563eb;
        transform: translateY(-1px);
    }

    .history-filter-panel {
        display: none;
        margin-top: 1rem;
    }

    .history-filter-panel.is-open {
        display: block;
        animation: fadeInPanel 0.18s ease-out;
    }

    @keyframes fadeInPanel {
        from {
            opacity: 0;
            transform: translateY(-6px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .booking-pill-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.7rem;
    }

    .booking-filter-pill {
        border: 1px solid #dbe6ef;
        border-radius: 999px;
        padding: 0.9rem 1.3rem;
        background: #fff;
        color: #20394f;
        font-weight: 700;
        font-size: 0.96rem;
        box-shadow: 0 6px 14px rgba(148, 163, 184, 0.08);
        transition: transform 0.16s ease, box-shadow 0.16s ease, color 0.16s ease;
    }

    .booking-filter-pill:hover {
        transform: translateY(-1px);
        color: #1d4ed8;
        box-shadow: 0 10px 18px rgba(37, 99, 235, 0.1);
    }

    .booking-filter-pill.active {
        background: linear-gradient(135deg, #1b8fff 0%, #2563eb 100%);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 12px 26px rgba(37, 99, 235, 0.22);
    }

    .bookings-grid {
        margin-top: 1.5rem;
    }

    .booking-pagination-shell {
        display: flex;
        justify-content: center;
        margin-top: 1.75rem;
    }

    .booking-pagination {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        flex-wrap: wrap;
        padding: 0.55rem 0.7rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.78);
        border: 1px solid rgba(219, 232, 243, 0.95);
        box-shadow: 0 16px 34px rgba(71, 130, 194, 0.1);
    }

    .booking-page-btn {
        min-width: 2.85rem;
        height: 2.85rem;
        padding: 0 0.9rem;
        border: 1px solid #dbe6ef;
        border-radius: 999px;
        background: #fff;
        color: #20394f;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.94rem;
        font-weight: 700;
        box-shadow: 0 6px 14px rgba(148, 163, 184, 0.08);
        transition: transform 0.16s ease, box-shadow 0.16s ease, color 0.16s ease;
    }

    .booking-page-btn:hover {
        transform: translateY(-1px);
        color: #1d4ed8;
        box-shadow: 0 10px 18px rgba(37, 99, 235, 0.1);
    }

    .booking-page-btn.is-active {
        background: linear-gradient(135deg, #1b8fff 0%, #2563eb 100%);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 12px 26px rgba(37, 99, 235, 0.22);
    }

    .booking-page-btn.is-ellipsis {
        border: none;
        background: transparent;
        box-shadow: none;
        min-width: auto;
        padding: 0 0.2rem;
        color: #6a8094;
        pointer-events: none;
    }

    .booking-page-btn.is-nav {
        min-width: 3.15rem;
    }

    .booking-page-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .bookings-grid > [class*="col-"] {
        display: flex;
    }

    .booking-showcase-card {
        height: 100%;
        width: 100%;
        padding: 0.95rem;
        border-radius: 30px;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(255, 255, 255, 0.95);
        box-shadow: 0 26px 45px rgba(71, 130, 194, 0.18);
        transition: transform 0.24s ease, box-shadow 0.24s ease;
        display: flex;
        flex-direction: column;
    }

    .booking-showcase-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 30px 52px rgba(59, 130, 246, 0.22);
    }

    .booking-showcase-card--interactive {
        cursor: pointer;
    }

    .booking-showcase-card--interactive:focus-visible {
        outline: none;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.18), 0 30px 52px rgba(59, 130, 246, 0.22);
        transform: translateY(-4px);
    }

    .booking-card-media {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        min-height: 9.4rem;
        padding: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.3);
    }

    .booking-card-media::before {
        content: "";
        position: absolute;
        inset: auto auto 1rem -2.5rem;
        width: 9rem;
        height: 9rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        filter: blur(8px);
    }

    .booking-card-media.theme-cool {
        background: linear-gradient(135deg, #3346ff 0%, #5a8dff 100%);
    }

    .booking-card-media.theme-water {
        background: linear-gradient(135deg, #5b18ff 0%, #9248ff 100%);
    }

    .booking-card-media.theme-laundry {
        background: linear-gradient(135deg, #364bff 0%, #5872ff 100%);
    }

    .booking-card-media.theme-electric {
        background: linear-gradient(135deg, #671fff 0%, #8d3bff 100%);
    }

    .booking-card-media.theme-store {
        background: linear-gradient(135deg, #0f7cff 0%, #3db6ff 100%);
    }

    .booking-card-media.theme-generic {
        background: linear-gradient(135deg, #315dff 0%, #7b5dff 100%);
    }

    .booking-service-image {
        position: relative;
        z-index: 1;
        width: 7rem;
        height: 7rem;
        object-fit: cover;
        border-radius: 1.25rem;
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.22);
        border: 4px solid rgba(255, 255, 255, 0.22);
    }

    .booking-service-icon {
        position: relative;
        z-index: 1;
        width: 7rem;
        height: 7rem;
        border-radius: 1.6rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.18);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.2);
    }

    .booking-service-icon .material-symbols-outlined {
        color: #fff;
        font-size: 3.2rem;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 48;
    }

    .booking-code-chip {
        position: absolute;
        top: 0.95rem;
        left: 0.95rem;
        z-index: 2;
        padding: 0.42rem 0.8rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.16);
        color: #fff;
        font-size: 0.8rem;
        font-weight: 700;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .booking-card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 1rem 0.15rem 0.1rem;
    }

    .booking-card-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.45rem;
        line-height: 1.12;
        font-weight: 800;
    }

    .booking-card-meta {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        margin-top: 0.75rem;
        color: #233b58;
        font-size: 0.96rem;
        font-weight: 600;
    }

    .booking-card-meta .material-symbols-outlined {
        color: #2563eb;
        font-size: 1.1rem;
    }

    .booking-status-chip {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        margin-top: 0.9rem;
        border-radius: 999px;
        padding: 0.5rem 0.8rem;
        font-size: 0.84rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .status-cho_xac_nhan {
        background: #fff7b8;
        color: #855b00;
    }

    .status-da_xac_nhan {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .status-dang_lam {
        background: #b8efff;
        color: #0f5d8b;
    }

    .status-cho_hoan_thanh,
    .status-cho_thanh_toan {
        background: #ffd3e4;
        color: #b4235a;
    }

    .status-da_xong {
        background: #c5ffd7;
        color: #177948;
    }

    .status-da_huy {
        background: #e6d5ff;
        color: #6d28d9;
    }

    .service-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-top: 0.7rem;
    }

    .service-tag {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.38rem 0.72rem;
        background: #eff6ff;
        color: #1e40af;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .service-tag--muted {
        background: #eef2ff;
        color: #475569;
    }

    .booking-summary-stack {
        display: grid;
        gap: 0.65rem;
        margin-top: 0.95rem;
    }

    .booking-summary-chip {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        border-radius: 18px;
        padding: 0.72rem 0.82rem;
        background: #f8fbff;
        border: 1px solid #e5eefc;
        color: #36506d;
        font-size: 0.9rem;
        line-height: 1.45;
    }

    .booking-summary-chip .material-symbols-outlined {
        flex-shrink: 0;
        color: #2563eb;
        font-size: 1.1rem;
    }

    .booking-summary-chip span:last-child {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .booking-total-pill {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 0.95rem;
        padding: 0.9rem 1rem;
        border-radius: 18px;
        background: linear-gradient(180deg, #f4faff 0%, #eef7ff 100%);
        border: 1px solid #d7e8ff;
        color: #27415f;
    }

    .booking-total-pill span {
        font-size: 0.88rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .booking-total-pill strong {
        font-family: 'DM Sans', sans-serif;
        font-size: 1.45rem;
        color: #0b5ed7;
        line-height: 1;
    }

    .booking-info-list {
        display: grid;
        gap: 0.7rem;
        margin-top: 1rem;
    }

    .booking-info-item {
        display: flex;
        align-items: flex-start;
        gap: 0.7rem;
        border-radius: 18px;
        padding: 0.8rem 0.85rem;
        background: #f8fbff;
        border: 1px solid #e5eefc;
        color: #36506d;
        font-size: 0.93rem;
        line-height: 1.5;
    }

    .booking-info-item .material-symbols-outlined {
        flex-shrink: 0;
        color: #2563eb;
        font-size: 1.18rem;
    }

    .booking-info-item--transport {
        background: #effcf7;
        border-color: #c8f3de;
        color: #17774c;
    }

    .booking-info-item--transport .material-symbols-outlined {
        color: #16a34a;
    }

    .booking-media-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 1rem;
    }

    .booking-media-thumb {
        width: 3.4rem;
        height: 3.4rem;
        border-radius: 1rem;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.9);
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.12);
    }

    .booking-media-video {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3.4rem;
        height: 3.4rem;
        border-radius: 1rem;
        background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
        color: #fff;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.14);
    }

    .booking-media-video .material-symbols-outlined {
        font-size: 1.4rem;
        font-variation-settings: 'FILL' 1;
    }

    .booking-media-strip a {
        text-decoration: none;
    }

    .booking-cost-panel {
        margin-top: 1rem;
        padding: 1rem;
        border-radius: 22px;
        background: linear-gradient(180deg, #f4faff 0%, #eef7ff 100%);
        border: 1px solid #d7e8ff;
    }

    .booking-cost-row,
    .booking-cost-total {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
    }

    .booking-cost-row {
        color: #42617f;
        font-size: 0.92rem;
    }

    .booking-cost-row + .booking-cost-row {
        margin-top: 0.55rem;
    }

    .booking-cost-total {
        margin-top: 0.8rem;
        padding-top: 0.8rem;
        border-top: 1px dashed #94bdf3;
        align-items: baseline;
        color: #123e67;
    }

    .booking-cost-total strong {
        font-size: 1.45rem;
        color: #0b5ed7;
        font-family: 'DM Sans', sans-serif;
    }

    .booking-assignee {
        margin-top: 1rem;
        padding-top: 0.95rem;
        border-top: 1px solid #edf2f7;
    }

    .booking-assignee-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .booking-assignee-avatar {
        width: 2.8rem;
        height: 2.8rem;
        border-radius: 999px;
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 10px 24px rgba(59, 130, 246, 0.18);
    }

    .booking-assignee-name {
        margin: 0;
        color: #111827;
        font-size: 0.96rem;
        font-weight: 700;
    }

    .booking-assignee-pending {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        border-radius: 16px;
        padding: 0.72rem 0.85rem;
        background: #f8fbff;
        border: 1px dashed #c6d7ef;
        color: #46637f;
    }

    .booking-assignee-pending .material-symbols-outlined {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.45rem;
        height: 2.45rem;
        border-radius: 999px;
        background: #d9f0ff;
        color: #0284c7;
        font-size: 1.25rem;
    }

    .booking-assignee-pending strong {
        display: block;
        color: #0f172a;
        font-size: 0.92rem;
    }

    .booking-assignee-pending span:last-child {
        display: block;
        margin-top: 0.15rem;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .booking-action-area {
        margin-top: 0.9rem;
        display: grid;
        gap: 0.7rem;
    }

    .booking-action-button,
    .booking-action-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        width: 100%;
        min-height: 3.2rem;
        border: none;
        border-radius: 999px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.96rem;
        font-weight: 700;
        text-decoration: none;
        transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
    }

    .booking-action-button:hover,
    .booking-action-link:hover {
        transform: translateY(-1px);
    }

    .booking-action-button--primary,
    .booking-action-link--primary {
        background: linear-gradient(135deg, #36a4ff 0%, #2583f7 100%);
        color: #fff;
        box-shadow: 0 16px 32px rgba(37, 131, 247, 0.24);
    }

    .booking-action-button--danger {
        background: linear-gradient(135deg, #4baeff 0%, #2484ea 100%);
        color: #fff;
        box-shadow: 0 16px 32px rgba(36, 132, 234, 0.22);
    }

    .booking-action-button--warning {
        background: linear-gradient(135deg, #f6b81f 0%, #f59e0b 100%);
        color: #fff;
        box-shadow: 0 16px 32px rgba(245, 158, 11, 0.24);
    }

    .booking-action-button--success {
        background: linear-gradient(135deg, #40c96d 0%, #12b981 100%);
        color: #fff;
        box-shadow: 0 16px 32px rgba(18, 185, 129, 0.22);
    }

    .booking-action-button--secondary {
        background: #fff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        box-shadow: 0 10px 18px rgba(37, 99, 235, 0.1);
    }

    .booking-payment-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.7rem;
    }

    .booking-payment-grid .booking-action-button {
        min-height: 3.1rem;
        font-size: 0.94rem;
        padding: 0.8rem 0.7rem;
    }

    .booking-payment-grid .booking-action-button--cash {
        background: linear-gradient(135deg, #e8fff0 0%, #d3ffe2 100%);
        color: #0f9d58;
        border: 1px solid #a7f3d0;
        box-shadow: none;
    }

    .booking-payment-grid .booking-action-button--vnpay {
        background: linear-gradient(135deg, #1b8fff 0%, #2563eb 100%);
        color: #fff;
    }

    .booking-payment-grid .booking-action-button--momo {
        background: linear-gradient(135deg, #ff4f88 0%, #dc267f 100%);
        color: #fff;
    }

    .booking-payment-grid .booking-action-button--zalopay {
        background: linear-gradient(135deg, #1f73ff 0%, #0059ff 100%);
        color: #fff;
    }

    .booking-review-summary {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        min-height: 3rem;
        border-radius: 999px;
        background: #f8fbff;
        border: 1px solid #dbe7fb;
        color: #0f172a;
        font-weight: 700;
        padding: 0.55rem 0.9rem;
    }

    .booking-review-stars {
        color: #f59e0b;
        letter-spacing: 0.04em;
    }

    .booking-review-stars .material-symbols-outlined {
        font-size: 1.05rem;
        font-variation-settings: 'FILL' 1, 'wght' 600, 'GRAD' 0, 'opsz' 24;
    }

    .booking-review-score {
        color: #0f172a;
        font-weight: 800;
    }

    .booking-review-state {
        padding: 0.28rem 0.58rem;
        border-radius: 999px;
        background: rgba(16, 185, 129, 0.12);
        color: #059669;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .booking-showcase-card--compact {
        padding: 0.8rem;
        border-radius: 28px;
    }

    .booking-showcase-card--compact .booking-card-media {
        min-height: 8.35rem;
        border-radius: 22px;
        padding: 0.85rem;
    }

    .booking-showcase-card--compact .booking-card-body {
        padding: 0.82rem 0.06rem 0.05rem;
    }

    .booking-showcase-card--compact .booking-card-title {
        font-size: 1.28rem;
        line-height: 1.08;
        min-height: calc(1.08em * 2);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .booking-showcase-card--compact .booking-card-meta {
        margin-top: 0.52rem;
        font-size: 0.89rem;
    }

    .booking-showcase-card--compact .booking-card-meta .material-symbols-outlined {
        font-size: 1rem;
    }

    .booking-compact-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-top: 0.68rem;
        min-height: 2rem;
    }

    .booking-showcase-card--compact .booking-status-chip {
        margin-top: 0;
        padding: 0.42rem 0.72rem;
        font-size: 0.76rem;
    }

    .booking-showcase-card--compact .service-tags {
        margin-top: 0;
    }

    .booking-showcase-card--compact .service-tag {
        padding: 0.32rem 0.56rem;
        font-size: 0.73rem;
    }

    .booking-quick-stack {
        display: grid;
        gap: 0.52rem;
        margin-top: 0.82rem;
    }

    .booking-address-line,
    .booking-transport-line {
        display: flex;
        align-items: flex-start;
        gap: 0.52rem;
        border-radius: 15px;
        padding: 0.68rem 0.78rem;
        background: #f8fbff;
        border: 1px solid #e5eefc;
        color: #36506d;
        font-size: 0.86rem;
        line-height: 1.4;
        min-height: 4.1rem;
    }

    .booking-address-line .material-symbols-outlined,
    .booking-transport-line .material-symbols-outlined {
        flex-shrink: 0;
        color: #2563eb;
        font-size: 1rem;
    }

    .booking-address-line span:last-child,
    .booking-transport-line span:last-child {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .booking-card-footer {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 0.75rem;
        margin-top: 0.82rem;
        padding-top: 0.82rem;
        border-top: 1px solid #edf2f7;
        min-height: 4.1rem;
    }

    .booking-assignee--compact {
        min-width: 0;
        margin-top: 0;
        padding-top: 0;
        border-top: none;
    }

    .booking-assignee--compact .booking-assignee-info {
        gap: 0.62rem;
    }

    .booking-assignee--compact .booking-assignee-avatar {
        width: 2.35rem;
        height: 2.35rem;
    }

    .booking-assignee--compact .booking-assignee-name {
        font-size: 0.9rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .booking-assignee--compact .booking-assignee-phone {
        display: none;
    }

    .booking-assignee--compact .booking-assignee-pending {
        padding: 0;
        background: transparent;
        border: none;
        gap: 0.55rem;
    }

    .booking-assignee--compact .booking-assignee-pending .material-symbols-outlined {
        width: 1.9rem;
        height: 1.9rem;
        font-size: 0.95rem;
    }

    .booking-assignee--compact .booking-assignee-pending strong {
        font-size: 0.86rem;
    }

    .booking-assignee--compact .booking-assignee-pending span:last-child {
        display: none;
    }

    .booking-total-mini {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.2rem;
        flex-shrink: 0;
    }

    .booking-total-mini span {
        color: #5e748d;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .booking-total-mini strong {
        color: #0b5ed7;
        font-family: 'Roboto', sans-serif;
        font-weight: 800;
        font-size: 1.32rem;
        line-height: 1;
    }

    .booking-action-area--compact {
        margin-top: auto;
        padding-top: 0.72rem;
    }

    .booking-action-area--compact .booking-action-button,
    .booking-action-area--compact .booking-action-link {
        min-height: 2.9rem;
        font-size: 0.9rem;
    }

    .booking-action-area--compact .booking-review-summary {
        min-height: 2.65rem;
        font-size: 0.85rem;
    }

    .history-feedback-card {
        padding: 2.25rem 1.25rem;
        text-align: center;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid rgba(255, 255, 255, 0.95);
        box-shadow: 0 22px 42px rgba(59, 130, 246, 0.16);
    }

    .history-feedback-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 5rem;
        height: 5rem;
        border-radius: 999px;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%);
        color: #2563eb;
    }

    .history-feedback-icon .material-symbols-outlined {
        font-size: 2rem;
        font-variation-settings: 'FILL' 1;
    }

    .history-feedback-card h3 {
        margin: 0;
        color: #0f172a;
        font-size: 1.55rem;
        font-weight: 800;
    }

    .history-feedback-card p {
        margin: 0.8rem auto 0;
        max-width: 30rem;
        color: #64748b;
        line-height: 1.7;
    }

    .history-feedback-card .history-cta {
        margin-top: 1.5rem;
    }

    .review-modal-shell .modal-content {
        border-radius: 30px;
        overflow: hidden;
    }

    .review-modal-shell .modal-header {
        padding: 1.5rem 1.5rem 0.25rem;
    }

    .review-modal-shell .modal-body {
        padding: 0.75rem 1.5rem 1.5rem;
    }

    .review-modal-shell .modal-title {
        font-size: 1.5rem;
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
        transition: transform 0.18s ease, color 0.18s ease;
        font-size: 2.4rem;
    }

    .star-rating label .material-symbols-outlined {
        font-size: inherit;
        font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 48;
        transition: font-variation-settings 0.18s ease;
    }

    .star-rating label:hover {
        transform: translateY(-1px);
    }

    .star-rating input:checked~label,
    .star-rating label:hover,
    .star-rating label:hover~label {
        color: #f59e0b;
    }

    .star-rating input:checked~label .material-symbols-outlined,
    .star-rating label:hover .material-symbols-outlined,
    .star-rating label:hover~label .material-symbols-outlined {
        font-variation-settings: 'FILL' 1, 'wght' 700, 'GRAD' 0, 'opsz' 48;
    }

    .review-rating-caption {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.75rem;
        margin-bottom: 1rem;
        padding: 0.65rem 1rem;
        border-radius: 999px;
        background: #f8fbff;
        border: 1px solid #dbe7fb;
        color: #36506d;
        font-size: 0.92rem;
        font-weight: 700;
    }

    .booking-complaint-state {
        margin: 0;
        padding: 0.58rem 0.78rem;
        border-radius: 14px;
        background: #fff4ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .complaint-reason-list {
        display: grid;
        gap: 0.65rem;
    }

    .complaint-reason-item {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        padding: 0.7rem 0.8rem;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        text-align: left;
    }

    .complaint-reason-item input {
        margin-top: 0.2rem;
    }

    .complaint-reason-item span {
        color: #0f172a;
        font-weight: 600;
        font-size: 0.92rem;
        line-height: 1.45;
    }

    @media (max-width: 1199.98px) {
        .metric-grid {
            margin-top: 1rem;
        }
    }

    @media (max-width: 991.98px) {
        .history-hero {
            border-radius: 30px;
        }

        .metric-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .booking-card-title {
            font-size: 1.35rem;
        }

        .booking-card-footer {
            grid-template-columns: 1fr;
            align-items: stretch;
        }

        .booking-total-mini {
            align-items: flex-start;
        }
    }

    @media (max-width: 767.98px) {
        .booking-history-shell {
            padding-top: 1rem;
        }

        .history-breadcrumb {
            font-size: 0.95rem;
        }

        .history-filter-wrap {
            border-radius: 20px;
        }

        .history-filter-controls {
            align-items: stretch;
        }

        .history-service-filter {
            width: 100%;
            min-width: 0;
        }

        .history-hero {
            padding: 1.2rem;
            border-radius: 26px;
        }

        .metric-grid {
            grid-template-columns: 1fr;
        }

        .history-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .booking-pill-group {
            gap: 0.55rem;
        }

        .booking-filter-pill {
            padding: 0.82rem 1rem;
            font-size: 0.9rem;
        }

        .booking-pagination {
            width: 100%;
            justify-content: center;
            border-radius: 24px;
        }

        .history-filter-trigger {
            width: 100%;
            height: 3.5rem;
            border-radius: 999px;
        }

        .history-searchbar {
            min-height: 3.8rem;
            padding: 0 1.1rem;
        }

        .history-searchbar input {
            font-size: 1rem;
        }

        .booking-card-title {
            font-size: 1.28rem;
        }

        .booking-payment-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="booking-history-shell">
    <div class="container">
        <section class="history-minimal-bar">
            <div class="history-breadcrumb" aria-label="Điều hướng">
                <a href="/customer/home">Trang chủ</a>
                <span class="divider">/</span>
                <strong>Đơn hàng của tôi</strong>
            </div>

            <div class="history-filter-wrap">
                <div class="history-filter-controls">
                    <div class="booking-pill-group" id="bookingTab" role="tablist" aria-label="Bộ lọc trạng thái đơn">
                        <button class="booking-filter-pill active" data-filter="all" type="button">Tất cả</button>
                        <button class="booking-filter-pill" data-filter="active" type="button">Đang xử lý</button>
                        <button class="booking-filter-pill" data-filter="payment" type="button">Chờ thanh toán</button>
                        <button class="booking-filter-pill" data-filter="completed" type="button">Hoàn thành</button>
                        <button class="booking-filter-pill" data-filter="cancelled" type="button">Đã hủy</button>
                    </div>

                    <label class="history-service-filter" for="bookingServiceFilter" aria-label="Lọc theo dịch vụ đã đặt">
                        <span class="material-symbols-outlined">home_repair_service</span>
                        <select id="bookingServiceFilter">
                            <option value="all">Tất cả dịch vụ</option>
                        </select>
                    </label>
                </div>
            </div>
        </section>

        <div class="row g-4 bookings-grid" id="myBookingsContainer">
            <!-- Rendered by JS -->
        </div>

        <div class="booking-pagination-shell d-none">
            <div class="booking-pagination" id="bookingPagination"></div>
        </div>
    </div>
</div>

<div class="modal fade review-modal-shell" id="modalReview" tabindex="-1" aria-labelledby="modalReviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0">
                <div>
                    <h5 class="modal-title fw-bold" id="modalReviewLabel">Đánh giá dịch vụ</h5>
                    <p class="text-muted mb-0 small">Chia sẻ trải nghiệm của bạn để chúng tôi phục vụ tốt hơn.</p>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted mb-4" id="reviewWorkerName">Hãy cho chúng tôi biết cảm nhận của bạn về thợ.</p>

                <form id="formReview">
                    <input type="hidden" id="reviewBookingId" name="don_dat_lich_id">
                    <input type="hidden" id="reviewRecordId" name="review_id">

                    <div class="star-rating mb-4">
                        <input type="radio" id="star5" name="so_sao" value="5" required />
                        <label for="star5" title="5 Sao" aria-label="5 sao"><span class="material-symbols-outlined">star</span></label>

                        <input type="radio" id="star4" name="so_sao" value="4" />
                        <label for="star4" title="4 Sao" aria-label="4 sao"><span class="material-symbols-outlined">star</span></label>

                        <input type="radio" id="star3" name="so_sao" value="3" />
                        <label for="star3" title="3 Sao" aria-label="3 sao"><span class="material-symbols-outlined">star</span></label>

                        <input type="radio" id="star2" name="so_sao" value="2" />
                        <label for="star2" title="2 Sao" aria-label="2 sao"><span class="material-symbols-outlined">star</span></label>

                        <input type="radio" id="star1" name="so_sao" value="1" />
                        <label for="star1" title="1 Sao" aria-label="1 sao"><span class="material-symbols-outlined">star</span></label>
                    </div>

                    <div class="review-rating-caption" id="reviewRatingCaption">Chưa chọn số sao đánh giá</div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-bold">Nhận xét chi tiết (tùy chọn)</label>
                        <textarea class="form-control bg-light border-0" id="reviewComment" name="nhan_xet" rows="4" placeholder="Thợ làm việc như thế nào? Thời gian xử lý có đúng hẹn không?"></textarea>
                    </div>

                    <div class="mb-4 text-start">
                        <label class="form-label fw-bold">Media dinh kem (tuy chon)</label>
                        <p class="review-media-upload__hint">Toi da 5 anh va 1 video toi da 20 giay. Media se duoc luu tren cloud.</p>
                        <div class="review-media-upload">
                            <div class="review-media-upload__actions">
                                <label class="review-media-upload__picker">
                                    <input type="file" id="reviewImagesInput" accept="image/*" multiple>
                                    <span class="material-symbols-outlined">imagesmode</span>
                                    <span>Them anh</span>
                                </label>
                                <label class="review-media-upload__picker review-media-upload__picker--video">
                                    <input type="file" id="reviewVideoInput" accept="video/mp4,video/quicktime,video/webm,video/x-msvideo,video/x-ms-wmv">
                                    <span class="material-symbols-outlined">videocam</span>
                                    <span>Them video</span>
                                </label>
                                <div class="review-media-upload__summary" id="reviewMediaSummary">0/5 anh • 0/1 video</div>
                            </div>
                            <div class="review-media-gallery" id="reviewMediaPreview"></div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold" id="btnSubmitReview">Gửi đánh giá</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade review-modal-shell" id="modalComplaint" tabindex="-1" aria-labelledby="modalComplaintLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0">
                <div>
                    <h5 class="modal-title fw-bold" id="modalComplaintLabel">Gửi khiếu nại</h5>
                    <p class="text-muted mb-0 small">Nếu phát sinh lỗi sau khi thợ hoàn tất, bạn có thể gửi khiếu nại để admin xử lý.</p>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="complaintBookingContext">Khiếu nại cho đơn hàng đã hoàn tất.</p>

                <form id="formComplaint">
                    <input type="hidden" id="complaintBookingId" name="don_dat_lich_id">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Lý do khiếu nại <span class="text-danger">*</span></label>
                        <div class="complaint-reason-list">
                            <label class="complaint-reason-item">
                                <input type="radio" name="ly_do_khieu_nai" value="loi_tai_phat" required>
                                <span>Lỗi tái phát</span>
                            </label>
                            <label class="complaint-reason-item">
                                <input type="radio" name="ly_do_khieu_nai" value="linh_kien_kem_chat_luong" required>
                                <span>Linh kiện thay thế kém chất lượng</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="complaintNote">Ghi chú lỗi</label>
                        <textarea class="form-control bg-light border-0" id="complaintNote" name="ghi_chu" rows="4" placeholder="Mô tả lỗi gặp phải, thời điểm xảy ra, tình trạng hiện tại..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Hình ảnh / Video minh chứng (tùy chọn)</label>
                        <div class="review-media-upload">
                            <div class="review-media-upload__actions">
                                <label class="review-media-upload__picker">
                                    <input type="file" id="complaintImagesInput" accept="image/*" multiple>
                                    <span class="material-symbols-outlined">imagesmode</span>
                                    <span>Thêm ảnh</span>
                                </label>
                                <label class="review-media-upload__picker review-media-upload__picker--video">
                                    <input type="file" id="complaintVideoInput" accept="video/mp4,video/quicktime,video/webm,video/x-msvideo,video/x-ms-wmv">
                                    <span class="material-symbols-outlined">videocam</span>
                                    <span>Thêm video</span>
                                </label>
                            </div>
                        </div>
                        <p class="text-muted mt-2 mb-0 small">Tối đa 5 ảnh, 1 video. Mỗi file ảnh &lt;= 5MB, video &lt;= 30MB.</p>
                    </div>

                    <button type="submit" class="btn btn-danger w-100 rounded-pill py-2 fw-bold" id="btnSubmitComplaint">Gửi khiếu nại</button>
                </form>
            </div>
        </div>
    </div>
</div>

@include('customer.partials.booking-wizard-modal')
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/customer/my-bookings.js') }}"></script>
@endpush
