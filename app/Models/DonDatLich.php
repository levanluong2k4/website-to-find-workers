<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DonDatLich extends Model
{
    public const CANCEL_REASON_DOI_Y_KHONG_MUON_DAT = 'doi_y_khong_muon_dat';
    public const CANCEL_REASON_THAY_DOI_THOI_GIAN_DAT = 'thay_doi_thoi_gian_dat';
    public const CANCEL_REASON_KHONG_CO_THO_NAO_NHAN = 'khong_co_tho_nao_nhan';
    public const CANCEL_REASON_CHO_QUA_LAU = 'cho_qua_lau';
    public const SCHEDULE_BLOCKING_STATUSES = [
        'cho_xac_nhan',
        'da_xac_nhan',
        'dang_lam',
        'cho_hoan_thanh',
        'cho_thanh_toan',
    ];

    protected $table = 'don_dat_lich';

    protected $fillable = [
        'khach_hang_id',
        'tho_id',
        'bai_dang_id',
        'loai_dat_lich',
        'thoi_gian_hen',
        'worker_reminder_sent_at',
        'thoi_gian_hoan_thanh',
        'ngay_hen',
        'khung_gio_hen',
        'so_lan_doi_lich',
        'dia_chi',
        'vi_do',
        'kinh_do',
        'mo_ta_van_de',
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
        'worker_reminder_sent_at' => 'datetime',
        'thoi_gian_hoan_thanh' => 'datetime',
        'ngay_hen' => 'date',
        'so_lan_doi_lich' => 'integer',
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

    public static function scheduleBlockingStatuses(): array
    {
        return self::SCHEDULE_BLOCKING_STATUSES;
    }

    public static function normalizeTimeSlot(?string $timeSlot): string
    {
        return preg_replace('/\s+/', '', (string) $timeSlot) ?? '';
    }

    public function scopeConflictsWithWorkerSchedule(Builder $query, int $workerId, string $date, string $timeSlot): Builder
    {
        return $query
            ->where('tho_id', $workerId)
            ->whereDate('ngay_hen', $date)
            ->whereRaw("REPLACE(khung_gio_hen, ' ', '') = ?", [self::normalizeTimeSlot($timeSlot)])
            ->whereIn('trang_thai', self::SCHEDULE_BLOCKING_STATUSES);
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
