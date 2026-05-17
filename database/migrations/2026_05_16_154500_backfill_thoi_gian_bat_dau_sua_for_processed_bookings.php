<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('don_dat_lich') || !Schema::hasColumn('don_dat_lich', 'thoi_gian_bat_dau_sua')) {
            return;
        }

        DB::table('don_dat_lich')
            ->whereNull('thoi_gian_bat_dau_sua')
            ->whereIn('trang_thai', ['dang_lam', 'cho_hoan_thanh', 'cho_thanh_toan', 'da_xong', 'hoan_thanh'])
            ->update([
                'thoi_gian_bat_dau_sua' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        // No-op: backfill should not be reverted.
    }
};
