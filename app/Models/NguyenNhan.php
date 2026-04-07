<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NguyenNhan extends Model
{
    protected $table = 'nguyen_nhan';

    protected $fillable = [
        'ten_nguyen_nhan',
    ];

    public function trieuChungs()
    {
        return $this->belongsToMany(TrieuChung::class, 'trieu_chung_nguyen_nhan', 'nguyen_nhan_id', 'trieu_chung_id')
            ->withTimestamps();
    }

    public function huongXuLys()
    {
        return $this->hasMany(HuongXuLy::class, 'nguyen_nhan_id');
    }
}
