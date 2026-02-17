<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\NotificationService;
use App\Services\StripeService;
use App\Services\PaymentService;
use App\Repositories\ItemRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\SettingsRepository;

class PaymentController extends Controller
{
    /**
     * GET /payment/:slug
     *
     * Show the payment checkout page for a winning bidder.
     * If ?session_id= is present, verify with Stripe and mark as paid.
     * If Stripe is not configured, show a "coming soon" message.
     * If already paid, show the success view.
     */
    public function show(string $slug): void
    {
        global $basePath;

        $user = requireAuth();

        $itemRepo    = new ItemRepository();
        $paymentRepo = new PaymentRepository();
        $stripe      = new StripeService();

        // Find item by slug
        $item = $itemRepo->findBySlug($slug);
        if ($item === null) {
            $this->abort(404);
        }

        // Find payment belonging to current user for this item
        $payment = $paymentRepo->findByItemAndUser((int)$item['id'], (int)$user['id']);
        if ($payment === null) {
            $this->abort(404);
        }

        // Build scheme + host for absolute URLs
        $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme . '://' . $host;

        $successUrl = $baseUrl . $basePath . '/payment/' . urlencode($slug);
        $cancelUrl  = $baseUrl . $basePath . '/payment/' . urlencode($slug);

        // ── Handle Stripe return: ?session_id=cs_... ──────────────────────────
        $sessionId = $_GET['session_id'] ?? null;
        if ($sessionId !== null && $sessionId !== '') {
            // Already marked completed — just show success
            if ($payment['status'] === 'completed') {
                $this->renderSuccess($payment, $item, $user);
                return;
            }

            try {
                $session = $stripe->getSession((string)$sessionId);

                if (($session['payment_status'] ?? '') === 'paid') {
                    // Mark payment completed and item as sold (same request — Galvani rule 10)
                    $paymentRepo->updateStatus((int)$payment['id'], 'completed');
                    $itemRepo->setStatus((int)$item['id'], 'sold');

                    // Store Stripe session + intent IDs
                    $intentId = $session['payment_intent'] ?? null;
                    $paymentRepo->updateStripeIds(
                        (int)$payment['id'],
                        (string)$sessionId,
                        $intentId ? (string)$intentId : null
                    );

                    // Re-fetch updated payment
                    $payment = $paymentRepo->find((int)$payment['id']);

                    // Send payment confirmation email (Phase 12)
                    try {
                        (new NotificationService())->sendPaymentConfirmation(
                            $user,
                            $item,
                            (float)($payment['amount'] ?? 0)
                        );
                    } catch (\Throwable $e) {
                        error_log('Email failed (sendPaymentConfirmation): ' . $e->getMessage());
                    }

                    flash('Payment received — thank you! Your item will be arranged shortly.');
                    $this->renderSuccess($payment, $item, $user);
                    return;
                }

                // Session exists but not yet paid (e.g. user came back without paying)
                flash('Payment was not completed. Please try again.', 'error');
            } catch (\RuntimeException $e) {
                flash('Unable to verify payment. Please contact us if you believe this is an error.', 'error');
            }
        }

        // ── Already paid (direct access after completion) ─────────────────────
        if ($payment['status'] === 'completed') {
            $this->renderSuccess($payment, $item, $user);
            return;
        }

        // ── Stripe not configured: show pending message ───────────────────────
        if (!$stripe->isConfigured()) {
            $this->renderPending($payment, $item, $user);
            return;
        }

        // ── Create Stripe Checkout Session and redirect ───────────────────────
        try {
            $result = $stripe->createCheckoutSession($payment, $item, $user, $successUrl, $cancelUrl);

            // Store session ID before redirect
            $paymentRepo->updateStripeIds((int)$payment['id'], $result['session_id']);

            $this->redirect($result['checkout_url']);
        } catch (\RuntimeException $e) {
            // Stripe error — fall back to pending view with error message
            flash('Payment processor error. Please try again later or contact us.', 'error');
            $this->renderPending($payment, $item, $user);
        }
    }

    /**
     * POST /payment/:slug
     *
     * Admin-managed manual payment completion (fallback for non-Stripe payments).
     * Requires admin role + CSRF.
     */
    public function process(string $slug): void
    {
        global $basePath;

        $user = requireAdmin();
        validateCsrf();

        $itemRepo    = new ItemRepository();
        $paymentRepo = new PaymentRepository();

        $item = $itemRepo->findBySlug($slug);
        if ($item === null) {
            $this->abort(404);
        }

        $payment = $paymentRepo->findByItem((int)$item['id']);
        if ($payment === null) {
            $this->abort(404);
        }

        if ($payment['status'] !== 'completed') {
            // Mark payment completed + item sold in same request (Galvani rule 10)
            $paymentRepo->updateStatus((int)$payment['id'], 'completed');
            $itemRepo->setStatus((int)$item['id'], 'sold');
        }

        flash('Payment marked as completed.');
        $this->redirect($basePath . '/admin/payments');
    }

