@extends('layouts.app')

@section('title', 'ASSISTANT SOUL - Admin')

@push('styles')
<style>
    body {
        background: #f8fafc;
    }

    .editor-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }

    .section-card {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .section-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.35rem;
    }

    .section-desc {
        font-size: 0.8rem;
        color: #64748b;
        margin-bottom: 1rem;
    }

    .form-control,
    .form-select {
        border-radius: 12px;
        border-color: #cbd5e1;
    }

    textarea.form-control {
        min-height: 140px;
        font-family: Consolas, monospace;
        font-size: 0.92rem;
    }

    .meta-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.75rem;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 0.8rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container py-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="/admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">ASSISTANT SOUL</li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-1" style="color:#0f172a;">Quản lý ASSISTANT SOUL</h2>
            <p class="text-muted mb-0">Chỉnh trực tiếp rule, từ khóa khẩn cấp và mẫu phản hồi của chatbot mà không cần sửa code.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="meta-chip" id="assistantSoulStatus">Đang tải cấu hình...</span>
            <span class="meta-chip d-none" id="assistantSoulUpdatedMeta"></span>
        </div>
    </div>

    <div class="editor-card p-4 p-lg-5">
        <form id="assistantSoulForm" class="d-grid gap-4">
            <div class="section-card p-4">
                <div class="section-title">Nhận diện trợ lý</div>
                <div class="section-desc">Tên, vai trò chính và giọng văn tổng quát.</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="assistantSoulName" class="form-label fw-semibold">Tên rule</label>
                        <input type="text" class="form-control" id="assistantSoulName" maxlength="255" required>
                    </div>
                    <div class="col-md-8">
                        <label for="assistantSoulRole" class="form-label fw-semibold">Vai trò</label>
                        <textarea class="form-control" id="assistantSoulRole" rows="4" required></textarea>
                    </div>
                    <div class="col-12">
                        <label for="assistantSoulOutputStyle" class="form-label fw-semibold">Phong cách đầu ra</label>
                        <textarea class="form-control" id="assistantSoulOutputStyle" rows="3" required></textarea>
                    </div>
                </div>
            </div>

            <div class="section-card p-4">
                <div class="section-title">Cách xưng hô và ứng xử</div>
                <div class="section-desc">Mỗi dòng là một rule.</div>
                <label for="assistantSoulIdentityRules" class="form-label fw-semibold">Identity rules</label>
                <textarea class="form-control" id="assistantSoulIdentityRules" required></textarea>
            </div>

            <div class="section-card p-4">
                <div class="section-title">Quy tắc bắt buộc</div>
                <div class="section-desc">Mỗi dòng là một quy tắc chatbot phải tuân thủ.</div>
                <label for="assistantSoulRequiredRules" class="form-label fw-semibold">Required rules</label>
                <textarea class="form-control" id="assistantSoulRequiredRules" required></textarea>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="section-card p-4 h-100">
                        <div class="section-title">Mục tiêu trả lời</div>
                        <div class="section-desc">Mỗi dòng là một mục tiêu.</div>
                        <label for="assistantSoulResponseGoals" class="form-label fw-semibold">Response goals</label>
                        <textarea class="form-control" id="assistantSoulResponseGoals" required></textarea>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="section-card p-4 h-100">
                        <div class="section-title">Thứ tự nội dung trả lời</div>
                        <div class="section-desc">Mỗi dòng là một mục theo thứ tự ưu tiên.</div>
                        <label for="assistantSoulTextOrder" class="form-label fw-semibold">assistant_text order</label>
                        <textarea class="form-control" id="assistantSoulTextOrder" required></textarea>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="section-card p-4 h-100">
                        <div class="section-title">Quy trình dịch vụ</div>
                        <div class="section-desc">Hiển thị vào prompt để AI nói đúng quy trình tiếp nhận.</div>
                        <label for="assistantSoulServiceProcess" class="form-label fw-semibold">Service process</label>
                        <textarea class="form-control" id="assistantSoulServiceProcess" required></textarea>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="section-card p-4 h-100">
                        <div class="section-title">JSON keys</div>
                        <div class="section-desc">Mỗi dòng là một key bắt buộc của phản hồi model.</div>
                        <label for="assistantSoulJsonKeys" class="form-label fw-semibold">JSON keys</label>
                        <textarea class="form-control" id="assistantSoulJsonKeys" required></textarea>
                    </div>
                </div>
            </div>

            <div class="section-card p-4">
                <div class="section-title">Khẩn cấp</div>
                <div class="section-desc">Từ khóa sẽ kích hoạt cảnh báo an toàn ngay, không đi qua suy luận thông thường.</div>
                <div class="row g-3">
                    <div class="col-lg-4">
                        <label for="assistantSoulEmergencyKeywords" class="form-label fw-semibold">Emergency keywords</label>
                        <textarea class="form-control" id="assistantSoulEmergencyKeywords" required></textarea>
                    </div>
                    <div class="col-lg-8">
                        <label for="assistantSoulEmergencyLines" class="form-label fw-semibold">Emergency response lines</label>
                        <textarea class="form-control" id="assistantSoulEmergencyLines" required></textarea>
                    </div>
                    <div class="col-lg-6">
                        <label for="assistantSoulFallbackPriceLine" class="form-label fw-semibold">Fallback price line</label>
                        <textarea class="form-control" id="assistantSoulFallbackPriceLine" rows="3" required></textarea>
                    </div>
                    <div class="col-lg-6">
                        <label for="assistantSoulPriceLineTemplate" class="form-label fw-semibold">Price line template</label>
                        <textarea class="form-control" id="assistantSoulPriceLineTemplate" rows="3" required></textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 pt-2">
                <button type="button" class="btn btn-outline-danger px-4 py-2 fw-semibold" id="btnResetAssistantSoul">
                    Khôi phục mặc định
                </button>
                <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold" id="btnSaveAssistantSoul">
                    Lưu cấu hình
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/admin/assistant-soul.js') }}"></script>
@endpush
