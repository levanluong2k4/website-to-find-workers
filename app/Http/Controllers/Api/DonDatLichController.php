<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonDatLich\UpdateTrangThaiRequest;
use App\Models\DonDatLich;
use App\Models\User;
use App\Notifications\NewBookingNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class DonDatLichController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = DonDatLich::with([
            'khachHang:id,name,avatar,phone',
            'tho:id,name,avatar,phone',
            'dichVu:id,ten_dich_vu',
            'danhGias',
        ]);

        if ($this->canActAsCustomer($user) && !$this->isAdmin($user)) {
            $query->where('khach_hang_id', $user->id);
        } elseif ($this->canActAsWorker($user) && !$this->isAdmin($user)) {
            $query->where('tho_id', $user->id);
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function store(\App\Http\Requests\DonDatLich\StoreDonDatLichRequest $request)
    {
        $user = $request->user();
        if (!$this->canActAsCustomer($user)) {
            return response()->json(['message' => 'Chi khach hang moi co quyen dat lich'], 403);
        }

        try {
            \Illuminate\Support\Facades\Log::info('Booking store started', ['request' => $request->all()]);
            $validated = $request->validated();
            \Illuminate\Support\Facades\Log::info('Validation passed', ['validated' => $validated]);

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

                $maxDistance = 20;
                if (!empty($validated['tho_id']) && isset($hoSoTho) && $hoSoTho) {
                    $maxDistance = $hoSoTho->ban_kinh_phuc_vu ?? 10;
                }

                if ($khoangCach > $maxDistance) {
                    return response()->json([
                        'message' => 'Dia chi cua ban qua xa khoang cach phuc vu (>' . $maxDistance . 'km). Khoang cach hien tai: ' . round($khoangCach, 1) . 'km. Vui long chon tho khac hoac mang den cua hang.',
                        'current_distance' => round($khoangCach, 1),
                    ], 400);
                }

                $phiDiLai = round($khoangCach * 5000);
            }

            $booking = new DonDatLich();
            $booking->khach_hang_id = $user->id;
            $booking->tho_id = $validated['tho_id'] ?? null;
            $booking->dich_vu_id = $validated['dich_vu_id'] ?? null;
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

            if ($booking->tho_id) {
                $tho = User::find($booking->tho_id);
                if ($tho) {
                    $tho->notify(new NewBookingNotification($booking));
                }
            } else {
                $thoList = User::where('role', 'worker')->where('is_active', true)->get();
                Notification::send($thoList, new NewBookingNotification($booking));
            }

            return response()->json([
                'message' => 'Dat lich thanh cong',
                'data' => $booking->load(['khachHang', 'tho', 'dichVu']),
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
            'dichVu:id,ten_dich_vu',
        ])
            ->whereNull('tho_id')
            ->where('trang_thai', 'cho_xac_nhan')
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

        $booking->tho_id = $user->id;
        $booking->trang_thai = 'da_xac_nhan';
        $booking->save();

        return response()->json(['message' => 'Nhan viec thanh cong', 'data' => $booking]);
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $booking = DonDatLich::with([
            'khachHang:id,name,avatar,phone,address',
            'tho:id,name,avatar,phone',
            'dichVu',
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

        if ($this->isAdmin($user)) {
            $booking->trang_thai = $newStatus;
            if ($newStatus === 'da_huy') {
                $booking->ly_do_huy = $validated['ly_do_huy'] ?? null;
            }
            if ($newStatus === 'da_xong') {
                $booking->trang_thai_thanh_toan = true;
            }
        } elseif ($this->canActAsCustomer($user)) {
            if ($newStatus === 'da_huy' && in_array($booking->trang_thai, ['cho_xac_nhan', 'da_xac_nhan'], true)) {
                $booking->trang_thai = $newStatus;
                $booking->ly_do_huy = $validated['ly_do_huy'] ?? null;
            } elseif ($newStatus === 'da_xong' && $booking->trang_thai === 'cho_hoan_thanh') {
                $booking->trang_thai = $newStatus;
                $booking->trang_thai_thanh_toan = true;
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
                $booking->ly_do_huy = $validated['ly_do_huy'] ?? null;
            } else {
                return response()->json(['message' => 'Tho khong the nhay coc trang thai nhu vay'], 400);
            }
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->save();

        return response()->json([
            'message' => 'Cap nhat trang thai thanh cong',
            'data' => $booking,
        ]);
    }

    public function updateCosts(\App\Http\Requests\DonDatLich\UpdateBookingCostsRequest $request, string $id)
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

        $booking->tien_cong = $validated['tien_cong'];
        if (isset($validated['tien_thue_xe'])) {
            $booking->tien_thue_xe = $validated['tien_thue_xe'];
        }
        $booking->phi_linh_kien = $validated['phi_linh_kien'];
        $booking->ghi_chu_linh_kien = $validated['ghi_chu_linh_kien'] ?? null;
        $booking->tong_tien = $booking->phi_di_lai + $booking->phi_linh_kien + $booking->tien_cong + $booking->tien_thue_xe;
        $booking->save();

        return response()->json([
            'message' => 'Cap nhat phi linh kien thanh cong',
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

        $request->validate([
            'tien_cong' => 'nullable|numeric|min:0',
            'tien_thue_xe' => 'nullable|numeric|min:0',
            'phuong_thuc_thanh_toan' => 'nullable|in:cod,transfer',
            'hinh_anh_ket_qua.*' => 'nullable|image|max:5120',
            'video_ket_qua' => 'nullable|mimes:mp4,mov,avi,wmv|max:20480',
        ]);

        $booking->trang_thai = 'cho_thanh_toan';
        if ($request->has('tien_cong')) {
            $booking->tien_cong = $request->tien_cong;
        }
        if ($request->has('tien_thue_xe')) {
            $booking->tien_thue_xe = $request->tien_thue_xe;
        }
        if ($request->has('phuong_thuc_thanh_toan')) {
            $booking->phuong_thuc_thanh_toan = $request->phuong_thuc_thanh_toan;
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

        $paymentTypeStr = $booking->phuong_thuc_thanh_toan === 'transfer' ? 'Chuyen khoan' : 'Tien mat';
        $actorName = $user->name ?? 'He thong';
        $booking->khachHang?->notify(new \App\Notifications\SimpleNotification(
            "Tho {$actorName} da hoan thanh cong viec. Vui long thanh toan don dat lich #{$booking->id} bang {$paymentTypeStr}."
        ));

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

        $booking->trang_thai = 'da_xong';
        $booking->save();

        \App\Models\ThanhToan::create([
            'don_dat_lich_id' => $booking->id,
            'so_tien' => ($booking->gia_tien ?? 0) + ($booking->tien_cong ?? 0) + ($booking->tien_thue_xe ?? 0),
            'phuong_thuc' => 'cash',
            'trang_thai' => 'success',
            'ma_giao_dich' => 'CASH_' . time(),
        ]);

        $booking->khachHang?->notify(new \App\Notifications\SimpleNotification(
            "Don sua chua #{$booking->id} da hoan tat thanh toan (Tien mat). Cam on ban da su dung dich vu!"
        ));

        return response()->json([
            'success' => true,
            'message' => 'Da thu tien mat va hoan tat don.',
            'booking' => $booking,
        ]);
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
}
