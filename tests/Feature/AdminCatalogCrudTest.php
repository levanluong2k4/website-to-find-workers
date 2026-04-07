<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminCatalogCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_admin_can_create_update_and_delete_part(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('catalog-admin')->plainTextToken;
        $serviceId = $this->createService('Sua may giat');

        $createResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/linh-kien', [
                'dich_vu_id' => $serviceId,
                'ten_linh_kien' => 'Board nguon inverter',
                'gia' => 650000,
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.ten_linh_kien', 'Board nguon inverter')
            ->assertJsonPath('data.service_name', 'Sua may giat');

        $partId = (int) $createResponse->json('data.id');

        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/linh-kien?service_id=' . $serviceId);

        $listResponse->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.items.0.id', $partId);

        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/linh-kien/' . $partId, [
                'dich_vu_id' => $serviceId,
                'ten_linh_kien' => 'Board nguon inverter LG',
                'gia' => 720000,
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.ten_linh_kien', 'Board nguon inverter LG')
            ->assertJsonPath('data.gia', 720000);

        $deleteResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/linh-kien/' . $partId);

        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('linh_kien', ['id' => $partId]);
    }

    public function test_admin_can_create_update_and_delete_symptom_with_causes(): void
    {
        $admin = $this->createAdmin('symptom-admin@example.com');
        $token = $admin->createToken('catalog-admin')->plainTextToken;
        $serviceId = $this->createService('Sua tu lanh');

        $createResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/trieu-chung', [
                'dich_vu_id' => $serviceId,
                'ten_trieu_chung' => 'May rung lac manh khi vat',
                'nguyen_nhans_text' => "Hong bo giam xoc\nMay dat lech chan",
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.ten_trieu_chung', 'May rung lac manh khi vat')
            ->assertJsonPath('data.nguyen_nhan_count', 2);

        $symptomId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('nguyen_nhan', ['ten_nguyen_nhan' => 'Hong bo giam xoc']);
        $this->assertDatabaseHas('nguyen_nhan', ['ten_nguyen_nhan' => 'May dat lech chan']);
        $this->assertSame(2, DB::table('trieu_chung_nguyen_nhan')->where('trieu_chung_id', $symptomId)->count());

        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/trieu-chung/' . $symptomId, [
                'dich_vu_id' => $serviceId,
                'ten_trieu_chung' => 'May rung lac manh khi vat',
                'nguyen_nhans_text' => "Hong bo giam xoc\nDay curoa gian",
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.nguyen_nhan_count', 2);

        $this->assertDatabaseHas('nguyen_nhan', ['ten_nguyen_nhan' => 'Day curoa gian']);
        $this->assertSame(2, DB::table('trieu_chung_nguyen_nhan')->where('trieu_chung_id', $symptomId)->count());

        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/trieu-chung?search=Day%20curoa');

        $listResponse->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.items.0.id', $symptomId);

        $deleteResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/trieu-chung/' . $symptomId);

        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('trieu_chung', ['id' => $symptomId]);
    }

    public function test_admin_can_create_update_and_delete_resolution(): void
    {
        $admin = $this->createAdmin('resolution-admin@example.com');
        $token = $admin->createToken('catalog-admin')->plainTextToken;
        $serviceId = $this->createService('Sua may lanh');
        $causeId = $this->createCauseWithSymptom($serviceId, 'May lanh khong lanh', 'Thieu gas');

        $createResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/huong-xu-ly', [
                'nguyen_nhan_id' => $causeId,
                'ten_huong_xu_ly' => 'Nap gas va kiem tra ro ri',
                'gia_tham_khao' => 350000,
                'mo_ta_cong_viec' => 'Do ap suat, kiem tra ro ri va nap them gas.',
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.cause_name', 'Thieu gas')
            ->assertJsonPath('data.ten_huong_xu_ly', 'Nap gas va kiem tra ro ri');

        $resolutionId = (int) $createResponse->json('data.id');

        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/huong-xu-ly?service_id=' . $serviceId . '&cause_id=' . $causeId);

        $listResponse->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.items.0.id', $resolutionId);

        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/huong-xu-ly/' . $resolutionId, [
                'nguyen_nhan_id' => $causeId,
                'ten_huong_xu_ly' => 'Nap gas, va sinh dan nong',
                'gia_tham_khao' => 420000,
                'mo_ta_cong_viec' => 'Nap gas, sinh dan nong va kiem tra lai he thong.',
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.ten_huong_xu_ly', 'Nap gas, va sinh dan nong')
            ->assertJsonPath('data.gia_tham_khao', 420000);

        $deleteResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/huong-xu-ly/' . $resolutionId);

        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('huong_xu_ly', ['id' => $resolutionId]);
    }

    private function createAdmin(string $email = 'catalog-admin@example.com'): User
    {
        return User::query()->create([
            'name' => 'Admin Catalog',
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createService(string $name): int
    {
        return (int) DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => $name,
            'mo_ta' => 'Dich vu test cho admin catalog',
            'hinh_anh' => null,
            'trang_thai' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCauseWithSymptom(int $serviceId, string $symptomName, string $causeName): int
    {
        $symptomId = (int) DB::table('trieu_chung')->insertGetId([
            'dich_vu_id' => $serviceId,
            'ten_trieu_chung' => $symptomName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $causeId = (int) DB::table('nguyen_nhan')->insertGetId([
            'ten_nguyen_nhan' => $causeName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('trieu_chung_nguyen_nhan')->insert([
            'trieu_chung_id' => $symptomId,
            'nguyen_nhan_id' => $causeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $causeId;
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
                $table->enum('role', ['admin', 'customer', 'worker'])->default('customer');
                $table->boolean('is_active')->default(true);
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

        if (!Schema::hasTable('linh_kien')) {
            Schema::create('linh_kien', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dich_vu_id');
                $table->string('ten_linh_kien');
                $table->string('hinh_anh')->nullable();
                $table->decimal('gia', 15, 2)->nullable();
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
        foreach ([
            'huong_xu_ly',
            'trieu_chung_nguyen_nhan',
            'nguyen_nhan',
            'trieu_chung',
            'linh_kien',
            'danh_muc_dich_vu',
            'personal_access_tokens',
            'users',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
