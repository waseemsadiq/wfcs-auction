<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\BidRepository;
use App\Repositories\ItemRepository;
use App\Repositories\UserRepository;
use App\Repositories\SettingsRepository;
use App\Services\GiftAidService;
use App\Services\NotificationService;

class BidService
{
    private BidRepository $bids;
    private ItemRepository $items;
    private ?GiftAidService $giftAid;

    public function __construct(
        ?BidRepository  $bids     = null,
        ?ItemRepository $items    = null,
        ?GiftAidService $giftAid  = null
    ) {
        $this->bids    = $bids    ?? new BidRepository();
        $this->items   = $items   ?? new ItemRepository();
        $this->giftAid = $giftAid; // null = lazy-create on first use
    }

    /**
     * Place a bid.
     *
     * Accepts item and user as arrays (already fetched by controller).
     * An optional $context array can carry extra data (future extensibility).
     *
     * Rules (ALL must pass):
     *   1. Item must be status='active'
     *   2. User email must be verified (email_verified_at not null)
     *   3. User must NOT be the donor (user[id] != item[donor_id])
     *   4. If not buy_now: amount >= starting_bid (when no previous bids)
     *      or >= (current_bid + min_increment)
     *   5. If buy_now: item must have buy_now_price set; amount must equal buy_now_price
     *
     * On success:
     *   - Create bid record
     *   - Update items.current_bid and items.bid_count
     *   - If buy_now: update item status to 'ended', set winner_id via updateBidStats
     *
     * Returns array with at least 'bid_id' key (and 'buy_now' = true for buy-now bids).
     *
     * @throws \RuntimeException with user-friendly message on any rule violation
     */
    public function place(
        array $item,
        array $user,
        float $amount,
        bool  $isBuyNow = false,
        array $context  = []
    ): array {
        // Rule 0: Bidding must not be paused
        if ((string)(new SettingsRepository())->get('bidding_paused') === '1') {
            throw new \RuntimeException('Bidding is currently paused. Please try again shortly.');
        }

        // Rule 1: Item must be active
        if (($item['status'] ?? '') !== 'active') {
            throw new \RuntimeException('This item is no longer accepting bids.');
        }

        // Rule 2: Email must be verified
        if (empty($user['email_verified_at'])) {
            throw new \RuntimeException('You must verify your email address before placing a bid.');
        }

        // Rule 3: Donor cannot bid on their own item
        if ((int)($user['id'] ?? 0) === (int)($item['donor_id'] ?? -1)) {
            throw new \RuntimeException('You cannot bid on an item you donated.');
        }

        $currentBid   = (float)($item['current_bid']   ?? 0);
        $startingBid  = (float)($item['starting_bid']  ?? 0);
        $minIncrement = (float)($item['min_increment']  ?? 1);

        if ($isBuyNow) {
            // Rule 5: Buy-now price must be set and amount must match
            $buyNowPrice = isset($item['buy_now_price']) && $item['buy_now_price'] !== null
                ? (float)$item['buy_now_price']
                : null;

            if ($buyNowPrice === null) {
                throw new \RuntimeException('This item does not have a Buy Now price.');
            }

            if (abs($amount - $buyNowPrice) > 0.001) {
                throw new \RuntimeException(
                    'Buy Now amount must equal the Buy Now price of £' . number_format($buyNowPrice, 2) . '.'
                );
            }
        } else {
            // Rule 4a: No previous bids — must meet starting_bid
            if ($currentBid <= 0) {
                if ($amount < $startingBid) {
                    throw new \RuntimeException(
                        'Bid must be at least the starting price of £' . number_format($startingBid, 2) . '.'
                    );
                }
            } else {
                // Rule 4b: Must exceed current_bid and meet minimum increment
                $minimumBid = $currentBid + $minIncrement;
                if ($amount < $minimumBid) {
                    throw new \RuntimeException(
                        'Bid must be at least £' . number_format($minimumBid, 2)
                        . ' (current bid + minimum increment of £' . number_format($minIncrement, 2) . ').'
                    );
                }
            }
        }

        // All rules passed — capture previous highest bidder BEFORE inserting new bid
        $itemId = (int)$item['id'];
        $userId = (int)$user['id'];

        // Notify previous highest bidder that they have been outbid (Phase 12)
        // Must happen BEFORE insert so we still see the old leader
        if (!$isBuyNow) {
            try {
                $previousBid = $this->bids->highestBid($itemId);
                if ($previousBid !== null && (int)$previousBid['user_id'] !== $userId) {
                    $previousBidder = (new UserRepository())->findById((int)$previousBid['user_id']);
                    if ($previousBidder !== null) {
                        $baseUrl = rtrim(config('app.url') ?: 'http://localhost:8080', '/');
                        $itemUrl = $baseUrl . '/items/' . ($item['slug'] ?? '');
                        (new NotificationService())->sendOutbidNotification(
                            $previousBidder,
                            $item,
                            $amount,
                            $itemUrl
                        );
                    }
                }
            } catch (\Throwable $e) {
                error_log('Email failed (sendOutbidNotification): ' . $e->getMessage());
            }
        }

        $bidId = $this->bids->insert($itemId, $userId, $amount, $isBuyNow);

        // Update item stats
        if ($isBuyNow) {
            // Buy-now: set winner and update bid stats in one call
            $this->items->updateBidStats($itemId, $amount, $userId);
            $this->items->setStatus($itemId, 'ended');
        } else {
            $this->items->updateBidStats($itemId, $amount);
        }

        // Calculate potential Gift Aid for this bid
        $giftAidService = $this->giftAid ?? new GiftAidService();
        $marketValue    = (float)($item['market_value'] ?? 0);
        $giftAidAmount  = $giftAidService->calculate($amount, $marketValue);

        $result = [
            'bid_id'           => $bidId,
            'item_id'          => $itemId,
            'user_id'          => $userId,
            'amount'           => $amount,
            'buy_now'          => $isBuyNow,
            'gift_aid_eligible' => $giftAidAmount > 0,
            'gift_aid_amount'   => $giftAidAmount,
        ];

        return $result;
    }

