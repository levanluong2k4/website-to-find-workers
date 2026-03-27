@extends('layouts.app')
@section('title', 'Lịch làm việc - Thợ Tốt NTU')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@600;700;800&family=Manrope:wght@400;500;600;700;800&family=Material+Symbols+Outlined" rel="stylesheet"/>
<style>
  :root {
    --dispatch-bg: #f3f7fd;
    --dispatch-panel: rgba(255, 255, 255, 0.9);
    --dispatch-surface: #ffffff;
    --dispatch-stroke: rgba(148, 163, 184, 0.18);
    --dispatch-text: #0f172a;
    --dispatch-muted: #64748b;
    --dispatch-soft: #94a3b8;
    --dispatch-primary: #0d7cc1;
    --dispatch-primary-strong: #095b91;
    --dispatch-primary-soft: #e8f3ff;
    --dispatch-amber: #c97b19;
    --dispatch-amber-soft: #fff3e2;
    --dispatch-copper: #915701;
    --dispatch-copper-soft: #fff1de;
    --dispatch-green: #0f9f7c;
    --dispatch-green-soft: #eafcf4;
    --dispatch-danger: #dc2626;
    --dispatch-danger-soft: #fef2f2;
    --dispatch-shadow: 0 26px 60px rgba(15, 23, 42, 0.08);
    --dispatch-shadow-soft: 0 16px 40px rgba(15, 23, 42, 0.06);
  }

  .worker-main {
    margin-left: 240px;
    min-height: 100vh;
    background:
      radial-gradient(circle at top right, rgba(13, 124, 193, 0.12), transparent 26rem),
      radial-gradient(circle at top left, rgba(244, 164, 52, 0.08), transparent 22rem),
      var(--dispatch-bg);
  }

  .dispatch-page {
    display: flex;
    min-height: 100vh;
  }

  .dispatch-shell {
    position: relative;
    padding: 26px 26px 36px;
    overflow: hidden;
  }

  .dispatch-shell::before,
  .dispatch-shell::after {
    content: "";
    position: absolute;
    border-radius: 999px;
    pointer-events: none;
    filter: blur(18px);
    opacity: 0.5;
  }

  .dispatch-shell::before {
    width: 240px;
    height: 240px;
    right: -100px;
    top: 30px;
    background: rgba(13, 124, 193, 0.12);
  }

  .dispatch-shell::after {
    width: 210px;
    height: 210px;
    left: -70px;
    bottom: 100px;
    background: rgba(201, 123, 25, 0.08);
  }

  .dispatch-hero {
    position: relative;
    z-index: 1;
    padding: 28px;
    border: 1px solid rgba(255, 255, 255, 0.85);
    border-radius: 32px;
    background:
      linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(245, 249, 255, 0.92)),
      var(--dispatch-panel);
    box-shadow: var(--dispatch-shadow);
    backdrop-filter: blur(18px);
  }

  .dispatch-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    letter-spacing: 0.28em;
    font-size: 0.72rem;
    font-weight: 800;
    color: var(--dispatch-primary);
  }

  .dispatch-eyebrow::before {
    content: "";
    width: 34px;
    height: 1px;
    background: rgba(13, 124, 193, 0.4);
  }

  .dispatch-hero__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 22px;
  }

  .dispatch-hero__headline h1 {
    margin: 0;
    font-family: 'DM Sans', sans-serif;
    font-weight: 800;
    font-size: clamp(2rem, 3vw, 3rem);
    letter-spacing: -0.04em;
    color: var(--dispatch-text);
  }

  .dispatch-hero__headline p {
    max-width: 720px;
    margin: 10px 0 0;
    font-size: 0.98rem;
    line-height: 1.65;
    color: var(--dispatch-muted);
  }

  .dispatch-refresh-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 18px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.92);
    color: var(--dispatch-text);
    font-weight: 700;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
    transition: 0.2s ease;
  }

  .dispatch-refresh-btn:hover {
    transform: translateY(-1px);
    color: var(--dispatch-primary);
    border-color: rgba(13, 124, 193, 0.25);
  }

  .dispatch-hero__meta {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(250px, 320px);
    gap: 18px;
  }

  .dispatch-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
  }

  .dispatch-stat {
    min-height: 120px;
    padding: 18px 20px;
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.86);
    box-shadow: var(--dispatch-shadow-soft);
  }

  .dispatch-stat__label {
    display: block;
    margin-bottom: 10px;
    font-size: 0.74rem;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    font-weight: 800;
    color: var(--dispatch-soft);
  }

  .dispatch-stat__value {
    display: block;
    font-family: 'DM Sans', sans-serif;
    font-size: clamp(1.55rem, 2vw, 2.2rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: var(--dispatch-text);
  }

  .dispatch-stat__hint {
    display: block;
    margin-top: 8px;
    font-size: 0.83rem;
    color: var(--dispatch-muted);
  }

  .dispatch-stat--primary .dispatch-stat__value {
    color: var(--dispatch-primary);
  }

  .dispatch-stat--amber .dispatch-stat__value {
    color: var(--dispatch-amber);
  }

  .dispatch-stat--copper .dispatch-stat__value {
    color: var(--dispatch-copper);
  }

  .dispatch-operator {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 18px 20px;
    border-radius: 26px;
    background: linear-gradient(135deg, #0f172a, #12253b);
    color: #e2e8f0;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.16);
  }

  .dispatch-operator__copy {
    flex: 1;
  }

  .dispatch-operator__label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-size: 0.8rem;
    font-weight: 700;
    color: rgba(226, 232, 240, 0.78);
  }

  .dispatch-operator__label::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #38bdf8;
    box-shadow: 0 0 0 6px rgba(56, 189, 248, 0.18);
  }

  .dispatch-operator__name {
    margin: 0;
    font-family: 'DM Sans', sans-serif;
    font-size: 1.35rem;
    font-weight: 800;
    color: #ffffff;
  }

  .dispatch-operator__role,
  .dispatch-operator__last {
    margin: 6px 0 0;
    font-size: 0.9rem;
    color: rgba(226, 232, 240, 0.78);
  }

  .dispatch-operator__avatar {
    width: 64px;
    height: 64px;
    border-radius: 22px;
    background: linear-gradient(135deg, rgba(56, 189, 248, 0.24), rgba(191, 219, 254, 0.2));
    border: 1px solid rgba(255, 255, 255, 0.14);
    display: grid;
    place-items: center;
    font-family: 'DM Sans', sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
    color: #ffffff;
  }

  .dispatch-toolbar {
    position: relative;
    z-index: 1;
    margin-top: 22px;
    margin-bottom: 22px;
  }

  .dispatch-tabs {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 12px;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.14);
    background: rgba(255, 255, 255, 0.72);
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.05);
    backdrop-filter: blur(16px);
  }

  .dispatch-tab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 22px;
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: var(--dispatch-muted);
    font-weight: 700;
    transition: 0.18s ease;
  }

  .dispatch-tab:hover {
    color: var(--dispatch-text);
    background: rgba(255, 255, 255, 0.9);
  }

  .dispatch-tab.active-tab {
    background: #ffffff;
    color: var(--dispatch-primary);
    box-shadow: 0 14px 28px rgba(13, 124, 193, 0.18);
  }

  .dispatch-tab__count {
    min-width: 28px;
    height: 28px;
    padding: 0 8px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(148, 163, 184, 0.15);
    color: inherit;
    font-size: 0.78rem;
    font-weight: 800;
  }

  .dispatch-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
  }

  .dispatch-card {
    position: relative;
    min-height: 420px;
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-radius: 28px;
    background: rgba(255, 255, 255, 0.94);
    box-shadow: var(--dispatch-shadow-soft);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .dispatch-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.12);
  }

  .dispatch-card::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    background: var(--card-accent, var(--dispatch-primary));
  }

  .dispatch-card::after {
    content: "";
    position: absolute;
    top: 0;
    right: 0;
    width: 140px;
    height: 140px;
    border-radius: 999px;
    background: radial-gradient(circle, rgba(13, 124, 193, 0.12), transparent 70%);
    transform: translate(28%, -28%);
    pointer-events: none;
  }

  .dispatch-card__inner {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    height: 100%;
    padding: 26px 26px 24px;
  }

  .dispatch-card--upcoming {
    --card-accent: var(--dispatch-primary);
  }

  .dispatch-card--pending {
    --card-accent: #6b7280;
  }

  .dispatch-card--inprogress {
    --card-accent: var(--dispatch-amber);
  }

  .dispatch-card--payment {
    --card-accent: var(--dispatch-copper);
  }

  .dispatch-card--done {
    --card-accent: var(--dispatch-green);
  }

  .dispatch-card--cancelled {
    --card-accent: #cbd5e1;
    opacity: 0.82;
  }

  .dispatch-card__top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 22px;
  }

  .dispatch-card__badges {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
  }

  .dispatch-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 16px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .dispatch-pill--service {
    background: rgba(13, 124, 193, 0.12);
    color: var(--dispatch-primary);
  }

  .dispatch-pill--status {
    background: rgba(148, 163, 184, 0.12);
    color: var(--dispatch-muted);
  }

  .dispatch-pill--pending {
    background: rgba(100, 116, 139, 0.14);
    color: #475569;
  }

  .dispatch-pill--upcoming {
    background: var(--dispatch-primary-soft);
    color: var(--dispatch-primary);
  }

  .dispatch-pill--inprogress {
    background: var(--dispatch-amber-soft);
    color: var(--dispatch-amber);
  }

  .dispatch-pill--payment {
    background: var(--dispatch-copper-soft);
    color: var(--dispatch-copper);
  }

  .dispatch-pill--done {
    background: var(--dispatch-green-soft);
    color: var(--dispatch-green);
  }

  .dispatch-pill--cancelled {
    background: #f1f5f9;
    color: #64748b;
  }

  .dispatch-timer {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 999px;
    background: rgba(201, 123, 25, 0.1);
    color: var(--dispatch-amber);
    font-weight: 800;
  }

  .dispatch-timer .material-symbols-outlined {
    font-size: 1rem;
  }

  .dispatch-card__customer {
    margin: 0;
    font-family: 'DM Sans', sans-serif;
    font-size: 2rem;
    line-height: 1.05;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: var(--dispatch-text);
  }

  .dispatch-card__service {
    margin: 10px 0 0;
    font-size: 1.05rem;
    line-height: 1.55;
    font-weight: 600;
    color: #1e293b;
  }

  .dispatch-card__meta {
    display: grid;
    gap: 10px;
    margin-top: 22px;
  }

  .dispatch-meta-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 0.96rem;
    line-height: 1.55;
    color: var(--dispatch-muted);
  }

  .dispatch-meta-row .material-symbols-outlined {
    margin-top: 2px;
    font-size: 1.1rem;
    color: var(--dispatch-soft);
  }

  .dispatch-time-chip {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-top: 14px;
    padding: 10px 14px;
    border-radius: 14px;
    background: rgba(13, 124, 193, 0.1);
    color: var(--dispatch-primary);
    font-weight: 800;
  }

  .dispatch-time-chip--warm {
    background: rgba(201, 123, 25, 0.14);
    color: var(--dispatch-amber);
  }

  .dispatch-summary-box,
  .dispatch-workflow {
    margin-top: 22px;
    padding: 18px 18px 16px;
    border-radius: 22px;
    background: linear-gradient(180deg, rgba(241, 245, 249, 0.92), rgba(248, 250, 252, 0.94));
    border: 1px solid rgba(148, 163, 184, 0.14);
  }

  .dispatch-summary-box__label {
    display: block;
    margin-bottom: 8px;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    font-weight: 800;
    color: var(--dispatch-soft);
  }

  .dispatch-summary-box__value {
    display: block;
    font-family: 'DM Sans', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: var(--dispatch-copper);
  }

  .dispatch-summary-box__hint {
    display: block;
    margin-top: 6px;
    color: var(--dispatch-muted);
  }

  .dispatch-workflow__title {
    margin: 0 0 14px;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-weight: 800;
    color: var(--dispatch-soft);
  }

  .dispatch-workflow__list {
    display: grid;
    gap: 14px;
  }

  .dispatch-workflow__item {
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 700;
    color: #334155;
  }

  .dispatch-workflow__icon {
    width: 28px;
    height: 28px;
    border-radius: 10px;
    display: inline-grid;
    place-items: center;
    background: rgba(148, 163, 184, 0.14);
    color: #94a3b8;
  }

  .dispatch-workflow__item.is-done .dispatch-workflow__icon {
    background: rgba(15, 159, 124, 0.12);
    color: var(--dispatch-green);
  }

  .dispatch-workflow__item.is-current .dispatch-workflow__icon {
    background: rgba(201, 123, 25, 0.14);
    color: var(--dispatch-amber);
  }

  .dispatch-workflow__item.is-locked {
    color: #94a3b8;
  }

  .dispatch-inline-note {
    margin-top: 14px;
    padding: 12px 14px;
    border-radius: 16px;
    background: rgba(13, 124, 193, 0.08);
    color: var(--dispatch-primary);
    font-size: 0.92rem;
    line-height: 1.55;
  }

  .dispatch-inline-note--danger {
    background: rgba(220, 38, 38, 0.08);
    color: var(--dispatch-danger);
  }

  .dispatch-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: auto;
    padding-top: 24px;
  }

  .dispatch-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
  }

  .dispatch-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 52px;
    padding: 0 18px;
    border: 1px solid transparent;
    border-radius: 18px;
    font-weight: 800;
    text-decoration: none;
    transition: 0.18s ease;
    cursor: pointer;
  }

  .dispatch-btn .material-symbols-outlined {
    font-size: 1.1rem;
  }

  .dispatch-btn:hover {
    transform: translateY(-1px);
  }

  .dispatch-btn--primary {
    background: linear-gradient(135deg, var(--dispatch-primary), #1497e0);
    color: #ffffff;
    box-shadow: 0 18px 28px rgba(13, 124, 193, 0.24);
  }

  .dispatch-btn--secondary {
    background: #eef4fb;
    color: #48617e;
    border-color: rgba(148, 163, 184, 0.14);
  }

  .dispatch-btn--warm {
    background: linear-gradient(135deg, #f0a020, #d98210);
    color: #ffffff;
    box-shadow: 0 16px 28px rgba(201, 123, 25, 0.22);
  }

  .dispatch-btn--ghost {
    background: rgba(255, 255, 255, 0.86);
    color: var(--dispatch-muted);
    border-color: rgba(148, 163, 184, 0.16);
  }

  .dispatch-btn--disabled,
  .dispatch-btn:disabled {
    background: #e2e8f0;
    color: #94a3b8;
    box-shadow: none;
    cursor: not-allowed;
    transform: none;
  }

  .dispatch-call-btn {
    flex-shrink: 0;
    width: 52px;
    height: 52px;
    border-radius: 18px;
    border: 1px solid rgba(13, 124, 193, 0.12);
    background: rgba(13, 124, 193, 0.08);
    color: var(--dispatch-primary);
    display: inline-grid;
    place-items: center;
    text-decoration: none;
    transition: 0.18s ease;
  }

  .dispatch-call-btn:hover {
    transform: translateY(-1px);
    background: rgba(13, 124, 193, 0.12);
  }

  .dispatch-empty {
    grid-column: 1 / -1;
    padding: 52px 22px;
    border-radius: 30px;
    border: 1px dashed rgba(148, 163, 184, 0.22);
    background: rgba(255, 255, 255, 0.78);
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.05);
    text-align: center;
    color: var(--dispatch-muted);
  }

  .dispatch-empty .material-symbols-outlined {
    font-size: 2.8rem;
    color: rgba(13, 124, 193, 0.42);
  }

  .dispatch-empty h3 {
    margin: 16px 0 8px;
    font-family: 'DM Sans', sans-serif;
    font-weight: 800;
    color: var(--dispatch-text);
  }

  .dispatch-empty p {
    margin: 0;
    font-size: 0.96rem;
  }

  .dispatch-modal .modal-dialog {
    max-width: 1080px;
  }

  .dispatch-modal--pricing .modal-dialog {
    max-width: 1180px;
  }

  .dispatch-modal__content {
    border: 0;
    border-radius: 32px;
    overflow: hidden;
    box-shadow: 0 28px 60px rgba(15, 23, 42, 0.2);
  }

  .dispatch-modal__header {
    padding: 30px 32px 22px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.16);
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.86), rgba(255, 255, 255, 0.98));
  }

  .dispatch-modal__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    font-weight: 800;
    color: var(--dispatch-primary);
  }

  .dispatch-modal__title {
    margin: 0;
    font-family: 'DM Sans', sans-serif;
    font-size: clamp(1.9rem, 2.3vw, 2.6rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: var(--dispatch-text);
  }

  .dispatch-modal__subtitle {
    margin: 10px 0 0;
    font-size: 1rem;
    line-height: 1.6;
    color: var(--dispatch-muted);
  }

  .dispatch-modal__close {
    width: 50px;
    height: 50px;
    border: 0;
    border-radius: 18px;
    background: rgba(15, 23, 42, 0.06);
    display: inline-grid;
    place-items: center;
    color: var(--dispatch-text);
  }

  .dispatch-modal__close:hover {
    background: rgba(15, 23, 42, 0.1);
  }

  .dispatch-modal__body {
    padding: 28px 32px 32px;
    background: #ffffff;
  }

  .dispatch-modal__content--pricing {
    background:
      radial-gradient(circle at top right, rgba(13, 124, 193, 0.12), transparent 26rem),
      linear-gradient(180deg, #f6fbff 0%, #ffffff 18%);
  }

  .dispatch-modal__header--pricing {
    background:
      linear-gradient(135deg, rgba(239, 246, 255, 0.95), rgba(255, 255, 255, 0.98) 56%, rgba(255, 247, 237, 0.72));
  }

  .dispatch-modal__body--pricing {
    background: transparent;
  }

  .dispatch-modal__footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 14px;
    padding-top: 22px;
    border-top: 1px solid rgba(148, 163, 184, 0.12);
  }

  .dispatch-cost-grid,
  .dispatch-complete-grid,
  .dispatch-detail-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(340px, 0.9fr);
    gap: 22px;
  }

  .dispatch-panel {
    padding: 22px;
    border-radius: 26px;
    border: 1px solid rgba(148, 163, 184, 0.14);
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.86), rgba(255, 255, 255, 0.98));
  }

  .dispatch-panel__title {
    margin: 0 0 16px;
    font-size: 0.82rem;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: var(--dispatch-soft);
  }

  .dispatch-booking-hero {
    padding: 18px 20px;
    border-radius: 22px;
    background: linear-gradient(135deg, rgba(13, 124, 193, 0.1), rgba(13, 124, 193, 0.02));
    margin-bottom: 18px;
  }

  .dispatch-booking-hero__row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
  }

  .dispatch-booking-hero__customer {
    margin: 0;
    font-family: 'DM Sans', sans-serif;
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--dispatch-text);
  }

  .dispatch-booking-hero__service {
    margin: 6px 0 0;
    color: var(--dispatch-muted);
    line-height: 1.55;
  }

  .dispatch-booking-hero--pricing {
    padding: 22px 24px;
    border: 1px solid rgba(13, 124, 193, 0.14);
    background:
      linear-gradient(135deg, rgba(219, 234, 254, 0.78), rgba(255, 255, 255, 0.96)),
      #ffffff;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
  }

  .dispatch-booking-hero__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: var(--dispatch-primary);
  }

  .dispatch-booking-hero__eyebrow::before {
    content: "";
    width: 26px;
    height: 1px;
    background: rgba(13, 124, 193, 0.32);
  }

  .dispatch-booking-hero__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 18px;
  }

  .dispatch-booking-meta-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 10px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.88);
    border: 1px solid rgba(148, 163, 184, 0.18);
    font-size: 0.82rem;
    font-weight: 700;
    color: #334155;
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.04);
  }

  .dispatch-booking-meta-chip[data-state="on"] {
    background: rgba(249, 115, 22, 0.12);
    border-color: rgba(249, 115, 22, 0.18);
    color: #c2410c;
  }

  .dispatch-booking-meta-chip[data-state="travel"] {
    background: rgba(13, 124, 193, 0.12);
    border-color: rgba(13, 124, 193, 0.18);
    color: var(--dispatch-primary-strong);
  }

  .dispatch-booking-meta-chip[data-state="muted"] {
    background: rgba(148, 163, 184, 0.14);
    color: #475569;
  }

  .dispatch-field {
    display: grid;
    gap: 8px;
    margin-bottom: 18px;
  }

  .dispatch-field:last-child {
    margin-bottom: 0;
  }

  .dispatch-field label {
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #334155;
  }

  .dispatch-input,
  .dispatch-textarea {
    width: 100%;
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 20px;
    padding: 16px 18px;
    background: #edf4ff;
    font-size: 1rem;
    font-weight: 700;
    color: var(--dispatch-text);
    outline: none;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
  }

  .dispatch-input:focus,
  .dispatch-textarea:focus {
    border-color: rgba(13, 124, 193, 0.34);
    box-shadow: 0 0 0 4px rgba(13, 124, 193, 0.08);
  }

  .dispatch-textarea {
    min-height: 240px;
    resize: vertical;
    font-weight: 600;
  }

  .dispatch-input-wrap {
    position: relative;
  }

  .dispatch-input-suffix {
    position: absolute;
    top: 50%;
    right: 18px;
    transform: translateY(-50%);
    font-weight: 800;
    color: var(--dispatch-muted);
  }

  .dispatch-summary-tile {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 18px;
    border-radius: 24px;
    border: 2px dashed rgba(13, 124, 193, 0.32);
    background: rgba(240, 249, 255, 0.74);
  }

  .dispatch-summary-tile__icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    background: var(--dispatch-primary);
    color: #ffffff;
    display: grid;
    place-items: center;
    flex-shrink: 0;
  }

  .dispatch-summary-tile__copy {
    flex: 1;
  }

  .dispatch-summary-tile__label {
    display: block;
    font-size: 0.78rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    font-weight: 800;
    color: var(--dispatch-primary);
  }

  .dispatch-summary-tile__hint {
    display: block;
    margin-top: 6px;
    color: var(--dispatch-muted);
  }

  .dispatch-summary-tile__value {
    font-family: 'DM Sans', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: var(--dispatch-primary-strong);
  }

  .dispatch-readonly {
    padding: 16px 18px;
    border-radius: 18px;
    background: #f8fafc;
    border: 1px solid rgba(148, 163, 184, 0.14);
    color: var(--dispatch-muted);
  }

  .dispatch-readonly strong {
    color: var(--dispatch-text);
  }

  .dispatch-readonly--accent {
    background: linear-gradient(180deg, rgba(239, 246, 255, 0.92), rgba(255, 255, 255, 0.98));
    border-color: rgba(13, 124, 193, 0.14);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.82);
  }

  .dispatch-readonly__value {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .dispatch-cost-stack {
    display: grid;
    gap: 18px;
  }

  .dispatch-cost-grid--pricing {
    grid-template-columns: minmax(0, 1.22fr) minmax(320px, 0.82fr);
    align-items: start;
  }

  .dispatch-panel--editor {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(246, 250, 255, 0.98));
    box-shadow: 0 20px 48px rgba(15, 23, 42, 0.06);
  }

  .dispatch-panel--summary {
    position: sticky;
    top: 0;
    background:
      linear-gradient(180deg, rgba(248, 250, 252, 0.98), rgba(255, 255, 255, 0.98)),
      #ffffff;
    box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08);
  }

  .dispatch-cost-flow {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 18px;
  }

  .dispatch-cost-flow__step {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    padding: 9px 14px;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.12);
    color: #475569;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .dispatch-cost-flow__step.is-active {
    background: rgba(13, 124, 193, 0.14);
    color: var(--dispatch-primary);
  }

  .dispatch-cost-section-card {
    padding: 18px;
    border-radius: 24px;
    background: #f8fbff;
    border: 1px solid rgba(148, 163, 184, 0.18);
  }

  .dispatch-cost-section-card--labor {
    background: linear-gradient(180deg, rgba(239, 246, 255, 0.82), rgba(255, 255, 255, 0.98));
  }

  .dispatch-cost-section-card--parts {
    background: linear-gradient(180deg, rgba(255, 247, 237, 0.78), rgba(255, 255, 255, 0.98));
  }

  .dispatch-cost-section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
  }

  .dispatch-cost-section-head p {
    margin: 6px 0 0;
    color: var(--dispatch-muted);
    line-height: 1.6;
  }

  .dispatch-cost-section-kicker {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
  }

  .dispatch-cost-section-index,
  .dispatch-cost-section-counter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
  }

  .dispatch-cost-section-index {
    background: #ffffff;
    color: var(--dispatch-primary);
    border: 1px solid rgba(13, 124, 193, 0.14);
  }

  .dispatch-cost-section-counter {
    background: rgba(15, 23, 42, 0.06);
    color: #475569;
  }

  .dispatch-chip-button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: none;
    border-radius: 999px;
    padding: 10px 16px;
    background: rgba(13, 124, 193, 0.12);
    color: var(--dispatch-primary);
    font-size: 0.82rem;
    font-weight: 800;
    white-space: nowrap;
    cursor: pointer;
  }

  .dispatch-chip-button:hover {
    background: rgba(13, 124, 193, 0.18);
  }

  .dispatch-chip-button--warm {
    background: rgba(201, 123, 25, 0.12);
    color: var(--dispatch-copper);
  }

  .dispatch-chip-button--warm:hover {
    background: rgba(201, 123, 25, 0.18);
  }

  .dispatch-chip-button:disabled {
    opacity: 0.55;
    cursor: not-allowed;
  }

  .dispatch-part-catalog {
    padding: 16px;
    margin-bottom: 16px;
    border-radius: 22px;
    border: 1px dashed rgba(201, 123, 25, 0.28);
    background: rgba(255, 255, 255, 0.74);
  }

  .dispatch-part-catalog__toolbar {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 12px;
    align-items: end;
  }

  .dispatch-part-catalog__field {
    display: grid;
    gap: 8px;
  }

  .dispatch-part-catalog__field span {
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #475569;
  }

  .dispatch-part-catalog__status {
    margin-top: 12px;
    color: var(--dispatch-muted);
    line-height: 1.6;
  }

  .dispatch-part-catalog__results {
    display: grid;
    gap: 10px;
    max-height: 320px;
    margin-top: 14px;
    overflow: auto;
    padding-right: 4px;
  }

  .dispatch-part-option {
    display: grid;
    grid-template-columns: auto 56px minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
    padding: 12px 14px;
    border-radius: 18px;
    background: #ffffff;
    border: 1px solid rgba(148, 163, 184, 0.18);
    cursor: pointer;
    transition: 0.18s ease;
  }

  .dispatch-part-option:hover {
    border-color: rgba(13, 124, 193, 0.26);
    transform: translateY(-1px);
  }

  .dispatch-part-option.is-selected {
    border-color: rgba(13, 124, 193, 0.34);
    background: rgba(232, 243, 255, 0.76);
    box-shadow: 0 12px 24px rgba(13, 124, 193, 0.08);
  }

  .dispatch-part-option.is-disabled {
    opacity: 0.56;
    cursor: not-allowed;
  }

  .dispatch-part-option__check {
    width: 18px;
    height: 18px;
    accent-color: var(--dispatch-primary);
  }

  .dispatch-part-option__thumb {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    overflow: hidden;
    background: rgba(15, 23, 42, 0.06);
    display: grid;
    place-items: center;
    color: var(--dispatch-soft);
  }

  .dispatch-part-option__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .dispatch-part-option__body {
    min-width: 0;
    display: grid;
    gap: 4px;
  }

  .dispatch-part-option__title {
    font-weight: 800;
    color: var(--dispatch-text);
  }

  .dispatch-part-option__meta {
    font-size: 0.82rem;
    color: var(--dispatch-muted);
  }

  .dispatch-part-option__price {
    font-weight: 800;
    color: var(--dispatch-copper);
    white-space: nowrap;
  }

  .dispatch-line-items {
    display: grid;
    gap: 14px;
  }

  .dispatch-line-item {
    padding: 16px;
    border-radius: 22px;
    background: #ffffff;
    border: 1px solid rgba(148, 163, 184, 0.18);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
  }

  .dispatch-line-item__tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 12px;
    padding: 7px 12px;
    border-radius: 999px;
    background: rgba(13, 124, 193, 0.1);
    color: var(--dispatch-primary-strong);
    font-size: 0.78rem;
    font-weight: 800;
  }

  .dispatch-line-item__grid {
    display: grid;
    grid-template-columns: minmax(0, 1.8fr) minmax(180px, 0.9fr) auto;
    gap: 12px;
    align-items: end;
  }

  .dispatch-line-item__grid.is-parts {
    grid-template-columns: minmax(0, 1.8fr) minmax(180px, 0.9fr) minmax(160px, 0.8fr) auto;
  }

  .dispatch-line-item__field {
    display: grid;
    gap: 8px;
  }

  .dispatch-line-item__field span {
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #475569;
  }

  .dispatch-line-item__remove {
    width: 48px;
    height: 48px;
    border: none;
    border-radius: 16px;
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    display: grid;
    place-items: center;
    cursor: pointer;
  }

  .dispatch-line-item__remove:hover {
    background: rgba(239, 68, 68, 0.16);
  }

  .dispatch-summary-list {
    display: grid;
    gap: 10px;
    margin-bottom: 18px;
  }

  .dispatch-summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.8);
    border: 1px solid rgba(148, 163, 184, 0.14);
    color: var(--dispatch-muted);
  }

  .dispatch-summary-row strong {
    color: var(--dispatch-text);
  }

  .dispatch-summary-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
  }

  .dispatch-summary-kicker {
    display: inline-flex;
    align-items: center;
    margin-bottom: 10px;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: var(--dispatch-primary);
  }

  .dispatch-summary-lede {
    margin: 0 0 18px;
    color: var(--dispatch-muted);
    line-height: 1.65;
  }

  .dispatch-summary-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 36px;
    padding: 8px 14px;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.14);
    color: #475569;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .dispatch-summary-status[data-state="attention"] {
    background: rgba(249, 115, 22, 0.14);
    color: #c2410c;
  }

  .dispatch-summary-status[data-state="ready"] {
    background: rgba(15, 159, 124, 0.12);
    color: var(--dispatch-green);
  }

  .dispatch-note-card {
    margin-bottom: 18px;
    padding: 18px;
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    background: rgba(255, 255, 255, 0.86);
  }

  .dispatch-note-card__label {
    display: block;
    margin-bottom: 6px;
    font-size: 0.82rem;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #334155;
  }

  .dispatch-note-card__hint {
    margin: 0 0 12px;
    color: var(--dispatch-muted);
    line-height: 1.6;
  }

  .dispatch-textarea--note {
    min-height: 180px;
    background: #f8fbff;
  }

  .dispatch-summary-footnote {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px dashed rgba(148, 163, 184, 0.2);
    color: var(--dispatch-muted);
    font-size: 0.9rem;
    line-height: 1.6;
  }

  .dispatch-cost-meta-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    margin-top: 18px;
  }

  .dispatch-field--compact {
    margin-bottom: 0;
  }

  .dispatch-modal__footer--pricing {
    margin-top: 24px;
  }

  .dispatch-cost-item-list {
    display: grid;
    gap: 12px;
  }

  .dispatch-cost-item-card {
    padding: 14px 16px;
    border-radius: 18px;
    background: #ffffff;
    border: 1px solid rgba(148, 163, 184, 0.18);
  }

  .dispatch-cost-item-card__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
  }

  .dispatch-cost-item-card__title {
    font-weight: 800;
    color: var(--dispatch-text);
  }

  .dispatch-cost-item-card__meta {
    margin-top: 6px;
    color: var(--dispatch-muted);
    line-height: 1.55;
  }

  .dispatch-cost-item-card__note {
    margin-top: 10px;
    color: var(--dispatch-muted);
    line-height: 1.55;
  }

  .dispatch-warranty-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: fit-content;
    min-height: 30px;
    margin-top: 10px;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    border: 1px solid transparent;
    background: #eef2f7;
    color: #475569;
  }

  .dispatch-warranty-pill.is-active {
    background: rgba(236, 253, 245, 0.96);
    border-color: rgba(16, 185, 129, 0.2);
    color: #059669;
  }

  .dispatch-warranty-pill.is-expired,
  .dispatch-warranty-pill.is-used {
    background: rgba(254, 242, 242, 0.96);
    border-color: rgba(239, 68, 68, 0.2);
    color: #dc2626;
  }

  .dispatch-warranty-pill.is-neutral {
    background: #eff6ff;
    border-color: rgba(13, 124, 193, 0.18);
    color: var(--dispatch-primary-strong);
  }

  .dispatch-warranty-action {
    margin-top: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    padding: 0 14px;
    border-radius: 12px;
    border: 1px solid rgba(13, 124, 193, 0.18);
    background: rgba(13, 124, 193, 0.08);
    color: var(--dispatch-primary-strong);
    font-size: 0.84rem;
    font-weight: 800;
    cursor: pointer;
    transition: 0.18s ease;
  }

  .dispatch-warranty-action:hover {
    background: rgba(13, 124, 193, 0.14);
    transform: translateY(-1px);
  }

  @media (max-width: 767.98px) {
    .dispatch-cost-section-head {
      flex-direction: column;
      align-items: stretch;
    }

    .dispatch-summary-header {
      flex-direction: column;
      align-items: stretch;
    }

    .dispatch-cost-meta-grid {
      grid-template-columns: 1fr;
    }

    .dispatch-line-item__grid,
    .dispatch-line-item__grid.is-parts {
      grid-template-columns: 1fr;
    }

    .dispatch-part-catalog__toolbar {
      grid-template-columns: 1fr;
    }

    .dispatch-part-option {
      grid-template-columns: auto minmax(0, 1fr);
    }

    .dispatch-part-option__thumb,
    .dispatch-part-option__price {
      grid-column: 2;
    }

    .dispatch-line-item__remove {
      width: 100%;
      height: 44px;
    }
  }

  .dispatch-radio-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    margin-top: 16px;
  }

  .dispatch-pay-option {
    position: relative;
  }

  .dispatch-pay-option input {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
  }

  .dispatch-pay-option__card {
    min-height: 88px;
    padding: 18px 20px;
    border-radius: 22px;
    border: 2px solid rgba(148, 163, 184, 0.18);
    background: #f8fbff;
    display: flex;
    align-items: center;
    gap: 14px;
    font-weight: 800;
    color: var(--dispatch-text);
    transition: 0.18s ease;
  }

  .dispatch-pay-option__card .material-symbols-outlined {
    font-size: 1.5rem;
    color: var(--dispatch-primary);
  }

  .dispatch-pay-option.is-active .dispatch-pay-option__card {
    border-color: var(--dispatch-primary);
    background: rgba(13, 124, 193, 0.08);
    box-shadow: 0 16px 28px rgba(13, 124, 193, 0.14);
  }

  .dispatch-upload-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    margin-top: 16px;
  }

  .dispatch-upload-area {
    padding: 18px;
    border-radius: 22px;
    background: #f8fbff;
    border: 1px solid rgba(148, 163, 184, 0.14);
  }

  .dispatch-upload-area__title {
    margin: 0 0 10px;
    font-size: 0.92rem;
    font-weight: 800;
    color: var(--dispatch-text);
  }

  .dispatch-upload-area__hint {
    margin: 0 0 14px;
    font-size: 0.88rem;
    color: var(--dispatch-muted);
    line-height: 1.55;
  }

  .dispatch-file-input {
    width: 100%;
    border: 1px dashed rgba(13, 124, 193, 0.28);
    border-radius: 18px;
    padding: 14px;
    background: #ffffff;
  }

  .dispatch-preview-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 14px;
  }

  .dispatch-preview-card {
    width: 96px;
    height: 96px;
    border-radius: 18px;
    overflow: hidden;
    background: #e2e8f0;
    border: 1px solid rgba(148, 163, 184, 0.18);
  }

  .dispatch-preview-card img,
  .dispatch-preview-card video {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .dispatch-video-preview {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px;
    border-radius: 18px;
    background: #edf4ff;
    color: var(--dispatch-text);
    font-weight: 700;
  }

  .dispatch-video-preview .material-symbols-outlined {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: rgba(13, 124, 193, 0.12);
    color: var(--dispatch-primary);
    display: grid;
    place-items: center;
  }

  .dispatch-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 18px;
    margin-top: 18px;
    border-radius: 18px;
    border-left: 4px solid var(--dispatch-danger);
    background: var(--dispatch-danger-soft);
    color: #b91c1c;
    line-height: 1.6;
  }

  .dispatch-alert .material-symbols-outlined {
    font-size: 1.3rem;
  }

  .dispatch-detail-grid .dispatch-panel {
    min-height: 100%;
  }

  .dispatch-detail-list {
    display: grid;
    gap: 14px;
  }

  .dispatch-detail-item {
    padding-bottom: 14px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.14);
  }

  .dispatch-detail-item:last-child {
    padding-bottom: 0;
    border-bottom: 0;
  }

  .dispatch-detail-item__label {
    display: block;
    margin-bottom: 6px;
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--dispatch-soft);
  }

  .dispatch-detail-item__value {
    color: var(--dispatch-text);
    font-size: 1rem;
    line-height: 1.65;
  }

  .dispatch-media-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 14px;
  }

  .dispatch-media-card {
    width: 104px;
    height: 104px;
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid rgba(148, 163, 184, 0.18);
    background: #f8fafc;
  }

  .dispatch-media-card img,
  .dispatch-media-card video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  .dispatch-cost-breakdown {
    display: grid;
    gap: 12px;
  }

  .dispatch-cost-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 16px;
    border-radius: 18px;
    background: #f8fbff;
  }

  .dispatch-cost-row strong {
    color: var(--dispatch-text);
  }

  .dispatch-cost-total {
    margin-top: 8px;
    padding: 20px;
    border-radius: 22px;
    background: linear-gradient(135deg, rgba(13, 124, 193, 0.08), rgba(15, 159, 124, 0.08));
  }

  .dispatch-cost-total__label {
    display: block;
    font-size: 0.78rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    font-weight: 800;
    color: var(--dispatch-soft);
  }

  .dispatch-cost-total__value {
    display: block;
    margin-top: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: var(--dispatch-green);
  }

  .worker-main {
    background: #eff6ff;
  }

  .dispatch-shell {
    max-width: 1320px;
    margin: 0 auto;
    padding: 18px 24px 32px;
  }

  .dispatch-shell::before,
  .dispatch-shell::after {
    display: none;
  }

  .dispatch-hero {
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
    backdrop-filter: none;
  }

  .dispatch-eyebrow {
    margin-bottom: 10px;
    font-size: 0.68rem;
    letter-spacing: 0.22em;
  }

  .dispatch-hero__top {
    align-items: center;
    margin-bottom: 20px;
  }

  .dispatch-hero__headline h1 {
    font-size: clamp(2.6rem, 4vw, 3.8rem);
  }

  .dispatch-hero__headline p {
    max-width: 620px;
    margin-top: 8px;
    font-size: 0.92rem;
    color: #50627f;
  }

  .dispatch-refresh-btn {
    min-width: 148px;
    min-height: 54px;
    justify-content: center;
    border-radius: 20px;
    background: #ffffff;
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
  }

  .dispatch-hero__meta {
    grid-template-columns: minmax(0, 1fr) minmax(280px, 320px);
    align-items: stretch;
  }

  .dispatch-stats {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
  }

  .dispatch-stat {
    min-height: 132px;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 18px 36px rgba(148, 163, 184, 0.18);
  }

  .dispatch-operator {
    padding: 20px 22px;
    border-radius: 26px;
    background: #ffffff;
    border: 1px solid rgba(148, 163, 184, 0.14);
    color: var(--dispatch-text);
    box-shadow: 0 18px 36px rgba(148, 163, 184, 0.18);
  }

  .dispatch-operator__label {
    color: #6a7f9d;
  }

  .dispatch-operator__name {
    color: var(--dispatch-text);
  }

  .dispatch-operator__role,
  .dispatch-operator__last {
    color: #64748b;
  }

  .dispatch-operator__avatar {
    background: linear-gradient(135deg, #10233c, #39587a);
    border-color: rgba(15, 23, 42, 0.1);
  }

  .dispatch-toolbar {
    margin-top: 24px;
    margin-bottom: 24px;
  }

  .dispatch-tabs {
    width: 100%;
    justify-content: space-between;
    gap: 8px;
    padding: 12px;
    border: 0;
    border-radius: 30px;
    background: rgba(231, 240, 249, 0.92);
    box-shadow: inset 0 0 0 1px rgba(160, 182, 205, 0.14);
  }

  .dispatch-tab {
    flex: 1 1 0;
    padding: 16px 12px;
    color: #5d6f8c;
  }

  .dispatch-tab__count {
    min-width: 24px;
    height: 24px;
    font-size: 0.72rem;
    background: rgba(196, 211, 228, 0.55);
  }

  .dispatch-tab.active-tab {
    color: var(--dispatch-primary);
    box-shadow: 0 12px 24px rgba(106, 145, 191, 0.18);
  }

  .dispatch-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 24px;
    align-items: start;
  }

  .dispatch-card {
    min-height: 0;
    border-radius: 28px;
    background: #ffffff;
    box-shadow: 0 24px 42px rgba(148, 163, 184, 0.18);
  }

  .dispatch-card::before {
    width: 6px;
  }

  .dispatch-card::after {
    display: none;
  }

  .dispatch-card__inner {
    padding: 26px 22px 22px;
  }

  .dispatch-card__top {
    margin-bottom: 12px;
  }

  .dispatch-card__badges {
    width: 100%;
    justify-content: space-between;
    gap: 12px;
  }

  .dispatch-pill {
    padding: 9px 16px;
    font-size: 0.76rem;
    letter-spacing: 0.08em;
  }

  .dispatch-pill--service {
    max-width: 170px;
    justify-content: flex-start;
    text-align: left;
    line-height: 1.45;
    border-radius: 20px;
    background: #d8ebfb;
    color: #0c7dc3;
  }

  .dispatch-timer {
    padding: 9px 16px;
    background: #fff3e3;
    color: #d07c16;
  }

  .dispatch-card__status-row {
    margin-bottom: 18px;
  }

  .dispatch-card__customer {
    font-size: 1.3rem;
    line-height: 1.15;
  }

  .dispatch-card__service {
    min-height: 44px;
    font-size: 0.98rem;
    line-height: 1.45;
  }

  .dispatch-card__meta {
    gap: 12px;
    margin-top: 18px;
  }

  .dispatch-meta-row {
    font-size: 0.95rem;
  }

  .dispatch-time-chip {
    width: 100%;
    justify-content: flex-start;
    margin-top: 18px;
    border-radius: 18px;
    padding: 14px 16px;
    background: #f7ecdf;
    color: #d07c16;
  }

  .dispatch-summary-box,
  .dispatch-workflow {
    margin-top: 20px;
    border-radius: 22px;
    background: #f4f7fb;
    border-color: #dce6f2;
  }

  .dispatch-summary-box__label,
  .dispatch-workflow__title {
    color: #8aa1bc;
  }

  .dispatch-summary-box__value {
    font-size: 1.85rem;
  }

  .dispatch-inline-note {
    margin-top: 16px;
    border-radius: 18px;
    font-size: 0.88rem;
  }

  .dispatch-card__footer {
    padding-top: 18px;
  }

  .dispatch-card__action-stack {
    width: 100%;
    display: grid;
    gap: 12px;
  }

  .dispatch-card__action-row {
    display: flex;
    gap: 12px;
    align-items: stretch;
  }

  .dispatch-card__action-row .dispatch-btn {
    flex: 1 1 0;
  }

  .dispatch-card__action-row--split .dispatch-btn:first-child {
    flex: 1.2 1 0;
  }

  .dispatch-card__action-row--split .dispatch-btn:last-child {
    flex: 0.86 1 0;
  }

  .dispatch-card__action-row .dispatch-call-btn {
    flex: 0 0 54px;
    width: 54px;
    height: auto;
    min-height: 52px;
    border-radius: 18px;
    background: #edf6ff;
    border-color: #c9ddf4;
  }

  .dispatch-btn {
    min-height: 52px;
    border-radius: 18px;
    font-size: 0.98rem;
  }

  .dispatch-btn--primary {
    box-shadow: 0 16px 28px rgba(13, 124, 193, 0.22);
  }

  .dispatch-btn--secondary {
    background: #eef3f8;
    color: #48617e;
  }

  .dispatch-route-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(290px, 0.82fr);
    gap: 18px;
    align-items: start;
  }

  .dispatch-route-map-shell {
    position: relative;
    min-height: 440px;
    border-radius: 26px;
    overflow: hidden;
    border: 1px solid rgba(148, 163, 184, 0.16);
    background:
      radial-gradient(circle at top left, rgba(13, 124, 193, 0.08), transparent 18rem),
      linear-gradient(180deg, #eff6ff 0%, #f8fbff 100%);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
  }

  .dispatch-route-map-canvas {
    width: 100%;
    height: 440px;
    min-height: 440px;
    display: block;
    background: transparent;
  }

  .dispatch-route-map-canvas .leaflet-control-attribution,
  .dispatch-route-map-canvas .leaflet-control-zoom {
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
    border: 0;
  }

  .dispatch-route-pin {
    position: relative;
    width: 34px;
    height: 34px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    border: 3px solid rgba(255, 255, 255, 0.96);
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.18);
    color: #fff;
    font-size: 1rem;
  }

  .dispatch-route-pin::after {
    content: attr(data-label);
    position: absolute;
    left: 50%;
    bottom: calc(100% + 8px);
    transform: translateX(-50%);
    padding: 4px 9px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.88);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    white-space: nowrap;
  }

  .dispatch-route-pin .material-symbols-outlined {
    font-size: 1rem;
    line-height: 1;
  }

  .dispatch-route-pin__image {
    width: 20px;
    height: 20px;
    object-fit: contain;
    display: block;
    border-radius: 999px;
  }

  .dispatch-route-pin--worker {
    background: linear-gradient(135deg, #0f9f7c, #0c7f64);
  }

  .dispatch-route-pin--customer {
    background: linear-gradient(135deg, #0d7cc1, #0a5e95);
  }

  .dispatch-route-map-fallback {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 24px;
    text-align: center;
    color: var(--dispatch-muted);
    background: linear-gradient(180deg, rgba(239, 246, 255, 0.96), rgba(248, 250, 252, 0.98));
  }

  .dispatch-route-map-fallback[hidden] {
    display: none;
  }

  .dispatch-route-map-fallback .material-symbols-outlined {
    width: 58px;
    height: 58px;
    display: grid;
    place-items: center;
    border-radius: 20px;
    background: rgba(13, 124, 193, 0.1);
    color: var(--dispatch-primary);
    font-size: 1.8rem;
  }

  .dispatch-route-map-fallback strong {
    font-size: 1.05rem;
    color: var(--dispatch-text);
  }

  .dispatch-route-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 16px;
  }

  .dispatch-route-toolbar .dispatch-btn {
    min-height: 48px;
  }

  .dispatch-route-card {
    padding: 18px 18px 20px;
    border-radius: 22px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    background: rgba(255, 255, 255, 0.92);
    box-shadow: var(--dispatch-shadow-soft);
  }

  .dispatch-route-card--subtle {
    background: #f8fbff;
  }

  .dispatch-route-card__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--dispatch-soft);
  }

  .dispatch-route-card__title {
    margin: 0 0 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 1.18rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--dispatch-text);
  }

  .dispatch-route-card__address {
    margin: 0;
    color: var(--dispatch-muted);
    line-height: 1.65;
  }

  .dispatch-route-coords {
    margin-top: 12px;
    font-size: 0.84rem;
    color: var(--dispatch-soft);
    word-break: break-word;
  }

  .dispatch-route-stats {
    display: grid;
    gap: 12px;
    margin: 14px 0;
  }

  .dispatch-route-stat {
    padding: 18px;
    border-radius: 22px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    background: rgba(255, 255, 255, 0.92);
    box-shadow: var(--dispatch-shadow-soft);
  }

  .dispatch-route-stat__label {
    display: block;
    margin-bottom: 10px;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--dispatch-soft);
  }

  .dispatch-route-stat__value {
    display: block;
    font-family: 'DM Sans', sans-serif;
    font-size: 1.6rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: var(--dispatch-text);
  }

  .dispatch-route-stat__value--small {
    font-size: 1rem;
    letter-spacing: 0;
    line-height: 1.6;
    word-break: break-word;
  }

  .dispatch-route-stat__hint {
    display: block;
    margin-top: 8px;
    color: var(--dispatch-muted);
    line-height: 1.6;
  }

  .dispatch-route-status {
    padding: 14px 16px;
    border-radius: 18px;
    font-size: 0.92rem;
    line-height: 1.6;
    border: 1px solid rgba(148, 163, 184, 0.14);
    background: #eff6ff;
    color: #215277;
  }

  .dispatch-route-status[data-tone="success"] {
    background: #ecfdf5;
    color: #0f766e;
    border-color: rgba(15, 118, 110, 0.14);
  }

  .dispatch-route-status[data-tone="warning"] {
    background: #fff7ed;
    color: #b45309;
    border-color: rgba(180, 83, 9, 0.14);
  }

  .dispatch-route-status[data-tone="danger"] {
    background: #fef2f2;
    color: #b91c1c;
    border-color: rgba(185, 28, 28, 0.14);
  }

  .dispatch-route-code {
    font-family: 'DM Sans', sans-serif;
    font-size: 1.1rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--dispatch-text);
  }

  @media (max-width: 1100px) {
    .dispatch-stats {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 1240px) {
    .dispatch-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dispatch-hero__meta,
    .dispatch-route-grid,
    .dispatch-cost-grid,
    .dispatch-complete-grid,
    .dispatch-detail-grid {
      grid-template-columns: 1fr;
    }

    .dispatch-panel--summary {
      position: static;
    }
  }

  @media (max-width: 768px) {
    .worker-main {
      margin-left: 0;
      padding-top: 96px;
    }

    .dispatch-shell {
      padding: 0 14px 6.75rem;
    }

    .dispatch-hero {
      margin-bottom: 18px;
      padding: 18px;
      border: 1px solid rgba(226, 232, 240, 0.9);
      border-radius: 28px;
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.92));
      box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
    }

    .dispatch-hero,
    .dispatch-card__inner,
    .dispatch-modal__header,
    .dispatch-modal__body {
      padding-left: 18px;
      padding-right: 18px;
    }

    .dispatch-hero__top,
    .dispatch-card__footer,
    .dispatch-booking-hero__row,
    .dispatch-modal__footer {
      flex-direction: column;
      align-items: stretch;
    }

    .dispatch-grid,
    .dispatch-stats,
    .dispatch-radio-grid,
    .dispatch-upload-grid {
      grid-template-columns: 1fr;
    }

    .dispatch-card {
      min-height: auto;
    }

    .dispatch-actions {
      width: 100%;
    }

    .dispatch-btn {
      width: 100%;
    }

    .dispatch-refresh-btn {
      width: 100%;
      min-width: 0;
    }

    .dispatch-card__action-row {
      flex-direction: column;
    }

    .dispatch-call-btn,
    .dispatch-card__action-row .dispatch-call-btn {
      width: 100%;
      flex: 1 1 auto;
      height: 52px;
      border-radius: 18px;
    }

    .dispatch-tabs {
      width: 100%;
      border-radius: 28px;
    }

    .dispatch-tab {
      flex: 1 1 calc(50% - 10px);
    }

    .dispatch-modal .modal-dialog {
      margin: 0.9rem;
    }
  }
