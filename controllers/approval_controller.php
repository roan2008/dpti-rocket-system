<?php
/**
 * Approval Controller
 * Handles all approval workflow operations
 */

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';
require_once '../includes/approval_functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../views/login_view.php');
    exit;
}

// Check if user has permission (engineer or admin only)
if (!has_role('engineer') && !has_role('admin')) {
    header('Location: ../dashboard.php?error=insufficient_permissions');
    exit;
}

// Get the action from POST or GET
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'list_pending':
        handle_list_pending();
        break;
    case 'list_history':
        handle_list_history();
        break;
    case 'submit_approval':
        handle_submit_approval();
        break;
    case 'view_history':
        handle_view_history();
        break;
    default:
        // Default action - show pending approvals
        handle_list_pending();
        break;
}

/**
 * Handle listing pending approvals
 */
function handle_list_pending() {
    global $pdo;
    
    try {
        // Get all pending approvals
        $pending_approvals = getPendingApprovals($pdo);
        
        // Get approval statistics for dashboard info
        $approval_stats = getApprovalStatistics($pdo);
        
        // Include the pending approvals view
        include '../views/pending_approvals_view.php';
        
    } catch (Exception $e) {
        error_log("Error loading pending approvals: " . $e->getMessage());
        header('Location: ../dashboard.php?error=approval_load_failed');
        exit;
    }
}

/**
 * Handle listing all approval history
 */
function handle_list_history() {
    global $pdo;
    
    try {
        // Get all approval history
        $all_history = getAllApprovalHistory($pdo);
        
        // Get approval statistics for dashboard info
        $approval_stats = getApprovalStatistics($pdo);
        
        // Include the approval history view
        include '../views/approval_history_list_view.php';
        
    } catch (Exception $e) {
        error_log("Error loading approval history: " . $e->getMessage());
        header('Location: ../dashboard.php?error=history_load_failed');
        exit;
    }
}

/**
 * Handle approval submission
 */
function handle_submit_approval() {
    global $pdo;
    
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../views/pending_approvals_view.php?error=invalid_method');
        exit;
    }
    
    // Get and validate form data
    $step_id = (int) ($_POST['step_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $comments = trim($_POST['comments'] ?? '');
    $engineer_id = $_SESSION['user_id'] ?? 0;
    
    // Validate required fields
    if ($step_id <= 0 || empty($status) || $engineer_id <= 0) {
        redirect_with_approval_error('missing_fields', $step_id);
        return;
    }
    
    // Validate status
    if (!in_array($status, ['approved', 'rejected'])) {
        redirect_with_approval_error('invalid_status', $step_id);
        return;
    }
    
    // Submit the approval
    $result = submitApproval($pdo, $step_id, $engineer_id, $status, $comments);
    
    if ($result) {
        // Success - redirect to pending approvals with success message
        header('Location: ../views/pending_approvals_view.php?success=approval_submitted&status=' . $status);
        exit;
    } else {
        // Failure - redirect with error
        redirect_with_approval_error('submission_failed', $step_id);
    }
}

/**
 * Handle viewing approval history for a specific step
 */
function handle_view_history() {
    global $pdo;
    
    $step_id = (int) ($_GET['step_id'] ?? 0);
    
    if ($step_id <= 0) {
        header('Location: ../views/pending_approvals_view.php?error=invalid_step_id');
        exit;
    }
    
    try {
        // Get approval history for the step
        $approval_history = getApprovalHistoryForStep($pdo, $step_id);
        
        // Get step details
        require_once '../includes/production_functions.php';
        $step_details = getProductionStepById($pdo, $step_id);
        
        if (!$step_details) {
            header('Location: ../views/pending_approvals_view.php?error=step_not_found');
            exit;
        }
        
        // Include the approval history view
        include '../views/approval_history_view.php';
        
    } catch (Exception $e) {
        error_log("Error loading approval history: " . $e->getMessage());
        header('Location: ../views/pending_approvals_view.php?error=history_load_failed');
        exit;
    }
}

/**
 * Helper function to redirect with error messages
 */
function redirect_with_approval_error($error, $step_id = '') {
    $params = http_build_query([
        'error' => $error,
        'step_id' => $step_id
    ]);
    
    header('Location: ../views/pending_approvals_view.php?' . $params);
    exit;
}

/**
 * Log approval activity for audit purposes
 */
function log_approval_activity($action, $step_id, $engineer_id, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO approval_logs (action, step_id, engineer_id, details, log_timestamp) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$action, $step_id, $engineer_id, $details]);
    } catch (Exception $e) {
        error_log("Failed to log approval activity: " . $e->getMessage());
    }
}
?>