    /**
     * Get bid history for an item (with masked bidder names).
     * Names are already masked inside BidRepository::byItem().
     */
    public function historyForItem(int $itemId): array
    {
        return $this->bids->byItem($itemId);
    }

    /**
     * Get bid list for the My Bids page.
     *
     * Each bid is enriched with:
     *   - is_user_highest (bool) — whether this is the user's highest bid on the item
     *   - label             — display status: Winning | Outbid | Won | Lost | Ended
     *
     * Returns array with keys: 'bids', 'total', 'page', 'per_page', 'pages'
     */
    public function myBids(int $userId, int $page = 1, int $perPage = 20): array
    {
        $page    = max(1, $page);
        $offset  = ($page - 1) * $perPage;
        $total   = $this->bids->countByUser($userId);
        $rows    = $this->bids->byUser($userId, $perPage, $offset);

        // For each bid, determine if the user is the highest bidder on that item
        // by grouping by item and tracking the highest amount per item for this user.
        // We compare the user's bid amount against current_bid on the item.
        $enriched = [];
        foreach ($rows as $bid) {
            $bid['is_user_highest'] = $this->isUserHighestBid($bid, $userId);
            $bid['label']           = $this->resolveBidLabel($bid, $userId);
            $enriched[] = $bid;
        }

        return [
            'bids'     => $enriched,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => max(1, (int)ceil($total / $perPage)),
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Check whether a given bid row represents the user's current highest bid on that item.
     * "Highest" means the bid amount equals the item's current_bid (no one has outbid them).
     */
    private function isUserHighestBid(array $bid, int $userId): bool
    {
        // If the item has a winner set, only that user is "winning"
        if (!empty($bid['winner_id'])) {
            return (int)$bid['winner_id'] === $userId;
        }

        // For active items: user is winning if their bid equals the current_bid
        $bidAmount  = (float)($bid['amount']      ?? 0);
        $currentBid = (float)($bid['current_bid'] ?? 0);

        return abs($bidAmount - $currentBid) < 0.001;
    }

    /**
     * Resolve a display label for a bid row.
     */
    private function resolveBidLabel(array $bid, int $userId): string
    {
        $itemStatus = (string)($bid['item_status'] ?? 'active');
        $winnerId   = !empty($bid['winner_id']) ? (int)$bid['winner_id'] : null;
        $isHighest  = (bool)($bid['is_user_highest'] ?? false);

        return match(true) {
            $itemStatus === 'sold'  && $winnerId === $userId => 'Won',
            $itemStatus === 'ended' && $winnerId === $userId => 'Won',
            $itemStatus === 'sold'                           => 'Lost',
            $itemStatus === 'ended'                          => 'Ended',
            $isHighest                                       => 'Winning',
            default                                          => 'Outbid',
        };
    }
}
