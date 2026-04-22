<?php

namespace Tests\Feature;

use App\Notifications\BookingStatusNotification;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkerContactIssueFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_worker_can_report_customer_unreachable_and_admin_sees_it_in_booking_management(): void
    {
        Notification::fake();

        $admin = $this->createUser('contact-admin@example.com', 'admin');
        $worker = $this->createUser('contact-worker@example.com', 'worker');
        $customer = $this->createUser('contact-customer@example.com', 'customer');
        $serviceId = $this->createService('Sua may giat');
        $this->attachWorkerService($worker->id, $serviceId);
        $bookingId = $this->createAssignedUpcomingBooking($customer->id, $worker->id, $serviceId);
        $reporterName = 'Le Van Luong';
        $calledPhone = '0799462980';
        $note = 'Da goi 3 lan luc 09:00 nhung khong ai bat may.';

        Sanctum::actingAs($worker);

        $response = $this->postJson('/api/don-dat-lich/' . $bookingId . '/report-customer-unreachable', [
            'reporter_name' => $reporterName,
            'called_phone' => $calledPhone,
            'note' => $note,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.trang_thai', 'khong_lien_lac_duoc_voi_khach_hang')
            ->assertJsonPath('data.worker_contact_issue_reporter_name', $reporterName)
            ->assertJsonPath('data.worker_contact_issue_called_phone', $calledPhone)
            ->assertJsonPath('data.worker_contact_issue_note', $note);

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'khong_lien_lac_duoc_voi_khach_hang',
            'worker_contact_issue_reported_by' => $worker->id,
            'worker_contact_issue_reporter_name' => $reporterName,
            'worker_contact_issue_called_phone' => $calledPhone,
            'worker_contact_issue_note' => $note,
        ]);

        Notification::assertSentToTimes($customer, BookingStatusNotification::class, 1);
        Notification::assertSentTo(
            $customer,
            BookingStatusNotification::class,
            function (BookingStatusNotification $notification, array $channels) use ($customer, $bookingId, $calledPhone): bool {
                $payload = $notification->toArray($customer);

                return in_array('database', $channels, true)
                    && $payload['booking_id'] === $bookingId
                    && $payload['booking_status'] === 'khong_lien_lac_duoc_voi_khach_hang'
                    && $payload['type'] === 'booking_customer_unreachable'
                    && str_contains($payload['message'], $calledPhone);
            }
        );

        Sanctum::actingAs($admin);

        $listResponse = $this->getJson('/api/admin/bookings?view=contact_issue');
        $listResponse->assertOk()
            ->assertJsonPath('data.summary.contact_issue_count', 1)
            ->assertJsonPath('data.items.0.id', $bookingId)
            ->assertJsonPath('data.items.0.flags.has_worker_contact_issue', true)
            ->assertJsonPath('data.items.0.contact_issue.is_open', true)
            ->assertJsonPath('data.items.0.contact_issue.called_phone', $calledPhone);

        $detailResponse = $this->getJson('/api/admin/bookings/' . $bookingId);
        $detailResponse->assertOk()
            ->assertJsonPath('data.contact_issue.is_open', true)
            ->assertJsonPath('data.contact_issue.reporter_name', $reporterName)
            ->assertJsonPath('data.contact_issue.called_phone', $calledPhone)
            ->assertJsonPath('data.contact_issue.note', $note);

        $historyTitles = collect($detailResponse->json('data.action_history'))->pluck('title')->all();
        $this->assertContains('Thợ báo không liên lạc được', $historyTitles);
    }

    public function test_admin_can_reschedule_unreachable_booking_and_close_contact_issue(): void
    {
        Notification::fake();

        $admin = $this->createUser('contact-admin-2@example.com', 'admin');
        $worker = $this->createUser('contact-worker-2@example.com', 'worker');
        $customer = $this->createUser('contact-customer-2@example.com', 'customer');
        $serviceId = $this->createService('Sua tu lanh');
        $this->attachWorkerService($worker->id, $serviceId);
        $bookingId = $this->createAssignedUpcomingBooking($customer->id, $worker->id, $serviceId);
        $reporterName = 'Le Van Luong';
        $calledPhone = '0799462980';
        $newDate = Carbon::now()->addDays(2)->toDateString();

        Sanctum::actingAs($worker);
        $this->postJson('/api/don-dat-lich/' . $bookingId . '/report-customer-unreachable', [
            'reporter_name' => $reporterName,
            'called_phone' => $calledPhone,
            'note' => 'Khach khong nghe may sau 2 lan goi.',
        ])->assertOk();

        Sanctum::actingAs($admin);

        $this->putJson('/api/don-dat-lich/' . $bookingId . '/reschedule', [
            'ngay_hen' => $newDate,
            'khung_gio_hen' => '10:00-12:00',
        ])->assertOk()
            ->assertJsonPath('data.ngay_hen', $newDate)
            ->assertJsonPath('data.khung_gio_hen', '10:00-12:00');

        $resolvedAt = DB::table('don_dat_lich')
            ->where('id', $bookingId)
            ->value('worker_contact_issue_resolved_at');
        $storedScheduleDate = (string) DB::table('don_dat_lich')
            ->where('id', $bookingId)
            ->value('ngay_hen');

        $this->assertNotNull($resolvedAt);
        $this->assertStringStartsWith($newDate, $storedScheduleDate);
        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'da_xac_nhan',
            'khung_gio_hen' => '10:00-12:00',
            'worker_contact_issue_reporter_name' => $reporterName,
            'worker_contact_issue_called_phone' => $calledPhone,
        ]);

        Notification::assertSentToTimes($customer, BookingStatusNotification::class, 2);
        Notification::assertSentTo(
            $customer,
            BookingStatusNotification::class,
            function (BookingStatusNotification $notification, array $channels) use ($customer, $bookingId, $newDate): bool {
                $payload = $notification->toArray($customer);

                return in_array('database', $channels, true)
                    && $payload['booking_id'] === $bookingId
                    && $payload['booking_status'] === 'da_xac_nhan'
                    && $payload['type'] === 'booking_status_updated'
                    && $payload['action_label'] === 'Xem lịch mới';
            }
        );

        $listResponse = $this->getJson('/api/admin/bookings?view=contact_issue');
        $listResponse->assertOk()
            ->assertJsonPath('data.summary.contact_issue_count', 0)
            ->assertJsonCount(0, 'data.items');

        $detailResponse = $this->getJson('/api/admin/bookings/' . $bookingId);
        $detailResponse->assertOk()
            ->assertJsonPath('data.contact_issue.is_open', false)
            ->assertJsonPath('data.status_key', 'da_xac_nhan');
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

    private function attachWorkerService(int $workerId, int $serviceId): void
    {
        DB::table('tho_dich_vu')->insert([
            'user_id' => $workerId,
            'dich_vu_id' => $serviceId,
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

    private function createAssignedUpcomingBooking(int $customerId, int $workerId, int $serviceId): int
    {
        $scheduleDate = Carbon::now()->addDay()->toDateString();

        $bookingId = (int) DB::table('don_dat_lich')->insertGetId([
            'khach_hang_id' => $customerId,
            'tho_id' => $workerId,
            'dich_vu_id' => $serviceId,
            'loai_dat_lich' => 'at_home',
            'ngay_hen' => $scheduleDate,
            'khung_gio_hen' => '08:00-10:00',
            'thoi_gian_hen' => $scheduleDate . ' 08:00:00',
            'dia_chi' => '12 Nguyen Thi Minh Khai, Nha Trang',
            'mo_ta_van_de' => 'May khong chay',
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
        ]);

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

            if (!Schema::hasColumn('don_dat_lich', 'worker_contact_issue_reporter_name')) {
                $missingColumns[] = 'worker_contact_issue_reporter_name';
            }

            if (!Schema::hasColumn('don_dat_lich', 'worker_contact_issue_called_phone')) {
                $missingColumns[] = 'worker_contact_issue_called_phone';
            }

            if ($missingColumns !== []) {
                Schema::table('don_dat_lich', function (Blueprint $table) use ($missingColumns) {
                    if (in_array('worker_contact_issue_reporter_name', $missingColumns, true)) {
                        $table->string('worker_contact_issue_reporter_name')->nullable();
                    }

                    if (in_array('worker_contact_issue_called_phone', $missingColumns, true)) {
                        $table->string('worker_contact_issue_called_phone')->nullable();
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
