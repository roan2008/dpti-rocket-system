<?php
/**
 * Production Functions
 * Contains all production steps-related logic and database operations
 */

/**
 * Get all production steps for a specific rocket
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @return array Array of production steps with staff information or empty array on failure
 */
function getStepsByRocketId($pdo, $rocket_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ps.step_id,
                ps.rocket_id,
                ps.step_name,
                ps.data_json,
                ps.staff_id,
                ps.step_timestamp,
                u.full_name as staff_full_name,
                u.username as staff_username
            FROM production_steps ps
            INNER JOIN users u ON ps.staff_id = u.user_id
            WHERE ps.rocket_id = ?
            ORDER BY ps.step_timestamp DESC
        ");
        
        $stmt->execute([$rocket_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Get steps by rocket ID error: " . $e->getMessage());
        return array();
    }
}

/**
 * Add a new production step and update rocket status
 * Uses PDO transaction for data integrity
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @param string $step_name Name of the production step
 * @param string $data_json JSON data containing step details
 * @param int $staff_id ID of staff member recording the step
 * @return int|false New step ID on success, false on failure
 */
function addProductionStep($pdo, $rocket_id, $step_name, $data_json, $staff_id) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert new production step
        $stmt = $pdo->prepare("
            INSERT INTO production_steps (rocket_id, step_name, data_json, staff_id, step_timestamp) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([$rocket_id, $step_name, $data_json, $staff_id]);
        
        if (!$result) {
            throw new Exception("Failed to insert production step");
        }
        
        $step_id = $pdo->lastInsertId();
        
        // Update rocket status based on step name
        $new_status = generateStatusFromStep($step_name);
        $update_stmt = $pdo->prepare("
            UPDATE rockets 
            SET current_status = ? 
            WHERE rocket_id = ?
        ");
        
        $update_result = $update_stmt->execute([$new_status, $rocket_id]);
        
        if (!$update_result) {
            throw new Exception("Failed to update rocket status");
        }
        
        // Commit transaction
        $pdo->commit();
        
        return $step_id;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        error_log("Add production step error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate rocket status based on production step name
 * 
 * @param string $step_name Name of the production step
 * @return string Generated status
 */
function generateStatusFromStep($step_name) {
    // Map common step names to appropriate statuses
    $step_status_mapping = [
        'Design Review' => 'Design',
        'Material Preparation' => 'In Production',
        'Component Assembly' => 'In Production',
        'Quality Check' => 'Testing',
        'Final Inspection' => 'Testing',
        'Launch Preparation' => 'Completed',
        'System Test' => 'Testing',
        'Integration Test' => 'Testing'
    ];
    
    // Check if step name matches a predefined mapping
    if (isset($step_status_mapping[$step_name])) {
        return $step_status_mapping[$step_name];
    }
    
    // Default: Create status with step name
    return 'Step: ' . $step_name . ' Recorded';
}

/**
 * Get production step by ID
 * 
 * @param PDO $pdo Database connection
 * @param int $step_id Production step ID
 * @return array|false Step data or false if not found
 */
function getProductionStepById($pdo, $step_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ps.step_id,
                ps.rocket_id,
                ps.step_name,
                ps.data_json,
                ps.staff_id,
                ps.step_timestamp,
                u.full_name as staff_full_name,
                u.username as staff_username,
                r.serial_number as rocket_serial,
                r.project_name as rocket_project
            FROM production_steps ps
            INNER JOIN users u ON ps.staff_id = u.user_id
            INNER JOIN rockets r ON ps.rocket_id = r.rocket_id
            WHERE ps.step_id = ?
        ");
        
        $stmt->execute([$step_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Get production step by ID error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update production step data
 * 
 * @param PDO $pdo Database connection
 * @param int $step_id Production step ID
 * @param string $step_name Updated step name
 * @param string $data_json Updated JSON data
 * @return bool True on success, false on failure
 */
function updateProductionStep($pdo, $step_id, $step_name, $data_json) {
    try {
        $stmt = $pdo->prepare("
            UPDATE production_steps 
            SET step_name = ?, data_json = ? 
            WHERE step_id = ?
        ");
        
        return $stmt->execute([$step_name, $data_json, $step_id]);
        
    } catch (PDOException $e) {
        error_log("Update production step error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete production step with business logic validation
 * 
 * @param PDO $pdo Database connection
 * @param int $step_id Production step ID
 * @return array Result array with success status and message
 */
function deleteProductionStep($pdo, $step_id) {
    try {
        // Start transaction for data integrity
        $pdo->beginTransaction();
        
        // First check if step exists
        $step = getProductionStepById($pdo, $step_id);
        if (!$step) {
            $pdo->rollBack();
            return [
                'success' => false,
                'error' => 'step_not_found',
                'message' => 'Production step not found'
            ];
        }
        
        // Check if step has associated approvals
        require_once 'approval_functions.php';
        $approval_status = getStepApprovalStatus($pdo, $step_id);
        
        if ($approval_status !== false) {
            $pdo->rollBack();
            return [
                'success' => false,
                'error' => 'has_approvals',
                'message' => 'Cannot delete a step that has been approved. Steps with approval records must be kept for audit purposes.',
                'approval_info' => $approval_status
            ];
        }
        
        // Check if this is the only step for the rocket
        $rocket_step_count = countStepsByRocketId($pdo, $step['rocket_id']);
        if ($rocket_step_count <= 1) {
            // Update rocket status back to initial state
            $stmt = $pdo->prepare("UPDATE rockets SET current_status = 'Planning' WHERE rocket_id = ?");
            $stmt->execute([$step['rocket_id']]);
        }
        
        // Delete the step
        $delete_stmt = $pdo->prepare("DELETE FROM production_steps WHERE step_id = ?");
        $delete_result = $delete_stmt->execute([$step_id]);
        
        if (!$delete_result) {
            $pdo->rollBack();
            return [
                'success' => false,
                'error' => 'delete_failed',
                'message' => 'Failed to delete production step'
            ];
        }
        
        // Commit transaction
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Production step deleted successfully',
            'deleted_step' => [
                'step_id' => $step_id,
                'step_name' => $step['step_name'],
                'rocket_id' => $step['rocket_id']
            ]
        ];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Delete production step error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'database_error',
            'message' => 'Database error occurred while deleting step'
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Delete production step error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'unknown_error',
            'message' => 'An unexpected error occurred'
        ];
    }
}

/**
 * Get count of production steps for a rocket
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @return int Number of production steps
 */
function countStepsByRocketId($pdo, $rocket_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM production_steps WHERE rocket_id = ?");
        $stmt->execute([$rocket_id]);
        return (int) $stmt->fetchColumn();
        
    } catch (PDOException $e) {
        error_log("Count steps error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get latest production step for a rocket
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @return array|false Latest step data or false if no steps found
 */
function getLatestStepByRocketId($pdo, $rocket_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ps.step_id,
                ps.rocket_id,
                ps.step_name,
                ps.data_json,
                ps.staff_id,
                ps.step_timestamp,
                u.full_name as staff_full_name
            FROM production_steps ps
            INNER JOIN users u ON ps.staff_id = u.user_id
            WHERE ps.rocket_id = ?
            ORDER BY ps.step_timestamp DESC
            LIMIT 1
        ");
        
        $stmt->execute([$rocket_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Get latest step error: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate JSON data for production step
 * 
 * @param string $json_data JSON string to validate
 * @return array|false Decoded data array or false if invalid
 */
function validateStepJsonData($json_data) {
    if (empty($json_data)) {
        return array(); // Empty data is valid
    }
    
    $decoded = json_decode($json_data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON validation error: " . json_last_error_msg());
        return false;
    }
    
    return $decoded;
}

/**
 * Create standardized JSON data for common production steps
 * 
 * @param string $step_name Name of the production step
 * @param array $additional_data Additional data to include
 * @return string JSON formatted data
 */
function createStepJsonData($step_name, $additional_data = array()) {
    $base_data = [
        'step_name' => $step_name,
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'completed'
    ];
    
    // Merge with additional data
    $full_data = array_merge($base_data, $additional_data);
    
    return json_encode($full_data, JSON_PRETTY_PRINT);
}

/**
 * Get all production steps across all rockets
 * 
 * @param PDO $pdo Database connection
 * @return array Array of all production steps with staff and rocket information
 */
function getAllProductionSteps($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ps.step_id,
                ps.rocket_id,
                ps.step_name,
                ps.data_json,
                ps.staff_id,
                ps.step_timestamp,
                u.full_name as staff_full_name,
                u.username as staff_username,
                r.serial_number as rocket_serial,
                r.project_name as rocket_project
            FROM production_steps ps
            INNER JOIN users u ON ps.staff_id = u.user_id
            INNER JOIN rockets r ON ps.rocket_id = r.rocket_id
            ORDER BY ps.step_timestamp DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Get all production steps error: " . $e->getMessage());
        return array();
    }
}
?>
