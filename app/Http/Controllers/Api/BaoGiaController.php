<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\BaoGia\StoreBaoGiaRequest;
use App\Models\BaoGia;
use App\Models\BaiDang;
use App\Models\DonDatLich;
use Illuminate\Support\Facades\DB;

class BaoGiaController extends Controller
{
  
    public function store(StoreBaoGiaRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        // Kiểm tra bài đăng có đang chờ thợ không
        $baiDang = BaiDang::find($validated['bai_dang_id']);
        if ($baiDang->trang_thai !== 'dang_mo') {
            return response()->json(['message' => 'Bài đăng này đã đóng hoặc đã có người nhận'], 400);
        }

        // Kiểm tra xem thợ này đã báo giá chưa
        $daBaoGia = BaoGia::where('bai_dang_id', $baiDang->id)
            ->where('tho_id', $user->id)
            ->exists();

        if ($daBaoGia) {
            return response()->json(['message' => 'Bạn đã nộp báo giá cho bài đăng này rồi'], 400);
        }

        $baoGia = BaoGia::create([
            'bai_dang_id' => $validated['bai_dang_id'],
            'tho_id' => $user->id,
            'muc_gia' => $validated['muc_gia'],
            'ghi_chu' => $validated['ghi_chu'] ?? null,
            'trang_thai' => 'cho_duyet',
        ]);

        return response()->json([
            'message' => 'Nộp báo giá thành công',
            'data' => $baoGia
        ], 201);
    }

    /**
     * WORKER: Xem danh sách các báo giá mình đã nộp.
     */
    public function myQuotes(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $quotes = BaoGia::with(['baiDang:id,tieu_de,trang_thai'])
            ->where('tho_id', $user->id)
            ->latest()
            ->paginate(15);

        return response()->json($quotes);
    }

    /**
     * CUSTOMER: Xem danh sách báo giá của 1 bài đăng cụ thể.
     */
    public function indexByBaiDang(Request $request, $baiDangId)
    {
        $baiDang = BaiDang::find($baiDangId);

        if (!$baiDang) {
            return response()->json(['message' => 'Không tìm thấy bài đăng'], 404);
        }

        if ($baiDang->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền xem báo giá của bài đăng này'], 403);
        }

        $quotes = BaoGia::with(['tho:id,name,avatar,phone'])
            ->where('bai_dang_id', $baiDangId)
            ->orderBy('muc_gia', 'asc') // Liệt kê giá từ thấp đến cao
            ->get();

        return response()->json($quotes);
    }

    /**
     * CUSTOMER: Chọn (Accept) một báo giá.
     */
    public function accept(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $baoGia = BaoGia::with(['baiDang'])->find($id);

        if (!$baoGia) {
            return response()->json(['message' => 'Không tìm thấy báo giá'], 404);
        }

        if ($baoGia->baiDang->user_id !== $user->id) {
            return response()->json(['message' => 'Bạn không có quyền thao tác trên bài đăng này'], 403);
        }

        if ($baoGia->baiDang->trang_thai !== 'dang_mo') {
            return response()->json(['message' => 'Bài đăng này đã được chốt thợ hoặc đã đóng'], 400);
        }

        DB::beginTransaction();
        try {
            // 1. Chuyển trạng thái báo giá thành 'da_chon'
            $baoGia->update(['trang_thai' => 'da_chon']);

            // 2. Chuyển các báo giá khác của bài này thành 'tu_choi'
            BaoGia::where('bai_dang_id', $baoGia->bai_dang_id)
                ->where('id', '!=', $id)
                ->update(['trang_thai' => 'tu_choi']);

            // 3. Đổi trạng thái bài đăng thành 'da_dong'
            $baoGia->baiDang->update(['trang_thai' => 'da_dong']);

            // 4. Tạo Đơn Đặt Lịch
            $donDatLich = DonDatLich::create([
                'khach_hang_id' => $user->id,
                'tho_id' => $baoGia->tho_id,
                'dich_vu_id' => $baoGia->baiDang->dich_vu_id,
                'bai_dang_id' => $baoGia->bai_dang_id,
                'thoi_gian_hen' => now(), // Tạm thời dùng giờ hiện tại
                'dia_chi' => $baoGia->baiDang->dia_chi,
                'vi_do' => $baoGia->baiDang->vi_do,
                'kinh_do' => $baoGia->baiDang->kinh_do,
                'mo_ta_van_de' => $baoGia->baiDang->mo_ta_chi_tiet,
                'tong_tien' => $baoGia->muc_gia,
                'trang_thai' => 'cho_xac_nhan', // Chờ thợ xác nhận lịch trình
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Đã chọn thợ và tạo đơn đặt lịch thành công',
                'don_dat_lich' => $donDatLich
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Có lỗi xảy ra', 'error' => $e->getMessage()], 500);
        }
    }
}
