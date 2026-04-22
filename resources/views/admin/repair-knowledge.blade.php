@extends('layouts.app')

@section('title', 'Tri thức sửa chữa - Thợ Tốt')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@700;800&display=swap');

    body {
        background:
            radial-gradient(circle at top left, rgba(0, 88, 190, 0.08), transparent 24rem),
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.08), transparent 26rem),
            #f3f6fa;
    }

    .knowledge-page {
        padding: 24px 32px 48px;
    }

    .knowledge-shell {
        display: grid;
        gap: 24px;
    }

    .knowledge-hero {
        display: grid;
        gap: 18px;
        padding: 28px;
        border: 1px solid rgba(203, 213, 225, 0.92);
        border-radius: 28px;
        background:
            radial-gradient(circle at top right, rgba(37, 99, 235, 0.12), transparent 16rem),
            linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.92));
        box-shadow: 0 24px 45px rgba(15, 23, 42, 0.08);
    }

    .knowledge-hero__lead {
        display: grid;
        gap: 12px;
        max-width: 880px;
    }

    .knowledge-hero__title {
        margin: 0;
        color: #191c1e;
        font-family: 'Manrope', sans-serif;
        font-size: clamp(34px, 4.5vw, 58px);
        font-weight: 800;
        line-height: 1.02;
        letter-spacing: -0.05em;
    }

    .knowledge-hero__copy {
        margin: 0;
        color: #475569;
        font-size: 16px;
        line-height: 1.8;
        max-width: 760px;
    }

    .knowledge-hero__stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .knowledge-hero__stat {
        display: grid;
        gap: 8px;
        min-height: 108px;
        padding: 18px 20px;
        border-radius: 22px;
        border: 1px solid rgba(219, 229, 239, 0.96);
        background: rgba(255, 255, 255, 0.94);
    }

    .knowledge-hero__stat span {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .knowledge-hero__stat strong {
        color: #0f172a;
        font-family: 'Manrope', sans-serif;
        font-size: clamp(28px, 3vw, 42px);
        font-weight: 800;
        line-height: 1;
    }

    .knowledge-hero__stat p {
        margin: 0;
        color: #64748b;
        font-size: 13px;
        line-height: 1.55;
    }

    .knowledge-guide {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .knowledge-guide__card {
        display: grid;
        gap: 12px;
        min-height: 196px;
        padding: 22px;
        border-radius: 24px;
        border: 1px solid rgba(220, 227, 234, 0.95);
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.05);
    }

    .knowledge-guide__card small {
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .knowledge-guide__card h3 {
        margin: 0;
        color: #0f172a;
        font-family: 'Manrope', sans-serif;
        font-size: 22px;
        font-weight: 800;
        line-height: 1.18;
    }

    .knowledge-guide__card p {
        margin: 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.7;
    }

    .knowledge-guide__card ul {
        margin: 0;
        padding-left: 18px;
        color: #475569;
        font-size: 14px;
        line-height: 1.7;
    }

    .knowledge-guide__card.is-primary {
        background: linear-gradient(180deg, rgba(29, 78, 216, 0.08), rgba(255, 255, 255, 0.96));
    }

    .knowledge-guide__card.is-accent {
        background: linear-gradient(155deg, #0f172a 0%, #1e3a8a 100%);
        border-color: transparent;
        color: rgba(255, 255, 255, 0.92);
        box-shadow: 0 24px 38px rgba(15, 23, 42, 0.18);
    }

    .knowledge-guide__card.is-accent small,
    .knowledge-guide__card.is-accent p,
    .knowledge-guide__card.is-accent ul {
        color: rgba(255, 255, 255, 0.82);
    }

    .knowledge-guide__card.is-accent h3 {
        color: #fff;
    }

    .knowledge-contextbar {
        position: sticky;
        top: 96px;
        z-index: 20;
        display: grid;
        gap: 14px;
        padding: 14px 18px;
        border: 1px solid rgba(203, 213, 225, 0.92);
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.82);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(18px);
    }

    .knowledge-service-context {
        display: flex;
        align-items: flex-start;
        flex: 1 1 auto;
        flex-wrap: wrap;
        gap: 10px;
        min-width: 0;
        overflow: hidden;
    }

    .knowledge-service-pill {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        min-height: 40px;
        padding: 8px 16px;
        border: 1px solid #d7e0ea;
        border-radius: 999px;
        background: #ffffff;
        color: #475569;
        font-size: 14px;
        font-weight: 600;
        max-width: min(100%, 220px);
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .knowledge-service-pill:hover {
        transform: translateY(-1px);
        border-color: rgba(0, 88, 190, 0.2);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }

    .knowledge-service-pill.is-active {
        background: linear-gradient(135deg, #1d4ed8, #0058be);
        border-color: transparent;
        color: #fff;
        box-shadow: 0 12px 28px rgba(0, 88, 190, 0.24);
    }

    .knowledge-service-pill.is-offline {
        opacity: 0.72;
    }

    .knowledge-service-pill__label {
        min-width: 0;
        line-height: 1.28;
        text-align: left;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .knowledge-service-pill__count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        min-height: 22px;
        padding: 0 8px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        flex-shrink: 0;
    }

    .knowledge-service-pill.is-active .knowledge-service-pill__count {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .knowledge-service-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 40px;
        padding: 8px 14px;
        border: 1px dashed #c8d4e3;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.72);
        color: #475569;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
    }

    .knowledge-service-toggle i {
        font-size: 12px;
    }

    .knowledge-contextbar__tools {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        width: 100%;
    }

    .knowledge-contextbar__search {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        width: min(100%, 340px);
        min-width: 0;
        min-height: 42px;
        padding: 0 14px;
        border: 1px solid #d7e0ea;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.96);
        color: #64748b;
    }

    .knowledge-contextbar__search i {
        color: #94a3b8;
        font-size: 14px;
    }

    .knowledge-contextbar__search input {
        width: 100%;
        border: 0;
        outline: 0;
        background: transparent;
        color: #0f172a;
        font-size: 14px;
    }

    .knowledge-contextbar__search input::placeholder {
        color: #94a3b8;
    }

    .knowledge-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 320px;
        gap: 28px;
        align-items: start;
    }

    .knowledge-main {
        min-width: 0;
        width: 100%;
        max-width: 1040px;
        justify-self: start;
    }

    .knowledge-workspace-shell {
        display: grid;
        gap: 24px;
    }

    .knowledge-toolbar {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
    }

    .knowledge-toolbar__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #0058be;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.18em;
        text-transform: uppercase;
    }

    .knowledge-toolbar__copy {
        max-width: 640px;
        margin: 10px 0 0;
        color: #64748b;
        font-size: 15px;
        line-height: 1.7;
    }

    .knowledge-toolbar__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
    }

    .knowledge-primary-btn,
    .knowledge-secondary-btn {
        min-height: 46px;
        border-radius: 999px;
        padding: 0 18px;
        font-size: 14px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid transparent;
        transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .knowledge-primary-btn {
        background: linear-gradient(135deg, #1d4ed8, #0058be);
        color: #fff;
        box-shadow: 0 14px 24px rgba(0, 88, 190, 0.2);
    }

    .knowledge-secondary-btn {
        background: rgba(255, 255, 255, 0.86);
        border-color: #d7e0ea;
        color: #334155;
    }

    .knowledge-primary-btn:hover,
    .knowledge-secondary-btn:hover {
        transform: translateY(-1px);
    }

    .knowledge-tree {
        display: grid;
        gap: 24px;
        width: 100%;
        max-width: 940px;
    }

    .knowledge-workspace__summary {
        display: grid;
        gap: 14px;
    }

    .knowledge-workspace__kicker,
    .knowledge-section-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .knowledge-workspace__kicker::before,
    .knowledge-section-label::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 999px;
        background: #0058be;
        box-shadow: 0 0 0 4px rgba(0, 88, 190, 0.12);
    }

    .knowledge-workspace__title {
        margin: 0;
        font-family: 'Manrope', sans-serif;
        font-size: clamp(34px, 4vw, 48px);
        font-weight: 800;
        line-height: 1.04;
        letter-spacing: -0.04em;
        color: #191c1e;
        overflow-wrap: anywhere;
    }

    .knowledge-workspace__desc {
        max-width: 760px;
        margin: 0;
        color: #64748b;
        font-size: 17px;
        line-height: 1.7;
    }

    .knowledge-summary-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .knowledge-summary-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 36px;
        padding: 0 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.85);
        border: 1px solid #dbe5ef;
        color: #334155;
        font-size: 13px;
        font-weight: 700;
    }

    .knowledge-summary-chip i {
        color: #0058be;
    }

    .knowledge-section {
        display: grid;
        gap: 16px;
    }

    .knowledge-section.is-focus-target {
        padding: 20px;
        border: 1px solid rgba(33, 112, 228, 0.16);
        border-radius: 24px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(240, 246, 255, 0.92));
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.06);
    }

    .knowledge-symptom-cloud {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .knowledge-tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 44px;
        padding: 0 16px;
        border-radius: 14px;
        border: 1px solid rgba(33, 112, 228, 0.18);
        background: rgba(33, 112, 228, 0.1);
        color: #0058be;
        font-size: 14px;
        font-weight: 700;
        transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease, border-color 0.18s ease;
    }

    .knowledge-tag:hover,
    .knowledge-tag.is-selected {
        transform: translateY(-1px);
        background: rgba(33, 112, 228, 0.14);
        border-color: rgba(33, 112, 228, 0.28);
        box-shadow: 0 12px 24px rgba(0, 88, 190, 0.08);
    }

    .knowledge-tag--ghost {
        background: #eceef0;
        border-color: transparent;
        color: #424754;
    }

    .knowledge-cause-stack {
        display: grid;
        gap: 24px;
    }

    .knowledge-cause-card {
        display: grid;
        gap: 18px;
        padding: 24px;
        border: 1px solid rgba(220, 227, 234, 0.95);
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 16px 32px rgba(148, 163, 184, 0.12);
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .knowledge-cause-card:hover,
    .knowledge-cause-card.is-selected {
        transform: translateY(-1px);
        border-color: rgba(33, 112, 228, 0.22);
        box-shadow: 0 20px 40px rgba(0, 88, 190, 0.08);
    }

    .knowledge-cause-card.is-focus-spotlight {
        border-color: rgba(33, 112, 228, 0.28);
        box-shadow: 0 18px 36px rgba(0, 88, 190, 0.1);
    }

    .knowledge-cause-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .knowledge-cause-card__lead {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        min-width: 0;
    }

    .knowledge-cause-card__icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eceef0;
        color: #64748b;
        font-size: 18px;
        flex-shrink: 0;
    }

    .knowledge-cause-card__copy {
        min-width: 0;
    }

    .knowledge-cause-card__title {
        margin: 0;
        color: #191c1e;
        font-family: 'Manrope', sans-serif;
        font-size: 24px;
        font-weight: 700;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    .knowledge-cause-card__desc {
        margin: 8px 0 0;
        color: #64748b;
        font-size: 15px;
        line-height: 1.6;
    }

    .knowledge-cause-card__badge {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        min-height: 32px;
        padding: 0 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .knowledge-cause-card__badge.is-primary {
        background: rgba(0, 88, 190, 0.14);
        color: #0f3b85;
    }

    .knowledge-cause-card__badge.is-warning {
        background: #ffe2cf;
        color: #7c2d12;
    }

    .knowledge-cause-card__badge.is-muted {
        background: #e6e8ea;
        color: #475569;
    }

    .knowledge-cause-card__symptoms {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .knowledge-cause-card__footer {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .knowledge-inline-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 36px;
        padding: 0 14px;
        border: 1px solid #d7e0ea;
        border-radius: 999px;
        background: #fff;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
    }

    .knowledge-inline-action.is-danger {
        color: #b91c1c;
    }

    .knowledge-resolution-list {
        display: grid;
        gap: 12px;
    }

    .knowledge-resolution {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px;
        border: 1px solid rgba(226, 232, 240, 0.96);
        border-radius: 16px;
        background: #f7f9fb;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .knowledge-resolution:hover,
    .knowledge-resolution.is-selected {
        transform: translateY(-1px);
        border-color: rgba(33, 112, 228, 0.24);
        box-shadow: 0 12px 20px rgba(15, 23, 42, 0.06);
    }

    .knowledge-resolution.is-focus-spotlight {
        border-color: rgba(33, 112, 228, 0.3);
        box-shadow: 0 16px 28px rgba(0, 88, 190, 0.09);
    }

    .knowledge-resolution__copy {
        min-width: 0;
        flex: 1 1 auto;
    }

    .knowledge-resolution__titleline {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .knowledge-resolution__titleline i {
        color: #0058be;
        font-size: 14px;
    }

    .knowledge-resolution__title {
        margin: 0;
        color: #191c1e;
        font-size: 18px;
        font-weight: 700;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .knowledge-resolution__desc {
        margin: 8px 0 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.65;
        white-space: pre-wrap;
    }

    .knowledge-resolution__side {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
        flex-shrink: 0;
    }

    .knowledge-resolution__price {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 32px;
        padding: 0 16px;
        border-radius: 999px;
        background: #0058be;
        color: #fff;
        font-size: 14px;
        font-weight: 800;
        line-height: 1;
        box-shadow: 0 10px 18px rgba(0, 88, 190, 0.16);
    }

    .knowledge-resolution.is-price-focus .knowledge-resolution__price {
        box-shadow: 0 16px 28px rgba(0, 88, 190, 0.28);
        transform: translateY(-1px);
    }

    .knowledge-resolution__price.is-empty {
        background: #e6e8ea;
        color: #64748b;
        box-shadow: none;
    }

    .knowledge-resolution__actions {
        display: flex;
        gap: 8px;
    }

    .knowledge-resolution__action {
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.95);
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 16px rgba(148, 163, 184, 0.14);
    }

    .knowledge-resolution__action.is-danger {
        color: #b91c1c;
    }

    .knowledge-sidebar {
        position: sticky;
        top: 154px;
        display: grid;
        gap: 18px;
    }

    .knowledge-side-card {
        display: grid;
        gap: 16px;
        padding: 24px;
        border-radius: 18px;
        background: #fff;
        border: 1px solid rgba(226, 232, 240, 0.96);
        box-shadow: 0 12px 24px rgba(148, 163, 184, 0.12);
    }

    .knowledge-side-card--muted {
        background: #f2f4f6;
    }

    .knowledge-side-card--accent {
        position: relative;
        overflow: hidden;
        background: linear-gradient(144deg, #0058be 0%, #2170e4 100%);
        border-color: transparent;
        box-shadow: 0 22px 36px rgba(0, 88, 190, 0.24);
        color: rgba(255, 255, 255, 0.92);
    }

    .knowledge-side-card--accent::after {
        content: '';
        position: absolute;
        right: -26px;
        bottom: -26px;
        width: 120px;
        height: 120px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        filter: blur(12px);
    }

    .knowledge-side-card__eyebrow {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .knowledge-side-card__title {
        margin: 0;
        color: #191c1e;
        font-size: 26px;
        font-family: 'Manrope', sans-serif;
        font-weight: 700;
        line-height: 1.2;
    }

    .knowledge-side-card__desc {
        margin: 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.7;
    }

    .knowledge-side-card--accent .knowledge-side-card__eyebrow,
    .knowledge-side-card--accent .knowledge-side-card__desc {
        color: rgba(255, 255, 255, 0.88);
    }

    .knowledge-side-card--accent .knowledge-side-card__title {
        color: #fff;
    }

    .knowledge-info-grid {
        display: grid;
        gap: 12px;
    }

    .knowledge-info {
        display: grid;
        gap: 4px;
        padding: 14px 16px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.86);
        border: 1px solid rgba(226, 232, 240, 0.92);
    }

    .knowledge-info__label {
        color: #94a3b8;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .knowledge-info__value {
        color: #191c1e;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.55;
        white-space: pre-wrap;
    }

    .knowledge-side-actions {
        display: grid;
        gap: 10px;
    }

    .knowledge-side-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 44px;
        padding: 0 16px;
        border: 1px solid #d7e0ea;
        border-radius: 14px;
        background: #fff;
        color: #334155;
        font-size: 14px;
        font-weight: 700;
    }

    .knowledge-side-action.is-primary {
        background: linear-gradient(135deg, #1d4ed8, #0058be);
        border-color: transparent;
        color: #fff;
    }

    .knowledge-side-action.is-danger {
        color: #b91c1c;
    }

    .knowledge-side-section {
        display: grid;
        gap: 10px;
    }

    .knowledge-side-section__label {
        color: #94a3b8;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .knowledge-side-list {
        display: grid;
        gap: 12px;
    }

    .knowledge-side-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 0;
        border: 0;
        background: transparent;
        color: inherit;
        text-align: left;
    }

    .knowledge-side-item__copy {
        min-width: 0;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #424754;
        font-size: 14px;
        font-weight: 600;
    }

    .knowledge-side-item__copy i {
        color: #94a3b8;
        font-size: 13px;
    }

    .knowledge-side-item__meta {
        display: inline-flex;
        align-items: center;
        min-height: 22px;
        padding: 0 8px;
        border-radius: 999px;
        background: rgba(0, 88, 190, 0.1);
        color: #0058be;
        font-size: 11px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .knowledge-side-divider {
        width: 100%;
        height: 1px;
        background: rgba(148, 163, 184, 0.24);
    }

    .knowledge-empty {
        display: grid;
        gap: 10px;
        justify-items: center;
        padding: 24px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.7);
        color: #64748b;
        text-align: center;
    }

    .knowledge-empty--inline {
        min-width: 260px;
        padding: 12px 16px;
        background: transparent;
        justify-items: start;
        text-align: left;
    }

    .knowledge-multiselect {
        min-height: 220px;
        border-radius: 18px;
    }

    .knowledge-modal__panel {
        border: 0;
        overflow: hidden;
        border-radius: 24px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
    }

    .knowledge-modal__head {
        padding: 1rem 1.25rem;
        background: linear-gradient(135deg, rgba(0, 88, 190, 0.12), rgba(29, 78, 216, 0.08));
        border-bottom: 1px solid rgba(148, 163, 184, 0.18);
    }

    .knowledge-modal__body {
        padding: 1.25rem;
    }

    .knowledge-modal__body .form-control,
    .knowledge-modal__body .form-select {
        border-radius: 16px;
        min-height: 48px;
    }

    @media (max-width: 1399.98px) {
        .knowledge-layout {
            grid-template-columns: minmax(0, 1fr) 290px;
        }

        .knowledge-contextbar__search {
            min-width: 220px;
        }
    }

    @media (max-width: 1199.98px) {
        .knowledge-hero__stats,
        .knowledge-guide {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .knowledge-layout {
            grid-template-columns: 1fr;
        }

        .knowledge-main,
        .knowledge-tree {
            max-width: none;
        }

        .knowledge-sidebar {
            position: static;
        }
    }

    @media (max-width: 991.98px) {
        .knowledge-page {
            padding: 20px 20px 40px;
        }

        .knowledge-contextbar {
            top: 88px;
            flex-direction: column;
            align-items: stretch;
        }

        .knowledge-contextbar__tools {
            width: 100%;
            justify-content: stretch;
        }

        .knowledge-contextbar__search {
            width: 100%;
        }

        .knowledge-toolbar {
            flex-direction: column;
        }

        .knowledge-toolbar__actions {
            width: 100%;
            justify-content: flex-start;
        }

        .knowledge-resolution {
            flex-direction: column;
            align-items: stretch;
        }

        .knowledge-resolution__side {
            width: 100%;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    @media (max-width: 767.98px) {
        .knowledge-page {
            padding: 16px 14px 32px;
        }

        .knowledge-hero {
            padding: 22px 18px;
        }

        .knowledge-hero__stats,
        .knowledge-guide {
            grid-template-columns: 1fr;
        }

        .knowledge-contextbar {
            border-radius: 18px;
            padding: 14px;
        }

        .knowledge-workspace__title {
            font-size: 32px;
        }

        .knowledge-cause-card,
        .knowledge-side-card {
            padding: 18px;
        }

        .knowledge-cause-card__header {
            flex-direction: column;
        }

        .knowledge-cause-card__lead {
            width: 100%;
        }

        .knowledge-cause-card__badge {
            align-self: flex-start;
        }

        .knowledge-primary-btn,
        .knowledge-secondary-btn {
            width: 100%;
            justify-content: center;
        }
    }

    .knowledge-hero,
    .knowledge-guide,
    .knowledge-toolbar {
        display: none !important;
    }

    .knowledge-page {
        padding: 12px 32px 40px;
    }

    .knowledge-shell {
        gap: 18px;
    }

    .knowledge-contextbar {
        top: 82px;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 20px;
    }

    .knowledge-contextbar__search {
        display: none !important;
    }

    .knowledge-contextbar__tools {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }

    .knowledge-filter-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 38px;
        padding: 0 14px;
        border-radius: 999px;
        border: 1px solid #d7e0ea;
        background: rgba(255, 255, 255, 0.96);
        color: #475569;
        font-size: 13px;
        font-weight: 700;
    }

    .knowledge-filter-indicator i {
        color: #7c8ca3;
    }

    .knowledge-filter-indicator.is-soft {
        background: #f8fbff;
        color: #0058be;
        border-color: rgba(0, 88, 190, 0.12);
    }

    .knowledge-filter-indicator.is-empty {
        color: #94a3b8;
    }

    .knowledge-layout {
        gap: 24px;
        align-items: start;
    }

    .knowledge-main,
    .knowledge-tree {
        max-width: none;
        width: 100%;
    }

    .knowledge-tree {
        gap: 20px;
    }

    .knowledge-canvas-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
    }

    .knowledge-canvas-header__copy {
        max-width: 700px;
        display: grid;
        gap: 12px;
    }

    .knowledge-canvas-header__actions {
        width: min(100%, 220px);
        display: grid;
        gap: 12px;
        justify-items: end;
    }

    .knowledge-canvas-header__hint {
        margin: 0;
        color: #64748b;
        font-size: 13px;
        line-height: 1.65;
        text-align: right;
    }

    .knowledge-fab {
        min-height: 96px;
        min-width: 172px;
        padding: 20px 28px;
        border: 0;
        border-radius: 30px;
        background: linear-gradient(135deg, #1d4ed8, #0058be);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        font-size: 17px;
        font-weight: 800;
        box-shadow: 0 24px 32px rgba(0, 88, 190, 0.22);
    }

    .knowledge-fab i {
        font-size: 18px;
    }

    .knowledge-workspace__desc {
        max-width: 640px;
        font-size: 16px;
    }

    .knowledge-summary-chips {
        gap: 12px;
    }

    .knowledge-summary-chip {
        min-height: 38px;
        background: rgba(255, 255, 255, 0.98);
    }

    .knowledge-section {
        gap: 18px;
    }

    .knowledge-inline-search {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        width: min(100%, 640px);
        min-height: 52px;
        padding: 0 18px;
        border: 1px solid #dbe5ef;
        border-radius: 18px;
        background: #fff;
        color: #64748b;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.04);
    }

    .knowledge-inline-search i {
        color: #94a3b8;
    }

    .knowledge-inline-search input {
        width: 100%;
        border: 0;
        outline: 0;
        background: transparent;
        color: #0f172a;
        font-size: 15px;
    }

    .knowledge-inline-search input::placeholder {
        color: #94a3b8;
    }

    .knowledge-cause-stack {
        gap: 20px;
    }

    .knowledge-cause-card {
        gap: 16px;
        padding: 22px;
        border-radius: 26px;
    }

    .knowledge-cause-card__header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .knowledge-meta-action {
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 12px;
        background: #eef2f7;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background 0.18s ease, color 0.18s ease, transform 0.18s ease;
    }

    .knowledge-meta-action:hover {
        transform: translateY(-1px);
        background: #e2e8f0;
    }

    .knowledge-meta-action.is-danger {
        color: #dc2626;
    }

    .knowledge-resolution {
        padding: 18px;
        border-radius: 18px;
        background: #f8fbff;
    }

    .knowledge-resolution__price {
        min-width: 110px;
        justify-content: center;
        background: linear-gradient(135deg, #0058be, #1d4ed8);
        color: #fff;
        box-shadow: 0 12px 20px rgba(0, 88, 190, 0.16);
    }

    .knowledge-resolution__price.is-empty {
        background: #e5ecf3;
        color: #64748b;
        box-shadow: none;
    }

    .knowledge-cause-card__footer.is-dashed {
        justify-content: stretch;
    }

    .knowledge-inline-action.is-dashed,
    .knowledge-block-action {
        width: 100%;
        justify-content: center;
        min-height: 48px;
        border: 1px dashed #d6dfea;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.72);
        color: #64748b;
        font-weight: 700;
    }

    .knowledge-block-action {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .knowledge-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        flex-wrap: wrap;
        padding: 8px 2px 0;
    }

    .knowledge-pagination__summary {
        color: #64748b;
        font-size: 14px;
    }

    .knowledge-pagination__controls {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .knowledge-page-btn {
        min-width: 42px;
        height: 42px;
        border: 1px solid #d7e0ea;
        border-radius: 14px;
        background: #fff;
        color: #475569;
        font-size: 14px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 18px rgba(15, 23, 42, 0.04);
    }

    .knowledge-page-btn.is-active {
        border-color: transparent;
        background: linear-gradient(135deg, #1d4ed8, #0058be);
        color: #fff;
        box-shadow: 0 16px 24px rgba(0, 88, 190, 0.18);
    }

    .knowledge-page-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
        box-shadow: none;
    }

    .knowledge-page-gap {
        color: #94a3b8;
        font-weight: 700;
        letter-spacing: 0.08em;
    }

    .knowledge-sidebar {
        top: 150px;
        display: grid;
        gap: 20px;
    }

    .knowledge-side-card {
        padding: 22px;
        border-radius: 24px;
    }

    .knowledge-side-metrics {
        display: grid;
        gap: 12px;
        margin-top: 14px;
    }

    .knowledge-side-metric {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 16px;
        background: #f8fafc;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
    }

    .knowledge-side-metric span {
        color: #64748b;
    }

    .knowledge-side-metric strong {
        color: #0f172a;
        font-size: 15px;
    }

    .knowledge-side-action {
        min-height: 44px;
        border-radius: 16px;
        justify-content: flex-start;
    }

    .knowledge-side-card--accent .knowledge-side-action {
        background: #fff;
        color: #0058be;
        border-color: transparent;
    }

    .knowledge-side-item__meta {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        background: rgba(0, 88, 190, 0.08);
        color: #0058be;
        font-size: 12px;
        font-weight: 700;
    }

    @media (max-width: 1199.98px) {
        .knowledge-canvas-header {
            flex-direction: column;
        }

        .knowledge-canvas-header__actions {
            width: 100%;
            justify-items: flex-start;
        }

        .knowledge-canvas-header__hint {
            text-align: left;
        }

        .knowledge-fab {
            min-height: 72px;
            min-width: 0;
        }
    }

    @media (max-width: 767.98px) {
        .knowledge-page {
            padding: 12px 14px 28px;
        }

        .knowledge-contextbar {
            top: 80px;
            padding: 12px;
        }

        .knowledge-contextbar__tools {
            justify-content: flex-start;
        }

        .knowledge-cause-card {
            padding: 18px;
        }

        .knowledge-cause-card__header-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .knowledge-pagination {
            flex-direction: column;
            align-items: flex-start;
        }

        .knowledge-inline-search {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="knowledge-page">
    <div class="knowledge-shell">
        <section class="knowledge-hero">
            <div class="knowledge-hero__lead">
                <span class="knowledge-toolbar__eyebrow">Tri thức sửa chữa</span>
                <h1 class="knowledge-hero__title">1 trang để nối triệu chứng, nguyên nhân, hướng xử lý và giá</h1>
                <p class="knowledge-hero__copy">Trang này gom toàn bộ logic sửa chữa vào một nơi duy nhất. Bạn chỉ cần đi theo luồng: khách mô tả <strong>triệu chứng</strong>, đội kỹ thuật xác định <strong>nguyên nhân</strong>, rồi chọn <strong>hướng xử lý và giá tham khảo</strong> phù hợp.</p>
            </div>

            <div class="knowledge-hero__stats">
                <article class="knowledge-hero__stat">
                    <span>Dịch vụ</span>
                    <strong id="knowledgeHeroServices">0</strong>
                    <p>Số dịch vụ đã có cây tri thức sửa chữa.</p>
                </article>
                <article class="knowledge-hero__stat">
                    <span>Triệu chứng</span>
                    <strong id="knowledgeHeroSymptoms">0</strong>
                    <p>Dấu hiệu khách thường mô tả khi liên hệ đặt thợ.</p>
                </article>
                <article class="knowledge-hero__stat">
                    <span>Nguyên nhân</span>
                    <strong id="knowledgeHeroCauses">0</strong>
                    <p>Lỗi gốc kỹ thuật viên cần chẩn đoán phía sau triệu chứng.</p>
                </article>
                <article class="knowledge-hero__stat">
                    <span>Hướng xử lý + Giá</span>
                    <strong id="knowledgeHeroResolutions">0</strong>
                    <p>Phương án sửa thực tế và mức giá tham khảo đi kèm.</p>
                </article>
            </div>
        </section>

        <section class="knowledge-guide" aria-label="Giải thích cách dùng trang tri thức sửa chữa">
            <article class="knowledge-guide__card is-primary">
                <small>Bước 1</small>
                <h3>Triệu chứng</h3>
                <p>Thứ khách nhìn thấy hoặc nghe thấy, ví dụ: không lạnh, chảy nước, rung mạnh, kêu to.</p>
            </article>

            <article class="knowledge-guide__card">
                <small>Bước 2</small>
                <h3>Nguyên nhân</h3>
                <p>Lỗi gốc nằm phía sau triệu chứng. Một triệu chứng có thể dẫn tới nhiều nguyên nhân khác nhau.</p>
            </article>

            <article class="knowledge-guide__card">
                <small>Bước 3</small>
                <h3>Hướng xử lý + Giá</h3>
                <p>Giá tham khảo nằm ngay trong từng hướng xử lý, không phải một khu riêng để tách ra xem.</p>
            </article>

            <article class="knowledge-guide__card is-accent">
                <small>Cách dùng</small>
                <h3>Chọn dịch vụ rồi đọc theo đúng thứ tự</h3>
                <ul>
                    <li>Chọn dịch vụ ở thanh lọc bên dưới.</li>
                    <li>Xem khách đang mô tả triệu chứng nào.</li>
                    <li>Đối chiếu nguyên nhân tương ứng.</li>
                    <li>Chốt hướng xử lý và cập nhật giá nếu còn thiếu.</li>
                </ul>
            </article>
        </section>

        <section class="knowledge-contextbar">
            <div id="repairKnowledgeServices" class="knowledge-service-context">
                <div class="knowledge-empty knowledge-empty--inline">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mb-0">Đang tải dịch vụ...</p>
                </div>
            </div>

            <div class="knowledge-contextbar__tools">
                <label class="knowledge-contextbar__search" aria-label="Tìm tri thức sửa chữa">
                    <i class="fas fa-search"></i>
                    <input type="search" id="knowledgeSearchInput" placeholder="Tìm triệu chứng, nguyên nhân hoặc hướng xử lý...">
                </label>

                <select class="form-select d-none" id="knowledgeServiceFilter">
                    <option value="">Tất cả dịch vụ</option>
                </select>
            </div>
        </section>

        <section class="knowledge-layout">
            <div class="knowledge-main">
                <div class="knowledge-workspace-shell">
                    <div class="knowledge-toolbar">
                        <div>
                            <span class="knowledge-toolbar__eyebrow">Canvas tri thức sửa chữa</span>
                            <p class="knowledge-toolbar__copy">Chuyển luồng thao tác sang một không gian làm việc rõ ràng hơn: chọn dịch vụ, đọc nguyên nhân gốc, đối chiếu giá tham khảo và cập nhật dữ liệu ngay trong cùng một nhịp.</p>
                        </div>

                        <div class="knowledge-toolbar__actions">
                            <button type="button" class="knowledge-primary-btn" id="btnAddSymptom">
                                <i class="fas fa-plus"></i>
                                <span>Thêm triệu chứng</span>
                            </button>
                            <button type="button" class="knowledge-secondary-btn" id="btnAddCause">
                                <i class="fas fa-link"></i>
                                <span>Thêm nguyên nhân</span>
                            </button>
                            <button type="button" class="knowledge-secondary-btn" id="btnAddResolution">
                                <i class="fas fa-screwdriver-wrench"></i>
                                <span>Thêm hướng xử lý</span>
                            </button>
                        </div>
                    </div>

                    <div id="repairKnowledgeTree" class="knowledge-tree">
                        <div class="knowledge-empty">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mb-0">Đang tải canvas tri thức sửa chữa...</p>
                        </div>
                    </div>
                </div>
            </div>

            <aside id="repairKnowledgeInspector" class="knowledge-sidebar">
                <div class="knowledge-side-card">
                    <div class="knowledge-empty">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mb-0">Đang tải insight và ngữ cảnh đang chọn...</p>
                    </div>
                </div>
            </aside>
        </section>

        <div class="visually-hidden" aria-hidden="true">
            <span id="knowledgeStatServices">0</span>
            <span id="knowledgeStatSymptoms">0</span>
            <span id="knowledgeStatCauses">0</span>
            <span id="knowledgeStatResolutions">0</span>
        </div>
    </div>
</div>

<div class="modal fade" id="symptomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content knowledge-modal__panel">
            <div class="knowledge-modal__head d-flex justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="symptomModalLabel">Thêm triệu chứng</h5>
                    <p class="text-muted small mb-0">Triệu chứng luôn thuộc về một dịch vụ cụ thể.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="knowledge-modal__body">
                <form id="symptomForm" class="d-grid gap-3">
                    <input type="hidden" id="symptomId">

                    <div>
                        <label class="form-label fw-semibold" for="symptomService">Dịch vụ</label>
                        <select class="form-select" id="symptomService" required>
                            <option value="">Chọn dịch vụ</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="symptomName">Tên triệu chứng</label>
                        <textarea class="form-control" id="symptomName" rows="4" placeholder="Ví dụ: Không lạnh, Kêu to; Chảy nước" required></textarea>
                        <p class="text-muted small mt-2 mb-0">Khi thêm mới, hệ thống tự tách theo dấu phẩy, chấm phẩy hoặc xuống dòng. Khi sửa, chỉ dùng một tên trên một dòng.</p>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnSaveSymptom">
                        <i class="fas fa-save me-2"></i>Lưu triệu chứng
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="causeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content knowledge-modal__panel">
            <div class="knowledge-modal__head d-flex justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="causeModalLabel">Thêm nguyên nhân</h5>
                    <p class="text-muted small mb-0">Một nguyên nhân có thể gắn cho nhiều triệu chứng nếu cần dùng chung.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="knowledge-modal__body">
                <form id="causeForm" class="d-grid gap-3">
                    <input type="hidden" id="causeId">

                    <div>
                        <label class="form-label fw-semibold" for="causeName">Tên nguyên nhân</label>
                        <textarea class="form-control" id="causeName" rows="4" placeholder="Ví dụ: Thiếu gas, Hỏng block; Nghẹt ống" required></textarea>
                        <p class="text-muted small mt-2 mb-0">Khi thêm mới, hệ thống tự tách theo dấu phẩy, chấm phẩy hoặc xuống dòng. Khi sửa, chỉ dùng một tên trên một dòng.</p>
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="causeSymptoms">Liên kết triệu chứng</label>
                        <select multiple class="form-select knowledge-multiselect" id="causeSymptoms" required></select>
                        <p class="text-muted small mt-2 mb-0" id="causeSymptomsMeta">Giữ Ctrl hoặc Cmd để chọn nhiều triệu chứng.</p>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnSaveCause">
                        <i class="fas fa-save me-2"></i>Lưu nguyên nhân
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="resolutionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content knowledge-modal__panel">
            <div class="knowledge-modal__head d-flex justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="resolutionModalLabel">Thêm hướng xử lý</h5>
                    <p class="text-muted small mb-0">Hướng xử lý thuộc về một nguyên nhân cụ thể.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="knowledge-modal__body">
                <form id="resolutionForm" class="d-grid gap-3">
                    <input type="hidden" id="resolutionId">

                    <div>
                        <label class="form-label fw-semibold" for="resolutionCause">Nguyên nhân</label>
                        <select class="form-select" id="resolutionCause" required>
                            <option value="">Chọn nguyên nhân</option>
                        </select>
                        <p class="text-muted small mt-2 mb-0" id="resolutionCauseMeta">Chọn nguyên nhân để gắn hướng xử lý đúng nhánh.</p>
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="resolutionName">Tên hướng xử lý</label>
                        <textarea class="form-control" id="resolutionName" rows="4" placeholder="Ví dụ: Vệ sinh dàn lạnh, Nạp gas; Thay tụ" required></textarea>
                        <p class="text-muted small mt-2 mb-0">Khi thêm mới, hệ thống tự tách theo dấu phẩy, chấm phẩy hoặc xuống dòng. Giá tham khảo và mô tả hiện tại sẽ áp dụng cho tất cả các tên vừa tách. Khi sửa, chỉ dùng một tên.</p>
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="resolutionPrice">Giá tham khảo</label>
                        <input type="number" class="form-control" id="resolutionPrice" min="0" step="1000" placeholder="Ví dụ: 450000">
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="resolutionDescription">Mô tả công việc</label>
                        <textarea class="form-control" id="resolutionDescription" rows="5" placeholder="Mô tả việc cần làm, vật tư, lưu ý kỹ thuật..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnSaveResolution">
                        <i class="fas fa-save me-2"></i>Lưu hướng xử lý
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php($repairKnowledgeScript = 'assets/js/admin/repair-knowledge.js')
<script
    type="module"
    charset="utf-8"
    src="{{ asset($repairKnowledgeScript) }}?v={{ file_exists(public_path($repairKnowledgeScript)) ? filemtime(public_path($repairKnowledgeScript)) : time() }}"
></script>
@endpush
