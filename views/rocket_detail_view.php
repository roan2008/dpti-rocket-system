<?php
/**
 * Rocket Detail View
 * Display detailed information about a specific rocket
 * Allow editing for authorized users
 */

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';
require_once '../includes/rocket_functions.php';
require_once '../includes/production_functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login_view.php');
    exit;
}

// Get rocket ID from URL
$rocket_id = (int) ($_GET['id'] ?? 0);
if ($rocket_id <= 0) {
    header('Location: ../dashboard.php?error=invalid_rocket_id');
    exit;
}

// Get rocket details
$rocket = get_rocket_by_id($pdo, $rocket_id);
if (!$rocket) {
    header('Location: ../dashboard.php?error=rocket_not_found');
    exit;
}

// Get production steps for this rocket
$production_steps = getStepsByRocketId($pdo, $rocket_id);
$step_count = countStepsByRocketId($pdo, $rocket_id);

// Check if user can edit (admin or engineer)
$can_edit = has_role('admin') || has_role('engineer');

// Check if this is edit mode
$edit_mode = isset($_GET['edit']) && $_GET['edit'] === '1' && $can_edit;

include '../includes/header.php';
?>

<div class="container">
<div class="detail-container">
    <div class="detail-header">
        <div class="header-left">
            <h1><?php echo $edit_mode ? 'Edit Rocket' : 'Rocket Details'; ?></h1>
            <p class="breadcrumb">
                <a href="../dashboard.php">Dashboard</a> → 
                <span><?php echo htmlspecialchars($rocket['serial_number']); ?></span>
            </p>
        </div>
        <div class="header-actions">
            <?php if (!$edit_mode && $can_edit): ?>
                <a href="?id=<?php echo $rocket_id; ?>&edit=1" class="btn btn-primary">Edit Rocket</a>
            <?php endif; ?>
            
            <?php if ($edit_mode): ?>
                <a href="?id=<?php echo $rocket_id; ?>" class="btn btn-secondary">Cancel Edit</a>
            <?php endif; ?>
            
            <?php if (has_role('admin')): ?>
                <button onclick="confirmDelete()" class="btn btn-danger">Delete Rocket</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="message success">
            <?php
            switch ($_GET['success']) {
                case 'updated':
                    echo "Rocket information updated successfully!";
                    break;
                case 'status_updated':
                    echo "Rocket status updated successfully!";
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
                case 'missing_fields':
                    echo "Please fill in all required fields.";
                    break;
                case 'serial_exists':
                    echo "A rocket with this serial number already exists.";
                    break;
                case 'update_failed':
                    echo "Failed to update rocket information.";
                    break;
                case 'invalid_status':
                    echo "Invalid status selected.";
                    break;
                default:
                    echo "An error occurred. Please try again.";
            }
            ?>
        </div>
    <?php endif; ?>

    <div class="detail-content">
        <?php if ($edit_mode): ?>
            <!-- Edit Form -->
            <form method="POST" action="../controllers/rocket_controller.php" class="rocket-edit-form">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="rocket_id" value="<?php echo $rocket_id; ?>">
                
                <div class="form-grid">
                    <div class="form-section">
                        <h3>Basic Information</h3>
                        
                        <div class="form-group">
                            <label for="serial_number">Serial Number <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="serial_number" 
                                name="serial_number" 
                                value="<?php echo htmlspecialchars($rocket['serial_number']); ?>"
                                required 
                                maxlength="50"
                                pattern="[A-Za-z0-9\-]+"
                                title="Only letters, numbers, and hyphens are allowed"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="project_name">Project Name <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="project_name" 
                                name="project_name" 
                                value="<?php echo htmlspecialchars($rocket['project_name']); ?>"
                                required 
                                maxlength="255"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="current_status">Status <span class="required">*</span></label>
                            <select id="current_status" name="current_status" required>
                                <?php 
                                $statuses = ['New', 'Planning', 'Design', 'In Production', 'Testing', 'Completed', 'On Hold'];
                                foreach ($statuses as $status): 
                                ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>" 
                                            <?php echo ($status === $rocket['current_status']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Metadata</h3>
                        <div class="info-item">
                            <label>Rocket ID:</label>
                            <span><?php echo htmlspecialchars($rocket['rocket_id']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Created:</label>
                            <span><?php echo date('M j, Y g:i A', strtotime($rocket['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Rocket</button>
                    <a href="?id=<?php echo $rocket_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <!-- Display Mode -->
            <div class="detail-grid">
                <div class="detail-section">
                    <h3>Rocket Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Serial Number:</label>
                            <span class="serial-number"><?php echo htmlspecialchars($rocket['serial_number']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Project Name:</label>
                            <span class="project-name"><?php echo htmlspecialchars($rocket['project_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Current Status:</label>
                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $rocket['current_status'])); ?>">
                                <?php echo htmlspecialchars($rocket['current_status']); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <label>Rocket ID:</label>
                            <span><?php echo htmlspecialchars($rocket['rocket_id']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Created:</label>
                            <span><?php echo date('M j, Y g:i A', strtotime($rocket['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Status Update (for all authenticated users) -->
                <div class="detail-section">
                    <h3>Quick Actions</h3>
                    <form method="POST" action="../controllers/rocket_controller.php" class="quick-status-form">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="rocket_id" value="<?php echo $rocket_id; ?>">
                        
                        <div class="form-group">
                            <label for="new_status">Update Status:</label>
                            <div class="status-update-group">
                                <select id="new_status" name="new_status">
                                    <?php 
                                    $statuses = ['New', 'Planning', 'Design', 'In Production', 'Testing', 'Completed', 'On Hold'];
                                    foreach ($statuses as $status): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>" 
                                                <?php echo ($status === $rocket['current_status']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Production Steps Section -->
            <div class="detail-section">
                <div class="section-header">
                    <h3>Production History (<?php echo $step_count; ?> steps)</h3>
                    <a href="step_add_view.php?rocket_id=<?php echo $rocket_id; ?>" class="btn btn-primary">
                        Add New Production Step
                    </a>
                </div>
                
                <?php if (empty($production_steps)): ?>
                    <div class="empty-state">
                        <p>No production steps recorded yet.</p>
                        <p><a href="step_add_view.php?rocket_id=<?php echo $rocket_id; ?>">Add the first production step</a> to start tracking progress.</p>
                    </div>
                <?php else: ?>
                    <div class="steps-container">
                        <?php foreach ($production_steps as $step): ?>
                            <div class="step-card">
                                <div class="step-header">
                                    <h4 class="step-name"><?php echo htmlspecialchars($step['step_name']); ?></h4>
                                    <span class="step-timestamp">
                                        <?php echo date('M j, Y g:i A', strtotime($step['step_timestamp'])); ?>
                                    </span>
                                </div>
                                
                                <div class="step-content">
                                    <div class="step-info">
                                        <span class="step-staff">
                                            Recorded by: <strong><?php echo htmlspecialchars($step['staff_full_name']); ?></strong>
                                            (<?php echo htmlspecialchars($step['staff_username']); ?>)
                                        </span>
                                    </div>
                                    
                                    <?php if (!empty($step['data_json'])): ?>
                                        <div class="step-data">
                                            <details>
                                                <summary>Step Details</summary>
                                                <div class="json-data">
                                                    <?php 
                                                    $step_data = json_decode($step['data_json'], true);
                                                    if ($step_data): 
                                                    ?>
                                                        <table class="data-table">
                                                            <?php foreach ($step_data as $key => $value): ?>
                                                                <tr>
                                                                    <td class="data-key"><?php echo htmlspecialchars($key); ?>:</td>
                                                                    <td class="data-value"><?php echo htmlspecialchars($value); ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </table>
                                                    <?php else: ?>
                                                        <pre><?php echo htmlspecialchars($step['data_json']); ?></pre>
                                                    <?php endif; ?>
                                                </div>
                                            </details>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (has_role('admin') || has_role('engineer')): ?>
                                    <div class="step-actions">
                                        <button onclick="editStep(<?php echo $step['step_id']; ?>)" class="btn-small btn-edit">
                                            Edit
                                        </button>
                                        <?php if (has_role('admin')): ?>
                                            <button onclick="deleteStep(<?php echo $step['step_id']; ?>)" class="btn-small btn-delete">
                                                Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Summary Statistics -->
                <div class="steps-summary">
                    <div class="summary-stats">
                        <div class="stat-item">
                            <label>Total Steps:</label>
                            <span><?php echo $step_count; ?></span>
                        </div>
                        <?php if (!empty($production_steps)): ?>
                            <div class="stat-item">
                                <label>Latest Step:</label>
                                <span><?php echo htmlspecialchars($production_steps[0]['step_name']); ?></span>
                            </div>
                            <div class="stat-item">
                                <label>Last Updated:</label>
                                <span><?php echo date('M j, Y g:i A', strtotime($production_steps[0]['step_timestamp'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- End container -->

<!-- Delete Confirmation Modal (for admins only) -->
<?php if (has_role('admin')): ?>
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to delete rocket <strong><?php echo htmlspecialchars($rocket['serial_number']); ?></strong>?</p>
        <p class="warning">This action cannot be undone and will remove all associated production steps and approvals.</p>
        <div class="modal-actions">
            <form method="POST" action="../controllers/rocket_controller.php" style="display: inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="rocket_id" value="<?php echo $rocket_id; ?>">
                <button type="submit" class="btn btn-danger">Yes, Delete</button>
            </form>
            <button onclick="closeModal()" class="btn btn-secondary">Cancel</button>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeModal() {
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
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
