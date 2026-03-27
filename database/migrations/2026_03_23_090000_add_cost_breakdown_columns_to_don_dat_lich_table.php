<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            if (!Schema::hasColumn('don_dat_lich', 'chi_tiet_tien_cong')) {
                $table->json('chi_tiet_tien_cong')->nullable()->after('tien_cong');
            }

            if (!Schema::hasColumn('don_dat_lich', 'chi_tiet_linh_kien')) {
                $table->json('chi_tiet_linh_kien')->nullable()->after('ghi_chu_linh_kien');
            }
        });
    }

    public function down(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('don_dat_lich', 'chi_tiet_tien_cong')) {
                $columns[] = 'chi_tiet_tien_cong';
            }

            if (Schema::hasColumn('don_dat_lich', 'chi_tiet_linh_kien')) {
                $columns[] = 'chi_tiet_linh_kien';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
