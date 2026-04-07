@extends('layouts.app')
@section('title', 'Lịch làm việc - Thợ Tốt NTU')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@600;700;800&family=Inter:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&family=Public+Sans:wght@700;800;900&family=Material+Symbols+Outlined" rel="stylesheet"/>
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

  .dispatch-part-catalog__search-shell {
    position: relative;
  }

  .dispatch-part-catalog__field span {
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #475569;
  }

  .dispatch-part-catalog__suggestions {
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    right: 0;
    z-index: 30;
    display: grid;
    gap: 8px;
    max-height: 320px;
    overflow: auto;
    padding: 10px;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 20px 48px rgba(15, 23, 42, 0.14);
    backdrop-filter: blur(18px);
  }

  .dispatch-part-suggestion {
    width: 100%;
    display: grid;
    grid-template-columns: 52px minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
    padding: 12px;
    border: 1px solid transparent;
    border-radius: 18px;
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.94), rgba(255, 255, 255, 0.98));
    color: inherit;
    text-align: left;
    cursor: pointer;
    transition: 0.18s ease;
  }

  .dispatch-part-suggestion:hover,
  .dispatch-part-suggestion.is-active {
    border-color: rgba(13, 124, 193, 0.26);
    background: rgba(239, 246, 255, 0.96);
    transform: translateY(-1px);
  }

  .dispatch-part-suggestion.is-selected {
    border-color: rgba(13, 124, 193, 0.32);
    background: rgba(232, 243, 255, 0.94);
  }

  .dispatch-part-suggestion.is-disabled {
    opacity: 0.62;
    cursor: not-allowed;
  }

  .dispatch-part-suggestion__thumb {
    width: 52px;
    height: 52px;
    display: grid;
    place-items: center;
    border-radius: 16px;
    overflow: hidden;
    background: rgba(15, 23, 42, 0.06);
    color: var(--dispatch-soft);
  }

  .dispatch-part-suggestion__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .dispatch-part-suggestion__body {
    min-width: 0;
    display: grid;
    gap: 4px;
  }

  .dispatch-part-suggestion__title {
    font-weight: 800;
    color: var(--dispatch-text);
  }

  .dispatch-part-suggestion__meta {
    font-size: 0.82rem;
    color: var(--dispatch-muted);
  }

  .dispatch-part-suggestion__aside {
    display: grid;
    justify-items: end;
    gap: 6px;
  }

  .dispatch-part-suggestion__price {
    font-weight: 800;
    color: var(--dispatch-copper);
    white-space: nowrap;
  }

  .dispatch-part-suggestion__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 24px;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(13, 124, 193, 0.12);
    color: var(--dispatch-primary-strong);
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.04em;
  }

  .dispatch-part-suggestion-empty {
    padding: 12px 14px;
    border-radius: 16px;
    background: rgba(248, 250, 252, 0.94);
    color: var(--dispatch-muted);
    line-height: 1.55;
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
    grid-template-columns: minmax(0, 1fr) 150px 112px 140px 48px;
    grid-template-areas:
      "description description description description remove"
      "price quantity warranty warranty remove";
  }

  .dispatch-line-item__field {
    display: grid;
    gap: 8px;
    min-width: 0;
  }

  .dispatch-line-item__field span {
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #475569;
  }

  .dispatch-line-item__grid.is-parts .dispatch-line-item__field--description {
    grid-area: description;
  }

  .dispatch-line-item__grid.is-parts .dispatch-line-item__field--price {
    grid-area: price;
  }

  .dispatch-line-item__grid.is-parts .dispatch-line-item__field--quantity {
    grid-area: quantity;
  }

  .dispatch-line-item__grid.is-parts .dispatch-line-item__field--warranty {
    grid-area: warranty;
  }

  .dispatch-line-item__grid.is-parts .dispatch-line-item__remove {
    grid-area: remove;
  }

  .dispatch-line-item__field--description .dispatch-input {
    min-width: 0;
  }

  .dispatch-quantity-control {
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr) 42px;
    align-items: center;
    min-height: 56px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 20px;
    background: #edf4ff;
    overflow: hidden;
  }

  .dispatch-quantity-control .dispatch-input {
    border: none;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
    text-align: center;
    padding: 16px 10px;
  }

  .dispatch-quantity-control .dispatch-input:focus {
    box-shadow: none;
  }

  .dispatch-quantity-control .dispatch-input::-webkit-outer-spin-button,
  .dispatch-quantity-control .dispatch-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }

  .dispatch-quantity-control .dispatch-input {
    -moz-appearance: textfield;
    appearance: textfield;
  }

  .dispatch-quantity-step {
    width: 42px;
    height: 100%;
    border: none;
    background: rgba(13, 124, 193, 0.08);
    color: var(--dispatch-primary-strong);
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: background 0.18s ease;
  }

  .dispatch-quantity-step:hover {
    background: rgba(13, 124, 193, 0.14);
  }

  .dispatch-quantity-step:disabled {
    opacity: 0.45;
    cursor: not-allowed;
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

  .dispatch-modal--pricing-v2 .modal-dialog {
    max-width: 1220px;
    height: calc(100vh - 24px);
    margin: 12px auto;
  }

  .dispatch-modal__content--pricing-v2 {
    height: 100%;
    display: flex;
    flex-direction: column;
    background:
      radial-gradient(circle at top right, rgba(13, 124, 193, 0.12), transparent 24rem),
      linear-gradient(180deg, #f7fbff 0%, #ffffff 20%);
  }

  .dispatch-modal__header--pricing-v2 {
    padding: 22px 28px 18px;
    background:
      linear-gradient(135deg, rgba(239, 246, 255, 0.92), rgba(255, 255, 255, 0.98) 54%, rgba(255, 247, 237, 0.7));
    border-bottom: 1px solid rgba(148, 163, 184, 0.16);
  }

  .dispatch-modal__title-accent {
    display: inline-flex;
    align-items: center;
    min-height: 34px;
    padding: 0 12px;
    border-radius: 999px;
    background: rgba(13, 124, 193, 0.1);
    color: var(--dispatch-primary-strong);
    font-size: 0.82rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
  }

  .dispatch-modal__header-divider {
    width: 1px;
    align-self: stretch;
    background: rgba(148, 163, 184, 0.18);
  }

  .dispatch-modal__header--pricing-v2 .dispatch-modal__title {
    font-size: clamp(1.72rem, 2.2vw, 2.28rem);
    line-height: 1.08;
  }

  .dispatch-modal__header--pricing-v2 .dispatch-modal__subtitle {
    margin-top: 8px;
    max-width: 60rem;
    font-size: 0.92rem;
    line-height: 1.55;
  }

  .dispatch-modal__close.dispatch-modal__close--v2 {
    width: 54px;
    height: 54px;
    border-radius: 20px;
    background: rgba(15, 23, 42, 0.05);
  }

  .dispatch-modal__close.dispatch-modal__close--v2:hover {
    background: rgba(15, 23, 42, 0.1);
  }

  .dispatch-modal__body--pricing-v2 {
    flex: 1;
    min-height: 0;
    overflow: hidden;
    background: transparent;
  }

  .dispatch-pricing-form {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 100%;
    min-height: 0;
    background: transparent;
  }

  .dispatch-pricing-v2-main {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    gap: 18px;
    padding: 18px 28px 20px;
    overflow: hidden;
  }

  .dispatch-pricing-v2-context {
    padding: 18px 24px;
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, 0.14);
    background:
      linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.9)),
      #ffffff;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.04);
  }

  .dispatch-pricing-v2-context-inner {
    display: grid;
    gap: 10px;
  }

  .dispatch-pricing-v2-context-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: fit-content;
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--dispatch-primary);
  }

  .dispatch-pricing-v2-context-title {
    margin: 0;
    font-family: 'DM Sans', sans-serif;
    font-size: clamp(1.8rem, 2.6vw, 2.28rem);
    font-weight: 800;
    letter-spacing: -0.05em;
    line-height: 1.04;
    color: var(--dispatch-text);
  }

  .dispatch-pricing-v2-context-note {
    max-width: 44rem;
    margin: 0;
    color: var(--dispatch-muted);
    font-size: 0.92rem;
    line-height: 1.55;
  }

  .dispatch-pricing-v2-context-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }

  .dispatch-pricing-v2-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    padding: 8px 14px;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    background: rgba(255, 255, 255, 0.88);
    color: #334155;
    font-size: 0.86rem;
    font-weight: 700;
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.04);
  }

  .dispatch-pricing-v2-meta-item .material-symbols-outlined {
    font-size: 1.1rem;
    color: var(--dispatch-primary);
  }

  .dispatch-pricing-v2-content-grid {
    display: grid;
    flex: 1;
    min-height: 0;
    grid-template-columns: minmax(0, 1fr) 360px;
    gap: 20px;
    align-items: stretch;
    overflow: hidden;
  }

  .dispatch-pricing-v2-editor,
  .dispatch-pricing-v2-summary {
    min-width: 0;
    height: 100%;
  }

  .dispatch-pricing-v2-editor {
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 6px;
    padding-bottom: 8px;
    scrollbar-gutter: stable;
  }

  .dispatch-pricing-v2-editor::-webkit-scrollbar {
    width: 10px;
  }

  .dispatch-pricing-v2-editor::-webkit-scrollbar-thumb {
    border: 2px solid transparent;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.48);
    background-clip: padding-box;
  }

  .dispatch-pricing-v2-editor::-webkit-scrollbar-track {
    background: transparent;
  }

  .dispatch-pricing-v2-summary {
    position: relative;
    align-self: stretch;
    overflow: hidden;
  }

  .dispatch-pricing-v2-section {
    margin-bottom: 16px;
    padding: 20px;
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, 0.14);
    background:
      linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.92));
    box-shadow: 0 16px 34px rgba(15, 23, 42, 0.04);
  }

  .dispatch-pricing-v2-section:last-child {
    margin-bottom: 0;
  }

  .dispatch-pricing-v2-section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
  }

  .dispatch-pricing-v2-section-copy {
    display: grid;
    gap: 6px;
    min-width: 0;
  }

  .dispatch-pricing-v2-section-kicker {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    min-height: 24px;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(13, 124, 193, 0.1);
    color: var(--dispatch-primary);
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-section-title {
    margin: 0;
    font-family: 'DM Sans', sans-serif;
    font-size: 1.18rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--dispatch-text);
  }

  .dispatch-pricing-v2-section-hint {
    margin: 0;
    color: var(--dispatch-muted);
    font-size: 0.88rem;
    line-height: 1.55;
  }

  .dispatch-pricing-v2-section-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
    flex-shrink: 0;
  }

  .dispatch-pricing-v2-section-head .badge {
    min-height: 28px;
    padding: 0 12px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.08) !important;
    color: #475569 !important;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-add-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 0 16px;
    border: none;
    border-radius: 999px;
    background: rgba(13, 124, 193, 0.12);
    color: var(--dispatch-primary);
    font-size: 0.82rem;
    font-weight: 800;
    cursor: pointer;
    transition: 0.18s ease;
    white-space: nowrap;
  }

  .dispatch-pricing-v2-add-btn:hover {
    background: rgba(13, 124, 193, 0.18);
    transform: translateY(-1px);
  }

  .dispatch-pricing-v2-labor-table-wrapper {
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, 0.14);
    background: #ffffff;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
    overflow: hidden;
  }

  .dispatch-pricing-v2-table {
    width: 100%;
    border-collapse: collapse;
  }

  .dispatch-pricing-v2-table th {
    padding: 14px 16px;
    background: rgba(248, 250, 252, 0.92);
    border-bottom: 1px solid rgba(148, 163, 184, 0.14);
    color: #475569;
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-table td {
    padding: 14px 16px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.12);
    vertical-align: middle;
    background: #ffffff;
  }

  .dispatch-pricing-v2-table tr:last-child td {
    border-bottom: none;
  }

  .dispatch-pricing-v2-searchbox {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 58px;
    padding: 8px 10px 8px 16px;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.18);
    background: #ffffff;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.03);
  }

  .dispatch-pricing-v2-searchbox:focus-within {
    border-color: rgba(13, 124, 193, 0.26);
    box-shadow: 0 0 0 4px rgba(13, 124, 193, 0.08);
  }

  .dispatch-pricing-v2-search-icon {
    color: #94a3b8;
    font-size: 1.2rem;
  }

  .dispatch-pricing-v2-search-input {
    flex: 1;
    min-width: 0;
    border: none;
    background: transparent;
    outline: none;
    color: var(--dispatch-text);
    font-size: 0.96rem;
    font-weight: 700;
  }

  .dispatch-pricing-v2-searchbox .btn,
  .dispatch-pricing-v2-search-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0 14px;
    border: none;
    border-radius: 14px;
    background: rgba(13, 124, 193, 0.12);
    color: var(--dispatch-primary);
    font-size: 0.8rem;
    font-weight: 800;
    box-shadow: none;
    white-space: nowrap;
  }

  .dispatch-pricing-v2-searchbox .btn:hover,
  .dispatch-pricing-v2-search-action:hover {
    background: rgba(13, 124, 193, 0.18);
    color: var(--dispatch-primary-strong);
  }

  .dispatch-pricing-v2-searchbox .btn:disabled,
  .dispatch-pricing-v2-search-action:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .dispatch-pricing-v2-inline-status {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin: 12px 0 14px;
  }

  .dispatch-pricing-v2-parts-list {
    display: grid;
    gap: 16px;
    margin-top: 16px;
  }

  .dispatch-pricing-v2-part-card {
    margin-bottom: 0;
    padding: 18px;
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    background:
      linear-gradient(180deg, rgba(248, 250, 252, 0.9), rgba(255, 255, 255, 0.98));
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.04);
  }

  .dispatch-pricing-v2-part-card-inner {
    display: grid;
    gap: 18px;
    grid-template-columns: minmax(0, 1fr);
    align-items: center;
  }

  .dispatch-pricing-v2-part-icon {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    border: 1px solid rgba(148, 163, 184, 0.14);
    background: rgba(248, 250, 252, 0.96);
    display: grid;
    place-items: center;
    overflow: hidden;
    flex-shrink: 0;
  }

  .dispatch-pricing-v2-part-icon .material-symbols-outlined {
    font-size: 1.8rem;
    color: #94a3b8;
  }

  .dispatch-pricing-v2-part-title {
    margin: 0;
    color: var(--dispatch-text);
    font-family: 'DM Sans', sans-serif;
    font-size: 1.08rem;
    font-weight: 800;
    line-height: 1.4;
  }

  .dispatch-pricing-v2-part-cat {
    display: inline-flex;
    align-items: center;
    min-height: 26px;
    margin-top: 6px;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(13, 124, 193, 0.1);
    color: var(--dispatch-primary);
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-stepper {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 4px;
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, 0.18);
    background: #edf4ff;
  }

  .dispatch-pricing-v2-stepper-btn {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 12px;
    background: #ffffff;
    color: #475569;
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: 0.18s ease;
  }

  .dispatch-pricing-v2-stepper-btn:hover {
    background: rgba(13, 124, 193, 0.1);
    color: var(--dispatch-primary);
  }

  .dispatch-pricing-v2-input-dark,
  .dispatch-pricing-v2-fee-input,
  .dispatch-pricing-v2-textarea {
    width: 100%;
    border: 1px solid rgba(148, 163, 184, 0.18);
    outline: none;
    transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
  }

  .dispatch-pricing-v2-input-dark {
    height: 48px;
    padding: 0 14px;
    border-radius: 16px;
    background: #edf4ff;
    color: var(--dispatch-text);
    font-size: 0.95rem;
    font-weight: 700;
  }

  .dispatch-pricing-v2-input-dark:focus,
  .dispatch-pricing-v2-fee-input:focus,
  .dispatch-pricing-v2-textarea:focus {
    border-color: rgba(13, 124, 193, 0.3);
    box-shadow: 0 0 0 4px rgba(13, 124, 193, 0.08);
  }

  .dispatch-pricing-v2-input-dark[readonly] {
    background: rgba(248, 250, 252, 0.94);
    color: #334155;
  }

  .dispatch-pricing-v2-fees-section {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
  }

  .dispatch-pricing-v2-fee-card {
    padding: 20px;
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, 0.14);
    background:
      linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.92));
  }

  .dispatch-pricing-v2-fee-title {
    margin: 0 0 16px;
    font-family: 'DM Sans', sans-serif;
    font-size: 1rem;
    font-weight: 800;
    color: var(--dispatch-text);
  }

  .dispatch-pricing-v2-fee-input-wrap {
    position: relative;
  }

  .dispatch-pricing-v2-fee-prefix {
    position: absolute;
    top: 50%;
    right: 16px;
    transform: translateY(-50%);
    color: var(--dispatch-muted);
    font-weight: 800;
  }

  .dispatch-pricing-v2-fee-input {
    height: 52px;
    padding: 0 42px 0 16px;
    border-radius: 16px;
    background: #edf4ff;
    color: var(--dispatch-primary-strong);
    font-size: 1.08rem;
    font-weight: 800;
  }

  .dispatch-pricing-v2-fee-label {
    color: #475569;
    font-size: 0.92rem;
    font-weight: 700;
  }

  .dispatch-pricing-v2-fee-value {
    font-family: 'DM Sans', sans-serif;
    font-size: 1.12rem;
    font-weight: 800;
    color: var(--dispatch-text);
  }

  .dispatch-pricing-v2-fee-hint {
    font-size: 0.82rem;
    line-height: 1.6;
    color: var(--dispatch-muted);
  }

  .dispatch-pricing-v2-textarea {
    min-height: 160px;
    padding: 16px 18px;
    border-radius: 20px;
    background: #edf4ff;
    color: var(--dispatch-text);
    font-size: 0.96rem;
    font-weight: 600;
    resize: vertical;
  }

  .dispatch-pricing-v2-summary-card {
    position: sticky;
    top: 0;
    max-height: 100%;
    padding: 24px;
    border-radius: 28px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    background:
      linear-gradient(180deg, rgba(248, 250, 252, 0.96), rgba(255, 255, 255, 0.98));
    box-shadow: 0 20px 42px rgba(15, 23, 42, 0.07);
  }

  .dispatch-pricing-v2-summary-title {
    margin: 0;
    font-family: 'DM Sans', sans-serif;
    font-size: 1.22rem;
    font-weight: 800;
    color: var(--dispatch-text);
  }

  .dispatch-pricing-v2-summary-note {
    margin: -6px 0 16px;
    color: var(--dispatch-muted);
    font-size: 0.87rem;
    line-height: 1.55;
  }

  .dispatch-pricing-v2-summary-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 32px;
    padding: 0 12px;
    border-radius: 999px;
    background: rgba(15, 159, 124, 0.14);
    color: #0f9f7c;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-summary-status[data-state="attention"] {
    background: rgba(245, 158, 11, 0.14);
    color: #b45309;
  }

  .dispatch-pricing-v2-summary-status[data-state="ready"] {
    background: rgba(15, 159, 124, 0.14);
    color: #0f9f7c;
  }

  .dispatch-pricing-v2-summary-list {
    display: grid;
    gap: 12px;
  }

  .dispatch-pricing-v2-summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.82);
    color: #475569;
    font-size: 0.95rem;
    font-weight: 700;
  }

  .dispatch-pricing-v2-summary-row strong {
    color: var(--dispatch-text);
  }

  .dispatch-pricing-v2-total-callout {
    padding: 20px;
    border-radius: 24px;
    border: 2px dashed rgba(13, 124, 193, 0.28);
    background: rgba(240, 249, 255, 0.78);
  }

  .dispatch-pricing-v2-total-label {
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--dispatch-primary);
  }

  .dispatch-pricing-v2-total-value {
    margin-top: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: clamp(2.2rem, 3vw, 3rem);
    font-weight: 800;
    letter-spacing: -0.05em;
    color: var(--dispatch-primary-strong);
    line-height: 1.05;
  }

  .dispatch-pricing-v2-footer {
    position: relative;
    flex-shrink: 0;
    padding: 16px 28px;
    border-top: 1px solid rgba(148, 163, 184, 0.16);
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
  }

  .dispatch-pricing-v2-footer .container-fluid {
    padding: 0;
  }

  .dispatch-pricing-v2-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 48px;
    padding: 0 20px;
    border: none;
    border-radius: 16px;
    font-size: 0.9rem;
    font-weight: 800;
    cursor: pointer;
    transition: 0.18s ease;
  }

  .dispatch-pricing-v2-btn--ghost {
    background: rgba(15, 23, 42, 0.06);
    color: #475569;
  }

  .dispatch-pricing-v2-btn--ghost:hover {
    background: rgba(15, 23, 42, 0.1);
    color: var(--dispatch-text);
  }

  .dispatch-pricing-v2-btn--primary {
    background: linear-gradient(135deg, #0d7cc1, #095b91);
    color: #ffffff;
    box-shadow: 0 16px 26px rgba(13, 124, 193, 0.24);
  }

  .dispatch-pricing-v2-btn--primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 20px 34px rgba(13, 124, 193, 0.28);
  }

  .dispatch-pricing-v2-remove-btn,
  .dispatch-pricing-v2-table .dispatch-line-item__remove,
  .dispatch-pricing-v2-part-card .dispatch-line-item__remove {
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 16px;
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: 0.18s ease;
    flex-shrink: 0;
  }

  .dispatch-pricing-v2-remove-btn:hover,
  .dispatch-pricing-v2-table .dispatch-line-item__remove:hover,
  .dispatch-pricing-v2-part-card .dispatch-line-item__remove:hover {
    background: rgba(239, 68, 68, 0.16);
  }

  @media (max-width: 991.98px) {
    .dispatch-modal--pricing-v2 .modal-dialog {
      height: auto;
      margin: 12px;
    }

    .dispatch-modal__content--pricing-v2 {
      height: auto;
    }

    .dispatch-modal__body--pricing-v2 {
      overflow: visible;
    }

    .dispatch-pricing-form,
    .dispatch-pricing-v2-main,
    .dispatch-pricing-v2-content-grid {
      min-height: auto;
    }

    .dispatch-pricing-v2-content-grid {
      grid-template-columns: 1fr;
    }

    .dispatch-pricing-v2-editor {
      overflow: visible;
      padding-right: 0;
      height: auto;
    }

    .dispatch-pricing-v2-summary {
      position: static;
      height: auto;
      overflow: visible;
    }

    .dispatch-pricing-v2-summary-card {
      position: static;
      max-height: none;
    }
  }

  @media (max-width: 767.98px) {
    .dispatch-modal__header--pricing-v2 {
      padding: 24px 20px 20px;
    }

    .dispatch-pricing-v2-main {
      padding: 18px 20px 18px;
    }

    .dispatch-pricing-v2-section,
    .dispatch-pricing-v2-summary-card,
    .dispatch-pricing-v2-context {
      padding: 18px;
      border-radius: 22px;
    }

    .dispatch-pricing-v2-section-head {
      flex-direction: column;
      align-items: stretch;
    }

    .dispatch-pricing-v2-section-actions {
      justify-content: flex-start;
    }

    .dispatch-pricing-v2-searchbox {
      flex-wrap: wrap;
      align-items: stretch;
      min-height: auto;
    }

    .dispatch-pricing-v2-searchbox .btn,
    .dispatch-pricing-v2-search-action {
      width: 100%;
    }

    .dispatch-pricing-v2-fees-section {
      grid-template-columns: 1fr;
    }

    .dispatch-pricing-v2-footer {
      padding: 14px 20px 18px;
    }
  }

  /* FIGMA SYNC: pricing modal */
  .dispatch-modal--pricing-v2 .modal-dialog {
    max-width: 1180px;
    margin: 24px auto;
  }

  @media (min-width: 992px) {
    .dispatch-modal--pricing-v2 .modal-dialog {
      height: calc(100vh - 48px);
    }
  }

  .dispatch-modal--pricing-v2 .dispatch-modal__content,
  .dispatch-modal--pricing-v2 input,
  .dispatch-modal--pricing-v2 textarea,
  .dispatch-modal--pricing-v2 button,
  .dispatch-modal--pricing-v2 select {
    font-family: 'Inter', sans-serif;
  }

  .dispatch-modal__content--pricing-v2 {
    background: #f8fafb;
    border-radius: 8px;
    box-shadow: 0 32px 64px -12px rgba(42, 52, 55, 0.15);
  }

  .dispatch-modal__header--pricing-v2 {
    position: relative;
    
    background: #f8fafb;
    border-bottom: 1px solid rgba(169, 180, 183, 0.1);
  }

  .dispatch-pricing-v2-header-row {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: start;
    gap: 20px;
  }

  .dispatch-pricing-v2-header-brand {
    display: flex;
    align-items: center;
    align-self: stretch;
    padding-right: 20px;
  }

  .dispatch-pricing-v2-header-copy {
    min-width: 0;
    display: grid;
    gap: 4px;
    padding-left: 20px;
    border-left: 1px solid rgba(169, 180, 183, 0.2);
  }

  .dispatch-modal__title-accent {
    min-height: 32px;
    padding: 0 16px;
    border-radius: 999px;
    background: #dbeafa;
    color: #0f62bc;
    font-family: 'Public Sans', sans-serif;
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 0.2em;
    text-transform: uppercase;
  }

  .dispatch-modal__header-divider {
    display: none;
  }

  .dispatch-modal__header--pricing-v2 .dispatch-modal__title {
    color: #2a3437;
    font-family: 'Public Sans', sans-serif;
    font-size: 24px;
    font-weight: 700;
    line-height: 32px;
    letter-spacing: 0;
  }

  .dispatch-modal__header--pricing-v2 .dispatch-modal__subtitle {
    max-width: 672px;
    margin-top: 0;
    color: #566164;
    font-size: 14px;
    line-height: 20px;
  }

  .dispatch-modal__close.dispatch-modal__close--v2 {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    background: transparent;
    color: #2a3437;
  }

  .dispatch-modal__close.dispatch-modal__close--v2:hover {
    background: rgba(42, 52, 55, 0.06);
  }

  .dispatch-pricing-v2-header-meta {
    position: absolute;
    top: 24px;
    right: 80px;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 8px;
    max-width: 420px;
  }

  .dispatch-pricing-v2-header-meta #costDistanceContainer {
    display: contents;
  }

  .dispatch-modal__body--pricing-v2 {
    background: #f8fafb;
  }

  .dispatch-pricing-form {
    height: 100%;
  }

  .dispatch-pricing-v2-main {
    padding: 0;
    gap: 0;
  }

  .dispatch-pricing-v2-banner {
    margin-top: 24px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 16px;
    padding: 12px 16px;
    border-radius: 4px;
    background: #f0f4f6;
  }

  .dispatch-pricing-v2-banner-main,
  .dispatch-pricing-v2-banner-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0;
    min-width: 0;
  }

  .dispatch-pricing-v2-banner-main {
    flex: 1;
  }

  .dispatch-pricing-v2-banner-item,
  .dispatch-pricing-v2-banner-item--compact,
  .dispatch-pricing-v2-banner-status {
    min-height: 20px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding-right: 16px;
    margin-right: 16px;
    border-right: 1px solid rgba(169, 180, 183, 0.2);
    color: #2a3437;
    font-size: 14px;
    line-height: 20px;
    font-weight: 500;
    white-space: nowrap;
  }

  .dispatch-pricing-v2-banner-item--compact {
    color: #566164;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-banner-item .material-symbols-outlined {
    font-size: 13px;
    color: #566164;
  }

  .dispatch-pricing-v2-banner-status {
    min-height: auto;
    padding: 2px 8px;
    margin-right: 0;
    border-right: none;
    border-radius: 2px;
    background: #d4e4f8;
    color: #445364;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-banner-actions {
    justify-content: flex-end;
    gap: 8px;
    flex: 1;
  }

  .dispatch-pricing-v2-banner-chip {
    min-height: 25px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 2px;
    background: #e1eaec;
    color: #566164;
    font-size: 11px;
    font-weight: 500;
    line-height: 16px;
    white-space: nowrap;
  }

  .dispatch-pricing-v2-banner-chip::before {
    font-family: 'Material Symbols Outlined';
    font-size: 12px;
    line-height: 1;
  }

  #costServiceModeBadge::before {
    content: "home_repair_service";
  }

  #costTruckBadge::before {
    content: "local_shipping";
  }

  #costDistanceBadge::before {
    content: "bolt";
  }

  #costDistanceBadge[data-state="travel"] {
    background: #cfe6f2;
    color: #40555f;
    font-weight: 600;
  }

  .dispatch-pricing-v2-content-grid {
    grid-template-columns: minmax(0, 767px) minmax(0, 413px);
    gap: 0;
    border-top: 1px solid rgba(169, 180, 183, 0.1);
  }

  .dispatch-pricing-v2-editor {
    padding: 32px 24px 32px 32px;
    background: #f8fafb;
  }

  .dispatch-pricing-v2-summary {
    padding: 20px 28px 24px;
    border-left: 1px solid rgba(169, 180, 183, 0.1);
    background: rgba(240, 244, 246, 0.5);
  }

  .dispatch-pricing-v2-summary-card {
    position: static;
    display: flex;
    flex-direction: column;
    height: 100%;
    max-height: none;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
  }

  .dispatch-pricing-v2-wizard-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 20px;
  }

  .dispatch-pricing-v2-wizard-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    color: #0d7cc1;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.18em;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-wizard-kicker::before {
    content: "";
    width: 26px;
    height: 1px;
    background: rgba(13, 124, 193, 0.35);
  }

  .dispatch-pricing-v2-wizard-title {
    margin: 0;
    color: #14232b;
    font-family: 'DM Sans', sans-serif;
    font-size: clamp(1.45rem, 2vw, 1.9rem);
    font-weight: 800;
    letter-spacing: -0.04em;
  }

  .dispatch-pricing-v2-wizard-copy {
    max-width: 560px;
    margin: 10px 0 0;
    color: #566164;
    font-size: 0.94rem;
    line-height: 1.65;
  }

  .dispatch-pricing-v2-wizard-badge {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 74px;
    min-height: 38px;
    padding: 0 16px;
    border-radius: 999px;
    background: #ffffff;
    border: 1px solid rgba(169, 180, 183, 0.36);
    color: #2a3437;
    font-size: 0.86rem;
    font-weight: 700;
    box-shadow: 0 12px 30px rgba(42, 52, 55, 0.05);
  }

  .dispatch-pricing-v2-progress {
    margin-bottom: 28px;
  }

  .dispatch-pricing-v2-progress-track {
    width: 100%;
    height: 8px;
    margin-bottom: 16px;
    border-radius: 999px;
    background: rgba(169, 180, 183, 0.18);
    overflow: hidden;
  }

  .dispatch-pricing-v2-progress-fill {
    height: 100%;
    width: 50%;
    border-radius: inherit;
    background: linear-gradient(90deg, #0d7cc1 0%, #37a1ef 100%);
    transition: width 220ms ease;
  }

  .dispatch-pricing-v2-flow {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-bottom: 0;
  }

  .dispatch-pricing-v2-flow-step {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0;
    border: 0;
    background: transparent;
    color: #566164;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: color 0.18s ease;
  }

  .dispatch-pricing-v2-flow-step:hover {
    color: #0d7cc1;
  }

  .dispatch-pricing-v2-flow-step__index {
    width: 24px;
    height: 24px;
    border: 1px solid #a9b4b7;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 600;
    color: #566164;
  }

  .dispatch-pricing-v2-flow-step.is-active {
    color: #2a3437;
  }

  .dispatch-pricing-v2-flow-step.is-active .dispatch-pricing-v2-flow-step__index {
    border-color: #2a3437;
    background: #2a3437;
    color: #ffffff;
  }

  .dispatch-pricing-v2-flow-step.is-complete {
    color: #0f9f7c;
  }

  .dispatch-pricing-v2-flow-step.is-complete .dispatch-pricing-v2-flow-step__index {
    border-color: rgba(15, 159, 124, 0.24);
    background: rgba(15, 159, 124, 0.12);
    color: #0f9f7c;
  }

  .dispatch-pricing-v2-flow-divider {
    width: 48px;
    height: 1px;
    background: rgba(169, 180, 183, 0.3);
  }

  .dispatch-pricing-v2-step-panel[hidden] {
    display: none !important;
  }

  .dispatch-pricing-v2-step-panel.is-active {
    display: block;
  }

  .dispatch-pricing-v2-section {
    margin-bottom: 32px;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
  }

  .dispatch-pricing-v2-section:last-child {
    margin-bottom: 0;
  }

  .dispatch-pricing-v2-step-panel[data-cost-step-panel="2"] .dispatch-pricing-v2-section + .dispatch-pricing-v2-section {
    margin-top: 32px;
  }

  .dispatch-pricing-v2-section-head {
    align-items: flex-end;
    margin-bottom: 16px;
  }

  .dispatch-pricing-v2-section-copy,
  .dispatch-pricing-v2-section-copy--fees {
    gap: 0;
  }

  .dispatch-pricing-v2-section-ordinal {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .dispatch-pricing-v2-section-number {
    color: rgba(169, 180, 183, 0.2);
    font-family: 'Public Sans', sans-serif;
    font-size: 30px;
    font-weight: 900;
    letter-spacing: -0.05em;
    line-height: 36px;
  }

  .dispatch-pricing-v2-section-heading-group {
    display: grid;
    align-items: start;
  }

  .dispatch-pricing-v2-section-kicker {
    min-height: auto;
    padding: 0;
    border-radius: 0;
    background: transparent;
    color: #2a3437;
    font-family: 'Public Sans', sans-serif;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.025em;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-section-count,
  .dispatch-pricing-v2-section-head .badge {
    width: fit-content;
    min-height: 16px;
    padding: 0 6px;
    margin-top: 1px;
    border-radius: 2px;
    background: #d9e4e8 !important;
    color: #566164 !important;
    font-size: 10px;
    font-weight: 600;
    line-height: 15px;
    letter-spacing: 0;
    text-transform: none;
  }

  .dispatch-pricing-v2-section-title,
  .dispatch-pricing-v2-section-hint {
    display: none;
  }

  .dispatch-pricing-v2-inline-add {
    min-height: auto;
    padding: 6px 12px;
    border: 0;
    border-radius: 4px;
    background: transparent;
    color: #516071;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-inline-add .material-symbols-outlined {
    font-size: 10px;
  }

  .dispatch-pricing-v2-inline-add--primary {
    color: #2171cc;
  }

  .dispatch-pricing-v2-inline-add:hover {
    background: rgba(81, 96, 113, 0.06);
    transform: none;
  }

  .dispatch-pricing-v2-labor-catalog {
    display: grid;
    gap: 14px;
    margin-bottom: 16px;
    padding: 16px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.72);
    border: 1px solid rgba(169, 180, 183, 0.18);
  }

  .dispatch-pricing-v2-cascade-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    align-items: start;
  }

  .dispatch-pricing-v2-picker-field {
    display: grid;
    gap: 6px;
    min-width: 0;
    position: relative;
  }

  .dispatch-pricing-v2-select--picker {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    min-height: 44px;
    padding: 0 14px;
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 12px;
    background: #ffffff;
    font-size: 0.92rem;
    font-weight: 600;
    color: #2a3437;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .dispatch-pricing-v2-select--picker:disabled {
    background: #f8fafc;
    color: #94a3b8;
    cursor: not-allowed;
  }

  .dispatch-pricing-v2-select--native {
    display: none;
  }

  .dispatch-search-picker {
    position: relative;
    width: 100%;
    max-width: 100%;
    min-width: 0;
  }

  .dispatch-search-picker__trigger {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    min-height: 44px;
    padding: 0 14px;
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 12px;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    font-size: 0.92rem;
    font-weight: 600;
    color: #2a3437;
    text-align: left;
    transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
  }

  .dispatch-search-picker__trigger:hover {
    border-color: rgba(33, 113, 204, 0.22);
  }

  .dispatch-search-picker.is-open .dispatch-search-picker__trigger {
    border-color: rgba(33, 113, 204, 0.28);
    box-shadow: 0 0 0 3px rgba(33, 113, 204, 0.08);
  }

  .dispatch-search-picker__trigger:disabled {
    background: #f8fafc;
    color: #94a3b8;
    cursor: not-allowed;
    box-shadow: none;
  }

  .dispatch-search-picker__label {
    flex: 1 1 auto;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .dispatch-search-picker__icon {
    flex-shrink: 0;
    color: #64748b;
    transition: transform 0.18s ease, color 0.18s ease;
  }

  .dispatch-search-picker.is-open .dispatch-search-picker__icon {
    transform: rotate(180deg);
    color: #2171cc;
  }

  .dispatch-search-picker__panel {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    z-index: 36;
    display: grid;
    gap: 8px;
    padding: 10px;
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, 0.18);
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
    backdrop-filter: blur(14px);
  }

  .dispatch-search-picker__panel[hidden] {
    display: none;
  }

  .dispatch-search-picker__searchbox {
    min-height: 42px;
    padding: 0 12px;
    border-radius: 12px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    background: #f8fafc;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .dispatch-search-picker__searchbox .material-symbols-outlined {
    font-size: 18px;
    color: #64748b;
  }

  .dispatch-search-picker__search {
    width: 100%;
    border: none;
    outline: none;
    background: transparent;
    color: #0f172a;
    font-size: 0.9rem;
    font-weight: 600;
  }

  .dispatch-search-picker__search::placeholder {
    color: #94a3b8;
    font-weight: 500;
  }

  .dispatch-search-picker__options {
    max-height: 248px;
    overflow-y: auto;
    display: grid;
    gap: 4px;
    padding-right: 2px;
  }

  .dispatch-search-picker__options::-webkit-scrollbar {
    width: 8px;
  }

  .dispatch-search-picker__options::-webkit-scrollbar-thumb {
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.42);
  }

  .dispatch-search-picker__option {
    width: 100%;
    padding: 10px 12px;
    border: none;
    border-radius: 12px;
    background: transparent;
    color: #1f2937;
    text-align: left;
    font-size: 0.88rem;
    line-height: 1.45;
    transition: background 0.18s ease, color 0.18s ease;
  }

  .dispatch-search-picker__option:hover {
    background: rgba(241, 245, 249, 0.96);
  }

  .dispatch-search-picker__option.is-selected {
    background: rgba(219, 234, 254, 0.98);
    color: #1d4ed8;
    font-weight: 700;
  }

  .dispatch-search-picker__empty {
    padding: 10px 12px;
    border-radius: 12px;
    background: rgba(248, 250, 252, 0.96);
    color: #64748b;
    font-size: 0.84rem;
    line-height: 1.45;
  }

  .dispatch-pricing-v2-labor-catalog-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .dispatch-pricing-v2-labor-note {
    min-width: 0;
    display: grid;
    gap: 4px;
  }

  .dispatch-pricing-v2-labor-note__title {
    color: #2a3437;
    font-size: 0.88rem;
    font-weight: 700;
    line-height: 1.5;
  }

  .dispatch-pricing-v2-labor-note__meta {
    color: #566164;
    font-size: 0.82rem;
    line-height: 1.55;
  }

  .dispatch-pricing-v2-labor-list,
  .dispatch-pricing-v2-parts-list {
    display: grid;
    gap: 12px;
  }

  .dispatch-pricing-v2-labor-row,
  .dispatch-pricing-v2-part-card {
    background: #f0f4f6;
    border-left: 4px solid #4d626c;
    border-radius: 4px;
    box-shadow: none;
  }

  .dispatch-pricing-v2-labor-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 128px 24px;
    gap: 16px;
    align-items: center;
    padding: 16px 16px 16px 20px;
  }

  .dispatch-pricing-v2-labor-main {
    display: grid;
    gap: 4px;
    min-width: 0;
  }

  .dispatch-pricing-v2-labor-row-meta {
    color: #566164;
    font-size: 11px;
    line-height: 1.55;
  }

  .dispatch-pricing-v2-labor-price {
    width: 100%;
    display: inline-flex;
    align-items: baseline;
    justify-content: flex-end;
    gap: 4px;
    color: #dc2626;
    font-size: 20px;
    font-weight: 700;
    line-height: 1.2;
    white-space: nowrap;
  }

  .dispatch-pricing-v2-labor-price__suffix {
    font-size: 14px;
    font-weight: 700;
    color: inherit;
  }

  .dispatch-pricing-v2-labor-col,
  .dispatch-pricing-v2-part-col {
    display: grid;
    gap: 4px;
    min-width: 0;
  }

  .dispatch-pricing-v2-field-label {
    color: #566164;
    font-size: 10px;
    font-weight: 600;
    line-height: 15px;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-inline-input {
    width: 100%;
    padding: 0;
    border: 0;
    background: transparent;
    outline: none;
    color: #2a3437;
    font-size: 14px;
    font-weight: 500;
    line-height: 20px;
  }

  .dispatch-pricing-v2-inline-input::placeholder {
    color: #6b7280;
  }

  .dispatch-pricing-v2-inline-input--price {
    text-align: right;
    font-weight: 600;
  }

  .dispatch-pricing-v2-part-card {
    padding: 20px 20px 20px 24px;
  }

  .dispatch-pricing-v2-part-card-inner {
    display: grid;
    grid-template-columns: minmax(0, 1.8fr) minmax(90px, 0.8fr) minmax(90px, 0.8fr) minmax(110px, 0.9fr) 20px;
    gap: 24px;
    align-items: start;
  }

  .dispatch-pricing-v2-part-main {
    display: grid;
    gap: 4px;
  }

  .dispatch-pricing-v2-part-title {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    line-height: 20px;
    letter-spacing: 0;
  }

  .dispatch-pricing-v2-part-meta {
    color: #566164;
    font-size: 11px;
    line-height: 16.5px;
  }

  .dispatch-pricing-v2-part-card .dispatch-pricing-v2-input-dark,
  .dispatch-pricing-v2-labor-row .dispatch-pricing-v2-input-dark {
    height: auto;
    min-height: 0;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
    color: #2a3437;
  }

  .dispatch-pricing-v2-part-card .dispatch-pricing-v2-input-dark:focus,
  .dispatch-pricing-v2-labor-row .dispatch-pricing-v2-input-dark:focus {
    box-shadow: none;
  }

  .dispatch-pricing-v2-stepper {
    gap: 8px;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
  }

  .dispatch-pricing-v2-stepper-btn {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    border: 0;
    background: #ffffff;
    color: #2a3437;
    box-shadow: none;
  }

  .dispatch-pricing-v2-stepper-btn:hover {
    background: rgba(42, 52, 55, 0.08);
  }

  .dispatch-pricing-v2-stepper .js-line-quantity {
    width: 24px !important;
    text-align: center;
    font-weight: 600;
  }

  .dispatch-pricing-v2-select {
    width: 100%;
    min-height: 20px;
    padding: 0;
    border: 0;
    background: transparent;
    color: #2a3437;
    font-size: 14px;
    font-weight: 500;
    outline: none;
    appearance: auto;
  }

  .dispatch-pricing-v2-part-remove,
  .dispatch-pricing-v2-labor-remove,
  .dispatch-pricing-v2-remove-btn {
    width: 12px;
    height: 13.5px;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    color: #a9b4b7;
  }

  .dispatch-pricing-v2-part-remove:hover,
  .dispatch-pricing-v2-labor-remove:hover,
  .dispatch-pricing-v2-remove-btn:hover {
    background: transparent;
    color: #566164;
  }

  .dispatch-pricing-v2-search-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 8px;
    align-items: center;
  }

  .dispatch-pricing-v2-searchbox {
    min-height: 0;
    padding: 9px 16px 10px 40px;
    border: 0;
    border-radius: 4px;
    background: #f0f4f6;
    box-shadow: none;
  }

  .dispatch-pricing-v2-searchbox:focus-within {
    box-shadow: 0 0 0 1px rgba(33, 113, 204, 0.18);
    border-color: transparent;
  }

  .dispatch-pricing-v2-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #6b7280;
  }

  .dispatch-pricing-v2-search-input {
    font-size: 14px;
    font-weight: 400;
    color: #6b7280;
  }

  .dispatch-pricing-v2-search-action {
    min-height: 0;
    padding: 10px 16px;
    border-radius: 4px;
    background: #e1eaec;
    color: #2a3437;
    font-size: 12px;
    font-weight: 600;
    text-transform: none;
    letter-spacing: 0;
  }

  .dispatch-pricing-v2-search-action:hover {
    background: #d8e2e6;
    color: #2a3437;
  }

  .dispatch-pricing-v2-inline-status {
    margin: 8px 0 0;
  }

  .dispatch-part-catalog__status {
    color: #566164;
    font-size: 12px;
    line-height: 18px;
  }

  .dispatch-pricing-v2-fees-section {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }

  .dispatch-pricing-v2-fee-card {
    padding: 12px 16px;
    border: 0;
    border-radius: 0;
    background: #f0f4f6;
  }

  .dispatch-pricing-v2-fee-title {
    margin: 0 0 6px;
    font-family: 'Inter', sans-serif;
    color: #566164;
    font-size: 10px;
    font-weight: 600;
    line-height: 15px;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-fee-input {
    height: auto;
    padding: 0 16px 0 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
    color: #2a3437;
    font-size: 14px;
    font-weight: 500;
  }

  .dispatch-pricing-v2-fee-input::placeholder {
    color: #566164;
  }

  .dispatch-pricing-v2-fee-input:focus {
    box-shadow: none;
  }

  .dispatch-pricing-v2-fee-prefix {
    right: 0;
    color: #566164;
    font-size: 14px;
    font-weight: 500;
  }

  .dispatch-pricing-v2-fee-readonly {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .dispatch-pricing-v2-fee-value {
    font-family: 'Inter', sans-serif;
    color: #2a3437;
    font-size: 14px;
    font-weight: 600;
    line-height: 20px;
  }

  .dispatch-pricing-v2-fee-chip {
    min-height: 20px;
    padding: 2px 8px;
    border-radius: 12px;
    background: #cfe6f2;
    color: #40555f;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: -0.05em;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-fee-hint {
    margin-top: 6px !important;
    color: #566164 !important;
    font-size: 11px;
    line-height: 16px;
  }

  .dispatch-pricing-v2-summary-eyebrow {
    color: #566164;
    font-family: 'Public Sans', sans-serif;
    font-size: 9px;
    font-weight: 900;
    letter-spacing: 0.2em;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-summary-header {
    display: grid;
    gap: 12px;
    padding: 0 0 14px;
  }

  .dispatch-pricing-v2-summary-title {
    color: #2a3437;
    font-family: 'Public Sans', sans-serif;
    font-size: 20px;
    font-weight: 700;
    line-height: 28px;
  }

  .dispatch-pricing-v2-summary-status {
    width: fit-content;
    min-height: 24px;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.1em;
  }

  .dispatch-pricing-v2-summary-note {
    display: none;
  }

  .dispatch-pricing-v2-summary-list {
    gap: 14px;
    padding-top: 12px;
    flex-shrink: 0;
  }

  .dispatch-pricing-v2-summary-row {
    padding: 0;
    border-radius: 0;
    background: transparent;
    color: #566164;
    font-size: 14px;
    font-weight: 400;
    line-height: 20px;
  }

  .dispatch-pricing-v2-summary-row strong {
    font-family: 'Inter', sans-serif;
    font-weight: 600;
  }

  .dispatch-pricing-v2-note-block {
    display: none;
  }

  .dispatch-pricing-v2-note-label {
    color: #566164;
    font-size: 10px;
    font-weight: 600;
    line-height: 15px;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-note-textarea {
    min-height: 68px;
    padding: 13px;
    border: 1px solid rgba(169, 180, 183, 0.2);
    border-radius: 4px;
    background: #ffffff;
    color: #6b7280;
    font-size: 14px;
    line-height: 20px;
    resize: vertical;
  }

  .dispatch-pricing-v2-note-textarea:focus {
    border-color: rgba(33, 113, 204, 0.26);
    box-shadow: 0 0 0 2px rgba(33, 113, 204, 0.08);
  }

  .dispatch-pricing-v2-total-card {
    position: relative;
    margin-top: 12px;
    display: grid;
    gap: 8px;
    flex-shrink: 0;
    min-height: 148px;
    padding: 20px;
    border-radius: 8px;
    background: #2a3437;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .dispatch-pricing-v2-total-card::after {
    content: "";
    position: absolute;
    right: -16px;
    bottom: -16px;
    width: 90px;
    height: 100px;
    background:
      linear-gradient(0deg, rgba(255,255,255,0.08), rgba(255,255,255,0.08)),
      linear-gradient(90deg, transparent 0 22%, rgba(255,255,255,0.08) 22% 30%, transparent 30% 100%);
    opacity: 0.6;
    border-radius: 8px;
    pointer-events: none;
  }

  .dispatch-pricing-v2-total-card__label {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    color: rgba(255, 255, 255, 0.7);
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.15em;
    text-transform: uppercase;
  }

  .dispatch-pricing-v2-total-card__label .material-symbols-outlined {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.2);
    font-size: 14px;
    color: #ffffff;
  }

  .dispatch-pricing-v2-total-card__value {
    position: relative;
    z-index: 1;
    display: block;
    margin-top: 0;
    color: #ffffff;
    font-family: 'Public Sans', sans-serif;
    font-size: 36px;
    font-weight: 900;
    line-height: 40px;
    letter-spacing: -0.025em;
  }

  .dispatch-pricing-v2-total-card__hint {
    position: relative;
    z-index: 1;
    margin-top: 0;
    color: rgba(255, 255, 255, 0.7);
    font-size: 12px;
    line-height: 18px;
    max-width: 240px;
  }

  .dispatch-pricing-v2-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 16px;
    flex-shrink: 0;
    padding: 16px 28px;
    border-top: 1px solid rgba(169, 180, 183, 0.14);
    background: rgba(248, 250, 251, 0.96);
    box-shadow: 0 -10px 24px rgba(42, 52, 55, 0.05);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
  }

  .dispatch-pricing-v2-footer-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    width: 100%;
  }

  .dispatch-pricing-v2-btn {
    min-height: 36px;
    padding: 8px 24px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
  }

  .dispatch-pricing-v2-btn--ghost {
    background: transparent;
    color: #566164;
  }

  .dispatch-pricing-v2-btn--ghost:hover {
    background: rgba(42, 52, 55, 0.05);
  }

  .dispatch-pricing-v2-btn--primary {
    background: #2a3437;
    color: #ffffff;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
  }

  .dispatch-pricing-v2-btn--primary:hover {
    background: #334145;
    transform: none;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
  }

  .dispatch-pricing-v2-btn .material-symbols-outlined {
    font-size: 14px;
  }

  @media (max-width: 991.98px) {
    .dispatch-modal--pricing-v2 .modal-dialog {
      margin: 12px;
      height: auto;
    }

    .dispatch-pricing-v2-header-row {
      grid-template-columns: 1fr auto;
      gap: 16px;
    }

    .dispatch-pricing-v2-header-brand {
      grid-column: 1 / -1;
      padding-right: 0;
      padding-bottom: 0;
    }

    .dispatch-pricing-v2-header-copy {
      padding-left: 0;
      border-left: 0;
    }

    .dispatch-pricing-v2-header-meta {
      position: static;
      max-width: none;
      grid-column: 1 / -1;
      justify-content: flex-start;
      margin-top: 10px;
    }

    .dispatch-pricing-v2-banner,
    .dispatch-pricing-v2-content-grid,
    .dispatch-pricing-v2-search-row,
    .dispatch-pricing-v2-fees-section {
      grid-template-columns: 1fr;
      display: grid;
    }

    .dispatch-pricing-v2-banner {
      align-items: stretch;
    }

    .dispatch-pricing-v2-banner-main,
    .dispatch-pricing-v2-banner-actions {
      gap: 8px;
    }

    .dispatch-pricing-v2-banner-item,
    .dispatch-pricing-v2-banner-item--compact,
    .dispatch-pricing-v2-banner-status {
      margin-right: 0;
      padding-right: 0;
      border-right: 0;
    }

    .dispatch-pricing-v2-editor,
    .dispatch-pricing-v2-summary {
      padding: 20px;
      height: auto;
      overflow: visible;
      border-left: 0;
    }

    .dispatch-pricing-v2-labor-row,
    .dispatch-pricing-v2-part-card-inner {
      grid-template-columns: 1fr;
    }

    .dispatch-pricing-v2-cascade-grid,
    .dispatch-pricing-v2-labor-catalog-footer {
      grid-template-columns: 1fr;
      display: grid;
    }

    .dispatch-pricing-v2-footer {
      padding: 14px 20px;
      justify-content: stretch;
    }

    .dispatch-pricing-v2-footer-actions {
      width: 100%;
      flex-wrap: wrap;
      justify-content: stretch;
    }

    .dispatch-pricing-v2-btn {
      flex: 1;
      justify-content: center;
    }
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
      grid-template-areas: none;
    }

    .dispatch-line-item__grid.is-parts .dispatch-line-item__field--description,
    .dispatch-line-item__grid.is-parts .dispatch-line-item__field--price,
    .dispatch-line-item__grid.is-parts .dispatch-line-item__field--quantity,
    .dispatch-line-item__grid.is-parts .dispatch-line-item__field--warranty,
    .dispatch-line-item__grid.is-parts .dispatch-line-item__remove {
      grid-area: auto;
    }

    .dispatch-part-catalog__toolbar {
      grid-template-columns: 1fr;
    }

    .dispatch-part-suggestion {
      grid-template-columns: 52px minmax(0, 1fr);
    }

    .dispatch-part-suggestion__aside {
      grid-column: 2;
      justify-items: start;
    }

    .dispatch-part-option {
      grid-template-columns: auto minmax(0, 1fr);
    }

    .dispatch-part-option__thumb,
    .dispatch-part-option__price {
      grid-column: 2;
    }

    .dispatch-line-item__grid .dispatch-line-item__remove {
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

  .dispatch-pay-option__copy {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .dispatch-pay-option__copy strong {
    font-size: 0.98rem;
    font-weight: 800;
    color: var(--dispatch-text);
  }

  .dispatch-pay-option__copy small {
    font-size: 0.82rem;
    font-weight: 600;
    line-height: 1.5;
    color: var(--dispatch-muted);
  }

  .dispatch-pay-option.is-active .dispatch-pay-option__card {
    border-color: var(--dispatch-primary);
    background: rgba(13, 124, 193, 0.08);
    box-shadow: 0 16px 28px rgba(13, 124, 193, 0.14);
  }

  .dispatch-pay-option.is-active .dispatch-pay-option__copy small {
    color: var(--dispatch-text);
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
  /* Figma redesign: job board */
  .worker-main {
    background: #f6f8fb;
  }

  .dispatch-shell {
    max-width: 1080px;
    padding: 40px 28px 48px;
  }

  .dispatch-shell::before,
  .dispatch-shell::after,
  .dispatch-hero,
  .dispatch-toolbar {
    display: none;
  }

  .dispatch-board {
    position: relative;
    z-index: 1;
    padding-top: 46px;
  }

  .dispatch-board-topbar {
    position: fixed;
    top: 0;
    left: 240px;
    right: 0;
    z-index: 140;
    margin-bottom: 0;
    padding: 0 28px;
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(38, 174, 235, 0.65);
    border-bottom-color: rgba(189, 200, 209, 0.45);
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    backdrop-filter: blur(18px);
  }

  .dispatch-board-topbar__inner {
    display: flex;
    align-items: stretch;
    justify-content: space-between;
    gap: 16px;
    overflow: visible;
    min-width: 0;
    max-width: 1080px;
    margin: 0 auto;
  }

  .dispatch-board-topbar__inner::-webkit-scrollbar {
    display: none;
  }

  .dispatch-board-topbar__nav {
    min-width: 0;
    flex: 1 1 auto;
    display: flex;
    align-items: stretch;
    overflow-x: auto;
    scrollbar-width: none;
  }

  .dispatch-board-topbar__nav::-webkit-scrollbar {
    display: none;
  }

  .dispatch-board-topbar__title {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    padding: 0 24px;
    min-height: 62px;
    color: #191c1d;
    font-family: 'Inter', sans-serif;
    font-size: 16px;
    font-weight: 700;
    white-space: nowrap;
  }

  .dispatch-board-topbar__tabs {
    display: flex;
    align-items: stretch;
    overflow-x: auto;
    scrollbar-width: none;
    border-left: 1px solid rgba(226, 232, 240, 0.96);
  }

  .dispatch-board-topbar__tabs::-webkit-scrollbar {
    display: none;
  }

  .dispatch-board-topbar__tab {
    position: relative;
    min-height: 62px;
    padding: 0 22px;
    border: 0;
    background: transparent;
    color: #64748b;
    font-family: 'Inter', sans-serif;
    font-size: 16px;
    font-weight: 500;
    white-space: nowrap;
    transition: color 0.18s ease;
  }

  .dispatch-board-topbar__tab:hover {
    color: #0f172a;
  }

  .dispatch-board-topbar__tab.is-active {
    color: #191c1d;
    font-weight: 700;
  }

  .dispatch-board-topbar__tab.is-active::after {
    content: "";
    position: absolute;
    left: 22px;
    right: 22px;
    bottom: 0;
    height: 3px;
    border-radius: 999px;
    background: #26aeeb;
  }

  .dispatch-board-topbar__actions {
    position: relative;
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 9px 18px 9px 14px;
    border-left: 1px solid rgba(226, 232, 240, 0.96);
    background: #ffffff;
    min-height: 62px;
  }

  .dispatch-board-topbar__icon-btn {
    position: relative;
    width: 44px;
    height: 44px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.92);
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    transition: color 0.18s ease, border-color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
  }

  .dispatch-board-topbar__icon-btn:hover,
  .dispatch-board-topbar__icon-btn.is-active {
    color: #0d7cc1;
    border-color: rgba(38, 174, 235, 0.45);
    transform: translateY(-1px);
    box-shadow: 0 14px 28px rgba(13, 124, 193, 0.16);
  }

  .dispatch-board-topbar__icon-btn .material-symbols-outlined {
    font-size: 22px;
    font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
  }

  .dispatch-board-topbar__notification {
    position: relative;
  }

  .dispatch-board-topbar__notification-badge {
    position: absolute;
    top: 7px;
    right: 6px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 999px;
    background: #ef4444;
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 700;
    line-height: 1;
    box-shadow: 0 0 0 2px #ffffff;
  }

  .dispatch-board-topbar__notification-badge.is-hidden {
    display: none;
  }

  .dispatch-board-topbar__notification-menu {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    width: min(360px, calc(100vw - 32px));
    display: none;
    background: rgba(255, 255, 255, 0.98);
    border: 1px solid rgba(203, 213, 225, 0.88);
    border-radius: 24px;
    box-shadow: 0 26px 48px rgba(15, 23, 42, 0.14);
    overflow: hidden;
    backdrop-filter: blur(16px);
  }

  .dispatch-board-topbar__notification-menu.is-open {
    display: block;
  }

  .dispatch-board-topbar__notification-head,
  .dispatch-board-topbar__notification-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px;
    background: rgba(248, 250, 252, 0.92);
  }

  .dispatch-board-topbar__notification-head {
    border-bottom: 1px solid rgba(226, 232, 240, 0.88);
  }

  .dispatch-board-topbar__notification-head h3 {
    margin: 0;
    color: #0f172a;
    font-size: 15px;
    font-weight: 700;
  }

  .dispatch-board-topbar__notification-head p,
  .dispatch-board-topbar__notification-foot a {
    margin: 0;
    color: #64748b;
    font-size: 12px;
    text-decoration: none;
  }

  .dispatch-board-topbar__notification-mark {
    border: 0;
    background: transparent;
    color: #0d7cc1;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
  }

  .dispatch-board-topbar__notification-mark:hover {
    color: #095b91;
  }

  .dispatch-board-topbar__notification-list {
    max-height: 360px;
    overflow-y: auto;
  }

  .dispatch-board-topbar__notification-empty {
    padding: 28px 20px;
    display: grid;
    justify-items: center;
    gap: 10px;
    color: #64748b;
    text-align: center;
  }

  .dispatch-board-topbar__notification-empty .material-symbols-outlined {
    font-size: 28px;
    color: rgba(100, 116, 139, 0.8);
  }

  .dispatch-board-topbar__notification-item {
    display: block;
    padding: 15px 18px;
    border-bottom: 1px solid rgba(226, 232, 240, 0.82);
    color: inherit;
    text-decoration: none;
    background: rgba(255, 255, 255, 0.98);
    transition: background 0.18s ease;
  }

  .dispatch-board-topbar__notification-item:last-child {
    border-bottom: 0;
  }

  .dispatch-board-topbar__notification-item:hover {
    background: #f8fbff;
  }

  .dispatch-board-topbar__notification-item.is-unread {
    background: #f4faff;
  }

  .dispatch-board-topbar__notification-row {
    display: grid;
    grid-template-columns: 40px minmax(0, 1fr);
    gap: 12px;
    align-items: start;
  }

  .dispatch-board-topbar__notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    background: rgba(13, 124, 193, 0.12);
    color: #0d7cc1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .dispatch-board-topbar__notification-icon.is-warning {
    background: rgba(249, 115, 22, 0.12);
    color: #c2410c;
  }

  .dispatch-board-topbar__notification-icon.is-success {
    background: rgba(16, 185, 129, 0.12);
    color: #0f9f7c;
  }

  .dispatch-board-topbar__notification-icon.is-danger {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
  }

  .dispatch-board-topbar__notification-icon .material-symbols-outlined {
    font-size: 20px;
    font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
  }

  .dispatch-board-topbar__notification-copy {
    min-width: 0;
  }

  .dispatch-board-topbar__notification-copy strong {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #0f172a;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.4;
    margin-bottom: 6px;
  }

  .dispatch-board-topbar__notification-copy p {
    margin: 0 0 10px;
    color: #475569;
    font-size: 13px;
    line-height: 1.5;
  }

  .dispatch-board-topbar__notification-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }

  .dispatch-board-topbar__notification-chip {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(226, 232, 240, 0.65);
    color: #475569;
    font-size: 11px;
    font-weight: 600;
  }

  .dispatch-board-topbar__notification-unread-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #26aeeb;
    flex: 0 0 auto;
  }

  .dispatch-board-topbar__avatar {
    width: 44px;
    height: 44px;
    border-radius: 18px;
    overflow: hidden;
    background: linear-gradient(135deg, rgba(13, 124, 193, 0.16), rgba(13, 124, 193, 0.3));
    color: #0d7cc1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 800;
    text-decoration: none;
    box-shadow: 0 12px 24px rgba(13, 124, 193, 0.14);
  }

  .dispatch-board-topbar__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .dispatch-board__controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding-top: 14px;
    border-top: 1px solid rgba(189, 200, 209, 0.45);
    margin-bottom: 24px;
  }

  .dispatch-pagination {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 44px;
  }

  .dispatch-pagination__btn,
  .dispatch-pagination__page,
  .dispatch-pagination__ellipsis {
    min-width: 36px;
    height: 36px;
    border: 0;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    color: #191c1d;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.18s ease, color 0.18s ease, opacity 0.18s ease;
  }

  .dispatch-pagination__btn:hover,
  .dispatch-pagination__page:hover {
    background: rgba(0, 101, 140, 0.08);
    color: #00658c;
  }

  .dispatch-pagination__btn.is-disabled,
  .dispatch-pagination__page.is-disabled {
    opacity: 0.3;
    pointer-events: none;
  }

  .dispatch-pagination__page.is-active {
    background: #00658c;
    color: #ffffff;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  }

  .dispatch-scope-toggle {
    display: inline-flex;
    align-items: center;
    padding: 4px;
    border-radius: 4px;
    background: #edeeef;
  }

  .dispatch-scope-toggle__btn {
    min-width: 84px;
    height: 36px;
    border: 0;
    border-radius: 6px;
    padding: 0 20px;
    background: transparent;
    color: #3e4850;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.18s ease, box-shadow 0.18s ease, color 0.18s ease;
  }

  .dispatch-scope-toggle__btn.is-active {
    background: #ffffff;
    color: #00658c;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  }

  .dispatch-board-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 24px;
    padding-bottom: 8px;
  }

  .dispatch-board-empty {
    grid-column: 1 / -1;
    min-height: 280px;
    border: 1px solid rgba(189, 200, 209, 0.3);
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    display: grid;
    place-items: center;
    padding: 32px 24px;
    text-align: center;
    color: #475569;
  }

  .dispatch-board-empty .material-symbols-outlined {
    font-size: 38px;
    color: #94a3b8;
    margin-bottom: 12px;
  }

  .dispatch-board-empty h3 {
    margin: 0 0 8px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 20px;
    font-weight: 800;
    color: #191c1d;
  }

  .dispatch-board-empty p {
    margin: 0;
    max-width: 34rem;
    line-height: 1.6;
  }

  .dispatch-board-card {
    position: relative;
    overflow: hidden;
    min-height: 362px;
    padding: 33px;
    border: 1px solid rgba(189, 200, 209, 0.3);
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  }

  .dispatch-board-card--inprogress {
    border-color: rgba(38, 174, 235, 0.18);
  }

  .dispatch-board-card--payment {
    border-color: rgba(245, 158, 11, 0.26);
    box-shadow: 0 14px 34px rgba(161, 98, 7, 0.08);
  }

  .dispatch-board-card--payment .dispatch-board-card__status {
    background: #ffedd5;
    color: #9a3412;
  }

  .dispatch-board-card__status {
    position: absolute;
    top: 13px;
    right: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 20px;
    padding: 2.5px 16px;
    border-radius: 12px;
    background: #bbe2fe;
    color: #3f657d;
    font-family: 'Inter', sans-serif;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }

  .dispatch-board-card__content {
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  .dispatch-board-card__header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding-bottom: 24px;
  }

  .dispatch-board-card__icon {
    width: 56px;
    height: 56px;
    flex: 0 0 56px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    background: #c6e7ff;
    color: #00658c;
  }

  .dispatch-board-card__icon .material-symbols-outlined {
    font-size: 26px;
    font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24;
  }

  .dispatch-board-card__summary {
    min-width: 0;
    flex: 1;
  }

  .dispatch-board-card__title {
    margin: 0;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 20px;
    line-height: 1.25;
    font-weight: 800;
    color: #191c1d;
  }

  .dispatch-board-card__meta-inline {
    margin-top: 3.5px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 12px;
    color: #3e4850;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
  }

  .dispatch-board-card__meta-inline .material-symbols-outlined {
    font-size: 14px;
    color: #3e4850;
  }

  .dispatch-board-card__meta-item {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    min-width: 0;
  }

  .dispatch-board-card__meta-dot {
    width: 4px;
    height: 4px;
    border-radius: 999px;
    background: #bdc8d1;
  }

  .dispatch-board-card__body {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding-bottom: 32px;
    margin-top: auto;
  }

  .dispatch-board-card__location {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    color: #3e4850;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    line-height: 1.45;
  }

  .dispatch-board-card__location .material-symbols-outlined {
    font-size: 20px;
    color: #26aeeb;
    margin-top: 1px;
  }

  .dispatch-board-note {
    border-left: 4px solid #e1921b;
    border-radius: 8px;
    background: rgba(255, 221, 184, 0.3);
    padding: 16px 16px 16px 20px;
  }

  .dispatch-board-note--info {
    border-left-color: #26aeeb;
    background: rgba(198, 231, 255, 0.32);
  }

  .dispatch-board-note--success {
    border-left-color: #3ba55d;
    background: rgba(219, 242, 225, 0.56);
  }

  .dispatch-board-note--danger {
    border-left-color: #dc2626;
    background: rgba(254, 226, 226, 0.6);
  }

  .dispatch-board-note__title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
    color: #533200;
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.35;
  }

  .dispatch-board-note--info .dispatch-board-note__title {
    color: #0b5e84;
  }

  .dispatch-board-note--success .dispatch-board-note__title {
    color: #1d6b35;
  }

  .dispatch-board-note--danger .dispatch-board-note__title {
    color: #991b1b;
  }

  .dispatch-board-note__title .material-symbols-outlined {
    font-size: 14px;
  }

  .dispatch-board-note__body {
    margin: 0;
    color: rgba(83, 50, 0, 0.8);
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    line-height: 1.45;
    font-style: italic;
  }

  .dispatch-board-note--info .dispatch-board-note__body {
    color: rgba(11, 94, 132, 0.86);
  }

  .dispatch-board-note--success .dispatch-board-note__body {
    color: rgba(29, 107, 53, 0.84);
    font-style: normal;
  }

  .dispatch-board-note--danger .dispatch-board-note__body {
    color: rgba(153, 27, 27, 0.82);
    font-style: normal;
  }

  .dispatch-board-payment {
    display: grid;
    gap: 12px;
    padding: 18px;
    border-radius: 18px;
    border: 1px solid rgba(245, 158, 11, 0.24);
    background: linear-gradient(180deg, rgba(255, 247, 237, 0.96), rgba(255, 255, 255, 0.98));
  }

  .dispatch-board-payment--transfer {
    border-color: rgba(37, 99, 235, 0.22);
    background: linear-gradient(180deg, rgba(239, 246, 255, 0.96), rgba(255, 255, 255, 0.98));
  }

  .dispatch-board-payment__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
  }

  .dispatch-board-payment__eyebrow {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(245, 158, 11, 0.14);
    color: #b45309;
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .dispatch-board-payment--transfer .dispatch-board-payment__eyebrow {
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
  }

  .dispatch-board-payment__total {
    margin-top: 8px;
    color: #191c1d;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 28px;
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.04em;
  }

  .dispatch-board-payment__method {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
    padding: 0 12px;
    border-radius: 999px;
    background: #ffffff;
    border: 1px solid rgba(245, 158, 11, 0.24);
    color: #9a3412;
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
  }

  .dispatch-board-payment--transfer .dispatch-board-payment__method {
    border-color: rgba(37, 99, 235, 0.22);
    color: #1d4ed8;
  }

  .dispatch-board-payment__stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
  }

  .dispatch-board-payment__stat {
    min-width: 0;
    padding: 12px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(226, 232, 240, 0.9);
  }

  .dispatch-board-payment__stat-label {
    display: block;
    color: #64748b;
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .dispatch-board-payment__stat-value {
    display: block;
    margin-top: 6px;
    color: #0f172a;
    font-family: 'Inter', sans-serif;
    font-size: 15px;
    font-weight: 800;
    line-height: 1.3;
  }

  .dispatch-board-payment__hint {
    margin: 0;
    color: #475569;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    line-height: 1.55;
  }

  .dispatch-board-card__footer {
    display: flex;
    align-items: stretch;
    gap: 12px;
    margin-top: auto;
  }

  .dispatch-board-card__action-main,
  .dispatch-board-card__action-secondary,
  .dispatch-board-card__action-icon {
    height: 40px;
    border: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 16px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease, color 0.18s ease;
  }

  .dispatch-board-card__action-main:hover,
  .dispatch-board-card__action-secondary:hover,
  .dispatch-board-card__action-icon:hover {
    transform: translateY(-1px);
  }

  .dispatch-board-card__action-main {
    flex: 1 1 0;
    color: #ffffff;
    background: linear-gradient(163deg, #00658c 0%, #26aeeb 100%);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  }

  .dispatch-board-card__action-main--warm {
    background: linear-gradient(163deg, #e1921b 0%, #f5b041 100%);
  }

  .dispatch-board-card__action-main--success {
    background: linear-gradient(163deg, #0f9f7c 0%, #32c39b 100%);
  }

  .dispatch-board-card__action-main--disabled,
  .dispatch-board-card__action-main:disabled {
    background: #cbd5e1;
    color: rgba(15, 23, 42, 0.65);
    pointer-events: none;
    box-shadow: none;
  }

  .dispatch-board-card__action-secondary {
    flex: 1 1 0;
    background: #edeeef;
    color: #3e4850;
  }

  .dispatch-board-card__action-icon {
    width: 40px;
    flex: 0 0 40px;
    padding: 0;
    background: #edeeef;
    color: #3e4850;
  }

  .dispatch-board-card__action-main .material-symbols-outlined,
  .dispatch-board-card__action-secondary .material-symbols-outlined,
  .dispatch-board-card__action-icon .material-symbols-outlined {
    font-size: 18px;
  }

  .dispatch-board-route {
    position: relative;
    min-height: 320px;
    margin-top: 32px;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    background:
      linear-gradient(120deg, rgba(255,255,255,0.08), rgba(255,255,255,0)),
      url('https://www.figma.com/api/mcp/asset/b518b917-2aac-4ad5-b6a2-653d678680ea') center center / cover no-repeat;
  }

  .dispatch-board-route[hidden] {
    display: none !important;
  }

  .dispatch-board-route__overlay {
    position: absolute;
    left: 24px;
    right: 24px;
    bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 25px;
    border: 1px solid rgba(255, 255, 255, 0.4);
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(12px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  }

  .dispatch-board-route__content {
    display: flex;
    align-items: center;
    gap: 16px;
    min-width: 0;
  }

  .dispatch-board-route__icon {
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: #00658c;
    color: #ffffff;
  }

  .dispatch-board-route__icon .material-symbols-outlined {
    font-size: 22px;
  }

  .dispatch-board-route__eyebrow {
    color: #26aeeb;
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }

  .dispatch-board-route__title {
    margin: 4px 0 0;
    font-family: 'Inter', sans-serif;
    font-size: 18px;
    line-height: 1.2;
    font-weight: 600;
    color: #0f172a;
  }

  .dispatch-board-route__meta {
    margin: 2px 0 0;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    line-height: 1.45;
    color: #475569;
  }

  .dispatch-board-route__action {
    min-width: 182px;
    height: 44px;
    border: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 24px;
    background: #00658c;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
  }

  .dispatch-board-route__action:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 24px rgba(0, 101, 140, 0.18);
  }

  @media (max-width: 1199.98px) {
    .dispatch-shell {
      max-width: none;
      padding-inline: 24px;
    }

    .dispatch-board-topbar {
      padding-inline: 24px;
    }
  }

  @media (max-width: 991.98px) {
    .dispatch-board-topbar__actions {
      gap: 10px;
      padding-inline: 14px;
    }

    .dispatch-board-topbar__title {
      padding-inline: 18px;
      min-height: 58px;
      font-size: 15px;
    }

    .dispatch-board-topbar__tab {
      padding-inline: 18px;
      min-height: 58px;
      font-size: 15px;
    }

    .dispatch-board-grid {
      grid-template-columns: 1fr;
    }

    .dispatch-board__controls,
    .dispatch-board-route__overlay {
      flex-direction: column;
      align-items: stretch;
    }

    .dispatch-board-route__action {
      width: 100%;
      min-width: 0;
    }
  }

  @media (max-width: 767.98px) {
    .dispatch-board-topbar {
      top: calc(env(safe-area-inset-top, 0px) + 92px);
      left: 0;
      padding-inline: 16px;
    }

    .dispatch-board-topbar__inner {
      gap: 10px;
    }

    .dispatch-board-topbar__actions {
      padding: 8px 12px;
      min-height: 58px;
    }

    .worker-main {
      margin-left: 0;
    }

    .dispatch-shell {
      padding: 24px 16px 40px;
    }

    .dispatch-board-card {
      min-height: auto;
      padding: 24px 20px;
    }

    .dispatch-board-card__footer {
      flex-wrap: wrap;
    }

    .dispatch-board-card__action-main,
    .dispatch-board-card__action-secondary {
      min-width: calc(50% - 6px);
    }

    .dispatch-board-route {
      min-height: 280px;
      margin-top: 24px;
    }

    .dispatch-board-route__overlay {
      left: 16px;
      right: 16px;
      bottom: 16px;
      padding: 18px;
    }
  }

  @media (max-width: 575.98px) {
    .dispatch-board-payment__stats {
      grid-template-columns: 1fr;
    }

    .dispatch-board-topbar__actions {
      gap: 8px;
      padding: 8px 10px 8px 8px;
    }

    .dispatch-board-topbar__icon-btn,
    .dispatch-board-topbar__avatar {
      width: 40px;
      height: 40px;
      border-radius: 14px;
    }

    .dispatch-board-topbar__notification-menu {
      right: -10px;
      width: min(340px, calc(100vw - 24px));
    }

    .dispatch-board-topbar {
      margin-bottom: 14px;
    }

    .dispatch-board-topbar__title {
      padding-inline: 16px;
      font-size: 14px;
    }

    .dispatch-board-topbar__tab {
      padding-inline: 16px;
      font-size: 14px;
    }

    .dispatch-board-card__header {
      padding-right: 68px;
    }

    .dispatch-board-card__title {
      font-size: 18px;
    }

    .dispatch-board-card__action-main,
    .dispatch-board-card__action-secondary,
    .dispatch-board-card__action-icon {
      width: 100%;
      flex: 1 1 100%;
    }

    .dispatch-pagination {
      width: 100%;
      justify-content: center;
      flex-wrap: wrap;
    }

    .dispatch-scope-toggle {
      width: 100%;
      justify-content: stretch;
    }

    .dispatch-scope-toggle__btn {
      flex: 1 1 0;
      min-width: 0;
    }
  }

  /* Figma board layout overrides */
  .dispatch-shell {
    max-width: 1180px;
    padding: 38px 28px 56px;
  }

  .dispatch-board {
    padding-top: 44px;
  }

  .dispatch-board-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 332px;
    gap: 24px;
    align-items: start;
  }

  .dispatch-board-main,
  .dispatch-board-side {
    min-width: 0;
  }

  .dispatch-board-side {
    position: sticky;
    top: 102px;
  }

  .dispatch-board-intro {
    display: grid;
    gap: 12px;
    margin: 0 0 18px;
  }

  .dispatch-board-intro__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: fit-content;
    min-height: 28px;
    padding: 0 12px;
    border-radius: 999px;
    background: rgba(13, 124, 193, 0.1);
    color: #0d7cc1;
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }

  .dispatch-board-intro__body {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
  }

  .dispatch-board-intro__copy {
    min-width: 0;
    max-width: 620px;
  }

  .dispatch-board-intro__title {
    margin: 0;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: clamp(1.8rem, 2.4vw, 2.3rem);
    line-height: 1.08;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: #0f172a;
  }

  .dispatch-board-intro__subtitle {
    margin: 10px 0 0;
    color: #64748b;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    line-height: 1.7;
  }

  .dispatch-board-intro__meta {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px;
  }

  .dispatch-board-intro__chip {
    display: inline-flex;
    align-items: center;
    min-height: 38px;
    padding: 0 14px;
    border-radius: 999px;
    background: #ffffff;
    border: 1px solid rgba(203, 213, 225, 0.9);
    color: #334155;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 700;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
  }

  .dispatch-board-intro__chip--status {
    background: linear-gradient(180deg, rgba(13, 124, 193, 0.12), rgba(255, 255, 255, 0.96));
    border-color: rgba(13, 124, 193, 0.16);
    color: #0b5e84;
  }

  .dispatch-board__controls {
    padding-top: 0;
    margin-bottom: 18px;
    border-top: 0;
  }

  .dispatch-board__controls-meta {
    color: #64748b;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 600;
  }

  .dispatch-scope-toggle {
    padding: 5px;
    border-radius: 999px;
    background: rgba(226, 232, 240, 0.8);
  }

  .dispatch-scope-toggle__btn {
    min-width: 102px;
    height: 38px;
    border-radius: 999px;
    padding: 0 16px;
    font-weight: 700;
  }

  .dispatch-board-grid {
    grid-template-columns: 1fr;
    gap: 18px;
    padding-bottom: 0;
  }

  .dispatch-board-card {
    min-height: 0;
    padding: 22px;
    border: 1px solid rgba(219, 228, 237, 0.96);
    border-radius: 22px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(249, 251, 255, 0.96));
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.06);
  }

  .dispatch-board-card__status {
    top: 20px;
    right: 22px;
    min-height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    background: rgba(13, 124, 193, 0.12);
    color: #0d7cc1;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: none;
  }

  .dispatch-board-card--pending .dispatch-board-card__status {
    background: rgba(249, 115, 22, 0.14);
    color: #c2410c;
  }

  .dispatch-board-card--upcoming .dispatch-board-card__status {
    background: rgba(13, 124, 193, 0.12);
    color: #0b5e84;
  }

  .dispatch-board-card--inprogress .dispatch-board-card__status {
    background: rgba(16, 185, 129, 0.14);
    color: #0f766e;
  }

  .dispatch-board-card--payment .dispatch-board-card__status {
    background: rgba(245, 158, 11, 0.14);
    color: #9a3412;
  }

  .dispatch-board-card--done .dispatch-board-card__status {
    background: rgba(16, 185, 129, 0.14);
    color: #047857;
  }

  .dispatch-board-card--cancelled .dispatch-board-card__status {
    background: rgba(239, 68, 68, 0.12);
    color: #b91c1c;
  }

  .dispatch-board-card__header {
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    padding: 0 76px 16px 0;
  }

  .dispatch-board-card__lead {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    min-width: 0;
    flex: 1 1 auto;
  }

  .dispatch-board-card__icon {
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    border-radius: 14px;
    background: linear-gradient(180deg, #e6f3ff, #d7efff);
    box-shadow: inset 0 0 0 1px rgba(13, 124, 193, 0.08);
  }

  .dispatch-board-card__icon .material-symbols-outlined {
    font-size: 24px;
  }

  .dispatch-board-card__summary {
    display: grid;
    gap: 8px;
  }

  .dispatch-board-card__eyebrow {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    min-height: 22px;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.05);
    color: #64748b;
    font-family: 'Inter', sans-serif;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .dispatch-board-card__title {
    font-size: 20px;
    line-height: 1.25;
    letter-spacing: -0.03em;
  }

  .dispatch-board-card__support {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 14px;
    color: #64748b;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 600;
  }

  .dispatch-board-card__support-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
  }

  .dispatch-board-card__support-item .material-symbols-outlined {
    font-size: 16px;
    color: #94a3b8;
  }

  .dispatch-board-card__schedule {
    display: grid;
    gap: 4px;
    justify-items: end;
    flex: 0 0 auto;
    text-align: right;
  }

  .dispatch-board-card__time {
    color: #0f172a;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 20px;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.03em;
  }

  .dispatch-board-card__date {
    color: #64748b;
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.4;
    text-transform: uppercase;
  }

  .dispatch-board-card__body {
    gap: 14px;
    padding-bottom: 18px;
    margin-top: 0;
  }

  .dispatch-board-card__info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }

  .dispatch-board-card__info {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    min-width: 0;
    padding: 12px 14px;
    border-radius: 16px;
    border: 1px solid rgba(226, 232, 240, 0.88);
    background: rgba(248, 250, 252, 0.92);
  }

  .dispatch-board-card__info--full {
    grid-column: 1 / -1;
  }

  .dispatch-board-card__info-icon {
    width: 32px;
    height: 32px;
    flex: 0 0 32px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    color: #0d7cc1;
    box-shadow: inset 0 0 0 1px rgba(13, 124, 193, 0.1);
  }

  .dispatch-board-card__info-icon .material-symbols-outlined {
    font-size: 18px;
  }

  .dispatch-board-card__info-copy {
    min-width: 0;
  }

  .dispatch-board-card__info-label {
    display: block;
    margin-bottom: 4px;
    color: #94a3b8;
    font-family: 'Inter', sans-serif;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .dispatch-board-card__info-value {
    display: block;
    color: #334155;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.55;
  }

  .dispatch-board-note {
    border-left: 0;
    padding: 14px 16px;
    border-radius: 16px;
    background: rgba(255, 249, 235, 0.9);
  }

  .dispatch-board-note--info {
    background: rgba(235, 245, 255, 0.92);
  }

  .dispatch-board-note--success {
    background: rgba(236, 253, 245, 0.94);
  }

  .dispatch-board-note--danger {
    background: rgba(254, 242, 242, 0.94);
  }

  .dispatch-board-note__title {
    margin-bottom: 6px;
  }

  .dispatch-board-payment {
    gap: 10px;
    padding: 15px;
    border-radius: 18px;
  }

  .dispatch-board-payment__total {
    font-size: 24px;
  }

  .dispatch-board-card__footer {
    gap: 10px;
    margin-top: auto;
  }

  .dispatch-board-card__action-main,
  .dispatch-board-card__action-secondary,
  .dispatch-board-card__action-icon {
    height: 42px;
    border-radius: 14px;
    font-size: 13px;
    font-weight: 700;
  }

  .dispatch-board-card__action-main {
    flex: 1 1 180px;
  }

  .dispatch-board-card__action-secondary {
    flex: 0 0 auto;
    padding: 0 18px;
    background: rgba(248, 250, 252, 0.94);
    border: 1px solid rgba(203, 213, 225, 0.94);
    color: #334155;
  }

  .dispatch-board-card__action-icon {
    width: 42px;
    flex: 0 0 42px;
    background: rgba(248, 250, 252, 0.94);
    border: 1px solid rgba(203, 213, 225, 0.94);
    color: #475569;
  }

  .dispatch-board__pagination-wrap {
    margin-top: 20px;
    display: flex;
    justify-content: center;
  }

  .dispatch-board__pagination-wrap[hidden] {
    display: none;
  }

  .dispatch-pagination {
    padding: 4px 6px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(226, 232, 240, 0.92);
    box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05);
  }

  .dispatch-pagination__btn,
  .dispatch-pagination__page,
  .dispatch-pagination__ellipsis {
    min-width: 34px;
    height: 34px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
  }

  .dispatch-board-route {
    min-height: 420px;
    margin-top: 0;
    border-radius: 26px;
    border: 1px solid rgba(219, 228, 237, 0.96);
    box-shadow: 0 20px 42px rgba(15, 23, 42, 0.08);
    background:
      linear-gradient(135deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0)),
      url('https://www.figma.com/api/mcp/asset/b518b917-2aac-4ad5-b6a2-653d678680ea') center center / cover no-repeat;
  }

  .dispatch-board-route__frame {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px;
  }

  .dispatch-board-route__label,
  .dispatch-board-route__badge {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: 0 12px;
    border-radius: 999px;
    backdrop-filter: blur(12px);
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .dispatch-board-route__label {
    background: rgba(255, 255, 255, 0.86);
    color: #0f172a;
  }

  .dispatch-board-route__badge {
    background: rgba(13, 124, 193, 0.9);
    color: #ffffff;
  }

  .dispatch-board-route__overlay {
    left: 18px;
    right: 18px;
    bottom: 18px;
    padding: 18px;
    border: 1px solid rgba(255, 255, 255, 0.56);
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(16px);
  }

  .dispatch-board-route__content {
    align-items: flex-start;
    gap: 14px;
  }

  .dispatch-board-route__icon {
    width: 44px;
    height: 44px;
    flex: 0 0 44px;
    border-radius: 14px;
  }

  .dispatch-board-route__eyebrow {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.08em;
  }

  .dispatch-board-route__title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 18px;
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: -0.03em;
  }

  .dispatch-board-route__location {
    margin: 6px 0 0;
    color: #334155;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.5;
  }

  .dispatch-board-route__meta {
    margin-top: 6px;
    font-size: 12px;
  }

  .dispatch-board-route__action {
    min-width: 152px;
    height: 42px;
    border-radius: 14px;
    padding: 0 18px;
    font-size: 13px;
    font-weight: 700;
  }

  @media (max-width: 1199.98px) {
    .dispatch-board-layout {
      grid-template-columns: minmax(0, 1fr) 300px;
    }

    .dispatch-board-side {
      top: 96px;
    }
  }

  @media (max-width: 991.98px) {
    .dispatch-board-layout {
      grid-template-columns: 1fr;
    }

    .dispatch-board-side {
      position: static;
    }

    .dispatch-board-intro__body,
    .dispatch-board__controls {
      flex-direction: column;
      align-items: stretch;
    }

    .dispatch-board-intro__meta {
      justify-content: flex-start;
    }
  }

  @media (max-width: 767.98px) {
    .dispatch-shell {
      padding: 22px 16px 40px;
    }

    .dispatch-board {
      padding-top: 72px;
    }

    .dispatch-board-intro {
      margin-bottom: 16px;
    }

    .dispatch-board-intro__title {
      font-size: 1.55rem;
    }

    .dispatch-board-card {
      padding: 18px;
      border-radius: 20px;
    }

    .dispatch-board-card__header {
      flex-wrap: wrap;
      padding-right: 0;
      gap: 14px;
    }

    .dispatch-board-card__lead {
      width: 100%;
    }

    .dispatch-board-card__schedule {
      width: 100%;
      justify-items: start;
      text-align: left;
      padding-left: 62px;
    }

    .dispatch-board-card__info-grid {
      grid-template-columns: 1fr;
    }

    .dispatch-board-card__footer {
      flex-wrap: wrap;
    }

    .dispatch-board-card__action-main,
    .dispatch-board-card__action-secondary,
    .dispatch-board-card__action-icon {
      width: 100%;
      flex: 1 1 100%;
    }

    .dispatch-board-route {
      min-height: 320px;
      border-radius: 22px;
    }

    .dispatch-board-route__overlay {
      left: 14px;
      right: 14px;
      bottom: 14px;
      padding: 16px;
    }

    .dispatch-board-route__frame {
      padding: 14px;
    }
  }

  @media (max-width: 575.98px) {
    .dispatch-board-intro__chip {
      width: 100%;
      justify-content: center;
    }

    .dispatch-board-intro__meta {
      width: 100%;
    }

    .dispatch-board-route__content {
      flex-direction: column;
    }

    .dispatch-board-route__action {
      width: 100%;
    }
  }
