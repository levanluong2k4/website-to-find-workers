<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            $table->json('hinh_anh_ket_qua')->nullable()->after('video_mo_ta');
            $table->string('video_ket_qua')->nullable()->after('hinh_anh_ket_qua');
        });

        // Add 'transfer' to enum
        DB::statement("ALTER TABLE don_dat_lich MODIFY COLUMN phuong_thuc_thanh_toan ENUM('cod', 'transfer') NOT NULL DEFAULT 'cod'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            $table->dropColumn(['hinh_anh_ket_qua', 'video_ket_qua']);
        });

        DB::statement("ALTER TABLE don_dat_lich MODIFY COLUMN phuong_thuc_thanh_toan ENUM('cod') NOT NULL DEFAULT 'cod'");
    }
};
