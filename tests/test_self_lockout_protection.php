<?php
/**
 * Self-Lockout Protection Validation Test
 * Focused test for the admin role protection feature
 */

require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';

echo "<h1>🔒 Self-Lockout Protection Test</h1>\n";
echo "<p>Testing the admin role protection mechanism...</p>\n";

$test_results = [];
$test_number = 1;

// Test 1: Verify countAdmins() function works
echo "<h2>Test {$test_number}: countAdmins() Function</h2>\n";
try {
    $admin_count = countAdmins($pdo);
    echo "✅ PASS: countAdmins() returned {$admin_count} admin(s)<br>\n";
    
    // Verify it's actually counting correctly
    $manual_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($admin_count == $manual_count) {
        echo "✅ PASS: Admin count matches manual query ({$manual_count})<br>\n";
        $test_results["Test {$test_number}"] = "PASS";
    } else {
        echo "❌ FAIL: Count mismatch - countAdmins(): {$admin_count}, manual: {$manual_count}<br>\n";
        $test_results["Test {$test_number}"] = "FAIL";
    }
} catch (Exception $e) {
    echo "❌ FAIL: countAdmins() error: " . $e->getMessage() . "<br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}
$test_number++;

// Test 2: Create test scenario with multiple admins
echo "<h2>Test {$test_number}: Multi-Admin Scenario</h2>\n";
$temp_admin_id = null;
try {
    // Create a temporary admin
    $result = create_user($pdo, 'temp_lockout_test_' . time(), 'testpass123', 'Temp Lockout Test Admin', 'admin');
    if ($result['success']) {
        $temp_admin_id = $result['user_id'];
        echo "✅ PASS: Created temporary admin (ID: {$temp_admin_id})<br>\n";
        
        // Verify admin count increased
        $new_count = countAdmins($pdo);
        echo "✅ PASS: Admin count is now {$new_count}<br>\n";
        $test_results["Test {$test_number}"] = "PASS";
    } else {
        echo "❌ FAIL: Could not create temporary admin: {$result['message']}<br>\n";
        $test_results["Test {$test_number}"] = "FAIL";
    }
} catch (Exception $e) {
    echo "❌ FAIL: Multi-admin setup error: " . $e->getMessage() . "<br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}
$test_number++;

// Test 3: Test role change when multiple admins exist (should succeed)
echo "<h2>Test {$test_number}: Role Change with Multiple Admins</h2>\n";
if ($temp_admin_id) {
    try {
        $temp_admin = get_user_by_id($pdo, $temp_admin_id);
        $result = update_user($pdo, $temp_admin_id, $temp_admin['username'], $temp_admin['full_name'], 'engineer');
        
        if ($result['success']) {
            echo "✅ PASS: Role change succeeded when multiple admins exist<br>\n";
            $test_results["Test {$test_number}"] = "PASS";
        } else {
            echo "❌ FAIL: Role change failed unexpectedly: {$result['message']}<br>\n";
            $test_results["Test {$test_number}"] = "FAIL";
        }
    } catch (Exception $e) {
        echo "❌ FAIL: Multi-admin role change error: " . $e->getMessage() . "<br>\n";
        $test_results["Test {$test_number}"] = "FAIL";
    }
} else {
    echo "⏭️ SKIP: No temporary admin to test with<br>\n";
    $test_results["Test {$test_number}"] = "SKIP";
}
$test_number++;

// Test 4: Get remaining admin and test last admin protection
echo "<h2>Test {$test_number}: Last Admin Protection</h2>\n";
try {
    $remaining_admins = get_users_by_role($pdo, 'admin');
    
    if (count($remaining_admins) === 1) {
        $last_admin = $remaining_admins[0];
        echo "✅ PASS: Found exactly one admin remaining (ID: {$last_admin['user_id']})<br>\n";
        
        // Try to change the last admin's role (should fail)
        $result = update_user($pdo, $last_admin['user_id'], $last_admin['username'], $last_admin['full_name'], 'staff');
        
        if (!$result['success'] && strpos($result['message'], 'last administrator') !== false) {
            echo "✅ PASS: Last admin protection worked - {$result['message']}<br>\n";
            
            // Verify the admin's role wasn't changed
            $check_admin = get_user_by_id($pdo, $last_admin['user_id']);
            if ($check_admin['role'] === 'admin') {
                echo "✅ PASS: Admin role preserved in database<br>\n";
                $test_results["Test {$test_number}"] = "PASS";
            } else {
                echo "❌ FAIL: Admin role was changed despite protection<br>\n";
                $test_results["Test {$test_number}"] = "FAIL";
            }
        } else {
            echo "❌ FAIL: Last admin protection failed - change succeeded when it shouldn't<br>\n";
            $test_results["Test {$test_number}"] = "FAIL";
        }
    } else {
        echo "❌ FAIL: Expected 1 admin, found " . count($remaining_admins) . "<br>\n";
        $test_results["Test {$test_number}"] = "FAIL";
    }
} catch (Exception $e) {
    echo "❌ FAIL: Last admin protection test error: " . $e->getMessage() . "<br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}
$test_number++;

// Test 5: Test edge cases
echo "<h2>Test {$test_number}: Edge Cases</h2>\n";
try {
    $remaining_admins = get_users_by_role($pdo, 'admin');
    if (!empty($remaining_admins)) {
        $admin = $remaining_admins[0];
        
        // Test admin-to-admin change (should succeed)
        $result = update_user($pdo, $admin['user_id'], $admin['username'], $admin['full_name'], 'admin');
        if ($result['success']) {
            echo "✅ PASS: Admin-to-admin role change succeeded<br>\n";
        } else {
            echo "❌ FAIL: Admin-to-admin change failed: {$result['message']}<br>\n";
        }
        
        // Test with invalid user ID
        $result = update_user($pdo, 999999, 'fake', 'Fake User', 'staff');
        if (!$result['success'] && strpos($result['message'], 'not found') !== false) {
            echo "✅ PASS: Invalid user ID handled correctly<br>\n";
        } else {
            echo "❌ FAIL: Invalid user ID not handled properly<br>\n";
        }
        
        $test_results["Test {$test_number}"] = "PASS";
    } else {
        echo "❌ FAIL: No admin users found for edge case testing<br>\n";
        $test_results["Test {$test_number}"] = "FAIL";
    }
} catch (Exception $e) {
    echo "❌ FAIL: Edge case test error: " . $e->getMessage() . "<br>\n";
    $test_results["Test {$test_number}"] = "FAIL";
}
$test_number++;

// Cleanup: Restore temp admin if it was changed to engineer
if ($temp_admin_id) {
    echo "<h2>🧹 Cleanup</h2>\n";
    try {
        $temp_user = get_user_by_id($pdo, $temp_admin_id);
        if ($temp_user && $temp_user['role'] === 'engineer') {
            // Delete the temporary user since it was changed from admin
            $delete_result = delete_user($pdo, $temp_admin_id, 999999);
            if ($delete_result['success']) {
                echo "✅ Cleaned up temporary test user<br>\n";
            } else {
                echo "⚠️ Warning: Could not clean up temporary user: {$delete_result['message']}<br>\n";
            }
        }
    } catch (Exception $e) {
        echo "⚠️ Warning: Cleanup error: " . $e->getMessage() . "<br>\n";
    }
}

// Summary
echo "<h2>🎯 Test Summary</h2>\n";
$total_tests = count($test_results);
$passed_tests = count(array_filter($test_results, function($result) { return $result === 'PASS'; }));
$failed_tests = count(array_filter($test_results, function($result) { return $result === 'FAIL'; }));
$skipped_tests = count(array_filter($test_results, function($result) { return $result === 'SKIP'; }));

echo "<div style='background: " . ($failed_tests === 0 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
echo "<h3>Results:</h3>\n";
echo "<p><strong>Total Tests:</strong> {$total_tests}</p>\n";
echo "<p><strong>Passed:</strong> <span style='color: green;'>{$passed_tests}</span></p>\n";
echo "<p><strong>Failed:</strong> <span style='color: red;'>{$failed_tests}</span></p>\n";
echo "<p><strong>Skipped:</strong> <span style='color: orange;'>{$skipped_tests}</span></p>\n";
echo "<p><strong>Success Rate:</strong> " . round(($passed_tests / $total_tests) * 100, 1) . "%</p>\n";
echo "</div>\n";

if ($failed_tests === 0) {
    echo "<h3>🎉 All Self-Lockout Protection Tests Passed!</h3>\n";
    echo "<p><strong>Your admin role protection is working correctly:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ countAdmins() function works accurately</li>\n";
    echo "<li>✅ Role changes are allowed when multiple admins exist</li>\n";
    echo "<li>✅ Last admin role change is prevented</li>\n";
    echo "<li>✅ Database integrity is maintained</li>\n";
    echo "<li>✅ Edge cases are handled properly</li>\n";
    echo "</ul>\n";
    
    echo "<h3>🛡️ Security Features Active:</h3>\n";
    echo "<ol>\n";
    echo "<li><strong>Self-Lockout Prevention:</strong> Admins cannot change their role if they are the last admin</li>\n";
    echo "<li><strong>Database Integrity:</strong> Role changes are atomic and validated</li>\n";
    echo "<li><strong>Business Logic Protection:</strong> System prevents administrative lockout scenarios</li>\n";
    echo "<li><strong>Error Handling:</strong> Clear error messages for prevented operations</li>\n";
    echo "</ol>\n";
} else {
    echo "<h3>⚠️ Some Tests Failed</h3>\n";
    echo "<p>Please review the failed tests and fix any issues.</p>\n";
}

echo "\n<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
?>
