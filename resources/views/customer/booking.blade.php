@extends('layouts.app')

@section('title', 'Đặt lịch sửa chữa')

@section('content')
<app-navbar></app-navbar>

<div style="min-height: calc(100vh - 80px); background: radial-gradient(circle at top left, rgba(255, 255, 255, 0.68) 0, rgba(255, 255, 255, 0) 24rem), radial-gradient(circle at top right, rgba(255, 255, 255, 0.58) 0, rgba(255, 255, 255, 0) 18rem), linear-gradient(180deg, #8ad0ff 0%, #c7e8ff 36%, #edf7ff 100%);"></div>

@include('customer.partials.booking-wizard-modal')
@endsection
