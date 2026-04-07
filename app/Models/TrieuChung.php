<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrieuChung extends Model
{
    protected $table = 'trieu_chung';

    protected $fillable = [
        'dich_vu_id',
        'ten_trieu_chung',
    ];

    public function dichVu()
    {
        return $this->belongsTo(DanhMucDichVu::class, 'dich_vu_id');
    }

    public function nguyenNhans()
    {
        return $this->belongsToMany(NguyenNhan::class, 'trieu_chung_nguyen_nhan', 'trieu_chung_id', 'nguyen_nhan_id')
            ->withTimestamps();
    }
}
