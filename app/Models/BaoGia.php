<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaoGia extends Model
{
    protected $table = 'bao_gia';

    protected $fillable = [
        'bai_dang_id', 'tho_id', 'muc_gia', 'ghi_chu', 'trang_thai'
    ];

    public function baiDang()
    {
        return $this->belongsTo(BaiDang::class, 'bai_dang_id');
    }

    public function tho()
    {
        return $this->belongsTo(User::class, 'tho_id');
    }
}
