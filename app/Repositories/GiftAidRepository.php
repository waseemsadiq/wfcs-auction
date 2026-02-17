<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Database;

class GiftAidRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all payments with gift_aid_claimed=1 for reporting.
     * Returns full details joined with users and items.
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function claimed(int $limit = 100, int $offset = 0): array
    {
        return $this->db->query(
            'SELECT
                p.id            AS payment_id,
                p.amount        AS payment_amount,
                p.gift_aid_amount,
                p.created_at    AS payment_date,
                u.id            AS user_id,
                u.name          AS user_name,
                u.email         AS user_email,
                u.gift_aid_name,
                i.id            AS item_id,
                i.slug          AS item_slug,
                i.title         AS item_title,
                i.market_value
             FROM payments p
             JOIN users u ON u.id = p.user_id
             JOIN items i ON i.id = p.item_id
             WHERE p.gift_aid_claimed = 1
               AND p.status = \'completed\'
             ORDER BY p.created_at DESC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
            []
        );
    }

    /**
     * Count all completed, gift-aid-claimed payments (for pagination).
     *
     * @return int
     */
    public function countClaimed(): int
    {
        $row = $this->db->queryOne(
            "SELECT COUNT(*) AS cnt
             FROM payments
             WHERE gift_aid_claimed = 1
               AND status = 'completed'",
            []
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Get total gift aid amount for a given date range (completed payments only).
     *
     * @param string $from  Date string (Y-m-d or Y-m-d H:i:s)
     * @param string $to    Date string (Y-m-d or Y-m-d H:i:s)
     * @return float
     */
    public function totalByDateRange(string $from, string $to): float
    {
        $row = $this->db->queryOne(
            "SELECT COALESCE(SUM(gift_aid_amount), 0) AS total
             FROM payments
             WHERE gift_aid_claimed = 1
               AND status = 'completed'
               AND created_at >= ?
               AND created_at <= ?",
            [$from, $to]
        );
        return (float)($row['total'] ?? 0.0);
    }

    /**
     * Mark a payment as having gift aid claimed.
     *
     * @param int   $paymentId
     * @param float $giftAidAmount
     * @return void
     */
    public function markClaimed(int $paymentId, float $giftAidAmount): void
    {
        $this->db->execute(
            'UPDATE payments
             SET gift_aid_claimed = 1,
                 gift_aid_amount  = ?,
                 updated_at       = NOW()
             WHERE id = ?',
            [$giftAidAmount, $paymentId]
        );
    }

    /**
     * Get gift aid summary stats.
     *
     * @return array{total_claimed: float, total_payments_eligible: int, total_amount: float}
     */
    public function stats(): array
    {
        $row = $this->db->queryOne(
            "SELECT
                COALESCE(SUM(gift_aid_amount), 0)  AS total_claimed,
                COUNT(*)                            AS total_payments_eligible,
                COALESCE(SUM(amount), 0)            AS total_amount
             FROM payments
             WHERE gift_aid_claimed = 1
               AND status = 'completed'",
            []
        );

        return [
            'total_claimed'           => (float)($row['total_claimed'] ?? 0.0),
            'total_payments_eligible' => (int)($row['total_payments_eligible'] ?? 0),
            'total_amount'            => (float)($row['total_amount'] ?? 0.0),
        ];
    }
}
