<?php
/**
 * Command Line Test Script for Login Function
 * This script tests the login_user() function with different scenarios
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';

echo "=== DPTI Rocket System - Login Function Test ===\n";
echo "Starting tests...\n\n";

// Test data
$test_username = 'test_user_' . time(); // Unique username to avoid conflicts
$test_password = 'testpass123';
$test_full_name = 'Test User';
$test_role = 'staff';

try {
    // Step 1: Insert temporary test user
    echo "1. Creating temporary test user...\n";
    $password_hash = password_hash($test_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
    $insert_result = $stmt->execute([$test_username, $password_hash, $test_full_name, $test_role]);
    
    if ($insert_result) {
        echo "   Test user created successfully.\n\n";
    } else {
        echo "   FAILED to create test user.\n";
        exit(1);
    }
    
    // Step 2: Test Case 1 - Successful login
    echo "2. Test Case 1: Valid credentials (should return true)\n";
    $result1 = login_user($pdo, $test_username, $test_password);
    
    if ($result1 === true) {
        echo "   PASS - Login function returned true for valid credentials.\n";
    } else {
        echo "   FAIL - Login function returned false for valid credentials.\n";
    }
    
    // Clear session for next test
    if (session_status() !== PHP_SESSION_NONE) {
        session_destroy();
    }
    
    echo "\n";
    
    // Step 3: Test Case 2 - Failed login (wrong password)
    echo "3. Test Case 2: Invalid credentials (should return false)\n";
    $result2 = login_user($pdo, $test_username, 'wrongpass');
    
    if ($result2 === false) {
        echo "   PASS - Login function returned false for invalid credentials.\n";
    } else {
        echo "   FAIL - Login function returned true for invalid credentials.\n";
    }
    
    echo "\n";
    
    // Step 4: Test Case 3 - Failed login (non-existent user)
    echo "4. Test Case 3: Non-existent user (should return false)\n";
    $result3 = login_user($pdo, 'non_existent_user', $test_password);
    
    if ($result3 === false) {
        echo "   PASS - Login function returned false for non-existent user.\n";
    } else {
        echo "   FAIL - Login function returned true for non-existent user.\n";
    }
    
    echo "\n";
    
    // Summary
    $total_tests = 3;
    $passed_tests = 0;
    
    if ($result1 === true) $passed_tests++;
    if ($result2 === false) $passed_tests++;
    if ($result3 === false) $passed_tests++;
    
    echo "=== TEST SUMMARY ===\n";
    echo "Total Tests: $total_tests\n";
    echo "Passed: $passed_tests\n";
    echo "Failed: " . ($total_tests - $passed_tests) . "\n";
    
    if ($passed_tests === $total_tests) {
        echo "Overall Result: ALL TESTS PASSED\n";
    } else {
        echo "Overall Result: SOME TESTS FAILED\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
} finally {
    // Step 5: Cleanup - Delete temporary test user
    echo "\n5. Cleaning up...\n";
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
        $delete_result = $stmt->execute([$test_username]);
        
        if ($delete_result) {
            echo "   Test user deleted successfully.\n";
        } else {
            echo "   WARNING: Failed to delete test user. Manual cleanup may be required.\n";
        }
    } catch (Exception $e) {
        echo "   ERROR during cleanup: " . $e->getMessage() . "\n";
    }
    
    echo "\nTest script completed.\n";
}
?>