</style>
@endpush

@section('content')
<div class="dispatch-page">
  <x-worker-sidebar />

  <main class="worker-main">
    <div class="dispatch-shell">
      <section class="dispatch-hero">
        <div class="dispatch-eyebrow">Concierge Dispatch</div>

        <div class="dispatch-hero__top">
          <div class="dispatch-hero__headline">
            <h1>Lịch làm việc</h1>
            <p>Điều phối đơn đã nhận, đơn đang sửa và đơn chờ thanh toán trong ngày.</p>
          </div>

          <button type="button" class="dispatch-refresh-btn" onclick="loadMyBookings(window.currentStatus)">
            <span class="material-symbols-outlined">refresh</span>
            Làm mới
          </button>
        </div>

        <div class="dispatch-hero__meta">
          <div class="dispatch-stats">
            <article class="dispatch-stat dispatch-stat--primary">
              <span class="dispatch-stat__label">Jobs Today</span>
              <span class="dispatch-stat__value" id="summaryTodayCount">00</span>
              <span class="dispatch-stat__hint">Lịch hẹn diễn ra trong hôm nay</span>
            </article>

            <article class="dispatch-stat dispatch-stat--amber">
              <span class="dispatch-stat__label">Đang sửa</span>
              <span class="dispatch-stat__value" id="summaryInProgressCount">00</span>
              <span class="dispatch-stat__hint">Công việc đang được xử lý</span>
            </article>

            <article class="dispatch-stat dispatch-stat--copper">
              <span class="dispatch-stat__label">Chờ thanh toán</span>
              <span class="dispatch-stat__value" id="summaryPendingPaymentCount">00</span>
              <span class="dispatch-stat__hint">Đã hoàn thành và chờ khách thanh toán</span>
            </article>

            <article class="dispatch-stat">
              <span class="dispatch-stat__label">Doanh thu dự kiến</span>
              <span class="dispatch-stat__value" id="summaryIncomeValue">0 đ</span>
              <span class="dispatch-stat__hint">Tính trên các đơn chưa bị hủy</span>
            </article>
          </div>

          <aside class="dispatch-operator">
            <div class="dispatch-operator__copy">
              <span class="dispatch-operator__label">Điều phối viên kỹ thuật</span>
              <h2 class="dispatch-operator__name" id="scheduleWorkerName">Đang tải...</h2>
              <p class="dispatch-operator__role" id="scheduleWorkerRole">Thợ kỹ thuật</p>
              <p class="dispatch-operator__last" id="summaryLastUpdated">Cập nhật lần cuối: --:--</p>
            </div>
            <div class="dispatch-operator__avatar" id="scheduleWorkerInitial">T</div>
          </aside>
        </div>
      </section>

      <section class="dispatch-toolbar">
        <div class="dispatch-tabs">
          <button class="dispatch-tab" data-status="pending" onclick="switchTab(this, 'pending')">
            <span>Chờ xác nhận</span>
            <span class="dispatch-tab__count" id="cnt-pending">0</span>
          </button>
          <button class="dispatch-tab" data-status="upcoming" onclick="switchTab(this, 'upcoming')">
            <span>Sắp tới</span>
            <span class="dispatch-tab__count" id="cnt-upcoming">0</span>
          </button>
          <button class="dispatch-tab active-tab" data-status="inprogress" onclick="switchTab(this, 'inprogress')">
            <span>Đang sửa</span>
            <span class="dispatch-tab__count" id="cnt-inprogress">0</span>
          </button>
          <button class="dispatch-tab" data-status="payment" onclick="switchTab(this, 'payment')">
            <span>Chờ thanh toán</span>
            <span class="dispatch-tab__count" id="cnt-payment">0</span>
          </button>
          <button class="dispatch-tab" data-status="done" onclick="switchTab(this, 'done')">
            <span>Hoàn thành</span>
            <span class="dispatch-tab__count" id="cnt-done">0</span>
          </button>
          <button class="dispatch-tab" data-status="cancelled" onclick="switchTab(this, 'cancelled')">
            <span>Đã hủy</span>
            <span class="dispatch-tab__count" id="cnt-cancelled">0</span>
          </button>
        </div>
      </section>

      <section id="bookingsContainer" class="dispatch-grid">
        <div class="dispatch-empty">
          <span class="material-symbols-outlined">hourglass_top</span>
          <h3>Đang tải lịch làm việc</h3>
          <p>Hệ thống đang lấy danh sách đơn sửa chữa của bạn.</p>
        </div>
      </section>
    </div>
  </main>
