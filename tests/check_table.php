<?php
require_once 'includes/db_connect.php';

try {
    echo "=== ROCKETS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query('DESCRIBE rockets');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\n=== PRODUCTION_STEPS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query('DESCRIBE production_steps');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
