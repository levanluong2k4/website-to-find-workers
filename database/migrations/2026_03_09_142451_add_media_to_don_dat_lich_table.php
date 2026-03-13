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
            $table->json('hinh_anh_mo_ta')->nullable()->after('mo_ta_van_de');
            $table->string('video_mo_ta')->nullable()->after('hinh_anh_mo_ta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            $table->dropColumn(['hinh_anh_mo_ta', 'video_mo_ta']);
        });
    }
};