</style>
@endpush

@section('content')
<div class="dispatch-page">
  <x-worker-sidebar />

  <main class="worker-main">
    <div class="dispatch-shell">
      <div class="dispatch-board">
        <section class="dispatch-board-topbar" aria-label="Điều hướng lịch làm việc">
          <div class="dispatch-board-topbar__inner">
            <div class="dispatch-board-topbar__nav">
              <div class="dispatch-board-topbar__title">Lịch làm việc</div>
              <div class="dispatch-board-topbar__tabs" role="tablist">
                <button type="button" class="dispatch-board-topbar__tab" data-board-status="pending">Nhận việc</button>
                <button type="button" class="dispatch-board-topbar__tab" data-board-status="upcoming">Sắp tới</button>
                <button type="button" class="dispatch-board-topbar__tab" data-board-status="inprogress">Đang sửa</button>
                <button type="button" class="dispatch-board-topbar__tab" data-board-status="payment">Chưa thanh toán</button>
                <button type="button" class="dispatch-board-topbar__tab" data-board-status="done">Hoàn thành</button>
                <button type="button" class="dispatch-board-topbar__tab" data-board-status="cancelled">Đã hủy</button>
              </div>
            </div>

            <div class="dispatch-board-topbar__actions">
              <div class="dispatch-board-topbar__notification">
                <button
                  type="button"
                  class="dispatch-board-topbar__icon-btn"
                  id="dispatchTopNotificationButton"
                  aria-label="Thông báo"
                  aria-expanded="false">
                  <span class="material-symbols-outlined">notifications</span>
                  <span class="dispatch-board-topbar__notification-badge is-hidden" id="dispatchTopNotificationBadge">0</span>
                </button>

                <div class="dispatch-board-topbar__notification-menu" id="dispatchTopNotificationMenu">
                  <div class="dispatch-board-topbar__notification-head">
                    <div>
                      <h3>Thông báo</h3>
                      <p>Cập nhật mới nhất từ các đơn bạn đang xử lý.</p>
                    </div>
                    <button type="button" class="dispatch-board-topbar__notification-mark" id="dispatchTopNotificationMarkAll">Đã đọc hết</button>
                  </div>

                  <div class="dispatch-board-topbar__notification-list" id="dispatchTopNotificationList">
                    <div class="dispatch-board-topbar__notification-empty">
                      <span class="material-symbols-outlined">notifications_off</span>
                      <p>Chưa có thông báo nào.</p>
                    </div>
                  </div>

                  <div class="dispatch-board-topbar__notification-foot">
                    <a href="/worker/my-bookings">Xem lịch làm việc</a>
                  </div>
                </div>
              </div>

              <a href="/worker/profile" class="dispatch-board-topbar__avatar" id="dispatchTopAvatar" aria-label="Mở hồ sơ">
                TT
              </a>
            </div>
          </div>
        </section>

        <section class="dispatch-board-layout">
          <div class="dispatch-board-main">
            <section class="dispatch-board-intro" aria-label="Tóm tắt màn lịch làm việc">
              <div class="dispatch-board-intro__eyebrow" id="dispatchBoardIntroEyebrow">Lịch làm việc</div>

              <div class="dispatch-board-intro__body">
                <div class="dispatch-board-intro__copy">
                  <h1 class="dispatch-board-intro__title" id="dispatchBoardIntroTitle">Đang tải lịch làm việc</h1>
                  <p class="dispatch-board-intro__subtitle" id="dispatchBoardIntroSubtitle">
                    Hệ thống đang chuẩn bị danh sách lịch sửa chữa phù hợp với trạng thái bạn đang xem.
                  </p>
                </div>

                <div class="dispatch-board-intro__meta">
                  <span class="dispatch-board-intro__chip dispatch-board-intro__chip--status" id="dispatchBoardStatusChip">Đang đồng bộ</span>
                  <span class="dispatch-board-intro__chip" id="dispatchBoardScopeChip">Phạm vi: tất cả</span>
                </div>
              </div>
            </section>

            <section class="dispatch-board__controls">
              <div class="dispatch-scope-toggle" role="tablist" aria-label="Lọc theo ngày">
                <button type="button" class="dispatch-scope-toggle__btn is-active" data-booking-scope="all">Tất cả</button>
                <button type="button" class="dispatch-scope-toggle__btn" data-booking-scope="today">Hôm nay</button>
              </div>

              <div class="dispatch-board__controls-meta" id="dispatchBoardControlsMeta">Đang tải lịch làm việc</div>
            </section>

            <section id="bookingsContainer" class="dispatch-board-grid">
              <div class="dispatch-board-empty">
                <div>
                  <span class="material-symbols-outlined">hourglass_top</span>
                  <h3>Đang tải lịch làm việc</h3>
                  <p>Hệ thống đang lấy danh sách đơn sửa chữa của bạn.</p>
                </div>
              </div>
            </section>

            <div class="dispatch-board__pagination-wrap" id="bookingPaginationWrap" hidden>
              <div class="dispatch-pagination" id="bookingPagination" aria-label="Phân trang lịch làm việc"></div>
            </div>
          </div>

          <aside class="dispatch-board-side">
            <section class="dispatch-board-route" id="routePreviewSection" hidden>
              <div class="dispatch-board-route__frame">
                <span class="dispatch-board-route__label">Bản đồ tiếp theo</span>
                <span class="dispatch-board-route__badge" id="routePreviewBadge">Sửa tại nhà</span>
              </div>

              <div class="dispatch-board-route__overlay">
                <div class="dispatch-board-route__content">
                  <div class="dispatch-board-route__icon">
                    <span class="material-symbols-outlined">directions_car</span>
                  </div>
                  <div>
                    <div class="dispatch-board-route__eyebrow">Tuyến đang ưu tiên</div>
                    <h3 class="dispatch-board-route__title" id="routePreviewTitle">Đang tìm điểm đến phù hợp</h3>
                    <p class="dispatch-board-route__location" id="routePreviewLocation">Địa chỉ sẽ hiển thị tại đây khi có lịch phù hợp.</p>
                    <p class="dispatch-board-route__meta" id="routePreviewMeta">Tuyến đường tiếp theo sẽ hiện tại đây khi có đơn sửa tại nhà.</p>
                  </div>
                </div>

                <button type="button" class="dispatch-board-route__action" id="routePreviewAction">
                  <span class="material-symbols-outlined">map</span>
                  Xem đường đi
                </button>
              </div>
            </section>
          </aside>
        </section>
      </div>
    </div>
  </main>
