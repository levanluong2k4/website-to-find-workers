<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('thanh_toan')) {
            return;
        }

        DB::statement("ALTER TABLE thanh_toan MODIFY COLUMN phuong_thuc ENUM('cash', 'vnpay', 'momo', 'zalopay', 'test') NOT NULL DEFAULT 'cash'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('thanh_toan')) {
            return;
        }

        DB::statement("ALTER TABLE thanh_toan MODIFY COLUMN phuong_thuc ENUM('cash', 'vnpay', 'momo', 'zalopay') NOT NULL DEFAULT 'cash'");
    }
};
