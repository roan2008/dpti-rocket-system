<?php
/**
 * Test transaction rollback functionality
 */

require_once 'includes/db_connect.php';
require_once 'includes/rocket_functions.php';

echo "=== Testing Transaction Rollback ===\n";

// Get a rocket to test with
$rockets = get_all_rockets($pdo);
$rocket_id = $rockets[0]['rocket_id'];
$current_rocket = get_rocket_by_id($pdo, $rocket_id);

echo "Before test:\n";
echo "Rocket ID: {$rocket_id}\n";
echo "Current Status: {$current_rocket['current_status']}\n";

// Test with invalid user ID (should rollback)
echo "\nTesting with invalid user ID (should rollback)...\n";
$result = update_rocket_status($pdo, $rocket_id, 'Testing', 99999, 'This should fail and rollback');

echo "Result: " . ($result['success'] ? "SUCCESS" : "FAILED") . "\n";
echo "Message: {$result['message']}\n";

// Check if rocket status remained unchanged
$after_rocket = get_rocket_by_id($pdo, $rocket_id);
echo "\nAfter failed transaction:\n";
echo "Rocket Status: {$after_rocket['current_status']}\n";

if ($current_rocket['current_status'] === $after_rocket['current_status']) {
    echo "✓ Transaction rollback successful - status unchanged!\n";
} else {
    echo "✗ Transaction rollback failed - status was changed!\n";
}

// Check that no log entry was created
$log_stmt = $pdo->prepare("SELECT COUNT(*) FROM rocket_status_logs WHERE rocket_id = ? AND new_status = 'Testing'");
$log_stmt->execute([$rocket_id]);
$log_count = $log_stmt->fetchColumn();

if ($log_count == 0) {
    echo "✓ No audit log created for failed transaction\n";
} else {
    echo "✗ Audit log was incorrectly created for failed transaction\n";
}

echo "\n=== Transaction Rollback Test Complete ===\n";
?>
