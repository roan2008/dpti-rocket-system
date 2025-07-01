<?php
$pdo = new PDO('mysql:host=localhost;dbname=dpti_rocket_prod', 'root', '');
$stmt = $pdo->query('SELECT user_id, username FROM users LIMIT 5');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Available users:\n";
foreach ($users as $user) {
    echo "ID: " . $user['user_id'] . ", Username: " . $user['username'] . "\n";
}
?>
