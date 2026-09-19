<?php

declare(strict_types=1);

/**
 * payment/lib/stripe_service.php - Professional Stripe payment service layer.
 *
 * Implements server-side checkout session creation, cryptographic webhook
 * verification, atomic stock reduction, and idempotent payment confirmation
 * for AutoParts Lanka.
 */

require_once __DIR__ . '/../../config/stripe.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/payment_helper.php';

/**
 * Validates that all items in an order are still active and have sufficient stock.
 *
 * @return array<array{partID:int, partName:string, reason:string}>
 */
function validateOrderStock(PDO $db, int $orderId): array
{
    $stmt = $db->prepare(
        'SELECT oi.partID, oi.quantity, sp.partName, sp.stockQty, sp.isActive
         FROM order_item oi
         JOIN spare_part sp ON sp.partID = oi.partID
         WHERE oi.orderID = ?'
    );
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();

    $problems = [];
    foreach ($items as $item) {
        if ((int) $item['isActive'] === 0) {
            $problems[] = [
                'partID' => (int) $item['partID'],
                'partName' => (string) $item['partName'],
                'reason' => 'is no longer available',
            ];
        } elseif ((int) $item['quantity'] > (int) $item['stockQty']) {
            $problems[] = [
                'partID' => (int) $item['partID'],
                'partName' => (string) $item['partName'],
                'reason' => 'only ' . (int) $item['stockQty'] . ' units currently available (requested ' . (int) $item['quantity'] . ')',
            ];
        }
    }

    return $problems;
}

/**
 * Creates a Stripe Checkout Session on the server using database-verified amounts.
 *
 * @throws RuntimeException If order not found, access denied, stock insufficient, or Stripe API error
 */
function createStripeCheckoutSession(PDO $db, int $orderId, int $userId): \Stripe\Checkout\Session
{
    $stmt = $db->prepare(
        'SELECT o.*, p.paymentID, p.status AS paymentStatus, p.gatewayID, g.gatewayName
         FROM orders o
         JOIN payment p ON p.orderID = o.orderID
         JOIN payment_gateway g ON g.gatewayID = p.gatewayID
         WHERE o.orderID = ? AND o.userID = ?'
    );
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new RuntimeException('Order not found or access denied.');
    }

    if (!in_array($order['paymentStatus'], ['Pending', 'Failed'], true)) {
        throw new RuntimeException('This order is not awaiting payment.');
    }

    // 1. Validate real-time stock in MySQL
    $stockProblems = validateOrderStock($db, $orderId);
    if (!empty($stockProblems)) {
        $reasons = array_map(static fn($p) => $p['partName'] . ' ' . $p['reason'], $stockProblems);
        throw new RuntimeException('Stock check failed: ' . implode(', ', $reasons));
    }

    // 2. Fetch order items
    $itemStmt = $db->prepare(
        'SELECT oi.*, sp.partName, sp.partNumber, sp.imageURL
         FROM order_item oi
         JOIN spare_part sp ON sp.partID = oi.partID
         WHERE oi.orderID = ?'
    );
    $itemStmt->execute([$orderId]);
    $items = $itemStmt->fetchAll();

    if (empty($items)) {
        throw new RuntimeException('Order has no items.');
    }

    $currency = getStripeCurrency();
    $stripeClient = getStripeClient();

    // 3. Build line items with server-verified prices
    $lineItems = [];
    foreach ($items as $item) {
        $unitPrice = (float) $item['unitPrice'];
        // Smallest currency unit: 100 for 2-decimal currencies (LKR, USD)
        $unitAmount = (int) round($unitPrice * 100);

        $productData = [
            'name' => (string) $item['partName'],
        ];

        if (!empty($item['partNumber'])) {
            $productData['description'] = 'Part Number: ' . $item['partNumber'];
        }

        // Add public image URL if available and valid
        if (!empty($item['imageURL'])) {
            $imgUrl = (filter_var($item['imageURL'], FILTER_VALIDATE_URL))
                ? $item['imageURL']
                : rtrim(BASE_URL, '/') . '/' . ltrim((string) $item['imageURL'], '/');
            if (filter_var($imgUrl, FILTER_VALIDATE_URL) && !preg_match('/localhost|127\.0\.0\.1/', $imgUrl)) {
                $productData['images'] = [$imgUrl];
            }
        }

        $lineItems[] = [
            'price_data' => [
                'currency' => $currency,
                'unit_amount' => $unitAmount,
                'product_data' => $productData,
            ],
            'quantity' => (int) $item['quantity'],
        ];
    }

    // Include tax if applicable
    $taxAmount = (float) ($order['taxAmount'] ?? 0);
    if ($taxAmount > 0) {
        $lineItems[] = [
            'price_data' => [
                'currency' => $currency,
                'unit_amount' => (int) round($taxAmount * 100),
                'product_data' => [
                    'name' => 'Tax (' . (float) TAX_RATE . '%)',
                ],
            ],
            'quantity' => 1,
        ];
    }

    $user = currentUser();
    $customerEmail = !empty($user['email']) ? $user['email'] : null;

    // 4. Create Stripe Checkout Session
    $sessionParams = [
        'payment_method_types' => ['card'],
        'line_items' => $lineItems,
        'mode' => 'payment',
        'client_reference_id' => (string) $orderId,
        'metadata' => [
            'order_id' => (string) $orderId,
            'user_id' => (string) $userId,
            'site' => SITE_NAME,
        ],
        'success_url' => BASE_URL . '/payment/payment_success.php?session_id={CHECKOUT_SESSION_ID}&order=' . $orderId,
        'cancel_url' => BASE_URL . '/payment/payment_cancel.php?order=' . $orderId . '&stripe=1',
    ];

    if ($customerEmail !== null) {
        $sessionParams['customer_email'] = $customerEmail;
    }

    $session = $stripeClient->checkout->sessions->create($sessionParams);

    // 5. Save the session ID in the database for tracking
    $updateStmt = $db->prepare('UPDATE payment SET stripe_session_id = ? WHERE orderID = ?');
    $updateStmt->execute([$session->id, $orderId]);

    return $session;
}

