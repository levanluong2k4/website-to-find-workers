<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_items', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_key')->unique();
            $table->foreignId('primary_service_id')->nullable()->constrained('danh_muc_dich_vu')->nullOnDelete();
            $table->string('service_name')->nullable();
            $table->string('title');
            $table->longText('content');
            $table->longText('normalized_content')->nullable();
            $table->text('symptom_text')->nullable();
            $table->text('cause_text')->nullable();
            $table->text('solution_text')->nullable();
            $table->text('price_context')->nullable();
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->decimal('quality_score', 5, 4)->default(0);
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['primary_service_id', 'is_active']);
            $table->index(['is_active', 'published_at']);
            $table->index(['quality_score', 'rating_avg']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE ai_knowledge_items ADD FULLTEXT ai_knowledge_items_fulltext (title, service_name, symptom_text, cause_text, solution_text, content)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_items');
    }
};
