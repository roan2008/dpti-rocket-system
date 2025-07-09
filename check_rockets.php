<?php
// Quick database check
$pdo = new PDO('mysql:host=localhost;dbname=dpti_rocket_prod', 'root', '');
$stmt = $pdo->query('SELECT rocket_id, serial_number, project_name FROM rockets');
$rockets = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Current Rockets in Database:</h3>\n";
foreach($rockets as $rocket) {
    echo "<p>ID: {$rocket['rocket_id']}, Serial: {$rocket['serial_number']}, Project: {$rocket['project_name']}</p>\n";
}

echo "<hr>\n";
echo "<h3>Production Steps for each rocket:</h3>\n";
foreach($rockets as $rocket) {
    $steps_stmt = $pdo->prepare('SELECT COUNT(*) as count FROM production_steps WHERE rocket_id = ?');
    $steps_stmt->execute([$rocket['rocket_id']]);
    $count = $steps_stmt->fetchColumn();
    
    echo "<p>Rocket {$rocket['rocket_id']} ({$rocket['serial_number']}): $count production steps</p>\n";
}
?>
