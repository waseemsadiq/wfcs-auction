<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\RateLimitRepository;

class RateLimitService
{
    private RateLimitRepository $repo;

    private const LIMITS = [
        'login'               => ['max' => 5,  'window' => 900,  'block' => 1800],
        'register'            => ['max' => 3,  'window' => 3600, 'block' => 3600],
        'bid'                 => ['max' => 30, 'window' => 60,   'block' => 300],
        'api_token'           => ['max' => 10, 'window' => 3600, 'block' => 3600],
        'password_reset'      => ['max' => 3,  'window' => 3600, 'block' => 3600],
        'resend_verification' => ['max' => 3,  'window' => 3600, 'block' => 3600],
    ];

    public function __construct(?RateLimitRepository $repo = null)
    {
        $this->repo = $repo ?? new RateLimitRepository();
    }

    /**
     * Check if an action is rate limited for the given identifier.
     *
     * Returns true if the request is allowed.
     * Throws \RuntimeException with a human-readable message if blocked.
     *
     * Logic:
     *   1. Find existing record.
     *   2. If blocked_until is set and in the future → throw.
     *   3. If no record exists → upsert (first attempt), return true.
     *   4. If the window has expired → upsert to reset, return true.
     *   5. If attempts >= max → block and throw.
     *   6. Otherwise → increment and return true.
     */
    public function check(string $identifier, string $action): bool
    {
        if (!isset(self::LIMITS[$action])) {
            // Unknown action — allow by default
            return true;
        }

        $config = self::LIMITS[$action];
        $now    = time();

        $record = $this->repo->find($identifier, $action);

        // Step 2: Already blocked?
        if ($record !== null && $record['blocked_until'] !== null) {
            $blockedUntil = strtotime($record['blocked_until']);
            if ($blockedUntil !== false && $blockedUntil > $now) {
                $remaining = $blockedUntil - $now;
                $minutes   = (int)ceil($remaining / 60);
                throw new \RuntimeException(
                    'Too many attempts. Please try again in ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's') . '.'
                );
            }
        }

        // Step 3: No record — first attempt
        if ($record === null) {
            $windowStart = date('Y-m-d H:i:s', $now);
            $this->repo->upsert($identifier, $action, $windowStart);
            return true;
        }

        // Step 4: Window expired — reset
        $windowStart = strtotime($record['window_start']);
        if ($windowStart === false || ($now - $windowStart) >= $config['window']) {
            $newWindowStart = date('Y-m-d H:i:s', $now);
            $this->repo->upsert($identifier, $action, $newWindowStart);
            return true;
        }

        // Step 5: Attempts have hit the max — block
        if ((int)$record['attempts'] >= $config['max']) {
            $blockedUntil = date('Y-m-d H:i:s', $now + $config['block']);
            $this->repo->block((int)$record['id'], $blockedUntil);

            $minutes = (int)ceil($config['block'] / 60);
            throw new \RuntimeException(
                'Too many attempts. Please try again in ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's') . '.'
            );
        }

        // Step 6: Within window and below max — increment
        $this->repo->increment((int)$record['id']);
        return true;
    }

    /**
     * Clear rate limit record for an identifier + action (e.g. on successful login).
     */
    public function clear(string $identifier, string $action): void
    {
        $this->repo->delete($identifier, $action);
    }
}
