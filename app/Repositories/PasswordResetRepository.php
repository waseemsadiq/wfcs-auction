<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Database;

class PasswordResetRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a reset token record.
     * Stores SHA-256 hash of the raw token (never the raw token itself).
     * Expires in 1 hour.
     * Deletes any existing unused tokens for this user first.
     */
    public function create(int $userId, string $tokenHash, int $expirySeconds = 3600): void
    {
        // Delete any existing tokens for this user first
        $this->deleteForUser($userId);

        $expiresAt = date('Y-m-d H:i:s', time() + $expirySeconds);

        $this->db->execute(
            'INSERT INTO password_reset_tokens
                (user_id, token_hash, expires_at, used_at, created_at)
             VALUES (?, ?, ?, NULL, NOW())',
            [$userId, $tokenHash, $expiresAt]
        );
    }

    /**
     * Find a valid (unused, not expired) reset record by token hash.
     * Returns the full row array or null if not found / invalid.
     */
    public function findValid(string $tokenHash): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM password_reset_tokens
             WHERE token_hash = ?
               AND used_at IS NULL
               AND expires_at > NOW()',
            [$tokenHash]
        );
    }

    /**
     * Mark a token record as used by setting used_at to now.
     */
    public function markUsed(int $id): void
    {
        $this->db->execute(
            'UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?',
            [$id]
        );
    }

    /**
     * Delete all tokens for a user (cleanup / on new request).
     */
    public function deleteForUser(int $userId): void
    {
        $this->db->execute(
            'DELETE FROM password_reset_tokens WHERE user_id = ?',
            [$userId]
        );
    }
}
