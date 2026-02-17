<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;
use App\Services\NotificationService;

class PasswordResetService
{
    private PasswordResetRepository $repo;
    private UserRepository $users;

    /**
     * Accept optional dependencies for injection in tests.
     */
    public function __construct(
        ?PasswordResetRepository $repo = null,
        ?UserRepository $users = null
    ) {
        $this->repo  = $repo  ?? new PasswordResetRepository();
        $this->users = $users ?? new UserRepository();
    }

    /**
     * Initiate a password reset.
     *
     * - Finds user by email (silently returns null if not found — don't reveal existence)
     * - Generates 32-byte cryptographically random token
     * - Stores SHA-256 hash of the token in DB
     * - Returns the raw token (for emailing) or null if user not found
     *
     * Phase 12 will replace the error_log with real email sending.
     */
    public function initiate(string $email): ?string
    {
        $email = trim(strtolower($email));

        $user = $this->users->findByEmail($email);

        if ($user === null) {
            return null;
        }

        // Generate a cryptographically secure random token
        $rawToken  = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        $this->repo->create((int)$user['id'], $tokenHash);

        // Send password reset email (Phase 12)
        try {
            $baseUrl = rtrim(config('app.url') ?: 'http://localhost:8080', '/');
            (new NotificationService())->sendPasswordReset($user, $rawToken, $baseUrl);
        } catch (\Throwable $e) {
            error_log('Email failed (sendPasswordReset): ' . $e->getMessage());
        }

        return $rawToken;
    }

    /**
     * Validate a reset token.
     *
     * - Hashes the provided raw token with SHA-256
     * - Looks up a valid (unused, unexpired) record
     * - Returns the user array if valid, or null otherwise
     */
    public function validateToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        $tokenHash = hash('sha256', $token);
        $record    = $this->repo->findValid($tokenHash);

        if ($record === null) {
            return null;
        }

        return $this->users->findById((int)$record['user_id']);
    }

    /**
     * Complete the password reset.
     *
     * - Validates new password strength (min 8 chars)
     * - Updates the user's password_hash
     * - Marks the token as used
     *
     * @throws \InvalidArgumentException if token is invalid/expired or password is too weak
     * @return array The updated user row
     */
    public function reset(string $token, string $newPassword): array
    {
        if (strlen($newPassword) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters.');
        }

        $tokenHash = hash('sha256', $token);
        $record    = $this->repo->findValid($tokenHash);

        if ($record === null) {
            throw new \InvalidArgumentException('This reset link is invalid or has expired.');
        }

        $user = $this->users->findById((int)$record['user_id']);

        if ($user === null) {
            throw new \InvalidArgumentException('This reset link is invalid or has expired.');
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->users->updatePassword((int)$user['id'], $passwordHash);
        $this->repo->markUsed((int)$record['id']);

        // Return a fresh copy of the user
        return $this->users->findById((int)$user['id']) ?? $user;
    }
}
