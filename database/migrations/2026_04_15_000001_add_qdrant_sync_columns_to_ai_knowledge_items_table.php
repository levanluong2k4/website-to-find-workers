<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_knowledge_items', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_knowledge_items', 'qdrant_document_hash')) {
                $table->string('qdrant_document_hash', 64)->nullable()->after('published_at');
            }

            if (!Schema::hasColumn('ai_knowledge_items', 'qdrant_synced_at')) {
                $table->timestamp('qdrant_synced_at')->nullable()->after('qdrant_document_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_knowledge_items', function (Blueprint $table) {
            if (Schema::hasColumn('ai_knowledge_items', 'qdrant_synced_at')) {
                $table->dropColumn('qdrant_synced_at');
            }

            if (Schema::hasColumn('ai_knowledge_items', 'qdrant_document_hash')) {
                $table->dropColumn('qdrant_document_hash');
            }
        });
    }
};