</div>

<div class="modal fade dispatch-modal dispatch-modal--pricing" id="modalCosts" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
    <div class="modal-content dispatch-modal__content dispatch-modal__content--pricing">
      <div class="dispatch-modal__header dispatch-modal__header--pricing d-flex justify-content-between align-items-start gap-3">
        <div>
          <div class="dispatch-modal__eyebrow">Pricing Desk</div>
          <h2 class="dispatch-modal__title">Cập nhật bảng giá sửa chữa</h2>
          <p class="dispatch-modal__subtitle">Điền rõ từng hạng mục để khách dễ kiểm tra, còn bạn dễ rà soát tổng tiền trước khi gửi yêu cầu thanh toán.</p>
        </div>
        <button type="button" class="dispatch-modal__close" data-bs-dismiss="modal" aria-label="Đóng">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <div class="dispatch-modal__body dispatch-modal__body--pricing modal-body">
        <form id="formUpdateCosts">
          <input type="hidden" id="costBookingId">

          <div class="dispatch-booking-hero dispatch-booking-hero--pricing">
            <div class="dispatch-booking-hero__row">
              <div>
                <div class="dispatch-booking-hero__eyebrow" id="costBookingReference">Đơn #0000</div>
                <p class="dispatch-booking-hero__customer" id="costCustomerName">Khách hàng</p>
                <p class="dispatch-booking-hero__service" id="costServiceName">Dịch vụ sửa chữa</p>
              </div>
              <span class="dispatch-pill dispatch-pill--status dispatch-pill--inprogress">Đang sửa</span>
            </div>

            <div class="dispatch-booking-hero__meta">
              <span class="dispatch-booking-meta-chip" id="costServiceModeBadge" data-state="travel">Sửa tại nhà</span>
              <span class="dispatch-booking-meta-chip" id="costTruckBadge" data-state="muted">Không thuê xe chở</span>
              <span class="dispatch-booking-meta-chip" id="costDistanceBadge" data-state="travel">Phí đi lại tự động</span>
            </div>
          </div>

          <div class="dispatch-cost-grid dispatch-cost-grid--pricing">
            <div class="dispatch-panel dispatch-panel--editor">
              <div class="dispatch-cost-flow" aria-hidden="true">
                <span class="dispatch-cost-flow__step is-active">1. Tiền công</span>
                <span class="dispatch-cost-flow__step">2. Linh kiện</span>
                <span class="dispatch-cost-flow__step">3. Rà soát</span>
              </div>

              <div class="dispatch-cost-stack">
                <div class="dispatch-cost-section-card dispatch-cost-section-card--labor">
                  <div class="dispatch-cost-section-head">
                    <div>
                      <div class="dispatch-cost-section-kicker">
                        <span class="dispatch-cost-section-index">01</span>
                        <span class="dispatch-cost-section-counter" id="laborCountBadge">0 dòng</span>
                      </div>
                      <h3 class="dispatch-panel__title mb-0">Tiền công</h3>
                      <p>Tách từng đầu việc để khách hiểu vì sao đơn có mức giá hiện tại.</p>
                    </div>
                    <button type="button" class="dispatch-chip-button" id="addLaborItem">
                      <span class="material-symbols-outlined">add</span>
                      Thêm dòng công
                    </button>
                  </div>
                  <div class="dispatch-line-items" id="laborItemsContainer"></div>
                </div>

                <div class="dispatch-cost-section-card dispatch-cost-section-card--parts">
                  <div class="dispatch-cost-section-head">
                    <div>
                      <div class="dispatch-cost-section-kicker">
                        <span class="dispatch-cost-section-index">02</span>
                        <span class="dispatch-cost-section-counter" id="partCountBadge">0 dòng</span>
                      </div>
                      <h3 class="dispatch-panel__title mb-0">Linh kiện</h3>
                      <p>Tìm linh kiện theo đúng dịch vụ của đơn, tick nhiều mục cùng lúc rồi thêm vào báo giá để hệ thống tự đổ sẵn giá.</p>
                    </div>
                    <button type="button" class="dispatch-chip-button" id="addPartItem">
                      <span class="material-symbols-outlined">add</span>
                      Thêm dòng thủ công
                    </button>
                  </div>
                  <div class="dispatch-part-catalog">
                    <div class="dispatch-part-catalog__toolbar">
                      <label class="dispatch-part-catalog__field" for="partCatalogSearch">
                        <span>Tìm trong danh mục linh kiện</span>
                        <input type="search" class="dispatch-input" id="partCatalogSearch" placeholder="Ví dụ: bo nóng Samsung, quạt dàn lạnh...">
                      </label>
                      <button type="button" class="dispatch-chip-button dispatch-chip-button--warm" id="addSelectedParts" disabled>
                        <span class="material-symbols-outlined">playlist_add</span>
                        Thêm linh kiện đã chọn
                      </button>
                    </div>
                    <div class="dispatch-part-catalog__status" id="partCatalogStatus">Mở đơn để tải danh mục linh kiện đúng theo dịch vụ của đơn.</div>
                    <div class="dispatch-part-catalog__results" id="partCatalogResults"></div>
                  </div>
                  <div class="dispatch-line-items" id="partItemsContainer"></div>
                </div>
              </div>

              <div class="dispatch-cost-meta-grid">
                <div class="dispatch-field dispatch-field--compact" id="truckFeeContainer" style="display:none;">
                  <label for="inputTienThueXe">Phí thuê xe chở</label>
                  <div class="dispatch-input-wrap">
                    <input type="number" class="dispatch-input" id="inputTienThueXe" min="0" value="0" placeholder="0">
                    <span class="dispatch-input-suffix">VND</span>
                  </div>
                </div>

                <div class="dispatch-field dispatch-field--compact">
                  <label>Phí đi lại cố định</label>
                  <div class="dispatch-readonly dispatch-readonly--accent">
                    <div class="dispatch-readonly__value">
                      <strong id="displayPhiDiLai">0 đ</strong>
                      <span class="dispatch-booking-meta-chip" data-state="travel">Tự tính</span>
                    </div>
                    <div id="costDistanceHint" class="mt-2">Hệ thống tính tự động theo quãng đường phục vụ.</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="dispatch-panel dispatch-panel--summary">
              <div class="dispatch-summary-header">
                <div>
                  <span class="dispatch-summary-kicker">Bản xem trước gửi khách</span>
                  <h3 class="dispatch-panel__title mb-0">Tóm tắt chi phí</h3>
                </div>
                <span class="dispatch-summary-status" id="costDraftState" data-state="attention">Cần nhập tiền công</span>
              </div>

              <p class="dispatch-summary-lede">Khối này là phần khách sẽ đọc nhanh nhất. Càng rõ ràng, tỷ lệ chấp nhận thanh toán càng cao.</p>

              <div class="dispatch-summary-list">
                <div class="dispatch-summary-row">
                  <span>Tổng tiền công</span>
                  <strong id="laborSubtotal">0 đ</strong>
                </div>
                <div class="dispatch-summary-row">
                  <span>Tổng linh kiện</span>
                  <strong id="partsSubtotal">0 đ</strong>
                </div>
                <div class="dispatch-summary-row">
                  <span>Phí đi lại cố định</span>
                  <strong id="travelSubtotal">0 đ</strong>
                </div>
                <div class="dispatch-summary-row" id="truckSummaryRow" style="display:none;">
                  <span>Phí xe chở</span>
                  <strong id="truckSubtotal">0 đ</strong>
                </div>
              </div>

              <div class="dispatch-note-card">
                <label class="dispatch-note-card__label" for="inputGhiChuLinhKien">Ghi chú hiển thị cho khách</label>
                <p class="dispatch-note-card__hint">Viết ngắn gọn phần đã xử lý, linh kiện đã thay hoặc lưu ý sử dụng sau sửa chữa.</p>
                <textarea class="dispatch-textarea dispatch-textarea--note" id="inputGhiChuLinhKien" placeholder="Ví dụ: Đã thay bo nguồn mới, chạy thử ổn định 20 phút và hướng dẫn khách cách sử dụng an toàn."></textarea>
              </div>

              <div class="dispatch-summary-tile">
                <div class="dispatch-summary-tile__icon">
                  <span class="material-symbols-outlined">calculate</span>
                </div>
                <div class="dispatch-summary-tile__copy">
                  <span class="dispatch-summary-tile__label">Tổng cộng tất cả chi phí</span>
                  <span class="dispatch-summary-tile__hint" id="costSummaryHint">Đã cộng tiền công, linh kiện, phí đi lại và phí xe chở nếu có.</span>
                </div>
                <div class="dispatch-summary-tile__value" id="costEstimateTotal">0 đ</div>
              </div>

              <div class="dispatch-summary-footnote">
                Sau khi lưu, bạn có thể quay lại đơn này và gửi yêu cầu thanh toán cho khách ngay từ cùng màn hình.
              </div>
            </div>
          </div>

          <div class="dispatch-modal__footer dispatch-modal__footer--pricing">
            <button type="button" class="dispatch-btn dispatch-btn--ghost" data-bs-dismiss="modal">Hủy</button>
            <button type="submit" class="dispatch-btn dispatch-btn--primary">
              <span class="material-symbols-outlined">save</span>
              Lưu chi phí
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade dispatch-modal" id="modalViewDetails" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content dispatch-modal__content">
      <div class="dispatch-modal__header d-flex justify-content-between align-items-start gap-3">
        <div>
          <div class="dispatch-modal__eyebrow">Booking Intelligence</div>
          <h2 class="dispatch-modal__title">Chi tiết đơn sửa chữa</h2>
          <p class="dispatch-modal__subtitle">Tổng hợp đầy đủ thông tin khách hàng, yêu cầu sửa chữa, hình ảnh ban đầu và breakdown chi phí của đơn.</p>
        </div>
        <button type="button" class="dispatch-modal__close" data-bs-dismiss="modal" aria-label="Đóng">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <div class="dispatch-modal__body">
        <div id="bookingDetailContent">
          <div class="dispatch-empty">
            <span class="material-symbols-outlined">hourglass_top</span>
            <h3>Đang tải chi tiết đơn</h3>
            <p>Vui lòng chờ trong giây lát.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade dispatch-modal" id="modalRouteGuide" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content dispatch-modal__content">
      <div class="dispatch-modal__header d-flex justify-content-between align-items-start gap-3">
        <div>
          <div class="dispatch-modal__eyebrow">Navigation Assist</div>
          <h2 class="dispatch-modal__title">Đường đi tới khách hàng</h2>
          <p class="dispatch-modal__subtitle">Theo dõi GPS hiện tại, cập nhật quãng đường còn lại và mở chỉ đường lái xe tới địa chỉ của khách hàng.</p>
        </div>
        <button type="button" class="dispatch-modal__close" data-bs-dismiss="modal" aria-label="Đóng">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <div class="dispatch-modal__body">
        <div class="dispatch-route-grid">
          <section>
            <div class="dispatch-route-map-shell">
              <div
                id="routeMapCanvas"
                class="dispatch-route-map-canvas"
                aria-label="Bản đồ chỉ đường tới khách hàng"
              ></div>

              <div class="dispatch-route-map-fallback" id="routeMapFallback" hidden>
                <span class="material-symbols-outlined">route</span>
                <strong id="routeMapFallbackTitle">Đang chờ vị trí hiện tại</strong>
                <p id="routeMapFallbackText">Cho phép truy cập GPS để hệ thống hiển thị bản đồ chỉ đường từ vị trí của bạn tới nhà khách hàng.</p>
              </div>
            </div>

            <div class="dispatch-route-toolbar">
              <button type="button" class="dispatch-btn dispatch-btn--secondary" id="routeRefreshLocationBtn">
                <span class="material-symbols-outlined">my_location</span>
                Làm mới vị trí
              </button>
              <a href="#" target="_blank" rel="noopener" class="dispatch-btn dispatch-btn--primary" id="routeOpenExternalBtn">
                <span class="material-symbols-outlined">navigation</span>
                Mở bản đồ ngoài
              </a>
            </div>
          </section>

          <aside>
            <div class="dispatch-route-card">
              <div class="dispatch-route-card__eyebrow">Điểm đến</div>
              <h3 class="dispatch-route-card__title" id="routeServiceName">Đơn sửa chữa</h3>
              <p class="dispatch-route-card__address" id="routeDestinationAddress">Đang tải địa chỉ khách hàng...</p>
              <div class="dispatch-route-coords" id="routeDestinationCoords">Tọa độ đích sẽ hiển thị tại đây.</div>
            </div>

            <div class="dispatch-route-stats">
              <div class="dispatch-route-stat">
                <span class="dispatch-route-stat__label">Quãng đường còn lại</span>
                <strong class="dispatch-route-stat__value" id="routeDistanceValue">--</strong>
                <span class="dispatch-route-stat__hint" id="routeDistanceHint">Cho phép GPS để hệ thống tính khoảng cách còn lại.</span>
              </div>

              <div class="dispatch-route-stat">
                <span class="dispatch-route-stat__label">ETA dự kiến</span>
                <strong class="dispatch-route-stat__value" id="routeEtaValue">--</strong>
                <span class="dispatch-route-stat__hint" id="routeEtaHint">Thời gian đến nơi sẽ hiển thị theo dữ liệu tuyến đường thực.</span>
              </div>

              <div class="dispatch-route-stat">
                <span class="dispatch-route-stat__label">Vị trí hiện tại</span>
                <strong class="dispatch-route-stat__value dispatch-route-stat__value--small" id="routeCurrentCoords">Đang chờ vị trí hiện tại...</strong>
                <span class="dispatch-route-stat__hint" id="routeLastUpdated">Chưa có lần cập nhật nào.</span>
              </div>
            </div>

            <div class="dispatch-route-status" id="routeTrackingStatus" data-tone="info">
              Mở modal để bắt đầu theo dõi vị trí realtime.
            </div>

            <div class="dispatch-inline-note mt-3" id="routeMapStatus">
              Bản đồ chỉ đường sẽ được tải sau khi hệ thống nhận được vị trí hiện tại của bạn.
            </div>

            <div class="dispatch-route-card dispatch-route-card--subtle mt-3">
              <div class="dispatch-route-card__eyebrow">Mã đơn đang dẫn đường</div>
              <div class="dispatch-route-code" id="routeBookingCode">#0000</div>
            </div>
          </aside>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade dispatch-modal" id="modalCompleteBooking" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content dispatch-modal__content">
      <div class="dispatch-modal__header d-flex justify-content-between align-items-start gap-3">
        <div>
          <div class="dispatch-modal__eyebrow">Completion Flow</div>
          <h2 class="dispatch-modal__title">Hoàn thành sửa chữa</h2>
          <p class="dispatch-modal__subtitle">Xác nhận lại quy trình, chọn phương thức thanh toán và tải lên minh chứng hoàn thành trước khi gửi yêu cầu cho khách.</p>
        </div>
        <button type="button" class="dispatch-modal__close" data-bs-dismiss="modal" aria-label="Đóng">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <div class="dispatch-modal__body">
        <form id="formCompleteBooking">
          <input type="hidden" id="completeBookingId">

          <div class="dispatch-booking-hero">
            <div class="dispatch-booking-hero__row">
              <div>
                <p class="dispatch-booking-hero__customer" id="completeCustomerName">Khách hàng</p>
                <p class="dispatch-booking-hero__service" id="completeServiceName">Dịch vụ sửa chữa</p>
              </div>
              <div class="text-end">
                <span class="dispatch-pill dispatch-pill--status dispatch-pill--payment" id="completeStatusBadge">Sẵn sàng báo hoàn thành</span>
                <div class="dispatch-summary-box__value mt-2" id="completeBookingTotal">0 đ</div>
              </div>
            </div>
          </div>

          <div class="dispatch-complete-grid">
            <div class="dispatch-panel">
              <h3 class="dispatch-panel__title">Bước 1. Kiểm tra quy trình</h3>

              <div class="dispatch-workflow">
                <div class="dispatch-workflow__list" id="completeWorkflowList">
                  <div class="dispatch-workflow__item is-done">
                    <span class="dispatch-workflow__icon material-symbols-outlined">check</span>
                    <span>Đã bắt đầu sửa</span>
                  </div>
                  <div class="dispatch-workflow__item is-done">
                    <span class="dispatch-workflow__icon material-symbols-outlined">check</span>
                    <span>Đã cập nhật chi phí</span>
                  </div>
                  <div class="dispatch-workflow__item is-current">
                    <span class="dispatch-workflow__icon material-symbols-outlined">priority_high</span>
                    <span>Chuẩn bị gửi yêu cầu thanh toán</span>
                  </div>
                </div>
              </div>

              <div class="dispatch-alert" id="completePricingAlert" style="display:none;">
                <span class="material-symbols-outlined">warning</span>
                <span>Bạn cần cập nhật chi phí trước khi báo hoàn thành đơn.</span>
              </div>

              <div class="dispatch-inline-note mt-4">
                Hãy tải lên hình ảnh rõ ràng về linh kiện đã thay hoặc thiết bị đã vận hành ổn định để khách dễ xác nhận hơn.
              </div>
            </div>

            <div class="dispatch-panel">
              <h3 class="dispatch-panel__title">Bước 2. Phương thức thanh toán</h3>

              <div class="dispatch-radio-grid">
                <label class="dispatch-pay-option" id="payOptionCod">
                  <input type="radio" name="phuong_thuc_thanh_toan" id="pay_cod" value="cod" checked>
                  <span class="dispatch-pay-option__card">
                    <span class="material-symbols-outlined">payments</span>
                    <span>Tiền mặt</span>
                  </span>
                </label>

                <label class="dispatch-pay-option" id="payOptionTransfer">
                  <input type="radio" name="phuong_thuc_thanh_toan" id="pay_transfer" value="transfer">
                  <span class="dispatch-pay-option__card">
                    <span class="material-symbols-outlined">account_balance</span>
                    <span>Chuyển khoản test</span>
                  </span>
                </label>
              </div>

              <h3 class="dispatch-panel__title mt-4">Bước 3. Minh chứng hoàn thành</h3>

              <div class="dispatch-upload-grid">
                <div class="dispatch-upload-area">
                  <h4 class="dispatch-upload-area__title">Hình ảnh sửa chữa</h4>
                  <p class="dispatch-upload-area__hint">Tối đa 5 ảnh. Ưu tiên ảnh toàn cảnh, ảnh linh kiện mới và ảnh máy hoạt động ổn định.</p>
                  <input type="file" class="dispatch-file-input" id="inputHinhAnhKetQua" name="hinh_anh_ket_qua[]" multiple accept="image/*">
                  <div id="imageUploadPreview" class="dispatch-preview-grid"></div>
                </div>

                <div class="dispatch-upload-area">
                  <h4 class="dispatch-upload-area__title">Video vận hành</h4>
                  <p class="dispatch-upload-area__hint">Tùy chọn. Tải lên video test nhanh sau sửa chữa để khách dễ đối chiếu.</p>
                  <input type="file" class="dispatch-file-input" id="inputVideoKetQua" name="video_ket_qua" accept="video/*">
                  <div id="videoUploadPreview" class="dispatch-preview-grid"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="dispatch-modal__footer">
            <button type="button" class="dispatch-btn dispatch-btn--ghost" data-bs-dismiss="modal">Hủy</button>
            <button type="submit" class="dispatch-btn dispatch-btn--primary" id="btnSubmitCompleteForm">
              <span class="material-symbols-outlined">task_alt</span>
              Xác nhận hoàn thành
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script type="module">
import { callApi, getCurrentUser, showToast } from "{{ asset('assets/js/api.js') }}";

