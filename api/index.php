<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "Step 1: Start\n";
flush();

require __DIR__ . '/../vendor/autoload.php';
echo "Step 2: Autoloader OK\n";
flush();

$app = require __DIR__ . '/../bootstrap/app.php';
echo "Step 3: App bootstrapped\n";
flush();
