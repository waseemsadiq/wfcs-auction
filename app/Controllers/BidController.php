<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\BidService;
use App\Services\RateLimitService;
use App\Repositories\ItemRepository;

class BidController extends Controller
{
    // No auth checks in constructor — Galvani rule #9
    // Router instantiates ALL controllers at registration.

    // -------------------------------------------------------------------------
    // GET /my-bids
    // -------------------------------------------------------------------------

    public function myBids(): void
    {
        global $basePath;

        $user = requireAuth();

        // Must have verified email
        if (empty($user['verified'])) {
            $this->redirect($basePath . '/verify-email');
        }

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $service = new BidService();
        $result  = $service->myBids((int)$user['id'], $page);

        $content = $this->renderView('bids/my-bids', [
            'user'    => $user,
            'bids'    => $result['bids'],
            'total'   => $result['total'],
            'page'    => $result['page'],
            'perPage' => $result['per_page'],
            'pages'   => $result['pages'],
        ]);

        $this->view('layouts/public', [
            'pageTitle' => 'My Bids — WFCS Auction',
            'activeNav' => 'my-bids',
            'user'      => $user,
            'content'   => $content,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /bids
    // -------------------------------------------------------------------------

    public function place(): void
    {
        global $basePath;

        $user = requireAuth();

        // Must have verified email
        if (empty($user['verified'])) {
            flash('Please verify your email address before placing a bid.', 'error');
            $this->redirect($basePath . '/verify-email');
        }

        validateCsrf();

        // Rate limiting
        try {
            (new RateLimitService())->check($user['slug'], 'bid');
        } catch (\RuntimeException $e) {
            flash($e->getMessage(), 'error');
            $this->redirect($basePath . '/auctions');
        }

        $itemSlug = trim($_POST['item_slug'] ?? '');
        $amount   = (float)($_POST['amount'] ?? 0);
        $isBuyNow = !empty($_POST['buy_now']);

        if (!$itemSlug) {
            flash('Invalid item.', 'error');
            $this->redirect($basePath . '/auctions');
        }

        $itemRepo = new ItemRepository();
        $item     = $itemRepo->findBySlug($itemSlug);

        if ($item === null) {
            $this->abort(404);
        }

        try {
            (new BidService())->place($item, $user, $amount, $isBuyNow);
            flash('Bid placed successfully!', 'success');
        } catch (\RuntimeException $e) {
            flash($e->getMessage(), 'error');
        }

        $this->redirect($basePath . '/items/' . $itemSlug);
    }
}
