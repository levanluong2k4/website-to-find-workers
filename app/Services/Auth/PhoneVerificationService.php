<?php

namespace App\Services\Auth;

use App\Models\PhoneVerificationCode;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PhoneVerificationService
{
    public const MODE_DEMO = 'demo';

    public const MODE_REAL = 'real';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function availableModes(): array
    {
        return [
            [
                'key' => self::MODE_DEMO,
                'label' => 'sdt demo',
                'enabled' => $this->isDemoEnabled(),
                'description' => 'Dung cho local/demo. Chi chap nhan cac so test da cau hinh.',
            ],
            [
                'key' => self::MODE_REAL,
                'label' => 'sdt that',
                'enabled' => $this->isRealEnabled(),
                'description' => $this->isRealEnabled()
                    ? 'Gui OTP toi so dien thoai that qua cong SMS/Zalo da cau hinh.'
                    : 'Can cau hinh cong SMS/Zalo truoc khi su dung.',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function demoNumbers(): array
    {
        return array_values(array_unique(array_map([$this, 'normalizePhone'], config('phone_verification.demo.numbers', []))));
    }

    public function requestCode(User $user, string $phone, string $mode): array
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $this->validatePhone($normalizedPhone);
        $this->assertPhoneAvailableForUser($user, $normalizedPhone);

        if ($mode === self::MODE_DEMO) {
            $this->ensureDemoModeAvailable($normalizedPhone);
            $code = $this->demoCode();
        } elseif ($mode === self::MODE_REAL) {
            $this->ensureRealModeAvailable();
            $code = $this->generateOtp();
        } else {
            throw ValidationException::withMessages([
                'mode' => 'Che do xac minh so dien thoai khong hop le.',
            ]);
        }

        PhoneVerificationCode::query()
            ->where('user_id', $user->id)
            ->delete();

        PhoneVerificationCode::query()->create([
            'user_id' => $user->id,
            'phone' => $normalizedPhone,
            'mode' => $mode,
            'code' => $code,
            'expires_at' => now()->addMinutes($this->ttlMinutes()),
        ]);

        return [
            'message' => $mode === self::MODE_DEMO
                ? 'Ma OTP demo da san sang.'
                : 'Ma OTP da duoc dua vao hang doi gui toi so dien thoai that.',
            'phone' => $normalizedPhone,
            'mode' => $mode,
            'debug_otp' => config('app.env') === 'local' ? $code : null,
        ];
    }

    public function verifyCode(User $user, string $phone, string $mode, string $code): User
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $this->validatePhone($normalizedPhone);

        $record = PhoneVerificationCode::query()
            ->where('user_id', $user->id)
            ->where('phone', $normalizedPhone)
            ->where('mode', $mode)
            ->where('code', $code)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'code' => 'Ma OTP so dien thoai khong hop le hoac da het han.',
            ]);
        }

        $this->assertPhoneAvailableForUser($user, $normalizedPhone);

        $user->forceFill([
            'phone' => $normalizedPhone,
            'phone_verified_at' => now(),
            'phone_verification_mode' => $mode,
        ])->save();

        PhoneVerificationCode::query()
            ->where('user_id', $user->id)
            ->delete();

        return $user->fresh();
    }

    public function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/[\s\-.]+/', '', trim($phone)) ?? '';

        if (str_starts_with($normalized, '+84')) {
            $normalized = '0' . substr($normalized, 3);
        } elseif (str_starts_with($normalized, '84') && strlen($normalized) >= 10) {
            $normalized = '0' . substr($normalized, 2);
        }

        return $normalized;
    }

    public function isDemoEnabled(): bool
    {
        return (bool) config('phone_verification.demo.enabled', true);
    }

    public function isRealEnabled(): bool
    {
        return (bool) config('phone_verification.real.enabled', false);
    }

    public function realProvider(): string
    {
        return trim((string) config('phone_verification.real.provider', ''));
    }

    private function ensureDemoModeAvailable(string $phone): void
    {
        if (!$this->isDemoEnabled()) {
            throw ValidationException::withMessages([
                'mode' => 'Che do sdt demo dang bi tat.',
            ]);
        }

        $demoNumbers = $this->demoNumbers();
        if ($demoNumbers !== [] && !in_array($phone, $demoNumbers, true)) {
            throw ValidationException::withMessages([
                'phone' => 'So nay khong nam trong danh sach sdt demo duoc phep.',
            ]);
        }
    }

    private function ensureRealModeAvailable(): void
    {
        if (!$this->isRealEnabled()) {
            throw ValidationException::withMessages([
                'mode' => 'Che do sdt that chua duoc cau hinh cong SMS/Zalo tren he thong.',
            ]);
        }

        if ($this->realProvider() === '') {
            throw ValidationException::withMessages([
                'mode' => 'He thong chua khai bao nha cung cap gui OTP so dien thoai.',
            ]);
        }
    }

    private function validatePhone(string $phone): void
    {
        if (!preg_match('/^0\d{9,10}$/', $phone)) {
            throw ValidationException::withMessages([
                'phone' => 'So dien thoai khong hop le. Vui long nhap dung dinh dang Viet Nam.',
            ]);
        }
    }

    private function assertPhoneAvailableForUser(User $user, string $phone): void
    {
        $exists = User::query()
            ->where('id', '!=', $user->id)
            ->where('phone', $phone)
            ->whereNotNull('phone_verified_at')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'phone' => 'So dien thoai nay da duoc xac minh cho tai khoan khac.',
            ]);
        }
    }

    private function demoCode(): string
    {
        $code = preg_replace('/\D/', '', (string) config('phone_verification.demo.code', '135790')) ?? '';
        $code = str_pad(substr($code, 0, 6), 6, '0');

        return $code !== '000000' ? $code : '135790';
    }

    private function generateOtp(): string
    {
        return sprintf('%06d', random_int(0, 999999));
    }

    private function ttlMinutes(): int
    {
        return max(1, (int) config('phone_verification.demo.ttl_minutes', 10));
    }
}
