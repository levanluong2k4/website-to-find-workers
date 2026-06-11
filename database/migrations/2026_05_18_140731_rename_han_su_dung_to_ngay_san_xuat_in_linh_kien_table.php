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
        Schema::table('linh_kien', function (Blueprint $table) {
            $table->renameColumn('han_su_dung', 'ngay_san_xuat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('linh_kien', function (Blueprint $table) {
            $table->renameColumn('ngay_san_xuat', 'han_su_dung');
        });
    }
};
