<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Database;

class RateLimitRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find current rate limit record for identifier + action.
     */
    public function find(string $identifier, string $action): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM rate_limits WHERE identifier = ? AND action = ?',
            [$identifier, $action]
        );
    }

    /**
     * Upsert a rate limit record — create on first attempt or reset for a new window.
     * Sets attempts = 1, window_start = provided value, blocked_until = NULL.
     */
    public function upsert(string $identifier, string $action, string $windowStart): void
    {
        $this->db->execute(
            'INSERT INTO rate_limits (identifier, action, attempts, window_start, blocked_until)
             VALUES (?, ?, 1, ?, NULL)
             ON DUPLICATE KEY UPDATE
                 attempts      = 1,
                 window_start  = VALUES(window_start),
                 blocked_until = NULL',
            [$identifier, $action, $windowStart]
        );
    }

    /**
     * Increment the attempts counter for a record by ID.
     */
    public function increment(int $id): void
    {
        $this->db->execute(
            'UPDATE rate_limits SET attempts = attempts + 1 WHERE id = ?',
            [$id]
        );
    }

    /**
     * Set blocked_until on a record by ID.
     */
    public function block(int $id, string $blockedUntil): void
    {
        $this->db->execute(
            'UPDATE rate_limits SET blocked_until = ? WHERE id = ?',
            [$blockedUntil, $id]
        );
    }

    /**
     * Delete the rate limit record for an identifier + action (e.g. after successful login).
     */
    public function delete(string $identifier, string $action): void
    {
        $this->db->execute(
            'DELETE FROM rate_limits WHERE identifier = ? AND action = ?',
            [$identifier, $action]
        );
    }

    /**
     * Delete expired records (cleanup — call occasionally).
     * Removes records where window_start is older than 2 hours AND
     * blocked_until is either NULL or in the past.
     */
    public function cleanup(): void
    {
        $cutoff = date('Y-m-d H:i:s', time() - 7200);
        $now    = date('Y-m-d H:i:s');

        $this->db->execute(
            'DELETE FROM rate_limits
             WHERE window_start < ?
               AND (blocked_until IS NULL OR blocked_until < ?)',
            [$cutoff, $now]
        );
    }
}
