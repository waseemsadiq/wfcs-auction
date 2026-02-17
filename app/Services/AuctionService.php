<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ItemRepository;
use App\Repositories\BidRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\UserRepository;
use App\Services\NotificationService;

class AuctionService
{
    private ItemRepository    $items;
    private BidRepository     $bids;
    private PaymentRepository $payments;
    private SettingsRepository $settings;
    private PaymentService    $paymentSvc;

    public function __construct(
        ?ItemRepository     $items      = null,
        ?BidRepository      $bids       = null,
        ?PaymentRepository  $payments   = null,
        ?SettingsRepository $settings   = null,
        ?PaymentService     $paymentSvc = null
    ) {
        $this->items      = $items      ?? new ItemRepository();
        $this->bids       = $bids       ?? new BidRepository();
        $this->payments   = $payments   ?? new PaymentRepository();
        $this->settings   = $settings   ?? new SettingsRepository();
        $this->paymentSvc = $paymentSvc ?? new PaymentService();
    }

    // -------------------------------------------------------------------------
    // Core end-of-auction processing
    // -------------------------------------------------------------------------

    /**
     * Process all active items whose auction time has expired.
     *
     * For each ended-active item:
     *   - Find the highest bid (winning bid)
     *   - If winner exists: set item status to 'awaiting_payment', create payment record
     *   - If no winner: set item status to 'ended'
     *   - If auto_payment_requests setting is '1' and winner: trigger payment request
     *
     * Called on every non-API request as a lightweight expiry check.
     */
    public function processEndedItems(): void
    {
        $endedItems = $this->items->findEndedActive();

        foreach ($endedItems as $item) {
            $this->processOneItem($item);
        }
    }

    /**
     * Process end of auction for a single item (by item array).
     * Returns: ['winner' => array|null, 'amount' => float|null]
     */
    public function processItemEnd(int $itemId): array
    {
        $item = $this->items->findById($itemId);
        if ($item === null) {
            return ['winner' => null, 'amount' => null];
        }

        return $this->processOneItem($item);
    }

    /**
     * Process end of auction for all items in an event.
     * Called when admin transitions event to 'ended' status.
     */
    public function processEventEnd(int $eventId): void
    {
        // Re-use findEndedActive filtered to this event
        // We fetch all active items for the event directly
        $endedItems = $this->items->findEndedActive();

        foreach ($endedItems as $item) {
            if ((int)$item['event_id'] === $eventId) {
                $this->processOneItem($item);
            }
        }
    }

    /**
     * Get the effective end time for an item.
     * Uses item.auction_end (item-level ends_at) if set; falls back to event.event_ends_at.
     *
     * @param array $item  Row with keys: auction_end, event_ends_at
     * @return string|null DateTime string, or null if neither is set
     */
    public function effectiveEndTime(array $item): ?string
    {
        if (!empty($item['auction_end'])) {
            return $item['auction_end'];
        }

        return $item['event_ends_at'] ?? null;
    }

    /**
     * Check and process any expired items/events.
     * Called on each non-API, non-webhook request (lightweight gate).
     */
    public function processExpired(): void
    {
        $this->processEndedItems();
    }

    /**
     * Trigger a payment record for an item (if a winner exists).
     * Returns true if a payment record was created, false if no winner.
     */
    public function triggerPayment(int $itemId): bool
    {
        $winningBid = $this->bids->findWinningBid($itemId);

        if ($winningBid === null) {
            return false;
        }

        $paymentId = $this->payments->create([
            'user_id'   => (int)$winningBid['bidder_id'],
            'item_id'   => $itemId,
            'bid_id'    => (int)$winningBid['id'],
            'winner_id' => (int)$winningBid['bidder_id'],
            'amount'    => (float)$winningBid['amount'],
            'status'    => 'pending',
            'gift_aid_claimed' => 0,
            'gift_aid_amount'  => null,
        ]);

        $payment = $this->payments->find($paymentId);
        if ($payment !== null) {
            $this->paymentSvc->requestPayment($payment);
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Process the end of a single item.
     * Returns: ['winner' => array|null, 'amount' => float|null]
     */
    private function processOneItem(array $item): array
    {
        $itemId     = (int)$item['id'];
        $winningBid = $this->bids->findWinningBid($itemId);

        if ($winningBid !== null) {
            // Step 1: move item to awaiting_payment (winner determined, payment due)
            $this->items->setStatus($itemId, 'awaiting_payment');

            // Step 2: create a pending payment record
            $paymentId = $this->payments->create([
                'user_id'          => (int)$winningBid['user_id'],
                'item_id'          => $itemId,
                'bid_id'           => (int)$winningBid['id'],
                'winner_id'        => (int)$winningBid['user_id'],
                'amount'           => (float)$winningBid['amount'],
                'status'           => 'pending',
                'gift_aid_claimed' => 0,
                'gift_aid_amount'  => null,
            ]);

            // Auto-trigger payment request if setting is enabled
            $autoRequest = $this->settings->get('auto_payment_requests');
            if ($autoRequest === '1') {
                $payment = $this->payments->find($paymentId);
                if ($payment !== null) {
                    $this->paymentSvc->requestPayment($payment);
                }
            }

            // Notify winner (Phase 12)
            try {
                $winnerId   = (int)$winningBid['user_id'];
                $winnerUser = (new UserRepository())->findById($winnerId);
                if ($winnerUser !== null) {
                    $baseUrl    = rtrim(config('app.url') ?: 'http://localhost:8080', '/');
                    $paymentUrl = $baseUrl . '/payment/' . ($item['slug'] ?? '');
                    (new NotificationService())->sendWinnerNotification(
                        $winnerUser,
                        $item,
                        (float)$winningBid['amount'],
                        $paymentUrl
                    );
                }
            } catch (\Throwable $e) {
                error_log('Email failed (sendWinnerNotification): ' . $e->getMessage());
            }

            return [
                'winner' => $winningBid,
                'amount' => (float)$winningBid['amount'],
            ];
        }

        // No bids — mark item as ended with no winner
        $this->items->setStatus($itemId, 'ended');

        return ['winner' => null, 'amount' => null];
    }
}
