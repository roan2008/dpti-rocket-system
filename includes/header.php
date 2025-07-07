<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>DPTI Rocket System</title>
    <link rel="stylesheet" href="/dpti-rocket-system/assets/css/style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php if (isset($_SESSION['user_id']) && is_logged_in()): ?>
        <nav class="main-nav">
            <div class="nav-container">
                <div class="nav-left">
                    <a href="/dpti-rocket-system/dashboard.php" class="nav-brand">
                        🚀 DPTI Rocket System
                    </a>
                </div>
                
                <div class="nav-center">
                    <a href="/dpti-rocket-system/dashboard.php" class="nav-link">Dashboard</a>
                    <a href="/dpti-rocket-system/views/production_steps_view.php" class="nav-link">Production Steps</a>
                    
                    <?php if (has_role('engineer') || has_role('admin')): ?>
                        <a href="/dpti-rocket-system/controllers/approval_controller.php?action=list_pending" class="nav-link nav-link-primary">
                            📋 Approvals
                            <?php
                            // Show pending count in navigation
                            require_once __DIR__ . '/approval_functions.php';
                            require_once __DIR__ . '/db_connect.php';
                            $approval_stats = getApprovalStatistics($pdo);
                            $pending_count = $approval_stats['pending_count'] ?? 0;
                            if ($pending_count > 0): ?>
                                <span class="nav-badge"><?php echo $pending_count; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (has_role('admin')): ?>
                        <a href="/dpti-rocket-system/views/user_management_view.php" class="nav-link">Admin</a>
                    <?php endif; ?>
                </div>
                
                <div class="nav-right">
                    <span class="nav-user">
                        <?php echo htmlspecialchars($_SESSION['username']); ?> 
                        (<?php echo htmlspecialchars($_SESSION['role']); ?>)
                    </span>
                    <a href="/dpti-rocket-system/controllers/logout_controller.php" class="nav-link nav-logout">Logout</a>
                </div>
            </div>
        </nav>
    <?php endif; ?>
    
    <main class="main-content">
