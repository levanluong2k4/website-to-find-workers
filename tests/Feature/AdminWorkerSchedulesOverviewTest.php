<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminWorkerSchedulesOverviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_admin_can_fetch_weekly_worker_schedule_overview_with_free_and_busy_slots(): void
    {
        $admin = $this->createUser('schedule-admin@example.com', 'admin');
        $customer = $this->createUser('schedule-customer@example.com', 'customer');
        $serviceId = $this->createService('Sửa máy lạnh');

        $busyWorker = $this->createWorker('schedule-busy@example.com', true, 'dang_hoat_dong', [$serviceId]);
        $freeWorker = $this->createWorker('schedule-free@example.com', true, 'dang_hoat_dong', [$serviceId]);
        $offlineWorker = $this->createWorker('schedule-offline@example.com', false, 'ngung_hoat_dong', [$serviceId]);

        $this->createBooking($customer->id, $busyWorker->id, $serviceId, [
            'ngay_hen' => '2026-04-20',
            'khung_gio_hen' => '08:00-10:00',
            'trang_thai' => 'da_xac_nhan',
            'dia_chi' => '12 Le Loi, Nha Trang',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/worker-schedules/overview?view=week&date=2026-04-20');

        $response->assertOk()
            ->assertJsonPath('data.meta.view', 'week')
            ->assertJsonPath('data.meta.date_from', '2026-04-20')
            ->assertJsonPath('data.meta.date_to', '2026-04-26')
            ->assertJsonPath('data.meta.day_count', 7)
            ->assertJsonPath('data.meta.slot_count', 4)
            ->assertJsonPath('data.meta.available_dates.0.date', '2026-04-20')
            ->assertJsonPath('data.meta.available_dates.0.booking_count', 1)
            ->assertJsonPath('data.summary.total_workers', 3)
            ->assertJsonPath('data.summary.total_busy_slots', 1)
            ->assertJsonPath('data.summary.offline_workers', 1);

        $workers = collect($response->json('data.workers'));

        $busyPayload = $workers->firstWhere('id', $busyWorker->id);
        $freePayload = $workers->firstWhere('id', $freeWorker->id);
        $offlinePayload = $workers->firstWhere('id', $offlineWorker->id);

        $this->assertNotNull($busyPayload);
        $this->assertNotNull($freePayload);
        $this->assertNotNull($offlinePayload);

        $this->assertSame('scheduled', data_get($busyPayload, 'current_status.key'));
        $this->assertSame(1, data_get($busyPayload, 'busy_slot_count'));
        $this->assertSame(27, data_get($busyPayload, 'free_slot_count'));
        $this->assertSame('DD-0001 • Sửa máy lạnh', data_get($busyPayload, 'current_booking_label'));

        $busyDay = collect(data_get($busyPayload, 'days'))->firstWhere('date', '2026-04-20');
        $this->assertSame(1, data_get($busyDay, 'busy_count'));
        $this->assertSame(3, data_get($busyDay, 'free_count'));
        $this->assertSame('busy', data_get($busyDay, 'slots.0.state'));
        $this->assertSame('free', data_get($busyDay, 'slots.1.state'));

        $this->assertSame('available', data_get($freePayload, 'current_status.key'));
        $this->assertSame(0, data_get($freePayload, 'busy_slot_count'));
        $this->assertSame(28, data_get($freePayload, 'free_slot_count'));

        $freeDay = collect(data_get($freePayload, 'days'))->firstWhere('date', '2026-04-20');
        $this->assertSame(4, data_get($freeDay, 'free_count'));
        $this->assertSame('free', data_get($freeDay, 'slots.0.state'));

        $this->assertSame('offline', data_get($offlinePayload, 'current_status.key'));
        $this->assertSame(0, data_get($offlinePayload, 'free_slot_count'));

        $offlineDay = collect(data_get($offlinePayload, 'days'))->firstWhere('date', '2026-04-20');
        $this->assertSame('offline', data_get($offlineDay, 'status_key'));
        $this->assertSame('offline', data_get($offlineDay, 'slots.0.state'));
    }

    private function createUser(string $email, string $role): User
    {
        return User::query()->create([
            'name' => ucfirst($role) . ' User',
            'email' => $email,
            'password' => bcrypt('password'),
            'phone' => '0900000' . random_int(100, 999),
            'address' => '1 Tran Phu, Nha Trang',
            'role' => $role,
            'is_active' => true,
            'phone_verified_at' => now(),
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

    private function createWorker(string $email, bool $isActive, string $operationalStatus, array $serviceIds): User
    {
        $worker = $this->createUser($email, 'worker');
        $worker->update(['name' => 'Worker ' . $worker->id]);

        DB::table('ho_so_tho')->insert([
            'user_id' => $worker->id,
            'cccd' => 'CCCD_' . $worker->id,
            'trang_thai_duyet' => 'da_duyet',
            'dang_hoat_dong' => $isActive,
            'trang_thai_hoat_dong' => $operationalStatus,
            'danh_gia_trung_binh' => 4.6,
            'tong_so_danh_gia' => 9,
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

        return $worker->fresh();
    }

    private function createBooking(int $customerId, int $workerId, int $serviceId, array $overrides = []): int
    {
        $date = $overrides['ngay_hen'] ?? '2026-04-20';
        $slot = $overrides['khung_gio_hen'] ?? '08:00-10:00';
        [$startTime] = explode('-', $slot);

        $bookingId = (int) DB::table('don_dat_lich')->insertGetId(array_merge([
            'khach_hang_id' => $customerId,
            'tho_id' => $workerId,
            'dich_vu_id' => $serviceId,
            'loai_dat_lich' => 'at_home',
            'ngay_hen' => $date,
            'khung_gio_hen' => $slot,
            'thoi_gian_hen' => $date . ' ' . trim($startTime) . ':00',
            'dia_chi' => '25 Hai Ba Trung, Nha Trang',
            'mo_ta_van_de' => 'Máy không hoạt động',
            'trang_thai' => 'da_xac_nhan',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        DB::table('don_dat_lich_dich_vu')->insert([
            'don_dat_lich_id' => $bookingId,
            'dich_vu_id' => $serviceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
