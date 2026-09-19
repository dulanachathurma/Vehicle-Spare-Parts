<?php

declare(strict_types=1);

/**
 * payment/stripe_checkout.php - Initiates Stripe Checkout Session.
 *
 * Verifies the customer's order, checks product stock in MySQL, calculates
 * server-side line items, creates a Stripe Checkout Session via the official SDK,
 * and securely redirects the customer to the Stripe-hosted checkout page.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/lib/payment_helper.php';
require_once __DIR__ . '/lib/stripe_service.php';

requireLogin();

$db = getDB();
$orderId = (int) ($_GET['order'] ?? 0);
$userId = currentUserId();

if ($orderId <= 0) {
    setFlash('error', 'Invalid order specified.');
    redirect('orders/my_orders.php');
}

$stmt = $db->prepare(
    'SELECT o.orderID, o.finalAmount, p.status AS paymentStatus, g.gatewayName
     FROM orders o
     JOIN payment p ON p.orderID = o.orderID
     JOIN payment_gateway g ON g.gatewayID = p.gatewayID
     WHERE o.orderID = ? AND o.userID = ?'
);
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    redirect('orders/my_orders.php');
}

if ($order['paymentStatus'] === 'Success') {
    setFlash('info', 'This order has already been paid.');
    redirect('payment/payment_success.php?order=' . $orderId);
}

if ($order['paymentStatus'] === 'Refunded') {
    setFlash('error', 'This order was cancelled or refunded.');
    redirect('orders/order_details.php?id=' . $orderId);
}

if (!isStripeConfigured()) {
    setFlash('error', 'Stripe payment is not currently configured. Please contact support or select an alternative payment method.');
    redirect('payment/pay.php?order=' . $orderId);
}

try {
    $session = createStripeCheckoutSession($db, $orderId, $userId);

    // Redirect to Stripe Checkout hosted payment page
    header('Location: ' . $session->url, true, 303);
    exit;
} catch (Throwable $e) {
    error_log(sprintf('[Stripe] Checkout creation failed for order #%d: %s', $orderId, $e->getMessage()));
    setFlash('error', 'Unable to initiate secure payment: ' . $e->getMessage());
    redirect('payment/pay.php?order=' . $orderId);
}
