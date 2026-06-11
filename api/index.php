<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

use Illuminate\Http\Request;

$storagePath = '/tmp/laravel-storage';
foreach (['app/public','framework/cache/data','framework/sessions','framework/views','logs'] as $dir) {
    $path = $storagePath . '/' . $dir;
    if (!is_dir($path)) mkdir($path, 0755, true);
}

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';

echo "Step 1: Autoloader OK\n"; flush();

$app = require __DIR__ . '/../bootstrap/app.php';
echo "Step 2: App OK\n"; flush();

$app->useStoragePath($storagePath);
echo "Step 3: StoragePath OK\n"; flush();

$request = Request::capture();
echo "Step 4: Request captured OK\n"; flush();

$app->handleRequest($request);
echo "Step 5: handleRequest OK\n"; flush();
