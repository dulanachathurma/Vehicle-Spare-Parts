<?php

declare(strict_types=1);

/**
 * includes/footer.php - closes the document, loads base.js then each
 * page's own scripts.
 * FROZEN: see docs/PROJECT_BRIEF.md, Section 3 and Section 4.
 */

$pageJs = $pageJs ?? [];
?>
</main>
<footer class="site-footer">
    <div class="container">
        <div class="site-footer-grid">
            <div>
                <p class="site-footer-brand"><?php echo e(SITE_NAME); ?></p>
                <p class="site-footer-tagline">Genuine and quality vehicle spare parts, sourced from trusted brands and delivered island-wide.</p>
            </div>
            <div>
                <p class="site-footer-heading">Quick Links</p>
                <ul class="site-footer-links">
                    <li><a href="<?php echo BASE_URL; ?>/catalogue/products.php">Shop All Parts</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/catalogue/search.php">Advanced Search</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/auth/login.php">Log In</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/auth/register.php">Create Account</a></li>
                </ul>
            </div>
            <div>
                <p class="site-footer-heading">Get in Touch</p>
                <ul class="site-footer-contact">
                    <li>123 Galle Road, Colombo 03, Sri Lanka</li>
                    <li><a href="https://wa.me/94703587028" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: none;">+94 70 358 7028 (WhatsApp)</a></li>
                    <li>support@autopartslanka.lk</li>
                </ul>
            </div>
        </div>
        <div class="site-footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo e(SITE_NAME); ?>. All rights reserved.</p>
            <p class="site-footer-note">Vehicle Spare Parts Management System</p>
        </div>
    </div>
</footer>
<script src="<?php echo BASE_URL; ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/base.js?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/js/base.js'); ?>"></script>
<?php foreach ($pageJs as $js): ?>
<script src="<?php echo BASE_URL; ?>/assets/js/<?php echo e($js); ?>?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/js/' . $js); ?>"></script>
<?php endforeach; ?>
</body>
</html>
