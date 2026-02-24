<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\DanhMucDichVu\StoreDanhMucDichVuRequest;
use App\Http\Requests\DanhMucDichVu\UpdateDanhMucDichVuRequest;
use App\Models\DanhMucDichVu;

class DanhMucDichVuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $danhMuc = DanhMucDichVu::where('trang_thai', 1)->get();
        return response()->json($danhMuc);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDanhMucDichVuRequest $request)
    {
        $validated = $request->validated();

        $danhMuc = DanhMucDichVu::create([
            'ten_dich_vu' => $request->ten_dich_vu,
            'mo_ta' => $request->mo_ta,
            'hinh_anh' => $request->hinh_anh,
            'trang_thai' => 1 // Mặc định là true (hoạt động)
        ]);

        return response()->json([
            'message' => 'Tạo danh mục thành công',
            'data' => $danhMuc
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $danhMuc = DanhMucDichVu::find($id);
        
        if (!$danhMuc) {
            return response()->json(['message' => 'Không tìm thấy danh mục'], 404);
        }

        return response()->json($danhMuc);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDanhMucDichVuRequest $request, string $id)
    {
        $danhMuc = DanhMucDichVu::find($id);

        if (!$danhMuc) {
            return response()->json(['message' => 'Không tìm thấy danh mục'], 404);
        }

        $validated = $request->validated();

        $danhMuc->update($request->only(['ten_dich_vu', 'mo_ta', 'hinh_anh', 'trang_thai']));

        return response()->json([
            'message' => 'Cập nhật danh mục thành công',
            'data' => $danhMuc
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $danhMuc = DanhMucDichVu::find($id);

        if (!$danhMuc) {
            return response()->json(['message' => 'Không tìm thấy danh mục'], 404);
        }

        // Soft delete bằng cách đổi trạng thái thay vì xóa cứng (0: ngừng hoạt động)
        $danhMuc->update(['trang_thai' => 0]);

        return response()->json(['message' => 'Đã ngừng hoạt động danh mục này']);
    }
}
