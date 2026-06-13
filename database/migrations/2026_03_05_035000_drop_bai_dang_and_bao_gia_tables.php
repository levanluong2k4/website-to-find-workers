<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            $table->dropForeign(['bai_dang_id']);
        });
        Schema::dropIfExists('bao_gia');
        Schema::dropIfExists('hinh_anh_bai_dang');
        Schema::dropIfExists('bai_dang');
    }

    public function down(): void
    {
        // Không khôi phục
    }
};
