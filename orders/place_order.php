<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/order_helper.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? null)) {
    setFlash('error', 'Your session expired. Please try again.');
    redirect('orders/checkout.php');
}

$db = getDB();
$userId = currentUserId();
$recipientName = trim($_POST['recipient_name'] ?? '');
$recipientPhone = trim($_POST['recipient_phone'] ?? '');
$shippingAddress = trim($_POST['shipping_address'] ?? '');
$gatewayId = (int) ($_POST['gateway_id'] ?? 0);

if ($recipientName === '' || $recipientPhone === '' || $shippingAddress === '' || $gatewayId <= 0) {
    setFlash('error', 'Please provide your name, phone number, shipping address and a payment method.');
    redirect('orders/checkout.php');
}

$gwStmt = $db->prepare('SELECT gatewayName FROM payment_gateway WHERE gatewayID = ?');
$gwStmt->execute([$gatewayId]);
$gw = $gwStmt->fetch();

$isStripe = $gw && stripos((string) $gw['gatewayName'], 'stripe') !== false;

try {
    // For Stripe, stock is decremented upon verified payment to prevent premature or duplicate stock reduction
    $result = placeOrder($db, $userId, $recipientName, $recipientPhone, $shippingAddress, $gatewayId, !$isStripe);

    if ($isStripe) {
        redirect('payment/stripe_checkout.php?order=' . $result['orderId']);
    } else {
        redirect('payment/pay.php?order=' . $result['orderId']);
    }
} catch (Throwable $e) {
    setFlash('error', 'We could not place your order: ' . $e->getMessage());
    redirect('orders/cart.php');
}

