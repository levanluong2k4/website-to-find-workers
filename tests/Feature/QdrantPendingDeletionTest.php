<?php

namespace Tests\Feature;

use App\Services\Chat\AiKnowledgeSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QdrantPendingDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();

        config([
            'services.qdrant.url' => 'https://qdrant.test',
            'services.qdrant.api_key' => 'qdrant-test-key',
            'services.qdrant.collection' => 'ai_knowledge_items_v1',
            'services.qdrant.timeout' => 5,
        ]);
    }

    public function test_delete_source_record_queues_pending_qdrant_deletion(): void
    {
        $itemId = DB::table('ai_knowledge_items')->insertGetId([
            'source_type' => 'booking_case',
            'source_id' => 55,
            'source_key' => 'booking_case:55',
            'title' => 'May lanh chay nuoc',
            'content' => 'Noi dung test',
            'quality_score' => 0.75,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(AiKnowledgeSyncService::class)->deleteSourceRecord('booking_case', 55);

        $this->assertDatabaseMissing('ai_knowledge_items', [
            'id' => $itemId,
        ]);
        $this->assertDatabaseHas('qdrant_pending_deletions', [
            'collection' => 'ai_knowledge_items_v1',
            'point_id' => (string) $itemId,
            'reason' => 'source_deleted:booking_case:55',
        ]);
    }

    public function test_cleanup_command_deletes_pending_points_from_qdrant(): void
    {
        DB::table('qdrant_pending_deletions')->insert([
            'collection' => 'ai_knowledge_items_v1',
            'point_id' => '99',
            'reason' => 'source_deleted:booking_case:99',
            'attempt_count' => 0,
            'available_at' => now()->subMinute(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'https://qdrant.test/*' => Http::response([
                'status' => 'ok',
            ]),
        ]);

        $this->artisan('app:cleanup-stale-qdrant-points', ['--limit' => 10])
            ->assertExitCode(0);

        $this->assertDatabaseCount('qdrant_pending_deletions', 0);

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->method() === 'POST'
                && str_contains($request->url(), '/collections/ai_knowledge_items_v1/points/delete')
                && data_get($request->data(), 'points.0') === 99;
        });
    }

    private function prepareSchema(): void
    {
        if (!Schema::hasTable('ai_knowledge_items')) {
            Schema::create('ai_knowledge_items', function (Blueprint $table) {
                $table->id();
                $table->string('source_type', 50);
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('source_key')->unique();
                $table->unsignedBigInteger('primary_service_id')->nullable();
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
                $table->string('qdrant_document_hash')->nullable();
                $table->timestamp('qdrant_synced_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('qdrant_pending_deletions')) {
            Schema::create('qdrant_pending_deletions', function (Blueprint $table) {
                $table->id();
                $table->string('collection', 191);
                $table->string('point_id', 191);
                $table->string('reason', 255)->nullable();
                $table->unsignedInteger('attempt_count')->default(0);
                $table->timestamp('available_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();

                $table->unique(['collection', 'point_id'], 'qdrant_pending_deletions_collection_point_unique');
            });
        }
    }

    private function truncateTables(): void
    {
        foreach (['qdrant_pending_deletions', 'ai_knowledge_items'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
