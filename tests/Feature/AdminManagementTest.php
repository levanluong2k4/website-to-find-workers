<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_admin_can_create_update_and_soft_delete_service(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('admin-test')->plainTextToken;

        $createResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/services', [
                'ten_dich_vu' => 'Sua may giat',
                'mo_ta' => 'Sua may giat tai nha',
                'hinh_anh' => 'https://example.com/may-giat.png',
                'trang_thai' => true,
            ]);

        $createResponse->assertCreated();
        $serviceId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('danh_muc_dich_vu', [
            'id' => $serviceId,
            'ten_dich_vu' => 'Sua may giat',
            'trang_thai' => 1,
        ]);

        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/services/' . $serviceId, [
                'ten_dich_vu' => 'Sua may giat inverter',
                'mo_ta' => 'Cap nhat mo ta',
                'hinh_anh' => 'https://example.com/new.png',
                'trang_thai' => false,
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.ten_dich_vu', 'Sua may giat inverter')
            ->assertJsonPath('data.trang_thai', 0);

        $deleteResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/services/' . $serviceId);

        $deleteResponse->assertOk();
        $this->assertDatabaseHas('danh_muc_dich_vu', [
            'id' => $serviceId,
            'trang_thai' => 0,
        ]);
    }

    public function test_admin_can_approve_worker_profile(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('admin-test')->plainTextToken;

        $worker = User::query()->create([
            'name' => 'Worker Pending',
            'email' => 'worker-pending@example.com',
            'password' => bcrypt('password'),
            'role' => 'worker',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ho_so_tho')->insert([
            'user_id' => $worker->id,
            'cccd' => '012345678901',
            'trang_thai_duyet' => 'cho_duyet',
            'ghi_chu_admin' => null,
            'dang_hoat_dong' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/admin/worker-profiles/' . $worker->id . '/approval', [
                'trang_thai_duyet' => 'da_duyet',
                'ghi_chu_admin' => 'Ho so hop le',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.trang_thai_duyet', 'da_duyet')
            ->assertJsonPath('data.ghi_chu_admin', 'Ho so hop le');

        $this->assertDatabaseHas('ho_so_tho', [
            'user_id' => $worker->id,
            'trang_thai_duyet' => 'da_duyet',
            'dang_hoat_dong' => 1,
        ]);
    }

    public function test_dashboard_stats_include_pending_worker_profiles_count(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('admin-test')->plainTextToken;

        $worker = User::query()->create([
            'name' => 'Pending Worker',
            'email' => 'pending-worker@example.com',
            'password' => bcrypt('password'),
            'role' => 'worker',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ho_so_tho')->insert([
            'user_id' => $worker->id,
            'cccd' => '098765432112',
            'trang_thai_duyet' => 'cho_duyet',
            'ghi_chu_admin' => null,
            'dang_hoat_dong' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.users.pending_worker_profiles', 1);
    }

    public function test_admin_can_use_customer_and_worker_booking_permissions(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('admin-test')->plainTextToken;

        $serviceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Sua tu lanh',
            'mo_ta' => 'Dich vu sua tu lanh',
            'hinh_anh' => null,
            'trang_thai' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $storeResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/don-dat-lich', [
                'loai_dat_lich' => 'at_store',
                'dich_vu_id' => $serviceId,
                'ngay_hen' => now()->addDay()->toDateString(),
                'khung_gio_hen' => '08:00-10:00',
                'mo_ta_van_de' => 'Tu lanh khong mat',
            ]);

        $storeResponse->assertCreated();
        $bookingId = (int) $storeResponse->json('data.id');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'khach_hang_id' => $admin->id,
            'trang_thai' => 'cho_xac_nhan',
        ]);

        $availableJobsResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/don-dat-lich/available');

        $availableJobsResponse->assertOk();
        $this->assertCount(1, $availableJobsResponse->json());

        $profileUpdateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/ho-so-tho', [
                'kinh_nghiem' => 'Admin test worker scope',
                'dang_hoat_dong' => true,
            ]);

        $profileUpdateResponse->assertOk()
            ->assertJsonPath('data.user_id', $admin->id);
    }

    private function createAdmin(): User
    {
        return User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-test@example.com',
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

        if (!Schema::hasTable('ho_so_tho')) {
            Schema::create('ho_so_tho', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('cccd')->nullable();
                $table->text('kinh_nghiem')->nullable();
                $table->text('chung_chi')->nullable();
                $table->string('bang_gia_tham_khao')->nullable();
                $table->decimal('vi_do', 10, 7)->nullable();
                $table->decimal('kinh_do', 10, 7)->nullable();
                $table->decimal('ban_kinh_phuc_vu', 8, 2)->nullable();
                $table->enum('trang_thai_duyet', ['cho_duyet', 'da_duyet', 'tu_choi'])->default('cho_duyet');
                $table->text('ghi_chu_admin')->nullable();
                $table->boolean('dang_hoat_dong')->default(true);
                $table->enum('trang_thai_hoat_dong', ['dang_hoat_dong', 'dang_ban', 'ngung_hoat_dong', 'tam_khoa'])->default('dang_hoat_dong');
                $table->decimal('danh_gia_trung_binh', 3, 2)->default(0);
                $table->unsignedInteger('tong_so_danh_gia')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tho_dich_vu')) {
            Schema::create('tho_dich_vu', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('dich_vu_id');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('don_dat_lich')) {
            Schema::create('don_dat_lich', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('khach_hang_id')->nullable();
                $table->unsignedBigInteger('tho_id')->nullable();
                $table->unsignedBigInteger('dich_vu_id')->nullable();
                $table->string('loai_dat_lich')->nullable();
                $table->date('ngay_hen')->nullable();
                $table->string('khung_gio_hen')->nullable();
                $table->timestamp('thoi_gian_hen')->nullable();
                $table->text('mo_ta_van_de')->nullable();
                $table->boolean('thue_xe_cho')->default(false);
                $table->string('trang_thai')->default('cho_xac_nhan');
                $table->string('phuong_thuc_thanh_toan')->nullable();
                $table->timestamp('thoi_gian_het_han_nhan')->nullable();
                $table->string('dia_chi')->nullable();
                $table->decimal('vi_do', 10, 7)->nullable();
                $table->decimal('kinh_do', 10, 7)->nullable();
                $table->decimal('khoang_cach', 8, 2)->nullable();
                $table->decimal('phi_di_lai', 12, 2)->default(0);
                $table->decimal('phi_linh_kien', 12, 2)->default(0);
                $table->decimal('tien_cong', 12, 2)->default(0);
                $table->decimal('tien_thue_xe', 12, 2)->default(0);
                $table->decimal('tong_tien', 12, 2)->default(0);
                $table->boolean('trang_thai_thanh_toan')->default(false);
                $table->text('ly_do_huy')->nullable();
                $table->text('ghi_chu_linh_kien')->nullable();
                $table->json('hinh_anh_mo_ta')->nullable();
                $table->string('video_mo_ta')->nullable();
                $table->json('hinh_anh_ket_qua')->nullable();
                $table->string('video_ket_qua')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('danh_gia')) {
            Schema::create('danh_gia', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('don_dat_lich_id');
                $table->unsignedBigInteger('nguoi_danh_gia_id')->nullable();
                $table->unsignedBigInteger('nguoi_bi_danh_gia_id')->nullable();
                $table->unsignedTinyInteger('so_sao')->default(5);
                $table->text('nhan_xet')->nullable();
                $table->unsignedTinyInteger('so_lan_sua')->default(0);
                $table->timestamps();
            });
        }
    }

    private function truncateTables(): void
    {
        foreach ([
            'danh_gia',
            'don_dat_lich',
            'tho_dich_vu',
            'ho_so_tho',
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
