@extends('layouts.app')
@section('title', 'Viá»‡c má»›i - Thá»£ Tá»‘t NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&family=Material+Symbols+Outlined" rel="stylesheet"/>
<style>
  .material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24;
    vertical-align: middle;
  }

  .worker-jobs-shell {
    min-height: 100vh;
    display: flex;
    background:
      radial-gradient(circle at top left, rgba(14, 165, 233, 0.08), transparent 28%),
      linear-gradient(180deg, #f7fbfe 0%, #f6fafc 100%);
  }

  .worker-jobs-main {
    flex: 1;
    min-height: 100vh;
    margin-left: 240px;
    background: transparent;
  }

  .worker-jobs-topbar {
    position: sticky;
    top: 0;
    z-index: 40;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1.5rem;
    padding: 1.5rem 2rem 1.35rem;
    background: rgba(246, 250, 252, 0.92);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid #e2e8f0;
  }

  .worker-jobs-topbar-copy h1 {
    margin: 0;
    color: #171c1e;
    font-size: 1.85rem;
    font-weight: 700;
    letter-spacing: -0.03em;
  }

  .worker-jobs-kicker {
    margin-bottom: 0.4rem;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
  }

  .worker-jobs-summary {
    margin: 0.5rem 0 0;
    max-width: 42rem;
    color: #475569;
    font-size: 0.98rem;
    font-weight: 500;
    line-height: 1.6;
  }

  .worker-jobs-topbar-chips {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.95rem;
    flex-wrap: wrap;
  }

  .worker-jobs-chip {
    display: inline-flex;
    align-items: center;
    min-height: 2.15rem;
    padding: 0.45rem 0.9rem;
    border-radius: 999px;
    border: 1px solid #dbe7ef;
    background: #fff;
    color: #334155;
    font-size: 0.79rem;
    font-weight: 700;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
  }

  .worker-jobs-chip--info {
    background: #e0f2fe;
    border-color: transparent;
    color: #0369a1;
  }

  .worker-jobs-chip--success {
    background: #ecfdf5;
    border-color: transparent;
    color: #166534;
  }

  .worker-jobs-topbar-actions {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    flex-shrink: 0;
  }

  .worker-jobs-refresh-btn {
    min-width: 9rem;
    height: 2.95rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    border: none;
    border-radius: 1rem;
    background: linear-gradient(135deg, #0ea5e9, #006591);
    color: #fff;
    font-size: 0.92rem;
    font-weight: 700;
    box-shadow: 0 16px 34px rgba(0, 101, 145, 0.18);
    transition: transform 180ms ease, box-shadow 180ms ease, opacity 180ms ease;
  }

  .worker-jobs-refresh-btn:hover {
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 18px 38px rgba(0, 101, 145, 0.22);
  }

  .worker-jobs-refresh-btn:disabled {
    opacity: 0.8;
    cursor: wait;
  }

  .jobs-board {
    padding: 1.5rem 2rem 2rem;
  }

  .jobs-lead-card {
    display: grid;
    grid-template-columns: minmax(0, 1.3fr) auto;
    gap: 1.25rem;
    padding: 1.35rem 1.5rem;
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid #e2e8f0;
    border-radius: 1.75rem;
    box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08);
  }

  .jobs-lead-copy {
    min-width: 0;
  }

  .jobs-lead-kicker {
    color: #0284c7;
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .jobs-lead-copy h2 {
    margin: 0.45rem 0 0;
    color: #0f172a;
    font-size: 1.7rem;
    font-weight: 700;
    line-height: 1.15;
    letter-spacing: -0.03em;
  }

  .jobs-lead-copy p {
    margin: 0.7rem 0 0;
    max-width: 42rem;
    color: #64748b;
    font-size: 0.95rem;
    line-height: 1.65;
  }

  .jobs-lead-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(132px, 1fr));
    gap: 0.85rem;
  }

  .jobs-stat-card {
    padding: 1rem 1rem 0.95rem;
    background: #f8fbff;
    border: 1px solid #e0eef7;
    border-radius: 1.35rem;
    min-width: 0;
  }

  .jobs-stat-label {
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }

  .jobs-stat-value {
    margin-top: 0.4rem;
    color: #0f172a;
    font-size: 1.9rem;
    font-weight: 700;
    letter-spacing: -0.04em;
    line-height: 1;
  }

  .jobs-stat-meta {
    margin-top: 0.45rem;
    color: #0284c7;
    font-size: 0.74rem;
    font-weight: 700;
    line-height: 1.45;
  }

  .jobs-board-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 1.5rem;
    margin-top: 1.5rem;
    align-items: start;
  }

  .jobs-list {
    display: grid;
    gap: 1rem;
  }

  .jobs-job-card {
    position: relative;
    overflow: hidden;
    padding: 1.35rem 1.45rem 1.4rem;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.99), rgba(247, 251, 255, 0.96));
    border: 1px solid rgba(192, 220, 241, 0.88);
    border-radius: 1.85rem;
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.07);
  }

  .jobs-job-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, #38bdf8, #0ea5e9 48%, #7dd3fc);
  }

  .jobs-job-header,
  .jobs-job-footer,
  .jobs-job-meta-inline,
  .jobs-job-stats,
  .jobs-job-actions,
  .jobs-job-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .jobs-job-header {
    align-items: flex-start;
    gap: 1rem;
  }

  .jobs-job-icon {
    width: 3.6rem;
    height: 3.6rem;
    flex: 0 0 3.6rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 1rem;
    background: linear-gradient(180deg, #d7edff, #bee3fb);
    color: #0369a1;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
  }

  .jobs-job-icon .material-symbols-outlined {
    font-size: 1.65rem;
    font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24;
  }

  .jobs-job-summary {
    min-width: 0;
    flex: 1 1 24rem;
  }

  .jobs-tag {
    display: inline-flex;
    align-items: center;
    min-height: 1.9rem;
    padding: 0.38rem 0.72rem;
    border-radius: 999px;
    font-size: 0.73rem;
    font-weight: 800;
    letter-spacing: 0.05em;
  }

  .jobs-tag--category {
    background: #e0f2fe;
    color: #0369a1;
  }

  .jobs-tag--mode {
    background: #ecfeff;
    color: #0f766e;
  }

  .jobs-job-status {
    display: inline-flex;
    align-items: center;
    min-height: 2rem;
    padding: 0.42rem 0.85rem;
    border-radius: 999px;
    background: #dbeafe;
    color: #0369a1;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    white-space: nowrap;
  }

  .jobs-job-title {
    margin: 0.72rem 0 0;
    color: #171c1e;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.3rem;
    font-weight: 800;
    line-height: 1.25;
    letter-spacing: -0.03em;
  }

  .jobs-job-meta-inline {
    margin-top: 0.45rem;
    align-items: center;
    gap: 0.45rem 0.72rem;
    color: #475569;
    font-size: 0.86rem;
  }

  .jobs-job-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 0.38rem;
    min-width: 0;
  }

  .jobs-job-meta-item .material-symbols-outlined {
    font-size: 1rem;
    color: #64748b;
  }

  .jobs-job-meta-dot {
    width: 4px;
    height: 4px;
    border-radius: 999px;
    background: #cbd5e1;
    flex: 0 0 auto;
  }

  .jobs-job-sidehead {
    margin-left: auto;
    display: grid;
    justify-items: end;
    gap: 0.45rem;
    text-align: right;
  }

  .jobs-job-price {
    color: #171c1e;
    font-size: 1.9rem;
    font-weight: 800;
    letter-spacing: -0.05em;
    line-height: 1;
  }

  .jobs-job-price-meta {
    color: #64748b;
    font-size: 0.8rem;
    font-weight: 700;
  }

  .jobs-job-copy {
    margin: 0.95rem 0 0;
    color: #64748b;
    font-size: 0.9rem;
    line-height: 1.68;
  }

  .jobs-job-location {
    margin-top: 0.95rem;
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    color: #334155;
    font-size: 0.9rem;
    line-height: 1.6;
  }

  .jobs-job-location .material-symbols-outlined {
    margin-top: 0.12rem;
    font-size: 1.02rem;
    color: #0ea5e9;
  }

  .jobs-job-note {
    margin-top: 1rem;
    display: grid;
    grid-template-columns: 20px minmax(0, 1fr);
    gap: 0.75rem;
    padding: 1rem 1rem 1rem 0.95rem;
    border-left: 4px solid #0ea5e9;
    border-radius: 1.2rem;
    background: #f0f9ff;
  }

  .jobs-job-note .material-symbols-outlined {
    margin-top: 0.08rem;
    font-size: 1.08rem;
  }

  .jobs-job-note strong {
    display: block;
    color: #0f172a;
    font-size: 0.92rem;
    font-weight: 700;
  }

  .jobs-job-note p {
    margin: 0.26rem 0 0;
    color: #475569;
    font-size: 0.85rem;
    line-height: 1.6;
  }

  .jobs-job-note-copy {
    min-width: 0;
  }

  .jobs-job-note--warn {
    border-left-color: #f97316;
    background: #fff7ed;
  }

  .jobs-job-note--warn .material-symbols-outlined,
  .jobs-job-note--warn strong {
    color: #c2410c;
  }

  .jobs-job-note--success {
    border-left-color: #16a34a;
    background: #f0fdf4;
  }

  .jobs-job-note--success .material-symbols-outlined,
  .jobs-job-note--success strong {
    color: #166534;
  }

  .jobs-job-note--info {
    border-left-color: #0ea5e9;
    background: #eff6ff;
  }

  .jobs-job-note--info .material-symbols-outlined,
  .jobs-job-note--info strong {
    color: #0369a1;
  }

  .jobs-job-stats {
    margin-top: 1rem;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
  }

  .jobs-job-stat {
    min-width: 0;
    padding: 0.9rem 1rem;
    border-radius: 1.1rem;
    border: 1px solid #e0eef7;
    background: #f8fbff;
  }

  .jobs-job-stat-label {
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .jobs-job-stat-value {
    margin-top: 0.42rem;
    color: #0f172a;
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.25;
  }

  .jobs-job-stat-caption {
    margin-top: 0.28rem;
    color: #64748b;
    font-size: 0.79rem;
    line-height: 1.5;
  }

  .jobs-job-footer {
    margin-top: 1.15rem;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }

  .jobs-job-actions {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-left: auto;
    flex-wrap: wrap;
  }

  .jobs-link-button {
    min-height: 2.9rem;
    padding: 0 1.05rem;
    border-radius: 1rem;
    background: #eef4f8;
    color: #0f172a;
    font-size: 0.88rem;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
  }

  .jobs-link-button:hover {
    background: #e2e8f0;
    color: #0f172a;
  }

  .jobs-primary-button {
    width: auto;
    min-width: 9.5rem;
    min-height: 2.9rem;
    border: none;
    border-radius: 1rem;
    padding: 0 1.2rem;
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    color: #fff;
    font-size: 0.92rem;
    font-weight: 700;
    box-shadow: 0 14px 28px rgba(14, 165, 233, 0.22);
    transition: transform 160ms ease, box-shadow 160ms ease, opacity 160ms ease;
  }

  .jobs-primary-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 32px rgba(14, 165, 233, 0.26);
  }

  .jobs-primary-button:disabled {
    opacity: 0.72;
    cursor: wait;
  }

  .jobs-side-column {
    display: grid;
    gap: 1rem;
  }

  .jobs-panel {
    padding: 1.25rem;
    background: rgba(255, 255, 255, 0.97);
    border: 1px solid #e2e8f0;
    border-radius: 1.75rem;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
  }

  .jobs-panel--dark {
    background: #0f172a;
    border-color: transparent;
    box-shadow: 0 18px 38px rgba(15, 23, 42, 0.18);
  }

  .jobs-panel-title {
    margin: 0;
    color: #171c1e;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -0.02em;
  }

  .jobs-panel--dark .jobs-panel-title {
    color: #fff;
  }

  .jobs-panel-copy {
    margin: 0.45rem 0 0;
    color: #64748b;
    font-size: 0.87rem;
    line-height: 1.6;
  }

  .jobs-panel--dark .jobs-panel-copy {
    color: #cbd5e1;
  }

  .jobs-filter-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
    margin-top: 1rem;
  }

  .jobs-filter-pill {
    display: inline-flex;
    align-items: center;
    min-height: 2rem;
    padding: 0.42rem 0.78rem;
    border-radius: 999px;
    background: #f1f5f9;
    color: #475569;
    font-size: 0.76rem;
    font-weight: 700;
  }

  .jobs-filter-pill--active {
    background: #e0f2fe;
    color: #0369a1;
  }

  .jobs-slot-list,
  .jobs-playbook-list {
    display: grid;
    gap: 0.7rem;
    margin-top: 1rem;
  }

  .jobs-slot-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
    padding: 0.85rem 0.95rem;
    background: #f8fafc;
    border-radius: 1rem;
  }

  .jobs-slot-item.is-open {
    background: #ecfdf5;
  }

  .jobs-slot-time {
    color: #0f172a;
    font-size: 0.84rem;
    font-weight: 700;
  }

  .jobs-slot-status {
    color: #166534;
    font-size: 0.76rem;
    font-weight: 700;
  }

  .jobs-slot-item:not(.is-open) .jobs-slot-status {
    color: #ef4444;
  }

  .jobs-playbook-item {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
    color: #e2e8f0;
    font-size: 0.84rem;
    line-height: 1.65;
  }

  .jobs-playbook-dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 999px;
    margin-top: 0.48rem;
    background: #38bdf8;
    flex-shrink: 0;
  }

  .jobs-empty {
    display: grid;
    justify-items: center;
    gap: 0.7rem;
    padding: 3rem 1.5rem;
    background: rgba(255, 255, 255, 0.97);
    border: 1px solid #e2e8f0;
    border-radius: 1.75rem;
    text-align: center;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
  }

  .jobs-empty-icon {
    width: 3.5rem;
    height: 3.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 1.25rem;
    background: #e0f2fe;
    color: #0284c7;
  }

  .jobs-empty-title {
    color: #171c1e;
    font-size: 1.1rem;
    font-weight: 700;
  }

  .jobs-empty-copy {
    max-width: 32rem;
    color: #64748b;
    font-size: 0.92rem;
    line-height: 1.7;
  }

  .jobs-skeleton-card {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 184px;
    gap: 1.1rem;
    padding: 1.25rem;
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid #e2e8f0;
    border-radius: 1.75rem;
  }

  .jobs-skeleton-copy,
  .jobs-skeleton-side {
    display: grid;
    gap: 0.8rem;
  }

  .jobs-skeleton-line {
    height: 0.9rem;
    border-radius: 999px;
    background: linear-gradient(90deg, #edf2f7 0%, #f8fbff 50%, #edf2f7 100%);
    background-size: 200% 100%;
    animation: jobsShimmer 1.2s linear infinite;
  }

  .jobs-skeleton-line.sm {
    width: 28%;
  }

  .jobs-skeleton-line.md {
    width: 54%;
  }

  .jobs-skeleton-line.lg {
    width: 76%;
  }

  .jobs-skeleton-line.full {
    width: 100%;
  }

  .jobs-skeleton-button {
    width: 100%;
    height: 2.9rem;
    border-radius: 1rem;
    background: linear-gradient(90deg, #e0f2fe 0%, #bae6fd 50%, #e0f2fe 100%);
    background-size: 200% 100%;
    animation: jobsShimmer 1.2s linear infinite;
  }

  @keyframes jobsShimmer {
    from { background-position: 200% 0; }
    to { background-position: -200% 0; }
  }

  @media (max-width: 1220px) {
    .jobs-lead-card {
      grid-template-columns: 1fr;
    }

    .jobs-lead-stats {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .jobs-board-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 900px) {
    .worker-jobs-main {
      margin-left: 0;
      padding-top: 96px;
    }

    .worker-jobs-topbar {
      position: static;
      flex-direction: column;
      width: auto;
      margin: 0 1rem 1rem;
      padding: 1.1rem;
      border: 1px solid #e2e8f0;
      border-radius: 1.5rem;
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.92));
      box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
    }

    .worker-jobs-topbar-actions {
      width: 100%;
    }

    .worker-jobs-refresh-btn {
      width: 100%;
      min-width: 0;
    }

    .worker-jobs-topbar-chips {
      flex-wrap: nowrap;
      overflow-x: auto;
      padding-bottom: 0.15rem;
      margin-right: -0.1rem;
    }

    .worker-jobs-chip {
      flex: 0 0 auto;
    }

    .jobs-board {
      padding: 0 1rem 6.75rem;
    }

    .jobs-lead-stats {
      grid-template-columns: 1fr;
    }

    .jobs-skeleton-card {
      grid-template-columns: 1fr;
    }

    .jobs-job-header {
      display: grid;
      grid-template-columns: 3.6rem minmax(0, 1fr);
    }

    .jobs-job-sidehead {
      grid-column: 1 / -1;
      margin-left: 0;
      justify-items: start;
      text-align: left;
    }

    .jobs-job-stats {
      grid-template-columns: 1fr;
    }

    .jobs-job-footer {
      flex-direction: column;
      align-items: stretch;
    }

    .jobs-job-actions {
      width: 100%;
      margin-left: 0;
    }

    .jobs-link-button,
    .jobs-primary-button {
      flex: 1 1 calc(50% - 0.35rem);
      min-width: 0;
    }
  }
</style>
@endpush

@section('content')
<div class="worker-jobs-shell">
  <x-worker-sidebar />

  <main class="worker-jobs-main">
    <header class="worker-jobs-topbar">
      <div class="worker-jobs-topbar-copy">
        <div class="worker-jobs-kicker">Trung tâm nhận việc</div>
        <h1>Việc mới</h1>
        <p class="worker-jobs-summary" id="topbarSummary">Đang rà soát các việc gần khu vực và lịch rảnh của bạn.</p>
        <div class="worker-jobs-topbar-chips">
          <span class="worker-jobs-chip" id="topbarTodayChip">Đang quét việc hôm nay</span>
          <span class="worker-jobs-chip worker-jobs-chip--info" id="topbarAreaChip">Đang xác định vùng nóng</span>
          <span class="worker-jobs-chip worker-jobs-chip--success" id="topbarSlotChip">Đang tổng hợp khung giờ</span>
        </div>
      </div>

      <div class="worker-jobs-topbar-actions">
        <button type="button" class="worker-jobs-refresh-btn" id="dispatchRefreshButton" onclick="loadAvailableJobs(event)" aria-label="Làm mới danh sách">
          <span class="material-symbols-outlined">refresh</span>
          <span>Làm mới</span>
        </button>
      </div>
    </header>

    <div class="jobs-board">
      <section class="jobs-lead-card">
        <div class="jobs-lead-copy">
          <div class="jobs-lead-kicker">Đề xuất đầu ca</div>
          <h2 id="leadTitle">Đang phân tích cụm việc phù hợp để bạn nhận nhanh hơn.</h2>
          <p id="leadCopy">Hệ thống sẽ ưu tiên các đơn cùng khu vực, cùng buổi và có giá trị đủ tốt để bạn xử lý gọn lịch trong ngày.</p>
        </div>

        <div class="jobs-lead-stats">
          <article class="jobs-stat-card">
            <div class="jobs-stat-label">Việc gấp</div>
            <div class="jobs-stat-value" id="leadUrgentValue">00</div>
            <div class="jobs-stat-meta" id="leadUrgentMeta">đang quét ưu tiên</div>
          </article>
          <article class="jobs-stat-card">
            <div class="jobs-stat-label">Khung giờ mở</div>
            <div class="jobs-stat-value" id="leadSlotValue">00</div>
            <div class="jobs-stat-meta" id="leadSlotMeta">đang tổng hợp lịch</div>
          </article>
          <article class="jobs-stat-card">
            <div class="jobs-stat-label">Dự toán cụm</div>
            <div class="jobs-stat-value" id="leadEstimateValue">0đ</div>
            <div class="jobs-stat-meta" id="leadEstimateMeta">chưa có cụm phù hợp</div>
          </article>
        </div>
      </section>

      <div class="jobs-board-grid">
        <section>
          <div class="jobs-list" id="jobsGrid">
            @for ($i = 0; $i < 3; $i++)
              <div class="jobs-skeleton-card">
                <div class="jobs-skeleton-copy">
                  <div class="jobs-skeleton-line sm"></div>
                  <div class="jobs-skeleton-line lg"></div>
                  <div class="jobs-skeleton-line full"></div>
                  <div class="jobs-skeleton-line md"></div>
                  <div class="jobs-skeleton-line full"></div>
                </div>
                <div class="jobs-skeleton-side">
                  <div class="jobs-skeleton-line md"></div>
                  <div class="jobs-skeleton-line sm"></div>
                  <div class="jobs-skeleton-button"></div>
                </div>
              </div>
            @endfor
          </div>

          <div class="jobs-empty" id="emptyState" style="display:none;">
            <div class="jobs-empty-icon">
              <span class="material-symbols-outlined">work_off</span>
            </div>
            <div class="jobs-empty-title">Hiện chưa có việc mới phù hợp</div>
            <div class="jobs-empty-copy">Hệ thống vừa quét xong nhưng chưa thấy yêu cầu nào khớp với nhóm dịch vụ và khu vực bạn đang phục vụ. Giữ trang này mở hoặc bấm làm mới lại sau ít phút để nhận cụm việc mới.</div>
          </div>
        </section>

        <aside class="jobs-side-column">
          <section class="jobs-panel">
            <h2 class="jobs-panel-title">Bộ lọc nhanh</h2>
            <p class="jobs-panel-copy">Giữ các bộ lọc dùng nhiều nhất ngay cạnh danh sách để thao tác trong vài giây.</p>
            <div class="jobs-filter-list">
              <span class="jobs-filter-pill jobs-filter-pill--active">< 5km</span>
              <span class="jobs-filter-pill" id="filterTodayChip">Hôm nay</span>
              <span class="jobs-filter-pill" id="filterCategoryChip">Điện lạnh</span>
              <span class="jobs-filter-pill" id="filterValueChip">Giá > 500k</span>
              <span class="jobs-filter-pill" id="filterUrgentChip">Ưu tiên gấp</span>
            </div>
          </section>

          <section class="jobs-panel">
            <h2 class="jobs-panel-title">Khung giờ đang trống</h2>
            <p class="jobs-panel-copy">Nhìn nhanh các ca còn chỗ để chọn đơn không đè lên lịch đang có.</p>
            <div class="jobs-slot-list" id="slotList">
              <div class="jobs-slot-item is-open">
                <span class="jobs-slot-time">Đang tổng hợp</span>
                <span class="jobs-slot-status">vui lòng chờ</span>
              </div>
            </div>
          </section>

          <section class="jobs-panel jobs-panel--dark">
            <h2 class="jobs-panel-title">Quy tắc nhận việc tốt</h2>
            <p class="jobs-panel-copy" id="playbookLead">Ưu tiên đơn gần tuyến, cùng buổi và có mô tả rõ để giảm huỷ đơn và tăng điểm phản hồi.</p>
            <div class="jobs-playbook-list">
              <div class="jobs-playbook-item">
                <span class="jobs-playbook-dot"></span>
                <span id="playbookTipPrimary">Nhận đơn gấp khi còn ít nhất 45 phút di chuyển.</span>
              </div>
              <div class="jobs-playbook-item">
                <span class="jobs-playbook-dot"></span>
                <span id="playbookTipSecondary">Nếu đã có lịch liền kề, ưu tiên cụm việc cùng khu vực trước.</span>
              </div>
              <div class="jobs-playbook-item">
                <span class="jobs-playbook-dot"></span>
                <span id="playbookTipTertiary">Giữ các bộ lọc quen dùng ngay bên phải để thao tác nhanh.</span>
              </div>
            </div>
          </section>
        </aside>
      </div>
    </div>
  </main>
</div>
@endsection

@push('scripts')
<script type="module">
import { callApi, getCurrentUser, showToast } from "{{ asset('assets/js/api.js') }}";

const baseUrl = "{{ url('/') }}";
const user = getCurrentUser();

if (!user || !['worker', 'admin'].includes(user.role)) {
  window.location.replace(`${baseUrl}/login?role=worker`);
}

const ui = {
  jobsGrid: document.getElementById('jobsGrid'),
  emptyState: document.getElementById('emptyState'),
  refreshButton: document.getElementById('dispatchRefreshButton'),
  topbarSummary: document.getElementById('topbarSummary'),
  topbarTodayChip: document.getElementById('topbarTodayChip'),
  topbarAreaChip: document.getElementById('topbarAreaChip'),
  topbarSlotChip: document.getElementById('topbarSlotChip'),
  leadTitle: document.getElementById('leadTitle'),
  leadCopy: document.getElementById('leadCopy'),
  leadUrgentValue: document.getElementById('leadUrgentValue'),
  leadUrgentMeta: document.getElementById('leadUrgentMeta'),
  leadSlotValue: document.getElementById('leadSlotValue'),
  leadSlotMeta: document.getElementById('leadSlotMeta'),
  leadEstimateValue: document.getElementById('leadEstimateValue'),
  leadEstimateMeta: document.getElementById('leadEstimateMeta'),
  filterTodayChip: document.getElementById('filterTodayChip'),
  filterCategoryChip: document.getElementById('filterCategoryChip'),
  filterValueChip: document.getElementById('filterValueChip'),
  filterUrgentChip: document.getElementById('filterUrgentChip'),
  slotList: document.getElementById('slotList'),
  playbookLead: document.getElementById('playbookLead'),
  playbookTipPrimary: document.getElementById('playbookTipPrimary'),
  playbookTipSecondary: document.getElementById('playbookTipSecondary'),
  playbookTipTertiary: document.getElementById('playbookTipTertiary'),
};

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function localDateKey(date = new Date()) {
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${date.getFullYear()}-${month}-${day}`;
}

function getServices(job) {
  if (Array.isArray(job.dich_vus) && job.dich_vus.length > 0) {
    return job.dich_vus
      .map((service) => service?.ten_dich_vu || service?.name)
      .filter(Boolean);
  }

  if (job.dich_vu?.ten_dich_vu) {
    return [job.dich_vu.ten_dich_vu];
  }

  return ['Yêu cầu sửa chữa'];
}

function getPrimaryService(job) {
  return getServices(job)[0] || 'Yêu cầu sửa chữa';
}

function getCategoryLabel(serviceName) {
  const source = String(serviceName || '').toLowerCase();

  if (source.includes('lạnh') || source.includes('máy lạnh') || source.includes('tủ lạnh')) return 'Điện lạnh';
  if (source.includes('nước') || source.includes('lọc')) return 'Điện nước';
  if (source.includes('điện') || source.includes('đèn') || source.includes('ổ')) return 'Điện dân dụng';
  if (source.includes('giặt') || source.includes('bếp') || source.includes('lò')) return 'Gia dụng';

  return 'Sửa chữa';
}

function getEstimate(job) {
  const direct = Number(job.estimated_price ?? job.tong_tien ?? 0);
  if (direct > 0) return direct;

  const parts = ['phi_di_lai', 'phi_linh_kien', 'tien_cong', 'tien_thue_xe']
    .map((key) => Number(job[key] ?? 0))
    .filter((value) => Number.isFinite(value));

  const fallback = parts.reduce((sum, value) => sum + value, 0);
  return fallback > 0 ? fallback : 0;
}

function formatMoney(value) {
  if (!Number.isFinite(value) || value <= 0) return 'Liên hệ';
  return `${value.toLocaleString('vi-VN')}đ`;
}

function formatCompactMoney(value) {
  if (!Number.isFinite(value) || value <= 0) return '0đ';
  if (value >= 1000000) {
    const millions = value / 1000000;
    return `${millions.toFixed(millions % 1 === 0 ? 0 : 1)}M`;
  }
  if (value >= 1000) {
    return `${Math.round(value / 1000)}k`;
  }
  return `${value.toLocaleString('vi-VN')}đ`;
}

function getShortAddress(address) {
  if (!address) return 'Địa chỉ sẽ hiện khi nhận việc';
  const parts = String(address).split(',').map((part) => part.trim()).filter(Boolean);
  return parts.slice(0, 2).join(', ') || String(address);
}

function getArea(address) {
  if (!address) return 'Khu vực đang mở';
  const parts = String(address).split(',').map((part) => part.trim()).filter(Boolean);

  if (parts.length >= 2) {
    return parts[parts.length - 2];
  }

  return parts[0] || 'Khu vực đang mở';
}

function getDateLabel(dateString) {
  if (!dateString) return 'Linh hoạt';

  const today = localDateKey();
  const tomorrow = localDateKey(new Date(Date.now() + 86400000));

  if (dateString === today) return 'Hôm nay';
  if (dateString === tomorrow) return 'Ngày mai';

  const date = new Date(`${dateString}T00:00:00`);
  if (Number.isNaN(date.getTime())) return 'Linh hoạt';

  return new Intl.DateTimeFormat('vi-VN', {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
  }).format(date);
}

function formatSchedule(job) {
  const dateLabel = getDateLabel(job.ngay_hen || job.ngay_dat_lich);
  const timeValue = String(job.gio_hen || job.khung_gio_hen || job.khung_gio || job.gio_du_kien || '').trim();
  return [dateLabel, timeValue].filter(Boolean).join(' · ');
}

function getTimeRange(job) {
  const timeValue = String(job.gio_hen || job.khung_gio_hen || job.khung_gio || job.gio_du_kien || '').trim();
  return timeValue || 'Chưa chốt giờ';
}

function getServiceModeLabel(job) {
  return job.loai_dat_lich === 'at_home' ? 'Sửa tại nhà' : 'Sửa tại cửa hàng';
}

function getServiceIcon(job) {
  const category = getCategoryLabel(getPrimaryService(job));

  switch (category) {
  case 'Điện lạnh':
    return 'mode_fan';
  case 'Điện nước':
    return 'plumbing';
  case 'Điện dân dụng':
    return 'bolt';
  case 'Gia dụng':
    return 'home_repair_service';
  default:
    return 'construction';
  }
}

function getWorkerDistanceKm(job) {
  const numericDistance = Number(job.worker_distance_km ?? job.khoang_cach ?? 0);
  return Number.isFinite(numericDistance) && numericDistance > 0 ? numericDistance : null;
}

function formatDistanceKm(value) {
  if (!Number.isFinite(value) || value <= 0) {
    return '';
  }

  return `${value.toFixed(value >= 10 ? 0 : 1)} km`;
}

function getDistanceValueLabel(job) {
  if (job.loai_dat_lich !== 'at_home') {
    return 'Tại cửa hàng';
  }

  const distanceKm = getWorkerDistanceKm(job);
  return distanceKm ? formatDistanceKm(distanceKm) : 'Chưa định vị';
}

function getDistanceCaption(job) {
  if (job.loai_dat_lich !== 'at_home') {
    return 'Khách mang thiết bị đến cửa hàng';
  }

  const distanceKm = getWorkerDistanceKm(job);
  if (distanceKm) {
    return `Cách vị trí hiện tại của bạn ${formatDistanceKm(distanceKm)}`;
  }

  return 'Cập nhật vị trí hồ sơ thợ để đo tuyến chính xác';
}

function getPriorityNote(job, index = 0) {
  const estimate = getEstimate(job);
  const isToday = (job.ngay_hen || job.ngay_dat_lich) === localDateKey();

  if (isToday && estimate >= 800000) return 'Đơn hôm nay, giá trị tốt để chốt nhanh';
  if (isToday) return 'Ưu tiên xử lý trong hôm nay';
  if (estimate >= 1200000) return 'Giá trị cao, nên mở chi tiết sớm';
  if (index === 0) return 'Đơn nổi bật đang được đề xuất đầu ca';
  return 'Mô tả đủ rõ để xử lý nhanh';
}

function getPriorityPanel(job, index = 0) {
  const estimate = getEstimate(job);
  const isToday = (job.ngay_hen || job.ngay_dat_lich) === localDateKey();

  if (isToday && estimate >= 800000) {
    return {
      tone: 'success',
      icon: 'target',
      title: 'Đơn hôm nay nên chốt sớm',
      body: 'Giá trị tốt, khung giờ gần và phù hợp để gom tuyến xử lý trong ngày.',
    };
  }

  if (isToday) {
    return {
      tone: 'warn',
      icon: 'event_upcoming',
      title: 'Ưu tiên nhận trong hôm nay',
      body: 'Khung giờ gần nhất đang mở, nên mở chi tiết sớm để khóa lịch cho tuyến hiện tại.',
    };
  }

  if (estimate >= 1200000) {
    return {
      tone: 'info',
      icon: 'workspace_premium',
      title: 'Đơn giá trị cao',
      body: 'Nên kiểm tra mô tả lỗi và linh kiện dự kiến trước để chuẩn bị tuyến sửa phù hợp.',
    };
  }

  return {
    tone: 'info',
    icon: 'bolt',
    title: 'Đơn nhận việc mới',
    body: getPriorityNote(job, index),
  };
}

function getDistinctSlots(jobs) {
  return new Set(
    jobs
      .map((job) => String(job.gio_hen || job.khung_gio_hen || job.khung_gio || job.gio_du_kien || '').trim())
      .filter((value) => value && value !== '-')
  ).size;
}

function getHotZone(jobs) {
  const areaCounts = jobs.reduce((acc, job) => {
    const area = getArea(job.dia_chi);
    acc[area] = (acc[area] || 0) + 1;
    return acc;
  }, {});

  const sorted = Object.entries(areaCounts).sort((a, b) => b[1] - a[1]);
  return sorted[0]?.[0] || 'Khu trung tâm';
}

function getHotZoneCount(jobs) {
  const hotZone = getHotZone(jobs);
  return jobs.filter((job) => getArea(job.dia_chi) === hotZone).length;
}

function getDominantCategory(jobs) {
  const categoryCounts = jobs.reduce((acc, job) => {
    const category = getCategoryLabel(getPrimaryService(job));
    acc[category] = (acc[category] || 0) + 1;
    return acc;
  }, {});

  const sorted = Object.entries(categoryCounts).sort((a, b) => b[1] - a[1]);
  return sorted[0]?.[0] || 'Sửa chữa';
}

function getBestJob(jobs) {
  return [...jobs].sort((a, b) => {
    const todayBoostA = (a.ngay_hen || a.ngay_dat_lich) === localDateKey() ? 300000 : 0;
    const todayBoostB = (b.ngay_hen || b.ngay_dat_lich) === localDateKey() ? 300000 : 0;
    return (getEstimate(b) + todayBoostB) - (getEstimate(a) + todayBoostA);
  })[0] || null;
}

function getAverageEstimate(jobs) {
  const values = jobs.map((job) => getEstimate(job)).filter((value) => value > 0);
  if (!values.length) return 0;
  return Math.round(values.reduce((sum, value) => sum + value, 0) / values.length);
}

function estimateDurationLabel(job) {
  const estimate = getEstimate(job);
  const serviceCount = getServices(job).length;

  if (serviceCount >= 2 || estimate >= 1500000) return '2-3 giờ';
  if (estimate >= 700000) return '60-90 phút';
  return '30-60 phút';
}

function renderSkeletonState() {
  ui.emptyState.style.display = 'none';
  ui.jobsGrid.style.display = 'grid';
  ui.jobsGrid.innerHTML = Array.from({ length: 3 }).map(() => `
    <div class="jobs-skeleton-card">
      <div class="jobs-skeleton-copy">
        <div class="jobs-skeleton-line sm"></div>
        <div class="jobs-skeleton-line lg"></div>
        <div class="jobs-skeleton-line full"></div>
        <div class="jobs-skeleton-line md"></div>
        <div class="jobs-skeleton-line full"></div>
      </div>
      <div class="jobs-skeleton-side">
        <div class="jobs-skeleton-line md"></div>
        <div class="jobs-skeleton-line sm"></div>
        <div class="jobs-skeleton-button"></div>
      </div>
    </div>
  `).join('');
}

function renderSlotAvailability(jobs) {
  if (!jobs.length) {
    ui.slotList.innerHTML = `
      <div class="jobs-slot-item is-open">
        <span class="jobs-slot-time">Đang chờ cụm việc mới</span>
        <span class="jobs-slot-status">hệ thống sẽ cập nhật lại</span>
      </div>
    `;
    return;
  }

  const slotMap = jobs.reduce((acc, job) => {
    const label = formatSchedule(job);
    if (!acc[label]) {
      acc[label] = 0;
    }
    acc[label] += 1;
    return acc;
  }, {});

  ui.slotList.innerHTML = Object.entries(slotMap)
    .slice(0, 4)
    .map(([label, count]) => `
      <div class="jobs-slot-item is-open">
        <span class="jobs-slot-time">${escapeHtml(label)}</span>
        <span class="jobs-slot-status">${escapeHtml(`${count} việc có thể nhận`)}</span>
      </div>
    `)
    .join('');
}

function updateTopbarSummary(jobs = [], state = 'ready') {
  const count = jobs.length;
  const todayCount = jobs.filter((job) => (job.ngay_hen || job.ngay_dat_lich) === localDateKey()).length;
  const hotZone = count > 0 ? getHotZone(jobs) : 'Chưa có vùng nóng';
  const slots = getDistinctSlots(jobs);

  if (state === 'error') {
    ui.topbarSummary.textContent = 'Không tải được việc mới. Vui lòng kiểm tra kết nối rồi bấm làm mới lại.';
    ui.topbarTodayChip.textContent = 'Chưa có dữ liệu hôm nay';
    ui.topbarAreaChip.textContent = 'Vùng nóng chưa xác định';
    ui.topbarSlotChip.textContent = 'Khung giờ chưa đồng bộ';
    return;
  }

  if (state === 'loading') {
    ui.topbarSummary.textContent = 'Đang rà soát các việc gần khu vực và lịch rảnh của bạn.';
    ui.topbarTodayChip.textContent = 'Đang quét việc hôm nay';
    ui.topbarAreaChip.textContent = 'Đang xác định vùng nóng';
    ui.topbarSlotChip.textContent = 'Đang tổng hợp khung giờ';
    return;
  }

  if (!count) {
    ui.topbarSummary.textContent = 'Hiện chưa có việc mới phù hợp. Giữ trang này mở để quét lại nhanh khi có đơn.';
    ui.topbarTodayChip.textContent = 'Chưa có việc hôm nay';
    ui.topbarAreaChip.textContent = 'Vùng nóng đang chờ';
    ui.topbarSlotChip.textContent = '0 khung giờ mở';
    return;
  }

  ui.topbarSummary.textContent = todayCount > 0
    ? `Có ${count} việc phù hợp, trong đó ${todayCount} việc nên ưu tiên xử lý trong hôm nay.`
    : `Có ${count} việc phù hợp đang mở. Nên ưu tiên các đơn gần tuyến bạn đang phục vụ.`;
  ui.topbarTodayChip.textContent = `${todayCount} việc hôm nay`;
  ui.topbarAreaChip.textContent = hotZone;
  ui.topbarSlotChip.textContent = `${slots} khung giờ mở`;
}

function updateBoardStats(jobs) {
  const count = jobs.length;
  const todayCount = jobs.filter((job) => (job.ngay_hen || job.ngay_dat_lich) === localDateKey()).length;
  const totalEstimate = jobs.reduce((sum, job) => sum + getEstimate(job), 0);
  const averageEstimate = getAverageEstimate(jobs);
  const hotZone = getHotZone(jobs);
  const hotZoneCount = getHotZoneCount(jobs);
  const slots = getDistinctSlots(jobs);
  const dominantCategory = getDominantCategory(jobs);
  const featured = getBestJob(jobs);

  updateTopbarSummary(jobs);

  if (!count) {
    ui.leadTitle.textContent = 'Hệ thống đang chờ cụm việc mới phù hợp với tuyến của bạn.';
    ui.leadCopy.textContent = 'Khi có đơn mới, khu vực nóng và khung giờ mở sẽ xuất hiện ngay ở đây để bạn nhận việc nhanh hơn.';
    ui.leadUrgentValue.textContent = '00';
    ui.leadUrgentMeta.textContent = 'chưa có đơn cần ưu tiên';
    ui.leadSlotValue.textContent = '00';
    ui.leadSlotMeta.textContent = 'đang chờ lịch mới';
    ui.leadEstimateValue.textContent = '0đ';
    ui.leadEstimateMeta.textContent = 'chưa có cụm phù hợp';
    ui.filterTodayChip.textContent = 'Ngày linh hoạt';
    ui.filterCategoryChip.textContent = 'Sửa chữa';
    ui.filterValueChip.textContent = 'Giá linh hoạt';
    ui.filterUrgentChip.textContent = 'Ưu tiên tiêu chuẩn';
    ui.playbookLead.textContent = 'Ưu tiên đơn gần tuyến, cùng buổi và có mô tả rõ để giảm huỷ đơn và tăng điểm phản hồi.';
    ui.playbookTipPrimary.textContent = 'Nhận đơn gấp khi còn ít nhất 45 phút di chuyển.';
    ui.playbookTipSecondary.textContent = 'Nếu đã có lịch liền kề, ưu tiên cụm việc cùng khu vực trước.';
    ui.playbookTipTertiary.textContent = 'Giữ các bộ lọc quen dùng ngay bên phải để thao tác nhanh.';
    renderSlotAvailability([]);
    return;
  }

  ui.leadTitle.textContent = `Cụm ${hotZone} đang có ${hotZoneCount} việc phù hợp để bạn gom tuyến nhanh trong ngày.`;
  ui.leadCopy.textContent = featured
    ? `Mở trước đơn ${getPrimaryService(featured).toLowerCase()} vào ${formatSchedule(featured).toLowerCase()} để giữ lịch liền mạch và chốt thêm việc quanh ${getArea(featured.dia_chi)}.`
    : 'Hệ thống đang ưu tiên các đơn cùng khu vực, cùng buổi và có giá trị đủ tốt để bạn xử lý gọn lịch trong ngày.';

  ui.leadUrgentValue.textContent = String(todayCount).padStart(2, '0');
  ui.leadUrgentMeta.textContent = todayCount > 0 ? 'việc cần ưu tiên hôm nay' : 'chưa có đơn gấp trong ngày';
  ui.leadSlotValue.textContent = String(slots).padStart(2, '0');
  ui.leadSlotMeta.textContent = slots > 0 ? 'khung giờ đang mở' : 'đang chờ lịch';
  ui.leadEstimateValue.textContent = formatCompactMoney(totalEstimate);
  ui.leadEstimateMeta.textContent = `${count} việc đang mở`;

  ui.filterTodayChip.textContent = todayCount > 0 ? `${todayCount} việc hôm nay` : 'Ngày linh hoạt';
  ui.filterCategoryChip.textContent = dominantCategory;
  ui.filterValueChip.textContent = averageEstimate > 0 ? `TB ${formatCompactMoney(averageEstimate)}` : 'Giá linh hoạt';
  ui.filterUrgentChip.textContent = todayCount > 0 ? `${todayCount} ưu tiên gấp` : 'Ưu tiên tiêu chuẩn';

  ui.playbookLead.textContent = `Vùng ${hotZone} đang nổi bật nhất. Ưu tiên nhận các đơn ${dominantCategory.toLowerCase()} cùng buổi để giảm quãng đường di chuyển.`;
  ui.playbookTipPrimary.textContent = featured
    ? `Mở trước đơn ${getPrimaryService(featured).toLowerCase()} tại ${getArea(featured.dia_chi)} nếu bạn còn trống ${formatSchedule(featured).toLowerCase()}.`
    : 'Nhận đơn gấp khi còn ít nhất 45 phút di chuyển.';
  ui.playbookTipSecondary.textContent = todayCount > 1
    ? `Bạn đang có ${todayCount} việc trong hôm nay; nên gom theo cùng tuyến trước khi nhận thêm ca xa.`
    : 'Khi chưa có lịch liền kề, ưu tiên việc gần khu dân cư đang nóng để tăng khả năng chốt thêm đơn.';
  ui.playbookTipTertiary.textContent = slots > 1
    ? `Hiện còn ${slots} khung giờ mở; giữ các bộ lọc nhanh bên phải để chốt đơn trong vài giây.`
    : 'Giữ trang này mở để hệ thống tự quét thêm việc mới phù hợp.';

  renderSlotAvailability(jobs);
}

function renderJobs(jobs) {
  const sortedJobs = [...jobs].sort((a, b) => {
    const todayA = (a.ngay_hen || a.ngay_dat_lich) === localDateKey() ? 1 : 0;
    const todayB = (b.ngay_hen || b.ngay_dat_lich) === localDateKey() ? 1 : 0;

    if (todayA !== todayB) {
      return todayB - todayA;
    }

    return getEstimate(b) - getEstimate(a);
  });

  ui.jobsGrid.style.display = 'grid';
  ui.emptyState.style.display = 'none';
  ui.jobsGrid.innerHTML = sortedJobs.map((job, index) => {
    const services = getServices(job);
    const title = services.join(' · ');
    const category = getCategoryLabel(services[0]);
    const customerName = job.khach_hang?.name || job.khach_hang_ten || 'Khách hàng';
    const estimate = getEstimate(job);
    const shortAddress = getShortAddress(job.dia_chi);
    const area = getArea(job.dia_chi);
    const note = getPriorityPanel(job, index);
    const dateLabel = getDateLabel(job.ngay_hen || job.ngay_dat_lich);
    const timeRange = getTimeRange(job);
    const detailLink = `${baseUrl}/worker/jobs/${job.id}`;
    const badge = (job.ngay_hen || job.ngay_dat_lich) === localDateKey() ? 'Hôm nay' : 'Sắp tới';
    const modeLabel = getServiceModeLabel(job);
    const distanceValue = getDistanceValueLabel(job);
    const distanceCaption = getDistanceCaption(job);
    const travelFee = Number(job.phi_di_lai || 0);
    const travelFeeLabel = travelFee > 0 ? formatCompactMoney(travelFee) : '0đ';
    const duration = estimateDurationLabel(job);
    const serviceIcon = getServiceIcon(job);
    const description = job.mo_ta_van_de || 'Khách hàng đã để lại mô tả ngắn. Mở chi tiết để xem hiện trạng và thiết bị cụ thể.';

    return `
      <article class="jobs-job-card">
        <div class="jobs-job-header">
          <div class="jobs-job-icon">
            <span class="material-symbols-outlined">${escapeHtml(serviceIcon)}</span>
          </div>

          <div class="jobs-job-summary">
            <div class="jobs-job-tags">
              <span class="jobs-tag jobs-tag--category">${escapeHtml(category)}</span>
              <span class="jobs-tag jobs-tag--mode">${escapeHtml(modeLabel)}</span>
            </div>

            <h2 class="jobs-job-title">${escapeHtml(title)}</h2>
            <div class="jobs-job-meta-inline">
              <span class="jobs-job-meta-item"><span class="material-symbols-outlined">calendar_month</span><span>${escapeHtml(dateLabel)}</span></span>
              <span class="jobs-job-meta-dot"></span>
              <span class="jobs-job-meta-item"><span class="material-symbols-outlined">schedule</span><span>${escapeHtml(timeRange)}</span></span>
              <span class="jobs-job-meta-dot"></span>
              <span class="jobs-job-meta-item"><span class="material-symbols-outlined">person</span><span>${escapeHtml(customerName)}</span></span>
            </div>
          </div>

          <div class="jobs-job-sidehead">
            <span class="jobs-job-status">${escapeHtml(badge)}</span>
            <div class="jobs-job-price">${escapeHtml(formatMoney(estimate))}</div>
            <div class="jobs-job-price-meta">${escapeHtml(`${duration} · ${area}`)}</div>
          </div>
        </div>

        <p class="jobs-job-copy">${escapeHtml(description)}</p>

        <div class="jobs-job-location">
          <span class="material-symbols-outlined">location_on</span>
          <span>${escapeHtml(shortAddress)}</span>
        </div>

        <div class="jobs-job-note jobs-job-note--${escapeHtml(note.tone)}">
          <span class="material-symbols-outlined">${escapeHtml(note.icon)}</span>
          <div class="jobs-job-note-copy">
            <div>
              <strong>${escapeHtml(note.title)}</strong>
              <p>${escapeHtml(note.body)}</p>
            </div>
          </div>
        </div>

        <div class="jobs-job-stats">
          <div class="jobs-job-stat">
            <div class="jobs-job-stat-label">Khoảng cách</div>
            <div class="jobs-job-stat-value">${escapeHtml(distanceValue)}</div>
            <div class="jobs-job-stat-caption">${escapeHtml(distanceCaption)}</div>
          </div>

          <div class="jobs-job-stat">
            <div class="jobs-job-stat-label">Thời lượng</div>
            <div class="jobs-job-stat-value">${escapeHtml(duration)}</div>
            <div class="jobs-job-stat-caption">${escapeHtml(`Phù hợp tuyến ${area}`)}</div>
          </div>

          <div class="jobs-job-stat">
            <div class="jobs-job-stat-label">Phục vụ</div>
            <div class="jobs-job-stat-value">${escapeHtml(modeLabel)}</div>
            <div class="jobs-job-stat-caption">${escapeHtml(job.loai_dat_lich === 'at_home' ? `Phí đi lại ${travelFeeLabel}` : 'Không cần di chuyển tới khách')}</div>
          </div>
        </div>

        <div class="jobs-job-footer">
          <div class="jobs-job-actions">
            <a href="${detailLink}" class="jobs-link-button"><span class="material-symbols-outlined">visibility</span><span>Chi tiết</span></a>
            <button type="button" onclick="claimJob(${job.id}, event)" class="jobs-primary-button">Nhận việc</button>
          </div>
        </div>
      </article>
    `;
  }).join('');
}

function setRefreshLoading(isLoading) {
  if (!ui.refreshButton) return;

  ui.refreshButton.disabled = isLoading;
  ui.refreshButton.innerHTML = isLoading
    ? '<span class="material-symbols-outlined">progress_activity</span><span>Đang quét</span>'
    : '<span class="material-symbols-outlined">refresh</span><span>Làm mới</span>';
}

window.loadAvailableJobs = async function loadAvailableJobs(event) {
  if (event?.preventDefault) event.preventDefault();

  setRefreshLoading(true);
  updateTopbarSummary([], 'loading');
  renderSkeletonState();

  try {
    const res = await callApi('/don-dat-lich/available', 'GET');

    if (!res.ok) {
      throw new Error(res.data?.message || 'Không thể tải danh sách việc');
    }

    const jobs = Array.isArray(res.data?.data)
      ? res.data.data
      : Array.isArray(res.data)
        ? res.data
        : [];

    updateBoardStats(jobs);

    if (!jobs.length) {
      ui.jobsGrid.style.display = 'none';
      ui.emptyState.style.display = 'flex';
      return;
    }

    renderJobs(jobs);
  } catch (error) {
    console.error(error);
    showToast(error.message || 'Lỗi kết nối máy chủ', 'error');
    updateTopbarSummary([], 'error');
    ui.jobsGrid.style.display = 'none';
    ui.emptyState.style.display = 'flex';
    ui.leadTitle.textContent = 'Không thể đồng bộ nguồn việc lúc này.';
    ui.leadCopy.textContent = 'Kiểm tra kết nối rồi bấm làm mới lại sau ít phút để quét lại các đơn phù hợp.';
    ui.leadUrgentValue.textContent = '--';
    ui.leadUrgentMeta.textContent = 'nguồn việc đang gián đoạn';
    ui.leadSlotValue.textContent = '--';
    ui.leadSlotMeta.textContent = 'chưa thể lấy lịch';
    ui.leadEstimateValue.textContent = '--';
    ui.leadEstimateMeta.textContent = 'đang chờ đồng bộ';
    renderSlotAvailability([]);
  } finally {
    setRefreshLoading(false);
  }
};

window.claimJob = async function claimJob(id, event) {
  if (!confirm('Bạn có chắc chắn muốn nhận việc này không?')) return;

  const button = event?.currentTarget || event?.target?.closest('button');
  const originalHtml = button?.innerHTML;

  if (button) {
    button.disabled = true;
    button.innerHTML = '<span class="material-symbols-outlined">progress_activity</span> Đang xử lý';
  }

  try {
    const res = await callApi(`/don-dat-lich/${id}/claim`, 'POST');

    if (!res.ok) {
      throw new Error(res.data?.message || 'Không thể nhận việc này');
    }

    showToast('Nhận việc thành công!');
    setTimeout(() => {
      window.location.href = `${baseUrl}/worker/my-bookings`;
    }, 800);
  } catch (error) {
    console.error(error);
    showToast(error.message || 'Lỗi kết nối máy chủ', 'error');

    if (button) {
      button.disabled = false;
      button.innerHTML = originalHtml;
    }

    window.loadAvailableJobs();
  }
};

document.addEventListener('DOMContentLoaded', () => {
  updateTopbarSummary([], 'loading');
  renderSkeletonState();
  renderSlotAvailability([]);
  window.loadAvailableJobs();
});
</script>
@endpush

