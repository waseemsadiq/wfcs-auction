<?php
declare(strict_types=1);

// Determine git root (works both from CLI and web request)
// Web request: Galvani sets cwd to the app subfolder (auction/)
// CLI: cwd is already the git root
$gitRoot = basename(getcwd()) === 'auction'
    ? dirname(getcwd())
    : getcwd();

$socketPath = $gitRoot . '/data/mysql.sock';

$dotenv = parse_ini_file(__DIR__ . '/.env') ?: [];
$dbName = $dotenv['DB_DATABASE'] ?? 'auction';
$dbUser = $dotenv['DB_USERNAME'] ?? 'root';
$dbPass = $dotenv['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO(
        "mysql:unix_socket={$socketPath};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => true,
        ]
    );
    $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');

    $pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
    $pdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");

    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
        $pdo->exec($stmt);
    }

    $seeds = file_get_contents(__DIR__ . '/database/seeds.sql');
    foreach (array_filter(array_map('trim', explode(';', $seeds))) as $stmt) {
        $pdo->exec($stmt);
    }

    echo "Database '{$dbName}' reset successfully.\n";
} catch (PDOException $e) {
    echo "DB init failed: " . $e->getMessage() . "\n";
    exit(1);
}
