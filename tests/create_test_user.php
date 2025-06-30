<?php
/**
 * Create Test User Script
 * This script creates a test user for logging into the system
 */

// Include required files
require_once '../includes/db_connect.php';

echo "=== Creating Test User for DPTI Rocket System ===\n";

// Test user data
$username = 'admin';
$password = 'admin123';
$full_name = 'System Administrator';
$role = 'admin';

try {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $existing_user = $stmt->fetch();
    
    if ($existing_user) {
        echo "User '$username' already exists!\n";
        echo "You can login with:\n";
        echo "Username: $username\n";
        echo "Password: admin123\n";
    } else {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Create the user
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$username, $password_hash, $full_name, $role]);
        
        if ($result) {
            echo "Test user created successfully!\n\n";
            echo "Login credentials:\n";
            echo "Username: $username\n";
            echo "Password: $password\n";
            echo "Role: $role\n\n";
        } else {
            echo "Failed to create test user.\n";
            exit(1);
        }
    }
    
    echo "Access the login page at: http://localhost/dpti-rocket-system/views/login_view.php\n";
    echo "After login, you'll be redirected to: http://localhost/dpti-rocket-system/dashboard.php\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
