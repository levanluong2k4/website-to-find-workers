<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table): void {
            if (!Schema::hasColumn('don_dat_lich', 'worker_contact_issue_reporter_name')) {
                $table->string('worker_contact_issue_reporter_name', 255)
                    ->nullable()
                    ->after('worker_contact_issue_reported_by');
            }

            if (!Schema::hasColumn('don_dat_lich', 'worker_contact_issue_called_phone')) {
                $table->string('worker_contact_issue_called_phone', 32)
                    ->nullable()
                    ->after('worker_contact_issue_reporter_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table): void {
            if (Schema::hasColumn('don_dat_lich', 'worker_contact_issue_called_phone')) {
                $table->dropColumn('worker_contact_issue_called_phone');
            }

            if (Schema::hasColumn('don_dat_lich', 'worker_contact_issue_reporter_name')) {
                $table->dropColumn('worker_contact_issue_reporter_name');
            }
        });
    }
};
