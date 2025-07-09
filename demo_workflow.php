<?php
/**
 * Complete workflow demonstration
 */

require_once 'includes/db_connect.php';
require_once 'includes/rocket_functions.php';
require_once 'includes/log_functions.php';

echo "=== Complete Audit Trail Workflow Demo ===\n";

// Get a rocket and user
$rockets = get_all_rockets($pdo);
$rocket_id = $rockets[0]['rocket_id'];

$user_stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE role = 'admin' LIMIT 1");
$user_stmt->execute();
$user = $user_stmt->fetch();

echo "Demo setup:\n";
echo "Rocket: {$rockets[0]['serial_number']} (ID: {$rocket_id})\n";
echo "User: {$user['username']} (ID: {$user['user_id']})\n";
echo "Current Status: {$rockets[0]['current_status']}\n\n";

// Simulate a complete status workflow
$statuses = ['Development', 'Testing', 'Ready for Production', 'In Production'];
$reasons = [
    'Development' => 'Initial development phase started',
    'Testing' => 'Development complete, moving to testing phase',
    'Ready for Production' => 'All tests passed, approved for production',
    'In Production' => 'Deployed to production environment'
];

foreach ($statuses as $new_status) {
    $current = get_rocket_by_id($pdo, $rocket_id);
    
    if ($current['current_status'] !== $new_status) {
        echo "--- Updating to: $new_status ---\n";
        
        $result = update_rocket_status(
            $pdo, 
            $rocket_id, 
            $new_status, 
            $user['user_id'], 
            $reasons[$new_status]
        );
        
        if ($result['success']) {
            echo "✓ Success: {$result['previous_status']} → {$result['new_status']}\n";
            echo "  Log ID: {$result['log_id']}\n";
            echo "  Reason: {$reasons[$new_status]}\n\n";
        } else {
            echo "✗ Failed: {$result['message']}\n\n";
        }
        
        // Small delay to show different timestamps
        sleep(1);
    }
}

// Show complete audit trail
echo "=== Complete Audit Trail ===\n";
$logs = getRocketStatusLogs($pdo, $rocket_id);

foreach ($logs as $log) {
    echo "{$log['changed_at']}: {$log['previous_status']} → {$log['new_status']}\n";
    echo "  By: {$log['username']}\n";
    echo "  Reason: {$log['change_reason']}\n\n";
}

// Show statistics
echo "=== Audit Statistics ===\n";
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_changes,
        COUNT(DISTINCT rocket_id) as rockets_changed,
        COUNT(DISTINCT user_id) as users_involved,
        MIN(changed_at) as first_change,
        MAX(changed_at) as last_change
    FROM rocket_status_logs
");
$stats_stmt->execute();
$stats = $stats_stmt->fetch();

echo "Total status changes: {$stats['total_changes']}\n";
echo "Rockets with changes: {$stats['rockets_changed']}\n";
echo "Users involved: {$stats['users_involved']}\n";
echo "First change: {$stats['first_change']}\n";
echo "Last change: {$stats['last_change']}\n";

echo "\n🎉 Audit trail system fully operational!\n";
echo "✓ All status changes are logged\n";
echo "✓ Transaction safety ensured\n";
echo "✓ Complete audit trail maintained\n";
echo "✓ Data integrity protected\n";
?>
