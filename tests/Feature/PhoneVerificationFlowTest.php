<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsurePhoneVerified;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhoneVerificationFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.env' => 'local',
            'app.url' => 'http://localhost',
            'phone_verification.required' => true,
            'phone_verification.demo.enabled' => true,
            'phone_verification.demo.numbers' => ['0900000001', '0900000002'],
            'phone_verification.demo.code' => '135790',
            'phone_verification.real.enabled' => false,
            'phone_verification.real.provider' => '',
        ]);

        $this->prepareSchema();
        $this->truncateTables();

        Route::middleware(['auth:sanctum', EnsurePhoneVerified::class])->get('/api/test-phone-protected', function () {
            return response()->json(['ok' => true]);
        });
    }

    public function test_email_otp_verification_requires_phone_verification_when_user_is_unverified(): void
    {
        $user = User::query()->create([
            'name' => 'OTP User',
            'email' => 'otp-user@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('otp_codes')->insert([
            'email' => $user->email,
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/verify-otp', [
            'email' => $user->email,
            'code' => '123456',
            'role' => 'customer',
        ]);

        $expectedVerifyUrl = url('/verify-phone');

        $response->assertOk()
            ->assertJsonPath('requires_phone_verification', true)
            ->assertJsonPath('phone_verification_url', $expectedVerifyUrl);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_demo_phone_verification_request_and_verify_updates_user(): void
    {
        $user = User::query()->create([
            'name' => 'Phone Demo',
            'email' => 'phone-demo@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $requestResponse = $this->postJson('/api/phone-verification/request', [
            'phone' => '0900000001',
            'mode' => 'demo',
        ]);

        $requestResponse->assertOk()
            ->assertJsonPath('mode', 'demo')
            ->assertJsonPath('phone', '0900000001')
            ->assertJsonPath('debug_otp', '135790');

        $this->assertDatabaseHas('phone_verification_codes', [
            'user_id' => $user->id,
            'phone' => '0900000001',
            'mode' => 'demo',
            'code' => '135790',
        ]);

        $verifyResponse = $this->postJson('/api/phone-verification/verify', [
            'phone' => '0900000001',
            'mode' => 'demo',
            'code' => '135790',
        ]);

        $expectedRedirect = url('/customer/home');

        $verifyResponse->assertOk()
            ->assertJsonPath('user.phone', '0900000001')
            ->assertJsonPath('user.phone_verification_mode', 'demo')
            ->assertJsonPath('redirect_to', $expectedRedirect);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '0900000001',
            'phone_verification_mode' => 'demo',
        ]);
        $this->assertNotNull(User::query()->find($user->id)?->phone_verified_at);
    }

    public function test_real_phone_option_returns_configuration_error_when_provider_is_missing(): void
    {
        $user = User::query()->create([
            'name' => 'Phone Real',
            'email' => 'phone-real@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/phone-verification/request', [
            'phone' => '0912345678',
            'mode' => 'real',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mode']);
    }

    public function test_unverified_user_cannot_access_phone_protected_api(): void
    {
        $user = User::query()->create([
            'name' => 'Need Phone Verify',
            'email' => 'need-phone@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/test-phone-protected');

        $expectedVerifyUrl = url('/verify-phone');

        $response->assertStatus(403)
            ->assertJsonPath('requires_phone_verification', true)
            ->assertJsonPath('phone_verification_url', $expectedVerifyUrl);
    }

    public function test_phone_protection_is_bypassed_when_requirement_is_disabled(): void
    {
        config(['phone_verification.required' => false]);

        $user = User::query()->create([
            'name' => 'Phone Optional',
            'email' => 'phone-optional@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/test-phone-protected')
            ->assertOk()
            ->assertJson(['ok' => true]);
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
                $table->timestamp('phone_verified_at')->nullable();
                $table->string('phone_verification_mode')->nullable();
                $table->string('address')->nullable();
                $table->string('avatar')->nullable();
                $table->string('google_id')->nullable()->unique();
                $table->enum('role', ['admin', 'customer', 'worker'])->default('customer');
                $table->boolean('is_active')->default(true);
                $table->rememberToken();
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

        if (!Schema::hasTable('phone_verification_codes')) {
            Schema::create('phone_verification_codes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('phone', 20);
                $table->string('mode', 20);
                $table->string('code', 6);
                $table->timestamp('expires_at');
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
    }

    private function truncateTables(): void
    {
        foreach (['personal_access_tokens', 'phone_verification_codes', 'otp_codes', 'users'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
