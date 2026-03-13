<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumnListing('don_dat_lich');
$output = "Columns in don_dat_lich:\n" . print_r($columns, true);

if (in_array('hinh_anh_mo_ta', $columns) && in_array('video_mo_ta', $columns)) {
    $output .= "\nSUCCESS: Media columns found!\n";
} else {
    $output .= "\nFAILURE: Media columns NOT found.\n";
}

file_put_contents('results.txt', $output);
