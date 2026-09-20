<?php
/**
 * test_db.php — AutoParts Lanka Deployment Diagnostic Tool
 * Upload this file to InfinityFree htdocs/test_db.php to check your connection status.
 */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>AutoParts Lanka - System Diagnostics</title>";
echo "<style>
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 30px; background: #0f172a; color: #e2e8f0; line-height: 1.6; }
.card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 28px; max-width: 720px; margin: 0 auto; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
h1 { margin-top: 0; color: #38bdf8; font-size: 24px; border-bottom: 1px solid #334155; padding-bottom: 16px; }
.badge-ok { background: #14532d; color: #4ade80; padding: 3px 10px; border-radius: 6px; font-weight: 600; font-size: 13px; }
.badge-err { background: #7f1d1d; color: #f87171; padding: 3px 10px; border-radius: 6px; font-weight: 600; font-size: 13px; }
.step { background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 16px; margin: 16px 0; }
.step h3 { margin-top: 0; font-size: 16px; color: #94a3b8; }
.err-box { background: #450a0a; border: 1px solid #991b1b; padding: 14px; border-radius: 8px; color: #fca5a5; margin-top: 10px; word-break: break-all; font-family: monospace; }
pre { background: #0f172a; padding: 12px; border-radius: 6px; overflow-x: auto; color: #a5f3fc; }
code { background: #334155; padding: 2px 6px; border-radius: 4px; color: #f1f5f9; font-size: 14px; }
</style></head><body>";

echo "<div class='card'>";
echo "<h1>🚗 AutoParts Lanka — Diagnostics</h1>";

// 1. PHP Version
echo "<div class='step'>";
echo "<h3>1. PHP Environment</h3>";
echo "PHP Version: <b>" . PHP_VERSION . "</b> <span class='badge-ok'>OK</span>";
echo "</div>";

// 2. config.local.php check
echo "<div class='step'>";
echo "<h3>2. Configuration File (config/config.local.php)</h3>";
$configPath = __DIR__ . '/config/config.local.php';
if (!file_exists($configPath)) {
    echo "<p><span class='badge-err'>FILE MISSING</span></p>";
    echo "<div class='err-box'>❌ <b>htdocs/config/config.local.php does NOT exist!</b><br><br>";
    echo "Your live website has no database credentials. It is trying to connect to 'localhost' with user 'root', which causes HTTP 500.<br><br>";
    echo "👉 <b>FIX:</b> Go to InfinityFree File Manager → open folder <b>htdocs/config</b> → click <b>+ New File</b> → name it <code>config.local.php</code> and paste your production database credentials.</div>";
} else {
    echo "<p><span class='badge-ok'>FOUND</span> <code>htdocs/config/config.local.php</code></p>";
    require_once $configPath;

    $host = defined('DB_HOST') ? DB_HOST : '';
    $user = defined('DB_USER') ? DB_USER : '';
    $db   = defined('DB_NAME') ? DB_NAME : '';
    $pass = defined('DB_PASS') ? DB_PASS : '';

    echo "<ul>";
    echo "<li><b>DB_HOST:</b> <code>" . htmlspecialchars($host) . "</code></li>";
    echo "<li><b>DB_USER:</b> <code>" . htmlspecialchars($user) . "</code></li>";
    echo "<li><b>DB_NAME:</b> <code>" . htmlspecialchars($db) . "</code></li>";
    echo "<li><b>DB_PASS:</b> " . (strlen($pass) > 0 ? "<code>" . str_repeat('•', strlen($pass)) . "</code> (" . strlen($pass) . " characters)" : "<span class='badge-err'>EMPTY</span>") . "</li>";
    echo "</ul>";

    // 3. MySQL PDO connection test
    echo "</div><div class='step'>";
    echo "<h3>3. MySQL Database Connection</h3>";
    try {
        $dsn = 'mysql:host=' . $host . ';dbname=' . $db . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo "<p><span class='badge-ok'>CONNECTED SUCCESSFULLY</span> to <code>" . htmlspecialchars($host) . "</code></p>";

        // 4. Tables check
        echo "</div><div class='step'>";
        echo "<h3>4. Database Tables & Content</h3>";
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Total tables found: <b>" . count($tables) . "</b></p>";

        if (empty($tables)) {
            echo "<p><span class='badge-err'>DATABASE IS EMPTY</span></p>";
            echo "<div class='err-box'>❌ Connected to database <code>" . htmlspecialchars($db) . "</code>, but it contains <b>0 tables</b>!<br><br>";
            echo "👉 <b>FIX:</b> Open phpMyAdmin in InfinityFree → select <code>" . htmlspecialchars($db) . "</code> → click <b>Import</b> → upload <code>database/deploy_dump.sql</code>.</div>";
        } else {
            echo "<p><span class='badge-ok'>TABLES DETECTED</span></p>";
            echo "<pre>" . implode(', ', $tables) . "</pre>";

            if (in_array('spare_part', $tables)) {
                $count = (int) $pdo->query("SELECT COUNT(*) FROM spare_part")->fetchColumn();
                echo "<p>• <b>spare_part</b> count: <code>" . $count . "</code></p>";
            }
            if (in_array('category', $tables)) {
                $count = (int) $pdo->query("SELECT COUNT(*) FROM category")->fetchColumn();
                echo "<p>• <b>category</b> count: <code>" . $count . "</code></p>";
            }
            if (in_array('admin', $tables)) {
                $count = (int) $pdo->query("SELECT COUNT(*) FROM admin")->fetchColumn();
                echo "<p>• <b>admin</b> count: <code>" . $count . "</code></p>";
            }
        }
    } catch (PDOException $e) {
        echo "<p><span class='badge-err'>CONNECTION FAILED</span></p>";
        echo "<div class='err-box'><b>MySQL Error:</b><br>" . htmlspecialchars($e->getMessage()) . "<br><br>";
        if (strpos($e->getMessage(), 'Access denied') !== false) {
            echo "👉 <b>Check password:</b> Your password in InfinityFree is <code>Chan1nduSE11</code> (with a number '1'). Make sure it is copied accurately into <code>config.local.php</code>.";
        } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
            echo "👉 <b>Check database name:</b> Make sure the database name is <code>" . htmlspecialchars($db) . "</code>.";
        } elseif (strpos($e->getMessage(), 'Connection refused') !== false || strpos($e->getMessage(), 'getaddrinfo') !== false) {
            echo "👉 <b>Check host:</b> Make sure DB_HOST is set to <code>sql211.infinityfree.com</code> (not localhost).";
        }
        echo "</div>";
    }
}
echo "</div>";

echo "<p style='text-align:center;color:#64748b;font-size:13px;'>Remember to delete <code>test_db.php</code> after testing is complete.</p>";
echo "</div></body></html>";
