<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_feedback_cases', function (Blueprint $table) {
            $table->timestamp('deadline_at')->nullable()->after('assigned_at');
            $table->text('assignment_note')->nullable()->after('deadline_at');

            $table->index(['assigned_admin_id', 'deadline_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_feedback_cases', function (Blueprint $table) {
            $table->dropIndex(['assigned_admin_id', 'deadline_at']);
            $table->dropColumn(['deadline_at', 'assignment_note']);
        });
    }
};
