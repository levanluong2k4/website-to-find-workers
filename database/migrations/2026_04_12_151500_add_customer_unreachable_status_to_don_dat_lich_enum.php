<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('don_dat_lich')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE don_dat_lich
            MODIFY COLUMN trang_thai ENUM(
                'cho_xac_nhan',
                'da_xac_nhan',
                'khong_lien_lac_duoc_voi_khach_hang',
                'dang_lam',
                'cho_hoan_thanh',
                'cho_thanh_toan',
                'da_xong',
                'da_huy'
            ) NOT NULL DEFAULT 'cho_xac_nhan'
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('don_dat_lich')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('don_dat_lich')
            ->where('trang_thai', 'khong_lien_lac_duoc_voi_khach_hang')
            ->update(['trang_thai' => 'da_xac_nhan']);

        DB::statement("
            ALTER TABLE don_dat_lich
            MODIFY COLUMN trang_thai ENUM(
                'cho_xac_nhan',
                'da_xac_nhan',
                'dang_lam',
                'cho_hoan_thanh',
                'cho_thanh_toan',
                'da_xong',
                'da_huy'
            ) NOT NULL DEFAULT 'cho_xac_nhan'
        ");
    }
};
