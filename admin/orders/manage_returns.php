<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../orders/lib/return_helper.php';

requireAdmin();

$db = getDB();
$statusFilter = $_GET['status'] ?? '';
$viewId = isset($_GET['id']) ? (int) $_GET['id'] : null;

$sql = 'SELECT rr.*, o.finalAmount, o.orderDate, u.username, u.email
        FROM return_request rr
        JOIN orders o ON o.orderID = rr.orderID
        JOIN registered_user u ON u.userID = rr.userID
        WHERE 1=1';
$params = [];
if (in_array($statusFilter, RETURN_STATUSES, true)) {
    $sql .= ' AND rr.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY rr.requestedAt DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$returns = $stmt->fetchAll();

$viewReturn = null;
if ($viewId) {
    $vStmt = $db->prepare(
        'SELECT rr.*, o.finalAmount, o.orderDate, u.username, u.email
         FROM return_request rr
         JOIN orders o ON o.orderID = rr.orderID
         JOIN registered_user u ON u.userID = rr.userID
         WHERE rr.returnRequestID = ?'
    );
    $vStmt->execute([$viewId]);
    $viewReturn = $vStmt->fetch();
}

$pageTitle = 'Return Requests';
$pageCss = ['admin.css'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Return Requests</h1>

            <form method="get" class="adm-filter-bar">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach (RETURN_STATUSES as $status): ?>
                    <option value="<?php echo e($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <table class="table-plain mt-2">
                <thead><tr><th>Order</th><th>Customer</th><th>Reason</th><th>Requested</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($returns)): ?>
                    <tr><td colspan="6" class="text-muted">No return requests found.</td></tr>
                <?php endif; ?>
                <?php foreach ($returns as $return): ?>
                    <tr>
                        <td>#<?php echo (int) $return['orderID']; ?></td>
                        <td><?php echo e($return['username']); ?></td>
                        <td><?php echo e($return['reasonCategory']); ?></td>
                        <td><?php echo e(date('Y-m-d', strtotime($return['requestedAt']))); ?></td>
                        <td>
                            <span class="badge-status badge-status--<?php echo strtolower($return['status']); ?>">
                                <?php echo e($return['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a class="btn btn-sm btn-outline" href="<?php echo BASE_URL; ?>/admin/orders/manage_returns.php?id=<?php echo (int) $return['returnRequestID']; ?><?php echo $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?>">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($viewReturn): ?>
            <div class="card mt-3">
                <h2 class="card-title">Return Request #<?php echo (int) $viewReturn['returnRequestID']; ?> - Order #<?php echo (int) $viewReturn['orderID']; ?></h2>
                <p>Customer: <?php echo e($viewReturn['username']); ?> (<?php echo e($viewReturn['email']); ?>)</p>
                <p>Order Total: <?php echo formatMoney((float) $viewReturn['finalAmount']); ?> &middot; Ordered: <?php echo e(date('Y-m-d', strtotime($viewReturn['orderDate']))); ?></p>
                <p>Reason: <?php echo e($viewReturn['reasonCategory']); ?></p>
                <p>Details: <?php echo e($viewReturn['description'] ?? '-'); ?></p>
                <p>Status:
                    <span class="badge-status badge-status--<?php echo strtolower($viewReturn['status']); ?>"><?php echo e($viewReturn['status']); ?></span>
                </p>

                <?php if ($viewReturn['status'] === 'Pending'): ?>
                <form method="post" action="<?php echo BASE_URL; ?>/admin/orders/update_return.php" class="adm-inline-form">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="return_request_id" value="<?php echo (int) $viewReturn['returnRequestID']; ?>">
                    <div class="form-group">
                        <label class="form-label" for="admin_notes">Note to Customer</label>
                        <textarea id="admin_notes" name="admin_notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" name="action" value="approve" class="btn btn-primary" data-confirm="Approve this return and refund the order in full?">Approve &amp; Refund</button>
                    <button type="submit" name="action" value="reject" class="btn btn-danger" data-confirm="Reject this return request?">Reject</button>
                </form>
                <?php else: ?>
                <p class="text-muted">Resolved <?php echo e(date('Y-m-d', strtotime($viewReturn['resolvedAt']))); ?><?php echo !empty($viewReturn['adminNotes']) ? ' - ' . e($viewReturn['adminNotes']) : ''; ?></p>
                <?php endif; ?>

                <a class="btn btn-sm btn-outline mt-1" href="<?php echo BASE_URL; ?>/admin/orders/manage_orders.php?id=<?php echo (int) $viewReturn['orderID']; ?>">View Order</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
