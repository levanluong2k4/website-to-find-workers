<?php

namespace Tests\Feature;

use App\Services\Chat\AiKnowledgeSyncService;
use App\Services\Chat\SimilarIssueSearchService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiKnowledgeSyncServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_sync_completed_booking_case_into_ai_knowledge_library(): void
    {
        $serviceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Sua may giat',
            'mo_ta' => 'Xu ly loi may giat tai nha',
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bookingId = DB::table('don_dat_lich')->insertGetId([
            'trang_thai' => 'da_xong',
            'mo_ta_van_de' => 'May giat bi ro nuoc o day',
            'giai_phap' => 'Thay ong xa moi va siet lai kep giu',
            'phi_di_lai' => 10000,
            'phi_linh_kien' => 400000,
            'tien_cong' => 50000,
            'tien_thue_xe' => 0,
            'tong_tien' => 460000,
            'gia_da_cap_nhat' => true,
            'chi_tiet_tien_cong' => json_encode([
                ['noi_dung' => 'Sua voi ri nuoc', 'so_tien' => 50000],
            ], JSON_UNESCAPED_UNICODE),
            'chi_tiet_linh_kien' => json_encode([
                ['noi_dung' => 'Thay voi', 'so_tien' => 400000, 'bao_hanh_thang' => 6],
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('don_dat_lich_dich_vu')->insert([
            'don_dat_lich_id' => $bookingId,
            'dich_vu_id' => $serviceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('danh_gia')->insert([
            'don_dat_lich_id' => $bookingId,
            'so_sao' => 5,
            'nhan_xet' => 'Sua nhanh, gon va sach.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $synced = app(AiKnowledgeSyncService::class)->syncBookingCases();

        $this->assertSame(1, $synced);
        $this->assertDatabaseHas('ai_knowledge_items', [
            'source_key' => 'booking_case:' . $bookingId,
            'source_type' => 'booking_case',
            'service_name' => 'Sua may giat',
        ]);
    }

    public function test_similar_issue_search_prefers_ai_knowledge_library(): void
    {
        $serviceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Sua may giat',
            'mo_ta' => 'Xu ly loi may giat tai nha',
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ai_knowledge_items')->insert([
            'source_type' => 'booking_case',
            'source_id' => 99,
            'source_key' => 'booking_case:99',
            'primary_service_id' => $serviceId,
            'service_name' => 'Sua may giat',
            'title' => 'Ca sua chua may giat ro nuoc',
            'content' => 'Dich vu: Sua may giat. Trieu chung: may giat ro nuoc o day. Giai phap: thay ong xa moi.',
            'normalized_content' => 'dich vu sua may giat trieu chung may giat ro nuoc o day giai phap thay ong xa moi',
            'symptom_text' => 'May giat ro nuoc o day',
            'cause_text' => null,
            'solution_text' => 'Thay ong xa moi',
            'price_context' => 'tien cong 50000 VND, linh kien 400000 VND',
            'rating_avg' => 4.80,
            'quality_score' => 0.9300,
            'metadata' => json_encode(['before_image' => null, 'after_image' => null], JSON_UNESCAPED_UNICODE),
            'is_active' => true,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $results = app(SimilarIssueSearchService::class)->search('may giat bi ro nuoc o day', 1);

        $this->assertCount(1, $results);
        $this->assertSame('Sua may giat', $results[0]['service_type']);
        $this->assertSame('', $results[0]['cause']);
        $this->assertSame('Thay ong xa moi', $results[0]['solution']);
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

        if (!Schema::hasTable('don_dat_lich')) {
            Schema::create('don_dat_lich', function (Blueprint $table) {
                $table->id();
                $table->string('trang_thai')->default('cho_xac_nhan');
                $table->text('mo_ta_van_de')->nullable();
                $table->text('giai_phap')->nullable();
                $table->decimal('phi_di_lai', 12, 2)->default(0);
                $table->decimal('phi_linh_kien', 12, 2)->default(0);
                $table->decimal('tien_cong', 12, 2)->default(0);
                $table->decimal('tien_thue_xe', 12, 2)->default(0);
                $table->decimal('tong_tien', 12, 2)->nullable();
                $table->boolean('gia_da_cap_nhat')->default(false);
                $table->json('chi_tiet_tien_cong')->nullable();
                $table->json('chi_tiet_linh_kien')->nullable();
                $table->json('hinh_anh_mo_ta')->nullable();
                $table->json('hinh_anh_ket_qua')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('don_dat_lich_dich_vu')) {
            Schema::create('don_dat_lich_dich_vu', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('don_dat_lich_id');
                $table->unsignedBigInteger('dich_vu_id');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('danh_gia')) {
            Schema::create('danh_gia', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('don_dat_lich_id');
                $table->integer('so_sao')->nullable();
                $table->text('nhan_xet')->nullable();
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
        foreach (['ai_knowledge_items', 'danh_gia', 'don_dat_lich_dich_vu', 'don_dat_lich', 'danh_muc_dich_vu'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