    /**
     * POST /webhook/stripe
     *
     * Stripe webhook endpoint.
     *
     * IMPORTANT — Galvani header note (CLAUDE.md rule 12):
     * Galvani drops custom HTTP headers, so the standard Stripe-Signature header
     * is NOT available. Instead we verify via a shared URL token:
     *   Stripe webhook URL: https://yourdomain.com/auction/webhook/stripe?webhook_secret=TOKEN
     *
     * The token is stored as 'stripe_webhook_url_token' in the settings table.
     * Set this in Admin > Settings before configuring the webhook in Stripe Dashboard.
     *
     * For production LAMP (where headers ARE available), you could switch to
     * StripeService::verifyWebhook() using the Stripe-Signature header.
     */
    public function webhook(): void
    {
        // ── Token-based auth (Galvani workaround for dropped headers) ─────────
        $settingsRepo   = new SettingsRepository();
        $storedToken    = $settingsRepo->get('stripe_webhook_url_token');
        $incomingToken  = $_GET['webhook_secret'] ?? '';

        if (empty($storedToken) || !hash_equals($storedToken, $incomingToken)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        // ── Read raw body ─────────────────────────────────────────────────────
        $payload = (string)file_get_contents('php://input');
        if ($payload === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Empty payload']);
            exit;
        }

        $event = json_decode($payload, true);
        if (!is_array($event) || empty($event['type'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid JSON payload']);
            exit;
        }

        // ── Handle checkout.session.completed ────────────────────────────────
        if ($event['type'] === 'checkout.session.completed') {
            $session = $event['data']['object'] ?? [];

            if (($session['payment_status'] ?? '') === 'paid') {
                $sessionId  = $session['id'] ?? null;
                $paymentId  = $session['metadata']['payment_id'] ?? null;
                $intentId   = $session['payment_intent'] ?? null;

                if ($sessionId !== null && $paymentId !== null) {
                    $paymentRepo = new PaymentRepository();
                    $itemRepo    = new ItemRepository();

                    $payment = $paymentRepo->find((int)$paymentId);

                    if ($payment !== null && $payment['status'] !== 'completed') {
                        // Belt-and-braces: mark completed + item sold (Galvani rule 10)
                        $paymentRepo->updateStatus((int)$payment['id'], 'completed');
                        $itemRepo->setStatus((int)$payment['item_id'], 'sold');

                        $paymentRepo->updateStripeIds(
                            (int)$payment['id'],
                            (string)$sessionId,
                            $intentId ? (string)$intentId : null
                        );
                    }
                }
            }
        }

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['received' => true]);
        exit;
    }

    // -------------------------------------------------------------------------
    // Private render helpers
    // -------------------------------------------------------------------------

    private function renderSuccess(array $payment, array $item, array $user): void
    {
        $content = $this->renderView('payment/success', [
            'payment' => $payment,
            'item'    => $item,
            'user'    => $user,
        ]);

        $this->view('layouts/public', [
            'pageTitle' => 'Payment Successful — WFCS Auction',
            'user'      => $user,
            'activeNav' => 'my-bids',
            'mainWidth' => 'max-w-xl',
            'content'   => $content,
        ]);
    }

    private function renderPending(array $payment, array $item, array $user): void
    {
        $content = $this->renderView('payment/pending', [
            'payment' => $payment,
            'item'    => $item,
            'user'    => $user,
        ]);

        $this->view('layouts/public', [
            'pageTitle' => 'Payment Pending — WFCS Auction',
            'user'      => $user,
            'activeNav' => 'my-bids',
            'mainWidth' => 'max-w-xl',
            'content'   => $content,
        ]);
    }

    private function renderCheckout(array $payment, array $item, array $user): void
    {
        $content = $this->renderView('payment/checkout', [
            'payment' => $payment,
            'item'    => $item,
            'user'    => $user,
        ]);

        $this->view('layouts/public', [
            'pageTitle'   => 'Complete Your Payment — WFCS Auction',
            'user'        => $user,
            'activeNav'   => 'my-bids',
            'mainWidth'   => 'max-w-xl',
            'content'     => $content,
            'pageScripts' => $this->checkoutScripts($payment, $item),
        ]);
    }

    private function checkoutScripts(array $payment, array $item): string
    {
        return '// Gift Aid toggle
function toggleGiftAid(cb) {
  document.getElementById(\'gift-aid-body\').classList.toggle(\'ga-collapsed\', !cb.checked);
  document.getElementById(\'gift-aid-off\').classList.toggle(\'ga-collapsed\', cb.checked);
}';
    }
}
