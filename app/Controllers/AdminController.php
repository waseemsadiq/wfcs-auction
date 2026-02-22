<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\EventService;
use App\Services\ItemService;
use App\Services\AuctionService;
use App\Services\UploadService;
use App\Repositories\EventRepository;
use App\Repositories\ItemRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\UserRepository;
use App\Repositories\BidRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\GiftAidRepository;
use App\Repositories\SettingsRepository;

class AdminController extends Controller
{
    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public function index(): void
    {
        $this->dashboard();
    }

    /**
     * GET /admin/dashboard
     */
    public function dashboard(): void
    {
        global $basePath;
        $user = requireAdmin();

        $eventRepo   = new EventRepository();
        $itemRepo    = new ItemRepository();
        $bidRepo     = new BidRepository();
        $paymentRepo = new PaymentRepository();
        $userRepo    = new UserRepository();
        $giftRepo    = new GiftAidRepository();

        $eventCount          = $eventRepo->countAll();
        $activeItemCount     = $itemRepo->countAll(['status' => 'active']);
        $bidCountToday       = $bidRepo->countToday();
        $totalRevenue        = $paymentRepo->totalRevenue();
        $giftAidStats        = $giftRepo->stats();
        $giftAidTotal        = $giftAidStats['total_claimed'];
        $pendingPaymentCount = $paymentRepo->statsByStatus(['pending'])['pending'];
        $userStats           = $userRepo->adminStats();
        $bidderCount         = $userStats['bidders'];
        $bidderGiftAidCount  = $userRepo->countGiftAidEligible();

        $stats = [
            'event_count'           => $eventCount,
            'active_item_count'     => $activeItemCount,
            'bid_count_today'       => $bidCountToday,
            'total_revenue'         => $totalRevenue,
            'gift_aid_total'        => $giftAidTotal,
            'pending_payment_count' => $pendingPaymentCount,
            'bidder_count'          => $bidderCount,
            'bidder_gift_aid_count' => $bidderGiftAidCount,
        ];

        // Recent 10 bids with item + category join
        $recentBids = $bidRepo->recentWithDetails(10);

        // Live event
        $settingsRepo = new SettingsRepository();
        $liveEventId  = (int)($settingsRepo->get('live_event_id') ?? 0);
        $liveEvent    = null;
        if ($liveEventId > 0) {
            $liveEvent = (new EventRepository())->findById($liveEventId);
        }

        $content = $this->renderView('admin/dashboard', [
            'stats'      => $stats,
            'recentBids' => $recentBids,
            'liveEvent'  => $liveEvent,
        ]);

        $this->view('layouts/admin', [
            'pageTitle' => 'Dashboard — WFCS Auction Admin',
            'user'      => $user,
            'activeNav' => 'dashboard',
            'content'   => $content,
        ]);
    }

    // -------------------------------------------------------------------------
    // Auctions
    // -------------------------------------------------------------------------

    /**
     * GET /admin/auctions
     */
    public function auctions(): void
    {
        global $basePath;
        $user = requireAdmin();

        $page        = max(1, (int)($_GET['page'] ?? 1));
        $eventService = new EventService();
        $result      = $eventService->adminList($page, 25);

        $eventRepo = new EventRepository();
        foreach ($result['events'] as &$ev) {
            $ev['item_count'] = $eventRepo->itemCount((int)$ev['id']);
        }
        unset($ev);

        $events     = $result['events'];
        $total      = $result['total'];
        $totalPages = $result['totalPages'];

        $content = $this->renderView('admin/auctions', [
            'events'     => $events,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => $totalPages,
        ]);

        $this->view('layouts/admin', [
            'pageTitle' => 'Auctions — WFCS Auction Admin',
            'user'      => $user,
            'activeNav' => 'auctions',
            'content'   => $content,
        ]);
    }

    /**
     * POST /admin/auctions  — create new auction
     */
    public function createAuction(): void
    {
        global $basePath;
        $user = requireAdmin();
        validateCsrf();

        try {
            $eventService = new EventService();
            $eventService->create($_POST, (int)$user['id']);
            flash('Auction created successfully.');
        } catch (\RuntimeException $e) {
            flash($e->getMessage(), 'error');
        }

        $this->redirect($basePath . '/admin/auctions');
    }

