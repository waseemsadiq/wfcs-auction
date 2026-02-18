<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\EventService;
use App\Services\ItemService;
use App\Repositories\CategoryRepository;

class HomeController extends Controller
{
    private EventService $eventService;
    private ItemService $itemService;
    private CategoryRepository $categories;

    public function __construct()
    {
        // No auth checks in constructor — Galvani rule #9
        $this->eventService = new EventService();
        $this->itemService  = new ItemService();
        $this->categories   = new CategoryRepository();
    }

    // -------------------------------------------------------------------------
    // GET /
    // -------------------------------------------------------------------------

    public function index(): void
    {
        $user = getAuthUser();

        $eventData  = $this->eventService->publicList(1, 6);
        $browseData = $this->itemService->browse(['status' => 'active'], 1, 6);
        $categories = $this->categories->all();

        // Find the first active event slug so the search form can target it
        $activeEventSlug = null;
        foreach ($eventData['events'] as $evt) {
            if ($evt['status'] === 'active') {
                $activeEventSlug = $evt['slug'];
                break;
            }
        }

        $content = $this->renderView('home/index', [
            'user'            => $user,
            'events'          => $eventData['events'],
            'items'           => $browseData['items'],
            'categories'      => $categories,
            'activeEventSlug' => $activeEventSlug,
        ]);

        $this->view('layouts/public', [
            'pageTitle'  => 'WFCS Auction — Bid for Change',
            'activeNav'  => 'auctions',
            'user'       => $user,
            'content'    => $content,
        ]);
    }
}
