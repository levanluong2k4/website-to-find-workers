<?php

namespace App\Console\Commands;

use App\Services\Media\CloudinaryUploadService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class UploadStaticAssetsToCloudinary extends Command
{
    protected $signature = 'app:upload-static-assets-to-cloudinary
        {path=public/assets/images : Local asset directory to upload}
        {--folder=website-to-find-workers/assets/images : Cloudinary folder/public ID prefix}
        {--dry-run : Show files without uploading}
        {--overwrite : Overwrite existing Cloudinary assets with the same public ID}';

    protected $description = 'Upload static project images and videos to Cloudinary while preserving relative paths.';

    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov', 'm4v', 'avi'];

    public function handle(CloudinaryUploadService $cloudinaryUploadService): int
    {
        $root = base_path(trim((string) $this->argument('path'), '/\\'));
        $folder = trim((string) $this->option('folder'), '/');

        if (! is_dir($root)) {
            $this->error("Directory does not exist: {$root}");

            return self::FAILURE;
        }

        $files = $this->collectFiles($root);

        if ($files === []) {
            $this->warn('No assets found.');

            return self::SUCCESS;
        }

        $this->info('Found ' . count($files) . ' asset(s).');

        foreach ($files as $file) {
            $relativePath = $this->relativePath($root, $file->getPathname());
            $extension = Str::lower($file->getExtension());
            $publicId = $folder . '/' . preg_replace('/\.[^.]+$/', '', $relativePath);
            $resourceType = in_array($extension, self::VIDEO_EXTENSIONS, true) ? 'video' : 'image';

            if ($this->option('dry-run')) {
                $this->line("[dry-run] {$relativePath} -> {$resourceType}:{$publicId}");

                continue;
            }

            $result = $cloudinaryUploadService->upload($file->getPathname(), [
                'resource_type' => $resourceType,
                'public_id' => $publicId,
                'overwrite' => (bool) $this->option('overwrite'),
                'invalidate' => (bool) $this->option('overwrite'),
            ]);

            $secureUrl = is_array($result) ? ($result['secure_url'] ?? '') : '';
            $this->line("Uploaded {$relativePath}" . ($secureUrl !== '' ? " -> {$secureUrl}" : ''));
        }

        return self::SUCCESS;
    }

    /**
     * @return list<SplFileInfo>
     */
    private function collectFiles(string $root): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $files = [];

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile()) {
                $files[] = $file;
            }
        }

        usort($files, fn (SplFileInfo $a, SplFileInfo $b): int => strcmp($a->getPathname(), $b->getPathname()));

        return $files;
    }

    private function relativePath(string $root, string $filePath): string
    {
        $relativePath = substr($filePath, strlen(rtrim($root, DIRECTORY_SEPARATOR)) + 1);

        return str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
    }
}
