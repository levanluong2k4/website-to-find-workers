<?php

use App\Models\CustomerFollowUp;
use App\Models\CustomerTag;
use App\Models\DanhGia;
use App\Models\DanhMucDichVu;
use App\Models\DonDatLich;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('playwright:seed-admin-crm {--json}', function () {
    $fixtureEmails = [
        'playwright.admin.crm@example.com',
        'playwright.assignee.crm@example.com',
        'playwright.customer.needs-care@example.com',
        'playwright.customer.quiet@example.com',
        'playwright.worker.crm@example.com',
    ];
    $serviceName = 'Playwright CRM Washing Machine';
    $tagSlug = 'playwright-care';
    $password = 'Playwright123!';

    $payload = DB::transaction(function () use ($fixtureEmails, $serviceName, $tagSlug, $password) {
        $existingUserIds = User::query()
            ->whereIn('email', $fixtureEmails)
            ->pluck('id');

        $bookingIds = DonDatLich::query()
            ->whereIn('khach_hang_id', $existingUserIds)
            ->orWhereIn('tho_id', $existingUserIds)
            ->pluck('id');

        if ($existingUserIds->isNotEmpty()) {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $existingUserIds)
                ->delete();

            DB::table('customer_feedback_cases')
                ->whereIn('customer_id', $existingUserIds)
                ->orWhereIn('worker_id', $existingUserIds)
                ->orWhereIn('assigned_admin_id', $existingUserIds)
                ->delete();

            DB::table('customer_follow_ups')
                ->whereIn('customer_id', $existingUserIds)
                ->orWhereIn('created_by_admin_id', $existingUserIds)
                ->orWhereIn('assigned_admin_id', $existingUserIds)
                ->delete();

            DB::table('customer_notes')
                ->whereIn('customer_id', $existingUserIds)
                ->orWhereIn('admin_id', $existingUserIds)
                ->delete();

            DB::table('customer_tag_assignments')
                ->whereIn('customer_id', $existingUserIds)
                ->orWhereIn('admin_id', $existingUserIds)
                ->delete();
        }

        if ($bookingIds->isNotEmpty()) {
            DB::table('don_dat_lich_dich_vu')
                ->whereIn('don_dat_lich_id', $bookingIds)
                ->delete();

            DanhGia::query()
                ->whereIn('don_dat_lich_id', $bookingIds)
                ->delete();

            DonDatLich::query()
                ->whereIn('id', $bookingIds)
                ->delete();
        }

        CustomerTag::query()->where('slug', $tagSlug)->delete();
        DanhMucDichVu::query()->where('ten_dich_vu', $serviceName)->delete();

        if (Schema::hasTable('otp_codes')) {
            DB::table('otp_codes')->whereIn('email', $fixtureEmails)->delete();
        }

        if (Schema::hasTable('phone_verification_codes')) {
            DB::table('phone_verification_codes')->whereIn('user_id', $existingUserIds)->delete();
        }

        User::query()->whereIn('email', $fixtureEmails)->delete();

        $userDefaults = [
            'is_active' => true,
            'password' => $password,
        ];

        if (Schema::hasColumn('users', 'email_verified_at')) {
            $userDefaults['email_verified_at'] = now();
        }

        if (Schema::hasColumn('users', 'phone_verified_at')) {
            $userDefaults['phone_verified_at'] = now();
        }

        if (Schema::hasColumn('users', 'phone_verification_mode')) {
            $userDefaults['phone_verification_mode'] = 'demo';
        }

        $ownerAdmin = User::query()->create(array_merge($userDefaults, [
            'name' => 'Playwright Owner Admin',
            'email' => 'playwright.admin.crm@example.com',
            'phone' => '0900000001',
            'address' => '123 Nguyen Van Linh, Quan 7',
            'role' => 'admin',
        ]));

        $assigneeAdmin = User::query()->create(array_merge($userDefaults, [
            'name' => 'Playwright Assignee Admin',
            'email' => 'playwright.assignee.crm@example.com',
            'phone' => '0900000002',
            'address' => '456 Le Van Sy, Quan 3',
            'role' => 'admin',
        ]));

        $customer = User::query()->create(array_merge($userDefaults, [
            'name' => 'Playwright Needs Care Customer',
            'email' => 'playwright.customer.needs-care@example.com',
            'phone' => '0900000003',
            'address' => '88 Nguyen Huu Tho, Quan 7',
            'role' => 'customer',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(1),
        ]));

        User::query()->create(array_merge($userDefaults, [
            'name' => 'Playwright Quiet Customer',
            'email' => 'playwright.customer.quiet@example.com',
            'phone' => '0900000010',
            'address' => '15 Tran Phu, Quan 1',
            'role' => 'customer',
            'created_at' => now()->subDays(80),
            'updated_at' => now()->subDays(5),
        ]));

        $worker = User::query()->create(array_merge($userDefaults, [
            'name' => 'Playwright Field Worker',
            'email' => 'playwright.worker.crm@example.com',
            'phone' => '0900000004',
            'address' => '21 Hai Ba Trung, Quan 1',
            'role' => 'worker',
            'created_at' => now()->subDays(50),
            'updated_at' => now()->subDays(1),
        ]));

        $service = DanhMucDichVu::query()->create([
            'ten_dich_vu' => $serviceName,
            'mo_ta' => 'Fixture service for Playwright admin CRM end-to-end tests.',
            'hinh_anh' => null,
            'trang_thai' => 1,
        ]);

        $booking = DonDatLich::query()->create([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'dich_vu_id' => $service->id,
            'loai_dat_lich' => 'at_home',
            'ngay_hen' => now()->subDay()->toDateString(),
            'khung_gio_hen' => '08:00-10:00',
            'thoi_gian_hen' => now()->subDay()->setTime(8, 30),
            'thoi_gian_hoan_thanh' => now()->subDay()->setTime(10, 0),
            'dia_chi' => '88 Nguyen Huu Tho, Quan 7',
            'mo_ta_van_de' => 'Playwright fixture: machine does not spin and needs a callback.',
            'trang_thai' => 'da_xong',
            'phuong_thuc_thanh_toan' => 'transfer',
            'trang_thai_thanh_toan' => true,
            'tong_tien' => 450000,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDay(),
        ]);

        $booking->dichVus()->sync([$service->id]);

        $review = DanhGia::query()->create([
            'don_dat_lich_id' => $booking->id,
            'nguoi_danh_gia_id' => $customer->id,
            'nguoi_bi_danh_gia_id' => $worker->id,
            'so_sao' => 1,
            'nhan_xet' => 'Playwright fixture: worker arrived late and issue was not resolved well.',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $tag = CustomerTag::query()->create([
            'label' => 'Playwright Care',
            'slug' => $tagSlug,
            'color' => 'amber',
        ]);

        $customer->customerTags()->attach($tag->id, [
            'admin_id' => $ownerAdmin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $overdueFollowUp = CustomerFollowUp::query()->create([
            'customer_id' => $customer->id,
            'booking_id' => $booking->id,
            'created_by_admin_id' => $ownerAdmin->id,
            'assigned_admin_id' => $ownerAdmin->id,
            'title' => 'Playwright overdue callback',
            'channel' => 'call',
            'priority' => 'high',
            'status' => 'pending',
            'scheduled_at' => now()->subHours(3),
            'note' => 'Playwright fixture: confirm refund expectation and next support step.',
            'created_at' => now()->subHours(4),
            'updated_at' => now()->subHours(3),
        ]);

        return [
            'admin' => [
                'email' => $ownerAdmin->email,
                'password' => $password,
                'id' => $ownerAdmin->id,
                'name' => $ownerAdmin->name,
            ],
            'assignee_admin' => [
                'id' => $assigneeAdmin->id,
                'name' => $assigneeAdmin->name,
            ],
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ],
            'service' => [
                'id' => $service->id,
                'name' => $service->ten_dich_vu,
            ],
            'booking' => [
                'id' => $booking->id,
                'code' => 'DD-' . str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT),
            ],
            'feedback' => [
                'case_key' => 'low_rating-' . $review->id,
            ],
            'follow_up' => [
                'id' => $overdueFollowUp->id,
                'title' => $overdueFollowUp->title,
            ],
        ];
    });

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($this->option('json')) {
        $this->line($json);
        return;
    }

    $this->info('Seeded Playwright admin CRM fixtures.');
    $this->line($json);
})->purpose('Seed deterministic admin CRM fixtures for Playwright end-to-end tests');

Schedule::command('app:cancel-expired-bookings')->everyMinute();
Schedule::command('app:send-worker-booking-reminders')->everyMinute()->withoutOverlapping();
