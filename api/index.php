<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '512M');

use Illuminate\Http\Request;

$storagePath = '/tmp/laravel-storage';
foreach (['app/public','framework/cache/data','framework/sessions','framework/views','logs'] as $dir) {
    $path = $storagePath . '/' . $dir;
    if (!is_dir($path)) mkdir($path, 0755, true);
}

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->useStoragePath($storagePath);

try {
    $app->handleRequest(Request::capture());
} catch (\Throwable $e) {
    http_response_code(500);
    echo "<pre>";
    echo get_class($e) . ": " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}
