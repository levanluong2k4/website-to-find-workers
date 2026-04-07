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
        Schema::create('customer_feedback_cases', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('don_dat_lich')->nullOnDelete();
            $table->foreignId('worker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('priority', 16)->default('medium');
            $table->string('status', 16)->default('new');
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->json('last_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
            $table->index(['status', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_feedback_cases');
    }
};
