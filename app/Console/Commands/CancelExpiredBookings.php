<?php

namespace App\Console\Commands;

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
    protected $description = 'Hủy các đơn đặt lịch cụ thể thợ nhưng quá hạn (1 tiếng) mà không có ai nhận.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredBookingsCount = \App\Models\DonDatLich::whereNotNull('tho_id')
            ->where('trang_thai', 'cho_xac_nhan')
            ->where('thoi_gian_het_han_nhan', '<', now())
            ->update([
                'trang_thai' => 'da_huy',
                'ly_do_huy' => 'Hệ thống tự động hủy do thợ không nhận đơn trong thời gian quy định (1 tiếng).'
            ]);

        $this->info("Đã hủy thành công {$expiredBookingsCount} đơn đặt lịch hết hạn.");
    }
}
