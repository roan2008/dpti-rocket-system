<?php
/**
 * Template AJAX Endpoint
 * Returns template field configuration as JSON for dynamic form generation
 */

// Set JSON header first
header('Content-Type: application/json');

try {
    // Include required files
    require_once '../includes/db_connect.php';
    require_once '../includes/template_functions.php';
    
    // Validate and get template_id from GET request
    $template_id = $_GET['template_id'] ?? '';
    
    // Validate that template_id is a positive integer
    if (!is_numeric($template_id) || (int)$template_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid template ID. Must be a positive integer.',
            'provided_value' => $template_id
        ]);
        exit;
    }
    
    $template_id = (int)$template_id;
    
    // Fetch template with fields from database
    $template_data = getTemplateWithFields($pdo, $template_id);
    
    // Check if template was found
    if (!$template_data) {
        http_response_code(404);
        echo json_encode([
            'error' => 'Template not found',
            'template_id' => $template_id
        ]);
        exit;
    }
    
    // Check if template is active
    if (!$template_data['is_active']) {
        http_response_code(403);
        echo json_encode([
            'error' => 'Template is inactive',
            'template_id' => $template_id,
            'step_name' => $template_data['step_name']
        ]);
        exit;
    }
    
    // Return successful response with template data
    echo json_encode([
        'success' => true,
        'template_id' => $template_data['template_id'],
        'step_name' => $template_data['step_name'],
        'step_description' => $template_data['step_description'],
        'fields' => $template_data['fields'],
        'field_count' => count($template_data['fields'])
    ]);
    
} catch (PDOException $e) {
    // Database error
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'message' => 'Please try again later'
    ]);
    error_log("Template AJAX PDO Error: " . $e->getMessage());
    
} catch (Exception $e) {
    // General error
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error occurred',
        'message' => 'Please try again later'
    ]);
    error_log("Template AJAX Error: " . $e->getMessage());
}
?>
