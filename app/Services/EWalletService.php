<?php

namespace App\Services;

use App\Models\ViDienTu;
use App\Models\LichSuGiaoDich;
use App\Models\AppSetting;
use Illuminate\Support\Facades\DB;
use Exception;

class EWalletService
{
    /**
     * Lấy giá trị cấu hình theo key
     */
    protected function getSetting($key, $default = 0)
    {
        $setting = AppSetting::where('key', $key)->first();
        return $setting ? (float) $setting->value : $default;
    }

    /**
     * Lấy hoặc tạo ví cho thợ
     */
    public function getWallet($ma_tho)
    {
        return ViDienTu::firstOrCreate(
            ['ma_tho' => $ma_tho],
            ['so_du' => 0, 'trang_thai' => 'hoat_dong']
        );
    }

    /**
     * Luồng 1: Validation lúc Báo Giá (Chặn nợ xấu)
     */
    public function validateBaoGia($ma_tho, $tien_cong, $tien_linh_kien)
    {
        $vi = $this->getWallet($ma_tho);

        $ty_le_thue = $this->getSetting('ty_le_thue_nha_nuoc', 10);
        $ty_le_phi = $this->getSetting('ty_le_phi_nen_tang', 20);

        $thue_du_kien = $tien_cong * ($ty_le_thue / 100);
        $phi_san_du_kien = $tien_cong * ($ty_le_phi / 100);
        
        $tong_thu_du_kien = $tien_linh_kien + $thue_du_kien + $phi_san_du_kien;

        if ($vi->so_du < $tong_thu_du_kien) {
            throw new Exception("Số dư ví không đủ để đảm bảo thanh toán tiền linh kiện và các khoản phí. Vui lòng nạp thêm tiền để gửi báo giá.");
        }

        return true;
    }

