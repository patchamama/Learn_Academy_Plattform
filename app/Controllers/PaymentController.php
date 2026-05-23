<?php

namespace LearnAcademy\App\Controllers;

use LearnAcademy\App\App;
use LearnAcademy\App\Database;

class PaymentController
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    // ── Checkout page ────────────────────────────────────────────────────

    public function checkout(string $courseSlug): void
    {
        $this->app->auth->requireLogin();

        $course = $this->app->db->fetchOne(
            'SELECT id, slug, title, description, thumbnail FROM courses WHERE slug = ?',
            [$courseSlug]
        );

        if (!$course) {
            http_response_code(404);
            echo '404 Course not found';
            return;
        }

        // Already enrolled?
        if ($this->app->auth->hasAccess($course['id'])) {
            header('Location: /courses/' . $courseSlug);
            exit;
        }

        $this->app->view->layout('payment/checkout', [
            'course'          => $course,
            'stripePublicKey' => $this->app->config['stripe_public_key'] ?? '',
            'paypalClientId'  => $this->app->config['paypal_client_id'] ?? '',
            'csrf'            => $this->app->auth->csrfToken(),
        ]);
    }

    // ── Stripe ───────────────────────────────────────────────────────────

    public function stripeCheckout(string $courseSlug): void
    {
        $this->app->auth->requireLogin();

        if (!$this->app->auth->verifyCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        $course = $this->app->db->fetchOne(
            'SELECT id, slug, title FROM courses WHERE slug = ?',
            [$courseSlug]
        );

        if (!$course) {
            http_response_code(404);
            exit('Course not found.');
        }

        $secretKey = $this->app->config['stripe_secret_key'] ?? '';
        if (empty($secretKey)) {
            $this->app->view->layout('payment/coming_soon', [
                'message' => 'Stripe is not configured yet. Ask the admin for manual access.',
            ]);
            return;
        }

        // Create Stripe Checkout Session via API directly (no SDK required in test mode)
        $appUrl  = rtrim($this->app->config['app_url'] ?? 'http://localhost', '/');
        $price   = $this->app->config['course_price_cents'] ?? 2900; // default $29.00

        $payload = http_build_query([
            'mode'                       => 'payment',
            'payment_method_types[]'     => 'card',
            'line_items[0][price_data][currency]'     => 'usd',
            'line_items[0][price_data][unit_amount]'  => $price,
            'line_items[0][price_data][product_data][name]' => $course['title'],
            'line_items[0][quantity]'    => 1,
            'success_url'                => $appUrl . '/purchase/' . $courseSlug . '/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'                 => $appUrl . '/purchase/' . $courseSlug,
            'metadata[course_id]'        => $course['id'],
            'metadata[user_id]'          => $this->app->auth->user()['id'],
        ]);

        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_USERPWD        => $secretKey . ':',
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        if ($httpCode !== 200 || empty($data['url'])) {
            error_log('Stripe error: ' . $response);
            http_response_code(500);
            $this->app->view->layout('payment/coming_soon', [
                'message' => 'Payment session could not be created. Please try again.',
            ]);
            return;
        }

        // Record pending payment
        $this->app->db->insert(
            'INSERT INTO payments (user_id, course_id, provider, provider_ref, amount, currency, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $this->app->auth->user()['id'],
                $course['id'],
                'stripe',
                $data['id'],
                $price,
                'usd',
                'pending',
            ]
        );

        header('Location: ' . $data['url']);
        exit;
    }

    public function stripeSuccess(string $courseSlug): void
    {
        $this->app->auth->requireLogin();

        $sessionId = $_GET['session_id'] ?? '';
        if (empty($sessionId)) {
            header('Location: /courses/' . $courseSlug);
            exit;
        }

        // Verify the session with Stripe (don't trust URL params alone)
        $secretKey = $this->app->config['stripe_secret_key'] ?? '';
        if (!empty($secretKey)) {
            $ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD        => $secretKey . ':',
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            $session = json_decode($response, true);

            if (($session['payment_status'] ?? '') === 'paid') {
                $courseId = (int)($session['metadata']['course_id'] ?? 0);
                $userId   = (int)($session['metadata']['user_id'] ?? 0);

                if ($courseId && $userId) {
                    $this->activateEnrollment($userId, $courseId, $session['id'], 'stripe');
                }
            }
        }

        header('Location: /courses/' . $courseSlug . '?payment=success');
        exit;
    }

    public function stripeWebhook(): void
    {
        $payload   = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $secret    = $this->app->config['stripe_webhook_secret'] ?? '';

        if (!empty($secret) && !$this->verifyStripeSignature($payload, $sigHeader, $secret)) {
            http_response_code(400);
            exit('Webhook signature verification failed.');
        }

        $event = json_decode($payload, true);

        if ($event['type'] === 'checkout.session.completed') {
            $session  = $event['data']['object'];
            $courseId = (int)($session['metadata']['course_id'] ?? 0);
            $userId   = (int)($session['metadata']['user_id'] ?? 0);

            if ($courseId && $userId && $session['payment_status'] === 'paid') {
                $this->activateEnrollment($userId, $courseId, $session['id'], 'stripe');
            }
        }

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['received' => true]);
    }

    private function verifyStripeSignature(string $payload, string $sigHeader, string $secret): bool
    {
        if (!preg_match('/t=(\d+).*v1=([a-f0-9]+)/', $sigHeader, $m)) {
            return false;
        }
        $timestamp = $m[1];
        $signature = $m[2];
        $expected  = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        return hash_equals($expected, $signature);
    }

    // ── PayPal ───────────────────────────────────────────────────────────

    public function paypalCreateOrder(string $courseSlug): void
    {
        $this->app->auth->requireLogin();

        header('Content-Type: application/json');

        $course = $this->app->db->fetchOne(
            'SELECT id, slug, title FROM courses WHERE slug = ?',
            [$courseSlug]
        );

        if (!$course) {
            http_response_code(404);
            echo json_encode(['error' => 'Course not found']);
            return;
        }

        $clientId = $this->app->config['paypal_client_id'] ?? '';
        $secret   = $this->app->config['paypal_secret'] ?? '';
        $mode     = $this->app->config['paypal_mode'] ?? 'sandbox';
        $baseUrl  = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        if (empty($clientId) || empty($secret)) {
            http_response_code(503);
            echo json_encode(['error' => 'PayPal not configured']);
            return;
        }

        // Get access token
        $token = $this->getPaypalAccessToken($baseUrl, $clientId, $secret);
        if (!$token) {
            http_response_code(500);
            echo json_encode(['error' => 'PayPal auth failed']);
            return;
        }

        $price = number_format(($this->app->config['course_price_cents'] ?? 2900) / 100, 2, '.', '');

        $orderPayload = json_encode([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount'      => ['currency_code' => 'USD', 'value' => $price],
                'description' => $course['title'],
                'custom_id'   => $course['id'] . ':' . $this->app->auth->user()['id'],
            ]],
        ]);

        $ch = curl_init($baseUrl . '/v2/checkout/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $orderPayload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $order = json_decode($response, true);

        if (empty($order['id'])) {
            http_response_code(500);
            echo json_encode(['error' => 'Order creation failed']);
            return;
        }

        // Record pending payment
        $this->app->db->insert(
            'INSERT INTO payments (user_id, course_id, provider, provider_ref, amount, currency, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $this->app->auth->user()['id'],
                $course['id'],
                'paypal',
                $order['id'],
                $this->app->config['course_price_cents'] ?? 2900,
                'usd',
                'pending',
            ]
        );

        echo json_encode(['id' => $order['id']]);
    }

    public function paypalCaptureOrder(string $courseSlug): void
    {
        $this->app->auth->requireLogin();

        header('Content-Type: application/json');

        $orderId  = $_POST['orderID'] ?? (json_decode(file_get_contents('php://input'), true)['orderID'] ?? '');
        $clientId = $this->app->config['paypal_client_id'] ?? '';
        $secret   = $this->app->config['paypal_secret'] ?? '';
        $mode     = $this->app->config['paypal_mode'] ?? 'sandbox';
        $baseUrl  = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $token = $this->getPaypalAccessToken($baseUrl, $clientId, $secret);
        if (!$token) {
            http_response_code(500);
            echo json_encode(['error' => 'PayPal auth failed']);
            return;
        }

        $ch = curl_init($baseUrl . '/v2/checkout/orders/' . urlencode($orderId) . '/capture');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => '',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $capture = json_decode($response, true);

        if (($capture['status'] ?? '') === 'COMPLETED') {
            $customId = $capture['purchase_units'][0]['payments']['captures'][0]['custom_id'] ?? '';
            [$courseId, $userId] = array_map('intval', explode(':', $customId . ':0'));

            if ($courseId && $userId) {
                $this->activateEnrollment($userId, $courseId, $orderId, 'paypal');
            }

            echo json_encode(['status' => 'COMPLETED']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Capture failed', 'status' => $capture['status'] ?? 'unknown']);
        }
    }

    public function paypalWebhook(): void
    {
        // PayPal webhook verification requires SDK or manual HMAC;
        // The capture endpoint handles payment confirmation server-side.
        http_response_code(200);
        echo json_encode(['received' => true]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function activateEnrollment(int $userId, int $courseId, string $providerRef, string $provider): void
    {
        $expiresAt = strtotime('+' . ($this->app->config['course_access_days'] ?? 365) . ' days');

        $this->app->db->transaction(function (Database $db) use ($userId, $courseId, $expiresAt, $providerRef, $provider) {
            // Upsert enrollment
            $db->execute(
                'INSERT OR REPLACE INTO enrollments (user_id, course_id, granted_by, expires_at)
                 VALUES (?, ?, NULL, ?)',
                [$userId, $courseId, $expiresAt]
            );

            // Mark payment completed
            $db->execute(
                "UPDATE payments SET status = 'completed' WHERE provider_ref = ? AND provider = ?",
                [$providerRef, $provider]
            );
        });
    }

    private function getPaypalAccessToken(string $baseUrl, string $clientId, string $secret): ?string
    {
        $ch = curl_init($baseUrl . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
            CURLOPT_USERPWD        => $clientId . ':' . $secret,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
}
