<?php

namespace Tests\Feature;

use App\Services\Chat\SimilarIssueSearchService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SimilarIssueSearchRankingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_exact_symptom_match_outranks_generic_high_rating_case(): void
    {
        $serviceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Washer repair',
            'mo_ta' => 'Repair washer leaks and drain issues',
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ai_knowledge_items')->insert([
            [
                'source_type' => 'booking_case',
                'source_id' => 101,
                'source_key' => 'booking_case:101',
                'primary_service_id' => $serviceId,
                'service_name' => 'Washer repair',
                'title' => 'Washer leaking from the bottom',
                'content' => 'Symptom: washer leaking from the bottom. Cause: cracked drain hose. Solution: replace the drain hose.',
                'normalized_content' => 'symptom washer leaking from the bottom cause cracked drain hose solution replace the drain hose',
                'symptom_text' => 'Washer leaking from the bottom',
                'cause_text' => 'Cracked drain hose',
                'solution_text' => 'Replace the drain hose',
                'price_context' => 'labor 50000 VND',
                'rating_avg' => null,
                'quality_score' => 0.2000,
                'metadata' => json_encode([], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'source_type' => 'booking_case',
                'source_id' => 102,
                'source_key' => 'booking_case:102',
                'primary_service_id' => $serviceId,
                'service_name' => 'Washer repair',
                'title' => 'General washer leak case',
                'content' => 'Symptom: washer leaks near the side panel. Cause: loose water valve. Solution: tighten the valve.',
                'normalized_content' => 'symptom washer leaks near the side panel cause loose water valve solution tighten the valve',
                'symptom_text' => 'Washer leaks near the side panel',
                'cause_text' => 'Loose water valve',
                'solution_text' => 'Tighten the valve',
                'price_context' => 'labor 80000 VND',
                'rating_avg' => 5.00,
                'quality_score' => 1.0000,
                'metadata' => json_encode([], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $results = app(SimilarIssueSearchService::class)->search('washer leaking from the bottom', 2);

        $this->assertCount(2, $results);
        $this->assertSame(101, $results[0]['id']);
        $this->assertSame('Cracked drain hose', $results[0]['cause']);
        $this->assertSame('Replace the drain hose', $results[0]['solution']);
    }

    private function prepareSchema(): void
    {
        if (!Schema::hasTable('danh_muc_dich_vu')) {
            Schema::create('danh_muc_dich_vu', function (Blueprint $table) {
                $table->id();
                $table->string('ten_dich_vu');
                $table->text('mo_ta')->nullable();
                $table->boolean('trang_thai')->default(true);
                $table->timestamps();
            });
        }

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
                $table->timestamps();
            });
        }
    }

    private function truncateTables(): void
    {
        foreach (['ai_knowledge_items', 'danh_muc_dich_vu'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
