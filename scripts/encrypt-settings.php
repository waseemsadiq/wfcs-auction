<?php
/**
 * One-shot script: encrypt any plaintext sensitive settings already in the DB.
 * Run once via: ./galvani auction/scripts/encrypt-settings.php
 * Safe to re-run — already-encrypted values (enc: prefix) are skipped.
 *
 * Uses raw PDO (CLI rule: no framework classes, __DIR__-relative socket path).
 */
declare(strict_types=1);

// Load .env
$dotenvPath = dirname(__DIR__) . '/.env';
if (file_exists($dotenvPath)) {
    $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
        putenv(trim($k) . '=' . trim($v));
    }
}

// Load config() helper (needed by encryptSetting / decryptSetting)
require_once dirname(__DIR__) . '/app/Helpers/functions.php';

// Raw PDO — __DIR__ relative socket path (CLI rule from CLAUDE.md)
$socketPath = dirname(__DIR__, 2) . '/data/mysql.sock';
$dbName     = $_ENV['DB_DATABASE'] ?? 'auction';
$dsn        = "mysql:unix_socket={$socketPath};dbname={$dbName};charset=utf8mb4";
$pdo = new PDO($dsn, 'root', '', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => true,
]);
$pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');

$sensitiveKeys = ['stripe_publishable_key', 'stripe_secret_key', 'stripe_webhook_url_token', 'smtp_password'];

foreach ($sensitiveKeys as $key) {
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE key_name = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();

    if ($row === false) {
        echo "{$key}: not found in DB\n";
        continue;
    }

    $value = (string)$row['value'];

    if ($value === '' || str_starts_with($value, 'enc:')) {
        echo "{$key}: skipped (empty or already encrypted)\n";
        continue;
    }

    $encrypted = encryptSetting($value);
    $upd = $pdo->prepare('UPDATE settings SET value = ?, updated_at = NOW() WHERE key_name = ?');
    $upd->execute([$encrypted, $key]);
    echo "{$key}: encrypted\n";
}

echo "Done.\n";
