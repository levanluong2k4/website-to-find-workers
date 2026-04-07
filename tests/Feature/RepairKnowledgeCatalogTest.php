<?php

namespace Tests\Feature;

use App\Models\DanhMucDichVu;
use App\Models\HuongXuLy;
use App\Models\NguyenNhan;
use App\Models\TrieuChung;
use Database\Seeders\MicrowaveKnowledgeSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RepairKnowledgeCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_service_symptom_cause_resolution_relationships_work(): void
    {
        $service = DanhMucDichVu::query()->create([
            'ten_dich_vu' => 'Sua may lanh',
            'mo_ta' => 'Catalog may lanh',
            'trang_thai' => 1,
        ]);

        $symptom = TrieuChung::query()->create([
            'dich_vu_id' => $service->id,
            'ten_trieu_chung' => 'May lanh khong lanh',
        ]);

        $cause = NguyenNhan::query()->create([
            'ten_nguyen_nhan' => 'Thieu gas',
        ]);

        $symptom->nguyenNhans()->attach($cause->id);

        $resolution = HuongXuLy::query()->create([
            'nguyen_nhan_id' => $cause->id,
            'ten_huong_xu_ly' => 'Nap gas va kiem tra ro ri',
            'gia_tham_khao' => 350000,
            'mo_ta_cong_viec' => 'Do ap suat, kiem tra ro ri, nap them gas.',
        ]);

        $this->assertSame($service->id, $symptom->dichVu->id);
        $this->assertSame('Thieu gas', $symptom->nguyenNhans->first()?->ten_nguyen_nhan);
        $this->assertSame('May lanh khong lanh', $cause->trieuChungs->first()?->ten_trieu_chung);
        $this->assertSame($cause->id, $resolution->nguyenNhan->id);
        $this->assertSame('Nap gas va kiem tra ro ri', $cause->huongXuLys->first()?->ten_huong_xu_ly);
    }

    public function test_microwave_knowledge_seeder_attaches_catalog_to_microwave_service(): void
    {
        $wrongService = DanhMucDichVu::query()->create([
            'ten_dich_vu' => 'Sua may giat',
            'mo_ta' => 'Service khac',
            'trang_thai' => 1,
        ]);

        $microwaveService = DanhMucDichVu::query()->create([
            'ten_dich_vu' => 'Sua lo vi song',
            'mo_ta' => 'Catalog lo vi song',
            'trang_thai' => 1,
        ]);

        TrieuChung::query()->create([
            'dich_vu_id' => $wrongService->id,
            'ten_trieu_chung' => 'Lò vi sóng không nóng (Mã lỗi F16, F19 LG; H97, H98 Panasonic; H64)',
        ]);

        $this->seed(MicrowaveKnowledgeSeeder::class);

        $symptom = TrieuChung::query()
            ->where('ten_trieu_chung', 'Lò vi sóng không nóng (Mã lỗi F16, F19 LG; H97, H98 Panasonic; H64)')
            ->first();
        $cause = NguyenNhan::query()
            ->where('ten_nguyen_nhan', 'Hỏng cục phát sóng (Magnetron)')
            ->first();
        $resolution = HuongXuLy::query()
            ->where('ten_huong_xu_ly', 'Thay cục sóng lò vi sóng cơ')
            ->first();

        $this->assertNotNull($symptom);
        $this->assertNotNull($cause);
        $this->assertNotNull($resolution);
        $this->assertSame($microwaveService->id, (int) $symptom->dich_vu_id);
        $this->assertSame(450000.0, (float) $resolution->gia_tham_khao);
        $this->assertDatabaseHas('trieu_chung_nguyen_nhan', [
            'trieu_chung_id' => $symptom->id,
            'nguyen_nhan_id' => $cause->id,
        ]);
        $this->assertSame($cause->id, $resolution->nguyen_nhan_id);
    }

    private function prepareSchema(): void
    {
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

        if (!Schema::hasTable('trieu_chung')) {
            Schema::create('trieu_chung', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dich_vu_id');
                $table->string('ten_trieu_chung');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('nguyen_nhan')) {
            Schema::create('nguyen_nhan', function (Blueprint $table) {
                $table->id();
                $table->string('ten_nguyen_nhan');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('trieu_chung_nguyen_nhan')) {
            Schema::create('trieu_chung_nguyen_nhan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('trieu_chung_id');
                $table->unsignedBigInteger('nguyen_nhan_id');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('huong_xu_ly')) {
            Schema::create('huong_xu_ly', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('nguyen_nhan_id');
                $table->string('ten_huong_xu_ly');
                $table->decimal('gia_tham_khao', 15, 2)->nullable();
                $table->text('mo_ta_cong_viec')->nullable();
                $table->timestamps();
            });
        }
    }

    private function truncateTables(): void
    {
        foreach (['huong_xu_ly', 'trieu_chung_nguyen_nhan', 'nguyen_nhan', 'trieu_chung', 'danh_muc_dich_vu'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
