<?php
/**
 * Pending Approvals View
 * Display all production steps awaiting approval (Engineer/Admin only)
 */

// Include header
include '../includes/header.php';
?>

<div class="approvals-container">
    <div class="approvals-header">
        <div class="header-left">
            <h1>Pending Approvals</h1>
            <p class="breadcrumb">
                <a href="../dashboard.php">Dashboard</a> → 
                <span>Approvals</span>
            </p>
        </div>
        <div class="header-stats">
            <div class="stat-card">
                <h3><?php echo $approval_stats['pending_count']; ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $approval_stats['approved_count']; ?></h3>
                <p>Approved</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $approval_stats['rejected_count']; ?></h3>
                <p>Rejected</p>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="message success">
            <?php
            switch ($_GET['success']) {
                case 'approval_submitted':
                    $status = $_GET['status'] ?? 'processed';
                    echo "Approval successfully submitted! Status: " . ucfirst(htmlspecialchars($status));
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
                case 'invalid_status':
                    echo "Invalid approval status selected.";
                    break;
                case 'submission_failed':
                    echo "Failed to submit approval. Please try again.";
                    break;
                case 'step_not_found':
                    echo "Production step not found.";
                    break;
                case 'insufficient_permissions':
                    echo "You don't have permission to perform this action.";
                    break;
                case 'approval_load_failed':
                    echo "Failed to load approval data.";
                    break;
                default:
                    echo "An error occurred. Please try again.";
            }
            ?>
        </div>
    <?php endif; ?>

    <div class="approvals-content">
        <?php if (empty($pending_approvals)): ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-icon">✅</div>
                <h3>All Caught Up!</h3>
                <p>There are no production steps awaiting approval at this time.</p>
                <div class="empty-actions">
                    <a href="../dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                    <a href="../views/production_steps_view.php" class="btn btn-secondary">View All Steps</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Pending Approvals Table -->
            <div class="table-container">
                <table class="approvals-table">
                    <thead>
                        <tr>
                            <th>Rocket Serial</th>
                            <th>Project Name</th>
                            <th>Step Name</th>
                            <th>Recorded By</th>
                            <th>Recorded Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_approvals as $step): ?>
                            <tr class="approval-row" data-step-id="<?php echo $step['step_id']; ?>">
                                <td class="serial-number">
                                    <a href="../views/rocket_detail_view.php?id=<?php echo $step['rocket_id']; ?>">
                                        <?php echo htmlspecialchars($step['serial_number']); ?>
                                    </a>
                                </td>
                                <td class="project-name">
                                    <?php echo htmlspecialchars($step['project_name']); ?>
                                </td>
                                <td class="step-name">
                                    <strong><?php echo htmlspecialchars($step['step_name']); ?></strong>
                                </td>
                                <td class="staff-info">
                                    <div class="staff-name"><?php echo htmlspecialchars($step['staff_name']); ?></div>
                                    <div class="staff-username">@<?php echo htmlspecialchars($step['staff_username']); ?></div>
                                </td>
                                <td class="step-date">
                                    <div class="date-primary"><?php echo date('M j, Y', strtotime($step['step_timestamp'])); ?></div>
                                    <div class="date-time"><?php echo date('g:i A', strtotime($step['step_timestamp'])); ?></div>
                                </td>
                                <td class="actions">
                                    <button onclick="openApprovalModal(<?php echo $step['step_id']; ?>, '<?php echo addslashes($step['step_name']); ?>', '<?php echo addslashes($step['serial_number']); ?>')" 
                                            class="btn btn-primary btn-small">
                                        Review & Approve
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary Info -->
            <div class="approvals-summary">
                <div class="summary-info">
                    <p><strong><?php echo count($pending_approvals); ?></strong> production steps awaiting your approval</p>
                    <p>As an engineer, your approval is required to advance these rockets through the production pipeline.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Approval Modal -->
