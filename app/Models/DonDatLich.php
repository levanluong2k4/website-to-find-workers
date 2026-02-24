<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonDatLich extends Model
{
    protected $table = 'don_dat_lich';

    protected $fillable = [
        'khach_hang_id', 'tho_id', 'dich_vu_id', 'bai_dang_id',
        'thoi_gian_hen', 'dia_chi', 'vi_do', 'kinh_do', 'mo_ta_van_de',
        'trang_thai', 'ly_do_huy', 'tong_tien', 'phuong_thuc_thanh_toan', 'trang_thai_thanh_toan'
    ];

    protected $casts = [
        'thoi_gian_hen' => 'datetime',
        'trang_thai_thanh_toan' => 'boolean',
    ];

    public function khachHang()
    {
        return $this->belongsTo(User::class, 'khach_hang_id');
    }

    public function tho()
    {
        return $this->belongsTo(User::class, 'tho_id');
    }

    public function dichVu()
    {
        return $this->belongsTo(DanhMucDichVu::class, 'dich_vu_id');
    }

    public function baiDang()
    {
        return $this->belongsTo(BaiDang::class, 'bai_dang_id');
    }

    public function danhGias()
    {
        return $this->hasMany(DanhGia::class, 'don_dat_lich_id');
    }
}
