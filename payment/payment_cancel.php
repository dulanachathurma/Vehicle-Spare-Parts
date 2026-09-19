<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/payment_helper.php';

requireLogin();

// Reached two different ways: our own "Cancel Payment" button on
// pay.php (a confirmed POST with order_id), and PayHere's own hosted
// checkout page redirecting the browser back to this exact URL as a
// plain GET (with ?order=) when the customer backs out there - PayHere
// controls that redirect and can't attach our CSRF token, so this
// endpoint has to accept both.
$db = getDB();
$orderId = (int) ($_POST['order_id'] ?? $_GET['order'] ?? 0);
$userId = currentUserId();

$stmt = $db->prepare('SELECT orderID FROM orders WHERE orderID = ? AND userID = ?');
$stmt->execute([$orderId, $userId]);

if ($stmt->fetch()) {
    markPaymentFailed($db, $orderId);
}

setFlash('warning', 'Payment was not completed. Your order is saved as pending - you can try paying again from My Orders.');
redirect('orders/my_orders.php');
