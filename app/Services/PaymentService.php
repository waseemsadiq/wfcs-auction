<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\PaymentRepository;
use App\Repositories\ItemRepository;
use App\Repositories\UserRepository;

class PaymentService
{
    private PaymentRepository $payments;
    private ItemRepository    $items;
    private UserRepository    $users;

    public function __construct(
        ?PaymentRepository $payments = null,
        ?ItemRepository    $items    = null,
        ?UserRepository    $users    = null
    ) {
        $this->payments = $payments ?? new PaymentRepository();
        $this->items    = $items    ?? new ItemRepository();
        $this->users    = $users    ?? new UserRepository();
    }

    /**
     * Send a payment request to the winner.
     * Creates or fetches the payment record and initiates the Stripe checkout.
     *
     * @param array $payment  The payment record from PaymentRepository::find()
     * @return void
     */
    public function requestPayment(array $payment): void
    {
        // Update payment status to 'request_sent'
        $this->payments->updateStatus($payment['id'], 'request_sent');

        // Further Stripe integration handled in PaymentController
        // This method is the hook for auto-triggering payment requests
    }

    /**
     * Mark a payment as complete (paid).
     */
    public function markPaid(int $paymentId): void
    {
        $payment = $this->payments->find($paymentId);
        if ($payment === null) {
            return;
        }

        $this->payments->updateStatus($paymentId, 'paid');

        // Mark the item as 'sold'
        if (!empty($payment['item_id'])) {
            $this->items->setStatus((int)$payment['item_id'], 'sold');
        }
    }

    /**
     * Get a payment by its Stripe session ID.
     */
    public function findByStripeSession(string $sessionId): ?array
    {
        return $this->payments->findByStripeSession($sessionId);
    }

    /**
     * Get a payment by its Stripe payment intent ID.
     */
    public function findByPaymentIntent(string $intentId): ?array
    {
        return $this->payments->findByPaymentIntent($intentId);
    }

    /**
     * Update Stripe IDs on a payment.
     */
    public function updateStripeIds(int $paymentId, string $sessionId, ?string $intentId = null): void
    {
        $this->payments->updateStripeIds($paymentId, $sessionId, $intentId);
    }

    /**
     * Get paginated payments for admin.
     */
    public function adminList(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $total  = $this->payments->countAll($filters);
        $rows   = $this->payments->all($filters, $perPage, $offset);

        return [
            'payments'   => $rows,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => (int)ceil($total / max(1, $perPage)),
        ];
    }

    /**
     * Get all payments for a user.
     */
    public function byUser(int $userId): array
    {
        return $this->payments->byUser($userId);
    }
}
