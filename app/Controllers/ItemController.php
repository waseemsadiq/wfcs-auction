<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\ItemService;
use App\Services\UploadService;
use App\Services\NotificationService;
use App\Repositories\ItemRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\SettingsRepository;
use App\Services\BidService;

class ItemController extends Controller
{
    private ItemRepository $itemRepo;
    private ItemService $itemService;
    private CategoryRepository $categories;
    private EventRepository $eventRepo;
    private UserRepository $users;
    private PasswordResetRepository $passwordResets;
    private SettingsRepository $settings;

    public function __construct()
    {
        // No auth checks in constructor — Galvani rule #9
        $this->itemRepo       = new ItemRepository();
        $this->itemService    = new ItemService();
        $this->categories     = new CategoryRepository();
        $this->eventRepo      = new EventRepository();
        $this->users          = new UserRepository();
        $this->passwordResets = new PasswordResetRepository();
        $this->settings       = new SettingsRepository();
    }

    // -------------------------------------------------------------------------
    // GET /items/:slug
    // -------------------------------------------------------------------------

    public function show(string $slug): void
    {
        $user = getAuthUser();
        $item = $this->itemRepo->findBySlug($slug);

        if ($item === null) {
            $this->abort(404);
        }

        // Only show active/ended/sold items publicly
        if (!in_array($item['status'], ['active', 'ended', 'sold'], true)) {
            $this->abort(404);
        }

        // Load real bid history for this item
        $bids = (new BidService())->historyForItem((int)$item['id']);

        $content = $this->renderView('items/show', [
            'user' => $user,
            'item' => $item,
            'bids' => $bids,
        ]);

        $this->view('layouts/public', [
            'pageTitle'  => e($item['title']) . ' — WFCS Auction',
            'activeNav'  => 'auctions',
            'user'       => $user,
            'content'    => $content,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /submit-item
    // -------------------------------------------------------------------------

    public function showSubmit(): void
    {
        $user = getAuthUser();

        $content = $this->renderView('items/submit', [
            'user'   => $user,
            'errors' => [],
            'old'    => [],
        ]);

        $this->view('layouts/public', [
            'pageTitle' => 'Donate an Item',
            'activeNav' => 'donate',
            'user'      => $user,
            'content'   => $content,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /donate
    // -------------------------------------------------------------------------

    public function submit(): void
    {
        global $basePath;

        validateCsrf();

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $email     = trim(strtolower($_POST['email'] ?? ''));
        $phone     = trim($_POST['phone'] ?? '');

        $data = [
            'title'        => trim($_POST['title'] ?? ''),
            'description'  => trim($_POST['description'] ?? ''),
            'market_value' => $_POST['market_value'] ?? '',
        ];

        // Validate required fields
        $errors = [];
        if ($firstName === '') $errors['first_name'] = 'First name is required.';
        if ($lastName === '')  $errors['last_name']  = 'Last name is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email address is required.';
        }
        if ($data['title'] === '') $errors['title'] = 'Item title is required.';

        if (!empty($errors)) {
            $user    = getAuthUser();
            $content = $this->renderView('items/submit', [
                'user'   => $user,
                'errors' => $errors,
                'old'    => array_merge($data, [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'email'      => $email,
                    'phone'      => $phone,
                ]),
            ]);
            $this->view('layouts/public', ['pageTitle' => 'Donate an Item', 'activeNav' => 'donate', 'user' => $user, 'content' => $content]);
            return;
        }

        // Handle optional image upload
        $uploadService = new UploadService();
        if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            try {
                $data['image'] = $uploadService->uploadItemImage($_FILES['photo']);
            } catch (\RuntimeException $e) {
                $user    = getAuthUser();
                $content = $this->renderView('items/submit', [
                    'user'   => $user,
                    'errors' => ['photo' => $e->getMessage()],
                    'old'    => array_merge($data, compact('firstName', 'lastName', 'email', 'phone')),
                ]);
                $this->view('layouts/public', ['pageTitle' => 'Donate an Item', 'activeNav' => 'donate', 'user' => $user, 'content' => $content]);
                return;
            }
        }

        // Find or create donor user
        $fullName    = $firstName . ' ' . $lastName;
        $existingUser = $this->users->findByEmail($email);
        $isNewDonor   = false;

        if ($existingUser !== null) {
            $donorId = (int)$existingUser['id'];
            if ($phone !== '') {
                $this->users->updatePhone($donorId, $phone);
            }
        } else {
            $isNewDonor = true;
            $slug       = $this->users->uniqueSlug($fullName);
            $donorId    = $this->users->create([
                'slug'             => $slug,
                'name'             => $fullName,
                'email'            => $email,
                'password_hash'    => '!',   // impossible hash — account locked until password set
                'role'             => 'donor',
                'phone'            => $phone !== '' ? $phone : null,
                'email_verified_at' => date('Y-m-d H:i:s'), // donors are pre-verified
            ]);
        }

        try {
            $item    = $this->itemService->submit($data, $donorId);
            $baseUrl = (string)($this->settings->get('site_url') ?? ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $basePath));

            // Generate a 7-day password set link for the donor
            $rawToken  = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);
            $this->passwordResets->create($donorId, $tokenHash, 86400 * 7);
            $setPasswordUrl = rtrim($baseUrl, '/') . '/reset-password?token=' . urlencode($rawToken);

            // Admin notification (with thumbnail attachment if image uploaded)
            $thumbPath = '';
            if (!empty($data['image'])) {
                $thumbPath = dirname(__DIR__, 2) . '/uploads/thumbs/' . basename($data['image']);
            }
            $adminEmail = (string)($this->settings->get('admin_email') ?? 'info@wellfoundation.org.uk');
            $donorRow   = ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email, 'phone' => $phone];

            try {
                (new NotificationService())->sendDonationNotification($item, $donorRow, $adminEmail, $baseUrl, $thumbPath);
            } catch (\Throwable $e) {
                error_log('ItemController: admin notification failed: ' . $e->getMessage());
            }

            // Donor welcome + set-password email (new donors only — existing users already have accounts)
            if ($isNewDonor) {
                try {
                    (new NotificationService())->sendDonorWelcome(['email' => $email], $firstName, $setPasswordUrl);
                } catch (\Throwable $e) {
                    error_log('ItemController: donor welcome email failed: ' . $e->getMessage());
                }
            }

            flash('Thank you! Your donation has been received. Check your email — we\'ve sent you a link to set up your account.');
            $this->redirect($basePath . '/donate?submitted=1');

        } catch (\RuntimeException $e) {
            $user    = getAuthUser();
            $content = $this->renderView('items/submit', [
                'user'   => $user,
                'errors' => ['general' => $e->getMessage()],
                'old'    => array_merge($data, compact('firstName', 'lastName', 'email', 'phone')),
            ]);
            $this->view('layouts/public', ['pageTitle' => 'Donate an Item', 'activeNav' => 'donate', 'user' => $user, 'content' => $content]);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/current-bid/:slug
    // -------------------------------------------------------------------------

    public function currentBid(string $slug): void
    {
        $item = $this->itemRepo->findBySlug($slug);

        if ($item === null) {
            $this->json(['error' => 'Not found'], 404);
        }

        $currentBid  = (float)($item['current_bid'] ?? $item['starting_bid'] ?? 0);
        $bidCount    = (int)($item['bid_count'] ?? 0);
        $status      = (string)($item['status'] ?? 'active');

        $this->json([
            'current_bid' => formatCurrency($currentBid),
            'bid_count'   => $bidCount,
            'status'      => $status,
        ]);
    }
}
