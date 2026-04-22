<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\Auth\ResetPasswordLinkNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ForgotPasswordFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
        config(['app.env' => 'local']);
    }

    public function test_forgot_password_sends_reset_notification_and_returns_debug_link(): void
    {
        Notification::fake();

        $user = User::query()->create([
            'name' => 'Khach Reset',
            'email' => 'forgot-password@example.com',
            'password' => bcrypt('old-password'),
            'role' => 'customer',
            'is_active' => true,
            'remember_token' => 'remember-old',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
            'role' => 'customer',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi liên kết đặt lại mật khẩu.');

        Notification::assertSentTo($user, ResetPasswordLinkNotification::class, function (ResetPasswordLinkNotification $notification) use ($response): bool {
            return str_contains((string) $response->json('debug_reset_url'), $notification->token);
        });
    }

    public function test_reset_password_updates_password_and_clears_reset_token(): void
    {
        Notification::fake();

        $user = User::query()->create([
            'name' => 'Tho Reset',
            'email' => 'reset-password@example.com',
            'password' => bcrypt('old-password'),
            'role' => 'worker',
            'is_active' => true,
            'remember_token' => 'remember-old',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/forgot-password', [
            'email' => $user->email,
            'role' => 'worker',
        ])->assertOk();

        $issuedToken = null;

        Notification::assertSentTo($user, ResetPasswordLinkNotification::class, function (ResetPasswordLinkNotification $notification) use (&$issuedToken): bool {
            $issuedToken = $notification->token;

            return true;
        });

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $issuedToken,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.');

        $user->refresh();

        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertNotSame('remember-old', $user->remember_token);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    private function prepareSchema(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('avatar')->nullable();
                $table->enum('role', ['admin', 'customer', 'worker'])->default('customer');
                $table->boolean('is_active')->default(true);
                $table->rememberToken();
                $table->timestamp('phone_verified_at')->nullable();
                $table->string('phone_verification_mode')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('otp_codes')) {
            Schema::create('otp_codes', function (Blueprint $table) {
                $table->id();
                $table->string('email');
                $table->string('code', 6);
                $table->timestamp('expires_at');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
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
    }

    private function truncateTables(): void
    {
        foreach (['personal_access_tokens', 'password_reset_tokens', 'otp_codes', 'users'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
