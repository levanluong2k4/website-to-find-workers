@extends('layouts.app')

@section('title', 'Lich su don cua khach - Admin')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Fira+Code:wght@500;600;700&family=Fira+Sans:wght@400;500;600;700;800&display=swap');

    :root {
        --customer-history-bg: #f8fafc;
        --customer-history-panel: rgba(255, 255, 255, 0.94);
        --customer-history-border: rgba(148, 163, 184, 0.18);
        --customer-history-text: #0f172a;
        --customer-history-muted: #64748b;
        --customer-history-primary: #0284c7;
        --customer-history-shadow: 0 26px 60px rgba(15, 23, 42, 0.08);
    }

    body {
        background:
            radial-gradient(circle at top left, rgba(2, 132, 199, 0.12), transparent 24%),
            radial-gradient(circle at bottom right, rgba(234, 88, 12, 0.08), transparent 18%),
            var(--customer-history-bg);
        color: var(--customer-history-text);
        font-family: 'Fira Sans', sans-serif;
    }

    .customer-history-page {
        min-height: calc(100vh - 7rem);
    }

    .customer-history-shell {
        padding: 30px;
        border-radius: 32px;
        background: rgba(255, 255, 255, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(20px);
        box-shadow: var(--customer-history-shadow);
    }

    .customer-history-topbar,
    .customer-history-toolbar,
    .customer-history-panel__head {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
    }

    .customer-history-topbar {
        margin-bottom: 20px;
    }

    .customer-history-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        color: var(--customer-history-primary);
        font-size: 0.82rem;
        font-weight: 700;
        text-decoration: none;
    }

    .customer-history-kicker {
        margin: 0 0 6px;
        color: var(--customer-history-muted);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .customer-history-title {
        margin: 0;
        font-family: 'Fira Code', monospace;
        font-size: clamp(1.8rem, 2vw, 2.45rem);
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .customer-history-subtitle {
        margin: 10px 0 0;
        max-width: 720px;
        color: var(--customer-history-muted);
        font-size: 0.95rem;
        line-height: 1.6;
    }

    .customer-history-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0 16px;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        background: #fff;
        color: var(--customer-history-text);
        font-size: 0.84rem;
        font-weight: 700;
        text-decoration: none;
    }

    .customer-history-action--primary {
        background: linear-gradient(135deg, #0284c7, #38bdf8);
        border-color: transparent;
        color: #fff;
    }

    .customer-history-stats {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .customer-history-stat,
    .customer-history-panel {
        background: var(--customer-history-panel);
        border: 1px solid var(--customer-history-border);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.06);
    }

    .customer-history-stat {
        padding: 18px;
        border-radius: 22px;
    }

    .customer-history-stat__label {
        display: block;
        color: var(--customer-history-muted);
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .customer-history-stat__value {
        display: block;
        margin-top: 10px;
        font-size: 1.75rem;
        line-height: 1;
        font-weight: 800;
    }

    .customer-history-stat__meta {
        display: block;
        margin-top: 8px;
        color: var(--customer-history-muted);
        font-size: 0.82rem;
    }

    .customer-history-toolbar {
        margin-bottom: 18px;
        padding: 16px;
        border-radius: 24px;
        background: var(--customer-history-panel);
        border: 1px solid var(--customer-history-border);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.05);
        align-items: center;
        flex-wrap: wrap;
    }

    .customer-history-toolbar-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        flex: 1 1 640px;
    }

    .customer-history-input,
    .customer-history-select {
        min-height: 44px;
        padding: 0 14px;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: #fff;
        color: var(--customer-history-text);
        font-size: 0.9rem;
        outline: none;
        box-shadow: none;
    }

    .customer-history-input {
        min-width: 220px;
        flex: 1 1 220px;
    }

    .customer-history-select {
        min-width: 154px;
        flex: 0 0 154px;
    }

    .customer-history-main {
        display: grid;
        grid-template-columns: minmax(0, 1.3fr) minmax(320px, 0.85fr);
        gap: 18px;
    }

    .customer-history-panel {
        border-radius: 28px;
        overflow: hidden;
    }

    .customer-history-panel__head {
        padding: 18px 20px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
    }

    .customer-history-panel__title {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
    }

    .customer-history-panel__copy {
        margin: 6px 0 0;
        color: var(--customer-history-muted);
        font-size: 0.84rem;
    }

    .customer-history-table-wrap {
        overflow: auto;
    }

    .customer-history-table {
        width: 100%;
        border-collapse: collapse;
    }

    .customer-history-table th {
        padding: 14px 20px;
        background: rgba(248, 250, 252, 0.92);
        color: var(--customer-history-muted);
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        white-space: nowrap;
    }

    .customer-history-table td {
        padding: 16px 20px;
        border-top: 1px solid rgba(148, 163, 184, 0.12);
        vertical-align: top;
    }

    .customer-history-row {
        cursor: pointer;
        transition: background 0.18s ease;
    }

    .customer-history-row:hover,
    .customer-history-row.is-selected {
        background: rgba(2, 132, 199, 0.04);
    }

    .customer-history-code {
        color: var(--customer-history-primary);
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .customer-history-name {
        margin-top: 5px;
        font-weight: 800;
        line-height: 1.45;
    }

    .customer-history-subcopy {
        margin-top: 4px;
        color: var(--customer-history-muted);
        font-size: 0.83rem;
        line-height: 1.5;
    }

    .customer-history-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 30px;
        padding: 0 12px;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .customer-history-pill--info { background: rgba(2, 132, 199, 0.1); color: #0284c7; }
    .customer-history-pill--success { background: rgba(5, 150, 105, 0.12); color: #059669; }
    .customer-history-pill--warning { background: rgba(234, 88, 12, 0.12); color: #ea580c; }
    .customer-history-pill--danger { background: rgba(220, 38, 38, 0.1); color: #dc2626; }
    .customer-history-pill--muted { background: rgba(148, 163, 184, 0.12); color: #64748b; }

    .customer-history-preview {
        padding: 20px;
        display: grid;
        gap: 12px;
    }

    .customer-history-preview-block,
    .customer-history-empty {
        border-radius: 20px;
        background: rgba(248, 250, 252, 0.9);
        border: 1px solid rgba(148, 163, 184, 0.12);
    }

    .customer-history-preview-block {
        padding: 16px;
    }

    .customer-history-preview-label {
        display: block;
        margin-bottom: 8px;
        color: var(--customer-history-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .customer-history-preview-value {
        color: var(--customer-history-text);
        font-size: 0.94rem;
        font-weight: 700;
        line-height: 1.6;
    }

    .customer-history-preview-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .customer-history-empty {
        padding: 42px 24px;
        color: var(--customer-history-muted);
        text-align: center;
        line-height: 1.7;
    }

    @media (max-width: 1199.98px) {
        .customer-history-stats {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .customer-history-main {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .customer-history-shell {
            padding: 20px;
            border-radius: 24px;
        }

        .customer-history-topbar {
            flex-direction: column;
        }

        .customer-history-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .customer-history-preview-actions {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<section class="customer-history-page py-4">
    <div class="container-xxl">
        <div class="customer-history-shell" id="customerHistoryApp" data-customer-id="{{ $id }}">
            <div class="customer-history-topbar">
                <div>
                    <a class="customer-history-back" href="{{ route('admin.customers.show', ['id' => $id]) }}">
                        <span>&larr;</span>
                        <span>Ho so khach hang</span>
                    </a>
                    <p class="customer-history-kicker">Khach hang / Lich su don</p>
                    <h1 class="customer-history-title" id="customerHistoryTitle">Dang tai lich su don...</h1>
                    <p class="customer-history-subtitle" id="customerHistorySubtitle">
                        Tra cuu toan bo don cua mot khach, loc nhanh theo trang thai, thanh toan va hinh thuc sua.
                    </p>
                </div>
                <div class="customer-history-toolbar-group" style="justify-content:flex-end; flex:0 0 auto;">
                    <a class="customer-history-action" id="customerHistoryDetailLink" href="#">Ho so khach hang</a>
                    <button type="button" class="customer-history-action customer-history-action--primary" id="customerHistoryRefreshButton">Lam moi</button>
                </div>
            </div>

            <div class="customer-history-stats" id="customerHistoryStats"></div>

            <div class="customer-history-toolbar">
                <div class="customer-history-toolbar-group">
                    <input type="search" class="customer-history-input" id="customerHistorySearch" placeholder="Tim theo ma don, dich vu, tho, dia chi...">
                    <select class="customer-history-select" id="customerHistoryStatus">
                        <option value="">Tat ca trang thai</option>
                        <option value="cho_xac_nhan">Cho xac nhan</option>
                        <option value="da_xac_nhan">Da xac nhan</option>
                        <option value="dang_lam">Dang lam</option>
                        <option value="cho_hoan_thanh">Cho nghiem thu</option>
                        <option value="cho_thanh_toan">Cho thanh toan</option>
                        <option value="da_xong">Hoan thanh</option>
                        <option value="da_huy">Da huy</option>
                    </select>
                    <select class="customer-history-select" id="customerHistoryPayment">
                        <option value="">Thanh toan</option>
                        <option value="paid">Da thanh toan</option>
                        <option value="unpaid">Chua thanh toan</option>
                    </select>
                    <select class="customer-history-select" id="customerHistoryMode">
                        <option value="">Hinh thuc sua</option>
                        <option value="at_home">Sua tai nha</option>
                        <option value="at_store">Tai cua hang</option>
                    </select>
                    <select class="customer-history-select" id="customerHistoryService">
                        <option value="">Tat ca dich vu</option>
                    </select>
                    <select class="customer-history-select" id="customerHistoryWorker">
                        <option value="">Tat ca tho</option>
                    </select>
                    <input type="date" class="customer-history-select" id="customerHistoryDateFrom">
                    <input type="date" class="customer-history-select" id="customerHistoryDateTo">
                    <input type="number" class="customer-history-select" id="customerHistoryAmountMin" placeholder="Tien tu..." min="0" step="1000">
                </div>
            </div>

            <div class="customer-history-main">
                <section class="customer-history-panel">
                    <div class="customer-history-panel__head">
                        <div>
                            <h2 class="customer-history-panel__title">Danh sach don</h2>
                            <p class="customer-history-panel__copy" id="customerHistoryCaption">Dang tai du lieu...</p>
                        </div>
                    </div>
                    <div class="customer-history-table-wrap">
                        <table class="customer-history-table">
                            <thead>
                                <tr>
                                    <th>Don hang</th>
                                    <th>Lich hen</th>
                                    <th>Tho</th>
                                    <th>Trang thai</th>
                                    <th>Thanh toan</th>
                                    <th>Tong tien</th>
                                </tr>
                            </thead>
                            <tbody id="customerHistoryTableBody"></tbody>
                        </table>
                    </div>
                </section>

                <aside class="customer-history-panel">
                    <div class="customer-history-panel__head">
                        <div>
                            <h2 class="customer-history-panel__title">Xem nhanh</h2>
                            <p class="customer-history-panel__copy">Chon mot don de xem nhanh thong tin chi tiet.</p>
                        </div>
                    </div>
                    <div class="customer-history-preview" id="customerHistoryPreview"></div>
                </aside>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/customer-bookings.js') }}"></script>
@endpush