</div>

<div class="modal fade dispatch-modal dispatch-modal--pricing-v2" id="modalCosts" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content dispatch-modal__content dispatch-modal__content--pricing-v2">
      <div class="dispatch-modal__header dispatch-modal__header--pricing-v2">
        <div class="dispatch-pricing-v2-header-row">
          <div class="dispatch-pricing-v2-header-brand">
            <div class="dispatch-modal__title-accent">Pricing Desk</div>
          </div>
          <div class="dispatch-pricing-v2-header-copy">
            <h2 class="dispatch-modal__title mb-0">Cập nhật bảng giá sửa chữa</h2>
            <p class="dispatch-modal__subtitle m-0">Điền rõ từng hạng mục để khách dễ kiểm tra, còn bạn dễ rà soát tổng tiền trước khi gửi yêu cầu thanh toán.</p>
          </div>
          <button type="button" class="dispatch-modal__close dispatch-modal__close--v2" data-bs-dismiss="modal" aria-label="Đóng">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>
        <div class="dispatch-pricing-v2-header-meta">
          <div class="dispatch-pricing-v2-banner-chip" id="costServiceModeBadge" data-state="travel">Sửa tại nhà</div>
          <div class="dispatch-pricing-v2-banner-chip" id="costTruckBadge" data-state="muted">Không thuê xe chở</div>
          <div id="costDistanceContainer">
            <div class="dispatch-pricing-v2-banner-chip" id="costDistanceBadge" data-state="travel">Phí đi lại tự động</div>
          </div>
        </div>
        <div class="dispatch-pricing-v2-banner">
          <div class="dispatch-pricing-v2-banner-main">
            <div class="dispatch-pricing-v2-banner-item dispatch-pricing-v2-banner-item--compact">
              <span id="costBookingReference">Đơn #0000</span>
            </div>
            <div class="dispatch-pricing-v2-banner-item">
              <span class="material-symbols-outlined">person</span>
              <span id="costCustomerName">Khách hàng</span>
            </div>
            <div class="dispatch-pricing-v2-banner-item">
              <span class="material-symbols-outlined">construction</span>
              <span id="costServiceName">Dịch vụ sửa chữa</span>
            </div>
            <div class="dispatch-pricing-v2-banner-status">Đang sửa</div>
          </div>
        </div>
      </div>

      <div class="dispatch-modal__body dispatch-modal__body--pricing-v2 p-0">
        <form id="formUpdateCosts" class="dispatch-pricing-form h-100 d-flex flex-column">
          <input type="hidden" id="costBookingId">
          <input type="hidden" id="inputGhiChuLinhKien" value="">

          <div class="dispatch-pricing-v2-main flex-grow-1">
            <div class="dispatch-pricing-v2-content-grid">
              <div class="dispatch-pricing-v2-editor">
                <div class="dispatch-pricing-v2-wizard-head">
                  <div>
                    <div class="dispatch-pricing-v2-wizard-kicker" id="costWizardKicker">Bước 1 trên 2</div>
                    <h3 class="dispatch-pricing-v2-wizard-title" id="costWizardTitle">Nhập tiền công</h3>
                    <p class="dispatch-pricing-v2-wizard-copy" id="costWizardCopy">Điền tiền công trước để khách nhìn rõ phần công thợ, sau đó chuyển sang bước linh kiện.</p>
                  </div>
                  <div class="dispatch-pricing-v2-wizard-badge" id="costWizardStepBadge">1 / 2</div>
                </div>

                <div class="dispatch-pricing-v2-progress">
                  <div class="dispatch-pricing-v2-progress-track">
                    <div class="dispatch-pricing-v2-progress-fill" id="costWizardProgressFill"></div>
                  </div>
                  <div class="dispatch-pricing-v2-flow" id="costWizardFlow">
                    <button type="button" class="dispatch-pricing-v2-flow-step is-active" data-cost-step-trigger="1">
                      <span class="dispatch-pricing-v2-flow-step__index">1</span>
                      <span class="dispatch-pricing-v2-flow-step__label">Tiền công</span>
                    </button>
                    <div class="dispatch-pricing-v2-flow-divider"></div>
                    <button type="button" class="dispatch-pricing-v2-flow-step" data-cost-step-trigger="2">
                      <span class="dispatch-pricing-v2-flow-step__index">2</span>
                      <span class="dispatch-pricing-v2-flow-step__label">Linh kiện</span>
                    </button>
                  </div>
                </div>

                <div class="dispatch-pricing-v2-step-panel is-active" data-cost-step-panel="1">
                  <section class="dispatch-pricing-v2-section dispatch-pricing-v2-section--labor">
                    <div class="dispatch-pricing-v2-section-head">
                      <div class="dispatch-pricing-v2-section-copy">
                        <div class="dispatch-pricing-v2-section-ordinal">
                          <span class="dispatch-pricing-v2-section-number">01</span>
                          <div class="dispatch-pricing-v2-section-heading-group">
                            <div class="dispatch-pricing-v2-section-kicker">Tiền công</div>
                            <span class="dispatch-pricing-v2-section-count" id="laborCountBadge">0 dòng</span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="dispatch-pricing-v2-labor-catalog">
                      <div class="dispatch-pricing-v2-cascade-grid">
                        <label class="dispatch-pricing-v2-picker-field">
                          <span class="dispatch-pricing-v2-field-label">Triệu chứng</span>
                          <div class="dispatch-search-picker" id="laborSymptomPicker">
                            <button type="button" class="dispatch-search-picker__trigger" id="laborSymptomTrigger" aria-haspopup="listbox" aria-expanded="false" aria-controls="laborSymptomPanel">
                              <span class="dispatch-search-picker__label" id="laborSymptomTriggerLabel">Chọn triệu chứng</span>
                              <span class="material-symbols-outlined dispatch-search-picker__icon">expand_more</span>
                            </button>
                            <div class="dispatch-search-picker__panel" id="laborSymptomPanel" hidden>
                              <div class="dispatch-search-picker__searchbox">
                                <span class="material-symbols-outlined">search</span>
                                <input type="search" class="dispatch-search-picker__search" id="laborSymptomSearch" placeholder="Tìm triệu chứng">
                              </div>
                              <div class="dispatch-search-picker__options" id="laborSymptomOptions" role="listbox" aria-label="Danh sách triệu chứng"></div>
                            </div>
                          </div>
                          <select class="dispatch-pricing-v2-select dispatch-pricing-v2-select--picker dispatch-pricing-v2-select--native" id="laborSymptomSelect">
                            <option value="">Chọn triệu chứng</option>
                          </select>
                        </label>
                        <label class="dispatch-pricing-v2-picker-field">
                          <span class="dispatch-pricing-v2-field-label">Nguyên nhân</span>
                          <div class="dispatch-search-picker" id="laborCausePicker">
                            <button type="button" class="dispatch-search-picker__trigger" id="laborCauseTrigger" aria-haspopup="listbox" aria-expanded="false" aria-controls="laborCausePanel" disabled>
                              <span class="dispatch-search-picker__label" id="laborCauseTriggerLabel">Chọn nguyên nhân</span>
                              <span class="material-symbols-outlined dispatch-search-picker__icon">expand_more</span>
                            </button>
                            <div class="dispatch-search-picker__panel" id="laborCausePanel" hidden>
                              <div class="dispatch-search-picker__searchbox">
                                <span class="material-symbols-outlined">search</span>
                                <input type="search" class="dispatch-search-picker__search" id="laborCauseSearch" placeholder="Tìm nguyên nhân">
                              </div>
                              <div class="dispatch-search-picker__options" id="laborCauseOptions" role="listbox" aria-label="Danh sách nguyên nhân"></div>
                            </div>
                          </div>
                          <select class="dispatch-pricing-v2-select dispatch-pricing-v2-select--picker dispatch-pricing-v2-select--native" id="laborCauseSelect" disabled>
                            <option value="">Chọn nguyên nhân</option>
                          </select>
                        </label>
                        <label class="dispatch-pricing-v2-picker-field">
                          <span class="dispatch-pricing-v2-field-label">Hướng xử lý</span>
                          <select class="dispatch-pricing-v2-select dispatch-pricing-v2-select--picker" id="laborResolutionSelect" disabled>
                            <option value="">Chọn hướng xử lý</option>
                          </select>
                        </label>
                      </div>
                      <div class="dispatch-pricing-v2-labor-catalog-footer">
                        <div class="dispatch-pricing-v2-labor-note">
                          <div class="dispatch-pricing-v2-labor-note__title" id="laborCatalogStatus">Chọn triệu chứng để hệ thống lọc tiền công đúng với đơn này.</div>
                          <div class="dispatch-pricing-v2-labor-note__meta" id="laborResolutionPrice">Giá tham khảo và mô tả xử lý sẽ hiện ở đây.</div>
                        </div>
                        <button type="button" class="dispatch-pricing-v2-inline-add dispatch-pricing-v2-inline-add--primary" id="addLaborItem" disabled>
                          <span class="material-symbols-outlined">playlist_add</span>
                          Thêm tiền công
                        </button>
                      </div>
                    </div>
                    <div class="dispatch-pricing-v2-labor-list" id="laborItemsContainer"></div>
                  </section>
                </div>

                <div class="dispatch-pricing-v2-step-panel" data-cost-step-panel="2" hidden>
                  <section class="dispatch-pricing-v2-section dispatch-pricing-v2-section--parts">
                    <div class="dispatch-pricing-v2-section-head">
                      <div class="dispatch-pricing-v2-section-copy">
                        <div class="dispatch-pricing-v2-section-ordinal">
                          <span class="dispatch-pricing-v2-section-number">02</span>
                          <div class="dispatch-pricing-v2-section-heading-group">
                            <div class="dispatch-pricing-v2-section-kicker">Linh kiện</div>
                            <span class="dispatch-pricing-v2-section-count" id="partCountBadge">0 dòng</span>
                          </div>
                        </div>
                      </div>
                      <button type="button" class="dispatch-pricing-v2-inline-add dispatch-pricing-v2-inline-add--primary" id="addPartItem">
                        <span class="material-symbols-outlined">add</span>
                        Thêm dòng thủ công
                      </button>
                    </div>

                    <div class="dispatch-pricing-v2-search-row">
                      <div class="dispatch-pricing-v2-searchbox">
                        <span class="material-symbols-outlined dispatch-pricing-v2-search-icon">search</span>
                        <input type="search" class="dispatch-pricing-v2-search-input" id="partCatalogSearch" placeholder="Ví dụ: bo nóng Samsung..." autocomplete="off">
                        <div class="dispatch-part-catalog__suggestions" id="partCatalogSuggestions" hidden></div>
                      </div>
                      <button type="button" class="dispatch-pricing-v2-search-action" id="addSelectedParts">Thêm linh kiện đã chọn</button>
                    </div>

                    <div class="dispatch-pricing-v2-inline-status">
                      <span class="dispatch-part-catalog__status" id="partCatalogStatus">Mở đơn để tải danh mục linh kiện.</span>
                    </div>

                    <div class="dispatch-part-catalog__results" id="partCatalogResults"></div>
                    <div class="dispatch-pricing-v2-parts-list" id="partItemsContainer"></div>
                  </section>

                  <section class="dispatch-pricing-v2-section dispatch-pricing-v2-section--fees">
                    <div class="dispatch-pricing-v2-section-copy dispatch-pricing-v2-section-copy--fees">
                      <div class="dispatch-pricing-v2-section-ordinal">
                        <span class="dispatch-pricing-v2-section-number">03</span>
                        <div class="dispatch-pricing-v2-section-heading-group">
                          <div class="dispatch-pricing-v2-section-kicker">Phí phụ thêm</div>
                        </div>
                      </div>
                    </div>
                    <div class="dispatch-pricing-v2-fees-section">
                      <div class="dispatch-pricing-v2-fee-card" id="truckFeeContainer" style="display:none;">
                        <h3 class="dispatch-pricing-v2-fee-title">Phí thuê xe chở</h3>
                        <div class="dispatch-pricing-v2-fee-input-wrap">
                          <input type="number" class="dispatch-pricing-v2-fee-input" id="inputTienThueXe" min="0" value="0" placeholder="Nhập số tiền...">
                          <span class="dispatch-pricing-v2-fee-prefix">đ</span>
                        </div>
                      </div>
                      <div class="dispatch-pricing-v2-fee-card dispatch-pricing-v2-fee-card--readonly">
                        <h3 class="dispatch-pricing-v2-fee-title">Phí đi lại cố định</h3>
                        <div class="dispatch-pricing-v2-fee-readonly">
                          <strong class="dispatch-pricing-v2-fee-value" id="displayPhiDiLai">0 đ</strong>
                          <span class="dispatch-pricing-v2-fee-chip">Tự tính</span>
                        </div>
                        <p class="dispatch-pricing-v2-fee-hint m-0" id="costDistanceHint">Hệ thống tính tự động theo quãng đường phục vụ.</p>
                      </div>
                    </div>
                  </section>
                </div>
              </div>

              <aside class="dispatch-pricing-v2-summary">
                <div class="dispatch-pricing-v2-summary-card">
                  <div class="dispatch-pricing-v2-summary-eyebrow">Bản xem trước gửi khách</div>
                  <div class="dispatch-pricing-v2-summary-header">
                    <h3 class="dispatch-pricing-v2-summary-title">Tóm tắt chi phí</h3>
                    <span class="dispatch-pricing-v2-summary-status" id="costDraftState">Sẵn sàng lưu</span>
                  </div>

                  <div class="dispatch-pricing-v2-summary-list">
                    <div class="dispatch-pricing-v2-summary-row">
                      <span>Tổng tiền công</span>
                      <strong id="laborSubtotal">0 đ</strong>
                    </div>
                    <div class="dispatch-pricing-v2-summary-row">
                      <span>Linh kiện & Vật tư</span>
                      <strong id="partsSubtotal">0 đ</strong>
                    </div>
                    <div class="dispatch-pricing-v2-summary-row" id="travelSummaryRow">
                      <span>Phí đi lại (Cố định)</span>
                      <strong id="travelSubtotal">0 đ</strong>
                    </div>
                    <div class="dispatch-pricing-v2-summary-row" id="truckSummaryRow" style="display:none;">
                      <span>Phí thuê xe</span>
                      <strong id="truckSubtotal">0 đ</strong>
                    </div>
                  </div>

                  <div class="dispatch-pricing-v2-total-card">
                    <div class="dispatch-pricing-v2-total-card__label">
                      <span class="material-symbols-outlined">receipt_long</span>
                      <span>Tổng cộng tất cả chi phí</span>
                    </div>
                    <div class="dispatch-pricing-v2-total-card__value" id="costEstimateTotal">0 đ</div>
                    <div class="dispatch-pricing-v2-total-card__hint" id="costSummaryHint">Bao gồm công, linh kiện và các phụ phí của đơn này.</div>
                  </div>
                </div>
              </aside>
            </div>
          </div>
          <div class="dispatch-pricing-v2-footer">
            <div class="dispatch-pricing-v2-footer-actions">
              <button type="button" class="dispatch-pricing-v2-btn dispatch-pricing-v2-btn--ghost" data-bs-dismiss="modal">Hủy</button>
              <button type="button" class="dispatch-pricing-v2-btn dispatch-pricing-v2-btn--ghost d-none" id="btnCostWizardPrev">
                <span class="material-symbols-outlined">arrow_back</span>
                Quay lại
              </button>
              <button type="button" class="dispatch-pricing-v2-btn dispatch-pricing-v2-btn--primary" id="btnCostWizardNext">
                <span class="material-symbols-outlined">arrow_forward</span>
                Tiếp tục
              </button>
              <button type="submit" class="dispatch-pricing-v2-btn dispatch-pricing-v2-btn--primary d-none" id="btnSubmitCostUpdate">
                <span class="material-symbols-outlined">save</span>
                Lưu chi phí
              </button>
            </div>
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
              <h3 class="dispatch-panel__title">Bước 2. Chọn phương thức thanh toán</h3>

              <div class="dispatch-radio-grid">
                <label class="dispatch-pay-option" id="completePaymentOptionCod">
                  <input type="radio" name="phuong_thuc_thanh_toan" value="cod" checked>
                  <span class="dispatch-pay-option__card">
                    <span class="material-symbols-outlined">payments</span>
                    <span class="dispatch-pay-option__copy">
                      <strong>Tiền mặt</strong>
                      <small>Thợ xác nhận hoàn thành là đơn được chốt ngay.</small>
                    </span>
                  </span>
                </label>

                <label class="dispatch-pay-option" id="completePaymentOptionTransfer">
                  <input type="radio" name="phuong_thuc_thanh_toan" value="transfer">
                  <span class="dispatch-pay-option__card">
                    <span class="material-symbols-outlined">account_balance_wallet</span>
                    <span class="dispatch-pay-option__copy">
                      <strong>Chuyển khoản</strong>
                      <small>Khách phải thanh toán online xong thì đơn mới hoàn tất.</small>
                    </span>
                  </span>
                </label>
              </div>

              <div class="dispatch-readonly dispatch-readonly--accent mt-3">
                <div class="dispatch-readonly__value">
                  <div>
                    <strong id="completePaymentMethodTitle">Tiền mặt</strong>
                    <div class="dispatch-summary-tile__hint mt-2" id="completePaymentMethodHint">
                      Khi bạn xác nhận hoàn thành, đơn sẽ chuyển thành hoàn tất ngay với ghi nhận đã thu tiền mặt.
                    </div>
                  </div>
                  <span class="dispatch-pill dispatch-pill--payment" id="completePaymentMethodBadge">Hoàn tất ngay</span>
                </div>
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
                  <p class="dispatch-upload-area__hint">Tùy chọn. Tải lên video chạy thử sau sửa chữa để khách dễ đối chiếu.</p>
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

