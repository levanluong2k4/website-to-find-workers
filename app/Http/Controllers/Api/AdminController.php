<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DonDatLich;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Lấy thống kê tổng quan (Dashboard)
     */
    public function getDashboardStats()
    {
        // Khách hàng & Thợ
        $totalCustomers = User::where('role', 'customer')->count();
        $totalWorkers = User::where('role', 'worker')->count();

        // Đơn hàng
        $totalBookings = DonDatLich::count();
        $completedBookings = DonDatLich::where('trang_thai', 'hoan_thanh')->count();
        $canceledBookings = DonDatLich::where('trang_thai', 'da_huy')->count();

        // Doanh thu (Tổng chi phí của các đơn hoàn thành)
        $totalRevenue = DonDatLich::where('trang_thai', 'hoan_thanh')->sum('tong_chi_phi');

        // Hoa hồng (Giả sử hệ thống thu 10% trên tổng chi phí)
        $systemCommission = $totalRevenue * 0.10;

        return response()->json([
            'status' => 'success',
            'data' => [
                'users' => [
                    'customers' => $totalCustomers,
                    'workers' => $totalWorkers,
                ],
                'bookings' => [
                    'total' => $totalBookings,
                    'completed' => $completedBookings,
                    'canceled' => $canceledBookings,
                ],
                'revenue' => [
                    'total_revenue' => $totalRevenue,
                    'system_commission' => $systemCommission,
                ]
            ]
        ]);
    }

    /**
     * Quản lý người dùng: Lấy danh sách Users
     */
    public function getUsers(Request $request)
    {
        $role = $request->query('role');

        $query = User::query();

        if ($role) {
            $query->where('role', $role);
        }

        // Không hiển thị admin ở danh sách quản lý
        $query->where('role', '!=', 'admin');

        $users = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    /**
     * Quản lý người dùng: Cập nhật trạng thái (Khóa/Mở khóa)
     */
    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể thay đổi trạng thái của Admin.'
            ], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => $user->is_active ? 'Đã mở khóa tài khoản' : 'Đã khóa tài khoản',
            'data' => $user
        ]);
    }

    /**
     * Giám sát Đơn hàng: Lấy toàn bộ đơn
     */
    public function getAllBookings(Request $request)
    {
        $status = $request->query('status');

        $query = DonDatLich::with(['khachHang:id,name,phone', 'tho:id,name,phone', 'dichVu:id,ten_dich_vu']);

        if ($status) {
            $query->where('trang_thai', $status);
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $bookings
        ]);
    }
}
