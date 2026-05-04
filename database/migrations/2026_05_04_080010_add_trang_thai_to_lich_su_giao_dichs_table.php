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
        Schema::table('lich_su_giao_dichs', function (Blueprint $table) {
            $table->enum('trang_thai', ['thanh_cong', 'dang_xu_ly', 'that_bai'])->default('thanh_cong')->after('loai_giao_dich');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lich_su_giao_dichs', function (Blueprint $table) {
            $table->dropColumn('trang_thai');
        });
    }
};
