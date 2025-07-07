<?php
/**
 * Test Script for Approval Workflow System
 * Thoroughly tests all approval functions and database transactions
 */

echo "=== APPROVAL WORKFLOW TEST SUITE ===\n\n";

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/approval_functions.php';

// Test configuration
$test_results = [];
$total_tests = 0;
$passed_tests = 0;

/**
 * Helper function to run a test and record results
 */
function runTest($test_name, $test_function) {
    global $test_results, $total_tests, $passed_tests;
    
    $total_tests++;
    echo "Testing: $test_name\n";
    
    try {
        $result = $test_function();
        if ($result) {
            echo "   ✅ PASS\n";
            $passed_tests++;
            $test_results[$test_name] = 'PASS';
        } else {
            echo "   ❌ FAIL\n";
            $test_results[$test_name] = 'FAIL';
        }
    } catch (Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
        $test_results[$test_name] = 'ERROR';
    }
    
    echo "\n";
}

// Setup test data
echo "🔧 Setting up test environment...\n";

try {
    // Clean up any existing test data
    $pdo->exec("DELETE FROM approvals WHERE step_id IN (SELECT step_id FROM production_steps WHERE rocket_id IN (SELECT rocket_id FROM rockets WHERE serial_number LIKE 'TEST-APPROVAL-%'))");
    $pdo->exec("DELETE FROM production_steps WHERE rocket_id IN (SELECT rocket_id FROM rockets WHERE serial_number LIKE 'TEST-APPROVAL-%')");
    $pdo->exec("DELETE FROM rockets WHERE serial_number LIKE 'TEST-APPROVAL-%'");
    
    // Create test rocket
    $stmt = $pdo->prepare("
        INSERT INTO rockets (serial_number, project_name, current_status, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute(['TEST-APPROVAL-001', 'Approval Test Project', 'In Production']);
    $test_rocket_id = $pdo->lastInsertId();
    
    // Get a valid staff user for testing
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE role IN ('staff', 'admin', 'engineer') LIMIT 1");
    $stmt->execute();
    $staff_user = $stmt->fetch();
    
    if (!$staff_user) {
        throw new Exception("No valid users found in database. Please ensure users exist.");
    }
    $test_staff_id = $staff_user['user_id'];
    
    // Create test production step
    $stmt = $pdo->prepare("
        INSERT INTO production_steps (rocket_id, step_name, data_json, staff_id, step_timestamp) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $test_data = json_encode(['test_field' => 'test_value', 'status' => 'completed']);
    $stmt->execute([$test_rocket_id, 'Test Production Step', $test_data, $test_staff_id]);
    $test_step_id = $pdo->lastInsertId();
    
    // Get engineer user for testing (assuming engineer user exists)
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE role = 'engineer' LIMIT 1");
    $stmt->execute();
    $engineer_user = $stmt->fetch();
    
    if (!$engineer_user) {
        // Create test engineer if none exists
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, full_name, role) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute(['test_engineer', password_hash('test123', PASSWORD_DEFAULT), 'Test Engineer', 'engineer']);
        $test_engineer_id = $pdo->lastInsertId();
    } else {
        $test_engineer_id = $engineer_user['user_id'];
    }
    
    echo "✅ Test environment ready\n";
    echo "   - Test Rocket ID: $test_rocket_id\n";
    echo "   - Test Step ID: $test_step_id\n";
    echo "   - Test Staff ID: $test_staff_id\n";
    echo "   - Test Engineer ID: $test_engineer_id\n\n";
    
} catch (Exception $e) {
    die("❌ Failed to setup test environment: " . $e->getMessage() . "\n");
}

// Test 1: Submit Approval (Approved)
runTest("Submit Approval - Approved Status", function() use ($pdo, $test_step_id, $test_engineer_id) {
    return submitApproval($pdo, $test_step_id, $test_engineer_id, 'approved', 'Test approval comment - looks good!');
});

// Test 2: Verify rocket status update after approval
runTest("Rocket Status Update After Approval", function() use ($pdo, $test_rocket_id) {
    $stmt = $pdo->prepare("SELECT current_status FROM rockets WHERE rocket_id = ?");
    $stmt->execute([$test_rocket_id]);
    $status = $stmt->fetchColumn();
    
    return $status === 'Step Approved: Test Production Step';
});

// Test 3: Get approval history
runTest("Get Approval History for Step", function() use ($pdo, $test_step_id) {
    $history = getApprovalHistoryForStep($pdo, $test_step_id);
    
    return is_array($history) && count($history) === 1 && 
           $history[0]['status'] === 'approved' && 
           $history[0]['step_id'] == $test_step_id;
});

// Test 4: Submit second approval (should fail due to unique constraint)
runTest("Duplicate Approval Prevention", function() use ($pdo, $test_step_id, $test_engineer_id) {
    // This should fail because step already has an approval
    $result = submitApproval($pdo, $test_step_id, $test_engineer_id, 'rejected', 'Second review - should fail');
    return $result === false; // Should return false due to unique constraint
});

// Test 5: Create new step for rejection test
runTest("Create Second Step for Rejection Test", function() use ($pdo, $test_rocket_id, $test_staff_id) {
    global $test_step_id_2;
    
    $stmt = $pdo->prepare("
        INSERT INTO production_steps (rocket_id, step_name, data_json, staff_id, step_timestamp) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $test_data = json_encode(['test_field' => 'test_value_2', 'status' => 'completed']);
    $stmt->execute([$test_rocket_id, 'Test Production Step 2', $test_data, $test_staff_id]);
    $test_step_id_2 = $pdo->lastInsertId();
    
    return $test_step_id_2 > 0;
});

// Test 6: Submit approval (rejected) on new step
runTest("Submit Approval - Rejected Status", function() use ($pdo, $test_engineer_id) {
    global $test_step_id_2;
    return submitApproval($pdo, $test_step_id_2, $test_engineer_id, 'rejected', 'Needs improvement');
});

// Test 7: Verify rocket status update after rejection
runTest("Rocket Status Update After Rejection", function() use ($pdo, $test_rocket_id) {
    $stmt = $pdo->prepare("SELECT current_status FROM rockets WHERE rocket_id = ?");
    $stmt->execute([$test_rocket_id]);
    $status = $stmt->fetchColumn();
    
    return $status === 'Step Rejected: Test Production Step 2';
});

// Test 8: Get approval history for both steps
runTest("Get Approval History Multiple Steps", function() use ($pdo, $test_step_id) {
    global $test_step_id_2;
    
    $history1 = getApprovalHistoryForStep($pdo, $test_step_id);
    $history2 = getApprovalHistoryForStep($pdo, $test_step_id_2);
    
    return is_array($history1) && count($history1) === 1 && 
           is_array($history2) && count($history2) === 1 &&
           $history1[0]['status'] === 'approved' &&
           $history2[0]['status'] === 'rejected';
});

// Test 7: Invalid status handling
runTest("Invalid Status Handling", function() use ($pdo, $test_step_id, $test_engineer_id) {
    $result = submitApproval($pdo, $test_step_id, $test_engineer_id, 'invalid_status', 'Should fail');
    return $result === false; // Should return false for invalid status
});

// Test 8: Invalid step ID handling
runTest("Invalid Step ID Handling", function() use ($pdo, $test_engineer_id) {
    $result = submitApproval($pdo, 99999, $test_engineer_id, 'approved', 'Should fail');
    return $result === false; // Should return false for non-existent step
});

// Test 9: Invalid engineer ID handling
runTest("Invalid Engineer ID Handling", function() use ($pdo, $test_step_id) {
    $result = submitApproval($pdo, $test_step_id, 99999, 'approved', 'Should fail');
    return $result === false; // Should return false for non-existent engineer
});

// Test 11: Get pending approvals
runTest("Get Pending Approvals", function() use ($pdo, $test_rocket_id, $test_staff_id) {
    // Create a new step without approval for testing
    $stmt = $pdo->prepare("
        INSERT INTO production_steps (rocket_id, step_name, data_json, staff_id, step_timestamp) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$test_rocket_id, 'Pending Test Step', '{}', $test_staff_id]);
    
    $pending = getPendingApprovals($pdo);
    
    return is_array($pending) && count($pending) >= 1;
});

// Test 11: Get approval statistics
runTest("Get Approval Statistics", function() use ($pdo) {
    $stats = getApprovalStatistics($pdo);
    
    return is_array($stats) && 
           isset($stats['total_approvals']) && 
           isset($stats['approved_count']) && 
           isset($stats['rejected_count']) && 
           isset($stats['pending_count']);
});

// Test 12: Get step approval status
runTest("Get Step Approval Status", function() use ($pdo, $test_step_id) {
    $status = getStepApprovalStatus($pdo, $test_step_id);
    
    return is_array($status) && 
           isset($status['status']) && 
           in_array($status['status'], ['approved', 'rejected']);
});

// Test 13: Database transaction integrity
runTest("Database Transaction Integrity", function() use ($pdo, $test_step_id, $test_engineer_id) {
    // This test verifies that if something goes wrong, the transaction rolls back
    // We'll temporarily break the database to simulate an error
    
    try {
        // Get current approval count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM approvals WHERE step_id = ?");
        $stmt->execute([$test_step_id]);
        $before_count = $stmt->fetchColumn();
        
        // Temporarily rename the rockets table to cause an error
        $pdo->exec("ALTER TABLE rockets RENAME TO rockets_temp");
        
        // Try to submit approval - should fail and rollback
        $result = submitApproval($pdo, $test_step_id, $test_engineer_id, 'approved', 'Should fail');
        
        // Restore the table
        $pdo->exec("ALTER TABLE rockets_temp RENAME TO rockets");
        
        // Check that no approval was inserted
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM approvals WHERE step_id = ?");
        $stmt->execute([$test_step_id]);
        $after_count = $stmt->fetchColumn();
        
        return $result === false && $before_count === $after_count;
        
    } catch (Exception $e) {
        // Make sure to restore table if something goes wrong
        try {
            $pdo->exec("ALTER TABLE rockets_temp RENAME TO rockets");
        } catch (Exception $e2) {
            // Table might already be restored
        }
        throw $e;
    }
});

// Cleanup test data
echo "🧹 Cleaning up test environment...\n";
try {
    $pdo->exec("DELETE FROM approvals WHERE step_id IN (SELECT step_id FROM production_steps WHERE rocket_id IN (SELECT rocket_id FROM rockets WHERE serial_number LIKE 'TEST-APPROVAL-%'))");
    $pdo->exec("DELETE FROM production_steps WHERE rocket_id IN (SELECT rocket_id FROM rockets WHERE serial_number LIKE 'TEST-APPROVAL-%')");
    $pdo->exec("DELETE FROM rockets WHERE serial_number LIKE 'TEST-APPROVAL-%'");
    
    // Only delete test engineer if we created one
    if (!$engineer_user) {
        $pdo->exec("DELETE FROM users WHERE username = 'test_engineer'");
    }
    
    echo "✅ Cleanup completed\n\n";
} catch (Exception $e) {
    echo "⚠️ Cleanup warning: " . $e->getMessage() . "\n\n";
}

// Summary
echo "📊 TEST SUMMARY\n";
echo "===============\n";
echo "Total Tests: $total_tests\n";
echo "Passed: $passed_tests\n";
echo "Failed: " . ($total_tests - $passed_tests) . "\n";
echo "Success Rate: " . round(($passed_tests / $total_tests) * 100, 1) . "%\n\n";

if ($passed_tests === $total_tests) {
    echo "🎉 ALL TESTS PASSED! Approval workflow system is working correctly.\n";
} else {
    echo "⚠️ Some tests failed. Please review the results above.\n";
    echo "\nDetailed Results:\n";
    foreach ($test_results as $test_name => $result) {
        $icon = ($result === 'PASS') ? '✅' : '❌';
        echo "$icon $test_name: $result\n";
    }
}

echo "\n=== APPROVAL WORKFLOW TEST COMPLETE ===\n";
?>
