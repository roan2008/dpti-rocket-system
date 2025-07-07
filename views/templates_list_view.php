<?php
/**
 * Template List View
 * Displays all step templates for admin and engineer users
 */

// Start session and check authentication
session_start();

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

// Get all active templates
$templates = getAllActiveTemplates($pdo);

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

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Step Template Management</h1>
        <div class="user-info">
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            <p>Role: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
            <a href="../controllers/logout_controller.php" class="btn-logout">Logout</a>
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
        </div>

        <?php if (!empty($templates)): ?>
            <div class="section-content">
                <div class="table-responsive">
                    <table class="table">
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
                                        <?php echo htmlspecialchars(getUserName($pdo, $template['created_by'])); ?>
                                    </td>
                                    <td class="created-date">
                                        <?php echo date('M j, Y', strtotime($template['created_at'])); ?>
                                    </td>
                                    <td class="template-status">
                                        <span class="badge badge-active">Active</span>
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
        <?php else: ?>
            <div class="empty-state">
                <h3>No step templates found</h3>
                <p>Get started by creating your first step template to standardize your production processes.</p>
                <?php if (has_role('admin') || has_role('engineer')): ?>
                    <a href="template_form_view.php" class="btn btn-primary">Create Your First Template</a>
                <?php endif; ?>
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
