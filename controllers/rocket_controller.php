<?php
/**
 * Rocket Controller
 * Handles all rocket-related operations (CRUD)
 */

// Start session first
session_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';
require_once '../includes/rocket_functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../views/login_view.php');
    exit;
}

// Get the action from POST or GET
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        handle_add_rocket();
        break;
    case 'edit':
        handle_edit_rocket();
        break;
    case 'delete':
        handle_delete_rocket();
        break;
    case 'update_status':
        handle_update_status();
        break;
    default:
        // Invalid action - redirect to dashboard
        header('Location: ../dashboard.php?error=invalid_action');
        exit;
}

/**
 * Handle adding a new rocket
 */
function handle_add_rocket() {
    global $pdo;
    
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../views/rocket_add_view.php?error=invalid_method');
        exit;
    }
    
    // Check permissions (admin or engineer can add rockets)
    if (!has_role('admin') && !has_role('engineer')) {
        header('Location: ../dashboard.php?error=insufficient_permissions');
        exit;
    }
    
    // Get and validate form data
    $serial_number = trim($_POST['serial_number'] ?? '');
    $project_name = trim($_POST['project_name'] ?? '');
    $current_status = $_POST['current_status'] ?? 'New';
    
    // Validate required fields
    if (empty($serial_number) || empty($project_name)) {
        redirect_with_error('missing_fields', $serial_number, $project_name, $current_status);
        return;
    }
    
    // Validate serial number format (alphanumeric and hyphens only)
    if (!preg_match('/^[A-Za-z0-9\-]+$/', $serial_number)) {
        redirect_with_error('invalid_serial', $serial_number, $project_name, $current_status);
        return;
    }
    
    // Check if serial number already exists
    $existing_rocket = get_rocket_by_serial($pdo, $serial_number);
    if ($existing_rocket) {
        redirect_with_error('serial_exists', $serial_number, $project_name, $current_status);
        return;
    }
    
    // Validate status (ensure it's from allowed list)
    $allowed_statuses = ['New', 'Planning', 'Design', 'In Production', 'Testing', 'Completed', 'On Hold'];
    if (!in_array($current_status, $allowed_statuses)) {
        $current_status = 'New'; // Default to 'New' if invalid status
    }
    
    // Create the rocket
    $rocket_id = create_rocket($pdo, $serial_number, $project_name, $current_status);
    
    if ($rocket_id) {
        // Success - redirect to dashboard with success message
        header('Location: ../dashboard.php?success=rocket_created&rocket_id=' . $rocket_id);
        exit;
    } else {
        // Failed to create rocket
        redirect_with_error('creation_failed', $serial_number, $project_name, $current_status);
        return;
    }
}

/**
 * Handle editing an existing rocket
 */
function handle_edit_rocket() {
    global $pdo;
    
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../dashboard.php?error=invalid_method');
        exit;
    }
    
    // Check permissions (admin or engineer can edit rockets)
    if (!has_role('admin') && !has_role('engineer')) {
        header('Location: ../dashboard.php?error=insufficient_permissions');
        exit;
    }
    
    // Get rocket ID
    $rocket_id = (int) ($_POST['rocket_id'] ?? 0);
    if ($rocket_id <= 0) {
        header('Location: ../dashboard.php?error=invalid_rocket_id');
        exit;
    }
    
    // Get and validate form data
    $serial_number = trim($_POST['serial_number'] ?? '');
    $project_name = trim($_POST['project_name'] ?? '');
    $current_status = $_POST['current_status'] ?? '';
    
    // Validate required fields
    if (empty($serial_number) || empty($project_name) || empty($current_status)) {
        header('Location: ../views/rocket_detail_view.php?id=' . $rocket_id . '&error=missing_fields');
        exit;
    }
    
    // Check if rocket exists
    $existing_rocket = get_rocket_by_id($pdo, $rocket_id);
    if (!$existing_rocket) {
        header('Location: ../dashboard.php?error=rocket_not_found');
        exit;
    }
    
    // Check if serial number is being changed and if new serial already exists
    if ($serial_number !== $existing_rocket['serial_number']) {
        $serial_check = get_rocket_by_serial($pdo, $serial_number);
        if ($serial_check) {
            header('Location: ../views/rocket_detail_view.php?id=' . $rocket_id . '&error=serial_exists');
            exit;
        }
    }
    
    // Update the rocket
    $update_result = update_rocket($pdo, $rocket_id, $serial_number, $project_name, $current_status);
    
    if ($update_result) {
        header('Location: ../views/rocket_detail_view.php?id=' . $rocket_id . '&success=updated');
        exit;
    } else {
        header('Location: ../views/rocket_detail_view.php?id=' . $rocket_id . '&error=update_failed');
        exit;
    }
}

/**
 * Handle deleting a rocket
 */
function handle_delete_rocket() {
    global $pdo;
    
    // Check permissions (only admin can delete rockets)
    if (!has_role('admin')) {
        header('Location: ../dashboard.php?error=insufficient_permissions');
        exit;
    }
    
    // Get rocket ID
    $rocket_id = (int) ($_POST['rocket_id'] ?? $_GET['rocket_id'] ?? 0);
    if ($rocket_id <= 0) {
        header('Location: ../dashboard.php?error=invalid_rocket_id');
        exit;
    }
    
    // Check if rocket exists
    $rocket = get_rocket_by_id($pdo, $rocket_id);
    if (!$rocket) {
        header('Location: ../dashboard.php?error=rocket_not_found');
        exit;
    }
    
    // Delete the rocket
    $delete_result = delete_rocket($pdo, $rocket_id);
    
    if ($delete_result) {
        header('Location: ../dashboard.php?success=rocket_deleted');
        exit;
    } else {
        header('Location: ../dashboard.php?error=delete_failed');
        exit;
    }
}

/**
 * Handle updating rocket status only
 */
function handle_update_status() {
    global $pdo;
    
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../dashboard.php?error=invalid_method');
        exit;
    }
    
    // Check permissions (admin, engineer, or staff can update status)
    if (!has_role('admin') && !has_role('engineer') && !has_role('staff')) {
        header('Location: ../dashboard.php?error=insufficient_permissions');
        exit;
    }
    
    // Get data
    $rocket_id = (int) ($_POST['rocket_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    
    if ($rocket_id <= 0 || empty($new_status)) {
        header('Location: ../dashboard.php?error=missing_data');
        exit;
    }
    
    // Update status
    $update_result = update_rocket_status($pdo, $rocket_id, $new_status);
    
    if ($update_result) {
        header('Location: ../dashboard.php?success=status_updated');
        exit;
    } else {
        header('Location: ../dashboard.php?error=status_update_failed');
        exit;
    }
}

/**
 * Helper function to redirect with error and preserve form data
 */
function redirect_with_error($error, $serial_number = '', $project_name = '', $current_status = '') {
    $params = http_build_query([
        'error' => $error,
        'serial_number' => $serial_number,
        'project_name' => $project_name,
        'current_status' => $current_status
    ]);
    
    header('Location: ../views/rocket_add_view.php?' . $params);
    exit;
}
?>
