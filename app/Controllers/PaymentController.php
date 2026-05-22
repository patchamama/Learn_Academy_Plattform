<?php

namespace LearnAcademy\App\Controllers;

use LearnAcademy\App\App;

/**
 * Payment controller — stubs for Stripe and PayPal integration.
 * Full implementation is a future phase.
 */
class PaymentController
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

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

        $this->app->view->layout('payment/checkout', [
            'course'          => $course,
            'stripePublicKey' => $this->app->config['stripe_public_key'] ?? '',
            'paypalClientId'  => $this->app->config['paypal_client_id'] ?? '',
            'csrf'            => $this->app->auth->csrfToken(),
        ]);
    }

    public function stripeCheckout(string $courseSlug): void
    {
        $this->app->auth->requireLogin();

        if (!$this->app->auth->verifyCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        // Stub: Stripe integration coming soon
        $this->app->view->layout('payment/coming_soon', [
            'message' => 'Stripe checkout coming soon.',
        ]);
    }

    public function stripeWebhook(): void
    {
        // Stub: verify Stripe-Signature header and process event
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['received' => true]);
    }

    public function paypalWebhook(): void
    {
        // Stub: verify PayPal webhook and process event
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['received' => true]);
    }
}
