<?php

// Vercel serverless: only /tmp is writable
$storagePath = '/tmp/laravel-storage';

foreach ([
    'app/public',
    'framework/cache/data',
    'framework/sessions',
    'framework/views',
    'logs',
] as $dir) {
    $path = $storagePath . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

$app = require __DIR__ . '/../bootstrap/app.php';
$app->useStoragePath($storagePath);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
)->send();

$kernel->terminate($request, $response);