const WORKER_BOARD_STATUSES = ['pending', 'upcoming', 'inprogress', 'payment', 'done', 'cancelled', 'all'];
const WORKER_BOOKING_SCOPES = ['all', 'today'];
const bookingPageParams = new URLSearchParams(window.location.search);
const initialBookingId = Number(bookingPageParams.get('booking') || 0);

window.currentStatus = WORKER_BOARD_STATUSES.includes(bookingPageParams.get('status')) ? bookingPageParams.get('status') : 'inprogress';
window.currentScope = WORKER_BOOKING_SCOPES.includes(bookingPageParams.get('scope')) ? bookingPageParams.get('scope') : 'all';
window.currentPage = 1;
window.assignedBookings = [];
window.availableBookings = [];
window.allBookings = [];
window.activeBookingId = 0;
window.pendingBookingIdToOpen = Number.isFinite(initialBookingId) && initialBookingId > 0 ? Math.trunc(initialBookingId) : 0;

const JOBS_PER_PAGE = 2;

const bookingsContainer = document.getElementById('bookingsContainer');
const bookingPagination = document.getElementById('bookingPagination');
const bookingPaginationWrap = document.getElementById('bookingPaginationWrap');
const boardStatusTabs = Array.from(document.querySelectorAll('[data-board-status]'));
const bookingScopeButtons = Array.from(document.querySelectorAll('[data-booking-scope]'));
const boardIntroEyebrow = document.getElementById('dispatchBoardIntroEyebrow');
const boardIntroTitle = document.getElementById('dispatchBoardIntroTitle');
const boardIntroSubtitle = document.getElementById('dispatchBoardIntroSubtitle');
const boardStatusChip = document.getElementById('dispatchBoardStatusChip');
const boardScopeChip = document.getElementById('dispatchBoardScopeChip');
const boardControlsMeta = document.getElementById('dispatchBoardControlsMeta');
const topNotificationButton = document.getElementById('dispatchTopNotificationButton');
const topNotificationBadge = document.getElementById('dispatchTopNotificationBadge');
const topNotificationMenu = document.getElementById('dispatchTopNotificationMenu');
const topNotificationList = document.getElementById('dispatchTopNotificationList');
const topNotificationMarkAll = document.getElementById('dispatchTopNotificationMarkAll');
const topAvatar = document.getElementById('dispatchTopAvatar');
const routePreviewSection = document.getElementById('routePreviewSection');
const routePreviewBadge = document.getElementById('routePreviewBadge');
const routePreviewTitle = document.getElementById('routePreviewTitle');
const routePreviewLocation = document.getElementById('routePreviewLocation');
const routePreviewMeta = document.getElementById('routePreviewMeta');
const routePreviewAction = document.getElementById('routePreviewAction');
const detailContent = document.getElementById('bookingDetailContent');
const costForm = document.getElementById('formUpdateCosts');
const completeForm = document.getElementById('formCompleteBooking');

