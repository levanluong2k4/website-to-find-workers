<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            if (!Schema::hasColumn('don_dat_lich', 'worker_contact_issue_reported_at')) {
                $table->timestamp('worker_contact_issue_reported_at')
                    ->nullable()
                    ->after('worker_reminder_sent_at');
            }

            if (!Schema::hasColumn('don_dat_lich', 'worker_contact_issue_resolved_at')) {
                $table->timestamp('worker_contact_issue_resolved_at')
                    ->nullable()
                    ->after('worker_contact_issue_reported_at');
            }

            if (!Schema::hasColumn('don_dat_lich', 'worker_contact_issue_reported_by')) {
                $table->unsignedBigInteger('worker_contact_issue_reported_by')
                    ->nullable()
                    ->after('worker_contact_issue_resolved_at');
            }

            if (!Schema::hasColumn('don_dat_lich', 'worker_contact_issue_note')) {
                $table->text('worker_contact_issue_note')
                    ->nullable()
                    ->after('worker_contact_issue_reported_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('don_dat_lich', function (Blueprint $table) {
            $columns = [
                'worker_contact_issue_note',
                'worker_contact_issue_reported_by',
                'worker_contact_issue_resolved_at',
                'worker_contact_issue_reported_at',
            ];

            $dropColumns = array_values(array_filter($columns, static fn (string $column): bool => Schema::hasColumn('don_dat_lich', $column)));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
