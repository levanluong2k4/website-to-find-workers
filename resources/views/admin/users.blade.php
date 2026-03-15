@extends('layouts.app')

@section('title', 'Qu&#7843;n l&#253; ng&#432;&#7901;i d&#249;ng - Th&#7907; T&#7889;t')

@push('styles')
<style>
    body {
        background-color: #f8fafc;
    }

    .table-custom {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .table-custom th {
        background-color: #f1f5f9;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 1rem;
    }

    .table-custom td {
        padding: 1rem;
        vertical-align: top;
        border-bottom: 1px solid #f1f5f9;
        color: #0f172a;
    }

    .chip {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.7rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        margin: 0 0.35rem 0.35rem 0;
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="/admin/dashboard" class="text-decoration-none">B&#7843;ng &#273;i&#7873;u khi&#7875;n</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Ng&#432;&#7901;i d&#249;ng</li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-1" style="color:#0f172a;">Qu&#7843;n l&#253; c&#7897;ng &#273;&#7891;ng v&#224; duy&#7879;t h&#7891; s&#417; th&#7907;</h2>
            <p class="text-muted mb-0">Kh&#243;a/m&#7903; kh&#243;a t&#224;i kho&#7843;n v&#224; duy&#7879;t h&#7891; s&#417; &#273;&#7889;i t&#225;c th&#7907; ngay t&#7841;i m&#7897;t m&#224;n h&#236;nh.</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <select class="form-select shadow-sm" id="roleFilter" style="min-width: 160px;">
                <option value="">T&#7845;t c&#7843; vai tr&#242;</option>
                <option value="customer">Kh&#225;ch h&#224;ng</option>
                <option value="worker">Th&#7907;</option>
            </select>
            <select class="form-select shadow-sm" id="approvalFilter" style="min-width: 180px;">
                <option value="">T&#7845;t c&#7843; duy&#7879;t h&#7891; s&#417;</option>
                <option value="cho_duyet">Ch&#7901; duy&#7879;t</option>
                <option value="da_duyet">&#272;&#227; duy&#7879;t</option>
                <option value="tu_choi">T&#7915; ch&#7889;i</option>
            </select>
            <button class="btn btn-outline-primary shadow-sm" id="btnRefresh">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <div class="table-responsive table-custom">
        <table class="table mb-0 table-borderless">
            <thead>
                <tr>
                    <th class="ps-4">UID</th>
                    <th>Th&#244;ng tin</th>
                    <th>Vai tr&#242;</th>
                    <th>H&#7891; s&#417; th&#7907;</th>
                    <th>Tr&#7841;ng th&#225;i TK</th>
                    <th class="text-end pe-4">Thao t&#225;c</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 mb-0">&#272;ang t&#7843;i danh s&#225;ch ng&#432;&#7901;i d&#249;ng...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/users.js') }}"></script>
@endpush