/**
 * Idempotently confirms a Stripe payment in MySQL with atomic stock reduction.
 *
 * If the payment is already marked 'Success', this function immediately returns true
 * without reducing stock or modifying order state a second time.
 *
 * @return bool True if payment confirmed or already confirmed
 */
function confirmStripePayment(PDO $db, int $orderId, ?string $paymentIntentId, ?string $sessionId = null): bool
{
    $db->beginTransaction();

    try {
        // Lock order and payment rows to prevent race conditions / duplicate webhook handling
        $stmt = $db->prepare(
            'SELECT o.orderID, o.status AS orderStatus, p.paymentID, p.status AS paymentStatus, p.gatewayID
             FROM orders o
             JOIN payment p ON p.orderID = o.orderID
             WHERE o.orderID = ?
             FOR UPDATE'
        );
        $stmt->execute([$orderId]);
        $record = $stmt->fetch();

        if (!$record) {
            $db->rollBack();
            return false;
        }

        // Idempotency: If already confirmed, avoid duplicate stock deduction
        if ($record['paymentStatus'] === 'Success') {
            $db->commit();
            return true;
        }

        // Fetch order items with lock
        $itemStmt = $db->prepare(
            'SELECT oi.partID, oi.quantity, sp.stockQty, sp.partName
             FROM order_item oi
             JOIN spare_part sp ON sp.partID = oi.partID
             WHERE oi.orderID = ?
             FOR UPDATE'
        );
        $itemStmt->execute([$orderId]);
        $items = $itemStmt->fetchAll();

        // Atomically decrement stock
        $stockStmt = $db->prepare(
            'UPDATE spare_part SET stockQty = stockQty - ? WHERE partID = ? AND stockQty >= ?'
        );

        foreach ($items as $item) {
            $qty = (int) $item['quantity'];
            $partId = (int) $item['partID'];

            $stockStmt->execute([$qty, $partId, $qty]);
            if ($stockStmt->rowCount() === 0) {
                // Safeguard against negative stock if stock changed in race
                $fallbackStmt = $db->prepare(
                    'UPDATE spare_part SET stockQty = GREATEST(0, stockQty - ?) WHERE partID = ?'
                );
                $fallbackStmt->execute([$qty, $partId]);
                error_log(sprintf(
                    '[Stripe] Low stock race on part #%d (%s) for order #%d',
                    $partId,
                    $item['partName'],
                    $orderId
                ));
            }
        }

        // Update payment table
        $updatePayment = $db->prepare(
            "UPDATE payment
             SET status = 'Success',
                 transactionID = COALESCE(?, transactionID),
                 stripe_session_id = COALESCE(?, stripe_session_id),
                 stripe_payment_intent_id = COALESCE(?, stripe_payment_intent_id),
                 paidAt = NOW()
             WHERE orderID = ?"
        );
        $updatePayment->execute([
            $paymentIntentId ?: $sessionId,
            $sessionId,
            $paymentIntentId,
            $orderId,
        ]);

        // Update orders table
        $updateOrder = $db->prepare(
            "UPDATE orders SET status = 'Confirmed' WHERE orderID = ? AND status = 'Pending'"
        );
        $updateOrder->execute([$orderId]);

        $db->commit();
        return true;
    } catch (Throwable $e) {
        $db->rollBack();
        error_log(sprintf('[Stripe] confirmStripePayment failed for order #%d: %s', $orderId, $e->getMessage()));
        throw $e;
    }
}

/**
 * Cryptographically verifies an incoming Stripe webhook signature.
 *
 * @throws \UnexpectedValueException If payload is invalid
 * @throws \Stripe\Exception\SignatureVerificationException If signature verification fails
 */
function verifyWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
{
    $secret = getStripeWebhookSecret();
    if ($secret === '') {
        throw new RuntimeException('Stripe webhook signing secret is not configured.');
    }

    return \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
}

/**
 * Retrieves a Stripe Checkout Session directly from Stripe API.
 */
function getStripeSession(string $sessionId): ?\Stripe\Checkout\Session
{
    try {
        return getStripeClient()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent'],
        ]);
    } catch (Throwable $e) {
        error_log('[Stripe] Failed to retrieve session: ' . $e->getMessage());
        return null;
    }
}
