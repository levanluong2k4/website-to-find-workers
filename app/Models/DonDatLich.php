<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonDatLich extends Model
{
    public const CANCEL_REASON_DOI_Y_KHONG_MUON_DAT = 'doi_y_khong_muon_dat';
    public const CANCEL_REASON_THAY_DOI_THOI_GIAN_DAT = 'thay_doi_thoi_gian_dat';
    public const CANCEL_REASON_KHONG_CO_THO_NAO_NHAN = 'khong_co_tho_nao_nhan';
    public const CANCEL_REASON_CHO_QUA_LAU = 'cho_qua_lau';

    protected $table = 'don_dat_lich';

    protected $fillable = [
        'khach_hang_id',
        'tho_id',
        'bai_dang_id',
        'loai_dat_lich',
        'thoi_gian_hen',
        'thoi_gian_hoan_thanh',
        'ngay_hen',
        'khung_gio_hen',
        'dia_chi',
        'vi_do',
        'kinh_do',
        'mo_ta_van_de',
        'nguyen_nhan',
        'giai_phap',
        'khoang_cach',
        'phi_di_lai',
        'phi_linh_kien',
        'ghi_chu_linh_kien',
        'chi_tiet_tien_cong',
        'chi_tiet_linh_kien',
        'thoi_gian_het_han_nhan',
        'trang_thai',
        'ma_ly_do_huy',
        'ly_do_huy',
        'tong_tien',
        'gia_da_cap_nhat',
        'phuong_thuc_thanh_toan',
        'trang_thai_thanh_toan',
        'hinh_anh_mo_ta',
        'video_mo_ta',
        'hinh_anh_ket_qua',
        'video_ket_qua'
    ];

    protected $casts = [
        'thoi_gian_hen' => 'datetime',
        'thoi_gian_hoan_thanh' => 'datetime',
        'ngay_hen' => 'date',
        'thoi_gian_het_han_nhan' => 'datetime',
        'gia_da_cap_nhat' => 'boolean',
        'trang_thai_thanh_toan' => 'boolean',
        'chi_tiet_tien_cong' => 'array',
        'chi_tiet_linh_kien' => 'array',
        'hinh_anh_mo_ta' => 'array',
        'hinh_anh_ket_qua' => 'array',
    ];

    public function khachHang()
    {
        return $this->belongsTo(User::class, 'khach_hang_id');
    }

    public function tho()
    {
        return $this->belongsTo(User::class, 'tho_id');
    }

    public function dichVus()
    {
        return $this->belongsToMany(DanhMucDichVu::class, 'don_dat_lich_dich_vu', 'don_dat_lich_id', 'dich_vu_id')
            ->withTimestamps();
    }

    public function danhGias()
    {
        return $this->hasMany(DanhGia::class, 'don_dat_lich_id');
    }

    public function thanhToans()
    {
        return $this->hasMany(ThanhToan::class, 'don_dat_lich_id')->latest();
    }

    public static function cancelReasonLabels(): array
    {
        return [
            self::CANCEL_REASON_DOI_Y_KHONG_MUON_DAT => 'Đổi ý không muốn đặt',
            self::CANCEL_REASON_THAY_DOI_THOI_GIAN_DAT => 'Thay đổi thời gian đặt',
            self::CANCEL_REASON_KHONG_CO_THO_NAO_NHAN => 'Không có thợ nào nhận',
            self::CANCEL_REASON_CHO_QUA_LAU => 'Chờ quá lâu',
        ];
    }

    public static function cancelReasonCodes(): array
    {
        return array_keys(self::cancelReasonLabels());
    }

    public static function cancelReasonLabel(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        return self::cancelReasonLabels()[$code] ?? null;
    }
}
