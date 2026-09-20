<?php

declare(strict_types=1);

/**
 * payment/payment_success.php — Stripe Payment Success Page.
 *
 * SECURITY MODEL (InfinityFree free-hosting, no webhook):
 * --------------------------------------------------------
 * This page is the authoritative payment confirmation point. It performs a
 * direct server-side Stripe API call to verify the Checkout Session before
 * making any database changes. It does NOT trust the URL alone.
 *
 * Verification chain:
 *   1. Require ?session_id=cs_... in the URL (Stripe sets this on redirect)
 *   2. Retrieve the session server-side from Stripe API
 *   3. Verify session.payment_status === 'paid'
 *   4. Verify session.metadata.order_id matches the local order
 *   5. Verify session.currency matches STRIPE_CURRENCY
 *   6. Verify session.amount_total / 100 matches order.finalAmount (±1 cent tolerance)
 *   7. Reject cancelled/refunded orders
 *   8. Call confirmStripePayment() — idempotent with FOR UPDATE lock
 *      (refreshing this page multiple times NEVER reduces stock twice)
 *
 * KNOWN LIMITATION — InfinityFree:
 * ---------------------------------
 * Stripe webhooks (stripe_webhook.php) cannot reliably reach InfinityFree
 * because the host blocks server-to-server incoming requests. This page
 * therefore acts as the sole confirmation trigger for paid orders.
 *
 * If a customer successfully pays on Stripe but closes their browser before
 * being redirected back to this page, the local order will remain "Pending"
 * until they return to this URL (e.g. from My Orders → Retry Payment link).
 * This is an accepted limitation of free hosting and is suitable for a demo.
 *
 * For production hosting with webhook support, stripe_webhook.php handles
 * confirmation server-to-server independently of this browser-return page.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/lib/payment_helper.php';
require_once __DIR__ . '/lib/stripe_service.php';

requireLogin();

$db        = getDB();
$userId    = currentUserId();
$sessionId = trim((string) ($_GET['session_id'] ?? ''));

// ── Require session_id ────────────────────────────────────────────────────────
// We do NOT accept ?success=1 or ?order=N alone as proof of payment.
// The session_id must be present so we can verify it with Stripe's API.
if ($sessionId === '') {
    // Allow direct access to already-confirmed order (e.g. receipt link) only
    // if the order is already marked paid in the database. No Stripe call needed.
    $orderId = (int) ($_GET['order'] ?? 0);
    if ($orderId > 0) {
        $stmt = $db->prepare(
            'SELECT o.*, p.status AS paymentStatus, p.transactionID, p.stripe_session_id,
                    p.stripe_payment_intent_id, p.paidAt, g.gatewayName
             FROM orders o
             JOIN payment p ON p.orderID = o.orderID
             JOIN payment_gateway g ON g.gatewayID = p.gatewayID
             WHERE o.orderID = ? AND o.userID = ?'
        );
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();

        if ($order && $order['paymentStatus'] === 'Success') {
            // Order is confirmed — show success page without Stripe re-verification
            goto render_page;
        }
    }
    setFlash('error', 'Invalid payment link. Please use the link from your email or My Orders.');
    redirect('orders/my_orders.php');
}

// ── Load order by stripe_session_id ──────────────────────────────────────────
$stmt = $db->prepare(
    'SELECT o.*, p.status AS paymentStatus, p.transactionID, p.stripe_session_id,
            p.stripe_payment_intent_id, p.paidAt, g.gatewayName
     FROM orders o
     JOIN payment p ON p.orderID = o.orderID
     JOIN payment_gateway g ON g.gatewayID = p.gatewayID
     WHERE p.stripe_session_id = ? AND o.userID = ?'
);
$stmt->execute([$sessionId, $userId]);
$order = $stmt->fetch();

// Fallback: try matching by ?order= parameter if session_id not yet stored
// (can happen if user hits back/forward before stripe_session_id was saved)
if (!$order) {
    $orderId = (int) ($_GET['order'] ?? 0);
    if ($orderId > 0) {
        $stmt2 = $db->prepare(
            'SELECT o.*, p.status AS paymentStatus, p.transactionID, p.stripe_session_id,
                    p.stripe_payment_intent_id, p.paidAt, g.gatewayName
             FROM orders o
             JOIN payment p ON p.orderID = o.orderID
             JOIN payment_gateway g ON g.gatewayID = p.gatewayID
             WHERE o.orderID = ? AND o.userID = ?'
        );
        $stmt2->execute([$orderId, $userId]);
        $order = $stmt2->fetch();
    }
}

if (!$order) {
    setFlash('error', 'Order not found or access denied.');
    redirect('orders/my_orders.php');
}

$orderId = (int) $order['orderID'];

// ── If already paid — skip Stripe verification (idempotent fast path) ─────────
if ($order['paymentStatus'] === 'Success') {
    goto render_page;
}

// ── Reject cancelled or refunded orders ───────────────────────────────────────
if (in_array($order['paymentStatus'], ['Refunded'], true)
    || in_array($order['status'], ['Cancelled'], true)
) {
    setFlash('error', 'This order has been cancelled or refunded and cannot be confirmed.');
    redirect('orders/order_details.php?id=' . $orderId);
}

// ── Server-side Stripe API verification ───────────────────────────────────────
$verificationError = null;

if (!isStripeConfigured()) {
    $verificationError = 'Stripe is not configured on this server.';
} else {
    $stripeSession = getStripeSession($sessionId);

    if ($stripeSession === null) {
        $verificationError = 'Could not retrieve payment session from Stripe. Please try again.';
    } elseif ($stripeSession->payment_status !== 'paid') {
        // Payment genuinely not completed
        $verificationError = 'Payment not completed (status: ' . htmlspecialchars($stripeSession->payment_status, ENT_QUOTES, 'UTF-8') . '). '
            . 'Please complete payment or contact support.';
    } else {
        // Verify metadata.order_id matches our local order
        $metaOrderId = (int) ($stripeSession->metadata->order_id ?? 0);
        if ($metaOrderId !== $orderId) {
            error_log(sprintf(
                '[Stripe][SECURITY] Session %s metadata.order_id=%d does not match local order #%d for user #%d',
                $sessionId, $metaOrderId, $orderId, $userId
            ));
            $verificationError = 'Payment session does not match this order. Please contact support.';
        } else {
            // Verify currency
            $expectedCurrency = strtolower(getStripeCurrency());
            $sessionCurrency  = strtolower((string) ($stripeSession->currency ?? ''));
            if ($sessionCurrency !== $expectedCurrency) {
                error_log(sprintf(
                    '[Stripe][SECURITY] Currency mismatch for order #%d: expected %s, got %s',
                    $orderId, $expectedCurrency, $sessionCurrency
                ));
                $verificationError = 'Currency mismatch in payment session. Please contact support.';
            } else {
                // Verify amount_total (Stripe stores in smallest unit, e.g. cents/paise)
                $stripeAmountPaid  = (int) ($stripeSession->amount_total ?? 0);
                $expectedAmountInt = (int) round((float) $order['finalAmount'] * 100);
                // Allow ±1 unit tolerance for floating-point rounding
                if (abs($stripeAmountPaid - $expectedAmountInt) > 1) {
                    error_log(sprintf(
                        '[Stripe][SECURITY] Amount mismatch for order #%d: expected %d, got %d (Stripe amount_total)',
                        $orderId, $expectedAmountInt, $stripeAmountPaid
                    ));
                    $verificationError = 'Payment amount does not match the order total. Please contact support.';
                } else {
                    // ── All checks passed — confirm payment atomically ────────
                    try {
                        $paymentIntentId = null;
                        if (!empty($stripeSession->payment_intent)) {
                            $paymentIntentId = is_string($stripeSession->payment_intent)
                                ? $stripeSession->payment_intent
                                : (string) ($stripeSession->payment_intent->id ?? '');
                        }
                        confirmStripePayment($db, $orderId, $paymentIntentId ?: null, $sessionId);
                    } catch (Throwable $e) {
                        error_log(sprintf('[Stripe] confirmStripePayment failed for order #%d: %s', $orderId, $e->getMessage()));
                        $verificationError = 'Payment was received but order confirmation failed. Please contact support with your order number.';
                    }
                }
            }
        }
    }
}

// Re-fetch updated record (separate query — do not reuse old result)
$fetchStmt = $db->prepare(
    'SELECT o.*, p.status AS paymentStatus, p.transactionID, p.stripe_session_id,
            p.stripe_payment_intent_id, p.paidAt, g.gatewayName
     FROM orders o
     JOIN payment p ON p.orderID = o.orderID
     JOIN payment_gateway g ON g.gatewayID = p.gatewayID
     WHERE o.orderID = ? AND o.userID = ?'
);
$fetchStmt->execute([$orderId, $userId]);
$order = $fetchStmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found after verification.');
    redirect('orders/my_orders.php');
}

render_page:

$isPaid = ($order['paymentStatus'] === 'Success');

// Fetch order line items
$itemsStmt = $db->prepare(
    'SELECT oi.*, sp.partName, sp.partNumber
     FROM order_item oi
     JOIN spare_part sp ON sp.partID = oi.partID
     WHERE oi.orderID = ?'
);
$itemsStmt->execute([(int) $order['orderID']]);
$items = $itemsStmt->fetchAll();

$estimatedDelivery = date('Y-m-d', strtotime($order['orderDate'] . ' +5 days'));

$pageTitle = $isPaid ? 'Payment Successful' : 'Payment Processing';
$pageCss   = ['orders.css'];
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

        <?php elseif (isset($verificationError) && $verificationError !== null): ?>
            <div style="font-size: 40px; color: #ef4444; margin-bottom: 12px;" aria-hidden="true">&#9888;</div>
            <h1 class="card-title">Verification Failed</h1>
            <p><strong>Order Number: #<?php echo (int) $order['orderID']; ?></strong></p>
            <p class="text-muted"><?php echo e($verificationError); ?></p>

            <div class="mt-3" style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/payment/payment_success.php?session_id=<?php echo urlencode($sessionId); ?>&order=<?php echo (int) $order['orderID']; ?>">Try Again</a>
                <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/orders/my_orders.php">View My Orders</a>
            </div>

        <?php else: ?>
            <div style="font-size: 40px; color: #f59e0b; margin-bottom: 12px;" aria-hidden="true">&#8987;</div>
            <h1 class="card-title">Payment is Processing</h1>
            <p><strong>Order Number: #<?php echo (int) $order['orderID']; ?></strong></p>
            <p class="text-muted">Your payment transaction is being verified. Please allow a few moments and refresh this page.</p>

            <div class="mt-3" style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/payment/payment_success.php?session_id=<?php echo urlencode($sessionId); ?>&order=<?php echo (int) $order['orderID']; ?>">Refresh Status</a>
                <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/orders/my_orders.php">View My Orders</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
