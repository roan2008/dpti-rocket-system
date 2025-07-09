<?php
/**
 * Template List View
 * Displays all step templates for admin and engineer users
 */

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/user_functions.php';
require_once '../includes/db_connect.php';
require_once '../includes/template_functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login_view.php');
    exit;
}

// Check if user has permission to view templates (admin or engineer)
if (!has_role('admin') && !has_role('engineer')) {
    header('Location: ../dashboard.php?error=insufficient_permissions');
    exit;
}

// Get filter parameters
$search_term = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? 'all');
$creator_filter = trim($_GET['creator'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$sort_by = trim($_GET['sort_by'] ?? 'step_name');
$sort_order = trim($_GET['sort_order'] ?? 'ASC');

// Get templates based on filters
if (!empty($search_term) || $status_filter !== 'all' || !empty($creator_filter) || !empty($date_from) || !empty($date_to)) {
    $templates = search_templates($pdo, $search_term, $status_filter, $creator_filter, $date_from, $date_to, $sort_by, $sort_order);
    $filtered_count = count($templates);
} else {
    $templates = getAllTemplates($pdo);
    $filtered_count = count($templates);
}

// Get total template count and available creators for filters
$total_template_count = count(getAllTemplates($pdo));
$available_creators = get_template_creators($pdo);

// Get user details for created_by lookup
function getUserName($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ? $user['full_name'] : 'Unknown User';
    } catch (PDOException $e) {
        return 'Unknown User';
    }
}

include '../includes/header.php';
?>

<div class="container">
<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Step Template Management</h1>
        <div class="user-info">
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            <p>Role: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
            <a href="../controllers/logout_controller.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>

    <!-- Breadcrumb Navigation -->
    <div class="page-header">
        <div class="header-left">
            <h2>Template Management</h2>
            <p class="breadcrumb">
                <a href="../dashboard.php">Dashboard</a> → 
                <span>Template Management</span>
            </p>
        </div>
        <div class="header-actions">
            <?php if (has_role('admin') || has_role('engineer')): ?>
                <a href="template_form_view.php" class="btn btn-primary">Add New Template</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="message success">
            <?php 
            switch ($_GET['success']) {
                case 'template_created':
                    echo htmlspecialchars('Step template created successfully!');
                    break;
                case 'template_updated':
                    echo htmlspecialchars('Step template updated successfully!');
                    break;
                case 'template_deleted':
                    echo htmlspecialchars('Step template deleted successfully!');
                    break;
                case 'status_updated':
                    echo htmlspecialchars('Template status updated successfully!');
                    break;
                default:
                    echo htmlspecialchars('Operation completed successfully!');
                    break;
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="message error">
            <?php 
            switch ($_GET['error']) {
                case 'insufficient_permissions':
                    echo htmlspecialchars('You do not have permission to perform this action.');
                    break;
                case 'template_not_found':
                    echo htmlspecialchars('Template not found.');
                    break;
                case 'invalid_template_id':
                    echo htmlspecialchars('Invalid template ID.');
                    break;
                case 'delete_failed':
                    echo htmlspecialchars('Failed to delete template. Please try again.');
                    break;
                case 'status_update_failed':
                    echo htmlspecialchars('Failed to update template status. Please try again.');
                    break;
                case 'invalid_action':
                    echo htmlspecialchars('Invalid action requested.');
                    break;
                default:
                    echo htmlspecialchars('An error occurred. Please try again.');
                    break;
            }
            ?>
        </div>
    <?php endif; ?>

    <!-- Templates Section -->
    <div class="section">
        <div class="section-header">
            <h2>Step Templates</h2>
            <div class="section-actions">
                <span class="section-subtitle">
                    <?php if (!empty($search_term) || $status_filter !== 'all' || !empty($creator_filter) || !empty($date_from) || !empty($date_to)): ?>
                        <?php echo $filtered_count; ?> of <?php echo $total_template_count; ?> templates shown
                    <?php else: ?>
                        <?php echo count($templates); ?> templates total
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-row">
                    <div class="search-group">
                        <label for="search_input">Search Templates:</label>
                        <div class="search-input-group">
                            <input 
                                type="text" 
                                id="search_input" 
                                name="search" 
                                placeholder="Search by step name or description..." 
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
                            <option value="all" <?php echo ($status_filter === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>Active Only</option>
                            <option value="inactive" <?php echo ($status_filter === 'inactive') ? 'selected' : ''; ?>>Inactive Only</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="creator_filter">Created By:</label>
                        <select id="creator_filter" name="creator">
                            <option value="">All Creators</option>
                            <?php foreach ($available_creators as $creator): ?>
                                <option value="<?php echo $creator['user_id']; ?>"
                                        <?php echo ($creator_filter == $creator['user_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($creator['full_name']); ?>
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
                            <option value="step_name" <?php echo ($sort_by === 'step_name') ? 'selected' : ''; ?>>Template Name</option>
                            <option value="created_at" <?php echo ($sort_by === 'created_at') ? 'selected' : ''; ?>>Created Date</option>
                            <option value="created_by" <?php echo ($sort_by === 'created_by') ? 'selected' : ''; ?>>Creator</option>
                            <option value="is_active" <?php echo ($sort_by === 'is_active') ? 'selected' : ''; ?>>Status</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort_order">Order:</label>
                        <select id="sort_order" name="sort_order">
                            <option value="ASC" <?php echo ($sort_order === 'ASC') ? 'selected' : ''; ?>>A-Z / Oldest First</option>
                            <option value="DESC" <?php echo ($sort_order === 'DESC') ? 'selected' : ''; ?>>Z-A / Newest First</option>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <?php if (!empty($search_term) || $status_filter !== 'all' || !empty($creator_filter) || !empty($date_from) || !empty($date_to) || $sort_by !== 'step_name' || $sort_order !== 'ASC'): ?>
                            <a href="templates_list_view.php" class="btn btn-outline">Clear All</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <?php if (empty($templates)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <?php if (!empty($search_term) || $status_filter !== 'all' || !empty($creator_filter) || !empty($date_from) || !empty($date_to)): ?>
                    <h3>No templates match your filters</h3>
                    <p>Try adjusting your search criteria or clearing the filters.</p>
                    <a href="templates_list_view.php" class="btn btn-secondary">Clear Filters</a>
                <?php else: ?>
                    <h3>No step templates found</h3>
                    <p>Create your first step template to get started.</p>
                    <a href="template_form_view.php" class="btn btn-primary">Create First Template</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="section-content">
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Template ID</th>
                                <th>Step Name</th>
                                <th>Description</th>
                                <th>Created By</th>
                                <th>Created Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td class="template-id">
                                        <span class="serial-number"><?php echo htmlspecialchars($template['template_id']); ?></span>
                                    </td>
                                    <td class="step-name">
                                        <span class="project-name"><?php echo htmlspecialchars($template['step_name']); ?></span>
                                    </td>
                                    <td class="description">
                                        <?php 
                                        $description = $template['step_description'] ?? '';
                                        if (strlen($description) > 60) {
                                            echo htmlspecialchars(substr($description, 0, 60)) . '...';
                                        } else {
                                            echo htmlspecialchars($description ?: 'No description');
                                        }
                                        ?>
                                    </td>
                                    <td class="created-by">
                                        <?php echo htmlspecialchars($template['creator_name'] ?? 'Unknown User'); ?>
                                    </td>
                                    <td class="created-date">
                                        <?php echo date('M j, Y', strtotime($template['created_at'])); ?>
                                    </td>
                                    <td class="template-status">
                                        <?php if ($template['is_active']): ?>
                                            <span class="badge badge-active">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <div class="btn-group">
                                            <a href="template_view.php?id=<?php echo $template['template_id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                            <?php if (has_role('admin') || has_role('engineer')): ?>
                                                <a href="template_form_view.php?id=<?php echo $template['template_id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                            <?php endif; ?>
                                            <?php if (has_role('admin')): ?>
                                                <button onclick="confirmDeleteTemplate(<?php echo $template['template_id']; ?>, '<?php echo htmlspecialchars(addslashes($template['step_name'])); ?>')" class="btn btn-sm btn-outline-danger">Delete</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Template Statistics -->
    <div class="section">
        <div class="section-header">
            <h2>Template Statistics</h2>
        </div>
        
        <div class="section-content">
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-card-icon">📋</div>
                    <h3><?php echo count($templates); ?></h3>
                    <p>Active Templates</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">🔧</div>
                    <h3><?php echo getTemplateFieldCount($pdo, 0); ?></h3>
                    <p>Total Template Fields</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">📊</div>
                    <h3>0</h3>
                    <p>Templates Used This Month</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">✅</div>
                    <h3><?php echo count(array_filter($templates, function($t) { return $t['is_active'] ?? true; })); ?></h3>
                    <p>Ready for Production</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Confirm Delete</h3>
        <p>Are you sure you want to delete the template "<span id="templateName"></span>"?</p>
        <p class="warning">This action cannot be undone and will also delete all associated template fields.</p>
        <div class="modal-actions">
            <form id="deleteForm" method="POST" action="../controllers/template_controller.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="template_id" id="deleteTemplateId">
                <div class="btn-group">
                    <button type="submit" class="btn btn-outline-danger">Delete Template</button>
                    <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End container -->

<script>
function confirmDeleteTemplate(templateId, templateName) {
    document.getElementById('templateName').textContent = templateName;
    document.getElementById('deleteTemplateId').value = templateId;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
