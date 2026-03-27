@extends('layouts.app')

@section('title', 'Trung tâm điều phối - Thợ Tốt NTU')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .glass-panel {
            background: rgba(14, 165, 233, 0.8);
            backdrop-filter: blur(12px);
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .orchestrator-pulse {
            position: relative;
        }
        .orchestrator-pulse::after {
            content: '';
            position: absolute;
            width: 6px;
            height: 6px;
            background: #6d3bd7;
            border-radius: 50%;
            top: 0;
            right: -8px;
            box-shadow: 0 0 0 rgba(109, 59, 215, 0.4);
            animation: pulse-ring 2s infinite;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.8); box-shadow: 0 0 0 0px rgba(109, 59, 215, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(109, 59, 215, 0); }
            100% { transform: scale(0.8); box-shadow: 0 0 0 0px rgba(109, 59, 215, 0); }
        }
        body {
            background-color: #f7fafc;
        }
    </style>
@endpush

@section('content')
<app-navbar></app-navbar>

<div class="container-fluid" style="padding-top: 8rem; padding-bottom: 3rem;">
<div class="px-4 min-w-[1200px]">
    <!-- Dashboard Header Section -->
    <div class="tw-flex tw-items-end tw-justify-between tw-mb-8">
        <div>
            <h2 class="tw-text-4xl tw-font-bold tw-text-[#181c1e] tw-tracking-tight tw-mb-2" style="font-size: 2rem;">Trung tâm điều phối</h2>
            <p class="tw-text-sm tw-text-[#6e7881] orchestrator-pulse tw-inline-block">Trực tiếp: 14 kỹ thuật viên đang hoạt động</p>
        </div>
        <div class="tw-flex tw-space-x-4">
            <div class="tw-bg-[#f1f4f6] tw-p-1 tw-rounded-xl tw-flex">
                <button class="tw-px-4 tw-py-2 tw-bg-white tw-shadow-sm tw-rounded-lg tw-text-sm tw-font-semibold tw-text-[#181c1e] tw-border-0 tw-outline-none">Tất cả thợ</button>
                <button class="tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-text-[#6e7881] tw-border-0 tw-bg-transparent tw-outline-none">Khu vực Quận 1</button>
                <button class="tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-text-[#6e7881] tw-border-0 tw-bg-transparent tw-outline-none">Khu vực Quận 7</button>
            </div>
        </div>
    </div>

    <!-- Kanban Grid -->
    <div class="tw-grid tw-grid-cols-4 tw-gap-6 tw-items-start font-body">
        
        <!-- COLUMN: Sắp Tới -->
        <div class="tw-flex tw-flex-col tw-space-y-4">
            <div class="tw-flex tw-items-center tw-justify-between tw-px-2 tw-mb-2">
                <h3 class="tw-text-lg tw-font-bold tw-text-[#181c1e] tw-flex tw-items-center tw-space-x-2 m-0 p-0">
                    <span>Sắp Tới</span>
                    <span class="tw-text-xs tw-bg-[#e0e3e5] tw-px-2 tw-py-0.5 tw-rounded-full">04</span>
                </h3>
                <span class="material-symbols-outlined tw-text-[#6e7881] tw-cursor-pointer">more_horiz</span>
            </div>
            
            <div class="tw-bg-[#f1f4f6] tw-p-4 tw-rounded-xl tw-space-y-4">
                <!-- Card 1 -->
                <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm tw-border-l-4 tw-border-[#bec8d2]/30 hover:tw-shadow-md tw-transition-shadow tw-cursor-grab">
                    <div class="tw-flex tw-justify-between tw-items-start tw-mb-3">
                        <span class="tw-text-xs tw-px-2 tw-py-0.5 tw-bg-[#e5e9eb] tw-text-[#3e4850] tw-rounded font-mono">#JOB-8821</span>
                        <span class="tw-text-sm tw-font-medium tw-text-[#006591]">09:30 AM</span>
                    </div>
                    <h4 class="tw-text-base tw-font-bold tw-text-[#181c1e] tw-mb-1 m-0 p-0" style="font-size: 1rem;">Trần Anh Tuấn</h4>
                    <p class="tw-text-sm tw-text-[#6e7881] tw-mb-3 m-0 p-0 leading-snug">241 Bis Cách Mạng Tháng 8, P.4, Q.3</p>
                    <div class="tw-flex tw-items-center tw-space-x-2 m-0 p-0">
                        <span class="material-symbols-outlined tw-text-sm tw-text-[#6e7881]">build</span>
                        <span class="tw-text-sm tw-font-medium tw-text-[#181c1e]">Sửa máy lạnh Inverter</span>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm tw-border-l-4 tw-border-[#bec8d2]/30 hover:tw-shadow-md tw-transition-shadow tw-cursor-grab">
                    <div class="tw-flex tw-justify-between tw-items-start tw-mb-3">
                        <span class="tw-text-xs tw-px-2 tw-py-0.5 tw-bg-[#e5e9eb] tw-text-[#3e4850] tw-rounded font-mono">#JOB-8824</span>
                        <span class="tw-text-sm tw-font-medium tw-text-[#006591]">10:45 AM</span>
                    </div>
                    <h4 class="tw-text-base tw-font-bold tw-text-[#181c1e] tw-mb-1 m-0 p-0" style="font-size: 1rem;">Lê Minh Hoàng</h4>
                    <p class="tw-text-sm tw-text-[#6e7881] tw-mb-3 m-0 p-0 leading-snug">12 Nguyễn Hữu Cảnh, P.22, Bình Thạnh</p>
                    <div class="tw-flex tw-items-center tw-space-x-2 m-0 p-0">
                        <span class="material-symbols-outlined tw-text-sm tw-text-[#6e7881]">electric_bolt</span>
                        <span class="tw-text-sm tw-font-medium tw-text-[#181c1e]">Xử lý chập điện</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMN: Đang Sửa Tận Nơi -->
        <div class="tw-flex tw-flex-col tw-space-y-4">
            <div class="tw-flex tw-items-center tw-justify-between tw-px-2 tw-mb-2">
                <h3 class="tw-text-lg tw-font-bold tw-text-[#181c1e] tw-flex tw-items-center tw-space-x-2 m-0 p-0">
                    <span>Đang Sửa</span>
                    <span class="tw-text-xs tw-bg-[#ffc329] tw-px-2 tw-py-0.5 tw-rounded-full tw-text-[#6f5100]">02</span>
                </h3>
                <span class="material-symbols-outlined tw-text-[#6e7881] tw-cursor-pointer">more_horiz</span>
            </div>
            <div class="tw-bg-[#f1f4f6] tw-p-4 tw-rounded-xl tw-space-y-4">
                <!-- Card Active Repair -->
                <div class="tw-bg-white tw-rounded-lg tw-overflow-hidden tw-shadow-lg tw-border-t-4 tw-border-[#ffc329] tw-transform tw-scale-[1.02] tw-ring-2 tw-ring-[#006591]/5">
                    <div class="tw-p-4">
                        <div class="tw-flex tw-justify-between tw-items-start tw-mb-3">
                            <span class="tw-text-xs tw-px-2 tw-py-0.5 tw-bg-[#ffc329] tw-text-[#6f5100] tw-rounded tw-font-bold">TRỰC TIẾP</span>
                            <div class="tw-px-2 tw-py-0.5 tw-bg-[#89ceff] tw-text-[#001e2f] tw-rounded tw-font-mono tw-text-[10px] tw-font-bold">01:24:18</div>
                        </div>
                        <h4 class="tw-text-base tw-font-bold tw-text-[#181c1e] tw-mb-1 m-0 p-0" style="font-size: 1rem;">Nguyễn Thị Mai</h4>
                        <p class="tw-text-sm tw-text-[#6e7881] tw-mb-4 m-0 p-0 leading-snug">Landmark 81, P.22, Bình Thạnh, TP.HCM</p>
                        
                        <div class="tw-bg-[#f1f4f6] tw-rounded tw-p-3 tw-mb-4">
                            <div class="tw-text-[10px] tw-uppercase tw-font-bold tw-text-[#bec8d2] tw-mb-2 tw-tracking-wider">Kiểm tra linh kiện</div>
                            <div class="tw-space-y-2">
                                <div class="tw-flex tw-items-center tw-space-x-2">
                                    <span class="material-symbols-outlined tw-text-[#006591] tw-text-sm" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                    <span class="tw-text-sm tw-line-through tw-text-[#6e7881]">Kiểm tra nguồn cấp</span>
                                </div>
                                <div class="tw-flex tw-items-center tw-space-x-2">
                                    <span class="material-symbols-outlined tw-text-[#bec8d2] tw-text-sm">radio_button_unchecked</span>
                                    <span class="tw-text-sm tw-text-[#181c1e] tw-font-medium">Thay thế tụ điện 45uF</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tw-flex tw-items-center tw-justify-between tw-pt-2 tw-border-t tw-border-[#bec8d2]/10">
                            <div class="tw-flex tw--space-x-2">
                                <img class="tw-w-6 tw-h-6 tw-rounded-full tw-border-2 tw-border-white" data-alt="Worker profile photo headshot" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBozZgmlt7DBLnXtRtzqiovlgXpPueF1ExU95RLtcIw7AcuxdxPtIbTV7ZoSLaqm1f_eeexEmmwbLsIHA36JYQTnG1N_iBHv_qhTPlGt6NKAJEIjHcmyjImlb1CqDKahNCLBDY8_qL3lDFzdekDQWeT29WVgp2SEZ3Az77_L5quRukw7DXC1-LzX90Xkl8EwCRVjCnt6CQ00L3mzmwAKn1yYXLCCtL1bDhLVr4mFWonEN8l6dQliAyG9WQsHBz1a-rdEcWvtI7M6s7y"/>
                                <div class="tw-w-6 tw-h-6 tw-rounded-full tw-bg-[#0ea5e9] tw-text-[8px] tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-border-2 tw-border-white">+1</div>
                            </div>
                            <button class="btn btn-link tw-text-xs tw-font-bold tw-text-[#006591] tw-flex tw-items-center tw-p-0 tw-text-decoration-none">
                                CHI TIẾT <span class="material-symbols-outlined tw-text-sm">chevron_right</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMN: Chờ Thanh Toán -->
        <div class="tw-flex tw-flex-col tw-space-y-4">
            <div class="tw-flex tw-items-center tw-justify-between tw-px-2 tw-mb-2">
                <h3 class="tw-text-lg tw-font-bold tw-text-[#181c1e] tw-flex tw-items-center tw-space-x-2 m-0 p-0">
                    <span>Chờ Thanh Toán</span>
                    <span class="tw-text-xs tw-bg-[#a986ff]/30 tw-px-2 tw-py-0.5 tw-rounded-full tw-text-[#3e0097]">01</span>
                </h3>
                <span class="material-symbols-outlined tw-text-[#6e7881] tw-cursor-pointer">more_horiz</span>
            </div>
            <div class="tw-bg-[#f1f4f6] tw-p-4 tw-rounded-xl tw-space-y-4">
                <!-- Card Pending Payment -->
                <div class="tw-bg-white tw-rounded-lg tw-p-0.5 tw-bg-gradient-to-br tw-from-[#a986ff] tw-to-[#ffc329] tw-shadow-md">
                    <div class="tw-bg-white tw-rounded-[calc(0.5rem-2px)] tw-p-4">
                        <div class="tw-flex tw-justify-between tw-items-start tw-mb-3">
                            <span class="tw-text-xs tw-px-2 tw-py-0.5 tw-bg-[#e9ddff] tw-text-[#23005c] tw-rounded tw-font-bold font-mono">#JOB-8799</span>
                            <span class="tw-text-sm tw-font-bold tw-text-[#181c1e]">1,250,000đ</span>
                        </div>
                        <h4 class="tw-text-base tw-font-bold tw-text-[#181c1e] tw-mb-1 m-0 p-0" style="font-size: 1rem;">Phạm Gia Bảo</h4>
                        <p class="tw-text-sm tw-text-[#6e7881] tw-mb-3 m-0 p-0 leading-snug">78 Lê Lợi, P. Bến Thành, Q.1</p>
                        
                        <div class="tw-flex tw-items-center tw-justify-between">
                            <div class="tw-flex tw-items-center tw-space-x-1 tw-text-[#6d3bd7] tw-font-bold">
                                <span class="material-symbols-outlined tw-text-sm">payments</span>
                                <span class="tw-text-[10px] tw-uppercase">Chờ xác nhận</span>
                            </div>
                            <button class="btn btn-light bg-[#e5e9eb] hover:bg-[#e0e3e5] tw-p-2 tw-rounded-lg tw-transition-colors tw-border-0">
                                <span class="material-symbols-outlined tw-text-sm m-0 p-0 tw-leading-none" style="display:flex;">receipt_long</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMN: Đã Hoàn Thành -->
        <div class="tw-flex tw-flex-col tw-space-y-4">
            <div class="tw-flex tw-items-center tw-justify-between tw-px-2 tw-mb-2">
                <h3 class="tw-text-lg tw-font-bold tw-text-[#181c1e] tw-flex tw-items-center tw-space-x-2 m-0 p-0">
                    <span>Hoàn Thành</span>
                    <span class="tw-text-xs tw-bg-[#e0e3e5] tw-px-2 tw-py-0.5 tw-rounded-full">08</span>
                </h3>
                <span class="material-symbols-outlined tw-text-[#6e7881] tw-cursor-pointer">more_horiz</span>
            </div>
            <div class="tw-bg-[#f1f4f6] tw-p-4 tw-rounded-xl tw-space-y-4 tw-opacity-70">
                <!-- Card Done 1 -->
                <div class="tw-bg-white/80 tw-rounded-lg tw-p-4 tw-shadow-sm tw-border-l-4 tw-border-[#006591]/20 tw-grayscale-[0.5]">
                    <div class="tw-flex tw-justify-between tw-items-start tw-mb-3">
                        <span class="tw-text-xs tw-px-2 tw-py-0.5 tw-bg-[#e5e9eb] tw-text-[#6e7881] tw-rounded font-mono">#JOB-8790</span>
                        <span class="material-symbols-outlined tw-text-[#006591]" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    </div>
                    <h4 class="tw-text-base tw-font-bold tw-text-[#6e7881] tw-mb-1 m-0 p-0" style="font-size: 1rem;">Bùi Thế Hiển</h4>
                    <p class="tw-text-sm tw-text-[#6e7881]/60 tw-mb-3 m-0 p-0 leading-snug">KDC Trung Sơn, Bình Chánh</p>
                    <div class="tw-text-[10px] tw-text-[#6e7881] tw-italic">Hoàn thành lúc 08:15 AM</div>
                </div>
                
                <!-- Card Done 2 -->
                <div class="tw-bg-white/80 tw-rounded-lg tw-p-4 tw-shadow-sm tw-border-l-4 tw-border-[#006591]/20 tw-grayscale-[0.5]">
                    <div class="tw-flex tw-justify-between tw-items-start tw-mb-3">
                        <span class="tw-text-xs tw-px-2 tw-py-0.5 tw-bg-[#e5e9eb] tw-text-[#6e7881] tw-rounded font-mono">#JOB-8785</span>
                        <span class="material-symbols-outlined tw-text-[#006591]" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    </div>
                    <h4 class="tw-text-base tw-font-bold tw-text-[#6e7881] tw-mb-1 m-0 p-0" style="font-size: 1rem;">Võ Thành Nam</h4>
                    <p class="tw-text-sm tw-text-[#6e7881]/60 tw-mb-3 m-0 p-0 leading-snug">Chung cư Estella, Q.2</p>
                    <div class="tw-text-[10px] tw-text-[#6e7881] tw-italic">Hoàn thành lúc 07:30 AM</div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Floating Glass Dashboard Detail -->
<div class="tw-fixed tw-bottom-8 tw-right-8 tw-w-80 glass-panel tw-p-6 tw-rounded-2xl tw-shadow-2xl tw-border tw-border-white/20 tw-text-white z-50">
    <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
        <h5 class="tw-text-sm tw-font-bold tw-uppercase tw-tracking-widest m-0 p-0" style="font-size: 0.875rem;">Hiệu suất đội ngũ</h5>
        <span class="material-symbols-outlined">analytics</span>
    </div>
    <div class="tw-space-y-4">
        <div>
            <div class="tw-flex tw-justify-between tw-text-[10px] tw-mb-1">
                <span>TIẾN ĐỘ CÔNG VIỆC</span>
                <span>82%</span>
            </div>
            <div class="tw-w-full tw-bg-white/20 tw-h-1.5 tw-rounded-full tw-overflow-hidden">
                <div class="tw-bg-white tw-h-full tw-w-[82%] tw-rounded-full tw-shadow-[0_0_10px_#fff]"></div>
            </div>
        </div>
        <div class="tw-grid tw-grid-cols-2 tw-gap-4 tw-pt-2">
            <div class="tw-bg-white/10 tw-p-3 tw-rounded-xl tw-border tw-border-white/10">
                <div class="tw-text-white/60 tw-text-[10px] tw-uppercase tw-font-bold tw-mb-1">Hoàn tất</div>
                <div class="tw-text-xl tw-font-black m-0 p-0 leading-none">24</div>
            </div>
            <div class="tw-bg-white/10 tw-p-3 tw-rounded-xl tw-border tw-border-white/10">
                <div class="tw-text-white/60 tw-text-[10px] tw-uppercase tw-font-bold tw-mb-1">Doanh thu</div>
                <div class="tw-text-xl tw-font-black m-0 p-0 leading-none">12.5M</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            prefix: 'tw-',
            corePlugins: {
                preflight: false,
            }
        }
    </script>
@endpush
