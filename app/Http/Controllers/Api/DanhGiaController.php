<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\DanhGia\StoreDanhGiaRequest;
use App\Http\Requests\DanhGia\UpdateDanhGiaRequest;
use App\Models\DanhGia;
use App\Models\DonDatLich;
use App\Models\HoSoTho;
use Illuminate\Support\Facades\DB;

class DanhGiaController extends Controller
{
    /**
     * CUSTOMER: Submit a review for a completed booking
     */
    public function store(StoreDanhGiaRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $booking = DonDatLich::find($validated['don_dat_lich_id']);

        // Phải là đơn của chính khách hàng này
        if ($booking->khach_hang_id !== $user->id) {
            return response()->json(['message' => 'Bạn không có quyền đánh giá đơn này'], 403);
        }

        // Đơn phải ở trạng thái đã xong
        if ($booking->trang_thai !== 'da_xong') {
            return response()->json(['message' => 'Chỉ có thể đánh giá khi đơn đã hoàn thành'], 400);
        }

        // Mỗi đơn chỉ được đánh giá 1 lần
        $daDanhGia = DanhGia::where('don_dat_lich_id', $booking->id)->exists();
        if ($daDanhGia) {
            return response()->json(['message' => 'Bạn đã gửi đánh giá cho đơn này rồi'], 400);
        }

        DB::beginTransaction();
        try {
            $danhGia = DanhGia::create([
                'don_dat_lich_id' => $booking->id,
                'nguoi_danh_gia_id' => $user->id,
                'nguoi_bi_danh_gia_id' => $booking->tho_id,
                'so_sao' => $validated['so_sao'],
                'nhan_xet' => $validated['nhan_xet'] ?? null,
                'so_lan_sua' => 0,
            ]);

            // Cập nhật điểm trung bình cho thợ
            $this->updateWorkerRating($booking->tho_id);

            DB::commit();

            return response()->json([
                'message' => 'Đánh giá thành công',
                'data' => $danhGia
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi hệ thống', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * CUSTOMER: Update their review (Max 1 edit)
     */
    public function update(UpdateDanhGiaRequest $request, string $id)
    {
        $validated = $request->validated();
        $user = $request->user();

        $danhGia = DanhGia::find($id);

        if (!$danhGia) {
            return response()->json(['message' => 'Không tìm thấy đánh giá'], 404);
        }

        if ($danhGia->nguoi_danh_gia_id !== $user->id) {
            return response()->json(['message' => 'Không có quyền sửa đánh giá này'], 403);
        }

        // Kiểm tra luật: Chỉ được sửa tối đa 1 lần
        if ($danhGia->so_lan_sua >= 1) {
            return response()->json(['message' => 'Bạn đã hết số lần sửa đổi đánh giá này (Tối đa 1 lần)'], 400);
        }

        DB::beginTransaction();
        try {
            $danhGia->update([
                'so_sao' => $validated['so_sao'],
                'nhan_xet' => $validated['nhan_xet'] ?? $danhGia->nhan_xet,
                'so_lan_sua' => $danhGia->so_lan_sua + 1
            ]);

            // Cập nhật lại điểm trung bình cho thợ
            $this->updateWorkerRating($danhGia->nguoi_bi_danh_gia_id);

            DB::commit();

            return response()->json([
                'message' => 'Cập nhật đánh giá thành công',
                'data' => $danhGia
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi hệ thống', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUBLIC: Get all reviews for a specific worker
     */
    public function indexByWorker(string $thoId)
    {
        $reviews = DanhGia::with(['nguoiDanhGia:id,name,avatar'])
            ->where('nguoi_bi_danh_gia_id', $thoId)
            ->latest()
            ->paginate(15);

        return response()->json($reviews);
    }

    /**
     * PUBLIC: Get a snapshot of a worker's rating
     */
    public function summary(string $thoId)
    {
        $hoSoTho = HoSoTho::where('user_id', $thoId)->first();

        if (!$hoSoTho) {
            return response()->json(['message' => 'Khônag tìm thấy hồ sơ thợ'], 404);
        }

        return response()->json([
            'tho_id' => $thoId,
            'danh_gia_trung_binh' => $hoSoTho->danh_gia_trung_binh,
            'tong_so_danh_gia' => $hoSoTho->tong_so_danh_gia
        ]);
    }

    /**
     * PRIVATE HELPER: Recalculate and update the worker's average rating
     */
    private function updateWorkerRating($thoId)
    {
        // Tính AVG và COUNT từ bảng danh_gia trực tiếp bằng SQL cho chính xác
        $stats = DB::table('danh_gia')
            ->where('nguoi_bi_danh_gia_id', $thoId)
            ->selectRaw('COUNT(id) as total, AVG(so_sao) as average')
            ->first();

        // Cập nhật vào hồ sơ thợ
        HoSoTho::where('user_id', $thoId)->update([
            'tong_so_danh_gia' => $stats->total ?? 0,
            'danh_gia_trung_binh' => $stats->average ? round($stats->average, 2) : 0
        ]);
    }
}
