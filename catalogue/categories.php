<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/lib/search_helper.php';

$db = getDB();

$mainCatId = isset($_GET['main_cat']) && $_GET['main_cat'] !== '' ? (int) $_GET['main_cat'] : null;
$subCatId = isset($_GET['sub_cat']) && $_GET['sub_cat'] !== '' ? (int) $_GET['sub_cat'] : null;
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float) $_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float) $_GET['max_price'] : null;
$page = max(1, (int) ($_GET['page'] ?? 1));

// Fetch category tree
$categoryTree = catCategoryTree($db);

// Determine active category IDs
$activeCatIds = [];
$selectedCatId = $subCatId ?: $mainCatId;
if ($subCatId) {
    $activeCatIds = [$subCatId];
} elseif ($mainCatId) {
    $activeCatIds = catDescendantCategoryIds($db, $mainCatId);
}

// Build query
$where = ['sp.isActive = 1'];
$params = [];

if (!empty($activeCatIds)) {
    $where[] = 'sp.categoryID IN (' . catInPlaceholders('cat', $activeCatIds, $params) . ')';
}

if ($minPrice !== null && $minPrice > 0) {
    $where[] = 'sp.price >= :minPrice';
    $params['minPrice'] = $minPrice;
}

if ($maxPrice !== null && $maxPrice > 0) {
    $where[] = 'sp.price <= :maxPrice';
    $params['maxPrice'] = $maxPrice;
}

$whereSql = implode(' AND ', $where);

// Count
$countStmt = $db->prepare("SELECT COUNT(*) AS c FROM spare_part sp WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['c'];

$perPage = CAT_PER_PAGE;
$pagination = paginate($total, $perPage, $page);

// Fetch items
$sql = "SELECT sp.*, b.brandName, c.countryCode, cat.categoryName
        FROM spare_part sp
        JOIN brand b ON b.brandID = sp.brandID
        JOIN country c ON c.countryID = sp.countryID
        JOIN category cat ON cat.categoryID = sp.categoryID
        WHERE $whereSql
        ORDER BY sp.createdAt DESC
        LIMIT :lim OFFSET :off";

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v);
}
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Search Spare Parts by Category';
$pageCss = ['catalogue.css'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="cat-page-header text-center">
        <span class="home-section-eyebrow">SRI LANKA'S AUTOMOTIVE SPARE PARTS STORE</span>
        <h1 class="cat-page-title">Search Spare Parts by Category</h1>
        <p class="text-muted">Browse our store by product category and quickly narrow down live inventory.</p>
    </div>

    <!-- Category Selector Card matching screenshot 4 -->
    <div class="cat-selector-card">
        <div class="cat-selector-head">
            <h3>Find parts by category</h3>
            <p class="text-muted">Choose a main category and narrow it with a subcategory</p>
        </div>

        <form method="get" action="<?php echo BASE_URL; ?>/catalogue/categories.php" class="cat-selector-form" id="catSelectorForm">
            <div class="cat-selector-row">
                <div class="cat-selector-col">
                    <label class="form-label" for="mainCatSelect">Main Category</label>
                    <select id="mainCatSelect" name="main_cat" class="form-control">
                        <option value="">Select Main Category</option>
                        <?php foreach ($categoryTree as $top): ?>
                        <option value="<?php echo (int) $top['categoryID']; ?>" <?php echo $mainCatId === (int) $top['categoryID'] ? 'selected' : ''; ?>>
                            <?php echo e($top['categoryName']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="cat-selector-col">
                    <label class="form-label" for="subCatSelect">Subcategory</label>
                    <select id="subCatSelect" name="sub_cat" class="form-control" <?php echo empty($mainCatId) ? 'disabled' : ''; ?>>
                        <option value="">Select Subcategory</option>
                    </select>
                </div>

                <div class="cat-selector-btn-col">
                    <button type="submit" class="btn btn-primary cat-selector-submit">Search Category</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Category Results Layout -->
    <div class="cat-search-layout mt-3">
        <aside class="cat-filter-sidebar">
            <form method="get" action="<?php echo BASE_URL; ?>/catalogue/categories.php" class="card">
                <?php if ($mainCatId): ?><input type="hidden" name="main_cat" value="<?php echo (int) $mainCatId; ?>"><?php endif; ?>
                <?php if ($subCatId): ?><input type="hidden" name="sub_cat" value="<?php echo (int) $subCatId; ?>"><?php endif; ?>

                <h4 class="card-title">Price Filter (<?php echo e(CURRENCY); ?>)</h4>
                <div class="form-group">
                    <label class="form-label" for="min_price">Minimum</label>
                    <input type="number" id="min_price" name="min_price" class="form-control" value="<?php echo $minPrice !== null ? (int) $minPrice : ''; ?>" placeholder="0" min="0">
                </div>

                <div class="form-group">
                    <label class="form-label" for="max_price">Maximum</label>
                    <input type="number" id="max_price" name="max_price" class="form-control" value="<?php echo $maxPrice !== null ? (int) $maxPrice : ''; ?>" placeholder="100000" min="0">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Apply Price</button>
                <a class="btn btn-outline btn-block mt-1" href="<?php echo BASE_URL; ?>/catalogue/categories.php<?php echo $mainCatId ? '?main_cat=' . (int) $mainCatId : ''; ?>">Reset Price</a>
            </form>
        </aside>

        <div class="cat-search-results">
            <div class="cat-browse-header">
                <h2>Browse by Category</h2>
                <p class="text-muted">Refine the live product catalogue using price or subcategory filters.</p>
            </div>

            <p class="text-muted"><?php echo (int) $total; ?> part(s) available in this category.</p>

            <?php if (empty($parts)): ?>
            <div class="card text-center p-3">
                <p class="text-muted">No spare parts found for this category and price selection.</p>
                <p><a class="btn btn-accent mt-1" href="<?php echo BASE_URL; ?>/requests/submit_request.php">Request Out-of-Stock Part</a></p>
            </div>
            <?php else: ?>
            <div class="cat-grid">
                <?php foreach ($parts as $part): ?>
                    <?php echo catRenderPartCard($part); ?>
                <?php endforeach; ?>
            </div>
            <?php echo catRenderPagination($pagination, $_GET); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var categoryTree = <?php echo json_encode($categoryTree, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var mainSelect = document.getElementById('mainCatSelect');
    var subSelect = document.getElementById('subCatSelect');
    var initialSubId = <?php echo (int) ($subCatId ?? 0); ?>;

    function updateSubcategories(selectedMainId, preselectSubId) {
        subSelect.innerHTML = '<option value="">Select Subcategory</option>';
        if (!selectedMainId) {
            subSelect.disabled = true;
            return;
        }

        var found = null;
        for (var i = 0; i < categoryTree.length; i++) {
            if (parseInt(categoryTree[i].categoryID, 10) === parseInt(selectedMainId, 10)) {
                found = categoryTree[i];
                break;
            }
        }

        if (found && found.children && found.children.length > 0) {
            found.children.forEach(function (child) {
                var opt = document.createElement('option');
                opt.value = child.categoryID;
                opt.textContent = child.categoryName;
                if (preselectSubId && parseInt(child.categoryID, 10) === parseInt(preselectSubId, 10)) {
                    opt.selected = true;
                }
                subSelect.appendChild(opt);
            });
            subSelect.disabled = false;
        } else {
            subSelect.disabled = true;
        }
    }

    if (mainSelect && subSelect) {
        if (mainSelect.value) {
            updateSubcategories(mainSelect.value, initialSubId);
        }

        mainSelect.addEventListener('change', function () {
            updateSubcategories(this.value, 0);
        });
    }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
