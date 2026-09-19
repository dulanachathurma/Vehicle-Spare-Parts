<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../orders/lib/return_helper.php';
require_once __DIR__ . '/../../payment/lib/payment_helper.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? null)) {
    setFlash('error', 'Invalid request.');
    redirect('admin/orders/manage_returns.php');
}

$db = getDB();
$returnRequestId = (int) ($_POST['return_request_id'] ?? 0);
$action = $_POST['action'] ?? '';
$notes = trim($_POST['admin_notes'] ?? '') ?: null;

try {
    if ($action === 'approve') {
        approveReturnRequest($db, $returnRequestId, currentAdminId(), $notes);
        setFlash('success', 'Return approved and refunded.');
    } elseif ($action === 'reject') {
        rejectReturnRequest($db, $returnRequestId, currentAdminId(), $notes);
        setFlash('success', 'Return request rejected.');
    } else {
        setFlash('error', 'Unknown action.');
    }
} catch (Throwable $e) {
    setFlash('error', $e->getMessage());
}

redirect('admin/orders/manage_returns.php?id=' . $returnRequestId);
