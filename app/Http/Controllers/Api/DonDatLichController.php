<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\DonDatLich\UpdateTrangThaiRequest;
use App\Models\DonDatLich;
use App\Models\User;
use App\Notifications\NewBookingNotification;
use Illuminate\Support\Facades\Notification;

class DonDatLichController extends Controller
{
    /**
     * Display a listing of bookings for the logged-in user.
     * Thợ thấy đơn của thợ, Khách thấy đơn của khách.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = DonDatLich::with([
            'khachHang:id,name,avatar,phone',
            'tho:id,name,avatar,phone',
            'dichVu:id,ten_dich_vu',
            'danhGias'
        ]);

        if ($user->role === 'customer') {
            $query->where('khach_hang_id', $user->id);
        } elseif ($user->role === 'worker') {
            $query->where('tho_id', $user->id);
        }

        $bookings = $query->latest()->paginate(15);

        return response()->json($bookings);
    }
    /**
     * Create a new booking (Khách hàng)
     */
    public function store(\App\Http\Requests\DonDatLich\StoreDonDatLichRequest $request)
    {
        $user = $request->user();
        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Chỉ khách hàng mới có quyền đặt lịch'], 403);
        }

        $validated = $request->validated();

        $khoangCach = null;
        $phiDiLai = 0;

        if ($validated['loai_dat_lich'] === 'at_home') {
            // Tọa độ cửa hàng Tôn Thất Thuyết (Ví dụ: 2 Đ. Nguyễn Đình Chiểu)
            $storeLat = 12.2618;
            $storeLng = 109.1995;

            $lat = $validated['vi_do'];
            $lng = $validated['kinh_do'];

            // Tính khoảng cách Haversine function
            $earthRadius = 6371;
            $dLat = deg2rad($lat - $storeLat);
            $dLng = deg2rad($lng - $storeLng);
            $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($storeLat)) * cos(deg2rad($lat)) * sin($dLng / 2) * sin($dLng / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $khoangCach = $earthRadius * $c;

            if ($khoangCach > 5) {
                return response()->json([
                    'message' => 'Địa chỉ của bạn quá xa cửa hàng (> 5km). Chúng tôi chỉ hỗ trợ sửa chữa tận nơi trong bán kính 5km. Vui lòng mang thiết bị đến cửa hàng hoặc chọn địa chỉ khác.',
                    'current_distance' => round($khoangCach, 1)
                ], 400);
            }

            // Tính phí đi lại (ví dụ 5000đ/km)
            $phiDiLai = round($khoangCach * 5000);
        }

        // Tạo đơn
        $booking = new DonDatLich();
        $booking->khach_hang_id = $user->id;
        $booking->tho_id = $validated['tho_id'] ?? null;
        $booking->dich_vu_id = $validated['dich_vu_id'];
        $booking->loai_dat_lich = $validated['loai_dat_lich'];
        $booking->ngay_hen = $validated['ngay_hen'];
        $booking->khung_gio_hen = $validated['khung_gio_hen'];

        // Add thoi_gian_hen as datetime (combining ngay_hen and the start of khung_gio_hen)
        $gioBatDau = explode('-', $validated['khung_gio_hen'])[0]; // ví dụ '10:00'
        $booking->thoi_gian_hen = $validated['ngay_hen'] . ' ' . $gioBatDau . ':00';
        $booking->mo_ta_van_de = $validated['mo_ta_van_de'] ?? null;
        $booking->thue_xe_cho = $validated['thue_xe_cho'] ?? false;
        $booking->trang_thai = 'cho_xac_nhan';
        $booking->phuong_thuc_thanh_toan = 'cod';

        // Thời gian hết hạn nhận đơn (1 tiếng) cho đơn đặt đích danh thợ
        if ($booking->tho_id) {
            $booking->thoi_gian_het_han_nhan = now()->addHour();
        }

        if ($validated['loai_dat_lich'] === 'at_home') {
            $booking->dia_chi = $validated['dia_chi'];
            $booking->vi_do = $validated['vi_do'];
            $booking->kinh_do = $validated['kinh_do'];
            $booking->khoang_cach = round($khoangCach, 2);
            $booking->phi_di_lai = $phiDiLai;
        } else {
            // Khách tự mang đến
            $booking->dia_chi = '2 Đ. Nguyễn Đình Chiểu, Vĩnh Thọ, Nha Trang, Khánh Hòa';
            $booking->phi_di_lai = 0;
        }

