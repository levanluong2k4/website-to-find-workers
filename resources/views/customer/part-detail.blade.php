@extends('layouts.app')

@section('title', 'Chi tiết linh kiện - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('assets/css/customer/part-detail.css') }}?v={{ time() }}">
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="part-detail-page" data-part-id="{{ $id }}">
    <section class="part-detail-shell" id="partDetailLoading">
        <div class="part-detail-state">
            <div class="part-detail-spinner"></div>
            <h2>Đang tải chi tiết linh kiện</h2>
            <p>Hệ thống đang chuẩn bị thông tin giá và nhóm dịch vụ liên quan.</p>
        </div>
    </section>

    <section class="part-detail-shell" id="partDetailError" hidden></section>
    <div id="partDetailContent" hidden></div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/customer/part-detail.js') }}?v={{ time() }}"></script>
@endpush
