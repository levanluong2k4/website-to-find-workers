<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThanhToan extends Model
{
    use HasFactory;

    protected $table = 'thanh_toan';

    protected $fillable = [
        'don_dat_lich_id',
        'so_tien',
        'phuong_thuc',
        'ma_giao_dich',
        'trang_thai',
        'thong_tin_extra',
    ];

    protected $casts = [
        'so_tien' => 'decimal:2',
        'thong_tin_extra' => 'array',
    ];

    public function donDatLich()
    {
        return $this->belongsTo(DonDatLich::class, 'don_dat_lich_id');
    }
}
