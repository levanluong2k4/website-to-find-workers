<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            if (!Schema::hasColumn('don_dat_lich', 'thoi_gian_hoan_thanh')) {
                $table->timestamp('thoi_gian_hoan_thanh')->nullable()->after('thoi_gian_het_han_nhan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            if (Schema::hasColumn('don_dat_lich', 'thoi_gian_hoan_thanh')) {
                $table->dropColumn('thoi_gian_hoan_thanh');
            }
        });
    }
};
