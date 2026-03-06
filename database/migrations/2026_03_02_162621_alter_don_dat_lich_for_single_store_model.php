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
            $table->enum('loai_dat_lich', ['at_home', 'at_store'])->default('at_home')->after('dich_vu_id');
            $table->date('ngay_hen')->nullable()->after('thoi_gian_hen');
            $table->enum('khung_gio_hen', ['08:00-10:00', '10:00-12:00', '12:00-14:00', '14:00-17:00'])->nullable()->after('ngay_hen');
            $table->decimal('khoang_cach', 8, 2)->nullable()->after('khung_gio_hen');
            $table->decimal('phi_di_lai', 15, 2)->default(0)->after('khoang_cach');
            $table->decimal('phi_linh_kien', 15, 2)->default(0)->after('phi_di_lai');
            $table->text('ghi_chu_linh_kien')->nullable()->after('phi_linh_kien');
            $table->timestamp('thoi_gian_het_han_nhan')->nullable()->after('trang_thai_thanh_toan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            $table->dropColumn([
                'loai_dat_lich',
                'ngay_hen',
                'khung_gio_hen',
                'khoang_cach',
                'phi_di_lai',
                'phi_linh_kien',
                'ghi_chu_linh_kien',
                'thoi_gian_het_han_nhan'
            ]);
        });
    }
};
