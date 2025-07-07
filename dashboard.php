<?php
/**
 * Dashboard - Main application page
 */

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

<div class="container">
    <!-- GOLDEN RULE #2: Consistent Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-section">
                <h1>DPTI Rocket System Dashboard</h1>
                <p class="page-description">Monitor and manage your rocket production pipeline</p>
            </div>
            <div class="page-actions">
                <a href="views/rocket_add_view.php" class="btn btn-primary">
                    <span>🚀</span> Add New Rocket
                </a>
                <?php if (has_role('engineer') || has_role('admin')): ?>
                    <a href="controllers/approval_controller.php?action=list_pending" class="btn btn-secondary">
                        <span>📋</span> Review Approvals
                    </a>
                <?php endif; ?>
            </div>
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
    
    <!-- GOLDEN RULE #1: Single Home for Global Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🚀</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $rocket_count; ?></div>
                <div class="stat-label">Total Rockets</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⚙️</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo count(array_filter($rockets, function($r) { return $r['current_status'] === 'In Production'; })); ?></div>
                <div class="stat-label">In Production</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo count(array_filter($rockets, function($r) { return $r['current_status'] === 'Completed'; })); ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <?php if (has_role('engineer') || has_role('admin')): ?>
            <?php
            // Get approval statistics for engineers/admins
            require_once 'includes/approval_functions.php';
            $approval_stats = getApprovalStatistics($pdo);
            $pending_count = $approval_stats['pending_count'] ?? 0;
            ?>
            <div class="stat-card <?php echo $pending_count > 0 ? 'stat-card-alert' : ''; ?>">
                <div class="stat-icon">📋</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $pending_count; ?></div>
                    <div class="stat-label">Pending Approvals</div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Content: Focus on Primary Data -->
    <div class="main-content-area">
        <!-- Primary Content: Rockets Overview -->
        <div class="content-card">
            <div class="card-header">
                <h2>Rockets Overview</h2>
                <div class="card-actions">
                    <span class="card-subtitle"><?php echo count($rockets); ?> rockets in system</span>
                </div>
            </div>
            
            <?php if (empty($rockets)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🚀</div>
                    <h3>No rockets found</h3>
                    <p>Get started by adding your first rocket to the system.</p>
                    <a href="views/rocket_add_view.php" class="btn btn-primary">Add First Rocket</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-modern">
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
                                        <span class="font-mono font-semibold"><?php echo htmlspecialchars($rocket['serial_number']); ?></span>
                                    </td>
                                    <td class="project-name">
                                        <span class="font-medium"><?php echo htmlspecialchars($rocket['project_name']); ?></span>
                                    </td>
                                    <td class="status">
                                        <span class="status-badge-modern status-<?php echo strtolower(str_replace(' ', '-', $rocket['current_status'])); ?>">
                                            <?php echo htmlspecialchars($rocket['current_status']); ?>
                                        </span>
                                    </td>
                                    <td class="created-date">
                                        <span class="text-gray-600"><?php echo date('M j, Y', strtotime($rocket['created_at'])); ?></span>
                                    </td>
                                    <td class="actions">
                                        <div class="action-buttons">
                                            <a href="views/rocket_detail_view.php?id=<?php echo $rocket['rocket_id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                            <a href="views/production_steps_view.php?rocket_id=<?php echo $rocket['rocket_id']; ?>" class="btn btn-sm btn-secondary">Steps</a>
                                            <?php if (has_role('admin') || has_role('engineer')): ?>
                                                <a href="views/rocket_edit_view.php?id=<?php echo $rocket['rocket_id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions Section (Replacing redundant navigation cards) -->
        <?php if (has_role('engineer') || has_role('admin')): ?>
            <div class="quick-actions-grid">
                <div class="quick-action-card">
                    <div class="quick-action-icon">📊</div>
                    <div class="quick-action-content">
                        <h3>Production Steps</h3>
                        <p>View and manage production progress</p>
                        <a href="views/production_steps_view.php" class="btn btn-sm btn-secondary">View Steps</a>
                    </div>
                </div>
                
                <div class="quick-action-card">
                    <div class="quick-action-icon">📋</div>
                    <div class="quick-action-content">
                        <h3>Step Templates</h3>
                        <p>Create and manage production templates</p>
                        <a href="views/templates_list_view.php" class="btn btn-sm btn-secondary">Manage Templates</a>
                    </div>
                </div>
                
                <?php if (has_role('admin')): ?>
                    <div class="quick-action-card">
                        <div class="quick-action-icon">👥</div>
                        <div class="quick-action-content">
                            <h3>User Management</h3>
                            <p>Manage system users and permissions</p>
                            <a href="views/user_management_view.php" class="btn btn-sm btn-secondary">Manage Users</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- End container -->
<!-- End container -->

<?php include 'includes/footer.php'; ?>
