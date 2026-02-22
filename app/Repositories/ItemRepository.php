<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Database;

class ItemRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Active items for public browsing with optional filters.
     * Filters: event_id, category_id, search (LIKE on title), status
     * Includes: item.*, c.name as category_name
     */
    public function browse(array $filters = [], int $limit = 24, int $offset = 0): array
    {
        [$where, $params] = $this->buildBrowseWhere($filters);

        return $this->db->query(
            'SELECT i.*, c.name AS category_name
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id'
            . $where .
            ' ORDER BY i.lot_number ASC, i.created_at DESC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
            $params
        );
    }

    /**
     * Count items matching the browse filters.
     */
    public function countBrowse(array $filters = []): int
    {
        [$where, $params] = $this->buildBrowseWhere($filters);

        $row = $this->db->queryOne(
            'SELECT COUNT(*) AS cnt
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id'
            . $where,
            $params
        );

        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Items in an event for public display (active/ended/sold), ordered by lot_number.
     * Optional filters: category_slug (string), search (LIKE on title)
     */
    public function byEvent(int $eventId, int $limit = 50, int $offset = 0, array $filters = []): array
    {
        $conditions = ["i.event_id = ?", "i.status IN ('active', 'ended', 'sold')"];
        $params     = [$eventId];

        if (!empty($filters['category_slug'])) {
            $conditions[] = 'c.slug = ?';
            $params[]     = $filters['category_slug'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = 'i.title LIKE ?';
            $params[]     = '%' . $filters['search'] . '%';
        }

        $where = ' WHERE ' . implode(' AND ', $conditions);

        return $this->db->query(
            'SELECT i.*, c.name AS category_name, c.slug AS category_slug
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id'
            . $where .
            ' ORDER BY i.lot_number ASC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
            $params
        );
    }

    /**
     * All items for admin with optional filters.
     * Filters: event_id, category_id, status, donor_id
     */
    public function all(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        [$where, $params] = $this->buildAdminWhere($filters);

        return $this->db->query(
            'SELECT i.*, c.name AS category_name, e.title AS event_title
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             LEFT JOIN events e ON e.id = i.event_id'
            . $where .
            ' ORDER BY i.created_at DESC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
            $params
        );
    }

    /**
     * Browse items for the public REST API.
     * Always restricts to status IN ('active', 'ended', 'sold').
     * Filters: event_id, category_id, search
     */
    public function browseApi(array $filters = [], int $limit = 24, int $offset = 0): array
    {
        [$where, $params] = $this->buildApiWhere($filters);

        return $this->db->query(
            'SELECT i.*, c.name AS category_name, e.title AS event_title, e.slug AS event_slug
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             LEFT JOIN events e     ON e.id = i.event_id'
            . $where .
            ' ORDER BY i.lot_number ASC, i.created_at DESC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
            $params
        );
    }

    /**
     * Count items for the public REST API (status IN 'active', 'ended', 'sold').
     */
    public function countApi(array $filters = []): int
    {
        [$where, $params] = $this->buildApiWhere($filters);

        $row = $this->db->queryOne(
            'SELECT COUNT(*) AS cnt
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             LEFT JOIN events e     ON e.id = i.event_id'
            . $where,
            $params
        );

        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Count all items for admin with optional filters (no status restriction).
     * Filters: event_id, category_id, status, donor_id
     */
    public function countAll(array $filters = []): int
    {
        [$where, $params] = $this->buildAdminWhere($filters);

        $row = $this->db->queryOne(
            'SELECT COUNT(*) AS cnt FROM items i' . $where,
            $params
        );

        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Find an item by slug, including joined fields.
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->db->queryOne(
            'SELECT i.*,
                    c.name  AS category_name,
                    e.title AS event_title,
                    e.slug  AS event_slug,
                    donor.name   AS donor_name,
                    winner.name  AS winner_name
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             LEFT JOIN events e     ON e.id = i.event_id
             LEFT JOIN users donor  ON donor.id  = i.donor_id
             LEFT JOIN users winner ON winner.id = i.winner_id
             WHERE i.slug = ?',
            [$slug]
        );
    }

    /**
     * Find an item by id.
     */
    public function findById(int $id): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM items WHERE id = ?',
            [$id]
        );
    }

    /**
     * Create a new item. Returns the new auto-increment id.
     * Expected keys: slug, event_id, category_id, donor_id (nullable), title,
     *   description, image (nullable), lot_number, starting_bid, min_increment,
     *   buy_now_price (nullable), market_value (nullable), status
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO items
                (slug, event_id, category_id, donor_id, title, description,
                 image, lot_number, starting_bid, min_increment, buy_now_price,
                 market_value, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $data['slug'],
                $data['event_id'],
                $data['category_id'],
                $data['donor_id'] ?? null,
                $data['title'],
                $data['description'] ?? null,
                $data['image'] ?? null,
                $data['lot_number'] ?? null,
                $data['starting_bid'] ?? 0.00,
                $data['min_increment'] ?? 1.00,
                $data['buy_now_price'] ?? null,
                $data['market_value'] ?? null,
                $data['status'] ?? 'draft',
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update an item's fields.
     */
    public function update(int $id, array $data): void
    {
        $allowed = [
            'slug', 'event_id', 'category_id', 'donor_id', 'title', 'description',
            'image', 'lot_number', 'starting_bid', 'min_increment', 'buy_now_price',
            'market_value', 'status', 'ends_at',
        ];
        $fields  = [];
        $values  = [];

        foreach ($data as $field => $value) {
            if (!in_array($field, $allowed, true)) {
                continue;
            }
            $fields[] = "$field = ?";
            $values[] = $value;
        }

        if (empty($fields)) {
            return;
        }

        $values[] = $id;
        $this->db->execute(
            'UPDATE items SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = ?',
            $values
        );
    }

    /**
     * Update just the status of an item.
     */
    public function updateStatus(int $id, string $status): void
    {
        $this->db->execute(
            'UPDATE items SET status = ?, updated_at = NOW() WHERE id = ?',
            [$status, $id]
        );
    }

    /**
     * Update current_bid and increment bid_count atomically.
     */
    public function updateBid(int $id, float $newBid): void
    {
        $this->db->execute(
            'UPDATE items SET current_bid = ?, bid_count = bid_count + 1, updated_at = NOW() WHERE id = ?',
            [$newBid, $id]
        );
    }

    /**
     * Update current_bid, bid_count, and optionally winner_id in one call.
     * Called by BidService after a successful bid.
     *
     * @param int        $id       Item id
     * @param float      $newBid   New current_bid value
     * @param int|null   $winnerId Optional winner_id (set on buy-now)
     */
    public function updateBidStats(int $id, float $newBid, ?int $winnerId = null): void
    {
        if ($winnerId !== null) {
            $this->db->execute(
                'UPDATE items SET current_bid = ?, bid_count = bid_count + 1, winner_id = ?, updated_at = NOW() WHERE id = ?',
                [$newBid, $winnerId, $id]
            );
        } else {
            $this->db->execute(
                'UPDATE items SET current_bid = ?, bid_count = bid_count + 1, updated_at = NOW() WHERE id = ?',
                [$newBid, $id]
            );
        }
    }

    /**
     * Set the status of an item (alias for updateStatus, used by BidService).
     */
    public function setStatus(int $id, string $status): void
    {
        $this->updateStatus($id, $status);
    }

    /**
     * Set the winner of an item.
     */
    public function setWinner(int $id, int $userId): void
    {
        $this->db->execute(
            'UPDATE items SET winner_id = ?, updated_at = NOW() WHERE id = ?',
            [$userId, $id]
        );
    }

    /**
     * Find active items whose auction time has ended.
     * Returns items with status='active' where:
     *   - item.ends_at is set and in the past, OR
     *   - item.ends_at is null AND the event's ends_at is in the past
     * Joins the event's ends_at as event_ends_at for use in AuctionService.
     */
    public function findEndedActive(): array
    {
        return $this->db->query(
            'SELECT i.*,
                    e.ends_at AS event_ends_at,
                    i.ends_at AS auction_end
             FROM items i
             LEFT JOIN events e ON e.id = i.event_id
             WHERE i.status = \'active\'
               AND (
                   (i.ends_at IS NOT NULL AND i.ends_at < NOW())
                   OR
                   (i.ends_at IS NULL AND e.ends_at IS NOT NULL AND e.ends_at < NOW())
               )'
        );
    }

    /**
     * All active (status='active') items for a given event, regardless of ends_at.
     * Used by AuctionService::processEventEnd when an admin manually ends an event early,
     * as findEndedActive() only returns time-expired items and would miss future-dated ones.
     */
    public function findActiveByEvent(int $eventId): array
    {
        return $this->db->query(
            "SELECT i.*, e.ends_at AS event_ends_at, i.ends_at AS auction_end
             FROM items i
             LEFT JOIN events e ON e.id = i.event_id
             WHERE i.event_id = ? AND i.status = 'active'",
            [$eventId]
        );
    }

    /**
     * All items for a given event, all statuses, ordered by lot_number.
     * Used by the auctioneer panel and projector to show the queue.
     */
    public function allForEvent(int $eventId): array
    {
        return $this->db->query(
            'SELECT i.*, c.name AS category_name
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             WHERE i.event_id = ?
             ORDER BY i.lot_number ASC, i.created_at ASC',
            [$eventId]
        );
    }

    /**
     * Search items by title using LIKE.
     */
    public function search(string $query, int $limit = 20): array
    {
        return $this->db->query(
            'SELECT i.*, c.name AS category_name
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             WHERE i.title LIKE ?
               AND i.status IN (\'active\', \'ended\', \'sold\')
             ORDER BY i.lot_number ASC, i.created_at DESC
             LIMIT ' . (int)$limit,
            ['%' . $query . '%']
        );
    }

    /**
     * Fetch all items donated by a user, ordered oldest-first.
     * Winner name is masked to "First L." for privacy.
     */
    public function forDonor(int $userId): array
    {
        $rows = $this->db->query(
            'SELECT items.*,
                    e.title  AS event_title,
                    e.slug   AS event_slug,
                    e.status AS event_status,
                    w.name   AS winner_name
             FROM   items
             LEFT   JOIN events e ON items.event_id = e.id
             LEFT   JOIN users  w ON items.winner_id = w.id
             WHERE  items.donor_id = ?
             ORDER  BY items.created_at ASC',
            [$userId]
        );

        foreach ($rows as &$row) {
            if (!empty($row['winner_name'])) {
                $row['winner_name'] = maskName($row['winner_name']);
            }
        }
        unset($row);

        return $rows;
    }


    /**
     * Return IDs of items donated by $userId whose event is currently active.
     * These items will be anonymised (donor_id = NULL) rather than deleted.
     */
    public function donorItemIdsInActiveAuctions(int $userId): array
    {
        $rows = $this->db->query(
            'SELECT i.id FROM items i
             JOIN events e ON e.id = i.event_id
             WHERE i.donor_id = ? AND e.status = \'active\'',
            [$userId]
        );
        return array_column($rows, 'id');
    }

    /**
     * Return IDs of items donated by $userId that are NOT in an active auction.
     * These items will be deleted.
     */
    public function donorItemIdsNotActive(int $userId): array
    {
        $rows = $this->db->query(
            'SELECT i.id FROM items i
             LEFT JOIN events e ON e.id = i.event_id
             WHERE i.donor_id = ?
               AND (e.id IS NULL OR e.status != \'active\')',
            [$userId]
        );
        return array_column($rows, 'id');
    }

    /**
     * Strip donor link from items in active auctions (GDPR anonymisation).
     */
    public function anonymiseDonor(int $userId): void
    {
        $this->db->execute(
            'UPDATE items i
             JOIN events e ON e.id = i.event_id
             SET i.donor_id = NULL
             WHERE i.donor_id = ? AND e.status = \'active\'',
            [$userId]
        );
    }

    /**
     * Delete items by their IDs.
     */
    public function deleteByIds(array $ids): void
    {
        if (empty($ids)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $this->db->execute(
            'DELETE FROM items WHERE id IN (' . $placeholders . ')',
            $ids
        );
    }

    /**
     * Clear winner_id where the deleted user is the winner.
     * The item stays — we just remove the winner reference.
     */
    public function clearWinner(int $userId): void
    {
        $this->db->execute(
            'UPDATE items SET winner_id = NULL WHERE winner_id = ?',
            [$userId]
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build WHERE clause + params for REST API item queries.
     * Always restricts to status IN ('active', 'ended', 'sold').
     */
    private function buildApiWhere(array $filters): array
    {
        $conditions = ["i.status IN ('active', 'ended', 'sold')"];
        $params     = [];

        if (!empty($filters['event_id'])) {
            $conditions[] = 'i.event_id = ?';
            $params[]     = (int)$filters['event_id'];
        }

        if (!empty($filters['category_id'])) {
            $conditions[] = 'i.category_id = ?';
            $params[]     = (int)$filters['category_id'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = 'i.title LIKE ?';
            $params[]     = '%' . $filters['search'] . '%';
        }

        return [' WHERE ' . implode(' AND ', $conditions), $params];
    }

    /**
     * Build WHERE clause + params for public browse queries.
     */
    private function buildBrowseWhere(array $filters): array
    {
        $conditions = ["i.status = 'active'"];
        $params     = [];

        if (!empty($filters['event_id'])) {
            $conditions[] = 'i.event_id = ?';
            $params[]     = (int)$filters['event_id'];
        }

        if (!empty($filters['category_id'])) {
            $conditions[] = 'i.category_id = ?';
            $params[]     = (int)$filters['category_id'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = 'i.title LIKE ?';
            $params[]     = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status'])) {
            // Override the default active-only filter
            array_shift($conditions);
            $conditions[] = 'i.status = ?';
            $params[]     = $filters['status'];
        }

        $where = ' WHERE ' . implode(' AND ', $conditions);

        return [$where, $params];
    }

    /**
     * Build WHERE clause + params for admin queries.
     */
    private function buildAdminWhere(array $filters): array
    {
        $conditions = [];
        $params     = [];

        if (!empty($filters['event_id'])) {
            $conditions[] = 'i.event_id = ?';
            $params[]     = (int)$filters['event_id'];
        }

        if (!empty($filters['category_id'])) {
            $conditions[] = 'i.category_id = ?';
            $params[]     = (int)$filters['category_id'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'i.status = ?';
            $params[]     = $filters['status'];
        }

        if (!empty($filters['donor_id'])) {
            $conditions[] = 'i.donor_id = ?';
            $params[]     = (int)$filters['donor_id'];
        }

        $where = empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions);

        return [$where, $params];
    }

    public function uniqueSlug(string $text): string
    {
        return uniqueSlug('items', $text, $this->db);
    }
}