const baseUrl = '{{ url('/') }}';
const routeWorkerMarkerImage = @json(asset('assets/images/shipper.png'));
const user = getCurrentUser();

if (!user || !['worker', 'admin'].includes(user.role)) {
  window.location.href = `${baseUrl}/login?role=worker`;
}

window.currentStatus = 'inprogress';
window.allBookings = [];

const bookingsContainer = document.getElementById('bookingsContainer');
const detailContent = document.getElementById('bookingDetailContent');
const costForm = document.getElementById('formUpdateCosts');
const completeForm = document.getElementById('formCompleteBooking');

const costModalEl = document.getElementById('modalCosts');
const costModalInstance = costModalEl && typeof bootstrap !== 'undefined'
  ? new bootstrap.Modal(costModalEl)
  : null;

const detailModalEl = document.getElementById('modalViewDetails');
const detailModalInstance = detailModalEl && typeof bootstrap !== 'undefined'
  ? new bootstrap.Modal(detailModalEl)
  : null;

const routeModalEl = document.getElementById('modalRouteGuide');
const routeModalInstance = routeModalEl && typeof bootstrap !== 'undefined'
  ? new bootstrap.Modal(routeModalEl)
  : null;

const completeModalEl = document.getElementById('modalCompleteBooking');
const completeModalInstance = completeModalEl && typeof bootstrap !== 'undefined'
  ? new bootstrap.Modal(completeModalEl)
  : null;

const costBookingId = document.getElementById('costBookingId');
const inputTienThueXe = document.getElementById('inputTienThueXe');
const inputGhiChuLinhKien = document.getElementById('inputGhiChuLinhKien');
const laborItemsContainer = document.getElementById('laborItemsContainer');
const partItemsContainer = document.getElementById('partItemsContainer');
const addLaborItemButton = document.getElementById('addLaborItem');
const addPartItemButton = document.getElementById('addPartItem');
const partCatalogSearch = document.getElementById('partCatalogSearch');
const partCatalogResults = document.getElementById('partCatalogResults');
const partCatalogStatus = document.getElementById('partCatalogStatus');
const addSelectedPartsButton = document.getElementById('addSelectedParts');
const truckFeeContainer = document.getElementById('truckFeeContainer');
const displayPhiDiLai = document.getElementById('displayPhiDiLai');
const costEstimateTotal = document.getElementById('costEstimateTotal');
const laborSubtotal = document.getElementById('laborSubtotal');
const partsSubtotal = document.getElementById('partsSubtotal');
const travelSubtotal = document.getElementById('travelSubtotal');
const truckSummaryRow = document.getElementById('truckSummaryRow');
const truckSubtotal = document.getElementById('truckSubtotal');
const costCustomerName = document.getElementById('costCustomerName');
const costServiceName = document.getElementById('costServiceName');
const costDistanceHint = document.getElementById('costDistanceHint');
const costBookingReference = document.getElementById('costBookingReference');
const costServiceModeBadge = document.getElementById('costServiceModeBadge');
const costTruckBadge = document.getElementById('costTruckBadge');
const costDistanceBadge = document.getElementById('costDistanceBadge');
const laborCountBadge = document.getElementById('laborCountBadge');
const partCountBadge = document.getElementById('partCountBadge');
const costDraftState = document.getElementById('costDraftState');
const costSummaryHint = document.getElementById('costSummaryHint');

const completeBookingId = document.getElementById('completeBookingId');
const completeCustomerName = document.getElementById('completeCustomerName');
const completeServiceName = document.getElementById('completeServiceName');
const completeBookingTotal = document.getElementById('completeBookingTotal');
const completePricingAlert = document.getElementById('completePricingAlert');
const completeWorkflowList = document.getElementById('completeWorkflowList');
const imageUploadPreview = document.getElementById('imageUploadPreview');
const videoUploadPreview = document.getElementById('videoUploadPreview');
const inputHinhAnhKetQua = document.getElementById('inputHinhAnhKetQua');
const inputVideoKetQua = document.getElementById('inputVideoKetQua');
const btnSubmitCompleteForm = document.getElementById('btnSubmitCompleteForm');
const routeMapCanvas = document.getElementById('routeMapCanvas');
const routeMapFallback = document.getElementById('routeMapFallback');
const routeMapFallbackTitle = document.getElementById('routeMapFallbackTitle');
const routeMapFallbackText = document.getElementById('routeMapFallbackText');
const routeRefreshLocationBtn = document.getElementById('routeRefreshLocationBtn');
const routeOpenExternalBtn = document.getElementById('routeOpenExternalBtn');
const routeServiceName = document.getElementById('routeServiceName');
const routeDestinationAddress = document.getElementById('routeDestinationAddress');
const routeDestinationCoords = document.getElementById('routeDestinationCoords');
const routeDistanceValue = document.getElementById('routeDistanceValue');
const routeDistanceHint = document.getElementById('routeDistanceHint');
const routeEtaValue = document.getElementById('routeEtaValue');
const routeEtaHint = document.getElementById('routeEtaHint');
const routeCurrentCoords = document.getElementById('routeCurrentCoords');
const routeLastUpdated = document.getElementById('routeLastUpdated');
const routeTrackingStatus = document.getElementById('routeTrackingStatus');
const routeMapStatus = document.getElementById('routeMapStatus');
const routeBookingCode = document.getElementById('routeBookingCode');

let currentCostBooking = null;
let repairTimers = {};
const partCatalogState = {
  items: [],
  cache: new Map(),
  selectedIds: new Set(),
};
const routeGuideState = {
  bookingId: null,
  watchId: null,
  currentOrigin: null,
  lastRouteOrigin: null,
  lastRouteUpdateAt: 0,
  pendingRouteRequestId: 0,
  map: null,
  routeLine: null,
  originMarker: null,
  destinationMarker: null,
};

const escapeHtml = (value = '') => String(value ?? '').replace(/[&<>"']/g, (char) => ({
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#039;',
}[char]));

const nl2brSafe = (value = '') => escapeHtml(value).replace(/\n/g, '<br>');

const formatMoney = (value) => new Intl.NumberFormat('vi-VN', {
  style: 'currency',
  currency: 'VND',
  maximumFractionDigits: 0,
}).format(Number(value || 0));

