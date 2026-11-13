<?php
require_once __DIR__ . '/../../config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h1>Database Tables</h1>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>$table</strong></li>";
        $stmt2 = $db->query("DESCRIBE $table");
        echo "<pre>";
        print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
        echo "</pre>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
