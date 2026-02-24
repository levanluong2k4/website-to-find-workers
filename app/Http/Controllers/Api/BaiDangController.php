<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\BaiDang\StoreBaiDangRequest;
use App\Http\Requests\BaiDang\UpdateBaiDangRequest;
use App\Models\BaiDang;
use App\Models\HinhAnhBaiDang;

class BaiDangController extends Controller
{
    /**
     * Display a listing of the resource (Public for workers to see).
     */
    public function index(Request $request)
    {
        $query = BaiDang::with(['user:id,name,avatar', 'dichVu:id,ten_dich_vu', 'hinhAnhs'])
            ->where('trang_thai', 'dang_mo');

        // Optional: Lọc theo danh mục dịch vụ
        if ($request->has('dich_vu_id')) {
            $query->where('dich_vu_id', $request->dich_vu_id);
        }

        // Tương lai: Thêm logic tính toán khoảng cách lat/lng ở đây

        return response()->json($query->latest()->paginate(15));
    }

    /**
     * Display posts belong to logged-in user.
     */
    public function myPosts(Request $request)
    {
        $user = $request->user();
        $posts = BaiDang::with(['dichVu:id,ten_dich_vu', 'hinhAnhs'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return response()->json($posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBaiDangRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $baiDang = BaiDang::create([
            'user_id' => $user->id,
            'dich_vu_id' => $validated['dich_vu_id'],
            'tieu_de' => $validated['tieu_de'],
            'mo_ta_chi_tiet' => $validated['mo_ta_chi_tiet'],
            'muc_gia_du_kien' => $validated['muc_gia_du_kien'],
            'dia_chi' => $validated['dia_chi'],
            'vi_do' => $validated['vi_do'],
            'kinh_do' => $validated['kinh_do'],
            'trang_thai' => 'dang_mo',
        ]);

        if (isset($validated['hinh_anhs']) && is_array($validated['hinh_anhs'])) {
            foreach ($validated['hinh_anhs'] as $hinhanh) {
                HinhAnhBaiDang::create([
                    'bai_dang_id' => $baiDang->id,
                    'url_hinh_anh' => $hinhanh
                ]);
            }
        }

        $baiDang->load('hinhAnhs');

        return response()->json([
            'message' => 'Đăng bài thành công',
            'data' => $baiDang
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $baiDang = BaiDang::with(['user:id,name,avatar,phone,address', 'dichVu', 'hinhAnhs'])
            ->find($id);

        if (!$baiDang) {
            return response()->json(['message' => 'Không tìm thấy bài đăng'], 404);
        }

        return response()->json($baiDang);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBaiDangRequest $request, string $id)
    {
        $baiDang = BaiDang::find($id);

        if (!$baiDang) {
            return response()->json(['message' => 'Không tìm thấy bài đăng'], 404);
        }

        // Chỉ chủ bài đăng mới được sửa
        if ($baiDang->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Không có quyền sửa bài đăng này'], 403);
        }

        if ($baiDang->trang_thai !== 'dang_mo') {
            return response()->json(['message' => 'Chỉ có thể sửa bài đăng khi đang chờ thợ'], 400);
        }

        $validated = $request->validated();
        $baiDang->update($validated);

        // Update hình ảnh: Cách đơn giản nhất là xóa cũ tạo mới nếu có gửi hình mảng lên
        if (isset($validated['hinh_anhs']) && is_array($validated['hinh_anhs'])) {
            HinhAnhBaiDang::where('bai_dang_id', $baiDang->id)->delete();
            foreach ($validated['hinh_anhs'] as $hinhanh) {
                HinhAnhBaiDang::create([
                    'bai_dang_id' => $baiDang->id,
                    'url_hinh_anh' => $hinhanh
                ]);
            }
        }

        $baiDang->load('hinhAnhs');

        return response()->json([
            'message' => 'Cập nhật bài đăng thành công',
            'data' => $baiDang
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $baiDang = BaiDang::find($id);

        if (!$baiDang) {
            return response()->json(['message' => 'Không tìm thấy bài đăng'], 404);
        }

        if ($baiDang->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Không có quyền xóa bài đăng này'], 403);
        }

        if ($baiDang->trang_thai !== 'dang_mo') {
            return response()->json(['message' => 'Không thể xóa bài đăng đã có người nhận hoặc hoàn thành'], 400);
        }

        $baiDang->update(['trang_thai' => 'da_huy']);

        return response()->json(['message' => 'Bài đăng đã được hủy mềm']);
    }
}
