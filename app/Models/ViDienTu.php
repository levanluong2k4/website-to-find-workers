<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViDienTu extends Model
{
    protected $table = 'vi_dien_tus';

    protected $fillable = ['ma_tho', 'so_du', 'trang_thai'];

    public function tho()
    {
        return $this->belongsTo(User::class, 'ma_tho');
    }

    public function lichSuGiaoDichs()
    {
        return $this->hasMany(LichSuGiaoDich::class, 'ma_vi');
    }
}
