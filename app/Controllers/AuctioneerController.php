<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Repositories\SettingsRepository;
use App\Repositories\ItemRepository;
use App\Repositories\EventRepository;
use App\Repositories\BidRepository;
use App\Services\AuctionService;

class AuctioneerController extends Controller
{
    // -------------------------------------------------------------------------
    // GET /auctioneer — Admin only
    // -------------------------------------------------------------------------

    /**
     * GET /auctioneer
     * Renders the auctioneer control panel (admin only).
     */
    public function panel(): void
    {
        global $basePath;
        $user = requireAdmin();

        $settingsRepo = new SettingsRepository();
        $liveEventId  = (int)($settingsRepo->get('live_event_id') ?? 0);

        if ($liveEventId === 0) {
            flash('No live event is active. Start one from Live Events first.', 'error');
            $this->redirect($basePath . '/admin/live-events');
        }

        $eventRepo = new EventRepository();
        $itemRepo  = new ItemRepository();
        $bidRepo   = new BidRepository();

        $liveEvent = $eventRepo->findById($liveEventId);
        if ($liveEvent === null) {
            flash('Live event not found.', 'error');
            $this->redirect($basePath . '/admin/live-events');
        }

        // Items for this event (all statuses for queue display)
        $items = $itemRepo->allForEvent($liveEventId);

        // Current live item
        $liveItemId     = (int)($settingsRepo->get('live_item_id') ?? 0);
        $liveItemStatus = $settingsRepo->get('live_item_status') ?? 'pending';
        $liveItem       = null;
        $recentBids     = [];
        $bidCount       = 0;

        if ($liveItemId > 0) {
            $liveItem = $itemRepo->findById($liveItemId);
            if ($liveItem !== null) {
                $rawBids  = $bidRepo->byItem($liveItemId, 10);
                $bidCount = $bidRepo->countByItem($liveItemId);

                // Format bids to match what the view and JS polling expect
                foreach ($rawBids as $bid) {
                    $recentBids[] = [
                        'bidder'           => (string)($bid['bidder_name'] ?? 'Unknown'),
                        'amount_formatted' => '£' . number_format((float)$bid['amount'], 2),
                        'time'             => $this->timeAgo((string)($bid['created_at'] ?? '')),
                    ];
                }
            }
        }

        $biddingPaused = (string)($settingsRepo->get('bidding_paused') ?? '') === '1';

        $content = $this->renderView('auctioneer/panel', [
            'user'           => $user,
            'liveEvent'      => $liveEvent,
            'items'          => $items,
            'liveItem'       => $liveItem,
            'liveItemStatus' => $liveItemStatus,
            'recentBids'     => $recentBids,
            'bidCount'       => $bidCount,
            'biddingPaused'  => $biddingPaused,
        ]);

        $this->view('layouts/auctioneer', [
            'pageTitle' => 'Auctioneer Panel — WFCS Auction',
            'user'      => $user,
            'content'   => $content,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /auctioneer/set-item — Admin
    // -------------------------------------------------------------------------

    /**
     * POST /auctioneer/set-item
     * Sets the current live_item_id in settings.
     * Accepts: item_id (int)
     */
    public function setItem(): void
    {
        global $basePath;
        requireAdmin();
        validateCsrf();

        $itemId = (int)($_POST['item_id'] ?? 0);

        if ($itemId === 0) {
            $this->json(['ok' => false, 'error' => 'No item_id provided'], 400);
        }

        $itemRepo = new ItemRepository();
        $item     = $itemRepo->findById($itemId);

        if ($item === null) {
            $this->json(['ok' => false, 'error' => 'Item not found'], 404);
        }

        $settingsRepo = new SettingsRepository();
        $settingsRepo->set('live_item_id', (string)$itemId);
        $settingsRepo->set('live_item_status', 'pending');

        $this->json(['ok' => true, 'item_id' => $itemId, 'live_item_status' => 'pending']);
    }

    // -------------------------------------------------------------------------
    // POST /auctioneer/open — Admin
    // -------------------------------------------------------------------------

    /**
     * POST /auctioneer/open
     * Opens bidding on the current live item.
     * Sets live_item_status = 'open'.
     */
    public function openBidding(): void
    {
        global $basePath;
        requireAdmin();
        validateCsrf();

        $settingsRepo = new SettingsRepository();
        $liveItemId   = (int)($settingsRepo->get('live_item_id') ?? 0);

        if ($liveItemId === 0) {
            $this->json(['ok' => false, 'error' => 'No item currently selected'], 400);
        }

        $settingsRepo->set('live_item_status', 'open');

        $this->json(['ok' => true, 'live_item_status' => 'open']);
    }

    // -------------------------------------------------------------------------
    // POST /auctioneer/close — Admin
    // -------------------------------------------------------------------------

    /**
     * POST /auctioneer/close
     * Closes the current live item as 'sold' or 'passed'.
     * Accepts: result ('sold'|'passed')
     * If sold: calls AuctionService::processItemEnd().
     */
    public function closeBidding(): void
    {
        global $basePath;
        requireAdmin();
        validateCsrf();

        $result = trim($_POST['result'] ?? '');
        if (!in_array($result, ['sold', 'passed'], true)) {
            $this->json(['ok' => false, 'error' => 'Invalid result. Use sold or passed.'], 400);
        }

        $settingsRepo = new SettingsRepository();
        $liveItemId   = (int)($settingsRepo->get('live_item_id') ?? 0);

        if ($liveItemId === 0) {
            $this->json(['ok' => false, 'error' => 'No item currently selected'], 400);
        }

        $settingsRepo->set('live_item_status', $result);

        if ($result === 'sold') {
            try {
                (new AuctionService())->processItemEnd($liveItemId);
            } catch (\Throwable $e) {
                error_log('AuctioneerController::closeBidding processItemEnd failed: ' . $e->getMessage());
            }
        } else {
            // Mark item as ended/passed
            $itemRepo = new ItemRepository();
            $item     = $itemRepo->findById($liveItemId);
            if ($item !== null) {
                $itemRepo->updateStatus($liveItemId, 'ended');
            }
        }

        $this->json(['ok' => true, 'live_item_status' => $result]);
    }

    // -------------------------------------------------------------------------
    // POST /auctioneer/pause — Admin
    // -------------------------------------------------------------------------

    /**
     * POST /auctioneer/pause
     * Sets bidding_paused = '1' in settings. Bidders will receive an error.
     */
    public function pauseBidding(): void
    {
        requireAdmin();
        validateCsrf();

        (new SettingsRepository())->set('bidding_paused', '1');
        $this->json(['ok' => true, 'paused' => true]);
    }

    // -------------------------------------------------------------------------
    // POST /auctioneer/resume — Admin
    // -------------------------------------------------------------------------

    /**
     * POST /auctioneer/resume
     * Clears bidding_paused. Bidding resumes immediately.
     */
    public function resumeBidding(): void
    {
        requireAdmin();
        validateCsrf();

        (new SettingsRepository())->set('bidding_paused', '0');
        $this->json(['ok' => true, 'paused' => false]);
    }

    // -------------------------------------------------------------------------
    // GET /projector — Public
    // -------------------------------------------------------------------------

    /**
     * GET /projector
     * Full-screen projector display (public, no auth required).
     */
    public function projector(): void
    {
        global $basePath;

        $settingsRepo = new SettingsRepository();
        $liveEventId  = (int)($settingsRepo->get('live_event_id') ?? 0);

        $liveEvent      = null;
        $liveItem       = null;
        $liveItemStatus = 'pending';
        $items          = [];

        if ($liveEventId > 0) {
            $eventRepo = new EventRepository();
            $liveEvent = $eventRepo->findById($liveEventId);

            $liveItemId     = (int)($settingsRepo->get('live_item_id') ?? 0);
            $liveItemStatus = $settingsRepo->get('live_item_status') ?? 'pending';

            if ($liveItemId > 0) {
                $itemRepo = new ItemRepository();
                $liveItem = $itemRepo->findById($liveItemId);
                $items    = $itemRepo->allForEvent($liveEventId);
            }
        }

        $content = $this->renderView('projector/display', [
            'liveEvent'      => $liveEvent,
            'liveItem'       => $liveItem,
            'liveItemStatus' => $liveItemStatus,
            'items'          => $items,
        ]);

        $this->view('layouts/projector', [
            'pageTitle' => 'Live Auction — WFCS Auction',
            'content'   => $content,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/live-status — Public JSON
    // -------------------------------------------------------------------------

    /**
     * GET /api/live-status
     * Returns current live state for polling (no auth required).
     */
    public function liveStatus(): void
    {
        $settingsRepo = new SettingsRepository();
        $liveEventId  = (int)($settingsRepo->get('live_event_id') ?? 0);

        if ($liveEventId === 0) {
            $this->json(['live' => false]);
        }

        $eventRepo = new EventRepository();
        $event     = $eventRepo->findById($liveEventId);

        if ($event === null) {
            $this->json(['live' => false]);
        }

        $liveItemId     = (int)($settingsRepo->get('live_item_id') ?? 0);
        $liveItemStatus = $settingsRepo->get('live_item_status') ?? 'pending';

        if ($liveItemId === 0) {
            $this->json([
                'live'        => true,
                'event_id'    => $liveEventId,
                'event_title' => (string)$event['title'],
                'item_id'     => null,
                'live_status' => $liveItemStatus,
            ]);
        }

        $itemRepo = new ItemRepository();
        $item     = $itemRepo->findById($liveItemId);

        if ($item === null) {
            $this->json([
                'live'        => true,
                'event_id'    => $liveEventId,
                'event_title' => (string)$event['title'],
                'item_id'     => null,
                'live_status' => $liveItemStatus,
            ]);
        }

        $bidRepo    = new BidRepository();
        $bids       = $bidRepo->byItem($liveItemId, 5);
        $bidCount   = $bidRepo->countByItem($liveItemId);
        $currentBid = (float)($item['current_bid'] ?? 0.0);

        // Format recent bids
        $recentBids = [];
        foreach ($bids as $bid) {
            $recentBids[] = [
                'amount_formatted' => '£' . number_format((float)$bid['amount'], 2),
                'bidder'           => (string)($bid['bidder_name'] ?? 'Unknown'),
                'time'             => $this->timeAgo((string)($bid['created_at'] ?? '')),
            ];
        }

        // Count total items in event
        $allItems   = $itemRepo->allForEvent($liveEventId);
        $lotNumber  = (int)($item['lot_number'] ?? 0);
        $totalItems = count($allItems);

        $biddingPaused = (string)($settingsRepo->get('bidding_paused') ?? '') === '1';

        $this->json([
            'live'                  => true,
            'paused'                => $biddingPaused,
            'event_id'              => $liveEventId,
            'event_title'           => (string)$event['title'],
            'item_id'               => $liveItemId,
            'item_title'            => (string)$item['title'],
            'item_slug'             => (string)$item['slug'],
            'lot_number'            => $lotNumber,
            'total_lots'            => $totalItems,
            'current_bid'           => $currentBid,
            'current_bid_formatted' => '£' . number_format($currentBid, 2),
            'bid_count'             => $bidCount,
            'live_status'           => $liveItemStatus,
            'image'                 => (string)($item['image'] ?? ''),
            'recent_bids'           => $recentBids,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Format a MySQL datetime into a relative "X ago" string.
     */
    private function timeAgo(string $datetime): string
    {
        if ($datetime === '') {
            return '';
        }

        $then = strtotime($datetime);
        if ($then === false) {
            return '';
        }

        $diff = time() - $then;

        if ($diff < 60) {
            return $diff <= 1 ? 'just now' : $diff . ' seconds ago';
        }
        if ($diff < 3600) {
            $m = (int)floor($diff / 60);
            return $m === 1 ? '1 minute ago' : $m . ' minutes ago';
        }
        if ($diff < 86400) {
            $h = (int)floor($diff / 3600);
            return $h === 1 ? '1 hour ago' : $h . ' hours ago';
        }
        return date('j M', $then);
    }
}
