<?php

use Illuminate\Http\Request;

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

define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->useStoragePath($storagePath);

if (strpos($_SERVER['REQUEST_URI'], '/api') !== 0) {
    $_SERVER['REQUEST_URI'] = '/api' . $_SERVER['REQUEST_URI'];
}

$app->handleRequest(Request::capture());
