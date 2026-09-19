<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../lib/inventory_helper.php';

requireAdmin();

header('Content-Type: application/json; charset=utf-8');

$make = trim($_GET['make'] ?? '');
if ($make === '') {
    echo '[]';
    exit;
}

$db = getDB();
$models = invGetModelsByMake($db, $make);

// Cast vehicleID to int so JSON is clean
foreach ($models as &$row) {
    $row['vehicleID'] = (int) $row['vehicleID'];
}
unset($row);

echo json_encode($models, JSON_UNESCAPED_UNICODE);
