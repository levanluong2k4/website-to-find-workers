<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\HoSoTho;
use App\Models\User;
use App\Services\Auth\GoogleAuthConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class GoogleAuthController extends Controller
{
    private const GOOGLE_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const GOOGLE_USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    public function redirect(Request $request, GoogleAuthConfigService $googleAuthConfig): RedirectResponse
    {
        $role = $this->resolveRequestedRole($request->query('role'));

        if (!$googleAuthConfig->isConfigured()) {
            return $this->redirectWithError($role, $googleAuthConfig->setupMessage());
        }

        $state = encrypt(json_encode([
            'csrf' => Str::random(40),
            'role' => $role,
            'ts' => now()->timestamp,
        ], JSON_THROW_ON_ERROR));

        $query = http_build_query([
            'client_id' => $googleAuthConfig->clientId(),
            'redirect_uri' => $googleAuthConfig->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'prompt' => 'select_account',
            'state' => $state,
        ]);

        return redirect()->away(self::GOOGLE_AUTH_URL . '?' . $query);
    }

    public function callback(Request $request, GoogleAuthConfigService $googleAuthConfig): RedirectResponse|View
    {
        $state = $this->decodeState($request->query('state'));
        $role = $state['role'] ?? 'customer';

        if (!$state) {
            return $this->redirectWithError('customer', 'Phiên đăng nhập Google không hợp lệ hoặc đã hết hạn.');
        }

        if ($request->filled('error')) {
            return $this->redirectWithError($role, 'Bạn đã hủy đăng nhập bằng Google.');
        }

        if (!$request->filled('code')) {
            return $this->redirectWithError($role, 'Không nhận được mã xác thực từ Google.');
        }

        if (!$googleAuthConfig->isConfigured()) {
            return $this->redirectWithError($role, $googleAuthConfig->setupMessage());
        }

        try {
            $googleProfile = $this->fetchGoogleProfile((string) $request->query('code'), $googleAuthConfig);

            $user = $this->resolveUserFromGoogleProfile($googleProfile, $role);

            if ($roleMismatch = $this->ensureRequestedRoleMatchesUser($user, $role)) {
                return $this->redirectWithError($role, $roleMismatch);
            }

            if (!$user->is_active) {
                return $this->redirectWithError($role, 'Tài khoản đã bị khóa.');
            }

            $user->forceFill([
                'google_id' => $googleProfile['sub'],
                'avatar' => $user->avatar ?: ($googleProfile['picture'] ?? null),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            $token = $user->createToken('google_auth_token')->plainTextToken;

            return view('auth.google-callback', [
                'token' => $token,
                'user' => $user->fresh(),
                'redirectTo' => $this->resolvePostLoginPath($user),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectWithError($role, 'Không thể đăng nhập bằng Google lúc này. Vui lòng thử lại sau.');
        }
    }

    private function fetchGoogleProfile(string $code, GoogleAuthConfigService $googleAuthConfig): array
    {
        $tokenResponse = Http::asForm()
            ->acceptJson()
            ->timeout(15)
            ->post(self::GOOGLE_TOKEN_URL, [
                'code' => $code,
                'client_id' => $googleAuthConfig->clientId(),
                'client_secret' => $googleAuthConfig->clientSecret(),
                'redirect_uri' => $googleAuthConfig->redirectUri(),
                'grant_type' => 'authorization_code',
            ]);

        if ($tokenResponse->failed()) {
            throw new \RuntimeException('Google token exchange failed.');
        }

        $accessToken = $tokenResponse->json('access_token');

        if (!is_string($accessToken) || $accessToken === '') {
            throw new \RuntimeException('Google access token missing.');
        }

        $profileResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(15)
            ->get(self::GOOGLE_USERINFO_URL);

        if ($profileResponse->failed()) {
            throw new \RuntimeException('Google userinfo request failed.');
        }

        $profile = $profileResponse->json();
        $email = $profile['email'] ?? null;
        $googleId = $profile['sub'] ?? null;
        $emailVerified = filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!is_string($email) || $email === '' || !is_string($googleId) || $googleId === '' || !$emailVerified) {
            throw new \RuntimeException('Google profile is missing required verified fields.');
        }

        return $profile;
    }

    private function resolveUserFromGoogleProfile(array $googleProfile, string $role): User
    {
        $email = $googleProfile['email'];
        $googleId = $googleProfile['sub'];

        $user = User::query()
            ->where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            return $user;
        }

        $user = User::query()->create([
            'name' => $googleProfile['name'] ?? Str::before($email, '@'),
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'phone' => null,
            'avatar' => $googleProfile['picture'] ?? null,
            'google_id' => $googleId,
            'role' => $role,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        if ($user->role === 'worker') {
            HoSoTho::create([
                'user_id' => $user->id,
                'cccd' => 'WAITING_UPDATE_' . $user->id,
            ]);
        }

        return $user;
    }

    private function decodeState(?string $state): ?array
    {
        if (!is_string($state) || $state === '') {
            return null;
        }

        try {
            $payload = decrypt($state);
            $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        $role = $decoded['role'] ?? null;
        $timestamp = (int) ($decoded['ts'] ?? 0);

        if (!in_array($role, ['customer', 'worker'], true)) {
            return null;
        }

        if ($timestamp < now()->subMinutes(10)->timestamp) {
            return null;
        }

        return $decoded;
    }

    private function resolveRequestedRole(?string $role): string
    {
        return in_array($role, ['customer', 'worker'], true) ? $role : 'customer';
    }

    private function redirectWithError(string $role, string $message): RedirectResponse
    {
        return redirect()
            ->route('login', ['role' => $this->resolveRequestedRole($role)])
            ->with('auth_error', $message);
    }

    private function resolvePostLoginPath(User $user): string
    {
        if (
            $user->role !== 'admin'
            && (bool) config('phone_verification.required', false)
            && !$user->phone_verified_at
        ) {
            return url('/verify-phone');
        }

        return match ($user->role) {
            'admin' => url('/admin/dashboard'),
            'worker' => url('/worker/dashboard'),
            default => url('/customer/home'),
        };
    }

    private function ensureRequestedRoleMatchesUser(User $user, string $requestedRole): ?string
    {
        if ($user->role === 'admin' || $user->role === $requestedRole) {
            return null;
        }

        return match ($requestedRole) {
            'worker' => 'Tài khoản này không phải tài khoản thợ.',
            'customer' => 'Tài khoản này không phải tài khoản khách hàng.',
            default => 'Tài khoản này không thuộc vai trò đã chọn.',
        };
    }
}