const costModalEl = document.getElementById('modalCosts');
const costModalInstance = costModalEl && typeof bootstrap !== 'undefined'
  ? new bootstrap.Modal(costModalEl)
  : null;

let topNotificationPollId = null;

const detailModalEl = document.getElementById('modalViewDetails');
const detailModalInstance = detailModalEl && typeof bootstrap !== 'undefined'
  ? new bootstrap.Modal(detailModalEl)
  : null;

detailModalEl?.addEventListener('hidden.bs.modal', () => {
  const hadActiveBooking = Number(window.activeBookingId || 0) > 0;
  window.activeBookingId = 0;

  if (hadActiveBooking) {
    syncWorkerBookingsUrl({ bookingId: 0 });
  }
});

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
const laborSymptomSelect = document.getElementById('laborSymptomSelect');
const laborCauseSelect = document.getElementById('laborCauseSelect');
const laborResolutionSelect = document.getElementById('laborResolutionSelect');
const laborSymptomPicker = document.getElementById('laborSymptomPicker');
const laborSymptomTrigger = document.getElementById('laborSymptomTrigger');
const laborSymptomTriggerLabel = document.getElementById('laborSymptomTriggerLabel');
const laborSymptomPanel = document.getElementById('laborSymptomPanel');
const laborSymptomSearch = document.getElementById('laborSymptomSearch');
const laborSymptomOptions = document.getElementById('laborSymptomOptions');
const laborCausePicker = document.getElementById('laborCausePicker');
const laborCauseTrigger = document.getElementById('laborCauseTrigger');
const laborCauseTriggerLabel = document.getElementById('laborCauseTriggerLabel');
const laborCausePanel = document.getElementById('laborCausePanel');
const laborCauseSearch = document.getElementById('laborCauseSearch');
const laborCauseOptions = document.getElementById('laborCauseOptions');
const laborCatalogStatus = document.getElementById('laborCatalogStatus');
const laborResolutionPrice = document.getElementById('laborResolutionPrice');
const addPartItemButton = document.getElementById('addPartItem');
const partCatalogSearch = document.getElementById('partCatalogSearch');
const partCatalogSuggestions = document.getElementById('partCatalogSuggestions');
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
const costWizardKicker = document.getElementById('costWizardKicker');
const costWizardTitle = document.getElementById('costWizardTitle');
const costWizardCopy = document.getElementById('costWizardCopy');
const costWizardStepBadge = document.getElementById('costWizardStepBadge');
const costWizardProgressFill = document.getElementById('costWizardProgressFill');
const costStepTriggers = Array.from(document.querySelectorAll('[data-cost-step-trigger]'));
const costStepPanels = Array.from(document.querySelectorAll('[data-cost-step-panel]'));
const btnCostWizardPrev = document.getElementById('btnCostWizardPrev');
const btnCostWizardNext = document.getElementById('btnCostWizardNext');

const completeBookingId = document.getElementById('completeBookingId');
const completeCustomerName = document.getElementById('completeCustomerName');
const completeServiceName = document.getElementById('completeServiceName');
const completeBookingTotal = document.getElementById('completeBookingTotal');
const completeStatusBadge = document.getElementById('completeStatusBadge');
const completePaymentMethodTitle = document.getElementById('completePaymentMethodTitle');
const completePaymentMethodHint = document.getElementById('completePaymentMethodHint');
const completePaymentMethodBadge = document.getElementById('completePaymentMethodBadge');
const completePaymentMethodInputs = Array.from(document.querySelectorAll('input[name="phuong_thuc_thanh_toan"]'));
const completePaymentOptions = Array.from(document.querySelectorAll('.dispatch-pay-option'));
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
let currentCostStep = 1;
let repairTimers = {};
const laborCatalogState = {
  items: [],
  cache: new Map(),
  selectedSymptomId: null,
  selectedCauseId: null,
  selectedResolutionId: null,
};
const laborSearchablePickerState = {
  symptom: {
    items: [],
    keyword: '',
  },
  cause: {
    items: [],
    keyword: '',
  },
};
const partCatalogState = {
  items: [],
  cache: new Map(),
  selectedIds: new Set(),
  activeSuggestionIndex: -1,
  fallbackItems: [],
  fallbackCache: new Map(),
  searchRequestId: 0,
};
const pricingWizardSteps = {
  1: {
    kicker: 'Bước 1 trên 2',
    title: 'Chọn tiền công',
    copy: 'Chọn triệu chứng, nguyên nhân và hướng xử lý để hình thành các dòng tiền công trước khi sang bước linh kiện.',
  },
  2: {
    kicker: 'Bước 2 trên 2',
    title: 'Thêm linh kiện',
    copy: 'Chọn linh kiện từ danh mục hoặc thêm thủ công, rồi lưu báo giá ngay trong modal này.',
  },
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
const laborSearchablePickers = {
  symptom: {
    rootEl: laborSymptomPicker,
    triggerEl: laborSymptomTrigger,
    triggerLabelEl: laborSymptomTriggerLabel,
    panelEl: laborSymptomPanel,
    searchEl: laborSymptomSearch,
    optionsEl: laborSymptomOptions,
    selectEl: laborSymptomSelect,
    placeholder: 'Chọn triệu chứng',
    emptyText: 'Không tìm thấy triệu chứng phù hợp.',
    getLabel: (item) => item?.ten_trieu_chung || 'Triệu chứng',
  },
  cause: {
    rootEl: laborCausePicker,
    triggerEl: laborCauseTrigger,
    triggerLabelEl: laborCauseTriggerLabel,
    panelEl: laborCausePanel,
    searchEl: laborCauseSearch,
    optionsEl: laborCauseOptions,
    selectEl: laborCauseSelect,
    placeholder: 'Chọn nguyên nhân',
    emptyText: 'Không tìm thấy nguyên nhân phù hợp.',
    getLabel: (item) => item?.ten_nguyen_nhan || 'Nguyên nhân',
  },
};

const escapeHtml = (value = '') => String(value ?? '').replace(/[&<>"']/g, (char) => ({
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#039;',
}[char]));

const resolveAvatarUrl = (avatar = '') => {
  if (!avatar) {
    return '';
  }

  if (/^https?:\/\//i.test(avatar) || avatar.startsWith('/')) {
    return avatar;
  }

  return `/storage/${avatar}`;
};

const getInitials = (name = '') => {
  const normalized = String(name || '').trim();
  if (!normalized) {
    return 'TT';
  }

  return normalized
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('') || normalized.charAt(0).toUpperCase() || 'TT';
};

const setAvatarContent = (element, avatar, fallbackName) => {
  if (!element) {
    return;
  }

  const avatarUrl = resolveAvatarUrl(avatar);
  if (avatarUrl) {
    element.innerHTML = `<img src="${escapeHtml(avatarUrl)}" alt="${escapeHtml(fallbackName || 'Avatar')}">`;
    return;
  }

  element.textContent = getInitials(fallbackName || 'Thợ kỹ thuật');
};

const nl2brSafe = (value = '') => escapeHtml(value).replace(/\n/g, '<br>');

const formatMoney = (value) => new Intl.NumberFormat('vi-VN', {
  style: 'currency',
  currency: 'VND',
  maximumFractionDigits: 0,
}).format(Number(value || 0));

const formatCount = (value) => String(Number(value || 0)).padStart(2, '0');
const getNumeric = (value) => Number(value || 0);
const getApiCollection = (payload) => {
  if (Array.isArray(payload?.data)) {
    return payload.data;
  }

  return Array.isArray(payload) ? payload : [];
};
const isClaimableMarketBooking = (booking) => booking?.trang_thai === 'cho_xac_nhan' && getNumeric(booking?.tho_id) <= 0;
const isAssignedPendingBooking = (booking) => booking?.trang_thai === 'cho_xac_nhan' && getNumeric(booking?.tho_id) === getNumeric(user?.id);
const isWorkerOwnedBooking = (booking) => getNumeric(booking?.tho_id) === getNumeric(user?.id);
const normalizeWorkerBooking = (booking = {}, { isMarketJob = false } = {}) => {
  const normalizedBooking = {
    ...booking,
    is_market_job: isMarketJob,
  };

  const workerDistanceKm = getNumeric(booking?.worker_distance_km);
  if (isMarketJob && workerDistanceKm > 0) {
    normalizedBooking.khoang_cach = workerDistanceKm;
  }

  return normalizedBooking;
};
const rebuildWorkerBookings = () => {
  const bookingMap = new Map();

  window.availableBookings.forEach((booking) => {
    const bookingId = getNumeric(booking?.id);
    if (bookingId > 0) {
      bookingMap.set(bookingId, normalizeWorkerBooking(booking, { isMarketJob: true }));
    }
  });

  window.assignedBookings.forEach((booking) => {
    const bookingId = getNumeric(booking?.id);
    if (bookingId > 0) {
      bookingMap.set(bookingId, normalizeWorkerBooking(booking, { isMarketJob: false }));
    }
  });

  window.allBookings = Array.from(bookingMap.values());
};
const normalizeDropdownSearchText = (value = '') => String(value || '')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLocaleLowerCase('vi-VN')
  .trim();
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

const buildWorkerBookingsHref = ({
  status = window.currentStatus,
  scope = window.currentScope,
  bookingId = window.activeBookingId,
} = {}) => {
  const params = new URLSearchParams();

  if (WORKER_BOARD_STATUSES.includes(status) && status !== 'inprogress') {
    params.set('status', status);
  }

  if (WORKER_BOOKING_SCOPES.includes(scope) && scope !== 'all') {
    params.set('scope', scope);
  }

  const normalizedBookingId = Number(bookingId);
  if (Number.isFinite(normalizedBookingId) && normalizedBookingId > 0) {
    params.set('booking', String(Math.trunc(normalizedBookingId)));
  }

  const query = params.toString();
  return query ? `/worker/my-bookings?${query}` : '/worker/my-bookings';
};

const syncWorkerBookingsUrl = ({ bookingId = window.activeBookingId, replace = true } = {}) => {
  const targetUrl = buildWorkerBookingsHref({ bookingId });
  const nextLocation = new URL(targetUrl, window.location.origin);
  const nextHref = `${nextLocation.pathname}${nextLocation.search}`;
  const currentHref = `${window.location.pathname}${window.location.search}`;

  if (nextHref === currentHref) {
    return;
  }

  const historyMethod = replace ? 'replaceState' : 'pushState';
  window.history[historyMethod](window.history.state, '', nextHref);
};

const statusLabelMap = {
  cho_xac_nhan: 'Chờ xác nhận',
  cho_hoan_thanh: 'Chờ xác nhận COD',
  da_xac_nhan: 'Sắp tới',
  dang_lam: 'Đang sửa',
  cho_thanh_toan: 'Chờ thanh toán online',
  da_xong: 'Hoàn thành',
  da_huy: 'Đã hủy',
};

const boardViewCopy = {
  pending: {
    eyebrow: 'Nhận việc',
    title: 'Việc mới đang chờ nhận',
    subtitle: 'Duyệt nhanh các yêu cầu mới, kiểm tra mô tả sự cố và quyết định nhận việc ngay trong một luồng gọn.',
    badgeLabel: 'Đơn mới',
  },
  upcoming: {
    eyebrow: 'Lịch làm việc',
    title: 'Lịch làm việc sắp tới',
    subtitle: 'Theo dõi các ca đã xác nhận, nhìn rõ thời gian hẹn và địa điểm để chuẩn bị lộ trình hợp lý.',
    badgeLabel: 'Sắp tới',
  },
  inprogress: {
    eyebrow: 'Đang sửa',
    title: 'Các đơn đang được xử lý',
    subtitle: 'Giữ nhịp cho các ca đang sửa, cập nhật giá và chốt bước tiếp theo mà không phải rời khỏi bảng công việc.',
    badgeLabel: 'Đang sửa',
  },
  payment: {
    eyebrow: 'Thanh toán',
    title: 'Các đơn chờ thanh toán',
    subtitle: 'Ưu tiên các công việc đã hoàn tất sửa chữa nhưng còn bước thanh toán để dòng tiền không bị chậm lại.',
    badgeLabel: 'Chờ thanh toán',
  },
  done: {
    eyebrow: 'Hoàn thành',
    title: 'Lịch sử công việc đã hoàn thành',
    subtitle: 'Xem lại các đơn đã chốt, tổng hợp kết quả xử lý và kiểm tra những lần hoàn thành gần nhất.',
    badgeLabel: 'Hoàn thành',
  },
  cancelled: {
    eyebrow: 'Đã hủy',
    title: 'Những lịch đã bị hủy',
    subtitle: 'Theo dõi các ca không tiếp tục triển khai để đối chiếu nguyên nhân và tránh bỏ sót thông tin quan trọng.',
    badgeLabel: 'Đã hủy',
  },
  all: {
    eyebrow: 'Lịch làm việc',
    title: 'Toàn bộ bảng công việc',
    subtitle: 'Tập trung toàn bộ lịch sửa chữa vào một mặt bằng trực quan để bạn đổi trạng thái và theo dõi nhanh.',
    badgeLabel: 'Toàn bộ',
  },
};

const getBoardViewConfig = (status = window.currentStatus) => boardViewCopy[status] || boardViewCopy.all;

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
      don_gia: getNumeric(booking?.phi_linh_kien),
      so_luong: 1,
      so_tien: getNumeric(booking?.phi_linh_kien),
      bao_hanh_thang: null,
    }];
  }

  return [];
};

const getPartQuantity = (item) => {
  const quantity = Math.trunc(getNumeric(item?.so_luong || 1));
  return quantity > 0 ? quantity : 1;
};

const getPartUnitPrice = (item) => {
  const explicitUnitPrice = getNumeric(item?.don_gia);
  if (explicitUnitPrice > 0) {
    return explicitUnitPrice;
  }

  const quantity = getPartQuantity(item);
  const total = getNumeric(item?.so_tien);
  return quantity > 0 ? total / quantity : total;
};

