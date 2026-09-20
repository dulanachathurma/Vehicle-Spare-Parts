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
$pageJs = ['home.js', 'vehicle_filter.js'];
// No separate stylesheet - index.php is part of the frozen skeleton, so
// its styling lives in base.css alongside the rest of the shared chrome.

$categories = [];
$featuredParts = [];
$newParts = [];
$vehicleMakesByRegion = [];

$popularVehicles = [
    [
        'name' => 'Toyota Aqua',
        'make' => 'Toyota',
        'model' => 'Aqua',
        'chassis' => 'NHP10',
        'badge' => 'Hybrid',
        'desc' => '1.5L Petrol Hybrid (2011–2021)',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="22" height="22"><path d="M4 14h16M6 14l2-6h8l2 6M6 18a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm12 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>',
    ],
    [
        'name' => 'Toyota Prius',
        'make' => 'Toyota',
        'model' => 'Prius',
        'chassis' => 'ZVW30 / ZVW50',
        'badge' => 'Eco',
        'desc' => '1.8L Hybrid Liftback (2009–2022)',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="22" height="22"><path d="M3 14h18M5 14l2-6h10l2 6M6 18a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm12 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>',
    ],
    [
        'name' => 'Suzuki Wagon R',
        'make' => 'Suzuki',
        'model' => 'Wagon R',
        'chassis' => 'MH34S / MH44S / MH55S',
        'badge' => 'Kei Car',
        'desc' => '660cc Hybrid / FX / FZ / Stingray',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="22" height="22"><path d="M4 15h16M5 15V8a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v7M6 18a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm12 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>',
    ],
    [
        'name' => 'Honda Fit',
        'make' => 'Honda',
        'model' => 'Fit',
        'chassis' => 'GP5 / GK3',
        'badge' => 'Sport Hybrid',
        'desc' => '1.5L i-DCD / 1.3L Petrol',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="22" height="22"><path d="M3 14h18M5 14l2-6h10l2 6M6 18a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm12 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>',
    ],
    [
        'name' => 'Toyota Hiace',
        'make' => 'Toyota',
        'model' => 'Hiace',
        'chassis' => 'KDH200 / GDH200',
        'badge' => 'Van / Bus',
        'desc' => '2.5L / 2.8L Diesel Commuter & GL',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="22" height="22"><path d="M3 15h18V9a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6zM6 18a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm12 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM7 9v3m5-3v3m5-3v3"/></svg>',
    ],
    [
        'name' => 'Suzuki Every',
        'make' => 'Suzuki',
        'model' => 'Every',
        'chassis' => 'DA64V / DA17V',
        'badge' => 'Micro Van',
        'desc' => '660cc Join / PC / PA Turbo',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="22" height="22"><path d="M3 15h18V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v7zM6 18a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm12 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>',
    ],
];

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

    // Featured Products (Selected Listings - matching Screenshot 4)
    $featuredParts = $db->query(
        "SELECT sp.partID, sp.partName, sp.price, sp.imageURL, sp.stockQty, COALESCE(c.categoryName, 'SPARE PART') AS categoryName
         FROM spare_part sp
         LEFT JOIN category c ON c.categoryID = sp.categoryID
         WHERE sp.isActive = 1
         ORDER BY (sp.partName LIKE '%Audi S8%' OR sp.partName LIKE '%Citroen%' OR sp.partName LIKE '%BMW M2%' OR sp.partName LIKE '%Mercedes%' OR sp.partName LIKE '%Peugeot%') DESC, sp.price DESC
         LIMIT 8"
    )->fetchAll();

    // Newly Listed Parts (Fresh Inventory - matching Screenshot 5)
    $newParts = $db->query(
        "SELECT sp.partID, sp.partName, sp.price, sp.imageURL, sp.stockQty, COALESCE(c.categoryName, 'SPARE PART') AS categoryName
         FROM spare_part sp
         LEFT JOIN category c ON c.categoryID = sp.categoryID
         WHERE sp.isActive = 1
         ORDER BY (sp.partName LIKE '%Aqua%' OR sp.partName LIKE '%Axio%' OR sp.partName LIKE '%Allion%') DESC, sp.createdAt DESC, sp.partID DESC
         LIMIT 8"
    )->fetchAll();

    // Grouped Vehicle Makes by Region
    $stmt = $db->query("SELECT region, make FROM vehicle_model GROUP BY region, make ORDER BY FIELD(region, 'Japanese Vehicles', 'European Vehicles', 'American Vehicles', 'Chinese Vehicles', 'Indian Vehicles', 'Korean Vehicles'), region ASC, make ASC");
    $rawMakes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rawMakes as $row) {
        $reg = $row['region'] ?: 'Other Vehicles';
        if (!isset($vehicleMakesByRegion[$reg])) {
            $vehicleMakesByRegion[$reg] = [];
        }
        $vehicleMakesByRegion[$reg][] = $row['make'];
    }
} catch (PDOException $e) {
    // Catalogue tables may not exist yet in a partial build - the page
    // still renders with empty sections rather than a fatal error.
    $categories = [];
    $featuredParts = [];
    $newParts = [];
    $vehicleMakesByRegion = [];
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
<style>.js-reveal .home-section-head, .js-reveal .home-category-tile, .js-reveal .home-why-item, .js-reveal .home-part-card, .js-reveal .home-product-card, .js-reveal .home-fitment-card { opacity: 1 !important; transform: none !important; }</style>
</noscript>

<section class="home-hero">
    <div class="container text-center home-hero-intro">
        <h1 class="home-hero-main-title">Find the Right Part. Get Back on the Road.</h1>
        <p class="home-hero-main-subtitle">Search thousands of genuine, OEM and aftermarket parts from our trusted inventory across Sri Lanka.</p>
    </div>

    <div class="container">
        <div class="home-hero-card">
            <div class="home-hero-card-head">
                <h2 class="home-hero-card-title">FIND THE RIGHT PART <span class="home-hero-card-fast">FAST</span></h2>
                <p class="home-hero-card-subtitle">Search by Make, Model, Chassis Code or Part Name</p>
            </div>

            <form class="home-hero-vehicle-form" action="<?php echo BASE_URL; ?>/catalogue/search.php" method="get" data-vehicle-widget data-base-url="<?php echo e(BASE_URL); ?>">
                <div class="home-hero-bar">
                    <div class="home-hero-col home-hero-col-select">
                        <select id="homeMakeSelect" name="make" class="home-hero-select" data-selected="">
                            <option value="">Any Make</option>
                            <?php foreach ($vehicleMakesByRegion as $reg => $makes): ?>
                            <optgroup label="<?php echo e($reg); ?>">
                                <?php foreach ($makes as $vm): ?>
                                <option value="<?php echo e($vm); ?>"><?php echo e($vm); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="home-hero-col home-hero-col-select">
                        <select id="homeModelSelect" name="model" class="home-hero-select" data-selected="" disabled>
                            <option value="">Any Model</option>
                        </select>
                    </div>

                    <div class="home-hero-col home-hero-col-select">
                        <select id="homeChassisSelect" name="chassis" class="home-hero-select" data-selected="" disabled>
                            <option value="">Any Chassis Code</option>
                        </select>
                    </div>

                    <div class="home-hero-col home-hero-col-input">
                        <span class="home-hero-input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
                        </span>
                        <input type="text" name="keyword" class="home-hero-input" placeholder="Part Name...">
                    </div>

                    <button type="submit" class="home-hero-submit-btn">SEARCH</button>
                </div>
            </form>

            <div class="home-hero-pills">
                <span class="home-hero-pill">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    100% Secure Shopping
                </span>
                <span class="home-hero-pill">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="m9 12 2 2 4-4"></path><circle cx="12" cy="12" r="10"></circle></svg>
                    Quality Parts Guaranteed
                </span>
                <span class="home-hero-pill">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                    Fast Delivery Islandwide
                </span>
                <span class="home-hero-pill">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    Dedicated Support
                </span>
            </div>
        </div>
    </div>
</section>

<section class="home-features-bar">
    <div class="container home-features-grid">
        <div class="home-feature-box">
            <span class="home-feature-icon home-feature-icon--cyan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            </span>
            <div class="home-feature-info">
                <h4 class="home-feature-title">1,000+ Parts</h4>
                <p class="home-feature-desc">Across our verified inventory</p>
            </div>
        </div>

        <div class="home-feature-box">
            <span class="home-feature-icon home-feature-icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </span>
            <div class="home-feature-info">
                <h4 class="home-feature-title">Quality Guaranteed</h4>
                <p class="home-feature-desc">100% Genuine & OEM options</p>
            </div>
        </div>

        <div class="home-feature-box">
            <span class="home-feature-icon home-feature-icon--orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
            </span>
            <div class="home-feature-info">
                <h4 class="home-feature-title">Islandwide Delivery</h4>
                <p class="home-feature-desc">Fast courier across Sri Lanka</p>
            </div>
        </div>

        <a href="<?php echo SUPPORT_WHATSAPP_URL; ?>" target="_blank" rel="noopener noreferrer" class="home-feature-box" style="text-decoration: none; color: inherit;">
            <span class="home-feature-icon home-feature-icon--teal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            </span>
            <div class="home-feature-info">
                <h4 class="home-feature-title">Direct Support</h4>
                <p class="home-feature-desc">WhatsApp</p>
            </div>
        </a>
    </div>
</section>

<div class="container">
    <section class="home-section js-reveal">
        <div class="home-section-head">
            <div>
                <span class="home-section-eyebrow">QUICK DISCOVERY</span>
                <h2>Shop by Category</h2>
                <p class="text-muted">Explore the most popular automotive part families.</p>
            </div>
            <a class="home-section-more" href="<?php echo BASE_URL; ?>/catalogue/categories.php">View all categories &rarr;</a>
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

<!-- ===================================================================== -->
<!-- FEATURED PRODUCTS (Selected Listings - Matching Screenshot 4)        -->
<!-- ===================================================================== -->
<section class="home-section home-featured-section js-reveal">
    <div class="container">
        <div class="home-section-head">
            <div>
                <span class="home-section-eyebrow">SELECTED LISTINGS</span>
                <h2>Featured Products</h2>
                <p class="text-muted">Hand-picked performance and OEM replacement components in high demand.</p>
            </div>
            <a class="home-section-more" href="<?php echo BASE_URL; ?>/catalogue/products.php">View All Products &rarr;</a>
        </div>
        <?php if (empty($featuredParts)): ?>
        <p class="text-muted">Featured parts will appear here once the catalogue is populated.</p>
        <?php else: ?>
        <div class="home-product-grid">
            <?php foreach ($featuredParts as $index => $part): ?>
            <div class="home-product-card" style="transition-delay: <?php echo $index * 0.05; ?>s">
                <div class="home-product-thumb">
                    <span class="home-product-pill home-product-pill--featured">Featured</span>
                    <a href="<?php echo BASE_URL; ?>/catalogue/product_details.php?id=<?php echo (int) $part['partID']; ?>">
                        <?php echo partImage($part, 'md'); ?>
                    </a>
                </div>
                <div class="home-product-body">
                    <div class="home-product-category"><?php echo strtoupper(e($part['categoryName'])); ?></div>
                    <h3 class="home-product-title">
                        <a href="<?php echo BASE_URL; ?>/catalogue/product_details.php?id=<?php echo (int) $part['partID']; ?>"><?php echo e($part['partName']); ?></a>
                    </h3>
                    <div class="home-product-footer">
                        <span class="home-product-price"><?php echo formatMoney((float) $part['price']); ?></span>
                        <?php if ((int) $part['stockQty'] > 0): ?>
                        <span class="home-product-stock home-product-stock--in">
                            <span class="home-product-stock-dot"></span> In stock
                        </span>
                        <?php else: ?>
                        <span class="home-product-stock home-product-stock--out">Out of stock</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===================================================================== -->
<!-- NEWLY LISTED PARTS (Fresh Inventory - Matching Screenshot 5)         -->
<!-- ===================================================================== -->
<section class="home-section home-new-parts-section js-reveal">
    <div class="container">
        <div class="home-section-head">
            <div>
                <span class="home-section-eyebrow">FRESH INVENTORY</span>
                <h2>Newly Listed Parts</h2>
                <p class="text-muted">Freshly arrived stock and recent additions directly from verified distributors.</p>
            </div>
            <a class="home-section-more" href="<?php echo BASE_URL; ?>/catalogue/products.php?sort=newest">View All New Arrivals &rarr;</a>
        </div>
        <?php if (empty($newParts)): ?>
        <p class="text-muted">New arrivals will appear here once the catalogue is populated.</p>
        <?php else: ?>
        <div class="home-product-grid">
            <?php foreach ($newParts as $index => $part): ?>
            <div class="home-product-card" style="transition-delay: <?php echo $index * 0.05; ?>s">
                <div class="home-product-thumb">
                    <span class="home-product-pill home-product-pill--new">New</span>
                    <a href="<?php echo BASE_URL; ?>/catalogue/product_details.php?id=<?php echo (int) $part['partID']; ?>">
                        <?php echo partImage($part, 'md'); ?>
                    </a>
                </div>
                <div class="home-product-body">
                    <div class="home-product-category"><?php echo strtoupper(e($part['categoryName'])); ?></div>
                    <h3 class="home-product-title">
                        <a href="<?php echo BASE_URL; ?>/catalogue/product_details.php?id=<?php echo (int) $part['partID']; ?>"><?php echo e($part['partName']); ?></a>
                    </h3>
                    <div class="home-product-footer">
                        <span class="home-product-price"><?php echo formatMoney((float) $part['price']); ?></span>
                        <?php if ((int) $part['stockQty'] > 0): ?>
                        <span class="home-product-stock home-product-stock--in">
                            <span class="home-product-stock-dot"></span> In stock
                        </span>
                        <?php else: ?>
                        <span class="home-product-stock home-product-stock--out">Out of stock</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===================================================================== -->
<!-- BROWSE BY VEHICLE (Popular Fitments - Matching Screenshot 5)          -->
<!-- ===================================================================== -->
<section class="home-section home-fitment-section js-reveal">
    <div class="container">
        <div class="home-section-head">
            <div>
                <span class="home-section-eyebrow">POPULAR FITMENTS</span>
                <h2>Browse by Vehicle</h2>
                <p class="text-muted">Find precision-fit spare parts specifically matched to Sri Lanka's most popular daily drivers.</p>
            </div>
            <a class="home-section-more" href="<?php echo BASE_URL; ?>/catalogue/search.php">Search All Models &rarr;</a>
        </div>
        <div class="home-fitment-grid">
            <?php foreach ($popularVehicles as $i => $veh): ?>
            <a class="home-fitment-card" style="transition-delay: <?php echo $i * 0.06; ?>s" href="<?php echo BASE_URL; ?>/catalogue/search.php?make=<?php echo urlencode($veh['make']); ?>&model=<?php echo urlencode($veh['model']); ?>">
                <div class="home-fitment-head">
                    <span class="home-fitment-icon"><?php echo $veh['icon']; ?></span>
                    <span class="home-fitment-badge"><?php echo e($veh['badge']); ?></span>
                </div>
                <h3 class="home-fitment-name"><?php echo e($veh['name']); ?></h3>
                <div class="home-fitment-chassis">Chassis: <strong><?php echo e($veh['chassis']); ?></strong></div>
                <div class="home-fitment-desc"><?php echo e($veh['desc']); ?></div>
                <div class="home-fitment-action">
                    <span>Find Parts</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

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
