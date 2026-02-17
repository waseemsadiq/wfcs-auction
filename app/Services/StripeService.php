<?php
declare(strict_types=1);

namespace App\Services;

/**
 * StripeService — thin curl wrapper around the Stripe REST API.
 *
 * No Composer SDK. All calls use PHP curl with Basic auth (secret key as user, empty password).
 *
 * Galvani note: Stripe-Signature header is dropped by Galvani. Webhook verification
 * falls back to a URL-based shared token stored in the settings table.
 */
class StripeService
{
    /**
     * Create a Stripe Checkout Session.
     *
     * @param array  $payment     Payment record from DB
     * @param array  $item        Item record from DB
     * @param array  $user        User record from DB
     * @param string $successUrl  Full URL to redirect on success
     * @param string $cancelUrl   Full URL to redirect on cancel
     * @return array ['session_id' => string, 'checkout_url' => string]
     * @throws \RuntimeException on Stripe API error
     */
    public function createCheckoutSession(
        array $payment,
        array $item,
        array $user,
        string $successUrl,
        string $cancelUrl
    ): array {
        $params = [
            'line_items[0][price_data][currency]'               => 'gbp',
            'line_items[0][price_data][product_data][name]'     => $item['title'],
            'line_items[0][price_data][unit_amount]'            => (int)round((float)$payment['amount'] * 100),
            'line_items[0][quantity]'                           => '1',
            'mode'                                              => 'payment',
            'customer_email'                                    => $user['email'],
            'success_url'                                       => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'                                        => $cancelUrl,
            'metadata[payment_id]'                              => (string)$payment['id'],
            'metadata[item_id]'                                 => (string)$item['id'],
        ];

        $response = $this->stripeRequest('POST', 'checkout/sessions', $params);

        if (!empty($response['error'])) {
            throw new \RuntimeException(
                'Stripe error: ' . ($response['error']['message'] ?? 'Unknown error')
            );
        }

        return [
            'session_id'   => $response['id'],
            'checkout_url' => $response['url'],
        ];
    }

    /**
     * Retrieve a Checkout Session by ID.
     *
     * @param string $sessionId Stripe session ID (cs_...)
     * @return array Stripe session object as associative array
     * @throws \RuntimeException on Stripe API error
     */
    public function getSession(string $sessionId): array
    {
        $response = $this->stripeRequest('GET', 'checkout/sessions/' . urlencode($sessionId));

        if (!empty($response['error'])) {
            throw new \RuntimeException(
                'Stripe error: ' . ($response['error']['message'] ?? 'Unknown error')
            );
        }

        return $response;
    }

    /**
     * Verify a Stripe webhook signature using HMAC-SHA256.
     *
     * NOTE: Galvani drops custom headers, so Stripe-Signature cannot be passed as a header.
     * This method is provided for completeness and future production use (LAMP).
     * In Galvani/production via this app, use URL-token verification instead
     * (see PaymentController::webhook).
     *
     * @param string $payload   Raw request body
     * @param string $signature Stripe-Signature header value
     * @param string $secret    Webhook signing secret (whsec_...)
     * @return array Parsed Stripe event
     * @throws \RuntimeException on invalid signature or malformed payload
     */
    public function verifyWebhook(string $payload, string $signature, string $secret): array
    {
        // Parse the Stripe-Signature header: t=<timestamp>,v1=<sig>,...
        $parts = [];
        foreach (explode(',', $signature) as $part) {
            [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
            $parts[$k] = $v;
        }

        if (empty($parts['t']) || empty($parts['v1'])) {
            throw new \RuntimeException('Invalid Stripe-Signature header format.');
        }

        $timestamp   = (int)$parts['t'];
        $expectedSig = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        // Constant-time comparison
        if (!hash_equals($expectedSig, $parts['v1'])) {
            throw new \RuntimeException('Stripe webhook signature verification failed.');
        }

        // Reject timestamps older than 5 minutes (replay protection)
        if (abs(time() - $timestamp) > 300) {
            throw new \RuntimeException('Stripe webhook timestamp is too old.');
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            throw new \RuntimeException('Invalid Stripe webhook payload (not valid JSON).');
        }

        return $event;
    }

    /**
     * Check whether Stripe is configured (both keys are non-empty).
     */
    public function isConfigured(): bool
    {
        $secret = config('stripe.secret_key');
        $pub    = config('stripe.publishable_key');

        return !empty($secret) && !empty($pub);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Execute a curl request to the Stripe API.
     *
     * @param string $method   HTTP method: GET or POST
     * @param string $endpoint Stripe endpoint path, e.g. 'checkout/sessions'
     * @param array  $params   POST body params (unused for GET)
     * @return array Decoded JSON response
     * @throws \RuntimeException on curl failure or non-JSON response
     */
    private function stripeRequest(string $method, string $endpoint, array $params = []): array
    {
        $secretKey = config('stripe.secret_key');
        $url       = 'https://api.stripe.com/v1/' . $endpoint;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $secretKey . ':',
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 30,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno !== 0) {
            throw new \RuntimeException('Stripe curl error: ' . $error);
        }

        $decoded = json_decode((string)$body, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Stripe returned invalid JSON.');
        }

        return $decoded;
    }
}