        $booking->save();

        // Gửi thông báo cho thợ
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
            'message' => 'Đặt lịch thành công',
            'data' => $booking->load(['khachHang', 'tho', 'dichVu'])
        ], 201);
    }

    /**
     * Get available jobs for workers (tho_id is null)
     */
    public function availableJobs(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $jobs = DonDatLich::with([
            'khachHang:id,name,avatar,phone',
            'dichVu:id,ten_dich_vu'
        ])
            ->whereNull('tho_id')
            ->where('trang_thai', 'cho_xac_nhan')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($jobs);
    }

    /**
     * Worker claims a job
     */
    public function claimJob(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking = DonDatLich::find($id);
        if (!$booking) {
            return response()->json(['message' => 'Không tìm thấy đơn đặt lịch'], 404);
        }

        if ($booking->tho_id !== null || $booking->trang_thai !== 'cho_xac_nhan') {
            return response()->json(['message' => 'Đơn này đã được thợ khác nhận hoặc không còn khả dụng'], 400);
        }

        $booking->tho_id = $user->id;
        $booking->trang_thai = 'da_xac_nhan';
        $booking->save();

        return response()->json(['message' => 'Nhận việc thành công', 'data' => $booking]);
    }

    /**
     * Get details of a single booking.
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $booking = DonDatLich::with([
            'khachHang:id,name,avatar,phone,address',
            'tho:id,name,avatar,phone',
            'dichVu',
            'baiDang.hinhAnhs'
        ])->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Không tìm thấy đơn đặt lịch'], 404);
        }

        // Kiểm tra quyền xem
        if ($booking->khach_hang_id !== $user->id && $booking->tho_id !== $user->id) {
            return response()->json(['message' => 'Bạn không có quyền xem đơn này'], 403);
        }

        return response()->json($booking);
    }

    /**
     * Update the status of the booking.
     */
    public function updateStatus(UpdateTrangThaiRequest $request, string $id)
    {
        $user = $request->user();
        $booking = DonDatLich::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Không tìm thấy đơn đặt lịch'], 404);
        }

        $validated = $request->validated();
        $newStatus = $validated['trang_thai'];

        // Logic phân quyền cập nhật trạng thái
        // Khách hàng: Có thể 'da_huy' (nếu chưa đang làm) hoặc 'da_xong' (nếu thợ báo chờ hoàn thành)
        // Thợ: Có thể 'da_xac_nhan', 'dang_lam', 'cho_hoan_thanh', 'da_huy' (nếu chưa nhận tiền)

        if ($user->role === 'customer') {
            if ($newStatus === 'da_huy' && in_array($booking->trang_thai, ['cho_xac_nhan', 'da_xac_nhan'])) {
                $booking->trang_thai = $newStatus;
                $booking->ly_do_huy = $validated['ly_do_huy'] ?? null;
            } elseif ($newStatus === 'da_xong' && $booking->trang_thai === 'cho_hoan_thanh') {
                $booking->trang_thai = $newStatus;
                $booking->trang_thai_thanh_toan = true; // Giả định thanh toán tiền mặt lúc thợ xong
            } else {
                return response()->json(['message' => 'Khách hàng không thể đổi sang trạng thái này lúc này'], 400);
            }
        } elseif ($user->role === 'worker') {
            if ($booking->tho_id !== $user->id) {
                return response()->json(['message' => 'Bạn không phải thợ của đơn này'], 403);
            }

            if ($newStatus === 'da_xac_nhan' && $booking->trang_thai === 'cho_xac_nhan') {
                $booking->trang_thai = $newStatus;
            } elseif ($newStatus === 'dang_lam' && $booking->trang_thai === 'da_xac_nhan') {
                $booking->trang_thai = $newStatus;
            } elseif ($newStatus === 'cho_hoan_thanh' && $booking->trang_thai === 'dang_lam') {
                $booking->trang_thai = $newStatus;
            } elseif ($newStatus === 'da_huy' && in_array($booking->trang_thai, ['cho_xac_nhan', 'da_xac_nhan'])) {
                $booking->trang_thai = $newStatus;
                $booking->ly_do_huy = $validated['ly_do_huy'] ?? null;
            } else {
                return response()->json(['message' => 'Thợ không thể nảy cóc trạng thái như vậy'], 400);
            }
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->save();

        return response()->json([
            'message' => 'Cập nhật trạng thái thành công',
            'data' => $booking
        ]);
    }

    /**
     * Thêm phụ phí linh kiện trong quá trình sửa chữa (Dành cho thợ)
     */
    public function updateCosts(\App\Http\Requests\DonDatLich\UpdateBookingCostsRequest $request, string $id)
    {
        $user = $request->user();
        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Chỉ thợ mới được quyền thêm phí linh kiện'], 403);
        }

        $booking = DonDatLich::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Không tìm thấy đơn đặt lịch'], 404);
        }

        if ($booking->tho_id !== $user->id) {
            return response()->json(['message' => 'Bạn không phải thợ của đơn này'], 403);
        }

        if ($booking->trang_thai !== 'dang_lam') {
            return response()->json(['success' => false, 'message' => 'Trạng thái đơn không hợp lệ.'], 400);
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
            'message' => 'Cập nhật phí linh kiện thành công',
            'data' => $booking
        ]);
    }

    /**
     * WORKER: Yêu cầu thanh toán
     */
    public function requestPayment(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'worker') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $booking = DonDatLich::find($id);
        if (!$booking || $booking->tho_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn.'], 404);
        }

        if ($booking->trang_thai !== 'dang_lam') {
            return response()->json(['success' => false, 'message' => 'Trạng thái đơn không hợp lệ.'], 400);
        }

        $request->validate([
            'tien_cong' => 'nullable|numeric|min:0',
            'tien_thue_xe' => 'nullable|numeric|min:0',
        ]);

        $booking->trang_thai = 'cho_thanh_toan';
        if ($request->has('tien_cong')) $booking->tien_cong = $request->tien_cong;
        if ($request->has('tien_thue_xe')) $booking->tien_thue_xe = $request->tien_thue_xe;
        $booking->save();

        if (class_exists(\App\Http\Controllers\Api\NotificationController::class)) {
            app(\App\Http\Controllers\Api\NotificationController::class)->createNotification(
                $booking->khach_hang_id,
                "Thợ {$user->ho_ten} đã hoàn thành công việc. Vui lòng thanh toán đơn đặt lịch #{$booking->id}.",
                'system'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi yêu cầu thanh toán cho khách hàng.',
            'booking' => $booking
        ]);
    }

    /**
     * WORKER: Xác nhận đã nhận tiền mặt từ khách
     */
    public function confirmCashPayment(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'worker') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $booking = DonDatLich::find($id);
        if (!$booking || $booking->tho_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn.'], 404);
        }

        if ($booking->trang_thai !== 'cho_thanh_toan' && $booking->trang_thai !== 'cho_hoan_thanh') {
            return response()->json(['success' => false, 'message' => 'Khách chưa được yêu cầu thanh toán.'], 400);
        }

        $booking->trang_thai = 'da_xong';
        $booking->save();

        \App\Models\ThanhToan::create([
            'don_dat_lich_id' => $booking->id,
            'so_tien' => ($booking->gia_tien ?? 0) + ($booking->tien_cong ?? 0) + ($booking->tien_thue_xe ?? 0),
            'phuong_thuc' => 'cash',
            'trang_thai' => 'success',
            'ma_giao_dich' => 'CASH_' . time()
        ]);

        if (class_exists(\App\Http\Controllers\Api\NotificationController::class)) {
            app(\App\Http\Controllers\Api\NotificationController::class)->createNotification(
                $booking->khach_hang_id,
                "Đơn sửa chữa #{$booking->id} đã hoàn tất thanh toán (Tiền mặt). Cảm ơn bạn đã sử dụng dịch vụ!",
                'booking'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã thu tiền mặt và hoàn tất đơn.',
            'booking' => $booking
        ]);
    }
}