    /**
     * GET /admin/auctions/:slug/edit  — show form with existing data
     * (Auctions use a popover in the list view. This method handles page-level form if JS disabled.)
     */
    public function editAuction(string $slug): void
    {
        global $basePath;
        $user = requireAdmin();

        $eventRepo = new EventRepository();
        $event     = $eventRepo->findBySlug($slug);

        if ($event === null) {
            $this->abort(404);
        }

        // For the popover approach the edit form posts back to updateAuction.
        // Redirect to auctions list (the edit happens via popover on that page).
        $this->redirect($basePath . '/admin/auctions');
    }

    /**
     * POST /admin/auctions/:slug  — update auction
     */
    public function updateAuction(string $slug): void
    {
        global $basePath;
        requireAdmin();
        validateCsrf();

        $eventRepo = new EventRepository();
        $event     = $eventRepo->findBySlug($slug);

        if ($event === null) {
            $this->abort(404);
        }

        try {
            $eventService = new EventService();
            $eventService->update((int)$event['id'], $_POST);
            flash('Auction updated.');
        } catch (\RuntimeException $e) {
            flash($e->getMessage(), 'error');
        }

        $this->redirect($basePath . '/admin/auctions');
    }

    /**
     * POST /admin/auctions/:slug/publish
     */
    public function publishAuction(string $slug): void
    {
        global $basePath;
        requireAdmin();

        $eventRepo = new EventRepository();
        $event     = $eventRepo->findBySlug($slug);

        if ($event === null) {
            $this->abort(404);
        }

        try {
            $eventService = new EventService();
            $eventService->transition((int)$event['id'], 'published');
            flash('Auction published successfully.');
        } catch (\RuntimeException $e) {
            flash($e->getMessage(), 'error');
        }

        $this->redirect($basePath . '/admin/auctions');
    }

    /**
     * POST /admin/auctions/:slug/open
     */
    public function openAuction(string $slug): void
    {
        global $basePath;
        requireAdmin();

        $eventRepo = new EventRepository();
        $event     = $eventRepo->findBySlug($slug);

        if ($event === null) {
            $this->abort(404);
        }

        try {
            $eventService = new EventService();
            $eventService->transition((int)$event['id'], 'active');
            flash('Auction opened — bidding is now live.');
        } catch (\RuntimeException $e) {
            flash($e->getMessage(), 'error');
        }

        $this->redirect($basePath . '/admin/auctions');
    }

    /**
     * POST /admin/auctions/:slug/end
     */
    public function endAuction(string $slug): void
    {
        global $basePath;
        requireAdmin();

        $eventRepo = new EventRepository();
        $event     = $eventRepo->findBySlug($slug);

        if ($event === null) {
            $this->abort(404);
        }

        try {
            $eventService   = new EventService();
            $auctionService = new AuctionService();

            $eventService->transition((int)$event['id'], 'ended');
            $auctionService->processEventEnd((int)$event['id']);

            flash('Auction ended — winners have been determined.');
        } catch (\RuntimeException $e) {
            flash($e->getMessage(), 'error');
        }

        $this->redirect($basePath . '/admin/auctions');
    }

    // -------------------------------------------------------------------------
    // Items
    // -------------------------------------------------------------------------

    /**
     * GET /admin/items
     */
    public function items(): void
    {
        global $basePath;
        $user = requireAdmin();

        $page     = max(1, (int)($_GET['page'] ?? 1));
        $perPage  = 30;
        $filters  = [
            'status'      => trim($_GET['status']      ?? ''),
            'event_id'    => (int)($_GET['event_id']   ?? 0),
            'category_id' => (int)($_GET['category_id'] ?? 0),
        ];
        // Remove empty filter values
        $filters = array_filter($filters, fn($v) => $v !== '' && $v !== 0);

        $itemRepo  = new ItemRepository();
        $offset    = ($page - 1) * $perPage;
        $items     = $itemRepo->all($filters, $perPage, $offset);
        $total     = $itemRepo->countAll($filters);

        $totalPages  = (int)ceil($total / max(1, $perPage));
        $categories  = (new CategoryRepository())->all();
        $events      = (new EventRepository())->all();

        $content = $this->renderView('admin/items', [
            'items'      => $items,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => $totalPages,
            'categories' => $categories,
            'events'     => $events,
            'filters'    => $filters,
        ]);

        $this->view('layouts/admin', [
            'pageTitle' => 'Items — WFCS Auction Admin',
            'user'      => $user,
            'activeNav' => 'items',
            'content'   => $content,
        ]);
    }

