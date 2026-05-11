<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_feedback_cases')) {
            return;
        }

        Schema::table('customer_feedback_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_feedback_cases', 'deadline_at')) {
                $table->timestamp('deadline_at')->nullable()->after('assigned_at');
            }

            if (!Schema::hasColumn('customer_feedback_cases', 'assignment_note')) {
                $table->text('assignment_note')->nullable()->after('deadline_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customer_feedback_cases')) {
            return;
        }

        Schema::table('customer_feedback_cases', function (Blueprint $table) {
            if (Schema::hasColumn('customer_feedback_cases', 'assignment_note')) {
                $table->dropColumn('assignment_note');
            }

            if (Schema::hasColumn('customer_feedback_cases', 'deadline_at')) {
                $table->dropColumn('deadline_at');
            }
        });
    }
};
