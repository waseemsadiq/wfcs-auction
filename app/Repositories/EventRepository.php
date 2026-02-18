<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Database;

class EventRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * All published and active events for public listing.
     */
    public function allPublic(int $limit = 20, int $offset = 0): array
    {
        return $this->db->query(
            'SELECT * FROM events
             WHERE status IN (\'published\', \'active\')
             ORDER BY starts_at ASC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset
        );
    }

    /**
     * All events for admin (all statuses).
     */
    public function all(int $limit = 50, int $offset = 0): array
    {
        return $this->db->query(
            'SELECT * FROM events
             ORDER BY created_at DESC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset
        );
    }

    /**
     * Find an event by slug.
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM events WHERE slug = ?',
            [$slug]
        );
    }

    /**
     * Find an event by id.
     */
    public function findById(int $id): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM events WHERE id = ?',
            [$id]
        );
    }

    /**
     * Create a new event. Returns the new auto-increment id.
     * Expected keys: slug, title, description, status, starts_at, ends_at, venue, created_by
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO events
                (slug, title, description, status, starts_at, ends_at, venue, created_by,
                 created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $data['slug'],
                $data['title'],
                $data['description'] ?? null,
                $data['status'] ?? 'draft',
                $data['starts_at'] ?? null,
                $data['ends_at'] ?? null,
                $data['venue'] ?? null,
                $data['created_by'],
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update an event's fields.
     */
    public function update(int $id, array $data): void
    {
        $allowed = ['slug', 'title', 'description', 'status', 'starts_at', 'ends_at', 'venue'];
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
            'UPDATE events SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = ?',
            $values
        );
    }

    /**
     * Update just the status of an event.
     */
    public function updateStatus(int $id, string $status): void
    {
        $this->db->execute(
            'UPDATE events SET status = ?, updated_at = NOW() WHERE id = ?',
            [$status, $id]
        );
    }

    /**
     * Count items belonging to an event.
     */
    public function itemCount(int $eventId): int
    {
        $row = $this->db->queryOne(
            'SELECT COUNT(*) AS cnt FROM items WHERE event_id = ?',
            [$eventId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Find events that should auto-transition:
     * status='active' with ends_at in the past.
     */
    public function findExpired(): array
    {
        return $this->db->query(
            'SELECT * FROM events WHERE status = \'active\' AND ends_at < NOW()'
        );
    }

    /**
     * Count all public events (published + active).
     */
    public function countPublic(): int
    {
        $row = $this->db->queryOne(
            'SELECT COUNT(*) AS cnt FROM events WHERE status IN (\'published\', \'active\')'
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Count all events (admin).
     */
    public function countAll(): int
    {
        $row = $this->db->queryOne(
            'SELECT COUNT(*) AS cnt FROM events'
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Fetch events by a single status, paginated. Used by the REST API.
     */
    public function byStatus(string $status, int $limit = 50, int $offset = 0): array
    {
        return $this->db->query(
            'SELECT * FROM events WHERE status = ?
             ORDER BY starts_at ASC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
            [$status]
        );
    }

    /**
     * Count events for a single status. Used by the REST API.
     */
    public function countByStatus(string $status): int
    {
        $row = $this->db->queryOne(
            'SELECT COUNT(*) AS cnt FROM events WHERE status = ?',
            [$status]
        );
        return (int)($row['cnt'] ?? 0);
    }

    public function uniqueSlug(string $text): string
    {
        return uniqueSlug('events', $text, $this->db);
    }
}
