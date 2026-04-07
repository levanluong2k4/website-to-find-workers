@extends('layouts.app')

@section('title', 'Feedback va khieu nai - Admin')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Fira+Code:wght@500;600;700&family=Fira+Sans:wght@400;500;600;700;800&display=swap');

    :root {
        --feedback-admin-bg: #f8fafc;
        --feedback-admin-panel: rgba(255, 255, 255, 0.94);
        --feedback-admin-border: rgba(148, 163, 184, 0.18);
        --feedback-admin-text: #0f172a;
        --feedback-admin-muted: #64748b;
        --feedback-admin-primary: #0284c7;
        --feedback-admin-shadow: 0 26px 60px rgba(15, 23, 42, 0.08);
    }

    body {
        background:
            radial-gradient(circle at top left, rgba(2, 132, 199, 0.12), transparent 24%),
            radial-gradient(circle at bottom right, rgba(220, 38, 38, 0.08), transparent 18%),
            var(--feedback-admin-bg);
        color: var(--feedback-admin-text);
        font-family: 'Fira Sans', sans-serif;
    }

    .feedback-admin-page {
        min-height: calc(100vh - 7rem);
    }

    .feedback-admin-shell {
        padding: 30px;
        border-radius: 32px;
        background: rgba(255, 255, 255, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(20px);
        box-shadow: var(--feedback-admin-shadow);
    }

    .feedback-admin-topbar,
    .feedback-admin-toolbar,
    .feedback-admin-panel__head {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
    }

    .feedback-admin-topbar {
        margin-bottom: 20px;
    }

    .feedback-admin-kicker {
        margin: 0 0 6px;
        color: var(--feedback-admin-muted);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .feedback-admin-title {
        margin: 0;
        font-family: 'Fira Code', monospace;
        font-size: clamp(1.85rem, 2vw, 2.5rem);
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .feedback-admin-subtitle {
        margin: 10px 0 0;
        max-width: 760px;
        color: var(--feedback-admin-muted);
        font-size: 0.95rem;
        line-height: 1.62;
    }

    .feedback-admin-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0 16px;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        background: #fff;
        color: var(--feedback-admin-text);
        font-size: 0.84rem;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
    }

    .feedback-admin-action:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .feedback-admin-action--primary {
        background: linear-gradient(135deg, #0284c7, #38bdf8);
        border-color: transparent;
        color: #fff;
    }

    .feedback-admin-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .feedback-admin-stat,
    .feedback-admin-panel,
    .feedback-admin-toolbar {
        background: var(--feedback-admin-panel);
        border: 1px solid var(--feedback-admin-border);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.06);
    }

    .feedback-admin-stat {
        padding: 18px;
        border-radius: 22px;
    }

    .feedback-admin-stat__label {
        display: block;
        color: var(--feedback-admin-muted);
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .feedback-admin-stat__value {
        display: block;
        margin-top: 10px;
        font-size: 1.75rem;
        line-height: 1;
        font-weight: 800;
    }

    .feedback-admin-stat__meta {
        display: block;
        margin-top: 8px;
        color: var(--feedback-admin-muted);
        font-size: 0.82rem;
    }

    .feedback-admin-toolbar {
        margin-bottom: 18px;
        padding: 16px;
        border-radius: 24px;
        align-items: center;
        flex-wrap: wrap;
    }

    .feedback-admin-toolbar-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        flex: 1 1 640px;
    }

    .feedback-admin-input,
    .feedback-admin-select {
        min-height: 44px;
        padding: 0 14px;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: #fff;
        color: var(--feedback-admin-text);
        font-size: 0.9rem;
        outline: none;
    }

    .feedback-admin-input {
        min-width: 240px;
        flex: 1 1 240px;
    }

    .feedback-admin-select {
        min-width: 160px;
        flex: 0 0 160px;
    }

    .feedback-admin-main {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(320px, 0.9fr);
        gap: 18px;
    }

    .feedback-admin-panel {
        border-radius: 28px;
        overflow: hidden;
    }

    .feedback-admin-panel__head {
        padding: 18px 20px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
    }

    .feedback-admin-panel__title {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
    }

    .feedback-admin-panel__copy {
        margin: 6px 0 0;
        color: var(--feedback-admin-muted);
        font-size: 0.84rem;
    }

    .feedback-admin-list,
    .feedback-admin-preview {
        display: grid;
        gap: 12px;
        padding: 20px;
    }

    .feedback-admin-item,
    .feedback-admin-block,
    .feedback-admin-empty {
        border-radius: 20px;
        background: rgba(248, 250, 252, 0.9);
        border: 1px solid rgba(148, 163, 184, 0.12);
    }

    .feedback-admin-item {
        padding: 16px;
        cursor: pointer;
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .feedback-admin-item:hover,
    .feedback-admin-item.is-selected {
        transform: translateY(-1px);
        border-color: rgba(2, 132, 199, 0.24);
        box-shadow: 0 18px 34px rgba(2, 132, 199, 0.06);
    }

    .feedback-admin-item-top,
    .feedback-admin-preview-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
    }

    .feedback-admin-code {
        color: var(--feedback-admin-muted);
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .feedback-admin-name {
        margin-top: 5px;
        font-size: 0.96rem;
        font-weight: 800;
        line-height: 1.45;
    }

    .feedback-admin-subcopy {
        margin-top: 5px;
        color: var(--feedback-admin-muted);
        font-size: 0.84rem;
        line-height: 1.6;
    }

    .feedback-admin-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 30px;
        padding: 0 12px;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 800;
    }

    .feedback-admin-pill-stack {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .feedback-admin-pill--info { background: rgba(2, 132, 199, 0.1); color: #0284c7; }
    .feedback-admin-pill--success { background: rgba(5, 150, 105, 0.12); color: #059669; }
    .feedback-admin-pill--warning { background: rgba(234, 88, 12, 0.12); color: #ea580c; }
    .feedback-admin-pill--danger { background: rgba(220, 38, 38, 0.1); color: #dc2626; }
    .feedback-admin-pill--muted { background: rgba(148, 163, 184, 0.12); color: #64748b; }

    .feedback-admin-block {
        padding: 16px;
    }

    .feedback-admin-label {
        display: block;
        margin-bottom: 8px;
        color: var(--feedback-admin-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .feedback-admin-value {
        color: var(--feedback-admin-text);
        font-size: 0.94rem;
        font-weight: 700;
        line-height: 1.65;
    }

    .feedback-admin-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .feedback-admin-empty {
        padding: 42px 24px;
        color: var(--feedback-admin-muted);
        text-align: center;
        line-height: 1.7;
    }

    @media (max-width: 1199.98px) {
        .feedback-admin-stats {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .feedback-admin-main {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .feedback-admin-shell {
            padding: 20px;
            border-radius: 24px;
        }

        .feedback-admin-topbar {
            flex-direction: column;
        }

        .feedback-admin-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .feedback-admin-actions {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<section class="feedback-admin-page py-4">
    <div class="container-xxl">
        <div class="feedback-admin-shell" id="customerFeedbackApp">
            <div class="feedback-admin-topbar">
                <div>
                    <p class="feedback-admin-kicker">Khach hang / Feedback va khieu nai</p>
                    <h1 class="feedback-admin-title">Feedback va khieu nai</h1>
                    <p class="feedback-admin-subtitle">
                        Tiep nhan phan hoi cua khach hang, xem nhanh noi dung khieu nai va cap nhat trang thai xu ly.
                    </p>
                </div>
                <div class="feedback-admin-toolbar-group" style="justify-content:flex-end; flex:0 0 auto;">
                    <a class="feedback-admin-action" href="{{ route('admin.customers') }}">Khach hang</a>
                    <button type="button" class="feedback-admin-action feedback-admin-action--primary" id="customerFeedbackRefreshButton">Lam moi</button>
                </div>
            </div>

            <div class="feedback-admin-stats" id="customerFeedbackStats"></div>

            <div class="feedback-admin-toolbar">
                <div class="feedback-admin-toolbar-group">
                    <input type="search" class="feedback-admin-input" id="customerFeedbackSearch" placeholder="Tim theo khach, tho, ma don, dich vu...">
                    <select class="feedback-admin-select" id="customerFeedbackType">
                        <option value="">Tat ca loai</option>
                        <option value="low_rating">Danh gia thap</option>
                        <option value="cancellation">Huy don</option>
                    </select>
                    <select class="feedback-admin-select" id="customerFeedbackStatus">
                        <option value="">Tat ca trang thai</option>
                        <option value="new">Moi</option>
                        <option value="in_progress">Dang xu ly</option>
                        <option value="resolved">Da xu ly</option>
                    </select>
                </div>
            </div>

            <div class="feedback-admin-main">
                <section class="feedback-admin-panel">
                    <div class="feedback-admin-panel__head">
                        <div>
                            <h2 class="feedback-admin-panel__title">Danh sach case</h2>
                            <p class="feedback-admin-panel__copy" id="customerFeedbackCaption">Dang tai du lieu...</p>
                        </div>
                    </div>
                    <div class="feedback-admin-list" id="customerFeedbackList"></div>
                </section>

                <aside class="feedback-admin-panel">
                    <div class="feedback-admin-panel__head">
                        <div>
                            <h2 class="feedback-admin-panel__title">Chi tiet case</h2>
                            <p class="feedback-admin-panel__copy">Chon mot case de xem nhanh thong tin lien quan.</p>
                        </div>
                    </div>
                    <div class="feedback-admin-preview" id="customerFeedbackPreview"></div>
                </aside>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/customer-feedback.js') }}"></script>
@endpush
