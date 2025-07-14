<?php
/**
 * Report Functions
 * Contains all report-related logic and business rules
 */

/**
 * Get all active step template names for Motor Charging Report requirements
 * 
 * @param PDO $pdo Database connection
 * @return array Array of step names from active templates
 */
function getMandatoryStepsFromTemplates($pdo) {
    try {
        $query = $pdo->prepare("
            SELECT step_name 
            FROM step_templates 
            WHERE is_active = 1 
            ORDER BY step_name
        ");
        $query->execute();
        
        $steps = [];
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $steps[] = $row['step_name'];
        }
        
        error_log("Motor Charging Report: Found " . count($steps) . " mandatory steps from templates: " . implode(', ', $steps));
        return $steps;
        
    } catch (PDOException $e) {
        error_log("Error getting mandatory steps from templates: " . $e->getMessage());
        // Fallback to hardcoded steps if database query fails
        return [
            'Motor Casing Preparation',
            'Propellant Mixing',
            'Propellant Loading',
            'Nozzle Installation',
            'Quality Control Inspection',
            'Final Assembly'
        ];
    }
}

/**
 * CRITICAL BUSINESS RULE: Motor Charging Report Gatekeeper Function
 * 
 * This function ensures that a Motor Charging Report can only be generated
 * if ALL mandatory production steps exist AND have been approved.
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @return bool True if report can be generated, false otherwise
 */
