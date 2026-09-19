<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

/**
 * index.php - storefront landing page.
 * FROZEN: see docs/PROJECT_BRIEF.md, Section 3.
 *
 * Hero search box, category tiles and a handful of featured parts.
 * Category and part data come from Module 2's tables, so this page
 * degrades gracefully (empty sections, no error) until Module 2 exists.
 */

$pageTitle = 'Home';
$pageJs = ['home.js'];
// No separate stylesheet - index.php is part of the frozen skeleton, so
// its styling lives in base.css alongside the rest of the shared chrome.

$categories = [];
$featuredParts = [];

try {
    $db = getDB();

    $categories = $db->query(
        'SELECT c.categoryID, c.categoryName, COUNT(sp.partID) AS partCount
         FROM category c
         LEFT JOIN category child ON child.parentCategoryID = c.categoryID
         LEFT JOIN spare_part sp ON sp.isActive = 1 AND (sp.categoryID = c.categoryID OR sp.categoryID = child.categoryID)
         WHERE c.parentCategoryID IS NULL
         GROUP BY c.categoryID, c.categoryName
         ORDER BY c.categoryName
         LIMIT 8'
    )->fetchAll();

    $featuredParts = $db->query(
        'SELECT partID, partName, price, imageURL, stockQty FROM spare_part WHERE isActive = 1 ORDER BY createdAt DESC LIMIT 8'
    )->fetchAll();
} catch (PDOException $e) {
    // Catalogue tables may not exist yet in a partial build - the page
    // still renders with empty sections rather than a fatal error.
    $categories = [];
    $featuredParts = [];
}

/**
 * A small hand-drawn icon per known category name, purely decorative -
 * falls back to a generic gear icon for any category not in the map
 * (e.g. a new one an admin adds later).
 */