const formatPartQuantityMeta = (item, warrantyLabel = '') => {
  const quantity = getPartQuantity(item);
  const unitPrice = getPartUnitPrice(item);
  const segments = [
    `SL ${quantity}`,
    unitPrice > 0 ? `${formatMoney(unitPrice)}/cái` : '',
    warrantyLabel,
  ].filter(Boolean);

  return segments.join(' • ');
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

const buildWarrantyOptionsMarkup = (value = '') => {
  const normalizedValue = value === '' ? '' : Math.max(0, Math.trunc(getNumeric(value)));
  const presets = ['', 0, 1, 3, 6, 12, 24];

  if (normalizedValue !== '' && !presets.includes(normalizedValue)) {
    presets.push(normalizedValue);
  }

  return presets
    .filter((option, index, array) => array.indexOf(option) === index)
    .sort((a, b) => {
      if (a === '') {
        return -1;
      }
      if (b === '') {
        return 1;
      }
      return Number(a) - Number(b);
    })
    .map((option) => {
      const selected = option === normalizedValue ? 'selected' : '';
      const label = option === ''
        ? 'Bảo hành'
        : option === 0
          ? '0 Tháng'
          : `${option} Tháng`;

      return `<option value="${option}" ${selected}>${label}</option>`;
    })
    .join('');
};

const buildCostItemRowMarkup = (type, item = {}) => {
  const description = escapeHtml(item?.noi_dung || '');
  const isPart = type === 'part';
  const amount = isPart ? getPartUnitPrice(item) : getNumeric(item?.so_tien);
  const amountValue = amount > 0 ? amount : '';
  const formattedAmountValue = amount > 0 ? Number(amount).toLocaleString('vi-VN') : '0';
  const catalogResolutionId = isPart ? 0 : getNumeric(item?.huong_xu_ly_id);
  const catalogCauseId = isPart ? 0 : getNumeric(item?.nguyen_nhan_id);
  const catalogPartId = isPart ? getNumeric(item?.linh_kien_id) : 0;
  const serviceId = getNumeric(item?.dich_vu_id);
  const image = isPart ? escapeHtml(item?.hinh_anh || '') : '';
  const isCatalogItem = isPart && catalogPartId > 0;
  const isCatalogLaborItem = !isPart && catalogResolutionId > 0;
  const quantityValue = isPart ? getPartQuantity(item) : '';
  const warrantyValue = isPart && item?.bao_hanh_thang !== null && item?.bao_hanh_thang !== undefined
    ? getNumeric(item.bao_hanh_thang)
    : '';
  const partMeta = isCatalogItem ? 'Từ danh mục linh kiện' : 'Tự nhập thủ công';
  const laborNote = !isPart ? escapeHtml(item?.mo_ta_cong_viec || '') : '';
  const laborSymptom = !isPart ? escapeHtml(item?.ten_trieu_chung || item?.trieu_chung || '') : '';
  const laborCause = !isPart
    ? escapeHtml(item?.ten_nguyen_nhan || item?.nguyen_nhan?.ten_nguyen_nhan || '')
    : '';
  const laborMeta = [laborSymptom, laborCause, laborNote].filter(Boolean).join(' • ')
    || (isCatalogLaborItem ? 'Từ danh mục tiền công' : 'Dữ liệu tiền công đã lưu');

  if (isPart) {
    return `
      <div class="dispatch-line-item dispatch-pricing-v2-part-card" data-line-type="${type}" data-catalog-part-id="${catalogPartId || ''}">
        <input type="hidden" class="js-line-part-id" value="${catalogPartId || ''}">
        <input type="hidden" class="js-line-service-id" value="${serviceId || ''}">
        <input type="hidden" class="js-line-image" value="${image}">

        <div class="dispatch-pricing-v2-part-card-inner">
          <div class="dispatch-pricing-v2-part-main">
            <div class="dispatch-pricing-v2-field-label">Tên linh kiện / Vật tư</div>
            <input type="text" class="dispatch-pricing-v2-input-dark js-line-description dispatch-pricing-v2-inline-input dispatch-pricing-v2-part-title" value="${description}" placeholder="Bo mạch chủ Samsung" ${isCatalogItem ? 'readonly' : ''}>
            <div class="dispatch-pricing-v2-part-meta">${escapeHtml(partMeta)}</div>
          </div>

          <div class="dispatch-pricing-v2-part-col">
            <div class="dispatch-pricing-v2-field-label">Đơn giá (đ)</div>
            <input type="number" class="dispatch-pricing-v2-input-dark js-line-amount dispatch-pricing-v2-inline-input dispatch-pricing-v2-inline-input--price" value="${amountValue}" placeholder="650000" ${isCatalogItem ? 'readonly' : ''}>
          </div>

          <div class="dispatch-pricing-v2-part-col">
            <div class="dispatch-pricing-v2-field-label">Số lượng</div>
            <div class="dispatch-pricing-v2-stepper">
              <button type="button" class="dispatch-pricing-v2-stepper-btn js-quantity-step" data-step="-1" aria-label="Giảm số lượng">
                <span class="material-symbols-outlined" style="font-size: 14px;">remove</span>
              </button>
              <input type="number" class="dispatch-pricing-v2-input-dark js-line-quantity dispatch-pricing-v2-inline-input" min="1" step="1" value="${quantityValue}" placeholder="1">
              <button type="button" class="dispatch-pricing-v2-stepper-btn js-quantity-step" data-step="1" aria-label="Tăng số lượng">
                <span class="material-symbols-outlined" style="font-size: 14px;">add</span>
              </button>
            </div>
          </div>

          <div class="dispatch-pricing-v2-part-col">
            <div class="dispatch-pricing-v2-field-label">Bảo hành</div>
            <select class="js-line-warranty dispatch-pricing-v2-select">
              ${buildWarrantyOptionsMarkup(warrantyValue)}
            </select>
          </div>

          <button type="button" class="dispatch-pricing-v2-part-remove dispatch-line-item__remove" aria-label="Xóa dòng">
            <span class="material-symbols-outlined" style="font-size: 14px;">delete</span>
          </button>
        </div>
      </div>
    `;
  } else {
    return `
      <div class="dispatch-line-item dispatch-pricing-v2-labor-row" data-line-type="${type}" data-catalog-resolution-id="${catalogResolutionId || ''}">
        <input type="hidden" class="js-line-resolution-id" value="${catalogResolutionId || ''}">
        <input type="hidden" class="js-line-cause-id" value="${catalogCauseId || ''}">
        <input type="hidden" class="js-line-service-id" value="${serviceId || ''}">
        <input type="hidden" class="js-line-work-note" value="${laborNote}">
        <input type="hidden" class="js-line-amount" value="${amountValue}">
        <div class="dispatch-pricing-v2-labor-main">
          <div class="dispatch-pricing-v2-field-label">Tên hạng mục công</div>
          <input type="text" class="dispatch-pricing-v2-input-dark js-line-description dispatch-pricing-v2-inline-input" value="${description}" placeholder="Chọn hướng xử lý từ danh mục" readonly>
          <div class="dispatch-pricing-v2-labor-row-meta">${laborMeta}</div>
        </div>
        <div class="dispatch-pricing-v2-labor-col dispatch-pricing-v2-labor-col--price">
          <div class="dispatch-pricing-v2-field-label">Đơn giá (đ)</div>
          <div class="dispatch-pricing-v2-labor-price">
            <span>${formattedAmountValue}</span>
            <span class="dispatch-pricing-v2-labor-price__suffix">đ</span>
          </div>
        </div>
        <button type="button" class="dispatch-pricing-v2-labor-remove dispatch-line-item__remove" aria-label="Xóa dòng">
          <span class="material-symbols-outlined" style="font-size: 14px;">delete</span>
        </button>
      </div>
    `;
  }
};


const populateCostItemRows = (container, type, items = []) => {
  if (!container) {
    return;
  }

  container.innerHTML = items.map((item) => buildCostItemRowMarkup(type, item)).join('');
};

const appendCostItemRow = (container, type, item = {}) => {
  container?.insertAdjacentHTML('beforeend', buildCostItemRowMarkup(type, item));
};

const ensureMinimumCostRows = () => {};

const sumDraftLineAmounts = (container) => Array.from(container?.querySelectorAll('.dispatch-line-item') || [])
  .reduce((total, row) => {
    const unitPrice = getNumeric(row.querySelector('.js-line-amount')?.value);
    const quantity = Math.max(1, Math.trunc(getNumeric(row.querySelector('.js-line-quantity')?.value || 1)));
    return total + (row.querySelector('.js-line-quantity') ? unitPrice * quantity : unitPrice);
  }, 0);

const countDraftLineRows = (container) => Array.from(container?.querySelectorAll('.dispatch-line-item') || []).length;

const collectCostItems = (container, type) => {
  let hasIncomplete = false;
  const items = [];

  Array.from(container?.querySelectorAll('.dispatch-line-item') || []).forEach((row) => {
    const description = row.querySelector('.js-line-description')?.value.trim() || '';
    const amountRaw = row.querySelector('.js-line-amount')?.value || '';
    const amount = getNumeric(amountRaw);
    const quantityRaw = row.querySelector('.js-line-quantity')?.value || '';
    const quantity = Math.max(1, Math.trunc(getNumeric(quantityRaw || 1)));
    const warrantyRaw = row.querySelector('.js-line-warranty')?.value || '';
    const resolutionIdRaw = row.querySelector('.js-line-resolution-id')?.value || '';
    const causeIdRaw = row.querySelector('.js-line-cause-id')?.value || '';
    const partIdRaw = row.querySelector('.js-line-part-id')?.value || '';
    const serviceIdRaw = row.querySelector('.js-line-service-id')?.value || '';
    const image = row.querySelector('.js-line-image')?.value || '';
    const workNote = row.querySelector('.js-line-work-note')?.value || '';
    const hasAnyValue = description !== '' || amountRaw !== '' || warrantyRaw !== '' || resolutionIdRaw !== '';

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

    if (type === 'labor') {
      item.huong_xu_ly_id = getNumeric(resolutionIdRaw) || null;
      item.nguyen_nhan_id = getNumeric(causeIdRaw) || null;
      item.dich_vu_id = getNumeric(serviceIdRaw) || null;
      item.mo_ta_cong_viec = workNote || null;
    }

    if (type === 'part') {
      item.so_luong = quantity;
      item.don_gia = amount;
      item.so_tien = amount * quantity;
    }

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

const getDraftLaborIds = () => new Set(
  Array.from(laborItemsContainer?.querySelectorAll('.js-line-resolution-id') || [])
    .map((input) => getNumeric(input?.value))
    .filter((id) => id > 0),
);

const getLaborCatalogSymptoms = () => {
  const symptomMap = new Map();

  laborCatalogState.items.forEach((item) => {
    (Array.isArray(item?.trieu_chungs) ? item.trieu_chungs : []).forEach((symptom) => {
      const symptomId = getNumeric(symptom?.id);
      if (symptomId <= 0 || symptomMap.has(symptomId)) {
        return;
      }

      symptomMap.set(symptomId, {
        id: symptomId,
        ten_trieu_chung: symptom?.ten_trieu_chung || 'Triệu chứng',
        dich_vu_id: getNumeric(symptom?.dich_vu_id) || null,
      });
    });
  });

  return Array.from(symptomMap.values()).sort((symptomA, symptomB) => (
    String(symptomA.ten_trieu_chung || '').localeCompare(String(symptomB.ten_trieu_chung || ''), 'vi')
  ));
};

const getLaborCatalogItemsBySymptom = () => {
  const selectedSymptomId = getNumeric(laborCatalogState.selectedSymptomId);

  if (selectedSymptomId <= 0) {
    return laborCatalogState.items;
  }

  return laborCatalogState.items.filter((item) => (
    Array.isArray(item?.trieu_chungs)
      && item.trieu_chungs.some((symptom) => getNumeric(symptom?.id) === selectedSymptomId)
  ));
};

const getLaborCatalogCauses = () => {
  if (getNumeric(laborCatalogState.selectedSymptomId) <= 0) {
    return [];
  }

  const causeMap = new Map();

  getLaborCatalogItemsBySymptom().forEach((item) => {
    const causeId = getNumeric(item?.nguyen_nhan?.id || item?.nguyen_nhan_id);
    if (causeId <= 0 || causeMap.has(causeId)) {
      return;
    }

    causeMap.set(causeId, {
      id: causeId,
      ten_nguyen_nhan: item?.nguyen_nhan?.ten_nguyen_nhan || 'Nguyên nhân',
    });
  });

  return Array.from(causeMap.values()).sort((causeA, causeB) => (
    String(causeA.ten_nguyen_nhan || '').localeCompare(String(causeB.ten_nguyen_nhan || ''), 'vi')
  ));
};

const getLaborCatalogResolutions = () => {
  const selectedCauseId = getNumeric(laborCatalogState.selectedCauseId);
  if (selectedCauseId <= 0) {
    return [];
  }

  const items = getLaborCatalogItemsBySymptom();
  const filteredItems = items.filter((item) => getNumeric(item?.nguyen_nhan?.id || item?.nguyen_nhan_id) === selectedCauseId);

  return filteredItems.sort((itemA, itemB) => (
    String(itemA?.ten_huong_xu_ly || '').localeCompare(String(itemB?.ten_huong_xu_ly || ''), 'vi')
  ));
};

const getSelectedLaborResolution = () => (
  getLaborCatalogResolutions().find((item) => getNumeric(item?.id) === getNumeric(laborCatalogState.selectedResolutionId))
);

const getLaborSearchablePickerState = (type) => laborSearchablePickerState[type] || null;
const getLaborSearchablePickerConfig = (type) => laborSearchablePickers[type] || null;

const closeLaborSearchablePicker = (type, { resetKeyword = true } = {}) => {
  const picker = getLaborSearchablePickerConfig(type);
  const state = getLaborSearchablePickerState(type);

  if (!picker) {
    return;
  }

  picker.panelEl?.setAttribute('hidden', 'hidden');
  picker.triggerEl?.setAttribute('aria-expanded', 'false');
  picker.rootEl?.classList.remove('is-open');

  if (resetKeyword && state) {
    state.keyword = '';
    if (picker.searchEl) {
      picker.searchEl.value = '';
    }
  }
};

const closeAllLaborSearchablePickers = (exceptType = null) => {
  Object.keys(laborSearchablePickers).forEach((type) => {
    if (type === exceptType) {
      return;
    }

    closeLaborSearchablePicker(type);
  });
};

const getVisibleLaborSearchablePickerItems = (type) => {
  const picker = getLaborSearchablePickerConfig(type);
  const state = getLaborSearchablePickerState(type);

  if (!picker || !state) {
    return [];
  }

  const keyword = normalizeDropdownSearchText(state.keyword);
  if (!keyword) {
    return state.items;
  }

  return state.items.filter((item) => (
    normalizeDropdownSearchText(picker.getLabel(item)).includes(keyword)
  ));
};

const renderLaborSearchablePickerOptions = (type) => {
  const picker = getLaborSearchablePickerConfig(type);
  const state = getLaborSearchablePickerState(type);

  if (!picker?.optionsEl || !state) {
    return;
  }

  const items = getVisibleLaborSearchablePickerItems(type);
  const selectedValue = String(picker.selectEl?.value || '');

  if (!items.length) {
    picker.optionsEl.innerHTML = `<div class="dispatch-search-picker__empty">${escapeHtml(picker.emptyText)}</div>`;
    return;
  }

  picker.optionsEl.innerHTML = items.map((item) => {
    const optionValue = String(getNumeric(item?.id));
    const isSelected = optionValue !== '0' && optionValue === selectedValue;

    return `
      <button type="button" class="dispatch-search-picker__option ${isSelected ? 'is-selected' : ''}" data-picker-type="${type}" data-picker-value="${optionValue}" role="option" aria-selected="${isSelected ? 'true' : 'false'}">
        ${escapeHtml(picker.getLabel(item))}
      </button>
    `;
  }).join('');
};

const syncLaborSearchablePicker = (type, items = [], selectedId = null, { disabled = false } = {}) => {
  const picker = getLaborSearchablePickerConfig(type);
  const state = getLaborSearchablePickerState(type);

  if (!picker || !state) {
    return;
  }

  state.items = Array.isArray(items) ? items.slice() : [];

  const selectedItem = state.items.find((item) => getNumeric(item?.id) === getNumeric(selectedId));

  if (picker.triggerLabelEl) {
    picker.triggerLabelEl.textContent = selectedItem ? picker.getLabel(selectedItem) : picker.placeholder;
  }

  if (picker.triggerEl) {
    picker.triggerEl.disabled = disabled;
  }

  if (picker.selectEl) {
    picker.selectEl.disabled = disabled;
  }

  if (disabled) {
    closeLaborSearchablePicker(type);
  }

  renderLaborSearchablePickerOptions(type);
};

const openLaborSearchablePicker = (type) => {
  const picker = getLaborSearchablePickerConfig(type);

  if (!picker?.panelEl || !picker.triggerEl || picker.triggerEl.disabled) {
    return;
  }

  closeAllLaborSearchablePickers(type);
  picker.panelEl.removeAttribute('hidden');
  picker.rootEl?.classList.add('is-open');
  picker.triggerEl.setAttribute('aria-expanded', 'true');
  renderLaborSearchablePickerOptions(type);

  window.requestAnimationFrame(() => {
    picker.searchEl?.focus();
    picker.searchEl?.select();
  });
};

const toggleLaborSearchablePicker = (type) => {
  const picker = getLaborSearchablePickerConfig(type);

  if (!picker?.panelEl) {
    return;
  }

  if (picker.panelEl.hasAttribute('hidden')) {
    openLaborSearchablePicker(type);
    return;
  }

  closeLaborSearchablePicker(type);
};

const applyLaborSearchablePickerSelection = (type, value) => {
  const picker = getLaborSearchablePickerConfig(type);

  if (!picker?.selectEl) {
    return;
  }

  picker.selectEl.value = String(value || '');
  closeLaborSearchablePicker(type);
  picker.selectEl.dispatchEvent(new Event('change', { bubbles: true }));
  picker.triggerEl?.focus();
};

const updateLaborCatalogPicker = () => {
  const symptoms = getLaborCatalogSymptoms();
  const currentSymptomId = getNumeric(laborCatalogState.selectedSymptomId);
  if (currentSymptomId > 0 && !symptoms.some((symptom) => getNumeric(symptom.id) === currentSymptomId)) {
    laborCatalogState.selectedSymptomId = null;
  }

  const causes = getLaborCatalogCauses();
  const currentCauseId = getNumeric(laborCatalogState.selectedCauseId);
  if (currentCauseId > 0 && !causes.some((cause) => getNumeric(cause.id) === currentCauseId)) {
    laborCatalogState.selectedCauseId = null;
  }

  const resolutions = getLaborCatalogResolutions();
  const currentResolutionId = getNumeric(laborCatalogState.selectedResolutionId);
  if (currentResolutionId > 0 && !resolutions.some((item) => getNumeric(item.id) === currentResolutionId)) {
    laborCatalogState.selectedResolutionId = null;
  }

  if (laborSymptomSelect) {
    laborSymptomSelect.innerHTML = [
      '<option value="">Chọn triệu chứng</option>',
      ...symptoms.map((symptom) => (
        `<option value="${symptom.id}" ${getNumeric(laborCatalogState.selectedSymptomId) === getNumeric(symptom.id) ? 'selected' : ''}>${escapeHtml(symptom.ten_trieu_chung || 'Triệu chứng')}</option>`
      )),
    ].join('');
    laborSymptomSelect.disabled = symptoms.length === 0;
  }
  syncLaborSearchablePicker('symptom', symptoms, laborCatalogState.selectedSymptomId, { disabled: symptoms.length === 0 });

  if (laborCauseSelect) {
    laborCauseSelect.innerHTML = [
      '<option value="">Chọn nguyên nhân</option>',
      ...causes.map((cause) => (
        `<option value="${cause.id}" ${getNumeric(laborCatalogState.selectedCauseId) === getNumeric(cause.id) ? 'selected' : ''}>${escapeHtml(cause.ten_nguyen_nhan || 'Nguyên nhân')}</option>`
      )),
    ].join('');
    laborCauseSelect.disabled = causes.length === 0;
  }
  syncLaborSearchablePicker('cause', causes, laborCatalogState.selectedCauseId, { disabled: causes.length === 0 });

  if (laborResolutionSelect) {
    laborResolutionSelect.innerHTML = [
      '<option value="">Chọn hướng xử lý</option>',
      ...resolutions.map((resolution) => (
        `<option value="${resolution.id}" ${getNumeric(laborCatalogState.selectedResolutionId) === getNumeric(resolution.id) ? 'selected' : ''}>${escapeHtml(resolution.ten_huong_xu_ly || 'Hướng xử lý')}</option>`
      )),
    ].join('');
    laborResolutionSelect.disabled = resolutions.length === 0;
  }

  const selectedResolution = getSelectedLaborResolution();
  const alreadyAdded = selectedResolution && getDraftLaborIds().has(getNumeric(selectedResolution.id));
  addLaborItemButton.disabled = !selectedResolution || getNumeric(selectedResolution?.gia_tham_khao) <= 0 || alreadyAdded;

  if (!laborCatalogState.items.length) {
    laborCatalogStatus.textContent = currentCostBooking
      ? 'Chưa có danh mục tiền công cho nhóm dịch vụ của đơn này.'
      : 'Mở đơn để tải danh mục tiền công.';
    laborResolutionPrice.textContent = 'Cần đồng bộ danh mục hướng xử lý để thợ chọn từ dropdown.';
    return;
  }

  if (!laborCatalogState.selectedSymptomId) {
    laborCatalogStatus.textContent = 'Chọn triệu chứng trước để lọc nguyên nhân tương ứng.';
    laborResolutionPrice.textContent = `Hệ thống đang có ${laborCatalogState.items.length} hướng xử lý theo dịch vụ của đơn.`;
    return;
  }

  if (!laborCatalogState.selectedCauseId) {
    laborCatalogStatus.textContent = 'Tiếp tục chọn nguyên nhân để thu hẹp danh sách hướng xử lý.';
    laborResolutionPrice.textContent = `${causes.length} nguyên nhân phù hợp với triệu chứng đang chọn.`;
    return;
  }

  if (!selectedResolution) {
    laborCatalogStatus.textContent = 'Chọn hướng xử lý để thêm đúng dòng tiền công.';
    laborResolutionPrice.textContent = `${resolutions.length} hướng xử lý đang khớp với triệu chứng và nguyên nhân.`;
    return;
  }

  laborCatalogStatus.textContent = selectedResolution.mo_ta_cong_viec
    ? selectedResolution.mo_ta_cong_viec
    : 'Hướng xử lý này chưa có mô tả công việc chi tiết.';
  laborResolutionPrice.textContent = alreadyAdded
    ? 'Hướng xử lý này đã có trong bảng tiền công.'
    : `Giá tham khảo: ${formatMoney(selectedResolution.gia_tham_khao)}. Chọn "Thêm tiền công" để đưa vào bảng.`;
};

const loadLaborCatalogForBooking = async (booking) => {
  const activeBookingId = getNumeric(booking?.id);
  const serviceIds = getBookingServiceIds(booking);
  const cacheKey = serviceIds.slice().sort((a, b) => a - b).join(',');

  laborCatalogState.selectedSymptomId = null;
  laborCatalogState.selectedCauseId = null;
  laborCatalogState.selectedResolutionId = null;

  if (!serviceIds.length) {
    laborCatalogState.items = [];
    updateLaborCatalogPicker();
    return;
  }

  if (laborCatalogState.cache.has(cacheKey)) {
    if (getNumeric(currentCostBooking?.id) !== activeBookingId) {
      return;
    }
    laborCatalogState.items = laborCatalogState.cache.get(cacheKey) || [];
    updateLaborCatalogPicker();
    return;
  }

  laborCatalogState.items = [];
  updateLaborCatalogPicker();
  laborCatalogStatus.textContent = 'Đang tải danh mục triệu chứng, nguyên nhân và hướng xử lý...';
  laborResolutionPrice.textContent = 'Hệ thống đang chuẩn bị dropdown tiền công cho đơn này.';

  try {
    const params = new URLSearchParams();
    serviceIds.forEach((serviceId) => params.append('dich_vu_ids[]', serviceId));
    const response = await callApi(`/huong-xu-ly?${params.toString()}`, 'GET');

    if (getNumeric(currentCostBooking?.id) !== activeBookingId) {
      return;
    }

    if (!response.ok) {
      throw new Error(response.data?.message || 'Không thể tải danh mục tiền công.');
    }

    laborCatalogState.items = Array.isArray(response.data) ? response.data : [];
    laborCatalogState.cache.set(cacheKey, laborCatalogState.items);
    updateLaborCatalogPicker();
  } catch (error) {
    if (getNumeric(currentCostBooking?.id) !== activeBookingId) {
      return;
    }

    laborCatalogState.items = [];
    updateLaborCatalogPicker();
    showToast(error.message || 'Lỗi khi tải hướng xử lý theo dịch vụ.', 'error');
  }
};

const addSelectedLaborCatalogItem = () => {
  const selectedResolution = getSelectedLaborResolution();

  if (!selectedResolution) {
    showToast('Vui lòng chọn đầy đủ triệu chứng, nguyên nhân và hướng xử lý.', 'error');
    return;
  }

  const resolutionId = getNumeric(selectedResolution.id);
  if (resolutionId <= 0 || getNumeric(selectedResolution.gia_tham_khao) <= 0) {
    showToast('Hướng xử lý này chưa có giá tham khảo để thêm vào tiền công.', 'error');
    return;
  }

  if (getDraftLaborIds().has(resolutionId)) {
    showToast('Hướng xử lý này đã có trong bảng tiền công.', 'error');
    updateLaborCatalogPicker();
    return;
  }

  const selectedSymptom = getLaborCatalogSymptoms().find((symptom) => (
    getNumeric(symptom.id) === getNumeric(laborCatalogState.selectedSymptomId)
  ));

  appendCostItemRow(laborItemsContainer, 'labor', {
    huong_xu_ly_id: resolutionId,
    nguyen_nhan_id: getNumeric(selectedResolution?.nguyen_nhan?.id || selectedResolution?.nguyen_nhan_id),
    dich_vu_id: getNumeric(selectedSymptom?.dich_vu_id || selectedResolution?.dich_vus?.[0]?.id),
    mo_ta_cong_viec: selectedResolution.mo_ta_cong_viec || '',
    ten_trieu_chung: selectedSymptom?.ten_trieu_chung || '',
    ten_nguyen_nhan: selectedResolution?.nguyen_nhan?.ten_nguyen_nhan || '',
    noi_dung: selectedResolution.ten_huong_xu_ly || 'Hướng xử lý',
    so_tien: getNumeric(selectedResolution.gia_tham_khao),
  });

  laborCatalogState.selectedResolutionId = null;
  updateLaborCatalogPicker();
  updateCostEstimate();
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

const getPartCatalogKeyword = () => String(partCatalogSearch?.value || '').trim().toLocaleLowerCase('vi-VN');

const getPartCatalogItemName = (item) => String(item?.ten_linh_kien || '').trim();

const getPartCatalogServiceName = (item) => item?.dich_vu?.ten_dich_vu || (currentCostBooking ? getBookingServiceNames(currentCostBooking) : 'Dịch vụ');

const getVisiblePartCatalogItems = () => {
  const keyword = getPartCatalogKeyword();
  const filteredItems = partCatalogState.items.filter((item) => getPartCatalogItemName(item)
    .toLocaleLowerCase('vi-VN')
    .includes(keyword));

  if (!keyword) {
    return filteredItems;
  }

  return filteredItems.slice().sort((itemA, itemB) => {
    const nameA = getPartCatalogItemName(itemA).toLocaleLowerCase('vi-VN');
    const nameB = getPartCatalogItemName(itemB).toLocaleLowerCase('vi-VN');
    const prefixDiff = Number(!nameA.startsWith(keyword)) - Number(!nameB.startsWith(keyword));

    if (prefixDiff !== 0) {
      return prefixDiff;
    }

    const matchDiff = nameA.indexOf(keyword) - nameB.indexOf(keyword);
    if (matchDiff !== 0) {
      return matchDiff;
    }

    const lengthDiff = nameA.length - nameB.length;
    if (lengthDiff !== 0) {
      return lengthDiff;
    }

    return nameA.localeCompare(nameB, 'vi');
  });
};

const getKnownPartCatalogItems = () => {
  const itemMap = new Map();

  [...partCatalogState.items, ...partCatalogState.fallbackItems].forEach((item) => {
    const partId = getNumeric(item?.id);
    if (partId > 0 && !itemMap.has(partId)) {
      itemMap.set(partId, item);
    }
  });

  return Array.from(itemMap.values());
};

const getSuggestionPartCatalogItems = (visibleItems = getVisiblePartCatalogItems()) => (
  visibleItems.length ? visibleItems : partCatalogState.fallbackItems
);

const hasLoadedFallbackSuggestionsForKeyword = (keyword) => {
  const cacheKey = String(keyword || '').trim().toLocaleLowerCase('vi-VN');
  return cacheKey !== '' && partCatalogState.fallbackCache.has(cacheKey);
};

const setPartCatalogSuggestionsVisible = (visible) => {
  if (!partCatalogSuggestions) {
    return;
  }

  partCatalogSuggestions.hidden = !visible;

  if (partCatalogSearch) {
    partCatalogSearch.setAttribute('aria-expanded', visible ? 'true' : 'false');
  }
};

const hidePartCatalogSuggestions = () => {
  partCatalogState.activeSuggestionIndex = -1;

  if (!partCatalogSuggestions) {
    return;
  }

  partCatalogSuggestions.innerHTML = '';
  setPartCatalogSuggestionsVisible(false);
};

const renderPartCatalogSuggestions = (visibleItems = getSuggestionPartCatalogItems()) => {
  if (!partCatalogSuggestions) {
    return;
  }

  const rawKeyword = String(partCatalogSearch?.value || '').trim();
  if (!rawKeyword) {
    hidePartCatalogSuggestions();
    return;
  }

  if (!visibleItems.length) {
    partCatalogSuggestions.innerHTML = '<div class="dispatch-part-suggestion-empty">Không tìm thấy linh kiện phù hợp với từ khóa đang nhập.</div>';
    setPartCatalogSuggestionsVisible(true);
    return;
  }

  const suggestionItems = visibleItems.slice(0, 6);
  if (partCatalogState.activeSuggestionIndex >= suggestionItems.length) {
    partCatalogState.activeSuggestionIndex = suggestionItems.length - 1;
  }

  partCatalogSuggestions.innerHTML = suggestionItems.map((item, index) => {
    const partId = getNumeric(item?.id);
    const hasPrice = getNumeric(item?.gia) > 0;
    const isSelected = partCatalogState.selectedIds.has(partId);
    const serviceName = getPartCatalogServiceName(item);

    return `
      <button
        type="button"
        class="dispatch-part-suggestion js-part-catalog-suggestion ${index === partCatalogState.activeSuggestionIndex ? 'is-active' : ''} ${isSelected ? 'is-selected' : ''} ${hasPrice ? '' : 'is-disabled'}"
        data-part-id="${partId}"
        data-index="${index}"
        ${hasPrice ? '' : 'disabled'}
      >
        <span class="dispatch-part-suggestion__thumb">
          ${item?.hinh_anh
            ? `<img src="${escapeHtml(item.hinh_anh)}" alt="${escapeHtml(getPartCatalogItemName(item) || 'Linh kiện')}">`
            : '<span class="material-symbols-outlined">image_not_supported</span>'}
        </span>
        <span class="dispatch-part-suggestion__body">
          <span class="dispatch-part-suggestion__title">${escapeHtml(getPartCatalogItemName(item) || 'Linh kiện')}</span>
          <span class="dispatch-part-suggestion__meta">${escapeHtml(serviceName)}</span>
        </span>
        <span class="dispatch-part-suggestion__aside">
          <strong class="dispatch-part-suggestion__price">${hasPrice ? formatMoney(item?.gia) : 'Chưa có giá'}</strong>
          ${isSelected ? '<span class="dispatch-part-suggestion__badge">Đã chọn</span>' : ''}
        </span>
      </button>
    `;
  }).join('');

  setPartCatalogSuggestionsVisible(true);
};

const loadFallbackPartSuggestions = async () => {
  const rawKeyword = String(partCatalogSearch?.value || '').trim();
  const visibleItems = getVisiblePartCatalogItems();

  if (!rawKeyword || visibleItems.length) {
    partCatalogState.fallbackItems = [];
    renderPartCatalogResults();
    return;
  }

  const cacheKey = rawKeyword.toLocaleLowerCase('vi-VN');
  if (partCatalogState.fallbackCache.has(cacheKey)) {
    partCatalogState.fallbackItems = partCatalogState.fallbackCache.get(cacheKey) || [];
    renderPartCatalogResults();
    return;
  }

  const requestId = partCatalogState.searchRequestId;

  try {
    const params = new URLSearchParams({ keyword: rawKeyword });
    const response = await callApi(`/linh-kien?${params.toString()}`, 'GET');

    if (requestId !== partCatalogState.searchRequestId) {
      return;
    }

    if (!response.ok) {
      throw new Error(response.data?.message || 'Không thể tìm linh kiện.');
    }

    const items = Array.isArray(response.data) ? response.data : [];
    partCatalogState.fallbackItems = items;
    partCatalogState.fallbackCache.set(cacheKey, items);
    renderPartCatalogResults();
  } catch (error) {
    if (requestId !== partCatalogState.searchRequestId) {
      return;
    }

    partCatalogState.fallbackItems = [];
    renderPartCatalogResults();
  }
};

const refreshPartCatalogSearch = async () => {
  partCatalogState.searchRequestId += 1;
  renderPartCatalogResults();
  await loadFallbackPartSuggestions();
};

const setPartCatalogSelectionState = (partId, isSelected) => {
  if (partId <= 0) {
    return;
  }

  if (isSelected) {
    partCatalogState.selectedIds.add(partId);
  } else {
    partCatalogState.selectedIds.delete(partId);
  }
};

const renderPartCatalogResults = () => {
  if (!partCatalogResults || !partCatalogStatus) {
    return;
  }

  const rawKeyword = String(partCatalogSearch?.value || '').trim();
  const visibleItems = getVisiblePartCatalogItems();
  const suggestionItems = getSuggestionPartCatalogItems(visibleItems);
  const isShowingFallback = !visibleItems.length && suggestionItems.length > 0;

  if (!partCatalogState.items.length) {
    if (!currentCostBooking) {
      partCatalogStatus.textContent = 'Mở đơn để tải danh mục linh kiện đúng theo dịch vụ của đơn.';
    } else if (isShowingFallback) {
      partCatalogStatus.textContent = `Dịch vụ của đơn này chưa có linh kiện mẫu. Đang gợi ý ${suggestionItems.length} linh kiện từ toàn bộ kho theo từ khóa "${rawKeyword}".`;
    } else if (rawKeyword && hasLoadedFallbackSuggestionsForKeyword(rawKeyword)) {
      partCatalogStatus.textContent = `Không tìm thấy linh kiện nào trong toàn bộ kho theo từ khóa "${rawKeyword}".`;
    } else if (rawKeyword) {
      partCatalogStatus.textContent = 'Dịch vụ của đơn này chưa có linh kiện mẫu. Tiếp tục nhập để tìm trên toàn bộ kho linh kiện.';
    } else {
      partCatalogStatus.textContent = 'Dịch vụ của đơn này chưa có linh kiện mẫu hoặc chưa đồng bộ danh mục.';
    }
    partCatalogResults.innerHTML = '';
    renderPartCatalogSuggestions(suggestionItems);
    updateSelectedPartsButtonState();
    return;
  }

  partCatalogStatus.textContent = visibleItems.length
    ? `Đang hiển thị ${visibleItems.length}/${partCatalogState.items.length} linh kiện phù hợp với dịch vụ của đơn.`
    : isShowingFallback
      ? `Không thấy linh kiện khớp trong dịch vụ của đơn. Đang gợi ý ${suggestionItems.length} linh kiện từ toàn bộ kho theo từ khóa "${rawKeyword}".`
      : hasLoadedFallbackSuggestionsForKeyword(rawKeyword)
        ? `Không tìm thấy linh kiện khớp với từ khóa "${rawKeyword}" trong dịch vụ của đơn hoặc toàn bộ kho.`
      : `Không tìm thấy linh kiện khớp với từ khóa "${partCatalogSearch?.value || ''}".`;

  partCatalogResults.innerHTML = visibleItems.map((item) => {
    const partId = getNumeric(item?.id);
    const hasPrice = getNumeric(item?.gia) > 0;
    const isSelected = partCatalogState.selectedIds.has(partId);
    const serviceName = getPartCatalogServiceName(item);

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

  renderPartCatalogSuggestions(suggestionItems);
  updateSelectedPartsButtonState();
};

const selectPartCatalogSuggestion = (partId) => {
  const selectedItem = getKnownPartCatalogItems().find((item) => getNumeric(item?.id) === partId);
  if (!selectedItem || getNumeric(selectedItem?.gia) <= 0) {
    return;
  }

  setPartCatalogSelectionState(partId, true);

  if (partCatalogSearch) {
    partCatalogSearch.value = getPartCatalogItemName(selectedItem);
  }

  partCatalogState.activeSuggestionIndex = -1;
  renderPartCatalogResults();
  hidePartCatalogSuggestions();

  const selectedCheckbox = partCatalogResults?.querySelector(`.js-part-catalog-check[value="${partId}"]`);
  selectedCheckbox?.closest('.dispatch-part-option')?.scrollIntoView({
    block: 'nearest',
    behavior: 'smooth',
  });
};

const loadPartCatalogForBooking = async (booking) => {
  const activeBookingId = getNumeric(booking?.id);
  const serviceIds = getBookingServiceIds(booking);
  const cacheKey = serviceIds.slice().sort((a, b) => a - b).join(',');

  partCatalogState.selectedIds = new Set();
  partCatalogState.activeSuggestionIndex = -1;
  partCatalogState.fallbackItems = [];
  partCatalogState.searchRequestId += 1;
  hidePartCatalogSuggestions();
  if (partCatalogResults) {
    partCatalogResults.innerHTML = '';
  }
  updateSelectedPartsButtonState();

  if (!serviceIds.length) {
    partCatalogState.items = [];
    renderPartCatalogResults();
    return;
  }

  if (partCatalogState.cache.has(cacheKey)) {
    if (getNumeric(currentCostBooking?.id) !== activeBookingId) {
      return;
    }
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

    if (getNumeric(currentCostBooking?.id) !== activeBookingId) {
      return;
    }

    if (!response.ok) {
      throw new Error(response.data?.message || 'Không thể tải danh mục linh kiện.');
    }

    const items = Array.isArray(response.data) ? response.data : [];
    partCatalogState.items = items;
    partCatalogState.cache.set(cacheKey, items);
    renderPartCatalogResults();
  } catch (error) {
    if (getNumeric(currentCostBooking?.id) !== activeBookingId) {
      return;
    }
    partCatalogState.items = [];
    renderPartCatalogResults();
    showToast(error.message || 'Lỗi khi tải linh kiện theo dịch vụ.', 'error');
  }
};

const addSelectedCatalogPartsToDraft = () => {
  const selectedParts = getKnownPartCatalogItems().filter((item) => partCatalogState.selectedIds.has(getNumeric(item?.id)));

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
      don_gia: partPrice,
      so_luong: 1,
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
                ${type === 'part'
                  ? escapeHtml(formatPartQuantityMeta(item, warrantyMeta?.warrantyLabel || formatWarrantyText(item?.bao_hanh_thang)))
                  : 'Tiền công sửa chữa'}
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

const getBookingCardDateLabel = (booking) => {
  if (!booking?.ngay_hen) {
    return 'Chưa chốt ngày';
  }

  const bookingDate = new Date(booking.ngay_hen);
  if (Number.isNaN(bookingDate.getTime())) {
    return 'Chưa chốt ngày';
  }

  const weekdayMap = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
  const weekday = weekdayMap[bookingDate.getDay()] || 'Lịch hẹn';

  return `${weekday}, ${bookingDate.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
  })}`;
};

const isTodayBooking = (booking) => String(booking?.ngay_hen || '').slice(0, 10) === getTodayKey();

const getScheduleLabel = (booking) => {
  const timeRange = booking?.khung_gio_hen || 'Chưa chọn giờ';
  return isTodayBooking(booking) ? `${timeRange} (Hôm nay)` : `${timeRange} · ${getBookingDateLabel(booking)}`;
};

const getBookingPrimaryTimeLabel = (booking) => {
  const timeRange = String(booking?.khung_gio_hen || '').trim();
  const startTime = timeRange.split('-')[0]?.trim();

  if (!startTime) {
    return 'Chưa chốt giờ';
  }

  const normalized = startTime.replace(/\./g, ':');
  const match = normalized.match(/^(\d{1,2}):(\d{2})$/);
  if (!match) {
    return startTime;
  }

  const hours = Number(match[1]);
  const minutes = Number(match[2]);
  if (!Number.isFinite(hours) || !Number.isFinite(minutes)) {
    return startTime;
  }

  const candidate = new Date();
  candidate.setHours(hours, minutes, 0, 0);
  return candidate.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  });
};

const updateBoardSurface = (status = window.currentStatus, totalItems = 0) => {
  const view = getBoardViewConfig(status);
  const totalPages = getTotalPages(status, window.currentScope);
  const startIndex = totalItems ? ((window.currentPage - 1) * JOBS_PER_PAGE) + 1 : 0;
  const endIndex = totalItems ? Math.min(window.currentPage * JOBS_PER_PAGE, totalItems) : 0;

  if (boardIntroEyebrow) {
    boardIntroEyebrow.textContent = view.eyebrow;
  }
  if (boardIntroTitle) {
    boardIntroTitle.textContent = view.title;
  }
  if (boardIntroSubtitle) {
    boardIntroSubtitle.textContent = view.subtitle;
  }
  if (boardStatusChip) {
    boardStatusChip.textContent = `${view.badgeLabel} · ${totalItems} lịch`;
  }
  if (boardScopeChip) {
    boardScopeChip.textContent = window.currentScope === 'today' ? 'Phạm vi: hôm nay' : 'Phạm vi: toàn bộ';
  }
  if (boardControlsMeta) {
    boardControlsMeta.textContent = totalItems
      ? `Hiển thị ${startIndex}-${endIndex} / ${totalItems} lịch · Trang ${window.currentPage}/${totalPages}`
      : 'Chưa có lịch phù hợp trong bộ lọc hiện tại';
  }
};

const getLocationLabel = (booking) => booking?.loai_dat_lich === 'at_home' ? 'Sửa tại nhà' : 'Sửa tại cửa hàng';
const getStatusTone = (booking) => statusToneMap[booking?.trang_thai] || 'upcoming';
const getStatusLabel = (booking) => statusLabelMap[booking?.trang_thai] || 'Đơn công việc';
const getStatusCodeLabel = (booking) => String(booking?.trang_thai || 'booking')
  .replace(/[^a-z0-9_]+/gi, '_')
  .replace(/^_+|_+$/g, '')
  .toUpperCase();

const boardStatusPriority = {
  da_xac_nhan: 1,
  dang_lam: 2,
  cho_hoan_thanh: 3,
  cho_thanh_toan: 4,
  cho_xac_nhan: 5,
  da_xong: 6,
  da_huy: 7,
};

const parseBookingStartDateTime = (booking) => {
  const dateText = String(booking?.ngay_hen || '').slice(0, 10);
  if (!dateText) {
    return Number.MAX_SAFE_INTEGER;
  }

  const timeRange = String(booking?.khung_gio_hen || '');
  const startTime = timeRange.split('-')[0]?.trim() || '00:00';
  const candidate = new Date(`${dateText}T${startTime.length === 5 ? `${startTime}:00` : startTime}`);
  return Number.isNaN(candidate.getTime()) ? Number.MAX_SAFE_INTEGER : candidate.getTime();
};

const compareBoardBookings = (left, right) => {
  const startDiff = parseBookingStartDateTime(left) - parseBookingStartDateTime(right);
  if (startDiff !== 0) {
    return startDiff;
  }

  const statusDiff = (boardStatusPriority[left?.trang_thai] || 99) - (boardStatusPriority[right?.trang_thai] || 99);
  if (statusDiff !== 0) {
    return statusDiff;
  }

  return getNumeric(left?.id) - getNumeric(right?.id);
};

const getScopedBookings = (status = window.currentStatus, scope = window.currentScope) => {
  const filteredByStatus = status === 'all'
    ? [...window.allBookings]
    : window.allBookings.filter((booking) => (statusFilters[status] || statusFilters.all)(booking));

  const filteredByScope = scope === 'today'
    ? filteredByStatus.filter((booking) => isTodayBooking(booking))
    : filteredByStatus;

  return filteredByScope.sort(compareBoardBookings);
};

const getFilterCount = (status) => getScopedBookings(status, 'all').length;
const getTotalPages = (status = window.currentStatus, scope = window.currentScope) => Math.max(1, Math.ceil(getScopedBookings(status, scope).length / JOBS_PER_PAGE));

const buildPaginationModel = (totalPages, currentPage) => {
  if (totalPages <= 1) {
    return [1];
  }

  const items = new Set([1, totalPages, currentPage, currentPage - 1, currentPage + 1]);
  const normalized = Array.from(items)
    .filter((page) => page >= 1 && page <= totalPages)
    .sort((left, right) => left - right);

  return normalized.flatMap((page, index) => {
    const previous = normalized[index - 1];
    if (previous && page - previous > 1) {
      return ['ellipsis', page];
    }
    return [page];
  });
};

const getFirstAddressSegment = (address) => String(address || '')
  .split(',')
  .map((part) => part.trim())
  .filter(Boolean)[0] || 'Điểm đến tiếp theo';

const estimateDriveMinutes = (booking) => {
  const distanceKm = getNumeric(booking?.khoang_cach);
  if (distanceKm <= 0) {
    return null;
  }

  return Math.max(8, Math.round(distanceKm * 2.6));
};

const renderLoadingState = () => {
  updateBoardSurface(window.currentStatus, 0);
  if (bookingPaginationWrap) {
    bookingPaginationWrap.hidden = true;
  }
  bookingsContainer.innerHTML = `
    <div class="dispatch-board-empty">
      <div>
        <span class="material-symbols-outlined">hourglass_top</span>
        <h3>Đang tải lịch làm việc</h3>
        <p>Hệ thống đang đồng bộ các đơn sửa chữa của bạn.</p>
      </div>
    </div>
  `;
};

const renderEmptyState = (scope = window.currentScope) => {
  bookingsContainer.innerHTML = `
    <div class="dispatch-board-empty">
      <div>
        <span class="material-symbols-outlined">inventory_2</span>
        <h3>${scope === 'today' ? 'Không có lịch trong hôm nay' : 'Không có lịch làm việc phù hợp'}</h3>
        <p>${scope === 'today'
          ? 'Hệ thống chưa ghi nhận đơn nào diễn ra trong hôm nay cho tài khoản này.'
          : 'Khi có lịch sửa chữa mới, hệ thống sẽ hiển thị trực tiếp tại đây.'}</p>
      </div>
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

const getBookingLaborTotal = (booking) => getBookingLaborItems(booking)
  .reduce((total, item) => total + getNumeric(item?.so_tien), 0);

const getBookingPartsTotal = (booking) => getBookingPartItems(booking)
  .reduce((total, item) => total + getNumeric(item?.so_tien), 0);

const getBookingSurchargeTotal = (booking) => getNumeric(booking?.phi_di_lai) + getNumeric(booking?.tien_thue_xe);

const getPaymentStageMeta = (booking) => {
  if (isCashPaymentBooking(booking)) {
    return {
      eyebrow: 'Chưa thanh toán COD',
      method: 'Tiền mặt',
      hint: booking.trang_thai === 'cho_hoan_thanh'
        ? 'Bạn đã báo hoàn thành. Chỉ còn bước thu đủ tiền mặt rồi xác nhận để chốt đơn.'
        : 'Đơn đang giữ phương thức tiền mặt. Kiểm tra đã nhận đủ tiền trước khi xác nhận hoàn tất.',
      tone: 'cash',
    };
  }

  return {
    eyebrow: 'Chờ thanh toán online',
    method: 'Chuyển khoản',
    hint: 'Hệ thống đang chờ khách hoàn tất giao dịch trực tuyến. Khi thanh toán thành công, đơn sẽ tự chuyển hoàn thành.',
    tone: 'transfer',
  };
};

const renderBoardPaymentPanel = (booking) => {
  if (!['cho_thanh_toan', 'cho_hoan_thanh'].includes(booking?.trang_thai)) {
    return '';
  }

  const paymentMeta = getPaymentStageMeta(booking);

  return `
    <div class="dispatch-board-payment dispatch-board-payment--${paymentMeta.tone}">
      <div class="dispatch-board-payment__top">
        <div>
          <span class="dispatch-board-payment__eyebrow">${escapeHtml(paymentMeta.eyebrow)}</span>
          <div class="dispatch-board-payment__total">${formatMoney(getBookingTotal(booking))}</div>
        </div>
        <span class="dispatch-board-payment__method">${escapeHtml(paymentMeta.method)}</span>
      </div>

      <div class="dispatch-board-payment__stats">
        <div class="dispatch-board-payment__stat">
          <span class="dispatch-board-payment__stat-label">Tiền công</span>
          <span class="dispatch-board-payment__stat-value">${formatMoney(getBookingLaborTotal(booking))}</span>
        </div>
        <div class="dispatch-board-payment__stat">
          <span class="dispatch-board-payment__stat-label">Linh kiện</span>
          <span class="dispatch-board-payment__stat-value">${formatMoney(getBookingPartsTotal(booking))}</span>
        </div>
        <div class="dispatch-board-payment__stat">
          <span class="dispatch-board-payment__stat-label">Phụ phí</span>
          <span class="dispatch-board-payment__stat-value">${formatMoney(getBookingSurchargeTotal(booking))}</span>
        </div>
      </div>

      <p class="dispatch-board-payment__hint">${escapeHtml(paymentMeta.hint)}</p>
    </div>
  `;
};

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
      : '<div class="dispatch-inline-note">Đơn đã được báo hoàn thành và đang chờ khách thanh toán trực tuyến. Hệ thống sẽ tự chốt đơn khi giao dịch thành công.</div>';
  }

  if (booking.trang_thai === 'cho_hoan_thanh') {
    return '<div class="dispatch-inline-note">Khách thanh toán tiền mặt trực tiếp. Sau khi thu đủ tiền, bạn cần xác nhận để chốt hoàn tất đơn.</div>';
  }

  if (booking.trang_thai === 'da_xong') {
    return '<div class="dispatch-inline-note">Công việc đã hoàn tất và được lưu vào lịch sử xử lý.</div>';
  }

  return '';
};

const stripHtmlTags = (value = '') => String(value || '').replace(/<br\s*\/?>/gi, ' ').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();

const getServiceIconName = (booking) => {
  const haystack = getBookingServiceNames(booking).toLowerCase();

  if (haystack.includes('giặt')) {
    return 'local_laundry_service';
  }
  if (haystack.includes('lạnh') || haystack.includes('điều hòa')) {
    return 'mode_fan';
  }
  if (haystack.includes('tủ lạnh')) {
    return 'kitchen';
  }
  if (haystack.includes('tivi')) {
    return 'tv';
  }
  if (haystack.includes('nước')) {
    return 'water_drop';
  }

  return 'home_repair_service';
};

const getBoardNoteConfig = (booking) => {
  if (booking.trang_thai === 'da_xac_nhan') {
    return {
      tone: 'default',
      icon: 'info',
      title: 'Ghi chú nhắc bắt đầu đúng giờ',
      body: booking.mo_ta_van_de || 'Khách đã chốt lịch. Vui lòng đến đúng khung giờ để tránh trễ hẹn.',
    };
  }

  if (booking.trang_thai === 'dang_lam') {
    if (hasUpdatedPricing(booking)) {
      return {
        tone: 'info',
        icon: 'price_check',
        title: 'Dịch vụ đang sửa và đã có báo giá',
        body: `Tổng chi phí tạm tính hiện tại là ${formatMoney(getBookingTotal(booking))}. Khi thiết bị đã ổn định, bạn có thể báo hoàn thành ngay trên thẻ này.`,
      };
    }

    return {
      tone: 'danger',
      icon: 'warning',
      title: 'Dịch vụ đang sửa, chờ cập nhật chi phí',
      body: 'Hãy điền tiền công, linh kiện và phụ phí trước khi chuyển sang bước báo hoàn thành cho khách.',
    };
  }

  if (booking.trang_thai === 'cho_thanh_toan' || booking.trang_thai === 'cho_hoan_thanh') {
    return isCashPaymentBooking(booking)
      ? {
          tone: 'info',
          icon: 'payments',
          title: 'Đơn đang chờ xác nhận COD',
          body: 'Chỉ xác nhận hoàn tất sau khi bạn đã thu đủ tiền trực tiếp từ khách hàng.',
        }
      : {
          tone: 'info',
          icon: 'credit_card',
          title: 'Đơn đang chờ thanh toán online',
          body: 'Hệ thống sẽ tự chốt đơn khi giao dịch trực tuyến của khách thành công.',
        };
  }

  if (booking.trang_thai === 'da_xong') {
    return {
      tone: 'success',
      icon: 'task_alt',
      title: 'Công việc đã hoàn tất',
      body: `Tổng chi phí đã chốt là ${formatMoney(getBookingTotal(booking))}. Đơn này hiện nằm trong lịch sử xử lý.`,
    };
  }

  if (booking.trang_thai === 'da_huy') {
    return {
      tone: 'danger',
      icon: 'cancel',
      title: 'Đơn đã bị hủy',
      body: 'Giữ lại chi tiết để đối chiếu nếu cần kiểm tra nguyên nhân hủy hoặc lịch sử làm việc.',
    };
  }

  return {
    tone: 'info',
    icon: 'schedule',
    title: 'Đơn đang chờ xác nhận',
    body: 'Kiểm tra kỹ mô tả và thông tin liên hệ trước khi thực hiện các bước tiếp theo.',
  };
};

const renderBoardNote = (booking) => {
  const note = getBoardNoteConfig(booking);
  const toneClass = note.tone && note.tone !== 'default' ? ` dispatch-board-note--${note.tone}` : '';

  return `
    <div class="dispatch-board-note${toneClass}">
      <div class="dispatch-board-note__title">
        <span class="material-symbols-outlined">${escapeHtml(note.icon)}</span>
        <span>${escapeHtml(note.title)}</span>
      </div>
      <p class="dispatch-board-note__body">${escapeHtml(note.body)}</p>
    </div>
  `;
};

const renderBoardButton = ({
  variant = 'secondary',
  icon = 'open_in_new',
  label = '',
  title = '',
  onclick = '',
  href = '',
  disabled = false,
}) => {
  const className = variant === 'main'
    ? 'dispatch-board-card__action-main'
    : variant === 'main-warm'
      ? 'dispatch-board-card__action-main dispatch-board-card__action-main--warm'
      : variant === 'main-success'
        ? 'dispatch-board-card__action-main dispatch-board-card__action-main--success'
        : variant === 'main-disabled'
          ? 'dispatch-board-card__action-main dispatch-board-card__action-main--disabled'
          : variant === 'icon'
            ? 'dispatch-board-card__action-icon'
            : 'dispatch-board-card__action-secondary';
  const labelHtml = label ? `<span>${escapeHtml(label)}</span>` : '';
  const titleAttr = title ? ` title="${escapeHtml(title)}"` : '';

  if (href) {
    return `
      <a href="${escapeHtml(href)}" class="${className}"${titleAttr}>
        <span class="material-symbols-outlined">${escapeHtml(icon)}</span>
        ${labelHtml}
      </a>
    `;
  }

  return `
    <button type="button" class="${className}"${disabled ? ' disabled' : ''}${titleAttr}${onclick && !disabled ? ` onclick="${onclick}"` : ''}>
      <span class="material-symbols-outlined">${escapeHtml(icon)}</span>
      ${labelHtml}
    </button>
  `;
};

const renderActionButtons = (booking) => {
  const actions = [];
  const utilityActions = [];
  const pricingReady = booking.trang_thai === 'dang_lam' ? hasUpdatedPricing(booking) : false;

  if (isClaimableMarketBooking(booking)) {
    actions.push(renderBoardButton({
      variant: 'main-success',
      icon: 'assignment_turned_in',
      label: 'Nhận đơn',
      onclick: `claimJob(${booking.id})`,
    }));
    actions.push(renderBoardButton({
      variant: 'secondary',
      icon: 'visibility',
      label: 'Chi tiết',
      onclick: `openViewDetailsModal(${booking.id})`,
    }));
  } else if (isAssignedPendingBooking(booking)) {
    actions.push(renderBoardButton({
      variant: 'main-success',
      icon: 'task_alt',
      label: 'Xác nhận đơn',
      onclick: `updateStatus(${booking.id}, 'da_xac_nhan')`,
    }));
    actions.push(renderBoardButton({
      variant: 'secondary',
      icon: 'visibility',
      label: 'Chi tiết',
      onclick: `openViewDetailsModal(${booking.id})`,
    }));
  } else if (booking.trang_thai === 'da_xac_nhan') {
    actions.push(renderBoardButton({
      variant: 'main',
      icon: 'play_arrow',
      label: 'Bắt đầu sửa',
      onclick: `updateStatus(${booking.id}, 'dang_lam')`,
    }));
    actions.push(renderBoardButton({
      variant: 'secondary',
      icon: 'visibility',
      label: 'Chi tiết',
      onclick: `openViewDetailsModal(${booking.id})`,
    }));
  } else if (booking.trang_thai === 'dang_lam') {
    actions.push(renderBoardButton({
      variant: pricingReady ? 'main-warm' : 'main',
      icon: pricingReady ? 'task_alt' : 'price_change',
      label: pricingReady ? 'Báo hoàn thành' : 'Cập nhật giá',
      onclick: pricingReady ? `openCompleteModal(${booking.id})` : `openCostModal(${booking.id})`,
      title: pricingReady ? 'Sẵn sàng báo hoàn thành' : 'Cập nhật bảng giá sửa chữa',
    }));
    actions.push(renderBoardButton({
      variant: 'secondary',
      icon: pricingReady ? 'price_change' : 'visibility',
      label: pricingReady ? 'Cập nhật giá' : 'Chi tiết',
      onclick: pricingReady ? `openCostModal(${booking.id})` : `openViewDetailsModal(${booking.id})`,
    }));
    if (pricingReady) {
      utilityActions.push(renderBoardButton({
        variant: 'icon',
        icon: 'visibility',
        onclick: `openViewDetailsModal(${booking.id})`,
        title: 'Xem chi tiết dịch vụ đang sửa',
      }));
    }
  } else if (booking.trang_thai === 'cho_thanh_toan' || booking.trang_thai === 'cho_hoan_thanh') {
    actions.push(renderBoardButton({
      variant: 'main-warm',
      icon: 'payments',
      label: isCashPaymentBooking(booking) ? 'Xác nhận đã thu' : 'Theo dõi TT',
      onclick: isCashPaymentBooking(booking) ? `confirmCashPayment(${booking.id})` : `openViewDetailsModal(${booking.id})`,
    }));
    actions.push(renderBoardButton({
      variant: 'secondary',
      icon: 'receipt_long',
      label: 'Chi tiết',
      onclick: `openViewDetailsModal(${booking.id})`,
    }));
  } else {
    actions.push(renderBoardButton({
      variant: 'secondary',
      icon: 'visibility',
      label: 'Chi tiết',
      onclick: `openViewDetailsModal(${booking.id})`,
    }));
  }

  const utilityLimit = pricingReady ? 2 : 2;

  if (canOpenRouteGuide(booking) && utilityActions.length < utilityLimit) {
    utilityActions.push(renderBoardButton({
      variant: 'icon',
      icon: 'near_me',
      onclick: `openRouteGuide(${booking.id})`,
      title: 'Mở chỉ đường',
    }));
  }

  if (getPhoneNumber(booking) && utilityActions.length < utilityLimit) {
    utilityActions.push(renderBoardButton({
      variant: 'icon',
      icon: 'call',
      href: getPhoneHref(booking),
      title: `Gọi ${getCustomerName(booking)}`,
    }));
  }

  return actions.concat(utilityActions).join('');
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
  const title = getBookingServiceNames(booking);
  const serviceBadge = getServiceBadge(booking);
  const customerName = getCustomerName(booking);
  const customerPhone = getPhoneNumber(booking) || 'Chưa có số liên hệ';
  const noteMarkup = renderBoardNote(booking);
  const paymentMarkup = renderBoardPaymentPanel(booking);
  const scheduleDateText = getBookingCardDateLabel(booking);
  const scheduleTimeText = getBookingPrimaryTimeLabel(booking);
  const location = getAddress(booking);
  const locationLabel = getLocationLabel(booking);
  const statusLabel = getStatusLabel(booking);
  const locationIcon = booking?.loai_dat_lich === 'at_home' ? 'home_repair_service' : 'storefront';

  return `
    <article class="dispatch-board-card dispatch-board-card--${tone}">
      <span class="dispatch-board-card__status">${escapeHtml(statusLabel)}</span>
      <div class="dispatch-board-card__content">
        <div class="dispatch-board-card__header">
          <div class="dispatch-board-card__lead">
            <div class="dispatch-board-card__icon">
              <span class="material-symbols-outlined">${escapeHtml(getServiceIconName(booking))}</span>
            </div>
            <div class="dispatch-board-card__summary">
              <span class="dispatch-board-card__eyebrow">${escapeHtml(serviceBadge)}</span>
              <h3 class="dispatch-board-card__title">${escapeHtml(title)}</h3>
              <div class="dispatch-board-card__support">
                <span class="dispatch-board-card__support-item">
                  <span class="material-symbols-outlined">person</span>
                  <span>${escapeHtml(customerName)}</span>
                </span>
                <span class="dispatch-board-card__support-item">
                  <span class="material-symbols-outlined">call</span>
                  <span>${escapeHtml(customerPhone)}</span>
                </span>
              </div>
            </div>
          </div>

          <div class="dispatch-board-card__schedule">
            <span class="dispatch-board-card__time">${escapeHtml(scheduleTimeText)}</span>
            <span class="dispatch-board-card__date">${escapeHtml(scheduleDateText)}</span>
          </div>
        </div>

        <div class="dispatch-board-card__body">
          <div class="dispatch-board-card__info-grid">
            <div class="dispatch-board-card__info dispatch-board-card__info--full">
              <span class="dispatch-board-card__info-icon">
                <span class="material-symbols-outlined">location_on</span>
              </span>
              <span class="dispatch-board-card__info-copy">
                <span class="dispatch-board-card__info-label">Địa điểm</span>
                <span class="dispatch-board-card__info-value">${escapeHtml(location)}</span>
              </span>
            </div>

            <div class="dispatch-board-card__info">
              <span class="dispatch-board-card__info-icon">
                <span class="material-symbols-outlined">${escapeHtml(locationIcon)}</span>
              </span>
              <span class="dispatch-board-card__info-copy">
                <span class="dispatch-board-card__info-label">Hình thức</span>
                <span class="dispatch-board-card__info-value">${escapeHtml(locationLabel)}</span>
              </span>
            </div>

            <div class="dispatch-board-card__info">
              <span class="dispatch-board-card__info-icon">
                <span class="material-symbols-outlined">event_note</span>
              </span>
              <span class="dispatch-board-card__info-copy">
                <span class="dispatch-board-card__info-label">Lịch hẹn</span>
                <span class="dispatch-board-card__info-value">${escapeHtml(booking?.khung_gio_hen || 'Chưa chọn giờ')}</span>
              </span>
            </div>
          </div>
          ${paymentMarkup}
          ${noteMarkup}
        </div>

        <div class="dispatch-board-card__footer">
          ${renderActionButtons(booking)}
        </div>
      </div>
    </article>
  `;
};

function renderPagination(totalItems) {
  if (!bookingPagination) {
    return;
  }

  const totalPages = Math.max(1, Math.ceil(totalItems / JOBS_PER_PAGE));
  window.currentPage = Math.min(Math.max(1, window.currentPage), totalPages);

  if (bookingPaginationWrap) {
    bookingPaginationWrap.hidden = totalPages <= 1;
  }

  if (totalPages <= 1) {
    bookingPagination.innerHTML = '';
    return;
  }

  const items = buildPaginationModel(totalPages, window.currentPage);
  const prevDisabled = window.currentPage <= 1;
  const nextDisabled = window.currentPage >= totalPages;

  bookingPagination.innerHTML = `
    <button type="button" class="dispatch-pagination__btn${prevDisabled ? ' is-disabled' : ''}" data-page-action="prev" aria-label="Trang trước">
      <span class="material-symbols-outlined">chevron_left</span>
    </button>
    ${items.map((item) => item === 'ellipsis'
      ? '<span class="dispatch-pagination__ellipsis">...</span>'
      : `<button type="button" class="dispatch-pagination__page${item === window.currentPage ? ' is-active' : ''}" data-page-number="${item}">${item}</button>`).join('')}
    <button type="button" class="dispatch-pagination__btn${nextDisabled ? ' is-disabled' : ''}" data-page-action="next" aria-label="Trang sau">
      <span class="material-symbols-outlined">chevron_right</span>
    </button>
  `;
}

function renderRoutePreview(bookings) {
  if (!routePreviewSection || !routePreviewTitle || !routePreviewMeta || !routePreviewAction) {
    return;
  }

  const previewBooking = bookings.find((booking) => canOpenRouteGuide(booking))
    || window.allBookings.find((booking) => canOpenRouteGuide(booking))
    || bookings.find((booking) => booking?.loai_dat_lich === 'at_home')
    || window.allBookings.find((booking) => booking?.loai_dat_lich === 'at_home')
    || bookings[0]
    || null;

  if (!previewBooking) {
    routePreviewSection.hidden = true;
    routePreviewAction.removeAttribute('data-booking-id');
    return;
  }

  const serviceTitle = getBookingServiceNames(previewBooking);
  const location = getAddress(previewBooking);
  const locationLabel = getFirstAddressSegment(location);
  const estimatedMinutes = estimateDriveMinutes(previewBooking);
  const distanceKm = getNumeric(previewBooking?.khoang_cach);
  const metaParts = [];

  if (distanceKm > 0) {
    metaParts.push(`${distanceKm.toFixed(1)} km`);
  }

  if (estimatedMinutes) {
    metaParts.push(`${estimatedMinutes} phút lái xe`);
  }

  if (!metaParts.length) {
    metaParts.push(getScheduleLabel(previewBooking));
  }

  routePreviewTitle.textContent = serviceTitle;
  if (routePreviewLocation) {
    routePreviewLocation.textContent = locationLabel;
  }
  if (routePreviewBadge) {
    routePreviewBadge.textContent = getLocationLabel(previewBooking);
  }
  routePreviewMeta.textContent = metaParts.join(' • ');
  routePreviewAction.dataset.bookingId = String(previewBooking.id);
  routePreviewAction.innerHTML = canOpenRouteGuide(previewBooking)
    ? `
        <span class="material-symbols-outlined">map</span>
        Xem đường đi
      `
    : `
        <span class="material-symbols-outlined">visibility</span>
        Xem chi tiết
      `;
  routePreviewSection.hidden = false;
}

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
  const list = getScopedBookings(status, window.currentScope);
  const totalItems = list.length;

  renderPagination(totalItems);
  renderRoutePreview(list);
  updateBoardSurface(status, totalItems);

  if (!totalItems) {
    clearRepairTimers();
    renderEmptyState(window.currentScope);
    return;
  }

  const startIndex = (window.currentPage - 1) * JOBS_PER_PAGE;
  const visibleList = list.slice(startIndex, startIndex + JOBS_PER_PAGE);

  bookingsContainer.innerHTML = visibleList.map((booking) => renderCard(booking)).join('');
  refreshRepairTimers(visibleList);
}

function updateSummary() {
  const summaryBookings = window.allBookings.filter((booking) => isWorkerOwnedBooking(booking));
  const todayBookings = summaryBookings.filter((booking) => isTodayBooking(booking));
  const inProgress = summaryBookings.filter((booking) => booking.trang_thai === 'dang_lam');
  const paymentPending = summaryBookings.filter((booking) => ['cho_hoan_thanh', 'cho_thanh_toan'].includes(booking.trang_thai));
  const projectedIncome = summaryBookings
    .filter((booking) => booking.trang_thai !== 'da_huy')
    .reduce((total, booking) => total + getBookingTotal(booking), 0);

  const summaryTodayCount = document.getElementById('summaryTodayCount');
  const summaryInProgressCount = document.getElementById('summaryInProgressCount');
  const summaryPendingPaymentCount = document.getElementById('summaryPendingPaymentCount');
  const summaryIncomeValue = document.getElementById('summaryIncomeValue');
  const summaryLastUpdated = document.getElementById('summaryLastUpdated');

  if (!summaryTodayCount || !summaryInProgressCount || !summaryPendingPaymentCount || !summaryIncomeValue || !summaryLastUpdated) {
    return;
  }

  summaryTodayCount.textContent = formatCount(todayBookings.length);
  summaryInProgressCount.textContent = formatCount(inProgress.length);
  summaryPendingPaymentCount.textContent = formatCount(paymentPending.length);
  summaryIncomeValue.textContent = formatMoney(projectedIncome);
  summaryLastUpdated.textContent = `Cập nhật lần cuối: ${new Date().toLocaleTimeString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
  })}`;
}

function updateCounters() {
  ['pending', 'upcoming', 'inprogress', 'payment', 'done', 'cancelled'].forEach((status) => {
    const counter = document.getElementById(`cnt-${status}`);
    if (counter) {
      counter.textContent = getFilterCount(status);
    }
  });
}

function hydrateWorkerSummary() {
  const name = user?.name || 'Thợ kỹ thuật';
  const role = user?.role === 'admin' ? 'Quản trị viên kỹ thuật' : 'Thợ kỹ thuật';
  const initial = name.trim().charAt(0).toUpperCase() || 'T';

  const scheduleWorkerName = document.getElementById('scheduleWorkerName');
  const scheduleWorkerRole = document.getElementById('scheduleWorkerRole');
  const scheduleWorkerInitial = document.getElementById('scheduleWorkerInitial');

  if (!scheduleWorkerName || !scheduleWorkerRole || !scheduleWorkerInitial) {
    return;
  }

  scheduleWorkerName.textContent = name;
  scheduleWorkerRole.textContent = role;
  scheduleWorkerInitial.textContent = initial;
}

function hydrateTopbarIdentity() {
  setAvatarContent(topAvatar, user?.avatar, user?.name || 'Thợ kỹ thuật');
}

function getTopbarNotificationVisual(type = 'booking_status_updated') {
  switch (type) {
  case 'new_booking':
    return { icon: 'work', tone: 'is-warning' };
  case 'booking_claimed':
    return { icon: 'assignment_turned_in', tone: 'is-success' };
  case 'booking_in_progress':
    return { icon: 'construction', tone: '' };
  case 'booking_waiting_completion':
  case 'booking_payment_requested':
    return { icon: 'payments', tone: 'is-warning' };
  case 'booking_completed':
    return { icon: 'task_alt', tone: 'is-success' };
  case 'booking_cancelled':
    return { icon: 'cancel', tone: 'is-danger' };
  default:
    return { icon: 'notifications', tone: '' };
  }
}

function formatTopbarNotificationTime(value) {
  if (!value) {
    return '';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '';
  }

  return date.toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function resolveTopbarNotificationDestination(notification) {
  const data = notification?.data || {};
  const bookingId = getNumeric(data.booking_id);
  const rawLink = typeof data.link === 'string' ? data.link.trim() : '';

  if (rawLink) {
    if (user?.role === 'worker') {
      if (bookingId > 0 && rawLink.startsWith('/customer/')) {
        return buildWorkerBookingsHref({ bookingId });
      }

      if (rawLink === '/worker/jobs') {
        return buildWorkerBookingsHref({ status: 'pending', bookingId: 0 });
      }
    }

    return rawLink;
  }

  if (bookingId > 0) {
    return user?.role === 'admin' ? '/admin/bookings' : buildWorkerBookingsHref({ bookingId });
  }

  return user?.role === 'admin' ? '/admin/bookings' : '/worker/my-bookings';
}

function renderTopbarNotificationList(notifications = []) {
  if (!topNotificationList) {
    return;
  }

  if (!notifications.length) {
    topNotificationList.innerHTML = `
      <div class="dispatch-board-topbar__notification-empty">
        <span class="material-symbols-outlined">notifications_off</span>
        <p>Chưa có thông báo nào.</p>
      </div>
    `;
    return;
  }

  topNotificationList.innerHTML = notifications.map((notification) => {
    const data = notification?.data || {};
    const visual = getTopbarNotificationVisual(data.type || 'booking_status_updated');
    const chips = [
      data.booking_code || (data.booking_id ? `#${data.booking_id}` : ''),
      data.status_label || '',
      data.service_name || data.dich_vu_name || '',
      formatTopbarNotificationTime(notification?.created_at),
    ].filter(Boolean).slice(0, 3);

    return `
      <a
        href="${escapeHtml(resolveTopbarNotificationDestination(notification))}"
        class="dispatch-board-topbar__notification-item${notification?.read_at ? '' : ' is-unread'}"
        data-notification-id="${escapeHtml(notification?.id || '')}">
        <div class="dispatch-board-topbar__notification-row">
          <span class="dispatch-board-topbar__notification-icon ${visual.tone}">
            <span class="material-symbols-outlined">${escapeHtml(visual.icon)}</span>
          </span>
          <div class="dispatch-board-topbar__notification-copy">
            <strong>
              ${notification?.read_at ? '' : '<span class="dispatch-board-topbar__notification-unread-dot"></span>'}
              ${escapeHtml(data.title || 'Thông báo mới')}
            </strong>
            <p>${escapeHtml(data.message || 'Hệ thống vừa cập nhật tiến độ đơn sửa chữa của bạn.')}</p>
            <div class="dispatch-board-topbar__notification-meta">
              ${chips.map((chip) => `<span class="dispatch-board-topbar__notification-chip">${escapeHtml(chip)}</span>`).join('')}
            </div>
          </div>
        </div>
      </a>
    `;
  }).join('');
}

function setTopbarNotificationMenuState(isOpen) {
  if (!topNotificationButton || !topNotificationMenu) {
    return;
  }

  topNotificationButton.classList.toggle('is-active', isOpen);
  topNotificationButton.setAttribute('aria-expanded', String(isOpen));
  topNotificationMenu.classList.toggle('is-open', isOpen);
}

async function fetchTopbarNotifications({ showErrorToast = false } = {}) {
  if (!topNotificationBadge || !topNotificationList) {
    return;
  }

  try {
    const response = await callApi('/notifications/unread');
    if (!response.ok || !response.data) {
      throw new Error(response.data?.message || 'Không thể tải thông báo.');
    }

    const unreadCount = Number(response.data.unread_count || 0);
    const notifications = Array.isArray(response.data.notifications) ? response.data.notifications : [];

    renderTopbarNotificationList(notifications);

    if (unreadCount > 0) {
      topNotificationBadge.classList.remove('is-hidden');
      topNotificationBadge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
    } else {
      topNotificationBadge.classList.add('is-hidden');
      topNotificationBadge.textContent = '0';
    }
  } catch (error) {
    console.error('Topbar notifications failed', error);
    if (showErrorToast) {
      showToast(error.message || 'Không thể tải thông báo.', 'error');
    }
  }
}

function initTopbarNotificationCenter() {
  if (!topNotificationButton || !topNotificationMenu || !topNotificationList || !topNotificationBadge) {
    return;
  }

  topNotificationButton.addEventListener('click', (event) => {
    event.stopPropagation();
    const willOpen = !topNotificationMenu.classList.contains('is-open');
    setTopbarNotificationMenuState(willOpen);

    if (willOpen) {
      fetchTopbarNotifications();
    }
  });

  topNotificationMarkAll?.addEventListener('click', async (event) => {
    event.stopPropagation();

    try {
      const response = await callApi('/notifications/read-all', 'POST');
      if (!response.ok) {
        throw new Error(response.data?.message || 'Không thể đánh dấu đã đọc.');
      }

      await fetchTopbarNotifications();
    } catch (error) {
      showToast(error.message || 'Không thể đánh dấu thông báo đã đọc.', 'error');
    }
  });

  topNotificationList.addEventListener('click', async (event) => {
    const target = event.target instanceof Element ? event.target.closest('[data-notification-id]') : null;
    if (!target) {
      return;
    }

    event.preventDefault();
    const notificationId = target.getAttribute('data-notification-id');
    const destination = target.getAttribute('href') || '/worker/my-bookings';

    try {
      if (notificationId) {
        await callApi(`/notifications/${notificationId}/read`, 'POST');
      }
    } catch (error) {
      console.error('Mark notification as read failed', error);
    }

    setTopbarNotificationMenuState(false);
    window.location.href = destination;
  });

  document.addEventListener('click', (event) => {
    if (!topNotificationMenu.contains(event.target) && !topNotificationButton.contains(event.target)) {
      setTopbarNotificationMenuState(false);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      setTopbarNotificationMenuState(false);
    }
  });

  fetchTopbarNotifications();

  if (topNotificationPollId) {
    window.clearInterval(topNotificationPollId);
  }

  topNotificationPollId = window.setInterval(() => {
    fetchTopbarNotifications();
  }, 10000);
}

function syncBoardStatusTabs() {
  boardStatusTabs.forEach((tab) => {
    tab.classList.toggle('is-active', tab.dataset.boardStatus === window.currentStatus);
  });
}

function syncBookingScopeButtons() {
  bookingScopeButtons.forEach((button) => {
    button.classList.toggle('is-active', button.dataset.bookingScope === window.currentScope);
  });
}

function focusBookingFromQuery() {
  const bookingId = Number(window.pendingBookingIdToOpen || 0);
  if (!Number.isFinite(bookingId) || bookingId <= 0) {
    return;
  }

  const booking = window.allBookings.find((item) => item.id === bookingId);
  window.pendingBookingIdToOpen = 0;

  if (!booking) {
    syncWorkerBookingsUrl({ bookingId: 0 });
    return;
  }

  window.currentStatus = statusToneMap[booking.trang_thai] || window.currentStatus;
  window.currentScope = 'all';
  window.currentPage = 1;
  syncBoardStatusTabs();
  syncBookingScopeButtons();
  renderBookings(window.currentStatus);

  window.requestAnimationFrame(() => {
    window.openViewDetailsModal(booking.id, { syncUrl: false });
  });
}

boardStatusTabs.forEach((tab) => {
  tab.addEventListener('click', () => {
    const status = tab.dataset.boardStatus || 'all';
    if (status === window.currentStatus) {
      return;
    }

    window.currentStatus = status;
    window.currentPage = 1;
    syncBoardStatusTabs();
    syncWorkerBookingsUrl({ bookingId: 0 });
    renderBookings(status);
  });
});

bookingScopeButtons.forEach((button) => {
  button.addEventListener('click', () => {
    const scope = button.dataset.bookingScope || 'all';
    if (scope === window.currentScope) {
      return;
    }

    window.currentScope = scope;
    window.currentPage = 1;
    syncBookingScopeButtons();
    syncWorkerBookingsUrl({ bookingId: 0 });
    renderBookings(window.currentStatus);
  });
});

bookingPagination?.addEventListener('click', (event) => {
  const target = event.target instanceof Element ? event.target.closest('[data-page-number], [data-page-action]') : null;
  if (!target) {
    return;
  }

  if (target.hasAttribute('data-page-number')) {
    const nextPage = getNumeric(target.getAttribute('data-page-number'));
    if (nextPage > 0) {
      window.currentPage = nextPage;
      renderBookings(window.currentStatus);
    }
    return;
  }

  const action = target.getAttribute('data-page-action');
  if (action === 'prev' && window.currentPage > 1) {
    window.currentPage -= 1;
    renderBookings(window.currentStatus);
  }
  if (action === 'next' && window.currentPage < getTotalPages(window.currentStatus, window.currentScope)) {
    window.currentPage += 1;
    renderBookings(window.currentStatus);
  }
});

routePreviewAction?.addEventListener('click', () => {
  const bookingId = getNumeric(routePreviewAction.dataset.bookingId);
  if (!bookingId) {
    return;
  }

  const booking = window.allBookings.find((item) => item.id === bookingId);
  if (!booking) {
    return;
  }

  if (canOpenRouteGuide(booking)) {
    window.openRouteGuide(bookingId);
    return;
  }

  window.openViewDetailsModal(bookingId);
});

window.switchTab = function(el, status) {
  window.currentStatus = status;
  window.currentPage = 1;
  syncBoardStatusTabs();
  syncWorkerBookingsUrl({ bookingId: 0 });
  renderBookings(status);
};

window.loadMyBookings = async function(status = window.currentStatus) {
  if (!window.allBookings.length) {
    renderLoadingState();
  }

  try {
    const [assignedResponse, availableResponse] = await Promise.all([
      callApi('/don-dat-lich', 'GET'),
      callApi('/don-dat-lich/available', 'GET'),
    ]);

    if (!assignedResponse.ok) {
      showToast(assignedResponse.data?.message || 'Không thể tải lịch làm việc.', 'error');
      renderEmptyState(status);
      return;
    }

    if (!availableResponse.ok) {
      showToast(availableResponse.data?.message || 'Không thể tải danh sách nhận việc.', 'error');
      renderEmptyState(status);
      return;
    }

    window.assignedBookings = getApiCollection(assignedResponse.data);
    window.availableBookings = getApiCollection(availableResponse.data);
    rebuildWorkerBookings();
    updateCounters();
    updateSummary();
    renderBookings(status);
    focusBookingFromQuery();
  } catch (error) {
    console.error(error);
    showToast('Lỗi kết nối khi tải lịch làm việc.', 'error');
    renderEmptyState(status);
  }
};

window.claimJob = async function(id) {
  try {
    const response = await callApi(`/don-dat-lich/${id}/claim`, 'POST');

    if (!response.ok) {
      showToast(response.data?.message || 'Không thể nhận đơn này.', 'error');
      return;
    }

    showToast(response.data?.message || 'Đã nhận đơn thành công.');
    window.currentStatus = 'upcoming';
    window.currentPage = 1;
    syncBoardStatusTabs();
    syncWorkerBookingsUrl({ bookingId: id });
    await loadMyBookings('upcoming');
  } catch (error) {
    console.error(error);
    showToast('Lỗi kết nối khi nhận đơn.', 'error');
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

window.openCostModal = function(id) {
  const booking = window.allBookings.find((item) => item.id === id);

  if (!booking) {
    showToast('Không tìm thấy đơn để cập nhật giá.', 'error');
    return;
  }
  hydrateCostModal(booking);
  costModalInstance?.show();
};

function syncCostWizardUi() {
  const totalSteps = Object.keys(pricingWizardSteps).length;
  const currentStepConfig = pricingWizardSteps[currentCostStep] || pricingWizardSteps[1];

  if (costWizardKicker) {
    costWizardKicker.textContent = currentStepConfig.kicker;
  }

  if (costWizardTitle) {
    costWizardTitle.textContent = currentStepConfig.title;
  }

  if (costWizardCopy) {
    costWizardCopy.textContent = currentStepConfig.copy;
  }

  if (costWizardStepBadge) {
    costWizardStepBadge.textContent = `${currentCostStep} / ${totalSteps}`;
  }

  if (costWizardProgressFill) {
    costWizardProgressFill.style.width = `${(currentCostStep / totalSteps) * 100}%`;
  }

  costStepPanels.forEach((panel) => {
    const step = Number(panel.dataset.costStepPanel || 1);
    const isActive = step === currentCostStep;
    panel.hidden = !isActive;
    panel.classList.toggle('is-active', isActive);
  });

  costStepTriggers.forEach((trigger) => {
    const step = Number(trigger.dataset.costStepTrigger || 1);
    const isActive = step === currentCostStep;
    const isComplete = step < currentCostStep;

    trigger.classList.toggle('is-active', isActive);
    trigger.classList.toggle('is-complete', isComplete);
    trigger.setAttribute('aria-current', isActive ? 'step' : 'false');
  });

  btnCostWizardPrev?.classList.toggle('d-none', currentCostStep === 1);
  btnCostWizardNext?.classList.toggle('d-none', currentCostStep >= totalSteps);
  document.getElementById('btnSubmitCostUpdate')?.classList.toggle('d-none', currentCostStep !== totalSteps);
}

function focusCostWizardStep() {
  if (currentCostStep === 1) {
    laborSymptomTrigger?.focus();
    return;
  }

  partCatalogSearch?.focus();
}

function validateCostWizardStep(step) {
  if (step !== 1) {
    return true;
  }

  const laborState = collectCostItems(laborItemsContainer, 'labor');

  if (!laborState.items.length) {
    showToast('Vui lòng chọn ít nhất 1 hướng xử lý để thêm tiền công trước khi tiếp tục.', 'error');
    laborSymptomTrigger?.focus();
    return false;
  }

  if (laborState.hasIncomplete) {
    showToast('Danh mục tiền công đang có dòng chưa hợp lệ, vui lòng kiểm tra lại.', 'error');
    laborSymptomTrigger?.focus();
    return false;
  }

  return true;
}

function setCostWizardStep(step, { validateForward = false, focus = true } = {}) {
  const totalSteps = Object.keys(pricingWizardSteps).length;
  const nextStep = Math.min(totalSteps, Math.max(1, Number(step) || 1));

  if (nextStep > currentCostStep && validateForward) {
    for (let wizardStep = currentCostStep; wizardStep < nextStep; wizardStep += 1) {
      if (!validateCostWizardStep(wizardStep)) {
        return false;
      }
    }
  }

  currentCostStep = nextStep;
  syncCostWizardUi();

  if (focus) {
    window.requestAnimationFrame(focusCostWizardStep);
  }

  return true;
}

function hydrateCostModal(booking) {
  currentCostBooking = booking;
  currentCostStep = 1;
  costBookingId.value = booking.id;

  if (inputGhiChuLinhKien) {
    inputGhiChuLinhKien.value = booking.ghi_chu_linh_kien || '';
  }

  if (partCatalogSearch) {
    partCatalogSearch.value = '';
  }

  costBookingReference.textContent = `Đơn #${String(booking.id).padStart(4, '0')}`;
  costCustomerName.textContent = getCustomerName(booking);
  costServiceName.textContent = getBookingServiceNames(booking);
  costServiceModeBadge.textContent = booking.loai_dat_lich === 'at_home' ? 'Sửa tại nhà' : 'Sửa tại cửa hàng';
  costTruckBadge.textContent = booking.thue_xe_cho ? 'Có thuê xe chở' : 'Không thuê xe chở';
  costDistanceBadge.textContent = booking.loai_dat_lich === 'at_home'
    ? `${getNumeric(booking.khoang_cach).toFixed(1)} km phục vụ`
    : 'Không phát sinh phí đi lại';
  displayPhiDiLai.textContent = formatMoney(getNumeric(booking.phi_di_lai));
  costDistanceHint.textContent = booking.loai_dat_lich === 'at_home'
    ? `Hệ thống đã chốt phí đi lại theo quãng đường ${getNumeric(booking.khoang_cach).toFixed(1)} km.`
    : 'Khách tự mang thiết bị đến cửa hàng nên không phát sinh khoảng cách phục vụ.';

  populateCostItemRows(laborItemsContainer, 'labor', getBookingLaborItems(booking));
  populateCostItemRows(partItemsContainer, 'part', getBookingPartItems(booking));

  if (booking.thue_xe_cho) {
    truckFeeContainer.style.display = '';
    truckSummaryRow.style.display = '';
    inputTienThueXe.value = getNumeric(booking.tien_thue_xe);
  } else {
    truckFeeContainer.style.display = 'none';
    truckSummaryRow.style.display = 'none';
    inputTienThueXe.value = 0;
  }

  partCatalogState.selectedIds = new Set();
  partCatalogState.activeSuggestionIndex = -1;
  partCatalogState.fallbackItems = [];
  if (partCatalogResults) {
    partCatalogResults.innerHTML = '';
  }
  hidePartCatalogSuggestions();
  updateSelectedPartsButtonState();
  updateCostEstimate();
  syncCostWizardUi();
  void loadLaborCatalogForBooking(booking);
  void loadPartCatalogForBooking(booking);
}

costStepTriggers.forEach((trigger) => {
  trigger.addEventListener('click', () => {
    const step = Number(trigger.dataset.costStepTrigger || 1);
    setCostWizardStep(step, { validateForward: step > currentCostStep });
  });
});

btnCostWizardPrev?.addEventListener('click', () => {
  setCostWizardStep(currentCostStep - 1);
});

btnCostWizardNext?.addEventListener('click', () => {
  setCostWizardStep(currentCostStep + 1, { validateForward: true });
});

costModalEl?.addEventListener('shown.bs.modal', () => {
  focusCostWizardStep();
});

costModalEl?.addEventListener('hidden.bs.modal', () => {
  currentCostBooking = null;
  currentCostStep = 1;
  costBookingId.value = '';
  laborCatalogState.items = [];
  laborCatalogState.selectedSymptomId = null;
  laborCatalogState.selectedCauseId = null;
  laborCatalogState.selectedResolutionId = null;
  laborSearchablePickerState.symptom.keyword = '';
  laborSearchablePickerState.cause.keyword = '';
  if (inputGhiChuLinhKien) {
    inputGhiChuLinhKien.value = '';
  }
  if (partCatalogSearch) {
    partCatalogSearch.value = '';
  }
  inputTienThueXe.value = 0;
  truckFeeContainer.style.display = 'none';
  truckSummaryRow.style.display = 'none';
  partCatalogState.selectedIds = new Set();
  partCatalogState.activeSuggestionIndex = -1;
  partCatalogState.fallbackItems = [];
  hidePartCatalogSuggestions();
  if (partCatalogResults) {
    partCatalogResults.innerHTML = '';
  }
  updateSelectedPartsButtonState();
  closeAllLaborSearchablePickers();
  updateLaborCatalogPicker();
  updateCostEstimate();
  syncCostWizardUi();
});

Object.entries(laborSearchablePickers).forEach(([type, picker]) => {
  picker.triggerEl?.addEventListener('click', () => {
    toggleLaborSearchablePicker(type);
  });

  picker.searchEl?.addEventListener('input', () => {
    const state = getLaborSearchablePickerState(type);
    if (!state) {
      return;
    }

    state.keyword = picker.searchEl?.value || '';
    renderLaborSearchablePickerOptions(type);
  });

  picker.searchEl?.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      event.preventDefault();
      closeLaborSearchablePicker(type);
      picker.triggerEl?.focus();
      return;
    }

    if (event.key === 'Enter') {
      const firstOption = picker.optionsEl?.querySelector('.dispatch-search-picker__option');
      if (!firstOption) {
        return;
      }

      event.preventDefault();
      applyLaborSearchablePickerSelection(type, firstOption.getAttribute('data-picker-value') || '');
    }
  });

  picker.optionsEl?.addEventListener('click', (event) => {
    const targetElement = event.target instanceof Element
      ? event.target
      : event.target?.parentElement || null;
    const option = targetElement?.closest('.dispatch-search-picker__option');
    if (!option) {
      return;
    }

    applyLaborSearchablePickerSelection(type, option.getAttribute('data-picker-value') || '');
  });
});

