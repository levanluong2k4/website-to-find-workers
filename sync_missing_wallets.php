<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DonDatLich;
use App\Models\LichSuGiaoDich;
use App\Models\ThanhToan;
use App\Services\EWalletService;
use Illuminate\Support\Facades\Log;

$service = new EWalletService();

$completedBookings = DonDatLich::whereIn('trang_thai', ['hoan_thanh', 'da_xong'])->get();
$count = 0;

foreach ($completedBookings as $booking) {
    if (!$booking->tho_id) continue;

    // Kiem tra xem da co lich su giao dich hoan_thanh_don cho don nay chua
    $hasTransaction = LichSuGiaoDich::where('ma_don_hang', $booking->id)
        ->where('loai_giao_dich', 'hoan_thanh_don')
        ->exists();

    if (!$hasTransaction) {
        // Xac dinh phuong thuc thanh toan tu bang ThanhToan neu co
        $thanhToan = ThanhToan::where('don_dat_lich_id', $booking->id)
            ->where('trang_thai', 'success')
            ->first();
            
        // Mac dinh la COD (tien mat) tru khi thanh toan online (vnpay, momo, bank_transfer, chuyen_khoan)
        $is_cod = true;
        if ($booking->phuong_thuc_thanh_toan === 'transfer' || $booking->phuong_thuc_thanh_toan === 'vnpay' || $booking->phuong_thuc_thanh_toan === 'momo') {
            $is_cod = false;
        } elseif ($thanhToan && in_array(strtolower($thanhToan->phuong_thuc), ['vnpay', 'momo', 'bank_transfer', 'chuyen_khoan'])) {
            $is_cod = false;
        }

        // Tinh toan tien cong
        $tien_cong = 0;
        if (is_array($booking->chi_tiet_tien_cong)) {
            $tien_cong = collect($booking->chi_tiet_tien_cong)->sum('so_tien');
        } elseif (isset($booking->tien_cong)) {
            $tien_cong = (float) $booking->tien_cong;
        }

        $tien_linh_kien = (float) ($booking->phi_linh_kien ?? 0);
        $phi_di_lai = (float) ($booking->phi_di_lai ?? 0);

        try {
            $service->processHoanThanhDonHang(
                $booking->tho_id,
                $booking->id,
                $tien_cong,
                $tien_linh_kien,
                $phi_di_lai,
                $is_cod
            );
            $count++;
            echo "Da dong bo vi cho Don ID: {$booking->id} | Tho ID: {$booking->tho_id} | Loai: " . ($is_cod ? 'Tien mat (COD)' : 'Chuyen khoan') . " | Tien cong: {$tien_cong}\n";
        } catch (\Exception $e) {
            echo "Loi khi dong bo Don ID: {$booking->id} - " . $e->getMessage() . "\n";
        }
    }
}

echo "Tong cong da dong bo: {$count} don hang.\n";
