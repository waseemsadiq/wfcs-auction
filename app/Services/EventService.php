<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\EventRepository;

class EventService
{
    private EventRepository $events;

    /**
     * Accept an optional EventRepository for dependency injection in tests.
     */
    public function __construct(?EventRepository $events = null)
    {
        $this->events = $events ?? new EventRepository();
    }

    // -------------------------------------------------------------------------
    // Valid status transitions
    // -------------------------------------------------------------------------

    private const TRANSITIONS = [
        'draft'     => 'published',
        'published' => 'active',
        'active'    => 'ended',
        'ended'     => 'closed',
    ];

    // -------------------------------------------------------------------------
    // Create / Update
    // -------------------------------------------------------------------------

    /**
     * Create a new event.
     * Validates: title required, dates valid (ends_at after starts_at if both set).
     * Generates a unique slug. Status defaults to 'draft'.
     * Returns the persisted event array.
     *
     * @throws \RuntimeException on validation failure
     */
    public function create(array $data, int $adminId): array
    {
        $this->validate($data);

        $slug = $this->events->uniqueSlug($data['title']);

        $id = $this->events->create([
            'slug'        => $slug,
            'title'       => trim($data['title']),
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'status'      => 'draft',
            'starts_at'   => $data['starts_at'] ?? null,
            'ends_at'     => $data['ends_at'] ?? null,
            'venue'       => isset($data['venue']) ? trim($data['venue']) : null,
            'created_by'  => $adminId,
        ]);

        return $this->events->findById($id) ?? ['id' => $id, 'slug' => $slug];
    }

    /**
     * Update an existing event.
     * Validates: title required, dates valid (ends_at after starts_at if both set).
     * Returns the updated event array.
     *
     * @throws \RuntimeException on validation failure or event not found
     */
    public function update(int $eventId, array $data): array
    {
        $this->validate($data);

        $this->events->update($eventId, [
            'title'       => trim($data['title']),
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'starts_at'   => $data['starts_at'] ?? null,
            'ends_at'     => $data['ends_at'] ?? null,
            'venue'       => isset($data['venue']) ? trim($data['venue']) : null,
        ]);

        $event = $this->events->findById($eventId);
        if ($event === null) {
            throw new \RuntimeException('Event not found.');
        }

        return $event;
    }

    // -------------------------------------------------------------------------
    // Status transitions
    // -------------------------------------------------------------------------

    /**
     * Transition an event to a new status.
     * Valid transitions: draft→published, published→active, active→ended, ended→closed
     *
     * @throws \RuntimeException on invalid transition or event not found
     */
    public function transition(int $eventId, string $newStatus): void
    {
        $event = $this->events->findById($eventId);
        if ($event === null) {
            throw new \RuntimeException('Event not found.');
        }

        $current = $event['status'];
        $allowed = self::TRANSITIONS[$current] ?? null;

        if ($allowed !== $newStatus) {
            throw new \RuntimeException(
                "Invalid status transition: cannot move from '{$current}' to '{$newStatus}'."
            );
        }

        $this->events->updateStatus($eventId, $newStatus);
    }

    // -------------------------------------------------------------------------
    // Listings
    // -------------------------------------------------------------------------

    /**
     * Get paginated public events (published + active).
     * Returns: ['events' => [...], 'total' => int, 'page' => int, 'totalPages' => int]
     */
    public function publicList(int $page = 1, int $perPage = 12): array
    {
        $page    = max(1, $page);
        $offset  = ($page - 1) * $perPage;
        $total   = $this->events->countPublic();
        $events  = $this->events->allPublic($perPage, $offset);

        return [
            'events'     => $events,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => (int)ceil($total / max(1, $perPage)),
        ];
    }

    /**
     * Get all events for admin.
     * Returns: ['events' => [...], 'total' => int, 'page' => int, 'totalPages' => int]
     */
    public function adminList(int $page = 1, int $perPage = 25): array
    {
        $page    = max(1, $page);
        $offset  = ($page - 1) * $perPage;
        $total   = $this->events->countAll();
        $events  = $this->events->all($perPage, $offset);

        return [
            'events'     => $events,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => (int)ceil($total / max(1, $perPage)),
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Validate event data.
     *
     * @throws \RuntimeException on failure
     */
    private function validate(array $data): void
    {
        $title = trim($data['title'] ?? '');
        if ($title === '') {
            throw new \RuntimeException('Event title is required.');
        }

        if (!empty($data['starts_at']) && !empty($data['ends_at'])) {
            $starts = strtotime((string)$data['starts_at']);
            $ends   = strtotime((string)$data['ends_at']);

            if ($starts !== false && $ends !== false && $ends <= $starts) {
                throw new \RuntimeException('End date must be after start date.');
            }
        }
    }
}
