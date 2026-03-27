<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingStoreCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_store_populates_legacy_dich_vu_id_when_request_uses_dich_vu_ids(): void
    {
        Notification::fake();

        $customer = $this->createUser('customer@example.com', 'customer');
        $serviceId = $this->createService('Sua may lanh');
        $worker = $this->createWorkerWithService($serviceId);

        $token = $customer->createToken('customer-booking')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/don-dat-lich', [
                'tho_id' => $worker->id,
                'loai_dat_lich' => 'at_store',
                'dich_vu_ids' => [$serviceId],
                'ngay_hen' => now()->addDay()->toDateString(),
                'khung_gio_hen' => '08:00-10:00',
                'mo_ta_van_de' => 'May lanh khong hoat dong',
            ]);

        $response->assertCreated();

        $bookingId = (int) $response->json('data.id');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'dich_vu_id' => $serviceId,
        ]);

        $this->assertDatabaseHas('don_dat_lich_dich_vu', [
            'don_dat_lich_id' => $bookingId,
            'dich_vu_id' => $serviceId,
        ]);
    }

    public function test_store_accepts_legacy_dich_vu_id_payload_and_normalizes_it(): void
    {
        Notification::fake();

        $customer = $this->createUser('legacy-customer@example.com', 'customer');
        $serviceId = $this->createService('Sua tu lanh');
        $worker = $this->createWorkerWithService($serviceId, 'legacy-worker@example.com');

        $token = $customer->createToken('customer-booking-legacy')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/don-dat-lich', [
                'tho_id' => $worker->id,
                'loai_dat_lich' => 'at_store',
                'dich_vu_id' => $serviceId,
                'ngay_hen' => now()->addDay()->toDateString(),
                'khung_gio_hen' => '10:00-12:00',
                'mo_ta_van_de' => 'Tu lanh khong lanh',
            ]);

        $response->assertCreated();

        $bookingId = (int) $response->json('data.id');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'dich_vu_id' => $serviceId,
        ]);

        $this->assertDatabaseHas('don_dat_lich_dich_vu', [
            'don_dat_lich_id' => $bookingId,
            'dich_vu_id' => $serviceId,
        ]);
    }

    public function test_store_uses_configured_travel_fee_tier_for_at_home_booking(): void
    {
        Notification::fake();

        $customer = $this->createUser('travel-customer@example.com', 'customer');
        $serviceId = $this->createService('Sua may lanh tai nha');
        $worker = $this->createWorkerWithService($serviceId, 'travel-worker@example.com');

        DB::table('ho_so_tho')
            ->where('user_id', $worker->id)
            ->update([
                'vi_do' => 12.2618,
                'kinh_do' => 109.1995,
                'ban_kinh_phuc_vu' => 8,
            ]);

        DB::table('app_settings')->insert([
            'key' => 'travel_fee_config',
            'value' => json_encode([
                'default_per_km' => 5000,
                'tiers' => [
                    ['from_km' => 0, 'to_km' => 2, 'fee' => 15000],
                    ['from_km' => 2, 'to_km' => 5, 'fee' => 30000],
                ],
            ]),
            'updated_by' => $worker->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $customer->createToken('customer-travel-booking')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/don-dat-lich', [
                'tho_id' => $worker->id,
                'loai_dat_lich' => 'at_home',
                'dich_vu_id' => $serviceId,
                'ngay_hen' => now()->addDay()->toDateString(),
                'khung_gio_hen' => '08:00-10:00',
                'dia_chi' => '12 Tran Phu, Nha Trang',
                'vi_do' => 12.2700,
                'kinh_do' => 109.1995,
                'mo_ta_van_de' => 'May lanh bi chay nuoc',
            ]);

        $response->assertCreated();

        $bookingId = (int) $response->json('data.id');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'phi_di_lai' => 15000,
        ]);
    }

    private function createUser(string $email, string $role): User
    {
        return User::query()->create([
            'name' => ucfirst($role) . ' User',
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createService(string $name): int
    {
        return (int) DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => $name,
            'mo_ta' => $name,
            'hinh_anh' => null,
            'trang_thai' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createWorkerWithService(int $serviceId, string $email = 'worker@example.com'): User
    {
        $worker = $this->createUser($email, 'worker');

        DB::table('ho_so_tho')->insert([
            'user_id' => $worker->id,
            'cccd' => 'CCCD_' . $worker->id,
            'trang_thai_duyet' => 'da_duyet',
            'dang_hoat_dong' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tho_dich_vu')->insert([
            'user_id' => $worker->id,
            'dich_vu_id' => $serviceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $worker;
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
                $table->decimal('vi_do', 10, 7)->nullable();
                $table->decimal('kinh_do', 10, 7)->nullable();
                $table->decimal('ban_kinh_phuc_vu', 8, 2)->nullable();
                $table->boolean('dang_hoat_dong')->default(true);
                $table->enum('trang_thai_duyet', ['cho_duyet', 'da_duyet', 'tu_choi'])->default('cho_duyet');
                $table->boolean('is_active')->default(true);
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
                $table->unsignedBigInteger('dich_vu_id');
                $table->enum('loai_dat_lich', ['at_home', 'at_store'])->default('at_home');
                $table->date('ngay_hen')->nullable();
                $table->string('khung_gio_hen')->nullable();
                $table->timestamp('thoi_gian_hen')->nullable();
                $table->text('mo_ta_van_de')->nullable();
                $table->boolean('thue_xe_cho')->default(false);
                $table->string('trang_thai')->default('cho_xac_nhan');
                $table->string('phuong_thuc_thanh_toan')->default('cod');
                $table->timestamp('thoi_gian_het_han_nhan')->nullable();
                $table->string('dia_chi')->nullable();
                $table->decimal('vi_do', 10, 7)->nullable();
                $table->decimal('kinh_do', 10, 7)->nullable();
                $table->decimal('khoang_cach', 8, 2)->nullable();
                $table->decimal('phi_di_lai', 12, 2)->default(0);
                $table->json('hinh_anh_mo_ta')->nullable();
                $table->string('video_mo_ta')->nullable();
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
    }

    private function truncateTables(): void
    {
        foreach ([
            'don_dat_lich_dich_vu',
            'don_dat_lich',
            'app_settings',
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
