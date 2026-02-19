<?php
declare(strict_types=1);

/**
 * Application Configuration - SHARED HOSTING VERSION
 *
 * Replace placeholder values with your actual settings.
 * This file does NOT use .env — values are hardcoded here.
 *
 * Generate APP_KEY:  php -r "echo bin2hex(random_bytes(32));"
 */

return [
    'name'       => 'WFCS Auction',
    'env'        => 'production',
    'debug'      => false,
    'url'        => 'https://YOUR_DOMAIN_HERE/auction',
    'key'        => 'YOUR_APP_KEY_HERE',        // 64-char hex
    'jwt_secret' => 'YOUR_JWT_SECRET_HERE',     // 64-char hex — different from APP_KEY
    'upload_dir' => __DIR__ . '/../uploads/',
    'upload_url' => '/auction/uploads/',
    'timezone'   => 'Europe/London',
];