const formatCount = (value) => String(Number(value || 0)).padStart(2, '0');
const getNumeric = (value) => Number(value || 0);
const getTodayKey = () => new Date().toISOString().slice(0, 10);
const formatDateTimeLabel = (value, fallback = 'Chưa cập nhật') => {
  if (!value) {
    return fallback;
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return fallback;
  }

  return parsed.toLocaleString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const statusFilters = {
  pending: (booking) => booking.trang_thai === 'cho_xac_nhan',
  upcoming: (booking) => booking.trang_thai === 'da_xac_nhan',
  inprogress: (booking) => booking.trang_thai === 'dang_lam',
  payment: (booking) => ['cho_hoan_thanh', 'cho_thanh_toan'].includes(booking.trang_thai),
  done: (booking) => booking.trang_thai === 'da_xong',
  cancelled: (booking) => booking.trang_thai === 'da_huy',
  all: () => true,
};

const statusToneMap = {
  cho_xac_nhan: 'pending',
  cho_hoan_thanh: 'payment',
  da_xac_nhan: 'upcoming',
  dang_lam: 'inprogress',
  cho_thanh_toan: 'payment',
  da_xong: 'done',
  da_huy: 'cancelled',
};

const statusLabelMap = {
  cho_xac_nhan: 'Chờ xác nhận',
  cho_hoan_thanh: 'Chờ xác nhận COD',
  da_xac_nhan: 'Sắp tới',
  dang_lam: 'Đang sửa',
  cho_thanh_toan: 'Chờ thanh toán test',
  da_xong: 'Hoàn thành',
  da_huy: 'Đã hủy',
};

const serviceBadgePresets = [
  { keywords: ['máy lạnh', 'điều hòa'], label: 'AIR CONDITIONING' },
  { keywords: ['tủ lạnh', 'tủ đông'], label: 'COOLING SERVICE' },
  { keywords: ['máy giặt'], label: 'LAUNDRY CARE' },
  { keywords: ['tivi', 'tv'], label: 'ELECTRONIC REPAIR' },
  { keywords: ['bồn cầu', 'vòi', 'ống nước'], label: 'PLUMBING' },
  { keywords: ['điện', 'ổ cắm', 'cầu dao'], label: 'ELECTRIC SERVICE' },
];

const getBookingServices = (booking) => Array.isArray(booking?.dich_vus) ? booking.dich_vus : [];
const getBookingPaymentMethod = (booking) => booking?.phuong_thuc_thanh_toan === 'transfer' ? 'transfer' : 'cod';
const isCashPaymentBooking = (booking) => getBookingPaymentMethod(booking) === 'cod';

const getBookingServiceNames = (booking) => {
  const services = getBookingServices(booking)
    .map((service) => service?.ten_dich_vu)
    .filter(Boolean);

  return services.length > 0 ? services.join(', ') : 'Dịch vụ sửa chữa';
};

const getServiceBadge = (booking) => {
  const haystack = getBookingServiceNames(booking).toLowerCase();
  const preset = serviceBadgePresets.find((item) => item.keywords.some((keyword) => haystack.includes(keyword)));
  return preset ? preset.label : 'HOME SERVICE';
};

const getCustomerName = (booking) => booking?.khach_hang?.name || 'Khách hàng';
const getPhoneNumber = (booking) => booking?.khach_hang?.phone || '';
const getPhoneHref = (booking) => `tel:${getPhoneNumber(booking).replace(/[^\d+]/g, '')}`;
const getAddress = (booking) => booking?.dia_chi || 'Chưa có địa chỉ';
const getCoordinateValue = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
};
const isValidCoordinatePair = (lat, lng) => Number.isFinite(lat)
  && Number.isFinite(lng)
  && Math.abs(lat) <= 90
  && Math.abs(lng) <= 180
  && !(lat === 0 && lng === 0);
const getBookingDestination = (booking) => {
  const lat = getCoordinateValue(booking?.vi_do);
  const lng = getCoordinateValue(booking?.kinh_do);

  return isValidCoordinatePair(lat, lng) ? { lat, lng } : null;
};
const canOpenRouteGuide = (booking) => booking?.loai_dat_lich === 'at_home'
  && ['da_xac_nhan', 'dang_lam', 'cho_hoan_thanh', 'cho_thanh_toan'].includes(booking?.trang_thai)
  && Boolean(getBookingDestination(booking));
const formatCoordinatePair = (point) => point && isValidCoordinatePair(point.lat, point.lng)
  ? `${point.lat.toFixed(6)}, ${point.lng.toFixed(6)}`
  : 'Chưa có tọa độ';
const toRadians = (value) => (value * Math.PI) / 180;
const calculateHaversineKm = (fromLat, fromLng, toLat, toLng) => {
  const earthRadiusKm = 6371;
  const dLat = toRadians(toLat - fromLat);
  const dLng = toRadians(toLng - fromLng);
  const a = Math.sin(dLat / 2) ** 2
    + Math.cos(toRadians(fromLat)) * Math.cos(toRadians(toLat)) * Math.sin(dLng / 2) ** 2;
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return earthRadiusKm * c;
};
const formatDistanceLabel = (distanceKm) => {
  if (!Number.isFinite(distanceKm)) {
    return '--';
  }

  if (distanceKm < 1) {
    return `${Math.max(1, Math.round(distanceKm * 1000))} m`;
  }

  return `${distanceKm < 10 ? distanceKm.toFixed(1) : distanceKm.toFixed(0)} km`;
};
const formatLiveUpdatedAt = (value = new Date()) => new Date(value).toLocaleTimeString('vi-VN', {
  hour: '2-digit',
  minute: '2-digit',
  second: '2-digit',
});
const buildExternalDirectionsUrl = (destination, origin = null) => {
  if (origin && destination && isValidCoordinatePair(origin.lat, origin.lng) && isValidCoordinatePair(destination.lat, destination.lng)) {
    const url = new URL('https://www.openstreetmap.org/directions');
    url.searchParams.set('engine', 'fossgis_osrm_car');
    url.searchParams.set('route', `${origin.lat},${origin.lng};${destination.lat},${destination.lng}`);
    return url.toString();
  }

  const url = new URL('https://www.openstreetmap.org/');
  if (destination && isValidCoordinatePair(destination.lat, destination.lng)) {
    url.searchParams.set('mlat', `${destination.lat}`);
    url.searchParams.set('mlon', `${destination.lng}`);
    url.hash = `map=16/${destination.lat}/${destination.lng}`;
  }
  return url.toString();
};
const formatEtaLabel = (seconds) => {
  const totalSeconds = Number(seconds);
  if (!Number.isFinite(totalSeconds) || totalSeconds <= 0) {
    return '--';
  }

  const totalMinutes = Math.max(1, Math.round(totalSeconds / 60));
  if (totalMinutes < 60) {
    return `${totalMinutes} phút`;
  }

  const hours = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;

  if (minutes === 0) {
    return `${hours} giờ`;
  }

  return `${hours} giờ ${minutes} phút`;
};
const shouldRefreshRouteMetrics = (origin) => {
  if (!routeGuideState.lastRouteOrigin) {
    return true;
  }

  const movedKm = calculateHaversineKm(
    routeGuideState.lastRouteOrigin.lat,
    routeGuideState.lastRouteOrigin.lng,
    origin.lat,
    origin.lng,
  );

  return movedKm >= 0.12 || (Date.now() - routeGuideState.lastRouteUpdateAt) >= 30000;
};
const hasUpdatedPricing = (booking) => Boolean(booking?.gia_da_cap_nhat);
const getStoredCostItems = (booking, key) => Array.isArray(booking?.[key]) ? booking[key].filter(Boolean) : [];

const getBookingTotal = (booking) => {
  const explicitTotal = getNumeric(booking?.tong_tien);
  if (explicitTotal > 0) {
    return explicitTotal;
  }

  const laborItems = getStoredCostItems(booking, 'chi_tiet_tien_cong');
  const partItems = getStoredCostItems(booking, 'chi_tiet_linh_kien');
  const laborTotal = laborItems.length
    ? laborItems.reduce((total, item) => total + getNumeric(item?.so_tien), 0)
    : getNumeric(booking?.tien_cong);
  const partTotal = partItems.length
    ? partItems.reduce((total, item) => total + getNumeric(item?.so_tien), 0)
    : getNumeric(booking?.phi_linh_kien);

  return getNumeric(booking?.phi_di_lai)
    + laborTotal
    + partTotal
    + getNumeric(booking?.tien_thue_xe);
};

const getLegacyFirstLine = (value = '', fallback = 'Linh kiện thay thế') => {
  const firstLine = String(value || '').split(/\r\n|\r|\n/).map((line) => line.trim()).find(Boolean);
  return firstLine || fallback;
};

const getBookingServiceIds = (booking) => {
  const relationIds = Array.isArray(booking?.dich_vus)
    ? booking.dich_vus.map((service) => getNumeric(service?.id)).filter((id) => id > 0)
    : [];

  if (relationIds.length) {
    return Array.from(new Set(relationIds));
  }

  const legacyId = getNumeric(booking?.dich_vu_id);
  return legacyId > 0 ? [legacyId] : [];
};

const getBookingLaborItems = (booking) => {
  const items = getStoredCostItems(booking, 'chi_tiet_tien_cong');
  if (items.length) {
    return items;
  }

  if (getNumeric(booking?.tien_cong) > 0) {
    return [{
      noi_dung: getBookingServiceNames(booking),
      so_tien: getNumeric(booking?.tien_cong),
    }];
  }

  return [];
};

const getBookingPartItems = (booking) => {
  const items = getStoredCostItems(booking, 'chi_tiet_linh_kien');
  if (items.length) {
    return items;
  }

  if (getNumeric(booking?.phi_linh_kien) > 0 || String(booking?.ghi_chu_linh_kien || '').trim() !== '') {
    return [{
      noi_dung: getLegacyFirstLine(booking?.ghi_chu_linh_kien, 'Linh kiện thay thế'),
      so_tien: getNumeric(booking?.phi_linh_kien),
      bao_hanh_thang: null,
    }];
  }

  return [];
};

const formatWarrantyText = (months) => {
  const value = Number(months);
  if (!Number.isFinite(value) || value <= 0) {
    return 'Không ghi bảo hành';
  }

  return `Bảo hành ${value} tháng`;
};

const parseWarrantyDate = (value) => {
  if (!value) {
    return null;
  }

  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? null : date;
};

const addWarrantyMonths = (value, months) => {
  const date = parseWarrantyDate(value);
  const monthCount = Number(months);
  if (!date || !Number.isFinite(monthCount) || monthCount <= 0) {
    return null;
  }

  const result = new Date(date.getTime());
  const originalDay = result.getDate();
  result.setDate(1);
  result.setMonth(result.getMonth() + Math.trunc(monthCount));

  const lastDay = new Date(result.getFullYear(), result.getMonth() + 1, 0).getDate();
  result.setDate(Math.min(originalDay, lastDay));

  return result;
};

const hasUsedWarranty = (item) => item?.bao_hanh_da_su_dung === true || item?.da_dung_bao_hanh === true || item?.used_warranty === true;

const formatWarrantyRemaining = (endDate, now = new Date()) => {
  const remainingDays = Math.max(0, Math.ceil((endDate.getTime() - now.getTime()) / (24 * 60 * 60 * 1000)));
  if (remainingDays <= 1) {
    return 'còn 1 ngày';
  }

  if (remainingDays < 30) {
    return `còn ${remainingDays} ngày`;
  }

  const months = Math.floor(remainingDays / 30);
  const days = remainingDays % 30;
  return days === 0 ? `còn ${months} tháng` : `còn ${months} tháng ${days} ngày`;
};

const getWarrantyStatusMeta = (booking, item) => {
  const warrantyLabel = formatWarrantyText(item?.bao_hanh_thang);
  const warrantyMonths = Number(item?.bao_hanh_thang);
  const completedAt = parseWarrantyDate(booking?.thoi_gian_hoan_thanh);
  const activatedAtLabel = completedAt ? formatDateTimeLabel(completedAt, '') : '';

  if (hasUsedWarranty(item)) {
    return {
      label: 'Hết bảo hành',
      detail: activatedAtLabel ? `Kích hoạt từ ${activatedAtLabel}. Linh kiện đã sử dụng quyền bảo hành.` : 'Linh kiện đã sử dụng quyền bảo hành.',
      tone: 'is-used',
      warrantyLabel,
      canConfirm: false,
    };
  }

  if (!Number.isFinite(warrantyMonths) || warrantyMonths <= 0) {
    return {
      label: 'Không ghi bảo hành',
      detail: 'Linh kiện này không có thời hạn bảo hành.',
      tone: 'is-neutral',
      warrantyLabel,
      canConfirm: false,
    };
  }

  if (!completedAt) {
    return {
      label: 'Chưa bắt đầu bảo hành',
      detail: 'Bảo hành được tính từ thời gian hoàn thành đơn.',
      tone: 'is-neutral',
      warrantyLabel,
      canConfirm: false,
    };
  }

  const warrantyEndDate = addWarrantyMonths(completedAt, warrantyMonths);
  if (!warrantyEndDate) {
    return {
      label: 'Không ghi bảo hành',
      detail: 'Không xác định được thời hạn bảo hành.',
      tone: 'is-neutral',
      warrantyLabel,
      canConfirm: false,
    };
  }

  const now = new Date();
  if (now.getTime() <= warrantyEndDate.getTime()) {
    return {
      label: 'Còn bảo hành',
      detail: `Kích hoạt từ ${activatedAtLabel} • hiệu lực đến ${warrantyEndDate.toLocaleDateString('vi-VN')} • ${formatWarrantyRemaining(warrantyEndDate, now)}.`,
      tone: 'is-active',
      warrantyLabel,
      canConfirm: true,
    };
  }

  return {
    label: 'Hết hạn bảo hành',
    detail: `Kích hoạt từ ${activatedAtLabel} • đã hết hạn từ ${warrantyEndDate.toLocaleDateString('vi-VN')}.`,
    tone: 'is-expired',
    warrantyLabel,
    canConfirm: false,
  };
};

const canConfirmWarranty = (booking, item) => Boolean(Number(booking?.tho_id || 0) === Number(user?.id || 0) && getWarrantyStatusMeta(booking, item).canConfirm);

const buildCostItemRowMarkup = (type, item = {}) => {
  const description = escapeHtml(item?.noi_dung || '');
  const amount = getNumeric(item?.so_tien);
  const amountValue = amount > 0 ? amount : '';
  const isPart = type === 'part';
  const catalogPartId = isPart ? getNumeric(item?.linh_kien_id) : 0;
  const serviceId = isPart ? getNumeric(item?.dich_vu_id) : 0;
  const image = isPart ? escapeHtml(item?.hinh_anh || '') : '';
  const isCatalogItem = isPart && catalogPartId > 0;
  const warrantyValue = isPart && item?.bao_hanh_thang !== null && item?.bao_hanh_thang !== undefined
    ? getNumeric(item.bao_hanh_thang)
    : '';
  return `
    <div class="dispatch-line-item" data-line-type="${type}" data-catalog-part-id="${catalogPartId || ''}">
      ${isCatalogItem ? `
        <div class="dispatch-line-item__tag">
          <span class="material-symbols-outlined">inventory_2</span>
          Giá lấy từ danh mục linh kiện
        </div>
      ` : ''}
      ${isPart ? `
        <input type="hidden" class="js-line-part-id" value="${catalogPartId || ''}">
        <input type="hidden" class="js-line-service-id" value="${serviceId || ''}">
        <input type="hidden" class="js-line-image" value="${image}">
      ` : ''}
      <div class="dispatch-line-item__grid ${isPart ? 'is-parts' : ''}">
        <label class="dispatch-line-item__field">
          <span>${isPart ? 'Linh kiện / vật tư' : 'Nội dung công việc'}</span>
          <input type="text" class="dispatch-input js-line-description" value="${description}" placeholder="${isPart ? 'Ví dụ: Thay lồng giặt' : 'Ví dụ: Tiền công thay lồng giặt'}" ${isCatalogItem ? 'readonly' : ''}>
        </label>
        <label class="dispatch-line-item__field">
          <span>Số tiền</span>
          <div class="dispatch-input-wrap">
            <input type="number" class="dispatch-input js-line-amount" min="0" step="1000" value="${amountValue}" placeholder="50000" ${isCatalogItem ? 'readonly' : ''}>
            <span class="dispatch-input-suffix">VND</span>
          </div>
        </label>
        ${isPart ? `
          <label class="dispatch-line-item__field">
            <span>Bảo hành</span>
            <div class="dispatch-input-wrap">
              <input type="number" class="dispatch-input js-line-warranty" min="0" step="1" value="${warrantyValue}" placeholder="0">
              <span class="dispatch-input-suffix">tháng</span>
            </div>
          </label>
        ` : ''}
        <button type="button" class="dispatch-line-item__remove" aria-label="Xóa dòng">
          <span class="material-symbols-outlined">delete</span>
        </button>
      </div>
    </div>
  `;
};

const populateCostItemRows = (container, type, items = []) => {
  if (!container) {
    return;
  }

  const normalizedItems = items.length
    ? items
    : (type === 'labor' ? [{}] : []);
  container.innerHTML = normalizedItems.map((item) => buildCostItemRowMarkup(type, item)).join('');
};

const appendCostItemRow = (container, type, item = {}) => {
  container?.insertAdjacentHTML('beforeend', buildCostItemRowMarkup(type, item));
};

const ensureMinimumCostRows = (container, type) => {
  if (type === 'labor' && !container?.querySelector('.dispatch-line-item')) {
    appendCostItemRow(container, type);
  }
};

const sumDraftLineAmounts = (container) => Array.from(container?.querySelectorAll('.dispatch-line-item') || [])
  .reduce((total, row) => total + getNumeric(row.querySelector('.js-line-amount')?.value), 0);

const countDraftLineRows = (container) => Array.from(container?.querySelectorAll('.dispatch-line-item') || []).length;

const collectCostItems = (container, type) => {
  let hasIncomplete = false;
  const items = [];

  Array.from(container?.querySelectorAll('.dispatch-line-item') || []).forEach((row) => {
    const description = row.querySelector('.js-line-description')?.value.trim() || '';
    const amountRaw = row.querySelector('.js-line-amount')?.value || '';
    const amount = getNumeric(amountRaw);
    const warrantyRaw = row.querySelector('.js-line-warranty')?.value || '';
    const partIdRaw = row.querySelector('.js-line-part-id')?.value || '';
    const serviceIdRaw = row.querySelector('.js-line-service-id')?.value || '';
    const image = row.querySelector('.js-line-image')?.value || '';
    const hasAnyValue = description !== '' || amountRaw !== '' || warrantyRaw !== '';

    if (!hasAnyValue) {
      return;
    }

    if (description === '' || amountRaw === '' || amount <= 0) {
      hasIncomplete = true;
      return;
    }

    const item = {
      noi_dung: description,
      so_tien: amount,
    };

    if (type === 'part' && warrantyRaw !== '') {
      item.bao_hanh_thang = Math.max(0, Math.trunc(getNumeric(warrantyRaw)));
    }

    if (type === 'part' && getNumeric(partIdRaw) > 0) {
      item.linh_kien_id = getNumeric(partIdRaw);
    }

    if (type === 'part' && getNumeric(serviceIdRaw) > 0) {
      item.dich_vu_id = getNumeric(serviceIdRaw);
    }

    if (type === 'part' && image) {
      item.hinh_anh = image;
    }

    items.push(item);
  });

  return { items, hasIncomplete };
};

const updateSelectedPartsButtonState = () => {
  const selectedCount = partCatalogState.selectedIds.size;

  if (!addSelectedPartsButton) {
    return;
  }

  addSelectedPartsButton.disabled = selectedCount === 0;
  addSelectedPartsButton.innerHTML = `
    <span class="material-symbols-outlined">playlist_add</span>
    ${selectedCount > 0 ? `Thêm ${selectedCount} linh kiện` : 'Thêm linh kiện đã chọn'}
  `;
};

