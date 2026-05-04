<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LichSuGiaoDich extends Model
{
    protected $table = 'lich_su_giao_dichs';

    protected $fillable = [
        'ma_vi', 
        'so_tien', 
        'loai_giao_dich', 
        'ma_don_hang',
        'trang_thai'
    ];

    public function viDienTu()
    {
        return $this->belongsTo(ViDienTu::class, 'ma_vi');
    }

    public function donHang()
    {
        return $this->belongsTo(DonDatLich::class, 'ma_don_hang');
    }
}
