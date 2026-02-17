<?php
declare(strict_types=1);

return [
    'publishable_key' => $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? getenv('STRIPE_PUBLISHABLE_KEY') ?: '',
    'secret_key'      => $_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY') ?: '',
    'webhook_secret'  => $_ENV['STRIPE_WEBHOOK_SECRET'] ?? getenv('STRIPE_WEBHOOK_SECRET') ?: '',
];
