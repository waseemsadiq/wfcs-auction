<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\ItemService;
use App\Services\UploadService;
use App\Repositories\ItemRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\EventRepository;
use App\Services\BidService;

class ItemController extends Controller
{
    private ItemRepository $itemRepo;
    private ItemService $itemService;
    private CategoryRepository $categories;
    private EventRepository $eventRepo;

    public function __construct()
    {
        // No auth checks in constructor — Galvani rule #9
        $this->itemRepo    = new ItemRepository();
        $this->itemService = new ItemService();
        $this->categories  = new CategoryRepository();
        $this->eventRepo   = new EventRepository();
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
        global $basePath;
        $user = getAuthUser();

        $categories = $this->categories->all();
        $events     = $this->eventRepo->allPublic(50, 0);

        $content = $this->renderView('items/submit', [
            'user'       => $user,
            'categories' => $categories,
            'events'     => $events,
            'errors'     => [],
            'old'        => [],
        ]);

        $this->view('layouts/public', [
            'pageTitle'  => 'Donate an Item',
            'activeNav'  => 'donate',
            'user'       => $user,
            'content'    => $content,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /submit-item
    // -------------------------------------------------------------------------

    public function submit(): void
    {
        global $basePath;
        $user = getAuthUser();

        validateCsrf();

        $categories = $this->categories->all();
        $events     = $this->eventRepo->allPublic(50, 0);

        $data = [
            'title'         => trim($_POST['title'] ?? ''),
            'description'   => trim($_POST['description'] ?? ''),
            'category_id'   => (int)($_POST['category_id'] ?? 0),
            'event_id'      => (int)($_POST['event_id'] ?? 0),
            'starting_bid'  => $_POST['starting_bid'] ?? '',
            'min_increment' => $_POST['min_increment'] ?? '',
            'buy_now_price' => $_POST['buy_now_price'] ?? '',
        ];

        // Handle optional image upload
        if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploadService  = new UploadService();
                $data['image']  = $uploadService->uploadItemImage($_FILES['photo']);
            } catch (\RuntimeException $e) {
                $errors  = ['photo' => $e->getMessage()];
                $content = $this->renderView('items/submit', [
                    'user'       => $user,
                    'categories' => $categories,
                    'events'     => $events,
                    'errors'     => $errors,
                    'old'        => $data,
                ]);

                $this->view('layouts/public', [
                    'pageTitle'  => 'Donate an Item',
                    'activeNav'  => 'donate',
                    'user'       => $user,
                    'content'    => $content,
                ]);
                return;
            }
        }

        try {
            $this->itemService->submit($data, (int)$user['id']);
            flash('Thank you! Your item has been submitted for review. Our team will be in touch shortly.');
            $this->redirect($basePath . '/my-bids');
        } catch (\RuntimeException $e) {
            $errors  = ['general' => $e->getMessage()];
            $content = $this->renderView('items/submit', [
                'user'       => $user,
                'categories' => $categories,
                'events'     => $events,
                'errors'     => $errors,
                'old'        => $data,
            ]);

            $this->view('layouts/public', [
                'pageTitle'  => 'Donate an Item',
                'activeNav'  => 'donate',
                'user'       => $user,
                'content'    => $content,
            ]);
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
