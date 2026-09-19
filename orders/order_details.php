<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/return_helper.php';

requireLogin();

$db = getDB();
$orderId = (int) ($_GET['id'] ?? 0);
$userId = currentUserId();

$stmt = $db->prepare(
    'SELECT o.*, p.status AS paymentStatus, p.transactionID, g.gatewayName
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

$itemsStmt = $db->prepare(
    'SELECT oi.*, sp.partName FROM order_item oi JOIN spare_part sp ON sp.partID = oi.partID WHERE oi.orderID = ?'
);
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

$timeline = ['Pending', 'Confirmed', 'Shipped', 'Delivered'];
$currentStep = array_search($order['status'], $timeline, true);
$isCancelled = $order['status'] === 'Cancelled';
$canCancel = in_array($order['status'], ['Pending', 'Confirmed'], true);
$returnRequest = returnRequestForOrder($db, $orderId);

$pageTitle = 'Order #' . $orderId;
$pageCss = ['orders.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>Order #<?php echo (int) $order['orderID']; ?></h1>
    <p class="text-muted">Placed <?php echo e(date('Y-m-d H:i', strtotime($order['orderDate']))); ?></p>

    <?php if (!$isCancelled): ?>
    <ol class="ord-timeline">
        <?php foreach ($timeline as $i => $step): ?>
        <li class="<?php echo ($currentStep !== false && $i <= $currentStep) ? 'is-done' : ''; ?>"><?php echo e($step); ?></li>
        <?php endforeach; ?>
    </ol>
    <?php else: ?>
    <p><span class="badge-status badge-status--cancelled">Cancelled</span></p>
    <?php endif; ?>

    <?php if (!empty($order['trackingNumber'])): ?>
    <p>Tracking Number: <strong><?php echo e($order['trackingNumber']); ?></strong></p>
    <?php endif; ?>

    <div class="card mb-2">
        <h2 class="card-title">Delivery Details</h2>
        <p>Name: <?php echo e($order['recipientName'] ?? ''); ?></p>
        <p>Phone: <?php echo e($order['recipientPhone'] ?? ''); ?></p>
        <p>Address: <?php echo e($order['shippingAddress']); ?></p>
    </div>

    <table class="table-plain mt-2">
        <thead><tr><th>Part</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo e($item['partName']); ?></td>
                <td><?php echo (int) $item['quantity']; ?></td>
                <td><?php echo formatMoney((float) $item['unitPrice']); ?></td>
                <td><?php echo formatMoney((float) $item['subtotal']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p>Subtotal: <?php echo formatMoney((float) $order['totalAmount']); ?></p>
    <p>Tax: <?php echo formatMoney((float) $order['taxAmount']); ?></p>
    <p><strong>Total: <?php echo formatMoney((float) $order['finalAmount']); ?></strong></p>
    <p>Payment: <?php echo e($order['gatewayName']); ?> -
        <span class="badge-status badge-status--<?php echo strtolower($order['paymentStatus']); ?>"><?php echo e($order['paymentStatus']); ?></span>
    </p>

    <?php if (in_array($order['paymentStatus'], ['Pending', 'Failed'], true)): ?>
    <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/payment/pay.php?order=<?php echo (int) $order['orderID']; ?>">Complete Payment</a>
    <?php else: ?>
    <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/payment/receipt.php?order=<?php echo (int) $order['orderID']; ?>">View Receipt</a>
    <?php endif; ?>

    <?php if ($canCancel): ?>
    <form method="post" action="<?php echo BASE_URL; ?>/orders/cancel_order.php" data-confirm="Cancel this order?" style="display:inline;">
        <?php echo csrfField(); ?>
        <input type="hidden" name="order_id" value="<?php echo (int) $order['orderID']; ?>">
        <button type="submit" class="btn btn-danger">Cancel Order</button>
    </form>
    <?php endif; ?>

    <?php if ($order['status'] === 'Delivered'): ?>
    <div class="card mt-2">
        <h2 class="card-title">Return / Refund</h2>
        <?php if (!$returnRequest): ?>
        <p class="text-muted">Not the right fit, or arrived damaged? You can request a return within 7 days of delivery.</p>
        <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/orders/submit_return.php?order=<?php echo (int) $order['orderID']; ?>">Return This Order</a>
        <?php else: ?>
        <p>Reason: <?php echo e($returnRequest['reasonCategory']); ?></p>
        <?php if (!empty($returnRequest['description'])): ?>
        <p>Details: <?php echo e($returnRequest['description']); ?></p>
        <?php endif; ?>
        <p>Status:
            <span class="badge-status badge-status--<?php echo strtolower($returnRequest['status']); ?>"><?php echo e($returnRequest['status']); ?></span>
        </p>
        <?php if (!empty($returnRequest['adminNotes'])): ?>
        <p class="text-muted">Note from our team: <?php echo e($returnRequest['adminNotes']); ?></p>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
