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
            if (!Schema::hasColumn('don_dat_lich', 'ma_ly_do_huy')) {
                $table->string('ma_ly_do_huy', 64)->nullable()->after('trang_thai');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            if (Schema::hasColumn('don_dat_lich', 'ma_ly_do_huy')) {
                $table->dropColumn('ma_ly_do_huy');
            }
        });
    }
};
