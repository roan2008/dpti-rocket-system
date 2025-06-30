<?php
/**
 * Login Diagnostic Script
 * This script checks all aspects of the login system
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';

echo "=== DPTI Rocket System - Login Diagnostic ===\n";
echo "Checking login system components...\n\n";

try {
    // 1. Check database connection
    echo "1. Testing database connection...\n";
    if ($pdo) {
        echo "   ✓ Database connection successful\n";
    } else {
        echo "   ✗ Database connection failed\n";
        exit(1);
    }
    
    echo "\n";
    
    // 2. Check if users table exists and has data
    echo "2. Checking users table...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    $user_count = $stmt->fetchColumn();
    echo "   ✓ Users table exists\n";
    echo "   ✓ Total users in database: $user_count\n";
    
    if ($user_count == 0) {
        echo "   ⚠ WARNING: No users found in database!\n";
        echo "   Run create_test_user.php to create a test user.\n";
    }
    
    echo "\n";
    
    // 3. List all users
    echo "3. Listing all users...\n";
    $stmt = $pdo->prepare("SELECT user_id, username, full_name, role, created_at FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "   No users found.\n";
    } else {
        foreach ($users as $user) {
            echo "   - ID: {$user['user_id']}, Username: '{$user['username']}', Name: '{$user['full_name']}', Role: '{$user['role']}'\n";
        }
    }
    
    echo "\n";
    
    // 4. Test login function with admin user (if exists)
    echo "4. Testing login function...\n";
    $test_username = 'admin';
    $test_password = 'admin123';
    
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$test_username]);
    $admin_exists = $stmt->fetch();
    
    if ($admin_exists) {
        echo "   ✓ Admin user exists\n";
        
        // Test login function
        $login_result = login_user($pdo, $test_username, $test_password);
        if ($login_result) {
            echo "   ✓ Login function works correctly\n";
            echo "   ✓ Credentials: username='$test_username', password='$test_password'\n";
        } else {
            echo "   ✗ Login function failed\n";
            echo "   Check if password is correct or if there's an issue with password_verify()\n";
        }
    } else {
        echo "   ✗ Admin user does not exist\n";
        echo "   You need to run create_test_user.php first\n";
    }
    
    echo "\n";
    
    // 5. Check session functionality
    echo "5. Testing session functionality...\n";
    if (php_sapi_name() === 'cli') {
        echo "   ⚠ Running in CLI mode - sessions not tested\n";
    } else {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        echo "   ✓ Session functionality available\n";
    }
    
    echo "\n";
    
    // 6. Final recommendations
    echo "=== RECOMMENDATIONS ===\n";
    
    if ($user_count == 0) {
        echo "1. Create a test user by running: php create_test_user.php\n";
    }
    
    if (!$admin_exists) {
        echo "2. Make sure admin user exists with correct credentials\n";
    }
    
    echo "3. Access login page at: http://localhost/dpti-rocket-system/views/login_view.php\n";
    echo "4. Use credentials: username='admin', password='admin123'\n";
    
    echo "\n=== DIAGNOSTIC COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
