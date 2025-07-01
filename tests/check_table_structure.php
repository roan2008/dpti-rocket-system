<?php
$pdo = new PDO('mysql:host=localhost;dbname=dpti_rocket_prod', 'root', '');
$stmt = $pdo->query('DESCRIBE users');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Users table structure:\n";
foreach ($columns as $col) {
    echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\nActual user data:\n";
$stmt = $pdo->query('SELECT * FROM users LIMIT 3');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $user) {
    echo "User: ";
    foreach ($user as $key => $value) {
        echo "$key: " . substr($value, 0, 20) . " | ";
    }
    echo "\n";
}
?>
