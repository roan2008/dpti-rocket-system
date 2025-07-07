<?php
/**
 * Quick Login Test for Engineer Account
 * Tests if we can login as test_engineer1 to access approval workflow
 */

// Start session
session_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';

echo "<h2>🔐 Login Test for Approval Workflow</h2>\n";
echo "<pre>";

// Test login credentials
$username = 'test_engineer1';
$password = 'testpass123';

echo "Testing login for engineer account...\n";
echo "Username: $username\n";
echo "Password: $password\n\n";

// Attempt login
$login_result = login_user($pdo, $username, $password);

if ($login_result) {
    echo "✅ Login successful!\n";
    echo "Session details:\n";
    echo "  - User ID: " . $_SESSION['user_id'] . "\n";
    echo "  - Username: " . $_SESSION['username'] . "\n";
    echo "  - Role: " . $_SESSION['role'] . "\n";
    echo "  - Full Name: " . $_SESSION['full_name'] . "\n\n";
    
    // Test role permissions
    echo "Testing role permissions:\n";
    if (has_role('engineer')) {
        echo "  ✅ Engineer role verified\n";
    } else {
        echo "  ❌ Engineer role check failed\n";
    }
    
    if (has_role('admin')) {
        echo "  ✅ Admin role also available\n";
    } else {
        echo "  ℹ️  Admin role not available (expected for engineer)\n";
    }
    
    echo "\n🎯 Ready to test approval workflow!\n";
    echo "Navigate to:\n";
    echo "  • /controllers/approval_controller.php?action=list_pending\n";
    echo "  • /views/pending_approvals_view.php (if direct access works)\n";
    
} else {
    echo "❌ Login failed!\n";
    echo "Please check:\n";
    echo "  1. Test data was populated correctly\n";
    echo "  2. Database connection is working\n";
    echo "  3. User credentials are correct\n";
}

echo "</pre>";

// Add some styling
echo "<style>
body { font-family: 'Consolas', 'Monaco', monospace; margin: 20px; }
h2 { color: #2c3e50; }
pre { background: #f8f9fa; padding: 20px; border-radius: 5px; border-left: 4px solid #3498db; }
</style>";
?>
