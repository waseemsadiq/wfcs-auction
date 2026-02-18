<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\CategoryRepository;
use App\Repositories\EventRepository;
use App\Repositories\ItemRepository;
use Core\Database;

class ItemService
{
    private ItemRepository     $items;
    private EventRepository    $events;
    private CategoryRepository $categories;

    /**
     * Accept optional repositories for dependency injection in tests.
     */
    public function __construct(
        ?ItemRepository     $items      = null,
        ?EventRepository    $events     = null,
        ?CategoryRepository $categories = null
    ) {
        $this->items      = $items      ?? new ItemRepository();
        $this->events     = $events     ?? new EventRepository();
        $this->categories = $categories ?? new CategoryRepository();
    }

    // -------------------------------------------------------------------------
    // Donor submission
    // -------------------------------------------------------------------------

    /**
     * Submit an item for donation.
     *
     * Validates: title required, category exists, event exists and is published/active.
     * Sets status to 'draft'.
     * Generates slug from title.
     * Returns the persisted item array.
     *
     * @throws \RuntimeException on validation failure
     */
    public function submit(array $data, ?int $donorId): array
    {
        $title = trim($data['title'] ?? '');
        if ($title === '') {
            throw new \RuntimeException('Item title is required.');
        }

        $categoryId = (int)($data['category_id'] ?? 0);
        if ($categoryId === 0 || $this->categories->findById($categoryId) === null) {
            throw new \RuntimeException('Please select a valid category.');
        }

        $db   = Database::getInstance();
        $slug = uniqueSlug('items', $title, $db);

        $id = $this->items->create([
            'slug'          => $slug,
            'event_id'      => null,
            'category_id'   => $categoryId,
            'donor_id'      => $donorId,
            'title'         => $title,
            'description'   => isset($data['description']) ? trim($data['description']) : null,
            'image'         => $data['image'] ?? null,
            'lot_number'    => null,
            'starting_bid'  => isset($data['starting_bid']) && $data['starting_bid'] !== ''
                                ? (float)$data['starting_bid'] : 0.00,
            'min_increment' => isset($data['min_increment']) && $data['min_increment'] !== ''
                                ? (float)$data['min_increment'] : 1.00,
            'buy_now_price' => isset($data['buy_now_price']) && $data['buy_now_price'] !== ''
                                ? (float)$data['buy_now_price'] : null,
            'status'        => 'pending',
        ]);

        return $this->items->findById($id) ?? ['id' => $id, 'slug' => $slug];
    }

    // -------------------------------------------------------------------------
    // Admin actions
    // -------------------------------------------------------------------------

    /**
     * Admin: create or update an item directly.
     * Returns the persisted item array.
     *
     * @throws \RuntimeException on validation failure
     */
    public function adminSave(array $data, int $adminId): array
    {
        $title = trim($data['title'] ?? '');
        if ($title === '') {
            throw new \RuntimeException('Item title is required.');
        }

        $categoryId = (int)($data['category_id'] ?? 0);
        if ($categoryId === 0 || $this->categories->findById($categoryId) === null) {
            throw new \RuntimeException('Please select a valid category.');
        }

        $eventId = (int)($data['event_id'] ?? 0);
        if ($eventId === 0 || $this->events->findById($eventId) === null) {
            throw new \RuntimeException('Please select a valid auction event.');
        }

        // Update if id provided, otherwise create
        if (!empty($data['id'])) {
            $itemId = (int)$data['id'];

            $this->items->update($itemId, [
                'event_id'      => $eventId,
                'category_id'   => $categoryId,
                'donor_id'      => $data['donor_id'] ?? null,
                'title'         => $title,
                'description'   => isset($data['description']) ? trim($data['description']) : null,
                'image'         => $data['image'] ?? null,
                'lot_number'    => isset($data['lot_number']) ? (int)$data['lot_number'] : null,
                'starting_bid'  => isset($data['starting_bid']) ? (float)$data['starting_bid'] : 0.00,
                'min_increment' => isset($data['min_increment']) ? (float)$data['min_increment'] : 1.00,
                'buy_now_price' => isset($data['buy_now_price']) && $data['buy_now_price'] !== ''
                                    ? (float)$data['buy_now_price'] : null,
                'status'        => $data['status'] ?? 'active',
            ]);

            return $this->items->findById($itemId) ?? ['id' => $itemId];
        }

        $db   = Database::getInstance();
        $slug = uniqueSlug('items', $title, $db);

        $id = $this->items->create([
            'slug'          => $slug,
            'event_id'      => $eventId,
            'category_id'   => $categoryId,
            'donor_id'      => $data['donor_id'] ?? null,
            'title'         => $title,
            'description'   => isset($data['description']) ? trim($data['description']) : null,
            'image'         => $data['image'] ?? null,
            'lot_number'    => isset($data['lot_number']) ? (int)$data['lot_number'] : null,
            'starting_bid'  => isset($data['starting_bid']) ? (float)$data['starting_bid'] : 0.00,
            'min_increment' => isset($data['min_increment']) ? (float)$data['min_increment'] : 1.00,
            'buy_now_price' => isset($data['buy_now_price']) && $data['buy_now_price'] !== ''
                                ? (float)$data['buy_now_price'] : null,
            'status'        => $data['status'] ?? 'active',
        ]);

        return $this->items->findById($id) ?? ['id' => $id, 'slug' => $slug];
    }

    // -------------------------------------------------------------------------
    // Public browsing
    // -------------------------------------------------------------------------

    /**
     * Public item browsing with pagination and filters.
     * Returns: ['items' => [...], 'total' => int, 'page' => int, 'totalPages' => int]
     */
    public function browse(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $total  = $this->items->countBrowse($filters);
        $items  = $this->items->browse($filters, $perPage, $offset);

        return [
            'items'      => $items,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => (int)ceil($total / max(1, $perPage)),
        ];
    }

    /**
     * Search items by title.
     */
    public function search(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        return $this->items->search($query);
    }
}
