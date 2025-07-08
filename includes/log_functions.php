<?php
/**
 * Log Functions for DPTI Rocket System
 * Handles audit trail and logging functionality
 * 
 * This file contains functions for managing audit logs,
 * particularly for tracking rocket status changes.
 * 
 * @version 1.0
 * @created July 8, 2025
 */

/**
 * Create a status change log entry for audit trail
 * 
 * This function records every rocket status change in the rocket_status_logs table
 * to maintain data integrity and provide an audit trail for compliance purposes.
 * 
 * @param PDO $pdo Database connection object
 * @param int $rocket_id The ID of the rocket whose status changed
 * @param int $user_id The ID of the user making the status change
 * @param string $previous_status The previous status value
 * @param string $new_status The new status value
 * @param string $change_reason The reason for the status change (required for audit)
 * @return array Result array with 'success' boolean and 'message' string
 * 
 * @throws PDOException If database operation fails
 * 
 * @example
 * $result = createStatusChangeLog($pdo, 1, 5, 'In Development', 'In Production', 'All tests passed');
 * if ($result['success']) {
 *     echo "Status change logged successfully";
 * }
 */
function createStatusChangeLog($pdo, $rocket_id, $user_id, $previous_status, $new_status, $change_reason) {
    try {
        // Input validation
        if (empty($rocket_id) || !is_numeric($rocket_id)) {
            return [
                'success' => false,
                'message' => 'Invalid rocket ID provided',
                'log_id' => null
            ];
        }
        
        if (empty($user_id) || !is_numeric($user_id)) {
            return [
                'success' => false,
                'message' => 'Invalid user ID provided',
                'log_id' => null
            ];
        }
        
        if (empty(trim($previous_status))) {
            return [
                'success' => false,
                'message' => 'Previous status cannot be empty',
                'log_id' => null
            ];
        }
        
        if (empty(trim($new_status))) {
            return [
                'success' => false,
                'message' => 'New status cannot be empty',
                'log_id' => null
            ];
        }
        
        if (empty(trim($change_reason))) {
            return [
                'success' => false,
                'message' => 'Change reason is required for audit purposes',
                'log_id' => null
            ];
        }
        
        // Sanitize inputs
        $rocket_id = (int) $rocket_id;
        $user_id = (int) $user_id;
        $previous_status = trim($previous_status);
        $new_status = trim($new_status);
        $change_reason = trim($change_reason);
        
        // Check if rocket exists
        $rocket_check_sql = "SELECT rocket_id FROM rockets WHERE rocket_id = :rocket_id";
        $rocket_stmt = $pdo->prepare($rocket_check_sql);
        $rocket_stmt->bindParam(':rocket_id', $rocket_id, PDO::PARAM_INT);
        $rocket_stmt->execute();
        
        if ($rocket_stmt->rowCount() === 0) {
            return [
                'success' => false,
                'message' => 'Rocket not found in database',
                'log_id' => null
            ];
        }
        
        // Check if user exists
        $user_check_sql = "SELECT user_id FROM users WHERE user_id = :user_id";
        $user_stmt = $pdo->prepare($user_check_sql);
        $user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $user_stmt->execute();
        
        if ($user_stmt->rowCount() === 0) {
            return [
                'success' => false,
                'message' => 'User not found in database',
                'log_id' => null
            ];
        }
        
        // Prepare the INSERT statement
        $sql = "INSERT INTO rocket_status_logs 
                (rocket_id, user_id, previous_status, new_status, change_reason, changed_at) 
                VALUES 
                (:rocket_id, :user_id, :previous_status, :new_status, :change_reason, NOW())";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind parameters with explicit types for security
        $stmt->bindParam(':rocket_id', $rocket_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':previous_status', $previous_status, PDO::PARAM_STR);
        $stmt->bindParam(':new_status', $new_status, PDO::PARAM_STR);
        $stmt->bindParam(':change_reason', $change_reason, PDO::PARAM_STR);
        
        // Execute the statement
        $result = $stmt->execute();
        
        if ($result) {
            $log_id = $pdo->lastInsertId();
            return [
                'success' => true,
                'message' => 'Status change logged successfully',
                'log_id' => $log_id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to insert status change log',
                'log_id' => null
            ];
        }
        
    } catch (PDOException $e) {
        // Log the error for debugging (in production, log to file instead of exposing)
        error_log("Database error in createStatusChangeLog(): " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Database error occurred while logging status change',
            'log_id' => null
        ];
    } catch (Exception $e) {
        // Handle any other unexpected errors
        error_log("Unexpected error in createStatusChangeLog(): " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'An unexpected error occurred',
            'log_id' => null
        ];
    }
}

/**
 * Get status change logs for a specific rocket
 * 
 * Retrieves all status change logs for a given rocket with user details
 * 
 * @param PDO $pdo Database connection object
 * @param int $rocket_id The ID of the rocket
 * @param int $limit Maximum number of logs to retrieve (default: 50)
 * @return array Array of log entries with user and rocket details
 */
