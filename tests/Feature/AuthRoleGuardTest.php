<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthRoleGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_login_rejects_customer_account_when_worker_role_is_selected(): void
    {
        Mail::fake();

        User::query()->create([
            'name' => 'Customer User',
            'email' => 'customer-role@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'customer-role@example.com',
            'password' => 'password',
            'role' => 'worker',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Tài khoản này không phải tài khoản thợ.')
            ->assertJsonPath('actual_role', 'customer')
            ->assertJsonPath('selected_role', 'worker');

        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_login_rejects_worker_account_when_customer_role_is_selected(): void
    {
        Mail::fake();

        User::query()->create([
            'name' => 'Worker User',
            'email' => 'worker-role@example.com',
            'password' => bcrypt('password'),
            'role' => 'worker',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'worker-role@example.com',
            'password' => 'password',
            'role' => 'customer',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Tài khoản này không phải tài khoản khách hàng.')
            ->assertJsonPath('actual_role', 'worker')
            ->assertJsonPath('selected_role', 'customer');

        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_verify_otp_rejects_mismatched_selected_role(): void
    {
        $user = User::query()->create([
            'name' => 'Worker Verify',
            'email' => 'worker-verify@example.com',
            'password' => bcrypt('password'),
            'role' => 'worker',
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

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Tài khoản này không phải tài khoản khách hàng.')
            ->assertJsonPath('actual_role', 'worker')
            ->assertJsonPath('selected_role', 'customer');

        $this->assertDatabaseCount('personal_access_tokens', 0);
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

        if (!Schema::hasTable('otp_codes')) {
            Schema::create('otp_codes', function (Blueprint $table) {
                $table->id();
                $table->string('email');
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
        foreach (['personal_access_tokens', 'otp_codes', 'users'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
