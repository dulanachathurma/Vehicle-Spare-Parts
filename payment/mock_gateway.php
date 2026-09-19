<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/payment_helper.php';

requireLogin();

$db = getDB();
$orderId = (int) ($_GET['order'] ?? $_POST['order_id'] ?? 0);
$userId = currentUserId();

$stmt = $db->prepare(
    'SELECT o.orderID, o.finalAmount, p.status AS paymentStatus
     FROM orders o JOIN payment p ON p.orderID = o.orderID
     WHERE o.orderID = ? AND o.userID = ?'
);
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

// Failed is retryable, same as Pending - only a Success/Refunded
// payment is truly settled and should turn a customer away here.
if (!$order || !in_array($order['paymentStatus'], ['Pending', 'Failed'], true)) {
    setFlash('error', 'This order is not awaiting payment.');
    redirect('orders/my_orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('payment/mock_gateway.php?order=' . $orderId);
    }

    if (!empty($_POST['simulate_failure'])) {
        markPaymentFailed($db, $orderId);
        setFlash('error', 'Payment failed. Please try again.');
        redirect('payment/pay.php?order=' . $orderId);
    }

    markPaymentSuccess($db, $orderId, 'MOCK-' . $orderId . '-' . time());
    setFlash('success', 'Payment successful.');
    redirect('orders/order_confirmation.php?order=' . $orderId);
}

$pageTitle = 'Secure Payment';
$pageCss = ['orders.css'];
$pageJs = ['cart.js'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container ord-pay-page">
    <div class="ord-pay-wrap">
        <div class="ord-card-preview" data-card-preview>
            <div class="ord-card-preview-top">
                <span class="ord-card-chip"></span>
                <span class="ord-card-brand" data-card-brand aria-hidden="true">
                    <span class="ord-mini-visa" data-brand-visa hidden>VISA</span>
                    <span class="ord-mini-mc" data-brand-mastercard hidden><i></i><i></i></span>
                </span>
            </div>
            <p class="ord-card-preview-number" data-preview-number>&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;</p>
            <div class="ord-card-preview-bottom">
                <div>
                    <span class="ord-card-preview-label">Card Holder</span>
                    <p class="ord-card-preview-value" data-preview-name>YOUR NAME</p>
                </div>
                <div>
                    <span class="ord-card-preview-label">Expires</span>
                    <p class="ord-card-preview-value" data-preview-expiry>MM/YY</p>
                </div>
            </div>
        </div>

        <div class="card ord-pay-card">
            <div class="ord-pay-secure-head">
                <span class="ord-pay-lock">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="9" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>
                </span>
                <div>
                    <h1 class="card-title mb-0">Secure Payment</h1>
                    <p class="text-muted mb-0">Order #<?php echo (int) $orderId; ?> &middot; <?php echo formatMoney((float) $order['finalAmount']); ?></p>
                </div>
            </div>

            <form method="post" action="<?php echo BASE_URL; ?>/payment/mock_gateway.php?order=<?php echo (int) $orderId; ?>" data-validate novalidate>
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label class="form-label" for="card_holder">Name on Card</label>
                    <input type="text" id="card_holder" name="card_holder" class="form-control" autocomplete="off" placeholder="J. Perera" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="card_number">Card Number</label>
                    <input type="text" id="card_number" name="card_number" class="form-control" inputmode="numeric" autocomplete="off" maxlength="19" placeholder="5500 0000 0000 0004" required>
                </div>
                <div class="form-group ord-mock-card-row">
                    <div>
                        <label class="form-label" for="card_expiry">Expiry (MM/YY)</label>
                        <input type="text" id="card_expiry" name="card_expiry" class="form-control" inputmode="numeric" autocomplete="off" maxlength="5" placeholder="MM/YY" required>
                    </div>
                    <div>
                        <label class="form-label" for="card_cvv">CVV</label>
                        <input type="text" id="card_cvv" name="card_cvv" class="form-control" inputmode="numeric" autocomplete="off" maxlength="3" placeholder="123" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block mt-2">Pay <?php echo formatMoney((float) $order['finalAmount']); ?></button>

                <p class="ord-pay-trust">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2 4 5v6c0 5 3.5 9 8 11 4.5-2 8-6 8-11V5l-8-3z"></path></svg>
                    Your payment information is encrypted and secure.
                </p>

                <p class="ord-pay-testnote">
                    This is a built-in test gateway for demo purposes - no real card is charged.
                    <label class="ord-pay-simulate">
                        <input type="checkbox" name="simulate_failure" value="1"> Simulate a failed payment instead
                    </label>
                </p>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
