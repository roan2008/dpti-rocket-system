<?php
// Only start session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include user functions for authentication checks
require_once __DIR__ . '/user_functions.php';

// Function to determine active navigation state
function is_active_nav($path) {
    $current_page = $_SERVER['REQUEST_URI'];
    $current_page = parse_url($current_page, PHP_URL_PATH);
    
    // Remove dpti-rocket-system prefix for comparison
    $current_page = str_replace('/dpti-rocket-system', '', $current_page);
    $path = str_replace('/dpti-rocket-system', '', $path);
    
    // Special cases for different page patterns
    if ($path === '/dashboard.php' && ($current_page === '/dashboard.php' || $current_page === '/')) {
        return true;
    }
    
    if ($path === '/views/production_steps_view.php' && strpos($current_page, 'production') !== false) {
        return true;
    }
    
    if ($path === '/views/templates_list_view.php' && strpos($current_page, 'template') !== false) {
        return true;
    }
    
    if ($path === '/controllers/approval_controller.php' && strpos($current_page, 'approval') !== false) {
        return true;
    }
    
    if ($path === '/views/user_management_view.php' && strpos($current_page, 'user_management') !== false) {
        return true;
    }
    
    if ($path === '/views/analytics_dashboard_view.php' && strpos($current_page, 'analytics') !== false) {
        return true;
    }
    
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>DPTI Rocket System</title>
    <link rel="stylesheet" href="/dpti-rocket-system/assets/css/style.css">
    <link rel="stylesheet" href="/dpti-rocket-system/assets/css/design-system-improvements.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php if (isset($_SESSION['user_id']) && is_logged_in()): ?>
        <nav class="main-nav">
            <div class="nav-container">
                <!-- Brand/Logo -->
                <div class="nav-left">
                    <a href="/dpti-rocket-system/dashboard.php" class="nav-brand">
                        🚀 DPTI Rocket System
                    </a>
                </div>
                
                <!-- Primary Navigation -->
                <div class="nav-center">
                    <a href="/dpti-rocket-system/dashboard.php" class="nav-link <?php echo is_active_nav('/dashboard.php') ? 'nav-link-primary' : ''; ?>">Dashboard</a>
                    <a href="/dpti-rocket-system/views/production_steps_view.php" class="nav-link <?php echo is_active_nav('/views/production_steps_view.php') ? 'nav-link-primary' : ''; ?>">Production</a>
                    
                    <?php if (has_role('engineer') || has_role('admin')): ?>
                        <a href="/dpti-rocket-system/views/templates_list_view.php" class="nav-link <?php echo is_active_nav('/views/templates_list_view.php') ? 'nav-link-primary' : ''; ?>">Templates</a>
                        <a href="/dpti-rocket-system/controllers/approval_controller.php?action=list_pending" class="nav-link <?php echo is_active_nav('/controllers/approval_controller.php') ? 'nav-link-primary' : ''; ?>">
                            Approvals
                            <?php
                            // Show pending count badge
                            require_once __DIR__ . '/approval_functions.php';
                            require_once __DIR__ . '/db_connect.php';
                            $approval_stats = getApprovalStatistics($pdo);
                            $pending_count = $approval_stats['pending_count'] ?? 0;
                            if ($pending_count > 0): ?>
                                <span class="nav-badge"><?php echo $pending_count; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (has_role('engineer') || has_role('admin')): ?>
                        <a href="/dpti-rocket-system/views/analytics_dashboard_view.php" class="nav-link <?php echo is_active_nav('/views/analytics_dashboard_view.php') ? 'nav-link-primary' : ''; ?>">📊 Analytics</a>
                    <?php endif; ?>
                    
                    <?php if (has_role('admin')): ?>
                        <a href="/dpti-rocket-system/views/user_management_view.php" class="nav-link <?php echo is_active_nav('/views/user_management_view.php') ? 'nav-link-primary' : ''; ?>">Admin</a>
                    <?php endif; ?>
                </div>
                
                <!-- User Context (SINGLE HOME for user info) -->
                <div class="nav-right">
                    <?php if (isset($_SESSION['username']) && isset($_SESSION['role'])): ?>
                        <div class="nav-user-info">
                            <span class="nav-user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <span class="nav-user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                        </div>
                    <?php endif; ?>
                    <a href="/dpti-rocket-system/controllers/logout_controller.php" class="nav-logout">
                        Logout
                    </a>
                </div>
            </div>
        </nav>
    <?php endif; ?>
    
    <main class="main-content">