    /**
     * Luồng 2: Hoàn thành đơn hàng & Chia tiền chi tiết
     *
     * COD (tiền mặt):
     *   Thợ thu hộ toàn bộ tiền mặt từ khách → ví thợ bị trừ: thue + phi_nen_tang (+ tien_linh_kien nếu có)
     *
     * Transfer (chuyển khoản):
     *   Admin thu hộ tiền khách → ví thợ được cộng: tien_cong - thue - phi_nen_tang + phi_di_lai
     */
    public function processHoanThanhDonHang(
        int|string $ma_tho,
        int|string $ma_don_hang,
        float $tien_cong,
        float $tien_linh_kien,
        float $phi_di_lai = 0,
        bool $la_tien_mat = true
    ): bool {
        return DB::transaction(function () use ($ma_tho, $ma_don_hang, $tien_cong, $tien_linh_kien, $phi_di_lai, $la_tien_mat) {
            $vi = $this->getWallet($ma_tho);

            $ty_le_thue = $this->getSetting('ty_le_thue_nha_nuoc', 10);
            $ty_le_phi  = $this->getSetting('ty_le_phi_nen_tang', 20);

            $thue    = round($tien_cong * ($ty_le_thue / 100), 0);
            $phi_san = round($tien_cong * ($ty_le_phi  / 100), 0);

            if ($la_tien_mat) {
                // ── COD: thợ thu hộ toàn bộ tiền mặt ──────────────────────
                // Thợ phải nộp lại: linh kiện + thuế + phí nền tảng
                $tong_tru = $tien_linh_kien + $thue + $phi_san;
                $vi->so_du -= $tong_tru;
                $vi->save();

                // Lịch sử tổng hợp
                LichSuGiaoDich::create([
                    'ma_vi'          => $vi->id,
                    'so_tien'        => -$tong_tru,
                    'loai_giao_dich' => 'hoan_thanh_don',
                    'ma_don_hang'    => $ma_don_hang,
                    'trang_thai'     => 'thanh_cong',
                ]);

                if ($tien_linh_kien > 0) {
                    LichSuGiaoDich::create([
                        'ma_vi'          => $vi->id,
                        'so_tien'        => -$tien_linh_kien,
                        'loai_giao_dich' => 'tru_tien_linh_kien',
                        'ma_don_hang'    => $ma_don_hang,
                        'trang_thai'     => 'thanh_cong',
                    ]);
                }

                if ($thue > 0) {
                    LichSuGiaoDich::create([
                        'ma_vi'          => $vi->id,
                        'so_tien'        => -$thue,
                        'loai_giao_dich' => 'tru_thue_nha_nuoc',
                        'ma_don_hang'    => $ma_don_hang,
                        'trang_thai'     => 'thanh_cong',
                    ]);
                }

                if ($phi_san > 0) {
                    LichSuGiaoDich::create([
                        'ma_vi'          => $vi->id,
                        'so_tien'        => -$phi_san,
                        'loai_giao_dich' => 'tru_phi_nen_tang',
                        'ma_don_hang'    => $ma_don_hang,
                        'trang_thai'     => 'thanh_cong',
                    ]);
                }
            } else {
                // ── Chuyển khoản: admin thu hộ tiền khách ──────────────────
                // Thợ nhận: tiền công còn lại sau phí + phí đi lại
                $tien_cong_thuc = $tien_cong - $thue - $phi_san;
                $tong_cong      = $tien_cong_thuc + $phi_di_lai;

                $vi->so_du += $tong_cong;
                $vi->save();

                // Lịch sử tổng hợp
                LichSuGiaoDich::create([
                    'ma_vi'          => $vi->id,
                    'so_tien'        => $tong_cong,
                    'loai_giao_dich' => 'hoan_thanh_don',
                    'ma_don_hang'    => $ma_don_hang,
                    'trang_thai'     => 'thanh_cong',
                ]);

                // Chi tiết: tiền công gốc
                LichSuGiaoDich::create([
                    'ma_vi'          => $vi->id,
                    'so_tien'        => $tien_cong,
                    'loai_giao_dich' => 'nhan_doanh_thu_cong',
                    'ma_don_hang'    => $ma_don_hang,
                    'trang_thai'     => 'thanh_cong',
                ]);

                // Phí đi lại (nếu có)
                if ($phi_di_lai > 0) {
                    LichSuGiaoDich::create([
                        'ma_vi'          => $vi->id,
                        'so_tien'        => $phi_di_lai,
                        'loai_giao_dich' => 'nhan_phi_di_lai',
                        'ma_don_hang'    => $ma_don_hang,
                        'trang_thai'     => 'thanh_cong',
                    ]);
                }

                if ($thue > 0) {
                    LichSuGiaoDich::create([
                        'ma_vi'          => $vi->id,
                        'so_tien'        => -$thue,
                        'loai_giao_dich' => 'tru_thue_nha_nuoc',
                        'ma_don_hang'    => $ma_don_hang,
                        'trang_thai'     => 'thanh_cong',
                    ]);
                }

                if ($phi_san > 0) {
                    LichSuGiaoDich::create([
                        'ma_vi'          => $vi->id,
                        'so_tien'        => -$phi_san,
                        'loai_giao_dich' => 'tru_phi_nen_tang',
                        'ma_don_hang'    => $ma_don_hang,
                        'trang_thai'     => 'thanh_cong',
                    ]);
                }
            }

            // Hậu kiểm duy trì
            $so_du_duy_tri = $this->getSetting('so_du_duy_tri', 500000);
            if ($vi->so_du < $so_du_duy_tri) {
                $vi->trang_thai = 'cho_nap_tien';
                $vi->save();
            }

            return true;
        });
    }

    /**
     * Luồng 3: Rút tiền
     */
    public function processRutTien($ma_tho, $so_tien_yeu_cau)
    {
        return DB::transaction(function () use ($ma_tho, $so_tien_yeu_cau) {
            $vi = $this->getWallet($ma_tho);
            $so_du_duy_tri = $this->getSetting('so_du_duy_tri', 500000);

            $so_tien_kha_dung = max(0, $vi->so_du - $so_du_duy_tri);

            if ($so_tien_yeu_cau > $so_tien_kha_dung) {
                throw new Exception("Số tiền yêu cầu vượt quá số dư khả dụng (cần duy trì tối thiểu " . number_format($so_du_duy_tri) . "đ).");
            }

            $vi->so_du -= $so_tien_yeu_cau;
            
            if ($vi->so_du < $so_du_duy_tri && $vi->trang_thai !== 'cho_nap_tien') {
                $vi->trang_thai = 'cho_nap_tien';
            }
            $vi->save();

            LichSuGiaoDich::create([
                'ma_vi' => $vi->id,
                'so_tien' => -$so_tien_yeu_cau,
                'loai_giao_dich' => 'rut_tien',
                'ma_don_hang' => null
            ]);

            return true;
        });
    }
}
