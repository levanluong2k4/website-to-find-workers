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

    public function test_typo_in_service_name_keeps_results_scoped_to_the_same_service(): void
    {
        $washerServiceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Sua may giat',
            'mo_ta' => 'Sua may giat tai nha',
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $microwaveServiceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Sua lo vi song',
            'mo_ta' => 'Sua lo vi song tai nha',
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ai_knowledge_items')->insert([
            [
                'source_type' => 'booking_case',
                'source_id' => 201,
                'source_key' => 'booking_case:201',
                'primary_service_id' => $washerServiceId,
                'service_name' => 'Sua may giat',
                'title' => 'May giat khong len nguon',
                'content' => 'Trieu chung: may giat khong len nguon. Nguyen nhan: long day nguon. Giai phap: kiem tra o cam va day cap.',
                'normalized_content' => 'trieu chung may giat khong len nguon nguyen nhan long day nguon giai phap kiem tra o cam va day cap',
                'symptom_text' => 'May giat khong len nguon',
                'cause_text' => 'Long day nguon',
                'solution_text' => 'Kiem tra o cam va day cap',
                'price_context' => 'labor 70000 VND',
                'rating_avg' => 4.90,
                'quality_score' => 0.9000,
                'metadata' => json_encode([], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'source_type' => 'booking_case',
                'source_id' => 202,
                'source_key' => 'booking_case:202',
                'primary_service_id' => $microwaveServiceId,
                'service_name' => 'Sua lo vi song',
                'title' => 'Lo vi song khong len nguon',
                'content' => 'Trieu chung: lo vi song khong len nguon. Nguyen nhan: dut cau chi. Giai phap: thay cau chi va kiem tra mach.',
                'normalized_content' => 'trieu chung lo vi song khong len nguon nguyen nhan dut cau chi giai phap thay cau chi va kiem tra mach',
                'symptom_text' => 'Lo vi song khong len nguon',
                'cause_text' => 'Dut cau chi',
                'solution_text' => 'Thay cau chi va kiem tra mach',
                'price_context' => 'labor 90000 VND',
                'rating_avg' => 5.00,
                'quality_score' => 0.9900,
                'metadata' => json_encode([], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $results = app(SimilarIssueSearchService::class)->search('may giac khong len nguon nguyen nhan gi', 3);

        $this->assertCount(1, $results);
        $this->assertSame('Sua may giat', $results[0]['service_type']);
        $this->assertSame(201, $results[0]['id']);
    }

    public function test_specific_issue_query_filters_out_other_same_service_symptoms(): void
    {
        $serviceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Sua may lanh',
            'mo_ta' => 'Sua may lanh tai nha',
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ai_knowledge_items')->insert([
            [
                'source_type' => 'booking_case',
                'source_id' => 301,
                'source_key' => 'booking_case:301',
                'primary_service_id' => $serviceId,
                'service_name' => 'Sua may lanh',
                'title' => 'May lanh khong hoat dong mat nguon toan tap',
                'content' => 'Trieu chung: may lanh khong hoat dong mat nguon toan tap. Nguyen nhan: long day nguon. Giai phap: kiem tra day cap va bo nguon.',
                'normalized_content' => 'trieu chung may lanh khong hoat dong mat nguon toan tap nguyen nhan long day nguon giai phap kiem tra day cap va bo nguon',
                'symptom_text' => 'May lanh khong hoat dong / Mat nguon toan tap',
                'cause_text' => 'Long day nguon',
                'solution_text' => 'Kiem tra day cap va bo nguon',
                'price_context' => 'labor 120000 VND',
                'rating_avg' => 4.80,
                'quality_score' => 0.9200,
                'metadata' => json_encode([], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'source_type' => 'booking_case',
                'source_id' => 302,
                'source_key' => 'booking_case:302',
                'primary_service_id' => $serviceId,
                'service_name' => 'Sua may lanh',
                'title' => 'May lanh qua lanh',
                'content' => 'Trieu chung: may lanh qua lanh. Nguyen nhan: loi cam bien nhiet do. Giai phap: kiem tra cam bien.',
                'normalized_content' => 'trieu chung may lanh qua lanh nguyen nhan loi cam bien nhiet do giai phap kiem tra cam bien',
                'symptom_text' => 'May lanh qua lanh',
                'cause_text' => 'Loi cam bien nhiet do',
                'solution_text' => 'Kiem tra cam bien',
                'price_context' => 'labor 150000 VND',
                'rating_avg' => 4.90,
                'quality_score' => 0.9500,
                'metadata' => json_encode([], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'source_type' => 'booking_case',
                'source_id' => 303,
                'source_key' => 'booking_case:303',
                'primary_service_id' => $serviceId,
                'service_name' => 'Sua may lanh',
                'title' => 'May lanh chay lien tuc nhung khong lanh',
                'content' => 'Trieu chung: may lanh chay lien tuc nhung khong lanh kem lanh thoi ra gio. Nguyen nhan: thieu gas. Giai phap: nap gas va kiem tra ro ri.',
                'normalized_content' => 'trieu chung may lanh chay lien tuc nhung khong lanh kem lanh thoi ra gio nguyen nhan thieu gas giai phap nap gas va kiem tra ro ri',
                'symptom_text' => 'May chay lien tuc nhung khong lanh / Kem lanh / Thoi ra gio',
                'cause_text' => 'Thieu gas',
                'solution_text' => 'Nap gas va kiem tra ro ri',
                'price_context' => 'labor 180000 VND',
                'rating_avg' => 5.00,
                'quality_score' => 0.9800,
                'metadata' => json_encode([], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $results = app(SimilarIssueSearchService::class)->search('may lanh mat nguon nguyen nhan do dau', 3);

        $this->assertCount(1, $results);
        $this->assertSame(301, $results[0]['id']);
        $this->assertSame('May lanh khong hoat dong / Mat nguon toan tap', $results[0]['problem_description']);
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
