<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\BookingStatusNotification;
use App\Notifications\NewBookingNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDispatchApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_dispatch_show_only_returns_workers_matching_service_and_open_slot(): void
    {
        $admin = $this->createUser('dispatch-admin@example.com', 'admin');
        $customer = $this->createUser('dispatch-customer@example.com', 'customer');
        $serviceId = $this->createService('Sua may lanh');
        $otherServiceId = $this->createService('Sua may giat');

        $bookingId = $this->createBooking($customer->id, [
            'ngay_hen' => now()->addDay()->toDateString(),
            'khung_gio_hen' => '08:00-10:00',
            'trang_thai' => 'cho_xac_nhan',
        ], [$serviceId]);

        $availableWorker = $this->createWorkerWithServices([$serviceId], 'dispatch-available@example.com');
        $busyWorker = $this->createWorkerWithServices([$serviceId], 'dispatch-busy@example.com');
        $wrongServiceWorker = $this->createWorkerWithServices([$otherServiceId], 'dispatch-wrong@example.com');

        $this->createBooking($customer->id, [
            'tho_id' => $busyWorker->id,
            'ngay_hen' => now()->addDay()->toDateString(),
            'khung_gio_hen' => '08:00-10:00',
            'trang_thai' => 'da_xac_nhan',
        ], [$serviceId]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/dispatch/' . $bookingId);

        $response->assertOk();
        $response->assertJsonPath('data.eligibility.matching_workers', 2);
        $response->assertJsonPath('data.eligibility.available_workers', 1);
        $response->assertJsonPath('data.eligibility.unavailable_workers', 1);
        $response->assertJsonPath('data.eligibility.busy_workers', 1);

        $candidateIds = collect($response->json('data.candidates'))->pluck('id')->all();
        $unavailableCandidateIds = collect($response->json('data.unavailable_candidates'))->pluck('id')->all();

        $this->assertSame([$availableWorker->id], $candidateIds);
        $this->assertSame([$busyWorker->id], $unavailableCandidateIds);
        $this->assertNotContains($wrongServiceWorker->id, $candidateIds);
        $this->assertNotContains($wrongServiceWorker->id, $unavailableCandidateIds);
    }

    public function test_admin_can_assign_dispatch_booking_to_eligible_worker(): void
    {
        Notification::fake();

        $admin = $this->createUser('dispatch-assign-admin@example.com', 'admin');
        $customer = $this->createUser('dispatch-assign-customer@example.com', 'customer');
        $serviceId = $this->createService('Sua tu lanh');
        $worker = $this->createWorkerWithServices([$serviceId], 'dispatch-assign-worker@example.com');

        $bookingId = $this->createBooking($customer->id, [
            'ngay_hen' => now()->addDays(2)->toDateString(),
            'khung_gio_hen' => '10:00-12:00',
            'trang_thai' => 'cho_xac_nhan',
        ], [$serviceId]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/dispatch/' . $bookingId . '/assign', [
            'worker_id' => $worker->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.booking.worker_name', $worker->name);

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'tho_id' => $worker->id,
            'trang_thai' => 'da_xac_nhan',
        ]);

        Notification::assertSentTo($worker, NewBookingNotification::class);
        Notification::assertSentTo($customer, BookingStatusNotification::class);
    }

    public function test_admin_cannot_assign_worker_when_schedule_conflict_appears(): void
    {
        $admin = $this->createUser('dispatch-conflict-admin@example.com', 'admin');
        $customer = $this->createUser('dispatch-conflict-customer@example.com', 'customer');
        $serviceId = $this->createService('Sua dieu hoa');
        $worker = $this->createWorkerWithServices([$serviceId], 'dispatch-conflict-worker@example.com');

        $bookingId = $this->createBooking($customer->id, [
            'ngay_hen' => now()->addDay()->toDateString(),
            'khung_gio_hen' => '13:00-15:00',
            'trang_thai' => 'cho_xac_nhan',
        ], [$serviceId]);

        $this->createBooking($customer->id, [
            'tho_id' => $worker->id,
            'ngay_hen' => now()->addDay()->toDateString(),
            'khung_gio_hen' => '13:00-15:00',
            'trang_thai' => 'dang_lam',
        ], [$serviceId]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/dispatch/' . $bookingId . '/assign', [
            'worker_id' => $worker->id,
        ]);

        $response->assertStatus(409);
        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'tho_id' => null,
            'trang_thai' => 'cho_xac_nhan',
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
            'phone' => '0900000' . random_int(100, 999),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createService(string $name): int
    {
        return (int) DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => $name,
            'mo_ta' => $name,
            'trang_thai' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createWorkerWithServices(array $serviceIds, string $email): User
    {
        $worker = $this->createUser($email, 'worker');

        DB::table('ho_so_tho')->insert([
            'user_id' => $worker->id,
            'cccd' => 'CCCD_' . $worker->id,
            'trang_thai_duyet' => 'da_duyet',
            'dang_hoat_dong' => true,
            'trang_thai_hoat_dong' => 'dang_hoat_dong',
            'danh_gia_trung_binh' => 4.8,
            'tong_so_danh_gia' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($serviceIds as $serviceId) {
            DB::table('tho_dich_vu')->insert([
                'user_id' => $worker->id,
                'dich_vu_id' => $serviceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $worker;
    }

    private function createBooking(int $customerId, array $overrides, array $serviceIds): int
    {
        $scheduleDate = $overrides['ngay_hen'] ?? now()->addDay()->toDateString();
        $timeSlot = $overrides['khung_gio_hen'] ?? '08:00-10:00';
        [$startTime] = explode('-', $timeSlot);

        $bookingId = (int) DB::table('don_dat_lich')->insertGetId(array_merge([
            'khach_hang_id' => $customerId,
            'tho_id' => null,
            'dich_vu_id' => $serviceIds[0] ?? null,
            'loai_dat_lich' => 'at_home',
            'ngay_hen' => $scheduleDate,
            'khung_gio_hen' => $timeSlot,
            'thoi_gian_hen' => $scheduleDate . ' ' . $startTime . ':00',
            'dia_chi' => '12 Nguyen Thi Minh Khai, Nha Trang',
            'mo_ta_van_de' => 'May khong chay',
            'trang_thai' => 'cho_xac_nhan',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        foreach ($serviceIds as $serviceId) {
            DB::table('don_dat_lich_dich_vu')->insert([
                'don_dat_lich_id' => $bookingId,
                'dich_vu_id' => $serviceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $bookingId;
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
                $table->timestamp('phone_verified_at')->nullable();
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
                $table->enum('trang_thai_duyet', ['cho_duyet', 'da_duyet', 'tu_choi'])->default('cho_duyet');
                $table->boolean('dang_hoat_dong')->default(true);
                $table->string('trang_thai_hoat_dong')->nullable();
                $table->float('danh_gia_trung_binh')->nullable();
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
                $table->enum('loai_dat_lich', ['at_home', 'at_store'])->default('at_home');
                $table->timestamp('thoi_gian_hen')->nullable();
                $table->timestamp('worker_reminder_sent_at')->nullable();
                $table->timestamp('thoi_gian_hoan_thanh')->nullable();
                $table->date('ngay_hen')->nullable();
                $table->string('khung_gio_hen')->nullable();
                $table->unsignedInteger('so_lan_doi_lich')->default(0);
                $table->string('dia_chi')->nullable();
                $table->decimal('vi_do', 10, 7)->nullable();
                $table->decimal('kinh_do', 10, 7)->nullable();
                $table->text('mo_ta_van_de')->nullable();
                $table->text('giai_phap')->nullable();
                $table->decimal('khoang_cach', 8, 2)->nullable();
                $table->decimal('phi_di_lai', 12, 2)->nullable();
                $table->decimal('phi_linh_kien', 12, 2)->nullable();
                $table->text('ghi_chu_linh_kien')->nullable();
                $table->json('chi_tiet_tien_cong')->nullable();
                $table->json('chi_tiet_linh_kien')->nullable();
                $table->timestamp('thoi_gian_het_han_nhan')->nullable();
                $table->string('trang_thai')->default('cho_xac_nhan');
                $table->string('ma_ly_do_huy')->nullable();
                $table->text('ly_do_huy')->nullable();
                $table->decimal('tong_tien', 12, 2)->nullable();
                $table->boolean('gia_da_cap_nhat')->default(false);
                $table->string('phuong_thuc_thanh_toan')->nullable();
                $table->boolean('trang_thai_thanh_toan')->default(false);
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
    }

    private function truncateTables(): void
    {
        foreach ([
            'don_dat_lich_dich_vu',
            'don_dat_lich',
            'tho_dich_vu',
            'ho_so_tho',
            'danh_muc_dich_vu',
            'users',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
