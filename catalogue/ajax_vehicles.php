<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$action = (string) ($_GET['action'] ?? 'all');
$db = getDB();

try {
    if ($action === 'makes') {
        $stmt = $db->query("SELECT region, make FROM vehicle_model GROUP BY region, make ORDER BY FIELD(region, 'Japanese Vehicles', 'European Vehicles', 'American Vehicles', 'Chinese Vehicles', 'Indian Vehicles', 'Korean Vehicles'), region ASC, make ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $grouped = [];
        foreach ($rows as $row) {
            $r = $row['region'] ?: 'Other Vehicles';
            if (!isset($grouped[$r])) {
                $grouped[$r] = [];
            }
            $grouped[$r][] = $row['make'];
        }
        echo json_encode(['success' => true, 'data' => $grouped]);
        exit;
    }

    if ($action === 'models') {
        $make = trim((string) ($_GET['make'] ?? ''));
        $stmt = $db->prepare('SELECT DISTINCT model FROM vehicle_model WHERE make = ? ORDER BY model ASC');
        $stmt->execute([$make]);
        $models = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['success' => true, 'data' => $models]);
        exit;
    }

    if ($action === 'chassis') {
        $make = trim((string) ($_GET['make'] ?? ''));
        $model = trim((string) ($_GET['model'] ?? ''));
        $stmt = $db->prepare('SELECT DISTINCT chassisCode, yearRange FROM vehicle_model WHERE make = ? AND model = ? ORDER BY chassisCode ASC');
        $stmt->execute([$make, $model]);
        $chassis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $chassis]);
        exit;
    }

    // Default: Return complete vehicle tree + grouped regions for instant client-side cascading
    $stmt = $db->query("SELECT region, make, model, chassisCode, yearRange FROM vehicle_model ORDER BY FIELD(region, 'Japanese Vehicles', 'European Vehicles', 'American Vehicles', 'Chinese Vehicles', 'Indian Vehicles', 'Korean Vehicles'), region ASC, make ASC, model ASC, chassisCode ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tree = [];
    $regions = [];
    foreach ($rows as $row) {
        $region = $row['region'] ?: 'Other Vehicles';
        $make = $row['make'];
        $model = $row['model'];
        $chassis = $row['chassisCode'];

        if (!isset($regions[$region])) {
            $regions[$region] = [];
        }
        if (!in_array($make, $regions[$region], true)) {
            $regions[$region][] = $make;
        }

        if (!isset($tree[$make])) {
            $tree[$make] = [];
        }
        if (!isset($tree[$make][$model])) {
            $tree[$make][$model] = [];
        }
        $tree[$make][$model][] = [
            'chassisCode' => $chassis,
            'yearRange' => $row['yearRange'],
        ];
    }

    echo json_encode(['success' => true, 'data' => ['tree' => $tree, 'regions' => $regions]]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
