<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('don_dat_lich') || !Schema::hasColumn('don_dat_lich', 'nguyen_nhan')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE don_dat_lich DROP INDEX don_dat_lich_ai_fulltext');
            } catch (\Throwable) {
                // Ignore when the index does not exist.
            }
        }

        Schema::table('don_dat_lich', function (Blueprint $table) {
            $table->dropColumn('nguyen_nhan');
        });

        if (DB::connection()->getDriverName() === 'mysql' && Schema::hasColumn('don_dat_lich', 'giai_phap')) {
            DB::statement('ALTER TABLE don_dat_lich ADD FULLTEXT don_dat_lich_ai_fulltext (mo_ta_van_de, giai_phap)');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('don_dat_lich')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE don_dat_lich DROP INDEX don_dat_lich_ai_fulltext');
            } catch (\Throwable) {
                // Ignore when the index does not exist.
            }
        }

        if (!Schema::hasColumn('don_dat_lich', 'nguyen_nhan')) {
            Schema::table('don_dat_lich', function (Blueprint $table) {
                $table->text('nguyen_nhan')->nullable()->after('mo_ta_van_de');
            });
        }

        if (DB::connection()->getDriverName() === 'mysql' && Schema::hasColumn('don_dat_lich', 'giai_phap')) {
            DB::statement('ALTER TABLE don_dat_lich ADD FULLTEXT don_dat_lich_ai_fulltext (mo_ta_van_de, nguyen_nhan, giai_phap)');
        }
    }
};
