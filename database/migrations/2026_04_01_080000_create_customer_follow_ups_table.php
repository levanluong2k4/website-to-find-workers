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
        Schema::create('customer_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('don_dat_lich')->nullOnDelete();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->string('channel', 24)->default('call');
            $table->string('priority', 16)->default('medium');
            $table->string('status', 16)->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('note')->nullable();
            $table->text('outcome_note')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status', 'scheduled_at']);
            $table->index(['assigned_admin_id', 'status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_follow_ups');
    }
};
