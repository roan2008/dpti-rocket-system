<?php
// Check rockets table columns
$pdo = new PDO('mysql:host=localhost;dbname=dpti_rocket_prod', 'root', '');
$stmt = $pdo->query('DESCRIBE rockets');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Rockets table columns:\n";
foreach($columns as $col) {
    echo $col['Field'] . ' (' . $col['Type'] . ")\n";
}
?>
