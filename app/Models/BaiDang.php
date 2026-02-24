<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaiDang extends Model
{
    protected $table = 'bai_dang';

    protected $fillable = [
        'user_id', 'dich_vu_id', 'tieu_de', 'mo_ta_chi_tiet', 'dia_chi',
        'vi_do', 'kinh_do', 'trang_thai'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dichVu()
    {
        return $this->belongsTo(DanhMucDichVu::class, 'dich_vu_id');
    }

    public function hinhAnhs()
    {
        return $this->hasMany(HinhAnhBaiDang::class, 'bai_dang_id');
    }

    public function baoGias()
    {
        return $this->hasMany(BaoGia::class, 'bai_dang_id');
    }

    public function donDatLich()
    {
        return $this->hasOne(DonDatLich::class, 'bai_dang_id');
    }
}
