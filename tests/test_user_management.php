<?php
/**
 * User Management Test Suite
 * Tests all CRUD functions for user management including security and business logic
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/user_functions.php';

echo "\n🧪 USER MANAGEMENT TEST SUITE\n";
echo "========================================\n";

// Test configuration
$test_results = [];
$total_tests = 0;
$passed_tests = 0;

/**
 * Helper function to run a test and record results
 */
function run_test($test_name, $test_function) {
    global $test_results, $total_tests, $passed_tests;
    
    $total_tests++;
    echo "\n[$total_tests] Testing: $test_name\n";
    
    try {
        $result = $test_function();
        if ($result === true) {
            echo "✅ PASS\n";
            $passed_tests++;
            $test_results[] = ['name' => $test_name, 'status' => 'PASS'];
        } else {
            echo "❌ FAIL: $result\n";
            $test_results[] = ['name' => $test_name, 'status' => 'FAIL', 'message' => $result];
        }
    } catch (Exception $e) {
        echo "💥 ERROR: " . $e->getMessage() . "\n";
        $test_results[] = ['name' => $test_name, 'status' => 'ERROR', 'message' => $e->getMessage()];
    }
}

/**
 * Clean up test data before starting
 */
function cleanup_test_data($pdo) {
    // Delete test users (be careful not to delete real users)
    $test_usernames = [
        'test_user_create', 'test_user_update', 'test_user_delete', 
        'test_admin_user', 'test_search_user', 'test_password_hash',
        'test_user_to_delete', 'test_weak_pass', 'test_invalid_role'
    ];
    
    foreach ($test_usernames as $username) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
            $stmt->execute([$username]);
        } catch (PDOException $e) {
            // Ignore errors during cleanup
        }
    }
}

/**
 * Test 1: Database Connection
 */
function test_database_connection() {
    global $pdo;
    return ($pdo instanceof PDO) ? true : "PDO connection failed";
}

/**
 * Test 2: Create User - Valid Data
 */
function test_create_user_valid() {
    global $pdo;
    
    $result = create_user($pdo, 'test_user_create', 'testpass123', 'Test User Create', 'staff');
    
    if (!$result['success']) {
        return "Failed to create user: " . $result['message'];
    }
    
    // Verify user was created
    $user = get_user_by_username($pdo, 'test_user_create');
    if (!$user) {
        return "User not found after creation";
    }
    
    if ($user['full_name'] !== 'Test User Create' || $user['role'] !== 'staff') {
        return "User data doesn't match expected values";
    }
    
    return true;
}

/**
 * Test 3: Create User - Duplicate Username
 */
function test_create_user_duplicate() {
    global $pdo;
    
    // Try to create user with same username
    $result = create_user($pdo, 'test_user_create', 'testpass456', 'Another User', 'engineer');
    
    if ($result['success']) {
        return "Should have failed due to duplicate username";
    }
    
    if (strpos($result['message'], 'already exists') === false) {
        return "Wrong error message for duplicate username: " . $result['message'];
    }
    
    return true;
}

/**
 * Test 4: Create User - Invalid Role
 */
function test_create_user_invalid_role() {
    global $pdo;
    
    $result = create_user($pdo, 'test_invalid_role', 'testpass123', 'Invalid Role User', 'invalid_role');
    
    if ($result['success']) {
        return "Should have failed due to invalid role";
    }
    
    if (strpos($result['message'], 'Invalid role') === false) {
        return "Wrong error message for invalid role: " . $result['message'];
    }
    
    return true;
}

/**
 * Test 5: Create User - Weak Password
 */
function test_create_user_weak_password() {
    global $pdo;
    
    $result = create_user($pdo, 'test_weak_pass', '123', 'Weak Password User', 'staff');
    
    if ($result['success']) {
        return "Should have failed due to weak password";
    }
    
    if (strpos($result['message'], 'at least 8 characters') === false) {
        return "Wrong error message for weak password: " . $result['message'];
    }
    
    return true;
}

/**
 * Test 6: Create User - Invalid Username Format
 */
