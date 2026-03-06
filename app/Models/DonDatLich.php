<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonDatLich extends Model
{
    protected $table = 'don_dat_lich';

    protected $fillable = [
        'khach_hang_id',
        'tho_id',
        'dich_vu_id',
        'bai_dang_id',
        'loai_dat_lich',
        'thoi_gian_hen',
        'ngay_hen',
        'khung_gio_hen',
        'dia_chi',
        'vi_do',
        'kinh_do',
        'mo_ta_van_de',
        'khoang_cach',
        'phi_di_lai',
        'phi_linh_kien',
        'ghi_chu_linh_kien',
        'thoi_gian_het_han_nhan',
        'trang_thai',
        'ly_do_huy',
        'tong_tien',
        'phuong_thuc_thanh_toan',
        'trang_thai_thanh_toan'
    ];

    protected $casts = [
        'thoi_gian_hen' => 'datetime',
        'ngay_hen' => 'date',
        'thoi_gian_het_han_nhan' => 'datetime',
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
