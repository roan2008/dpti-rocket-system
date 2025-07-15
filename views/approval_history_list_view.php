<?php
/**
 * Approval History List View
 * Displays all approval history with comprehensive information
 */

$page_title = $page_title ?? "Approval History";
include '../includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-history"></i> Approval History
                    </h3>
                    <div>
                        <a href="approval_controller.php?action=list_pending" class="btn btn-outline-primary">
                            <i class="fas fa-clock"></i> Pending Approvals
                        </a>
                        <a href="../dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if (isset($approval_stats) && !empty($approval_stats)): ?>
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h4><?php echo $approval_stats['approved'] ?? 0; ?></h4>
                                        <small>Approved</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h4><?php echo $approval_stats['rejected'] ?? 0; ?></h4>
                                        <small>Rejected</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h4><?php echo $approval_stats['pending'] ?? 0; ?></h4>
                                        <small>Pending</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h4><?php echo ($approval_stats['approved'] ?? 0) + ($approval_stats['rejected'] ?? 0) + ($approval_stats['pending'] ?? 0); ?></h4>
                                        <small>Total</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($all_history)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle"></i>
                            No approval history found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Rocket</th>
                                        <th>Step</th>
                                        <th>Staff</th>
                                        <th>Engineer</th>
                                        <th>Status</th>
                                        <th>Approval Date</th>
                                        <th>Comments</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_history as $approval): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($approval['serial_number']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($approval['project_name']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($approval['step_name']); ?></span><br>
                                                <small><?php echo date('M j, Y g:i A', strtotime($approval['step_timestamp'])); ?></small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($approval['staff_name']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($approval['engineer_name']); ?>
                                            </td>
                                            <td>
                                                <?php if ($approval['status'] === 'approved'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> Approved
                                                    </span>
                                                <?php elseif ($approval['status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times"></i> Rejected
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock"></i> <?php echo ucfirst($approval['status']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y g:i A', strtotime($approval['approval_timestamp'])); ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($approval['comments'])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            data-bs-toggle="tooltip" 
                                                            title="<?php echo htmlspecialchars($approval['comments']); ?>">
                                                        <i class="fas fa-comment"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="approval_controller.php?action=view_history&step_id=<?php echo $approval['step_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="View Step Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize Bootstrap tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include '../includes/footer.php'; ?>
