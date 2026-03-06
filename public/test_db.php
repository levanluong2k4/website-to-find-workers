<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$count = \App\Models\HoSoTho::count();
echo "Total HoSoTho: " . $count . "\n";
echo "Active HoSoTho: " . \App\Models\HoSoTho::where('trang_thai_duyet', 'da_duyet')->where('dang_hoat_dong', true)->count() . "\n";
