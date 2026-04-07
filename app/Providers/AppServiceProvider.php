<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureCaBundleFallback();
    }

    private function configureCaBundleFallback(): void
    {
        $configuredCurlCa = (string) ini_get('curl.cainfo');
        $configuredOpenSslCa = (string) ini_get('openssl.cafile');

        if ($this->isReadableCertificateFile($configuredCurlCa) || $this->isReadableCertificateFile($configuredOpenSslCa)) {
            $resolvedPath = $this->isReadableCertificateFile($configuredCurlCa) ? $configuredCurlCa : $configuredOpenSslCa;
            $this->applyCaBundlePath($resolvedPath);
            return;
        }

        $fallbackPath = collect([
            env('CURL_CA_BUNDLE'),
            env('SSL_CERT_FILE'),
            base_path('cacert.pem'),
            base_path('certs/cacert.pem'),
            base_path('storage/certs/cacert.pem'),
            $this->resolveLaragonCaBundlePath(),
            'D:\\laragon\\etc\\ssl\\cacert.pem',
            'C:\\laragon\\etc\\ssl\\cacert.pem',
        ])->filter(fn ($path) => is_string($path) && $path !== '')
            ->map(fn (string $path) => $this->normalizePath($path))
            ->first(fn (string $path) => $this->isReadableCertificateFile($path));

        if ($fallbackPath) {
            $this->applyCaBundlePath($fallbackPath);
        }
    }

    private function applyCaBundlePath(string $path): void
    {
        putenv("CURL_CA_BUNDLE={$path}");
        putenv("SSL_CERT_FILE={$path}");

        $_ENV['CURL_CA_BUNDLE'] = $path;
        $_ENV['SSL_CERT_FILE'] = $path;
        $_SERVER['CURL_CA_BUNDLE'] = $path;
        $_SERVER['SSL_CERT_FILE'] = $path;

        @ini_set('curl.cainfo', $path);
        @ini_set('openssl.cafile', $path);
    }

    private function resolveLaragonCaBundlePath(): ?string
    {
        $phpBinaryDir = dirname(PHP_BINARY);
        $candidates = [
            $phpBinaryDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'cacert.pem',
            $phpBinaryDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'cacert.pem',
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizePath($candidate);
            if ($this->isReadableCertificateFile($normalized)) {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizePath(string $path): string
    {
        $trimmed = trim($path);

        if ($trimmed === '') {
            return '';
        }

        $resolved = realpath($trimmed);

        return $resolved !== false ? $resolved : $trimmed;
    }

    private function isReadableCertificateFile(?string $path): bool
    {
        if (! is_string($path) || trim($path) === '') {
            return false;
        }

        $normalized = $this->normalizePath($path);

        return is_file($normalized) && is_readable($normalized) && Str::endsWith(Str::lower($normalized), '.pem');
    }
}
