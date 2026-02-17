<?php
declare(strict_types=1);

// Socket path: web requests have cwd = app subfolder; CLI has cwd = git root
$gitRoot = basename(getcwd()) === 'auction'
    ? dirname(getcwd())
    : getcwd();

return [
    'driver'   => 'mysql',
    'socket'   => $gitRoot . '/data/mysql.sock',
    'database' => $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'auction',
    'username' => $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
    ],
];