const renderPartCatalogResults = () => {
  if (!partCatalogResults || !partCatalogStatus) {
    return;
  }

  const keyword = String(partCatalogSearch?.value || '').trim().toLocaleLowerCase('vi-VN');
  const visibleItems = partCatalogState.items.filter((item) => String(item?.ten_linh_kien || '')
    .toLocaleLowerCase('vi-VN')
    .includes(keyword));

  if (!partCatalogState.items.length) {
    partCatalogStatus.textContent = currentCostBooking
      ? 'Dịch vụ của đơn này chưa có linh kiện mẫu hoặc chưa đồng bộ danh mục.'
      : 'Mở đơn để tải danh mục linh kiện đúng theo dịch vụ của đơn.';
    partCatalogResults.innerHTML = '';
    updateSelectedPartsButtonState();
    return;
  }

  partCatalogStatus.textContent = visibleItems.length
    ? `Đang hiển thị ${visibleItems.length}/${partCatalogState.items.length} linh kiện phù hợp với dịch vụ của đơn.`
    : `Không tìm thấy linh kiện khớp với từ khóa "${partCatalogSearch?.value || ''}".`;

  partCatalogResults.innerHTML = visibleItems.map((item) => {
    const partId = getNumeric(item?.id);
    const hasPrice = getNumeric(item?.gia) > 0;
    const isSelected = partCatalogState.selectedIds.has(partId);
    const serviceName = item?.dich_vu?.ten_dich_vu || (currentCostBooking ? getBookingServiceNames(currentCostBooking) : 'Dịch vụ');

    return `
      <label class="dispatch-part-option ${isSelected ? 'is-selected' : ''} ${hasPrice ? '' : 'is-disabled'}">
        <input type="checkbox" class="dispatch-part-option__check js-part-catalog-check" value="${partId}" ${isSelected ? 'checked' : ''} ${hasPrice ? '' : 'disabled'}>
        <div class="dispatch-part-option__thumb">
          ${item?.hinh_anh
            ? `<img src="${escapeHtml(item.hinh_anh)}" alt="${escapeHtml(item?.ten_linh_kien || 'Linh kiện')}">`
            : '<span class="material-symbols-outlined">image_not_supported</span>'}
        </div>
        <div class="dispatch-part-option__body">
          <div class="dispatch-part-option__title">${escapeHtml(item?.ten_linh_kien || 'Linh kiện')}</div>
          <div class="dispatch-part-option__meta">${escapeHtml(serviceName)}</div>
        </div>
        <div class="dispatch-part-option__price">${hasPrice ? formatMoney(item?.gia) : 'Chưa có giá'}</div>
      </label>
    `;
  }).join('');

  updateSelectedPartsButtonState();
};

const loadPartCatalogForBooking = async (booking) => {
  const serviceIds = getBookingServiceIds(booking);
  const cacheKey = serviceIds.slice().sort((a, b) => a - b).join(',');

  partCatalogState.selectedIds = new Set();
  updateSelectedPartsButtonState();

  if (!serviceIds.length) {
    partCatalogState.items = [];
    renderPartCatalogResults();
    return;
  }

  if (partCatalogState.cache.has(cacheKey)) {
    partCatalogState.items = partCatalogState.cache.get(cacheKey) || [];
    renderPartCatalogResults();
    return;
  }

  if (partCatalogStatus) {
    partCatalogStatus.textContent = 'Đang tải danh mục linh kiện theo dịch vụ của đơn...';
  }

  try {
    const params = new URLSearchParams();
    serviceIds.forEach((serviceId) => params.append('dich_vu_ids[]', serviceId));

    const response = await callApi(`/linh-kien?${params.toString()}`, 'GET');

    if (!response.ok) {
      throw new Error(response.data?.message || 'Không thể tải danh mục linh kiện.');
    }

    const items = Array.isArray(response.data) ? response.data : [];
    partCatalogState.items = items;
    partCatalogState.cache.set(cacheKey, items);
    renderPartCatalogResults();
  } catch (error) {
    partCatalogState.items = [];
    renderPartCatalogResults();
    showToast(error.message || 'Lỗi khi tải linh kiện theo dịch vụ.', 'error');
  }
};

const addSelectedCatalogPartsToDraft = () => {
  const selectedParts = partCatalogState.items.filter((item) => partCatalogState.selectedIds.has(getNumeric(item?.id)));

  if (!selectedParts.length) {
    showToast('Vui lòng chọn ít nhất 1 linh kiện trong danh mục.', 'error');
    return;
  }

  const existingIds = new Set(Array.from(partItemsContainer?.querySelectorAll('.js-line-part-id') || [])
    .map((input) => getNumeric(input?.value))
    .filter((id) => id > 0));

  let addedCount = 0;

  selectedParts.forEach((item) => {
    const partId = getNumeric(item?.id);
    const partPrice = getNumeric(item?.gia);

    if (partId <= 0 || partPrice <= 0 || existingIds.has(partId)) {
      return;
    }

    appendCostItemRow(partItemsContainer, 'part', {
      linh_kien_id: partId,
      dich_vu_id: getNumeric(item?.dich_vu_id),
      hinh_anh: item?.hinh_anh || '',
      noi_dung: item?.ten_linh_kien || 'Linh kiện',
      so_tien: partPrice,
      bao_hanh_thang: '',
    });

    existingIds.add(partId);
    addedCount += 1;
  });

  if (addedCount === 0) {
    showToast('Các linh kiện đã chọn đã có sẵn trong bảng chi phí hoặc chưa có giá niêm yết.', 'error');
    return;
  }

  partCatalogState.selectedIds = new Set();
  renderPartCatalogResults();
  updateCostEstimate();
};

const renderCostItemCards = (items, emptyMessage, type, booking = null) => {
  if (!items.length) {
    return `<div class="dispatch-inline-note">${emptyMessage}</div>`;
  }

  return `
    <div class="dispatch-cost-item-list">
      ${items.map((item, index) => {
        const warrantyMeta = type === 'part' ? getWarrantyStatusMeta(booking, item) : null;

        return `
        <div class="dispatch-cost-item-card">
          <div class="dispatch-cost-item-card__top">
            <div>
              <div class="dispatch-cost-item-card__title">${escapeHtml(item?.noi_dung || (type === 'part' ? 'Linh kiện' : 'Tiền công'))}</div>
              <div class="dispatch-cost-item-card__meta">
                ${type === 'part' ? escapeHtml(warrantyMeta?.warrantyLabel || formatWarrantyText(item?.bao_hanh_thang)) : 'Tiền công sửa chữa'}
              </div>
              ${type === 'part' && warrantyMeta ? `<div class="dispatch-warranty-pill ${warrantyMeta.tone}">${escapeHtml(warrantyMeta.label)}</div><div class="dispatch-cost-item-card__note">${escapeHtml(warrantyMeta.detail)}</div>${canConfirmWarranty(booking, item) ? `<button type="button" class="dispatch-warranty-action" onclick="confirmPartWarranty(${booking.id}, ${index})">Xác nhận đã bảo hành</button>` : ''}` : ''}
            </div>
            <strong>${formatMoney(getNumeric(item?.so_tien))}</strong>
          </div>
        </div>
      `;
      }).join('')}
    </div>
  `;
};

