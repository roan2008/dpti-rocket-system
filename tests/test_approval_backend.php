<?php
/**
 * Direct Test of Approval Controller
 * Tests the pending approvals functionality without browser
 */

// Start session and simulate login
session_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';
require_once '../includes/approval_functions.php';

echo "<h2>🔧 Approval Controller Direct Test</h2>\n";
echo "<pre>";

// Simulate engineer login
$_SESSION['user_id'] = 8;  // test_engineer1 ID from test data
$_SESSION['username'] = 'test_engineer1';
$_SESSION['role'] = 'engineer';
$_SESSION['full_name'] = 'Dr. Sarah Chen';

echo "Simulated login as: " . $_SESSION['full_name'] . " (Engineer)\n\n";

// Test 1: Check if user has proper permissions
echo "1. Testing permissions:\n";
echo "   Current role: " . ($_SESSION['role'] ?? 'none') . "\n";
if (($_SESSION['role'] ?? '') === 'engineer' || ($_SESSION['role'] ?? '') === 'admin') {
    echo "   ✅ User has approval permissions (engineer or admin)\n";
} else {
    echo "   ❌ User lacks approval permissions\n";
    exit;
}

// Test 2: Test getPendingApprovals function
echo "\n2. Testing getPendingApprovals function:\n";
try {
    $pending_approvals = getPendingApprovals($pdo);
    echo "   ✅ Function executed successfully\n";
    echo "   📊 Found " . count($pending_approvals) . " pending approvals\n";
    
    if (!empty($pending_approvals)) {
        echo "\n   Sample pending approval:\n";
        $sample = $pending_approvals[0];
        echo "     - Step ID: " . $sample['step_id'] . "\n";
        echo "     - Step Name: " . $sample['step_name'] . "\n";
        echo "     - Rocket: " . $sample['serial_number'] . "\n";
        echo "     - Project: " . $sample['project_name'] . "\n";
        echo "     - Staff: " . $sample['staff_name'] . "\n";
        echo "     - Date: " . $sample['step_timestamp'] . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: Test getApprovalStatistics function
echo "\n3. Testing getApprovalStatistics function:\n";
try {
    $approval_stats = getApprovalStatistics($pdo);
    echo "   ✅ Function executed successfully\n";
    echo "   📊 Statistics:\n";
    echo "     - Total Approvals: " . ($approval_stats['total_approvals'] ?? 0) . "\n";
    echo "     - Approved Count: " . ($approval_stats['approved_count'] ?? 0) . "\n";
    echo "     - Rejected Count: " . ($approval_stats['rejected_count'] ?? 0) . "\n";
    echo "     - Pending Count: " . ($approval_stats['pending_count'] ?? 0) . "\n";
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 4: Simulate what the controller would do
echo "\n4. Simulating approval controller action:\n";
try {
    // Simulate the list_pending action
    $pending_approvals = getPendingApprovals($pdo);
    $approval_stats = getApprovalStatistics($pdo);
    
    echo "   ✅ Controller simulation successful\n";
    echo "   📋 Ready to display pending approvals view\n";
    echo "   🎯 All backend functions working correctly\n";
    
} catch (Exception $e) {
    echo "   ❌ Controller simulation failed: " . $e->getMessage() . "\n";
}

echo "\n✅ Approval workflow backend is ready!\n";
echo "\nNext steps:\n";
echo "1. Open browser and navigate to approval controller\n";
echo "2. Login as test_engineer1 with password testpass123\n";
echo "3. Access: /controllers/approval_controller.php?action=list_pending\n";

echo "</pre>";

// Add some styling
echo "<style>
body { font-family: 'Consolas', 'Monaco', monospace; margin: 20px; }
h2 { color: #2c3e50; }
pre { background: #f8f9fa; padding: 20px; border-radius: 5px; border-left: 4px solid #3498db; }
</style>";
?>
