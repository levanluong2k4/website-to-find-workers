<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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

    public function test_admin_can_update_worker_even_when_worker_profile_is_missing(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('admin-test')->plainTextToken;

        $worker = User::query()->create([
            'name' => 'Worker Legacy',
            'email' => 'worker-legacy@example.com',
            'password' => bcrypt('password'),
            'phone' => '0900000004',
            'role' => 'worker',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $serviceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Sua may giat',
            'mo_ta' => 'Dich vu cho worker legacy',
            'hinh_anh' => null,
            'trang_thai' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/workers/' . $worker->id, [
                'name' => 'Worker Legacy Updated',
                'email' => 'worker-legacy@example.com',
                'phone' => '0900000004',
                'cccd' => '012345678901',
                'address' => '123 Duong Test',
                'kinh_nghiem' => '5 nam kinh nghiem',
                'dich_vu_ids' => [$serviceId],
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Worker Legacy Updated')
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.ho_so_tho.cccd', '012345678901')
            ->assertJsonPath('data.ho_so_tho.kinh_nghiem', '5 nam kinh nghiem');

        $this->assertDatabaseHas('ho_so_tho', [
            'user_id' => $worker->id,
            'cccd' => '012345678901',
            'kinh_nghiem' => '5 nam kinh nghiem',
            'trang_thai_duyet' => 'da_duyet',
            'dang_hoat_dong' => 0,
            'trang_thai_hoat_dong' => 'tam_khoa',
        ]);

        $this->assertDatabaseHas('tho_dich_vu', [
            'user_id' => $worker->id,
            'dich_vu_id' => $serviceId,
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

    public function test_admin_can_update_travel_fee_config(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('admin-test')->plainTextToken;

        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/travel-fee-config', [
                'store_address' => '25 Nguyen Thi Minh Khai, Nha Trang',
                'store_latitude' => 12.2388,
                'store_longitude' => 109.1967,
                'max_service_distance_km' => 8,
                'default_per_km' => 5000,
                'tiers' => [
                    ['from_km' => 0, 'to_km' => 2, 'transport_fee' => 0, 'travel_fee' => 15000],
                    ['from_km' => 2.01, 'to_km' => 5, 'transport_fee' => 0, 'travel_fee' => 30000],
                ],
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.config.store_address', '25 Nguyen Thi Minh Khai, Nha Trang')
            ->assertJsonPath('data.config.store_latitude', 12.2388)
            ->assertJsonPath('data.config.store_longitude', 109.1967)
            ->assertJsonPath('data.config.max_service_distance_km', 8)
            ->assertJsonPath('data.config.default_per_km', 5000)
            ->assertJsonPath('data.config.tiers.0.travel_fee', 15000)
            ->assertJsonPath('data.config.tiers.1.to_km', 5);

        $this->assertDatabaseHas('app_settings', [
            'key' => 'travel_fee_config',
            'updated_by' => $admin->id,
        ]);

        $getResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/travel-fee-config');

        $getResponse->assertOk()
            ->assertJsonPath('data.config.max_service_distance_km', 8)
            ->assertJsonPath('data.config.tiers.0.from_km', 0)
            ->assertJsonPath('data.preview.samples.2.fee', 30000);
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

        DB::table('tho_dich_vu')->insert([
            'user_id' => $admin->id,
            'dich_vu_id' => $serviceId,
            'created_at' => now(),
            'updated_at' => now(),
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

    public function test_booking_with_selected_worker_rejects_service_outside_worker_skills(): void
    {
        Notification::fake();

        $customer = User::query()->create([
            'name' => 'Customer Booking',
            'email' => 'customer-booking@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $worker = User::query()->create([
            'name' => 'Worker Booking',
            'email' => 'worker-booking@example.com',
            'password' => bcrypt('password'),
            'role' => 'worker',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ho_so_tho')->insert([
            'user_id' => $worker->id,
            'cccd' => '012345678999',
            'trang_thai_duyet' => 'da_duyet',
            'ghi_chu_admin' => null,
            'dang_hoat_dong' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workerServiceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Sua lo vi song',
            'mo_ta' => 'Worker co the sua',
            'hinh_anh' => null,
            'trang_thai' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherServiceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Sua tu lanh',
            'mo_ta' => 'Ngoai chuyen mon',
            'hinh_anh' => null,
            'trang_thai' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tho_dich_vu')->insert([
            'user_id' => $worker->id,
            'dich_vu_id' => $workerServiceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $customer->createToken('customer-booking-test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/don-dat-lich', [
                'tho_id' => $worker->id,
                'loai_dat_lich' => 'at_store',
                'dich_vu_id' => $otherServiceId,
                'ngay_hen' => now()->addDay()->toDateString(),
                'khung_gio_hen' => '08:00-10:00',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['dich_vu_ids']);

        $this->assertDatabaseCount('don_dat_lich', 0);
    }

    public function test_booking_with_selected_worker_is_assigned_immediately(): void
    {
        Notification::fake();

        $customer = User::query()->create([
            'name' => 'Customer Private Booking',
            'email' => 'customer-private@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $chosenWorker = User::query()->create([
            'name' => 'Chosen Worker',
            'email' => 'chosen-worker@example.com',
            'password' => bcrypt('password'),
            'role' => 'worker',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherWorker = User::query()->create([
            'name' => 'Other Worker',
            'email' => 'other-worker@example.com',
            'password' => bcrypt('password'),
            'role' => 'worker',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([$chosenWorker->id, $otherWorker->id] as $workerId) {
            DB::table('ho_so_tho')->insert([
                'user_id' => $workerId,
                'cccd' => 'CCCD_' . $workerId,
                'trang_thai_duyet' => 'da_duyet',
                'ghi_chu_admin' => null,
                'dang_hoat_dong' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $serviceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Dien nuoc dan dung',
            'mo_ta' => 'Dich vu worker co the nhan',
            'hinh_anh' => null,
            'trang_thai' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tho_dich_vu')->insert([
            'user_id' => $chosenWorker->id,
            'dich_vu_id' => $serviceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customerToken = $customer->createToken('customer-private-booking')->plainTextToken;

        $storeResponse = $this->withHeader('Authorization', 'Bearer ' . $customerToken)
            ->postJson('/api/don-dat-lich', [
                'tho_id' => $chosenWorker->id,
                'loai_dat_lich' => 'at_store',
                'dich_vu_id' => $serviceId,
                'ngay_hen' => now()->addDay()->toDateString(),
                'khung_gio_hen' => '10:00-12:00',
            ]);

        $storeResponse->assertCreated()
            ->assertJsonPath('data.tho_id', $chosenWorker->id);
        $bookingId = (int) $storeResponse->json('data.id');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'tho_id' => $chosenWorker->id,
            'trang_thai' => 'cho_xac_nhan',
        ]);

        $this->assertNotNull(
            DB::table('don_dat_lich')->where('id', $bookingId)->value('thoi_gian_het_han_nhan')
        );
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

        if (!Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->json('value')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ai_knowledge_items')) {
            Schema::create('ai_knowledge_items', function (Blueprint $table) {
                $table->id();
                $table->string('source_type');
                $table->unsignedBigInteger('source_id');
                $table->string('source_key')->unique();
                $table->unsignedBigInteger('primary_service_id')->nullable();
                $table->string('service_name')->nullable();
                $table->string('title')->nullable();
                $table->longText('content')->nullable();
                $table->longText('normalized_content')->nullable();
                $table->text('symptom_text')->nullable();
                $table->text('cause_text')->nullable();
                $table->text('solution_text')->nullable();
                $table->text('price_context')->nullable();
                $table->decimal('rating_avg', 5, 2)->nullable();
                $table->decimal('quality_score', 8, 4)->nullable();
                $table->json('metadata')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('published_at')->nullable();
                $table->string('qdrant_document_hash')->nullable();
                $table->timestamp('qdrant_synced_at')->nullable();
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
                $table->timestamp('thoi_gian_hoan_thanh')->nullable();
                $table->text('ly_do_huy')->nullable();
                $table->text('ghi_chu_linh_kien')->nullable();
                $table->json('hinh_anh_mo_ta')->nullable();
                $table->string('video_mo_ta')->nullable();
                $table->json('hinh_anh_ket_qua')->nullable();
                $table->string('video_ket_qua')->nullable();
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
            'don_dat_lich_dich_vu',
            'don_dat_lich',
            'tho_dich_vu',
            'ho_so_tho',
            'app_settings',
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
