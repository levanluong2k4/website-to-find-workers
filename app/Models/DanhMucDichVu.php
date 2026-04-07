<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function linhKiens()
    {
        return $this->hasMany(LinhKien::class, 'dich_vu_id');
    }

    public function trieuChungs()
    {
        return $this->hasMany(TrieuChung::class, 'dich_vu_id');
    }

    protected function hinhAnh(): Attribute
    {
        return Attribute::make(
            get: static function ($value) {
                $value = trim((string) $value);

                if ($value === '') {
                    return null;
                }

                if (Str::startsWith($value, ['http://', 'https://', 'data:'])) {
                    return $value;
                }

                if (Str::startsWith($value, '/storage/')) {
                    return asset(ltrim($value, '/'));
                }

                if (Str::startsWith($value, 'storage/')) {
                    return asset($value);
                }

                if (Str::contains($value, '/')) {
                    return asset(ltrim(Storage::url($value), '/'));
                }

                return null;
            },
        );
    }
}
