<?php

declare(strict_types=1);

/**
 * orders/lib/return_helper.php - validation and status logic for
 * customer return/refund requests, shared by orders/submit_return.php
 * and admin/orders/manage_returns.php + update_return.php. Kept out of
 * orders/lib/order_helper.php (and out of includes/functions.php) so
 * it stays a self-contained addition on top of the frozen order flow,
 * the same way requests/lib/request_helper.php is kept separate for
 * product requests.
 */

const RETURN_REASONS = ['Defective / Damaged', 'Wrong Item Received', 'No Longer Needed', 'Other'];
const RETURN_STATUSES = ['Pending', 'Approved', 'Rejected'];

/** The return request already filed for an order, if any. */
function returnRequestForOrder(PDO $db, int $orderId): ?array
{
    $stmt = $db->prepare('SELECT * FROM return_request WHERE orderID = ?');
    $stmt->execute([$orderId]);

    return $stmt->fetch() ?: null;
}

/**
 * Files a return request for a Delivered order the customer owns.
 * Only one request per order (the table's UNIQUE orderID enforces this
 * too, but checking first gives a clean error instead of a raw
 * constraint-violation exception).
 */
function submitReturnRequest(PDO $db, int $orderId, int $userId, string $reasonCategory, string $description): int
{
    $stmt = $db->prepare('SELECT status FROM orders WHERE orderID = ? AND userID = ?');
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new RuntimeException('Order not found.');
    }
    if ($order['status'] !== 'Delivered') {
        throw new RuntimeException('Only delivered orders can be returned.');
    }
    if (returnRequestForOrder($db, $orderId)) {
        throw new RuntimeException('A return request already exists for this order.');
    }
    if (!in_array($reasonCategory, RETURN_REASONS, true)) {
        throw new RuntimeException('Please choose a valid reason.');
    }

    $insert = $db->prepare(
        'INSERT INTO return_request (orderID, userID, reasonCategory, description, status, requestedAt)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );
    $insert->execute([$orderId, $userId, $reasonCategory, $description !== '' ? $description : null, 'Pending']);

    return (int) $db->lastInsertId();
}

/**
 * Approves a Pending return request and refunds it in full, reusing
 * the same adminProcessRefund() an admin's manual refund uses - see
 * payment/lib/payment_helper.php (the caller must require that file).
 */
function approveReturnRequest(PDO $db, int $returnRequestId, int $adminId, ?string $notes): void
{
    $stmt = $db->prepare(
        'SELECT rr.*, o.finalAmount
         FROM return_request rr JOIN orders o ON o.orderID = rr.orderID
         WHERE rr.returnRequestID = ?'
    );
    $stmt->execute([$returnRequestId]);
    $returnRequest = $stmt->fetch();

    if (!$returnRequest) {
        throw new RuntimeException('Return request not found.');
    }
    if ($returnRequest['status'] !== 'Pending') {
        throw new RuntimeException('This return request has already been resolved.');
    }

    $db->beginTransaction();

    try {
        adminProcessRefund($db, (int) $returnRequest['orderID'], (float) $returnRequest['finalAmount']);

        $update = $db->prepare(
            "UPDATE return_request SET status = 'Approved', adminID = ?, adminNotes = ?, resolvedAt = NOW() WHERE returnRequestID = ?"
        );
        $update->execute([$adminId, $notes, $returnRequestId]);

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function rejectReturnRequest(PDO $db, int $returnRequestId, int $adminId, ?string $notes): void
{
    $stmt = $db->prepare("SELECT status FROM return_request WHERE returnRequestID = ?");
    $stmt->execute([$returnRequestId]);
    $returnRequest = $stmt->fetch();

    if (!$returnRequest) {
        throw new RuntimeException('Return request not found.');
    }
    if ($returnRequest['status'] !== 'Pending') {
        throw new RuntimeException('This return request has already been resolved.');
    }

    $update = $db->prepare(
        "UPDATE return_request SET status = 'Rejected', adminID = ?, adminNotes = ?, resolvedAt = NOW() WHERE returnRequestID = ?"
    );
    $update->execute([$adminId, $notes, $returnRequestId]);
}
