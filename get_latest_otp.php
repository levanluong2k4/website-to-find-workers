<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$otp = App\Models\OtpCode::orderBy('id', 'desc')->first();

if ($otp) {
    echo "LATEST_OTP: " . $otp->code . " for email: " . $otp->email . "\n";
} else {
    echo "NO_OTP_FOUND\n";
}
