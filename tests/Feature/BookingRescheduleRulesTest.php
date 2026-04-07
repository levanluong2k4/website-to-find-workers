<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingRescheduleRulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
        Carbon::setTestNow('2026-04-01 08:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_customer_can_reschedule_once_when_new_slot_respects_minimum_gap(): void
    {
        $customer = $this->createCustomer();
        $token = $customer->createToken('booking-reschedule')->plainTextToken;

        $bookingId = DB::table('don_dat_lich')->insertGetId([
            'khach_hang_id' => $customer->id,
            'trang_thai' => 'cho_xac_nhan',
            'ngay_hen' => '2026-04-02',
            'khung_gio_hen' => '08:00-10:00',
            'thoi_gian_hen' => '2026-04-02 08:00:00',
            'so_lan_doi_lich' => 0,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/don-dat-lich/{$bookingId}/reschedule", [
                'ngay_hen' => '2026-04-01',
                'khung_gio_hen' => '12:00-14:00',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.ngay_hen', '2026-04-01')
            ->assertJsonPath('data.khung_gio_hen', '12:00-14:00')
            ->assertJsonPath('data.so_lan_doi_lich', 1);

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'khung_gio_hen' => '12:00-14:00',
            'so_lan_doi_lich' => 1,
        ]);
    }

    public function test_customer_cannot_reschedule_more_than_once(): void
    {
        $customer = $this->createCustomer();
        $token = $customer->createToken('booking-reschedule-limit')->plainTextToken;

        $bookingId = DB::table('don_dat_lich')->insertGetId([
            'khach_hang_id' => $customer->id,
            'trang_thai' => 'da_xac_nhan',
            'ngay_hen' => '2026-04-02',
            'khung_gio_hen' => '10:00-12:00',
            'thoi_gian_hen' => '2026-04-02 10:00:00',
            'so_lan_doi_lich' => 1,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/don-dat-lich/{$bookingId}/reschedule", [
                'ngay_hen' => '2026-04-01',
                'khung_gio_hen' => '14:00-17:00',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Moi don dat lich chi duoc doi lich toi da 1 lan.');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'ngay_hen' => '2026-04-02',
            'khung_gio_hen' => '10:00-12:00',
            'so_lan_doi_lich' => 1,
        ]);
    }

    public function test_customer_cannot_pick_slot_before_two_future_store_slots(): void
    {
        $customer = $this->createCustomer();
        $token = $customer->createToken('booking-reschedule-gap')->plainTextToken;

        $bookingId = DB::table('don_dat_lich')->insertGetId([
            'khach_hang_id' => $customer->id,
            'trang_thai' => 'cho_xac_nhan',
            'ngay_hen' => '2026-04-02',
            'khung_gio_hen' => '08:00-10:00',
            'thoi_gian_hen' => '2026-04-02 08:00:00',
            'so_lan_doi_lich' => 0,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/don-dat-lich/{$bookingId}/reschedule", [
                'ngay_hen' => '2026-04-01',
                'khung_gio_hen' => '10:00-12:00',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('minimum_allowed_date', '2026-04-01')
            ->assertJsonPath('minimum_allowed_slot', '12:00-14:00');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'ngay_hen' => '2026-04-02',
            'khung_gio_hen' => '08:00-10:00',
            'so_lan_doi_lich' => 0,
        ]);
    }

    private function createCustomer(): User
    {
        return User::query()->create([
            'name' => 'Reschedule Customer',
            'email' => 'reschedule-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
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