document.addEventListener('click', (event) => {
  const target = event.target;

  if (!(target instanceof Element)) {
    return;
  }

  const clickedInsidePicker = Object.values(laborSearchablePickers)
    .some((picker) => picker.rootEl?.contains(target));

  if (!clickedInsidePicker) {
    closeAllLaborSearchablePickers();
  }
});

laborSymptomSelect?.addEventListener('change', () => {
  laborCatalogState.selectedSymptomId = laborSymptomSelect.value || null;
  laborCatalogState.selectedCauseId = null;
  laborCatalogState.selectedResolutionId = null;
  updateLaborCatalogPicker();
});

laborCauseSelect?.addEventListener('change', () => {
  laborCatalogState.selectedCauseId = laborCauseSelect.value || null;
  laborCatalogState.selectedResolutionId = null;
  updateLaborCatalogPicker();
});

laborResolutionSelect?.addEventListener('change', () => {
  laborCatalogState.selectedResolutionId = laborResolutionSelect.value || null;
  updateLaborCatalogPicker();
});

addLaborItemButton?.addEventListener('click', () => {
  addSelectedLaborCatalogItem();
});

addPartItemButton?.addEventListener('click', () => {
  appendCostItemRow(partItemsContainer, 'part');
  updateCostEstimate();
});

