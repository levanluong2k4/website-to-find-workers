<?php

namespace Tests\Feature;

use App\Notifications\UpcomingWorkerBookingReminderNotification;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkerBookingReminderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
        Carbon::setTestNow('2026-04-03 08:00:00');
        config()->set('booking.worker_reminder.minutes_before', 30);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_command_sends_email_reminder_for_upcoming_confirmed_booking(): void
    {
        Notification::fake();

        $worker = $this->createWorker('upcoming-worker@example.com');

        $bookingId = DB::table('don_dat_lich')->insertGetId([
            'tho_id' => $worker->id,
            'trang_thai' => 'da_xac_nhan',
            'ngay_hen' => '2026-04-03',
            'khung_gio_hen' => '08:00-10:00',
            'thoi_gian_hen' => now()->addMinutes(20),
            'worker_reminder_sent_at' => null,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $this->artisan('app:send-worker-booking-reminders')->assertSuccessful();

        Notification::assertSentTo($worker, UpcomingWorkerBookingReminderNotification::class);

        $this->assertNotNull(
            DB::table('don_dat_lich')->where('id', $bookingId)->value('worker_reminder_sent_at')
        );
    }

    public function test_command_does_not_resend_reminder_when_booking_was_already_notified(): void
    {
        Notification::fake();

        $worker = $this->createWorker('already-reminded@example.com');

        DB::table('don_dat_lich')->insert([
            'tho_id' => $worker->id,
            'trang_thai' => 'da_xac_nhan',
            'ngay_hen' => '2026-04-03',
            'khung_gio_hen' => '08:00-10:00',
            'thoi_gian_hen' => now()->addMinutes(10),
            'worker_reminder_sent_at' => now()->subMinute(),
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $this->artisan('app:send-worker-booking-reminders')->assertSuccessful();

        Notification::assertNotSentTo($worker, UpcomingWorkerBookingReminderNotification::class);
    }

    public function test_reschedule_clears_sent_worker_reminder_marker(): void
    {
        $customer = $this->createCustomer();
        $token = $customer->createToken('worker-reminder-reschedule')->plainTextToken;

        $bookingId = DB::table('don_dat_lich')->insertGetId([
            'khach_hang_id' => $customer->id,
            'trang_thai' => 'da_xac_nhan',
            'ngay_hen' => '2026-04-04',
            'khung_gio_hen' => '08:00-10:00',
            'thoi_gian_hen' => '2026-04-04 08:00:00',
            'worker_reminder_sent_at' => now()->subMinutes(15),
            'so_lan_doi_lich' => 0,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/don-dat-lich/{$bookingId}/reschedule", [
                'ngay_hen' => '2026-04-03',
                'khung_gio_hen' => '12:00-14:00',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.khung_gio_hen', '12:00-14:00');

        $this->assertNull(
            DB::table('don_dat_lich')->where('id', $bookingId)->value('worker_reminder_sent_at')
        );
    }

    private function createCustomer(): User
    {
        return User::query()->create([
            'name' => 'Reminder Customer',
            'email' => 'customer-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
            'phone_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createWorker(string $email): User
    {
        return User::query()->create([
            'name' => 'Reminder Worker',
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => 'worker',
            'is_active' => true,
            'phone_verified_at' => now(),
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
                $table->enum('role', ['admin', 'customer', 'worker'])->default('customer');
                $table->boolean('is_active')->default(true);
                $table->timestamp('phone_verified_at')->nullable();
                $table->rememberToken();
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

        if (!Schema::hasTable('don_dat_lich')) {
            Schema::create('don_dat_lich', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('khach_hang_id')->nullable();
                $table->unsignedBigInteger('tho_id')->nullable();
                $table->string('trang_thai')->default('cho_xac_nhan');
                $table->date('ngay_hen')->nullable();
                $table->string('khung_gio_hen')->nullable();
                $table->unsignedTinyInteger('so_lan_doi_lich')->default(0);
                $table->timestamp('thoi_gian_hen')->nullable();
                $table->timestamp('worker_reminder_sent_at')->nullable();
                $table->string('dia_chi')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('don_dat_lich', function (Blueprint $table) {
                if (!Schema::hasColumn('don_dat_lich', 'khach_hang_id')) {
                    $table->unsignedBigInteger('khach_hang_id')->nullable();
                }
                if (!Schema::hasColumn('don_dat_lich', 'tho_id')) {
                    $table->unsignedBigInteger('tho_id')->nullable();
                }
                if (!Schema::hasColumn('don_dat_lich', 'trang_thai')) {
                    $table->string('trang_thai')->default('cho_xac_nhan');
                }
                if (!Schema::hasColumn('don_dat_lich', 'ngay_hen')) {
                    $table->date('ngay_hen')->nullable();
                }
                if (!Schema::hasColumn('don_dat_lich', 'khung_gio_hen')) {
                    $table->string('khung_gio_hen')->nullable();
                }
                if (!Schema::hasColumn('don_dat_lich', 'so_lan_doi_lich')) {
                    $table->unsignedTinyInteger('so_lan_doi_lich')->default(0);
                }
                if (!Schema::hasColumn('don_dat_lich', 'thoi_gian_hen')) {
                    $table->timestamp('thoi_gian_hen')->nullable();
                }
                if (!Schema::hasColumn('don_dat_lich', 'worker_reminder_sent_at')) {
                    $table->timestamp('worker_reminder_sent_at')->nullable();
                }
                if (!Schema::hasColumn('don_dat_lich', 'dia_chi')) {
                    $table->string('dia_chi')->nullable();
                }
            });
        }
    }

    private function truncateTables(): void
    {
        foreach (['personal_access_tokens', 'don_dat_lich', 'users'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