<div id="approvalModal" class="modal" style="display: none;">
    <div class="modal-content approval-modal">
        <div class="modal-header">
            <h3>Review & Approve Production Step</h3>
            <span class="modal-close" onclick="closeApprovalModal()">&times;</span>
        </div>
        
        <form method="POST" action="../controllers/approval_controller.php" class="approval-form">
            <input type="hidden" name="action" value="submit_approval">
            <input type="hidden" id="modal_step_id" name="step_id" value="">
            
            <div class="modal-body">
                <div class="step-info">
                    <div class="info-row">
                        <label>Rocket:</label>
                        <span id="modal_rocket_serial"></span>
                    </div>
                    <div class="info-row">
                        <label>Step:</label>
                        <span id="modal_step_name"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="status">Approval Decision <span class="required">*</span></label>
                    <div class="radio-group">
                        <label class="radio-option approve-option">
                            <input type="radio" name="status" value="approved" required>
                            <span class="radio-label">
                                <span class="radio-icon">✅</span>
                                <span class="radio-text">
                                    <strong>Approve</strong>
                                    <small>This step meets quality standards</small>
                                </span>
                            </span>
                        </label>
                        
                        <label class="radio-option reject-option">
                            <input type="radio" name="status" value="rejected" required>
                            <span class="radio-label">
                                <span class="radio-icon">❌</span>
                                <span class="radio-text">
                                    <strong>Reject</strong>
                                    <small>This step needs rework or correction</small>
                                </span>
                            </span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="comments">Comments</label>
                    <textarea 
                        id="comments" 
                        name="comments" 
                        rows="4" 
                        placeholder="Add your review comments here (optional for approval, recommended for rejection)..."
                        class="form-control"
                    ></textarea>
                    <small class="field-help">
                        Provide feedback, quality notes, or instructions for improvement
                    </small>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Submit Approval</button>
                <button type="button" onclick="closeApprovalModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal management functions
function openApprovalModal(stepId, stepName, rocketSerial) {
    document.getElementById('modal_step_id').value = stepId;
    document.getElementById('modal_step_name').textContent = stepName;
    document.getElementById('modal_rocket_serial').textContent = rocketSerial;
    
    // Reset form
    document.querySelector('.approval-form').reset();
    document.getElementById('modal_step_id').value = stepId; // Restore after reset
    
    // Show modal
    document.getElementById('approvalModal').style.display = 'flex';
}

function closeApprovalModal() {
    document.getElementById('approvalModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('approvalModal');
    if (event.target === modal) {
        closeApprovalModal();
    }
}

// Keyboard support
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeApprovalModal();
    }
});

// Form validation enhancement
document.querySelector('.approval-form').addEventListener('submit', function(e) {
    const status = document.querySelector('input[name="status"]:checked');
    const comments = document.getElementById('comments').value.trim();
    
    // Warn if rejecting without comments
    if (status && status.value === 'rejected' && comments === '') {
        if (!confirm('You are rejecting this step without comments. Are you sure you want to continue?')) {
            e.preventDefault();
            return false;
        }
    }
    
    return true;
});

// Auto-focus comments when rejecting
document.querySelectorAll('input[name="status"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.value === 'rejected') {
            setTimeout(() => {
                document.getElementById('comments').focus();
            }, 100);
        }
    });
});
</script>

<style>
/* Approval-specific styles */
.approvals-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.approvals-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e1e5e9;
}

.header-stats {
    display: flex;
    gap: 15px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 15px 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    min-width: 80px;
}

.stat-card h3 {
    margin: 0;
    font-size: 24px;
    color: #2c3e50;
}

.stat-card p {
    margin: 5px 0 0 0;
    font-size: 12px;
    color: #7f8c8d;
    text-transform: uppercase;
}

.approvals-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.approvals-table th,
.approvals-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #e1e5e9;
}

.approvals-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
}

.approval-row:hover {
    background: #f8f9fa;
}

.staff-username {
    font-size: 12px;
    color: #7f8c8d;
}

.date-time {
    font-size: 12px;
    color: #7f8c8d;
}

.approval-modal .modal-content {
    max-width: 500px;
}

.step-info {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
}

.info-row {
    display: flex;
    margin-bottom: 8px;
}

.info-row label {
    font-weight: 600;
    width: 80px;
    color: #2c3e50;
}

.radio-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.radio-option {
    display: flex;
    align-items: center;
    padding: 15px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.radio-option:hover {
    border-color: #3498db;
    background: #f8f9fa;
}

.radio-option input[type="radio"] {
    display: none;
}

.radio-option input[type="radio"]:checked + .radio-label {
    color: #2c3e50;
}

.approve-option input[type="radio"]:checked ~ .radio-label,
.approve-option:has(input[type="radio"]:checked) {
    border-color: #27ae60;
    background: #d5f4e6;
}

.reject-option input[type="radio"]:checked ~ .radio-label,
.reject-option:has(input[type="radio"]:checked) {
    border-color: #e74c3c;
    background: #fdf2f2;
}

.radio-label {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
}

.radio-icon {
    font-size: 20px;
}

.radio-text strong {
    display: block;
    margin-bottom: 3px;
}

.radio-text small {
    color: #7f8c8d;
    font-size: 12px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.empty-actions {
    margin-top: 30px;
    display: flex;
    gap: 15px;
    justify-content: center;
}
</style>

<?php include '../includes/footer.php'; ?>