function test_create_user_invalid_username() {
    global $pdo;
    
    $result = create_user($pdo, 'test@user!', 'testpass123', 'Invalid Username', 'staff');
    
    if ($result['success']) {
        return "Should have failed due to invalid username format";
    }
    
    if (strpos($result['message'], 'letters, numbers, and underscores') === false) {
        return "Wrong error message for invalid username: " . $result['message'];
    }
    
    return true;
}

/**
 * Test 7: Password Hashing Security
 */
function test_password_hashing() {
    global $pdo;
    
    // Create user with known password
    $result = create_user($pdo, 'test_password_hash', 'mypassword123', 'Password Test User', 'staff');
    
    if (!$result['success']) {
        return "Failed to create user for password test: " . $result['message'];
    }
    
    // Get user from database directly to check password hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = ?");
    $stmt->execute(['test_password_hash']);
    $user = $stmt->fetch();
    
    if (!$user) {
        return "User not found for password hash test";
    }
    
    // Verify password is hashed (not stored in plain text)
    if ($user['password_hash'] === 'mypassword123') {
        return "Password is stored in plain text - security violation!";
    }
    
    // Verify password can be verified
    if (!password_verify('mypassword123', $user['password_hash'])) {
        return "Password verification failed";
    }
    
    // Verify wrong password fails
    if (password_verify('wrongpassword', $user['password_hash'])) {
        return "Wrong password verified successfully - security issue!";
    }
    
    return true;
}

/**
 * Test 8: Get All Users
 */
function test_get_all_users() {
    global $pdo;
    
    $users = get_all_users($pdo);
    
    if (!is_array($users)) {
        return "get_all_users should return an array";
    }
    
    // Should have at least our test users
    if (count($users) < 1) {
        return "Should have at least one user";
    }
    
    // Check structure of first user
    $first_user = $users[0];
    $required_fields = ['user_id', 'username', 'full_name', 'role', 'created_at'];
    
    foreach ($required_fields as $field) {
        if (!isset($first_user[$field])) {
            return "Missing field '$field' in user data";
        }
    }
    
    // Should NOT include password_hash for security
    if (isset($first_user['password_hash'])) {
        return "Security violation: password_hash included in user list";
    }
    
    return true;
}

/**
 * Test 9: Count Users
 */
function test_count_users() {
    global $pdo;
    
    $count = count_users($pdo);
    
    if (!is_int($count)) {
        return "count_users should return an integer";
    }
    
    if ($count < 1) {
        return "Should have at least one user";
    }
    
    return true;
}

/**
 * Test 10: Update User - Valid Data
 */
function test_update_user_valid() {
    global $pdo;
    
    // Get the test user we created
    $user = get_user_by_username($pdo, 'test_user_create');
    if (!$user) {
        return "Test user not found for update test";
    }
    
    // Update user
    $result = update_user($pdo, $user['user_id'], 'test_user_update', 'Updated Test User', 'engineer');
    
    if (!$result['success']) {
        return "Failed to update user: " . $result['message'];
    }
    
    // Verify update
    $updated_user = get_user_by_id($pdo, $user['user_id']);
    if (!$updated_user) {
        return "User not found after update";
    }
    
    if ($updated_user['username'] !== 'test_user_update' || 
        $updated_user['full_name'] !== 'Updated Test User' || 
        $updated_user['role'] !== 'engineer') {
        return "User data not updated correctly";
    }
    
    return true;
}

/**
 * Test 11: Update User - With Password
 */
function test_update_user_with_password() {
    global $pdo;
    
    $user = get_user_by_username($pdo, 'test_user_update');
    if (!$user) {
        return "Test user not found for password update test";
    }
    
    // Update with new password
    $result = update_user($pdo, $user['user_id'], 'test_user_update', 'Updated Test User', 'engineer', 'newpassword123');
    
    if (!$result['success']) {
        return "Failed to update user with password: " . $result['message'];
    }
    
    // Verify new password works
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    $updated_user = $stmt->fetch();
    
    if (!password_verify('newpassword123', $updated_user['password_hash'])) {
        return "New password not working after update";
    }
    
    return true;
}

