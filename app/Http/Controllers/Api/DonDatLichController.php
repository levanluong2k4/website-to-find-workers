<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\DonDatLich\UpdateTrangThaiRequest;
use App\Models\DonDatLich;

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
            'dichVu:id,ten_dich_vu'
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
}
