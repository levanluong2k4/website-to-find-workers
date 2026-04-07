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
        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 32)->default('van_hanh');
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('customer_tags', function (Blueprint $table) {
            $table->id();
            $table->string('label', 60)->unique();
            $table->string('slug', 70)->unique();
            $table->string('color', 32)->nullable();
            $table->timestamps();
        });

        Schema::create('customer_tag_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('customer_tags')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['customer_id', 'tag_id']);
            $table->index(['tag_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_tag_assignments');
        Schema::dropIfExists('customer_tags');
        Schema::dropIfExists('customer_notes');
    }
};
