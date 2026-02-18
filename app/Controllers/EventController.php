<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\EventService;
use App\Repositories\EventRepository;
use App\Repositories\ItemRepository;
use App\Repositories\CategoryRepository;

class EventController extends Controller
{
    private EventService $eventService;
    private EventRepository $eventRepo;
    private ItemRepository $itemRepo;
    private CategoryRepository $categories;

    public function __construct()
    {
        // No auth checks in constructor — Galvani rule #9
        $this->eventService = new EventService();
        $this->eventRepo    = new EventRepository();
        $this->itemRepo     = new ItemRepository();
        $this->categories   = new CategoryRepository();
    }

    // -------------------------------------------------------------------------
    // GET /auctions
    // -------------------------------------------------------------------------

    public function index(): void
    {
        $user  = getAuthUser();
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $data  = $this->eventService->publicList($page, 12);

        $content = $this->renderView('events/index', [
            'user'       => $user,
            'events'     => $data['events'],
            'total'      => $data['total'],
            'page'       => $data['page'],
            'totalPages' => $data['totalPages'],
        ]);

        $this->view('layouts/public', [
            'pageTitle'  => 'Auctions',
            'activeNav'  => 'auctions',
            'user'       => $user,
            'content'    => $content,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /auctions/:slug
    // -------------------------------------------------------------------------

    public function show(string $slug): void
    {
        $user  = getAuthUser();
        $event = $this->eventRepo->findBySlug($slug);

        if ($event === null) {
            $this->abort(404);
        }

        // Only show published/active/ended events to the public; draft = 404
        if (!in_array($event['status'], ['published', 'active', 'ended'], true)) {
            $this->abort(404);
        }

        $filters = [
            'search' => trim($_GET['q'] ?? ''),
        ];

        $items      = $this->itemRepo->byEvent((int)$event['id'], 50, 0, $filters);
        $categories = $this->categories->all();

        $content = $this->renderView('events/show', [
            'user'         => $user,
            'event'        => $event,
            'items'        => $items,
            'categories'   => $categories,
            'activeCategory' => trim($_GET['category'] ?? ''),
            'searchQuery'    => $filters['search'],
        ]);

        $this->view('layouts/public', [
            'pageTitle'  => e($event['title']) . ' — WFCS Auction',
            'activeNav'  => 'auctions',
            'user'       => $user,
            'content'    => $content,
        ]);
    }
}
