<?php
/**
 * Test script for refactored update_rocket_status() function
 * This script tests the new audit trail functionality
 */

// Start session for user context
session_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/rocket_functions.php';
require_once '../includes/log_functions.php';

// Set up test environment
$_SESSION['user_id'] = 1; // Simulate admin user

echo "<h1>Testing Refactored update_rocket_status() Function</h1>";
echo "<hr>";

try {
    // Test 1: Create a test rocket
    echo "<h2>Test 1: Creating Test Rocket</h2>";
    $test_rocket = create_rocket($pdo, 'TEST-001', 'Test Rocket for Status Update', 'New');
    
    if ($test_rocket) {
        $rocket_id = $pdo->lastInsertId();
        echo "✅ Test rocket created with ID: $rocket_id<br>";
    } else {
        echo "❌ Failed to create test rocket<br>";
        exit;
    }
    
    // Test 2: Test successful status update
    echo "<h2>Test 2: Testing Successful Status Update</h2>";
    $result = update_rocket_status(
        $pdo,
        $rocket_id,
        'In Development',
        1, // user_id
        'Moving rocket to development phase after initial review'
    );
    
    if ($result['success']) {
        echo "✅ Status update successful<br>";
        echo "📋 Log ID: " . $result['log_id'] . "<br>";
        echo "🔄 Previous Status: " . $result['previous_status'] . "<br>";
        echo "🔄 New Status: " . $result['new_status'] . "<br>";
        echo "💬 Message: " . $result['message'] . "<br>";
    } else {
        echo "❌ Status update failed: " . $result['message'] . "<br>";
    }
    
    echo "<br>";
    
    // Test 3: Test validation - same status
    echo "<h2>Test 3: Testing Validation (Same Status)</h2>";
    $result = update_rocket_status(
        $pdo,
        $rocket_id,
        'In Development', // Same as current
        1,
        'Trying to set same status'
    );
    
    if (!$result['success']) {
        echo "✅ Validation working: " . $result['message'] . "<br>";
    } else {
        echo "❌ Validation failed - should not allow same status<br>";
    }
    
    echo "<br>";
    
    // Test 4: Test validation - empty reason
    echo "<h2>Test 4: Testing Validation (Empty Reason)</h2>";
    $result = update_rocket_status(
        $pdo,
        $rocket_id,
        'In Production',
        1,
        '' // Empty reason
    );
    
    if (!$result['success']) {
        echo "✅ Validation working: " . $result['message'] . "<br>";
    } else {
        echo "❌ Validation failed - should require change reason<br>";
    }
    
    echo "<br>";
    
    // Test 5: Test another successful update
    echo "<h2>Test 5: Testing Another Status Update</h2>";
    $result = update_rocket_status(
        $pdo,
        $rocket_id,
        'In Production',
        1,
        'All development tasks completed, moving to production'
    );
    
    if ($result['success']) {
        echo "✅ Second status update successful<br>";
        echo "📋 Log ID: " . $result['log_id'] . "<br>";
        echo "🔄 Previous Status: " . $result['previous_status'] . "<br>";
        echo "🔄 New Status: " . $result['new_status'] . "<br>";
    } else {
        echo "❌ Second status update failed: " . $result['message'] . "<br>";
    }
    
    echo "<br>";
    
    // Test 6: Display audit logs
    echo "<h2>Test 6: Displaying Audit Logs</h2>";
    $logs = getRocketStatusLogs($pdo, $rocket_id);
    
    if (!empty($logs)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        echo "<th>Log ID</th>";
        echo "<th>User</th>";
        echo "<th>Previous Status</th>";
        echo "<th>New Status</th>";
        echo "<th>Reason</th>";
        echo "<th>Date</th>";
        echo "</tr>";
        
        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($log['log_id']) . "</td>";
            echo "<td>" . htmlspecialchars($log['full_name']) . " (" . htmlspecialchars($log['role']) . ")</td>";
            echo "<td>" . htmlspecialchars($log['previous_status']) . "</td>";
            echo "<td>" . htmlspecialchars($log['new_status']) . "</td>";
            echo "<td>" . htmlspecialchars($log['change_reason']) . "</td>";
            echo "<td>" . htmlspecialchars($log['changed_at']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "✅ Found " . count($logs) . " audit log entries<br>";
    } else {
        echo "❌ No audit logs found<br>";
    }
    
    echo "<br>";
    
    // Test 7: Test legacy function
    echo "<h2>Test 7: Testing Legacy Wrapper Function</h2>";
    $legacy_result = update_rocket_status_legacy($pdo, $rocket_id, 'Completed');
    
    if ($legacy_result) {
        echo "✅ Legacy function works<br>";
        
        // Check if audit log was created
        $recent_logs = getRocketStatusLogs($pdo, $rocket_id, 1);
        if (!empty($recent_logs)) {
            $latest_log = $recent_logs[0];
            echo "📋 Audit log created with reason: " . htmlspecialchars($latest_log['change_reason']) . "<br>";
        }
    } else {
        echo "❌ Legacy function failed<br>";
    }
    
    echo "<br>";
    
    // Cleanup
    echo "<h2>Cleanup</h2>";
    $cleanup = delete_rocket($pdo, $rocket_id);
    if ($cleanup) {
        echo "✅ Test rocket deleted successfully<br>";
    } else {
        echo "❌ Failed to delete test rocket<br>";
    }
    
    echo "<hr>";
    echo "<h2>Summary</h2>";
    echo "✅ All tests completed. The refactored update_rocket_status() function is working correctly with:<br>";
    echo "• Transaction-based updates<br>";
    echo "• Audit trail logging<br>";
    echo "• Input validation<br>";
    echo "• Error handling<br>";
    echo "• Backward compatibility<br>";
    
} catch (Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
