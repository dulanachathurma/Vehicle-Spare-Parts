<?php

declare(strict_types=1);

/**
 * payment/stripe_webhook.php — Stripe Webhook Endpoint.
 *
 * Authoritative, server-to-server payment verification with cryptographic
 * signature validation. Handles successful checkouts, payments, failures,
 * and refunds idempotently inside MySQL transactions with atomic stock updates.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * IMPORTANT — InfinityFree Free Hosting Limitation
 * ═══════════════════════════════════════════════════════════════════════════
 * InfinityFree blocks server-to-server incoming HTTP requests from third
 * parties (including Stripe's webhook delivery servers). This means Stripe
 * CANNOT reliably reach this endpoint on InfinityFree hosting.
 *
 * For the free-hosting demo at autopartslanka.wuaze.com, payment
 * confirmation is handled instead via browser-return verification in:
 *   payment/payment_success.php
 *
 * That page retrieves the Checkout Session directly from the Stripe API
 * (server-side, using your secret key) and verifies payment_status, metadata,
 * currency, and amount before calling confirmStripePayment().
 *
 * This file is retained in the project so that upgrading to proper hosting
 * (VPS, shared cPanel, AWS, etc.) only requires registering this endpoint
 * URL in the Stripe Dashboard — no code changes needed.
 *
 * To enable webhooks on capable hosting:
 *   1. Register: https://dashboard.stripe.com/test/webhooks
 *   2. Endpoint URL: https://yourdomain.com/payment/stripe_webhook.php
 *   3. Events: checkout.session.completed, payment_intent.succeeded,
 *              payment_intent.payment_failed, charge.refunded
 *   4. Copy the signing secret to STRIPE_WEBHOOK_SECRET in config.local.php
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/lib/payment_helper.php';
require_once __DIR__ . '/lib/stripe_service.php';

// Prepare logging directory
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (empty($payload) || empty($sigHeader)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing payload or signature']);
    exit;
}

// Cryptographic signature verification
try {
    $event = verifyWebhookEvent($payload, $sigHeader);
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    error_log('[Stripe Webhook] Invalid payload received: ' . $e->getMessage());
    echo json_encode(['error' => 'Invalid payload']);
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    error_log('[Stripe Webhook] Invalid signature: ' . $e->getMessage());
    echo json_encode(['error' => 'Invalid signature']);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[Stripe Webhook] Configuration or server error: ' . $e->getMessage());
    echo json_encode(['error' => 'Webhook handler error']);
    exit;
}

$db = getDB();
$eventType = $event->type;
$logEntry = sprintf(
    "[%s] Received event: %s | ID: %s\n",
    date('Y-m-d H:i:s'),
    $eventType,
    $event->id
);
file_put_contents($logDir . '/stripe_webhook.log', $logEntry, FILE_APPEND);

try {
    switch ($eventType) {
        case 'checkout.session.completed':
            /** @var \Stripe\Checkout\Session $session */
            $session = $event->data->object;
            $orderId = (int) ($session->metadata->order_id ?? $session->client_reference_id ?? 0);
            $paymentIntentId = is_string($session->payment_intent) ? $session->payment_intent : null;
            $sessionId = $session->id;

            if ($orderId > 0 && ($session->payment_status === 'paid' || $session->mode === 'payment')) {
                confirmStripePayment($db, $orderId, $paymentIntentId, $sessionId);
                file_put_contents(
                    $logDir . '/stripe_webhook.log',
                    sprintf("[%s] Confirmed payment for order #%d (Session: %s)\n", date('Y-m-d H:i:s'), $orderId, $sessionId),
                    FILE_APPEND
                );
            }
            break;

        case 'payment_intent.succeeded':
            /** @var \Stripe\PaymentIntent $paymentIntent */
            $paymentIntent = $event->data->object;
            $orderId = (int) ($paymentIntent->metadata->order_id ?? 0);
            if ($orderId > 0) {
                confirmStripePayment($db, $orderId, $paymentIntent->id, null);
                file_put_contents(
                    $logDir . '/stripe_webhook.log',
                    sprintf("[%s] Confirmed payment via PaymentIntent for order #%d (PI: %s)\n", date('Y-m-d H:i:s'), $orderId, $paymentIntent->id),
                    FILE_APPEND
                );
            }
            break;

        case 'payment_intent.payment_failed':
            /** @var \Stripe\PaymentIntent $paymentIntent */
            $paymentIntent = $event->data->object;
            $orderId = (int) ($paymentIntent->metadata->order_id ?? 0);
            if ($orderId > 0) {
                markPaymentFailed($db, $orderId);
                file_put_contents(
                    $logDir . '/stripe_webhook.log',
                    sprintf("[%s] Payment failed for order #%d\n", date('Y-m-d H:i:s'), $orderId),
                    FILE_APPEND
                );
            }
            break;

        case 'charge.refunded':
            /** @var \Stripe\Charge $charge */
            $charge = $event->data->object;
            $orderId = (int) ($charge->metadata->order_id ?? 0);
            $amountRefunded = ((float) $charge->amount_refunded) / 100;
            if ($orderId > 0 && $amountRefunded > 0) {
                adminProcessRefund($db, $orderId, $amountRefunded);
                file_put_contents(
                    $logDir . '/stripe_webhook.log',
                    sprintf("[%s] Processed refund of %0.2f for order #%d\n", date('Y-m-d H:i:s'), $amountRefunded, $orderId),
                    FILE_APPEND
                );
            }
            break;

        default:
            // Unhandled event types are acknowledged with HTTP 200
            break;
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'event' => $eventType]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log(sprintf('[Stripe Webhook Error] %s on line %d: %s', $e->getFile(), $e->getLine(), $e->getMessage()));
    echo json_encode(['error' => 'Internal processing error']);
}
