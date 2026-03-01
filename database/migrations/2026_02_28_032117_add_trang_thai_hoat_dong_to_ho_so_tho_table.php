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
            $table->enum('trang_thai_hoat_dong', ['dang_hoat_dong', 'dang_ban', 'ngung_hoat_dong', 'tam_khoa'])
                ->default('dang_hoat_dong')
                ->after('dang_hoat_dong');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ho_so_tho', function (Blueprint $table) {
            $table->dropColumn('trang_thai_hoat_dong');
        });
    }
};
