<?php
/**
 * Rocket Detail View
 * Display detailed information about a specific rocket
 * Allow editing for authorized users
 */

// Start session and check authentication
session_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';
require_once '../includes/rocket_functions.php';

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

// Check if user can edit (admin or engineer)
$can_edit = has_role('admin') || has_role('engineer');

// Check if this is edit mode
$edit_mode = isset($_GET['edit']) && $_GET['edit'] === '1' && $can_edit;

include '../includes/header.php';
?>

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
                                <button type="submit" class="btn btn-small btn-primary">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Future: Production Steps Section -->
            <div class="detail-section">
                <h3>Production Steps</h3>
                <div class="placeholder-content">
                    <p>Production steps tracking will be implemented in the next development phase.</p>
                    <div class="coming-soon">
                        <span class="coming-soon-badge">Coming Soon</span>
                        <ul>
                            <li>View production step history</li>
                            <li>Add new production steps</li>
                            <li>Track step completion</li>
                            <li>Approval workflow</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

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
