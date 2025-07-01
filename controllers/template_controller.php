<?php
/**
 * Template Controller
 * Handles all template management operations
 */

// Start session first
session_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';
require_once '../includes/template_functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../views/login_view.php');
    exit;
}

// Get the action from POST or GET
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        handle_list_templates();
        break;
    case 'add':
        handle_add_template();
        break;
    case 'edit':
        handle_edit_template();
        break;
    case 'delete':
        handle_delete_template();
        break;
    case 'toggle_status':
        handle_toggle_template_status();
        break;
    default:
        // Invalid action - redirect to list
        header('Location: ../views/templates_list_view.php?error=invalid_action');
        exit;
}

/**
 * Handle displaying template list
 */
function handle_list_templates() {
    // Check permissions (admin or engineer can manage templates)
    if (!has_role('admin') && !has_role('engineer')) {
        header('Location: ../dashboard.php?error=insufficient_permissions');
        exit;
    }
    
    // Redirect to the list view (this function is called when action=list)
    header('Location: ../views/templates_list_view.php');
    exit;
}

/**
 * Handle adding a new template
 */
function handle_add_template() {
    global $pdo;
    
    // Check permissions (admin or engineer can add templates)
    if (!has_role('admin') && !has_role('engineer')) {
        header('Location: ../dashboard.php?error=insufficient_permissions');
        exit;
    }
    
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../views/template_add_view.php?error=invalid_method');
        exit;
    }
    
    // Get and validate form data
    $step_name = trim($_POST['step_name'] ?? '');
    $step_description = trim($_POST['step_description'] ?? '');
    $created_by = $_SESSION['user_id'] ?? 0;
    
    // Validate required fields
    if (empty($step_name)) {
        redirect_with_error('missing_fields', $step_name, $step_description);
        return;
    }
    
    // Check if template name already exists
    if (templateNameExists($pdo, $step_name)) {
        redirect_with_error('template_exists', $step_name, $step_description);
        return;
    }
    
    // Create the template (this function needs to be implemented)
    $template_id = createTemplate($pdo, $step_name, $step_description, $created_by);
    
    if ($template_id) {
        // Success - redirect to template list with success message
        header('Location: ../views/templates_list_view.php?success=template_created&template_id=' . $template_id);
        exit;
    } else {
        // Failed to create template
        redirect_with_error('creation_failed', $step_name, $step_description);
        return;
    }
}

/**
 * Handle editing an existing template
 */
function handle_edit_template() {
    global $pdo;
    
    // Check permissions (admin or engineer can edit templates)
    if (!has_role('admin') && !has_role('engineer')) {
        header('Location: ../dashboard.php?error=insufficient_permissions');
        exit;
    }
    
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../views/templates_list_view.php?error=invalid_method');
        exit;
    }
    
    // Get template ID
    $template_id = (int) ($_POST['template_id'] ?? 0);
    if ($template_id <= 0) {
        header('Location: ../views/templates_list_view.php?error=invalid_template_id');
        exit;
    }
    
    // Get and validate form data
    $step_name = trim($_POST['step_name'] ?? '');
    $step_description = trim($_POST['step_description'] ?? '');
    
    // Validate required fields
    if (empty($step_name)) {
        header('Location: ../views/template_edit_view.php?id=' . $template_id . '&error=missing_fields');
        exit;
    }
    
    // Check if template exists
    $existing_template = getTemplateWithFields($pdo, $template_id);
    if (!$existing_template) {
        header('Location: ../views/templates_list_view.php?error=template_not_found');
        exit;
    }
    
    // Check if template name is being changed and if new name already exists
    if ($step_name !== $existing_template['step_name']) {
        if (templateNameExists($pdo, $step_name, $template_id)) {
            header('Location: ../views/template_edit_view.php?id=' . $template_id . '&error=template_exists');
            exit;
        }
    }
    
    // Update the template (this function needs to be implemented)
    $update_result = updateTemplate($pdo, $template_id, $step_name, $step_description);
    
    if ($update_result) {
        header('Location: ../views/templates_list_view.php?success=template_updated&template_id=' . $template_id);
        exit;
    } else {
        header('Location: ../views/template_edit_view.php?id=' . $template_id . '&error=update_failed');
        exit;
    }
}

/**
 * Handle deleting a template
 */
function handle_delete_template() {
    global $pdo;
    
    // Check permissions (only admin can delete templates)
    if (!has_role('admin')) {
        header('Location: ../dashboard.php?error=insufficient_permissions');
        exit;
    }
    
    // Get template ID
    $template_id = (int) ($_POST['template_id'] ?? $_GET['template_id'] ?? 0);
    if ($template_id <= 0) {
        header('Location: ../views/templates_list_view.php?error=invalid_template_id');
        exit;
    }
    
    // Check if template exists
    $template = getTemplateWithFields($pdo, $template_id);
    if (!$template) {
        header('Location: ../views/templates_list_view.php?error=template_not_found');
        exit;
    }
    
    // Delete the template (this function needs to be implemented)
    $delete_result = deleteTemplate($pdo, $template_id);
    
    if ($delete_result) {
        header('Location: ../views/templates_list_view.php?success=template_deleted');
        exit;
    } else {
        header('Location: ../views/templates_list_view.php?error=delete_failed');
        exit;
    }
}

/**
 * Handle toggling template active status
 */
function handle_toggle_template_status() {
    global $pdo;
    
    // Check permissions (admin or engineer can toggle status)
    if (!has_role('admin') && !has_role('engineer')) {
        header('Location: ../dashboard.php?error=insufficient_permissions');
        exit;
    }
    
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../views/templates_list_view.php?error=invalid_method');
        exit;
    }
    
    // Get data
    $template_id = (int) ($_POST['template_id'] ?? 0);
    $new_status = (int) ($_POST['new_status'] ?? 1);
    
    if ($template_id <= 0) {
        header('Location: ../views/templates_list_view.php?error=missing_data');
        exit;
    }
    
    // Update status (this function needs to be implemented)
    $update_result = updateTemplateStatus($pdo, $template_id, $new_status);
    
    if ($update_result) {
        header('Location: ../views/templates_list_view.php?success=status_updated');
        exit;
    } else {
        header('Location: ../views/templates_list_view.php?error=status_update_failed');
        exit;
    }
}

/**
 * Helper function to redirect with error and preserve form data
 */
function redirect_with_error($error, $step_name = '', $step_description = '') {
    $params = http_build_query([
        'error' => $error,
        'step_name' => $step_name,
        'step_description' => $step_description
    ]);
    
    header('Location: ../views/template_add_view.php?' . $params);
    exit;
}

// All template CRUD functions are now implemented in template_functions.php

?>
