<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_TIME_SLOTS = [
        '08:00-10:00',
        '10:00-12:00',
        '12:00-14:00',
        '14:00-17:00',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('don_dat_lich') || !Schema::hasColumn('don_dat_lich', 'khung_gio_hen')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE don_dat_lich MODIFY COLUMN khung_gio_hen VARCHAR(20) NULL');

            return;
        }

        Schema::table('don_dat_lich', function (Blueprint $table) {
            $table->string('khung_gio_hen', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('don_dat_lich') || !Schema::hasColumn('don_dat_lich', 'khung_gio_hen')) {
            return;
        }

        DB::table('don_dat_lich')
            ->whereNotNull('khung_gio_hen')
            ->whereNotIn('khung_gio_hen', self::LEGACY_TIME_SLOTS)
            ->update(['khung_gio_hen' => null]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE don_dat_lich
                MODIFY COLUMN khung_gio_hen ENUM(
                    '08:00-10:00',
                    '10:00-12:00',
                    '12:00-14:00',
                    '14:00-17:00'
                ) NULL
            ");

            return;
        }

        Schema::table('don_dat_lich', function (Blueprint $table) {
            $table->enum('khung_gio_hen', self::LEGACY_TIME_SLOTS)->nullable()->change();
        });
    }
};
