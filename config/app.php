<?php
declare(strict_types=1);

return [
    'name'       => 'WFCS Auction',
    'env'        => $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production',
    'debug'      => ($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: '0') === '1',
    'url'        => $_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'http://localhost:8080',
    'key'        => $_ENV['APP_KEY'] ?? getenv('APP_KEY') ?: '',
    'jwt_secret' => $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: '',
    'upload_dir' => __DIR__ . '/../uploads/',
    'upload_url' => '/uploads/',
    'timezone'   => 'Europe/London',
];
