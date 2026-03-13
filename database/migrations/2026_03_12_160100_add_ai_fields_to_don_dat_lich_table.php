<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            if (!Schema::hasColumn('don_dat_lich', 'nguyen_nhan')) {
                $table->text('nguyen_nhan')->nullable()->after('mo_ta_van_de');
            }

            if (!Schema::hasColumn('don_dat_lich', 'giai_phap')) {
                $table->text('giai_phap')->nullable()->after('nguyen_nhan');
            }

            $table->index(['trang_thai', 'tho_id', 'created_at'], 'don_dat_lich_status_worker_created_idx');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE don_dat_lich ADD FULLTEXT don_dat_lich_ai_fulltext (mo_ta_van_de, nguyen_nhan, giai_phap)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE don_dat_lich DROP INDEX don_dat_lich_ai_fulltext');
            } catch (\Throwable) {
                // Ignore when index does not exist.
            }
        }

        Schema::table('don_dat_lich', function (Blueprint $table) {
            $table->dropIndex('don_dat_lich_status_worker_created_idx');

            if (Schema::hasColumn('don_dat_lich', 'giai_phap')) {
                $table->dropColumn('giai_phap');
            }

            if (Schema::hasColumn('don_dat_lich', 'nguyen_nhan')) {
                $table->dropColumn('nguyen_nhan');
            }
        });
    }
};

