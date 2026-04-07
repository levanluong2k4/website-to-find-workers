<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('don_dat_lich', 'worker_reminder_sent_at')) {
            Schema::table('don_dat_lich', function (Blueprint $table) {
                $table->timestamp('worker_reminder_sent_at')
                    ->nullable()
                    ->after('thoi_gian_hen');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('don_dat_lich', 'worker_reminder_sent_at')) {
            Schema::table('don_dat_lich', function (Blueprint $table) {
                $table->dropColumn('worker_reminder_sent_at');
            });
        }
    }
};
