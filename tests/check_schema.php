<?php
require_once 'includes/db_connect.php';

echo "Users table structure:\n";
echo "=====================\n";
$stmt = $pdo->query('DESCRIBE users');
while($row = $stmt->fetch()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\nChecking which tables exist:\n";
echo "===========================\n";
$stmt = $pdo->query('SHOW TABLES');
while($row = $stmt->fetch()) {
    echo $row[0] . "\n";
}
?>
