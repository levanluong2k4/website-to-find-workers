<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DanhGia extends Model
{
    protected $table = 'danh_gia';

    protected $fillable = [
        'don_dat_lich_id',
        'nguoi_danh_gia_id',
        'nguoi_bi_danh_gia_id',
        'so_sao',
        'nhan_xet',
        'hinh_anh_danh_gia',
        'video_danh_gia',
        'so_lan_sua',
        'chuyen_mon',
        'thai_do',
        'dung_gio',
        'gia_ca'
    ];

    protected $casts = [
        'hinh_anh_danh_gia' => 'array',
    ];

    public function donDatLich()
    {
        return $this->belongsTo(DonDatLich::class, 'don_dat_lich_id');
    }

    public function nguoiDanhGia()
    {
        return $this->belongsTo(User::class, 'nguoi_danh_gia_id');
    }

    public function nguoiBiDanhGia()
    {
        return $this->belongsTo(User::class, 'nguoi_bi_danh_gia_id');
    }
}
