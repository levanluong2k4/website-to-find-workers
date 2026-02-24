<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HoSoTho;
use App\Http\Requests\HoSoTho\UpdateHoSoThoRequest;

class HoSoThoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Có thể thêm filter theo dịch vụ sau này
        $query = HoSoTho::with('user:id,name,email,avatar,phone,address')
            ->where('trang_thai_duyet', 'da_duyet')
            ->where('dang_hoat_dong', true);

        return response()->json($query->paginate(15));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Thay vì dùng HoSoTho ID, ta lấy theo User ID để URL thân thiện hơn (vd: /api/ho-so-tho/2)
        $hoSo = HoSoTho::with(['user:id,name,email,avatar,phone,address', 'user.dichVus'])
            ->where('user_id', $id)
            ->first();

        if (!$hoSo) {
            return response()->json(['message' => 'Không tìm thấy hồ sơ thợ'], 404);
        }

        return response()->json($hoSo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHoSoThoRequest $request)
    {
        $user = $request->user();

        // Vì user->role == 'worker' đã check ở FormRequest, chắc chắn user có hoSoTho
        $hoSo = $user->hoSoTho;

        if (!$hoSo) {
            return response()->json(['message' => 'Không tìm thấy hồ sơ thợ'], 404);
        }

        $validated = $request->validated();

        $hoSo->update([
            'cccd' => $validated['cccd'] ?? $hoSo->cccd,
            'kinh_nghiem' => $validated['kinh_nghiem'] ?? $hoSo->kinh_nghiem,
            'chung_chi' => $validated['chung_chi'] ?? $hoSo->chung_chi,
            'bang_gia_tham_khao' => $validated['bang_gia_tham_khao'] ?? $hoSo->bang_gia_tham_khao,
            'vi_do' => $validated['vi_do'] ?? $hoSo->vi_do,
            'kinh_do' => $validated['kinh_do'] ?? $hoSo->kinh_do,
            'ban_kinh_phuc_vu' => $validated['ban_kinh_phuc_vu'] ?? $hoSo->ban_kinh_phuc_vu,
            'dang_hoat_dong' => $validated['dang_hoat_dong'] ?? $hoSo->dang_hoat_dong,
        ]);

        // Cập nhật Danh mục dịch vụ cho thợ (Pivot table tho_dich_vu)
        if (isset($validated['dich_vu_ids'])) {
            $user->dichVus()->sync($validated['dich_vu_ids']);
        }

        // Load lại relationship để trả về
        $hoSo->load('user.dichVus');

        return response()->json([
            'message' => 'Cập nhật hồ sơ thành công',
            'data' => $hoSo
        ]);
    }
}
