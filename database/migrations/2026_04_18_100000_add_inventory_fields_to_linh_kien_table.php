<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('linh_kien', function (Blueprint $table) {
            if (!Schema::hasColumn('linh_kien', 'so_luong_ton_kho')) {
                $table->unsignedInteger('so_luong_ton_kho')->default(0)->after('gia');
            }

            if (!Schema::hasColumn('linh_kien', 'han_su_dung')) {
                $table->date('han_su_dung')->nullable()->after('so_luong_ton_kho');
            }
        });
    }

    public function down(): void
    {
        Schema::table('linh_kien', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('linh_kien', 'han_su_dung')) {
                $columns[] = 'han_su_dung';
            }

            if (Schema::hasColumn('linh_kien', 'so_luong_ton_kho')) {
                $columns[] = 'so_luong_ton_kho';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
