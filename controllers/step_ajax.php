<?php
/**
 * Production Step AJAX Controller
 * Handles AJAX requests for production step data
 */

// Start session first
session_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';
require_once '../includes/production_functions.php';

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Get the action from request
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_step_data':
        handle_get_step_data();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
}

/**
 * Handle getting step data by ID
 */
function handle_get_step_data() {
    global $pdo;
    
    // Get and validate step ID
    $step_id = (int) ($_GET['step_id'] ?? 0);
    
    if ($step_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid step ID']);
        return;
    }
    
    try {
        // Get step data using existing function
        $step_data = getProductionStepById($pdo, $step_id);
        
        if (!$step_data) {
            http_response_code(404);
            echo json_encode(['error' => 'Production step not found']);
            return;
        }
        
        // Parse and validate JSON data
        $parsed_data = json_decode($step_data['data_json'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // If JSON is invalid, create a simple representation
            $parsed_data = [
                'raw_data' => $step_data['data_json'],
                'parse_error' => 'Invalid JSON format'
            ];
        }
        
        // Prepare response with all step information
        $response = [
            'success' => true,
            'step_info' => [
                'step_id' => $step_data['step_id'],
                'step_name' => $step_data['step_name'],
                'rocket_id' => $step_data['rocket_id'],
                'rocket_serial' => $step_data['rocket_serial'],
                'staff_name' => $step_data['staff_full_name'],
                'timestamp' => $step_data['step_timestamp'],
                'formatted_timestamp' => date('F j, Y g:i A', strtotime($step_data['step_timestamp']))
            ],
            'data_fields' => $parsed_data,
            'field_count' => is_array($parsed_data) ? count($parsed_data) : 1
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        error_log("Step AJAX error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
}
?>
