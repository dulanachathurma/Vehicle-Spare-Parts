<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/search_helper.php';

$db = getDB();
$partId = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare(
    'SELECT sp.*, b.brandName, b.isAuthorized, c.countryName, c.countryCode, c.importDutyRate, cat.categoryName
     FROM spare_part sp
     JOIN brand b ON b.brandID = sp.brandID
     JOIN country c ON c.countryID = sp.countryID
     JOIN category cat ON cat.categoryID = sp.categoryID
     WHERE sp.partID = ? AND sp.isActive = 1'
);
$stmt->execute([$partId]);
$part = $stmt->fetch();

if (!$part) {
    setFlash('error', 'That part could not be found.');
    redirect('catalogue/products.php');
}

$relatedStmt = $db->prepare(
    'SELECT sp.*, b.brandName, c.countryCode
     FROM spare_part sp
     JOIN brand b ON b.brandID = sp.brandID
     JOIN country c ON c.countryID = sp.countryID
     WHERE sp.categoryID = ? AND sp.partID != ? AND sp.isActive = 1
     ORDER BY sp.createdAt DESC
     LIMIT 6'
);
$relatedStmt->execute([$part['categoryID'], $partId]);
$relatedParts = $relatedStmt->fetchAll();

$compatibleVehicles = catPartCompatibleVehicles($db, $partId);

$outOfStock = (int) $part['stockQty'] <= 0;

$pageTitle = $part['partName'];
$pageCss = ['catalogue.css'];
$pageJs = ['catalogue.js'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="cat-detail">
        <div class="cat-detail-image">
            <?php echo partImage($part, 'lg'); ?>
        </div>
        <div class="cat-detail-info">
            <h1><?php echo e($part['partName']); ?></h1>
            <p class="text-muted">Part No. <?php echo e($part['partNumber']); ?> &middot; <?php echo e($part['categoryName']); ?></p>

            <p class="cat-detail-price"><?php echo formatMoney((float) $part['price']); ?></p>

            <p>
                <span class="badge-status badge-status--<?php echo $outOfStock ? 'cancelled' : 'success'; ?>">
                    <?php echo $outOfStock ? 'Out of Stock' : 'In Stock (' . (int) $part['stockQty'] . ' available)'; ?>
                </span>
            </p>

            <table class="table-plain cat-detail-specs">
                <tr>
                    <th>Brand</th>
                    <td>
                        <?php echo e($part['brandName']); ?>
                        <?php if ($part['isAuthorized']): ?><span class="badge-status badge-status--info">Authorised Distributor</span><?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Country of Origin</th>
                    <td><?php echo e($part['countryName']); ?> (<?php echo e($part['countryCode']); ?>) &middot; Import duty <?php echo number_format((float) $part['importDutyRate'], 2); ?>%</td>
                </tr>
                <?php if (!empty($part['size'])): ?>
                <tr><th>Size</th><td><?php echo e($part['size']); ?></td></tr>
                <?php endif; ?>
            </table>

            <?php if (!empty($part['description'])): ?>
            <h2>Description</h2>
            <p><?php echo nl2br(e($part['description'])); ?></p>
            <?php endif; ?>

            <?php if (!empty($compatibleVehicles)): ?>
            <div class="cat-detail-vehicles mt-2">
                <h3 class="cat-detail-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="vertical-align: -2px; margin-right: 4px;"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"></path><circle cx="7" cy="17" r="2"></circle><path d="M9 17h6"></path><circle cx="17" cy="17" r="2"></circle></svg>
                    Compatible Vehicles
                </h3>
                <div class="cat-vehicle-tags">
                    <?php foreach ($compatibleVehicles as $cv): ?>
                    <span class="cat-vehicle-tag">
                        <strong><?php echo e($cv['make'] . ' ' . $cv['model']); ?></strong>
                        <span class="cat-vehicle-code"><?php echo e($cv['chassisCode']); ?></span>
                        <?php if (!empty($cv['yearRange'])): ?>
                        <span class="cat-vehicle-years"><?php echo e($cv['yearRange']); ?></span>
                        <?php endif; ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="cat-detail-actions mt-2">
                <?php if ($outOfStock): ?>
                <a class="btn btn-accent" href="<?php echo BASE_URL; ?>/requests/submit_request.php?part_name=<?php echo urlencode($part['partName']); ?>">Request this part</a>
                <?php elseif (isLoggedIn()): ?>
                <form method="post" action="<?php echo BASE_URL; ?>/orders/cart_action.php" class="cat-detail-cart-form">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="part_id" value="<?php echo (int) $part['partID']; ?>">
                    <div class="form-group cat-qty-group">
                        <label class="form-label" for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" value="1" min="1" max="<?php echo (int) $part['stockQty']; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Add to Cart</button>
                </form>
                <?php else: ?>
                <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/auth/login.php">Log in to order</a>
                <?php endif; ?>

                <a class="btn btn-outline cat-whatsapp-btn" target="_blank" rel="noopener noreferrer" href="https://wa.me/94770000000?text=<?php echo urlencode('Hi AutoParts Lanka, I would like to check compatibility for ' . $part['partName'] . ' (Part No: ' . $part['partNumber'] . ')'); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="vertical-align: -3px; margin-right: 5px;"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    WhatsApp Inquiry
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($relatedParts)): ?>
    <section class="home-section">
        <h2>Related Parts</h2>
        <div class="cat-grid">
            <?php foreach ($relatedParts as $related): ?>
                <?php echo catRenderPartCard($related); ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