const getBookingDateLabel = (booking) => {
  if (!booking?.ngay_hen) {
    return 'Chưa xác định';
  }

  const bookingDate = new Date(booking.ngay_hen);
  if (Number.isNaN(bookingDate.getTime())) {
    return 'Chưa xác định';
  }

  return bookingDate.toLocaleDateString('vi-VN', {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const isTodayBooking = (booking) => String(booking?.ngay_hen || '').slice(0, 10) === getTodayKey();

const getScheduleLabel = (booking) => {
  const timeRange = booking?.khung_gio_hen || 'Chưa chọn giờ';
  return isTodayBooking(booking) ? `${timeRange} (Hôm nay)` : `${timeRange} · ${getBookingDateLabel(booking)}`;
};

const getLocationLabel = (booking) => booking?.loai_dat_lich === 'at_home' ? 'Sửa tại nhà' : 'Sửa tại cửa hàng';
const getStatusTone = (booking) => statusToneMap[booking?.trang_thai] || 'upcoming';
const getStatusLabel = (booking) => statusLabelMap[booking?.trang_thai] || 'Đơn công việc';

const getFilterCount = (status) => status === 'all'
  ? window.allBookings.length
  : window.allBookings.filter((booking) => (statusFilters[status] || statusFilters.all)(booking)).length;

const renderLoadingState = () => {
  bookingsContainer.innerHTML = `
    <div class="dispatch-empty">
      <span class="material-symbols-outlined">hourglass_top</span>
      <h3>Đang tải lịch làm việc</h3>
      <p>Hệ thống đang đồng bộ các đơn sửa chữa của bạn.</p>
    </div>
  `;
};

const renderEmptyState = (status) => {
  const labels = {
    pending: 'chờ xác nhận',
    upcoming: 'sắp tới',
    inprogress: 'đang sửa',
    payment: 'chờ thanh toán',
    done: 'hoàn thành',
    cancelled: 'đã hủy',
  };

  const label = labels[status] || 'phù hợp';

  bookingsContainer.innerHTML = `
    <div class="dispatch-empty">
      <span class="material-symbols-outlined">inventory_2</span>
      <h3>Không có đơn ${label}</h3>
      <p>Khi có lịch mới phù hợp trạng thái này, hệ thống sẽ hiển thị ngay tại đây.</p>
    </div>
  `;
};

const clearRepairTimers = () => {
  Object.values(repairTimers).forEach((timer) => clearInterval(timer));
  repairTimers = {};
};

const renderWorkflow = (booking) => {
  const pricingReady = hasUpdatedPricing(booking);

  return `
    <div class="dispatch-workflow">
      <p class="dispatch-workflow__title">Quy trình hiện tại</p>
      <div class="dispatch-workflow__list">
        <div class="dispatch-workflow__item is-done">
          <span class="dispatch-workflow__icon material-symbols-outlined">check</span>
          <span>Đã bắt đầu sửa</span>
        </div>
        <div class="dispatch-workflow__item ${pricingReady ? 'is-done' : 'is-current'}">
          <span class="dispatch-workflow__icon material-symbols-outlined">${pricingReady ? 'check' : 'priority_high'}</span>
          <span>${pricingReady ? 'Đã cập nhật chi phí' : 'Cần cập nhật chi phí'}</span>
        </div>
        <div class="dispatch-workflow__item ${pricingReady ? 'is-current' : 'is-locked'}">
          <span class="dispatch-workflow__icon material-symbols-outlined">${pricingReady ? 'arrow_forward' : 'lock'}</span>
          <span>${pricingReady ? 'Sẵn sàng báo hoàn thành' : 'Khóa cho đến khi cập nhật giá'}</span>
        </div>
      </div>
    </div>
  `;
};

const renderSummaryBox = (booking) => `
  <div class="dispatch-summary-box">
    <span class="dispatch-summary-box__label">Tổng chi phí</span>
    <span class="dispatch-summary-box__value">${formatMoney(getBookingTotal(booking))}</span>
    <span class="dispatch-summary-box__hint">Đã sẵn sàng để khách thanh toán.</span>
  </div>
`;

const renderInlineNote = (booking) => {
  if (booking.trang_thai === 'dang_lam' && !hasUpdatedPricing(booking)) {
    return '<div class="dispatch-inline-note dispatch-inline-note--danger">Bạn cần cập nhật giá trước khi sử dụng nút báo hoàn thành.</div>';
  }

  if (booking.trang_thai === 'da_xac_nhan') {
    return '<div class="dispatch-inline-note">Ưu tiên bắt đầu đúng khung giờ để giữ trải nghiệm đúng hẹn cho khách.</div>';
  }

  if (booking.trang_thai === 'cho_thanh_toan') {
    return isCashPaymentBooking(booking)
      ? '<div class="dispatch-inline-note">Khách sẽ thanh toán tiền mặt trực tiếp. Chỉ xác nhận hoàn tất sau khi bạn đã thu đủ tiền mặt.</div>'
      : '<div class="dispatch-inline-note">Đơn đã được báo hoàn thành và đang chờ khách thanh toán test trên hệ thống.</div>';
  }

  if (booking.trang_thai === 'cho_hoan_thanh') {
    return '<div class="dispatch-inline-note">Khách thanh toán tiền mặt trực tiếp. Sau khi thu đủ tiền, bạn cần xác nhận để chốt hoàn tất đơn.</div>';
  }

  if (booking.trang_thai === 'da_xong') {
    return '<div class="dispatch-inline-note">Công việc đã hoàn tất và được lưu vào lịch sử xử lý.</div>';
  }

  return '';
};

const renderActionButtons = (booking) => {
  const callButton = getPhoneNumber(booking)
    ? `
        <a href="${escapeHtml(getPhoneHref(booking))}" class="dispatch-call-btn" title="Gọi khách hàng">
          <span class="material-symbols-outlined">call</span>
        </a>
      `
    : '';
  const routeButton = canOpenRouteGuide(booking)
    ? `
        <button type="button" class="dispatch-btn dispatch-btn--secondary" onclick="openRouteGuide(${booking.id})">
          <span class="material-symbols-outlined">route</span>
          Đường đi
        </button>
      `
    : '';

  if (booking.trang_thai === 'da_xac_nhan') {
    return `
      <div class="dispatch-card__action-stack">
        <div class="dispatch-card__action-row">
          <button type="button" class="dispatch-btn dispatch-btn--primary" onclick="updateStatus(${booking.id}, 'dang_lam')">
            <span class="material-symbols-outlined">play_arrow</span>
            Bắt đầu sửa
          </button>
          ${callButton}
        </div>
        ${routeButton ? `<div class="dispatch-card__action-row">${routeButton}</div>` : ''}
        <div class="dispatch-card__action-row">
          <button type="button" class="dispatch-btn dispatch-btn--secondary" onclick="openViewDetailsModal(${booking.id})">
            <span class="material-symbols-outlined">visibility</span>
            Chi tiết
          </button>
        </div>
      </div>
    `;
  }

  if (booking.trang_thai === 'dang_lam') {
    const pricingReady = hasUpdatedPricing(booking);

    return `
      <div class="dispatch-card__action-stack">
        <div class="dispatch-card__action-row dispatch-card__action-row--split">
          <button type="button" class="dispatch-btn dispatch-btn--primary" onclick="openCostModal(${booking.id})">
            <span class="material-symbols-outlined">price_change</span>
            Cập nhật giá
          </button>
          <button type="button" class="dispatch-btn dispatch-btn--secondary" onclick="openViewDetailsModal(${booking.id})">
            <span class="material-symbols-outlined">visibility</span>
            Chi tiết
          </button>
        </div>
        <div class="dispatch-card__action-row">
          <button
            type="button"
            class="dispatch-btn ${pricingReady ? 'dispatch-btn--warm' : 'dispatch-btn--disabled'}"
            onclick="openCompleteModal(${booking.id})"
            title="${pricingReady ? 'Sẵn sàng báo hoàn thành' : 'Bạn phải cập nhật giá trước'}"
          >
            <span class="material-symbols-outlined">task_alt</span>
            Báo hoàn thành
          </button>
          ${callButton}
        </div>
        ${routeButton ? `<div class="dispatch-card__action-row">${routeButton}</div>` : ''}
      </div>
    `;
  }

  if (booking.trang_thai === 'cho_thanh_toan' || booking.trang_thai === 'cho_hoan_thanh') {
    if (isCashPaymentBooking(booking)) {
      return `
        <div class="dispatch-card__action-stack">
          <div class="dispatch-card__action-row dispatch-card__action-row--split">
            <button type="button" class="dispatch-btn dispatch-btn--warm" onclick="confirmCashPayment(${booking.id})">
              <span class="material-symbols-outlined">payments</span>
              Xác nhận đã thu tiền
            </button>
            <button type="button" class="dispatch-btn dispatch-btn--secondary" onclick="openViewDetailsModal(${booking.id})">
              <span class="material-symbols-outlined">receipt_long</span>
              Xem chi tiết
            </button>
          </div>
          ${routeButton ? `<div class="dispatch-card__action-row">${routeButton}</div>` : ''}
          ${callButton ? `<div class="dispatch-card__action-row">${callButton}</div>` : ''}
        </div>
      `;
    }

    return `
      <div class="dispatch-card__action-stack">
        <div class="dispatch-card__action-row dispatch-card__action-row--split">
          <button type="button" class="dispatch-btn dispatch-btn--warm" onclick="openViewDetailsModal(${booking.id})">
            <span class="material-symbols-outlined">payments</span>
            Kiểm tra thanh toán
          </button>
          <button type="button" class="dispatch-btn dispatch-btn--secondary" onclick="openViewDetailsModal(${booking.id})">
            <span class="material-symbols-outlined">receipt_long</span>
            Xem chi tiết
          </button>
        </div>
        ${routeButton ? `<div class="dispatch-card__action-row">${routeButton}</div>` : ''}
        ${callButton ? `<div class="dispatch-card__action-row">${callButton}</div>` : ''}
      </div>
    `;
  }

  return `
    <div class="dispatch-card__action-stack">
      <div class="dispatch-card__action-row">
        <button type="button" class="dispatch-btn dispatch-btn--secondary" onclick="openViewDetailsModal(${booking.id})">
          <span class="material-symbols-outlined">visibility</span>
          Xem chi tiết
        </button>
        ${callButton}
      </div>
    </div>
  `;
};

const getActiveRouteBooking = () => window.allBookings.find((item) => item.id === routeGuideState.bookingId) || null;

const setRouteMapFallback = (title, text) => {
  if (routeMapFallbackTitle) {
    routeMapFallbackTitle.textContent = title;
  }
  if (routeMapFallbackText) {
    routeMapFallbackText.textContent = text;
  }
  routeMapFallback?.removeAttribute('hidden');
  if (routeMapCanvas) {
    routeMapCanvas.style.visibility = 'hidden';
  }
};

const hideRouteMapFallback = () => {
  routeMapFallback?.setAttribute('hidden', 'hidden');
  if (routeMapCanvas) {
    routeMapCanvas.style.visibility = 'visible';
  }
};

const setRouteTrackingStatus = (message, tone = 'info') => {
  if (!routeTrackingStatus) {
    return;
  }

  routeTrackingStatus.textContent = message;
  routeTrackingStatus.dataset.tone = tone;
};

const setRouteMapStatus = (message) => {
  if (routeMapStatus) {
    routeMapStatus.textContent = message;
  }
};

const updateExternalDirectionsLink = (origin = null) => {
  const booking = getActiveRouteBooking();
  const destination = getBookingDestination(booking);
  if (!routeOpenExternalBtn || !destination) {
    return;
  }

  routeOpenExternalBtn.href = buildExternalDirectionsUrl(destination, origin);
};

const ensureRouteMapReady = () => {
  if (!routeMapCanvas) {
    return false;
  }

  if (typeof window.L === 'undefined') {
    setRouteMapFallback(
      'Không tải được thư viện bản đồ',
      'Leaflet chưa sẵn sàng nên hệ thống chưa thể hiển thị bản đồ chỉ đường trong trang.',
    );
    setRouteMapStatus('Không tải được thư viện bản đồ OpenStreetMap.');
    return false;
  }

  if (!routeGuideState.map) {
    routeGuideState.map = window.L.map(routeMapCanvas, {
      zoomControl: true,
      attributionControl: true,
    });

    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(routeGuideState.map);
  }

  routeGuideState.map.invalidateSize();
  return true;
};

const clearRouteMapLayers = () => {
  if (!routeGuideState.map) {
    return;
  }

  if (routeGuideState.routeLine) {
    routeGuideState.map.removeLayer(routeGuideState.routeLine);
    routeGuideState.routeLine = null;
  }
  if (routeGuideState.originMarker) {
    routeGuideState.map.removeLayer(routeGuideState.originMarker);
    routeGuideState.originMarker = null;
  }
  if (routeGuideState.destinationMarker) {
    routeGuideState.map.removeLayer(routeGuideState.destinationMarker);
    routeGuideState.destinationMarker = null;
  }
};

const createRoutePinIcon = (label, tone, symbol, imageUrl = '') => window.L.divIcon({
  className: '',
  html: `
    <div class="dispatch-route-pin dispatch-route-pin--${tone}" data-label="${escapeHtml(label)}">
      ${imageUrl
        ? `<img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(label)}" class="dispatch-route-pin__image">`
        : `<span class="material-symbols-outlined">${escapeHtml(symbol)}</span>`}
    </div>
  `,
  iconSize: [34, 34],
  iconAnchor: [17, 17],
  popupAnchor: [0, -18],
});

const renderRouteMap = (destination, origin = null, routeLatLngs = []) => {
  if (!destination || !ensureRouteMapReady()) {
    return;
  }

  clearRouteMapLayers();

  routeGuideState.destinationMarker = window.L.marker([destination.lat, destination.lng], {
    icon: createRoutePinIcon('Khách', 'customer', 'home'),
  }).addTo(routeGuideState.map).bindPopup('Vị trí khách hàng');

  if (origin && isValidCoordinatePair(origin.lat, origin.lng)) {
    routeGuideState.originMarker = window.L.marker([origin.lat, origin.lng], {
      icon: createRoutePinIcon('Thợ', 'worker', 'construction', routeWorkerMarkerImage),
    }).addTo(routeGuideState.map).bindPopup('Vị trí hiện tại của thợ');
  }

  const normalizedRoute = Array.isArray(routeLatLngs) && routeLatLngs.length
    ? routeLatLngs
    : (origin ? [[origin.lat, origin.lng], [destination.lat, destination.lng]] : [[destination.lat, destination.lng]]);

  if (normalizedRoute.length >= 2) {
    routeGuideState.routeLine = window.L.polyline(normalizedRoute, {
      color: '#0d7cc1',
      weight: 5,
      opacity: 0.88,
      lineCap: 'round',
      lineJoin: 'round',
    }).addTo(routeGuideState.map);
  }

  const bounds = window.L.latLngBounds(normalizedRoute);
  routeGuideState.map.fitBounds(bounds.pad(0.18), { animate: false });
  hideRouteMapFallback();
};

const fetchOsrmRoute = async (origin, destination) => {
  const url = new URL(`https://router.project-osrm.org/route/v1/driving/${origin.lng},${origin.lat};${destination.lng},${destination.lat}`);
  url.searchParams.set('overview', 'full');
  url.searchParams.set('geometries', 'geojson');
  url.searchParams.set('steps', 'false');
  url.searchParams.set('annotations', 'false');

  const response = await fetch(url.toString(), {
    headers: {
      Accept: 'application/json',
    },
  });

  const data = await response.json();
  if (!response.ok || !Array.isArray(data?.routes) || !data.routes.length) {
    throw new Error(data?.message || 'Không lấy được tuyến đường từ OSRM.');
  }

  return data.routes[0];
};

const updateRouteTravelMetrics = async (origin, force = false) => {
  const booking = getActiveRouteBooking();
  const destination = getBookingDestination(booking);
  if (!booking || !destination) {
    return;
  }

  if (!force && !shouldRefreshRouteMetrics(origin)) {
    return;
  }

  const requestId = routeGuideState.pendingRouteRequestId + 1;
  routeGuideState.pendingRouteRequestId = requestId;

  if (routeDistanceHint) {
    routeDistanceHint.textContent = 'Đang tính quãng đường theo tuyến đường thực từ OSRM...';
  }
  if (routeEtaHint) {
    routeEtaHint.textContent = 'Đang tính ETA theo thời lượng tuyến đường thực...';
  }

  try {
    const route = await fetchOsrmRoute(origin, destination);

    if (requestId !== routeGuideState.pendingRouteRequestId) {
      return;
    }

    routeGuideState.lastRouteOrigin = origin;
    routeGuideState.lastRouteUpdateAt = Date.now();

    const routeLatLngs = Array.isArray(route?.geometry?.coordinates)
      ? route.geometry.coordinates
        .map((coordinate) => Array.isArray(coordinate) && coordinate.length >= 2
          ? [Number(coordinate[1]), Number(coordinate[0])]
          : null)
        .filter((coordinate) => Array.isArray(coordinate) && coordinate.every(Number.isFinite))
      : [];

    renderRouteMap(destination, origin, routeLatLngs);

    if (routeDistanceValue) {
      routeDistanceValue.textContent = formatDistanceLabel(Number(route.distance || 0) / 1000);
    }
    if (routeDistanceHint) {
      routeDistanceHint.textContent = 'Quãng đường đang hiển thị theo tuyến lái xe thực từ OSRM.';
    }
    if (routeEtaValue) {
      routeEtaValue.textContent = formatEtaLabel(route.duration);
    }
    if (routeEtaHint) {
      routeEtaHint.textContent = 'ETA dựa trên thời lượng tuyến đường từ OSRM, chưa tính giao thông thời gian thực.';
    }
    setRouteMapStatus('Bản đồ đang hiển thị tuyến đường thực bằng OpenStreetMap + OSRM.');
  } catch (error) {
    const fallbackDistanceKm = calculateHaversineKm(origin.lat, origin.lng, destination.lat, destination.lng);
    renderRouteMap(destination, origin);

    if (routeDistanceValue) {
      routeDistanceValue.textContent = formatDistanceLabel(fallbackDistanceKm);
    }
    if (routeDistanceHint) {
      routeDistanceHint.textContent = 'Không lấy được tuyến đường OSRM, đang tạm hiển thị khoảng cách GPS đường chim bay.';
    }
    if (routeEtaValue) {
      routeEtaValue.textContent = '--';
    }
    if (routeEtaHint) {
      routeEtaHint.textContent = 'Không thể cập nhật ETA lúc này.';
    }
    setRouteMapStatus('OSRM tạm thời không phản hồi. Bản đồ đang hiển thị tuyến nối thẳng để bạn vẫn định hướng được.');
  }
};

const handleRoutePositionUpdate = (position, force = false) => {
  const booking = getActiveRouteBooking();
  const destination = getBookingDestination(booking);
  if (!booking || !destination) {
    return;
  }

  const origin = {
    lat: position.coords.latitude,
    lng: position.coords.longitude,
  };

  routeGuideState.currentOrigin = origin;

  if (routeCurrentCoords) {
    routeCurrentCoords.textContent = formatCoordinatePair(origin);
  }
  if (routeLastUpdated) {
    routeLastUpdated.textContent = `Cập nhật lúc ${formatLiveUpdatedAt(new Date())}`;
  }

  const distanceKm = calculateHaversineKm(origin.lat, origin.lng, destination.lat, destination.lng);
  if (routeDistanceValue && !routeGuideState.lastRouteUpdateAt) {
    routeDistanceValue.textContent = formatDistanceLabel(distanceKm);
  }
  if (routeDistanceHint && !routeGuideState.lastRouteUpdateAt) {
    routeDistanceHint.textContent = distanceKm < 0.15
      ? 'Bạn đã ở rất gần vị trí của khách hàng.'
      : 'Đang chờ dữ liệu tuyến đường OSRM. Tạm thời hiển thị khoảng cách GPS.';
  }
  if (routeEtaValue && !routeGuideState.lastRouteUpdateAt) {
    routeEtaValue.textContent = '--';
  }
  if (routeEtaHint && !routeGuideState.lastRouteUpdateAt) {
    routeEtaHint.textContent = 'ETA sẽ hiển thị sau khi OSRM trả kết quả.';
  }

  setRouteTrackingStatus('Đang theo dõi vị trí realtime của bạn.', 'success');
  updateExternalDirectionsLink(origin);
  updateRouteTravelMetrics(origin, force);
};

const handleRouteLocationError = (error) => {
  const booking = getActiveRouteBooking();
  const destination = getBookingDestination(booking);

  let message = 'Không thể lấy vị trí hiện tại.';
  if (error?.code === 1) {
    message = 'Bạn đã từ chối quyền truy cập vị trí.';
  } else if (error?.code === 2) {
    message = 'Không xác định được vị trí hiện tại.';
  } else if (error?.code === 3) {
    message = 'Hết thời gian chờ lấy vị trí. Hãy thử làm mới.';
  }

  if (routeCurrentCoords) {
    routeCurrentCoords.textContent = 'Chưa lấy được vị trí hiện tại';
  }
  if (routeLastUpdated) {
    routeLastUpdated.textContent = message;
  }
  if (routeDistanceValue) {
    routeDistanceValue.textContent = '--';
  }
  if (routeDistanceHint) {
    routeDistanceHint.textContent = 'Cần cấp quyền GPS để cập nhật quãng đường realtime.';
  }
  if (routeEtaValue) {
    routeEtaValue.textContent = '--';
  }
  if (routeEtaHint) {
    routeEtaHint.textContent = 'Cần cấp quyền GPS để tính ETA theo tuyến đường thực.';
  }

  setRouteTrackingStatus(message, 'warning');
  updateExternalDirectionsLink(null);

  if (destination) {
    setRouteMapFallback(
      'Chưa có vị trí hiện tại',
      'Trình duyệt chưa cung cấp GPS nên bản đồ trong trang chưa thể vẽ lộ trình. Bạn vẫn có thể bấm Mở bản đồ ngoài để dẫn đường.',
    );
    setRouteMapStatus('Chỉ đường trong trang cần quyền GPS hiện tại của thiết bị.');
    renderRouteMap(destination);
  }
};

const stopRouteGuide = ({ resetState = true } = {}) => {
  if (routeGuideState.watchId !== null && navigator.geolocation) {
    navigator.geolocation.clearWatch(routeGuideState.watchId);
  }

  routeGuideState.watchId = null;
  routeGuideState.currentOrigin = null;
  routeGuideState.lastRouteOrigin = null;
  routeGuideState.lastRouteUpdateAt = 0;
  routeGuideState.pendingRouteRequestId += 1;

  if (routeGuideState.map) {
    clearRouteMapLayers();
  }
  setRouteMapFallback(
    'Đang chờ vị trí hiện tại',
    'Cho phép truy cập GPS để hệ thống hiển thị bản đồ chỉ đường từ vị trí của bạn tới nhà khách hàng.',
  );
  if (routeDistanceValue) {
    routeDistanceValue.textContent = '--';
  }
  if (routeDistanceHint) {
    routeDistanceHint.textContent = 'Cho phép GPS để hệ thống tính khoảng cách còn lại.';
  }
  if (routeEtaValue) {
    routeEtaValue.textContent = '--';
  }
  if (routeEtaHint) {
    routeEtaHint.textContent = 'Thời gian đến nơi sẽ hiển thị theo dữ liệu tuyến đường thực.';
  }

  if (resetState) {
    routeGuideState.bookingId = null;
  }
};

const requestRouteLocation = (force = false) => {
  if (!navigator.geolocation) {
    handleRouteLocationError({ code: 2 });
    return;
  }

  navigator.geolocation.getCurrentPosition(
    (position) => handleRoutePositionUpdate(position, force),
    handleRouteLocationError,
    {
      enableHighAccuracy: true,
      maximumAge: 0,
      timeout: 15000,
    },
  );
};

window.openRouteGuide = function(id) {
  const booking = window.allBookings.find((item) => item.id === id);

  if (!booking) {
    showToast('Không tìm thấy đơn cần chỉ đường.', 'error');
    return;
  }

  const destination = getBookingDestination(booking);
  if (!canOpenRouteGuide(booking) || !destination) {
    showToast('Đơn này chưa có đủ tọa độ khách hàng để mở đường đi.', 'error');
    return;
  }

  stopRouteGuide({ resetState: false });
  routeGuideState.bookingId = booking.id;

  if (routeServiceName) {
    routeServiceName.textContent = getBookingServiceNames(booking);
  }
  if (routeDestinationAddress) {
    routeDestinationAddress.textContent = getAddress(booking);
  }
  if (routeDestinationCoords) {
    routeDestinationCoords.textContent = `Tọa độ đích: ${formatCoordinatePair(destination)}`;
  }
  if (routeDistanceValue) {
    routeDistanceValue.textContent = '--';
  }
  if (routeDistanceHint) {
    routeDistanceHint.textContent = 'Đang chờ GPS để tính quãng đường còn lại.';
  }
  if (routeEtaValue) {
    routeEtaValue.textContent = '--';
  }
  if (routeEtaHint) {
    routeEtaHint.textContent = 'Đang chờ dữ liệu tuyến đường thực để tính ETA.';
  }
  if (routeCurrentCoords) {
    routeCurrentCoords.textContent = 'Đang chờ vị trí hiện tại...';
  }
  if (routeLastUpdated) {
    routeLastUpdated.textContent = 'Chưa có lần cập nhật nào.';
  }
  if (routeBookingCode) {
    routeBookingCode.textContent = `#${String(booking.id).padStart(4, '0')}`;
  }

  setRouteTrackingStatus('Đang yêu cầu quyền vị trí của trình duyệt...', 'info');
  setRouteMapStatus('Bản đồ sẽ tự tải bằng OpenStreetMap + OSRM sau khi hệ thống nhận được GPS hiện tại của bạn.');
  updateExternalDirectionsLink(null);
  setRouteMapFallback(
    'Đang kết nối GPS',
    'Ngay khi có vị trí hiện tại, bản đồ sẽ hiển thị lộ trình lái xe tới khách hàng bằng OpenStreetMap + OSRM.',
  );
  renderRouteMap(destination);

  routeModalInstance?.show();

  if (!navigator.geolocation) {
    handleRouteLocationError({ code: 2 });
    return;
  }

  requestRouteLocation(true);
  routeGuideState.watchId = navigator.geolocation.watchPosition(
    (position) => handleRoutePositionUpdate(position),
    handleRouteLocationError,
    {
      enableHighAccuracy: true,
      maximumAge: 5000,
      timeout: 15000,
    },
  );
};

const renderCard = (booking) => {
  const tone = getStatusTone(booking);
  const statusLabel = getStatusLabel(booking);
  const timerHtml = booking.trang_thai === 'dang_lam'
    ? `
        <div class="dispatch-timer">
          <span class="material-symbols-outlined">timer</span>
          <span id="timer-${booking.id}">00:00:00</span>
        </div>
      `
    : '';

  const supportingBlock = booking.trang_thai === 'dang_lam'
    ? renderWorkflow(booking)
    : ['cho_hoan_thanh', 'cho_thanh_toan', 'da_xong'].includes(booking.trang_thai) || hasUpdatedPricing(booking)
      ? renderSummaryBox(booking)
      : '';

  return `
    <article class="dispatch-card dispatch-card--${tone}">
      <div class="dispatch-card__inner">
        <div class="dispatch-card__top">
          <div class="dispatch-card__badges">
            <span class="dispatch-pill dispatch-pill--service">${escapeHtml(getServiceBadge(booking))}</span>
          </div>
          ${timerHtml}
        </div>

        <div class="dispatch-card__status-row">
          <span class="dispatch-pill dispatch-pill--status dispatch-pill--${tone}">${escapeHtml(statusLabel)}</span>
        </div>

        <div>
          <h3 class="dispatch-card__customer">${escapeHtml(getCustomerName(booking))}</h3>
          <p class="dispatch-card__service">${escapeHtml(getBookingServiceNames(booking))}</p>

          <div class="dispatch-card__meta">
            <div class="dispatch-meta-row">
              <span class="material-symbols-outlined">location_on</span>
              <span>${escapeHtml(getAddress(booking))}</span>
            </div>
            <div class="dispatch-meta-row">
              <span class="material-symbols-outlined">home_repair_service</span>
              <span>${escapeHtml(getLocationLabel(booking))}</span>
            </div>
          </div>

          <div class="dispatch-time-chip ${['dang_lam', 'cho_hoan_thanh', 'cho_thanh_toan'].includes(booking.trang_thai) ? 'dispatch-time-chip--warm' : ''}">
            <span class="material-symbols-outlined">schedule</span>
            <span>${escapeHtml(getScheduleLabel(booking))}</span>
          </div>

          ${supportingBlock}
          ${renderInlineNote(booking)}
        </div>

        <div class="dispatch-card__footer">
          ${renderActionButtons(booking)}
        </div>
      </div>
    </article>
  `;
};

function refreshRepairTimers(bookings) {
  clearRepairTimers();

  bookings
    .filter((booking) => booking.trang_thai === 'dang_lam')
    .forEach((booking) => {
      const el = document.getElementById(`timer-${booking.id}`);
      if (!el) {
        return;
      }

      let seconds = 0;
      repairTimers[booking.id] = setInterval(() => {
        seconds += 1;
        const hours = String(Math.floor(seconds / 3600)).padStart(2, '0');
        const minutes = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
        const secs = String(seconds % 60).padStart(2, '0');
        el.textContent = `${hours}:${minutes}:${secs}`;
      }, 1000);
    });
}

function renderBookings(status = window.currentStatus) {
  const list = window.allBookings.filter((booking) => (statusFilters[status] || statusFilters.all)(booking));

  if (!list.length) {
    clearRepairTimers();
    renderEmptyState(status);
    return;
  }

  bookingsContainer.innerHTML = list.map((booking) => renderCard(booking)).join('');
  refreshRepairTimers(list);
}

function updateSummary() {
  const todayBookings = window.allBookings.filter((booking) => isTodayBooking(booking));
  const inProgress = window.allBookings.filter((booking) => booking.trang_thai === 'dang_lam');
  const paymentPending = window.allBookings.filter((booking) => ['cho_hoan_thanh', 'cho_thanh_toan'].includes(booking.trang_thai));
  const projectedIncome = window.allBookings
    .filter((booking) => booking.trang_thai !== 'da_huy')
    .reduce((total, booking) => total + getBookingTotal(booking), 0);

  document.getElementById('summaryTodayCount').textContent = formatCount(todayBookings.length);
  document.getElementById('summaryInProgressCount').textContent = formatCount(inProgress.length);
  document.getElementById('summaryPendingPaymentCount').textContent = formatCount(paymentPending.length);
  document.getElementById('summaryIncomeValue').textContent = formatMoney(projectedIncome);
  document.getElementById('summaryLastUpdated').textContent = `Cập nhật lần cuối: ${new Date().toLocaleTimeString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
  })}`;
}

function updateCounters() {
  document.getElementById('cnt-pending').textContent = getFilterCount('pending');
  document.getElementById('cnt-upcoming').textContent = getFilterCount('upcoming');
  document.getElementById('cnt-inprogress').textContent = getFilterCount('inprogress');
  document.getElementById('cnt-payment').textContent = getFilterCount('payment');
  document.getElementById('cnt-done').textContent = getFilterCount('done');
  document.getElementById('cnt-cancelled').textContent = getFilterCount('cancelled');
}

function hydrateWorkerSummary() {
  const name = user?.name || 'Thợ kỹ thuật';
  const role = user?.role === 'admin' ? 'Quản trị viên kỹ thuật' : 'Thợ kỹ thuật';
  const initial = name.trim().charAt(0).toUpperCase() || 'T';

  document.getElementById('scheduleWorkerName').textContent = name;
  document.getElementById('scheduleWorkerRole').textContent = role;
  document.getElementById('scheduleWorkerInitial').textContent = initial;
}

window.switchTab = function(el, status) {
  document.querySelectorAll('.dispatch-tab').forEach((tab) => tab.classList.remove('active-tab'));
  el.classList.add('active-tab');
  window.currentStatus = status;
  renderBookings(status);
};

window.loadMyBookings = async function(status = window.currentStatus) {
  if (!window.allBookings.length) {
    renderLoadingState();
  }

  try {
    const response = await callApi('/don-dat-lich', 'GET');

    if (!response.ok) {
      showToast(response.data?.message || 'Không thể tải lịch làm việc.', 'error');
      renderEmptyState(status);
      return;
    }

    window.allBookings = response.data?.data || response.data || [];
    updateCounters();
    updateSummary();
    renderBookings(status);
  } catch (error) {
    console.error(error);
    showToast('Lỗi kết nối khi tải lịch làm việc.', 'error');
    renderEmptyState(status);
  }
};

window.updateStatus = async function(id, newStatus) {
  try {
    const response = await callApi(`/don-dat-lich/${id}/status`, 'PUT', { trang_thai: newStatus });

    if (!response.ok) {
      showToast(response.data?.message || 'Không thể cập nhật trạng thái đơn.', 'error');
      return;
    }

    showToast('Đã cập nhật trạng thái thành công.');
    await loadMyBookings(window.currentStatus);
  } catch (error) {
    showToast('Lỗi kết nối khi cập nhật trạng thái.', 'error');
  }
};

window.confirmCashPayment = async function(id) {
  if (!confirm('Bạn xác nhận đã thu đủ tiền mặt cho đơn này?')) {
    return;
  }

  try {
    const response = await callApi(`/bookings/${id}/confirm-cash-payment`, 'POST');

    if (!response.ok) {
      showToast(response.data?.message || 'Không thể xác nhận đã thu tiền mặt.', 'error');
      return;
    }

    showToast(response.data?.message || 'Đã xác nhận thu tiền mặt và hoàn tất đơn.');
    await loadMyBookings(window.currentStatus);
  } catch (error) {
    showToast('Lỗi kết nối khi xác nhận tiền mặt.', 'error');
  }
};

window.confirmPartWarranty = async function(id, partIndex) {
  const booking = window.allBookings.find((item) => item.id === id);
  const partItem = getBookingPartItems(booking || {})[partIndex];

  if (!booking || !partItem) {
    showToast('Không tìm thấy linh kiện cần xác nhận bảo hành.', 'error');
    return;
  }

  if (!confirm(`Xác nhận linh kiện "${partItem.noi_dung || 'Linh kiện'}" đã sử dụng bảo hành?`)) {
    return;
  }

  try {
    const response = await callApi(`/don-dat-lich/${id}/parts/${partIndex}/confirm-warranty`, 'POST');

    if (!response.ok) {
      showToast(response.data?.message || 'Không thể xác nhận bảo hành.', 'error');
      return;
    }

    showToast(response.data?.message || 'Đã xác nhận sử dụng bảo hành.');
    await loadMyBookings(window.currentStatus);
    openViewDetailsModal(id);
  } catch (error) {
    showToast('Lỗi kết nối khi xác nhận bảo hành.', 'error');
  }
};

function updateCostEstimate() {
  const booking = currentCostBooking;
  if (!booking) {
    costEstimateTotal.textContent = formatMoney(0);
    laborSubtotal.textContent = formatMoney(0);
    partsSubtotal.textContent = formatMoney(0);
    travelSubtotal.textContent = formatMoney(0);
    truckSubtotal.textContent = formatMoney(0);
    laborCountBadge.textContent = '0 dòng';
    partCountBadge.textContent = '0 dòng';
    costDraftState.textContent = 'Cần nhập tiền công';
    costDraftState.dataset.state = 'attention';
    costSummaryHint.textContent = 'Đã cộng tiền công, linh kiện, phí đi lại và phí xe chở nếu có.';
    return;
  }

  const laborTotal = sumDraftLineAmounts(laborItemsContainer);
  const partTotal = sumDraftLineAmounts(partItemsContainer);
  const travelTotal = getNumeric(booking?.phi_di_lai);
  const hasTruckLine = truckFeeContainer.style.display !== 'none';
  const truckTotal = hasTruckLine ? getNumeric(inputTienThueXe.value) : 0;
  const total = travelTotal + laborTotal + partTotal + truckTotal;
  const laborRows = countDraftLineRows(laborItemsContainer);
  const partRows = countDraftLineRows(partItemsContainer);

  laborSubtotal.textContent = formatMoney(laborTotal);
  partsSubtotal.textContent = formatMoney(partTotal);
  travelSubtotal.textContent = formatMoney(travelTotal);
  truckSubtotal.textContent = formatMoney(truckTotal);
  costEstimateTotal.textContent = formatMoney(total);
  laborCountBadge.textContent = `${laborRows} dòng`;
  partCountBadge.textContent = `${partRows} dòng`;

  if (laborTotal <= 0) {
    costDraftState.textContent = 'Cần nhập tiền công';
    costDraftState.dataset.state = 'attention';
  } else {
    costDraftState.textContent = 'Sẵn sàng lưu';
    costDraftState.dataset.state = 'ready';
  }

  costSummaryHint.textContent = hasTruckLine
    ? 'Đã cộng tiền công, linh kiện, phí đi lại và phí xe chở của đơn này.'
    : 'Đã cộng tiền công, linh kiện và phí đi lại cố định của đơn này.';
}

window.openCostModal = async function(id) {
  const booking = window.allBookings.find((item) => item.id === id);

  if (!booking) {
    showToast('Không tìm thấy đơn để cập nhật giá.', 'error');
    return;
  }

  currentCostBooking = booking;
  costBookingId.value = booking.id;
  costBookingReference.textContent = `Đơn #${String(booking.id).padStart(4, '0')}`;
  costCustomerName.textContent = getCustomerName(booking);
  costServiceName.textContent = getBookingServiceNames(booking);
  inputGhiChuLinhKien.value = booking.ghi_chu_linh_kien || '';
  displayPhiDiLai.textContent = formatMoney(getNumeric(booking.phi_di_lai));
  costDistanceHint.textContent = booking.loai_dat_lich === 'at_home'
    ? `Phí đi lại tính theo quãng đường ${getNumeric(booking.khoang_cach).toFixed(1)} km.`
    : 'Khách tự mang thiết bị đến cửa hàng nên không phát sinh quãng đường phục vụ.';
  costServiceModeBadge.textContent = booking.loai_dat_lich === 'at_home' ? 'Sửa tại nhà' : 'Sửa tại cửa hàng';
  costServiceModeBadge.dataset.state = booking.loai_dat_lich === 'at_home' ? 'travel' : 'muted';
  costDistanceBadge.textContent = booking.loai_dat_lich === 'at_home'
    ? `${getNumeric(booking.khoang_cach).toFixed(1)} km phục vụ`
    : 'Không phát sinh đi lại';
  costDistanceBadge.dataset.state = booking.loai_dat_lich === 'at_home' ? 'travel' : 'muted';
  if (partCatalogSearch) {
    partCatalogSearch.value = '';
  }

  populateCostItemRows(laborItemsContainer, 'labor', getBookingLaborItems(booking));
  populateCostItemRows(partItemsContainer, 'part', getBookingPartItems(booking));

  if (booking.thue_xe_cho) {
    truckFeeContainer.style.display = '';
    truckSummaryRow.style.display = '';
    inputTienThueXe.value = getNumeric(booking.tien_thue_xe);
    costTruckBadge.textContent = 'Có xe chở thiết bị';
    costTruckBadge.dataset.state = 'on';
  } else {
    truckFeeContainer.style.display = 'none';
    truckSummaryRow.style.display = 'none';
    inputTienThueXe.value = 0;
    costTruckBadge.textContent = 'Không thuê xe chở';
    costTruckBadge.dataset.state = 'muted';
  }

  updateCostEstimate();
  costModalInstance?.show();
  await loadPartCatalogForBooking(booking);
};

addLaborItemButton?.addEventListener('click', () => {
  appendCostItemRow(laborItemsContainer, 'labor');
  updateCostEstimate();
});

addPartItemButton?.addEventListener('click', () => {
  appendCostItemRow(partItemsContainer, 'part');
  updateCostEstimate();
});

partCatalogSearch?.addEventListener('input', renderPartCatalogResults);
partCatalogResults?.addEventListener('change', (event) => {
  const input = event.target.closest('.js-part-catalog-check');
  if (!input) {
    return;
  }

  const partId = getNumeric(input.value);
  if (input.checked) {
    partCatalogState.selectedIds.add(partId);
  } else {
    partCatalogState.selectedIds.delete(partId);
  }

  updateSelectedPartsButtonState();
  input.closest('.dispatch-part-option')?.classList.toggle('is-selected', input.checked);
});
addSelectedPartsButton?.addEventListener('click', addSelectedCatalogPartsToDraft);

[laborItemsContainer, partItemsContainer].forEach((container) => {
  container?.addEventListener('input', updateCostEstimate);
  container?.addEventListener('click', (event) => {
    const removeButton = event.target.closest('.dispatch-line-item__remove');
    if (!removeButton) {
      return;
    }

    const type = removeButton.closest('.dispatch-line-item')?.dataset.lineType === 'part' ? 'part' : 'labor';
    removeButton.closest('.dispatch-line-item')?.remove();
    ensureMinimumCostRows(container, type);
    updateCostEstimate();
  });
});

inputTienThueXe?.addEventListener('input', updateCostEstimate);

if (costForm) {
  costForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const bookingId = costBookingId.value;
    const submitButton = costForm.querySelector('button[type="submit"]');
    const originalLabel = submitButton?.innerHTML || '';
    const laborState = collectCostItems(laborItemsContainer, 'labor');
    const partState = collectCostItems(partItemsContainer, 'part');

    if (!laborState.items.length) {
      showToast('Vui lòng nhập ít nhất 1 dòng tiền công.', 'error');
      return;
    }

    if (laborState.hasIncomplete || partState.hasIncomplete) {
      showToast('Vui lòng điền đủ nội dung và số tiền cho các dòng chi phí đang nhập.', 'error');
      return;
    }

    const payload = {
      tien_cong: laborState.items.reduce((total, item) => total + getNumeric(item.so_tien), 0),
      phi_linh_kien: partState.items.reduce((total, item) => total + getNumeric(item.so_tien), 0),
      chi_tiet_tien_cong: laborState.items,
      chi_tiet_linh_kien: partState.items,
      ghi_chu_linh_kien: inputGhiChuLinhKien.value || '',
    };

    if (truckFeeContainer.style.display !== 'none') {
      payload.tien_thue_xe = inputTienThueXe.value || 0;
    }

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.innerHTML = '<span class="material-symbols-outlined">progress_activity</span>Đang lưu';
    }

    try {
      const response = await callApi(`/don-dat-lich/${bookingId}/update-costs`, 'PUT', payload);

      if (!response.ok) {
        const firstValidationError = response.data?.errors
          ? Object.values(response.data.errors).flat()[0]
          : null;
        throw new Error(firstValidationError || response.data?.message || 'Không thể cập nhật chi phí.');
      }

      showToast('Đã cập nhật chi phí thành công.');
      costModalInstance?.hide();
      await loadMyBookings(window.currentStatus);
    } catch (error) {
      showToast(error.message || 'Lỗi kết nối khi cập nhật giá.', 'error');
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.innerHTML = originalLabel;
      }
    }
  });
}

