<?php

namespace Tests\Feature;

use App\Models\CustomerFeedbackCase;
use App\Models\User;
use App\Notifications\BookingStatusNotification;
use App\Notifications\BookingWarrantyRequestedNotification;
use App\Services\Media\CloudinaryUploadService;
use Cloudinary\Api\ApiResponse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class BookingWarrantyFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_customer_can_submit_warranty_request_and_worker_receives_notification(): void
    {
        Notification::fake();

        $customer = $this->createUser('warranty-customer@example.com', 'customer');
        $worker = $this->createUser('warranty-worker@example.com', 'worker');
        $serviceId = $this->createService('Sua tu lanh');
        $bookingId = $this->createCompletedBooking($customer->id, $worker->id, $serviceId, Carbon::now()->subDays(3));

        Sanctum::actingAs($customer);

        $response = $this->postJson("/api/don-dat-lich/{$bookingId}/complaint", [
            'ly_do_khieu_nai' => 'loi_tai_phat',
            'ghi_chu' => 'Loi cu tai phat sau khi sua xong.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.booking.id', $bookingId)
            ->assertJsonPath('data.warranty_case.status', 'worker_notified')
            ->assertJsonPath('data.warranty_case.reason_code', 'loi_tai_phat');

        $case = CustomerFeedbackCase::query()->where('booking_id', $bookingId)->first();
        $this->assertNotNull($case);
        $this->assertSame('worker_notified', $case->status);
        $this->assertSame('loi_tai_phat', $case->last_snapshot['reason_code'] ?? null);
        $this->assertSame('Loi cu tai phat sau khi sua xong.', $case->last_snapshot['note'] ?? null);
        $this->assertNotEmpty($case->last_snapshot['warranty_expires_at'] ?? null);

        Notification::assertSentTo(
            $worker,
            BookingWarrantyRequestedNotification::class,
            function (BookingWarrantyRequestedNotification $notification, array $channels) use ($worker, $bookingId): bool {
                $payload = $notification->toArray($worker);

                return in_array('database', $channels, true)
                    && $payload['booking_id'] === $bookingId
                    && $payload['type'] === 'booking_warranty_requested';
            }
        );
    }

    public function test_customer_can_submit_warranty_request_with_media_evidence(): void
    {
        Notification::fake();

        $customer = $this->createUser('warranty-customer-media@example.com', 'customer');
        $worker = $this->createUser('warranty-worker-media@example.com', 'worker');
        $serviceId = $this->createService('Sua lo nuong');
        $bookingId = $this->createCompletedBooking($customer->id, $worker->id, $serviceId, Carbon::now()->subDays(3));

        $uploadService = Mockery::mock(CloudinaryUploadService::class);
        $uploadService->shouldReceive('uploadUploadedFile')
            ->once()
            ->with(
                Mockery::type(UploadedFile::class),
                Mockery::on(static fn (array $options): bool => ($options['folder'] ?? null) === 'complaints/images')
            )
            ->andReturn(new ApiResponse([
                'secure_url' => 'https://example.com/complaints/images/customer-proof.jpg',
            ], []));
        $uploadService->shouldReceive('uploadUploadedFile')
            ->once()
            ->with(
                Mockery::type(UploadedFile::class),
                Mockery::on(static fn (array $options): bool => ($options['folder'] ?? null) === 'complaints/videos'
                    && ($options['resource_type'] ?? null) === 'video')
            )
            ->andReturn(new ApiResponse([
                'secure_url' => 'https://example.com/complaints/videos/customer-proof.mp4',
            ], []));
        $this->app->instance(CloudinaryUploadService::class, $uploadService);

        Sanctum::actingAs($customer);

        $response = $this->post("/api/don-dat-lich/{$bookingId}/complaint", [
            'ly_do_khieu_nai' => 'loi_tai_phat',
            'ghi_chu' => 'Khach gui them anh va video minh chung.',
            'hinh_anh_khieu_nai' => [UploadedFile::fake()->image('customer-proof.jpg')],
            'video_khieu_nai' => UploadedFile::fake()->create('customer-proof.mp4', 1024, 'video/mp4'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.warranty_case.images.0', 'https://example.com/complaints/images/customer-proof.jpg')
            ->assertJsonPath('data.warranty_case.video', 'https://example.com/complaints/videos/customer-proof.mp4');

        $case = CustomerFeedbackCase::query()->where('booking_id', $bookingId)->first();
        $this->assertSame(
            ['https://example.com/complaints/images/customer-proof.jpg'],
            $case?->last_snapshot['images'] ?? []
        );
        $this->assertSame(
            'https://example.com/complaints/videos/customer-proof.mp4',
            $case?->last_snapshot['video'] ?? null
        );
    }

    public function test_customer_cannot_submit_second_open_warranty_case(): void
    {
        Notification::fake();

        $customer = $this->createUser('warranty-customer-2@example.com', 'customer');
        $worker = $this->createUser('warranty-worker-2@example.com', 'worker');
        $serviceId = $this->createService('Sua may giat');
        $bookingId = $this->createCompletedBooking($customer->id, $worker->id, $serviceId, Carbon::now()->subDays(2));
        $this->createWarrantyCase($bookingId, $customer->id, $worker->id, 'worker_notified');

        Sanctum::actingAs($customer);

        $this->postJson("/api/don-dat-lich/{$bookingId}/complaint", [
            'ly_do_khieu_nai' => 'sua_chua_khong_triet_de',
            'ghi_chu' => 'Van chua sua dut diem.',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Don nay dang co 1 case bao hanh mo.');

        Notification::assertNothingSent();
    }

    public function test_customer_cannot_choose_part_quality_reason_when_booking_has_no_replacement_parts(): void
    {
        Notification::fake();

        $customer = $this->createUser('warranty-customer-no-part@example.com', 'customer');
        $worker = $this->createUser('warranty-worker-no-part@example.com', 'worker');
        $serviceId = $this->createService('Sua quat dien');
        $bookingId = $this->createCompletedBooking($customer->id, $worker->id, $serviceId, Carbon::now()->subDays(2));

        Sanctum::actingAs($customer);

        $this->postJson("/api/don-dat-lich/{$bookingId}/complaint", [
            'ly_do_khieu_nai' => 'linh_kien_kem_chat_luong',
            'ghi_chu' => 'Muon bao hanh linh kien du don khong thay linh kien.',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Don nay khong co linh kien thay the nen khong the chon ly do bao hanh nay.');

        $this->assertDatabaseMissing('customer_feedback_cases', [
            'booking_id' => $bookingId,
        ]);

        Notification::assertNothingSent();
    }

    public function test_non_owner_customer_cannot_submit_warranty_request(): void
    {
        Notification::fake();

        $customer = $this->createUser('warranty-customer-3@example.com', 'customer');
        $otherCustomer = $this->createUser('warranty-customer-4@example.com', 'customer');
        $worker = $this->createUser('warranty-worker-3@example.com', 'worker');
        $serviceId = $this->createService('Sua bep tu');
        $bookingId = $this->createCompletedBooking($customer->id, $worker->id, $serviceId, Carbon::now()->subDay());

        Sanctum::actingAs($otherCustomer);

        $this->postJson("/api/don-dat-lich/{$bookingId}/complaint", [
            'ly_do_khieu_nai' => 'khac',
            'ghi_chu' => 'Toi khong phai chu don.',
        ])->assertForbidden()
            ->assertJsonPath('message', 'Ban khong phai khach hang cua don nay.');
    }

    public function test_assigned_worker_can_accept_warranty_case_and_customer_is_notified(): void
    {
        Notification::fake();

        $customer = $this->createUser('warranty-customer-5@example.com', 'customer');
        $worker = $this->createUser('warranty-worker-4@example.com', 'worker');
        $serviceId = $this->createService('Sua may lanh');
        $bookingId = $this->createCompletedBooking($customer->id, $worker->id, $serviceId, Carbon::now()->subDays(4));
        $this->createWarrantyCase($bookingId, $customer->id, $worker->id, 'worker_notified');

        Sanctum::actingAs($worker);

        $response = $this->putJson("/api/don-dat-lich/{$bookingId}/complaint/status", [
            'status' => 'accepted',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.booking.warranty_case.status', 'accepted');

        $case = CustomerFeedbackCase::query()->where('booking_id', $bookingId)->first();
        $this->assertSame('accepted', $case?->status);
        $this->assertNull($case?->resolved_at);

        Notification::assertSentTo(
            $customer,
            BookingStatusNotification::class,
            function (BookingStatusNotification $notification, array $channels) use ($customer, $bookingId): bool {
                $payload = $notification->toArray($customer);

                return in_array('database', $channels, true)
                    && $payload['booking_id'] === $bookingId
                    && $payload['type'] === 'booking_warranty_accepted';
            }
        );
    }

    public function test_worker_cannot_reject_warranty_case_without_note(): void
    {
        Notification::fake();

        $customer = $this->createUser('warranty-customer-6@example.com', 'customer');
        $worker = $this->createUser('warranty-worker-5@example.com', 'worker');
        $serviceId = $this->createService('Sua lo vi song');
        $bookingId = $this->createCompletedBooking($customer->id, $worker->id, $serviceId, Carbon::now()->subDays(2));
        $this->createWarrantyCase($bookingId, $customer->id, $worker->id, 'worker_notified');

        Sanctum::actingAs($worker);

        $this->putJson("/api/don-dat-lich/{$bookingId}/complaint/status", [
            'status' => 'rejected',
            'note' => '',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Tho phai nhap ly do khi tu choi bao hanh.');
    }

    public function test_worker_can_complete_warranty_case_with_media_evidence(): void
    {
        Notification::fake();

        $customer = $this->createUser('warranty-customer-7@example.com', 'customer');
        $worker = $this->createUser('warranty-worker-7@example.com', 'worker');
        $serviceId = $this->createService('Sua may loc nuoc');
        $bookingId = $this->createCompletedBooking($customer->id, $worker->id, $serviceId, Carbon::now()->subDays(2));
        $this->createWarrantyCase($bookingId, $customer->id, $worker->id, 'in_progress');

        $uploadService = Mockery::mock(CloudinaryUploadService::class);
        $uploadService->shouldReceive('uploadUploadedFile')
            ->once()
            ->with(
                Mockery::type(UploadedFile::class),
                Mockery::on(static fn (array $options): bool => ($options['folder'] ?? null) === 'complaints/worker-results/images')
            )
            ->andReturn(new ApiResponse([
                'secure_url' => 'https://example.com/complaints/worker-results/images/warranty-completed.jpg',
            ], []));
        $uploadService->shouldReceive('uploadUploadedFile')
            ->once()
            ->with(
                Mockery::type(UploadedFile::class),
                Mockery::on(static fn (array $options): bool => ($options['folder'] ?? null) === 'complaints/worker-results/videos'
                    && ($options['resource_type'] ?? null) === 'video')
            )
            ->andReturn(new ApiResponse([
                'secure_url' => 'https://example.com/complaints/worker-results/videos/warranty-completed.mp4',
            ], []));
        $this->app->instance(CloudinaryUploadService::class, $uploadService);

        Sanctum::actingAs($worker);

        $response = $this->post("/api/don-dat-lich/{$bookingId}/complaint/status", [
            '_method' => 'PUT',
            'status' => 'completed',
            'note' => 'Da thay linh kien va test lai may on dinh.',
            'hinh_anh_ket_qua' => [UploadedFile::fake()->image('warranty-completed.jpg')],
            'video_ket_qua' => UploadedFile::fake()->create('warranty-completed.mp4', 1024, 'video/mp4'),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.booking.warranty_case.status', 'completed')
            ->assertJsonPath('data.booking.warranty_case.worker_result_images.0', 'https://example.com/complaints/worker-results/images/warranty-completed.jpg')
            ->assertJsonPath('data.booking.warranty_case.worker_result_video', 'https://example.com/complaints/worker-results/videos/warranty-completed.mp4');

        $case = CustomerFeedbackCase::query()->where('booking_id', $bookingId)->first();
        $this->assertSame('completed', $case?->status);
        $this->assertSame(
            ['https://example.com/complaints/worker-results/images/warranty-completed.jpg'],
            $case?->last_snapshot['worker_result_images'] ?? []
        );
        $this->assertSame(
            'https://example.com/complaints/worker-results/videos/warranty-completed.mp4',
            $case?->last_snapshot['worker_result_video'] ?? null
        );
    }

    public function test_customer_cannot_submit_new_warranty_request_after_completed_warranty_case(): void
    {
        Notification::fake();

        $customer = $this->createUser('warranty-customer-used@example.com', 'customer');
        $worker = $this->createUser('warranty-worker-used@example.com', 'worker');
        $serviceId = $this->createService('Sua may rua chen');
        $bookingId = $this->createCompletedBooking($customer->id, $worker->id, $serviceId, Carbon::now()->subDays(2));
        $this->createWarrantyCase($bookingId, $customer->id, $worker->id, 'completed');

        Sanctum::actingAs($customer);

        $this->postJson("/api/don-dat-lich/{$bookingId}/complaint", [
            'ly_do_khieu_nai' => 'loi_tai_phat',
            'ghi_chu' => 'Muon gui lai sau khi da hoan tat bao hanh.',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Don nay da su dung quyen bao hanh, khong the gui them yeu cau moi.');
    }

    public function test_customer_can_submit_new_warranty_request_after_rejected_case_when_still_in_warranty(): void
    {
        Notification::fake();

        $customer = $this->createUser('warranty-customer-retry@example.com', 'customer');
        $worker = $this->createUser('warranty-worker-retry@example.com', 'worker');
        $serviceId = $this->createService('Sua binh nong lanh');
        $bookingId = $this->createCompletedBooking($customer->id, $worker->id, $serviceId, Carbon::now()->subDays(2));
        $this->createWarrantyCase($bookingId, $customer->id, $worker->id, 'rejected');

        Sanctum::actingAs($customer);

        $response = $this->postJson("/api/don-dat-lich/{$bookingId}/complaint", [
            'ly_do_khieu_nai' => 'loi_tai_phat',
            'ghi_chu' => 'Gui lai vi yeu cau truoc bi tu choi nhung don van con han.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.warranty_case.status', 'worker_notified')
            ->assertJsonPath('data.warranty_case.note', 'Gui lai vi yeu cau truoc bi tu choi nhung don van con han.');

        $case = CustomerFeedbackCase::query()->where('booking_id', $bookingId)->first();
        $this->assertSame('worker_notified', $case?->status);
        $this->assertSame('Gui lai vi yeu cau truoc bi tu choi nhung don van con han.', $case?->last_snapshot['note'] ?? null);
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
            'address' => '12 Nguyen Thi Minh Khai, Nha Trang',
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

    private function createCompletedBooking(int $customerId, int $workerId, int $serviceId, Carbon $completedAt): int
    {
        $bookingId = (int) DB::table('don_dat_lich')->insertGetId([
            'khach_hang_id' => $customerId,
            'tho_id' => $workerId,
            'dich_vu_id' => $serviceId,
            'loai_dat_lich' => 'at_home',
            'ngay_hen' => $completedAt->copy()->subDay()->toDateString(),
            'khung_gio_hen' => '08:00-10:00',
            'thoi_gian_hen' => $completedAt->copy()->subDay()->setTime(8, 0),
            'thoi_gian_hoan_thanh' => $completedAt,
            'dia_chi' => '12 Nguyen Thi Minh Khai, Nha Trang',
            'mo_ta_van_de' => 'May khong hoat dong',
            'trang_thai' => 'da_xong',
            'phuong_thuc_thanh_toan' => 'cod',
            'trang_thai_thanh_toan' => true,
            'phi_di_lai' => 15000,
            'phi_linh_kien' => 0,
            'tien_cong' => 250000,
            'tien_thue_xe' => 0,
            'tong_tien' => 265000,
            'created_at' => $completedAt->copy()->subDays(2),
            'updated_at' => $completedAt,
        ]);

        DB::table('don_dat_lich_dich_vu')->insert([
            'don_dat_lich_id' => $bookingId,
            'dich_vu_id' => $serviceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $bookingId;
    }

    private function createWarrantyCase(int $bookingId, int $customerId, int $workerId, string $status): void
    {
        $requestedAt = Carbon::now()->subHours(4);

        CustomerFeedbackCase::query()->create([
            'source_type' => 'customer_complaint',
            'source_id' => $bookingId,
            'customer_id' => $customerId,
            'booking_id' => $bookingId,
            'worker_id' => $workerId,
            'priority' => 'medium',
            'status' => $status,
            'assigned_admin_id' => null,
            'assigned_at' => null,
            'deadline_at' => $requestedAt->copy()->addHours(24),
            'assignment_note' => null,
            'resolved_at' => null,
            'resolution_note' => null,
            'last_snapshot' => [
                'reason_code' => 'loi_tai_phat',
                'reason_label' => 'Lỗi tái phát khi sửa',
                'note' => 'Can quay lai bao hanh.',
                'images' => [],
                'video' => null,
                'requested_at' => $requestedAt->toIso8601String(),
                'warranty_expires_at' => $requestedAt->copy()->addWeeks(3)->toIso8601String(),
                'response_deadline_at' => $requestedAt->copy()->addHours(24)->toIso8601String(),
                'worker_response_note' => '',
                'worker_response_at' => null,
            ],
        ]);
    }

    private function prepareSchema(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable()->unique();
                $table->string('password');
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('avatar')->nullable();
                $table->string('role')->default('customer');
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
                $table->timestamp('thoi_gian_hoan_thanh')->nullable();
                $table->string('dia_chi')->nullable();
                $table->text('mo_ta_van_de')->nullable();
                $table->string('trang_thai')->nullable();
                $table->decimal('tong_tien', 12, 2)->nullable();
                $table->string('phuong_thuc_thanh_toan')->nullable();
                $table->boolean('trang_thai_thanh_toan')->default(false);
                $table->decimal('phi_di_lai', 12, 2)->nullable();
                $table->decimal('phi_linh_kien', 12, 2)->nullable();
                $table->decimal('tien_cong', 12, 2)->nullable();
                $table->decimal('tien_thue_xe', 12, 2)->nullable();
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
                $table->timestamp('deadline_at')->nullable();
                $table->text('assignment_note')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_note')->nullable();
                $table->json('last_snapshot')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ai_knowledge_items')) {
            Schema::create('ai_knowledge_items', function (Blueprint $table) {
                $table->id();
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('source_key')->unique();
                $table->unsignedBigInteger('primary_service_id')->nullable();
                $table->string('service_name')->nullable();
                $table->string('title')->nullable();
                $table->longText('content')->nullable();
                $table->longText('normalized_content')->nullable();
                $table->text('symptom_text')->nullable();
                $table->text('cause_text')->nullable();
                $table->text('solution_text')->nullable();
                $table->string('price_context')->nullable();
                $table->decimal('rating_avg', 5, 2)->nullable();
                $table->decimal('quality_score', 6, 4)->nullable();
                $table->json('metadata')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('published_at')->nullable();
                $table->string('qdrant_document_hash')->nullable();
                $table->timestamp('qdrant_synced_at')->nullable();
                $table->timestamps();
            });
        }
    }

    private function truncateTables(): void
    {
        foreach ([
            'customer_feedback_cases',
            'ai_knowledge_items',
            'don_dat_lich_dich_vu',
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
