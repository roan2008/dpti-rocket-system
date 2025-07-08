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
        <div class="page-actions">
            <?php if (!$edit_mode): ?>
                <button onclick="openStatusUpdateModal()" class="btn btn-primary">
                    <i class="icon-edit"></i> Manual Status Update
                </button>
                <?php if ($can_edit): ?>
                    <a href="?id=<?php echo $rocket_id; ?>&edit=1" class="btn btn-secondary">
                        <i class="icon-settings"></i> Edit Rocket
                    </a>
                <?php endif; ?>
                <?php if (has_role('admin')): ?>
                    <button onclick="confirmDelete()" class="btn btn-danger">
                        <i class="icon-delete"></i> Delete Rocket
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <a href="?id=<?php echo $rocket_id; ?>" class="btn btn-secondary">Cancel Edit</a>
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
                case 'status_updated_with_audit':
                    $previous = htmlspecialchars($_GET['previous_status'] ?? 'Unknown');
                    $new = htmlspecialchars($_GET['new_status'] ?? 'Unknown');
                    $log_id = htmlspecialchars($_GET['log_id'] ?? 'N/A');
                    echo "✅ Status successfully updated from '<strong>$previous</strong>' to '<strong>$new</strong>'<br>";
                    echo "<small>Change logged with audit ID: #$log_id</small>";
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
                case 'missing_status':
                    echo "Please select a new status.";
                    break;
                case 'missing_reason':
                    echo "Change reason is required for audit purposes.";
                    break;
                case 'reason_too_short':
                    echo "Change reason must be at least 10 characters long.";
                    break;
                case 'reason_too_long':
                    echo "Change reason cannot exceed 500 characters.";
                    break;
                case 'same_status':
                    echo "The selected status is the same as the current status.";
                    break;
                case 'audit_update_failed':
                    $message = htmlspecialchars($_GET['message'] ?? 'Unknown error');
                    echo "Failed to update status: $message";
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
            </div>
            
            <!-- Production Steps Section -->
            <div class="detail-section">
                <div class="section-header-modern">
                    <div class="section-title">
                        <h3>Production History</h3>
                        <span class="section-subtitle"><?php echo $step_count; ?> steps recorded</span>
                    </div>
                    <div class="section-actions">
                        <a href="step_add_view.php?rocket_id=<?php echo $rocket_id; ?>" class="btn btn-primary">
                            <i class="icon-plus"></i> Add New Production Step
                        </a>
                    </div>
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

<!-- Status Update Modal with Audit Trail -->
<div id="statusUpdateModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3>Update Rocket Status</h3>
            <button onclick="closeStatusModal()" class="modal-close">&times;</button>
        </div>
        
        <div class="modal-body">
            <div class="current-status-info">
                <div class="status-info-card">
                    <h4>Current Information</h4>
                    <div class="info-row">
                        <span class="label">Rocket:</span>
                        <span class="value"><?php echo htmlspecialchars($rocket['serial_number']); ?> - <?php echo htmlspecialchars($rocket['project_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Current Status:</span>
                        <span class="value">
                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $rocket['current_status'])); ?>">
                                <?php echo htmlspecialchars($rocket['current_status']); ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
            
            <form id="statusUpdateForm" method="POST" action="../controllers/rocket_controller.php" class="status-update-form">
                <input type="hidden" name="action" value="update_status_with_audit">
                <input type="hidden" name="rocket_id" value="<?php echo $rocket_id; ?>">
                <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($rocket['current_status']); ?>">
                
                <div class="form-group">
                    <label for="modal_new_status">New Status <span class="required">*</span></label>
                    <select id="modal_new_status" name="new_status" required>
                        <option value="">-- Select New Status --</option>
                        <?php 
                        $statuses = [
                            'New' => 'New Project',
                            'Planning' => 'Planning Phase', 
                            'Design' => 'Design Phase',
                            'Development' => 'Development Phase',
                            'Testing' => 'Testing Phase',
                            'Ready for Production' => 'Ready for Production',
                            'In Production' => 'In Production',
                            'Completed' => 'Completed',
                            'On Hold' => 'On Hold',
                            'Cancelled' => 'Cancelled'
                        ];
                        
                        foreach ($statuses as $value => $display): 
                            // Don't show current status as an option
                            if ($value !== $rocket['current_status']):
                        ?>
                            <option value="<?php echo htmlspecialchars($value); ?>">
                                <?php echo htmlspecialchars($display); ?>
                            </option>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </select>
                    <small class="form-hint">Select the new status for this rocket</small>
                </div>
                
                <div class="form-group">
                    <label for="change_reason">Reason for Change <span class="required">*</span></label>
                    <textarea 
                        id="change_reason" 
                        name="change_reason" 
                        required 
                        rows="4" 
                        maxlength="500"
                        placeholder="Please provide a detailed reason for this status change. This will be logged for audit purposes."
                    ></textarea>
                    <small class="form-hint">
                        <span id="reason-counter">0</span>/500 characters. 
                        This reason will be permanently recorded in the audit log.
                    </small>
                </div>
                
                <div class="status-preview" id="statusPreview" style="display: none;">
                    <div class="preview-card">
                        <h4>Change Preview</h4>
                        <div class="status-change-visual">
                            <span class="current-status">
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $rocket['current_status'])); ?>">
                                    <?php echo htmlspecialchars($rocket['current_status']); ?>
                                </span>
                            </span>
                            <span class="arrow">→</span>
                            <span class="new-status" id="previewNewStatus">
                                <!-- Will be filled by JavaScript -->
                            </span>
                        </div>
                        <div class="change-summary">
                            <p><strong>Reason:</strong> <span id="previewReason"><!-- Will be filled by JavaScript --></span></p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="modal-footer">
            <button type="button" onclick="closeStatusModal()" class="btn btn-secondary">Cancel</button>
            <button type="submit" form="statusUpdateForm" class="btn btn-primary" id="confirmChangeBtn" disabled>
                <i class="icon-check"></i> Confirm Status Change
            </button>
        </div>
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
// Status Update Modal Functions
function openStatusUpdateModal() {
    document.getElementById('statusUpdateModal').style.display = 'flex';
    // Reset form
    document.getElementById('statusUpdateForm').reset();
    document.getElementById('statusPreview').style.display = 'none';
    document.getElementById('confirmChangeBtn').disabled = true;
    updateCharacterCounter();
}