function getRocketStatusLogs($pdo, $rocket_id, $limit = 50) {
    try {
        $sql = "SELECT 
                    rsl.log_id,
                    rsl.rocket_id,
                    r.serial_number,
                    r.project_name,
                    rsl.user_id,
                    u.username,
                    u.full_name,
                    u.role,
                    rsl.previous_status,
                    rsl.new_status,
                    rsl.change_reason,
                    rsl.changed_at
                FROM rocket_status_logs rsl
                INNER JOIN rockets r ON rsl.rocket_id = r.rocket_id
                INNER JOIN users u ON rsl.user_id = u.user_id
                WHERE rsl.rocket_id = :rocket_id
                ORDER BY rsl.changed_at DESC
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':rocket_id', $rocket_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Database error in getRocketStatusLogs(): " . $e->getMessage());
        return [];
    }
}

/**
 * Get recent status change logs across all rockets
 * 
 * Retrieves recent status changes with pagination support
 * 
 * @param PDO $pdo Database connection object
 * @param int $limit Maximum number of logs to retrieve (default: 20)
 * @param int $offset Offset for pagination (default: 0)
 * @return array Array of recent log entries
 */
function getRecentStatusLogs($pdo, $limit = 20, $offset = 0) {
    try {
        $sql = "SELECT 
                    rsl.log_id,
                    rsl.rocket_id,
                    r.serial_number,
                    r.project_name,
                    rsl.user_id,
                    u.username,
                    u.full_name,
                    u.role,
                    rsl.previous_status,
                    rsl.new_status,
                    rsl.change_reason,
                    rsl.changed_at
                FROM rocket_status_logs rsl
                INNER JOIN rockets r ON rsl.rocket_id = r.rocket_id
                INNER JOIN users u ON rsl.user_id = u.user_id
                ORDER BY rsl.changed_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Database error in getRecentStatusLogs(): " . $e->getMessage());
        return [];
    }
}

/**
 * Get status change statistics
 * 
 * Returns statistics about status changes for dashboard/reporting
 * 
 * @param PDO $pdo Database connection object
 * @param string $period Time period ('today', 'week', 'month', 'all')
 * @return array Statistics array
 */
function getStatusChangeStatistics($pdo, $period = 'month') {
    try {
        $date_condition = '';
        switch ($period) {
            case 'today':
                $date_condition = 'AND DATE(rsl.changed_at) = CURDATE()';
                break;
            case 'week':
                $date_condition = 'AND rsl.changed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
                break;
            case 'month':
                $date_condition = 'AND rsl.changed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
            case 'all':
            default:
                $date_condition = '';
                break;
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_changes,
                    COUNT(DISTINCT rsl.rocket_id) as rockets_affected,
                    COUNT(DISTINCT rsl.user_id) as users_involved,
                    MIN(rsl.changed_at) as earliest_change,
                    MAX(rsl.changed_at) as latest_change
                FROM rocket_status_logs rsl
                WHERE 1=1 {$date_condition}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Database error in getStatusChangeStatistics(): " . $e->getMessage());
        return [
            'total_changes' => 0,
            'rockets_affected' => 0,
            'users_involved' => 0,
            'earliest_change' => null,
            'latest_change' => null
        ];
    }
}

/**
 * Validate status change parameters before logging
 * 
 * Helper function to validate all parameters before creating a log entry
 * 
 * @param int $rocket_id Rocket ID to validate
 * @param int $user_id User ID to validate
 * @param string $previous_status Previous status to validate
 * @param string $new_status New status to validate
 * @param string $change_reason Change reason to validate
 * @return array Validation result with 'valid' boolean and 'errors' array
 */
function validateStatusChangeParameters($rocket_id, $user_id, $previous_status, $new_status, $change_reason) {
    $errors = [];
    
    // Validate rocket_id
    if (!is_numeric($rocket_id) || $rocket_id <= 0) {
        $errors[] = 'Rocket ID must be a positive integer';
    }
    
    // Validate user_id
    if (!is_numeric($user_id) || $user_id <= 0) {
        $errors[] = 'User ID must be a positive integer';
    }
    
    // Validate previous_status
    if (empty(trim($previous_status))) {
        $errors[] = 'Previous status cannot be empty';
    } elseif (strlen(trim($previous_status)) > 100) {
        $errors[] = 'Previous status cannot exceed 100 characters';
    }
    
    // Validate new_status
    if (empty(trim($new_status))) {
        $errors[] = 'New status cannot be empty';
    } elseif (strlen(trim($new_status)) > 100) {
        $errors[] = 'New status cannot exceed 100 characters';
    }
    
    // Validate change_reason
    if (empty(trim($change_reason))) {
        $errors[] = 'Change reason is required for audit purposes';
    } elseif (strlen(trim($change_reason)) > 65535) { // TEXT field limit
        $errors[] = 'Change reason is too long (maximum 65535 characters)';
    }
    
    // Check if statuses are the same
    if (trim($previous_status) === trim($new_status)) {
        $errors[] = 'Previous status and new status cannot be the same';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}
?>
