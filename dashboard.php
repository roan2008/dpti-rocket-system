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

// Get filter parameters
$search_term = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$sort_by = trim($_GET['sort_by'] ?? 'created_at');
$sort_order = trim($_GET['sort_order'] ?? 'DESC');

// Get rockets based on filters
if (!empty($search_term) || !empty($status_filter) || !empty($date_from) || !empty($date_to)) {
    $rockets = search_rockets($pdo, $search_term, $status_filter, $date_from, $date_to, $sort_by, $sort_order);
    $filtered_count = count($rockets);
} else {
    $rockets = get_all_rockets($pdo);
    $filtered_count = count($rockets);
}

// Get total rocket count and available statuses for filters
$total_rocket_count = count_rockets($pdo);
$available_statuses = get_rocket_statuses($pdo);

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
                <div class="stat-number"><?php echo $total_rocket_count; ?></div>
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
                    <span class="card-subtitle">
                        <?php if (!empty($search_term) || !empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                            <?php echo $filtered_count; ?> of <?php echo $total_rocket_count; ?> rockets shown
                        <?php else: ?>
                            <?php echo count($rockets); ?> rockets in system
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-row">
                        <div class="search-group">
                            <label for="search_input">Search Rockets:</label>
                            <div class="search-input-group">
                                <input 
                                    type="text" 
                                    id="search_input" 
                                    name="search" 
                                    placeholder="Search by serial number or project name..." 
                                    value="<?php echo htmlspecialchars($search_term); ?>"
                                >
                                <button type="submit" class="btn btn-secondary">Search</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="status_filter">Status:</label>
                            <select id="status_filter" name="status">
                                <option value="">All Statuses</option>
                                <?php foreach ($available_statuses as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>"
                                            <?php echo ($status_filter === $status) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_from">Created From:</label>
                            <input 
                                type="date" 
                                id="date_from" 
                                name="date_from" 
                                value="<?php echo htmlspecialchars($date_from); ?>"
                            >
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">Created To:</label>
                            <input 
                                type="date" 
                                id="date_to" 
                                name="date_to" 
                                value="<?php echo htmlspecialchars($date_to); ?>"
                            >
                        </div>
                        
                        <div class="filter-group">
                            <label for="sort_by">Sort By:</label>
                            <select id="sort_by" name="sort_by">
                                <option value="created_at" <?php echo ($sort_by === 'created_at') ? 'selected' : ''; ?>>Created Date</option>
                                <option value="serial_number" <?php echo ($sort_by === 'serial_number') ? 'selected' : ''; ?>>Serial Number</option>
                                <option value="project_name" <?php echo ($sort_by === 'project_name') ? 'selected' : ''; ?>>Project Name</option>
                                <option value="current_status" <?php echo ($sort_by === 'current_status') ? 'selected' : ''; ?>>Status</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="sort_order">Order:</label>
                            <select id="sort_order" name="sort_order">
                                <option value="DESC" <?php echo ($sort_order === 'DESC') ? 'selected' : ''; ?>>Newest First</option>
                                <option value="ASC" <?php echo ($sort_order === 'ASC') ? 'selected' : ''; ?>>Oldest First</option>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <?php if (!empty($search_term) || !empty($status_filter) || !empty($date_from) || !empty($date_to) || $sort_by !== 'created_at' || $sort_order !== 'DESC'): ?>
                                <a href="dashboard.php" class="btn btn-outline">Clear All</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
            
            <?php if (empty($rockets)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🚀</div>
                    <?php if (!empty($search_term) || !empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                        <h3>No rockets match your filters</h3>
                        <p>Try adjusting your search criteria or clearing the filters.</p>
                        <a href="dashboard.php" class="btn btn-secondary">Clear Filters</a>
                    <?php else: ?>
                        <h3>No rockets found</h3>
                        <p>Get started by adding your first rocket to the system.</p>
                        <a href="views/rocket_add_view.php" class="btn btn-primary">Add First Rocket</a>
                    <?php endif; ?>
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
