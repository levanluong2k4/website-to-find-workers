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
            $table->decimal('tien_cong', 15, 2)->default(0)->after('trang_thai');
            $table->decimal('tien_thue_xe', 15, 2)->default(0)->after('thue_xe_cho');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            $table->dropColumn(['tien_cong', 'tien_thue_xe']);
        });
    }
};
