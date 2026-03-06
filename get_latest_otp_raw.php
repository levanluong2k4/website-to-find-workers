<?php

try {
    $env = parse_ini_file(__DIR__ . '/.env');
    $pdo = new PDO("mysql:host=" . $env['DB_HOST'] . ";dbname=" . $env['DB_DATABASE'], $env['DB_USERNAME'], $env['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT * FROM otp_codes ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo "OTP_FOUND: " . $row['code'];
    } else {
        echo "NO_OTP_FOUND";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
