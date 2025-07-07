<?php
/**
 * Dashboard - Main application page
 */

// Start session first
session_start();

// Include required files
require_once 'includes/db_connect.php';
require_once 'includes/user_functions.php';
require_once 'includes/rocket_functions.php';

// Access Control: Check if user is logged in
if (!isset($_SESSION['user_id']) || !is_logged_in()) {
    header('Location: views/login_view.php');
    exit;
}

// Get all rockets for display
$rockets = get_all_rockets($pdo);
$rocket_count = count_rockets($pdo);

include 'includes/header.php';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>DPTI Rocket System Dashboard</h1>
        <div class="user-info">
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            <p>Role: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
            <a href="controllers/logout_controller.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="message success">
            <?php
            switch ($_GET['success']) {
                case 'rocket_created':
                    echo "Rocket successfully created!";
                    if (isset($_GET['rocket_id'])) {
                        echo " (ID: " . htmlspecialchars($_GET['rocket_id']) . ")";
                    }
                    break;
                case 'rocket_deleted':
                    echo "Rocket successfully deleted!";
                    break;
                case 'status_updated':
                    echo "Rocket status successfully updated!";
                    break;
                default:
                    echo "Operation completed successfully!";
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="message error">
            <?php
            switch ($_GET['error']) {
                case 'invalid_action':
                    echo "Invalid action requested.";
                    break;
                case 'insufficient_permissions':
                    echo "You don't have permission to perform this action.";
                    break;
                case 'rocket_not_found':
                    echo "Rocket not found.";
                    break;
                case 'delete_failed':
                    echo "Failed to delete rocket.";
                    break;
                case 'status_update_failed':
                    echo "Failed to update rocket status.";
                    break;
                default:
                    echo "An error occurred. Please try again.";
            }
            ?>
        </div>
    <?php endif; ?>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <h3><?php echo $rocket_count; ?></h3>
            <p>Total Rockets</p>
        </div>
        <div class="stat-card">
            <h3><?php echo count(array_filter($rockets, function($r) { return $r['current_status'] === 'In Production'; })); ?></h3>
            <p>In Production</p>
        </div>
        <div class="stat-card">
            <h3><?php echo count(array_filter($rockets, function($r) { return $r['current_status'] === 'Completed'; })); ?></h3>
            <p>Completed</p>
        </div>
    </div>
    
    <div class="dashboard-content">
        <div class="rockets-section">
            <div class="section-header">
                <h2>Rockets Overview</h2>
                <a href="views/rocket_add_view.php" class="btn-primary">Add New Rocket</a>
            </div>
            
            <?php if (empty($rockets)): ?>
                <div class="empty-state">
                    <p>No rockets found. <a href="views/rocket_add_view.php">Add the first rocket</a> to get started.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="rockets-table">
                        <thead>
                            <tr>
                                <th>Serial Number</th>
                                <th>Project Name</th>
                                <th>Current Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rockets as $rocket): ?>
                                <tr>
                                    <td class="serial-number">
                                        <?php echo htmlspecialchars($rocket['serial_number']); ?>
                                    </td>
                                    <td class="project-name">
                                        <?php echo htmlspecialchars($rocket['project_name']); ?>
                                    </td>
                                    <td class="status">
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $rocket['current_status'])); ?>">
                                            <?php echo htmlspecialchars($rocket['current_status']); ?>
                                        </span>
                                    </td>
                                    <td class="created-date">
                                        <?php echo date('M j, Y', strtotime($rocket['created_at'])); ?>
                                    </td>
                                    <td class="actions">
                                        <a href="views/rocket_detail_view.php?id=<?php echo $rocket['rocket_id']; ?>" class="btn-small btn-view">View</a>
                                        <a href="views/production_steps_view.php?rocket_id=<?php echo $rocket['rocket_id']; ?>" class="btn-small btn-steps">Steps</a>
                                        <?php if (has_role('admin') || has_role('engineer')): ?>
                                            <a href="views/rocket_edit_view.php?id=<?php echo $rocket['rocket_id']; ?>" class="btn-small btn-edit">Edit</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Production Steps</h3>
                <p>Track production progress</p>
                <a href="views/production_steps_view.php" class="btn-primary">View All Steps</a>
            </div>
            
            <?php if (has_role('engineer') || has_role('admin')): ?>
            <div class="dashboard-card">
                <h3>Template Management</h3>
                <p>Create and manage step templates</p>
                <a href="views/templates_list_view.php" class="btn-primary">Manage Templates</a>
            </div>
            
            <div class="dashboard-card">
                <h3>Approvals</h3>
                <p>Review and approve production steps</p>
                <a href="views/approvals_view.php" class="btn-primary">View Approvals</a>
            </div>
            <?php endif; ?>
            
            <?php if (has_role('admin')): ?>
            <div class="dashboard-card">
                <h3>User Management</h3>
                <p>Manage system users</p>
                <a href="views/user_management_view.php" class="btn-primary">Manage Users</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
