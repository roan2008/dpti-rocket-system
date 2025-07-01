<?php
$pdo = new PDO('mysql:host=localhost;dbname=dpti_rocket_prod', 'root', '');
$stmt = $pdo->query('SELECT user_id, username, password_hash FROM users');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "User credentials:\n";
foreach ($users as $user) {
    echo "Username: " . $user['username'] . "\n";
    echo "Password hash: " . substr($user['password_hash'], 0, 30) . "...\n";
    echo "---\n";
}

// Let's also test if these are hashed with password_hash()
echo "\nTesting common passwords:\n";
$common_passwords = ['admin123', 'engineer123', 'staff123', 'password123', 'admin', 'engineer', 'staff', 'password', '123456', 'test123'];

foreach ($users as $user) {
    echo "Testing {$user['username']}:\n";
    foreach ($common_passwords as $test_password) {
        if (password_verify($test_password, $user['password_hash'])) {
            echo "  ✓ Password is: $test_password\n";
            break;
        }
    }
}
?>
