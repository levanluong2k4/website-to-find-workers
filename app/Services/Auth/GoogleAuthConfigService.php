<?php

namespace App\Services\Auth;

class GoogleAuthConfigService
{
    /**
     * @return array<int, string>
     */
    public function missingKeys(): array
    {
        $missing = [];

        if (!$this->hasClientId()) {
            $missing[] = 'GOOGLE_CLIENT_ID';
        }

        if (!$this->hasClientSecret()) {
            $missing[] = 'GOOGLE_CLIENT_SECRET';
        }

        return $missing;
    }

    public function isConfigured(): bool
    {
        return $this->missingKeys() === [];
    }

    public function clientId(): ?string
    {
        return $this->normalize(config('services.google.client_id'));
    }

    public function clientSecret(): ?string
    {
        return $this->normalize(config('services.google.client_secret'));
    }

    public function redirectUri(): string
    {
        return (string) ($this->normalize(config('services.google.redirect')) ?: route('auth.google.callback'));
    }

    public function setupMessage(): string
    {
        if ($this->isConfigured()) {
            return '';
        }

        return 'Đăng nhập Google chưa được cấu hình. Thiếu: ' . implode(', ', $this->missingKeys()) . '.';
    }

    private function hasClientId(): bool
    {
        return $this->clientId() !== null;
    }

    private function hasClientSecret(): bool
    {
        return $this->clientSecret() !== null;
    }

    private function normalize(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
