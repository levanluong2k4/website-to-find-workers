<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guest_token', 100)->nullable()->index();
            $table->string('actor_type', 20)->index();
            $table->string('actor_key', 160)->index();
            $table->string('memory_type', 50)->index();
            $table->string('memory_key', 191);
            $table->string('label', 255);
            $table->text('value');
            $table->text('summary')->nullable();
            $table->decimal('confidence', 4, 3)->default(0.5);
            $table->unsignedBigInteger('source_message_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['actor_key', 'memory_type', 'memory_key'], 'chat_memories_actor_type_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_memories');
    }
};
