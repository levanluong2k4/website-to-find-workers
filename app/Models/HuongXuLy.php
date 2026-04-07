<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HuongXuLy extends Model
{
    protected $table = 'huong_xu_ly';

    protected $fillable = [
        'nguyen_nhan_id',
        'ten_huong_xu_ly',
        'gia_tham_khao',
        'mo_ta_cong_viec',
    ];

    protected $casts = [
        'gia_tham_khao' => 'float',
    ];

    public function nguyenNhan()
    {
        return $this->belongsTo(NguyenNhan::class, 'nguyen_nhan_id');
    }
}