partCatalogSearch?.addEventListener('input', async () => {
  partCatalogState.activeSuggestionIndex = -1;
  await refreshPartCatalogSearch();
});
partCatalogSearch?.addEventListener('focus', async () => {
  if (String(partCatalogSearch.value || '').trim()) {
    await refreshPartCatalogSearch();
  }
});
partCatalogSearch?.addEventListener('blur', () => {
  window.setTimeout(() => {
    if (document.activeElement !== partCatalogSearch) {
      hidePartCatalogSuggestions();
    }
  }, 120);
});
partCatalogSearch?.addEventListener('keydown', (event) => {
  const visibleItems = getSuggestionPartCatalogItems().slice(0, 6);

  if (event.key === 'Escape') {
    hidePartCatalogSuggestions();
    return;
  }

  if (event.key === 'Enter') {
    event.preventDefault();

    if (!visibleItems.length) {
      hidePartCatalogSuggestions();
      return;
    }

    const fallbackIndex = partCatalogState.activeSuggestionIndex >= 0 ? partCatalogState.activeSuggestionIndex : 0;
    const selectedItem = visibleItems[fallbackIndex];
    if (selectedItem) {
      selectPartCatalogSuggestion(getNumeric(selectedItem.id));
    }
    return;
  }

  if (!visibleItems.length) {
    return;
  }

  if (event.key === 'ArrowDown') {
    event.preventDefault();
    partCatalogState.activeSuggestionIndex = (partCatalogState.activeSuggestionIndex + 1 + visibleItems.length) % visibleItems.length;
    renderPartCatalogSuggestions(getSuggestionPartCatalogItems());
  }

  if (event.key === 'ArrowUp') {
    event.preventDefault();
    partCatalogState.activeSuggestionIndex = partCatalogState.activeSuggestionIndex <= 0
      ? visibleItems.length - 1
      : partCatalogState.activeSuggestionIndex - 1;
    renderPartCatalogSuggestions(getSuggestionPartCatalogItems());
  }
});
partCatalogSuggestions?.addEventListener('mousedown', (event) => {
  if (event.target.closest('.js-part-catalog-suggestion')) {
    event.preventDefault();
  }
});
partCatalogSuggestions?.addEventListener('click', (event) => {
  const suggestion = event.target.closest('.js-part-catalog-suggestion');
  if (!suggestion || suggestion.hasAttribute('disabled')) {
    return;
  }

  selectPartCatalogSuggestion(getNumeric(suggestion.dataset.partId));
});
partCatalogResults?.addEventListener('change', (event) => {
  const input = event.target.closest('.js-part-catalog-check');
  if (!input) {
    return;
  }

  const partId = getNumeric(input.value);
  setPartCatalogSelectionState(partId, input.checked);

  updateSelectedPartsButtonState();
  renderPartCatalogSuggestions(getSuggestionPartCatalogItems());
  input.closest('.dispatch-part-option')?.classList.toggle('is-selected', input.checked);
});
addSelectedPartsButton?.addEventListener('click', addSelectedCatalogPartsToDraft);

[laborItemsContainer, partItemsContainer].forEach((container) => {
  container?.addEventListener('input', updateCostEstimate);
  container?.addEventListener('change', (event) => {
    const quantityInput = event.target.closest('.js-line-quantity');
    if (!quantityInput) {
      return;
    }

    quantityInput.value = String(Math.max(1, Math.trunc(getNumeric(quantityInput.value || 1))));
    updateCostEstimate();
  });
  container?.addEventListener('click', (event) => {
    const quantityStepButton = event.target.closest('.js-quantity-step');
    if (quantityStepButton) {
      const lineItem = quantityStepButton.closest('.dispatch-line-item');
      const quantityInput = lineItem?.querySelector('.js-line-quantity');
      if (quantityInput) {
        const step = Math.trunc(getNumeric(quantityStepButton.dataset.step || 0));
        const nextValue = Math.max(1, Math.trunc(getNumeric(quantityInput.value || 1)) + step);
        quantityInput.value = String(nextValue);
        updateCostEstimate();
      }
      return;
    }

    const removeButton = event.target.closest('.dispatch-line-item__remove');
    if (!removeButton) {
      return;
    }

    const type = removeButton.closest('.dispatch-line-item')?.dataset.lineType === 'part' ? 'part' : 'labor';
    removeButton.closest('.dispatch-line-item')?.remove();
    ensureMinimumCostRows(container, type);
    updateCostEstimate();
    if (type === 'labor') {
      updateLaborCatalogPicker();
    }
  });
});

inputTienThueXe?.addEventListener('input', updateCostEstimate);

if (costForm) {
  costForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (currentCostStep < Object.keys(pricingWizardSteps).length) {
      setCostWizardStep(currentCostStep + 1, { validateForward: true });
      return;
    }

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
      ghi_chu_linh_kien: inputGhiChuLinhKien?.value || '',
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

window.openViewDetailsModal = function(id, { syncUrl = true } = {}) {
  const booking = window.allBookings.find((item) => item.id === id);

  if (!booking) {
    showToast('Không tìm thấy chi tiết đơn.', 'error');
    return;
  }

  window.activeBookingId = booking.id;
  if (syncUrl) {
    syncWorkerBookingsUrl({ bookingId: booking.id });
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

function getSelectedCompletePaymentMethod() {
  return completePaymentMethodInputs.find((input) => input.checked)?.value === 'transfer' ? 'transfer' : 'cod';
}

function syncCompletePaymentMethodUi(paymentMethod = getSelectedCompletePaymentMethod()) {
  const isTransfer = paymentMethod === 'transfer';

  completePaymentOptions.forEach((option) => {
    const input = option.querySelector('input[name="phuong_thuc_thanh_toan"]');
    option.classList.toggle('is-active', input?.checked === true);
  });

  if (completePaymentMethodTitle) {
    completePaymentMethodTitle.textContent = isTransfer ? 'Chuyển khoản online' : 'Tiền mặt';
  }

  if (completePaymentMethodHint) {
    completePaymentMethodHint.textContent = isTransfer
      ? 'Sau khi bạn xác nhận hoàn thành, khách bắt buộc phải vào tài khoản để thanh toán trực tuyến. Đơn chỉ hoàn tất khi giao dịch thành công.'
      : 'Khi bạn xác nhận hoàn thành, hệ thống sẽ ghi nhận đơn đã hoàn tất ngay với phương thức tiền mặt.';
  }

  if (completePaymentMethodBadge) {
    completePaymentMethodBadge.textContent = isTransfer ? 'Chờ khách chuyển khoản' : 'Hoàn tất ngay';
  }

  if (completeStatusBadge) {
    completeStatusBadge.textContent = isTransfer ? 'Chờ khách thanh toán' : 'Hoàn tất ngay';
  }

  if (btnSubmitCompleteForm) {
    btnSubmitCompleteForm.innerHTML = isTransfer
      ? '<span class="material-symbols-outlined">payments</span>Gửi yêu cầu chuyển khoản'
      : '<span class="material-symbols-outlined">task_alt</span>Xác nhận hoàn thành';
  }

  if (completeWorkflowList) {
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
        <span>${isTransfer ? 'Chuẩn bị chuyển đơn sang chờ khách thanh toán online' : 'Chuẩn bị chốt đơn tiền mặt ngay sau khi xác nhận'}</span>
      </div>
    `;
  }
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
completePaymentMethodInputs.forEach((input) => {
  input.addEventListener('change', () => syncCompletePaymentMethodUi(input.value));
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
  completeForm.reset();
  const initialPaymentMethod = getBookingPaymentMethod(booking);
  completePaymentMethodInputs.forEach((input) => {
    input.checked = input.value === initialPaymentMethod;
  });
  syncCompletePaymentMethodUi(initialPaymentMethod);
  imageUploadPreview.innerHTML = '';
  videoUploadPreview.innerHTML = '';
  completeModalInstance?.show();
};

if (completeForm) {
  completeForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const bookingId = completeBookingId.value;
    const paymentMethod = getSelectedCompletePaymentMethod();
    const originalButtonHtml = btnSubmitCompleteForm.innerHTML;
    btnSubmitCompleteForm.disabled = true;
    btnSubmitCompleteForm.innerHTML = paymentMethod === 'transfer'
      ? '<span class="material-symbols-outlined">progress_activity</span>Đang gửi yêu cầu'
      : '<span class="material-symbols-outlined">progress_activity</span>Đang xác nhận hoàn thành';

    try {
      const formData = new FormData();
      formData.append('_method', 'POST');
      formData.append('phuong_thuc_thanh_toan', paymentMethod);

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

      showToast(data.message || 'Đã cập nhật trạng thái hoàn thành cho đơn hàng.');
      completeModalInstance?.hide();
      completeForm.reset();
      imageUploadPreview.innerHTML = '';
      videoUploadPreview.innerHTML = '';
      window.currentStatus = paymentMethod === 'transfer' ? 'payment' : 'done';
      window.currentPage = 1;
      syncBoardStatusTabs();
      syncWorkerBookingsUrl({ bookingId: paymentMethod === 'transfer' ? bookingId : 0 });
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
hydrateTopbarIdentity();
syncCostWizardUi();
syncBoardStatusTabs();
syncBookingScopeButtons();
initTopbarNotificationCenter();
loadMyBookings(window.currentStatus);
</script>
@endpush
