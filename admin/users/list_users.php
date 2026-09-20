<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

requireAdmin();

$db = getDB();

$search = trim((string) ($_GET['q'] ?? ''));

$sql = 'SELECT u.userID, u.username, u.email, u.phone, u.address, u.registeredAt, u.isVerified,
               COUNT(o.orderID) AS orderCount,
               COALESCE(SUM(CASE WHEN o.status != "Cancelled" THEN o.totalAmount ELSE 0 END), 0) AS totalSpent
        FROM registered_user u
        LEFT JOIN orders o ON o.userID = u.userID';

$params = [];
if ($search !== '') {
    $sql .= ' WHERE u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ?';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}

$sql .= ' GROUP BY u.userID, u.username, u.email, u.phone, u.address, u.registeredAt, u.isVerified
          ORDER BY u.registeredAt DESC, u.userID DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Registered Users';
$pageCss = ['admin.css'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <div class="adm-page-header">
                <div>
                    <h1>Registered Users</h1>
                    <p class="text-muted">Customers registered on AutoParts Lanka.</p>
                </div>
            </div>

            <form method="get" class="adm-filter-bar mb-3" style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="text" name="q" class="form-control" placeholder="Search by username, email or phone..." value="<?php echo e($search); ?>" style="max-width: 320px;">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search !== ''): ?>
                <a href="<?php echo BASE_URL; ?>/admin/users/list_users.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>

            <table class="table-plain">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Joined</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="8" class="text-muted">No registered users found.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>#<?php echo (int) $user['userID']; ?></td>
                        <td>
                            <strong><?php echo e($user['username']); ?></strong><br>
                            <span class="text-muted small"><?php echo e($user['email']); ?></span>
                        </td>
                        <td><?php echo e($user['phone'] ?: '—'); ?></td>
                        <td><small><?php echo e($user['address'] ?: '—'); ?></small></td>
                        <td>
                            <?php if ((int) $user['orderCount'] > 0): ?>
                            <a href="<?php echo BASE_URL; ?>/admin/orders/manage_orders.php?user_id=<?php echo (int) $user['userID']; ?>" class="badge-status badge-status--info" style="text-decoration: none;">
                                <?php echo (int) $user['orderCount']; ?> orders
                            </a>
                            <?php else: ?>
                            <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatMoney((float) $user['totalSpent']); ?></td>
                        <td><?php echo e(date('M d, Y', strtotime($user['registeredAt']))); ?></td>
                        <td>
                            <span class="badge-status badge-status--<?php echo (int) $user['isVerified'] === 1 ? 'success' : 'warning'; ?>">
                                <?php echo (int) $user['isVerified'] === 1 ? 'Verified' : 'Pending'; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
