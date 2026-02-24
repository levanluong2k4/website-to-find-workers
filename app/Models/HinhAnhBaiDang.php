<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HinhAnhBaiDang extends Model
{
    protected $table = 'hinh_anh_bai_dang';

    protected $fillable = [
        'bai_dang_id', 'url_hinh_anh'
    ];

    public function baiDang()
    {
        return $this->belongsTo(BaiDang::class, 'bai_dang_id');
    }
}
