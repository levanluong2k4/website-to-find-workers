<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LichSuGiaoDich;

$history = LichSuGiaoDich::orderBy('id', 'desc')->limit(5)->get();
foreach ($history as $item) {
    echo "ID: {$item->id} | Wallet: {$item->ma_vi} | Amount: {$item->so_tien} | Type: {$item->loai_giao_dich} | Status: {$item->trang_thai} | Order: {$item->ma_don_hang} | Date: {$item->created_at}\n";
}
echo "Total history records: " . LichSuGiaoDich::count() . "\n";
