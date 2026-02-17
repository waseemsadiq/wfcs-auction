<?php
declare(strict_types=1);

namespace App\Services;

use Core\JWT;

class ApiTokenService
{
    /**
     * Generate a long-lived API token (1 year).
     * Same JWT format as the web session token — just a much longer expiry.
     * The token is intended to be passed as ?token= or in the POST body
     * (NOT via an Authorization header — Galvani drops custom headers).
     *
     * @param array $user Full user row from the database.
     * @return string Signed JWT string.
     */
    public function generate(array $user): string
    {
        return JWT::encode([
            'id'       => (int)$user['id'],
            'email'    => (string)$user['email'],
            'name'     => (string)$user['name'],
            'role'     => (string)$user['role'],
            'slug'     => (string)$user['slug'],
            'verified' => !empty($user['email_verified_at']),
            'exp'      => time() + (365 * 24 * 3600),
        ], config('app.jwt_secret'));
    }
}
