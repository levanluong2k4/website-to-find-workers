<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'avatar',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function hoSoTho()
    {
        return $this->hasOne(HoSoTho::class);
    }

    public function baiDangs()
    {
        return $this->hasMany(BaiDang::class);
    }

    public function donDatLichAsKhach()
    {
        return $this->hasMany(DonDatLich::class, 'khach_hang_id');
    }

    public function donDatLichAsTho()
    {
        return $this->hasMany(DonDatLich::class, 'tho_id');
    }

    public function dichVus()
    {
        return $this->belongsToMany(DanhMucDichVu::class, 'tho_dich_vu', 'user_id', 'dich_vu_id');
    }

    public function danhGiasNhan()
    {
        return $this->hasMany(DanhGia::class, 'nguoi_bi_danh_gia_id');
    }

    public function danhGiasDaGui()
    {
        return $this->hasMany(DanhGia::class, 'nguoi_danh_gia_id');
    }
}
