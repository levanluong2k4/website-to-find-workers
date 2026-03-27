<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'http://localhost',
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
            'phone_verification.required' => true,
        ]);

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_google_callback_creates_worker_account_and_returns_bridge_view(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
            ]),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google-worker-123',
                'email' => 'google-worker@example.com',
                'email_verified' => true,
                'name' => 'Google Worker',
                'picture' => 'https://example.com/avatar.png',
            ]),
        ]);

        $redirectResponse = $this->get('/auth/google/redirect?role=worker');

        $redirectResponse->assertRedirect();

        parse_str((string) parse_url($redirectResponse->headers->get('Location'), PHP_URL_QUERY), $query);

        $response = $this->get('/auth/google/callback?code=test-code&state=' . urlencode($query['state']));

        $expectedRedirect = str_replace('/', '\\/', url('/verify-phone'));

        $response->assertOk()
            ->assertSee('localStorage.setItem', false)
            ->assertSee('google-worker@example.com', false)
            ->assertSee($expectedRedirect, false);

        $this->assertDatabaseHas('users', [
            'email' => 'google-worker@example.com',
            'role' => 'worker',
            'google_id' => 'google-worker-123',
        ]);

        $this->assertDatabaseHas('ho_so_tho', [
            'user_id' => User::query()->where('email', 'google-worker@example.com')->value('id'),
            'cccd' => 'WAITING_UPDATE_' . User::query()->where('email', 'google-worker@example.com')->value('id'),
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_google_callback_rejects_existing_account_when_selected_role_mismatches(): void
    {
        User::query()->create([
            'name' => 'Existing Worker',
            'email' => 'existing-role@example.com',
            'password' => bcrypt('password'),
            'google_id' => null,
            'role' => 'worker',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
            ]),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google-existing-123',
                'email' => 'existing-role@example.com',
                'email_verified' => true,
                'name' => 'Existing Worker',
            ]),
        ]);

        $redirectResponse = $this->get('/auth/google/redirect?role=customer');

        $redirectResponse->assertRedirect();

        parse_str((string) parse_url($redirectResponse->headers->get('Location'), PHP_URL_QUERY), $query);

        $response = $this->get('/auth/google/callback?code=test-code&state=' . urlencode($query['state']));

        $response->assertRedirect(route('login', ['role' => 'customer']));
        $response->assertSessionHas('auth_error', 'Tài khoản này không phải tài khoản khách hàng.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_google_redirect_reports_missing_configuration_keys(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
            'services.google.redirect' => null,
        ]);

        $response = $this->get('/auth/google/redirect?role=customer');

        $response->assertRedirect(route('login', ['role' => 'customer']));
        $response->assertSessionHas(
            'auth_error',
            'Đăng nhập Google chưa được cấu hình. Thiếu: GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET.'
        );
    }

    public function test_login_view_surfaces_missing_google_configuration_state(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
            'services.google.redirect' => null,
        ]);

        $response = $this->get('/login?role=customer');

        $response->assertOk()
            ->assertSee('data-google-enabled="0"', false)
            ->assertSee('Đăng nhập Google đang tạm tắt.', false)
            ->assertSee('GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET', false)
            ->assertSee(route('auth.google.callback'), false);
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

        if (!Schema::hasTable('ho_so_tho')) {
            Schema::create('ho_so_tho', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('cccd')->unique();
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
        foreach (['personal_access_tokens', 'ho_so_tho', 'users'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
