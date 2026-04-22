<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LinhKien extends Model
{
    protected $table = 'linh_kien';

    protected $fillable = [
        'dich_vu_id',
        'ten_linh_kien',
        'hinh_anh',
        'gia',
        'so_luong_ton_kho',
        'han_su_dung',
    ];

    protected $casts = [
        'gia' => 'float',
        'so_luong_ton_kho' => 'integer',
        'han_su_dung' => 'date:Y-m-d',
    ];

    public function dichVu()
    {
        return $this->belongsTo(DanhMucDichVu::class, 'dich_vu_id');
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
