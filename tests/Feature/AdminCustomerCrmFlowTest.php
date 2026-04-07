<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminCustomerCrmFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_admin_can_store_internal_note_and_filter_basic_customer_list(): void
    {
        $admin = $this->createAdmin('crm-admin@example.com');
        $customer = $this->createCustomer('crm-customer@example.com');
        $worker = $this->createWorker('crm-worker@example.com');
        $token = $admin->createToken('crm-admin')->plainTextToken;
        $this->createCompletedBooking($customer, $worker, 'Bao tri may giat', 'Don da duoc hoan tat on dinh');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/customers/' . $customer->id . '/notes', [
                'content' => 'Da goi xac nhan thong tin va huong dan khach theo doi them.',
                'category' => 'cskh',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.note.category', 'cskh')
            ->assertJsonPath('data.note.content', 'Da goi xac nhan thong tin va huong dan khach theo doi them.');

        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/customers?status=has_booking');

        $listResponse->assertOk()
            ->assertJsonPath('data.summary.booked_customers', 1)
            ->assertJsonCount(1, 'data.customers')
            ->assertJsonPath('data.customers.0.id', $customer->id);

        $detailResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/customers/' . $customer->id);

        $detailResponse->assertOk()
            ->assertJsonPath('data.notes.0.category', 'cskh')
            ->assertJsonPath('data.notes.0.content', 'Da goi xac nhan thong tin va huong dan khach theo doi them.');
    }

    public function test_admin_can_claim_and_resolve_feedback_case(): void
    {
        $ownerAdmin = $this->createAdmin('owner-admin@example.com');
        $customer = $this->createCustomer('feedback-customer@example.com');
        $worker = $this->createWorker('feedback-worker@example.com');
        $token = $ownerAdmin->createToken('crm-admin')->plainTextToken;
        $bookingId = $this->createCompletedBooking($customer, $worker, 'Sua may giat', 'May giat khong vat');

        $reviewId = DB::table('danh_gia')->insertGetId([
            'don_dat_lich_id' => $bookingId,
            'nguoi_danh_gia_id' => $customer->id,
            'nguoi_bi_danh_gia_id' => $worker->id,
            'so_sao' => 1,
            'nhan_xet' => 'Tho den muon va xu ly chua on',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $claimResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/customer-feedback/low_rating-' . $reviewId . '/claim');

        $claimResponse->assertOk()
            ->assertJsonPath('data.case.status', 'in_progress')
            ->assertJsonPath('data.case.assigned_admin_id', $ownerAdmin->id);

        $resolveResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/customer-feedback/low_rating-' . $reviewId . '/resolve', [
                'resolution_note' => 'Da lien he khach va ghi nhan phuong an xu ly.',
            ]);

        $resolveResponse->assertOk()
            ->assertJsonPath('data.case.status', 'resolved')
            ->assertJsonPath('data.case.resolution_note', 'Da lien he khach va ghi nhan phuong an xu ly.');

        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/customer-feedback?status=resolved&customer=' . $customer->id);

        $listResponse->assertOk()
            ->assertJsonCount(1, 'data.cases')
            ->assertJsonPath('data.cases.0.customer_id', $customer->id)
            ->assertJsonPath('data.cases.0.status', 'resolved')
            ->assertJsonPath('data.cases.0.resolution_note', 'Da lien he khach va ghi nhan phuong an xu ly.');
    }

    private function createAdmin(string $email, string $name = 'Admin CRM'): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCustomer(string $email, string $name = 'Customer CRM'): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => 'customer',
            'phone' => '0900000001',
            'address' => '456 Le Van Sy, Quan 3',
            'is_active' => true,
            'created_at' => now()->subDays(10),
            'updated_at' => now(),
        ]);
    }

    private function createWorker(string $email, string $name = 'Worker CRM'): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => 'worker',
            'phone' => '0900000002',
            'is_active' => true,
            'created_at' => now()->subDays(20),
            'updated_at' => now(),
        ]);
    }

    private function createCompletedBooking(User $customer, User $worker, string $serviceName, string $problem): int
    {
        $serviceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => $serviceName,
            'mo_ta' => 'Dich vu test cho admin customer flow',
            'hinh_anh' => null,
            'trang_thai' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bookingId = DB::table('don_dat_lich')->insertGetId([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'dich_vu_id' => $serviceId,
            'loai_dat_lich' => 'at_home',
            'ngay_hen' => now()->subDay()->toDateString(),
            'khung_gio_hen' => '08:00-10:00',
            'dia_chi' => '123 Nguyen Van Linh, Quan 7',
            'mo_ta_van_de' => $problem,
            'trang_thai' => 'da_xong',
            'tong_tien' => 450000,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDay(),
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
                $table->text('mo_ta_van_de')->nullable();
                $table->string('trang_thai')->default('cho_xac_nhan');
                $table->string('phuong_thuc_thanh_toan')->nullable();
                $table->string('ma_ly_do_huy')->nullable();
                $table->text('ly_do_huy')->nullable();
                $table->string('dia_chi')->nullable();
                $table->decimal('tong_tien', 12, 2)->default(0);
                $table->boolean('trang_thai_thanh_toan')->default(false);
                $table->timestamp('thoi_gian_hoan_thanh')->nullable();
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
                $table->unsignedTinyInteger('so_sao')->default(5);
                $table->text('nhan_xet')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('customer_tags')) {
            Schema::create('customer_tags', function (Blueprint $table) {
                $table->id();
                $table->string('label', 60)->unique();
                $table->string('slug', 70)->unique();
                $table->string('color', 32)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('customer_tag_assignments')) {
            Schema::create('customer_tag_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('tag_id');
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('customer_notes')) {
            Schema::create('customer_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('category', 32)->default('van_hanh');
                $table->text('content');
                $table->boolean('is_pinned')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('customer_feedback_cases')) {
            Schema::create('customer_feedback_cases', function (Blueprint $table) {
                $table->id();
                $table->string('source_type', 32);
                $table->unsignedBigInteger('source_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('booking_id')->nullable();
                $table->unsignedBigInteger('worker_id')->nullable();
                $table->string('priority', 16)->default('medium');
                $table->string('status', 16)->default('new');
                $table->unsignedBigInteger('assigned_admin_id')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('deadline_at')->nullable();
                $table->text('assignment_note')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_note')->nullable();
                $table->json('last_snapshot')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('customer_follow_ups')) {
            Schema::create('customer_follow_ups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('booking_id')->nullable();
                $table->unsignedBigInteger('created_by_admin_id')->nullable();
                $table->unsignedBigInteger('assigned_admin_id')->nullable();
                $table->string('title', 180);
                $table->string('channel', 24)->default('call');
                $table->string('priority', 16)->default('medium');
                $table->string('status', 16)->default('pending');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('note')->nullable();
                $table->text('outcome_note')->nullable();
                $table->timestamps();
            });
        }
    }

    private function truncateTables(): void
    {
        foreach ([
            'customer_follow_ups',
            'customer_feedback_cases',
            'customer_notes',
            'customer_tag_assignments',
            'customer_tags',
            'danh_gia',
            'don_dat_lich_dich_vu',
            'don_dat_lich',
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
