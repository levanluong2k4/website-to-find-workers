<?php

namespace App\Services\Media;

use App\Support\CertificatePathResolver;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
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
            CertificatePathResolver::resolveLaragonCaBundlePath(),
            'D:\\laragon\\etc\\ssl\\cacert.pem',
            'C:\\laragon\\etc\\ssl\\cacert.pem',
        ];

        $resolvedPath = CertificatePathResolver::resolveFromCandidates($candidates);

        if ($resolvedPath !== null) {
            $this->resolvedCaBundlePath = $resolvedPath;

            return $this->resolvedCaBundlePath;
        }

        throw new RuntimeException('Khong tim thay file chung chi SSL hop le de tai len media.');
    }
}
