<?php
require_once '../includes/config.php';

echo "<h1>Database Debug</h1>";

try {
    // 1. Check Connection
    echo "<h2>1. Connection</h2>";
    echo "Connected to database: $db_name<br>";

    // 2. Check Schema
    echo "<h2>2. Table Schema</h2>";
    $stmt = $pdo->query("DESCRIBE visits");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
    }
    echo "</table>";

    // 3. Test Queries
    echo "<h2>3. Test Queries</h2>";

    // Device Stats
    echo "<h3>Device Stats</h3>";
    try {
        $sql = "SELECT COALESCE(device_model, 'Unknown') as model, COUNT(*) as count FROM visits GROUP BY device_model ORDER BY count DESC";
        $pdo->query($sql);
        echo "<span style='color:green'>OK</span><br>";
    } catch (PDOException $e) {
        echo "<span style='color:red'>FAIL: " . $e->getMessage() . "</span><br>";
    }

    // OS Stats
    echo "<h3>OS Stats</h3>";
    try {
        $sql = "SELECT COALESCE(os, 'Unknown') as os_name, COUNT(*) as count FROM visits GROUP BY os ORDER BY count DESC";
        $pdo->query($sql);
        echo "<span style='color:green'>OK</span><br>";
    } catch (PDOException $e) {
        echo "<span style='color:red'>FAIL: " . $e->getMessage() . "</span><br>";
    }

    // Traffic
    echo "<h3>Traffic</h3>";
    try {
        $sql = "SELECT DATE_FORMAT(visit_time, '%H:00') as hour, COUNT(*) as count 
                FROM visits 
                WHERE visit_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                GROUP BY DATE_FORMAT(visit_time, '%H:00') 
                ORDER BY MIN(visit_time) ASC";
        $pdo->query($sql);
        echo "<span style='color:green'>OK</span><br>";
    } catch (PDOException $e) {
        echo "<span style='color:red'>FAIL: " . $e->getMessage() . "</span><br>";
    }

} catch (PDOException $e) {
    echo "CRITICAL ERROR: " . $e->getMessage();
}
?>