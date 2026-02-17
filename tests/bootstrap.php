<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = __DIR__ . '/../.env.test';
if (file_exists($dotenv)) {
    foreach (file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '=') !== false && $line[0] !== '#') {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim(trim($v), '"\'');
        }
    }
}

// Helpers will be loaded here once they exist — for now stub the globals
$basePath = '';
$csrfToken = 'test_csrf_token';
