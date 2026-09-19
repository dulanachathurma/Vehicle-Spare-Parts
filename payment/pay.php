<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/payment_helper.php';

requireLogin();

$db = getDB();
$orderId = (int) ($_GET['order'] ?? 0);
$userId = currentUserId();

$stmt = $db->prepare(
    'SELECT o.orderID, o.finalAmount, o.status AS orderStatus, p.paymentID, p.status AS paymentStatus, p.gatewayID, g.gatewayName
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

// Only a Success payment is actually settled - a Failed one (a
// declined card, or a payment the customer cancelled out of) must
// still land back on this page so "Pay Now" can be tried again,
// instead of being waved through to the confirmation page as if it
// had gone through.
if ($order['paymentStatus'] === 'Success') {
    redirect('orders/order_confirmation.php?order=' . $orderId);
}

if ($order['paymentStatus'] === 'Refunded') {
    redirect('orders/order_details.php?id=' . $orderId);
}

$pageTitle = 'Pay for Order #' . $orderId;
$pageCss = ['orders.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container ord-pay-page">
    <div class="card ord-pay-card">
        <h1 class="card-title">Complete Your Payment</h1>
        <p>Order #<?php echo (int) $orderId; ?> &middot; <?php echo formatMoney((float) $order['finalAmount']); ?></p>
        <p class="text-muted">Payment method: <?php echo e($order['gatewayName']); ?></p>

        <?php if ($order['paymentStatus'] === 'Failed'): ?>
        <p class="form-error">Your last payment attempt didn't go through. Please try again.</p>
        <?php endif; ?>

        <?php if (stripos($order['gatewayName'], 'payhere') !== false): ?>
        <a class="btn btn-primary btn-block" href="<?php echo BASE_URL; ?>/payment/payhere_checkout.php?order=<?php echo (int) $orderId; ?>">Pay with PayHere</a>
        <?php elseif (stripos($order['gatewayName'], 'stripe') !== false): ?>
        <a class="btn btn-primary btn-block" href="<?php echo BASE_URL; ?>/payment/stripe_checkout.php?order=<?php echo (int) $orderId; ?>">Pay Securely with Stripe</a>
        <?php else: ?>
        <a class="btn btn-primary btn-block" href="<?php echo BASE_URL; ?>/payment/mock_gateway.php?order=<?php echo (int) $orderId; ?>">Pay Now</a>
        <?php endif; ?>

        <form method="post" action="<?php echo BASE_URL; ?>/payment/payment_cancel.php" data-confirm="Cancel this payment? Your order will stay saved as pending.">
            <?php echo csrfField(); ?>
            <input type="hidden" name="order_id" value="<?php echo (int) $orderId; ?>">
            <button type="submit" class="btn btn-outline btn-block mt-1">Cancel Payment</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
