<?php

namespace Tests\Feature;

use App\Models\DanhGia;
use App\Models\User;
use App\Services\Media\CloudinaryUploadService;
use Cloudinary\Api\ApiResponse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class ReviewMediaUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'phone_verification.required' => false,
        ]);

        $this->prepareSchema();
        $this->truncateTables();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_customer_can_create_review_with_images_and_video(): void
    {
        [$customer, $worker, $bookingId] = $this->seedBookingContext();

        $mock = Mockery::mock(CloudinaryUploadService::class);
        $mock->shouldReceive('uploadUploadedFile')
            ->twice()
            ->andReturn(
                new ApiResponse([
                    'secure_url' => 'https://res.cloudinary.com/demo/image/upload/reviews/image-1.jpg',
                ], []),
                new ApiResponse([
                    'secure_url' => 'https://res.cloudinary.com/demo/video/upload/reviews/video-1.mp4',
                    'duration' => 12.4,
                ], [])
            );
        $this->app->instance(CloudinaryUploadService::class, $mock);

        Sanctum::actingAs($customer);

        $response = $this->post('/api/danh-gia', [
            'don_dat_lich_id' => $bookingId,
            'so_sao' => 5,
            'nhan_xet' => 'Khach danh gia co media',
            'video_duration' => 12.4,
            'hinh_anh_danh_gia' => [
                UploadedFile::fake()->image('review-1.jpg'),
            ],
            'video_danh_gia' => UploadedFile::fake()->create('review.mp4', 1024, 'video/mp4'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.so_sao', 5);

        /** @var \App\Models\DanhGia $review */
        $review = DanhGia::query()->firstOrFail();

        $this->assertSame(['https://res.cloudinary.com/demo/image/upload/reviews/image-1.jpg'], $review->hinh_anh_danh_gia);
        $this->assertSame('https://res.cloudinary.com/demo/video/upload/reviews/video-1.mp4', $review->video_danh_gia);
        $this->assertDatabaseHas('ho_so_tho', [
            'user_id' => $worker->id,
            'tong_so_danh_gia' => 1,
        ]);
    }

    public function test_customer_can_update_review_with_method_spoof_and_keep_old_video(): void
    {
        [$customer, $worker, $bookingId] = $this->seedBookingContext();

        $reviewId = DB::table('danh_gia')->insertGetId([
            'don_dat_lich_id' => $bookingId,
            'nguoi_danh_gia_id' => $customer->id,
            'nguoi_bi_danh_gia_id' => $worker->id,
            'so_sao' => 4,
            'nhan_xet' => 'Ban dau',
            'hinh_anh_danh_gia' => json_encode(['https://res.cloudinary.com/demo/image/upload/reviews/old-image.jpg']),
            'video_danh_gia' => 'https://res.cloudinary.com/demo/video/upload/reviews/old-video.mp4',
            'so_lan_sua' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mock = Mockery::mock(CloudinaryUploadService::class);
        $mock->shouldReceive('uploadUploadedFile')
            ->once()
            ->andReturn(new ApiResponse([
                'secure_url' => 'https://res.cloudinary.com/demo/image/upload/reviews/new-image.jpg',
            ], []));
        $this->app->instance(CloudinaryUploadService::class, $mock);

        Sanctum::actingAs($customer);

        $response = $this->post("/api/danh-gia/{$reviewId}", [
            '_method' => 'PUT',
            'so_sao' => 5,
            'nhan_xet' => 'Da cap nhat media',
            'keep_existing_video' => '1',
            'existing_hinh_anh_danh_gia' => [
                'https://res.cloudinary.com/demo/image/upload/reviews/old-image.jpg',
            ],
            'hinh_anh_danh_gia' => [
                UploadedFile::fake()->image('review-2.jpg'),
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.so_sao', 5);

        /** @var \App\Models\DanhGia $review */
        $review = DanhGia::query()->findOrFail($reviewId);

        $this->assertSame([
            'https://res.cloudinary.com/demo/image/upload/reviews/old-image.jpg',
            'https://res.cloudinary.com/demo/image/upload/reviews/new-image.jpg',
        ], $review->hinh_anh_danh_gia);
        $this->assertSame('https://res.cloudinary.com/demo/video/upload/reviews/old-video.mp4', $review->video_danh_gia);
        $this->assertSame(1, (int) $review->so_lan_sua);
    }

    private function seedBookingContext(): array
    {
        $customer = User::query()->create([
            'name' => 'Customer Review',
            'email' => 'customer-review@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
            'phone_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $worker = User::query()->create([
            'name' => 'Worker Review',
            'email' => 'worker-review@example.com',
            'password' => bcrypt('password'),
            'role' => 'worker',
            'is_active' => true,
            'phone_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workerProfilePayload = [
            'user_id' => $worker->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('ho_so_tho', 'cccd')) {
            $workerProfilePayload['cccd'] = '079' . str_pad((string) $worker->id, 9, '0', STR_PAD_LEFT);
        }

        if (Schema::hasColumn('ho_so_tho', 'trang_thai_duyet')) {
            $workerProfilePayload['trang_thai_duyet'] = 'da_duyet';
        }

        if (Schema::hasColumn('ho_so_tho', 'dang_hoat_dong')) {
            $workerProfilePayload['dang_hoat_dong'] = true;
        }

        if (Schema::hasColumn('ho_so_tho', 'danh_gia_trung_binh')) {
            $workerProfilePayload['danh_gia_trung_binh'] = 0;
        }

        if (Schema::hasColumn('ho_so_tho', 'tong_so_danh_gia')) {
            $workerProfilePayload['tong_so_danh_gia'] = 0;
        }

        DB::table('ho_so_tho')->insert($workerProfilePayload);

        $serviceId = null;
        if (Schema::hasTable('danh_muc_dich_vu')) {
            $servicePayload = [
                'ten_dich_vu' => 'Sua dien dan dung',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('danh_muc_dich_vu', 'trang_thai')) {
                $servicePayload['trang_thai'] = true;
            }

            $serviceId = DB::table('danh_muc_dich_vu')->insertGetId($servicePayload);
        }

        $bookingPayload = [
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'da_xong',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($serviceId !== null && Schema::hasColumn('don_dat_lich', 'dich_vu_id')) {
            $bookingPayload['dich_vu_id'] = $serviceId;
        }

        if (Schema::hasColumn('don_dat_lich', 'thoi_gian_hen')) {
            $bookingPayload['thoi_gian_hen'] = now();
        }

        if (Schema::hasColumn('don_dat_lich', 'ngay_hen')) {
            $bookingPayload['ngay_hen'] = now()->toDateString();
        }

        if (Schema::hasColumn('don_dat_lich', 'khung_gio_hen')) {
            $bookingPayload['khung_gio_hen'] = '08:00-10:00';
        }

        if (Schema::hasColumn('don_dat_lich', 'loai_dat_lich')) {
            $bookingPayload['loai_dat_lich'] = 'at_store';
        }

        if (Schema::hasColumn('don_dat_lich', 'dia_chi')) {
            $bookingPayload['dia_chi'] = '2 Nguyen Dinh Chieu, Nha Trang';
        }

        if (Schema::hasColumn('don_dat_lich', 'phuong_thuc_thanh_toan')) {
            $bookingPayload['phuong_thuc_thanh_toan'] = 'cod';
        }

        if (Schema::hasColumn('don_dat_lich', 'trang_thai_thanh_toan')) {
            $bookingPayload['trang_thai_thanh_toan'] = false;
        }

        $bookingId = DB::table('don_dat_lich')->insertGetId($bookingPayload);

        return [$customer, $worker, $bookingId];
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
                $table->timestamp('phone_verified_at')->nullable();
                $table->string('address')->nullable();
                $table->string('avatar')->nullable();
                $table->enum('role', ['admin', 'customer', 'worker'])->default('customer');
                $table->boolean('is_active')->default(true);
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'phone_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('phone_verified_at')->nullable();
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
                $table->unsignedBigInteger('khach_hang_id');
                $table->unsignedBigInteger('tho_id')->nullable();
                $table->string('trang_thai')->default('cho_xac_nhan');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('danh_muc_dich_vu')) {
            Schema::create('danh_muc_dich_vu', function (Blueprint $table) {
                $table->id();
                $table->string('ten_dich_vu');
                $table->boolean('trang_thai')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('danh_gia')) {
            Schema::create('danh_gia', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('don_dat_lich_id');
                $table->unsignedBigInteger('nguoi_danh_gia_id');
                $table->unsignedBigInteger('nguoi_bi_danh_gia_id');
                $table->integer('so_sao');
                $table->text('nhan_xet')->nullable();
                $table->json('hinh_anh_danh_gia')->nullable();
                $table->string('video_danh_gia')->nullable();
                $table->integer('so_lan_sua')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('danh_gia') && !Schema::hasColumn('danh_gia', 'hinh_anh_danh_gia')) {
            Schema::table('danh_gia', function (Blueprint $table) {
                $table->json('hinh_anh_danh_gia')->nullable();
            });
        }

        if (Schema::hasTable('danh_gia') && !Schema::hasColumn('danh_gia', 'video_danh_gia')) {
            Schema::table('danh_gia', function (Blueprint $table) {
                $table->string('video_danh_gia')->nullable();
            });
        }

        if (Schema::hasTable('danh_gia') && !Schema::hasColumn('danh_gia', 'so_lan_sua')) {
            Schema::table('danh_gia', function (Blueprint $table) {
                $table->integer('so_lan_sua')->default(0);
            });
        }

        if (!Schema::hasTable('ho_so_tho')) {
            Schema::create('ho_so_tho', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->decimal('danh_gia_trung_binh', 3, 2)->default(0);
                $table->integer('tong_so_danh_gia')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('ho_so_tho') && !Schema::hasColumn('ho_so_tho', 'danh_gia_trung_binh')) {
            Schema::table('ho_so_tho', function (Blueprint $table) {
                $table->decimal('danh_gia_trung_binh', 3, 2)->default(0);
            });
        }

        if (Schema::hasTable('ho_so_tho') && !Schema::hasColumn('ho_so_tho', 'tong_so_danh_gia')) {
            Schema::table('ho_so_tho', function (Blueprint $table) {
                $table->integer('tong_so_danh_gia')->default(0);
            });
        }
    }

    private function truncateTables(): void
    {
        foreach (['danh_gia', 'don_dat_lich', 'danh_muc_dich_vu', 'ho_so_tho', 'personal_access_tokens', 'users'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
