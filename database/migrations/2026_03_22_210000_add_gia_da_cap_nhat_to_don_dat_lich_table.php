<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            $table->boolean('gia_da_cap_nhat')->default(false)->after('tong_tien');
        });
    }

    public function down(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            $table->dropColumn('gia_da_cap_nhat');
        });
    }
};
