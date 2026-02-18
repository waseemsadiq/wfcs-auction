<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Database;

class BidRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all bids for an item, newest first (with user name join).
     * Bidder name is masked to "First L." for privacy.
     */
    public function byItem(int $itemId, int $limit = 20): array
    {
        $rows = $this->db->query(
            'SELECT bids.*, u.name AS bidder_name, u.slug AS bidder_slug
             FROM bids
             JOIN users u ON u.id = bids.user_id
             WHERE bids.item_id = ?
             ORDER BY bids.created_at DESC
             LIMIT ' . (int)$limit,
            [$itemId]
        );

        // Mask bidder name and compute time_ago
        foreach ($rows as &$row) {
            $row['bidder_name'] = $this->maskName((string)($row['bidder_name'] ?? ''));
            $row['time_ago']    = $this->timeAgo((string)($row['created_at'] ?? ''));
        }
        unset($row);

        return $rows;
    }

    /**
     * Get all bids by a user (with item + event joins).
     */
    public function byUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->db->query(
            'SELECT bids.*, i.title AS item_title, i.slug AS item_slug,
                    i.current_bid, i.status AS item_status, i.image AS item_image,
                    i.winner_id, e.title AS event_title
             FROM bids
             JOIN items i ON i.id = bids.item_id
             JOIN events e ON e.id = i.event_id
             WHERE bids.user_id = ?
             ORDER BY bids.created_at DESC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
            [$userId]
        );
    }

    /**
     * Count total bids by a user.
     */
    public function countByUser(int $userId): int
    {
        $row = $this->db->queryOne(
            'SELECT COUNT(*) AS cnt FROM bids WHERE user_id = ?',
            [$userId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Get the highest bid on an item.
     */
    public function highestBid(int $itemId): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM bids WHERE item_id = ? ORDER BY amount DESC, created_at ASC LIMIT 1',
            [$itemId]
        );
    }

    /**
     * Get a user's highest bid on a specific item.
     */
    public function userHighestBid(int $itemId, int $userId): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM bids WHERE item_id = ? AND user_id = ? ORDER BY amount DESC LIMIT 1',
            [$itemId, $userId]
        );
    }

    /**
     * Create a new bid. Returns the new auto-increment id.
     * NOTE: is_buy_now stored as 0/1 integer — no PHP booleans in SQL.
     *
     * @deprecated Use insert() — kept for compatibility.
     */
    public function create(int $itemId, int $userId, float $amount, bool $isBuyNow = false): int
    {
        return $this->insert($itemId, $userId, $amount, $isBuyNow);
    }

    /**
     * Insert a new bid record. Returns the new auto-increment id.
     * NOTE: is_buy_now stored as 0/1 integer — no PHP booleans in SQL.
     */
    public function insert(int $itemId, int $userId, float $amount, bool $isBuyNow = false): int
    {
        $isBuyNowInt = $isBuyNow ? 1 : 0;
        $this->db->execute(
            'INSERT INTO bids (item_id, user_id, amount, is_buy_now, created_at)
             VALUES (?, ?, ?, ?, NOW())',
            [$itemId, $userId, $amount, $isBuyNowInt]
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * Get the winning (highest) bid for a given item.
     * Returns null if no bids exist.
     * Alias for highestBid() with a semantically clear name for auction end logic.
     */
    public function findWinningBid(int $itemId): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM bids WHERE item_id = ? ORDER BY amount DESC, created_at ASC LIMIT 1',
            [$itemId]
        );
    }

    /**
     * Count total bids on a given item.
     */
    public function countByItem(int $itemId): int
    {
        $row = $this->db->queryOne(
            'SELECT COUNT(*) AS cnt FROM bids WHERE item_id = ?',
            [$itemId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Count bids by a user within the given time window (seconds).
     * Used for rate limiting checks.
     */
    public function countRecentByUser(int $userId, int $seconds = 60): int
    {
        $row = $this->db->queryOne(
            'SELECT COUNT(*) AS cnt
             FROM bids
             WHERE user_id = ?
               AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$userId, $seconds]
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Count all bids placed today (calendar day in server timezone).
     */
    public function countToday(): int
    {
        $row = $this->db->queryOne(
            'SELECT COUNT(*) AS cnt FROM bids WHERE DATE(created_at) = CURDATE()'
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Fetch the most recent bids with user, item and category joined.
     * Used on the admin dashboard.
     */
    public function recentWithDetails(int $limit = 10): array
    {
        return $this->db->query(
            'SELECT b.id, b.amount, b.created_at,
                    u.name AS bidder_name,
                    i.title AS item_title,
                    c.name  AS category_name
             FROM bids b
             JOIN users u  ON u.id = b.user_id
             JOIN items i  ON i.id = b.item_id
             LEFT JOIN categories c ON c.id = i.category_id
             ORDER BY b.created_at DESC
             LIMIT ' . (int)$limit
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Mask a full name to "First L." for bidder privacy.
     * "John Smith" → "John S."
     * "John"       → "John"
     */
    private function maskName(string $name): string
    {
        $name  = trim($name);
        $parts = preg_split('/\s+/', $name);

        if (empty($parts) || $parts === [false]) {
            return $name;
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        $first = $parts[0];
        $last  = $parts[count($parts) - 1];

        return $first . ' ' . mb_strtoupper(mb_substr($last, 0, 1)) . '.';
    }

    private function timeAgo(string $datetime): string
    {
        if ($datetime === '') {
            return '';
        }

        $diff = time() - strtotime($datetime);

        if ($diff < 60)  return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        return floor($diff / 86400) . 'd ago';
    }
}
