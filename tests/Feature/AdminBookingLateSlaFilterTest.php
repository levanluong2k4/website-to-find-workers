<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\BookingStatusNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminBookingLateSlaFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_worker_starting_after_slot_end_marks_booking_as_late_in_admin_sla_filter(): void
    {
        Notification::fake([BookingStatusNotification::class]);

        Carbon::setTestNow(Carbon::parse('2026-05-16 17:30:00', 'Asia/Ho_Chi_Minh'));

        $admin = $this->createUser('late-admin@example.com', 'admin');
        $worker = $this->createUser('late-worker@example.com', 'worker');
        $customer = $this->createUser('late-customer@example.com', 'customer');
        $serviceId = $this->createService('Sửa điều hòa');

        $bookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'dich_vu_id' => $serviceId,
            'ngay_hen' => '2026-05-16',
            'khung_gio_hen' => '16:00-17:00',
            'thoi_gian_hen' => '2026-05-16 16:00:00',
            'trang_thai' => 'da_xac_nhan',
        ], $serviceId);

        Sanctum::actingAs($worker);

        $this->putJson('/api/don-dat-lich/' . $bookingId . '/status', [
            'trang_thai' => 'dang_lam',
        ])->assertOk()
            ->assertJsonPath('data.trang_thai', 'dang_lam');

        $startedAt = DB::table('don_dat_lich')
            ->where('id', $bookingId)
            ->value('thoi_gian_bat_dau_sua');

        $this->assertNotNull($startedAt);
        $this->assertSame(
            '2026-05-16 17:30:00',
            Carbon::parse((string) $startedAt, 'UTC')->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
        );

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/bookings?sla=late');

        $response->assertOk()
            ->assertJsonPath('data.summary.late_count', 1)
            ->assertJsonPath('data.summary.late_worker_count', 1)
            ->assertJsonPath('data.summary.late_workers.0.worker_id', $worker->id)
            ->assertJsonPath('data.summary.late_workers.0.late_count', 1)
            ->assertJsonPath('data.items.0.id', $bookingId)
            ->assertJsonPath('data.items.0.sla_state', 'late')
            ->assertJsonPath('data.items.0.flags.is_late_start', true);

        $slaOptions = collect($response->json('data.filters.sla_options'));
        $this->assertTrue($slaOptions->contains(fn(array $option): bool => ($option['value'] ?? null) === 'late'));
    }

    public function test_completed_booking_with_late_start_still_appears_in_late_sla_filter(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-16 19:00:00', 'Asia/Ho_Chi_Minh'));

        $admin = $this->createUser('late-admin-2@example.com', 'admin');
        $worker = $this->createUser('late-worker-2@example.com', 'worker');
        $customer = $this->createUser('late-customer-2@example.com', 'customer');
        $serviceId = $this->createService('Sửa máy giặt');

        $lateBookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'dich_vu_id' => $serviceId,
            'ngay_hen' => '2026-05-16',
            'khung_gio_hen' => '16:00-17:00',
            'thoi_gian_hen' => '2026-05-16 16:00:00',
            'thoi_gian_hoan_thanh' => '2026-05-16 11:15:00',
            'trang_thai' => 'da_xong',
            'trang_thai_thanh_toan' => true,
            'updated_at' => '2026-05-16 10:20:00',
        ], $serviceId);

        $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'dich_vu_id' => $serviceId,
            'ngay_hen' => '2026-05-16',
            'khung_gio_hen' => '16:00-17:00',
            'thoi_gian_hen' => '2026-05-16 16:00:00',
            'thoi_gian_hoan_thanh' => '2026-05-16 09:45:00',
            'trang_thai' => 'da_xong',
            'trang_thai_thanh_toan' => true,
            'updated_at' => '2026-05-16 09:10:00',
        ], $serviceId);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/bookings?sla=late')
            ->assertOk()
            ->assertJsonPath('data.summary.late_count', 1)
            ->assertJsonPath('data.summary.late_worker_count', 1)
            ->assertJsonPath('data.summary.late_workers.0.worker_id', $worker->id)
            ->assertJsonPath('data.summary.late_workers.0.late_count', 1)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $lateBookingId)
            ->assertJsonPath('data.items.0.sla_state', 'late')
            ->assertJsonPath('data.items.0.flags.is_late_start', true);
    }

    public function test_late_sla_summary_groups_orders_by_worker(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-16 19:30:00', 'Asia/Ho_Chi_Minh'));

        $admin = $this->createUser('late-admin-3@example.com', 'admin');
        $workerA = $this->createUser('late-worker-a@example.com', 'worker');
        $workerB = $this->createUser('late-worker-b@example.com', 'worker');
        $customer = $this->createUser('late-customer-3@example.com', 'customer');
        $serviceId = $this->createService('Sửa tủ lạnh');

        $firstLateBookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $workerA->id,
            'dich_vu_id' => $serviceId,
            'ngay_hen' => '2026-05-16',
            'khung_gio_hen' => '15:00-16:00',
            'thoi_gian_hen' => '2026-05-16 15:00:00',
            'updated_at' => '2026-05-16 09:20:00',
            'thoi_gian_hoan_thanh' => '2026-05-16 10:00:00',
            'trang_thai' => 'da_xong',
        ], $serviceId);

        $secondLateBookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $workerA->id,
            'dich_vu_id' => $serviceId,
            'ngay_hen' => '2026-05-16',
            'khung_gio_hen' => '16:00-17:00',
            'thoi_gian_hen' => '2026-05-16 16:00:00',
            'updated_at' => '2026-05-16 10:35:00',
            'thoi_gian_hoan_thanh' => '2026-05-16 11:10:00',
            'trang_thai' => 'da_xong',
        ], $serviceId);

        $thirdLateBookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $workerB->id,
            'dich_vu_id' => $serviceId,
            'ngay_hen' => '2026-05-16',
            'khung_gio_hen' => '14:00-15:00',
            'thoi_gian_hen' => '2026-05-16 14:00:00',
            'updated_at' => '2026-05-16 08:10:00',
            'thoi_gian_hoan_thanh' => '2026-05-16 08:45:00',
            'trang_thai' => 'da_xong',
        ], $serviceId);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/bookings?sla=late');

        $response->assertOk()
            ->assertJsonPath('data.summary.late_count', 3)
            ->assertJsonPath('data.summary.late_worker_count', 2)
            ->assertJsonPath('data.summary.late_workers.0.worker_id', $workerA->id)
            ->assertJsonPath('data.summary.late_workers.0.late_count', 2)
            ->assertJsonPath('data.summary.late_workers.1.worker_id', $workerB->id)
            ->assertJsonPath('data.summary.late_workers.1.late_count', 1);

        $lateWorkers = collect($response->json('data.summary.late_workers'));
        $workerALateBookings = collect($lateWorkers->firstWhere('worker_id', $workerA->id)['booking_codes'] ?? []);
        $workerBLateBookings = collect($lateWorkers->firstWhere('worker_id', $workerB->id)['booking_codes'] ?? []);

        $this->assertTrue($workerALateBookings->contains('DD-' . str_pad((string) $firstLateBookingId, 4, '0', STR_PAD_LEFT)));
        $this->assertTrue($workerALateBookings->contains('DD-' . str_pad((string) $secondLateBookingId, 4, '0', STR_PAD_LEFT)));
        $this->assertTrue($workerBLateBookings->contains('DD-' . str_pad((string) $thirdLateBookingId, 4, '0', STR_PAD_LEFT)));
    }

    private function createUser(string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => ucfirst($role) . ' User',
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
            'phone' => '0900000' . random_int(100, 999),
            'address' => '12 Nguyen Thi Minh Khai, Nha Trang',
            'phone_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($role === 'worker') {
            DB::table('ho_so_tho')->insert([
                'user_id' => $user->id,
                'trang_thai_duyet' => 'da_duyet',
                'dang_hoat_dong' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $user;
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

    private function createBooking(array $overrides, int $serviceId): int
    {
        $bookingId = (int) DB::table('don_dat_lich')->insertGetId(array_merge([
            'khach_hang_id' => null,
            'tho_id' => null,
            'dich_vu_id' => $serviceId,
            'loai_dat_lich' => 'at_home',
            'ngay_hen' => null,
            'khung_gio_hen' => '16:00-17:00',
            'thoi_gian_hen' => null,
            'worker_reminder_sent_at' => null,
            'thoi_gian_bat_dau_sua' => null,
            'worker_contact_issue_reported_at' => null,
            'worker_contact_issue_resolved_at' => null,
            'worker_contact_issue_reported_by' => null,
            'worker_contact_issue_reporter_name' => null,
            'worker_contact_issue_called_phone' => null,
            'worker_contact_issue_note' => null,
            'thoi_gian_hoan_thanh' => null,
            'dia_chi' => '12 Nguyen Thi Minh Khai, Nha Trang',
            'mo_ta_van_de' => 'Thiết bị không hoạt động',
            'trang_thai' => 'da_xac_nhan',
            'phuong_thuc_thanh_toan' => 'cod',
            'trang_thai_thanh_toan' => false,
            'phi_di_lai' => 10000,
            'phi_linh_kien' => 0,
            'tien_cong' => 0,
            'tien_thue_xe' => 0,
            'tong_tien' => 10000,
            'gia_da_cap_nhat' => false,
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
                $table->rememberToken()->nullable();
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
                $table->string('trang_thai_duyet')->nullable();
                $table->boolean('dang_hoat_dong')->default(true);
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
                $table->timestamp('worker_reminder_sent_at')->nullable();
                $table->timestamp('thoi_gian_bat_dau_sua')->nullable();
                $table->timestamp('worker_contact_issue_reported_at')->nullable();
                $table->timestamp('worker_contact_issue_resolved_at')->nullable();
                $table->unsignedBigInteger('worker_contact_issue_reported_by')->nullable();
                $table->string('worker_contact_issue_reporter_name')->nullable();
                $table->string('worker_contact_issue_called_phone')->nullable();
                $table->text('worker_contact_issue_note')->nullable();
                $table->timestamp('thoi_gian_hoan_thanh')->nullable();
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
                $table->string('trang_thai')->nullable();
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
                $table->decimal('tien_cong', 12, 2)->nullable();
                $table->decimal('tien_thue_xe', 12, 2)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('don_dat_lich')) {
            $missingColumns = [];

            foreach ([
                'worker_contact_issue_reporter_name',
                'worker_contact_issue_called_phone',
                'thoi_gian_bat_dau_sua',
            ] as $column) {
                if (!Schema::hasColumn('don_dat_lich', $column)) {
                    $missingColumns[] = $column;
                }
            }

            if ($missingColumns !== []) {
                Schema::table('don_dat_lich', function (Blueprint $table) use ($missingColumns) {
                    if (in_array('worker_contact_issue_reporter_name', $missingColumns, true)) {
                        $table->string('worker_contact_issue_reporter_name')->nullable();
                    }

                    if (in_array('worker_contact_issue_called_phone', $missingColumns, true)) {
                        $table->string('worker_contact_issue_called_phone')->nullable();
                    }

                    if (in_array('thoi_gian_bat_dau_sua', $missingColumns, true)) {
                        $table->timestamp('thoi_gian_bat_dau_sua')->nullable();
                    }
                });
            }
        }

        if (!Schema::hasTable('don_dat_lich_dich_vu')) {
            Schema::create('don_dat_lich_dich_vu', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('don_dat_lich_id');
                $table->unsignedBigInteger('dich_vu_id');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('thanh_toan')) {
            Schema::create('thanh_toan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('don_dat_lich_id');
                $table->decimal('so_tien', 12, 2)->default(0);
                $table->string('phuong_thuc')->nullable();
                $table->string('ma_giao_dich')->nullable();
                $table->string('trang_thai')->nullable();
                $table->json('thong_tin_extra')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('danh_gia')) {
            Schema::create('danh_gia', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('don_dat_lich_id');
                $table->unsignedBigInteger('nguoi_danh_gia_id')->nullable();
                $table->unsignedTinyInteger('so_sao')->default(5);
                $table->text('nhan_xet')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('customer_feedback_cases')) {
            Schema::create('customer_feedback_cases', function (Blueprint $table) {
                $table->id();
                $table->string('source_type');
                $table->unsignedBigInteger('source_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('booking_id')->nullable();
                $table->unsignedBigInteger('worker_id')->nullable();
                $table->string('priority')->nullable();
                $table->string('status')->nullable();
                $table->unsignedBigInteger('assigned_admin_id')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_note')->nullable();
                $table->json('last_snapshot')->nullable();
                $table->timestamps();
            });
        }
    }

    private function truncateTables(): void
    {
        foreach ([
            'customer_feedback_cases',
            'danh_gia',
            'thanh_toan',
            'don_dat_lich_dich_vu',
            'tho_dich_vu',
            'ho_so_tho',
            'don_dat_lich',
            'danh_muc_dich_vu',
            'users',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
