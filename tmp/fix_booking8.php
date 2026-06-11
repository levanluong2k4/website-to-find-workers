<?php

use Illuminate\Support\Facades\DB;

// Booking #8: cho_hoan_thanh -> da_xong, mark paid, set completion time
DB::table('don_dat_lich')
    ->where('id', 8)
    ->update([
        'trang_thai'            => 'da_xong',
        'trang_thai_thanh_toan' => true,
        'thoi_gian_hoan_thanh'  => '2026-03-09 14:15:00',
    ]);

$row = DB::table('don_dat_lich')->where('id', 8)->first();
echo "OK => trang_thai={$row->trang_thai} | thoi_gian_hoan_thanh={$row->thoi_gian_hoan_thanh} | thanh_toan={$row->trang_thai_thanh_toan}\n";
