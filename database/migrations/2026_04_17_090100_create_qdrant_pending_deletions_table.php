<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qdrant_pending_deletions', function (Blueprint $table) {
            $table->id();
            $table->string('collection', 191)->index();
            $table->string('point_id', 191);
            $table->string('reason', 255)->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['collection', 'point_id'], 'qdrant_pending_deletions_collection_point_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qdrant_pending_deletions');
    }
};
