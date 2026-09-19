<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../lib/inventory_helper.php';

requireAdmin();

$db = getDB();
$errors = [];
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('admin/parts/add_part.php');
    }

    $errors = invValidatePartForm($_POST);

    if (empty($errors)) {
        try {
            $imageFilename = invHandleImageUpload($_FILES['image'] ?? []);
            $partId = invCreatePart($db, $_POST, $imageFilename, currentAdminId());
            // Save vehicle compatibility rows
            $vehicleIds = array_filter(array_map('intval', (array) ($_POST['vehicle_ids'] ?? [])));
            invSaveCompatibility($db, $partId, $vehicleIds);
            setFlash('success', 'Part created.');
            redirect('admin/parts/list_parts.php');
        } catch (RuntimeException $e) {
            $errors['image'] = $e->getMessage();
        }
    }

    $old = $_POST;
}

$categoryTree = invCategoryTree($db);
$brands = $db->query('SELECT brandID, brandName FROM brand ORDER BY brandName')->fetchAll();
$countries = $db->query('SELECT countryID, countryName FROM country ORDER BY countryName')->fetchAll();
$makes = invGetMakes($db);
$oldVehicleIds = array_map('intval', (array) ($old['vehicle_ids'] ?? []));

$pageTitle = 'Add Part';
$pageCss = ['admin.css'];
$pageJs = ['admin.js'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Add Part</h1>

            <div class="card">
                <form method="post" action="<?php echo BASE_URL; ?>/admin/parts/add_part.php" enctype="multipart/form-data" data-validate novalidate>
                    <?php echo csrfField(); ?>

                    <div class="adm-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="part_name">Part Name</label>
                            <input type="text" id="part_name" name="part_name" class="form-control<?php echo isset($errors['part_name']) ? ' is-invalid' : ''; ?>" value="<?php echo e($old['part_name'] ?? ''); ?>" required>
                            <?php if (isset($errors['part_name'])): ?><p class="form-error"><?php echo e($errors['part_name']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="part_number">Part Number</label>
                            <input type="text" id="part_number" name="part_number" class="form-control<?php echo isset($errors['part_number']) ? ' is-invalid' : ''; ?>" value="<?php echo e($old['part_number'] ?? ''); ?>" required>
                            <?php if (isset($errors['part_number'])): ?><p class="form-error"><?php echo e($errors['part_number']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="category_id">Category</label>
                            <select id="category_id" name="category_id" class="form-control<?php echo isset($errors['category_id']) ? ' is-invalid' : ''; ?>" required>
                                <option value="">Select a category</option>
                                <?php echo invCategoryOptionsHtml($categoryTree, isset($old['category_id']) ? (int) $old['category_id'] : null); ?>
                            </select>
                            <?php if (isset($errors['category_id'])): ?><p class="form-error"><?php echo e($errors['category_id']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="brand_id">Brand</label>
                            <select id="brand_id" name="brand_id" class="form-control<?php echo isset($errors['brand_id']) ? ' is-invalid' : ''; ?>" required>
                                <option value="">Select a brand</option>
                                <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo (int) $brand['brandID']; ?>" <?php echo (isset($old['brand_id']) && (int) $old['brand_id'] === (int) $brand['brandID']) ? 'selected' : ''; ?>><?php echo e($brand['brandName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['brand_id'])): ?><p class="form-error"><?php echo e($errors['brand_id']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="country_id">Country of Origin</label>
                            <select id="country_id" name="country_id" class="form-control<?php echo isset($errors['country_id']) ? ' is-invalid' : ''; ?>" required>
                                <option value="">Select a country</option>
                                <?php foreach ($countries as $country): ?>
                                <option value="<?php echo (int) $country['countryID']; ?>" <?php echo (isset($old['country_id']) && (int) $old['country_id'] === (int) $country['countryID']) ? 'selected' : ''; ?>><?php echo e($country['countryName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['country_id'])): ?><p class="form-error"><?php echo e($errors['country_id']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="price">Price (<?php echo e(CURRENCY); ?>)</label>
                            <input type="number" id="price" name="price" class="form-control<?php echo isset($errors['price']) ? ' is-invalid' : ''; ?>" value="<?php echo e($old['price'] ?? ''); ?>" step="0.01" min="0" required>
                            <?php if (isset($errors['price'])): ?><p class="form-error"><?php echo e($errors['price']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="size">Size</label>
                            <input type="text" id="size" name="size" class="form-control" value="<?php echo e($old['size'] ?? ''); ?>" placeholder="Optional, e.g. 205/55 R16">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="stock_qty">Stock Quantity</label>
                            <input type="number" id="stock_qty" name="stock_qty" class="form-control<?php echo isset($errors['stock_qty']) ? ' is-invalid' : ''; ?>" value="<?php echo e($old['stock_qty'] ?? '0'); ?>" min="0" required>
                            <?php if (isset($errors['stock_qty'])): ?><p class="form-error"><?php echo e($errors['stock_qty']); ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="min_stock_level">Minimum Stock Level</label>
                            <input type="number" id="min_stock_level" name="min_stock_level" class="form-control<?php echo isset($errors['min_stock_level']) ? ' is-invalid' : ''; ?>" value="<?php echo e($old['min_stock_level'] ?? '5'); ?>" min="0" required>
                            <?php if (isset($errors['min_stock_level'])): ?><p class="form-error"><?php echo e($errors['min_stock_level']); ?></p><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?php echo e($old['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="image">Image (JPG, PNG or WebP, max 2 MB)</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" data-adm-image-preview>
                        <img class="adm-image-preview-target" alt="" hidden>
                        <?php if (isset($errors['image'])): ?><p class="form-error"><?php echo e($errors['image']); ?></p><?php endif; ?>
                    </div>

                    <!-- Vehicle Compatibility -->
                    <div class="form-group compat-section">
                        <label class="form-label">Vehicle Compatibility <span style="font-weight:400;font-size:.85em;color:var(--text-muted)">(optional — select all models this part fits)</span></label>

                        <div class="compat-picker">
                            <div class="compat-make-col">
                                <label class="compat-sublabel" for="compat_make">1. Choose Make</label>
                                <select id="compat_make" class="form-control">
                                    <option value="">— Select Make —</option>
                                    <?php foreach ($makes as $mk): ?>
                                    <option value="<?php echo e($mk); ?>"><?php echo e($mk); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="compat-model-col">
                                <label class="compat-sublabel">2. Tick Compatible Models</label>
                                <div id="compat_models_list" class="compat-models-list">
                                    <p class="compat-placeholder">Select a make first.</p>
                                </div>
                            </div>
                        </div>

                        <div id="compat_selected_tags" class="compat-tags"></div>
                    </div>
                    <!-- hidden inputs written by JS -->
                    <div id="compat_hidden_inputs"></div>

                    <button type="submit" class="btn btn-primary">Create Part</button>
                    <a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/parts/list_parts.php">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const makeSelect  = document.getElementById('compat_make');
    const modelsList  = document.getElementById('compat_models_list');
    const tagsBox     = document.getElementById('compat_selected_tags');
    const hiddenBox   = document.getElementById('compat_hidden_inputs');
    const preselected = <?php echo json_encode($oldVehicleIds); ?>;

    // Sync hidden inputs & tag pills from checked checkboxes
    function syncSelection() {
        const checked = modelsList.querySelectorAll('input[type=checkbox]:checked');
        hiddenBox.innerHTML = '';
        tagsBox.innerHTML   = '';
        checked.forEach(cb => {
            const inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = 'vehicle_ids[]';
            inp.value = cb.value;
            hiddenBox.appendChild(inp);

            const tag = document.createElement('span');
            tag.className   = 'compat-tag';
            tag.textContent = cb.dataset.label;
            tagsBox.appendChild(tag);
        });
    }

    makeSelect.addEventListener('change', function () {
        const make = this.value;
        if (!make) { modelsList.innerHTML = '<p class="compat-placeholder">Select a make first.</p>'; return; }

        modelsList.innerHTML = '<p class="compat-placeholder">Loading…</p>';

        fetch(`<?php echo BASE_URL; ?>/admin/parts/ajax_models.php?make=${encodeURIComponent(make)}`)
            .then(r => r.json())
            .then(models => {
                if (!models.length) {
                    modelsList.innerHTML = '<p class="compat-placeholder">No models found for this make.</p>';
                    return;
                }
                modelsList.innerHTML = '';
                models.forEach(m => {
                    const label = document.createElement('label');
                    label.className = 'compat-model-row';
                    const cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.value = m.vehicleID;
                    const txt = `${m.model} <span class="compat-chassis">${m.chassisCode}${m.yearRange ? ' · '+m.yearRange : ''}</span>`;
                    cb.dataset.label = `${make} ${m.model} (${m.chassisCode})`;
                    if (preselected.includes(Number(m.vehicleID))) cb.checked = true;
                    cb.addEventListener('change', syncSelection);
                    label.appendChild(cb);
                    label.insertAdjacentHTML('beforeend', ' ' + txt);
                    modelsList.appendChild(label);
                });
                syncSelection();
            })
            .catch(() => { modelsList.innerHTML = '<p class="compat-placeholder" style="color:var(--danger)">Failed to load models.</p>'; });
    });
}());
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
