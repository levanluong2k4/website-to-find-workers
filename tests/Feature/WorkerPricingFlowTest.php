<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkerPricingFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_worker_cannot_request_payment_before_updating_price(): void
    {
        Notification::fake();

        [$worker, $customer, $token] = $this->createWorkerContext();
        $bookingId = DB::table('don_dat_lich')->insertGetId([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'dang_lam',
            'gia_da_cap_nhat' => false,
            'phi_di_lai' => 8000,
            'phi_linh_kien' => 0,
            'tien_cong' => 0,
            'tien_thue_xe' => 0,
            'tong_tien' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/bookings/{$bookingId}/request-payment", [
                'phuong_thuc_thanh_toan' => 'cod',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Vui lòng cập nhật giá trước khi báo hoàn thành.');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'dang_lam',
            'gia_da_cap_nhat' => 0,
        ]);
    }

    public function test_worker_can_request_payment_after_price_was_updated(): void
    {
        Notification::fake();

        [$worker, $customer, $token] = $this->createWorkerContext();
        $bookingId = DB::table('don_dat_lich')->insertGetId([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'dang_lam',
            'gia_da_cap_nhat' => true,
            'phi_di_lai' => 8000,
            'phi_linh_kien' => 50000,
            'tien_cong' => 120000,
            'tien_thue_xe' => 0,
            'tong_tien' => 178000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/bookings/{$bookingId}/request-payment", [
                'phuong_thuc_thanh_toan' => 'cod',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('booking.trang_thai', 'cho_hoan_thanh');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'cho_hoan_thanh',
            'gia_da_cap_nhat' => 1,
        ]);
    }

    public function test_worker_can_update_costs_with_clear_breakdown_and_warranty(): void
    {
        Notification::fake();

        [$worker, $customer, $token] = $this->createWorkerContext();
        $bookingId = DB::table('don_dat_lich')->insertGetId([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'dang_lam',
            'gia_da_cap_nhat' => false,
            'phi_di_lai' => 10000,
            'phi_linh_kien' => 0,
            'tien_cong' => 0,
            'tien_thue_xe' => 20000,
            'tong_tien' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/don-dat-lich/{$bookingId}/update-costs", [
                'tien_thue_xe' => 20000,
                'ghi_chu_linh_kien' => 'Bao gia da duoc thong bao ro cho khach.',
                'chi_tiet_tien_cong' => [
                    ['noi_dung' => 'Thay long giat', 'so_tien' => 50000],
                    ['noi_dung' => 'Sua voi ri nuoc', 'so_tien' => 50000],
                ],
                'chi_tiet_linh_kien' => [
                    ['noi_dung' => 'Thay long giat', 'so_tien' => 500000, 'bao_hanh_thang' => 12],
                    ['noi_dung' => 'Thay voi', 'so_tien' => 400000, 'bao_hanh_thang' => 6],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.chi_tiet_tien_cong.0.noi_dung', 'Thay long giat')
            ->assertJsonPath('data.chi_tiet_linh_kien.0.bao_hanh_thang', 12)
            ->assertJsonPath('data.gia_da_cap_nhat', true);

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'tien_cong' => 100000.00,
            'phi_linh_kien' => 900000.00,
            'tong_tien' => 1030000.00,
            'gia_da_cap_nhat' => 1,
        ]);
    }

    public function test_worker_can_update_costs_by_selecting_catalog_parts(): void
    {
        Notification::fake();

        [$worker, $customer, $token] = $this->createWorkerContext();
        $serviceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Sua dieu hoa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $partAId = DB::table('linh_kien')->insertGetId([
            'dich_vu_id' => $serviceId,
            'ten_linh_kien' => 'Bo mat nhan LG',
            'hinh_anh' => null,
            'gia' => 330000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $partBId = DB::table('linh_kien')->insertGetId([
            'dich_vu_id' => $serviceId,
            'ten_linh_kien' => 'Hall dem quat dan lanh',
            'hinh_anh' => null,
            'gia' => 130000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bookingId = DB::table('don_dat_lich')->insertGetId([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'dang_lam',
            'gia_da_cap_nhat' => false,
            'phi_di_lai' => 10000,
            'phi_linh_kien' => 0,
            'tien_cong' => 0,
            'tien_thue_xe' => 0,
            'tong_tien' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('don_dat_lich_dich_vu')->insert([
            'don_dat_lich_id' => $bookingId,
            'dich_vu_id' => $serviceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/don-dat-lich/{$bookingId}/update-costs", [
                'chi_tiet_tien_cong' => [
                    ['noi_dung' => 'Ve sinh va kiem tra tong quat', 'so_tien' => 120000],
                ],
                'chi_tiet_linh_kien' => [
                    ['linh_kien_id' => $partAId, 'noi_dung' => 'Gia bi sua tay', 'so_tien' => 1, 'bao_hanh_thang' => 12],
                    ['linh_kien_id' => $partBId, 'noi_dung' => 'Gia bi sua tay', 'so_tien' => 2, 'bao_hanh_thang' => 6],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.chi_tiet_linh_kien.0.linh_kien_id', $partAId)
            ->assertJsonPath('data.chi_tiet_linh_kien.0.noi_dung', 'Bo mat nhan LG')
            ->assertJsonPath('data.chi_tiet_linh_kien.0.so_tien', 330000)
            ->assertJsonPath('data.chi_tiet_linh_kien.1.noi_dung', 'Hall dem quat dan lanh')
            ->assertJsonPath('data.chi_tiet_linh_kien.1.so_tien', 130000)
            ->assertJsonPath('data.phi_linh_kien', 460000)
            ->assertJsonPath('data.tong_tien', 590000);

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'tien_cong' => 120000.00,
            'phi_linh_kien' => 460000.00,
            'tong_tien' => 590000.00,
            'gia_da_cap_nhat' => 1,
        ]);
    }

    public function test_confirming_cash_payment_sets_real_completion_time(): void
    {
        Notification::fake();

        [$worker, $customer, $token] = $this->createWorkerContext();
        $bookingId = DB::table('don_dat_lich')->insertGetId([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'cho_thanh_toan',
            'gia_da_cap_nhat' => true,
            'phi_di_lai' => 10000,
            'phi_linh_kien' => 50000,
            'tien_cong' => 120000,
            'tien_thue_xe' => 0,
            'tong_tien' => 180000,
            'thoi_gian_hoan_thanh' => null,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subMinutes(10),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/bookings/{$bookingId}/confirm-cash-payment");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('booking.trang_thai', 'da_xong');

        $booking = DB::table('don_dat_lich')->where('id', $bookingId)->first();

        $this->assertSame('da_xong', $booking->trang_thai);
        $this->assertEquals(1, (int) $booking->trang_thai_thanh_toan);
        $this->assertNotNull($booking->thoi_gian_hoan_thanh);
    }

    private function createWorkerContext(): array
    {
        $worker = User::query()->create([
            'name' => 'Worker Flow',
            'email' => 'worker-flow@example.com',
            'password' => bcrypt('password'),
            'role' => 'worker',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customer = User::query()->create([
            'name' => 'Customer Flow',
            'email' => 'customer-flow@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$worker, $customer, $worker->createToken('worker-flow')->plainTextToken];
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
                $table->decimal('phi_di_lai', 12, 2)->default(0);
                $table->decimal('phi_linh_kien', 12, 2)->default(0);
                $table->decimal('tien_cong', 12, 2)->default(0);
                $table->json('chi_tiet_tien_cong')->nullable();
                $table->json('chi_tiet_linh_kien')->nullable();
                $table->decimal('tien_thue_xe', 12, 2)->default(0);
                $table->decimal('tong_tien', 12, 2)->nullable();
                $table->boolean('gia_da_cap_nhat')->default(false);
                $table->string('phuong_thuc_thanh_toan')->nullable();
                $table->text('ghi_chu_linh_kien')->nullable();
                $table->boolean('trang_thai_thanh_toan')->default(false);
                $table->timestamp('thoi_gian_hoan_thanh')->nullable();
                $table->json('hinh_anh_ket_qua')->nullable();
                $table->string('video_ket_qua')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('danh_muc_dich_vu')) {
            Schema::create('danh_muc_dich_vu', function (Blueprint $table) {
                $table->id();
                $table->string('ten_dich_vu');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('linh_kien')) {
            Schema::create('linh_kien', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dich_vu_id');
                $table->string('ten_linh_kien');
                $table->string('hinh_anh')->nullable();
                $table->decimal('gia', 12, 2)->nullable();
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
                $table->unsignedTinyInteger('so_sao')->default(0);
                $table->text('nhan_xet')->nullable();
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
                $table->string('trang_thai')->default('pending');
                $table->json('thong_tin_extra')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('don_dat_lich', function (Blueprint $table) {
            if (!Schema::hasColumn('don_dat_lich', 'chi_tiet_tien_cong')) {
                $table->json('chi_tiet_tien_cong')->nullable()->after('tien_cong');
            }

            if (!Schema::hasColumn('don_dat_lich', 'chi_tiet_linh_kien')) {
                $table->json('chi_tiet_linh_kien')->nullable()->after('phi_linh_kien');
            }

            if (!Schema::hasColumn('don_dat_lich', 'ghi_chu_linh_kien')) {
                $table->text('ghi_chu_linh_kien')->nullable()->after('chi_tiet_linh_kien');
            }

            if (!Schema::hasColumn('don_dat_lich', 'trang_thai_thanh_toan')) {
                $table->boolean('trang_thai_thanh_toan')->default(false)->after('phuong_thuc_thanh_toan');
            }

            if (!Schema::hasColumn('don_dat_lich', 'thoi_gian_hoan_thanh')) {
                $table->timestamp('thoi_gian_hoan_thanh')->nullable()->after('trang_thai_thanh_toan');
            }
        });
    }

    private function truncateTables(): void
    {
        foreach (['thanh_toan', 'danh_gia', 'don_dat_lich_dich_vu', 'linh_kien', 'danh_muc_dich_vu', 'personal_access_tokens', 'don_dat_lich', 'users'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
