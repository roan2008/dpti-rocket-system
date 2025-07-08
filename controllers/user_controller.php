<?php
/**
 * User Controller
 * Handles all user management operations (list, create, update, delete)
 */

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';

// Access Control: Only admins can access this controller
if (!isset($_SESSION['user_id']) || !is_logged_in()) {
    header('Location: ../views/login_view.php');
    exit;
}

if (!has_role('admin')) {
    header('Location: ../dashboard.php?error=insufficient_permissions');
    exit;
}

// Get the action from POST or GET
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Route the action
switch ($action) {
    case 'list':
        handle_list();
        break;
    
    case 'show_form':
        handle_show_form();
        break;
    
    case 'create':
        handle_create();
        break;
    
    case 'update':
        handle_update();
        break;
    
    case 'delete':
        handle_delete();
        break;
    
    default:
        // Default to list view
        header('Location: ../views/user_management_view.php');
        exit;
}

/**
 * Handle list action (redirect to user management view)
 */
function handle_list() {
    header('Location: ../views/user_management_view.php');
    exit;
}

/**
 * Handle show form action (redirect to user form view)
 */
function handle_show_form() {
    $user_id = $_GET['id'] ?? '';
    
    if ($user_id) {
        // Edit mode
        header('Location: ../views/user_form_view.php?id=' . urlencode($user_id));
    } else {
        // Create mode
        header('Location: ../views/user_form_view.php');
    }
    exit;
}

/**
 * Handle create action (process new user creation)
 */
function handle_create() {
    global $pdo;
    
    // Validate that this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../views/user_form_view.php?error=invalid_request');
        exit;
    }
    
    // Get form data
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Server-side validation
    $validation_error = validate_user_input($full_name, $username, $role, $password, $confirm_password, false);
    
    if ($validation_error) {
        $error_details = urlencode($validation_error);
        header('Location: ../views/user_form_view.php?error=validation_failed&details=' . $error_details);
        exit;
    }
    
    // Attempt to create user
    $result = create_user($pdo, $username, $password, $full_name, $role);
    
    if ($result['success']) {
        // Success - redirect to user management with success message
        header('Location: ../views/user_management_view.php?success=user_created&username=' . urlencode($username));
        exit;
    } else {
        // Failed - redirect back to form with error
        $error_details = urlencode($result['message']);
        header('Location: ../views/user_form_view.php?error=save_failed&details=' . $error_details);
        exit;
    }
}

/**
 * Handle update action (process user updates)
 */
function handle_update() {
    global $pdo;
    
    // Validate that this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../views/user_management_view.php?error=invalid_request');
        exit;
    }
    
    // Get form data
    $user_id = (int)($_POST['user_id'] ?? 0);
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate user ID
    if ($user_id <= 0) {
        header('Location: ../views/user_management_view.php?error=invalid_user_id');
        exit;
    }
    
    // Server-side validation (password is optional for updates)
    $validation_error = validate_user_input($full_name, $username, $role, $password, $confirm_password, true);
    
    if ($validation_error) {
        $error_details = urlencode($validation_error);
        header('Location: ../views/user_form_view.php?id=' . $user_id . '&error=validation_failed&details=' . $error_details);
        exit;
    }
    
    // Prepare password parameter (null if not changing)
    $password_param = (!empty($password)) ? $password : null;
    
    // Attempt to update user
    $result = update_user($pdo, $user_id, $username, $full_name, $role, $password_param);
    
    if ($result['success']) {
        // Success - redirect to user management with success message
        header('Location: ../views/user_management_view.php?success=user_updated&username=' . urlencode($username));
        exit;
    } else {
        // Failed - redirect back to form with error
        $error_details = urlencode($result['message']);
        header('Location: ../views/user_form_view.php?id=' . $user_id . '&error=save_failed&details=' . $error_details);
        exit;
    }
}

/**
 * Handle delete action (process user deletion)
 */
