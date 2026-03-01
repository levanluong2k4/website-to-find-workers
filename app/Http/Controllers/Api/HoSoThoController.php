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
        // Khởi tạo query Builder
        $query = HoSoTho::with('user:id,name,email,avatar,phone,address', 'user.dichVus:id,ten_dich_vu')
            ->where('trang_thai_duyet', 'da_duyet')
            ->where('dang_hoat_dong', true);

        // 1. Tích hợp Search Keyword (?q=...)
        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhereHas('dichVus', function ($q2) use ($keyword) {
                        $q2->where('ten_dich_vu', 'LIKE', "%{$keyword}%");
                    });
            });
        }

        // 2. Tích hợp Lọc Danh mục (?category_id=...)
        if ($request->filled('category_id')) {
            $categoryId = $request->category_id;
            $query->whereHas('user.dichVus', function ($q) use ($categoryId) {
                $q->where('dich_vu_id', $categoryId);
            });
        }

        // 3. Tích hợp Lọc Vị trí theo Tỉnh Thành (?province=...)
        if ($request->filled('province')) {
            $province = $request->province;
            $query->whereHas('user', function ($q) use ($province) {
                $q->where('address', 'LIKE', "%{$province}%");
            });
        }

        // 4. Ưu tiên Geolocation: Nếu cấp vĩ độ/kinh độ, tính khoảng cách (?lat=...&lng=...)
        $latitude = $request->lat;
        $longitude = $request->lng;

        if ($latitude && $longitude) {
            // Công thức Haversine để tính khoảng cách vật lý (theo km)
            $haversine = "(6371 * acos(cos(radians($latitude)) * cos(radians(vi_do)) * cos(radians(kinh_do) - radians($longitude)) + sin(radians($latitude)) * sin(radians(vi_do))))";

            $query->selectRaw("*, {$haversine} AS distance");

            // Có thể filter những thợ ở quá xa (ví dụ: > 50km thì loại)
            // $query->whereRaw("{$haversine} <= ban_kinh_phuc_vu"); 
        }

        // 5. Tích hợp Sắp xếp Ưu tiên phân tầng (?sort=...)
        // Mặc định luôn ưu tiên Trạng thái hoạt động trước:
        // dang_hoat_dong (1) -> dang_ban (2) -> ngung_hoat_dong (3) -> tam_khoa (4)
        $query->orderByRaw("
            CASE trang_thai_hoat_dong
                WHEN 'dang_hoat_dong' THEN 1
                WHEN 'dang_ban' THEN 2
                WHEN 'ngung_hoat_dong' THEN 3
                WHEN 'tam_khoa' THEN 4
                ELSE 5
            END ASC
        ");

        if ($request->sort === 'nearest' && $latitude && $longitude) {
            // Ưu tiên khoảng cách sau ưu tiên trạng thái
            $query->orderBy('distance', 'ASC');
        } elseif ($request->sort === 'rating') {
            $query->orderBy('danh_gia_trung_binh', 'DESC');
            $query->orderBy('tong_so_danh_gia', 'DESC');
        } else {
            // Mặc định (hoặc sort=jobs) ưu tiên điểm số & mức độ hoạt động
            $query->orderBy('tong_so_danh_gia', 'DESC');
            $query->orderBy('danh_gia_trung_binh', 'DESC');
        }

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