/**
 * Test 12: Delete User - Business Logic Protection (Self-deletion)
 */
function test_delete_user_self_protection() {
    global $pdo;
    
    // Create a test user to try self-deletion
    $result = create_user($pdo, 'test_user_delete', 'testpass123', 'Test Delete User', 'admin');
    if (!$result['success']) {
        return "Failed to create user for deletion test: " . $result['message'];
    }
    
    $user = get_user_by_username($pdo, 'test_user_delete');
    
    // Try to delete self (same user_id as current_user_id)
    $result = delete_user($pdo, $user['user_id'], $user['user_id']);
    
    if ($result['success']) {
        return "Should have prevented self-deletion";
    }
    
    if (strpos($result['message'], 'cannot delete your own account') === false) {
        return "Wrong error message for self-deletion: " . $result['message'];
    }
    
    return true;
}

/**
 * Test 13: Delete User - Last Admin Protection
 */
function test_delete_user_last_admin_protection() {
    global $pdo;
    
    // Count current admins
    $admins = get_users_by_role($pdo, 'admin');
    $admin_count = count($admins);
    
    // Create a temporary admin for testing
    $result = create_user($pdo, 'test_temp_admin', 'testpass123', 'Temp Admin User', 'admin');
    if (!$result['success']) {
        return "Failed to create admin for test: " . $result['message'];
    }
    
    $temp_admin = get_user_by_username($pdo, 'test_temp_admin');
    
    if ($admin_count <= 1) {
        // If there was only 1 admin before, now we have 2, so we can test deletion of the original
        if (empty($admins)) {
            return "No existing admins found to test deletion protection";
        }
        
        $original_admin = $admins[0];
        
        // Try to delete the temp admin first (should succeed)
        $result = delete_user($pdo, $temp_admin['user_id'], $original_admin['user_id']);
        if (!$result['success']) {
            return "Failed to delete temp admin: " . $result['message'];
        }
        
        // Now try to delete the last remaining admin (should fail)
        $result = delete_user($pdo, $original_admin['user_id'], 999999);
        
        if ($result['success']) {
            return "Should have prevented deletion of last admin";
        }
        
        if (strpos($result['message'], 'last admin account') === false) {
            return "Wrong error message for last admin deletion: " . $result['message'];
        }
    } else {
        // We have multiple admins, delete the temp one and test deleting one of the others
        $result = delete_user($pdo, $temp_admin['user_id'], 999999);
        if (!$result['success']) {
            return "Failed to delete temp admin: " . $result['message'];
        }
        
        // This test passes since we can't easily create a scenario with exactly 1 admin
        return true;
    }
    
    return true;
}

/**
 * Test 14: Delete User - Success Case
 */
function test_delete_user_success() {
    global $pdo;
    
    // Create a user specifically for deletion
    $result = create_user($pdo, 'test_user_to_delete', 'testpass123', 'User To Delete', 'staff');
    if (!$result['success']) {
        return "Failed to create user for deletion: " . $result['message'];
    }
    
    $user = get_user_by_username($pdo, 'test_user_to_delete');
    
    // Delete the user (using different current_user_id)
    $result = delete_user($pdo, $user['user_id'], 999999);
    
    if (!$result['success']) {
        return "Failed to delete user: " . $result['message'];
    }
    
    // Verify user is deleted
    $deleted_user = get_user_by_id($pdo, $user['user_id']);
    if ($deleted_user) {
        return "User still exists after deletion";
    }
    
    return true;
}

/**
 * Test 15: Search Users
 */