function handle_delete() {
    global $pdo;
    
    // Validate that this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../views/user_management_view.php?error=invalid_request');
        exit;
    }
    
    // Get user ID
    $user_id = (int)($_POST['user_id'] ?? 0);
    $current_user_id = (int)$_SESSION['user_id'];
    
    // Validate user ID
    if ($user_id <= 0) {
        header('Location: ../views/user_management_view.php?error=invalid_user_id');
        exit;
    }
    
    // Get user data for response message
    $user_data = get_user_by_id($pdo, $user_id);
    $username = $user_data ? $user_data['username'] : 'Unknown';
    
    // Attempt to delete user
    $result = delete_user($pdo, $user_id, $current_user_id);
    
    if ($result['success']) {
        // Success - redirect to user management with success message
        header('Location: ../views/user_management_view.php?success=user_deleted&username=' . urlencode($username));
        exit;
    } else {
        // Failed - redirect back with error
        $error_details = urlencode($result['message']);
        header('Location: ../views/user_management_view.php?error=delete_failed&details=' . $error_details);
        exit;
    }
}

/**
 * Validate user input data
 * 
 * @param string $full_name Full name
 * @param string $username Username
 * @param string $role Role
 * @param string $password Password
 * @param string $confirm_password Confirm password
 * @param bool $is_update Whether this is an update (password optional)
 * @return string|null Error message or null if valid
 */
function validate_user_input($full_name, $username, $role, $password, $confirm_password, $is_update = false) {
    // Check required fields
    if (empty($full_name) || empty($username) || empty($role)) {
        return 'All required fields must be filled in.';
    }
    
    // Validate role
    $valid_roles = ['admin', 'engineer', 'staff'];
    if (!in_array($role, $valid_roles)) {
        return 'Invalid role selected.';
    }
    
    // Validate username format
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return 'Username can only contain letters, numbers, and underscores.';
    }
    
    // Validate username length
    if (strlen($username) < 3 || strlen($username) > 50) {
        return 'Username must be between 3 and 50 characters long.';
    }
    
    // Validate full name length
    if (strlen($full_name) < 2 || strlen($full_name) > 100) {
        return 'Full name must be between 2 and 100 characters long.';
    }
    
    // Password validation (only if password is provided)
    if (!empty($password) || !$is_update) {
        // For new users, password is required
        if (!$is_update && empty($password)) {
            return 'Password is required for new users.';
        }
        
        // If password is provided, validate it
        if (!empty($password)) {
            if (strlen($password) < 8) {
                return 'Password must be at least 8 characters long.';
            }
            
            if ($password !== $confirm_password) {
                return 'Password and confirm password do not match.';
            }
        }
    }
    
    return null; // No validation errors
}

/**
 * AJAX endpoint for user operations (if needed for future enhancements)
 */
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    
    $ajax_action = $_POST['ajax_action'] ?? $_GET['ajax_action'] ?? '';
    
    switch ($ajax_action) {
        case 'check_username':
            handle_ajax_check_username();
            break;
        
        case 'get_user_info':
            handle_ajax_get_user_info();
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid AJAX action']);
    }
    exit;
}

/**
 * AJAX: Check if username is available
 */
function handle_ajax_check_username() {
    global $pdo;
    
    $username = trim($_POST['username'] ?? '');
    $exclude_user_id = (int)($_POST['exclude_user_id'] ?? 0);
    
    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Username is required']);
        return;
    }
    
    // Check if username exists (excluding current user for updates)
    if ($exclude_user_id > 0) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $stmt->execute([$username, $exclude_user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
    }
    
    $exists = $stmt->fetch() !== false;
    
    echo json_encode([
        'success' => true,
        'available' => !$exists,
        'message' => $exists ? 'Username already exists' : 'Username is available'
    ]);
}

/**
 * AJAX: Get user information
 */
function handle_ajax_get_user_info() {
    global $pdo;
    
    $user_id = (int)($_GET['user_id'] ?? 0);
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }
    
    $user_data = get_user_by_id($pdo, $user_id);
    
    if ($user_data) {
        // Remove sensitive data
        unset($user_data['password_hash']);
        
        echo json_encode([
            'success' => true,
            'user' => $user_data
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
}
?>
