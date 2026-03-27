<?php

namespace App\Console\Commands;

use App\Models\DonDatLich;
use Illuminate\Console\Command;

class CancelExpiredBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cancel-expired-bookings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tu dong huy cac don cho xac nhan khi da cho qua lau hoac qua lich hen ma chua co tho nhan.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $waitingTooLongCount = DonDatLich::query()
            ->where('trang_thai', 'cho_xac_nhan')
            ->whereNotNull('thoi_gian_het_han_nhan')
            ->where('thoi_gian_het_han_nhan', '<', now())
            ->update([
                'trang_thai' => 'da_huy',
                'ma_ly_do_huy' => DonDatLich::CANCEL_REASON_CHO_QUA_LAU,
                'ly_do_huy' => DonDatLich::cancelReasonLabel(DonDatLich::CANCEL_REASON_CHO_QUA_LAU),
                'updated_at' => now(),
            ]);

        $unclaimedExpiredCount = DonDatLich::query()
            ->where('trang_thai', 'cho_xac_nhan')
            ->whereNotNull('thoi_gian_hen')
            ->where('thoi_gian_hen', '<', now())
            ->update([
                'trang_thai' => 'da_huy',
                'ma_ly_do_huy' => DonDatLich::CANCEL_REASON_KHONG_CO_THO_NAO_NHAN,
                'ly_do_huy' => DonDatLich::cancelReasonLabel(DonDatLich::CANCEL_REASON_KHONG_CO_THO_NAO_NHAN),
                'updated_at' => now(),
            ]);

        $totalCancelled = $waitingTooLongCount + $unclaimedExpiredCount;

        $this->info("Da tu dong huy {$totalCancelled} don: {$waitingTooLongCount} don cho qua lau, {$unclaimedExpiredCount} don qua lich nhung chua co tho nhan.");

        return self::SUCCESS;
    }
}
