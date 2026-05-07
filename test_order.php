<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = App\Models\DonDatLich::find(4);
echo 'Order 4: tong_tien: ' . $order->tong_tien . ' - tien_cong: ' . $order->tien_cong . "\n";

// Let's also check resolveBookingTotal
$controller = new App\Http\Controllers\Api\DonDatLichController();
$reflection = new \ReflectionMethod($controller, 'resolveBookingTotal');
$reflection->setAccessible(true);
$total = $reflection->invoke($controller, $order);
echo 'Resolved Total: ' . $total . "\n";
