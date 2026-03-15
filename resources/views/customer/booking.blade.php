@extends('layouts.app')

@section('title', 'Đặt lịch sửa chữa')

@section('content')
<app-navbar></app-navbar>

<div style="min-height: calc(100vh - 80px); background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);"></div>

@include('customer.partials.booking-wizard-modal')
@endsection
