<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/return_helper.php';

requireLogin();

$db = getDB();
$userId = currentUserId();
$orderId = (int) ($_GET['order'] ?? $_POST['order_id'] ?? 0);

$stmt = $db->prepare('SELECT orderID, status FROM orders WHERE orderID = ? AND userID = ?');
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    redirect('orders/my_orders.php');
}
if ($order['status'] !== 'Delivered') {
    setFlash('error', 'Only delivered orders can be returned.');
    redirect('orders/order_details.php?id=' . $orderId);
}
if (returnRequestForOrder($db, $orderId)) {
    setFlash('info', 'You already have a return request for this order.');
    redirect('orders/order_details.php?id=' . $orderId);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('orders/submit_return.php?order=' . $orderId);
    }

    $reason = $_POST['reason'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (!in_array($reason, RETURN_REASONS, true)) {
        $errors['reason'] = 'Please choose a reason.';
    }

    if (empty($errors)) {
        try {
            submitReturnRequest($db, $orderId, $userId, $reason, $description);
            setFlash('success', 'Your return request has been submitted. We will review it shortly.');
            redirect('orders/order_details.php?id=' . $orderId);
        } catch (Throwable $e) {
            setFlash('error', $e->getMessage());
            redirect('orders/order_details.php?id=' . $orderId);
        }
    }

    $_SESSION['old_input'] = $_POST;
}

$pageTitle = 'Return Order #' . $orderId;
$pageCss = ['admin.css', 'orders.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container req-form-page">
    <div class="card req-form-card">
        <h1 class="card-title">Return Order #<?php echo (int) $orderId; ?></h1>
        <p class="text-muted">Tell us what went wrong and we'll review your request. Approved returns are refunded to your original payment method.</p>

        <form method="post" action="<?php echo BASE_URL; ?>/orders/submit_return.php?order=<?php echo (int) $orderId; ?>" data-validate novalidate>
            <?php echo csrfField(); ?>
            <input type="hidden" name="order_id" value="<?php echo (int) $orderId; ?>">

            <div class="form-group">
                <label class="form-label" for="reason">Reason for Return</label>
                <select id="reason" name="reason" class="form-control<?php echo isset($errors['reason']) ? ' is-invalid' : ''; ?>" required>
                    <option value="">Choose a reason...</option>
                    <?php foreach (RETURN_REASONS as $reasonOption): ?>
                    <option value="<?php echo e($reasonOption); ?>" <?php echo ($_SESSION['old_input']['reason'] ?? '') === $reasonOption ? 'selected' : ''; ?>><?php echo e($reasonOption); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['reason'])): ?><p class="form-error"><?php echo e($errors['reason']); ?></p><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Additional Details</label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Describe the issue in a bit more detail..."><?php echo oldInput('description'); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Submit Return Request</button>
            <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/orders/order_details.php?id=<?php echo (int) $orderId; ?>">Cancel</a>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
