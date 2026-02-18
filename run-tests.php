<?php declare(strict_types=1);

// Shebang-free PHPUnit entry point for Galvani (which doesn't strip shebangs).
// Usage: ./galvani auction/run-tests.php [phpunit-args]

if (!ini_get('date.timezone')) {
    ini_set('date.timezone', 'UTC');
}

define('PHPUNIT_COMPOSER_INSTALL', __DIR__ . '/vendor/autoload.php');

require PHPUNIT_COMPOSER_INSTALL;

exit((new PHPUnit\TextUI\Application)->run($_SERVER['argv']));