const renderMediaGrid = (images = [], video = '') => {
  const imageCards = images.length
    ? `<div class="dispatch-media-grid">${images.map((img) => `
        <a class="dispatch-media-card" href="${escapeHtml(img)}" target="_blank" rel="noopener">
          <img src="${escapeHtml(img)}" alt="Ảnh mô tả">
        </a>
      `).join('')}</div>`
    : '<div class="dispatch-inline-note">Khách hàng chưa gửi ảnh mô tả.</div>';

  const videoCard = video
    ? `
        <div class="dispatch-media-grid">
          <a class="dispatch-media-card" href="${escapeHtml(video)}" target="_blank" rel="noopener">
            <video src="${escapeHtml(video)}"></video>
          </a>
        </div>
      `
    : '';

  return `${imageCards}${videoCard}`;
};

window.openViewDetailsModal = function(id) {
  const booking = window.allBookings.find((item) => item.id === id);

  if (!booking) {
    showToast('Không tìm thấy chi tiết đơn.', 'error');
    return;
  }

  const distanceInfo = booking.loai_dat_lich === 'at_home'
    ? `Khoảng cách đo được: ${getNumeric(booking.khoang_cach).toFixed(1)} km`
    : 'Khách tự mang thiết bị đến cửa hàng';

  const truckInfo = booking.thue_xe_cho
    ? '<div class="dispatch-inline-note dispatch-inline-note--danger">Khách có yêu cầu thuê xe chở hoặc vận chuyển thiết bị cồng kềnh.</div>'
    : '<div class="dispatch-inline-note">Không phát sinh yêu cầu xe chở riêng cho đơn này.</div>';

  detailContent.innerHTML = `
    <div class="dispatch-detail-grid">
      <div class="dispatch-panel">
        <h3 class="dispatch-panel__title">Thông tin khách hàng</h3>

        <div class="dispatch-detail-list">
          <div class="dispatch-detail-item">
            <span class="dispatch-detail-item__label">Khách hàng</span>
            <div class="dispatch-detail-item__value">${escapeHtml(getCustomerName(booking))}</div>
          </div>
          <div class="dispatch-detail-item">
            <span class="dispatch-detail-item__label">Điện thoại</span>
            <div class="dispatch-detail-item__value">
              ${getPhoneNumber(booking)
                ? `<a href="${escapeHtml(getPhoneHref(booking))}" class="text-decoration-none">${escapeHtml(getPhoneNumber(booking))}</a>`
                : 'Chưa có số điện thoại'}
            </div>
          </div>
          <div class="dispatch-detail-item">
            <span class="dispatch-detail-item__label">Địa chỉ</span>
            <div class="dispatch-detail-item__value">${escapeHtml(getAddress(booking))}</div>
          </div>
        </div>

        <h3 class="dispatch-panel__title mt-4">Yêu cầu sửa chữa</h3>

        <div class="dispatch-detail-list">
          <div class="dispatch-detail-item">
            <span class="dispatch-detail-item__label">Dịch vụ</span>
            <div class="dispatch-detail-item__value">${escapeHtml(getBookingServiceNames(booking))}</div>
          </div>
          <div class="dispatch-detail-item">
            <span class="dispatch-detail-item__label">Lịch hẹn</span>
            <div class="dispatch-detail-item__value">${escapeHtml(getBookingDateLabel(booking))} · ${escapeHtml(booking.khung_gio_hen || 'Chưa chọn giờ')}</div>
          </div>
          <div class="dispatch-detail-item">
            <span class="dispatch-detail-item__label">Thời gian đặt lịch</span>
            <div class="dispatch-detail-item__value">${escapeHtml(formatDateTimeLabel(booking.created_at))}</div>
          </div>
          <div class="dispatch-detail-item">
            <span class="dispatch-detail-item__label">Thời gian hoàn thành</span>
            <div class="dispatch-detail-item__value">${escapeHtml(formatDateTimeLabel(booking.thoi_gian_hoan_thanh, 'Chưa hoàn thành'))}</div>
          </div>
          <div class="dispatch-detail-item">
            <span class="dispatch-detail-item__label">Mô tả lỗi</span>
            <div class="dispatch-detail-item__value">${nl2brSafe(booking.mo_ta_van_de || 'Khách hàng chưa nhập mô tả chi tiết.')}</div>
          </div>
        </div>

        ${truckInfo}

        <h3 class="dispatch-panel__title mt-4">Hình ảnh / video từ khách</h3>
        ${renderMediaGrid(Array.isArray(booking.hinh_anh_mo_ta) ? booking.hinh_anh_mo_ta : [], booking.video_mo_ta || '')}
      </div>

      <div class="dispatch-panel">
        <h3 class="dispatch-panel__title">Breakdown chi phí</h3>

        <div class="dispatch-cost-breakdown">
          <div class="dispatch-cost-row">
            <span>Phí đi lại</span>
            <strong>${formatMoney(getNumeric(booking.phi_di_lai))}</strong>
          </div>
          <div class="dispatch-cost-row">
            <span>Tiền công thợ</span>
            <strong>${formatMoney(getNumeric(booking.tien_cong))}</strong>
          </div>
          <div class="dispatch-cost-row">
            <span>Phí linh kiện</span>
            <strong>${formatMoney(getNumeric(booking.phi_linh_kien))}</strong>
          </div>
          ${booking.thue_xe_cho ? `
            <div class="dispatch-cost-row">
              <span>Phí thuê xe chở</span>
              <strong>${formatMoney(getNumeric(booking.tien_thue_xe))}</strong>
            </div>
          ` : ''}
        </div>

        <div class="mt-4">
          <span class="dispatch-detail-item__label">Chi tiết tiền công</span>
          ${renderCostItemCards(getBookingLaborItems(booking), 'Chưa có dòng tiền công.', 'labor', booking)}
        </div>

        <div class="mt-4">
          <span class="dispatch-detail-item__label">Chi tiết linh kiện</span>
          ${renderCostItemCards(getBookingPartItems(booking), 'Chưa có linh kiện phát sinh.', 'part', booking)}
        </div>

        <div class="dispatch-inline-note mt-4">${escapeHtml(distanceInfo)}</div>

        <div class="dispatch-cost-total">
          <span class="dispatch-cost-total__label">Tổng chi phí dự kiến</span>
          <span class="dispatch-cost-total__value">${formatMoney(getBookingTotal(booking))}</span>
        </div>

        <div class="dispatch-detail-list mt-4">
          <div class="dispatch-detail-item">
            <span class="dispatch-detail-item__label">Trạng thái đơn</span>
            <div class="dispatch-detail-item__value">${escapeHtml(getStatusLabel(booking))}</div>
          </div>
          <div class="dispatch-detail-item">
            <span class="dispatch-detail-item__label">Ghi chú linh kiện</span>
            <div class="dispatch-detail-item__value">${nl2brSafe(booking.ghi_chu_linh_kien || 'Chưa có ghi chú linh kiện.')}</div>
          </div>
          <div class="dispatch-detail-item">
            <span class="dispatch-detail-item__label">Hình thức phục vụ</span>
            <div class="dispatch-detail-item__value">${escapeHtml(getLocationLabel(booking))}</div>
          </div>
        </div>
      </div>
    </div>
  `;

  detailModalInstance?.show();
};

function updatePaymentOptionState() {
  document.querySelectorAll('.dispatch-pay-option').forEach((option) => {
    const input = option.querySelector('input[type="radio"]');
    option.classList.toggle('is-active', Boolean(input?.checked));
  });
}

function renderImagePreview() {
  const files = Array.from(inputHinhAnhKetQua?.files || []);
  imageUploadPreview.innerHTML = files.slice(0, 5).map((file) => `
    <div class="dispatch-preview-card">
      <img src="${URL.createObjectURL(file)}" alt="${escapeHtml(file.name)}">
    </div>
  `).join('');
}

function renderVideoPreview() {
  const file = inputVideoKetQua?.files?.[0];

  if (!file) {
    videoUploadPreview.innerHTML = '';
    return;
  }

  videoUploadPreview.innerHTML = `
    <div class="dispatch-video-preview">
      <span class="material-symbols-outlined">movie</span>
      <div>
        <div>${escapeHtml(file.name)}</div>
        <small>${(file.size / 1024 / 1024).toFixed(1)} MB</small>
      </div>
    </div>
  `;
}

inputHinhAnhKetQua?.addEventListener('change', renderImagePreview);
inputVideoKetQua?.addEventListener('change', renderVideoPreview);
document.querySelectorAll('input[name="phuong_thuc_thanh_toan"]').forEach((input) => {
  input.addEventListener('change', updatePaymentOptionState);
});
routeRefreshLocationBtn?.addEventListener('click', () => {
  if (!routeGuideState.bookingId) {
    return;
  }

  setRouteTrackingStatus('Đang làm mới vị trí hiện tại...', 'info');
  requestRouteLocation(true);
});
routeModalEl?.addEventListener('hidden.bs.modal', () => {
  stopRouteGuide();
});
routeModalEl?.addEventListener('shown.bs.modal', () => {
  routeGuideState.map?.invalidateSize();
});

window.openCompleteModal = function(id) {
  const booking = window.allBookings.find((item) => item.id === id);

  if (!booking) {
    showToast('Không tìm thấy đơn cần hoàn thành.', 'error');
    return;
  }

  if (!hasUpdatedPricing(booking)) {
    showToast('Bạn phải cập nhật giá trước khi báo hoàn thành.', 'error');
    openCostModal(id);
    return;
  }

  completeBookingId.value = booking.id;
  completeCustomerName.textContent = getCustomerName(booking);
  completeServiceName.textContent = getBookingServiceNames(booking);
  completeBookingTotal.textContent = formatMoney(getBookingTotal(booking));
  completePricingAlert.style.display = 'none';
  completeWorkflowList.innerHTML = `
    <div class="dispatch-workflow__item is-done">
      <span class="dispatch-workflow__icon material-symbols-outlined">check</span>
      <span>Đã bắt đầu sửa</span>
    </div>
    <div class="dispatch-workflow__item is-done">
      <span class="dispatch-workflow__icon material-symbols-outlined">check</span>
      <span>Đã cập nhật chi phí</span>
    </div>
    <div class="dispatch-workflow__item is-current">
      <span class="dispatch-workflow__icon material-symbols-outlined">priority_high</span>
      <span>Chuẩn bị gửi yêu cầu thanh toán</span>
    </div>
  `;

  completeForm.reset();
  document.getElementById('pay_cod').checked = true;
  updatePaymentOptionState();
  imageUploadPreview.innerHTML = '';
  videoUploadPreview.innerHTML = '';
  completeModalInstance?.show();
};

if (completeForm) {
  completeForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const bookingId = completeBookingId.value;
    const originalButtonHtml = btnSubmitCompleteForm.innerHTML;
    btnSubmitCompleteForm.disabled = true;
    btnSubmitCompleteForm.innerHTML = '<span class="material-symbols-outlined">progress_activity</span>Đang tải tệp';

    try {
      const formData = new FormData();
      formData.append('_method', 'POST');
      formData.append('phuong_thuc_thanh_toan', document.querySelector('input[name="phuong_thuc_thanh_toan"]:checked')?.value || 'cod');

      Array.from(inputHinhAnhKetQua.files || []).forEach((file) => {
        formData.append('hinh_anh_ket_qua[]', file);
      });

      const videoFile = inputVideoKetQua.files?.[0];
      if (videoFile) {
        formData.append('video_ket_qua', videoFile);
      }

      const token = localStorage.getItem('access_token');
      const response = await fetch(`${baseUrl}/api/bookings/${bookingId}/request-payment`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
        body: formData,
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Không thể gửi yêu cầu thanh toán.');
      }

      showToast('Đã gửi yêu cầu thanh toán cho khách hàng.');
      completeModalInstance?.hide();
      completeForm.reset();
      imageUploadPreview.innerHTML = '';
      videoUploadPreview.innerHTML = '';
      await loadMyBookings(window.currentStatus);
    } catch (error) {
      showToast(error.message || 'Lỗi kết nối khi báo hoàn thành.', 'error');
    } finally {
      btnSubmitCompleteForm.disabled = false;
      btnSubmitCompleteForm.innerHTML = originalButtonHtml;
    }
  });
}

hydrateWorkerSummary();
updatePaymentOptionState();
loadMyBookings(window.currentStatus);
</script>
@endpush