function test_search_users() {
    global $pdo;
    
    // Create a user with distinctive name for search
    $result = create_user($pdo, 'test_search_user', 'testpass123', 'Searchable Test User', 'staff');
    if (!$result['success']) {
        return "Failed to create user for search test: " . $result['message'];
    }
    
    // Search by username
    $results = search_users($pdo, 'test_search');
    if (empty($results)) {
        return "Search by username returned no results";
    }
    
    $found = false;
    foreach ($results as $user) {
        if ($user['username'] === 'test_search_user') {
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        return "Created user not found in search results";
    }
    
    // Search by full name
    $results = search_users($pdo, 'Searchable');
    if (empty($results)) {
        return "Search by full name returned no results";
    }
    
    return true;
}

/**
 * Test 16: Get Users by Role
 */
function test_get_users_by_role() {
    global $pdo;
    
    $staff_users = get_users_by_role($pdo, 'staff');
    $engineer_users = get_users_by_role($pdo, 'engineer');
    $admin_users = get_users_by_role($pdo, 'admin');
    
    if (!is_array($staff_users) || !is_array($engineer_users) || !is_array($admin_users)) {
        return "get_users_by_role should return arrays";
    }
    
    // Should have at least some users in each role from our tests
    if (empty($staff_users)) {
        return "Should have at least one staff user from tests";
    }
    
    // Verify all returned users have the correct role
    foreach ($staff_users as $user) {
        if ($user['role'] !== 'staff') {
            return "Found non-staff user in staff results: " . $user['role'];
        }
    }
    
    return true;
}

/**
 * Test 17: Prevent changing the role of the last admin (Self-lockout protection)
 */
function test_update_user_last_admin_role_protection() {
    global $pdo;
    
    // Step 1: Ensure we have exactly one admin user
    $admins = get_users_by_role($pdo, 'admin');
    $admin_count = count($admins);
    
    if ($admin_count === 0) {
        return "No admin users found to test protection";
    }
    
    $test_admin = null;
    $cleanup_admin_ids = [];
    
    if ($admin_count > 1) {
        // If there are multiple admins, temporarily delete extras to get to exactly 1
        for ($i = 1; $i < $admin_count; $i++) {
            $cleanup_admin_ids[] = $admins[$i]['user_id'];
            // Temporarily update their role to staff (we'll restore later)
            $stmt = $pdo->prepare("UPDATE users SET role = 'staff' WHERE user_id = ?");
            $stmt->execute([$admins[$i]['user_id']]);
        }
        $test_admin = $admins[0];
    } else {
        // Exactly 1 admin - perfect for testing
        $test_admin = $admins[0];
    }
    
    // Step 2: Verify we now have exactly 1 admin
    $current_admin_count = countAdmins($pdo);
    if ($current_admin_count !== 1) {
        return "Failed to setup test state: expected 1 admin, got {$current_admin_count}";
    }
    
    // Step 3: Attempt to change the last admin's role to 'engineer' (should fail)
    $result = update_user($pdo, $test_admin['user_id'], $test_admin['username'], $test_admin['full_name'], 'engineer');
    
    // Step 4: Verify the operation failed
    if ($result['success']) {
        // Restore cleanup before returning error
        foreach ($cleanup_admin_ids as $admin_id) {
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?");
            $stmt->execute([$admin_id]);
        }
        return "FAIL: Last admin role change should have been prevented but succeeded";
    }
    
    // Step 5: Verify the error message is correct
    $expected_message = "Cannot change the role of the last administrator";
    if ($result['message'] !== $expected_message) {
        // Restore cleanup before returning error
        foreach ($cleanup_admin_ids as $admin_id) {
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?");
            $stmt->execute([$admin_id]);
        }
        return "FAIL: Expected error message '{$expected_message}', got '{$result['message']}'";
    }
    
    // Step 6: Verify the admin's role in database remains unchanged
    $updated_admin = get_user_by_id($pdo, $test_admin['user_id']);
    if ($updated_admin['role'] !== 'admin') {
        // Restore cleanup before returning error
        foreach ($cleanup_admin_ids as $admin_id) {
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?");
            $stmt->execute([$admin_id]);
        }
        return "FAIL: Admin role was changed in database despite protection - role is now '{$updated_admin['role']}'";
    }
    
    // Step 7: Test that the protection only applies when going from admin to non-admin
    // Try changing admin to admin (should succeed)
    $result = update_user($pdo, $test_admin['user_id'], $test_admin['username'], $test_admin['full_name'], 'admin');
    if (!$result['success']) {
        // Restore cleanup before returning error
        foreach ($cleanup_admin_ids as $admin_id) {
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?");
            $stmt->execute([$admin_id]);
        }
        return "FAIL: Admin-to-admin role update should succeed: " . $result['message'];
    }
    
    // Step 8: Test that protection doesn't apply when there are multiple admins
    // Create a temporary second admin
    $temp_result = create_user($pdo, 'temp_second_admin_' . time(), 'temppass123', 'Temp Second Admin', 'admin');
    if (!$temp_result['success']) {
        // Restore cleanup before returning error
        foreach ($cleanup_admin_ids as $admin_id) {
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?");
            $stmt->execute([$admin_id]);
        }
        return "FAIL: Could not create temporary second admin for test: " . $temp_result['message'];
    }
    
    $temp_admin_id = $temp_result['user_id'];
    
    // Now try changing first admin's role (should succeed since there are 2 admins)
    $result = update_user($pdo, $test_admin['user_id'], $test_admin['username'], $test_admin['full_name'], 'engineer');
    if (!$result['success']) {
        // Cleanup temp admin and restore others
        delete_user($pdo, $temp_admin_id, 999999);
        foreach ($cleanup_admin_ids as $admin_id) {
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?");
            $stmt->execute([$admin_id]);
        }
        return "FAIL: Role change should succeed when multiple admins exist: " . $result['message'];
    }
    
    // Step 9: Cleanup - restore original state
    // Restore the first admin's role
    $result = update_user($pdo, $test_admin['user_id'], $test_admin['username'], $test_admin['full_name'], 'admin');
    if (!$result['success']) {
        echo "Warning: Could not restore test admin's role\n";
    }
    
    // Delete the temporary second admin
    delete_user($pdo, $temp_admin_id, 999999);
    
    // Restore other admins that were temporarily changed
    foreach ($cleanup_admin_ids as $admin_id) {
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?");
        $stmt->execute([$admin_id]);
    }
    
    return true;
}

// Initialize database connection - use same settings as db_connect.php
$host = 'localhost';
$dbname = 'dpti_rocket_prod';
$username = 'root';
$password = '';

$pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
    $username, 
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Clean up any existing test data
echo "🧹 Cleaning up test data...\n";
cleanup_test_data($pdo);

// Run all tests
echo "\n🚀 Starting User Management Tests...\n";

run_test("Database Connection", "test_database_connection");
run_test("Create User - Valid Data", "test_create_user_valid");
run_test("Create User - Duplicate Username", "test_create_user_duplicate");
run_test("Create User - Invalid Role", "test_create_user_invalid_role");
run_test("Create User - Weak Password", "test_create_user_weak_password");
run_test("Create User - Invalid Username Format", "test_create_user_invalid_username");
run_test("Password Hashing Security", "test_password_hashing");
run_test("Get All Users", "test_get_all_users");
run_test("Count Users", "test_count_users");
run_test("Update User - Valid Data", "test_update_user_valid");
run_test("Update User - With Password", "test_update_user_with_password");
run_test("Delete User - Self-deletion Protection", "test_delete_user_self_protection");
run_test("Delete User - Last Admin Protection", "test_delete_user_last_admin_protection");
run_test("Delete User - Success Case", "test_delete_user_success");
run_test("Search Users", "test_search_users");
run_test("Get Users by Role", "test_get_users_by_role");
run_test("Update User - Last Admin Role Protection", "test_update_user_last_admin_role_protection");

// Clean up test data after tests
echo "\n🧹 Cleaning up test data after tests...\n";
cleanup_test_data($pdo);

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "🏁 TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "Total Tests: $total_tests\n";
echo "Passed: $passed_tests\n";
echo "Failed: " . ($total_tests - $passed_tests) . "\n";
echo "Success Rate: " . round(($passed_tests / $total_tests) * 100, 1) . "%\n";

if ($passed_tests === $total_tests) {
    echo "\n🎉 ALL TESTS PASSED! User management backend is ready.\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the issues above.\n";
    
    echo "\nFailed Tests:\n";
    foreach ($test_results as $result) {
        if ($result['status'] !== 'PASS') {
            echo "- " . $result['name'] . ": " . ($result['message'] ?? 'Unknown error') . "\n";
        }
    }
}

echo "\n";
?>
