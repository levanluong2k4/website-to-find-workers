<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DanhMucDichVu extends Model
{
    protected $table = 'danh_muc_dich_vu';

    protected $fillable = [
        'ten_dich_vu', 'mo_ta', 'hinh_anh', 'trang_thai'
    ];

    public function baiDangs()
    {
        return $this->hasMany(BaiDang::class, 'dich_vu_id');
    }

    public function thos()
    {
        return $this->belongsToMany(User::class, 'tho_dich_vu', 'dich_vu_id', 'user_id');
    }
}