function closeStatusModal() {
    document.getElementById('statusUpdateModal').style.display = 'none';
}

// Character counter for reason textarea
function updateCharacterCounter() {
    const textarea = document.getElementById('change_reason');
    const counter = document.getElementById('reason-counter');
    const count = textarea.value.length;
    counter.textContent = count;
    
    // Change color based on usage
    if (count > 450) {
        counter.style.color = '#dc3545'; // Red when near limit
    } else if (count > 300) {
        counter.style.color = '#ffc107'; // Yellow when getting long
    } else {
        counter.style.color = '#6c757d'; // Default gray
    }
}

// Preview status change
function updateStatusPreview() {
    const newStatusSelect = document.getElementById('modal_new_status');
    const reasonTextarea = document.getElementById('change_reason');
    const preview = document.getElementById('statusPreview');
    const confirmBtn = document.getElementById('confirmChangeBtn');
    
    const newStatus = newStatusSelect.value;
    const reason = reasonTextarea.value.trim();
    
    if (newStatus && reason.length >= 10) {
        // Show preview
        preview.style.display = 'block';
        confirmBtn.disabled = false;
        
        // Update preview content
        const previewNewStatus = document.getElementById('previewNewStatus');
        const previewReason = document.getElementById('previewReason');
        
        // Create status badge for new status
        const statusClass = 'status-' + newStatus.toLowerCase().replace(/\s+/g, '-');
        previewNewStatus.innerHTML = `<span class="status-badge ${statusClass}">${newStatus}</span>`;
        
        // Truncate reason if too long for preview
        const displayReason = reason.length > 100 ? reason.substring(0, 97) + '...' : reason;
        previewReason.textContent = displayReason;
    } else {
        // Hide preview and disable button
        preview.style.display = 'none';
        confirmBtn.disabled = true;
    }
}

// Form validation and submission
function validateStatusForm() {
    const newStatus = document.getElementById('modal_new_status').value;
    const reason = document.getElementById('change_reason').value.trim();
    
    if (!newStatus) {
        alert('Please select a new status.');
        return false;
    }
    
    if (reason.length < 10) {
        alert('Please provide a more detailed reason (at least 10 characters).');
        document.getElementById('change_reason').focus();
        return false;
    }
    
    // Show loading state
    const confirmBtn = document.getElementById('confirmChangeBtn');
    confirmBtn.innerHTML = '<i class="icon-loading"></i> Updating...';
    confirmBtn.disabled = true;
    
    return true;
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Character counter
    document.getElementById('change_reason').addEventListener('input', function() {
        updateCharacterCounter();
        updateStatusPreview();
    });
    
    // Status change preview
    document.getElementById('modal_new_status').addEventListener('change', updateStatusPreview);
    
    // Form submission validation
    document.getElementById('statusUpdateForm').addEventListener('submit', function(e) {
        if (!validateStatusForm()) {
            e.preventDefault();
        }
    });
    
    // Close modal on outside click
    window.addEventListener('click', function(event) {
        const statusModal = document.getElementById('statusUpdateModal');
        if (event.target === statusModal) {
            closeStatusModal();
        }
    });
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const statusModal = document.getElementById('statusUpdateModal');
            if (statusModal.style.display === 'flex') {
                closeStatusModal();
            }
        }
    });
});

// Legacy delete modal functions (keep existing functionality)
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
