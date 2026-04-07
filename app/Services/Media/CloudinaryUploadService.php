<?php

namespace App\Services\Media;

use Cloudinary\Api\ApiResponse;
use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class CloudinaryUploadService
{
    private ?string $resolvedCaBundlePath = null;

    public function __construct(private readonly Cloudinary $cloudinary)
    {
    }

    public function uploadUploadedFile(UploadedFile $file, array $options = []): ApiResponse
    {
        $realPath = $file->getRealPath();

        if (!is_string($realPath) || trim($realPath) === '') {
            throw new RuntimeException('Khong doc duoc tep tam de tai len.');
        }

        return $this->upload($realPath, $options);
    }

    public function upload(string $filePath, array $options = []): ApiResponse
    {
        $uploadOptions = $this->buildUploadOptions($options);

        try {
            return $this->cloudinary->uploadApi()->upload($filePath, $uploadOptions);
        } catch (Throwable $exception) {
            Log::error('Cloudinary media upload failed', [
                'message' => $exception->getMessage(),
                'folder' => $options['folder'] ?? null,
                'resource_type' => $options['resource_type'] ?? 'image',
                'file_name' => basename($filePath),
            ]);

            throw new RuntimeException('Khong the tai len media luc nay. Vui long thu lai.', 0, $exception);
        }
    }

    private function buildUploadOptions(array $options): array
    {
        if (array_key_exists('verify', $options)) {
            return $options;
        }

        $options['verify'] = $this->resolveCaBundlePath();

        return $options;
    }

    private function resolveCaBundlePath(): string
    {
        if ($this->resolvedCaBundlePath !== null) {
            return $this->resolvedCaBundlePath;
        }

        $candidates = [
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
            $this->resolveLaragonCaBundlePath(),
            'D:\\laragon\\etc\\ssl\\cacert.pem',
            'C:\\laragon\\etc\\ssl\\cacert.pem',
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            $normalized = $this->normalizePath($candidate);
            if ($this->isReadableCertificateFile($normalized)) {
                $this->resolvedCaBundlePath = $normalized;

                return $this->resolvedCaBundlePath;
            }
        }

        throw new RuntimeException('Khong tim thay file chung chi SSL hop le de tai len media.');
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
        if (!is_string($path) || trim($path) === '') {
            return false;
        }

        $normalized = $this->normalizePath($path);

        return is_file($normalized) && is_readable($normalized) && Str::endsWith(Str::lower($normalized), '.pem');
    }
}
