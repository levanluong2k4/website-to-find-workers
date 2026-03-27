<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonDatLich\UpdateBookingCostsRequest;
use App\Http\Requests\DonDatLich\UpdateTrangThaiRequest;
use App\Models\DonDatLich;
use App\Models\LinhKien;
use App\Models\User;
use App\Notifications\BookingStatusNotification;
use App\Notifications\NewBookingNotification;
use App\Services\TravelFeeConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DonDatLichController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = DonDatLich::with([
            'khachHang:id,name,avatar,phone',
            'tho:id,name,avatar,phone',
            'dichVus:id,ten_dich_vu,hinh_anh',
            'danhGias',
        ]);

        if ($this->canActAsCustomer($user) && !$this->isAdmin($user)) {
            $query->where('khach_hang_id', $user->id);
        } elseif ($this->canActAsWorker($user) && !$this->isAdmin($user)) {
            $query->where('tho_id', $user->id);
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function store(
        \App\Http\Requests\DonDatLich\StoreDonDatLichRequest $request,
        TravelFeeConfigService $travelFeeConfigService
    )
    {
        $user = $request->user();
        if (!$this->canActAsCustomer($user)) {
            return response()->json(['message' => 'Chi khach hang moi co quyen dat lich'], 403);
        }

        try {
            \Illuminate\Support\Facades\Log::info('Booking store started', ['request' => $request->all()]);
            $validated = $request->validated();
            \Illuminate\Support\Facades\Log::info('Validation passed', ['validated' => $validated]);
            $serviceIds = $this->normalizeValidatedServiceIds($validated);

            $khoangCach = null;
            $phiDiLai = 0;

            if (($validated['loai_dat_lich'] ?? '') === 'at_home') {
                $storeLat = 12.2618;
                $storeLng = 109.1995;

                if (!empty($validated['tho_id'])) {
                    $hoSoTho = \App\Models\HoSoTho::where('user_id', $validated['tho_id'])->first();
                    if ($hoSoTho && $hoSoTho->vi_do && $hoSoTho->kinh_do) {
                        $storeLat = $hoSoTho->vi_do;
                        $storeLng = $hoSoTho->kinh_do;
                        \Illuminate\Support\Facades\Log::info('Using worker coordinates for distance calculation', ['lat' => $storeLat, 'lng' => $storeLng]);
                    }
                }

                $lat = $validated['vi_do'] ?? 0;
                $lng = $validated['kinh_do'] ?? 0;

                $earthRadius = 6371;
                $dLat = deg2rad($lat - $storeLat);
                $dLng = deg2rad($lng - $storeLng);
                $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($storeLat)) * cos(deg2rad($lat)) * sin($dLng / 2) * sin($dLng / 2);
                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                $khoangCach = $earthRadius * $c;

                $maxDistance = 8;
                if (!empty($validated['tho_id']) && isset($hoSoTho) && $hoSoTho) {
                    $maxDistance = min((float) ($hoSoTho->ban_kinh_phuc_vu ?? 8), 8);
                }

                if ($khoangCach > $maxDistance) {
                    return response()->json([
                        'message' => 'Dia chi cua ban qua xa khoang cach phuc vu (>' . $maxDistance . 'km). Khoang cach hien tai: ' . round($khoangCach, 1) . 'km. Vui long chon tho khac hoac mang den cua hang.',
                        'current_distance' => round($khoangCach, 1),
                    ], 400);
                }

                $phiDiLai = $travelFeeConfigService->resolveFee((float) $khoangCach);
            }

            $booking = new DonDatLich();
            $booking->khach_hang_id = $user->id;
            $booking->tho_id = $validated['tho_id'] ?? null;
            $this->hydrateLegacyServiceColumn($booking, $serviceIds);
            $booking->loai_dat_lich = $validated['loai_dat_lich'] ?? 'at_store';
            $booking->ngay_hen = $validated['ngay_hen'] ?? now()->toDateString();
            $booking->khung_gio_hen = $validated['khung_gio_hen'] ?? '08:00-10:00';

            $khungGio = $validated['khung_gio_hen'] ?? '';
            $gioBatDau = '08:00';
            if (str_contains($khungGio, '-')) {
                $parts = explode('-', $khungGio);
                $gioBatDau = trim($parts[0]);
            }

            $booking->thoi_gian_hen = \Illuminate\Support\Carbon::parse(($validated['ngay_hen'] ?? now()->toDateString()) . ' ' . $gioBatDau . ':00');
            $booking->mo_ta_van_de = $validated['mo_ta_van_de'] ?? null;
            $booking->thue_xe_cho = $validated['thue_xe_cho'] ?? false;
            $booking->trang_thai = 'cho_xac_nhan';
            $booking->phuong_thuc_thanh_toan = 'cod';

            if ($booking->tho_id) {
                $booking->thoi_gian_het_han_nhan = \Illuminate\Support\Carbon::now()->addHour();
            }

            if (($validated['loai_dat_lich'] ?? '') === 'at_home') {
                $booking->dia_chi = $validated['dia_chi'] ?? '';
                $booking->vi_do = $validated['vi_do'] ?? 0;
                $booking->kinh_do = $validated['kinh_do'] ?? 0;
                $booking->khoang_cach = round($khoangCach, 2);
                $booking->phi_di_lai = $phiDiLai;
            } else {
                $booking->dia_chi = '2 Duong Nguyen Dinh Chieu, Vinh Tho, Nha Trang, Khanh Hoa';
                $booking->phi_di_lai = 0;
            }

            \Illuminate\Support\Facades\Log::info('Preparing media upload');
            if ($request->hasFile('hinh_anh_mo_ta')) {
                $images = [];
                foreach ($request->file('hinh_anh_mo_ta') as $file) {
                    \Illuminate\Support\Facades\Log::info('Uploading image', ['name' => $file->getClientOriginalName()]);
                    $uploadResult = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::uploadApi()->upload($file->getRealPath(), [
                        'folder' => 'bookings/images',
                    ]);
                    $images[] = $uploadResult['secure_url'];
                }
                $booking->hinh_anh_mo_ta = $images;
            }

            if ($request->hasFile('video_mo_ta')) {
                \Illuminate\Support\Facades\Log::info('Uploading video');
                $uploadResult = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::uploadApi()->upload($request->file('video_mo_ta')->getRealPath(), [
                    'folder' => 'bookings/videos',
                    'resource_type' => 'video',
                ]);
                $booking->video_mo_ta = $uploadResult['secure_url'];
            }

            \Illuminate\Support\Facades\Log::info('Saving booking');
            $booking->save();
            $booking->dichVus()->sync($serviceIds);

            if ($booking->tho_id) {
                $tho = User::find($booking->tho_id);
                if ($tho) {
                    $tho->notify(new NewBookingNotification($booking));
                }
            } else {
                $thoList = $this->getEligibleWorkersForServiceIds($serviceIds);
                Notification::send($thoList, new NewBookingNotification($booking));
            }

            return response()->json([
                'message' => 'Dat lich thanh cong',
                'data' => $booking->load(['khachHang', 'tho', 'dichVus']),
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in DonDatLichController@store', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Co loi xay ra: ' . $e->getMessage(),
                'debug_info' => $e->getFile() . ' L:' . $e->getLine(),
            ], 500);
        }
    }

    public function availableJobs(Request $request)
    {
        $user = $request->user();
        if (!$this->canActAsWorker($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $jobs = DonDatLich::with([
            'khachHang:id,name,avatar,phone',
            'dichVus:id,ten_dich_vu,hinh_anh',
        ])
            ->whereNull('tho_id')
            ->where('trang_thai', 'cho_xac_nhan')
            ->when(
                $user->dichVus()->exists(),
                function ($query) use ($user) {
                    $workerServiceIds = $user->dichVus()->pluck('danh_muc_dich_vu.id')->all();

                    $query->whereHas('dichVus')
                        ->whereDoesntHave('dichVus', function ($serviceQuery) use ($workerServiceIds) {
                            $serviceQuery->whereNotIn('danh_muc_dich_vu.id', $workerServiceIds);
                        });
                },
                function ($query) {
                    $query->whereRaw('1 = 0');
                }
            )
            ->orderByDesc('created_at')
            ->get();

        return response()->json($jobs);
    }

    public function claimJob(Request $request, $id)
    {
        $user = $request->user();
        if (!$this->canActAsWorker($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking = DonDatLich::find($id);
        if (!$booking) {
            return response()->json(['message' => 'Khong tim thay don dat lich'], 404);
        }

        if ($booking->tho_id !== null || $booking->trang_thai !== 'cho_xac_nhan') {
            return response()->json(['message' => 'Don nay da duoc tho khac nhan hoac khong con kha dung'], 400);
        }

        if (!$this->workerSupportsBookingServices($user, $booking)) {
            return response()->json(['message' => 'Ban chi co the nhan don nam trong nhom dich vu minh co the sua'], 400);
        }

        $booking->tho_id = $user->id;
        $booking->trang_thai = 'da_xac_nhan';
        $booking->save();
        $booking->loadMissing(['tho:id,name', 'khachHang:id,name,email', 'dichVus:id,ten_dich_vu']);

        $this->notifyCustomerAboutBookingUpdate(
            $booking,
            'Đơn đặt lịch đã được nhận',
            'Thợ ' . ($user->name ?? 'hệ thống') . ' đã nhận đơn đặt lịch #' . $booking->id . ' của bạn.',
            'booking_claimed'
        );

        return response()->json(['message' => 'Nhan viec thanh cong', 'data' => $booking]);
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $booking = DonDatLich::with([
            'khachHang:id,name,avatar,phone,address',
            'tho:id,name,avatar,phone',
            'tho.hoSoTho:user_id,danh_gia_trung_binh,tong_so_danh_gia',
            'dichVus:id,ten_dich_vu,hinh_anh',
            'danhGias',
            'thanhToans',
        ])->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Khong tim thay don dat lich'], 404);
        }

        $isOwner = $booking->khach_hang_id === $user->id;
        $isAssignedWorker = $booking->tho_id === $user->id;
        $isAvailableJobForWorker = $this->canActAsWorker($user) && $booking->tho_id === null && $booking->trang_thai === 'cho_xac_nhan';

        if (!$isOwner && !$isAssignedWorker && !$isAvailableJobForWorker && !$this->isAdmin($user)) {
            return response()->json(['message' => 'Ban khong co quyen xem don nay'], 403);
        }

        return response()->json($booking);
    }

    public function updateStatus(UpdateTrangThaiRequest $request, string $id)
    {
        $user = $request->user();
        $booking = DonDatLich::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Khong tim thay don dat lich'], 404);
        }

        $validated = $request->validated();
        $newStatus = $validated['trang_thai'];
        $previousStatus = $booking->trang_thai;

        if ($this->isAdmin($user)) {
            $booking->trang_thai = $newStatus;
            if ($newStatus === 'da_huy') {
                $this->applyCancellationReason($booking, $validated['ma_ly_do_huy'] ?? null, $validated['ly_do_huy'] ?? null);
            }
            if ($newStatus === 'da_xong') {
                $booking->trang_thai_thanh_toan = true;
            }
        } elseif ($this->canActAsCustomer($user)) {
            if ($newStatus === 'da_huy' && in_array($booking->trang_thai, ['cho_xac_nhan', 'da_xac_nhan'], true)) {
                $booking->trang_thai = $newStatus;
                $this->applyCancellationReason($booking, $validated['ma_ly_do_huy'] ?? null, $validated['ly_do_huy'] ?? null);
            } elseif ($newStatus === 'da_xong') {
                return response()->json([
                    'message' => 'Khach hang khong the tu xac nhan hoan tat. Hay thanh toan chuyen khoan tren he thong hoac doi tho xac nhan tien mat.',
                ], 400);
            } else {
                return response()->json(['message' => 'Khach hang khong the doi sang trang thai nay luc nay'], 400);
            }
        } elseif ($this->canActAsWorker($user)) {
            if (!$this->isAdmin($user) && $booking->tho_id !== $user->id) {
                return response()->json(['message' => 'Ban khong phai tho cua don nay'], 403);
            }

            if ($newStatus === 'da_xac_nhan' && $booking->trang_thai === 'cho_xac_nhan') {
                $booking->trang_thai = $newStatus;
            } elseif ($newStatus === 'dang_lam' && $booking->trang_thai === 'da_xac_nhan') {
                $booking->trang_thai = $newStatus;
            } elseif ($newStatus === 'cho_hoan_thanh' && $booking->trang_thai === 'dang_lam') {
                $booking->trang_thai = $newStatus;
            } elseif ($newStatus === 'da_huy' && in_array($booking->trang_thai, ['cho_xac_nhan', 'da_xac_nhan'], true)) {
                $booking->trang_thai = $newStatus;
                $this->applyCancellationReason($booking, $validated['ma_ly_do_huy'] ?? null, $validated['ly_do_huy'] ?? null);
            } else {
                return response()->json(['message' => 'Tho khong the nhay coc trang thai nhu vay'], 400);
            }
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->thoi_gian_hoan_thanh = $booking->trang_thai === 'da_xong'
            ? ($booking->thoi_gian_hoan_thanh ?? now())
            : null;

        $booking->save();

        if ($booking->trang_thai === 'da_xong') {
            app(\App\Services\Chat\AiKnowledgeSyncService::class)->syncBookingCases((int) $booking->id);
        }

        if ($previousStatus !== $booking->trang_thai && $user->role !== 'customer') {
            $booking->loadMissing(['tho:id,name', 'khachHang:id,name,email', 'dichVus:id,ten_dich_vu']);
            $notificationContent = $this->buildCustomerStatusNotificationContent($booking, $user, $previousStatus);

            if ($notificationContent !== null) {
                $this->notifyCustomerAboutBookingUpdate(
                    $booking,
                    $notificationContent['title'],
                    $notificationContent['message'],
                    $notificationContent['type'] ?? 'booking_status_updated',
                    $notificationContent['action_label'] ?? null
                );
            }
        }

        return response()->json([
            'message' => 'Cap nhat trang thai thanh cong',
            'data' => $booking,
        ]);
    }

    public function updateCosts(UpdateBookingCostsRequest $request, string $id)
    {
        $user = $request->user();
        if (!$this->canActAsWorker($user)) {
            return response()->json(['message' => 'Chi tho moi duoc quyen them phi linh kien'], 403);
        }

        $booking = DonDatLich::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Khong tim thay don dat lich'], 404);
        }

        if (!$this->isAdmin($user) && $booking->tho_id !== $user->id) {
            return response()->json(['message' => 'Ban khong phai tho cua don nay'], 403);
        }

        if ($booking->trang_thai !== 'dang_lam') {
            return response()->json(['success' => false, 'message' => 'Trang thai don khong hop le.'], 400);
        }

        $validated = $request->validated();
        $laborItems = $this->normalizeLaborCostItems($validated['chi_tiet_tien_cong'] ?? []);
        $partItems = $this->normalizePartCostItems($validated['chi_tiet_linh_kien'] ?? [], $booking);

        $booking->tien_cong = $this->sumCostItems($laborItems);
        $booking->chi_tiet_tien_cong = $laborItems;
        $booking->phi_linh_kien = $this->sumCostItems($partItems);
        $booking->chi_tiet_linh_kien = $partItems;
        $booking->tien_thue_xe = (float) ($validated['tien_thue_xe'] ?? $booking->tien_thue_xe ?? 0);
        $booking->ghi_chu_linh_kien = $validated['ghi_chu_linh_kien'] ?? null;
        $booking->tong_tien = (float) $booking->phi_di_lai
            + (float) $booking->phi_linh_kien
            + (float) $booking->tien_cong
            + (float) $booking->tien_thue_xe;
        $booking->gia_da_cap_nhat = true;
        $booking->save();

        return response()->json([
            'message' => 'Cap nhat chi phi sua chua thanh cong',
            'data' => $booking,
        ]);
    }

    public function requestPayment(Request $request, $id)
    {
        $user = $request->user();
        if (!$this->canActAsWorker($user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $booking = DonDatLich::find($id);
        if (!$booking || (!$this->isAdmin($user) && $booking->tho_id !== $user->id)) {
            return response()->json(['success' => false, 'message' => 'Khong tim thay don.'], 404);
        }

        if ($booking->trang_thai !== 'dang_lam') {
            return response()->json(['success' => false, 'message' => 'Trang thai don khong hop le.'], 400);
        }

        if (!$booking->gia_da_cap_nhat) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng cập nhật giá trước khi báo hoàn thành.',
            ], 422);
        }

        $request->validate([
            'tien_cong' => 'nullable|numeric|min:0',
            'tien_thue_xe' => 'nullable|numeric|min:0',
            'phuong_thuc_thanh_toan' => 'nullable|in:cod,transfer',
            'hinh_anh_ket_qua.*' => 'nullable|image|max:5120',
            'video_ket_qua' => 'nullable|mimes:mp4,mov,avi,wmv|max:20480',
        ]);

        $paymentMethod = $request->input('phuong_thuc_thanh_toan', $booking->phuong_thuc_thanh_toan ?? 'cod');
        $booking->phuong_thuc_thanh_toan = $paymentMethod;
        $booking->trang_thai = $paymentMethod === 'cod' ? 'cho_hoan_thanh' : 'cho_thanh_toan';
        if ($request->has('tien_cong')) {
            $booking->tien_cong = $request->tien_cong;
        }
        if ($request->has('tien_thue_xe')) {
            $booking->tien_thue_xe = $request->tien_thue_xe;
        }
        if ($request->hasFile('hinh_anh_ket_qua')) {
            $images = [];
            foreach ($request->file('hinh_anh_ket_qua') as $file) {
                $uploadResult = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::uploadApi()->upload($file->getRealPath(), [
                    'folder' => 'bookings/results/images',
                ]);
                $images[] = $uploadResult['secure_url'];
            }
            $booking->hinh_anh_ket_qua = $images;
        }

        if ($request->hasFile('video_ket_qua')) {
            $uploadResult = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::uploadApi()->upload($request->file('video_ket_qua')->getRealPath(), [
                'folder' => 'bookings/results/videos',
                'resource_type' => 'video',
            ]);
            $booking->video_ket_qua = $uploadResult['secure_url'];
        }

        $booking->save();

        $actorName = $user->name ?? 'Hệ thống';
        $notificationMessage = $booking->phuong_thuc_thanh_toan === 'transfer'
            ? "Thợ {$actorName} đã hoàn thành sửa chữa đơn #{$booking->id}. Vui lòng thanh toán online để hoàn tất đơn."
            : "Thợ {$actorName} đã hoàn thành sửa chữa đơn #{$booking->id}. Vui lòng thanh toán tiền mặt trực tiếp cho thợ để hoàn tất đơn.";
        $booking->loadMissing(['tho:id,name', 'khachHang:id,name,email', 'dichVus:id,ten_dich_vu']);
        $this->notifyCustomerAboutBookingUpdate(
            $booking,
            'Thợ đã cập nhật kết quả sửa chữa',
            $notificationMessage,
            'booking_payment_requested',
            $booking->phuong_thuc_thanh_toan === 'transfer' ? 'Xem và thanh toán đơn' : 'Xem hướng dẫn thanh toán'
        );

        return response()->json([
            'success' => true,
            'message' => 'Da gui yeu cau thanh toan cho khach hang.',
            'booking' => $booking,
        ]);
    }

    public function confirmCashPayment(Request $request, $id)
    {
        $user = $request->user();
        if (!$this->canActAsWorker($user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $booking = DonDatLich::find($id);
        if (!$booking || (!$this->isAdmin($user) && $booking->tho_id !== $user->id)) {
            return response()->json(['success' => false, 'message' => 'Khong tim thay don.'], 404);
        }

        if ($booking->trang_thai !== 'cho_thanh_toan' && $booking->trang_thai !== 'cho_hoan_thanh') {
            return response()->json(['success' => false, 'message' => 'Khach chua duoc yeu cau thanh toan.'], 400);
        }

        if (($booking->phuong_thuc_thanh_toan ?? 'cod') !== 'cod') {
            return response()->json([
                'success' => false,
                'message' => 'Don nay dang cho thanh toan chuyen khoan. Tho khong the xac nhan tien mat.',
            ], 400);
        }

        $booking->trang_thai = 'da_xong';
        $booking->thoi_gian_hoan_thanh = $booking->thoi_gian_hoan_thanh ?? now();
        $booking->trang_thai_thanh_toan = true;
        $booking->save();

        app(\App\Services\Chat\AiKnowledgeSyncService::class)->syncBookingCases((int) $booking->id);

        \App\Models\ThanhToan::create([
            'don_dat_lich_id' => $booking->id,
            'so_tien' => $this->resolveBookingTotal($booking),
            'phuong_thuc' => 'cash',
            'trang_thai' => 'success',
            'ma_giao_dich' => 'CASH_' . time(),
        ]);

        $booking->loadMissing(['tho:id,name', 'khachHang:id,name,email', 'dichVus:id,ten_dich_vu']);
        $this->notifyCustomerAboutBookingUpdate(
            $booking,
            'Đơn đặt lịch đã hoàn tất',
            'Thợ ' . ($user->name ?? 'hệ thống') . ' đã xác nhận thanh toán tiền mặt cho đơn #' . $booking->id . '. Cảm ơn bạn đã sử dụng dịch vụ!',
            'booking_completed'
        );

        return response()->json([
            'success' => true,
            'message' => 'Da thu tien mat va hoan tat don.',
            'booking' => $booking,
        ]);
    }

    public function confirmPartWarranty(Request $request, string $id, int $partIndex)
    {
        $user = $request->user();
        $booking = DonDatLich::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Khong tim thay don dat lich'], 404);
        }

        $isAssignedWorker = $this->canActAsWorker($user) && !$this->isAdmin($user) && $booking->tho_id === $user->id;
        if (!$this->isAdmin($user) && !$isAssignedWorker) {
            return response()->json([
                'message' => 'Chi admin hoac tho dang phu trach don moi duoc xac nhan bao hanh.',
            ], 403);
        }

        $partItems = is_array($booking->chi_tiet_linh_kien) ? array_values($booking->chi_tiet_linh_kien) : [];
        if (!array_key_exists($partIndex, $partItems)) {
            return response()->json(['message' => 'Khong tim thay linh kien can xac nhan bao hanh.'], 404);
        }

        $partItem = $partItems[$partIndex];
        $warrantyMonths = isset($partItem['bao_hanh_thang']) ? (int) $partItem['bao_hanh_thang'] : 0;
        if ($warrantyMonths <= 0) {
            return response()->json(['message' => 'Linh kien nay khong co thong tin bao hanh hop le.'], 422);
        }

        if (!$booking->thoi_gian_hoan_thanh) {
            return response()->json(['message' => 'Don chua hoan thanh nen chua the xac nhan bao hanh.'], 422);
        }

        $alreadyUsed = filter_var($partItem['bao_hanh_da_su_dung'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($alreadyUsed) {
            return response()->json(['message' => 'Linh kien nay da duoc xac nhan su dung bao hanh truoc do.'], 422);
        }

        $completedAt = $booking->thoi_gian_hoan_thanh instanceof \Illuminate\Support\Carbon
            ? $booking->thoi_gian_hoan_thanh->copy()
            : \Illuminate\Support\Carbon::parse($booking->thoi_gian_hoan_thanh);
        $warrantyEndsAt = $completedAt->copy()->addMonthsNoOverflow($warrantyMonths);

        if (now()->greaterThan($warrantyEndsAt)) {
            return response()->json(['message' => 'Linh kien nay da het han bao hanh, khong the xac nhan su dung.'], 422);
        }

        $partItems[$partIndex]['bao_hanh_da_su_dung'] = true;
        $partItems[$partIndex]['bao_hanh_xac_nhan_at'] = now()->toDateTimeString();
        $partItems[$partIndex]['bao_hanh_xac_nhan_boi_id'] = $user->id;
        $partItems[$partIndex]['bao_hanh_xac_nhan_boi_ten'] = $user->name;

        $booking->chi_tiet_linh_kien = $partItems;
        $booking->save();

        return response()->json([
            'message' => 'Da xac nhan linh kien da su dung bao hanh.',
            'data' => $booking->fresh([
                'khachHang:id,name,avatar,phone,address',
                'tho:id,name,avatar,phone',
                'tho.hoSoTho:user_id,danh_gia_trung_binh,tong_so_danh_gia',
                'dichVus:id,ten_dich_vu,hinh_anh',
                'danhGias',
                'thanhToans',
            ]),
        ]);
    }

    private function normalizeLaborCostItems(array $items): array
    {
        return array_values(array_map(function (array $item): array {
            return [
                'noi_dung' => trim((string) ($item['noi_dung'] ?? '')),
                'so_tien' => (float) ($item['so_tien'] ?? 0),
            ];
        }, array_filter($items, function ($item): bool {
            return is_array($item)
                && (
                    trim((string) ($item['noi_dung'] ?? '')) !== ''
                    || (float) ($item['so_tien'] ?? 0) > 0
                );
        })));
    }

    private function normalizePartCostItems(array $items, ?DonDatLich $booking = null): array
    {
        $normalizedItems = array_values(array_filter($items, function ($item): bool {
            return is_array($item)
                && (
                    trim((string) ($item['noi_dung'] ?? '')) !== ''
                    || (float) ($item['so_tien'] ?? 0) > 0
                    || ($item['bao_hanh_thang'] ?? null) !== null
                    || ($item['bao_hanh_thang'] ?? null) === '0'
                    || filter_var($item['bao_hanh_da_su_dung'] ?? false, FILTER_VALIDATE_BOOLEAN)
                    || !empty($item['linh_kien_id'])
                );
        }));

        $catalogPartIds = collect($normalizedItems)
            ->pluck('linh_kien_id')
            ->filter(static fn ($id) => $id !== null && $id !== '')
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $catalogParts = $catalogPartIds->isEmpty()
            ? collect()
            : LinhKien::query()
                ->whereIn('id', $catalogPartIds->all())
                ->get()
                ->keyBy('id');

        $allowedServiceIds = $booking === null
            ? []
            : $this->resolveBookingServiceIds($booking);

        $invalidPartIds = [];

        $partItems = array_map(function (array $item) use ($catalogParts, $allowedServiceIds, &$invalidPartIds): ?array {
            $warrantyMonths = $item['bao_hanh_thang'] ?? null;
            $warrantyUsed = filter_var($item['bao_hanh_da_su_dung'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $catalogPartId = isset($item['linh_kien_id']) && $item['linh_kien_id'] !== ''
                ? (int) $item['linh_kien_id']
                : null;

            if ($catalogPartId !== null) {
                /** @var \App\Models\LinhKien|null $catalogPart */
                $catalogPart = $catalogParts->get($catalogPartId);

                if (
                    $catalogPart === null
                    || ($allowedServiceIds !== [] && !in_array((int) $catalogPart->dich_vu_id, $allowedServiceIds, true))
                ) {
                    $invalidPartIds[] = $catalogPartId;
                    return null;
                }
            }

            $resolvedAmount = $catalogPartId !== null
                ? (float) ($catalogParts->get($catalogPartId)?->gia ?? $item['so_tien'] ?? 0)
                : (float) ($item['so_tien'] ?? 0);
            $resolvedDescription = $catalogPartId !== null
                ? trim((string) ($catalogParts->get($catalogPartId)?->ten_linh_kien ?? $item['noi_dung'] ?? ''))
                : trim((string) ($item['noi_dung'] ?? ''));

            return [
                'linh_kien_id' => $catalogPartId,
                'dich_vu_id' => $catalogPartId !== null
                    ? (int) ($catalogParts->get($catalogPartId)?->dich_vu_id)
                    : (isset($item['dich_vu_id']) ? (int) $item['dich_vu_id'] : null),
                'hinh_anh' => $catalogPartId !== null
                    ? $catalogParts->get($catalogPartId)?->hinh_anh
                    : ($item['hinh_anh'] ?? null),
                'noi_dung' => $resolvedDescription,
                'so_tien' => $resolvedAmount,
                'bao_hanh_thang' => $warrantyMonths === null || $warrantyMonths === ''
                    ? null
                    : (int) $warrantyMonths,
                'bao_hanh_da_su_dung' => $warrantyUsed,
            ];
        }, $normalizedItems);

        if ($invalidPartIds !== []) {
            throw ValidationException::withMessages([
                'chi_tiet_linh_kien' => ['Có linh kiện không thuộc nhóm dịch vụ của đơn hoặc không tồn tại.'],
            ]);
        }

        return array_values(array_filter($partItems));
    }

    private function sumCostItems(array $items): float
    {
        return (float) collect($items)->sum(function (array $item): float {
            return (float) ($item['so_tien'] ?? 0);
        });
    }

    private function resolveBookingTotal(DonDatLich $booking): float
    {
        $total = (float) ($booking->tong_tien ?? 0);
        if ($total > 0) {
            return $total;
        }

        return (float) ($booking->phi_di_lai ?? 0)
            + (float) ($booking->phi_linh_kien ?? 0)
            + (float) ($booking->tien_cong ?? 0)
            + (float) ($booking->tien_thue_xe ?? 0);
    }

    private function notifyCustomerAboutBookingUpdate(
        DonDatLich $booking,
        string $title,
        string $message,
        string $type = 'booking_status_updated',
        ?string $actionLabel = null
    ): void {
        try {
            $booking->khachHang?->notify(new BookingStatusNotification(
                $booking,
                $title,
                $message,
                $type,
                $actionLabel
            ));
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('Customer booking notification failed', [
                'booking_id' => $booking->id,
                'customer_id' => $booking->khach_hang_id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function buildCustomerStatusNotificationContent(DonDatLich $booking, User $actor, string $previousStatus): ?array
    {
        $actorName = $booking->tho?->name ?? $actor->name ?? 'hệ thống';
        $status = $booking->trang_thai;

        if ($previousStatus === $status) {
            return null;
        }

        return match ($status) {
            'da_xac_nhan' => [
                'title' => 'Đơn đặt lịch đã được xác nhận',
                'message' => 'Thợ ' . $actorName . ' đã xác nhận lịch hẹn cho đơn #' . $booking->id . '.',
                'type' => 'booking_claimed',
            ],
            'dang_lam' => [
                'title' => 'Thợ đang xử lý đơn đặt lịch',
                'message' => 'Thợ ' . $actorName . ' đang bắt đầu xử lý đơn #' . $booking->id . ' của bạn.',
                'type' => 'booking_in_progress',
            ],
            'cho_hoan_thanh' => [
                'title' => 'Đơn đặt lịch đang chờ hoàn tất',
                'message' => 'Thợ ' . $actorName . ' đã cập nhật đơn #' . $booking->id . ' sang trạng thái chờ hoàn tất.',
                'type' => 'booking_waiting_completion',
            ],
            'da_huy' => [
                'title' => 'Đơn đặt lịch đã bị hủy',
                'message' => 'Đơn #' . $booking->id . ' đã bị hủy.'
                    . ($booking->ly_do_huy ? ' Lý do: ' . $booking->ly_do_huy . '.' : ''),
                'type' => 'booking_cancelled',
            ],
            'da_xong' => [
                'title' => 'Đơn đặt lịch đã hoàn tất',
                'message' => 'Đơn #' . $booking->id . ' đã được cập nhật thành hoàn tất.',
                'type' => 'booking_completed',
            ],
            default => [
                'title' => 'Đơn đặt lịch vừa được cập nhật',
                'message' => 'Đơn #' . $booking->id . ' đã được cập nhật sang trạng thái "' . $this->resolveStatusLabel($status) . '".',
                'type' => 'booking_status_updated',
            ],
        };
    }

    private function isAdmin(User $user): bool
    {
        return $user->role === 'admin';
    }

    private function canActAsCustomer(User $user): bool
    {
        return in_array($user->role, ['customer', 'admin'], true);
    }

    private function canActAsWorker(User $user): bool
    {
        return in_array($user->role, ['worker', 'admin'], true);
    }

    private function normalizeValidatedServiceIds(array $validated): array
    {
        $serviceIds = $validated['dich_vu_ids'] ?? [];

        if (!is_array($serviceIds)) {
            $serviceIds = [$serviceIds];
        }

        return collect($serviceIds)
            ->filter(static fn ($id) => $id !== null && $id !== '')
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function hydrateLegacyServiceColumn(DonDatLich $booking, array $serviceIds): void
    {
        static $hasLegacyServiceColumn = null;

        if ($hasLegacyServiceColumn === null) {
            $hasLegacyServiceColumn = Schema::hasColumn($booking->getTable(), 'dich_vu_id');
        }

        if ($hasLegacyServiceColumn) {
            $booking->dich_vu_id = $serviceIds[0] ?? null;
        }
    }

    private function getEligibleWorkersForServiceIds(array $serviceIds): Collection
    {
        $serviceIds = collect($serviceIds)
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($serviceIds)) {
            return collect();
        }

        $query = User::query()
            ->where('role', 'worker')
            ->where('is_active', true)
            ->whereHas('hoSoTho', function ($profileQuery) {
                $profileQuery
                    ->where('trang_thai_duyet', 'da_duyet')
                    ->where('dang_hoat_dong', true);
            });

        foreach ($serviceIds as $serviceId) {
            $query->whereHas('dichVus', function ($serviceQuery) use ($serviceId) {
                $serviceQuery->whereKey($serviceId);
            });
        }

        return $query->get();
    }

    private function workerSupportsBookingServices(User $worker, DonDatLich $booking): bool
    {
        $bookingServiceIds = collect($this->resolveBookingServiceIds($booking));

        if ($bookingServiceIds->isEmpty()) {
            return false;
        }

        $workerServiceIds = $worker->dichVus()
            ->pluck('danh_muc_dich_vu.id')
            ->map(static fn ($id) => (int) $id);

        return $bookingServiceIds->diff($workerServiceIds)->isEmpty();
    }

    private function applyCancellationReason(DonDatLich $booking, ?string $reasonCode, ?string $fallbackMessage = null): void
    {
        $booking->ma_ly_do_huy = $reasonCode;
        $booking->ly_do_huy = DonDatLich::cancelReasonLabel($reasonCode)
            ?? ($fallbackMessage !== null && trim($fallbackMessage) !== '' ? trim($fallbackMessage) : null);
    }

    private function resolveBookingServiceIds(DonDatLich $booking): array
    {
        $serviceIds = $booking->relationLoaded('dichVus')
            ? collect($booking->dichVus)->pluck('id')
            : $booking->dichVus()->pluck('danh_muc_dich_vu.id');

        if ($serviceIds->isEmpty() && !empty($booking->dich_vu_id)) {
            $serviceIds = collect([(int) $booking->dich_vu_id]);
        }

        return $serviceIds
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function resolveStatusLabel(?string $status): string
    {
        return match ($status) {
            'cho_xac_nhan' => 'Đang tìm thợ',
            'da_xac_nhan' => 'Đã có thợ nhận',
            'dang_lam' => 'Đang xử lý',
            'cho_hoan_thanh' => 'Chờ hoàn tất',
            'cho_thanh_toan' => 'Chờ thanh toán',
            'da_xong' => 'Đã hoàn tất',
            'da_huy' => 'Đã hủy',
            default => 'Đang cập nhật',
        };
    }
}
