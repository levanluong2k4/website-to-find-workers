<?php

namespace App\Support;

final class HttpClientTlsConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function options(): array
    {
        $verifyPath = self::resolveVerifyPath();

        return $verifyPath !== null
            ? ['verify' => $verifyPath]
            : [];
    }

    private static function resolveVerifyPath(): ?string
    {
        return CertificatePathResolver::resolveFromCandidates([
            getenv('CURL_CA_BUNDLE') ?: null,
            $_ENV['CURL_CA_BUNDLE'] ?? null,
            $_SERVER['CURL_CA_BUNDLE'] ?? null,
            getenv('SSL_CERT_FILE') ?: null,
            $_ENV['SSL_CERT_FILE'] ?? null,
            $_SERVER['SSL_CERT_FILE'] ?? null,
            ini_get('openssl.cafile') ?: null,
            ini_get('curl.cainfo') ?: null,
            base_path('cacert.pem'),
            base_path('certs/cacert.pem'),
            base_path('storage/certs/cacert.pem'),
            CertificatePathResolver::resolveLaragonCaBundlePath(),
            'D:\\laragon\\etc\\ssl\\cacert.pem',
            'C:\\laragon\\etc\\ssl\\cacert.pem',
        ]);
    }
}
