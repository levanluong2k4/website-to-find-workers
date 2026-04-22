<?php

namespace App\Support;

use Illuminate\Support\Str;

final class CertificatePathResolver
{
    /**
     * @param  array<int, mixed>  $candidates
     */
    public static function resolveFromCandidates(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            $normalized = self::normalizePath($candidate);

            if (self::isReadableCertificateFile($normalized)) {
                return $normalized;
            }
        }

        return null;
    }

    public static function resolveLaragonCaBundlePath(): ?string
    {
        $phpBinaryDir = dirname(PHP_BINARY);

        return self::resolveFromCandidates([
            $phpBinaryDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'cacert.pem',
            $phpBinaryDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'cacert.pem',
        ]);
    }

    public static function normalizePath(string $path): string
    {
        $trimmed = trim($path);

        if ($trimmed === '') {
            return '';
        }

        $resolved = realpath($trimmed);

        return $resolved !== false ? $resolved : $trimmed;
    }

    public static function isReadableCertificateFile(?string $path): bool
    {
        if (!is_string($path) || trim($path) === '') {
            return false;
        }

        $normalized = self::normalizePath($path);

        return is_file($normalized)
            && is_readable($normalized)
            && Str::endsWith(Str::lower($normalized), '.pem');
    }
}
