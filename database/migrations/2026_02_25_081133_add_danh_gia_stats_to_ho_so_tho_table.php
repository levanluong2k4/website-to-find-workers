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
        Schema::table('ho_so_tho', function (Blueprint $table) {
            $table->decimal('danh_gia_trung_binh', 3, 2)->default(0)->after('dang_hoat_dong')->comment('Điểm đánh giá trung bình 0.00 -> 5.00');
            $table->integer('tong_so_danh_gia')->default(0)->after('danh_gia_trung_binh')->comment('Tổng số lượt đánh giá');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ho_so_tho', function (Blueprint $table) {
            $table->dropColumn(['danh_gia_trung_binh', 'tong_so_danh_gia']);
        });
    }
};
