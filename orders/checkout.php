<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/order_helper.php';

requireLogin();

$db = getDB();
$userId = currentUserId();
$user = currentUser();
$cart = getOrCreateCart($db, $userId);
$items = cartItemsForCart($db, (int) $cart['cartID']);

if (empty($items)) {
    setFlash('info', 'Your cart is empty.');
    redirect('orders/cart.php');
}

$stockProblems = validateStock($items);
if (!empty($stockProblems)) {
    setFlash('error', 'Please resolve the stock issues in your cart before checking out.');
    redirect('orders/cart.php');
}

$totals = calculateTotals(cartTotal($items));
$gateways = $db->query('SELECT * FROM payment_gateway WHERE isActive = 1 ORDER BY gatewayID DESC')->fetchAll();

/** A small icon + subtitle per payment gateway, purely decorative. */
function gatewayVisual(string $gatewayName): array
{
    if (stripos($gatewayName, 'stripe') !== false) {
        return [
            'name' => 'Stripe Card Payment',
            'icon' => '<span class="ord-pay-method-cards"><span class="ord-mini-visa">VISA</span><span class="ord-mini-mc"><i></i><i></i></span></span>',
            'sub' => 'Visa, Mastercard, Amex via Stripe Secure Checkout',
        ];
    }


    if (stripos($gatewayName, 'payhere') !== false) {
        return [
            'name' => $gatewayName,
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="6" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path><path d="M6 15h4"></path></svg>',
            'sub' => 'Cards, eZ Cash, mobile banking and more',
        ];
    }

    return [
        'name' => $gatewayName,
        'icon' => '<span class="ord-pay-method-cards"><span class="ord-mini-visa">VISA</span><span class="ord-mini-mc"><i></i><i></i></span></span>',
        'sub' => 'Visa, Mastercard and other major cards',
    ];
}

$pageTitle = 'Checkout';
$pageCss = ['orders.css'];
$pageJs = ['cart.js'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container ord-checkout-page">
    <h1>Checkout</h1>

    <div class="ord-checkout-layout">
        <form method="post" action="<?php echo BASE_URL; ?>/orders/place_order.php" id="ordCheckoutForm" data-validate novalidate>
            <?php echo csrfField(); ?>

            <div class="card mb-2">
                <h2 class="card-title">Delivery Details</h2>
                <div class="form-group">
                    <label class="form-label" for="recipient_name">Full Name</label>
                    <input type="text" id="recipient_name" name="recipient_name" class="form-control" value="<?php echo e($user['username'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="recipient_phone">Phone Number</label>
                    <input type="tel" id="recipient_phone" name="recipient_phone" class="form-control" placeholder="07X XXX XXXX" value="<?php echo e($user['phone'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="shipping_address">Shipping Address</label>
                    <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3" required><?php echo e($user['address'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="card mb-2">
                <h2 class="card-title">Payment Method</h2>
                <?php if (empty($gateways)): ?>
                <p class="form-error">No payment methods are currently available. Please contact the shop.</p>
                <?php endif; ?>
                <?php foreach ($gateways as $i => $gateway): $visual = gatewayVisual($gateway['gatewayName']); ?>
                <label class="ord-pay-method">
                    <input type="radio" name="gateway_id" value="<?php echo (int) $gateway['gatewayID']; ?>" <?php echo $i === 0 ? 'checked' : ''; ?> required>
                    <span class="ord-pay-method-icon"><?php echo $visual['icon']; ?></span>
                    <span class="ord-pay-method-text">
                        <span class="ord-pay-method-name"><?php echo e($visual['name'] ?? $gateway['gatewayName']); ?></span>
                        <span class="ord-pay-method-sub"><?php echo e($visual['sub']); ?></span>
                    </span>
                    <span class="ord-pay-method-check">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block" <?php echo empty($gateways) ? 'disabled' : ''; ?>>Proceed to Payment</button>
        </form>

        <aside class="card ord-order-summary">
            <h2 class="card-title">Order Summary</h2>
            <ul class="ord-summary-list">
            <?php foreach ($items as $item): ?>
                <li><?php echo e($item['partName']); ?> &times; <?php echo (int) $item['quantity']; ?> <span><?php echo formatMoney((float) $item['price'] * (int) $item['quantity']); ?></span></li>
            <?php endforeach; ?>
            </ul>
            <p>Subtotal <span><?php echo formatMoney($totals['subtotal']); ?></span></p>
            <p>Tax (<?php echo (float) TAX_RATE; ?>%) <span><?php echo formatMoney($totals['taxAmount']); ?></span></p>
            <p class="ord-summary-final">Total <span><?php echo formatMoney($totals['finalAmount']); ?></span></p>
        </aside>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
