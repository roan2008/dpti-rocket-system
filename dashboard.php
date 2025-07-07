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
        <!-- Approval Workflow Section (Engineers and Admins Only) -->
        <?php if (has_role('engineer') || has_role('admin')): ?>
            <div class="approvals-section">
                <div class="section-header">
                    <h2>Approval Workflow</h2>
                    <div class="approval-actions">
                        <a href="controllers/approval_controller.php?action=list_pending" class="btn-primary">
                            📋 Review Pending Approvals
                        </a>
                        <a href="views/production_steps_view.php" class="btn-secondary">
                            📊 View All Production Steps
                        </a>
                    </div>
                </div>
                
                <div class="approval-summary">
                    <?php
                    // Get quick approval statistics for dashboard
                    require_once 'includes/approval_functions.php';
                    $approval_stats = getApprovalStatistics($pdo);
                    $pending_count = $approval_stats['pending_count'] ?? 0;
                    $approved_count = $approval_stats['approved_count'] ?? 0;
                    $rejected_count = $approval_stats['rejected_count'] ?? 0;
                    ?>
                    
                    <div class="approval-stats">
                        <div class="stat-card pending">
                            <div class="stat-number"><?php echo $pending_count; ?></div>
                            <div class="stat-label">Pending Review</div>
                        </div>
                        <div class="stat-card approved">
                            <div class="stat-number"><?php echo $approved_count; ?></div>
                            <div class="stat-label">Approved</div>
                        </div>
                        <div class="stat-card rejected">
                            <div class="stat-number"><?php echo $rejected_count; ?></div>
                            <div class="stat-label">Rejected</div>
                        </div>
                    </div>
                    
                    <?php if ($pending_count > 0): ?>
                        <div class="approval-alert">
                            <strong>⚠️ Action Required:</strong> 
                            <?php echo $pending_count; ?> production step<?php echo $pending_count === 1 ? '' : 's'; ?> 
                            <?php echo $pending_count === 1 ? 'needs' : 'need'; ?> your approval review.
                        </div>
                    <?php else: ?>
                        <div class="approval-success">
                            <strong>✅ All Caught Up:</strong> 
                            No production steps are currently pending approval.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
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

<style>
/* Approval Workflow Section Styling */
.approvals-section {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.approvals-section .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.approval-actions {
    display: flex;
    gap: 10px;
}

.approval-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.stat-card {
    background: white;
    border-radius: 6px;
    padding: 15px 20px;
    text-align: center;
    min-width: 100px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card.pending {
    border-left: 4px solid #ffc107;
}

.stat-card.approved {
    border-left: 4px solid #28a745;
}

.stat-card.rejected {
    border-left: 4px solid #dc3545;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
}

.approval-alert {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    padding: 10px 15px;
    color: #856404;
}

.approval-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
    padding: 10px 15px;
    color: #155724;
}
</style>

<?php include 'includes/footer.php'; ?>
