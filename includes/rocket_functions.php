<?php
/**
 * Rocket Functions
 * Contains all rocket-related logic and database operations
 */

/**
 * Get all rockets from the database
 * 
 * @param PDO $pdo Database connection
 * @return array Array of all rockets or empty array on failure
 */
function get_all_rockets($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT rocket_id, serial_number, project_name, current_status, created_at FROM rockets ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get all rockets error: " . $e->getMessage());
        return array();
    }
}

/**
 * Get rocket by ID
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @return array|false Rocket data or false if not found
 */
function get_rocket_by_id($pdo, $rocket_id) {
    try {
        $stmt = $pdo->prepare("SELECT rocket_id, serial_number, project_name, current_status, created_at FROM rockets WHERE rocket_id = ?");
        $stmt->execute([$rocket_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get rocket by ID error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get rocket by serial number
 * 
 * @param PDO $pdo Database connection
 * @param string $serial_number Rocket serial number
 * @return array|false Rocket data or false if not found
 */
function get_rocket_by_serial($pdo, $serial_number) {
    try {
        $stmt = $pdo->prepare("SELECT rocket_id, serial_number, project_name, current_status, created_at FROM rockets WHERE serial_number = ?");
        $stmt->execute([$serial_number]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get rocket by serial error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create new rocket
 * 
 * @param PDO $pdo Database connection
 * @param string $serial_number Unique serial number
 * @param string $project_name Project name
 * @param string $current_status Initial status
 * @return int|false New rocket ID or false on failure
 */
function create_rocket($pdo, $serial_number, $project_name, $current_status = 'New') {
    try {
        $stmt = $pdo->prepare("INSERT INTO rockets (serial_number, project_name, current_status) VALUES (?, ?, ?)");
        $stmt->execute([$serial_number, $project_name, $current_status]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Create rocket error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update rocket status with audit trail
 * 
 * This function updates a rocket's status and logs the change for audit purposes.
 * The entire operation is wrapped in a database transaction to ensure data integrity.
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @param string $new_status New status
 * @param int $user_id ID of user making the change
 * @param string $change_reason Reason for the status change (required for audit)
 * @return array Result array with 'success' boolean, 'message' string, and optional 'log_id'
 */
function update_rocket_status($pdo, $rocket_id, $new_status, $user_id, $change_reason) {
    // Include log functions for audit trail
    require_once __DIR__ . '/log_functions.php';
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Input validation
        if (empty($rocket_id) || !is_numeric($rocket_id)) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Invalid rocket ID provided',
                'log_id' => null
            ];
        }
        
        if (empty(trim($new_status))) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'New status cannot be empty',
                'log_id' => null
            ];
        }
        
        if (empty($user_id) || !is_numeric($user_id)) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Invalid user ID provided',
                'log_id' => null
            ];
        }
        
        if (empty(trim($change_reason))) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Change reason is required for audit purposes',
                'log_id' => null
            ];
        }
        
        // Sanitize inputs
        $rocket_id = (int) $rocket_id;
        $user_id = (int) $user_id;
        $new_status = trim($new_status);
        $change_reason = trim($change_reason);
        
        // Step 1: Fetch current rocket status
        $current_stmt = $pdo->prepare("SELECT current_status FROM rockets WHERE rocket_id = ?");
        $current_stmt->execute([$rocket_id]);
        $rocket_data = $current_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rocket_data) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Rocket not found',
                'log_id' => null
            ];
        }
        
        $previous_status = $rocket_data['current_status'];
        
        // Check if status is actually changing
        if ($previous_status === $new_status) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'New status is the same as current status',
                'log_id' => null
            ];
        }
        
        // Step 2: Update rocket status
        $update_stmt = $pdo->prepare("UPDATE rockets SET current_status = ? WHERE rocket_id = ?");
        $update_result = $update_stmt->execute([$new_status, $rocket_id]);
        
        if (!$update_result) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Failed to update rocket status',
                'log_id' => null
            ];
        }
        
        // Step 3: Create audit log entry
        $log_result = createStatusChangeLog(
            $pdo,
            $rocket_id,
            $user_id,
            $previous_status,
            $new_status,
            $change_reason
        );
        
        if (!$log_result['success']) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Status updated but failed to create audit log: ' . $log_result['message'],
                'log_id' => null
            ];
        }
        
        // Step 4: Commit transaction if both operations succeeded
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Rocket status updated successfully and logged for audit',
            'log_id' => $log_result['log_id'],
            'previous_status' => $previous_status,
            'new_status' => $new_status
        ];
        
    } catch (PDOException $e) {
        // Rollback transaction on database error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Update rocket status error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Database error occurred while updating rocket status',
            'log_id' => null
        ];
    } catch (Exception $e) {
        // Rollback transaction on any other error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Unexpected error in update_rocket_status: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An unexpected error occurred',
            'log_id' => null
        ];
    }
}

/**
 * DEPRECATED: Legacy update rocket status function
 * 
 * This is a backward-compatible wrapper for the old update_rocket_status function.
 * It uses a default user and change reason for systems that haven't been updated yet.
 * 
 * @deprecated Use update_rocket_status() with full parameters instead
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @param string $new_status New status
 * @return bool True on success, false on failure
 */
function update_rocket_status_legacy($pdo, $rocket_id, $new_status) {
    // Use system user (ID 1) and generic reason for legacy calls
    $result = update_rocket_status(
        $pdo,
        $rocket_id,
        $new_status,
        $_SESSION['user_id'] ?? 1, // Use session user or default to admin
        'Status updated via legacy system call'
    );
    
    return $result['success'];
}

/**
 * Update rocket information
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @param string $serial_number Serial number
 * @param string $project_name Project name
 * @param string $current_status Current status
 * @return bool True on success, false on failure
 */
function update_rocket($pdo, $rocket_id, $serial_number, $project_name, $current_status) {
    try {
        $stmt = $pdo->prepare("UPDATE rockets SET serial_number = ?, project_name = ?, current_status = ? WHERE rocket_id = ?");
        return $stmt->execute([$serial_number, $project_name, $current_status, $rocket_id]);
    } catch (PDOException $e) {
        error_log("Update rocket error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete rocket
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @return bool True on success, false on failure
 */
function delete_rocket($pdo, $rocket_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM rockets WHERE rocket_id = ?");
        return $stmt->execute([$rocket_id]);
    } catch (PDOException $e) {
        error_log("Delete rocket error: " . $e->getMessage());
        return false;
    }
}

/**
 * Count total rockets
 * 
 * @param PDO $pdo Database connection
 * @return int Number of rockets
 */
function count_rockets($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rockets");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Count rockets error: " . $e->getMessage());
        return 0;
    }
}
?>
