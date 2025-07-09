<?php
// Test navigation display
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate login for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

require_once 'includes/user_functions.php';
require_once 'includes/db_connect.php';

include 'includes/header.php';
?>

<div class="container">
    <h1>Testing Navigation Bar</h1>
    <p>This page is for testing the navigation bar display.</p>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3>Session Debug Info:</h3>
        <p><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></p>
        <p><strong>Username:</strong> <?php echo $_SESSION['username'] ?? 'Not set'; ?></p>
        <p><strong>Role:</strong> <?php echo $_SESSION['role'] ?? 'Not set'; ?></p>
        <p><strong>Is Logged In:</strong> <?php echo is_logged_in() ? 'Yes' : 'No'; ?></p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
