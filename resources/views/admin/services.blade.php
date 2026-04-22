@extends('layouts.app')

@section('title', 'Quản lý danh mục - Thợ Tốt')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --lc-primary: #0058be;
        --lc-primary-alt: #2170e4;
        --lc-surface: #f7f9fb;
        --lc-surface-low: #f2f4f6;
        --lc-surface-mid: #eceef0;
        --lc-surface-high: #e0e3e5;
        --lc-white: #ffffff;
        --lc-text: #191c1e;
        --lc-text-muted: #424754;
        --lc-outline: #727785;
        --lc-outline-faint: rgba(114,119,133,0.15);
        --lc-shadow-sm: 0 2px 8px rgba(0,88,190,0.06);
        --lc-shadow-md: 0 8px 24px rgba(0,88,190,0.08);
        --lc-shadow-lg: 0 16px 48px rgba(0,88,190,0.10);
    }

    *, *::before, *::after { box-sizing: border-box; }

    body {
        background-color: var(--lc-surface);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: var(--lc-text);
    }

    h1, h2, h3, .display-md {
        font-family: 'Manrope', sans-serif;
        font-weight: 800;
        letter-spacing: -0.025em;
    }

    /* ── PAGE HEADER ── */
    .page-header {
        padding: 2.25rem 0 1.75rem;
        border-bottom: 1px solid var(--lc-outline-faint);
        margin-bottom: 2rem;
    }

    .page-eyebrow {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--lc-primary);
        margin-bottom: 0.35rem;
    }

    .page-title {
        font-size: 2rem;
        font-weight: 800;
        color: var(--lc-text);
        margin: 0 0 0.5rem;
    }

    .page-subtitle {
        font-size: 0.9rem;
        color: var(--lc-text-muted);
        margin: 0;
        max-width: 44rem;
        line-height: 1.6;
    }

    /* ── BREADCRUMB ── */
    .breadcrumb { margin-bottom: 0.75rem; }
    .breadcrumb-item { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; }
    .breadcrumb-item a { color: var(--lc-text-muted); text-decoration: none; }
    .breadcrumb-item a:hover { color: var(--lc-primary); }

    /* ── SEGMENT TABS ── */
    .seg-tabs {
        display: inline-flex;
        gap: 0.35rem;
        background: var(--lc-surface-mid);
        border-radius: 999px;
        padding: 0.375rem;
    }

    .seg-tab {
        border: none;
        background: transparent;
        border-radius: 999px;
        padding: 0.6rem 1.35rem;
        font-family: 'Inter', sans-serif;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--lc-text-muted);
        cursor: pointer;
        transition: all 0.22s ease;
        white-space: nowrap;
    }

    .seg-tab.is-active {
        background: linear-gradient(135deg, var(--lc-primary) 0%, var(--lc-primary-alt) 100%);
        color: #fff;
        box-shadow: 0 6px 18px rgba(0,88,190,0.22);
    }

    .seg-tab:not(.is-active):hover {
        background: rgba(0,88,190,0.07);
        color: var(--lc-primary);
    }

    /* ── CATALOG PANELS ── */
    .catalog-panel { display: none; flex-direction: column; gap: 1.25rem; }
    .catalog-panel.is-active { display: flex; }

    /* ── SECTION TOOLBAR ── */
    .section-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .section-toolbar__lead h2 {
        font-size: 1.25rem;
        font-weight: 800;
        margin: 0 0 0.2rem;
        color: var(--lc-text);
    }

    .section-toolbar__lead p {
        font-size: 0.82rem;
        color: var(--lc-text-muted);
        margin: 0;
    }

    .section-toolbar__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.625rem;
        align-items: center;
    }

    /* ── BUTTONS ── */
    .btn-ic {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-weight: 600;
        font-size: 0.875rem;
        border: none;
        cursor: pointer;
        transition: all 0.22s ease;
        white-space: nowrap;
    }

    .btn-ic-primary {
        background: linear-gradient(135deg, var(--lc-primary) 0%, var(--lc-primary-alt) 100%);
        color: #fff;
        border-radius: 999px;
        padding: 0.6rem 1.4rem;
        box-shadow: 0 4px 14px rgba(0,88,190,0.18);
    }

    .btn-ic-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 22px rgba(0,88,190,0.28);
        color: #fff;
    }

    .btn-ic-secondary {
        background: var(--lc-surface-high);
        color: var(--lc-text);
        border-radius: 999px;
        padding: 0.6rem 1.2rem;
    }

    .btn-ic-secondary:hover { background: var(--lc-surface-mid); color: var(--lc-text); }

    .btn-ic-ghost {
        width: 38px;
        height: 38px;
        padding: 0;
        justify-content: center;
        background: var(--lc-white);
        color: var(--lc-primary);
        border-radius: 50%;
        box-shadow: var(--lc-shadow-sm);
    }

    .btn-ic-ghost:hover { background: var(--lc-surface-low); transform: rotate(18deg); }

    .btn-ic-sm { font-size: 0.8rem; padding: 0.4rem 0.9rem; border-radius: 999px; }

    /* ── CANVAS CARD (table wrapper) ── */
    .canvas-card {
        background: var(--lc-white);
        border-radius: 1.25rem;
        box-shadow: var(--lc-shadow-md);
        overflow: hidden;
    }

    /* ── TABLES ── */
    .ic-table { width: 100%; border-collapse: collapse; }

    .ic-table thead th {
        background: var(--lc-surface-low);
        color: var(--lc-text-muted);
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 1rem 1.25rem;
        border: none;
        white-space: nowrap;
    }

    .ic-table tbody td {
        padding: 1rem 1.25rem;
        border: none;
        border-bottom: 1px solid var(--lc-outline-faint);
        vertical-align: middle;
        background: var(--lc-white);
        transition: background 0.15s;
    }

    .ic-table tbody tr:last-child td { border-bottom: none; }
    .ic-table tbody tr:hover td { background: var(--lc-surface-low); }

    /* ── STAT CARDS ── */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
    }

    .stat-card {
        background: var(--lc-white);
        border-radius: 1rem;
        padding: 1.1rem 1.25rem;
        box-shadow: var(--lc-shadow-sm);
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .stat-card__icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg, rgba(0,88,190,0.1), rgba(33,112,228,0.06));
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--lc-primary);
        font-size: 0.9rem;
        margin-bottom: 0.4rem;
    }

    .stat-card__label {
        font-size: 0.73rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--lc-text-muted);
    }

    .stat-card__value {
        font-family: 'Manrope', sans-serif;
        font-size: 2rem;
        font-weight: 800;
        color: var(--lc-text);
        line-height: 1;
    }

    /* ── FILTER BAR ── */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.625rem;
        background: var(--lc-white);
        border-radius: 1rem;
        padding: 0.875rem 1.25rem;
        box-shadow: var(--lc-shadow-sm);
    }

    .filter-bar .form-control,
    .filter-bar .form-select {
        background: var(--lc-surface-low);
        border: 2px solid transparent;
        border-radius: 0.625rem;
        font-size: 0.875rem;
        padding: 0.5rem 0.875rem;
        color: var(--lc-text);
        transition: all 0.2s;
    }

    .filter-bar .form-control:focus,
    .filter-bar .form-select:focus {
        background: var(--lc-white);
        border-color: rgba(0,88,190,0.25);
        box-shadow: 0 0 0 3px rgba(0,88,190,0.08);
        outline: none;
    }

    /* ── TABLE FOOTER / PAGINATION ── */
    .table-footer {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        background: var(--lc-white);
        border-radius: 1rem;
        padding: 0.875rem 1.25rem;
        box-shadow: var(--lc-shadow-sm);
    }

    .table-footer__summary {
        font-size: 0.85rem;
        color: var(--lc-text-muted);
    }

    /* ── INLINE BADGES ── */
    .badge-status {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-status--active { background: #d1fae5; color: #065f46; }
    .badge-status--inactive { background: #f1f5f9; color: #475569; }

    .badge-service {
        display: inline-flex;
        padding: 0.3rem 0.65rem;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* ── THUMBNAILS ── */
    .thumb-svc {
        width: 52px;
        height: 52px;
        border-radius: 0.875rem;
        object-fit: cover;
        background: var(--lc-surface-mid);
    }

    .thumb-part {
        width: 48px;
        height: 48px;
        border-radius: 0.75rem;
        object-fit: cover;
        background: var(--lc-surface-mid);
    }

    /* ── STOCK PILL ── */
    .stock-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 3.5rem;
        padding: 0.3rem 0.6rem;
        border-radius: 999px;
        font-weight: 700;
        font-size: 0.78rem;
        background: #ecfdf5;
        color: #047857;
    }

    .stock-pill--low { background: #fff7ed; color: #c2410c; }
    .stock-pill--empty { background: #fef2f2; color: #b91c1c; }

    /* ── EXPIRY ── */
    .expiry-label { font-weight: 600; font-size: 0.85rem; color: var(--lc-text); }
    .expiry-label--expired { color: #b91c1c; }
    .expiry-label--soon { color: #c2410c; }
    .expiry-label--none { color: var(--lc-text-muted); font-weight: 400; }
    .expiry-cell {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .expiry-warning-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.28rem 0.58rem;
        border-radius: 999px;
        background: #fee2e2;
        color: #b91c1c;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        line-height: 1;
    }
    .expiry-warning-badge::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 999px;
        background: currentColor;
    }
    .expiry-warning-badge--expired {
        background: #fecaca;
        color: #991b1b;
    }

    /* ── MODALS ── */
    .modal-ic .modal-content {
        border: none;
        border-radius: 1.5rem;
        background: rgba(255,255,255,0.96);
        backdrop-filter: blur(20px);
        box-shadow: 0 24px 64px rgba(0,0,0,0.12);
    }

    .modal-ic .modal-header {
        border: none;
        padding: 1.75rem 1.75rem 0;
    }

    .modal-ic .modal-body {
        padding: 1.5rem 1.75rem 1.75rem;
    }

    .modal-ic .modal-title {
        font-family: 'Manrope', sans-serif;
        font-weight: 800;
        font-size: 1.2rem;
    }

    /* ── FORM CONTROLS (modal) ── */
    .field-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--lc-text-muted);
        margin-bottom: 0.4rem;
        display: block;
    }

    .field-input {
        width: 100%;
        background: var(--lc-surface-low);
        border: 2px solid transparent;
        border-radius: 0.75rem;
        padding: 0.7rem 1rem;
        font-size: 0.9rem;
        color: var(--lc-text);
        transition: all 0.2s;
        outline: none;
    }

    .field-input:focus {
        background: var(--lc-white);
        border-color: rgba(0,88,190,0.25);
        box-shadow: 0 0 0 4px rgba(0,88,190,0.08);
    }

    /* ── IMAGE UPLOADER ── */
    .img-uploader {
        display: flex;
        align-items: center;
        gap: 1rem;
        background: var(--lc-surface-low);
        border-radius: 1rem;
        padding: 1rem;
    }

    .img-uploader__preview {
        width: 80px;
        height: 80px;
        border-radius: 1rem;
        object-fit: cover;
        background: var(--lc-surface-mid);
        flex-shrink: 0;
    }

    .img-uploader__actions { flex: 1; }
    .img-uploader__hint { font-size: 0.72rem; color: var(--lc-text-muted); margin: 0.4rem 0 0; }

    /* ── PART UPLOAD PREVIEW ── */
    .part-preview {
        width: 88px;
        height: 88px;
        border-radius: 1rem;
        object-fit: cover;
        background: var(--lc-surface-mid);
    }

    .part-form-alert { border-radius: 0.875rem; margin-bottom: 0; }

    /* ── TOGGLE SWITCH ROW ── */
    .toggle-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--lc-surface-low);
        border-radius: 0.875rem;
        padding: 0.875rem 1rem;
    }

    .toggle-row__copy .toggle-row__label {
        font-weight: 700;
        font-size: 0.85rem;
        color: var(--lc-text);
        display: block;
    }

    .toggle-row__copy .toggle-row__hint {
        font-size: 0.78rem;
        color: var(--lc-text-muted);
        margin: 0;
    }

    /* ── IMAGE SIZES ── */
    .service-thumb {
        width: 52px;
        height: 52px;
        min-width: 52px;
        border-radius: 12px;
        object-fit: cover;
        background: var(--lc-surface-mid);
    }

    .part-thumb {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 10px;
        object-fit: cover;
        background: var(--lc-surface-mid);
        border: 1px solid var(--lc-outline-faint);
    }

    .img-uploader {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        padding: 1rem;
        border-radius: 1rem;
        background: var(--lc-surface-low);
        border: 1.5px dashed var(--lc-outline-faint);
    }

    .img-uploader__preview {
        width: 96px;
        height: 96px;
        min-width: 96px;
        border-radius: 14px;
        object-fit: cover;
        background: var(--lc-surface-mid);
    }

    .img-uploader__hint {
        font-size: 0.75rem;
        color: var(--lc-text-muted);
        margin: 0.5rem 0 0;
    }

    .part-preview {
        width: 80px;
        height: 80px;
        min-width: 80px;
        border-radius: 12px;
        object-fit: cover;
        background: var(--lc-surface-mid);
        border: 1px solid var(--lc-outline-faint);
    }

    /* ── RESPONSIVE ── */
    .stat-card__sub {
        margin-top: 0.35rem;
        font-size: 0.74rem;
        color: var(--lc-text-muted);
        line-height: 1.45;
    }

    body {
        background: #f3f5f8;
    }

    .container.pb-5 {
        max-width: 1040px;
        margin-top: 1.5rem;
        margin-bottom: 3rem;
        padding: 0 1.35rem 1.5rem !important;
        border: 1px solid rgba(114, 119, 133, 0.12);
        border-radius: 28px;
        background:
            radial-gradient(circle at top right, rgba(0, 88, 190, 0.08), transparent 24%),
            rgba(255, 255, 255, 0.78);
        box-shadow: 0 18px 48px rgba(20, 30, 50, 0.08);
        backdrop-filter: blur(18px);
    }

    .page-header {
        padding: 1.7rem 0 1rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid rgba(114, 119, 133, 0.12);
    }

    .page-eyebrow {
        font-size: 0.72rem;
        color: var(--lc-text-muted);
        letter-spacing: 0.08em;
    }

    .page-title {
        font-family: 'Inter', sans-serif;
        font-size: 2rem;
        letter-spacing: -0.05em;
        margin-bottom: 0.4rem;
    }

    .page-subtitle {
        max-width: 36rem;
        font-size: 0.9rem;
        line-height: 1.65;
    }

    .seg-tabs {
        gap: 1rem;
        padding: 0;
        background: transparent;
        border-radius: 0;
        border-bottom: 1px solid rgba(114, 119, 133, 0.12);
    }

    .seg-tab {
        position: relative;
        padding: 0 0 0.75rem;
        border-radius: 0;
        font-size: 0.88rem;
        font-weight: 600;
    }

    .seg-tab::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -1px;
        width: 100%;
        height: 2px;
        border-radius: 999px;
        background: transparent;
        transition: background 0.2s ease;
    }

    .seg-tab.is-active {
        background: transparent;
        color: var(--lc-primary);
        box-shadow: none;
    }

    .seg-tab.is-active::after {
        background: var(--lc-primary);
    }

    .seg-tab:not(.is-active):hover {
        background: transparent;
        color: var(--lc-primary);
    }

    .catalog-panel {
        gap: 1rem;
    }

    .section-toolbar,
    .filter-bar,
    .table-footer,
    .canvas-card {
        border: 1px solid rgba(114, 119, 133, 0.12);
        box-shadow: none;
    }

    .section-toolbar,
    .filter-bar,
    .table-footer {
        border-radius: 18px;
        padding: 0.9rem 1rem;
        background: rgba(255, 255, 255, 0.88);
    }

    .section-toolbar__lead h2 {
        font-family: 'Inter', sans-serif;
        font-size: 1.08rem;
        letter-spacing: -0.03em;
    }

    .section-toolbar__lead p {
        font-size: 0.8rem;
        line-height: 1.55;
    }

    .btn-ic {
        height: 38px;
        border-radius: 12px;
        font-size: 0.8rem;
    }

    .btn-ic-primary {
        padding: 0 1rem;
        background: #1768f2;
        box-shadow: 0 10px 22px rgba(23, 104, 242, 0.16);
    }

    .btn-ic-secondary {
        padding: 0 0.95rem;
        background: #ffffff;
        border: 1px solid rgba(114, 119, 133, 0.16);
    }

    .btn-ic-ghost {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        border: 1px solid rgba(114, 119, 133, 0.16);
        box-shadow: none;
    }

    .btn-ic-ghost:hover {
        transform: translateY(-1px);
        background: #eef5ff;
    }

    .stat-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.85rem;
    }

    .stat-card {
        position: relative;
        padding: 0.95rem 1rem;
        border: 1px solid rgba(114, 119, 133, 0.12);
        border-radius: 18px;
        box-shadow: none;
    }

    .service-stat-grid .stat-card,
    section[data-catalog-panel="parts"] .stat-card {
        overflow: hidden;
    }

    .service-stat-grid .stat-card::before,
    section[data-catalog-panel="parts"] .stat-card::before {
        content: '';
        position: absolute;
        inset: 0 auto auto 0;
        width: 100%;
        height: 3px;
        background: rgba(0, 88, 190, 0.16);
    }

    .service-stat-grid .stat-card:nth-child(2)::before,
    section[data-catalog-panel="parts"] .stat-card:nth-child(2)::before {
        background: rgba(247, 144, 9, 0.72);
    }

    .service-stat-grid .stat-card:nth-child(3)::before,
    section[data-catalog-panel="parts"] .stat-card:nth-child(3)::before {
        background: rgba(20, 174, 92, 0.72);
    }

    section[data-catalog-panel="parts"] .stat-card:nth-child(4) {
        display: none;
    }

    .stat-card__icon {
        width: 22px;
        height: 22px;
        border-radius: 999px;
        font-size: 0.68rem;
        background: #ebf3ff;
        color: #1768f2;
        margin-bottom: 0.55rem;
    }

    .stat-card:nth-child(2) .stat-card__icon {
        background: #fff4e5;
        color: #f79009;
    }

    .stat-card:nth-child(3) .stat-card__icon {
        background: #e9f9ef;
        color: #14ae5c;
    }

    .stat-card__label {
        font-size: 0.68rem;
        letter-spacing: 0.08em;
    }

    .stat-card__value {
        font-family: 'Inter', sans-serif;
        font-size: 1.78rem;
        letter-spacing: -0.05em;
    }

    .filter-bar .form-control,
    .filter-bar .form-select {
        height: 38px;
        border-radius: 12px;
        border: 1px solid rgba(114, 119, 133, 0.14);
        background: #ffffff;
        box-shadow: none;
    }

    .filter-bar .form-control {
        padding-left: 0.9rem;
    }

    .canvas-card {
        border-radius: 20px;
        overflow: hidden;
    }

    .ic-table thead th {
        padding: 0.95rem 1rem;
        background: #fbfcfe;
        font-size: 0.68rem;
        letter-spacing: 0.08em;
        border-bottom: 1px solid rgba(114, 119, 133, 0.12);
    }

    .ic-table tbody td {
        padding: 0.95rem 1rem;
        font-size: 0.83rem;
        border-bottom: 1px solid rgba(114, 119, 133, 0.08);
    }

    .ic-table tbody tr:hover td {
        background: #fbfcfe;
    }

    .catalog-code {
        color: var(--lc-text-muted);
        font-size: 0.77rem;
        font-weight: 700;
        letter-spacing: 0.04em;
    }

    .catalog-name {
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--lc-text);
    }

    .catalog-meta {
        display: block;
        margin-top: 0.2rem;
        color: var(--lc-text-muted);
        font-size: 0.75rem;
        line-height: 1.45;
    }

    .catalog-status {
        display: inline-flex;
        align-items: center;
        gap: 0.36rem;
        padding: 0.34rem 0.68rem;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 600;
    }

    .catalog-status::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 999px;
        background: currentColor;
    }

    .catalog-status--active {
        background: #e9f9ef;
        color: #14ae5c;
    }

    .catalog-status--inactive {
        background: #f4f5f7;
        color: #667085;
    }

    .catalog-money {
        color: #1768f2;
        font-weight: 700;
        font-size: 0.84rem;
    }

    .catalog-money--empty {
        color: var(--lc-text-muted);
        font-weight: 500;
    }

    .catalog-updated {
        color: var(--lc-text-muted);
        font-size: 0.76rem;
        white-space: nowrap;
    }

    .catalog-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.4rem;
    }

    .catalog-action-btn {
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        border: 1px solid rgba(114, 119, 133, 0.16);
        background: #ffffff;
        color: var(--lc-text-muted);
        transition: all 0.18s ease;
    }

    .catalog-action-btn:hover {
        color: #1768f2;
        background: #ebf3ff;
        border-color: rgba(23, 104, 242, 0.22);
    }

    .catalog-action-btn--danger:hover {
        color: #f04438;
        background: #feeceb;
        border-color: rgba(240, 68, 56, 0.22);
    }

    @media (max-width: 768px) {
        .section-toolbar { flex-direction: column; align-items: stretch; }
        .section-toolbar__actions { flex-wrap: wrap; }
        .filter-bar { flex-direction: column; align-items: stretch; }
        .stat-grid { grid-template-columns: repeat(2, 1fr); }
        .page-title { font-size: 1.5rem; }
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container pb-5">
    <header class="page-header">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="/admin/dashboard">Bảng điều khiển</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Danh mục</li>
                    </ol>
                </nav>
                <div class="page-eyebrow">Quản trị hệ thống</div>
                <h1 class="page-title">Quản lý danh mục</h1>
                <p class="page-subtitle">Làm mới giao diện quản trị dịch vụ và linh kiện theo bố cục compact trong Figma: ít chrome hơn, dễ quét mắt hơn và tập trung vào số liệu quan trọng.</p>
            </div>
            <div class="d-flex flex-column align-items-end gap-2 pt-1">
                <div class="seg-tabs" role="tablist">
                    <button type="button" class="seg-tab catalog-tab is-active" data-catalog-tab="services" aria-selected="true">
                        <i class="fas fa-th-list me-1"></i> Dịch vụ
                    </button>
                    <button type="button" class="seg-tab catalog-tab" data-catalog-tab="parts" aria-selected="false">
                        <i class="fas fa-microchip me-1"></i> Linh kiện
                    </button>
                </div>
            </div>
        </div>
    </header>

    <section class="catalog-panel is-active" data-catalog-panel="services">
        <div class="section-toolbar">
            <div class="section-toolbar__lead">
                <h2>Danh mục dịch vụ</h2>
                <p>Quản trị trạng thái, mô tả và hình ảnh của từng dịch vụ.</p>
            </div>
            <div class="section-toolbar__actions">
                <select class="form-select" id="serviceStatusFilter" style="min-width:190px;background:var(--lc-surface-low);border:2px solid transparent;border-radius:.625rem;font-size:.875rem;">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1">Đang hoạt động</option>
                    <option value="0">Đã ẩn</option>
                </select>
                <select class="form-select" id="servicePageSize" style="min-width:140px;background:var(--lc-surface-low);border:2px solid transparent;border-radius:.625rem;font-size:.875rem;">
                    <option value="10">10 / trang</option>
                    <option value="20">20 / trang</option>
                    <option value="40">40 / trang</option>
                </select>
                <button class="btn-ic btn-ic-ghost" id="btnRefreshServices" title="Làm mới" type="button">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button class="btn-ic btn-ic-primary" data-bs-toggle="modal" data-bs-target="#serviceModal" id="btnAddService" type="button">
                    <i class="fas fa-plus"></i> Thêm dịch vụ
                </button>
            </div>
        </div>

        <div class="stat-grid service-stat-grid">
            <div class="stat-card">
                <div class="stat-card__icon"><i class="fas fa-layer-group"></i></div>
                <div class="stat-card__label">Tổng dịch vụ</div>
                <div class="stat-card__value" id="serviceStatTotal">0</div>
                <div class="stat-card__sub" id="serviceStatTotalMeta">Đang tải dữ liệu dịch vụ</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon"><i class="fas fa-circle-check"></i></div>
                <div class="stat-card__label">Đang hoạt động</div>
                <div class="stat-card__value" id="serviceStatActive">0</div>
                <div class="stat-card__sub" id="serviceStatActiveMeta">Sẵn sàng cho đặt lịch</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon"><i class="fas fa-eye-slash"></i></div>
                <div class="stat-card__label">Đã ẩn</div>
                <div class="stat-card__value" id="serviceStatHidden">0</div>
                <div class="stat-card__sub" id="serviceStatHiddenMeta">Không hiện cho khách hàng</div>
            </div>
        </div>

        <div class="canvas-card">
            <div class="table-responsive">
                <table class="ic-table">
                    <thead>
                        <tr>
                            <th style="padding-left:1.5rem">Mã</th>
                            <th>Dịch vụ</th>
                            <th>Mô tả</th>
                            <th>Trạng thái</th>
                            <th>Cập nhật</th>
                            <th style="text-align:right;padding-right:1.5rem">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="servicesTableBody">
                        <tr>
                            <td colspan="6" style="text-align:center;padding:3rem">
                                <div class="spinner-border text-primary" style="width:2.5rem;height:2.5rem" role="status"></div>
                                <p class="text-muted mt-3 mb-0" style="font-size:.85rem">Đang tải dịch vụ...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-footer">
            <div class="table-footer__summary" id="servicePaginationSummary">Đang hiển thị 0 / 0 dịch vụ</div>
            <div class="d-flex gap-2">
                <button type="button" class="btn-ic btn-ic-secondary btn-ic-sm" id="btnPrevServicePage">
                    <i class="fas fa-chevron-left"></i> Trước
                </button>
                <button type="button" class="btn btn-light btn-sm px-3" id="servicePageIndicator" disabled style="border-radius:999px;font-size:.82rem">Trang 1 / 1</button>
                <button type="button" class="btn-ic btn-ic-secondary btn-ic-sm" id="btnNextServicePage">
                    Sau <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>

    <section class="catalog-panel" data-catalog-panel="parts">
        <!-- Toolbar -->
        <div class="section-toolbar">
            <div class="section-toolbar__lead">
                <h2>Danh mục linh kiện</h2>
                <p>Quản lý linh kiện theo từng dịch vụ, báo giá nhanh và giữ bộ giá tham khảo tập trung.</p>
            </div>
            <div class="section-toolbar__actions">
                <a href="/admin/tri-thuc-sua-chua" class="btn-ic btn-ic-secondary text-decoration-none">
                    <i class="fas fa-sitemap"></i> Tri thức sửa chữa
                </a>
                <button class="btn-ic btn-ic-primary" id="btnAddPart" type="button" data-bs-toggle="modal" data-bs-target="#partModal">
                    <i class="fas fa-plus"></i> Thêm linh kiện
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-card__icon"><i class="fas fa-boxes"></i></div>
                <div class="stat-card__label">Tổng lượng linh kiện</div>
                <div class="stat-card__value" id="partStatTotal">0</div>
                <div class="stat-card__sub" id="partStatTotalMeta">Chưa có dữ liệu tồn kho</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="stat-card__label">Cảnh báo tồn kho</div>
                <div class="stat-card__value" id="partStatPriced">0</div>
                <div class="stat-card__sub" id="partStatPricedMeta">Chưa có mục cần xử lý</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon"><i class="fas fa-sack-dollar"></i></div>
                <div class="stat-card__label">Giá trị tồn kho</div>
                <div class="stat-card__value" id="partStatUnpriced">0 đ</div>
                <div class="stat-card__sub" id="partStatUnpricedMeta">Chưa có linh kiện có giá</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon"><i class="fas fa-concierge-bell"></i></div>
                <div class="stat-card__label">Dịch vụ có linh kiện</div>
                <div class="stat-card__value" id="partStatServices">0</div>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="filter-bar">
            <input type="search" class="form-control" id="partSearchInput" placeholder="Tìm theo mã, tên linh kiện, dịch vụ..." style="max-width:280px">
            <select class="form-select" id="partServiceFilter" style="max-width:220px">
                <option value="">Tất cả dịch vụ</option>
            </select>
            <select class="form-select" id="partSortSelect" style="max-width:200px">
                <option value="updated_desc">Sắp xếp mới nhất</option>
                <option value="updated_asc">Sắp xếp cũ nhất</option>
                <option value="name_asc">Tên A-Z</option>
                <option value="price_desc">Giá trị cao nhất</option>
                <option value="price_asc">Giá trị thấp nhất</option>
            </select>
            <select class="form-select" id="partPageSize" style="max-width:140px">
                <option value="12">12 / trang</option>
                <option value="24">24 / trang</option>
                <option value="48">48 / trang</option>
            </select>
            <button class="btn-ic btn-ic-ghost ms-auto" id="btnRefreshParts" type="button" title="Làm mới">
                <i class="fas fa-sync-alt"></i>
            </button>
            <span class="table-footer__summary" id="partVisibleCount" style="white-space:nowrap">0 linh kiện</span>
        </div>

        <!-- Table -->
        <div class="canvas-card">
            <div class="table-responsive">
                <table class="ic-table">
                    <thead>
                        <tr>
                            <th style="padding-left:1.5rem">Mã</th>
                            <th>Tên linh kiện</th>
                            <th>Tồn kho</th>
                            <th>Giá trị</th>
                            <th>Hạn sử dụng</th>
                            <th style="text-align:right;padding-right:1.5rem">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="partsTableBody">
                        <tr>
                            <td colspan="6" style="text-align:center;padding:3rem">
                                <div class="spinner-border text-primary" style="width:2.5rem;height:2.5rem" role="status"></div>
                                <p class="text-muted mt-3 mb-0" style="font-size:.85rem">Đang tải linh kiện...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination footer -->
        <div class="table-footer">
            <div class="table-footer__summary" id="partPaginationSummary">Đang hiển thị 0 / 0 linh kiện</div>
            <div class="d-flex gap-2">
                <button type="button" class="btn-ic btn-ic-secondary btn-ic-sm" id="btnPrevPartPage">
                    <i class="fas fa-chevron-left"></i> Trước
                </button>
                <button type="button" class="btn btn-light btn-sm px-3" id="partPageIndicator" disabled style="border-radius:999px;font-size:.82rem">Trang 1 / 1</button>
                <button type="button" class="btn-ic btn-ic-secondary btn-ic-sm" id="btnNextPartPage">
                    Sau <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>
</div>

<!-- SERVICE MODAL -->
<div class="modal fade modal-ic" id="serviceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" id="serviceModalLabel">Thêm dịch vụ mới</h3>
                    <p style="font-size:.82rem;color:var(--lc-text-muted);margin:.25rem 0 0">Nhập thông tin chi tiết để khởi tạo dịch vụ hệ thống.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="serviceForm" style="display:grid;gap:1.25rem">
                    <input type="hidden" id="serviceId">
                    <div>
                        <label class="field-label" for="serviceName">Tên dịch vụ</label>
                        <input type="text" class="field-input" id="serviceName" placeholder="VD: Sửa tủ lạnh" required maxlength="255">
                    </div>
                    <div>
                        <label class="field-label" for="serviceDesc">Mô tả chi tiết</label>
                        <textarea class="field-input" id="serviceDesc" rows="3" placeholder="Nhập mô tả ngắn gọn về dịch vụ..." style="resize:vertical"></textarea>
                    </div>
                    <div>
                        <label class="field-label">Hình ảnh minh họa</label>
                        <div class="img-uploader">
                            <img src="{{ asset('assets/images/logontu.png') }}" alt="Xem trước" class="img-uploader__preview" id="serviceImagePreview">
                            <div class="img-uploader__actions">
                                <input type="file" class="d-none" id="serviceImage" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp">
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn-ic btn-ic-primary btn-ic-sm" onclick="document.getElementById('serviceImage').click()">
                                        <i class="fas fa-camera"></i> Chọn file
                                    </button>
                                    <button type="button" class="btn-ic btn-ic-secondary btn-ic-sm" id="btnRemoveServiceImage">Xóa</button>
                                </div>
                                <p class="img-uploader__hint">Hỗ trợ JPG, PNG, WEBP. Tối đa 5MB.</p>
                            </div>
                        </div>
                    </div>
                    <div class="toggle-row">
                        <div class="toggle-row__copy">
                            <span class="toggle-row__label">Trạng thái hoạt động</span>
                            <p class="toggle-row__hint">Cho phép khách hàng đặt dịch vụ này ngay lập tức.</p>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="serviceActive" checked style="width:2.5em;height:1.25em;cursor:pointer">
                        </div>
                    </div>
                    <button type="submit" class="btn-ic btn-ic-primary w-100 justify-content-center py-3" id="btnSaveService">
                        <i class="fas fa-check-circle"></i> Lưu thay đổi dịch vụ
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- PART MODAL -->
<div class="modal fade modal-ic" id="partModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="partModalLabel">Thêm linh kiện</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="partForm" style="display:grid;gap:1rem">
                    <input type="hidden" id="partId">
                    <div class="alert alert-danger d-none part-form-alert" id="partFormAlert" role="alert"></div>
                    <div>
                        <label class="field-label" for="partService">Dịch vụ</label>
                        <select class="field-input" id="partService" required>
                            <option value="">Chọn dịch vụ</option>
                        </select>
                        <div class="invalid-feedback" id="partServiceError"></div>
                    </div>
                    <div>
                        <label class="field-label" for="partName">Tên linh kiện</label>
                        <input type="text" class="field-input" id="partName" required maxlength="255">
                        <div class="invalid-feedback" id="partNameError"></div>
                    </div>
                    <div>
                        <label class="field-label" for="partPrice">Giá tham khảo (VND)</label>
                        <input type="number" class="field-input" id="partPrice" min="0" step="1000" placeholder="VD: 350000">
                        <div class="invalid-feedback" id="partPriceError"></div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                        <div>
                            <label class="field-label" for="partStock">Tồn kho</label>
                            <input type="number" class="field-input" id="partStock" min="0" value="0">
                            <div class="invalid-feedback" id="partStockError"></div>
                        </div>
                        <div>
                            <label class="field-label" for="partExpiry">Hạn sử dụng</label>
                            <input type="date" class="field-input" id="partExpiry">
                            <div class="invalid-feedback" id="partExpiryError"></div>
                        </div>
                    </div>
                    <div>
                        <label class="field-label" for="partImage">Hình ảnh linh kiện</label>
                        <input type="file" class="field-input" id="partImage" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp" style="padding:.5rem">
                        <div class="invalid-feedback" id="partImageError"></div>
                        <div class="d-flex align-items-center gap-3 mt-2">
                            <img src="{{ asset('assets/images/logontu.png') }}" alt="Xem trước" class="part-preview" id="partImagePreview">
                            <div>
                                <p style="font-size:.75rem;color:var(--lc-text-muted);margin:0 0 .5rem">Hỗ trợ JPG, PNG, GIF, WEBP. Tối đa 5MB.</p>
                                <button type="button" class="btn-ic btn-ic-secondary btn-ic-sm" id="btnRemovePartImage">Xóa ảnh</button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn-ic btn-ic-primary w-100 justify-content-center py-3" id="btnSavePart">
                        <i class="fas fa-save"></i> Lưu linh kiện
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/services.js') }}?v={{ filemtime(public_path('assets/js/admin/services.js')) }}"></script>
<script type="module" src="{{ asset('assets/js/admin/parts.js') }}?v={{ filemtime(public_path('assets/js/admin/parts.js')) }}"></script>
@endpush
