<?php

declare(strict_types=1);

/**
 * payment/payment_cancel.php - Payment Cancellation Handler.
 *
 * Handles customer cancellations from Stripe Checkout, PayHere, or the local
 * payment screen. Preserves order in Pending state, marks payment Failed/Pending,
 * ensures inventory is not reduced, and provides clear retry/navigation options.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/payment_helper.php';

requireLogin();

$db = getDB();
$orderId = (int) ($_POST['order_id'] ?? $_GET['order'] ?? 0);
$userId = currentUserId();

$order = null;
if ($orderId > 0) {
    $stmt = $db->prepare(
        'SELECT o.*, p.status AS paymentStatus, g.gatewayName
         FROM orders o
         JOIN payment p ON p.orderID = o.orderID
         JOIN payment_gateway g ON g.gatewayID = p.gatewayID
         WHERE o.orderID = ? AND o.userID = ?'
    );
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();

    if ($order && $order['paymentStatus'] === 'Pending') {
        markPaymentFailed($db, $orderId);
    }
}

// If invoked via POST from pay.php button, redirect with flash message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    setFlash('warning', 'Payment was not completed. Your order is saved as pending - you can try paying again from My Orders.');
    redirect('orders/my_orders.php');
}

$pageTitle = 'Payment Cancelled';
$pageCss = ['orders.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container ord-pay-page">
    <div class="card ord-pay-card" style="text-align: center; max-width: 560px; margin: 40px auto;">
        <div style="font-size: 48px; color: #ef4444; margin-bottom: 12px;" aria-hidden="true">&#9888;</div>
        <h1 class="card-title">Payment Cancelled</h1>

        <?php if ($order): ?>
            <p>Your payment for <strong>Order #<?php echo (int) $order['orderID']; ?></strong> was not completed.</p>
            <p class="text-muted">No charges were made to your account. Your order has been saved as pending so you can retry whenever you are ready.</p>

            <div class="mt-3" style="display: flex; flex-direction: column; gap: 10px;">
                <a class="btn btn-primary btn-block" href="<?php echo BASE_URL; ?>/payment/pay.php?order=<?php echo (int) $order['orderID']; ?>">Retry Payment</a>
                <a class="btn btn-outline btn-block" href="<?php echo BASE_URL; ?>/orders/order_details.php?id=<?php echo (int) $order['orderID']; ?>">View Order Details</a>
                <a class="btn btn-outline btn-block" href="<?php echo BASE_URL; ?>/orders/my_orders.php">Go to My Orders</a>
            </div>
        <?php else: ?>
            <p class="text-muted">The payment was cancelled. You can review your orders or return to your shopping cart.</p>
            <div class="mt-3">
                <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/orders/my_orders.php">My Orders</a>
                <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/catalogue/products.php">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
