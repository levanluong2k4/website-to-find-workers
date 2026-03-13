<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DanhGia\StoreDanhGiaRequest;
use App\Http\Requests\DanhGia\UpdateDanhGiaRequest;
use App\Models\DanhGia;
use App\Models\DonDatLich;
use App\Models\HoSoTho;
use Illuminate\Support\Facades\DB;

class DanhGiaController extends Controller
{
    public function store(StoreDanhGiaRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $booking = DonDatLich::find($validated['don_dat_lich_id']);

        if (!$booking) {
            return response()->json(['message' => 'Khong tim thay don dat lich'], 404);
        }

        if ($user->role !== 'admin' && $booking->khach_hang_id !== $user->id) {
            return response()->json(['message' => 'Ban khong co quyen danh gia don nay'], 403);
        }

        if ($booking->trang_thai !== 'da_xong') {
            return response()->json(['message' => 'Chi co the danh gia khi don da hoan thanh'], 400);
        }

        $daDanhGia = DanhGia::where('don_dat_lich_id', $booking->id)->exists();
        if ($daDanhGia) {
            return response()->json(['message' => 'Ban da gui danh gia cho don nay roi'], 400);
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

            $this->updateWorkerRating($booking->tho_id);
            DB::commit();

            return response()->json([
                'message' => 'Danh gia thanh cong',
                'data' => $danhGia,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Loi he thong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateDanhGiaRequest $request, string $id)
    {
        $validated = $request->validated();
        $user = $request->user();

        $danhGia = DanhGia::find($id);

        if (!$danhGia) {
            return response()->json(['message' => 'Khong tim thay danh gia'], 404);
        }

        if ($user->role !== 'admin' && $danhGia->nguoi_danh_gia_id !== $user->id) {
            return response()->json(['message' => 'Khong co quyen sua danh gia nay'], 403);
        }

        if ($danhGia->so_lan_sua >= 1) {
            return response()->json(['message' => 'Ban da het so lan sua doi danh gia nay (toi da 1 lan)'], 400);
        }

        DB::beginTransaction();

        try {
            $danhGia->update([
                'so_sao' => $validated['so_sao'],
                'nhan_xet' => $validated['nhan_xet'] ?? $danhGia->nhan_xet,
                'so_lan_sua' => $danhGia->so_lan_sua + 1,
            ]);

            $this->updateWorkerRating($danhGia->nguoi_bi_danh_gia_id);
            DB::commit();

            return response()->json([
                'message' => 'Cap nhat danh gia thanh cong',
                'data' => $danhGia,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Loi he thong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function indexByWorker(string $thoId)
    {
        return response()->json(
            DanhGia::with(['nguoiDanhGia:id,name,avatar'])
                ->where('nguoi_bi_danh_gia_id', $thoId)
                ->latest()
                ->paginate(15)
        );
    }

    public function summary(string $thoId)
    {
        $hoSoTho = HoSoTho::where('user_id', $thoId)->first();

        if (!$hoSoTho) {
            return response()->json(['message' => 'Khong tim thay ho so tho'], 404);
        }

        return response()->json([
            'tho_id' => $thoId,
            'danh_gia_trung_binh' => $hoSoTho->danh_gia_trung_binh,
            'tong_so_danh_gia' => $hoSoTho->tong_so_danh_gia,
        ]);
    }

    private function updateWorkerRating($thoId): void
    {
        $stats = DB::table('danh_gia')
            ->where('nguoi_bi_danh_gia_id', $thoId)
            ->selectRaw('COUNT(id) as total, AVG(so_sao) as average')
            ->first();

        HoSoTho::where('user_id', $thoId)->update([
            'tong_so_danh_gia' => $stats->total ?? 0,
            'danh_gia_trung_binh' => $stats->average ? round($stats->average, 2) : 0,
        ]);
    }
}
