<?php
/**
 * Approval Functions
 * Contains all approval-related logic and database operations
 * Handles production step approval workflow
 */

/**
 * Submit an approval for a production step
 * Uses database transaction to ensure data consistency
 * 
 * @param PDO $pdo Database connection
 * @param int $step_id Production step ID to approve/reject
 * @param int $engineer_id ID of the engineer making the approval
 * @param string $status 'approved' or 'rejected'
 * @param string $comments Engineer's comments on the approval
 * @return bool True on success, false on failure
 */
function submitApproval($pdo, $step_id, $engineer_id, $status, $comments) {
    try {
        // Start database transaction
        $pdo->beginTransaction();
        
        // Validate input parameters
        if (!is_numeric($step_id) || !is_numeric($engineer_id)) {
            throw new Exception("Invalid step_id or engineer_id");
        }
        
        if (!in_array($status, ['approved', 'rejected'])) {
            throw new Exception("Status must be 'approved' or 'rejected'");
        }
        
        // Get step details and associated rocket information
        $stmt = $pdo->prepare("
            SELECT ps.step_name, ps.rocket_id, r.serial_number 
            FROM production_steps ps 
            JOIN rockets r ON ps.rocket_id = r.rocket_id 
            WHERE ps.step_id = ?
        ");
        $stmt->execute([$step_id]);
        $step_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$step_info) {
            throw new Exception("Production step not found");
        }
        
        // Verify engineer exists and has proper role
        $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ? AND role IN ('engineer', 'admin')");
        $stmt->execute([$engineer_id]);
        $engineer = $stmt->fetch();
        
        if (!$engineer) {
            throw new Exception("Engineer not found or insufficient permissions");
        }
        
        // Insert approval record
        $stmt = $pdo->prepare("
            INSERT INTO approvals (step_id, engineer_id, status, comments, approval_timestamp) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$step_id, $engineer_id, $status, $comments]);
        
        // Update rocket status based on approval
        $new_status = ($status === 'approved') 
            ? "Step Approved: " . $step_info['step_name']
            : "Step Rejected: " . $step_info['step_name'];
            
        $stmt = $pdo->prepare("UPDATE rockets SET current_status = ? WHERE rocket_id = ?");
        $stmt->execute([$new_status, $step_info['rocket_id']]);
        
        // Commit transaction
        $pdo->commit();
        
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        error_log("Approval submission failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get approval history for a specific production step
 * 
 * @param PDO $pdo Database connection
 * @param int $step_id Production step ID
 * @return array Array of approval records or empty array
 */
function getApprovalHistoryForStep($pdo, $step_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                a.approval_id,
                a.step_id,
                a.engineer_id,
                a.status,
                a.comments,
                a.approval_timestamp,
                u.full_name AS engineer_name,
                u.username AS engineer_username
            FROM approvals a
            JOIN users u ON a.engineer_id = u.user_id
            WHERE a.step_id = ?
            ORDER BY a.approval_timestamp DESC
        ");
        $stmt->execute([$step_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error fetching approval history: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all pending approvals for an engineer
 * 
 * @param PDO $pdo Database connection
 * @param int $engineer_id Engineer's user ID (optional, if null returns all pending)
 * @return array Array of pending production steps awaiting approval
 */
function getPendingApprovals($pdo, $engineer_id = null) {
    try {
        // Get production steps that don't have approvals yet
        $sql = "
            SELECT 
                ps.step_id,
                ps.step_name,
                ps.step_timestamp,
                ps.rocket_id,
                r.serial_number,
                r.project_name,
                u.full_name AS staff_name,
                u.username AS staff_username
            FROM production_steps ps
            JOIN rockets r ON ps.rocket_id = r.rocket_id
            JOIN users u ON ps.staff_id = u.user_id
            LEFT JOIN approvals a ON ps.step_id = a.step_id
            WHERE a.step_id IS NULL
            ORDER BY ps.step_timestamp ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error fetching pending approvals: " . $e->getMessage());
        return [];
    }
}

/**
 * Get approval statistics for dashboard
 * 
 * @param PDO $pdo Database connection
 * @return array Statistics about approvals
 */
function getApprovalStatistics($pdo) {
    try {
        $stats = [];
        
        // Count total approvals
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM approvals");
        $stmt->execute();
        $stats['total_approvals'] = $stmt->fetchColumn();
        
        // Count approved vs rejected
        $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM approvals GROUP BY status");
        $stmt->execute();
        $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['approved_count'] = 0;
        $stats['rejected_count'] = 0;
        
        foreach ($status_counts as $status) {
            if ($status['status'] === 'approved') {
                $stats['approved_count'] = $status['count'];
            } elseif ($status['status'] === 'rejected') {
                $stats['rejected_count'] = $status['count'];
            }
        }
        
        // Count pending approvals
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as pending
            FROM production_steps ps
            LEFT JOIN approvals a ON ps.step_id = a.step_id
            WHERE a.step_id IS NULL
        ");
        $stmt->execute();
        $stats['pending_count'] = $stmt->fetchColumn();
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error fetching approval statistics: " . $e->getMessage());
        return [
            'total_approvals' => 0,
            'approved_count' => 0,
            'rejected_count' => 0,
            'pending_count' => 0
        ];
    }
}

/**
 * Check if a step has been approved/rejected
 * 
 * @param PDO $pdo Database connection
 * @param int $step_id Production step ID
 * @return array|false Approval record if exists, false if not approved yet
 */
function getStepApprovalStatus($pdo, $step_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                a.status,
                a.comments,
                a.approval_timestamp,
                u.full_name AS engineer_name
            FROM approvals a
            JOIN users u ON a.engineer_id = u.user_id
            WHERE a.step_id = ?
            ORDER BY a.approval_timestamp DESC
            LIMIT 1
        ");
        $stmt->execute([$step_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error checking step approval status: " . $e->getMessage());
        return false;
    }
}

/**
 * Search and filter pending approvals
 * 
 * @param PDO $pdo Database connection
 * @param string $search_term Search term for rocket serial, project name, step name, or staff name
 * @param string $step_filter Filter by step type
 * @param string $rocket_filter Filter by rocket ID
 * @param string $staff_filter Filter by staff member ID
 * @param string $date_from Filter by date from (YYYY-MM-DD)
 * @param string $date_to Filter by date to (YYYY-MM-DD)
 * @param string $sort_by Sort field (step_timestamp, serial_number, step_name, staff_name)
 * @param string $sort_order Sort order (ASC or DESC)
 * @param int $engineer_id Engineer's user ID (optional, if null returns all pending)
 * @return array Array of filtered pending approvals
 */
function search_pending_approvals($pdo, $search_term = '', $step_filter = '', $rocket_filter = '', $staff_filter = '', $date_from = '', $date_to = '', $sort_by = 'step_timestamp', $sort_order = 'DESC', $engineer_id = null) {
    try {
        // Build the WHERE clause
        $where_conditions = [];
        $params = [];
        
        // Base condition - only get steps that don't have approvals yet
        $where_conditions[] = "ps.step_id NOT IN (SELECT step_id FROM approvals)";
        
        // Search term for rocket serial, project name, step name, or staff name
        if (!empty($search_term)) {
            $where_conditions[] = "(r.serial_number LIKE ? OR r.project_name LIKE ? OR ps.step_name LIKE ? OR u.full_name LIKE ?)";
            $search_param = '%' . $search_term . '%';
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        // Step type filter
        if (!empty($step_filter)) {
            $where_conditions[] = "ps.step_name = ?";
            $params[] = $step_filter;
        }
        
        // Rocket filter
        if (!empty($rocket_filter)) {
            $where_conditions[] = "ps.rocket_id = ?";
            $params[] = $rocket_filter;
        }
        
        // Staff filter
        if (!empty($staff_filter)) {
            $where_conditions[] = "ps.staff_id = ?";
            $params[] = $staff_filter;
        }
        
        // Date range filter
        if (!empty($date_from)) {
            $where_conditions[] = "DATE(ps.step_timestamp) >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "DATE(ps.step_timestamp) <= ?";
            $params[] = $date_to;
        }
        
        // Build the complete query
        $sql = "SELECT 
                    ps.step_id,
                    ps.rocket_id,
                    ps.step_name,
                    ps.step_timestamp,
                    ps.staff_id,
                    r.serial_number,
                    r.project_name,
                    u.full_name AS staff_name,
                    u.username AS staff_username
                FROM production_steps ps
                INNER JOIN rockets r ON ps.rocket_id = r.rocket_id
                INNER JOIN users u ON ps.staff_id = u.user_id";
        
        if (!empty($where_conditions)) {
            $sql .= " WHERE " . implode(' AND ', $where_conditions);
        }
        
        // Add sorting
        $allowed_sort_fields = ['step_timestamp', 'serial_number', 'step_name', 'staff_name'];
        $sort_by = in_array($sort_by, $allowed_sort_fields) ? $sort_by : 'step_timestamp';
        $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';
        
        // Map sort field to actual column
        $sort_field_map = [
            'step_timestamp' => 'ps.step_timestamp',
            'serial_number' => 'r.serial_number',
            'step_name' => 'ps.step_name',
            'staff_name' => 'u.full_name'
        ];
        
        $sql .= " ORDER BY " . $sort_field_map[$sort_by] . " " . $sort_order;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Search pending approvals error: " . $e->getMessage());
        return array();
    }
}

/**
 * Get unique step types from pending approvals
 * 
 * @param PDO $pdo Database connection
 * @return array Array of unique step types
 */
function get_pending_step_types($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT ps.step_name 
            FROM production_steps ps 
            WHERE ps.step_id NOT IN (SELECT step_id FROM approvals)
            ORDER BY ps.step_name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log("Get pending step types error: " . $e->getMessage());
        return array();
    }
}

/**
 * Get rockets that have pending approvals
 * 
 * @param PDO $pdo Database connection
 * @return array Array of rockets with pending approvals
 */
function get_rockets_with_pending_approvals($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT r.rocket_id, r.serial_number, r.project_name 
            FROM production_steps ps 
            INNER JOIN rockets r ON ps.rocket_id = r.rocket_id
            WHERE ps.step_id NOT IN (SELECT step_id FROM approvals)
            ORDER BY r.serial_number
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get rockets with pending approvals error: " . $e->getMessage());
        return array();
    }
}

/**
 * Get staff members who have steps pending approval
 * 
 * @param PDO $pdo Database connection
 * @return array Array of staff members with pending approvals
 */
function get_staff_with_pending_approvals($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.user_id, u.full_name, u.username 
            FROM production_steps ps 
            INNER JOIN users u ON ps.staff_id = u.user_id
            WHERE ps.step_id NOT IN (SELECT step_id FROM approvals)
            ORDER BY u.full_name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get staff with pending approvals error: " . $e->getMessage());
        return array();
    }
}
?>
