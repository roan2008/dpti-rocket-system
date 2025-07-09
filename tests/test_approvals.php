<?php
// Test approval system
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate login for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

require_once 'includes/user_functions.php';
require_once 'includes/db_connect.php';
require_once 'includes/approval_functions.php';

echo "<h1>🔍 Approval System Test</h1>";

echo "<h2>Session Info:</h2>";
echo "<p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p><strong>Username:</strong> " . ($_SESSION['username'] ?? 'Not set') . "</p>";
echo "<p><strong>Role:</strong> " . ($_SESSION['role'] ?? 'Not set') . "</p>";
echo "<p><strong>Is Logged In:</strong> " . (is_logged_in() ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Has Engineer/Admin Role:</strong> " . ((has_role('engineer') || has_role('admin')) ? 'Yes' : 'No') . "</p>";

echo "<h2>Approval Functions Test:</h2>";

try {
    // Test getApprovalStatistics
    $approval_stats = getApprovalStatistics($pdo);
    echo "<p>✅ getApprovalStatistics() works</p>";
    echo "<pre>" . print_r($approval_stats, true) . "</pre>";
    
    // Test getPendingApprovals
    $pending_approvals = getPendingApprovals($pdo);
    echo "<p>✅ getPendingApprovals() works - Found " . count($pending_approvals) . " pending approvals</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Navigation Links:</h2>";
echo "<p><a href='views/pending_approvals_view.php'>📋 Pending Approvals View</a></p>";
echo "<p><a href='controllers/approval_controller.php?action=list_pending'>🎯 Approval Controller</a></p>";
echo "<p><a href='dashboard.php'>🏠 Dashboard</a></p>";
?>
