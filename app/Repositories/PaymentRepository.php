<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Database;

class PaymentRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a payment record. Returns the new auto-increment id.
     *
     * Expected keys: user_id, item_id, bid_id, winner_id, amount, status,
     *   gift_aid_claimed (0/1), gift_aid_amount
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO payments
                (user_id, item_id, bid_id, winner_id, amount, status,
                 gift_aid_claimed, gift_aid_amount,
                 stripe_session_id, stripe_payment_intent,
                 created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $data['user_id'],
                $data['item_id'],
                $data['bid_id']          ?? null,
                $data['winner_id']       ?? null,
                $data['amount'],
                $data['status']          ?? 'pending',
                $data['gift_aid_claimed'] ?? 0,
                $data['gift_aid_amount'] ?? null,
                $data['stripe_session_id']      ?? null,
                $data['stripe_payment_intent']  ?? null,
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * Find a payment by its id.
     */
    public function find(int $id): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM payments WHERE id = ?',
            [$id]
        );
    }

    /**
     * Find payment by item_id and user_id (winner lookup).
     */
    public function findByItemAndUser(int $itemId, int $userId): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM payments WHERE item_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1',
            [$itemId, $userId]
        );
    }

    /**
     * Find payment by item_id.
     */
    public function findByItem(int $itemId): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM payments WHERE item_id = ? ORDER BY created_at DESC LIMIT 1',
            [$itemId]
        );
    }

    /**
     * Find payment by Stripe checkout session ID.
     */
    public function findByStripeSession(string $sessionId): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM payments WHERE stripe_session_id = ?',
            [$sessionId]
        );
    }

    /**
     * Find payment by Stripe payment intent ID.
     */
    public function findByPaymentIntent(string $intentId): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM payments WHERE stripe_payment_intent = ?',
            [$intentId]
        );
    }

    /**
     * Update payment status.
     */
    public function updateStatus(int $id, string $status): void
    {
        $this->db->execute(
            'UPDATE payments SET status = ?, updated_at = NOW() WHERE id = ?',
            [$status, $id]
        );
    }

    /**
     * Update Stripe session and optional payment intent IDs.
     */
    public function updateStripeIds(int $id, string $sessionId, ?string $intentId = null): void
    {
        if ($intentId !== null) {
            $this->db->execute(
                'UPDATE payments
                 SET stripe_session_id = ?, stripe_payment_intent = ?, updated_at = NOW()
                 WHERE id = ?',
                [$sessionId, $intentId, $id]
            );
        } else {
            $this->db->execute(
                'UPDATE payments
                 SET stripe_session_id = ?, updated_at = NOW()
                 WHERE id = ?',
                [$sessionId, $id]
            );
        }
    }

    /**
     * Get all payments for admin with optional filters.
     * Joins: users (name, email), items (title, slug), events (title).
     * Filters: status, user_id, item_id, event_id
     */
    public function all(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        [$where, $params] = $this->buildWhere($filters);

        return $this->db->query(
            'SELECT p.*,
                    u.name AS user_name, u.email AS user_email,
                    i.title AS item_title, i.slug AS item_slug,
                    e.title AS event_title
             FROM payments p
             JOIN users u  ON u.id = p.user_id
             JOIN items i  ON i.id = p.item_id
             JOIN events e ON e.id = i.event_id'
            . $where .
            ' ORDER BY p.created_at DESC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
            $params
        );
    }

    /**
     * Count all payments matching optional filters.
     */
    public function countAll(array $filters = []): int
    {
        [$where, $params] = $this->buildWhere($filters);

        $row = $this->db->queryOne(
            'SELECT COUNT(*) AS cnt
             FROM payments p
             JOIN users u  ON u.id = p.user_id
             JOIN items i  ON i.id = p.item_id
             JOIN events e ON e.id = i.event_id'
            . $where,
            $params
        );

        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Get all payments for a specific user (with item + event joins).
     */
    public function byUser(int $userId): array
    {
        return $this->db->query(
            'SELECT p.*,
                    i.title AS item_title, i.slug AS item_slug,
                    e.title AS event_title
             FROM payments p
             JOIN items i  ON i.id = p.item_id
             JOIN events e ON e.id = i.event_id
             WHERE p.user_id = ?
             ORDER BY p.created_at DESC',
            [$userId]
        );
    }

    /**
     * Total revenue from completed payments.
     */
    public function totalRevenue(): float
    {
        $row = $this->db->queryOne(
            "SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE status = 'completed'"
        );
        return (float)($row['total'] ?? 0.0);
    }

    /**
     * Count payments grouped by status.
     * Returns e.g. ['pending' => 3, 'completed' => 12, 'failed' => 1, 'refunded' => 0]
     *
     * @param string[] $statuses
     * @return array<string,int>
     */
    public function statsByStatus(array $statuses): array
    {
        $result = [];
        foreach ($statuses as $s) {
            $row = $this->db->queryOne('SELECT COUNT(*) AS cnt FROM payments WHERE status = ?', [$s]);
            $result[$s] = (int)($row['cnt'] ?? 0);
        }
        return $result;
    }


    /**
     * Delete payment records for a set of items (e.g. donated items being deleted).
     */
    public function deleteByItems(array $itemIds): void
    {
        if (empty($itemIds)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $this->db->execute(
            'DELETE FROM payments WHERE item_id IN (' . $placeholders . ')',
            $itemIds
        );
    }

    /**
     * Delete all payment records for a user (e.g. items they won).
     */
    public function deleteByUser(int $userId): void
    {
        $this->db->execute('DELETE FROM payments WHERE user_id = ?', [$userId]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function buildWhere(array $filters): array
    {
        $conditions = [];
        $params     = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'p.status = ?';
            $params[]     = $filters['status'];
        }

        if (!empty($filters['user_id'])) {
            $conditions[] = 'p.user_id = ?';
            $params[]     = (int)$filters['user_id'];
        }

        if (!empty($filters['item_id'])) {
            $conditions[] = 'p.item_id = ?';
            $params[]     = (int)$filters['item_id'];
        }

        if (!empty($filters['event_id'])) {
            $conditions[] = 'i.event_id = ?';
            $params[]     = (int)$filters['event_id'];
        }

        $where = empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions);

        return [$where, $params];
    }
}
