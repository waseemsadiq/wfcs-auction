<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Services\NotificationService;
use Core\JWT;

class AuthService
{
    private UserRepository $users;

    /**
     * Accept an optional UserRepository for dependency injection in tests.
     */
    public function __construct(?UserRepository $users = null)
    {
        $this->users = $users ?? new UserRepository();
    }

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    /**
     * Register a new user.
     *
     * @param string $name
     * @param string $email
     * @param string $password     Plain-text password
     * @param string $role         Default 'bidder'
     * @return array{user: array, verificationToken: string}
     * @throws \RuntimeException on validation failure or duplicate email
     */
    public function register(
        string $name,
        string $email,
        string $password,
        string $role = 'bidder'
    ): array {
        // -- Validation -------------------------------------------------------
        $name  = trim($name);
        $email = trim(strtolower($email));

        if ($name === '') {
            throw new \RuntimeException('Name is required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Invalid email address.');
        }

        if (strlen($password) < 8) {
            throw new \RuntimeException('Password must be at least 8 characters.');
        }

        // -- Duplicate check --------------------------------------------------
        if ($this->users->findByEmail($email) !== null) {
            throw new \RuntimeException('An account with that email already exists.');
        }

        // -- Hash password ----------------------------------------------------
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // -- Generate slug ----------------------------------------------------
        $slug = $this->generateSlug($name);

        // -- Verification token -----------------------------------------------
        $verificationToken   = bin2hex(random_bytes(32));
        $verificationExpires = date('Y-m-d H:i:s', time() + 86400); // 24 h

        // -- Persist ----------------------------------------------------------
        $userId = $this->users->create([
            'slug'                          => $slug,
            'name'                          => $name,
            'email'                         => $email,
            'password_hash'                 => $passwordHash,
            'role'                          => $role,
            'email_verification_token'      => $verificationToken,
            'email_verification_expires_at' => $verificationExpires,
        ]);

        // Send verification email (Phase 12)
        try {
            $baseUrl = rtrim(config('app.url') ?: 'http://localhost:8080', '/');
            (new NotificationService())->sendVerification($user, $verificationToken, $baseUrl);
        } catch (\Throwable $e) {
            error_log('Email failed (sendVerification): ' . $e->getMessage());
        }

        $user = $this->users->findById($userId) ?? [
            'id'                => $userId,
            'slug'              => $slug,
            'name'              => $name,
            'email'             => $email,
            'role'              => $role,
            'email_verified_at' => null,
        ];

        return [
            'user'              => $user,
            'verificationToken' => $verificationToken,
        ];
    }

    // -------------------------------------------------------------------------
    // Login
    // -------------------------------------------------------------------------

    /**
     * Attempt login.
     *
     * @return array JWT payload: id, email, name, role, slug, verified
     * @throws \RuntimeException on wrong credentials
     *
     * TODO Phase 4: add DB-based rate limiting here
     */
    public function login(string $email, string $password): array
    {
        $email = trim(strtolower($email));

        $user = $this->users->findByEmail($email);

        if ($user === null) {
            throw new \RuntimeException('Invalid email or password.');
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new \RuntimeException('Invalid email or password.');
        }

        return $this->buildPayload($user);
    }

    // -------------------------------------------------------------------------
    // Email verification
    // -------------------------------------------------------------------------

    /**
     * Verify an email address by token.
     * Returns the user array on success, null if the token is invalid/expired.
     */
    public function verifyEmail(string $token): ?array
    {
        $user = $this->users->findByVerificationToken($token);

        if ($user === null) {
            return null;
        }

        // Check expiry
        if (!empty($user['email_verification_expires_at'])) {
            $expires = strtotime((string)$user['email_verification_expires_at']);
            if ($expires !== false && $expires < time()) {
                return null;
            }
        }

        // Mark verified
        $this->users->updateVerification((int)$user['id'], true);

        // Return a fresh copy
        return $this->users->findById((int)$user['id']);
    }

    // -------------------------------------------------------------------------
    // JWT
    // -------------------------------------------------------------------------

    /**
     * Generate a signed JWT for the given user row.
     * Expiry: 30 days.
     */
    public function generateToken(array $user): string
    {
        $secret  = config('app.jwt_secret');
        $payload = $this->buildPayload($user);
        $payload['exp'] = time() + (30 * 24 * 3600);
        return JWT::encode($payload, $secret);
    }

    // -------------------------------------------------------------------------
    // Resend verification
    // -------------------------------------------------------------------------

    /**
     * Regenerate and store a new verification token for the user.
     * Returns the new raw token string.
     *
     * TODO Phase 12: send the actual email here
     */
    public function resendVerification(int $userId): string
    {
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 86400);

        $this->users->setVerificationToken($userId, $token, $expires);

        // Send verification email (Phase 12)
        try {
            $freshUser = $this->users->findById($userId);
            if ($freshUser !== null) {
                $baseUrl = rtrim(config('app.url') ?: 'http://localhost:8080', '/');
                (new NotificationService())->sendVerification($freshUser, $token, $baseUrl);
            }
        } catch (\Throwable $e) {
            error_log('Email failed (resendVerification): ' . $e->getMessage());
        }

        return $token;
    }

    // -------------------------------------------------------------------------
    // Admin: change a user's email address
    // -------------------------------------------------------------------------

    /**
     * Change a user's email address on their behalf (admin action).
     *
     * Validates format, checks for duplicates, updates the email, resets
     * email verification, and sends a new verification email to the new address.
     *
     * @throws \RuntimeException on validation failure or duplicate email
     */
    public function changeUserEmail(int $userId, string $newEmail, string $currentEmail): void
    {
        $newEmail = trim(strtolower($newEmail));

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Invalid email address.');
        }

        if ($newEmail === trim(strtolower($currentEmail))) {
            throw new \RuntimeException('That is already their email address.');
        }

        $existing = $this->users->findByEmail($newEmail);
        if ($existing !== null) {
            throw new \RuntimeException('That email address is already in use by another account.');
        }

        $token = $this->users->updateEmail($userId, $newEmail);

        try {
            $updatedUser = $this->users->findById($userId);
            if ($updatedUser !== null) {
                $baseUrl = rtrim(config('app.url') ?: 'http://localhost:8080', '/');
                (new NotificationService())->sendVerification($updatedUser, $token, $baseUrl);
            }
        } catch (\Throwable $e) {
            error_log('Email failed (changeUserEmail): ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Slug generation
    // -------------------------------------------------------------------------

    /**
     * Generate a unique URL slug from a display name.
     * Appends -2, -3, … until the slug is free.
     * Public so it can be tested in isolation.
     */
    public function generateSlug(string $name): string
    {
        $base = $this->slugify($name);
        $slug = $base;
        $i    = 2;

        while ($this->users->findBySlug($slug) !== null) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function buildPayload(array $user): array
    {
        return [
            'id'       => (int)($user['id'] ?? 0),
            'email'    => $user['email'] ?? '',
            'name'     => $user['name'] ?? '',
            'role'     => $user['role'] ?? 'bidder',
            'slug'     => $user['slug'] ?? '',
            'verified' => !empty($user['email_verified_at']),
        ];
    }

    /**
     * Convert a string to a URL-safe slug (no dependency on global helpers).
     */
    private function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text) ?? '';
        $text = preg_replace('/[\s-]+/', '-', trim($text)) ?? '';
        return $text;
    }
}