function homeCategoryIcon(string $categoryName): string
{
    $icons = [
        'Engine Parts' => '<circle cx="12" cy="12" r="3"></circle><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"></path>',
        'Brake System' => '<circle cx="12" cy="12" r="8"></circle><circle cx="12" cy="12" r="2.5"></circle><path d="M12 6v2.5M12 15.5V18M6 12h2.5M15.5 12H18"></path>',
        'Suspension & Steering' => '<circle cx="12" cy="12" r="8"></circle><path d="M12 4v5M12 15v5M6.2 8l4 2.5M17.8 8l-4 2.5M6.2 16l4-2.5M17.8 16l-4-2.5"></path>',
        'Electrical & Lighting' => '<path d="M13 2 4 14h6l-1 8 9-12h-6l1-8z"></path>',
        'Filters & Fluids' => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"></path>',
        'Transmission & Clutch' => '<path d="M17 2.1 21 6l-3.9 3.9M3 12h13M7 21.9 3 18l3.9-3.9M21 12H8"></path>',
        'Body & Exterior' => '<path d="M3 16.5V13l2-4.5A2 2 0 0 1 6.85 7.2h10.3A2 2 0 0 1 19 8.5L21 13v3.5"></path><path d="M3 16.5h18M6.5 16.5v2M17.5 16.5v2"></path><circle cx="7" cy="16.5" r="1.4"></circle><circle cx="17" cy="16.5" r="1.4"></circle>',
        'Wheels & Tyres' => '<circle cx="12" cy="12" r="8"></circle><circle cx="12" cy="12" r="2.2"></circle><path d="m12 4 1.2 4M12 20l1.2-4M4 12l4-1.2M20 12l-4-1.2M6.3 6.3l3 2.7M17.7 6.3l-3 2.7M6.3 17.7l3-2.7M17.7 17.7l-3-2.7"></path>',
    ];

    $path = $icons[$categoryName] ?? '<circle cx="12" cy="12" r="3"></circle><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"></path>';

    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

$homeWhyItems = [
    [
        'tone' => 'primary',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2 4 5v6c0 5 3.5 9 8 11 4.5-2 8-6 8-11V5l-8-3z"></path><path d="m9 12 2 2 4-4"></path></svg>',
        'title' => 'Genuine Parts Guarantee',
        'text' => 'Every part is sourced directly from authorized, trusted brands.',
    ],
    [
        'tone' => 'accent',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="7" width="13" height="9" rx="1"></rect><path d="M14 10h4l3 3v3h-7z"></path><circle cx="6" cy="18" r="1.8"></circle><circle cx="17" cy="18" r="1.8"></circle></svg>',
        'title' => 'Island-wide Delivery',
        'text' => 'Fast, reliable delivery to every district in Sri Lanka.',
    ],
    [
        'tone' => 'info',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="9" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>',
        'title' => 'Secure Checkout',
        'text' => 'Your payments are protected with secure, encrypted checkout.',
    ],
    [
        'tone' => 'success',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.34-5.66"></path><path d="M20 4v5h-5"></path></svg>',
        'title' => 'Easy Returns',
        'text' => 'Not the right fit? Hassle-free returns within 7 days.',
    ],
];

require __DIR__ . '/includes/header.php';
?>

<noscript>
<style>.js-reveal .home-section-head, .js-reveal .home-category-tile, .js-reveal .home-why-item, .js-reveal .home-part-card { opacity: 1 !important; transform: none !important; }</style>
</noscript>

<section class="home-hero">
    <div class="container home-hero-inner">
        <div class="home-hero-content">
            <span class="home-hero-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
                Trusted by hundreds of vehicle owners across Sri Lanka
            </span>
            <h1>Find the right spare part, fast.</h1>
            <p class="text-muted">Search by part name, brand, size or your target price.</p>
            <form class="home-hero-search" action="<?php echo BASE_URL; ?>/catalogue/search.php" method="get">
                <input
                    type="text"
                    name="keyword"
                    class="form-control"
                    placeholder="Search by part name, part number or description..."
                >
                <button type="submit" class="btn btn-accent">Search</button>
            </form>
        </div>
        <div class="home-hero-art">
            <div class="home-hero-photo-frame">
                <img
                    src="<?php echo BASE_URL; ?>/assets/images/hero-banner.jpg?v=<?php echo (int) @filemtime(__DIR__ . '/assets/images/hero-banner.jpg'); ?>"
                    alt="Spare parts and tools organised on a workshop wall"
                    class="home-hero-photo"
                >
                <span class="home-hero-photo-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
                    Genuine Parts
                </span>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <section class="home-section js-reveal">
        <div class="home-section-head">
            <h2>Browse by Category</h2>
        </div>
        <?php if (empty($categories)): ?>
        <p class="text-muted">Categories will appear here once the catalogue is populated.</p>
        <?php else: ?>
        <div class="home-category-grid">
            <?php foreach ($categories as $i => $category): ?>
            <a class="home-category-tile" style="transition-delay: <?php echo $i * 0.06; ?>s" href="<?php echo BASE_URL; ?>/catalogue/products.php?category=<?php echo (int) $category['categoryID']; ?>">
                <span class="home-category-icon"><?php echo homeCategoryIcon($category['categoryName']); ?></span>
                <span class="home-category-name"><?php echo e($category['categoryName']); ?></span>
                <span class="home-category-count"><?php echo (int) $category['partCount']; ?> part<?php echo ((int) $category['partCount'] === 1) ? '' : 's'; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</div>

<section class="home-why js-reveal">
    <div class="container">
        <div class="home-section-head">
            <h2>Why Shop With Us</h2>
        </div>
        <div class="home-why-grid">
            <?php foreach ($homeWhyItems as $i => $item): ?>
            <div class="home-why-item" style="transition-delay: <?php echo $i * 0.08; ?>s">
                <span class="home-why-icon home-why-icon--<?php echo e($item['tone']); ?>"><?php echo $item['icon']; ?></span>
                <p class="home-why-title"><?php echo e($item['title']); ?></p>
                <p class="home-why-text"><?php echo e($item['text']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="home-featured js-reveal">
    <div class="container">
        <div class="home-section-head">
            <h2>Featured Parts</h2>
            <a href="<?php echo BASE_URL; ?>/catalogue/products.php">View All &rarr;</a>
        </div>
        <?php if (empty($featuredParts)): ?>
        <p class="text-muted">Featured parts will appear here once the catalogue is populated.</p>
        <?php else: ?>
        <div class="home-part-grid">
            <?php foreach ($featuredParts as $index => $part): ?>
            <div class="home-part-card" style="transition-delay: <?php echo $index * 0.06; ?>s">
                <?php if ($index < 3): ?>
                <span class="home-part-badge">New</span>
                <?php endif; ?>
                <a class="home-part-card-link" href="<?php echo BASE_URL; ?>/catalogue/product_details.php?id=<?php echo (int) $part['partID']; ?>">
                    <?php echo partImage($part, 'md'); ?>
                    <p class="home-part-name"><?php echo e($part['partName']); ?></p>
                    <p class="home-part-price"><?php echo formatMoney((float) $part['price']); ?></p>
                </a>
                <?php if ((int) $part['stockQty'] > 0): ?>
                <form class="home-part-quickadd" method="post" action="<?php echo BASE_URL; ?>/orders/cart_action.php">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="part_id" value="<?php echo (int) $part['partID']; ?>">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Add to Cart</button>
                </form>
                <?php else: ?>
                <p class="home-part-quickadd home-part-outofstock">Out of Stock</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="home-cta">
    <div class="container home-cta-inner">
        <span class="home-cta-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path><path d="M11 8v3M11 14v.01"></path></svg>
        </span>
        <div class="home-cta-text">
            <h2>Can't find the part you need?</h2>
            <p>Tell us what you're looking for and our team will help you source it.</p>
        </div>
        <a class="btn" href="<?php echo BASE_URL; ?>/requests/submit_request.php">Request a Part &rarr;</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
