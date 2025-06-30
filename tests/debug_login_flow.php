<?php
/**
 * Debug Login Flow
 * This script will help debug the exact login issue
 */

// Start session
session_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';

echo "=== Login Flow Debug ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "\n";

// Test credentials
$username = 'admin';
$password = 'admin123';

echo "Testing login with:\n";
echo "Username: $username\n";
echo "Password: $password\n";
echo "\n";

// Before login - check session
echo "BEFORE LOGIN:\n";
echo "Session data: " . print_r($_SESSION, true) . "\n";
echo "is_logged_in(): " . (is_logged_in() ? 'true' : 'false') . "\n";
echo "\n";

// Attempt login
echo "Attempting login...\n";
$login_result = login_user($pdo, $username, $password);
echo "Login result: " . ($login_result ? 'SUCCESS' : 'FAILED') . "\n";
echo "\n";

// After login - check session
echo "AFTER LOGIN:\n";
echo "Session data: " . print_r($_SESSION, true) . "\n";
echo "is_logged_in(): " . (is_logged_in() ? 'true' : 'false') . "\n";
echo "isset(\$_SESSION['user_id']): " . (isset($_SESSION['user_id']) ? 'true' : 'false') . "\n";
echo "\n";

// Test the exact condition from dashboard
$dashboard_condition = !isset($_SESSION['user_id']) || !is_logged_in();
echo "Dashboard condition (!isset(\$_SESSION['user_id']) || !is_logged_in()): " . ($dashboard_condition ? 'WOULD REDIRECT' : 'WOULD ALLOW ACCESS') . "\n";

echo "\n=== Debug Complete ===\n";
?>
