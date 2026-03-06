<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "1. Getting don_dat_lich schema to find actual FK name...\n";
    $schema = DB::select('SHOW CREATE TABLE don_dat_lich')[0]->{'Create Table'};
    echo $schema . "\n";

    echo "\n2. Looking for bai_dang_id foreign key...\n";
    if (preg_match('/CONSTRAINT `([^`]+)` FOREIGN KEY \(`bai_dang_id`\)/', $schema, $matches)) {
        $fkName = $matches[1];
        echo "Found FK: $fkName\n";

        echo "3. Dropping FK $fkName...\n";
        DB::statement("ALTER TABLE don_dat_lich DROP FOREIGN KEY `$fkName`");
        echo "FK Dropped successfully.\n";
    } else {
        echo "No foreign key found for bai_dang_id.\n";
    }

    echo "4. Dropping bai_dang_id column...\n";
    try {
        DB::statement('ALTER TABLE don_dat_lich DROP COLUMN bai_dang_id');
        echo "Column Dropped successfully.\n";
    } catch (\Exception $e) {
        echo "Column error (maybe already dropped): " . $e->getMessage() . "\n";
    }

    echo "5. Dropping tables...\n";
    Schema::dropIfExists('bao_gia');
    Schema::dropIfExists('hinh_anh_bai_dang');
    Schema::dropIfExists('bai_dang');
    echo "Tables Dropped successfully.\n";

    echo "6. Recording migration...\n";
    try {
        DB::table('migrations')->insert([
            'migration' => '2026_03_05_035000_drop_bai_dang_and_bao_gia_tables',
            'batch' => 6
        ]);
        echo "Migration recorded.\n";
    } catch (\Exception $e) {
    }
} catch (\Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
