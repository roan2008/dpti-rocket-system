<?php
/**
 * Production Controller
 * Handles all production step-related operations
 */

// Start session first
session_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';
require_once '../includes/rocket_functions.php';
require_once '../includes/production_functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../views/login_view.php');
    exit;
}

// Get the action from POST or GET
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add_step':
        handle_add_step();
        break;
    case 'edit_step':
        handle_edit_step();
        break;
    case 'delete_step':
        handle_delete_step();
        break;
    case 'view_steps':
        handle_view_steps();
        break;
    default:
        // Invalid action - redirect to dashboard
        header('Location: ../dashboard.php?error=invalid_action');
        exit;
}

/**
 * Handle adding a new production step
 */
function handle_add_step() {
    global $pdo;
    
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../dashboard.php?error=invalid_method');
        exit;
    }
    
    // Get and validate form data
    $rocket_id = (int) ($_POST['rocket_id'] ?? 0);
    $template_id = trim($_POST['step_name'] ?? ''); // This is actually template_id now
    $data_json = trim($_POST['data_json'] ?? '');
    $staff_id = $_SESSION['user_id'] ?? 0;
    
    // Validate required fields
    if ($rocket_id <= 0 || empty($template_id) || $staff_id <= 0) {
        redirect_with_error('missing_fields', $rocket_id, $template_id, $data_json);
        return;
    }
    
    // Verify rocket exists
    $rocket = get_rocket_by_id($pdo, $rocket_id);
    if (!$rocket) {
        redirect_with_error('invalid_rocket', $rocket_id, $template_id, $data_json);
        return;
    }
    
    // Include template functions for validation
    require_once '../includes/template_functions.php';
    
    // Validate template exists and is active
    $template_data = getTemplateWithFields($pdo, $template_id);
    if (!$template_data || !$template_data['is_active']) {
        redirect_with_error('invalid_template', $rocket_id, $template_id, $data_json);
        return;
    }
    
    // Get the actual step name from template
    $step_name = $template_data['step_name'];
    
    // Validate JSON data if provided
    if (!empty($data_json)) {
        $validated_json = validateStepJsonData($data_json);
        if ($validated_json === false) {
            redirect_with_error('invalid_json', $rocket_id, $template_id, $data_json);
            return;
        }
    } else {
        // Create default JSON data if none provided
        $data_json = createStepJsonData($step_name, [
            'template_id' => $template_id,
            'recorded_by' => $_SESSION['username'],
            'status' => 'completed'
        ]);
    }
    
    // Add the production step
    $step_id = addProductionStep($pdo, $rocket_id, $step_name, $data_json, $staff_id);
    
    if ($step_id) {
        // Success - redirect to rocket detail view with success message
        header('Location: ../views/rocket_detail_view.php?id=' . $rocket_id . '&success=step_added&step_id=' . $step_id);
        exit;
    } else {
        // Failed to create step
        redirect_with_error('step_creation_failed', $rocket_id, $step_name, $data_json);
        return;
    }
}

/**
 * Handle editing an existing production step
 */
function handle_edit_step() {
    global $pdo;
    
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../dashboard.php?error=invalid_method');
        exit;
    }
    
    // Check permissions (admin or engineer can edit steps)
    if (!has_role('admin') && !has_role('engineer')) {
        header('Location: ../dashboard.php?error=insufficient_permissions');
        exit;
    }
    
    // Get step ID
    $step_id = (int) ($_POST['step_id'] ?? 0);
    if ($step_id <= 0) {
        header('Location: ../dashboard.php?error=invalid_step_id');
        exit;
    }
    
    // Get and validate form data
    $step_name = trim($_POST['step_name'] ?? '');
    $data_json = trim($_POST['data_json'] ?? '');
    
    // Validate required fields
    if (empty($step_name)) {
        header('Location: ../views/step_edit_view.php?id=' . $step_id . '&error=missing_fields');
        exit;
    }
    
    // Check if step exists
    $existing_step = getProductionStepById($pdo, $step_id);
    if (!$existing_step) {
        header('Location: ../dashboard.php?error=step_not_found');
        exit;
    }
    
    // Validate JSON data if provided
    if (!empty($data_json)) {
        $validated_json = validateStepJsonData($data_json);
        if ($validated_json === false) {
            header('Location: ../views/step_edit_view.php?id=' . $step_id . '&error=invalid_json');
            exit;
        }
    }
    
    // Update the step
    $update_result = updateProductionStep($pdo, $step_id, $step_name, $data_json);
    
    if ($update_result) {
        header('Location: ../views/rocket_detail_view.php?id=' . $existing_step['rocket_id'] . '&success=step_updated');
        exit;
    } else {
        header('Location: ../views/step_edit_view.php?id=' . $step_id . '&error=update_failed');
        exit;
    }
}

