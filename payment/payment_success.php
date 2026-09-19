<?php

declare(strict_types=1);

/**
 * payment/payment_success.php - Stripe Payment Success Page.
 *
 * Displays post-payment details for confirmed Stripe orders.
 * Adheres strictly to security rules: does NOT blindly mark orders paid
 * from the URL, but reads verified status from the database or authoritative
 * Stripe API verification.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/lib/payment_helper.php';
require_once __DIR__ . '/lib/stripe_service.php';

requireLogin();

$db = getDB();
$userId = currentUserId();
$orderId = (int) ($_GET['order'] ?? 0);
$sessionId = trim((string) ($_GET['session_id'] ?? ''));

// Locate order by orderID or stripe_session_id
$order = null;
if ($orderId > 0) {
    $stmt = $db->prepare(
        'SELECT o.*, p.status AS paymentStatus, p.transactionID, p.stripe_session_id, p.paidAt, g.gatewayName
         FROM orders o
         JOIN payment p ON p.orderID = o.orderID
         JOIN payment_gateway g ON g.gatewayID = p.gatewayID
         WHERE o.orderID = ? AND o.userID = ?'
    );
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();
} elseif ($sessionId !== '') {
    $stmt = $db->prepare(
        'SELECT o.*, p.status AS paymentStatus, p.transactionID, p.stripe_session_id, p.paidAt, g.gatewayName
         FROM orders o
         JOIN payment p ON p.orderID = o.orderID
         JOIN payment_gateway g ON g.gatewayID = p.gatewayID
         WHERE p.stripe_session_id = ? AND o.userID = ?'
    );
    $stmt->execute([$sessionId, $userId]);
    $order = $stmt->fetch();
    if ($order) {
        $orderId = (int) $order['orderID'];
    }
}

if (!$order) {
    setFlash('error', 'Order not found.');
    redirect('orders/my_orders.php');
}

// Authoritative verification if webhook hasn't arrived yet on localhost/fast redirect
if ($order['paymentStatus'] !== 'Success' && $sessionId !== '' && isStripeConfigured()) {
    $stripeSession = getStripeSession($sessionId);
    if (
        $stripeSession !== null &&
        $stripeSession->payment_status === 'paid' &&
        (int) ($stripeSession->metadata->order_id ?? 0) === $orderId
    ) {
        $pi = is_string($stripeSession->payment_intent) ? $stripeSession->payment_intent : null;
        confirmStripePayment($db, $orderId, $pi, $sessionId);

        // Re-fetch updated record
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();
    }
}

$isPaid = ($order['paymentStatus'] === 'Success');

// Fetch order line items
$itemsStmt = $db->prepare(
    'SELECT oi.*, sp.partName, sp.partNumber
     FROM order_item oi
     JOIN spare_part sp ON sp.partID = oi.partID
     WHERE oi.orderID = ?'
);
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

$estimatedDelivery = date('Y-m-d', strtotime($order['orderDate'] . ' +5 days'));

$pageTitle = $isPaid ? 'Payment Successful' : 'Payment Processing';
$pageCss = ['orders.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container ord-confirmation">
    <div class="card ord-confirmation-card">
        <?php if ($isPaid): ?>
            <div style="font-size: 48px; color: #10b981; margin-bottom: 12px; line-height: 1;" aria-hidden="true">&#10004;</div>
            <h1 class="card-title">Payment Successful!</h1>
            <p><strong>Order Number: #<?php echo (int) $order['orderID']; ?></strong></p>
            <p class="text-muted">Thank you for your purchase. Your order is now being processed.</p>

            <div class="mt-2 mb-2" style="background: var(--color-surface-sunken, #f8fafc); padding: 12px 16px; border-radius: 8px; text-align: left;">
                <p class="mb-1"><strong>Status:</strong>
                    <span class="badge-status badge-status--<?php echo strtolower($order['status']); ?>"><?php echo e($order['status']); ?></span>
                    &nbsp;&middot;&nbsp;
                    <strong>Payment:</strong>
                    <span class="badge-status badge-status--success">Paid</span>
                </p>
                <p class="mb-1"><strong>Estimated Delivery:</strong> <?php echo e($estimatedDelivery); ?></p>
                <p class="mb-0"><strong>Shipping To:</strong> <?php echo e($order['recipientName']); ?>, <?php echo e($order['shippingAddress']); ?> (Tel: <?php echo e($order['recipientPhone']); ?>)</p>
            </div>

            <table class="table-plain mt-2">
                <thead>
                    <tr><th>Item</th><th>Qty</th><th>Price</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo e($item['partName']); ?> <?php echo !empty($item['partNumber']) ? '(' . e($item['partNumber']) . ')' : ''; ?></td>
                        <td>&times; <?php echo (int) $item['quantity']; ?></td>
                        <td><?php echo formatMoney((float) $item['subtotal']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p class="mt-2"><strong>Total Paid: <?php echo formatMoney((float) $order['finalAmount']); ?></strong></p>

            <div class="mt-3" style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/payment/receipt.php?order=<?php echo (int) $order['orderID']; ?>">View Receipt</a>
                <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/orders/my_orders.php">View My Orders</a>
                <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/catalogue/products.php">Continue Shopping</a>
            </div>

        <?php else: ?>
            <div style="font-size: 40px; color: #f59e0b; margin-bottom: 12px;" aria-hidden="true">&#8987;</div>
            <h1 class="card-title">Payment is Processing</h1>
            <p><strong>Order Number: #<?php echo (int) $order['orderID']; ?></strong></p>
            <p class="text-muted">Your payment transaction is being verified by Stripe. Please allow a few moments.</p>

            <div class="mt-3">
                <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/payment/payment_success.php?order=<?php echo (int) $order['orderID']; ?>&session_id=<?php echo urlencode($sessionId); ?>">Refresh Status</a>
                <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/orders/my_orders.php">View My Orders</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