    /**
     * GET /admin/items/:slug/edit
     */
    public function editItem(string $slug): void
    {
        global $basePath;
        $user = requireAdmin();

        $itemRepo = new ItemRepository();
        $item     = $itemRepo->findBySlug($slug);

        if ($item === null) {
            $this->abort(404);
        }

        $categories = (new CategoryRepository())->all();
        $events     = (new EventRepository())->all();
        $errors     = [];
        $old        = [];

        $content = $this->renderView('admin/items-form', [
            'item'       => $item,
            'categories' => $categories,
            'events'     => $events,
            'errors'     => $errors,
            'old'        => $old,
        ]);

        $this->view('layouts/admin', [
            'pageTitle' => 'Edit Item — WFCS Auction Admin',
            'user'      => $user,
            'activeNav' => 'items',
            'content'   => $content,
        ]);
    }

    /**
     * POST /admin/items/:slug/edit
     */
    public function updateItem(string $slug): void
    {
        global $basePath;
        $user = requireAdmin();
        validateCsrf();

        $itemRepo = new ItemRepository();
        $item     = $itemRepo->findBySlug($slug);

        if ($item === null) {
            $this->abort(404);
        }

        $imageFilename = $item['image'];

        // Handle image upload
        if (!empty($_FILES['image']['tmp_name'])) {
            try {
                $uploadService = new UploadService();
                $imageFilename = $uploadService->handleImage($_FILES['image']);
            } catch (\RuntimeException $e) {
                flash($e->getMessage(), 'error');
                $this->redirect($basePath . '/admin/items/' . $slug . '/edit');
                return;
            }
        }

        try {
            $itemService = new ItemService();
            $data = array_merge($_POST, ['id' => $item['id'], 'image' => $imageFilename]);
            $itemService->adminSave($data, (int)$user['id']);
            flash('Item updated.');
        } catch (\RuntimeException $e) {
            flash($e->getMessage(), 'error');
            $this->redirect($basePath . '/admin/items/' . $slug . '/edit');
            return;
        }

        $this->redirect($basePath . '/admin/items');
    }

    // -------------------------------------------------------------------------
    // Users
    // -------------------------------------------------------------------------

    /**
     * GET /admin/users
     */
    public function users(): void
    {
        global $basePath;
        $user = requireAdmin();

        $perPage    = 30;
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $search     = trim($_GET['q'] ?? '');
        $roleFilter = trim($_GET['role'] ?? '');
        $offset     = ($page - 1) * $perPage;

        $userRepo = new UserRepository();
        $users    = $userRepo->search($search, $roleFilter, $perPage, $offset);
        $total    = $userRepo->countSearch($search, $roleFilter);
        $stats    = $userRepo->adminStats();
        $totalPages = (int)ceil($total / max(1, $perPage));

        // Append bid counts
        $userIds = array_column($users, 'id');
        $bidCounts = $userRepo->bidCountForUsers(array_map('intval', $userIds));
        foreach ($users as &$u) {
            $u['bid_count'] = $bidCounts[(int)$u['id']] ?? 0;
        }
        unset($u);

        $content = $this->renderView('admin/users', [
            'users'      => $users,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => $totalPages,
            'stats'      => $stats,
            'search'     => $search,
            'roleFilter' => $roleFilter,
        ]);

        $this->view('layouts/admin', [
            'pageTitle' => 'Users — WFCS Auction Admin',
            'user'      => $user,
            'activeNav' => 'users',
            'content'   => $content,
        ]);
    }

