<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LichSuGiaoDich;
use App\Models\ViDienTu;
use Carbon\Carbon;

// Find all hoan_thanh_don transactions created today
$badTxs = LichSuGiaoDich::where('loai_giao_dich', 'hoan_thanh_don')
    ->whereDate('created_at', Carbon::today())
    ->get();

echo "Found " . $badTxs->count() . " bad transactions.\n";

$orderIds = $badTxs->pluck('ma_don_hang')->unique()->toArray();
echo "Order IDs: " . implode(', ', $orderIds) . "\n";

if (!empty($orderIds)) {
    // Delete all transactions related to these orders created today
    $deleted = LichSuGiaoDich::whereIn('ma_don_hang', $orderIds)
        ->whereDate('created_at', Carbon::today())
        ->delete();
    echo "Deleted " . $deleted . " related transactions.\n";
}

// Recalculate wallet balances
$wallets = ViDienTu::all();
foreach ($wallets as $wallet) {
    $realBalance = LichSuGiaoDich::where('ma_vi', $wallet->id)
        ->where('trang_thai', 'thanh_cong')
        ->sum('so_tien');
    $wallet->so_du = $realBalance;
    $wallet->save();
}
echo "Recalculated wallet balances.\n";
