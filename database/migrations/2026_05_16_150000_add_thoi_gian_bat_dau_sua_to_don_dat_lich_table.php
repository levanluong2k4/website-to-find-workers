<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('don_dat_lich') || Schema::hasColumn('don_dat_lich', 'thoi_gian_bat_dau_sua')) {
            return;
        }

        Schema::table('don_dat_lich', function (Blueprint $table): void {
            $table->timestamp('thoi_gian_bat_dau_sua')
                ->nullable()
                ->after('worker_reminder_sent_at');
        });

        DB::table('don_dat_lich')
            ->where('trang_thai', 'dang_lam')
            ->whereNull('thoi_gian_bat_dau_sua')
            ->update([
                'thoi_gian_bat_dau_sua' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('don_dat_lich') || !Schema::hasColumn('don_dat_lich', 'thoi_gian_bat_dau_sua')) {
            return;
        }

        Schema::table('don_dat_lich', function (Blueprint $table): void {
            $table->dropColumn('thoi_gian_bat_dau_sua');
        });
    }
};