    /**
     * GET /admin/users/:slug
     */
    public function showUser(string $slug): void
    {
        global $basePath;
        $user = requireAdmin();

        $userRepo = new UserRepository();
        $profile  = $userRepo->findBySlug($slug);

        if ($profile === null) {
            $this->abort(404);
        }

        $bidRepo     = new BidRepository();
        $paymentRepo = new PaymentRepository();
        $bids        = $bidRepo->byUser((int)$profile['id']);
        $payments    = $paymentRepo->byUser((int)$profile['id']);

        $content = $this->renderView('admin/user-detail', [
            'profile'  => $profile,
            'bids'     => $bids,
            'payments' => $payments,
        ]);

        $this->view('layouts/admin', [
            'pageTitle' => 'User: ' . ($profile['name'] ?? '') . ' — WFCS Auction Admin',
            'user'      => $user,
            'activeNav' => 'users',
            'content'   => $content,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /admin/users/:slug/delete
    // -------------------------------------------------------------------------

    public function deleteUser(string $slug): void
    {
        global $basePath;
        $actingAdmin = requireAdmin();

        $userRepo = new UserRepository();
        $profile  = $userRepo->findBySlug($slug);

        if ($profile === null) {
            flash('User not found.', 'error');
            $this->redirect($basePath . '/admin/users');
        }

        if ((string)($profile['role'] ?? '') === 'admin') {
            flash('Admin accounts cannot be deleted.', 'error');
            $this->redirect($basePath . '/admin/users');
        }

        try {
            $service = new \App\Services\UserService(
                $userRepo,
                new BidRepository(),
                new ItemRepository(),
                new PaymentRepository()
            );
            $service->deleteUser($profile, (int)$actingAdmin['id']);
        } catch (\RuntimeException $e) {
            flash($e->getMessage(), 'error');
            $this->redirect($basePath . '/admin/users');
        }

        flash(e($profile['name']) . ' has been permanently deleted.', 'success');
        $this->redirect($basePath . '/admin/users');
    }

    /**
     * POST /admin/users/:slug  — update role
     */
    public function updateUser(string $slug): void
    {
        global $basePath;
        $admin = requireAdmin();
        validateCsrf();

        $userRepo = new UserRepository();
        $profile  = $userRepo->findBySlug($slug);

        if ($profile === null) {
            $this->abort(404);
        }

        $action = trim($_POST['action'] ?? 'change_role');

        // --- Change email -------------------------------------------------------
        if ($action === 'change_email') {

            // Admin accounts cannot be edited from this panel
            if ($profile['role'] === 'admin') {
                flash('Admin email addresses cannot be changed from here.', 'error');
                $this->redirect($basePath . '/admin/users/' . $slug);
            }

            // Admin cannot use this form to change their own email — use /account/profile
            if ((int)$profile['id'] === (int)$admin['id']) {
                flash('Use Account Settings to change your own email address.', 'error');
                $this->redirect($basePath . '/admin/users/' . $slug);
            }

            $newEmail = trim($_POST['new_email'] ?? '');

            try {
                (new \App\Services\AuthService())->changeUserEmail(
                    (int)$profile['id'],
                    $newEmail,
                    (string)($profile['email'] ?? '')
                );
                flash('Email updated. A verification link has been sent to ' . $newEmail . '.');
            } catch (\RuntimeException $e) {
                flash($e->getMessage(), 'error');
            } catch (\Throwable $e) {
                error_log('AdminController::updateUser changeEmail failed: ' . $e->getMessage());
                flash('Failed to update email. Please try again.', 'error');
            }

            $this->redirect($basePath . '/admin/users/' . $slug);
        }

        // --- Change role (existing logic) ---------------------------------------
        if ((int)$profile['id'] === (int)$admin['id']) {
            flash('You cannot change your own role.', 'error');
            $this->redirect($basePath . '/admin/users/' . $slug);
        }

        $newRole = trim($_POST['role'] ?? '');
        $allowed = ['bidder', 'donor'];

        if (!in_array($newRole, $allowed, true)) {
            flash('Invalid role.', 'error');
        } else {
            try {
                $userRepo->updateRole((int)$profile['id'], $newRole);
                flash('User role updated to ' . ucfirst($newRole) . '.');
            } catch (\Throwable $e) {
                error_log('AdminController::updateUser updateRole failed: ' . $e->getMessage());
                flash('Failed to update role. Please try again.', 'error');
            }
        }

        $this->redirect($basePath . '/admin/users/' . $slug);
    }

    // -------------------------------------------------------------------------
    // Payments
    // -------------------------------------------------------------------------

    /**
     * GET /admin/payments
     */
    public function payments(): void
    {
        global $basePath;
        $user = requireAdmin();

        $perPage      = 30;
        $page         = max(1, (int)($_GET['page'] ?? 1));
        $statusFilter = trim($_GET['status'] ?? '');
        $eventIdFilter = (int)($_GET['event_id'] ?? 0);
        $offset       = ($page - 1) * $perPage;

        $filters = [];
        if ($statusFilter !== '') $filters['status']   = $statusFilter;
        if ($eventIdFilter > 0)   $filters['event_id'] = $eventIdFilter;

        $paymentRepo  = new PaymentRepository();
        $payments     = $paymentRepo->all($filters, $perPage, $offset);
        $total        = $paymentRepo->countAll($filters);
        $totalPages   = (int)ceil($total / max(1, $perPage));
        $totalRevenue = $paymentRepo->totalRevenue();
        $paymentStats = $paymentRepo->statsByStatus(['pending', 'completed', 'failed', 'refunded']);

        $events = (new EventRepository())->all();

        $content = $this->renderView('admin/payments', [
            'payments'     => $payments,
            'total'        => $total,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'totalRevenue' => $totalRevenue,
            'paymentStats' => $paymentStats,
            'statusFilter' => $statusFilter,
            'events'       => $events,
        ]);

        $this->view('layouts/admin', [
            'pageTitle' => 'Payments — WFCS Auction Admin',
            'user'      => $user,
            'activeNav' => 'payments',
            'content'   => $content,
        ]);
    }

    // -------------------------------------------------------------------------
    // Gift Aid
    // -------------------------------------------------------------------------

    /**
     * GET /admin/gift-aid
     */
    public function giftAid(): void
    {
        global $basePath;
        $user = requireAdmin();

        $perPage  = 30;
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $offset   = ($page - 1) * $perPage;

        $giftAidRepo  = new GiftAidRepository();
        $claims       = $giftAidRepo->claimed($perPage, $offset);
        $total        = $giftAidRepo->countClaimed();
        $giftAidStats = $giftAidRepo->stats();
        $totalPages   = (int)ceil($total / max(1, $perPage));

        $content = $this->renderView('admin/gift-aid', [
            'claims'       => $claims,
            'total'        => $total,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'giftAidStats' => $giftAidStats,
        ]);

        $this->view('layouts/admin', [
            'pageTitle' => 'Gift Aid — WFCS Auction Admin',
            'user'      => $user,
            'activeNav' => 'gift-aid',
            'content'   => $content,
        ]);
    }

    /**
     * POST /admin/gift-aid/export — CSV download
     */
    public function exportGiftAid(): void
    {
        requireAdmin();
        validateCsrf();

        $giftAidRepo = new GiftAidRepository();
        $claims      = $giftAidRepo->claimed(10000, 0);

        // Open output stream before sending headers — if this fails, we can still redirect
        $out = fopen('php://output', 'w');
        if ($out === false) {
            error_log('AdminController::exportGiftAid failed to open php://output');
            flash('Export failed. Please try again.', 'error');
            $this->redirect($basePath . '/admin/gift-aid');
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="gift-aid-' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // UTF-8 BOM for Excel compatibility
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [
            'Title',
            'First Name',
            'Last Name',
            'House Number/Name',
            'Postcode',
            'Donation Date',
            'Donation Amount (£)',
            'Gift Aid Amount (£)',
            'Donation Type',
        ]);

        foreach ($claims as $claim) {
            $name  = (string)($claim['gift_aid_name'] ?? $claim['user_name'] ?? '');
            $parts = explode(' ', $name, 2);
            fputcsv($out, [
                '',                // Title (Mr/Mrs) — not collected
                trim($parts[0] ?? ''),
                trim($parts[1] ?? ''),
                '',                // Address — not collected
                '',                // Postcode — not collected
                isset($claim['payment_date']) ? date('d/m/Y', strtotime((string)$claim['payment_date'])) : '',
                number_format((float)($claim['payment_amount'] ?? 0), 2, '.', ''),
                number_format((float)($claim['gift_aid_amount'] ?? 0), 2, '.', ''),
                'Auction Bid',
            ]);
        }

        fclose($out);
        exit;
    }

    // -------------------------------------------------------------------------
    // Live Events
    // -------------------------------------------------------------------------

    /**
     * GET /admin/live-events
     */
    public function liveEvents(): void
    {
        global $basePath;
        $user = requireAdmin();

        $settingsRepo = new SettingsRepository();
        $liveEventId  = (int)($settingsRepo->get('live_event_id') ?? 0);
        $liveEvent    = null;

        if ($liveEventId > 0) {
            $liveEvent = (new EventRepository())->findById($liveEventId);
        }

        $allEvents = (new EventRepository())->all();

        $content = $this->renderView('admin/live-events', [
            'liveEvent' => $liveEvent,
            'allEvents' => $allEvents,
        ]);

        $this->view('layouts/admin', [
            'pageTitle' => 'Live Events — WFCS Auction Admin',
            'user'      => $user,
            'activeNav' => 'live-events',
            'content'   => $content,
        ]);
    }

    /**
     * POST /admin/live-events/start
     */
    public function startLiveEvent(): void
    {
        global $basePath;
        requireAdmin();
        validateCsrf();

        $eventId = (int)($_POST['event_id'] ?? 0);

        if ($eventId === 0) {
            flash('Please select an event.', 'error');
            $this->redirect($basePath . '/admin/live-events');
            return;
        }

        $event = (new EventRepository())->findById($eventId);

        if ($event === null) {
            flash('Event not found.', 'error');
            $this->redirect($basePath . '/admin/live-events');
            return;
        }

        $settingsRepo = new SettingsRepository();
        $settingsRepo->set('live_event_id', (string)$eventId);
        flash('Live event started: ' . $event['title']);
        $this->redirect($basePath . '/admin/live-events');
    }

    /**
     * POST /admin/live-events/stop
     */
    public function stopLiveEvent(): void
    {
        global $basePath;
        requireAdmin();
        validateCsrf();

        $settingsRepo = new SettingsRepository();
        $settingsRepo->delete('live_event_id');
        flash('Live event stopped.');
        $this->redirect($basePath . '/admin/live-events');
    }

    // -------------------------------------------------------------------------
    // Settings
    // -------------------------------------------------------------------------

    /**
     * GET /admin/settings
     */
    public function settings(): void
    {
        global $basePath;
        $user = requireAdmin();

        $settingsRepo = new SettingsRepository();
        $settings     = $settingsRepo->all();

        // Decrypt sensitive values so the view can show masked placeholders with real last-4 chars
        $sensitiveKeys = ['stripe_publishable_key', 'stripe_secret_key', 'stripe_webhook_url_token', 'smtp_password'];
        foreach ($sensitiveKeys as $k) {
            if (!empty($settings[$k])) {
                $settings[$k] = decryptSetting($settings[$k]);
            }
        }

        $content = $this->renderView('admin/settings', [
            'settings' => $settings,
        ]);

        $this->view('layouts/admin', [
            'pageTitle' => 'Settings — WFCS Auction Admin',
            'user'      => $user,
            'activeNav' => 'settings',
            'content'   => $content,
        ]);
    }

    /**
     * POST /admin/settings
     */
    public function saveSettings(): void
    {
        global $basePath;
        requireAdmin();
        validateCsrf();

        $settingsRepo = new SettingsRepository();

        $allowedKeys = [
            'stripe_publishable_key',
            'stripe_secret_key',
            'stripe_webhook_url_token',
            'email_from',
            'email_from_name',
            'smtp_host',
            'smtp_port',
            'smtp_username',
            'smtp_password',
            'notify_outbid',
            'notify_winner',
            'payment_reminder_days',
        ];

        $sensitiveKeys = ['stripe_publishable_key', 'stripe_secret_key', 'stripe_webhook_url_token', 'smtp_password'];

        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $_POST)) {
                $value = trim((string)($_POST[$key] ?? ''));
                // Keep existing value if field submitted blank (so *** placeholders don't wipe real values)
                if (in_array($key, $sensitiveKeys, true) && $value === '') {
                    continue;
                }
                // Encrypt sensitive values before storing
                if (in_array($key, $sensitiveKeys, true) && $value !== '') {
                    $value = encryptSetting($value);
                }
                $settingsRepo->set($key, $value);
            }
        }

        flash('Settings saved successfully.');
        $this->redirect($basePath . '/admin/settings');
    }
}
