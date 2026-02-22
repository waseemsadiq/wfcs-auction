<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\BidRepository;
use App\Repositories\ItemRepository;
use App\Repositories\PaymentRepository;

class UserService
{
    private UserRepository    $users;
    private BidRepository     $bids;
    private ItemRepository    $items;
    private PaymentRepository $payments;

    public function __construct(
        ?UserRepository    $users    = null,
        ?BidRepository     $bids     = null,
        ?ItemRepository    $items    = null,
        ?PaymentRepository $payments = null
    ) {
        $this->users    = $users    ?? new UserRepository();
        $this->bids     = $bids     ?? new BidRepository();
        $this->items    = $items    ?? new ItemRepository();
        $this->payments = $payments ?? new PaymentRepository();
    }

    /**
     * Permanently delete a user and all their associated data (GDPR erasure).
     *
     * Cascade order is FK-safe: each step removes rows that reference the user
     * before the next step removes the rows those rows depend on.
     *
     * @param array $user          The user row (must include id, role, email)
     * @param int   $actingAdminId The admin performing the deletion (for event transfer)
     * @throws \RuntimeException   If the target user is an admin
     */
    public function deleteUser(array $user, int $actingAdminId): void
    {
        $userId = (int)$user['id'];
        $email  = (string)($user['email'] ?? '');

        if (($user['role'] ?? '') === 'admin') {
            throw new \RuntimeException('Admin accounts cannot be deleted via this action.');
        }

        // 1. Password reset tokens
        $this->users->deletePasswordResets($userId);

        // 2. Rate limits (keyed by email, no FK)
        $this->users->deleteRateLimits($email);

        // 3. Bids placed by this user
        $this->bids->deleteByUser($userId);

        // 4. Donated items in active auctions — anonymise, not delete
        $this->items->anonymiseDonor($userId);

        // 5–8. Donated items not in active auctions — cascade delete
        $itemIds = $this->items->donorItemIdsNotActive($userId);
        if (!empty($itemIds)) {
            $this->payments->deleteByItems($itemIds);
            $this->bids->deleteByItems($itemIds);
            $this->items->deleteByIds($itemIds);
        }

        // 9. User's own payment records (for items they won)
        $this->payments->deleteByUser($userId);

        // 10. Clear winner_id on items they won
        $this->items->clearWinner($userId);

        // 11. Transfer any events they created (edge case: demoted admins)
        $this->users->transferEvents($userId, $actingAdminId);

        // 12. Delete the user row
        $this->users->delete($userId);
    }
}
