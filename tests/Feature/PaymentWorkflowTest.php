<?php

namespace Tests\Feature;

use App\Services\Chat\AiKnowledgeSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['phone_verification.required' => false]);
        Notification::fake();

        $this->app->instance(AiKnowledgeSyncService::class, new class {
            public function syncBookingCases(?int $bookingId = null): int
            {
                return 0;
            }
        });

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_customer_cannot_mark_cash_booking_complete_directly(): void
    {
        $customer = $this->createUser('customer', 'cash-customer@example.com');
        $worker = $this->createUser('worker', 'cash-worker@example.com');
        $token = $customer->createToken('cash-customer')->plainTextToken;

        $bookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'cho_hoan_thanh',
            'phuong_thuc_thanh_toan' => 'cod',
            'tong_tien' => 550000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/don-dat-lich/{$bookingId}/status", [
                'trang_thai' => 'da_xong',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Khach hang khong the tu xac nhan hoan tat. Hay thanh toan chuyen khoan tren he thong hoac doi tho xac nhan tien mat.');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'cho_hoan_thanh',
            'trang_thai_thanh_toan' => 0,
        ]);
    }

    public function test_worker_confirming_cash_payment_marks_booking_complete(): void
    {
        $customer = $this->createUser('customer', 'confirm-cash-customer@example.com');
        $worker = $this->createUser('worker', 'confirm-cash-worker@example.com');
        $token = $worker->createToken('cash-worker')->plainTextToken;

        $bookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'cho_hoan_thanh',
            'phuong_thuc_thanh_toan' => 'cod',
            'tong_tien' => 780000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/bookings/{$bookingId}/confirm-cash-payment");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('booking.trang_thai', 'da_xong');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'da_xong',
            'trang_thai_thanh_toan' => 1,
        ]);

        $this->assertDatabaseHas('thanh_toan', [
            'don_dat_lich_id' => $bookingId,
            'phuong_thuc' => 'cash',
            'trang_thai' => 'success',
        ]);
    }

    public function test_customer_test_payment_completes_transfer_booking(): void
    {
        $customer = $this->createUser('customer', 'transfer-customer@example.com');
        $worker = $this->createUser('worker', 'transfer-worker@example.com');
        $token = $customer->createToken('transfer-customer')->plainTextToken;

        $bookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'cho_thanh_toan',
            'phuong_thuc_thanh_toan' => 'transfer',
            'tong_tien' => 1250000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payment/create', [
                'don_dat_lich_id' => $bookingId,
                'phuong_thuc' => 'test',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment_status', 'success');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'da_xong',
            'trang_thai_thanh_toan' => 1,
        ]);

        $this->assertDatabaseHas('thanh_toan', [
            'don_dat_lich_id' => $bookingId,
            'phuong_thuc' => 'test',
            'trang_thai' => 'success',
        ]);
    }

    private function createUser(string $role, string $email): \App\Models\User
    {
        return \App\Models\User::query()->create([
            'name' => ucfirst($role) . ' User',
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
            'phone_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createBooking(array $attributes = []): int
    {
        return DB::table('don_dat_lich')->insertGetId(array_merge([
            'khach_hang_id' => null,
            'tho_id' => null,
            'trang_thai' => 'cho_xac_nhan',
            'phuong_thuc_thanh_toan' => 'cod',
            'tong_tien' => 0,
            'phi_di_lai' => 0,
            'phi_linh_kien' => 0,
            'tien_cong' => 0,
            'tien_thue_xe' => 0,
            'trang_thai_thanh_toan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
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
                $table->string('phuong_thuc_thanh_toan')->default('cod');
                $table->decimal('tong_tien', 12, 2)->default(0);
                $table->decimal('phi_di_lai', 12, 2)->default(0);
                $table->decimal('phi_linh_kien', 12, 2)->default(0);
                $table->decimal('tien_cong', 12, 2)->default(0);
                $table->decimal('tien_thue_xe', 12, 2)->default(0);
                $table->timestamp('thoi_gian_hoan_thanh')->nullable();
                $table->boolean('trang_thai_thanh_toan')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('thanh_toan')) {
            Schema::create('thanh_toan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('don_dat_lich_id');
                $table->decimal('so_tien', 12, 2)->default(0);
                $table->string('phuong_thuc')->default('cash');
                $table->string('ma_giao_dich')->nullable();
                $table->string('trang_thai')->default('pending');
                $table->json('thong_tin_extra')->nullable();
                $table->timestamps();
            });
        }
    }

    private function truncateTables(): void
    {
        foreach (['thanh_toan', 'don_dat_lich', 'personal_access_tokens', 'users'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