/**
 * Handle deleting a production step
 */
function handle_delete_step() {
    global $pdo;
    
    // Check permissions (only admin can delete steps)
    if (!has_role('admin')) {
        header('Location: ../dashboard.php?error=insufficient_permissions');
        exit;
    }
    
    // Get step ID
    $step_id = (int) ($_POST['step_id'] ?? $_GET['step_id'] ?? 0);
    if ($step_id <= 0) {
        header('Location: ../dashboard.php?error=invalid_step_id');
        exit;
    }
    
    // Get step data before deletion for redirect
    $step = getProductionStepById($pdo, $step_id);
    if (!$step) {
        header('Location: ../dashboard.php?error=step_not_found');
        exit;
    }
    
    $rocket_id = $step['rocket_id'];
    
    // Delete the step using enhanced function
    $delete_result = deleteProductionStep($pdo, $step_id);
    
    if ($delete_result['success']) {
        header('Location: ../views/rocket_detail_view.php?id=' . $rocket_id . '&success=step_deleted&step_name=' . urlencode($step['step_name']));
        exit;
    } else {
        // Handle different error types
        $error_param = 'error=' . $delete_result['error'];
        if (isset($delete_result['message'])) {
            $error_param .= '&message=' . urlencode($delete_result['message']);
        }
        
        header('Location: ../views/rocket_detail_view.php?id=' . $rocket_id . '&' . $error_param);
        exit;
    }
}

/**
 * Handle viewing production steps for a rocket
 */
function handle_view_steps() {
    global $pdo;
    
    // Get rocket ID
    $rocket_id = (int) ($_GET['rocket_id'] ?? 0);
    if ($rocket_id <= 0) {
        header('Location: ../dashboard.php?error=invalid_rocket_id');
        exit;
    }
    
    // Verify rocket exists
    $rocket = get_rocket_by_id($pdo, $rocket_id);
    if (!$rocket) {
        header('Location: ../dashboard.php?error=rocket_not_found');
        exit;
    }
    
    // Redirect to rocket detail view (which now shows production steps)
    header('Location: ../views/rocket_detail_view.php?id=' . $rocket_id);
    exit;
}

/**
 * Helper function to redirect with error and preserve form data
 */
function redirect_with_error($error, $rocket_id = '', $step_name = '', $data_json = '') {
    $params = http_build_query([
        'error' => $error,
        'rocket_id' => $rocket_id,
        'step_name' => $step_name,
        'data_json' => $data_json
    ]);
    
    if (!empty($rocket_id)) {
        header('Location: ../views/step_add_view.php?rocket_id=' . $rocket_id . '&' . $params);
    } else {
        header('Location: ../dashboard.php?' . $params);
    }
    exit;
}

/**
 * Log production step activity for audit purposes
 */
function log_step_activity($action, $step_id, $user_id, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (action, entity_type, entity_id, user_id, details, timestamp) 
            VALUES (?, 'production_step', ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$action, $step_id, $user_id, $details]);
    } catch (PDOException $e) {
        error_log("Activity log error: " . $e->getMessage());
        // Don't fail the main operation if logging fails
    }
}
?>
