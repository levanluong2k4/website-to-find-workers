<?php
$host = '127.0.0.1';
$db   = 'find_workers';
$user = 'root';
$pass = ''; // MySQL root pass is usually empty on Laragon
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $sql = "CREATE TABLE IF NOT EXISTS `notifications` (
      `id` char(36) NOT NULL,
      `type` varchar(255) NOT NULL,
      `notifiable_type` varchar(255) NOT NULL,
      `notifiable_id` bigint(20) unsigned NOT NULL,
      `data` text NOT NULL,
      `read_at` timestamp NULL DEFAULT NULL,
      `created_at` timestamp NULL DEFAULT NULL,
      `updated_at` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "notifications table created successfully\n";
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
