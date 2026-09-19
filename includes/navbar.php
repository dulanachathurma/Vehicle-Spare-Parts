<?php

declare(strict_types=1);

/**
 * includes/navbar.php - public site navigation.
 * FROZEN: see docs/PROJECT_BRIEF.md, Section 3.
 *
 * Contains links to every member's customer-facing pages, including
 * pages that do not exist yet in an early build - that is expected
 * (see PROJECT_BRIEF.md, Section 3, Rule 1). The cart badge count comes
 * from cartItemCount(), which Module 3 implements in
 * orders/lib/order_helper.php; the function_exists() guard lets this
 * frozen navbar render safely before that module is built.
 */

$navUser = isLoggedIn() ? currentUser() : null;
$navCartCount = null;
if (isLoggedIn() && function_exists('cartItemCount')) {
    $navCartCount = cartItemCount();
}
?>
<nav class="navbar navbar-expand-lg navbar-main">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>/index.php">
            <span class="navbar-brand-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                </svg>
            </span>
            <?php echo e(SITE_NAME); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/catalogue/products.php">Shop</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/catalogue/search.php">Search</a></li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/orders/my_orders.php">My Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/requests/my_requests.php">My Requests</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link nav-cart-link" href="<?php echo BASE_URL; ?>/orders/cart.php">
                        Cart
                        <?php if ($navCartCount !== null && $navCartCount > 0): ?>
                        <span class="nav-cart-badge"><?php echo (int) $navCartCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navAccountDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo e($navUser['username'] ?? 'Account'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navAccountDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/auth/profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/requests/submit_request.php">Request a Part</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/auth/logout.php">Logout</a></li>
                    </ul>
                </li>
                <?php elseif (isAdmin()): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admin/dashboard.php">Admin Panel</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/auth/logout.php">Logout</a></li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/auth/login.php">Login</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/auth/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