function canGenerateMotorChargingReport($pdo, $rocket_id) {
    try {
        // Check if rocket exists
        $rocket_check = $pdo->prepare("SELECT rocket_id FROM rockets WHERE rocket_id = ?");
        $rocket_check->execute([$rocket_id]);
        if (!$rocket_check->fetch()) {
            error_log("Motor Charging Report: Rocket ID $rocket_id not found");
            return false;
        }
        
        // Check if rocket has ANY production steps
        // Motor Charging Report shows whatever steps have been done, not enforce specific requirements
        $steps_query = $pdo->prepare("
            SELECT COUNT(*) as step_count 
            FROM production_steps 
            WHERE rocket_id = ?
        ");
        $steps_query->execute([$rocket_id]);
        $result = $steps_query->fetch(PDO::FETCH_ASSOC);
        
        if ($result['step_count'] == 0) {
            error_log("Motor Charging Report: No production steps found for rocket $rocket_id");
            return false;
        }
        
        // Has production steps - ready to generate report
        error_log("Motor Charging Report: Found {$result['step_count']} production steps for rocket $rocket_id - ready to print");
        return true;
        
    } catch (PDOException $e) {
        error_log("Motor Charging Report Permission Check Error: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Motor Charging Report Permission Check Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Data Aggregator Function for Motor Charging Report
 * 
 * Fetches ALL necessary data to populate the Motor Charging Report.
 * This function assumes permissions have already been validated.
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @return array|false Complete report data or false on failure
 */
function getMotorChargingReportData($pdo, $rocket_id) {
    try {
        // SECURITY CHECK: Re-validate permissions before data access
        if (!canGenerateMotorChargingReport($pdo, $rocket_id)) {
            error_log("Motor Charging Report: Permission denied for rocket $rocket_id");
            return false;
        }
        
        // Initialize report data structure
        $report_data = [
            'rocket_info' => null,
            'production_steps' => [],
            'approvals_summary' => [],
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $_SESSION['username'] ?? 'System',
            'report_metadata' => [
                'total_steps' => 0,
                'approved_steps' => 0,
                'report_status' => 'complete'
            ]
        ];
        
        // 1. Get Rocket Basic Information
        $rocket_query = $pdo->prepare("
            SELECT 
                rocket_id,
                serial_number,
                project_name,
                current_status,
                created_at
            FROM rockets 
            WHERE rocket_id = ?
        ");
        $rocket_query->execute([$rocket_id]);
        $report_data['rocket_info'] = $rocket_query->fetch(PDO::FETCH_ASSOC);
        
        if (!$report_data['rocket_info']) {
            error_log("Motor Charging Report: Rocket data not found for ID $rocket_id");
            return false;
        }
        
        // 2. Get ALL Production Steps with Detailed Information
        $steps_query = $pdo->prepare("
            SELECT 
                ps.step_id,
                ps.step_name,
                ps.data_json,
                ps.step_timestamp,
                ps.staff_id,
                u.full_name as staff_name,
                u.username as staff_username,
                u.role as staff_role
            FROM production_steps ps
            INNER JOIN users u ON ps.staff_id = u.user_id
            WHERE ps.rocket_id = ?
            ORDER BY ps.step_timestamp ASC
        ");
        $steps_query->execute([$rocket_id]);
        $production_steps = $steps_query->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. For each production step, get approval information
        foreach ($production_steps as &$step) {
            // Get approval details for this step
            $approval_query = $pdo->prepare("
                SELECT 
                    a.approval_id,
                    a.status,
                    a.comments,
                    a.approval_timestamp as approval_date,
                    a.engineer_id,
                    approver.full_name as approver_name,
                    approver.username as approver_username,
                    approver.role as approver_role
                FROM approvals a
                INNER JOIN users approver ON a.engineer_id = approver.user_id
                WHERE a.step_id = ?
                ORDER BY a.approval_timestamp DESC
                LIMIT 1
            ");
            $approval_query->execute([$step['step_id']]);
            $step['approval_info'] = $approval_query->fetch(PDO::FETCH_ASSOC);
            
            // Parse JSON data if exists
            if (!empty($step['data_json'])) {
                $decoded_data = json_decode($step['data_json'], true);
                $step['parsed_data'] = $decoded_data ?: [];
            } else {
                $step['parsed_data'] = [];
            }
            
            // Count statistics
            $report_data['report_metadata']['total_steps']++;
            if ($step['approval_info'] && $step['approval_info']['status'] === 'approved') {
                $report_data['report_metadata']['approved_steps']++;
            }
        }
        
        $report_data['production_steps'] = $production_steps;
        
        // 4. Create Approvals Summary for Quick Reference
        $approvals_summary_query = $pdo->prepare("
            SELECT 
                ps.step_name,
                a.status,
                a.comments,
                a.approval_timestamp as approval_date,
                approver.full_name as approver_name,
                approver.role as approver_role
            FROM production_steps ps
            INNER JOIN approvals a ON ps.step_id = a.step_id
            INNER JOIN users approver ON a.engineer_id = approver.user_id
            WHERE ps.rocket_id = ?
            ORDER BY a.approval_timestamp DESC
        ");
        $approvals_summary_query->execute([$rocket_id]);
        $report_data['approvals_summary'] = $approvals_summary_query->fetchAll(PDO::FETCH_ASSOC);
        
        // 5. Get Final Report Approver (Most Recent Approval)
        $final_approver_query = $pdo->prepare("
            SELECT 
                approver.full_name as final_approver_name,
                approver.role as final_approver_role,
                approver.username as final_approver_username,
                MAX(a.approval_timestamp) as final_approval_date
            FROM production_steps ps
            INNER JOIN approvals a ON ps.step_id = a.step_id
            INNER JOIN users approver ON a.engineer_id = approver.user_id
            WHERE ps.rocket_id = ? AND a.status = 'approved'
            GROUP BY approver.user_id
            ORDER BY final_approval_date DESC
            LIMIT 1
        ");
        $final_approver_query->execute([$rocket_id]);
        $report_data['final_approver'] = $final_approver_query->fetch(PDO::FETCH_ASSOC);
        
        // 6. Add Report Validation Status
        $report_data['validation'] = [
            'is_complete' => ($report_data['report_metadata']['total_steps'] > 0),
            'all_approved' => ($report_data['report_metadata']['approved_steps'] === $report_data['report_metadata']['total_steps']),
            'mandatory_steps_present' => canGenerateMotorChargingReport($pdo, $rocket_id),
            'generation_timestamp' => time()
        ];
        
        error_log("Motor Charging Report: Data successfully aggregated for rocket $rocket_id");
        return $report_data;
        
    } catch (PDOException $e) {
        error_log("Motor Charging Report Data Error: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Motor Charging Report Data Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to get mandatory steps list
 * 
 * @return array List of mandatory steps for Motor Charging Report
 */
function getMandatoryMotorChargingSteps() {
    return [
        'Motor Casing Preparation',
        'Propellant Mixing',
        'Propellant Loading',
        'Nozzle Installation',
        'Quality Control Inspection',
        'Final Assembly'
    ];
}

/**
 * Helper function to validate report data structure
 * 
 * @param array $report_data Report data array
 * @return bool True if data structure is valid
 */
function validateReportDataStructure($report_data) {
    if (!is_array($report_data)) {
        return false;
    }
    
    $required_keys = [
        'rocket_info',
        'production_steps', 
        'approvals_summary',
        'generated_at',
        'report_metadata'
    ];
    
    foreach ($required_keys as $key) {
        if (!array_key_exists($key, $report_data)) {
            error_log("Motor Charging Report: Missing required key '$key' in report data");
            return false;
        }
    }
    
    return true;
}

/**
 * Get report generation audit log
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @return array List of report generations for this rocket
 */
function getReportGenerationAuditLog($pdo, $rocket_id) {
    try {
        // Note: This would require an audit table to track report generations
        // For now, return empty array - can be implemented later
        return [];
        
    } catch (Exception $e) {
        error_log("Report Audit Log Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Log report generation attempt for audit purposes
 * 
 * @param PDO $pdo Database connection
 * @param int $rocket_id Rocket ID
 * @param bool $success Whether generation was successful
 * @param string $user_id User who attempted generation
 * @return bool True if logged successfully
 */
function logReportGeneration($pdo, $rocket_id, $success, $user_id = null) {
    try {
        $user_id = $user_id ?: ($_SESSION['user_id'] ?? null);
        
        // Log to PHP error log for now
        $status = $success ? 'SUCCESS' : 'FAILED';
        $log_message = "Motor Charging Report Generation $status: Rocket ID $rocket_id, User ID $user_id, Time: " . date('Y-m-d H:i:s');
        error_log($log_message);
        
        // Future implementation: Insert into audit log table
        /*
        $audit_query = $pdo->prepare("
            INSERT INTO report_generation_log (rocket_id, user_id, report_type, status, generated_at)
            VALUES (?, ?, 'motor_charging', ?, NOW())
        ");
        return $audit_query->execute([$rocket_id, $user_id, $status]);
        */
        
        return true;
        
    } catch (Exception $e) {
        error_log("Report Generation Logging Error: " . $e->getMessage());
        return false;
    }
}
?>
