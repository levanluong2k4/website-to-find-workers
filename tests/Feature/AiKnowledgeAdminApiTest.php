<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiKnowledgeAdminApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_admin_can_list_filter_and_view_ai_knowledge_items(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('admin-ai-knowledge')->plainTextToken;

        $serviceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Drain cleaning',
            'mo_ta' => 'Clean and replace drain parts',
            'hinh_anh' => null,
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $matchingId = DB::table('ai_knowledge_items')->insertGetId([
            'source_type' => 'booking_case',
            'source_id' => 41,
            'source_key' => 'booking_case:41',
            'primary_service_id' => $serviceId,
            'service_name' => 'Drain cleaning',
            'title' => 'Drain leak under washing machine',
            'content' => 'Service: drain cleaning. Symptom: washing machine leaks from drain hose. Cause: cracked hose. Solution: replace hose.',
            'normalized_content' => 'service drain cleaning symptom washing machine leaks from drain hose cause cracked hose solution replace hose',
            'symptom_text' => 'Washing machine leaks from drain hose',
            'cause_text' => 'Cracked drain hose',
            'solution_text' => 'Replace the drain hose',
            'price_context' => 'labor 50000 VND, part 400000 VND',
            'rating_avg' => 4.70,
            'quality_score' => 0.9400,
            'metadata' => json_encode(['cost_breakdown' => ['tong_tien' => 450000]], JSON_UNESCAPED_UNICODE),
            'is_active' => true,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ai_knowledge_items')->insert([
            'source_type' => 'service_catalog',
            'source_id' => 99,
            'source_key' => 'service_catalog:99',
            'primary_service_id' => $serviceId,
            'service_name' => 'Drain cleaning',
            'title' => 'Catalog entry',
            'content' => 'Generic service catalog content',
            'normalized_content' => 'generic service catalog content',
            'symptom_text' => 'General cleaning',
            'cause_text' => null,
            'solution_text' => null,
            'price_context' => null,
            'rating_avg' => null,
            'quality_score' => 0.4500,
            'metadata' => json_encode([], JSON_UNESCAPED_UNICODE),
            'is_active' => true,
            'published_at' => now()->subDay(),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/ai-knowledge?source_type=booking_case&q=drain hose&min_quality_score=0.9');

        $listResponse->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $matchingId)
            ->assertJsonPath('data.0.source_key', 'booking_case:41')
            ->assertJsonPath('data.0.primary_service.id', $serviceId)
            ->assertJsonPath('data.0.training_prompt', "Dich vu: Drain cleaning\nTrieu chung: Washing machine leaks from drain hose");

        $detailResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/ai-knowledge/' . $matchingId);

        $detailResponse->assertOk()
            ->assertJsonPath('data.id', $matchingId)
            ->assertJsonPath('data.training_completion', "Nguyen nhan du kien: Cracked drain hose\nGiai phap de xuat: Replace the drain hose\nChi phi tham khao: labor 50000 VND, part 400000 VND");
    }

    public function test_admin_can_export_ai_knowledge_as_jsonl_and_csv(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('admin-ai-export')->plainTextToken;

        $serviceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Pump repair',
            'mo_ta' => 'Repair and replace pump parts',
            'hinh_anh' => null,
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ai_knowledge_items')->insert([
            'source_type' => 'booking_case',
            'source_id' => 52,
            'source_key' => 'booking_case:52',
            'primary_service_id' => $serviceId,
            'service_name' => 'Pump repair',
            'title' => 'Pump replacement',
            'content' => 'Pump replacement case',
            'normalized_content' => 'pump replacement case',
            'symptom_text' => 'Pump does not start',
            'cause_text' => 'Burned pump motor',
            'solution_text' => 'Replace the pump motor',
            'price_context' => 'labor 120000 VND',
            'rating_avg' => 4.90,
            'quality_score' => 0.9800,
            'metadata' => json_encode(['source' => 'test'], JSON_UNESCAPED_UNICODE),
            'is_active' => true,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $jsonlResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get('/api/admin/ai-knowledge/export?format=jsonl&profile=records&source_type=booking_case');

        $jsonlResponse->assertOk();
        $jsonlContent = trim($jsonlResponse->streamedContent());
        $this->assertStringContainsString('"source_key":"booking_case:52"', $jsonlContent);
        $this->assertStringContainsString('"training_prompt":"Dich vu: Pump repair\\nTrieu chung: Pump does not start"', $jsonlContent);

        $csvResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get('/api/admin/ai-knowledge/export?format=csv&profile=finetune&source_type=booking_case');

        $csvResponse->assertOk();
        $csvContent = ltrim($csvResponse->streamedContent(), "\xEF\xBB\xBF");
        $this->assertStringContainsString('source_key,service_name,prompt,completion,quality_score,rating_avg', $csvContent);
        $this->assertStringContainsString('booking_case:52', $csvContent);
        $this->assertStringContainsString('Pump repair', $csvContent);
    }

    public function test_admin_can_trigger_ai_knowledge_sync_for_service_catalog(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('admin-ai-sync')->plainTextToken;

        $serviceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Faucet replacement',
            'mo_ta' => 'Replace leaking faucet parts',
            'hinh_anh' => '/storage/services/faucet.png',
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/ai-knowledge/sync', [
                'source' => 'service_catalog',
                'id' => $serviceId,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.result.service_catalog', 1)
            ->assertJsonPath('data.source', 'service_catalog')
            ->assertJsonPath('data.source_id', $serviceId);

        $this->assertDatabaseHas('ai_knowledge_items', [
            'source_key' => 'service_catalog:' . $serviceId,
            'source_type' => 'service_catalog',
            'primary_service_id' => $serviceId,
        ]);
    }

    private function createAdmin(): User
    {
        return User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-ai@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function prepareSchema(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('avatar')->nullable();
                $table->string('google_id')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->enum('role', ['admin', 'customer', 'worker'])->default('customer');
                $table->boolean('is_active')->default(true);
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('danh_muc_dich_vu')) {
            Schema::create('danh_muc_dich_vu', function (Blueprint $table) {
                $table->id();
                $table->string('ten_dich_vu');
                $table->text('mo_ta')->nullable();
                $table->string('hinh_anh')->nullable();
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
        foreach (['ai_knowledge_items', 'danh_muc_dich_vu', 'personal_access_tokens', 'users'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
