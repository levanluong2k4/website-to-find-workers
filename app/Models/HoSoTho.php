<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoSoTho extends Model
{
    protected $table = 'ho_so_tho';

    protected $fillable = [
        'user_id',
        'cccd',
        'kinh_nghiem',
        'chung_chi',
        'bang_gia_tham_khao',
        'vi_do',
        'kinh_do',
        'ban_kinh_phuc_vu',
        'trang_thai_duyet',
        'ghi_chu_admin',
        'dang_hoat_dong',
        'trang_thai_hoat_dong',
        'danh_gia_trung_binh',
        'tong_so_danh_gia'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
