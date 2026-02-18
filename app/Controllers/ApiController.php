<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Repositories\ItemRepository;
use App\Repositories\EventRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\UserRepository;
use App\Services\BidService;
use App\Services\RateLimitService;
use App\Services\ApiTokenService;

class ApiController extends Controller
{
    // -------------------------------------------------------------------------
    // API response helpers
    // -------------------------------------------------------------------------

    /**
     * Emit a successful API response with optional pagination meta.
     */
    protected function apiSuccess(mixed $data, array $meta = [], int $status = 200): never
    {
        $payload = ['data' => $data];
        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }
        $this->json($payload, $status);
    }

    /**
     * Emit an API error response.
     */
    protected function apiError(string $message, int $status = 400): never
    {
        $this->json(['error' => $message], $status);
    }

    // -------------------------------------------------------------------------
    // Items
    // -------------------------------------------------------------------------

    /**
     * GET /api/v1/items
     * Optional: ?q=, ?category=, ?event=, ?page=1, ?per_page=20
     */
    public function listItems(): void
    {
        $q          = trim((string)($_GET['q']          ?? ''));
        $categorySlug = trim((string)($_GET['category'] ?? ''));
        $eventSlug  = trim((string)($_GET['event']      ?? ''));
        $page       = max(1, (int)($_GET['page']        ?? 1));
        $perPage    = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $offset     = ($page - 1) * $perPage;

        $items    = new ItemRepository();
        $events   = new EventRepository();
        $cats     = new CategoryRepository();

        $filters = [];

        // Resolve category slug → id
        if ($categorySlug !== '') {
            $cat = $cats->findBySlug($categorySlug);
            if ($cat) {
                $filters['category_id'] = (int)$cat['id'];
            }
        }

        // Resolve event slug → id
        if ($eventSlug !== '') {
            $event = $events->findBySlug($eventSlug);
            if ($event) {
                $filters['event_id'] = (int)$event['id'];
            }
        }

        // Search query
        if ($q !== '') {
            $filters['search'] = $q;
        }

        // For the API we want active, ended and sold — not draft
        // The browse() method defaults to active. We need a broader status set.
        // Use the search/browse infrastructure with a multi-status override.
        // Borrowing the 'status' filter key to inject a multi-value condition
        // is not ideal, so we use a direct query via a custom flag.

        // We cannot pass multiple statuses through the existing buildBrowseWhere
        // (it only handles one string). Use a dedicated repository call instead.
        $rows  = $this->browsePublicItems($items, $filters, $perPage, $offset);
        $total = $this->countPublicItems($items, $filters);

        $this->apiSuccess($rows, [
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    /**
     * GET /api/v1/items/:slug
     */
    public function showItem(string $slug): void
    {
        $items = new ItemRepository();
        $item  = $items->findBySlug($slug);

        if (!$item || $item['status'] === 'draft') {
            $this->apiError('Item not found.', 404);
        }

        // Bid history — last 5, masked names
        $bids    = new \App\Repositories\BidRepository();
        $history = $bids->byItem((int)$item['id'], 5);

        $item['bid_history'] = $history;

        $this->apiSuccess($item);
    }

    // -------------------------------------------------------------------------
    // Events
    // -------------------------------------------------------------------------

    /**
     * GET /api/v1/events
     * Optional: ?status=active, ?page=1, ?per_page=20
     */
    public function listEvents(): void
    {
        $statusFilter = trim((string)($_GET['status']   ?? ''));
        $page         = max(1, (int)($_GET['page']      ?? 1));
        $perPage      = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $offset       = ($page - 1) * $perPage;

        $events = new EventRepository();

        // Only published/active by default; allow filtering to just 'active'
        $allowedStatuses = ['published', 'active'];
        if ($statusFilter !== '' && in_array($statusFilter, $allowedStatuses, true)) {
            $rows  = $this->getEventsByStatus($statusFilter, $perPage, $offset);
            $total = $this->countEventsByStatus($statusFilter);
        } else {
            $rows  = $events->allPublic($perPage, $offset);
            $total = $events->countPublic();
        }

        $this->apiSuccess($rows, [
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    /**
     * GET /api/v1/events/:slug
     */
    public function showEvent(string $slug): void
    {
        $events = new EventRepository();
        $event  = $events->findBySlug($slug);

        if (!$event || $event['status'] === 'draft') {
            $this->apiError('Event not found.', 404);
        }

        $this->apiSuccess($event);
    }

    /**
     * GET /api/v1/events/:slug/items
     */
    public function eventItems(string $slug): void
    {
        $events = new EventRepository();
        $event  = $events->findBySlug($slug);

        if (!$event || $event['status'] === 'draft') {
            $this->apiError('Event not found.', 404);
        }

        $page    = max(1, (int)($_GET['page']     ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 50)));
        $offset  = ($page - 1) * $perPage;

        $items = new ItemRepository();
        $rows  = $items->byEvent((int)$event['id'], $perPage, $offset);

        $this->apiSuccess($rows, [
            'event_slug' => $event['slug'],
            'event_title' => $event['title'],
            'total'      => count($rows),
            'page'       => $page,
            'per_page'   => $perPage,
        ]);
    }

    // -------------------------------------------------------------------------
    // Bids
    // -------------------------------------------------------------------------

    /**
     * POST /api/v1/bids
     * Body params: item_slug, amount, buy_now (0|1), token
     */
    public function placeBid(): void
    {
        $user = getAuthUser();
        if (!$user) {
            $this->apiError('Authentication required.', 401);
        }

        // Rate limiting by user slug
        try {
            (new RateLimitService())->check($user['slug'], 'api_token');
        } catch (\RuntimeException $e) {
            $this->apiError($e->getMessage(), 429);
        }

        $itemSlug = trim((string)($_POST['item_slug'] ?? ''));
        $amount   = (float)($_POST['amount']           ?? 0);
        $isBuyNow = (bool)(int)($_POST['buy_now']      ?? 0);

        if ($itemSlug === '') {
            $this->apiError('item_slug is required.');
        }

        if ($amount <= 0) {
            $this->apiError('amount must be a positive number.');
        }

        // Fetch item
        $items = new ItemRepository();
        $item  = $items->findBySlug($itemSlug);
        if (!$item) {
            $this->apiError('Item not found.', 404);
        }

        // Fetch user record from DB (need email_verified_at, id, etc.)
        $users    = new UserRepository();
        $userRow  = $users->findById((int)$user['id']);
        if (!$userRow) {
            $this->apiError('User not found.', 404);
        }

        try {
            $result = (new BidService())->place($item, $userRow, $amount, $isBuyNow);
        } catch (\RuntimeException $e) {
            $this->apiError($e->getMessage(), 422);
        }

        $this->apiSuccess($result, [], 201);
    }

    // -------------------------------------------------------------------------
    // Users
    // -------------------------------------------------------------------------

    /**
     * GET /api/v1/users/me
     */
    public function me(): void
    {
        $user = getAuthUser();
        if (!$user) {
            $this->apiError('Authentication required.', 401);
        }

        // Fetch full user row from DB (never expose password_hash)
        $users   = new UserRepository();
        $userRow = $users->findById((int)$user['id']);
        if (!$userRow) {
            $this->apiError('User not found.', 404);
        }

        $this->apiSuccess([
            'id'                => (int)$userRow['id'],
            'slug'              => $userRow['slug'],
            'name'              => $userRow['name'],
            'email'             => $userRow['email'],
            'role'              => $userRow['role'],
            'email_verified'    => !empty($userRow['email_verified_at']),
            'gift_aid_eligible' => (bool)(int)($userRow['gift_aid_eligible'] ?? 0),
            'created_at'        => $userRow['created_at'],
        ]);
    }

    /**
     * GET /api/v1/users/me/bids
     * Optional: ?page=1, ?per_page=20
     */
    public function myBids(): void
    {
        $user = getAuthUser();
        if (!$user) {
            $this->apiError('Authentication required.', 401);
        }

        $page    = max(1, (int)($_GET['page']     ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));

        $result = (new BidService())->myBids((int)$user['id'], $page, $perPage);

        $this->apiSuccess($result['bids'], [
            'total'    => $result['total'],
            'page'     => $result['page'],
            'per_page' => $result['per_page'],
            'pages'    => $result['pages'],
        ]);
    }

    // -------------------------------------------------------------------------
    // Token generation
    // -------------------------------------------------------------------------

    /**
     * GET /api/v1/token
     * Requires auth. Returns a long-lived (1-year) API token.
     */
    public function generateToken(): void
    {
        $user = getAuthUser();
        if (!$user) {
            $this->apiError('Authentication required.', 401);
        }

        // Fetch full user row to ensure we have fresh data
        $users   = new UserRepository();
        $userRow = $users->findById((int)$user['id']);
        if (!$userRow) {
            $this->apiError('User not found.', 404);
        }

        $token = (new ApiTokenService())->generate($userRow);

        $this->apiSuccess(['token' => $token, 'expires_in' => 365 * 24 * 3600]);
    }

    // -------------------------------------------------------------------------
    // Internal AJAX polling
    // -------------------------------------------------------------------------

    /**
     * GET /api/current-bid/:slug
     * Returns the current bid and bid count for a given item (no auth required).
     */
    public function currentBid(string $slug): void
    {
        $items = new ItemRepository();
        $item  = $items->findBySlug($slug);

        if (!$item) {
            $this->apiError('Item not found.', 404);
        }

        $this->apiSuccess([
            'slug'        => $item['slug'],
            'current_bid' => (float)($item['current_bid'] ?? 0),
            'bid_count'   => (int)($item['bid_count'] ?? 0),
            'status'      => $item['status'],
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Browse public items (active, ended, sold) with optional filters.
     * Delegates to ItemRepository::browseApi() which applies the correct status filter.
     */
    private function browsePublicItems(
        ItemRepository $items,
        array $filters,
        int $limit,
        int $offset
    ): array {
        return $items->browseApi($filters, $limit, $offset);
    }

    /**
     * Count public items matching the given filters.
     * Delegates to ItemRepository::countApi().
     */
    private function countPublicItems(ItemRepository $items, array $filters): int
    {
        return $items->countApi($filters);
    }

    /**
     * Fetch events filtered by a single status, paginated.
     * Delegates to EventRepository::byStatus().
     */
    private function getEventsByStatus(string $status, int $limit, int $offset): array
    {
        return (new EventRepository())->byStatus($status, $limit, $offset);
    }

    /**
     * Count events for a single status.
     * Delegates to EventRepository::countByStatus().
     */
    private function countEventsByStatus(string $status): int
    {
        return (new EventRepository())->countByStatus($status);
    }
}
