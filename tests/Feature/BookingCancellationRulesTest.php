<?php

namespace Tests\Feature;

use App\Models\DonDatLich;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingCancellationRulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_customer_cancellation_uses_structured_reason_code(): void
    {
        $customer = User::query()->create([
            'name' => 'Cancel Customer',
            'email' => 'cancel-customer@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
            'phone_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $customer->createToken('cancel-booking')->plainTextToken;

        $bookingId = DB::table('don_dat_lich')->insertGetId([
            'khach_hang_id' => $customer->id,
            'trang_thai' => 'cho_xac_nhan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/don-dat-lich/{$bookingId}/status", [
                'trang_thai' => 'da_huy',
                'ma_ly_do_huy' => DonDatLich::CANCEL_REASON_THAY_DOI_THOI_GIAN_DAT,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.trang_thai', 'da_huy');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'da_huy',
            'ma_ly_do_huy' => DonDatLich::CANCEL_REASON_THAY_DOI_THOI_GIAN_DAT,
            'ly_do_huy' => DonDatLich::cancelReasonLabel(DonDatLich::CANCEL_REASON_THAY_DOI_THOI_GIAN_DAT),
        ]);
    }

    public function test_command_auto_cancels_unclaimed_booking_that_passed_scheduled_time(): void
    {
        $bookingId = DB::table('don_dat_lich')->insertGetId([
            'trang_thai' => 'cho_xac_nhan',
            'thoi_gian_hen' => now()->subMinutes(30),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $this->artisan('app:cancel-expired-bookings')->assertSuccessful();

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'da_huy',
            'ma_ly_do_huy' => DonDatLich::CANCEL_REASON_KHONG_CO_THO_NAO_NHAN,
            'ly_do_huy' => DonDatLich::cancelReasonLabel(DonDatLich::CANCEL_REASON_KHONG_CO_THO_NAO_NHAN),
        ]);
    }

    public function test_command_auto_cancels_booking_waiting_too_long_for_confirmation(): void
    {
        $bookingId = DB::table('don_dat_lich')->insertGetId([
            'tho_id' => 999,
            'trang_thai' => 'cho_xac_nhan',
            'thoi_gian_hen' => now()->addHours(2),
            'thoi_gian_het_han_nhan' => now()->subMinutes(5),
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);

        $this->artisan('app:cancel-expired-bookings')->assertSuccessful();

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'da_huy',
            'ma_ly_do_huy' => DonDatLich::CANCEL_REASON_CHO_QUA_LAU,
            'ly_do_huy' => DonDatLich::cancelReasonLabel(DonDatLich::CANCEL_REASON_CHO_QUA_LAU),
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
                $table->string('ma_ly_do_huy')->nullable();
                $table->text('ly_do_huy')->nullable();
                $table->timestamp('thoi_gian_hen')->nullable();
                $table->timestamp('thoi_gian_het_han_nhan')->nullable();
                $table->timestamp('thoi_gian_hoan_thanh')->nullable();
                $table->boolean('trang_thai_thanh_toan')->default(false);
                $table->timestamps();
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
