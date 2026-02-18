<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\EventRepository;
use App\Repositories\ItemRepository;
use App\Repositories\UserRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\GiftAidRepository;
use App\Repositories\BidRepository;
use App\Repositories\SettingsRepository;
use App\Services\EventService;
use App\Services\AuctionService;

class AdminApiController extends ApiController
{
    private function requireAdmin(): array
    {
        $user = getAuthUser();
        if (!$user || $user['role'] !== 'admin') {
            $this->apiError('Admin access required.', 403);
        }
        return $user;
    }

    private function jsonBody(): array
    {
        return json_decode(file_get_contents('php://input') ?: '{}', true) ?? [];
    }

    // -------------------------------------------------------------------------
    // Auctions
    // -------------------------------------------------------------------------

    /**
     * GET /api/admin/v1/auctions
     */
    public function listAuctions(): void
    {
        $this->requireAdmin();

        $page    = max(1, (int)($_GET['page']     ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;

        $events = new EventRepository();
        $rows   = $events->all($perPage, $offset);
        $total  = $events->countAll();

        // Append item_count to each event
        foreach ($rows as &$row) {
            $row['item_count'] = $events->itemCount((int)$row['id']);
        }
        unset($row);

        $this->apiSuccess($rows, [
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    /**
     * GET /api/admin/v1/auctions/:slug
     */
    public function showAuction(string $slug): void
    {
        $this->requireAdmin();

        $events = new EventRepository();
        $event  = $events->findBySlug($slug);

        if (!$event) {
            $this->apiError('Auction not found.', 404);
        }

        $event['item_count'] = $events->itemCount((int)$event['id']);

        $this->apiSuccess($event);
    }

    /**
     * POST /api/admin/v1/auctions
     */
    public function createAuction(): void
    {
        $admin = $this->requireAdmin();
        $body  = $this->jsonBody();

        $title = trim((string)($body['title'] ?? ''));
        if ($title === '') {
            $this->apiError('title is required.');
        }

        $events = new EventRepository();
        $slug   = $events->uniqueSlug($title);

        $id = $events->create([
            'slug'        => $slug,
            'title'       => $title,
            'description' => $body['description'] ?? null,
            'status'      => 'draft',
            'starts_at'   => $body['starts_at'] ?? null,
            'ends_at'     => $body['ends_at'] ?? null,
            'venue'       => $body['venue'] ?? null,
            'created_by'  => (int)$admin['id'],
        ]);

        $event = $events->findById($id);
        $this->apiSuccess($event, [], 201);
    }

    /**
     * PUT /api/admin/v1/auctions/:slug
     */
    public function updateAuction(string $slug): void
    {
        $this->requireAdmin();
        $body = $this->jsonBody();

        $events = new EventRepository();
        $event  = $events->findBySlug($slug);

        if (!$event) {
            $this->apiError('Auction not found.', 404);
        }

        $allowed = ['title', 'description', 'starts_at', 'ends_at', 'venue'];
        $data    = array_intersect_key($body, array_flip($allowed));

        $events->update((int)$event['id'], $data);

        $this->apiSuccess($events->findBySlug($slug));
    }

    /**
     * POST /api/admin/v1/auctions/:slug/publish
     */
    public function publishAuction(string $slug): void
    {
        $this->requireAdmin();

        $events = new EventRepository();
        $event  = $events->findBySlug($slug);

        if (!$event) {
            $this->apiError('Auction not found.', 404);
        }

        try {
            (new EventService())->transition((int)$event['id'], 'published');
        } catch (\RuntimeException $e) {
            $this->apiError($e->getMessage(), 422);
        }

        $this->apiSuccess($events->findBySlug($slug));
    }

    /**
     * POST /api/admin/v1/auctions/:slug/open
     */
    public function openAuction(string $slug): void
    {
        $this->requireAdmin();

        $events = new EventRepository();
        $event  = $events->findBySlug($slug);

        if (!$event) {
            $this->apiError('Auction not found.', 404);
        }

        try {
            (new EventService())->transition((int)$event['id'], 'active');
        } catch (\RuntimeException $e) {
            $this->apiError($e->getMessage(), 422);
        }

        $this->apiSuccess($events->findBySlug($slug));
    }

    /**
     * POST /api/admin/v1/auctions/:slug/end
     */
    public function endAuction(string $slug): void
    {
        $this->requireAdmin();
        $body = $this->jsonBody();

        if (empty($body['confirmed'])) {
            $this->apiError('confirmed: true is required to end an auction.', 422);
        }

        $events = new EventRepository();
        $event  = $events->findBySlug($slug);

        if (!$event) {
            $this->apiError('Auction not found.', 404);
        }

        try {
            (new EventService())->transition((int)$event['id'], 'ended');
            (new AuctionService())->processEventEnd((int)$event['id']);
        } catch (\RuntimeException $e) {
            $this->apiError($e->getMessage(), 422);
        }

        $this->apiSuccess($events->findBySlug($slug));
    }

    // -------------------------------------------------------------------------
    // Items
    // -------------------------------------------------------------------------

    /**
     * GET /api/admin/v1/items
     */
    public function listItems(): void
    {
        $this->requireAdmin();

        $page    = max(1, (int)($_GET['page']     ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;

        $filters = [];
        if (!empty($_GET['status']))     { $filters['status']   = $_GET['status']; }
        if (!empty($_GET['event_slug'])) {
            $event = (new EventRepository())->findBySlug($_GET['event_slug']);
            if ($event) { $filters['event_id'] = (int)$event['id']; }
        }

        $items = new ItemRepository();
        $rows  = $items->all($filters, $perPage, $offset);
        $total = $items->countAll($filters);

        $this->apiSuccess($rows, [
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    /**
     * GET /api/admin/v1/items/:slug
     */
    public function showItem(string $slug): void
    {
        $this->requireAdmin();

        $items = new ItemRepository();
        $item  = $items->findBySlug($slug);

        if (!$item) {
            $this->apiError('Item not found.', 404);
        }

        $this->apiSuccess($item);
    }

    /**
     * PUT /api/admin/v1/items/:slug
     */
    public function updateItem(string $slug): void
    {
        $this->requireAdmin();
        $body = $this->jsonBody();

        $items = new ItemRepository();
        $item  = $items->findBySlug($slug);

        if (!$item) {
            $this->apiError('Item not found.', 404);
        }

        $allowed = [
            'title', 'description', 'status', 'lot_number',
            'starting_bid', 'min_increment', 'buy_now_price', 'market_value',
        ];
        $data = array_intersect_key($body, array_flip($allowed));

        $items->update((int)$item['id'], $data);

        $this->apiSuccess($items->findBySlug($slug));
    }

    // -------------------------------------------------------------------------
    // Users
    // -------------------------------------------------------------------------

    /**
     * GET /api/admin/v1/users
     */
    public function listUsers(): void
    {
        $this->requireAdmin();

        $q       = trim((string)($_GET['q']    ?? ''));
        $role    = trim((string)($_GET['role'] ?? ''));
        $page    = max(1, (int)($_GET['page']  ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;

        $users = new UserRepository();

        if ($q !== '' || $role !== '') {
            $rows  = $users->search($q, $role, $perPage, $offset);
            $total = $users->countSearch($q, $role);
        } else {
            $rows  = $users->all($perPage, $offset);
            $total = $users->count();
        }

        // Strip sensitive fields
        foreach ($rows as &$row) {
            unset($row['password_hash'], $row['email_verification_token']);
        }
        unset($row);

        $this->apiSuccess($rows, [
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    /**
     * GET /api/admin/v1/users/:slug
     */
    public function showUser(string $slug): void
    {
        $this->requireAdmin();

        $users = new UserRepository();
        $user  = $users->findBySlug($slug);

        if (!$user) {
            $this->apiError('User not found.', 404);
        }

        unset($user['password_hash'], $user['email_verification_token']);

        $this->apiSuccess($user);
    }

    /**
     * PUT /api/admin/v1/users/:slug
     */
    public function updateUser(string $slug): void
    {
        $this->requireAdmin();
        $body = $this->jsonBody();

        $users = new UserRepository();
        $user  = $users->findBySlug($slug);

        if (!$user) {
            $this->apiError('User not found.', 404);
        }

        // Role update handled separately
        if (isset($body['role'])) {
            $allowedRoles = ['bidder', 'donor', 'admin'];
            if (!in_array($body['role'], $allowedRoles, true)) {
                $this->apiError('Invalid role. Must be bidder, donor, or admin.');
            }
            $users->updateRole((int)$user['id'], $body['role']);
        }

        // Profile fields
        $profileFields = ['name', 'phone', 'gift_aid_eligible'];
        $profileData   = array_intersect_key($body, array_flip($profileFields));
        if (!empty($profileData)) {
            $users->updateProfile((int)$user['id'], $profileData);
        }

        $updated = $users->findBySlug($slug);
        unset($updated['password_hash'], $updated['email_verification_token']);

        $this->apiSuccess($updated);
    }

    // -------------------------------------------------------------------------
    // Payments
    // -------------------------------------------------------------------------

    /**
     * GET /api/admin/v1/payments
     */
    public function listPayments(): void
    {
        $this->requireAdmin();

        $page    = max(1, (int)($_GET['page']     ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;

        $filters = [];
        if (!empty($_GET['status'])) { $filters['status'] = $_GET['status']; }

        $payments = new PaymentRepository();
        $rows     = $payments->all($filters, $perPage, $offset);
        $total    = $payments->countAll($filters);

        $this->apiSuccess($rows, [
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    // -------------------------------------------------------------------------
    // Gift Aid
    // -------------------------------------------------------------------------

    /**
     * GET /api/admin/v1/gift-aid
     */
    public function giftAidOverview(): void
    {
        $this->requireAdmin();

        $giftAid = new GiftAidRepository();
        $stats   = $giftAid->stats();
        $recent  = $giftAid->claimed(10, 0);

        $this->apiSuccess([
            'stats'  => $stats,
            'recent' => $recent,
        ]);
    }

    // -------------------------------------------------------------------------
    // Reports
    // -------------------------------------------------------------------------

    /**
     * GET /api/admin/v1/reports/revenue
     */
    public function revenueReport(): void
    {
        $this->requireAdmin();

        $payments    = new PaymentRepository();
        $bids        = new BidRepository();
        $statusStats = $payments->statsByStatus(['pending', 'completed', 'failed', 'refunded']);

        $this->apiSuccess([
            'total_revenue'  => $payments->totalRevenue(),
            'by_status'      => $statusStats,
            'bids_today'     => $bids->countToday(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Settings
    // -------------------------------------------------------------------------

    /**
     * GET /api/admin/v1/settings
     */
    public function getSettings(): void
    {
        $this->requireAdmin();

        $settings = new SettingsRepository();
        $all      = $settings->all();

        // Mask sensitive values
        $sensitive = ['stripe_secret_key', 'stripe_webhook_secret'];
        foreach ($all as $key => $value) {
            if (in_array($key, $sensitive, true) && $value !== '') {
                $all[$key] = '***';
            }
        }

        $this->apiSuccess($all);
    }

    /**
     * PUT /api/admin/v1/settings
     */
    public function updateSettings(): void
    {
        $this->requireAdmin();
        $body = $this->jsonBody();

        $settings  = new SettingsRepository();
        $sensitive = ['stripe_secret_key', 'stripe_webhook_secret'];
        $updated   = [];

        foreach ($body as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                continue;
            }
            if (in_array($key, $sensitive, true)) {
                continue; // Cannot update sensitive keys via API
            }
            $settings->set($key, $value);
            $updated[$key] = $value;
        }

        $this->apiSuccess(['updated' => $updated]);
    }

    // -------------------------------------------------------------------------
    // Live events
    // -------------------------------------------------------------------------

    /**
     * GET /api/admin/v1/live
     */
    public function getLiveStatus(): void
    {
        $this->requireAdmin();

        $settings   = new SettingsRepository();
        $liveItemId = $settings->get('live_item_id');
        $paused     = $settings->get('bidding_paused');

        $liveItem = null;
        if ($liveItemId !== null && $liveItemId !== '') {
            $items    = new ItemRepository();
            $liveItem = $items->findById((int)$liveItemId);
        }

        $this->apiSuccess([
            'live_item'      => $liveItem,
            'bidding_paused' => $paused === '1',
        ]);
    }

    /**
     * POST /api/admin/v1/live/start
     */
    public function startLive(): void
    {
        $this->requireAdmin();
        $body = $this->jsonBody();

        $itemSlug = trim((string)($body['item_slug'] ?? ''));
        if ($itemSlug === '') {
            $this->apiError('item_slug is required.');
        }

        $items = new ItemRepository();
        $item  = $items->findBySlug($itemSlug);

        if (!$item) {
            $this->apiError('Item not found.', 404);
        }

        $settings = new SettingsRepository();
        $settings->set('live_item_id', (string)$item['id']);
        $settings->set('live_item_status', 'pending');
        $settings->set('bidding_paused', '0');

        $this->apiSuccess(['live_item' => $item]);
    }

    /**
     * POST /api/admin/v1/live/stop
     */
    public function stopLive(): void
    {
        $this->requireAdmin();

        $settings = new SettingsRepository();
        $settings->set('live_item_id', '');
        $settings->set('live_item_status', '');
        $settings->set('bidding_paused', '0');

        $this->apiSuccess(['live_item' => null]);
    }
}
